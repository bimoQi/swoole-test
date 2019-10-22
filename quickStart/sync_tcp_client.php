<?php

/**
 * 同步web socket client
 */
$client = new Swoole\Client(SWOOLE_SOCK_TCP);

if (!$client->connect('127.0.0.1', 9901, 0.5)) {
    exit('connect failed');
}
while (1) {
    if (!$client->send('hello world')) {
        die('send failed');
    }
    $data = $client->recv(); //如果不做接收数据处理 服务端报notice错误： swFactoryProcess_finish (ERRNO 1004): send 18 byte failed, because connection[fd=2] is closed
    if (!$data) {
        die('rece failed');
    }
    echo $data.PHP_EOL;
    sleep(1);
}


$client->close(); // 这里不手动关闭 程序也会自动关闭
