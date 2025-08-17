<?php

namespace App\Controller;

use App\Service\AppConstants;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Slim\Http\UploadedFile;

class FileController extends FileBaseController
{
    public function saveFileAction(Request $request, Response $response)
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->getUploadedFiles()['media'];
        $fileType = $request->getQueryParam('type', 'photo');

        $parsedBody = $request->getParsedBody();
        $dirToUpload = $this->getUploadDir($fileType);

        $tempDirToUpload = TEMP_DIR;

        if ((bool)$parsedBody['multipart'] !== true) {
            $filePath = $this->handleSingleFile($dirToUpload, $uploadedFile);
            return $response->withJson(['file_name' => $filePath]);
        }

        $fileName    = $parsedBody['filename'];
        $chunkNumber = $parsedBody['currentPart'];
        $totalChunks = $parsedBody['totalParts'];


        $chunkFilePath = $tempDirToUpload . $fileName . '_' . $chunkNumber;

        $uploadedFile->moveTo($chunkFilePath);

        if ($chunkNumber != $totalChunks) {
            return $response->withJson(['chunk' => 'ok']);
        }

        $newFileName = bin2hex(random_bytes(5)). $fileName;

        $targetFile = fopen($dirToUpload . DIRECTORY_SEPARATOR . $newFileName, 'w');

        for ($i = 1; $i <= $totalChunks; $i++) {
            $chunkFilePath = $tempDirToUpload . $fileName . '_' . $i;
            $chunkData = file_get_contents($chunkFilePath);
            fwrite($targetFile, $chunkData);
            unlink($chunkFilePath);
        }

        fclose($targetFile);
        return $response->withJson(['file_name' => $newFileName]);
    }

    private function getUploadDir(string $type = 'image'): string
    {
        $uploadDir = ($type == 'video') ? UPLOAD_DIR.AppConstants::UPLOAD_DIR_VIDEO : UPLOAD_DIR.AppConstants::UPLOAD_DIR_ROW_PHOTO;

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir);
        }

        return $uploadDir.DIRECTORY_SEPARATOR;
    }


    private function handleSingleFile($dirToUpload, UploadedFileInterface $uploadedFile): string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filePath = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($dirToUpload . $filePath);

        // Check if the file is an image (you can extend this check based on file types you support)
        // if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) {
        //     $this->resizeImage($dirToUpload . $filePath, 800, 600); // Example resizing to 800x600
        // }

        return $filePath;
    }

}
