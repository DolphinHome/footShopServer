<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\model;

use app\user\model\User as UserModel;
use phpDocumentor\Reflection\Types\Self_;
use think\Model as ThinkModel;
use think\Db;

/**
 * 余额变动记录表
 * Class Money
 * @package app\user\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/3 16:38
 */
class Marketing extends ThinkModel
{
    protected $table = "__USER_MARKETING__";

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 销售日志
     * @param $user_id 用户ID
     * @param $sell_level_title 销售等级
     * @param $order_sn 订单号
     * @param $percent 计算佣金百分比
     * @param $real_money 订单金额
     * @param $commission 佣金
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/3 16:38
     */
    public static function SellLog($user_id, $sell_level_title, $order_sn, $percent, $real_money, $commission, $subc_status = 0, $sell_level = 1)
    {
        return self::insertGetId([
            'user_id' => $user_id,
            'sell_level_title' => $sell_level_title,
            'order_sn' => $order_sn,
            'percent' => $percent,
            'real_money' => $real_money,
            'commission' => $commission,
            'subc_status' => $subc_status,
            'sell_level' => $sell_level,
            'create_time' => time(),
        ]);
    }

    /**
     * 获取分佣等级
     * @param int $totalCommissionMoney 总金额
     * @return int 返回可晋升的等级
     * @author zenghu [ 1427305236@qq.com ]
     * @date 2020年8月4日11:39:39
     */
    public function getLevel($totalCommissionMoney = '0')
    {
        // 查询晋升规则
        $operationSellLevel = Db::name('operation_sell_level')->field('aid,upgrade_condition')->where(['status' => 1])->order('upgrade_condition AES')->select();
        foreach ($operationSellLevel as $val) {
            if ($totalCommissionMoney >= $val['upgrade_condition']) {
                $commissionLevel = $val['aid'];
            }
        }

        return $commissionLevel;
    }

    /**
     * 分销日志及等级晋升
     * @param $type int 1 确认收货添加记录 2 退款添加记录
     * @author zenghu [ 1427305236@qq.com ]
     * @date 2020年8月4日11:39:39
     * @update 2020年8月4日18:55:39
     */
    public static function add_user_marketing($order_money, $order_no, $user, $type = 0)
    {
        Db::startTrans();
        try {
            if ($user['lastid']) {
                $subc_status = 0;
                $order_money_real = $order_money;
                // 一级分销
                $seller = Db::name('user')->get($user['lastid']);
                if ($seller) {
                    if ($type == 2) {
                        $subc_status = 2;
                        $order_money = abs($order_money) * -1;
                    }
                    $sell_leve = Db::name('operation_sell_level')->where(['aid' => $seller['spread_level']])->find();
                    $commission = bcmul($order_money, bcdiv($sell_leve['percent'], 100, 2), 2);
                    $commission = $commission == 0 ? '0.01' : $commission;
//                $order_money = abs($order_money);
                    $sell_log = Marketing::SellLog($seller['id'], $sell_leve['name'], $order_no, $sell_leve['percent'], $order_money_real, $commission, $subc_status, 1);
                    if (!$sell_log) {
                        exception(lang('销售日志插入失败'));
                    }

                    // zenghu ADD 2020年8月1日11:29:53 分佣等级晋升
                    // 查询晋升规则
                    $totalCommissionMoney = $seller['total_order_commission_money'] + $order_money; // 计算享受分佣总金额

                    // 一级晋升
                    $user_level = Db::name('user')->where(['id' => $seller['id']])->update([
                        'spread_level' => self::getLevel($totalCommissionMoney),
                        'total_order_commission_money' => $totalCommissionMoney
                    ]);
                    if (!$user_level) {
                        exception(lang('会员等级晋升失败'));
                    }

                    // 二级分销
                    $seller_next = Db::name('user')->get($seller['lastid']);
                    if ($seller_next) {
                        $sell_leve = Db::name('operation_sell_level')->where(['aid' => $seller_next['spread_level']])->find();
                        $commission = bcmul($order_money, bcdiv($sell_leve['percent_next'], 100, 2), 2);
                        $commission = $commission == 0 ? '0.01' : $commission;
                        $sell_log1 = Marketing::SellLog($seller_next['id'], $sell_leve['name'], $order_no, $sell_leve['percent_next'], $order_money_real, $commission, $subc_status, 2);
                        if (!$sell_log1) {
                            exception(lang('销售日志插入失败'));
                        }

                        // zenghu ADD 2020年8月1日14:29:36 分佣等级晋升
                        $totalCommissionMoneyNext = $seller_next['total_order_commission_money'] + $order_money; // 计算享受分佣总金额

                        // 二级晋升
                        $user_level1 = Db::name('user')->where(['id' => $seller_next['id']])->update([
                            'spread_level' => self::getLevel($totalCommissionMoneyNext),
                            'total_order_commission_money' => $totalCommissionMoneyNext
                        ]);
                        if (!$user_level1) {
                            exception(lang('会员等级晋升失败'));
                        }
                    }
                }
            }
            Db::commit();
            return true;
        } catch (\Exception $exception) {
            Db::rollback();
            return false;
        }
    }
}
