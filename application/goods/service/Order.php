<?php
/**
 * 订单服务层
 * @author chenchen
 * @time 2021年4月20日15:19:02
 */

namespace app\goods\service;

use app\goods\model\Goods as GoodsModel;
use app\goods\model\GoodsSku;
use app\goods\model\OrderGoods;
use app\goods\model\OrderInvoice;
use app\operation\model\CouponRecord;
use app\user\model\ScoreLog;
use app\user\model\User;
use app\user\service\Money;
use think\Db;
use app\goods\service\Goods as GoodsService;
use app\common\model\Order as OrderModel;

class Order extends Base
{

    /**
     * 计算订单积分抵扣
     * @param int $user_id 会员id （必须）
     * @param float $payable_money （订单实际支付金额）
     * @time 2021年4月21日17:20:26
     * @author chenchen
     */
    public static function integral_reduce($user_id, $payable_money)
    {
        //获取积分抵扣配置值 默认100积分抵扣1元
        $integral_deduction = module_config('integral.integral_deduction') ?: 100;
        //获取用户积分
        $score = User::where(['id' => $user_id])->value('score');
        //没有积分或者积分抵扣不足一分钱，不可以积分抵扣
        if (Money::format_money($score / $integral_deduction, 1) == 0) {
            $res = [
                'score' => $score,
                'reduce_money' => 0,
                'integral_reduce' => 0,
                'integral_payable_money' => $payable_money,
                'is_integral_reduce' => 0 //0不可以抵扣 1可以
            ];
            return $res;
        }
        $reduce = $payable_money - Money::format_money($score / $integral_deduction, 1);
        if ($reduce >= 0) {
            //积分抵扣金额
            $reduce_money = Money::format_money($score / $integral_deduction, 1);
            //扣减积分
            $integral_reduce = $score;
            //积分抵扣之后需要支付的金额
            $integral_payable_money = $payable_money - Money::format_money($score / $integral_deduction, 1);
        } else {
            $reduce_money = $payable_money;
            $integral_reduce = ceil($payable_money * $integral_deduction);
            $integral_payable_money = 0;
        }
        $res = [
            'score' => $score,//会员积分
            'reduce_money' => $reduce_money, //积分抵扣金额
            'integral_reduce' => $integral_reduce,//扣减积分
            'integral_payable_money' => $integral_payable_money,//积分抵扣之后需要支付的金额
            'is_integral_reduce' => 1 //0不可以抵扣 1可以
        ];
        return $res;
    }

    /**
     * 订单保存
     * @param $data .order_type 订单类型 （必须）
     * @param $data .
     * @time 2021-4-22 14:11:54
     * @author chenchen
     */
    public static function save_order($data, $user)
    {
        try {
            $order_type = $data['order_type'];
            //订单信息
            $order_info = json_decode($data['order_info'], true);
            //获取订单的配送类型
            $send_type = $order_info['send_type'] ?? 0;
            //生成订单号
            $order_no = get_order_sn('GD');
            //优惠券id
            $coupon_id = $data['coupon_id'] ?: 0;
            switch ($order_type) {
                case '3'://普通订单
                    break;
                case '12'://积分商品
                    break;
                case '5'://拼团订单
                    break;
                case '6'://秒杀订单
                    break;
                case '7'://预售订单
                    break;
                case '14'://砍价订单
                    break;
                case '16': // 会员购买订单
                    break;
                default:
                    throw new \Exception(lang('暂不支持该类型订单下单'));
                    break;
            }
            //保存订单附加信息
            if (!isset($order_info['address']['address_id'])) {
                throw new \Exception(lang('请添加收货地址'));
            }
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
            $order_goods_insert = Db::name('order_goods_info')->insert($order_goods_info);
            if (!$order_goods_insert) {
                throw new \Exception(lang('保存订单附加信息失败'));
            }

            //保存订单商品表信息
            $payable_money = 0;
            $goodinfo = new GoodsModel();
            $goodsku = new GoodsSku();
            $goods_list = $where = $where1 = [];
            foreach ($order_info['goods'] as $g) {
                $good_info = $goodinfo->get($g['id']);
                $sku_id = $g['sku_id'] ? $g['sku_id'] : 0;
                $calculate_price = GoodsService::calculate_price($user['id'], $g['id'], $sku_id, $g['number']);
                if ($calculate_price['code'] == 0) {
                    exception($calculate_price['msg']);
                }
                $shop_price = $calculate_price['data']['shop_price'];
                $sku_name = '';
                if ($sku_id) {
                    $sku_name = $goodsku->where(['sku_id' => $sku_id, 'goods_id' => $g['id']])->value('key_name') ?? '';
                }
                $goods_money = Money::format_money($shop_price * $g['number'], 1);
                $payable_money += $goods_money;
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
                    'share_sign' => $share_sign
                ];
                //扣库存
                $update_stock = GoodsService::update_stock($g['id'], $g['number'], $sku_id);
                if ($update_stock['code'] == 0) {
                    exception($update_stock['msg']);
                }
                //加销量
                $update_sale = GoodsService::update_sale($g['id'], $g['number'], $sku_id);
                if ($update_sale['code'] == 0) {
                    exception($update_sale['msg']);
                }
            }
            //订单金额
            $order_money = $payable_money;
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
                $payable_money = Money::format_money($payable_money - $coupon['money'], 1);
                $coupon_update = $coupon_record->where(['id' => $data['coupon_id'], 'status' => 1])->update(['status' => 3, 'use_time' => time(), 'order_sn' => $order_no]);
                if (!$coupon_update) {
                    exception(lang('优惠券无效，请重新下单'));
                }
            }
            //普通快递配送订单需要计入运费，自提不计运费，wangph修改2021-4-22
            if ($send_type != 1) {
                //如果有运费，加上
                if ($order_info['express_price']) {
                    $payable_money = Money::format_money($payable_money + $order_info['express_price'], 1);
                }
            }
            //积分抵扣金额
            $reduce_money = $integral_reduce = 0;
            if (isset($data['isSelect_integral_reduce']) && $data['isSelect_integral_reduce'] == 1) {
                $reduce_data = self::integral_reduce($user['id'], $payable_money);
                if ($reduce_data['is_integral_reduce'] == 1) {
                    $payable_money = $reduce_data['integral_payable_money'];
                    $reduce_money = $reduce_data['reduce_money'];
                    $integral_reduce = $reduce_data['integral_reduce'];
                    $score_log = ScoreLog::change($user['id'], -$integral_reduce, 5, lang('购买商品抵扣积分'), $order_no);
                    if (!$score_log) {
                        exception(lang('积分抵扣失败'));
                    }
                }
            }
            // 计算出来的金额和提交过的来金额做对比，一致才往下走
            if (!check_money($data['payable_money'], $payable_money)) {
                exception(lang('金额校验失败'));
            }

            //如果pickup_id不为空，为自提类型的订单，add by wangph at 2021-4-19
            if (isset($order_info['invite_address']['pickup_id']) && $order_info['invite_address']['pickup_id']) {
                $order_pickup = [
                    'order_sn' => $order_no,
                    'pickup_id' => $order_info['invite_address']['pickup_id'],
                    'pickup_date' => $order_info['invite_address']['pickup_date'],
                    'pickup_delivery_time_id' => $order_info['invite_address']['pickup_delivery_time_id'],
                    'user_pickup_id' => $order_info['invite_address']['user_pickup_id']
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
            //积分抵扣0元支付
            if (
                isset($data['isSelect_integral_reduce'])
                && $data['isSelect_integral_reduce'] == 1
                && $payable_money == 0
            ) {
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
                    'invoice_add_time' => time(),
                ];
                $invoice = self::save_invoice($invoice_data);
                if (!$invoice) {
                    exception(lang('订单发票信息保存失败'));
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
            ];

            // 插入订单信息
            $order = OrderModel::create($orderData);
            if (!$order) {
                exception(lang('创建订单失败'));
            }
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            return ['code' => 0, 'msg' => $exception->getMessage()];
        }
    }

    /**
     * 获取提交订单可使用的优惠券列表
     * @param $user_id  int 会员id （必须）
     * @param $goods_id_str string 商品id字符串 形如1，2,3 （选填）
     * @param $order_price float 订单价格 （选填）
     * @time 2021年4月25日14:12:11
     * @author chenchen
     */
    public static function coupon_list($user_id, $goods_id_str = '', $order_price = 0)
    {
    }

    /**
     * 保存发票
     * @param $data  array 订单发票数据 （必须）
     * @time 2021/4/26 16:02
     * @author chenchen
     */
    public static function save_invoice(array $data)
    {
        return (new OrderInvoice())->insert($data);
    }

    /**
     * 取消订单
     * @param $order_sn  string 订单号 （必须）
     * @time 2021-4-30 17:51:38
     * @author chenchen
     */
    public static function cancel_order($order_sn)
    {
        try {
            Db::startTrans();
            $order = OrderModel::where([
                'order_sn' => $order_sn,
                'status' => 0
            ])->find();
            if (!$order) {
                exception("订单不存在");
            }
            //变更订单状态
            $order_update = OrderModel::where([
                'order_sn' => $order_sn,
                'status' => 0
            ])->update([
                'status' => -1
            ]);
            if (!$order_update) {
                exception("订单状态更新失败");
            }
            if ($order["order_type"] != 1) {
                $order_goods = OrderGoods::where([
                    'order_status' => 0,
                    'order_sn' => $order_sn
                ])->select();
                if (count($order_goods) <= 0) {
                    exception("订单商品信息不存在");
                }
                foreach ($order_goods as $v) {
                    //加库存
                    $stock_update = GoodsService::update_stock($v['goods_id'], $v['num'], $v['sku_id']);
                    if ($stock_update['code'] == 0) {
                        exception($stock_update['msg']);
                    }
                    //减销量
                    $update_sale = GoodsService::update_sale($v['goods_id'], -$v['num'], $v['sku_id']);
                    if ($update_sale['code'] == 0) {
                        exception($update_sale['msg']);
                    }
                }
                //变更商品订单表状态
                $order_goods_update = OrderGoods::where([
                    'order_sn' => $order_sn,
                    'order_status' => 0
                ])->update([
                    'order_status' => -1
                ]);
                if (!$order_goods_update) {
                    exception("订单商品信息更新失败");
                }
                //返优惠券
                if ($order['coupon_id']) {
                    $coupon = CouponRecord::where([
                        ['id', '=', $order['coupon_id']],
                        ['start_time', '<=', time()],
                        ['end_time', '>=', time()],
                        ['status', '=', 3]
                    ])->update([
                        'status' => 1
                    ]);
                    if (!$coupon) {
                        exception("退回优惠券失败");
                    }
                }

            }

            Db::commit();
            return format_data(1, [], "订单取消成功");
        } catch (\Exception $exception) {
            Db::rollback();
            return format_data(0, [], $exception->getMessage());
        }
    }

    /**
     * 拼团
     * @param $activity_id  int 活动id （必须）
     * @param $goods_id int 商品id （必须）
     * @param $user_id int 用户id （必须）
     * @param $order_sn string 订单号 （必须）
     * @param $join_number int 几人团（必须）
     * @param $group_id int 参团id （非必须）
     * @time 2021/5/28 18:16
     * @author chenchen
     */
    public static function make_group($activity_id, $goods_id, $user_id, $order_sn, $join_number, $group_id = 0)
    {
        try {
            Db::startTrans();
            $user_info = User::where(["id" => $user_id])->find();
            $user_name = $user_info['user_nickname'] ?? '';
            $user_head = get_file_url($user_info['head_img']);
            if (!$group_id) {
                //发起拼团
                $group['goods_id'] = $goods_id;
                $group['num'] = 0;
                $group['activity_id'] = $activity_id;
                $group['create_time'] = time();
                $gid = Db::name('goods_activity_group')->insertGetId($group);
                $gid = $gid ?: 0;
                if ($gid) {
                    Db::name('goods_activity_group_user')->insertGetId([
                        'group_id' => $gid,
                        'uid' => $user_id,
                        'user_name' => $user_name,
                        'user_head' => $user_head,
                        'order_sn' => $order_sn,
                    ]);
                }
            } else {
                //参团
                $goods_activity_group = Db::name('goods_activity_group')->where(['id' => $group_id, 'status' => 1])->find();
                // 如果拼团单已生效
                if (!empty($goods_activity_group) && $goods_activity_group['status'] == 1) {
                    $activity_user = Db::name('goods_activity_group_user')->where(['group_id' => $group_id, 'status' => 1])->find();
                    if ($activity_user['uid'] == $user_id) {
                        exception(lang('用户自己不能成团，请换团'));
                    } else {
                        $check = Db::name('goods_activity_group')->where([
                            ['id', '=', $group_id],
                            ['num', 'lt', $join_number],
                            ['status', '=', 1]
                        ])->find();
                        if ($check) {
                            Db::name('goods_activity_group_user')->insertGetId([
                                'group_id' => $group_id,
                                'uid' => $user_id,
                                'user_name' => $user_name,
                                'user_head' => $user_head,
                                'order_sn' => $order_sn,
                            ]);
                        } else {
                            exception(lang('团已满，请换团'));
                        }
                    }
                } else {  // 拼团单未生效
                    exception(lang('当前团已失效或取消，请换团'));
                }

            }
            Db::commit();
            return format_data(1, [], '拼团成功');
        } catch (\Exception $exception) {
            Db::rollback();
            return format_data(0, [], $exception->getMessage());
        }
    }


}