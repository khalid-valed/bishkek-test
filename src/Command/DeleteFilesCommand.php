<?php

namespace App\Command;

use App\Service\AppConstants;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

class DeleteFilesCommand extends Command
{
    protected static $defaultName = 'app:delete-files';

    private $client;

    private const URL = 'https://aws-s3.main/connector/assets-remove';

    public function __construct()
    {
        parent::__construct();
        $this->client = HttpClient::create();
    }

    protected function configure()
    {
        $this->setDescription('Deletes image and video files from the server.');
    }

    private function getUploadDir(string $type = 'photo'): string
    {
        $uploadDir = ($type == 'video') ? UPLOAD_DIR.AppConstants::UPLOAD_DIR_VIDEO : UPLOAD_DIR.AppConstants::UPLOAD_DIR_PHOTO;

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir);
        }

        return $uploadDir.DIRECTORY_SEPARATOR;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
	$response = $this->client->request('GET', self::URL); 

        $data = $response->toArray();

        $images = $data['images'] ?? [];
        $videos = $data['videos'] ?? [];

        // Define paths
        $imageFolderPath = $this->getUploadDir('photo');
        $videoFolderPath = $this->getUploadDir('video');

        // Delete image files
        foreach ($images as $image) {
            $imagePath = $imageFolderPath . $image;
            if (file_exists($imagePath)) {
                unlink($imagePath);
                $output->writeln("Deleted image: $image");
            } else {
                $output->writeln("Image not found: $image");
            }
        }

        // Delete video files
        foreach ($videos as $video) {
            $videoPath = $videoFolderPath . $video;
            if (file_exists($videoPath)) {
                unlink($videoPath);
                $output->writeln("Deleted video: $video");
            } else {
                $output->writeln("Video not found: $video");
            }
        }

        return Command::SUCCESS;
    }
}
