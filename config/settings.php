<?php

define('ROOT_DIR', __DIR__ . '/../');
define('UPLOAD_DIR', '/var/www/assets/');
define('TEMP_DIR', ROOT_DIR.'public/tmp/');

(Dotenv\Dotenv::createImmutable(ROOT_DIR))->load();

$config['displayErrorDetails'] = ($_ENV['ENV'] === 'prod') ? false : true;
$config['addContentLengthHeader'] = false;
