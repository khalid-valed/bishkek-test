<?php

namespace App\Command;

use App\Model\Job;
use DfTools\SlimOrm\DB;
use Google\Client;
use Google\Service\Drive as GoogleDriveService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:download-drive-file',
    description: 'Download a Google Drive file using Google API',
)]

#     +---------------------------------------------------+
#     | Google Drive Folder: " FOO_BAR-row-videos"        |
#     +---------------------------------------------------+
#                     |
#                     v
#     +-------------------------------------------+
#     | Clients make payment and upload video/photo |
#     +-------------------------------------------+
#                     |
#                     v
#     +------------------------------------------------+
#     | Agents preview videos and create job by form   |
#     +------------------------------------------------+
#                     |
#                     v
#     +-------------------------------------------------------------+
#     | Job tries to download video to /var/www/assets              |
#     +-------------------------------------------------------------+
#                     |
#              +----------- Yes ------------+
#              |                             |
#              v                             v
#     +-------------------------+     +----------------------------------+
#     | Download Success        |     | Download Failed                 |
#     | - Remove orig file      |     | - Mark job as "download_failed" |
#     | from Google Drive       |     | - Notify admin                  |
#     +-------------------------+     +----------------------------------+
#              |
#              v
#     +----------------------------------------------------+
#     | Cron Job parses files in /var/www/foo-bar-videos/  |
#     +----------------------------------------------------+
#              |
#              v
#     +------------------------------------------------------------+
#     | Move parsed videos to /var/assets/aws                      |
#     | (generate AWS segments)                                    |
#     +------------------------------------------------------------+
#              |
#              v
#     +-----------------------------------------------+
#     | Send POST request to create AWS               |
#     +-----------------------------------------------+
#              |
#         +-------- Success --------+
#         |                         |
#         v                         v
#     +-------------------+    +----------------------------------+
#     | Mark job as done   |   | POST request failed              |
#     | Status: "done"     |   | - Retry X times                  |
#     +-------------------+    | - If still fails:                |
#                              |    - Mark job as "post_failed"   |
#                              |    - Notify admin                |
#                              +----------------------------------+
#

class FileDownloaderCommand extends Command
{
    protected static $defaultName = 'app:process-video';

    private const STATUS_CREATED = 'CREATED';
    private const STATUS_DOWNLOADING = 'DOWNLOADING';
    private const STATUS_DOWNLOADED = 'DOWNLOADED';
    private const STATUS_CHUNKING = 'CHUNKING';
    private const STATUS_CHUNKED = 'CHUNKED';

    private const CHUNK_OUTPUT_DIR = '/var/www/assets/videos/aws/';

    private const API_SERVER = 'https://aws.s3bucket-main.com/api';

    protected function configure(): void
    {
        $this
            ->setDescription('Download or chunk videos from Google Drive.')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: download or chunk');

        $pdo = new \PDO('sqlite:' . ROOT_DIR . '/database/jobs.db');
        DB::init($pdo);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');

        return match ($action) {
            'download' => $this->downloadFile($io),
            'chunk'    => $this->chunkVideo($output),
            default    => $this->invalidAction($io),
        };
    }

    private function invalidAction(SymfonyStyle $io): int
    {
        $io->error('Invalid action. Use "download" or "chunk" or "send".');
        return Command::INVALID;
    }

    private function downloadFile(SymfonyStyle $io): int
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        ignore_user_abort(true);

        $job = Job::where('status', self::STATUS_CREATED)->first();

        if (!$job) {
            $io->warning('No job with CREATED status found.');
            return Command::SUCCESS;
        }

        $job->status = self::STATUS_DOWNLOADING;
        $job->save();

	$client = $this->getClient();
	$client->authorize();
        $service = new GoogleDriveService($client);

        $fileId = $job->url;
        // @TODO Download via chunk
        $file = $service->files->get($fileId, ['fields' => 'name']);
        $fileName = $file->getName();

        $filePathnr = self::DOWNLOAD_DIR . '/' . $fileName;
        $resource = fopen($filePath, 'w');

        $io->section("Downloading {$fileName}...");

        $response = $service->files->get($fileId, ['alt' => 'media']);

        $body = $response->getBody();

        while (!$body->eof()) {
            fwrite($resource, $body->read(1024 * 1024));
        }

        fclose($resource);

        $job->status = self::STATUS_DOWNLOADED;
        $job->name = $fileName;
        $job->save();
        $io->success("File downloaded successfully to {$filePath}");
        //$service->files->delete($fileId);
        return Command::SUCCESS;
    }


    private function chunkVideo(OutputInterface $output): int
    {
        $job = Job::where('status', self::STATUS_DOWNLOADED)->first();
        if (!$job) {
            $output->writeln("<comment>No job with CREATED status found.</comment>");
            return Command::SUCCESS;
        }

        $job->status = self::STATUS_CHUNKING;
        $job->save();

        $videoName = $job->name();
        $videoPath = UPLOAD_DIR . AppConstants::UPLOAD_DIR_VIDEO . '/' . $videoName;

        if (!file_exists($videoPath)) {
            unlink($job->getRealPath());
            $output->writeln("<error>Video file not found: {$videoPath}</error>");
            return Command::FAILURE;
        }

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $output->writeln("<error> command failed: {$process->getErrorOutput()}</error>");
            return Command::FAILURE;
        }

        unlink($job->getRealPath());
        $output->writeln("<info>File saved successfully! Files saved to " . self::CHUNK_OUTPUT_DIR . "</info>");
        $job->status = self::STATUS_CHUNKED;
        $job->save();
        $this->postJobToApi($job);

        return Command::SUCCESS;
    }

    private function postJobToApi($job): void
    {
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('POST', self::API_SERVER, ['body' => $job->toArrya()]);

            if ($response->getStatusCode() === 200) {
                $job->status = "DONE";
            } else {
                $job->status = "error_on_post";
            }
            $job->save();
        } catch (\Exception $e) {
            dd($e);
        }
    }

    private function getClient()
    {
        $client = new Client();
        $client->setApplicationName('Google Drive API PHP Quickstart');
        $client->setScopes(GoogleDriveService::DRIVE);
        $client->setAuthConfig(ROOT_DIR.'/config/gmail/drive_credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $tokenPath = ROOT_DIR.'/config/gmail/drive-token.json';

        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);
                if (array_key_exists('error', $accessToken)) {
                    throw new \Exception(join(', ', $accessToken));
                }
            }
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        return $client;
    }
}
