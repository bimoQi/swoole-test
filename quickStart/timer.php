<?php

// 相当于js的 setInterval 持续触发的
$timer_id = swoole_timer_tick(2000, function ($timer_id) {
    echo 'tick-2000ms' . PHP_EOL;
});

// 相当于js的 setTimeout 触发一次
$timer_id2 = swoole_timer_after(3000, function () {
    echo 'tick-3000ms' . PHP_EOL;
});

// 4秒后将timer_id 清除
go(function () use ($timer_id) {
    co::sleep(4);
    swoole_timer_clear($timer_id);
});
