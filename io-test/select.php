<?php

/**
 * 非多路复用：
 *          一个进程只能处理一个socket客户端，下一个需要等待上个结束
 * 多路复用io：能通过一种机制一个进程能同时等待多个io socket；
 *          本身隶属于同步通信，只是表现出结果像异步，三种方案：select,poll,epoll
 * select模式：
 *          server启动后，将三组不通的socketd fd加入到select函数内参数(fd_set:可读，可写，异常，内核维护)
 *          每当监控项中有可读/写/异常出现时，通知调用方
 *          调用方调用select后，将fd_set copy到用户内存中，调用方会被select阻塞 等待可读/写等事件发生
 *          调用方通过轮训方式遍历fd，取出活跃fd进行操作，若调用方没有处理，则下次轮询时还是可以找到这个fd，直到调用发理会
 * 缺点：
 *          每次调用都需要吧fd_set copy 到内存中，还要遍历，开销大
 *          select支持的文件描述符太小
 */
$host = '0.0.0.0';
$port = 9501;
$listen_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); //创建socket的tcp服务
socket_bind($listen_socket, $host, $port);
socket_listen($listen_socket);
socket_set_nonblock($listen_socket);

$client = [$listen_socket]; //也将监听的socket放到read set中 因为select也要监听此socket上的事件
$write = []; //监听写
$exp = []; //监听异常

while (1) {
    $read = $client; //监听读
    // 第四个参数是超时时间 null代表无超时，一直停留在此函数上  其实就是监听里面的socket变化 有变化就继续运行
    if (socket_select($read, $write, $exp, null) > 0) { // 阻塞，处理所有有活动的fd
        if (in_array($listen_socket, $read)) {
            $client_socket = socket_accept($listen_socket); // 接收socket
            $client[] = $client_socket;
            $key = array_search($listen_socket, $read);
            unset($read[$key]);
        }
        if (count($read) > 0) {
            foreach ($read as $socket_item) {
                $content = socket_read($socket_item, 2048);
                foreach ($client as $client_socket) {
                    // 当前发送者 和本身server监听者不用管 目的是通知其他fd消息
                    if ($client_socket != $listen_socket && $client_socket != $socket_item) {
                        socket_write($client_socket, $content, strlen($content));
                    }
                }
            }
        }
    } else {
        continue;
    }
}
