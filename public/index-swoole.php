<?php

require __DIR__ . '/../bootstrap/app.php';

$bridgeManager = new \Pachico\SlimSwoole\BridgeManager($app);

$http = new swoole_http_server("0.0.0.0", 1337);


$http->set([
    'daemonize' => 1,
    'enable_static_handler' => false,
    // Logging
    'pid_file' => __DIR__.'/logs/server.pid',
    'log_file' => __DIR__.'/logs/server.log',
    /* 'document_root' => __DIR__ . '/web', */
]);


$http->on("start", function (\swoole_http_server $server) {
    echo sprintf('Swoole http server is started at http://%s:%s', $server->host, $server->port), PHP_EOL;
});

$http->on(
    "request",
    function (swoole_http_request $swooleRequest, swoole_http_response $swooleResponse) use ($bridgeManager) {
        $bridgeManager->process($swooleRequest, $swooleResponse)->end();
    }
);

$http->start();
