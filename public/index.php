<?php

use App\Http\Middleware\CorsMiddleware;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../config/settings.php';


$app = new \Slim\App(['settings' => $config]);

$container = $app->getContainer();

$app->add(new CorsMiddleware());

require_once __DIR__ . '/../config/routes.php';
require_once __DIR__ . '/../config/dependency_provider.php';
$app->run();
