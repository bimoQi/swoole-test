<?php
// use Swoole\Table;
// $table = new \Swoole\Table(8);

// $table->column('data',Table::TYPE_STRING, 1);
// $table->column('pad',Table::TYPE_STRING, 1);
// $table->create();

// $table->set(1,[]);
// $table->set(2,[]);
// $table->set(5,['data'=>1]);
// $table->set(5,['pad'=>1]);
// var_dump($table->get(5));exit;
// foreach ($table as $key => $value) {
//     echo $key.PHP_EOL;
//     var_dump($value);
// }

// $atomic = new Swoole\Atomic(1);
// echo $atomic->add();
// exit;

// exit;
go(function () {

    $client = new Swoole\Coroutine\Http\Client('127.0.0.1', 9999);
    $client->upgrade('/');

    $user_nickname = 'qichen_' . mt_rand(0, 1000);
    $cmd = [
        '{"cmd":1,"data":{"avatar":"http://baidu.com.com/test.png","user_nickname":"' . $user_nickname . '","user_password":"1234"}}',
        '{"cmd":2,"data":{}}',
        '{"cmd":3,"data":{}}',
    ];
    go(function () use ($client) {
        while (1) {
            $res = $client->recv();
            if ($res && $res->finish == true) {
                echo '收到数据：' . $res->data . PHP_EOL;
                file_put_contents(__DIR__ . '/client_test.log', ($res->data) . PHP_EOL, FILE_APPEND);
            }
        }
    });

    // 使用异步监听‘标准输入’ 进行push --底层操作epoll/kqueue
    swoole_event_add(STDIN, function($fp)  use ($client){
        $client->push(fgets($fp));
    });

    $cmd_i = 0;
    while (1) {
        $msg = '';
        if (isset($cmd[$cmd_i])) {
            $msg = $cmd[$cmd_i];
            $cmd_i++;
        } else {
            // 这种方式会阻塞client的其他协程操作
            // $msg = fgets(STDIN); // 读取标准输入的每一行
            // $msg = fread(STDIN, 8192); // 读取标准输入的多少大小，遇到结束符或超出大小截止
        }
        if ($msg) {
            $client->push($msg);
        } else {
            break;
        }
        co::sleep(0.1);
    }
});

/**
 * 创建/登陆用户： {"cmd":1,"data":{"avatar":"http://baidu.com.com/test.png","user_nickname":"qichen","user_password":"1234"}}
 * 进入房间：{"cmd":2,"data":{}}
 * 点击准备：{"cmd":3,"data":{}}
 * 点击抢地主：{"cmd":4,"data":{}}
 * 出牌：{"cmd":5,"data":{"out_cards":[4,12,15,16,17,21,22,24,27,29,36,40,44,45,48,53,54,25,26,32]}}
 */
