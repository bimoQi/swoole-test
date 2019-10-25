<?php
/**
 * 使用协程  代理异步websocket client
 *  目的是：循环接收数据，如果连接断了， 10秒内自动连接，还是不成功退出进程
 */
class Test
{

    private $client;
    private $host;
    private $port;
    private $max_times;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->max_times = 10;
    }

    public function start()
    {
        go(function () {
            try {
                $this->connect();
                $obj = $this;
                $timer_id = Swoole\Timer::tick(1000, function ($timer_id) use ($obj) {
                    if (!$obj->client->connected) {
                        return;
                    }
                    $obj->client->push('hello!');
                });
                while (1) {
                    if (!$this->client->connected) {
                        $this->connect();
                    }
                    $res = $this->client->recv();   // 阻塞代码
                    if ($res) {
                        // 去做自己的业务
                        go(function () use ($res) {
                            co::sleep(1);
                            echo 'res: ' . $res->data . PHP_EOL;
                        });
                    }
                }
            } catch (\Exception $e) {
                echo 'err: ' . $e->getMessage();
                Swoole\Timer::clearAll();
                return;
            }
        });
    }

    //超出最大次数就停掉
    private function connect()
    {
        while (1) {
            static $i = 0;
            $this->client = new Swoole\Coroutine\Http\Client($this->host, $this->port);
            $this->client->upgrade('/');
            if (!$this->client->connected) {
                if ($i > $this->max_times) {
                    throw new \Exception(' over max connect times !');
                }
                co::sleep(1);   // 异步io中 sleep不能使用 会阻塞代码
                $i++;
                continue;
            }
            return;
        }
    }
}

(new Test('127.0.0.1', 9501))->start();
