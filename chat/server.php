<?php
$ws = new Swoole\WebSocket\Server('0.0.0.0', 4000);

$ws->on('open', function ($server, $request){
    echo "server: handshake success with fd {$request->fd}\n";
    $to_data = [
        'person_num' => count($server->connections)
    ];
    $server->push($request->fd, json_encode($to_data));
});

$ws->on('message', function ($server, $frame) {
    $recv_data = json_decode($frame->data, true);
    $to_data = [
        'person_num' => count($server->connections),
        'msg' => $recv_data['msg'],
        'name' => $recv_data['name'],
        'time' => $recv_data['time']
    ];
    foreach ($server->connections as $fd) {
        $server->push($fd, json_encode($to_data));
    }
});

$ws->on('close', function ($server, $fd) {
    echo 'client ' . $fd . ' close' . PHP_EOL;
});

$ws->start();
