<?php

/**
 * mysql连接池
 * 如果启动协程mysql-client 那么 每个mysql连接池里的连接个数是针对当前进程的
 * 如果是用pdo的话， 那么进程池基本没用到， 一个进程里的连接池永远只有1个
 * 案例：https://blog.csdn.net/weixin_33834679/article/details/92266809
 * 下面案例 修改use_coroutine 是否使用协程mysql-client 使用ab测试工具ab -n 30 -c 30 127.0.0.1:9501/
 * 进程数量设置为2 可以看出使用了之后 效率高很多，当然这个只是适用于超高并发，普通的php-fpm已经足够
 * 
 ************************************
 * 所以要注意的是，协程的客户端内执行其实是同步的，不要理解为异步，它只是遇到IO阻塞时能让出执行权，切换到其他协程而已，不能和异步混淆。
 ************************************
 * 
 * 
 * 传统pdo形式：
 *              server监听用户请求，当接收发请求时，调用连接数的getConnection()方法从connections通道中pop()一个对象。
 *              此时如果并发了10个请求，server因为配置了1个worker,所以再pop到一个对象返回时，遇到sleep()的查询，
 *              因为用的连接对象是pdo的查询，此时的woker进程只能等待，完成后才能进入下一个请求。因此，池中的其余连接其实是多余的，
 *              同步客户端的请求速度只能和woker的数量有关
 * 协程mysql形式：
 *              server启动后，初始化都和同步一样。不一样的在获取连接对象的时候，此时如果并发了10个请求，同样是配置了1个worker进程在处理，
 *              但是在第一请求到达，pop出池中的一个连接对象，执行到query()方法，遇上sleep阻塞时，此时，woker进程不是在等待select的完成，
 *              而是切换到另外的协程去处理下一个请求。完成后同样释放对象到池中
 */
include __DIR__.'/AbstractPool.php';

class MysqlPoolPdo extends AbstractPool
{
    public static $instance;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new MysqlPoolPdo();
        }
        return self::$instance;
    }
    protected function createDb()
    {
        return new \PDO('mysql:host=127.0.0.1;', $this->db_conf['user'], $this->db_conf['password']);
    }
}


class MysqlPoolCoroutine extends AbstractPool
{
    public static $instance;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new MysqlPoolCoroutine();
        }
        return self::$instance;
    }
    protected function createDb()
    {
        echo 'create';
        $db = new Swoole\Coroutine\Mysql();
        $bool = $db->connect($this->db_conf);
        if ($bool == false) {
            return false;
        }
        return $db;
    }
}

$use_coroutine = 1;
$http = new Swoole\Http\Server('0.0.0.0', 9501);
$http->set([
    'worker_num' => 1,  // 进程数
]);

$http->on('request', function ($req, $res) use ($use_coroutine) {
    $obj = null;
    if ($use_coroutine) {
        $obj = MysqlPoolCoroutine::getInstance()->getConnection();
    } else {
        $obj = MysqlPoolPdo::getInstance()->getConnection();
    }

    $db = $obj ? $obj['db'] : null;
    if ($db) {  //遇到阻塞，协程会
        // var_dump($obj);
        $db->query('select sleep(2)');
        $ret = $db->query('select count(1) from user');
        if ($use_coroutine) {
            MysqlPoolCoroutine::getInstance()->free($obj);
        } else {
            MysqlPoolPdo::getInstance()->free($obj);
        }
        if ($ret == false) {
            // var_dump($db->errno, $db->error);
        }
        $res->end(json_encode($ret));
    } else {
        echo 'db not inaf';
    }
});

$http->on('workerStart', function () use ($use_coroutine) {
    if ($use_coroutine) {
        MysqlPoolCoroutine::getInstance()->init();
    } else {
        MysqlPoolPdo::getInstance()->init();
    }
});


$http->start();
