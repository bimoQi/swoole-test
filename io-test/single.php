<?php

/**
 * 单个进程监听socket 也就说没有使用io多路复用情况下
 *      一个进程同一时间内只能接收一个socket请求，如果多个请求进来 他会循环执行 时间为 T = T1+T2+T3
 *      相比多路复用io 执行时间为：T = max(T1,T2,T3)
 */

$host = '0.0.0.0';
$port = 9999;
// 创建一个tcp socket
$listen_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
// 将socket bind到IP：port上
socket_bind($listen_socket, $host, $port);
// 开始监听socket
socket_listen($listen_socket);
// 进入while循环，不用担心死循环死机，因为程序将会阻塞在下面的socket_accept()函数上
while (true) {
    // 此处将会阻塞住，一直到有客户端来连接服务器。阻塞状态的进程是不会占据CPU的
    // 所以你不用担心while循环会将机器拖垮，不会的
    $connection_socket = socket_accept($listen_socket);
    sleep(10);
    // 向客户端发送一个helloworld
    $msg = "helloworld\r\n";
    socket_write($connection_socket, $msg, strlen($msg));
    socket_close($connection_socket);
}
socket_close($listen_socket);
