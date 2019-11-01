<?php
$serv = new Swoole\Server('0.0.0.0', 9902, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

/**
 * udp
 * on 可选types
 * onPacket,onReceive      udp与tcp不同，udp没有连接的概念，启动server后 client无需连接 直接向server端口发送数据包
 */

//监听接收
$serv->on('packet', function ($serv, $data, $clientInfo) {
    var_dump($clientInfo);
    $serv->sendto($clientInfo['address'], $clientInfo['port'], 'Server:' . $data);
});

// 启动服务
$serv->start();

//测试方法-使用udp方式  nc -u 127.0.0.1 9902
