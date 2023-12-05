<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\model;

use think\Model as ThinkModel;
use think\Collection;

/**
 * 优惠券领取模型
 * @package app\operation\model
 */
class CouponRecord extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__OPERATION_COUPON_RECORD__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     *
     * @return \think\model\relation\HasOne
     */
    public function operation_coupon()
    {
        return $this->hasOne('OperationCoupon', 'c_id');
    }

    /**
     * 优惠券列表
     * @param $userId
     * @param $type
     * @author  风轻云淡
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function get_coupon_list($userId, $type, $orderPrice)
    {
        $where[] = ['user_id', 'eq', $userId];
        $nowTime = time();
        $sort = "cr.end_time desc";
        switch ($type) {
            case 1: //全部
                $where[] = ['cr.status', 'neq', 1];
                break;
            case 2: //待使用
                $where[] = ['cr.status', 'eq', 1];
                $where[] = ['cr.start_time', 'lt', $nowTime];
                $where[] = ['cr.end_time', 'gt', $nowTime];
                break;
            case 3: //已使用
                $where[] = ['cr.status', 'eq', 3];
                $where[] = ['cr.use_time', '<>', 0];
                break;
            case 4: //已过期
                $where[] = ['cr.status', 'eq', 4];
                $where[] = ['cr.end_time', 'lt', $nowTime];
                break;
        }
        if ($orderPrice > 0) {
            $sort = "oc.money desc";
            $where[] = ['oc.min_order_money', 'elt', $orderPrice];
        }
        $couponList = CouponRecord::alias("cr")->join("__OPERATION_COUPON__ oc", "oc.id=cr.cid")
            ->field("cr.id,cr.end_time,cr.status,oc.money,oc.min_order_money,oc.name as coupon_name,oc.cid,oc.goods_id")
            ->where($where)
            ->order($sort)
            ->select()->toArray();
        $couponNouse = CouponRecord::alias("cr")->join("__OPERATION_COUPON__ oc", "oc.id=cr.cid")
            ->field("cr.id,cr.end_time,cr.status,oc.money,oc.min_order_money,oc.name as coupon_name,oc.cid,oc.goods_id")
            ->where([
                'user_id' => $userId,
                'cr.status' => 1
            ])
            ->order($sort)
            ->select()->toArray();
        if ($type == 1) {
            $couponList = array_merge($couponNouse, $couponList);
        }


        return $couponList;
    }

    /**
     * 优惠券详情
     * @param $where
     * @author  风轻云淡
     * @return array|false|null|\PDOStatement|string|ThinkModel
     */
    public function get_user_coupon($where)
    {
        $coupon_detail = CouponRecord::alias("cr")->join("__OPERATION_COUPON__ oc", "oc.id=cr.cid")
            ->where($where)
            ->field("cr.id,cr.end_time,cr.status,cr.end_time,oc.money,oc.min_order_money,oc.name as coupon_name,oc.content")->find();
        if ($coupon_detail) {
            $coupon_detail['content'] = $coupon_detail['content'] ? $coupon_detail['content'] : "";
            $coupon_detail['end_time'] = date("Y-m-d H:i", $coupon_detail['end_time']);
        }
        return $coupon_detail;
    }

    /**
     * 修改优惠券是否过期
     * @param $userId
     * @author  风轻云淡
     * @return int|string
     */
    public function edit_coupon($userId)
    {
        $where[] = ['user_id', 'eq', $userId];
        $where[] = ['status', 'eq', 1];
        $where[] = ['end_time', 'lt', time()];
        $res = CouponRecord::where($where)->update(['status' => 4]);
        return $res;
    }

    /**
     * 获得可用优惠券
     * @param $userId 用户id
     * @param $orderPrice 订单金额
     * @param $cid 商品分类id
     * @param $goods_id 商品id
     * @author 风轻云淡
     * @return array|false|null|\PDOStatement|string|ThinkModel
     */
    public static function get_best_coupon($userId, $orderPrice, $cid = 0, $goods_id = 0)
    {
        $nowTime = time();
        $where[] = ['cr.user_id', 'eq', $userId];
        $where[] = ['cr.status', 'eq', 1];
        $where[] = ['min_order_money', 'elt', $orderPrice];
        $where[] = ['cr.start_time', 'elt', $nowTime];
        $where[] = ['cr.end_time', 'egt', $nowTime];
        //绑定商品分类判断
        if ($cid) {
            $cid_arr = [0];
            if (is_array($cid)) {
                $cid_arr = array_merge($cid_arr, $cid);
            } else {
                array_push($cid_arr, $cid); 
            }
            $where[] = ['oc.cid', 'IN', $cid_arr];
        }
       
        //绑定商品ID判断
        if ($goods_id) {
            $goods_id_arr = [0]; 
            if (is_array($goods_id)) {
                $goods_id_arr = array_merge($goods_id_arr, $goods_id);
            } else {
                array_push($goods_id_arr, $goods_id); 
            }
            $where[] = ['oc.goods_id', 'IN', $goods_id_arr];
        }
        
        $couponInfo = CouponRecord::alias("cr")->join("__OPERATION_COUPON__ oc", "oc.id=cr.cid")
            ->where($where)
            ->field("cr.id,oc.name,oc.money,oc.min_order_money,cr.end_time")
            ->order("money desc")
            ->select();
        return $couponInfo ? $couponInfo : [];
    }


    /**
     * 获得可用优惠券
     * @author zenghu 2020年8月31日16:22:48
     * @return array
     */
    public static function get_best_coupon_new($userId, $goods_cid, $jiage)
    {
        // 处理参数
        $price = 0;
        $cid = [];
        $cid[] = 0; // 添加全部类型优惠券
        if ($jiage) {
            foreach ($jiage as $k => $v) {
                $price += $v['price'];
                $cid[] = $v['cid'];
            }
            $cid = array_unique($cid);
        }
        $cids = implode($cid, ',');

        // 拼接查询的条件
        $where['cr.user_id'] = $userId;
        $where['cr.status'] = 1;
        $where['oc.type'] = 0;
        $nowTime = time();
        $where1[] = ['cr.start_time', 'elt', $nowTime];
        $where1[] = ['cr.end_time', 'egt', $nowTime];
        $where1[] = ['oc.cid', 'IN', $cids];
        $where1[] = ['min_order_money', 'elt', $price];
        $couponInfo = CouponRecord::alias("cr")
            ->join("__OPERATION_COUPON__ oc", "oc.id=cr.cid")
            ->where($where)
            ->where($where1)
            ->field("cr.id,oc.name,oc.money,oc.min_order_money,cr.end_time")
            ->order("money desc")
            ->select()
            ->toArray();

        return $couponInfo ? $couponInfo : [];
    }

    /**
     * 获得免运券
     * @param $userId 用户id
     * @author jxy
     * @return array|false|null|\PDOStatement|string|ThinkModel
     */
    public static function get_shipping_coupon($userId)
    {
        $nowTime = time();
        $where[] = ['cr.user_id', 'eq', $userId];
        $where[] = ['cr.status', 'eq', 1];
        $where[] = ['cr.start_time', 'elt', $nowTime];
        $where[] = ['cr.end_time', 'egt', $nowTime];
        $where[] = ['oc.type', 'eq', 1];
        $couponInfo = CouponRecord::alias("cr")->join("__OPERATION_COUPON__ oc", "oc.id=cr.cid")
            ->where($where)
            ->field("cr.id,oc.name,oc.money,oc.min_order_money,cr.end_time")
            ->order("money desc")
            ->select();
        return $couponInfo ? $couponInfo : [];
    }
}
