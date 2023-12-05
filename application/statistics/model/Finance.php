<?php
/**
 * Created by PhpStorm.
 * User: I5
 * Date: 2020/11/23
 * Time: 16:30
 */

namespace app\statistics\model;


use app\common\model\Order;
use app\goods\model\Goods as GoodsModel;
use app\goods\model\OrderRefund;
use app\operation\model\UserAddress;
use app\user\model\User;
use think\Db;
use function GuzzleHttp\Psr7\_caseless_remove;

class Finance
{
    /*
     * 交易数据
     *
     */
    public static function payData($where, $order = 'o.aid desc', $is_page = true)

    {

        $list = Order::where($where)
            ->alias("o")
            ->field("
            o.order_sn as orderSn,
            o.order_money as orderAmount, 
            o.payable_money as realAmount,
            (o.order_money-o.payable_money) as discountsAmount,
            o.pay_status as payStatus,
            o.pay_type as payType,
            o.status as orderStatus,
            o.create_time as createTime,
            o.cost_integral,
            p.transaction_no as transactionNo,
            og.receiver_name as consigneeName,
            og.receiver_mobile as consigneePhone,
            og.receiver_address,
            og.province,
            og.city,
            og.district
            ")
            ->join("payment_log p", 'o.order_sn=p.order_sn', 'left')
            ->join("order_goods_info og", 'o.order_sn=og.order_sn', 'left')
            ->order($order);
        if ($is_page) {
            $list = $list->paginate()->each(function ($v) {
                if ((int)$v['realAmount']) {
                    //金额+积分
                    if ($v['cost_integral']) {
                        $v['realAmount'] = '￥' . $v['realAmount'] . '+' . $v['cost_integral'] . lang('积分');
                    } else {
                        //纯金额
                        $v['realAmount'] = '￥' . $v['realAmount'];
                    }
                } else {
                    //纯积分
                    $v['realAmount'] = $v['cost_integral'] . lang('积分');
                }
                $v['payStatus'] = Order::$order_status[$v['payStatus']];
                $v['payType'] = Order::$payTypes[$v['payType']];
                $v['orderStatus'] = Order::$order_status[$v['orderStatus']];
                $v['consigneeAdress'] = $v['province'] . '-' . $v['city'] . '-' . $v['district'] . '-' . $v['receiver_address'];
                $v['createTime'] = date('Y-m-d H:i:s', $v['createTime']);
                unset($v['province'], $v['city'], $v['district'], $v['receiver_address']);
            });

        } else {
            $list = $list->select();
            foreach ($list as &$v) {
                if ($v['realAmount']) {
                    //金额+积分
                    if ($v['cost_integral']) {
                        $v['realAmount'] = '￥' . $v['realAmount'] . '+' . $v['cost_integral'] . lang('积分');
                    } else {
                        //纯金额
                        $v['realAmount'] = '￥' . $v['realAmount'];
                    }
                } else {
                    //纯积分
                    $v['realAmount'] = $v['cost_integral'] . lang('积分');
                }
                $v['payStatus'] = Order::$order_status[$v['payStatus']];
                $v['payType'] = Order::$payTypes[$v['payType']];
                $v['orderStatus'] = Order::$order_status[$v['orderStatus']];
                $v['consigneeAdress'] = $v['province'] . '-' . $v['city'] . '-' . $v['district'] . '-' . $v['receiver_address'];
                $v['createTime'] = date('Y-m-d H:i:s', $v['createTime']);
                unset($v['province'], $v['city'], $v['district'], $v['receiver_address']);
            }
        }

        return $list;
    }

    /*
     * 订单详情
     *
     */
    public static function ordersDetail($orderSn)
    {
        $order = Order::where(['o.order_sn' => $orderSn])
            ->alias("o")
            ->field("
            o.order_sn as orderSn,
            o.order_money as orderAmount, 
            o.payable_money as realAmount,
            (o.order_money-o.payable_money) as discountsAmount,
            o.pay_status as payStatus,
            o.pay_type as payType,
            o.status as orderStatus,
            o.create_time as createTime,
            o.cost_integral,
            p.transaction_no as transactionNo,
            og.receiver_name as consigneeName,
            og.receiver_mobile as consigneePhone,
            og.receiver_address,
            og.province,
            og.city,
            og.district,
            u.user_name as userName,
            u.mobile as userPhone
            ")
            ->join("payment_log p", 'o.order_sn=p.order_sn', 'left')
            ->join("order_goods_info og", 'o.order_sn=og.order_sn', 'left')
            ->join("user u", 'o.user_id=u.id', 'left')
            ->find();

        if ($order) {
            if ((int)$order['realAmount']) {
                //金额+积分
                if ($order['cost_integral']) {
                    $order['realAmount'] = '￥' . $order['realAmount'] . '+' . $order['cost_integral'] . lang('积分');
                } else {
                    //纯金额
                    $order['realAmount'] = '￥' . $order['realAmount'];
                }
            } else {
                //纯积分
                $order['realAmount'] = $order['cost_integral'] . lang('积分');
            }
            $order['payStatus'] = Order::$order_status[$order['payStatus']];
            $order['payType'] = Order::$payTypes[$order['payType']];
            $order['orderStatus'] = Order::$order_status[$order['orderStatus']];
            $order['consigneeAdress'] = $order['province'] . '-' . $order['city'] . '-' . $order['district'] . '-' . $order['receiver_address'];
            $order['createTime'] = date('Y-m-d H:i:s', $order['createTime']);
            unset($order['province'], $order['city'], $order['district'], $order['receiver_address']);
            //查询商品信息
            $goodsList = Db::name("order_goods_list")
                ->where([
                    'order_sn' => $orderSn
                ])->field("
                    goods_thumb as goodsImgUrl,
                    goods_name as goodsName,
                    sku_name as goodsSpecification,
                    shop_price as goodsPrice,
                    num as goodsNum,
                    goods_money as goodsAmount,
                    goods_money as goodsRealAmount,
                    ((num*shop_price)-goods_money) as goodsdiscountsAmount,
                    is_pure_integral,
                    sales_integral
                    ")->select();
            if ($goodsList) {
                foreach ($goodsList as &$v) {
                    $v['goodsImgUrl'] = get_file_url($v['goodsImgUrl']);
                    if ($v['is_pure_integral'] == 1) {
                        //纯积分
                        $v['goodsRealAmount'] = $v['sales_integral'] . lang('积分');
                    } else {
                        if ($v['sales_integral']) {
                            //金额+积分
                            $v['goodsRealAmount'] = '￥' . $v['goodsRealAmount'] . '+' . $v['sales_integral'] . lang('积分');
                        } else {
                            //纯金额
                            $v['goodsRealAmount'] = '￥' . $v['goodsRealAmount'];
                        }
                    }
                }
            }
            $order['goodsList'] = $goodsList;

        }

        return $order;

    }

    /*
     * 获取退款
     *
     */
    public static function getRefundRecord($where, $order = 'or.id desc', $is_page = true)

    {

        $list = OrderRefund::where($where)
            ->alias("or")
            ->field("
            or.refund_money as refundAmount,
            or.refund_reason as refundCause,
            or.status as refundStatus,
            or.create_time as applyRefundTime,
            o.order_sn as orderSn,
            o.order_money as orderAmount, 
            o.payable_money as realAmount,
            (o.order_money-o.payable_money) as discountsAmount,
            o.pay_status as payStatus,
            o.pay_type as payType,
            o.status as orderStatus,
            o.create_time as createTime,
            p.transaction_no as transactionNo,
            og.receiver_name as consigneeName,
            og.receiver_mobile as consigneePhone,
            og.receiver_address,
            og.province,
            og.city,
            og.district
            ")
            ->join("order o", "o.order_sn=or.order_sn", "left")
            ->join("payment_log p", 'o.order_sn=p.order_sn', 'left')
            ->join("order_goods_info og", 'o.order_sn=og.order_sn', 'left')
            ->order($order);
        if ($is_page) {
            $list = $list->paginate()->each(function ($v) {
                $v['payStatus'] = Order::$order_status[$v['payStatus']];
                $v['payType'] = Order::$payTypes[$v['payType']];
                $v['orderStatus'] = Order::$order_status[$v['orderStatus']];
                $v['refundStatus'] = OrderRefund::$status[$v['refundStatus']];
                $v['consigneeAdress'] = $v['province'] . '-' . $v['city'] . '-' . $v['district'] . '-' . $v['receiver_address'];
                $v['applyRefundTime'] = date('Y-m-d H:i:s', $v['applyRefundTime']);
                unset($v['province'], $v['city'], $v['district'], $v['receiver_address']);
            });

        } else {
            $list = $list->select();
            foreach ($list as &$v) {
                $v['payStatus'] = Order::$order_status[$v['payStatus']];
                $v['payType'] = Order::$payTypes[$v['payType']];
                $v['orderStatus'] = Order::$order_status[$v['orderStatus']];
                $v['refundStatus'] = OrderRefund::$status[$v['refundStatus']];
                $v['consigneeAdress'] = $v['province'] . '-' . $v['city'] . '-' . $v['district'] . '-' . $v['receiver_address'];
                $v['applyRefundTime'] = date('Y-m-d H:i:s', $v['applyRefundTime']);
                unset($v['province'], $v['city'], $v['district'], $v['receiver_address']);
            }
        }

        return $list;
    }

    /*
     * 退款详情
     *
     */
    public static function getRefundRecordDetail($orderSn)
    {
        $order = OrderRefund::where(['or.order_sn' => $orderSn])
            ->alias("or")
            ->field("
            or.refund_money as refundAmount,
            or.refund_reason as refundCause,
            or.status as refundStatus,
            or.create_time as applyRefundTime,
            or.refund_picture,
            o.order_sn as orderSn,
            o.order_money as orderAmount, 
            o.payable_money as realAmount,
            (o.order_money-o.payable_money) as discountsAmount,
            o.pay_status as payStatus,
            o.pay_type as payType,
            o.status as orderStatus,
            o.create_time as createTime,
            p.transaction_no as transactionNo,
            og.receiver_name as consigneeName,
            og.receiver_mobile as consigneePhone,
            og.receiver_address,
            og.province,
            og.city,
            og.district,
            u.user_name as userName,
            u.mobile as userPhone
            ")
            ->join("order o", 'or.order_sn=o.order_sn', 'left')
            ->join("payment_log p", 'or.order_sn=p.order_sn', 'left')
            ->join("order_goods_info og", 'or.order_sn=og.order_sn', 'left')
            ->join("user u", 'or.user_id=u.id', 'left')
            ->find();

        if ($order) {
            $order['payStatus'] = Order::$order_status[$order['payStatus']];
            $order['payType'] = Order::$payTypes[$order['payType']];
            $order['orderStatus'] = Order::$order_status[$order['orderStatus']];
            $order['refundStatus'] = OrderRefund::$status[$order['refundStatus']];
            $order['consigneeAdress'] = $order['province'] . '-' . $order['city'] . '-' . $order['district'] . '-' . $order['receiver_address'];
            $order['applyRefundTime'] = date('Y-m-d H:i:s', $order['applyRefundTime']);
            unset($order['province'], $order['city'], $order['district'], $order['receiver_address']);
            //查询商品信息
            $goodsList = Db::name("order_goods_list")
                ->where([
                    'order_sn' => $orderSn
                ])->field("
                    goods_thumb as goodsImgUrl,
                    goods_name as goodsName,
                    sku_name as goodsSpecification,
                    shop_price as goodsPrice,
                    num as goodsNum,
                    goods_money as goodsAmount,
                    goods_money as goodsRealAmount,
                    ((num*shop_price)-goods_money) as goodsdiscountsAmount
                    ")->select();
            if ($goodsList) {
                foreach ($goodsList as &$v) {
                    $v['goodsImgUrl'] = get_file_url($v['goodsImgUrl']);
                }
            }
            $order['goodsList'] = $goodsList;
            $order['refundImgList'] = get_files_url($order['refund_picture']);

        }

        return $order;

    }

    /*
     *获取对账
     *
     */
    public static function getCheckCenterData($where, $order = 'log.create_time desc', $is_page = true)
    {
        $list = Db::name("payment_log")
            ->alias("log")
            ->leftJoin("order o", "log.order_sn=o.order_sn ")
            ->field("
            o.user_id,
            o.order_sn as orderSn,
            o.order_money as orderAmount, 
            o.payable_money,
            o.pay_status as payStatus,
            o.pay_type as payType,
            log.transaction_no as transactionNo,
            (o.coupon_money+o.reduce_money) as discount_money,
            o.real_balance,
            o.real_money,
            log.amount,
            log.create_time as createTime,
            log.check_status
            ")
            ->where($where)
            ->order($order);
            //->group('log.order_sn');
        if ($is_page) {
            $list = $list->paginate();
        } else {
            $list = $list->select();
        }
        if (count($list) > 0) {
            $list = $list->each(function ($v) {
                $v['payStatus'] = Order::$order_status[$v['payStatus']];
                $pay_type = $v['payType'];
                $v['payType'] = Order::$payTypes[$v['payType']];
                $v['createTime'] = date('Y-m-d H:i:s', $v['createTime']);
                $v['user_nickname'] = User::where(['id' => $v['user_id']])->value("user_nickname");
                if ($v['check_status'] == 0){
                    $v['isFinish'] = ($v['payable_money'] == Db::name("payment_log")->where(["order_sn" => $v["orderSn"]])->sum("amount")) ? '√' : '×';
                } else {
                    if($v['check_status'] == 1) {
                        $v['isFinish'] = '√';
                    } elseif ($v['check_status'] == 2){
                        $v['isFinish'] = '×';
                    }elseif ($v['check_status'] == 3){
                        $v['isFinish'] = '已修正';
                    }
                }
               
                if ($v['real_balance'] > 0 && $pay_type!='balance') {
                    // $other_amount = Db::name("payment_log")->where(["order_sn" => $v["orderSn"],'pay_type'=> $pay_type])->field('amount')->find();
                    // $detail = $v['payType'] . '：￥ ' . $other_amount['amount'] . '+' . '余额支付：￥' . $v['real_balance'];
                    // $v['realAmount'] = $v['payable_money'] . '（' . $detail . '）';
                    $v['realAmount'] = $v['amount'];
                    //混合支付的余额支付判断
                    if(empty($v['transactionNo'])) {
                        $v['payType'] = '余额支付';
                    }
                } else {
                    $v['realAmount'] = $v['amount'];
                }
                return $v;
            });
        } else {
            $list = [];
        }


        return $list;
    }
}