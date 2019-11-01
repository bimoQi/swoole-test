<?php

/**
 *  
 */
$serv = new Swoole\Server('0.0.0.0', 9501);



$serv->on('receive', function ($serv, $fd, $from_id, $data) {
});

$serv->start();
