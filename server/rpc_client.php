<?php
/**
 * 使用tcp协议 作为传输标准 创建rpc服务
 *          自定义协议头，使用protobuf进行数据传输
 * ? mac下 connect 总是返回true, linux 下总是有php warning
 */
include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../protobuf/GPBMetadata/Rpcdata.php';
include __DIR__ . '/../protobuf/Rpcdemo/rpc.php';

try {
    $rpc_client = new Swoole\Client(SWOOLE_SOCK_TCP);
    $rpc_client->set([
        'open_length_check' => 1, // 开启协议解析
        'package_length_type' => 'N', // 长度字段的类型
        'package_length_offset' => 0, // 第N个字节是包长度的值
        'package_body_offset' => 4, // 第N个字节开始计算长度
        'package_max_length' => 2000000, // 协议最大长度
    ]);
    if ($rpc_client->connect('127.0.0.1', 9501, 0.5)) {

        // 使用protobuf传输数据
        $protobuf = new \Rpcdemo\rpc();
        $protobuf->setReqClass('Demo');
        $protobuf->setReqAction('test');
        $protobuf->setReqParams('hhh');
        $protobuf_str = $protobuf->serializeToString();

        $data = packdata($protobuf_str, 'N');
        $res = $rpc_client->send($data);
        $res = $rpc_client->recv();
        $recv_body_data = unpackdata($res, 'N');

        $protobuf->mergeFromString($recv_body_data); //解析数据
        $res_json = [
            'data' => $protobuf->getResData(),
            'code' => $protobuf->getResCode(),
            'do_time' => $protobuf->getResTime(),
        ];
        echo '结果：' . PHP_EOL . json_encode($res_json, JSON_UNESCAPED_UNICODE);
        $rpc_client->close();
    } else {
        echo $rpc_client->errCode;
    }
} catch (Throwable $e) {
    var_dump($e->getMessage());
}

//解包封包  自定义打包 测试扩展包头/包体： 固定包头  4字节为包头，包体前2位是扩展信息(version,cmd),后面是具体包信息
function packdata($data, $package_length_type)
{
    $version = 2;       // 如果这个version和服务器的version不正确，在解析包的时候会不通过
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
        throw new \Exception('unpack err: version err');
    }
    return substr($body, 2);
}
