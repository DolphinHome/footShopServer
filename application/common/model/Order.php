<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\common\model;

use app\goods\model\Goods;
use app\goods\model\GoodsSku;
use app\goods\model\OrderGoods;
use app\operation\model\CouponRecord;
use app\operation\model\SystemMessage;
use app\user\model\ScoreLog;
use app\goods\model\OrderInvoice;
use app\user\service\Money;
use think\Db;
use think\Model as ThinkModel;
use app\user\model\User as UserModel;
use app\user\model\LevelCard as LevelNewModel;
use app\user\model\Task as TaskModel;
use app\goods\service\Goods as GoodsService;
use app\user\service\User as UserService;
use app\goods\service\Order as OrderService;
use app\common\model\UserLevelCardData as CarData;
use app\goods\model\GoodsStockLog;

/**
 * 购物车模型
 * @package app\operation\model
 */
class Order extends ThinkModel
{

    // 设置当前模型对应的完整数据表名称
    public static $payTypes = [
//        'wxpay' => '微信',
//        'alipay' => '支付宝',
//        'appleiap' => '苹果内购',
        'balance' => '余额支付',
        'minipay' => '微信小程序支付',
//        'minipay_mix' => '组合支付',
//        'xx_pay' => '后台下单'
    ];

    // 自动写入时间戳
    public static $orderTypes = [
        1 => '现金充值',
        2 => '虚拟币充值',
        3 => '商城交易',
        4 => '购买VIP',
        5 => '拼团订单',
        6 => '秒杀订单',
        7 => '预售订单',
        8 => '预售订单-尾款',
        9 => '折扣订单',
        10 => '商城交易',
        16=>'充值会员'
    ];

    public static $oeder_type_name = [
        1 => '',
        2 => '',
        3 => '普通商品',
        4 => '积分商品',
        5 => '拼团订单',
        6 => '秒杀订单',
        7 => '预售订单',
        8 => '预售订单-尾款',
        9 => '折扣订单',
        10 => '',
        11 => '会员限购订单',
        12 => '积分商品',
        14 => '砍价商品'
    ];
    //所有支付方式
    public static $pay_status = [
        0 => '未付款',
        1 => '已付款',
    ];
    //订单类型
    public static $order_status = [
        '-2' => '未付款',
        '-1' => '已取消',
        0 => '待支付',
        1 => '已支付',
        2 => '已发货',
        3 => '已完成',
        4 => '已评价',
        5 => '售后中',
        6 => '售后完成',
    ];

    //配送方式
    public static $sendTypeArr = [
        ['key' => 0, 'name' => '快递'],
        ['key' => 1, 'name' => '自提']
    ];

    public static function order_typeArr()
    {
        return array(
            '3' => array(
                'key' => 3,
                'name' => '普通订单'
            ),
            // '4' => array(
            //     'key' => 4,
            //     'name' => '积分订单'
            // ),
//            '5' => array(
//                'key' => 5,
//                'name' => '拼团订单'
//            ),
//            '6' => array(
//                'key' => 6,
//                'name' => '秒杀订单'
//            ),
//            '7' => array(
//                'key' => 7,
//                'name' => '预售订单'
//            ),
            // '9' => array(
            //     'key' => 9,
            //     'name' => '折扣订单'
            // ),
            /*'11' => array(
                'key' => 11,
                'name' => '会员限购'
            ),*/
//            '12' => array(
//                'key' => 12,
//                'name' => '积分商品'
//            ),
//            '14' => array(
//                'key' => 14,
//                'name' => '砍价订单'
//            ),


        );
    }

    //支付状态
    protected $table = '__ORDER__';

    //订单状态
    protected $autoWriteTimestamp = true;

    /**
     * 创建充值订单
     * @param array $data 下单参数数组
     * @param array $user 会员数据数组
     * @return array
     * @throws \Exception
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function addRechargeOrder($data, $user)
    {
        $rule_id = $data['product_id'];
        if ($rule_id) {
            $rule = \app\user\model\RechargeRule::get($rule_id);
            if (!$rule) {
                throw new \Exception(lang('获取充值规则失败'));
            }
            $money = $rule['money'];
            $orderData['product_id'] = $data['product_id'];
        } else {
            $money = $data['order_money'];
        }

        if (!$money) {
            throw new \Exception(lang('缺少充值金额'));
        }

        //写入订单
        $order_no = get_order_sn('CZ');
        $orderData['user_id'] = $user['id'];
        $orderData['order_sn'] = $order_no;
        $orderData['order_money'] = $money;
        $orderData['payable_money'] = $data['payable_money'] ? $data['payable_money'] : $money;
        $orderData['real_money'] = 0;
        $orderData['pay_status'] = 0;
        $orderData['status'] = 0;
        $orderData['pay_type'] = $data['pay_type'];
        $orderData['order_type'] = $data['order_type'];
        $orderData['cost_price_total'] = 0;

        $ret = self::create($orderData);
        if (!$ret) {
            throw new \Exception(lang('创建订单失败'));
        }
        return [
            'order_sn' => $order_no
        ];
    }

    /**
     * 创建商城订单
     * @param array $data 下单参数数组
     * @param array $user 会员数据数组
     * @return array
     * @throws \Exception
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function addGoodsOrder($data, $user)
    {

        $order_info = json_decode($data['order_info'], true);
        //获取订单的配送类型 
        $send_type = $order_info['send_type'] ?? 0;
        //优惠券id
        $coupon_id = $data['coupon_id'] ?: 0;
        Db::startTrans();
        try {
            //生成订单号
            $order_no = get_order_sn('GD');
            //保存订单商品表信息
            $payable_money = $order_money = $cost_price_total = 0;
            $goodinfo = new Goods();
            $goodsku = new GoodsSku();
            $goods_list = $where = $where1 = [];
            foreach ($order_info['goods'] as $g) {
                $good_info = $goodinfo->get($g['id']);
                $sku_id = $g['sku_id'] ? $g['sku_id'] : 0;
                $calculate_price = GoodsService::calculate_price($user['id'], $g['id'], $sku_id, 0, $g['number']);
                if ($calculate_price['code'] == 0) {
                    exception($calculate_price['msg']);
                }
                $shop_price = $calculate_price['data']['shop_price'];
                $cost_price = $calculate_price['data']['cost_price'];
                $sku_name = '';
                if ($sku_id) {
                    $sku_name = $goodsku->where(['sku_id' => $sku_id, 'goods_id' => $g['id']])->value("key_name") ?? '';
                }
                $goods_money = Money::format_money($shop_price * $g['number'], 1);
                $payable_money += $goods_money;
                $order_money += $goods_money;
                $cost_price_total += Money::format_money(($cost_price * $g['number']), 1);
                $share_sign = $data['share_sign'] ?? '';
                $goods_list[] = [
                    'order_sn' => $order_no,
                    'goods_id' => $g['id'],
                    'goods_name' => $good_info['name'],
                    'shop_price' => $shop_price,
                    'sku_id' => $sku_id,
                    'num' => $g['number'],
                    'goods_thumb' => $good_info['thumb'],
                    'order_status' => 0,
                    'sku_name' => $sku_name,
                    'goods_money' => $goods_money,
                    'share_sign' => $share_sign,
                    'create_time' => time(),
                    'sender_id' => $good_info['sender_id'],
                    'cost_price' => $cost_price
                ];
                //扣库存
                $update_stock = GoodsService::update_stock($g['id'], -$g['number'], $sku_id);
                if ($update_stock['code'] == 0) {
                    exception($update_stock['msg']);
                }
                //加销量
                $update_sale = GoodsService::update_sale($g['id'], $g['number'], $sku_id);
                if ($update_sale['code'] == 0) {
                    exception($update_sale['msg']);
                }
                //多规格商品销售时增加商品展示销量
                if ($sku_id) {
                    $update_all_sale = Db::name('goods')->where(['id' => $g['id']])->setInc('sales_sum', $g['number']);
                    if (!$update_all_sale) {
                        exception('更新商品销量失败');
                    }
                }
                //真实销量
                $update_true_sale = Db::name('goods')->where(['id' => $g['id']])->setInc('sales_num_new', $g['number']);
                if (!$update_true_sale) {
                    exception('更新商品销量失败');
                }
            }
//            dump($payable_money);die;
            //插入订单商品表
            $goods_list_insert = (new OrderGoods())->insertAll($goods_list);
            if (!$goods_list_insert) {
                exception(lang('保存订单商品失败'));
            }
            //如果提交了优惠券id，则查询数据库中的优惠券
            if ($coupon_id) {
                //TODO 可使用优惠券待优化
                $coupon_record = new CouponRecord();
                $coupon = $coupon_record->get_user_coupon(['cr.user_id' => $user['id'], 'cr.id' => $coupon_id, 'cr.status' => 1]);
                if (!$coupon) {
                    exception(lang('优惠券无效，请重新下单'));
                }
                if ($payable_money - $coupon['money'] <= 0) {
                    exception(lang('优惠券金额不能大于商品总价'));
                }
                $payable_money = Money::format_money(($payable_money - $coupon['money']), 1);
                $coupon_update = $coupon_record->where(['id' => $data['coupon_id'], 'status' => 1])->update(['status' => 3, 'use_time' => time(), 'order_sn' => $order_no]);
                if (!$coupon_update) {
                    exception(lang('优惠券无效，请重新下单'));
                }
            }
            //普通快递配送订单需要计入运费，自提不计运费，wangph修改2021-4-22
            if ($send_type != 1) {
                //添加商品订单附表信息
                $order_goods_info = [
                    'order_sn' => $order_no,
                    'address_id' => $order_info['address']['address_id'],
                    'receiver_mobile' => $order_info['address']['mobile'],
                    'receiver_address' => $order_info['address']['address'],
                    'receiver_name' => $order_info['address']['name'],
                    'remark' => $order_info['remark'] ?? '',
                    'express_price' => $order_info['express_price'] ?? 0,
                    'province' => $order_info['address']['province'],
                    'city' => $order_info['address']['city'],
                    'district' => $order_info['address']['district'],
                    'sex' => $order_info['address']['sex'],
                    'label_name' => $order_info['address']['label_name']

                ];
                $res1 = Db::name('order_goods_info')->insert($order_goods_info);
                if (!$res1) {
                    exception(lang('保存订单附加信息失败'));
                }
                //如果有运费，加上
                if ($order_info['express_price']) {
                    //实际支付金额
                    $payable_money = Money::format_money($payable_money + $order_info['express_price'], 1);
                    //订单金额
                    $order_money = Money::format_money($order_money + $order_info['express_price'], 1);
                }
            }
            //积分抵扣金额
            $reduce_money = $integral_reduce = 0;
            if (isset($data['isSelect_integral_reduce']) && $data['isSelect_integral_reduce'] == 1) {
                $reduce_data = OrderService::integral_reduce($user['id'], $payable_money);
                if ($reduce_data['is_integral_reduce'] == 1) {
                    $payable_money = $reduce_data['integral_payable_money'];
                    $reduce_money = $reduce_data['reduce_money'];
                    $integral_reduce = $reduce_data['integral_reduce'];
                    $score_log = ScoreLog::change($user['id'], -$integral_reduce, 5, '购买商品抵扣积分', $order_no);
                    if (!$score_log) {
                        exception(lang('积分抵扣失败'));
                    }
                }
            }
            // 计算出来的金额和提交过的来金额做对比，一致才往下走
            if (!check_money($data['payable_money'], $payable_money)) {
                exception(lang('金额校验失败') . ',' . lang('计价') . ':' . $payable_money . ':' . $data['payable_money']);
            }

            //如果pickup_id不为空，为自提类型的订单，add by wangph at 2021-4-19
            if (isset($order_info['invite_address']['pickup_id']) && $order_info['invite_address']['pickup_id']) {
                $order_pickup = [
                    'order_sn' => $order_no,
                    'pickup_id' => $order_info['invite_address']['pickup_id'],
                    'pickup_date' => $order_info['invite_address']['pickup_date'],
                    'pickup_delivery_time_id' => $order_info['invite_address']['pickup_delivery_time_id'],
                    'user_pickup_id' => $order_info['invite_address']['user_pickup_id'],
                    'remark' => $order_info['remark'] ?? '',
                ];
                $res_pickup = Db::name('order_pickup')->insert($order_pickup);
                if (!$res_pickup) {
                    exception(lang('保存订单自提信息失败'));
                }
            }

            $pay_type = $data['pay_type'] ?? '';
            $coupon_id = $data['coupon_id'] ?: 0;
            $coupon_money = isset($coupon['money']) ? $coupon['money'] : 0;
            $pay_status = $status = 0;
            if ($payable_money == 0) {
                //免单
                $pay_status = $status = 1;
            }
            //发票状态（1申请开票中 2已开票 3发票作废）
            $invoice_status = 0;
            if (!empty($order_info['invoice']) && !empty($order_info['invoice']['invoice_type']) && !empty($order_info['invoice']['invoice_title'])) {
                $invoice_status = 1;
                $invoice_data = [
                    'order_sn' => $order_no,
                    'invoice_type' => $order_info['invoice']['invoice_type'],
                    'invoice_title' => $order_info['invoice']['invoice_title'],
                    'invoice_company_duty_paragraph' => $order_info['invoice']['invoice_company_duty_paragraph'],
                    'invoice_status' => 1, // 申请开票中
                    'invoice_price' => $payable_money, // 申请开票金额
                    'invoice_company_bank' => $order_info['invoice']['invoice_company_bank'],
                    'invoice_company_bank_num' => $order_info['invoice']['invoice_company_bank_num'],
                    'invoice_company_address' => $order_info['invoice']['invoice_company_address'],
                    'invoice_company_phone' => $order_info['invoice']['invoice_company_phone'],
                    'invoice_email' => $order_info['invoice']['invoice_email'],
                    'invoice_add_time' => time(),
                ];
                $invoice = (new OrderInvoice())->insert($invoice_data);
                if (!$invoice) {
                    exception(lang('订单发票信息保存失败'));
                }
                //默认抬头
                if ($order_info['invoice']['invoice_is_default'] == 1) {
                    if ($order_info['invoice']['invoice_type'] == 1) {
                        $whereInvoice['invoice_title'] = $order_info['invoice']['invoice_title'];
                    } elseif ($order_info['invoice']['invoice_type'] == 2) {
                        $whereInvoice['invoice_company_title'] = $order_info['invoice']['invoice_title'];
                    } else {
                        exception(lang('请填写发票抬头'));
                    }
                    Db::name('user')->where('id', $user['id'])->update($whereInvoice);
                }
            }
            //组装订单信息
            $orderData = [
                'send_type' => $send_type,
                'user_id' => $user['id'],
                'order_sn' => $order_no,
                'order_money' => $order_money,
                'payable_money' => $payable_money,
                'status' => $status,
                'real_money' => 0,
                'pay_status' => $pay_status,
                'pay_type' => $pay_type,
                'coupon_id' => $coupon_id,
                'coupon_money' => $coupon_money,
                'order_type' => $data['order_type'],
                'reduce_money' => $reduce_money,
                'integral_reduce' => $integral_reduce,
                'invoice_status' => $invoice_status,
                'cost_price_total' => $cost_price_total
            ];

            // 插入订单信息
            $order = self::create($orderData);
            if (!$order) {
                exception(lang('创建订单失败'));
            }
            Db::commit();
            $res = [
                'order_sn' => $order_no,
                'pay_status' => $pay_status
            ];
            return $res;

        } catch (\Exception $e) {
            Db::rollback();
            exception($e->getMessage());
        }
    }


    /**
     * 订单异步回调，支付回调
     * @param string $order_no 订单号
     * @param int $pay_type 支付方式
     * @param string $transaction_id 第三方订单号
     * @return boolean
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */

    public static function verify($order_no, $pay_type, $transaction_id = '', $total_fee)
    {
        set_time_limit(0);
        $order = self::where('order_sn', $order_no)->find();
        $user = Db::name('user')->get($order['user_id']);
        if ($order['pay_status'] > 0) {
            return true;
        }
        if (!$order) {
            return false;
        }
        //加进程锁
        $locKey = "lock_" . $order_no;
        $redis = \app\common\model\Redis::handler();
        $lock = $redis->get($locKey);
        if ($lock) {
            return false;
        }
        $redis->setnx($locKey, 1);
        //使用事务
        Db::startTrans();
        try {
            $upOrder = [
                'pay_status' => 1,
                'pay_type' => $pay_type,
                'pay_time' => time(),
                'transaction_id' => $transaction_id,
            ];
            if ($order['order_type'] == 1 || $order['order_type'] == 2 || $order['order_type'] == 16) {
                //如果是充值，则直接完成订单
                $upOrder['status'] = 3;
            } else if ($order['order_type'] == 3 || $order['order_type'] == 5 || $order['order_type'] == 6 || $order['order_type'] == 7 || $order['order_type'] == 8 || $order['order_type'] == 12) {
                //商城订单改为已支付
                $upOrder['status'] = 1;
            }

            if ($pay_type == 'wxpay' || $pay_type == 'minipay') {
                //微信要除以100
                $upOrder['real_money'] = $total_fee / 100;
            } else {
                $upOrder['real_money'] = $total_fee;
            }

            if ($order['real_balance'] > 0) {
                //混合支付
                //写入金额变更日志
                $after_money = \app\user\model\MoneyLog::changeMoney($order['user_id'], $user['user_money'], -$order['real_balance'], 7, lang('余额支付'), $order['order_sn']);
                if (!$after_money) {
                    exception(lang('金额日志写入失败'));
                }
                //写入支付日志
                //余额
                $payment_log = Db::name("payment_log")->insert([
                    'order_sn' => $order_no,
                    'amount' => $order['real_balance'],
                    'transaction_no' => '',
                    'create_time' => time(),
                    'status' => 1,
                    'pay_type' => 'balance'
                ]);
                if (!$payment_log) {
                    exception(lang('金额日志写入失败'));
                }
                //微信 、微信小程序
                $payment_log1 = Db::name("payment_log")->insert([
                    'order_sn' => $order_no,
                    'amount' => $upOrder['real_money'],
                    'transaction_no' => $transaction_id,
                    'create_time' => time(),
                    'status' => 1,
                    'pay_type' => $pay_type
                ]);
                if (!$payment_log1) {
                    exception(lang('金额日志写入失败'));
                }

            } else {
                $payment_log = Db::name("payment_log")->insert([
                    'order_sn' => $order_no,
                    'amount' => $order['payable_money'],
                    'transaction_no' => $transaction_id,
                    'create_time' => time(),
                    'status' => 1,
                    'pay_type' => $pay_type
                ]);
                if (!$payment_log) {
                    exception(lang('金额日志写入失败'));
                }

            }

            $res = self::where(['order_sn' => $order_no, 'status' => 0])->update($upOrder);
            if (!$res) {
                exception(lang('订单处理异常'));
            }


            if (in_array($order['order_type'], [3, 5, 6, 8, 9, 10, 11])) {
                //增加产品销量
                //查询订单中的商品
                $total_cost_price = 0;
                $goodsId = Db::name('order_goods_list')->where('order_sn', $order_no)->select();

                // zenghu ADD 设置修改user表条件 2020年8月7日11:58:00
                $userUpdateWhere = []; // 定义user表修改的条件
                $consumption = $user['empirical']; // 定义会员成长值
                foreach ($goodsId as $v) {
                    if ($v['sku_id']) {
                        $cost_price = Db::name('goods_sku')->where(['sku_id' => $v['sku_id']])->value('cost_price');
                        Db::name('goods_sku')->where(['sku_id' => $v['sku_id']])->setInc('sales_num', $v['num']);
                        //多规格的货号
                        $v['goods_sn'] = GoodsSku::where("sku_id", $v['sku_id'])->value("sku_sn");
                    } else {
                        $cost_price = Db::name('goods')->where(['id' => $v['goods_id']])->value('cost_price');
                        //货号
                        $v['goods_sn'] = Goods::where("id", $v['goods_id'])->value("sn");
                    }
                    $total_cost_price = bcadd($total_cost_price, bcmul($cost_price, $v['num'], 2), 2);
                    $goods = Db::name('goods')->get($v['goods_id']);
                    if ($goods['empirical'] > 0) {
                        $consumption += $goods['empirical']; // 累计权益值
                        $userUpdateWhere[] = [
                            'id' => $user['id'],
                            'empirical' => ['inc', $goods['empirical']]
                        ];

                    }//自购返
//                    if ($goods['discounts'] > 0) {
//                        $earnings = Money::format_money($goods['discounts'] * $v['num'], 1);
//                        $freeze_money = Money::freeze_money($order['user_id'], $earnings, 1, $order_no, $v['goods_id'], $v['sku_id']);
//                        if ($freeze_money['code'] == 0) {
//                            exception(lang('自购返处理异常'));
//                        }
//                    }
                    //分享赚
//                    if ($v['share_sign']) {
//                        $share_uid = Db::name('goods_share')->where(['share_sign' => $v['share_sign']])->find();
//                        $earnings = Money::format_money($goods['share_award_money'] * $v['num'], 1);
//                        if ($share_uid['uid'] != $order['user_id']) {
//                            $freeze_money = Money::freeze_money($share_uid['uid'], $earnings, 2, $order_no, $v['goods_id'], $v['sku_id']);
//                            if ($freeze_money['code'] == 0) {
//                                exception(lang('分享赚处理异常'));
//                            }
//                        }
//                    }
//                    //分销佣金
//                    $commission = GoodsService::goods_commission($v['goods_id']);
//                    if ($commission['first_profit'] > 0 && $commission['second_profit'] > 0) {
//                        //上级
//                        $first_id = UserService::parent_dis_id($user['id']);
//                        if ($first_id) {
//                            $first_profit = Money::freeze_money($first_id, $commission['first_profit'], 3, $order_no, $v['goods_id'], $v['sku_id']);
//                            if ($first_profit['code'] == 0) {
//                                exception(lang('分销佣金处理异常'));
//                            }
//                            //上上级
//                            $second_id = UserService::parent_dis_id($first_id);
//                            if ($second_id) {
//                                $second_profit = Money::freeze_money($second_id, $commission['second_profit'], 3, $order_no, $v['goods_id'], $v['sku_id']);
//                                if ($second_profit['code'] == 0) {
//                                    exception(lang('分销佣金处理异常'));
//                                }
//                            }
//                        }
//                    }

                    //库存记录
                    $stock_after = GoodsService::get_stock($v['goods_id'], $v['sku_id']);
                    $stock_before = $stock_after + $v['num'];
                    GoodsStockLog::AddStockLog(
                        $v['goods_id'],
                        $v['sku_id'],
                        $order_no,
                        $stock_before,
                        $v['num'],
                        $stock_after,
                        2,
                        0,
                        lang('用户购买'),
                        $v['goods_sn']
                    );
                }
                // zenghu ADD 更新用户消费总额 2020年8月7日11:33:36
                if ($pay_type == 'wxpay' || $pay_type == 'minipay' || $pay_type == 'minipay_mix') {
                    $totalConsumptionMoney = $total_fee / 100;
                } else {
                    $totalConsumptionMoney = $total_fee;
                }
                // 更新消费总金额
                $userUpdateWhere[] = [
                    'id' => $user['id'],
                    'total_consumption_money' => ['inc', $totalConsumptionMoney]
                ];

                // 修改用户信息
                $UserModelName = new UserModel();
                $UserModelName->saveAll($userUpdateWhere);
            }


            switch ($order['order_type']) {
                case 1:
                    $user = \app\user\model\User::get($order['user_id']);
                    $remark = lang('现金充值订单');

                    if ($order['product_id']) {
                        $money = \app\user\model\RechargeRule::where('id', $order['product_id'])->value('add_money');
                    } else {
                        $money = $order['order_money'];
                    }

                    $after_money = \app\user\model\MoneyLog::changeMoney($user['id'], $user['user_money'], $money, 1, $remark, $order_no);
                    if (!$after_money) {
                        exception(lang('订单处理异常'));
                    }
                    break;
                case 2:
                    $user = \app\user\model\User::get($order['user_id']);
                    $remark = lang('虚拟币充值订单');

                    if ($order['product_id']) {
                        $money = \app\user\model\RechargeRule::where('id', $order['product_id'])->value('add_money');
                    } else {
                        $money = $order['order_money'];
                    }

                    $after_money = \app\user\model\VirtualMoneyLog::changeMoney($user['id'], $user['user_virtual_money'], $money, 1, $remark, $order_no);
                    if (!$after_money) {
                        exception(lang('订单处理异常'));
                    }
                    break;
                case 3:
                    Db::name('order_goods_list')->where(['order_sn' => $order_no, 'order_status' => 0])->update(['order_status' => 1]);
                    break;
                case 5:
                    //查询拼团
                    Db::name('goods_activity_group_user')->where([
                        'order_sn' => $order_no,
                        'status' => 0
                    ])->update(['status' => 1]);
                    $goods_activity_group_user = Db::name('goods_activity_group_user')->where('order_sn', $order_no)->find();
                    Db::name('goods_activity_group')->where([
                        'id' => $goods_activity_group_user['group_id'],
                        'status' => 0
                    ])->update(['status' => 1]);
                    Db::name('order_goods_list')->where(['order_sn' => $order_no, 'order_status' => 0])->update(['order_status' => 1]);

                    $goods_activity_group = Db::name('goods_activity_group')->where('id', $goods_activity_group_user['group_id'])->find();
                    $gcd = Db::name('goods_activity_details')->where([
                        'activity_id' => $goods_activity_group['activity_id'],
                        'goods_id' => $goods_activity_group['goods_id'],
                        'status' => 1,
                    ])->find();


                    // 如果拼团单已生效
                    if ($goods_activity_group_user['status'] == 1) {

                        Db::name('goods_activity_group')->where([
                            ['id', '=', $goods_activity_group_user['group_id']],
                            ['num', '<', $gcd['join_number']],
                            ['status', '=', 1]
                        ])->setInc('num');

                        $activity_user = Db::name('goods_activity_group_user')->where(['group_id' => $goods_activity_group_user['group_id'], 'status' => 1])->find();
                        if ($activity_user['uid'] == $goods_activity_group_user['uid']) {
                            // 暂不处理 
                            break;
                        } else {
                            Db::name('goods_activity_group_user')->where('order_sn', $order_no)->update(['status' => 1]);
                            $num = $goods_activity_group['num'] + 1;
//                            $num = $goods_activity_group['num'];

                            if ($num >= $gcd['join_number']) {
                                //拼团成功 增加销量
                                Db::name("goods")->where(['id' => $goods_activity_group['goods_id']])->setInc('sales_sum', $gcd['join_number']);
                                Db::name('goods_activity_group')->where([['id', '=', $goods_activity_group_user['group_id']]])->setField('is_full', 1);
                                Db::name('goods_activity_group_user')->where('group_id', $goods_activity_group_user['group_id'])->update(['is_full' => 1]);

                            }

                        }

                    } else {  // 拼团单未生效
//                        Db::name('goods_activity_group_user')->where([
//                            'order_sn' => $order_no,
//                            'status' => 0
//                        ])->update(['status' => 1]);
//                        Db::name('goods_activity_group')->where([
//                            'id' => $goods_activity_group_user['group_id'],
//                            'status' => 0
//                        ])->update(['status' => 1]);
//                        Db::name('order_goods_list')->where(['order_sn' => $order_no, 'order_status' => 0])->update(['order_status' => 1]);

                    }
                    break;
                case 6:
                    // 秒杀
                    Db::name('order_goods_list')->where(['order_sn' => $order_no, 'order_status' => 0])->update(['order_status' => 1]);
                    $goodsId = Db::name('order_goods_list')->field("goods_id")->where(['order_sn' => $order_no])->find();
                    Db::name("goods")->where(['id' => $goodsId['goods_id']])->setInc('sales_sum', 1);
                    break;
                case 12:
                    // 积分商品
                    Db::name('order_goods_list')->where(['order_sn' => $order_no, 'order_status' => 0])->update(['order_status' => 1]);
//                    Db::name("user")->where("id",$order['user_id'])->setDec("score",$order['cost_integral']);

                    //积分变更记录--同时扣除积分
                    ScoreLog::change($order['user_id'], -$order['cost_integral'], 3, lang('积分商城兑换'));
                    break;
                case 16:
                    // 会员卡订单
                    //订单信息
                    $order_level_info = Db::name('order_user_level')->where(['order_sn' => $order_no, 'order_status' => 0])->find();
                    $res16 = Db::name('order_user_level')->where(['order_sn' => $order_no, 'order_status' => 0])->update(['order_status' => 1]);
                    if (!$res16) {
                        exception(lang('订单附属信息处理异常'));
                    }
                    //下单的会员卡详情
                    $level_info = Db::name('user_level_card')->where(['id' => $order_level_info['level_id']])->find();
                    //查询用户会员现有卡信息
                    $user_level = 0;
                    $user_level_card_data = Db::name('user_level_card_data')->where(['status' => 1, 'user_id' => $order_level_info['user_id']])->find();
                    if ($user_level_card_data) {
                        //查询用户现有会员卡信息
                        $user_level = Db::name('user_level_card')->where(['id' => $user_level_card_data['level_id']])->find();
                    } else {
                        //不存在则创建
                        $res_level = CarData::create([
                            'user_id' => $order_level_info['user_id'],
                            'level_id' => $order_level_info['level_id'],
                            'card_number' => '1000' . $order_level_info['user_id']
                        ]);
                        if (!$res_level) {
                            exception(lang('创建会员卡处理异常'));
                        }
                    }
                    $vip_time = 3600 * 24 * $level_info['days'];//期限
                    $vip_last_time = time() + 3600 * 24 * $level_info['days'];
                    //查询用户购买会员卡等级
                    if (module_config('user.user_card') == 1) {
                        //金银铜
                        //判断用户是否是同级续费
                        if ($user_level == $level_info['level'] && $user_level_card_data['user_vip_last_time'] > time()) {
                            $vip_last_time = $user_level_card_data['user_vip_last_time'] + $vip_time;
                        }
                    } elseif (module_config('user.user_card') == 2) {
                        //判断用户是否是续费
                        if ($user_level_card_data['user_vip_last_time'] > time()) {
                            $vip_last_time = $user_level_card_data['user_vip_last_time'] + $vip_time;
                        } else {
                            $vip_last_time = time() + $vip_time;
                        }
                    } elseif (module_config('user.user_card') == 3) {
                        //终身卡
                        $vip_last_time = time() + 3600 * 24 * $level_info['days'];
                    }
                    $res_user = Db::name('user_level_card_data')->where(['user_id' => $order_level_info['user_id']])->update(['level_id' => $order_level_info['level_id'], 'user_vip_start_time' => time(), 'user_vip_last_time' => $vip_last_time]);
                    if (!$res_user) {
                        exception(lang('用户表level处理异常'));
                    }
                    break;
                default:

                    break;
            }
            //充值和会员卡购买订单无详情
            if (!in_array($order['order_type'], [1, 2, 16])) {
                //下单成功发送通知消息
                $goods_thumb = OrderGoods::where([
                        'order_sn' => $order_no
                    ])->value("goods_thumb") ?? 0;
                SystemMessage::send_msg(
                    $order['user_id'],
                    lang('您的订单支付成功'),
                    lang('您的订单') . '：' . $order_no . lang('支付成功'),
                    1,
                    3,
                    1,
                    $goods_thumb,
                    '/pages/order/orderdetail/order-detail/index?order_sn=' . $order_no . '&order_type=3'
                );
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $redis->del($locKey);
            exception($e->getMessage());
            return false;
        }
//        TaskModel::doTask($order['user_id'], 'firstOrder');
        $redis->del($locKey);
        return true;
    }


    public static function back_verify($arr)
    {
        Db::startTrans();
        try {
            $res = Db::name('order_refund')->where(['server_no' => $arr['out_refund_no']])->update([
                'status' => 3,
                'refund_id' => $arr['refund_id'],
                'refund_money' => ($arr['refund_fee'] / 100),
                'refund_status' => 1,
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return $res;
    }

    /**
     * 创建积分商城订单
     * @param array $data 下单参数数组
     * @param array $user 会员数据数组
     * @return array
     * @throws \Exception
     * @author jxy [ 415782189@qq.com ]
     */
    public static function addIntegralOrder($data, $user)
    {
        $order_info = json_decode($data['order_info'], true);
        //获取订单的配送类型
        $send_type = 0;
        //优惠券id
        $coupon_id = $data['coupon_id'] ?: 0;
        Db::startTrans();
        try {
            //生成订单号
            $order_no = get_order_sn('GD');
            $cost_integral = 0;
            $is_pure_integral = 0;
            //实例化商品模型
            $goodinfo = new Goods();
            $goodsku = new GoodsSku();
            // 初始化变量
            $goods_list = $where = $where1 = $gmap = [];
            $payable_money = $order_money = $cost_price_total = 0;
            //添加订单商品表信息
            foreach ($order_info['goods'] as $g) {
                // 开始循环商品信息
                $good_info = $goodinfo->get($g['id']);
                $sku_id = $g['sku_id'] ? $g['sku_id'] : 0;
                $activity_id = $order_info['activity_id'] ?? 0;
                $calculate_price = GoodsService::calculate_price($user['id'], $g['id'], $sku_id, $activity_id, $g['number']);
                if ($calculate_price['code'] == 0) {
                    exception($calculate_price['msg']);
                }
                $shop_price = $calculate_price['data']['shop_price'];
                $cost_price = $calculate_price['data']['cost_price'];
                $sku_name = '';
                if ($sku_id) {
                    $sku_name = $goodsku->where(['sku_id' => $sku_id, 'goods_id' => $g['id']])->value("key_name") ?? '';
                }
                $goods_money = Money::format_money($shop_price * $g['number'], 1);
                $payable_money += $goods_money;
                $order_money += $goods_money;
                $cost_price_total += $cost_price;
                $share_sign = $data['share_sign'] ?? '';
                $activity_details = GoodsService::activity_details($activity_id, $g['id'], $sku_id);
                if ($activity_details['code'] == 0) {
                    exception(lang('活动未开始或已结束，下单失败'));
                }
                $activity_data = $activity_details['data'];
                if ($activity_data['stock'] < $g['number']) {
                    exception(lang('库存不足，无法下单'));
                }
                $is_pure_integral = $activity_data['is_pure_integral'];
                $cost_integral = $activity_data['sales_integral'];
                $goods_list[] = [
                    'order_sn' => $order_no,
                    'goods_id' => $g['id'],
                    'goods_name' => $good_info['name'],
                    'shop_price' => $shop_price,
                    'sku_id' => $sku_id,
                    'num' => $g['number'],
                    'goods_thumb' => $good_info['thumb'],
                    'order_status' => 0,
                    'sku_name' => $sku_name,
                    'goods_money' => $goods_money,
                    'share_sign' => $share_sign,
                    'create_time' => time(),
                    'sender_id' => $good_info['sender_id'],
                    'activity_id' => $activity_id,
                    'is_pure_integral' => $is_pure_integral,
                    'sales_integral' => $activity_data['sales_integral'],
                    'cost_price' => $cost_price
                ];
                //扣库存
                $update_stock = GoodsService::update_stock($g['id'], -$g['number'], $sku_id, $activity_id);
                if ($update_stock['code'] == 0) {
                    exception($update_stock['msg']);
                }
                //加销量
                $sale_update = GoodsService::update_sale($g['id'], $g['number'], $sku_id, $activity_id);
                if ($sale_update['code'] == 0) {
                    exception($sale_update['msg']);
                }
            }
            //插入订单商品表
            $goods_list = (new OrderGoods())->insertAll($goods_list);
            if (!$goods_list) {
                exception(lang('保存订单商品失败'));
            }
            //如果提交了优惠券id，则查询数据库中的优惠券
            if ($coupon_id) {
                //TODO 可使用优惠券待优化
                $coupon_record = new CouponRecord();
                $coupon = $coupon_record->get_user_coupon(['cr.user_id' => $user['id'], 'cr.id' => $coupon_id, 'cr.status' => 1]);
                if (!$coupon) {
                    exception(lang('优惠券无效，请重新下单'));
                }
                if ($payable_money - $coupon['money'] <= 0) {
                    exception(lang('优惠券金额不能大于商品总价'));
                }
                $payable_money = Money::format_money(($payable_money - $coupon['money']), 1);
                $coupon_update = $coupon_record->where(['id' => $data['coupon_id'], 'status' => 1])->update(['status' => 3, 'use_time' => time(), 'order_sn' => $order_no]);
                if (!$coupon_update) {
                    exception(lang('优惠券无效，请重新下单'));
                }
            }
            $pay_status = 0;
            $order_status = 0;
            if ($is_pure_integral == 1) {
                //纯积分 实际支付金额为0
                $payable_money = $data['payable_money'] = 0;
                $result = ScoreLog::change($user['id'], -$cost_integral, 3, lang('积分商城兑换'));
                if (!$result) {
                    throw new \Exception(lang('积分日志写入失败'));
                }
                $pay_status = 1;
                $order_status = 1;
                $orderData['pay_time'] = time();
            }
            // 计算出来的金额和提交过的来金额做对比，一致才往下走
            if (!check_money($data['payable_money'], $payable_money)) {
                exception(lang('金额校验失败') . ',' . lang('计价') . ':' . $payable_money . ':' . $data['payable_money']);
            }

            //如果pickup_id不为空，为自提类型的订单，add by wangph at 2021-4-19
            if (isset($order_info['invite_address']['pickup_id']) && $order_info['invite_address']['pickup_id']) {
                $order_pickup = [
                    'order_sn' => $order_no,
                    'pickup_id' => $order_info['invite_address']['pickup_id'],
                    'pickup_date' => $order_info['invite_address']['pickup_date'],
                    'pickup_delivery_time_id' => $order_info['invite_address']['pickup_delivery_time_id'],
                    'user_pickup_id' => $order_info['invite_address']['user_pickup_id'],
                    'remark' => $order_info['remark'] ?? '',
                ];
                $res_pickup = Db::name('order_pickup')->insert($order_pickup);
                if (!$res_pickup) {
                    exception(lang('保存订单自提信息失败'));
                }
            }

            if ($send_type != 1) {
                //自提订单金额不含配送费,仅商品总额
                $order_money = Money::format_money(($order_money + $order_info['express_price']), 1);
                $payable_money = Money::format_money(($order_money + $order_info['express_price']), 1);
                $res1 = self::saveGoodsInfo($order_info, $order_no, $order_info['remark']);
                if (!$res1) {
                    exception(lang('保存订单附加信息失败'));
                }
            }

            //订单配送类型send_type
            $orderData['send_type'] = $send_type;
            // 组装订单信息
            $orderData['user_id'] = $user['id'];
            $orderData['order_sn'] = $order_no;
            $orderData['order_money'] = $order_money;
            $orderData['payable_money'] = $payable_money;
            $orderData['cost_integral'] = $cost_integral;
            $orderData['status'] = $order_status;
            $orderData['real_money'] = 0;
            $orderData['pay_status'] = $pay_status;
            $orderData['pay_type'] = $data['pay_type'] ?? '';
            $orderData['coupon_id'] = $data['coupon_id'] ? $data['coupon_id'] : 0;
            $orderData['coupon_money'] = $coupon['money'] ? $coupon['money'] : 0;
            $orderData['order_type'] = $data['order_type'];
            $orderData['create_time'] = time();
            $orderData['update_time'] = time();
            $orderData['cost_price_total'] = $cost_price_total;

            // 插入订单信息
            $ret = (new Order())->insert($orderData);
            if (!$ret) {
                exception(lang('创建订单失败'));
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return exception($e->getMessage());
        }
        return [
            'order_sn' => $order_no
        ];
    }

    //添加商品订单附表信息
    public static function saveGoodsInfo($order_info, $order_no, $remark = null)
    {
        $order_goods_info['order_sn'] = $order_no;
        $order_goods_info['address_id'] = $order_info['address']['address_id'];
        $order_goods_info['receiver_mobile'] = $order_info['address']['mobile'];
        $order_goods_info['receiver_address'] = $order_info['address']['address'];
        $order_goods_info['receiver_name'] = $order_info['address']['name'];
        $order_goods_info['province'] = $order_info['address']['province'];
        $order_goods_info['city'] = $order_info['address']['city'];
        $order_goods_info['district'] = $order_info['address']['district'];
        $order_goods_info['remark'] = $remark ?? '';
        $order_goods_info['express_price'] = $order_info['express_price'] ? $order_info['express_price'] : 0;
        $order_goods_info['sex'] = $order_info['sex'];
        $order_goods_info['label_name'] = $order_info['address']['label_name'] ?? '';
        return Db::name('order_goods_info')->insert($order_goods_info);
    }


    /**
     * 创建用户购买会员订单
     * @param [type] $data [description]
     * @param [type] $user [description]
     */
    public static function addUserLevelOrder($data, $user)
    {
        $order_info = json_decode($data['order_info'], true);
        Db::startTrans();
        try {
            //生成订单号
            $order_no = get_order_sn('GD');
            $nowTime = time();
            //获取用户会员等级信息
            $userInfo = Db::name('user_level_card_data')
                ->alias('d')
                ->join('user_level_card c', 'c.id = d.level_id')
                ->where('d.user_id', $user['id'])
                ->where('d.status', 1)
                ->field('c.level')
                ->find();
            // 获取等级信息
            $levelInfo = LevelNewModel::getInfo($data['level_id']);
            if (empty($levelInfo)) {
                exception(lang('数据有误'));
            }
            // 会员不能降级
            if ($userInfo['level'] > $levelInfo['level'] && module_config('user.user_card') == 1) {
                exception(lang('会员不能降级，请续费同等级或开通更高等级'));
            }
            // 金额和提交过的来金额做对比，一致才往下走
            if (!check_money($data['payable_money'], $levelInfo['price'])) {
                exception(lang('金额校验失败'));
            }
            $level_list['order_sn'] = $order_no;
            $level_list['create_time'] = $nowTime;
            $level_list['update_time'] = $nowTime;
            $level_list['user_id'] = $user['id'];
            $level_list['level_id'] = $data['level_id'];
            $level_list['level_price'] = $levelInfo['price'];
            //插入订单商品表
            $res2 = Db::name('order_user_level')->insert($level_list);
            if (!$res2) {
                exception(lang('保存附属订单失败'));
            }
            // 组装订单信息
            $orderData['user_id'] = $user['id'];
            $orderData['order_sn'] = $order_no;
            $orderData['order_money'] = $level_list['level_price'];
            $orderData['payable_money'] = $level_list['level_price'];
            $orderData['status'] = 0;
            $orderData['real_money'] = 0;
            $orderData['pay_status'] = 0;
            $orderData['pay_type'] = $data['pay_type'] ?? '';
            $orderData['coupon_id'] = $data['coupon_id'] ? $data['coupon_id'] : 0;
            $orderData['coupon_money'] = 0;
            $orderData['order_type'] = $data['order_type'];
            // 插入订单信息
            $ret = self::create($orderData);
            if (!$ret) {
                exception(lang('创建订单失败'));
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            exception($e->getMessage());
            return false;
        }
        return [
            'order_sn' => $order_no
        ];
    }

    /**
     * 创建预售订单
     * @param $data
     * @param $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/3/7 23:39
     */
    public static function addPreorderOrder($data, $user)
    {
        $order_info = json_decode($data['order_info'], true);
        //获取订单的配送类型 
        $send_type = $order_info['send_type'] ?? 0;
        Db::startTrans();
        try {
            //生成订单号
            $order_no = get_order_sn('GD');
            $order_no2 = get_order_sn('GD');
            $nowTime = time();

            $money = 0;
            $pre_money = 0;
            //实例化商品模型
            $goodinfo = new \app\goods\model\Goods();
            $goodsku = new \app\goods\model\GoodsSku();
            $nowHour = (int)date('H');
            //添加订单商品表信息
            foreach ($order_info['goods'] as $g) {
                // 初始化变量
                $goods = $where = $where1 = $gmap = [];
                // 开始循环商品信息
                $good_info = $goodinfo->get($g['id']);
                $goods['order_sn'] = $order_no;
                $goods['goods_id'] = $g['id'];
                $goods['goods_name'] = $good_info['name'];
                $goods['shop_price'] = $good_info['shop_price'];
                $goods['sku_id'] = $g['sku_id'] ? $g['sku_id'] : 0;
                $goods['num'] = $g['number'];
                $stock = $good_info['stock'];
                $goods['goods_thumb'] = $good_info['thumb'];
                $goods['order_status'] = 0;
                $goods['sender_id'] = $good_info['sender_id'];
                if ($goods['sku_id']) {
                    //如果是sku商品，则查询sku的价格和库存
                    $sku_info = $goodsku->get(['sku_id' => $goods['sku_id'], 'goods_id' => $g['id']]);
                    $goods['shop_price'] = $sku_info['shop_price'];
                    $stock = $sku_info['stock'];
                    $goods['sku_name'] = $sku_info['key_name'];
                } else {
                    $goods['shop_price'] = $good_info['shop_price'];
                    $stock = $good_info['stock'];
                    $goods['sku_name'] = '';
                }
                if ($order_info['activity_id']) {//根据活动ID修改为活动价格和活动库存
                    $gmap[] = ['sku_id', '=', $goods['sku_id']];
                    $gmap[] = ['goods_id', '=', $goods['goods_id']];
                    $gmap[] = ['status', '=', 1];
                    $gmap[] = ['activity_id', '=', $order_info['activity_id']];
                    $ga = Db::name('goods_activity')->where(
                        [['id', '=', $order_info['activity_id']],
                            ['status', '=', 1],
                            ['sdate', 'lt', $nowTime],
                            ['edate', 'gt', $nowTime]]
                    )->find();
                    $gcd = Db::name('goods_activity_details')->where($gmap)->find();
                    if ($gcd && $ga) {
                        $goods['deposit'] = $gcd['deposit'];
                        $goods['activity_id'] = $gcd['activity_id'];
                        $price['shop_price'] = $gcd['activity_price'];
                        $stock = $gcd['stock'];
                    } else {
                        exception(lang('活动停止,下单失败'));
                    }
                }
                if ($stock < $g['number']) {
                    // exception($goods['sku_id']?$sku_info['key_name']:$sku_info['name'] . ",库存不足，无法下单");
                    exception(lang('库存不足，无法下单'));
                }
                //计算商品总价
                $goods['goods_money'] = bcmul($goods['shop_price'], $g['number'], 2);//原价
                $price['goods_money'] = bcmul($price['shop_price'], $g['number'], 2);//定金
                $money = bcadd($money, $goods['goods_money'], 2);
                $pre_money = bcadd($pre_money, $price['goods_money'], 2);
                $result = self::inventoryReduction($goods, $g['number']);
                if ($result['state']) {
                    exception($result['info']);
                }
                Db::name('goods_activity_details')->where([['id', '=', $gcd['id']]])->setDec('stock', $g['number']);
                Db::name('goods_activity_details')->where([['id', '=', $gcd['id']]])->setInc('sales_sum', $g['number']);

                $goods['share_sign'] = $data['share_sign'];
                // 增加sku销量
                $goods_list[] = $goods;
            }
            //插入订单商品表
            $res2 = Db::name('order_goods_list')->insertAll($goods_list);
            if (!$res2) {
                exception(lang('保存订单商品失败'));
            }
            // 计算出来的金额和提交过的来金额做对比，一致才往下走
            if (!check_money($data['payable_money'], $goods['deposit'])) {
                exception(lang('金额校验失败'));
            }
            //如果pickup_id不为空，为自提类型的订单，add by wangph at 2021-4-19
            if (isset($order_info['invite_address']['pickup_id']) && $order_info['invite_address']['pickup_id']) {
                $order_pickup = [
                    'order_sn' => $order_no,
                    'pickup_id' => $order_info['invite_address']['pickup_id'],
                    'pickup_date' => $order_info['invite_address']['pickup_date'],
                    'pickup_delivery_time_id' => $order_info['invite_address']['pickup_delivery_time_id'],
                    'user_pickup_id' => $order_info['invite_address']['user_pickup_id'],
                    'remark' => $order_info['remark'] ?? '',
                ];
                $res_pickup = Db::name('order_pickup')->insert($order_pickup);
                if (!$res_pickup) {
                    exception(lang('保存订单自提信息失败'));
                }
                //自提类型
                $send_type = 1;
            }

            if ($send_type == 1) {
                //自提订单金额不含配送费,仅商品总额
                $order_money = $money;
            } else {
                //快递订单金额含配送费
                $order_money = bcadd($money, $order_info['express_price'], 2);
            }

            //订单配送类型send_type
            $orderData['send_type'] = $send_type;
            // 组装订单信息
            $orderData['user_id'] = $user['id'];
            $orderData['order_sn'] = $order_no;
            $orderData['order_money'] = $order_money;
            $orderData['payable_money'] = $goods['deposit'];
            $orderData['status'] = 0;
            $orderData['real_money'] = 0;
            $orderData['pay_status'] = 0;
            $orderData['pay_type'] = $data['pay_type'] ?? '';
            $orderData['coupon_id'] = $data['coupon_id'] ? $data['coupon_id'] : 0;
            $orderData['coupon_money'] = 0;
            $orderData['order_type'] = $data['order_type'];
            // 插入订单信息
            $ret = self::create($orderData);
            if (!$ret) {
                exception(lang('创建订单失败'));
            }
            $payable_money = bcadd($order_info['express_price'], $money, 2) - $goods['deposit'];
            $orderFinalData['user_id'] = $user['id'];
            $orderFinalData['order_sn'] = $order_no2;
            $orderFinalData['order_money'] = $order_money;
            $orderFinalData['payable_money'] = $payable_money;
            $orderFinalData['status'] = 0;
            $orderFinalData['real_money'] = 0;
            $orderFinalData['pay_status'] = 0;
            $orderFinalData['pay_type'] = '';
            $orderFinalData['coupon_id'] = 0;
            $orderFinalData['coupon_money'] = 0;
            $orderFinalData['order_type'] = 8;
            //订单配送类型send_type
            $orderFinalData['send_type'] = $send_type;

            // 如果有发票信息则生成订单发票信息
            if (!empty($order_info['invoice']) && !empty($order_info['invoice']['invoice_type']) && !empty($order_info['invoice']['invoice_title'])) {
                $orderFinalData['invoice_status'] = 1;
                OrderInvoice::insert([
                    'order_sn' => $order_no,
                    'invoice_type' => $order_info['invoice']['invoice_type'],
                    'invoice_title' => $order_info['invoice']['invoice_title'],
                    'invoice_company_duty_paragraph' => $order_info['invoice']['invoice_company_duty_paragraph'],
                    'invoice_status' => 1, // 申请开票中
                    'invoice_price' => $payable_money, // 申请开票金额
                    'invoice_company_bank' => $order_info['invoice']['invoice_company_bank'],
                    'invoice_company_bank_num' => $order_info['invoice']['invoice_company_bank_num'],
                    'invoice_company_address' => $order_info['invoice']['invoice_company_address'],
                    'invoice_company_phone' => $order_info['invoice']['invoice_company_phone'],
                ]);
            }

            self::create($orderFinalData);
            Db::name('order_relation')->insertGetId(['book_order_sn' => $order_no, 'final_order_sn' => $order_no2]);

            if ($send_type != 1) {
                $res1 = self::saveGoodsInfo($order_info, $order_no, $order_info['remark']);
                if (!$res1) {
                    exception(lang('保存订单附加信息失败'));
                }
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            exception($e->getMessage());
            return false;
        }
        return [
            'order_sn' => $order_no
        ];
    }

    //商品库存减少
    public static function inventoryReduction($goods, $number)
    {
        if ($goods['sku_id']) {
            $where[] = ['sku_id', '=', $goods['sku_id']];
            $where[] = ['stock', '>=', $number];
            $key_name = Db::name('goods_sku')->where($where)->value('key_name');
            $res = Db::name('goods_sku')->where($where)->setDec('stock', $number);
        } else {
            $where[] = ['id', '=', $goods['goods_id']];
            $where[] = ['stock', '>=', $number];
            $key_name = Db::name('goods')->where($where)->value('name');
            $res = Db::name('goods')->where($where)->setDec('stock', $number);
        }
        if (!$res) {
            return ['state' => 1, 'info' => $key_name . ',' . lang('库存不足，无法下单')];
        } else {
            return ['state' => 0, 'info' => ""];
        }
    }

    /**
     * 创建商城秒杀订单
     * @param array $data 下单参数数组
     * @param array $user 会员数据数组
     * @return array
     * @throws \Exception
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function addSeckillOrder($data, $user)
    {
        $order_info = json_decode($data['order_info'], true);

        //获取订单的配送类型 
        $send_type = $order_info['send_type'] ?? 0;
        Db::startTrans();
        try {
            //生成订单号
            $order_no = get_order_sn('GD');
            $nowTime = time();

            $money = 0;
            //实例化商品模型
            $goodinfo = new \app\goods\model\Goods();
            $goodsku = new \app\goods\model\GoodsSku();
            $nowHour = (int)date('H');
            //添加订单商品表信息
            foreach ($order_info['goods'] as $g) {
                // 初始化变量
                $goods = $where = $where1 = $gmap = [];
                // 开始循环商品信息
                $good_info = $goodinfo->get($g['id']);
                $goods['order_sn'] = $order_no;
                $goods['goods_id'] = $g['id'];
                $goods['goods_name'] = $good_info['name'];
                $goods['shop_price'] = $good_info['shop_price'];
                $goods['sku_id'] = isset($g['sku_id']) ? $g['sku_id'] : 0;
                $goods['num'] = $g['number'];
                $stock = $good_info['stock'];
                $goods['goods_thumb'] = $good_info['thumb'];
                $goods['order_status'] = 0;
                $goods['sender_id'] = $good_info['sender_id'];
                if ($goods['sku_id']) {
                    //如果是sku商品，则查询sku的价格和库存
                    $sku_info = $goodsku->get(['sku_id' => $goods['sku_id'], 'goods_id' => $g['id']]);
                    $goods['shop_price'] = $sku_info['shop_price'];
                    $stock = $sku_info['stock'];
                    $goods['sku_name'] = $sku_info['key_name'];
                } else {
                    $goods['shop_price'] = $good_info['shop_price'];
                    $goods['sku_name'] = '';
                }
                if ($order_info['activity_id']) {//根据活动ID修改为活动价格和活动库存
                    $gmap[] = ['sku_id', '=', $goods['sku_id']];
                    $gmap[] = ['goods_id', '=', $goods['goods_id']];
                    $gmap[] = ['status', '=', 1];
                    $gmap[] = ['activity_id', '=', $order_info['activity_id']];

                    $ga = Db::name('goods_activity')->where(
                        [['id', '=', $order_info['activity_id']],
                            ['status', '=', 1],
                            ['sdate', 'lt', $nowTime],
                            ['edate', 'gt', $nowTime]]
                    )->cache(3600)->find();
                    $gcd = Db::name('goods_activity_details')->where($gmap)->cache(3600)->find();
                    if ($gcd['unlimited'] == 1) {
                        if ($gcd['start_time'] > $nowHour || $gcd['end_time'] <= $nowHour) {
                            exception(lang('活动未开始或已结束,下单失败'));
                        }
                    }
                    if ($gcd && $ga) {
                        $goods['activity_id'] = $gcd['activity_id'];
                        $goods['shop_price'] = self::getRightPrice($gcd, $user);
                        $stock = $gcd['stock'];
                    } else {
                        exception(lang('活动停止,下单失败'));
                    }
                }

                if ($stock < $g['number']) {
                    // exception($goods['sku_id']?$sku_info['key_name']:$sku_info['name'] . ",库存不足，无法下单");
                    exception(lang('库存不足，无法下单'));
                }
                //计算商品总价
                $goods['goods_money'] = bcmul($goods['shop_price'], $g['number'], 2);
                $money = bcadd($money, $goods['goods_money'], 2);
                Db::name('goods_activity_details')->where([['id', '=', $gcd['id']]])->setDec('stock', $g['number']);
                // 增加销量
                Db::name('goods_activity_details')->where(['id' => $gcd['id']])->setInc('sales_sum', $g['number']);
                $goods['share_sign'] = $data['share_sign'];
                // 增加sku销量
                $goods_list[] = $goods;
                $goods_id[] = $goods['goods_id'];
            }

            //插入订单商品表
            $res2 = Db::name('order_goods_list')->insertAll($goods_list);
            if (!$res2) {
                exception(lang('保存订单商品失败'));
            }
            $payable_money = $money;
            //如果提交了优惠券id，则查询数据库中的优惠券
            if ($data['coupon_id']) {
                $cou = new \app\operation\model\CouponRecord();
                $coupon = $cou->get_user_coupon(['cr.user_id' => $user['id'], 'cr.id' => $data['coupon_id'], 'cr.status' => 1]);
                if (!$coupon) {
                    exception(lang('优惠券无效，请重新下单'));
                }
                $payable_money = bcsub($money, $coupon['money'], 2);
                \app\operation\model\CouponRecord::where(['id' => $coupon['id']])->update([
                    "use_time" => time(),
                    "order_sn" => $order_no,
                    "goods_id" => $goods_id[0],
                ]);
            }
            if ($data['coin_id']) {
                $payable_money = self::useCoin($data['coin_id'], $payable_money, $order_no);
            }
            if ($data['shipping_coupon']) {
                $info = self::free_shipping($user['id'], $data['shipping_coupon'], $order_no);
                if ($info['info']) {
                    $order_info['express_price'] = 0;
                } else {
                    exception($info['msg']);
                }
            }
            //如果有运费，加上
            if ($order_info['express_price']) {
                $payable_money = bcadd($payable_money, $order_info['express_price'], 2);
            }
            //如果选择了积分抵扣，减掉
            if ($data['isSelect_integral_reduce'] == 1) {
                $payable_money = bcsub($payable_money, $data['reduce_money'], 2);
            }
            // 计算出来的金额和提交过的来金额做对比，一致才往下走
            if (!check_money($data['payable_money'], $payable_money)) {
                exception(lang('金额校验失败') . ',' . lang('计价') . ':' . $payable_money);
            }

            //如果pickup_id不为空，为自提类型的订单，add by wangph at 2021-4-19
            if (isset($order_info['invite_address']['pickup_id']) && $order_info['invite_address']['pickup_id']) {
                $order_pickup = [
                    'order_sn' => $order_no,
                    'pickup_id' => $order_info['invite_address']['pickup_id'],
                    'pickup_date' => $order_info['invite_address']['pickup_date'],
                    'pickup_delivery_time_id' => $order_info['invite_address']['pickup_delivery_time_id'],
                    'user_pickup_id' => $order_info['invite_address']['user_pickup_id'],
                    'remark' => $order_info['remark'] ?? '',
                ];
                $res_pickup = Db::name('order_pickup')->insert($order_pickup);
                if (!$res_pickup) {
                    exception(lang('保存订单自提信息失败'));
                }
                //自提类型
                $send_type = 1;
            }

            if ($send_type == 1) {
                //自提订单金额不含配送费,仅商品总额
                $order_money = $money;
            } else {
                //快递订单金额含配送费
                $order_money = bcadd($money, $order_info['express_price'], 2);
            }

            //订单配送类型send_type
            $orderData['send_type'] = $send_type;
            // 组装订单信息
            $orderData['user_id'] = $user['id'];
            $orderData['order_sn'] = $order_no;
            $orderData['order_money'] = $order_money;
            $orderData['payable_money'] = $payable_money;
            $orderData['status'] = 0;
            $orderData['real_money'] = 0;
            $orderData['pay_status'] = 0;
            $orderData['pay_type'] = $data['pay_type'] ?? '';
            $orderData['coupon_id'] = $data['coupon_id'] ? $data['coupon_id'] : 0;
            $orderData['coupon_money'] = $coupon['money'] ? $coupon['money'] : 0;
            $orderData['order_type'] = $data['order_type'];

            // 如果有发票信息则生成订单发票信息
            if (!empty($order_info['invoice']) && !empty($order_info['invoice']['invoice_type']) && !empty($order_info['invoice']['invoice_title'])) {
                $orderData['invoice_status'] = 1;
                OrderInvoice::insert([
                    'order_sn' => $order_no,
                    'invoice_type' => $order_info['invoice']['invoice_type'],
                    'invoice_title' => $order_info['invoice']['invoice_title'],
                    'invoice_company_duty_paragraph' => $order_info['invoice']['invoice_company_duty_paragraph'],
                    'invoice_status' => 1, // 申请开票中
                    'invoice_price' => $payable_money, // 申请开票金额
                    'invoice_company_bank' => $order_info['invoice']['invoice_company_bank'],
                    'invoice_company_bank_num' => $order_info['invoice']['invoice_company_bank_num'],
                    'invoice_company_address' => $order_info['invoice']['invoice_company_address'],
                    'invoice_company_phone' => $order_info['invoice']['invoice_company_phone'],
                ]);
            }
            // 插入订单信息
            $ret = self::create($orderData);
            if (!$ret) {
                exception(lang('创建订单失败'));
            }

            //非自提类型判断
            if ($send_type != 1) {
                $res1 = self::saveGoodsInfo($order_info, $order_no, $order_info['remark']);
                if (!$res1) {
                    exception(lang('保存订单附加信息失败'));
                }
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            exception($e->getMessage());
            return false;
        }
        return [
            'order_sn' => $order_no
        ];
    }

    /**
     * 使用免运券并记录订单号
     * @param int $uid 用户ID
     * @param int $coupon_id 优惠券ID
     * @param string $order_no 订单号
     * @return array
     * @throws \Exception
     * @author jxy[ 415782189@qq.com ]
     */
    public static function free_shipping($uid, $coupon_id, $order_no)
    {
        $cou = new \app\operation\model\CouponRecord();
        $coupon = $cou->get_user_coupon(['cr.user_id' => $uid, 'cr.id' => $coupon_id, 'cr.status' => 1]);
        if (!$coupon) {
            return ['info' => 0, 'msg' => '无优惠券数据'];
        }
        $res = $cou->where(['id' => $coupon_id, 'status' => 1])->update(['status' => 3, 'use_time' => time(), 'order_sn' => $order_no]);
        if ($res) {
            return ['info' => 1, 'msg' => ''];
        } else {
            return ['info' => 0, 'msg' => '优惠券使用失败'];
        }
    }


    /**
     * 创建商城拼团订单
     * @param array $data 下单参数数组
     * @param array $user 会员数据数组
     * @return array
     * @throws \Exception
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function addGroupOrder($data, $user)
    {
        $order_info = json_decode($data['order_info'], true);
        //获取订单的配送类型 
        $send_type = $order_info['send_type'] ?? 0;
        //优惠券id
        $coupon_id = $data['coupon_id'] ?? 0;
        Db::startTrans();
        try {
            //生成订单号
            $order_no = get_order_sn('PT');
            $money = 0;
            //实例化商品模型
            $goodinfo = new \app\goods\model\Goods();
            $goodsku = new \app\goods\model\GoodsSku();
            //添加订单商品表信息
            $payable_money = $order_money = $cost_price_total = 0;
            foreach ($order_info['goods'] as $g) {
                // 开始循环商品信息
                $good_info = $goodinfo->get($g['id']);
                $sku_id = $g['sku_id'] ? $g['sku_id'] : 0;
                $activity_id = $order_info['activity_id'] ?? 0;
                $calculate_price = GoodsService::calculate_price($user['id'], $g['id'], $sku_id, $activity_id, $g['number']);
                if ($calculate_price['code'] == 0) {
                    exception($calculate_price['msg']);
                }
                $shop_price = $calculate_price['data']['shop_price'];
                $cost_price = $calculate_price['data']['cost_price'];
                $sku_name = '';
                if ($sku_id) {
                    $sku_name = $goodsku->where(['sku_id' => $sku_id, 'goods_id' => $g['id']])->value("key_name") ?? '';
                }
                $goods_money = Money::format_money($shop_price * $g['number'], 1);
                $payable_money += $goods_money;
                $order_money += $goods_money;
                $cost_price_total += $cost_price;
                $share_sign = $data['share_sign'] ?? '';
                $activity_details = GoodsService::activity_details($activity_id, $g['id'], $sku_id);
                if ($activity_details['code'] == 0) {
                    exception(lang('活动未开始或已结束，下单失败'));
                }
                $activity_data = $activity_details['data'];
                if ($activity_data['stock'] < $g['number']) {
                    exception(lang('库存不足，无法下单'));
                }
                $is_pure_integral = $activity_data['is_pure_integral'];
                $goods_list[] = [
                    'order_sn' => $order_no,
                    'goods_id' => $g['id'],
                    'goods_name' => $good_info['name'],
                    'shop_price' => $shop_price,
                    'sku_id' => $sku_id,
                    'num' => $g['number'],
                    'goods_thumb' => $good_info['thumb'],
                    'order_status' => 0,
                    'sku_name' => $sku_name,
                    'goods_money' => $goods_money,
                    'share_sign' => $share_sign,
                    'create_time' => time(),
                    'sender_id' => $good_info['sender_id'],
                    'activity_id' => $activity_id,
                    'is_pure_integral' => $is_pure_integral,
                    'sales_integral' => $activity_data['sales_integral'],
                    'cost_price' => $cost_price
                ];

                //扣库存
                $update_stock = GoodsService::update_stock($g['id'], -$g['number'], $sku_id, $activity_id);
                if ($update_stock['code'] == 0) {
                    exception($update_stock['msg']);
                }
                //加销量
                $sale_update = GoodsService::update_sale($g['id'], $g['number'], $sku_id, $activity_id);
                if ($sale_update['code'] == 0) {
                    exception($sale_update['msg']);
                }
                //拼团
                $group_id = $data['group_id'] ?? 0;
                $make_group = OrderService::make_group($activity_id, $g['id'], $user['id'], $order_no, $activity_data['join_number'], $group_id);
                if ($make_group['code'] == 0) {
                    exception($make_group['msg']);
                }

            }
            //插入订单商品表
            $res2 = Db::name('order_goods_list')->insertAll($goods_list);
            if (!$res2) {
                exception(lang('保存订单商品失败'));
            }
            //如果提交了优惠券id，则查询数据库中的优惠券
            if ($coupon_id) {
                //TODO 可使用优惠券待优化
                $coupon_record = new CouponRecord();
                $coupon = $coupon_record->get_user_coupon(['cr.user_id' => $user['id'], 'cr.id' => $coupon_id, 'cr.status' => 1]);
                if (!$coupon) {
                    exception(lang('优惠券无效，请重新下单'));
                }
                if ($payable_money - $coupon['money'] <= 0) {
                    exception(lang('优惠券金额不能大于商品总价'));
                }
                $payable_money = Money::format_money(($payable_money - $coupon['money']), 1);
                $coupon_update = $coupon_record->where(['id' => $data['coupon_id'], 'status' => 1])->update(['status' => 3, 'use_time' => time(), 'order_sn' => $order_no]);
                if (!$coupon_update) {
                    exception(lang('优惠券无效，请重新下单'));
                }
            }
            //普通快递配送订单需要计入运费，自提不计运费，wangph修改2021-4-22
            if ($send_type != 1) {
                //添加商品订单附表信息
                $order_goods_info = [
                    'order_sn' => $order_no,
                    'address_id' => $order_info['address']['address_id'],
                    'receiver_mobile' => $order_info['address']['mobile'],
                    'receiver_address' => $order_info['address']['address'],
                    'receiver_name' => $order_info['address']['name'],
                    'remark' => $order_info['remark'] ?? '',
                    'express_price' => $order_info['express_price'] ?? 0,
                    'province' => $order_info['address']['province'],
                    'city' => $order_info['address']['city'],
                    'district' => $order_info['address']['district'],
                    'sex' => $order_info['address']['sex'],
                    'label_name' => $order_info['address']['label_name']

                ];
                $res1 = Db::name('order_goods_info')->insert($order_goods_info);
                if (!$res1) {
                    exception(lang('保存订单附加信息失败'));
                }
                //如果有运费，加上
                if ($order_info['express_price']) {
                    //实际支付金额
                    $payable_money = Money::format_money($payable_money + $order_info['express_price'], 1);
                    //订单金额
                    $order_money = Money::format_money($order_money + $order_info['express_price'], 1);
                }
            }
            // 计算出来的金额和提交过的来金额做对比，一致才往下走
            $miss = 0.001;
            if (!check_money($data['payable_money'], $payable_money)) {
                exception(lang('金额校验失败') . ',' . lang('计价') . ':' . $payable_money);
            }
            //如果pickup_id不为空，为自提类型的订单，add by wangph at 2021-4-19
            if (isset($order_info['invite_address']['pickup_id']) && $order_info['invite_address']['pickup_id']) {
                $order_pickup = [
                    'order_sn' => $order_no,
                    'pickup_id' => $order_info['invite_address']['pickup_id'],
                    'pickup_date' => $order_info['invite_address']['pickup_date'],
                    'pickup_delivery_time_id' => $order_info['invite_address']['pickup_delivery_time_id'],
                    'user_pickup_id' => $order_info['invite_address']['user_pickup_id'],
                    'remark' => $order_info['remark'] ?? '',
                ];
                $res_pickup = Db::name('order_pickup')->insert($order_pickup);
                if (!$res_pickup) {
                    exception(lang('保存订单自提信息失败'));
                }
            }

            //订单配送类型send_type
            $orderData['send_type'] = $send_type;
            // 组装订单信息
            $orderData['user_id'] = $user['id'];
            $orderData['order_sn'] = $order_no;
            $orderData['order_money'] = $order_money;
            $orderData['payable_money'] = $payable_money;
            $orderData['status'] = 0;
            $orderData['real_money'] = 0;
            $orderData['pay_status'] = 0;
            $orderData['pay_type'] = $data['pay_type'] ?? '';
            $orderData['coupon_id'] = $data['coupon_id'] ? $data['coupon_id'] : 0;
            $orderData['coupon_money'] = $coupon['money'] ? $coupon['money'] : 0;
            $orderData['order_type'] = $data['order_type'];
            $orderData['cost_price_total'] = $cost_price_total ?? 0;
            // 如果有发票信息则生成订单发票信息
            if (!empty($order_info['invoice']) && !empty($order_info['invoice']['invoice_type']) && !empty($order_info['invoice']['invoice_title'])) {
                $orderData['invoice_status'] = 1;
                OrderInvoice::insert([
                    'order_sn' => $order_no,
                    'invoice_type' => $order_info['invoice']['invoice_type'],
                    'invoice_title' => $order_info['invoice']['invoice_title'],
                    'invoice_company_duty_paragraph' => $order_info['invoice']['invoice_company_duty_paragraph'],
                    'invoice_status' => 1, // 申请开票中
                    'invoice_price' => $payable_money, // 申请开票金额
                    'invoice_company_bank' => $order_info['invoice']['invoice_company_bank'],
                    'invoice_company_bank_num' => $order_info['invoice']['invoice_company_bank_num'],
                    'invoice_company_address' => $order_info['invoice']['invoice_company_address'],
                    'invoice_company_phone' => $order_info['invoice']['invoice_company_phone'],
                ]);
            }

            // 插入订单信息
            $ret = self::create($orderData);
            if (!$ret) {
                exception(lang('创建订单失败'));
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            exception($e->getMessage());
        }
        return [
            'order_sn' => $order_no
        ];
    }

    public static function getRightPrice($activityGoods, $user)
    {
        $user_level = Db::name('user')->where(['id' => $user['user_id']])->value('user_level');
        if ($user_level) {
            return $activityGoods['member_activity_price'];
        } else {
            return $activityGoods['activity_price'];
        }
    }

    /**
     * 是否参与拼团
     * @param $uid 用户ID
     * @param $goodsId 商品ID
     * @param $activityId 活动ID
     * @return int 组ID
     */
    protected static function isAndGroup($uid, $goodsId, $activityId)
    {
        $groupId = Db::name('goods_activity_group')->alias('gcg')
                ->leftJoin('goods_activity_group_user gagu', 'gagu.group_id = gcg.id')
                ->where('gagu.uid', '=', $uid)
                ->where('gcg.goods_id', '=', $goodsId)
                ->where('gcg.activity_id', '=', $activityId)
                ->where('gcg.is_full', '=', 0)
                ->where('gcg.status', '=', 1)
                ->order('gcg.id desc')
                ->value('gagu.group_id') ?? 0;
        return $groupId;
    }

    /**
     * 创建砍价活动订单
     * @param array $data 下单参数数组
     * @param array $user 会员数据数组
     * @return array
     * @throws \Exception
     * @author zhougs
     * @createTime 2020年12月24日14:59:33
     */
    public static function addBargainOrder($data, $user)
    {
        $order_info = json_decode($data['order_info'], true);
        //获取订单的配送类型 
        $send_type = $order_info['send_type'] ?? 0;
        Db::startTrans();
        try {
            //生成订单号
            $order_no = get_order_sn('KJ');
            $nowTime = time();

            $money = 0;
            //实例化商品模型
            $goodinfo = new \app\goods\model\Goods();
            $goodsku = new \app\goods\model\GoodsSku();
            $nowHour = (int)date('H');
            //添加订单商品表信息
            $baRes = 0;
            foreach ($order_info['goods'] as $g) {
                // 初始化变量
                $goods = $where = $where1 = $gmap = [];
                // 开始循环商品信息
                $good_info = $goodinfo->get($g['id']);
                $goods['order_sn'] = $order_no;
                $goods['goods_id'] = $g['id'];
                $goods['goods_name'] = $good_info['name'];
                $goods['shop_price'] = $good_info['shop_price'];
                $goods['sku_id'] = isset($g['sku_id']) ? $g['sku_id'] : 0;
                $goods['num'] = $g['number'];
                $stock = $good_info['stock'];
                $goods['goods_thumb'] = $good_info['thumb'];
                $goods['order_status'] = 0;
                $goods['sender_id'] = $good_info['sender_id'];
                if ($goods['sku_id']) {
                    //如果是sku商品，则查询sku的价格和库存
                    $sku_info = $goodsku->get(['sku_id' => $goods['sku_id'], 'goods_id' => $g['id']]);
                    $goods['shop_price'] = $sku_info['shop_price'];
                    $stock = $sku_info['stock'];
                    $goods['sku_name'] = $sku_info['key_name'];
                } else {
                    $goods['shop_price'] = $good_info['shop_price'];
                    $goods['sku_name'] = '';
                }
                if ($order_info['activity_id']) {//根据活动ID修改为活动价格和活动库存
                    $gmap[] = ['sku_id', '=', $goods['sku_id']];
                    $gmap[] = ['goods_id', '=', $goods['goods_id']];
                    $gmap[] = ['status', '=', 1];
                    $gmap[] = ['activity_id', '=', $order_info['activity_id']];

                    $ga = Db::name('goods_activity')->where(
                        [['id', '=', $order_info['activity_id']],
                            ['status', '=', 1]]
                    )->cache(3600)->find();
                    $gcd = Db::name('goods_activity_details')->where($gmap)->cache(3600)->find();
                    /*                    if ($gcd['unlimited'] == 1) {
                                            if ($gcd['start_time'] > $nowHour || $gcd['end_time'] <= $nowHour) {
                                                exception(lang('活动未开始或已结束,下单失败'));
                                            }
                                        }*/
                    if ($gcd && $ga) {
                        $goods['activity_id'] = $gcd['activity_id'];
                        $goods['shop_price'] = $gcd['activity_price'];
                        $stock = $gcd['stock'];
                    } else {
                        exception(lang('活动停止，下单失败'));
                    }

                    //添加商品进入砍价表
                    $bargainData = [
                        "activity_detail_id" => $gcd['id'],
                        "goods_id" => $gcd['goods_id'],
                        "sku_id" => $gcd['sku_id'],
                        "user_id" => $user['id'],
                        "price" => $gcd['activity_price'],
                        "create_time" => $nowTime,
                        "order_sn" => $order_no,
                    ];
                    $baRes = Db::name("goods_bargain_order")->insertGetId($bargainData);
                    if (!$baRes) {
                        exception(lang('加入砍价表失败'));
                    }
                }

                if ($stock < $g['number']) {
                    // exception($goods['sku_id']?$sku_info['key_name']:$sku_info['name'] . ",库存不足，无法下单");
                    exception(lang('库存不足，无法下单'));
                }

                //计算商品总价
                $goods['goods_money'] = bcmul($goods['shop_price'], $g['number'], 2);
                $money = bcadd($money, $goods['goods_money'], 2);
                /*                $result = self::inventoryReduction($goods, $g['number']);
                                if ($result['state']) {
                                    exception($result['info']);
                                }*/

//                Db::name('goods_activity_details')->where([['id', '=', $gcd['id']]])->setDec('stock', $g['number']);
                $goods['share_sign'] = $data['share_sign'];

                $goods_list[] = $goods;
            }

            //插入订单商品表
            $res2 = Db::name('order_goods_list')->insertAll($goods_list);
            if (!$res2) {
                exception(lang('保存订单商品失败'));
            }
            $payable_money = $money;


            if ($data['coin_id']) {
                $payable_money = self::useCoin($data['coin_id'], $payable_money, $order_no);
            }
            if ($data['shipping_coupon']) {
                $info = self::free_shipping($user['id'], $data['shipping_coupon'], $order_no);
                if ($info['info']) {
                    $order_info['express_price'] = 0;
                } else {
                    exception($info['msg']);
                }
            }

            //如果pickup_id不为空，为自提类型的订单，add by wangph at 2021-4-19
            if (isset($order_info['invite_address']['pickup_id']) && $order_info['invite_address']['pickup_id']) {
                $order_pickup = [
                    'order_sn' => $order_no,
                    'pickup_id' => $order_info['invite_address']['pickup_id'],
                    'pickup_date' => $order_info['invite_address']['pickup_date'],
                    'pickup_delivery_time_id' => $order_info['invite_address']['pickup_delivery_time_id'],
                    'user_pickup_id' => $order_info['invite_address']['user_pickup_id'],
                    'remark' => $order_info['remark'] ?? '',
                ];
                $res_pickup = Db::name('order_pickup')->insert($order_pickup);
                if (!$res_pickup) {
                    exception(lang('保存订单自提信息失败'));
                }
                //自提类型
                $send_type = 1;
            }

            //订单配送类型send_type
            $orderData['send_type'] = $send_type;
            // 组装订单信息--砍价订单状态为未付款 砍价成功之后修改状态
            $orderData['user_id'] = $user['id'];
            $orderData['order_sn'] = $order_no;
            $orderData['order_money'] = 0;
            $orderData['payable_money'] = 0;
            $orderData['status'] = -2;
            $orderData['real_money'] = 0;
            $orderData['pay_status'] = 0;
            $orderData['pay_type'] = $data['pay_type'] ?? '';
            $orderData['order_type'] = $data['order_type'];
            // 插入订单信息
            $ret = self::create($orderData);
            if (!$ret) {
                exception(lang('创建订单失败'));
            }

            if ($send_type != 1) {
                $res1 = self::saveGoodsInfo($order_info, $order_no, $order_info['remark']);
                if (!$res1) {
                    exception(lang('保存订单附加信息失败'));
                }
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return exception($e->getMessage());
        }
        return [
            'order_sn' => $order_no,
            'bargain' => $baRes
        ];
    }


}
