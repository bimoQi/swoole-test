<?php

class Room
{
    private $table_room;
    private $server;

    const STATUS_READY = 0; // 未开始
    const STATUS_RUNNING = 1; // 已开始

    private $last_room_id = 0;
    public function __construct($server)
    {
        $this->server = $server;
        $this->table_room = new Swoole\Table(1024);
        $this->table_room->column('id', swoole_table::TYPE_INT, 4); //  房间id
        $this->table_room->column('user_ids', swoole_table::TYPE_STRING, 512); // 房间用户id
        $this->table_room->column('current_num', swoole_table::TYPE_FLOAT); // 当前房间内人数
        $this->table_room->column('status', swoole_table::TYPE_INT);
        $this->table_room->create();
    }

    /**
     * 进入房间
     *
     * @param [type] $user_id
     * @param [type] $room_id
     * @return void
     */
    public function enter($user_id, $room_id = null)
    {
        $init_user_room_id = 0;
        if (!$room_id) {
            // 随机分配房间
            foreach ($this->table_room as $pre_room_id => $data) {
                if ($data['current_num'] >= 3 || $data['status'] == self::STATUS_RUNNING) {
                    continue;
                }
                $init_user_room_id = $pre_room_id;
                $user_ids = '';
                if (empty($data['user_ids'])) {
                    $user_ids = $user_id;
                } else {
                    $user_ids = $data['user_ids'] . ',' . $user_id;
                }
                $this->table_room->set($pre_room_id, [
                    'user_ids' => $user_ids,
                    'current_num' => ++$data['current_num'],
                ]);
            }
        }
        if ($init_user_room_id == 0) {
            $this->create();
            $this->enter($user_id, $room_id);
        }
    }

    /**
     * 创建新房间
     *
     * @return void
     */
    public function create()
    {
        ++$this->last_room_id;
        $this->table_room->set($this->last_room_id, [
            'id' => $this->last_room_id,
        ]);

    }

    /**
     * 获取房间列表
     *
     * @return array
     */
    public function getList()
    {
        $list = [];
        foreach ($this->table_room as $room_id => $data) {
            $list[] = [
                'room_id' => $room_id,
                'user_ids' => explode(',', $data['user_ids']),
                'current_num' => $data['current_num'],
                'status' => $data['status'],
            ];
        }
        return $list;
    }

    /**
     * 获取房间信息
     *
     * @param [type] $room_id
     * @return void
     */
    public function getRoomInfo($room_id)
    {
        if (!$this->table_room->exist($room_id)) {
            throw new Exception('room not exist');
        }
        $room = $this->table_room->get($room_id);
        return [
            'room_id' => $room_id,
            'user_ids' => explode(',', $data['user_ids']),
            'current_num' => $data['current_num'],
            'status' => $data['status'],
        ];
    }
}
