<?php

/**
 * poll 是 select的高级版本，时间复杂度都是O(n)，只不过poll的描述fd方式不一样，其采用的是pollfd方式而不是fd_set，其他差不多
 *
 * epoll (mac 下叫 kqueue)
 *      时间复杂度 O(1)
 *      主要是事件监听-回调处理
 *      提供了三个函数：epoll_create(句柄), epoll_ctl(注册监听), epoll_wiat(等待事件)，相比下select只有一个函数
 *      理论上可以无上限个fd，只跳出活跃的fd，其余的不理会
 *      c语言可以直接操作epoll；php无法直接操控epoll，需要通过libevent来使用 pecl install event
 * 
 * reactor (反应堆)
 */

$host = '0.0.0.0';
$port = 9999;
$fd = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($fd, $host, $port);
socket_listen($fd);
// 注意，将“监听socket”设置为非阻塞模式
// socket_set_nonblock($fd);    // 非阻塞意思是：函数立马返回，每隔一段时间看下这个函数的执行结果

$event_arr = [];
$conn_arr = [];

$event_base = new EventBase();
//  Event::READ | Event::PERSIST 表示持久性读，后面2个参数一个是回调，一个是回调参数
$event = new Event($event_base, $fd, Event::READ | Event::PERSIST, function ($fd) {
    global $event_arr, $conn_arr, $event_base;
    $conn = socket_accept($fd);
    if ($conn != false) {
        echo 'welcome:' . intval($conn) . '进入' . PHP_EOL;
        // socket_set_nonblock($conn);
        $conn_arr[intval($conn)] = $conn;
        // 每个socket 都设置事件监听
        $event = new Event($event_base, $conn, Event::READ | Event::PERSIST, function ($conn) {
            echo 'event 来了:'.$conn.PHP_EOL;
            global $conn_arr;
            $buffer = socket_read($conn, 65535);
            foreach ($conn_arr as $conn_key => $conn_item) {
                if ($conn != $conn_item) {
                    $msg = intval($conn) . '说：' . $buffer;
                    socket_write($conn_item, $msg, strlen($msg));
                }
            }
        }, $conn);
        $event->add();
        $event_arr[] = $event;  // ？ 不太明白 这个只是保存event而已，难道只是内存操作而已，并没有对arr有实际上的操作
    }
}, $fd);
$event->add();  // 挂其event对象
$event_base->loop();    //循环
