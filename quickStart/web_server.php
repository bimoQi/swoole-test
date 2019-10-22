<?php
/**
 * http
 * on 可选types
 * onRequest
 */
$http = new Swoole\Http\Server('0.0.0.0', 9903);

$http->set([
    'worker_num' => 4,  //如果不设置 就是cpu逻辑核数量
    'daemonize' => false, //是否作为守护进程
]);

$http->on('request', function ($request, $response) {
    var_dump($request->get, $request->post);
    $response->header('Content-Type', 'text/html;charset=utf-8');
    $response->end("hello swoole" . rand(0, 1999)); //输出并结束
});

$http->start();

//路由 如：http://127.0.0.1:9903/test/index/?a=1
// $http->on('request', function($request, $response){
//     list($controller, $action) = explode('/', trim($request->server['request_uri'], '/'));
//     (new $controller)->$action($request, $response);
// });
