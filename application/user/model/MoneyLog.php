<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\model;

use think\Model as ThinkModel;

/**
 * 余额变动记录表
 * Class Money
 * @package app\user\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/3 16:38
 */
class MoneyLog extends ThinkModel
{
    protected $table = "__USER_MONEY_LOG__";

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    //记录类型。你有新的类型，请添加到这里
    public static $types = [
//        '1' => '会员充值',
        '2' => '会员消费',
//        '3' => '管理员操作',
//        '4' => '会员提现',
//        '5' => '管理员拒绝提现，返还金额',
//        '6' => '会员分成',
        '7' => '余额支付',
//        '8' => '分享赚',
//        '9' => '自购返',
//        '10' => '分销佣金',
        '11' => '退款余额'
    ];

    /**
     * 获取记录类型
     * @param $id
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/4/28 9:05
     * @editor XX [ xxx@qq.com ]
     * @updated 2019.03.30
     */
    public static function get_type($id)
    {
        return self::$types[$id];
    }

    /**
     * 会员余额变动(含充值，打赏)
     * @param int $user_id 会员ID
     * @param int $before_money 会员现余额
     * @param int $money 增加金额 负数就是减少
     * @param int $type 消费类型
     * @param int $consumption 传入此金额记录总消费
     * @editor 李志豪 [ 995562569@qq.com ]
     * @updated 2019.05.21
     * @return boolean
     * @throws \Exception
     */

    public static function changeMoney($user_id, $before_money, $money, $type = 1, $remark = '', $ordeNo = '', $consumption = 0)
    {
        // 启动事务
        self::startTrans();
        try {
            $after_money = bcadd($before_money, $money, 2);

            //如果变动结果小于0 则返回失败
            if ($after_money < 0) {
                throw new \Exception(lang('金额不足'));
            }
            if ($after_money == $before_money) {
                throw new \Exception(lang('金额没有发生变化'));
            }
            if ($type == 4) {
                //提现到余额
                $ret = User::where('id', $user_id)->update([
                    'withdrawal_money' => $after_money,
                    'user_money' => ['inc', abs($money)],
                ]);
                if (!$ret) {
                    throw new \Exception(lang('更新会员提现金额失败'));
                }
            } else {
                $ret = User::where('id', $user_id)->update([
                    'user_money' => $after_money,
                ]);
                if (!$ret) {
                    throw new \Exception(lang('更新会员余额失败'));
                }
            }

            $data = array(
                'user_id' => $user_id,
                'change_money' => $money,
                'before_money' => $before_money,
                'after_money' => $after_money,
                'change_type' => $type,
                'remark' => $remark,
                'order_no' => $ordeNo,
            );

            $result = self::create($data);
            if (!$result) {
                throw new \Exception(lang('插入流水记录失败'));
            }
            // 提交事务
            self::commit();
        } catch (\Exception $e) {
            // 回滚事务
            self::rollback();
//            throw new \Exception($e->getMessage());
            return false;
        }
        return true;
    }


    public static function share_changeMoney($user_id, $before_money, $money, $type = 1, $remark = '', $ordeNo = '', $consumption = 0, $goods_id = 0)
    {
        // 启动事务
        self::startTrans();
        try {
            $after_money = bcadd($before_money, $money, 2);
            //如果变动结果小于0 则返回失败
            if ($after_money < 0) {
                throw new \Exception(lang('金额不足'));
            }
            if ($money < 0) {
                $map[] = ['user_money', '>=', $money];
            }

            if ($money == 0) {
                throw new \Exception(lang('无金额变动'));
            }

            $data = array(
                'user_id' => $user_id,
                'change_money' => $money,
                'before_money' => $before_money,
                'after_money' => $after_money,
                'change_type' => $type,
                'remark' => $remark,
                'order_no' => $ordeNo,
                'goods_id' => $goods_id,
            );
            $result = self::create($data);
            if (!$result) {
                throw new \Exception(lang('插入流水记录失败'));
            }
            // 提交事务
            self::commit();
        } catch (\Exception $e) {
            // 回滚事务
            self::rollback();
            return false;
        }
        return true;
    }

    /**
     * 收益提现到余额
     * @param int $user_id 会员ID
     * @param int $before_money 会员现余额
     * @param int $money 增加金额 负数就是减少
     * @param int $type 消费类型
     * @param int $consumption 传入此金额记录总消费
     * @editor 李志豪 [ 995562569@qq.com ]
     * @updated 2019.05.21
     * @return boolean
     * @throws \Exception
     */
    /**
     * 获取指定用户的消费列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public static function getList($user_id)
    {
        return self::where("user_id", $user_id)->order("aid desc")->paginate();
    }

    /**
     * 获取所有会员消费记录列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public static function getAllList($map = [], $order = [], $is_page = true)
    {
        if (isset($map['change_type']) && $map['change_type'] == 0) {
            unset($map['change_type']);
        }
        
        if (isset($map['user_nickname'])) {
            $where[] = ['user.user_nickname', 'like', '%' . $map['user_nickname'] . '%'];
        }
        if (isset($map['mobile'])) {
            $where[] = ['user.mobile', 'like', '%' . $map['mobile'] . '%'];
        }
        if (isset($map['change_type'])) {
            $where[] = ['user_money_log.change_type', '=', $map['change_type']];
        }
        if (isset($map['create_time'])) {
            $create_time = explode(' - ', $map['create_time']);
            $start_time = strtotime($create_time[0].' 00:00:00');
            $end_time = strtotime($create_time[1].' 23:59:59');
            $where[] = ['user_money_log.create_time', '>=', $start_time];
            $where[] = ['user_money_log.create_time', '<=', $end_time];
        }
        if (isset($map['pay_type'])) {
            if (!empty($map['pay_type'])) {
                $where[] = ['pay_type', '=', $map['pay_type']]; 
            }
        }

        if($map['pay_type']) {
            $res = self::view("user_money_log", true)
            ->view("user", 'user_nickname,mobile', 'user_money_log.user_id=user.id', 'left')
            ->view("order", 'pay_type', 'user_money_log.order_no=order.order_sn', 'left')
            ->where($where)
            ->order($order);
        } else {
            $res = self::view("user_money_log", true)
            ->view("user", 'user_nickname,mobile', 'user_money_log.user_id=user.id', 'left')
            ->where($where)
            ->order($order);
        }
        if ($is_page) {
            return $res->paginate();
        } else {
            return $res->select();
        }
    }
}
