<?php

/** @var Slim\App $app */

$app->post('/vko/media/files', \FileController::class . ':saveFileAction');

$app->get('/vko/media/ping', function ($request, $response) {
    return $response->withJson(['ping' => 'pong']);
});

$app->get('/vko/media/i-kabar-jobs', \IKabarJobsController::class.':getJobs');
$app->post('/vko/media/i-kabar-jobs', \IKabarJobsController::class.':createJobs');

$app->post('/vko/media/files-approved', function ($request, $response) {

    $files = $request->getParsedBody();
    $videoName = $files['vidoe'] ?? null;
    $imageNames = $files['images'] ?? [];
    $videoPath = VIDEO_JOBS_DIR;
    $imagePath = IMAGE_JOBS_DIR;

    if (!$videoName) {
        return $response->withStatus(400)->withJson(['error' => 'Video name is required']);
    }

    $videoFilePath = $videoPath  . $videoName;
    if (!touch($videoFilePath)) {
        return $response->withStatus(500)->withJson(['error' => 'Failed to create video file']);
    }

    $createdImages = [];
    foreach ($imageNames as $imageName) {
        $imageFilePath = $imagePath  . $imageName;
        if (touch($imageFilePath)) {
            $createdImages[] = $imageName;
        }
    }

    return $response->withJson([
        'message' => 'Files created successfully',
    ]);
});
