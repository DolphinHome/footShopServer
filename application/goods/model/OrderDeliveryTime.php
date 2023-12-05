<?php
/*
 * @Descripttion:订单的配送可选时间段
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-04-12 18:04:09
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-28 18:43:06
 */
namespace app\goods\model;

use think\Model as ThinkModel;
use app\common\model\Area;

class OrderDeliveryTime extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ORDER_DELIVERY_TIME__';
    protected $autoWriteTimestamp = true;


    /**
     * 获取几天内的时间段
     * @param int $days 几天的时间段
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-15 11:17:23
     */
    public static function getTimeList($days = 3)
    {
        $res = $today_data = [];
        //如果是今天当前时间，只获取当前时间两小时后的时间段。之前的不显示
        $now_hour = date('G', time()+7200);
        $data= self::field('id,name,start_hour,end_hour')->select();
        foreach ($data  as $kt=>$vt) {
            if ($now_hour < $vt['end_hour']) {
                $today_data[] = $vt;
            }
        }
        
        $i = 0;
        while ($i < $days) {
            if ($i == 0) {
                $res[$i]['list'] = $today_data;
                $res[$i]['date_name'] = lang('今天');
            } elseif ($i == 1) {
                $res[$i]['list'] = $data;
                $res[$i]['date_name'] = lang('明天');
            } else {
                $res[$i]['list'] = $data;
                $res[$i]['date_name'] = date('n-d', time()+86400*$i);
            }
            $res[$i]['real_date'] = date('Y-n-d', time()+86400*$i);

            $i++;
        }
        //如果当前时间定位的今天时段超过22，不显示今天数据
        if (empty($today_data)) {
            array_splice($res,0,1);
        }
        
        return $res;
    }


    /**
     * 根据id获取时间段内容
     * @param {*} $id
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-15 17:52:24
     */
    public static function getTimeById($id)
    {
        $where[] = ['id', '=', $id];
        $item = self::where($where)->find();
        return $item;
    }
}
