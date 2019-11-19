<?php

class Core
{
    private $room_service;
    private $user_service;
    private $server;

    private $fd_user;
    private $fd_room;

    private $fd; //句柄
    private $data; //用户发送过来的数据
    private $cmd;

    public function __construct($server)
    {
        $this->server = $server;
    }

    /**
     * 处理业务逻辑
     *
     * @param [type] $from_data  数据源：[fd, data]
     * @return void
     */
    public function exec($from_data, $room, $user)
    {
        $this->room_service = $room;
        $this->user_service = $user;
        try {
            $this->fd = $from_data['fd'];
            $all_data = json_decode($from_data['data'], true);
            if (!isset($all_data['cmd']) || !isset($all_data['data'])) {
                return [
                    'code' => -1,
                    'msg' => 'recv data invalid',
                ];
            }
            $this->cmd = $all_data['cmd'];
            $this->data = $all_data['data'];
            if ($this->cmd != 1) {
                $this->fd_user = $this->user_service->checkUser(intval($this->fd));
                if (!$this->fd_user) {
                    return [
                        'code' => -2,
                        'msg' => 'please login',
                    ];
                }
            }
            $action = $this->cmdMap($this->cmd); // 操作码
            $return_data = ['cmd' => $this->cmd];
            $res = $this->$action();
            if ($res && is_array($res)) {
                return array_merge($return_data, $res);
            }
        } catch (Exception $e) {
            return 'system err:' . $e->getMessage();
        }
    }

    // 命令-操作action
    public function cmdMap($cmd)
    {
        $map = [
            1 => 'initUser', //注册/登陆
            2 => 'enterRoom', //进入房间
            3 => 'ready', //点击准备
            4 => 'grab', //抢地主
            5 => 'palyCards', //出牌或压子
            6 => 'pass', //过
        ];
        return $map[$cmd];
    }

    // 注册或登陆
    private function initUser()
    {
        $avatar = isset($this->data['avatar']) ? $this->data['avatar'] : '';
        $user_info = $this->user_service->loginOrCreate($this->data['user_nickname'], $this->data['user_password'], $avatar, intval($this->fd));
        return [
            'code' => 0,
            'data' => $user_info,
        ];
    }

    private function enterRoom()
    {
        $room_id = $this->room_service->enter($this->fd_user['id']);
        $this->fd_user = $this->user_service->checkUser(intval($this->fd)); //刷新此用户信息
        $room_info = $this->room_service->getRoomInfo($room_id);
        //通知
        foreach ($room_info['card_info']['users'] as $user_id => $p_user) {
            $msg = [
                'code' => 0,
            ];
            $this->notifyClient([$user_id], $msg);
        }
    }

    /**
     * 玩家点击准备，系统进行发牌
     *
     * @return void
     */
    private function ready()
    {
        $room = $this->checkRoom();
        $card_info = $room['card_info'];
        $card_info['users'][$this->fd_user['id']]['is_ready'] = 1;
        $this->room_service->update($room['id'], [
            'card_info' => json_encode($card_info),
        ]);
        // 检查是否全部准备，则发送牌给所有玩家
        if (count($card_info['users']) != 3) {
            return;
        }
        foreach ($card_info['users'] as $user) {
            if (!$user['is_ready']) {
                return;
            }
        }
        $this->fapai($room['id']);
    }

    /**
     * 发牌操作
     *
     * @param [int] $room
     * @return void
     */
    private function fapai(int $room_id)
    {
        $room = $this->room_service->getRoomInfo($this->fd_user['room_id']);
        $card_info = $room['card_info'];
        //都已经点击了准备 进行-发牌 和其他处理
        $cards = CardUtils::init();
        $i = 0;
        foreach ($card_info['users'] as &$user) {
            ++$i;
            $user['cards'] = $cards['cards_' . $i];
        }
        $card_info['bottom'] = $cards['bottom'];
        $this->room_service->update($room['id'], [
            'card_info' => json_encode($card_info),
            'status' => Room::STATUS_RUNNING,
        ]);
        //通知
        foreach ($card_info['users'] as $user_id => $p_user) {
            $msg = [
                'code' => 0,
            ];
            $this->notifyClient([$user_id], $msg);
        }
        // 监听出牌超时，进行-AI操作
        $this->listenRoom($room_id);
    }

    /**
     * 监听房间 自动ai处理
     *
     * @param integer $room_id
     * @return void
     */
    private function listenRoom(int $room_id)
    {
        //抢地主： 都没抢 则重新发牌；三次没抢 则随机一人为地主
        $grab_over_times = new Swoole\Atomic(0);
        $grab_timer_id = swoole_timer_tick(1000, function ($grab_timer_id) use ($grab_over_times, $room_id) {
            $room = $this->room_service->getRoomInfo($this->fd_user['room_id']);
            $is_ok = 0;
            if ($room['status'] == Room::STATUS_RUNNING) {
                foreach ($room['card_info']['users'] as $user) {
                    if ($user['role'] == 'landlord') {
                        $is_ok = 1;
                    }
                }
            }
            if ($is_ok) {
                unset($grab_over_times);
                swoole_timer_clear($grab_timer_id);
            } elseif ($grab_over_times->add() > 3) {
                unset($grab_over_times);
                swoole_timer_clear($grab_timer_id);
                // 随机一个地主
                foreach ($room['card_info']['users'] as $user) {
                    if ($user['role'] == 'landlord') {
                        $is_ok = 1;
                    }
                }
                $user_ids = array_keys($room['card_info']['users']);
                $this->fd_user = $this->user_service->getUserInfoByKey('id', $user_ids[mt_rand(0, 2)]); //随机一个用户
                $this->grab();
            }
        });
    }

    /**
     * 监听用户操作
     *
     * @param [type] $room_id
     * @param [type] $user_id
     * @return void
     */
    private function listenUser($user_id)
    {
        echo PHP_EOL;
        echo '----------listenUser--------- userid:' . $user_id . PHP_EOL;
        //出牌：若超时没有出牌 则开始托管模式
        swoole_timer_after(1000, function () use ($user_id) {
            $this->fd_user = $this->user_service->getUserInfoByKey('id', $user_id);
            $room = $this->room_service->getRoomInfo($this->fd_user['room_id']);
            if ($room['card_info']['last_out_user_id'] == $user_id) {
                $this->data['out_cards'] = CardUtils::getAiCard([], $room['card_info']['users'][$user_id]['cards']);
            } else {
                $this->data['out_cards'] = CardUtils::getAiCard($room['card_info']['last_cards'], $room['card_info']['users'][$user_id]['cards']);
            }
            $role = $room['card_info']['users'][$user_id]['role'] == 'landlord' ? '地主' : '农民' . $user_id;
            $last_role = '无';
            if ($room['card_info']['last_out_user_id']) {
                $last_role = $room['card_info']['users'][$room['card_info']['last_out_user_id']]['role'] == 'landlord' ? '地主' : '农民-' . $room['card_info']['last_out_user_id'];
            }

            echo '----------listenUser--------- user:' . $role . ' 已有牌：' . implode(',', CardUtils::convCardsToNatural($room['card_info']['users'][$user_id]['cards'])) . PHP_EOL;
            echo '----------listenUser--------- user:' . $role . ' 上次出牌玩家为：' . $last_role . ' 他的出牌为：' . implode(',', CardUtils::convCardsToNatural($room['card_info']['last_cards'])) . PHP_EOL;
            echo '----------listenUser--------- user:' . $role . ' 本次出牌为：' . (implode(',', CardUtils::convCardsToNatural($this->data['out_cards'])) ?? '要不起！') . PHP_EOL;
            try {
                if (empty($this->data['out_cards'])) {
                    $this->pass();
                } else {
                    $res = $this->palyCards();
                    if ($res) {
                        echo '出牌异常结果：' . json_encode($res, JSON_UNESCAPED_UNICODE);
                    }
                }
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        });
    }

    /**
     * 抢地主 第一个到达的玩家就算抢到
     *
     * @return void
     */
    private function grab()
    {
        $room = $this->checkRoom();
        $card_info = $room['card_info'];
        $roles = array_column($card_info['users'], 'role');
        $is_readys = array_column($card_info['users'], 'is_ready');
        if (array_sum($is_readys) !== 3) {
            throw new Exception('非法操作', -1);
        }
        // 地主已经被抢到了
        if (in_array('landlord', $roles)) {
            return;
        }
        foreach ($card_info['users'] as $user_id => &$user) {
            //抢到地主
            if ($user_id == $this->fd_user['id']) {
                $user['role'] = 'landlord';
                $user['in_round'] = 1; // 开始回合内
                $user['cards'] = array_merge($user['cards'], $card_info['bottom']); // 发放底牌
                $this->room_service->update($room['id'], [
                    'card_info' => json_encode($card_info),
                ]);
            }
        }
        //循环推送消息给client
        foreach ($card_info['users'] as $user_id => $p_user) {
            $msg = [
                'code' => 0,
            ];
            $this->notifyClient([$user_id], $msg);
        }
        $this->listenUser($this->fd_user['id']);
    }

    /**
     * 获取当前请求的用户可以获取到房间内其他信息，包括 玩家牌数量/是否准备/是否托管/是否明牌，底牌，等其他信息
     *
     * @param [int] $target_user_id 需要发送给该用户时的组装数据
     * @return void
     */
    private function getRoomWrapInfo($target_user_id)
    {
        $this->checkRoom(); // 刷新下此房间信息
        $card_info = $this->fd_room['card_info'];
        $return_data = [];
        //房间信息以及杂乱信息
        $return_data['bottom'] = $card_info['bottom'];
        $return_data['status'] = $this->fd_room['status'];

        //用户信息
        $return_data['other_user_info'] = [];
        foreach ($card_info['users'] as $user_id => $user) {
            $the_user = $this->user_service->getuserInfoByKey('id', $user_id, ['nickname', 'avatar']);
            $the_user_info = [
                'id' => (int) $user_id,
                'nickname' => $the_user['nickname'],
                'avatar' => $the_user['avatar'],
                'role' => $card_info['users'][$user_id]['role'],
                'is_ready' => $card_info['users'][$user_id]['is_ready'],
                'playing_cards' => $card_info['users'][$user_id]['playing_cards'],
                'in_round' => $card_info['users'][$user_id]['in_round'],
                'card_num' => count($user['cards']),
                'place' => $card_info['users'][$user_id]['place'],
                'is_win' => $card_info['users'][$user_id]['is_win'],
            ];
            // 发送给目标用户所显示的信息
            if ($user_id == $target_user_id) {
                $return_data['self'] = array_merge($the_user_info, [
                    'cards' => $card_info['users'][$user_id]['cards'],
                ]);
            } else {
                $merge_other_data = [];
                // 已经结束则将卡牌信息展示给所有玩家
                if ($this->fd_room['status'] == Room::STATUS_OVER) {
                    $merge_other_data['cards'] = $card_info['users'][$user_id]['cards'];
                }
                // 目标用户的其他玩家信息 //TODO
                $return_data['other_user_info'][$user_id] = array_merge($the_user_info, $merge_other_data);
            }
        }
        return $return_data;
    }

    /**
     * 出牌游戏 先验证->后记录
     *
     * @return void
     */
    private function palyCards()
    {
        $room = $this->checkRoom(2);
        $card_info = $room['card_info'];
        $fd_user_card_info = $card_info['users'][$this->fd_user['id']];
        if (!$fd_user_card_info['in_round']) {
            throw new Exception('不在自己的回合内', -3);
        }
        // echo '----------cards--------' . implode(',', CardUtils::convCardsToNatural($this->data['out_cards'])) . PHP_EOL;
        $err_cards = array_diff($this->data['out_cards'], $fd_user_card_info['cards']);
        if (!empty($err_cards)) {
            throw new Exception('出牌错误-出了不在自己手中的牌', -3);
        }
        $intersect_cards = array_intersect($this->data['out_cards'], $card_info['stack_cards']);
        if (!empty($intersect_cards)) {
            throw new Exception('出牌错误-竟然出了牌堆里的牌', -3);
        }
        $check_last_cards = $card_info['last_cards'];
        if ($card_info['last_out_user_id'] == $this->fd_user['id']) {
            $check_last_cards = [];
        }
        list($code, $expand) = CardUtils::checkPass($check_last_cards, $this->data['out_cards']);
        if ($code != 0) {
            return [
                'code' => $code,
                'msg' => $expand,
            ];
        }
        $card_info['step']++;
        $card_info['last_cards'] = $this->data['out_cards'];
        $card_info['stack_cards'] = array_merge($card_info['stack_cards'], $this->data['out_cards']);
        $next_place = ++$fd_user_card_info['place'] > 3 ? 1 : $fd_user_card_info['place']; //顺位下一个出牌
        $is_over = 0;
        $win_role = '';

        // 清理牌
        foreach ($fd_user_card_info['cards'] as $k => $p_card) {
            if (in_array($p_card, $this->data['out_cards'])) {
                unset($fd_user_card_info['cards'][$k]);
            }
        }
        $fd_user_card_info['cards'] = array_values($fd_user_card_info['cards']);
        // 胜利/结束
        if (empty($fd_user_card_info['cards'])) {
            $room['status'] = Room::STATUS_OVER;
            $is_over = 1;
            $win_role = $fd_user_card_info['role'];
        }
        $next_paly_user_id = 0;
        // 各个用户数据逻辑处理
        foreach ($card_info['users'] as $user_id => &$user) {
            if ($user_id == $this->fd_user['id']) {
                $user['in_round'] = 0;
                $user['cards'] = $fd_user_card_info['cards'];
            } elseif ($user['place'] == $next_place) {
                $next_paly_user_id = $user_id;
                $user['in_round'] = 1;
            }
            if ($is_over && $win_role == $user['role']) {
                $user['is_win'] = 1;
            }
        }

        $card_info['last_out_user_id'] = $this->fd_user['id'];
        $this->room_service->update($room['id'], [
            'card_info' => json_encode($card_info),
            'status' => $room['status'],
        ]);
        //循环推送消息给client
        foreach ($card_info['users'] as $p_user_id => $p_user) {
            $msg = [
                'code' => 0,
            ];
            $this->notifyClient([$p_user_id], $msg);
        }
        // 结束后 重新初始化数据
        if ($is_over) {
            echo '----------- 游戏结束，胜利者:' . ($win_role == 'landlord' ? '地主' : '平民');
            $this->room_service->overGame($room['id']);
        } else {
            // 监听下一个用户发牌
            $this->listenUser($next_paly_user_id);
        }
    }

    /**
     * 托管
     *
     * @return void
     */
    private function tuoguan()
    {
        $room = $this->checkRoom(2);
        $card_info = $room['card_info'];
        if ($room['status'] != Room::STATUS_RUNNING) {
            return;
        }
        $card_info['users'][$this->fd_user['id']]['is_tuoguan'] = 1;
        $this->room_service->update($room['id'], [
            'card_info' => json_encode($card_info),
        ]);
        //循环推送消息给client
        foreach ($card_info['users'] as $user_id => $p_user) {
            $msg = [
                'code' => 0,
            ];
            $this->notifyClient([$user_id], $msg);
        }
    }

    /**
     * 过牌
     *
     * @return void
     */
    private function pass()
    {
        $room = $this->checkRoom(2);
        $card_info = $room['card_info'];
        $fd_user_card_info = $card_info['users'][$this->fd_user['id']];
        if (!$fd_user_card_info['in_round']) {
            throw new Exception('不在自己的回合内', -3);
        }
        $card_info['step']++;
        $next_place = ++$fd_user_card_info['place'] > 3 ? 1 : $fd_user_card_info['place']; //顺位下一个出牌
        // 各个用户数据逻辑处理
        $next_paly_user_id = 0;
        foreach ($card_info['users'] as $user_id => &$user) {
            if ($user_id == $this->fd_user['id']) {
                $user['in_round'] = 0;
                $user['cards'] = $fd_user_card_info['cards'];
            } elseif ($user['place'] == $next_place) {
                $next_paly_user_id = $user_id;
                $user['in_round'] = 1;
            }
        }
        $this->room_service->update($room['id'], [
            'card_info' => json_encode($card_info),
            'status' => $room['status'],
        ]);
        //循环推送消息给client
        foreach ($card_info['users'] as $p_user_id => $p_user) {
            $msg = [
                'code' => 0,
            ];
            $this->notifyClient([$p_user_id], $msg);
        }
        // 监听下一个用户发牌
        $this->listenUser($next_paly_user_id);
    }

    /**
     * 检验房间是否正确
     *
     * @param integer $level  检测等级 1-普通 2-是否还正在玩
     * @return void
     */
    private function checkRoom($level = 1)
    {
        if (empty($this->fd_user['room_id'])) {
            throw new Exception('未进入房间', -3);
        }
        $room = $this->room_service->getRoomInfo($this->fd_user['room_id']);
        if (!$room) {
            throw new Exception('房间不存在', -3);
        }
        if ($level >= 2) {
            if ($room['status'] !== Room::STATUS_RUNNING) {
                throw new Exception('游戏不在开始阶段', -3);
            }
        }
        $this->fd_room = $room;
        return $this->fd_room;
    }

    /**
     * 通知client
     *
     * @param array $user_ids
     * @param string $msg
     * @return void
     */
    private function notifyClient(array $user_ids, array $msg)
    {
        $ws_server = $this->server->server;
        $fds = [];
        foreach ($user_ids as $user_id) {
            $user = $this->user_service->getUserInfoByKey('id', $user_id);
            $fds = [$user['fd'] => $user_id];
        }
        foreach ($ws_server->connections as $conn_fd) {
            foreach ($fds as $fd => $p_user_id) {
                if (intval($conn_fd) == $fd) {
                    $msg = array_merge([
                        'cmd' => $this->cmd,
                    ], $this->getRoomWrapInfo($p_user_id), $msg);

                    $res = $ws_server->push($conn_fd, json_encode($msg), WEBSOCKET_OPCODE_BINARY);
                    $this->server->log('Send [' . $conn_fd . '] >>> ' . json_encode($msg, JSON_UNESCAPED_UNICODE));
                }
            }
        }
    }
}
