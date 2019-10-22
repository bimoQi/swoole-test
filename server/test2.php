<?php

/**
 *  使用addListener 可以监听多个端口，多种形式的socket  别名 listen()
 *  使用addProcess  用户自定义进程，特殊工作进程，用户监控，上报或其他特殊任务
 */
$serv = new Swoole\Server('0.0.0.0', 9501);

$serv->addListener('127.0.0.1', 9502, SWOOLE_SOCK_TCP);
$serv->listen('127.0.0.1', 9503, SWOOLE_SOCK_TCP);

// 特殊进程， 用户广播给其他用户
$process = new Swoole\Process(function ($process) use ($serv) {
    while (1) {
        $msg = $process->read();
        foreach ($serv->connections as $conn) {
            $serv->send($conn, $msg);
        }
    }
});
$serv->addProcess($process);

$serv->on('receive', function ($serv, $fd, $reactor_id, $data) use ($process) {
    echo 'receive fd:' . $fd . ' reactor_id:' . $reactor_id . ' data:' . $data . PHP_EOL;
    $serv->send($fd, 'server is:' . $data);
    $process->write($data);
});


$serv->start();
