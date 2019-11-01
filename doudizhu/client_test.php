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

// exit;
go(function () {

    $client = new Swoole\Coroutine\Http\Client('127.0.0.1', 9999);
    $client->upgrade('/');

    while (1) {
        $msg = fgets(STDIN);
        $client->push($msg);
        $res = $client->recv();
        if ($res && $res->finish == true) {
            echo '收到数据：' . $res->data . PHP_EOL;
        }
    }
});
