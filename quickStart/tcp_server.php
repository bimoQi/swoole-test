<?php
$serv = new Swoole\Server('0.0.0.0', 9901); //默认不设置其他参数就是tcp服务


/**
 * tcp
 * on 可选types
 * onConnect，onClose，onReceive，onBufferFull，onBufferEmpty       --不区分大小写
 */
//监听连接
$serv->on('conNect', function ($serv, $fd) { //fd 是客户端连接的唯一标识符
    echo 'Client: connect' . PHP_EOL;
});

//监听接收
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    echo 'Client msg:' . $data . PHP_EOL;
    echo $from_id;
    $serv->send($fd, 'Server:' . $data);
});

//监听关闭
$serv->on('close', function ($serv, $fd) {
    echo 'Client: close' . PHP_EOL;
});

// 启动服务
$serv->start();
