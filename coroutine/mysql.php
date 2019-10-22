<?php

/**
 * 协程mysql-client
 * 协程必须用go方法
 * 协程的client 可以设置延迟机制 setdefer() - 先发送数据到client，再处理其他业务，最后使用$client->recv() 进行接收刚刚的响应内容
 * 协程的客户端内执行其实是同步的，不要理解为异步，它只是遇到IO阻塞时能让出执行权，切换到其他协程而已，不能和异步混淆。
 */

go(function () {
    $swoole_mysql1 = new Swoole\Coroutine\MySQL();
    $swoole_mysql1->connect([
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '',
        'database' => 'fishGame',
    ]);
    $swoole_mysql1->setDefer();
    $swoole_mysql2 = new Swoole\Coroutine\MySQL();
    $swoole_mysql2->connect([
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '',
        'database' => 'fishGame',
    ]);
    $swoole_mysql2->setDefer();
    
    $res1 = $swoole_mysql1->query('select sleep(2);');
    echo 'go 1' . PHP_EOL;
    $res2 = $swoole_mysql2->query('update user set uid = 1111 where id =1');
    echo 'go 2' . PHP_EOL;
    $swoole_mysql1->recv();     // 这个方法会阻塞等待
    $swoole_mysql2->recv();
});

$coros = Swoole\Coroutine::listCoroutines();
foreach ($coros as $cid) {
    // var_dump(\Swoole\Coroutine::getBackTrace($cid));
}
