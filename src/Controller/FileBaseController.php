<?php

namespace App\Controller;

use Exception;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\UploadedFile;

class FileBaseController
{
    public Container $container;

    public Request $request;

    public function __construct(Container $container)
    {
        $this->request = $container->get('request');
        $this->container = $container;

    }

    public function errorResponse(Response $response, string $message, int $status = 400): Response
    {
        return $response->withStatus($status)->withJson(['error' => $message]);
    }

    /**
     * @throws Exception
     */
    public function saveFile(Request $request, $directory): string
    {
        $directory = ROOT_DIR.'/public/' . $directory;
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['file'];
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new Exception('error happended');
        }
        $filename = $this->moveUploadedFile($directory, $uploadedFile);
        return $filename;
    }

    /**
     * Moves the uploaded file to the upload directory and assigns it a unique name
     * to avoid overwriting an existing uploaded file.
     * @param string $directory directory to which the file is moved
     * @param UploadedFile $uploadedFile file uploaded file to move
     * @return string filename of moved file
     */
    private function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }
}
