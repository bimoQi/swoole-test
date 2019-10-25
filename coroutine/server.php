<?php

$http = new Swoole\Http\Server('0.0.0.0', 9504);

$http->set([
    'worker_num' => 1,
    'max_coroutine' => 1000,
]);
$http->on('request', function ($req, $res) {
    // $client = new Swoole\Client(SWOOLE_SOCK_TCP);    // 没有使用协程, 速度是 sum(clent->recv 的time)
    $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP); // 使用协程，速度是 max(clent->recv 的time)
    $client->connect('127.0.0.1', 9501, 10);
    $client->send('hhhhh');
    $ret = $client->recv();
    $client->close();
    $res->end($ret);
});

$http->start();
