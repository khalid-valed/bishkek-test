<?php

// Controllers
$container['BaseController'] = function ($c) {
    return new \App\Controller\BaseController($c);
};

$container['FileController'] = function ($c) {
    return new \App\Controller\FileController($c);
};

$container['IKabarJobsController'] = function ($c) {
    return new \App\Controller\IKabarJobsController($c);
};
