<?php

require_once __DIR__ . '/core.php';
require_once __DIR__ . '/room.php';
require_once __DIR__ . '/user.php';

class WsServer
{
    private $host = '0.0.0.0';
    private $port = 9999;
    private $server;
    private $log;

    private $room;
    private $user;

    protected $ws_config = array(
        'dispatch_mode' => 3,
        'open_length_check' => 1,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,

        'package_max_length' => 2097152, // 1024 * 1024 * 2,
        'buffer_output_size' => 3145728, //1024 * 1024 * 3,
        'pipe_buffer_size' => 33554432, // 1024 * 1024 * 32,

        'heartbeat_check_interval' => 30,
        'heartbeat_idle_time' => 60,

        'max_conn' => 2000,
        'worker_num' => 2,
        'task_worker_num' => 4, //生产环境请加大，建议1000

        'max_request' => 0, //必须设置为0，否则会导致并发任务超时,don't change this number
        'task_max_request' => 2000,

        //'daemonize'=>1,
        'backlog' => 3000,
    );

    public function __construct()
    {

    }

    public function start()
    {
        $this->room = new Room($this);
        $this->user = new User($this);

        $this->server = new Swoole\WebSocket\Server($this->host, $this->port);
        $this->server->set($this->ws_config);
        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('task', [$this, 'onTask']);
        $this->server->start();
    }

    public function onStart()
    {
        $this->log('########################################################################');
        $this->log("* WS  |  HOST: \e[0;32m{$this->host}\e[0m, PORT:\e[0;32m{$this->port}\e[0m, MODE:\e[0;32m{$this->ws_config['dispatch_mode']}\e[0m, WORKER:\e[0;32m{$this->ws_config['worker_num']}\e[0m, TASK:\e[0;32m{$this->ws_config['task_worker_num']}\e[0m");
        $this->log("* MasterPid={$this->server->master_pid}  |  ManagerPid={$this->server->manager_pid}  |  Swoole version is [" . SWOOLE_VERSION . "]");
        $this->log('########################################################################');
    }

    public function onOpen($server, $request)
    {
        $stats = $server->stats();
        $this->room->enter($request->fd);
        $this->log("onOpen connection open: #" . $request->fd . "  connection_num:{$stats['connection_num']}，accept_count:{$stats['accept_count']}，close_count:{$stats['close_count']}");
    }

    public function onClose($server, $fd)
    {
        $this->log('onClose #' . $fd);
        //TODO 标识用户下线
    }

    public function onMessage($serv, $frame)
    {
        $send['data'] = $frame->data;
        $send['fd'] = $frame->fd;
        if (empty($send['data'])) {
            return;
        }
        $this->server->task($send, -1, function ($serv, $task_id, $data) use ($frame) {
            $this->log('Send [' . $frame->fd . '] >>> ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            $serv->push($frame->fd, json_encode($data), WEBSOCKET_OPCODE_BINARY);
        });
    }

    public function onTask($serv, $task_id, $worker_id, $data)
    {
        $this->log('Recv [' . $data['fd'] . '] <<< ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $res_data = (new Core())->exec($data, $this->room, $this->user);
        return $res_data;
    }

    public function log($msg = '', $level = 1)
    {
        $level_map = [
            1 => 'INFO',
            2 => 'DEBUG',
            3 => 'ERROR',
        ];
        echo '[' . $level_map[$level] . '] ' . '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    }
}

(new WsServer())->start();
