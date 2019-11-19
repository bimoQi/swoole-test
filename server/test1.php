<?php

/**
 * params 3 server的运行模式
 *  SWOOLE_BASE 单线程模式
 *          没有master进程
 *          如果worker_num=1 并且没有使用task和max_request时，直接创建worker进程，不会创建manager进程
 *          适用于client连接不需用交互http服务
 *  SWOOLE_PROCESS 多进程模式 -默认
 *          创建master，mannager进程
 *          可以实现复杂场景，数据请求分发至workers中
 * params 4 指定socket类型 支持tcp，udp，tcp6，udp6，stream.
 */
$serv = new Swoole\Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
$serv->set([
    'worker_num' => 1, // Worker：接收reactor投递过来的数据包，php进行回调处理数据，返回Reactor，Reactor再返回给tcp客户端 -异步非阻塞 相当于php-fpm
    'reactor_num' => 1, // Reactor:负责维护客户端TCP连接、处理网络IO、处理协议、收发数据,不处理php代码，组包拆包-多线程 相当于nginx
    'task_worker_num' => 1, //TaskWorker：接收由worker投递过来的任务($serv->task())，处理结束后返回给worker($task->finish()) - 同步阻塞 相当于后台守护进程php处理队列数据
    'deamonize' => false,
    'backlog' => 128, //队列长度  最多同时有多少个等待accept的连接。
]);

// from_id 为来源于哪个worker进程
$serv->on('Task', function ($serv, $task_id, $from_id, $data) {
    $a = new Swoole\Atomic(0);
    echo 'atomic:' . $a->add() . PHP_EOL;
    echo 'taskid:' . $task_id . PHP_EOL;
    // sleep(1);
    (int) $from_id;
    echo "内存: " . memory_get_usage() . " B\n";
    $serv->finish($data);
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    echo 'receive ' . $data . PHP_EOL;
    $task_id = $serv->task($data, 0, function ($serv, $task_id) use ($fd) { // task方法可用以第三个参数 callback形式
        $serv->send($fd, '通知client 任务' . $task_id . '已经完成：' . PHP_EOL);
    });
    // sleep(1);
    $serv->send($fd, '分发任务完成 任务id：' . $task_id . PHP_EOL);
});

$serv->on('workerStart', function ($serv, $worker_id) {
    global $argv;
    // if($worker_id >= $serv->setting['worker_num']) {
    //     cli_set_process_title("php {$argv[0]}: task_worker");
    // } else {
    //     cli_set_process_title("php {$argv[0]}: worker");
    // }
});

// $timer_id = swoole_timer_tick(3000, function ($timer_id) {
//     echo "内存: " . memory_get_usage() . " B\n";
//     echo "峰值: " . memory_get_peak_usage() . " B\n";
// });
$serv->start();