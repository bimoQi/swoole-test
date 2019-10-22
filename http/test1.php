<?php

$http = new Swoole\Http\Server('0.0.0.0', 9501);

$http->on('request', function ($req, $res) {
    $res->end('hello swoole' . PHP_EOL);
});

$http->start();
