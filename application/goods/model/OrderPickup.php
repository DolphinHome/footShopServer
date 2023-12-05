<?php
/*
 * @Descripttion: 订单自提信息
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-04-12 18:04:09
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-22 11:44:40
 */
namespace app\goods\model;

use think\Model as ThinkModel;
use app\common\model\Area;
use app\goods\model\PickupDeliver;
use app\goods\model\OrderDeliveryTime;
use app\user\model\Pickup as UserPickupModel;

class OrderPickup extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ORDER_PICKUP__';
    protected $autoWriteTimestamp = true;

    /**
     * 根据订单号获取自提相关信息
     * @param string $order_sn
     * @return array
     * @Author: wangph
     * @Date: 2021-04-15 18:03:41
     */
    public static function getOrderPickUp($order_sn)
    {
        $where[] = ['order_sn', '=', $order_sn];
        //订单的自提表内容
        $order_pickup = self::where($where)->find();
        if (count($order_pickup) == 0) {
            return [];
        }
        //获取自提点信息
        $deliver = PickupDeliver::getPickUpById($order_pickup['pickup_id']);
        //自取时间对应内容
        $pickup_delivery_time = OrderDeliveryTime::getTimeById($order_pickup['pickup_delivery_time_id']);
        //获取提货人信息
        $pickup_user = UserPickupModel::getInfoById(0, $order_pickup['user_pickup_id']);
        $res = [
            'deliver_name' => $deliver['deliver_name'],
            'deliver_mobile' => $deliver['deliver_mobile'],
            'full_address' => $deliver['full_address'],
            'pickup_date' => $order_pickup['pickup_date'],
            'pickup_delivery_time' => $pickup_delivery_time['name'],
            'pickup_user_name' => $pickup_user['name']??'',
            'pickup_user_mobile' => $pickup_user['mobile']??'',
            'remark'=> $order_pickup['remark'],
        ];
        return $res;
    }

}