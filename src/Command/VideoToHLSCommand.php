<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use App\Service\AppConstants;

class VideoToHLSCommand extends Command
{
    protected static $defaultName = 'app:video-to-hls';

    protected function configure()
    {
        $this->setDescription('Converts a video to HLS format.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $finder = new Finder();
        $finder->files()->in(VIDEO_JOBS_DIR);

        foreach ($finder as $file) {
            $video = $file;
            break;
        }

        if (!$video) {
            return Command::SUCCESS;
        }

        $videoPath = $video->getFilename();
        $videoPath = UPLOAD_DIR.AppConstants::UPLOAD_DIR_VIDEO.'/'.$videoPath;

        // Check if the input video file exists
        if (!file_exists($videoPath)) {
            unlink($video->getRealPath());
            $output->writeln("<error>Video file not found: $videoPath</error>");
            return Command::FAILURE;
        }

        $outputPath = "/var/www/assets/videos/hls/";

        // Run the command using Symfony's Process component
	$process = new Process($command);
	$process->setTimeout(3000);
        $process->run();

        if (!$process->isSuccessful()) {
            $output->writeln("<error>FFmpeg command failed: " . $process->getErrorOutput() . "</error>");
            return Command::FAILURE;
        }

        unlink($video->getRealPath());

        $output->writeln("<info>Video converted to HLS successfully! Files saved to $outputPath</info>");
        return Command::SUCCESS;
    }
}
