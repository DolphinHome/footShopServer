<?php
/*
 * @Descripttion: 
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-23 10:46:48
 */
// +----------------------------------------------------------------------
// | LwwanPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.lwwan.com
// +----------------------------------------------------------------------
// | Author 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 QQ群331378225
// +----------------------------------------------------------------------
namespace app\goods\model;

use app\common\model\Order;
use app\goods\model\OrderGoods;
use app\goods\model\OrderRefund;
use think\Model as ThinkModel;

/**
 * 订单附加信息表
 * @package app\goods\model
 */
class OrderInfo extends ThinkModel
{

    // 设置当前模型对应的完整数据表名称
    protected $table = '__ORDER_GOODS_INFO__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = false;

    /**
     * 获取一个订单详情
     * @param null $order_sn
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/28 11:32
     */
    public static function get_order_detail($order_sn = null)
    {
        $info = Order::alias('o')->leftJoin('order_goods_info oi', 'o.order_sn = oi.order_sn')->where('o.order_sn', $order_sn)->find();
        //自提类型运费为0
        if ($info['send_type'] == 1) {
            $info['express_price'] = 0;
        }

        $info['order_sn'] = $order_sn;
        /*        $goods_id = OrderRefund::where(["order_sn"=>$order_sn])->column("goods_id");
                $goods_ids = implode(',',$goods_id);
                $where[] = ['goods_id','in',$goods_ids];*/
        $where[] = ['order_sn', '=', $order_sn];
        $order_goods = OrderGoods::where($where)->select();
        if ($order_goods) {
            foreach ($order_goods as $k => $v) {
                if ($v['is_pure_integral'] == 1) {
                    //纯积分兑换
                    $order_goods[$k]['goods_money'] = $v['sales_integral'] . "积分";

                } else {
                    if ($v['sales_integral'] > 0) {

                        //积分+余额兑换
                        $order_goods[$k]['goods_money'] = "¥" . $info['payable_money'] . "+" . $info['cost_integral'] . "积分";
                    }
                }
                $order_goods[$k]['goods_thumb'] = get_file_url($v['goods_thumb']);
                if ($v['sku_id']) {
                    $order_goods[$k]['goods_sn'] = GoodsSku::where("sku_id", $v['sku_id'])->value("sku_sn");
                } else {
                    $order_goods[$k]['goods_sn'] = Goods::where("id", $v['goods_id'])->value("sn");
                }
            }
        }
        $info['order_goods'] = $order_goods;
        return $info;
    }
}