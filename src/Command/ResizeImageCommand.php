<?php

namespace App\Command;

use App\Service\AppConstants;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:resize-image',
    description: 'Resize an image keeping the aspect ratio and add a watermark.',
)]
class ResizeImageCommand extends Command
{
    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetWidth = 900;
        $watermark = 'AWS S3';

        $finder = new Finder();
        $finder->files()->in(IMAGE_JOBS_DIR);

        foreach ($finder as $file) {

            $inputPath = $file->getFilename();

            $outputPath = UPLOAD_DIR.AppConstants::UPLOAD_DIR_PHOTO.'/'.$inputPath;

            $inputPath = UPLOAD_DIR.AppConstants::UPLOAD_DIR_ROW_PHOTO.'/'.$inputPath;

            if (!file_exists($inputPath)) {
                $output->writeln('<error>Input file does not exist.</error>');
                continue;
            }

            $imageInfo = getimagesize($inputPath);

            if ($imageInfo === false) {
                $output->writeln('<error>Invalid image file.</error>');
                continue;
            }

            [$originalWidth, $originalHeight] = $imageInfo;
            $aspectRatio = $originalHeight / $originalWidth;
            $targetHeight = (int) ($targetWidth * $aspectRatio);

            $image = $this->createImageFromPath($inputPath, $imageInfo[2]);

            if ($image === null) {
                $output->writeln('<error>Unsupported image type.</error>');
                return Command::FAILURE;
            }

            $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);

            imagecopyresampled(
                $resizedImage,
                $image,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $originalWidth,
                $originalHeight
            );

            if (!empty($watermark)) {
                $this->addWatermark($resizedImage, $watermark, $targetWidth, $targetHeight);
            }


            $this->saveImage($resizedImage, $outputPath, $imageInfo[2]);

            imagedestroy($image);
            imagedestroy($resizedImage);
            unlink($file->getRealPath());
        }

        $output->writeln('<info>Image resized and saved successfully.</info>');
        return Command::SUCCESS;
    }

    private function createImageFromPath(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            default => null,
        };
    }

    private function saveImage($image, string $path, int $type): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, 90),
            IMAGETYPE_PNG => imagepng($image, $path, 0),
            IMAGETYPE_GIF => imagegif($image, $path),
            default => throw new \RuntimeException('Unsupported image type'),
        };
    }

    private function addWatermark($image, string $text, int $width, int $height): void
    {
        $fontSize = 20;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);

        $x = $width - $textWidth - 10;
        $y = $height - $textHeight - 10;

        $textColor = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, $fontSize, $x, $y, $text, $textColor);
    }
}
