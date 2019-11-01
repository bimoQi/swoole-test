<?php

class User
{
    private $table_user;

    private $last_user_id = 0;

    const STATUS_ONLINE = 0; // 在线
    const STATUS_OFFLINE = 1; // 离线

    public function __construct($server)
    {
        $this->server = $server;
        $this->table_user = new Swoole\Table(1024);
        $this->table_user->column('id', swoole_table::TYPE_INT, 4); // 用户ID
        $this->table_user->column('nickname', swoole_table::TYPE_STRING, 512); // 昵称
        $this->table_user->column('password', swoole_table::TYPE_STRING, 512); // 昵称
        $this->table_user->column('avatar', swoole_table::TYPE_STRING, 512); // 头像地址
        $this->table_user->column('status', swoole_table::TYPE_INT, 4); // 用户状态 是否在线
        $this->table_user->column('fd', swoole_table::TYPE_INT, 4); // 用户的fd具柄
        $this->table_user->create();
    }

    /**
     * 创建新用户
     *
     * @return void
     */
    public function loginOrCreate($nickname, $password, $avatar, int $fd)
    {
        $user = $this->getuserInfoByKey('nickname', $nickname);
        if ($user && $user['password'] == $password) {
            if ($user['password'] == $password) {
                // TODO 通知其他登陆client 下线
                $this->table_user->set($this->last_user_id, [
                    'avatar' => $avatar,
                    'status' => self::STATUS_ONLINE,
                    'fd' => $fd
                ]);
                return $user;
            } else {
                return false;
            }
        }
        ++$this->last_user_id;
        $user = [
            'id' => $this->last_user_id,
            'nickname' => $nickname,
            'password' => $password,
            'avatar' => $avatar,
            'fd' => $fd,
            'status' => self::STATUS_ONLINE,
        ];
        $this->table_user->set($this->last_user_id, $user);
        return $user;
    }

    /**
     * 检测改fd是否有效-是否是内存表user里的值
     *
     * @return void
     */
    public function checkUser(int $fd)
    {
        $user = $this->getuserInfoByKey('fd', $fd);
        if ($user) {
            return true;
        }
        return false;
    }

    /**
     * 获取用户信息 通过key-value值查询
     *
     * @param [type] $key
     * @param [type] $value
     * @return void
     */
    private function getuserInfoByKey($key, $value)
    {
        foreach ($this->table_user as $user_id => $user) {
            if ($user[$key] == $value) {
                return $user;
            }
        }
    }

    public function logout($fd)
    {
        $user = $this->getuserInfoByKey('fd', $fd);
        if ($user) {
            $this->table_user->set($user['id'], [
                'status' => self::STATUS_OFFLINE,
            ]);
        }
    }
}
