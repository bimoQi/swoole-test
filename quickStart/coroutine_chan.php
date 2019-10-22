<?php

//协程通信-管道 channel

/**
 * 第一个go 用来循环获取chan里的数据， 下面几个go分别获取http状态码push到chan，协程1拿到数据
 * 这里的chan实现了协程并发
 */
$chan = new chan(2);

go(function () use ($chan) {
    $res = [];
    for ($i = 0; $i < 3; $i++) {
        $res += $chan->pop();
    }
    var_dump($res);
});

go(function () use ($chan) {
    $cli = new Swoole\Coroutine\Http\Client('www.qq.com', 80);
    $cli->set(['timeout' => 10]);
    $cli->setHeaders([
        'Host' => 'www.qq.com',
        'User-Agent' => 'Chrome',
        'Accept' => 'text/html,application/xml',
        'Accept-Encoding' => 'gzip',
    ]);
    $res = $cli->get('/');
    $chan->push(['www.qq.com' => $cli->statusCode]);
});

go(function () use ($chan) {
    $cli = new Swoole\Coroutine\Http\Client('www.163.com', 80);
    $cli->set(['timeout' => 10]);
    $cli->setHeaders([
        'Host' => 'www.163.com',
        'User-Agent' => 'Chrome',
        'Accept' => 'text/html,application/xml',
        'Accept-Encoding' => 'gzip',
    ]);
    $res = $cli->get('/');
    var_dump($cli->recv);
    $chan->push(['www.163.com' => $cli->statusCode]);
});


go(function () use ($chan) {
    $cli = new Swoole\Coroutine\Http\Client('www.baidu.com', 80);
    $cli->set(['timeout' => 10]);
    $cli->setHeaders([
        'Host' => 'www.baidu.com',
        'User-Agent' => 'Chrome',
        'Accept' => 'text/html,application/xml',
        'Accept-Encoding' => 'gzip',
    ]);
    $res = $cli->get('/');
    $chan->push(['www.baidu.com' => $cli->statusCode]);
});
