<?php
/**
 * Created by PhpStorm.
 * User: 7howe
 * DateTime: 2019/11/26 11:37
 */

namespace app\goods\model;


use think\Model;
use think\Db;
use app\common\model\Order as OrderModel;

class OrderRefund extends Model
{

    public static $refundCause = [
        '商品降价',
        '缺少件',
        '质量问题',
        '不想要了',
        '商品与描述不符',
    ];

    public static $refundGoodsState = [
        '未收到货',
        '已收到货',
    ];
    //售后申请状态
    public static $status = [
        0 => '申请中',
        -2 => '用户取消',
        -1 => '驳回',
        1 => '同意',
        2 => '确认收回',
        3 => '确认打款'
    ];

    public function get_list($map, $order)
    {
        $data = OrderRefund::alias('rf')
            ->join('order o', 'rf.order_sn=o.order_sn')
            ->field('rf.id,rf.server_no,rf.refund_money,rf.order_sn,rf.num,rf.goods_id,rf.sku_id,rf.user_id,rf.create_time,rf.status,rf.refund_reason,rf.refund_type,o.order_type,o.pay_type,o.pay_status,o.order_money,rf.goods_money,o.real_money,o.coupon_money,o.status as order_status,rf.refuse_reason')
            ->where($map)
            ->order($order)
            ->paginate();
        foreach ($data as $k => $v) {
            if (in_array($v['status'], [3, 4])) {
                $v['refund_type'] = 2;
            }
//            $data[$k]['refund_type_title'] = self::$refundGoodsState[$v['refund_type']];
            $refund_type_title = lang('客户未收到货');
            if ($v['order_status'] >= 2) {
                $refund_type_title = lang('客户已收到货');
                if ($v['status'] > 1) {
                    $refund_type_title = lang('平台已收到货');
                } else {
                    if ($v['order_status'] == 2) {
                        $refund_type_title = lang('已发货');
                    }
                }
            }
            $data[$k]['refund_type_title'] = $refund_type_title;

            $data[$k]['goods_name'] = Db::name('goods')->where(['id' => $v['goods_id']])->value('name');
            $data[$k]['sku_name'] = Db::name('goods_sku')->where(['sku_id' => $v['sku_id']])->value('key_name');
            $data[$k]['coupon_money'] = bcmul($v['coupon_money'], bcdiv($v['goods_money'], $v['order_money'], 2), 2);
//            $data[$k]['refund_money'] = bcsub($v['goods_money'], $data[$k]['coupon_money'], 2);
            $data[$k]['order_type_title'] = OrderModel::$orderTypes[$v['order_type']];

            switch ($v['refund_type']) {
                case 1:
                    $data[$k]['refund_type_name'] = lang('退款');
                    break;
                case 2:
                    $data[$k]['refund_type_name'] = lang('退货');
                    break;
                case 3:
                    $data[$k]['refund_type_name'] = lang('换货');
                    break;
                default:
                    $data[$k]['refund_type_name'] = lang('退款');
                    break;
            }
        }
        return $data;
    }
}
