<?php
/**
 * web socket
 * on 可选types
 * onMessage, onOpen
 */

$ws = new Swoole\WebSocket\Server('0.0.0.0', 9904);

$ws->on('open', function ($ws, $request) {
    var_dump($request->fd, $request->get, $request->server);
    $ws->push($request->fd, 'hello ws' . PHP_EOL);
});

$ws->on('message', function ($ws, $frame) {
    echo 'message:' . PHP_EOL;
    $ws->push($frame->fd, 'server:' . $frame->data);
});

$ws->on('close', function ($ws, $fd) {
    echo 'client-' . $fd . ' is closed' . PHP_EOL;
});

$ws->start();




/*  js代码 
var wsServer = 'ws://127.0.0.1:9904';
var websocket = new WebSocket(wsServer);
websocket.onopen = function (evt) {
    console.log("Connected to WebSocket server.");
};

websocket.onclose = function (evt) {
    console.log("Disconnected");
};

websocket.onmessage = function (evt) {
    console.log('Retrieved data from server: ' + evt.data);
};

websocket.onerror = function (evt, e) {
    console.log('Error occured: ' + evt.data);
};
*/