<?php
/**
 * 优惠券服务层
 * @author chenchen
 * @time 2021年4月23日17:57:27
 */
namespace app\user\service;

use app\goods\model\Category;
use app\goods\model\Goods;
use app\operation\model\Coupon as CouponModel;
use app\operation\model\CouponRecord;


class Coupon extends Base
{


    /**
     * 获取优惠券优惠金额
     * @author chenchen
     * @param $goods_id 商品id （必须）
     * @param $method 领取方式 -1 全部 0手动发放1首页弹窗2被动领取（必须）
     * @param $user_id  会员id （选填）
     * @time 2021年4月25日14:12:11
     */
    public static function coupon_money($coupon_id, $user_id)
    {
        $coupon_detail = (new CouponRecord())->alias("cr")->join("__OPERATION_COUPON__ oc", "oc.id=cr.cid", "left")
            ->where([
                ['user_id', '=', $user_id],
                ['id', '=', $coupon_id],
                ['status', '=', 1],
                ['start_time', '<=', time()],
                ['end_time', '>=', time()]
            ])
            ->field("cr.id,cr.end_time,cr.status,cr.end_time,oc.money,oc.min_order_money,oc.name as coupon_name,oc.content")->find();
        if ($coupon_detail) {
            $coupon_detail['content'] = $coupon_detail['content'] ? $coupon_detail['content'] : "";
            $coupon_detail['end_time'] = date("Y-m-d H:i", $coupon_detail['end_time']);
        }
        return $coupon_detail;
    }

}