<?php

class Room
{
    private $table_room;
    private $server;

    private $atomic; // 用来进行表的自增id

    const STATUS_READY = 0; // 未开始
    const STATUS_RUNNING = 1; // 已开始
    const STATUS_OVER = 3; // 已结束

    public function __construct($server)
    {
        $this->server = $server;
        $this->table_room = new Swoole\Table(1024);
        $this->table_room->column('id', swoole_table::TYPE_INT, 4); //  房间id
        $this->table_room->column('user_ids', swoole_table::TYPE_STRING, 512); // 房间用户id
        $this->table_room->column('current_num', swoole_table::TYPE_FLOAT); // 当前房间内人数
        $this->table_room->column('status', swoole_table::TYPE_INT);
        /**
         * card_info信息为json字符串，属性：
         *          {
         *              "bottom":[5,2,1],  底牌
         *              "step": 0, 出牌的步数/回合
         *              "last_cards": [] 上次出牌 如果为空代表‘不要’
         *              "last_out_user_id": [] 上次出牌的用户id
         *              "stack_cards": [] 牌堆 所有玩家打出的牌记录
         *              "users":
         *                  '1':{   用户id
         *                      'cards':[1,2,3,10,41,51,52], 手上的牌
         *                      'role':'landlord'   地主landlord 平民civilian
         *                      'double': 1 当前倍率
         *                      'is_ready': 1 准备状态
         *                      'playing_cards': [1,2,3] 当前出牌信息
         *                      'in_round':0 是否该你出牌了-在回合内，
         *                      'place': 1 自己所在的位置 对应着发牌顺序 1->2->3->1 这样轮回
         *                      'is_win': 0
         *                      'is_tuoguan': 0
         *                      },
         *                  '4':{},
         *                  '19':{},
         *              }
         *          }
         */
        $this->table_room->column('card_info', swoole_table::TYPE_STRING, 1024); //卡牌信息 json字符串：{"bottom":[],'1':{},'2':{}}
        $this->table_room->create();
        $this->atomic = new Swoole\Atomic(0); // 用来user表的自增id
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
            foreach ($this->table_room as $the_room) {
                if ($the_room['current_num'] >= 3 || $the_room['status'] == self::STATUS_RUNNING) {
                    continue;
                }
                $init_user_room_id = $the_room['id'];
                $user_ids = '';
                if (empty($the_room['user_ids'])) {
                    $user_ids = $user_id;
                } else {
                    $user_ids = $the_room['user_ids'] . ',' . $user_id;
                }
                $place = 1; //位置
                //  真正进入房间
                $card_info = json_decode($the_room['card_info'], true);
                if (!empty($card_info['users'])) {
                    $places = array_column($card_info['users'], 'place');
                    $all_places = [1, 2, 3];
                    $place = $all_places[array_rand(array_diff($all_places, $places), 1)];
                }
                $card_info['users'][$user_id] = [
                    'cards' => [],
                    'role' => 'civilian',
                    'double' => 1,
                    'is_ready' => 0,
                    'playing_cards' => [],
                    'in_round' => 0,
                    'place' => $place,
                    'is_win' => 0,
                    'is_tuoguan' => 0,
                ];
                $this->table_room->set($init_user_room_id, [
                    'user_ids' => $user_ids,
                    'current_num' => ++$the_room['current_num'],
                    'card_info' => json_encode($card_info),
                ]);
                // 设置用户属性-房间id
                $this->server->user_server->update($user_id, [
                    'room_id' => $init_user_room_id,
                ]);
            }
        }
        if ($init_user_room_id == 0) {
            $this->create();
            return $this->enter($user_id, $room_id);
        }
        return $init_user_room_id;
    }

    /**
     * 创建新房间
     *
     * @return void
     */
    public function create()
    {
        $new_room_id = $this->generateNewRoomId();
        $this->table_room->set($new_room_id, [
            'id' => $new_room_id,
            'user_ids' => '',
            'current_num' => 0,
            'status' => self::STATUS_READY,
            'card_info' => json_encode([
                'bottom' => [],
                'users' => [],
                'last_cards' => [],
                'last_out_user_id' => null,
                'stack_cards' => [],
                'step' => 0,
            ]),
        ]);
    }

    /**
     * 结束游戏 ，清理房间用户个别信息
     *
     * @return void
     */
    public function overGame($room_id)
    {
        $room = $this->getRoomInfo($room_id);
        $status = self::STATUS_READY;
        $card_info = $room['card_info'];
        $users = [];
        foreach ($card_info['users'] as $user_id => $user) {
            $user[$user_id] = [
                'cards' => [],
                'role' => 'civilian',
                'double' => 1,
                'is_ready' => 0,
                'playing_cards' => [],
                'in_round' => 0,
                'place' => $user['place'],
                'is_win' => 0,
            ];
        }
        $card_info = [
            'bottom' => [],
            'users' => $users,
            'last_cards' => [],
            'last_out_user_id' => null,
            'stack_cards' => [],
            'step' => 0,
        ];
        $this->update($room_id, [
            'card_info' => json_encode($card_info),
            'status' => $status,
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
            'id' => $room_id,
            'user_ids' => explode(',', $room['user_ids']),
            'current_num' => $room['current_num'],
            'status' => $room['status'],
            'card_info' => json_decode($room['card_info'], true),
        ];
    }

    public function update($room_id, array $field)
    {
        if (!$this->table_room->exist($room_id)) {
            throw new Exception('user not exist');
        }
        return $this->table_room->set($room_id, $field);
    }

    private function generateNewRoomId()
    {
        return $this->atomic->add();
    }
}
