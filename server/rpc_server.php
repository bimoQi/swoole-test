<?php
/**
 * 使用tcp协议 作为传输标准 创建rpc服务
 *      传输协议使用protobuf
 */
include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../protobuf/GPBMetadata/Rpcdata.php';
include __DIR__ . '/../protobuf/Rpcdemo/rpc.php';

/******** 业务 ******/
class Demo
{
    public function test($params)
    {
        $return_data = [
            'data' => $params,
            'time' => time(),
        ];
        return $return_data;
    }
}
/******** 服务 ******/
$rpc_server = new Swoole\Server('0.0.0.0', '9501');
/**
 * 制定协议 因为tcp是流式的可能多次信息合并到一个包中，也可能一个信息分多个包传输，所以应用层需要自定义协议进行‘分包’，‘合包‘
 *      一般有两种制定边界的方式： 参考：https://www.jianshu.com/p/a2cbc92e38e3
 *          1.EOF协议   如：Memcache，ftp，smtp；  可以用telnet 连接尝试
 *              $serv->set([
 *                  'open_eof_split' => true,   // 开启EOF检测
 *                  'package_eof' => '/r/n' ,   // 设置EOF标记
 *              ]);
 *          2.固定包头协议 下面这种 -- 实际上常用的 有包头有包体
 *              在TCP的数据流中使用固定包头协议的数据流特征是|length长度|数据|length长度|数据|
 *              如下面的packdata和unpackdata方法
 */

// $rpc_server->set([       // 这种方式 就telnet 127.0.0.1 9501，然后输入一些东西，知道收到'/r/n' 才标志这个数据包的完整性
//     'open_eof_split' => true,   // 开启EOF检测
//     'package_eof' => '/r/n' ,   // 设置EOF标记
// ]);
$rpc_server->set([
    'open_length_check' => 1, // 开启协议解析
    'package_length_type' => 'N', // 长度字段的类型
    'package_length_offset' => 0, // 第N个字节是包长度的值
    'package_body_offset' => 4, // 第N个字节开始计算长度
    'package_max_length' => 2000000, // 协议最大长度
]);

$rpc_server->on('task', function ($serv, $task_id, $from_id, $data) {

});

$rpc_server->on('receive', function ($serv, $fd, $from_id, $data) {
    // $r_data =  'receive:'.$data.PHP_EOL;
    // echo $r_data;
    $worker_id = $serv->worker_id;
    $length = strlen($data);
    debug("[receive] worker:{$worker_id} length:{$length} raw_data:{$data}");
    $recv_body_data = unpackdata($data, 'N');
    if ($recv_body_data === false) {
        $res = [
            'data' => '协议错误-version不正确',
            'time' => time(),
            'code' => -1,
        ];
        goto last;
    }
    debug("[receive] recv:{$recv_body_data}");

    // 业务处理 使用protobuf 进行数据传输
    $protobuf = new \Rpcdemo\rpc();
    $protobuf->mergeFromString($recv_body_data);
    $class_name = $protobuf->getReqClass();
    $action_name = $protobuf->getReqAction();
    $params = $protobuf->getReqParams();
    $protobuf_str = $protobuf->serializeToString();
    $res = (new $class_name())->$action_name($params);

    last:
    $protobuf = new \Rpcdemo\rpc();
    $protobuf->setResData($res['data']);
    $protobuf->setResCode(isset($res['code']) ? $res['code'] : 1);
    $protobuf->setResTime($res['time']);
    $send_str = $protobuf->serializeToString();
    $send = packdata($send_str, 'N');
    debug("[receive] send:{$send}");
    $serv->send($fd, $send);
});

$rpc_server->on('connect', function ($serv, $fd) {
    debug("[connect] client {$fd}");
});
$rpc_server->on("close", function ($server, $fd) {
    debug("[close] client {$fd}");
});

$rpc_server->start();

//解包封包  自定义打包 测试扩展包头/包体： 固定包头  4字节为包头，包体前2位是扩展信息(version,cmd),后面是具体包信息
function packdata($data, $package_length_type)
{
    $version = 1;
    $cmd = 1; // 测试用
    return pack($package_length_type, strlen($data) + 2) . pack('C2', $version, $cmd) . $data;
}

function unpackdata($data, $package_length_type)
{
    $version = 1;
    $length = $package_length_type == "N" ? 4 : 2;
    $header = unpack($package_length_type, substr($data, 0, $length));
    $body = substr($data, $length);
    $expand = unpack('Cversion/Ccmd', substr($body, 0, 2));
    $b_version = $expand['version'];
    $b_cmd = $expand['cmd'];
    if ($version != $b_version) { //自定义验证信息， version不对 就不让通过解析
        return false;
    }
    return substr($body, 2);
}

function debug($msg)
{
    echo $msg . PHP_EOL;
}
