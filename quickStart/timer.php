<?php

// 相当于js的 setInterval 持续触发的
$b = new Swoole\Atomic(0);
$timer_id = swoole_timer_tick(1000, function ($timer_id) use ($b) {
    echo $b->get() . PHP_EOL;
    $b->add();;
    echo 'tick-2000ms' . PHP_EOL;
});

// 相当于js的 setTimeout 触发一次
// $a = 1;
// $timer_id2 = swoole_timer_after(1000, function () use ($a) {
//     echo $a . PHP_EOL;
//     $a++;
//     echo 'tick-3000ms' . PHP_EOL;
// });

// 4秒后将timer_id 清除
go(function () use ($timer_id) {
    co::sleep(10);
    swoole_timer_clear($timer_id);
});

// class A
// {
//     private $fd;

//     public function test()
//     {
//         $fd = 1;
//         swoole_timer_tick(1000, function () {
//             $this->fd = 1;
//             $this->echo('time 1 ');
//         });
//         swoole_timer_tick(1500, function () {
//             $this->fd = mt_rand(1000,100000);
//             $this->echo('time 1.5 ');
//         });
//     }
//     function echo ($msg) {
//         echo 'msg' . $msg . 'this fd:' . $this->fd . PHP_EOL;
//     }
// }

// (new A())->test();
