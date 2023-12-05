<?php
/*
 * @Descripttion: 用户自提点
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-04-12 18:04:09
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-28 14:57:00
 */
namespace app\goods\model;

use think\Model as ThinkModel;
use think\facade\Config;
use app\common\model\Area;


class PickupDeliver extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__PICKUP_DELIVER__';
    protected $autoWriteTimestamp = true;

    /**
     * 获取附近的自提点
     * @param int $district_id 区id
     * @param int $deliver_name 自提点名称-搜索
     * @param {*} $lng
     * @param {*} $lat
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-15 17:52:42
     */
    public static function getNearPickUp($district_id, $deliver_name = '', $lng=0, $lat=0)
    {
        $where = $data_list = $sort = $data_list_new = [];
        if ($deliver_name) {
            $where[] = ['deliver_name', 'like', '%' . $deliver_name . '%'];
        }
        if ($district_id) {
            $where[] = ['district_id', '=', $district_id];
        }
        $where[] = ['status', '=', 1];
        $data_list = self::where($where)->select()
        ->each(function ($item) {
            $item['full_address'] = $item['city_name'] . $item['district_name'] . $item['full_address'];
            $item['thumb'] = get_thumb($item['thumb']);
            return $item;
        })->toArray();
        
        if ($lng && $lat) {
            //redis方法获取附近点,默认是当前地址为圆心，30公里范围内的点
            $sortNearList = self::useRedisGeoDistance($lng, $lat, 30, 'ASC', $data_list);
            //如果redis方案不支持，没数据，采用php计算
            if (empty($sortNearList) || true) {
                foreach ($data_list as $k=>&$v) {
                    if ($v['lng'] && $v['lat']) {
                        $v['distance'] = (int)round(self::getDistance($lng, $lat, $v['lng'], $v['lat']));
                        $sort[$k] = $v['distance'];
                        $v['distance_read'] = self::distanceReadUnit($v['distance']);
                    }
                }
                array_multisort($sort, SORT_ASC, SORT_NUMERIC, $data_list);
                return $data_list;
            }
            $pick_list = [];
            foreach ($data_list as $v) {
                $pick_list[$v['id']] = $v;
            }
            
            foreach ($sortNearList as $id=>$v) {
                $pickinfo = $pick_list[$id];
                $pickinfo['distance'] = $v['distance'];
                $pickinfo['distance_read'] = self::distanceReadUnit($v['distance']);
                $data_list_new[] = $pickinfo;
            }
            return $data_list_new;
        }
        
        foreach ($data_list as $k=>&$v) {
            if ($v['lng'] && $v['lat']) {
                $v['distance'] = '';
                $v['distance_read'] = '';
            }
        }
        
        return $data_list;
    }

    /**
     * 根据id获取单个自提点信息
     * @param {*} $id
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-15 17:52:24
     */
    public static function getPickUpById($id)
    {
        $where[] = ['id', '=', $id];
        $item = self::where($where)->find();
        $item['full_address'] = $item['city_name'] . $item['district_name'] . $item['full_address'];
        return $item;
    }

    /**
     * 获取距离和单位组合
     * @param int $distance
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-16 11:11:31
     */
    public static function distanceReadUnit($distance)
    {
        if ($distance < 1000) {
            return $distance.lang('米');
        }
        return round($distance/1000, 1).lang('公里');
    }

    /**
    *求两个已知经纬度之间的距离,单位为米
    *@param lng1,lng2 经度
    *@param lat1,lat2 纬度
    *@return float 距离，单位米
    **/
    public static function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        //deg2rad()函数将角度转换为弧度
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2*asin(sqrt(pow(sin($a/2), 2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2), 2)))*6378.137*1000;
        return $s;
    }


    
    /**
     * 利用redis的Geo计算经纬度距离
     * @param {*} $lng        圆心经度
     * @param {*} $lat        圆心纬度
     * @param {*} $radius     半径范围（公里）
     * @param {*} $sorttype   排序类型ASC/DESC
     * @param {*} $point_list 附近的地点经纬度数组
     * @return {*}            排序数组，计算好距离
     * @Author: wangph
     * @Date: 2021-04-23 19:01:01
     */
    public static function useRedisGeoDistance($lng, $lat, $radius=30, $sorttype='ASC', $point_list=[])
    {
        $redis_version = 0;
        try {
            $redis = new \Redis();
            //读取配置config\redis.php
            $config_redis = Config::get("redis.");
           
            if($config_redis['host'] &&  $config_redis['port']) {
                $redis->connect($config_redis['host'], $config_redis['port']);
            }
            if($config_redis['password']){
                $redis->auth($config_redis['password']);
            }
            
            $redis_version = $redis->info()['redis_version'];
            //3.2版本以上的redis才支持geo
            if ($redis_version < 3.2) {
                return false;
            }

            $redis->select(1);
            //添加成员的经纬度信息
            foreach ($point_list as $k=>$v) {
                $redis->rawCommand('geoadd', 'picklist', $v['lng'], $v['lat'], $v['id']);
            }
            // ASC 根据圆心位置，从近到远的返回元素 ,DESC 根据圆心位置，从远到近的返回元素
            $current_key =  strval($lng.'|'.$lat);
            
            $current_loc = $redis->rawCommand('geoadd', 'picklist', $lng, $lat,  $current_key);
            $sortList = $redis->rawCommand('georadius', 'picklist', $lng, $lat, $radius, 'km', $sorttype);
            //获取两个地理位置的距离，单位：m(米，默认)， km(千米)， mi(英里)， ft(英尺)
            $sortNear = [];
            foreach ($sortList as $id) {
                if (is_numeric($id)) {
                    $sortNear[$id]['distance'] = round($redis->rawCommand('geodist', 'picklist', $current_key, $id, 'm'));
                }
            }
            return $sortNear;
            
		} catch(\Exception $e){                    
            //throw new \Exception("redis connect fail");
            return false;           
        }
       
        
    }
}
