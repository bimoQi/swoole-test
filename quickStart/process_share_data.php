<?php

/**
 * 进程间共享数据
 */

$serv = new Swoole\Server('127.0.0.1', 9907);

$serv->set([
    'worker_num' => 4,
]);

// $fds = [];
// $serv->on('connect', function ($serv, $fd) {
//     echo 'connect:' . $fd . PHP_EOL;
//     global $fds; // 这种方式不可以，进程隔离了 不在一个内存块
//     $fds[] = $fd;
//     var_dump($fds);
// });

// 使用swoole/table -- 相当于数据库中的表
$table = new Swoole\Table(1024);
$table->column('fd', Swoole\Table::TYPE_STRING, 10);
$table->create();
$table->set(1, []);

$serv->on('connect', function ($serv, $fd) use ($table) {
    echo 'connect:' . $fd . PHP_EOL;
    $old_fd = $table->get('1');
    if (!empty($old_fd['fd'])) {
        $new_fd = $old_fd['fd'] . '-' . $fd;
    } else {
        $new_fd = $fd;
    }
    $table->set('1', ['fd' => $new_fd]);
    var_dump($table->get('1'));
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    echo 'Client msg:' . $data . PHP_EOL;
    echo $from_id;
    $serv->send($fd, 'Server:' . $data);
});

$serv->start();
