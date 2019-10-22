<?php

/**
 * 执行异步任务，使用task worker 多个进程进行操作
 */
$serv = new Swoole\Server('127.0.0.1', 9905);

$serv->set([
    'task_worker_num' => 4, // 一共有个多个个task worker工作，若只有1个 就相当于投递到队列里慢慢处理，有多少个队列进行消费
]);

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    $task_id = $serv->task($data);
    echo 'dispath async task:' . $task_id . PHP_EOL;
});

$serv->on('task', function ($serv, $task_id, $from_id, $data) {
    echo 'new async task:' . $task_id . PHP_EOL;
    sleep(1);       // 测试多个请求与多个task worker处理方式
    $serv->finish($data . '->OK');
});

$serv->on('finish', function ($serv, $task_id, $data) {
    echo 'async task ' . $task_id . ' finished data:' . $data . PHP_EOL;
});

$serv->start();
