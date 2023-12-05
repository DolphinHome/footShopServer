<?php
/**
 * 会员金额操作服务层
 * @author chenchen
 * @time 2021年4月17日15:51:55
 */
namespace app\user\service;

use app\common\model\Order;
use app\goods\service\Goods;
use app\user\model\MoneyFreeLog;
use app\user\model\MoneyLog;
use app\user\model\User;
use think\Db;
use app\user\service\User as UserService;

class Money extends Base
{
    /**
     * 会员冻结金额写入
     * @author chenchen
     * @param $user_id int 金额变更会员id （必须）
     * @param $change_money float 操作金额 （必须）
     * @param $type int 1自购返2分享赚3分销佣金（必须）
     * @param $order_no string 订单号（选填）
     * @param $goods_id int 商品id（选填）
     * @param $sku_id int 商品规格id （选填）
     * @time 2021年4月17日15:52:54
     */
    public static function freeze_money($user_id, $change_money, $type, $order_no = '', $goods_id = 0, $sku_id = 0)
    {
        try {
            Db::startTrans();
            switch ($type) {
                case 1:
                    $remark = lang('商城购物');
                    break;
                case 2:
                    $remark = lang('分享赚佣');

                    break;
                case 3:
                    $remark = lang('下级购买商品获得分销佣金');
                    //下单人id
                    $create_user_id = Order::where(['order_sn' => $order_no])->value("user_id");
                    if (!$create_user_id) {
                        throw new \Exception(lang('订单信息不存在'));
                    }
                    //写入分销收益
                    $commission = Db::name("distribution_commission")->insert([
                        'money' => $change_money,
                        'order_sn' => $order_no,
                        'user_id' => $user_id,//收益人id
                        'create_user_id' => $create_user_id,//下单人id
                        'create_time' => time(),
                        'is_settlement' => 0
                    ]);
                    if (!$commission) {
                        throw new \Exception(lang('分销收益写入失败'));

                    }
                    break;
                default:
                    $remark = '';
            }
            //存储记录
            $log = (new MoneyFreeLog())->insertGetId([
                'user_id' => $user_id,
                'change_money' => $change_money,
                'create_time' => time(),
                'order_no' => $order_no,
                'remark' => $remark,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
                'type' => $type
            ]);
            if (!$log) {
                throw new \Exception(lang('会员冻结金额记录写入失败'));
            }
            //变更冻结金额
            $freeze_money = User::where([
                'id' => $user_id
            ])->update([
                'freeze_money' => ['inc', $change_money]
            ]);
            if (!$freeze_money) {
                throw new \Exception(lang('会员冻结金额变更失败'));
            }
            Db::commit();
            return ['code' => 1, 'msg' => lang('会员冻结金额写入成功')];
        } catch (\Exception $exception) {
            Db::rollback();
            return ['code' => 0, 'msg' => $exception->getMessage()];
        }
    }

    /**
     * 会员金额变更
     * @author chenchen
     * @param $user_id int 金额变更会员id （必须）
     * @param $before_money float 会员变更前金额（必须）
     * @param $change_money float 操作金额 正数增加 负数就是减少（必须）
     * @param $change_type int 操作类型1会员充值 2余额支付 3管理员操作 4会员提现 5售后退款
     * 6会员分成 7提现申请拒绝 8分享赚 9自购返 10分销佣金（必须）
     * @param $order_no string 订单号（选填）
     * @param $goods_id int 商品id（选填）
     * @param $sku_id int 商品规格id （选填）
     * @time 2021年4月17日17:31:46
     */
    public static function money_update($user_id, $before_money, $change_money, $change_type, $order_no = '', $goods_id = 0, $sku_id = 0)
    {
        try {
            Db::startTrans();
            $after_money = self::format_money(($before_money + $change_money), 1);
            //如果变动结果小于0 则返回失败
            if ($after_money <= 0) {
                throw new \Exception(lang('金额不足'));
            }
            if ($change_money == 0) {
                throw new \Exception(lang('无金额变动'));
            }
            switch ($change_type) {
                case 1:
                    $remark = lang('现金充值订单');
                    break;
                case 2:
                    $remark = lang('余额支付');
                    break;
                case 3:
                    $remark = lang('系统快速变更');
                    break;
                case 4:
                    $remark = lang('会员申请提现');
                    break;
                case 5:
                    $remark = lang('售后退款');
                    break;
                case 6:
                    $remark = lang('会员分成');
                    break;
                case 7:
                    $remark = lang('提现申请拒绝');
                    break;
                case 8:
                    $remark = lang('分享赚返钱');

                    //变更会员分享赚金额 ，冻结金额，可提现金额
                    $user_update = User::where([
                        ['id', '=', $user_id],
                        ['freeze_money', '>=', $change_money]
                    ])->update([
                        'freeze_money' => ['dec', $change_money],
                        'share_money' => ['inc', $change_money],
                        'withdrawal_money' => ['inc', $change_money],
                    ]);
                    if (!$user_update) {
                        throw new \Exception(lang('会员分享赚金额，冻结金额，可提现金额变更失败'));
                    }
                    break;
                case 9:
                    $remark = lang('购买商品返钱');
                    //变更会员自购返金额 ，冻结金额，可提现金额
                    $user_update = User::where([
                        ['id', '=', $user_id],
                        ['freeze_money', '>=', $change_money]
                    ])->update([
                        'freeze_money' => ['dec', $change_money],
                        'buy_back_money' => ['inc', $change_money],
                        'withdrawal_money' => ['inc', $change_money],
                    ]);
                    if (!$user_update) {
                        throw new \Exception(lang('会员自购返金额，冻结金额，可提现金额变更失败'));
                    }
                    break;
                case 10:
                    $remark = lang('分销佣金返钱');
                    //变更会员分销佣金金额 ，冻结金额，可提现金额
                    $user_update = User::where([
                        ['id', '=', $user_id],
                        ['freeze_money', '>=', $change_money]
                    ])->update([
                        'freeze_money' => ['dec', $change_money],
                        'commission' => ['inc', $change_money],
                        'withdrawal_money' => ['inc', $change_money],
                    ]);
                    if (!$user_update) {
                        throw new \Exception(lang('会员分销佣金金额，冻结金额，可提现金额变更失败'));
                    }
                    //变更分销明细为结算
                    $commission = Db::name("distribution_commission")->where([
                        'order_sn' => $order_no
                    ])->update([
                        'is_settlement' => 1
                    ]);
                    if (!$commission) {
                        throw new \Exception(lang('分销佣金结算状态变更失败'));
                    }
                    break;
                default:
                    $remark = '';
            }
            //存储记录
            $money_log = (new MoneyLog())->insertGetId([
                'user_id' => $user_id,
                'before_money' => $before_money,
                'change_money' => $change_money,
                'after_money' => $after_money,
                'change_type' => $change_type,
                'create_time' => time(),
                'remark' => $remark,
                'order_no' => $order_no,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
            ]);
            if (!$money_log) {
                throw new \Exception(lang('会员金额日志存储失败'));
            }
            //变更冻结金额记录状态
            $freeze_log = (new MoneyFreeLog())->where([
                'user_id' => $user_id,
                'order_no' => $order_no,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
            ])->setField("is_delete", 1);
            if (!$freeze_log) {
                throw new \Exception(lang('会员冻结金额状态变更失败'));
            }
            Db::commit();
            return ['code' => 1, 'msg' => lang('会员冻结金额转为金额成功')];
        } catch (\Exception $exception) {
            Db::rollback();
            return ['code' => 0, 'msg' => $exception->getMessage()];
        }
    }

    /**
     * 格式化金额
     * @author chenchen
     * @param  $money float 金额
     * @param $type int 千位逗号分隔，1不分割
     * @param $decimals int 保留小数位数
     * @time 2021年4月20日14:31:48
     */
    public static function format_money($money = 0.00, $type = 0, $decimals = 2)
    {
        if ($type == 1) {
            return number_format($money, $decimals, '.', '');
        } else {
            return number_format($money, $decimals);
        }
    }
}
