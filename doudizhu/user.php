<?php

class User
{
    private $table_user;

    private $atomic;

    const STATUS_ONLINE = 1; // 在线
    const STATUS_OFFLINE = 0; // 离线

    public function __construct($server)
    {
        $this->server = $server;
        $this->table_user = new Swoole\Table(1024);
        $this->table_user->column('id', swoole_table::TYPE_INT, 4); // 用户ID
        $this->table_user->column('nickname', swoole_table::TYPE_STRING, 512); // 昵称
        $this->table_user->column('password', swoole_table::TYPE_STRING, 512); // 密码
        $this->table_user->column('avatar', swoole_table::TYPE_STRING, 512); // 头像地址
        $this->table_user->column('status', swoole_table::TYPE_INT, 4); // 用户状态 是否在线
        $this->table_user->column('room_id', swoole_table::TYPE_INT, 4); // 用户目前所在的房间
        $this->table_user->column('fd', swoole_table::TYPE_INT, 4); // 用户的fd具柄
        $this->table_user->create();
        $this->atomic = new Swoole\Atomic(0); // 用来user表的自增id 
    }

    /**
     * 创建新用户
     *
     * @return void
     */
    public function loginOrCreate($nickname, $password, $avatar, int $fd)
    {
        $user = $this->getUserInfoByKey('nickname', $nickname);
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
        $new_user_id = $this->generateNewUserId();
        $user = [
            'id' => $new_user_id,
            'nickname' => $nickname,
            'password' => $password,
            'avatar' => $avatar,
            'fd' => $fd,
            'status' => self::STATUS_ONLINE,
        ];
        
        $this->table_user->set($new_user_id, $user);
        return $user;
    }

    /**
     * 检测改fd是否有效-是否是内存表user里的值
     *
     * @return void
     */
    public function checkUser(int $fd)
    {
        $user = $this->getUserInfoByKey('fd', $fd);
        if ($user) {
            return $user;
        }
        return false;
    }

    /**
     * 获取用户信息 通过key-value值查询
     *
     * @param [type] $key
     * @param [type] $value
     * @param [array] $get_field 要获取的数据
     * @return void
     */
    public function getUserInfoByKey($key, $value, $get_field = [])
    {
        foreach ($this->table_user as $user_id => $user) {
            if ($user[$key] == $value) {
                if (!empty($get_field)) {
                    $return_user = [];
                    foreach($get_field as $field) {
                        $return_user[$field] = $user[$field];
                    }
                    return $return_user;
                }
                return $user;
            }
        }
        return false;
    }

    public function logout($fd)
    {
        $user = $this->getUserInfoByKey('fd', $fd);
        if ($user) {
            $this->table_user->set($user['id'], [
                'status' => self::STATUS_OFFLINE,
            ]);
        }
    }

    // 获取新id
    private function generateNewUserId()
    {
        return $this->atomic->add();
    }

    public function update($user_id,array $field)
    {
        if (!$this->table_user->exist($user_id)) {
            throw new Exception('user not exist');
        }
        return $this->table_user->set($user_id, $field);
    }
}
