<?php
$server = new Swoole\WebSocket\Server("0.0.0.0", 9501);

$server->on('open', function (Swoole\WebSocket\Server $server, $request) {
    echo "server: handshake success with fd{$request->fd}\n";
});

$server->on('message', function (Swoole\WebSocket\Server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},finish:{$frame->finish}\n";
    $times = mt_rand(1, 10);
    for ($i = 0; $i < $times; $i++) {
        echo 'push is:' . ' times: ' . $i . ' time:' . date('Y-m-d H:i:s', time()) . PHP_EOL;
        $server->push($frame->fd, "this is server the times is : " . $i . " time:" . date('Y-m-d H:i:s', time()));
    }
});

$server->on('close', function ($ser, $fd) {
    echo "client {$fd} closed\n";
});

$server->start();
