<?php

class Core
{
    private $room;
    private $user;

    private $fd; //具柄
    private $data; //用户发送过来的数据
    private $cmd;

    public function __construct()
    {
    }

    /**
     * 处理业务逻辑
     *
     * @param [type] $from_data  数据源：[fd, data]
     * @return void
     */
    public function exec($from_data, $room, $user)
    {
        $this->room = $room;
        $this->user = $user;
        try {
            $this->fd = $from_data['fd'];
            $all_data = json_decode($from_data['data'], true);
            $this->cmd = $all_data['cmd'];
            $this->data = $all_data['data'];
            if ($this->cmd != 1) {
                if (!$user->checkUser(intval($this->fd))) {
                    return json_encode([
                        'code' => -1,
                        'msg' => '非法连接',
                    ], JSON_UNESCAPED_UNICODE);
                }
            }
            $action = $this->cmdMap($this->cmd); // 操作码
            return $this->$action();
        } catch (Exception $e) {
            return 'system err:' . $e->getMessage();
        }
    }

    // 命令-操作action
    public function cmdMap($cmd)
    {
        $map = [
            1 => 'initUser', //点击准备
            2 => 'ready', //点击准备
            3 => 'grab', //抢地主
            4 => 'palyCards', //出牌或压子
            5 => 'pass', //过
        ];
        return $map[$cmd];
    }

    // 注册或登陆
    private function initUser()
    {
        $avatar = isset($this->data['avatar']) ? $this->data['avatar'] : '';
        return $this->user->loginOrCreate($this->data['user_nickname'], $this->data['user_password'], $avatar, intval($this->fd));
    }

    private function ready()
    {
        $this->room->getRoomInfo($fd);
        return __METHOD__;
    }

    private function grab()
    {
        return __METHOD__;
    }

    private function palyCards()
    {
        return __METHOD__;
    }
    private function pass()
    {
        return __METHOD__;
    }
}
