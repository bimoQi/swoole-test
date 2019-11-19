<?php

Swoole\Runtime::enableCoroutine(); // 是否启动协程

go(function () {
    while (1) {
        echo 'he' . PHP_EOL;
        co::sleep(0.1);
    }
});
go(function () {
    while (1) {
        $msg = fgets(STDIN);
        echo $msg . PHP_EOL;
    }
});
