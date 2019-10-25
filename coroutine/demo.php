<?php
/**
 *  协程测试 - 协程：可以理解为纯用户态的线程，其通过协作而不是抢占来进行切换;可以在程序的任意位置调用其他方法，自行控制线程的切换，提高了性能
 *  每个go都是一个子程序(协程)？ 执行互不影响
 *  优点：极高的执行效率 子程序切换不是线程切换，而是由程序自身控制，没有线程开销，和多线程相比，性能高
 *  缺点：需要程序控制
 *  示例里的go() 方法都是并行执行 不需用等待其返回
 *  注意：不能保证全局变量和static的一致性
 */
Swoole\Runtime::enableCoroutine(); // 是否启动协程
// $i = 0;
// while ($i < 100) {
//     go(function () {
//         exec('php /private/var/www/fishing/fishing-server-happy-os/artisan fix');
//     });
//     echo $i;
//     $i++;
// }
// exit;
function fuc($b)
{
    echo $b . PHP_EOL;
};

go(function () {
    echo '1' . PHP_EOL;
    sleep(1);
    fuc('阻塞'); // 当然也可以阻塞做其他事情
    sleep(2);
    go(function () { // 这里在开一个线程做
        fuc('last 1');
        sleep(2);
        fuc('last 2'); //这个方法块最后输出
    });
    echo '44' . PHP_EOL;
});

go(function () {
    echo '2' . PHP_EOL;
});
