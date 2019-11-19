<?php
/**
 * 斗地主逻辑判断
 */
class CardUtils
{
    /**
     * 按照花色 每组取模13 分为4种花色 分别代表： ♠ ♦️ ♥️ ♣️
     *
     * @var array
     */
    public static $card_map = [
        1 => '3', 2 => '4', 3 => '5', 4 => '6', 5 => '7', 6 => '8', 7 => '9', 8 => '10', 9 => 'J', 10 => 'Q', 11 => 'K', 12 => 'A', 13 => '2',
        14 => '3', 15 => '4', 16 => '5', 17 => '6', 18 => '7', 19 => '8', 20 => '9', 21 => '10', 22 => 'J', 23 => 'Q', 24 => 'K', 25 => 'A', 26 => '2',
        27 => '3', 28 => '4', 29 => '5', 30 => '6', 31 => '7', 32 => '8', 33 => '9', 34 => '10', 35 => 'J', 36 => 'Q', 37 => 'K', 38 => 'A', 39 => '2',
        40 => '3', 41 => '4', 42 => '5', 43 => '6', 44 => '7', 45 => '8', 46 => '9', 47 => '10', 48 => 'J', 49 => 'Q', 50 => 'K', 51 => 'A', 52 => '2',
        53 => 'JOKER_BIG', 54 => 'JOKER_SMALL', //大小王
    ];

    const TYPE_DAN = 1; //单牌
    const TYPE_DUIZI = 2; //对子
    const TYPE_SANZHANG = 3; //三张
    const TYPE_SANDAIYI = 4; //三带
    const TYPE_SHUNZI = 5; //顺子
    const TYPE_SIDAIER = 6; //四带二
    const TYPE_ZHADAN = 7; //炸弹
    const TYPE_WANGZHA = 8; //王炸

    const CARD_JOKER_BIG_ID = 53;
    const CARD_JOKER_SMALL_ID = 54;

    /**
     * 初始化牌
     * 一人17张牌 3张底牌
     *
     * @return void
     */
    public static function init()
    {
        $cards = self::$card_map;
        $user_cards1 = array_rand($cards, 17);
        foreach ($user_cards1 as $u_card) {
            unset($cards[$u_card]);
        }
        $user_cards2 = array_rand($cards, 17);
        foreach ($user_cards2 as $u_card) {
            unset($cards[$u_card]);
        }
        $user_cards3 = array_rand($cards, 17);
        foreach ($user_cards3 as $u_card) {
            unset($cards[$u_card]);
        }
        return [
            'cards_1' => self::sort($user_cards1),
            'cards_2' => self::sort($user_cards2),
            'cards_3' => self::sort($user_cards3),
            'bottom' => self::sort(array_keys($cards)),
        ];
    }

    /**
     * 转换卡牌id为人类可认识的自然牌
     *
     * @param array $cards
     * @return void
     */
    public static function convCardsToNatural(array $cards)
    {
        $cards = array_reverse(self::sort($cards));
        $return_data = [];
        foreach ($cards as $card) {
            $return_data[] = self::$card_map[$card];
        }
        return $return_data;
    }

    /**
     * 判断这次出牌行为是否合理
     *
     * @param array $last_cards     上轮出堆牌
     * @param array $current_cards  当前出堆牌
     * @param array $stack_cards    牌堆里堆牌
     * @return void
     */
    public static function checkPass(array $last_cards, array $current_cards)
    {
        $code = 0;
        $msg = 'ojbk';
        $c_type = self::getType($current_cards);
        if ($c_type < 0) {
            $code = 1001;
            $msg = '出牌不合理';
            goto last;
        }
        if (empty($last_cards)) {
            return [$code, $msg];
        }

        $l_type = self::getType($last_cards);

        //判断大小
        if ($c_type != $l_type) {
            //上家是王炸 打不过
            if ($l_type == self::TYPE_WANGZHA) {
                $code = 1002;
                $msg = '你的牌大不过人家';
                goto last;
            }
            // 炸弹/王炸 可大过任何其他类型牌
            if ($c_type !== self::TYPE_ZHADAN && $c_type !== self::TYPE_WANGZHA) {
                $code = 1002;
                $msg = '你的牌大不过人家';
                goto last;
            }
            goto last;
        } else {
            if (count($last_cards) !== count($current_cards)) {
                $code = 1001;
                $msg = '出牌不合理';
                goto last;
            }
            if (self::compare($current_cards, $last_cards, $l_type) > 0) {
                goto last;
            } else {
                $code = 1002;
                $msg = '你的牌大不过人家';
                goto last;
            }
        }

        last:
        return [$code, $msg];
    }

    /**
     * 比较大小
     *
     * @param array $cards1 比较1
     * @param array $cards2  比较2
     * @param integer $type 此牌的类型
     * @return integer 大于0代表 cards1大于cards2 反之小于
     */
    public static function compare(array $cards1, array $cards2, int $type): int
    {
        $cards1 = self::getModValue($cards1);
        $cards2 = self::getModValue($cards2);
        //单张 对子 三张 顺子
        if ($type == self::TYPE_DAN || $type == self::TYPE_DUIZI || $type == self::TYPE_SANZHANG || $type == self::TYPE_SHUNZI) {
            return array_keys($cards1)[0] > array_keys($cards2)[0] ? 1 : -1;
        }
        // 三带1/2
        if ($type == self::TYPE_SANDAIYI) {
            return array_flip($cards1)[3] > array_flip($cards2)[3] ? 1 : -1; // key->value 数组翻转
        }
        // 四带二 炸弹
        if ($type == self::TYPE_SIDAIER || $type == self::TYPE_ZHADAN) {
            return array_flip($cards1)[4] > array_flip($cards2)[4] ? 1 : -1;
        }
        throw new \Exception('type err', 2001);
    }

    /**
     * 获取最优出牌解-AI
     *
     * @param array $last_cards
     * @param array $has_cards
     * @return void
     */
    public static function getAiCard(array $last_cards, array $has_cards)
    {
        if (!$has_cards) {
            return [];
        }
        if (!$last_cards) {
            $has_cards = self::sort($has_cards);
            return [$has_cards[count($has_cards) - 1]];
        }
        $type = self::getType($last_cards);
        $has_mods = self::getModValue($has_cards);
        $tmp_cards = [];
        if ($type == self::TYPE_DAN) {
            $tmp_cards = self::getDanZhang($has_cards);
        }
        if ($type == self::TYPE_DUIZI) {
            $tmp_cards = self::getDuiZi($has_cards);
        }
        if ($type == self::TYPE_SANZHANG) {
            $tmp_cards = self::getSanZhang($has_cards);
        }
        if ($type == self::TYPE_SANDAIYI) {
            $tmp_cards = self::getSanDaiYi($has_cards, count($last_cards));
        }
        if ($type == self::TYPE_SHUNZI) {
            $tmp_cards = self::getShunZi($has_cards, count($last_cards));
        }
        if ($type == self::TYPE_SIDAIER) {
            $tmp_cards = self::getSiDaiEr($has_cards, count($last_cards));
        }
        if ($type == self::TYPE_ZHADAN) {
            $tmp_cards = self::getZhaDan($has_cards);
        }
        if ($type == self::TYPE_WANGZHA) {
            return [];
        }

        // 由低到高分别出牌
        foreach ($tmp_cards as $tmp_card) {
            if (self::compare($tmp_card, $last_cards, $type) > 0) {
                return $tmp_card;
            }
        }
        // 炸弹可以打过其他非炸弹的牌
        if ($type != self::TYPE_ZHADAN) {
            $tmp_cards = self::getZhaDan($has_cards);
            if (!$tmp_cards) {
                return [];
            }
            return $tmp_cards[0];
        }

        //王炸可以打过所有牌
        if (in_array(self::CARD_JOKER_BIG_ID, $has_cards) && in_array(self::CARD_JOKER_SMALL_ID, $has_cards)) {
            return [self::CARD_JOKER_BIG_ID, self::CARD_JOKER_SMALL_ID];
        }
        return [];
    }

    /**
     * 获取他的顺子，三带1 等
     *
     * @param array $cards
     * @return void
     */
    public static function getModValue(array $cards)
    {
        $mods = [];
        $jokers = [self::CARD_JOKER_BIG_ID, self::CARD_JOKER_SMALL_ID];
        foreach ($cards as $card) {
            if (in_array($card, $jokers)) {
                $mods[] = $card;
                continue;
            }
            $mods[] = ($card % 13) == 0 ? 13 : $card % 13;
        }
        $agge_arr = array_count_values($mods); // 聚合
        ksort($agge_arr);
        return $agge_arr;
    }

    /**
     * 获取此牌的类型
     *
     * @param [array] $cards
     * @return void
     */
    public static function getType(array $cards)
    {
        $is_wangzha = self::isWangZha($cards);
        $is_zhadan = self::isZhaDan($cards);
        $is_shunzi = self::isShunZi($cards);
        $is_sanzhang = self::isSanZhang($cards);
        $is_duizi = self::isDuiZi($cards);
        $is_sandaiyi = self::isSanDaiYi($cards);
        $is_sidaier = self::isSiDaiEr($cards);
        $is_dan = count($cards) === 1 ? true : false;
        if ($is_wangzha) {
            return self::TYPE_WANGZHA;
        }
        if ($is_zhadan) {
            return self::TYPE_ZHADAN;
        }
        if ($is_sidaier) {
            return self::TYPE_SIDAIER;
        }
        if ($is_shunzi) {
            return self::TYPE_SHUNZI;
        }
        if ($is_sanzhang) {
            return self::TYPE_SANZHANG;
        }
        if ($is_sandaiyi) {
            return self::TYPE_SANDAIYI;
        }
        if ($is_duizi) {
            return self::TYPE_DUIZI;
        }
        if ($is_dan) {
            return self::TYPE_DAN;
        }
        return -1;
    }

    /**
     * 排序 给前端显示
     *
     * @param array $cards
     * @return void
     */
    public static function sort(array $cards)
    {
        $tmp_cards = [];
        $jokers = [self::CARD_JOKER_BIG_ID, self::CARD_JOKER_SMALL_ID];
        foreach ($cards as $card) {
            if (in_array($card, $jokers)) {
                $tmp_cards[$card] = $card;
                continue;
            }
            $tmp_cards[$card] = ($card % 13) == 0 ? 13 : $card % 13;
        }
        asort($tmp_cards);
        return array_reverse(array_keys($tmp_cards));
    }

    /**
     * 将卡牌上的值，转化为系统内对应的id
     *
     * @param integer $value    卡牌上的值
     * @param array $cards  在这个排堆范围内转化
     * @param integer $has_num  value目标需要转化的张数 null 代表所有
     * @return void
     */
    public static function convertValueToCards(int $value, array $cards, int $has_num = null)
    {
        $return_data = [];
        foreach (self::$card_map as $static_card_id => $static_card_value) {
            if (in_array($static_card_id, [self::CARD_JOKER_BIG_ID, self::CARD_JOKER_SMALL_ID])) {
                $static_mod = $static_card_id;
            } else {
                $static_mod = ($static_card_id % 13) == 0 ? 13 : $static_card_id % 13;
            }
            if ($has_num !== null && count($return_data) >= $has_num) {
                break;
            }
            if ($value == $static_mod && in_array($static_card_id, $cards)) {
                $return_data[] = $static_card_id;
            }
        }
        return $return_data;
    }

    /**
     * 是否为王炸
     *
     * @param array $cards
     * @return boolean
     */
    public static function isWangzha(array $cards)
    {
        $jokers = [self::CARD_JOKER_BIG_ID, self::CARD_JOKER_SMALL_ID];
        if (count($cards) != 2 || !in_array($cards[0], $jokers) || !in_array($cards[1], $jokers)) {
            return false;
        }
        return true;
    }

    /**
     * 从指定的牌里获取单张
     *
     * @param array $cards
     * @return void
     */
    public static function getDanZhang(array $cards)
    {
        $after_cards = [];
        $mods = self::getModValue($cards);
        // $value 代表实际的牌上的值 1，2，3等等  count代表这张种一共几张
        foreach ($mods as $value => $count) {
            if ($count == 1) {
                $after_cards[] = self::convertValueToCards($value, $cards);
            }
        }
        if (!$after_cards) {
            return [[$cards[0]]];
        }
        return $after_cards;
    }

    /**
     * 是否为顺子
     *
     * @param array $cards
     * @return boolean
     */
    public static function isShunZi(array $cards)
    {
        if (count($cards) < 5) {
            return false;
        }
        $mods = self::getModValue($cards);
        // 带2 和王的不行 key为13的就是2
        if (isset($mods[13]) || isset($mods[53]) || isset($mods[54])) {
            return false;
        }
        // 不是单张牌组成
        if (array_sum($mods) !== count($mods)) {
            return false;
        }
        $mod_keys = array_keys($mods);
        while (1) {
            $c_key = current($mod_keys);
            $n_key = next($mod_keys);
            if (!$c_key || !$n_key) {
                break;
            }
            if ($n_key - $c_key != 1) {
                return false;
            }
        }
        return true;
    }
    /**
     * 从指定的牌里获取对子
     *
     * @param array $cards
     * @return void
     */
    public static function getDuiZi(array $cards)
    {
        $after_cards = [];
        $mods = self::getModValue($cards);
        foreach ($mods as $value => $count) {
            if ($count == 2) {
                $after_cards[] = self::convertValueToCards($value, $cards, 2);
            }
        }
        // 三个或，四个的
        foreach ($mods as $value => $count) {
            if ($count == 3 || $count == 4) {
                $after_cards[] = self::convertValueToCards($value, $cards, 2);
            }
        }
        return $after_cards;
    }

    /**
     * 从指定的牌里获取三张
     *
     * @param array $cards
     * @return void
     */
    public static function getSanZhang(array $cards)
    {
        $after_cards = [];
        $mods = self::getModValue($cards);
        foreach ($mods as $value => $count) {
            if ($count == 3) {
                $after_cards[] = self::convertValueToCards($value, $cards);
            }
        }
        return $after_cards;
    }

    /**
     * 从指定的牌里获取三带一
     *
     * @param array $cards
     * @param int $total_count 牌的中张数
     * @return void
     */
    public static function getSanDaiYi(array $cards, int $total_count)
    {
        $dai_cards = [];
        if ($total_count == 5) { //带一对
            $dai_cards = self::getDuiZi($cards);
        } else { //带一张
            $dai_cards = self::getDanZhang($cards);
        }
        if (empty($dai_cards)) {
            return [];
        }
        $dai_cards = $dai_cards[0]; //先得到可以带的牌，去除后获取三张进行组合
        foreach ($cards as $key => $value) {
            if (in_array($value, $dai_cards)) {
                unset($cards[$key]);
            }
        }

        $after_cards = [];
        $mods = self::getModValue($cards);
        foreach ($mods as $value => $count) {
            if ($count == 3) {
                $after_cards[] = array_merge(self::convertValueToCards($value, $cards), $dai_cards);
            }
        }
        return $after_cards;
    }

    /**
     * 从指定的牌里获取四带2
     *
     * @param array $cards
     * @param int $total_count 牌的中张数
     * @return void
     */
    public static function getSiDaiEr(array $cards, int $total_count)
    {
        $dai_cards = [];
        if ($total_count == 8) { //带2对
            $dai_cards = self::getDanZhang($cards);
        } else { //带2张
            $dai_cards = self::getDuiZi($cards);
        }
        if (empty($dai_cards) || count($dai_cards) < 2) {
            return [];
        }
        $dai_cards = array_merge($dai_cards[0], $dai_cards[1]); //先得到可以带的牌，去除后获取三张进行组合
        foreach ($cards as $key => $value) {
            if (in_array($value, $dai_cards)) {
                unset($cards[$key]);
            }
        }
        $after_cards = [];
        $mods = self::getModValue($cards);
        foreach ($mods as $value => $count) {
            if ($count == 4) {
                $after_cards[] = array_merge(self::convertValueToCards($value, $cards), $dai_cards);
            }
        }
        return $after_cards;
    }

    /**
     * 从指定的牌里获取顺子
     *
     * @param array $cards
     * @param int $total_count 牌的中张数
     * @return void
     */
    public static function getShunZi(array $cards, int $total_count)
    {
        $after_cards = [];
        $mods = self::getModValue($cards);
        $mod_keys = array_keys($mods);
        $after_values = [];
        while (count($mod_keys) >= $total_count) {
            $after_value = [current($mod_keys)];
            $tmp_unset_values = [];
            while (1) {
                $c_key = current($mod_keys);
                $n_key = next($mod_keys);
                $tmp_unset_values = [$c_key];
                if (!$c_key || !$n_key) {
                    break;
                }
                if ($n_key - $c_key != 1) {
                    // 将之前没有连续的值 全部删除 ，重新用新的进行遍历
                    foreach ($tmp_unset_values as $tmp_unset_value) {
                        $tmp_key = array_search($tmp_unset_value, $mod_keys);
                        unset($mod_keys[$tmp_key]);
                    }
                    $after_value = [$n_key]; //重置
                    continue;
                }
                $after_value[] = $n_key;
                if (count($after_value) == $total_count) {
                    break;
                }
            }
            // 匹配到一个 入数组，删除第一个元素
            if (count($after_value) == $total_count) {
                $tmp_key = array_search($after_value[0], $mod_keys);
                unset($mod_keys[$tmp_key]);
                $after_values[] = $after_value;
            }
            $mod_keys = array_values($mod_keys);
        }
        //做转化
        foreach ($after_values as $after_value) {
            $tmp_arr = [];
            foreach ($after_value as $tmp_value) {
                $tmp_arr = array_merge($tmp_arr, self::convertValueToCards($tmp_value, $cards, 1));
            }
            $after_cards[] = $tmp_arr;
        }
        return $after_cards;
    }

    /**
     * 从指定的牌里获取炸弹
     *
     * @param array $cards
     * @param int $total_count 牌的中张数
     * @return void
     */
    public static function getZhaDan(array $cards)
    {
        $after_cards = [];
        $mods = self::getModValue($cards);
        foreach ($mods as $value => $count) {
            if ($count == 4) {
                $after_cards[] = self::convertValueToCards($value, $cards);
            }
        }
        return $after_cards;
    }

    /**
     * 是否对子
     *
     * @param array $cards
     * @return boolean
     */
    public static function isDuiZi(array $cards)
    {
        $mods = self::getModValue($cards);
        if (count($mods) != 1) {
            return false;
        }
        $tmp_values = array_values($mods);
        rsort($tmp_values);
        $format = [
            [2],
        ];
        if (!in_array($tmp_values, $format)) {
            return false;
        }
        return true;
    }

    /**
     * 是否三张
     *
     * @param array $cards
     * @return boolean
     */
    public static function isSanZhang(array $cards)
    {
        $mods = self::getModValue($cards);
        if (count($mods) != 1) {
            return false;
        }
        $tmp_values = array_values($mods);
        rsort($tmp_values);
        $format = [
            [3],
        ];
        if (!in_array($tmp_values, $format)) {
            return false;
        }
        return true;
    }

    /**
     * 是否三带一
     *
     * @param array $cards
     * @return boolean
     */
    public static function isSanDaiYi(array $cards)
    {
        $mods = self::getModValue($cards);
        if (count($mods) != 2) {
            return false;
        }
        $tmp_values = array_values($mods);
        rsort($tmp_values);
        $format = [
            [3, 1],
            [3, 2],
        ];
        if (!in_array($tmp_values, $format)) {
            return false;
        }
        return true;
    }

    /**
     * 是否四带二
     *
     * @param array $cards
     * @return boolean
     */
    public static function isSiDaiEr(array $cards)
    {
        $mods = self::getModValue($cards);
        if (count($mods) != 3) {
            return false;
        }
        $tmp_values = array_values($mods);
        rsort($tmp_values);
        $format = [
            [4, 2, 2],
            [4, 1, 1],
        ];
        if (!in_array($tmp_values, $format)) {
            return false;
        }
        return true;
    }

    /**
     * 是否炸弹
     *
     * @param array $cards
     * @return boolean
     */
    public static function isZhaDan(array $cards)
    {
        $mods = self::getModValue($cards);
        $mod_values = array_values($mods);
        if (count($mod_values) !== 1 || $mod_values[0] !== 4) {
            return false;
        }
        return true;
    }
}

// $res = CardUtils::init();
// var_dump(json_encode($res));

// var_dump(json_encode(CardUtils::getModValue($res['cards_1'])));
// var_dump(json_encode(CardUtils::getModValue($res['cards_2'])));
// var_dump(json_encode(CardUtils::getModValue($res['cards_3'])));

// echo PHP_EOL;
// $bool = CardUtils::isShunZi([1, 2, 16, 31, 45, 30]); // 345678
// echo '是否为顺子:' . ($bool ? 'true' : 'false') . PHP_EOL;
// $bool = CardUtils::isSanDaiYi([1, 14, 27, 31]); // 333 7
// echo '是否为三带:' . ($bool ? 'true' : 'false') . PHP_EOL;
// $bool = CardUtils::isSiDaiEr([1, 14, 27, 40, 17, 30, 18, 31]); //3333 66 77
// echo '是否为四带:' . ($bool ? 'true' : 'false') . PHP_EOL;

// $a = [1, 2, 3, 4, 5, 6];
// $b = [17, 18, 19, 20, 21, 22];
// list($code, $mix) = CardUtils::checkPass($a, $b);
// echo '是否验证通过:' . ($code == 0 ? 'true' : 'false code为:' . $code . ' msg为:' . $mix) . PHP_EOL;

// $res = CardUtils::getAiCard([1, 14], [2, 16, 3, 4, 5, 6]); // 上家牌为33 出牌为55
// echo 'ai 出牌为：' . implode(',', $res) . PHP_EOL;

// $res = CardUtils::getAiCard([1, 14, 40, 32, 45], [2, 16, 3, 29, 4, 5, 6, 19]); // 上家牌为33388 出牌为44488
// echo 'ai 出牌为：' . implode(',', $res) . PHP_EOL;

// $res = CardUtils::getAiCard([2, 15], [1, 14, 2, 15, 3, 16, 29, 19]); // 上家牌为33 出牌为44
// echo 'ai 出牌为：' . implode(',', $res) . PHP_EOL;

// $res = CardUtils::getAiCard([1, 2, 3, 4, 5], [14, 15, 16, 17, 18, 19]); // 上家牌为34567 出牌为45678
// echo 'ai 出牌为：' . implode(',', $res) . PHP_EOL;

// $res = CardUtils::getAiCard([], [54, 26, 38, 12, 35, 22, 47, 7, 32, 6, 19, 31, 44, 30, 3, 28, 41, 52, 51, 21]);
// echo 'ai 出牌为：' . implode(',', $res) . PHP_EOL;

// $res = CardUtils::getAiCard([54], [53, 36, 48, 47, 8, 33, 44, 30, 4, 17, 43, 3, 16, 27, 1]);
// echo 'ai 出牌为：' . implode(',', $res) . PHP_EOL;

// $res = CardUtils::convCardsToNatural([53, 36, 48, 47, 8, 33, 44, 30, 4, 17, 43, 3, 16, 27, 1]);
// echo '转化为：' . implode(',', $res) . PHP_EOL;
