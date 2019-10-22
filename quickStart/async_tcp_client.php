<?php

/**
 * 异步web socket client
 * 4个回调事件必须设置 onConnect、onError、onReceive、onClose。分
 */

$client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

$client->on('connect', function ($cli) {
    echo 'client connected' . PHP_EOL;
    $i = 0;
    while ($i < 20) {
        $i++;
        $cli->send('me hello world' . PHP_EOL);
    }
});

$client->on('receive', function ($client, $data) {
    echo 'Receive:'.PHP_EOL . $data . PHP_EOL;
});

$client->on('error', function ($client) {
    echo 'connect failed' . PHP_EOL;
});

$client->on('close', function () {
    var_dump(111);
    echo 'closed' . PHP_EOL;
});

if (!$client->connect('127.0.0.1', 9901, 0.5)) {
    exit('connect failed');
}
