<?php

use Swoole\Coroutine\Channel;

abstract class AbstractPool
{
    private $min = 1;
    private $max = 10;
    private $count = 0;
    private $connections = [];
    private $inited = 0;

    protected $db_conf = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '',
        'database' => 'fishGame',
    ];

    public function __construct()
    {
        $this->connections = new Channel($this->max+1);
    }

    abstract protected function createDb();

    protected function createObj()
    {
        $obj = null;
        $db = $this->createDb();
        if ($db) {
            $obj = [
                'last_use_time' => time(),
                'db' => $db
            ];
        } else {
            return $obj;
        }
        return $obj;
    }

    public function init()
    {
        if ($this->inited) {
            return null;
        }
        for ($i = 0; $i<$this->min; $i++) {
            $obj = $this->createObj();
            $this->count++;
            $this->connections->push($obj);
        }
        return $this;
    }

    public function getConnection($timeout = 3)
    {
        $obj = null;
       
        if ($this->connections->isEmpty()) {
            if ($this->count < $this->max) {
                $obj = $this->createObj();  // 创建之后 先返回给用户使用，使用完后再push到chnnel 里
                if (!$obj) {
                    return $obj;
                }
                $this->count++;
            } else {
                $obj = $this->connections->pop($timeout);
            }
        } else {
            $obj = $this->connections->pop($timeout);
        }
        return $obj;
    }

    public function free($obj)
    {
        if ($obj) {
            $this->connections->push($obj);
        }
    }

    // 处理空闲连接
    public function gcSpareObject()
    {
        swoole_timer_tick(120000, function () {
            $list = [];
            /*echo "开始检测回收空闲链接" . $this->connections->length() . PHP_EOL;*/
            if ($this->connections->length() < intval($this->max * 0.5)) {
                echo "请求连接数还比较多，暂不回收空闲连接\n";
            }
            while (true) {
                if (!$this->connections->isEmpty()) {
                    $obj = $this->connections->pop(0.001);
                    $last_used_time = $obj['last_used_time'];
                    if ($this->count > $this->min && (time() - $last_used_time > $this->spareTime)) {//回收
                        $this->count--;
                    } else {
                        array_push($list, $obj);
                    }
                } else {
                    break;
                }
            }
            foreach ($list as $item) {
                $this->connections->push($item);
            }
            unset($list);
        });
    }
}
