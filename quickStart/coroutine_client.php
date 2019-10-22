<?php

/**
 * 协程client
 */

$http = new Swoole\Http\Server('0.0.0.0', 9908);
$http->on('request', function ($request, $response) {
    $db = new Swoole\Coroutine\MySQL();
    $db->connect([
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '',
        'database' => 'edusoho',
    ]);
    $data = $db->query('select sleep(3);');
    if ($data == false) {
        var_dump($db->errno, $db->error);
    }
    echo 1;
    $data2 = $db->query('show tables;');
    if ($data2 == false) {
        var_dump($db->errno, $db->error);
    }
    $response->end(json_encode($data));
});

$http->start();