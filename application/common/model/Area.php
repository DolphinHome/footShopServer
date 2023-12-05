<?php
/*
 * @Descripttion:
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-05-07 09:23:17
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
namespace app\common\model;

use service\Tree;
use think\Model as ThinkModel;

/**
 * 订单商品列表
 * @package app\goods\model
 */
class Area extends ThinkModel
{

    // 设置当前模型对应的完整数据表名称
    protected $table = '__CHINA_AREA__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = false;
    //直辖市
    public static $municipality = [
        '北京市','上海市','天津市','重庆市'
    ];

    /**
     * 获取地区缓存
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/11/11 10:14
     */
    public static function get_cache()
    {
        $area = cache('area_region');
        if (!$area) {
            $area = self::column('id,pid,name,level');
            $area = Tree::config(['child' => 'city'])->toLayer($area);
            cache('area_region', $area);
        }
        return $area;
    }
    /**
     * 根据id获取地区名称
     * @param $id
     * @return string
     */
    public static function getNameById($id)
    {
        $region = self::get_cache();
        return $region[$id]['name'];
    }

    /**
     * 根据名称获取地区id
     * @param $name
     * @param int $level
     * @param int $pid
     * @return mixed
     */
    public static function getIdByName($name, $level = 0, $pid = 0)
    {
        return static::useGlobalScope(false)->where(compact('name', 'level', 'pid'))->value('id');
    }

  
    /**
     * 根据地址获取经纬度
     * @param {*} $address
     * @return {*}
     * @Author: wangph
     * @Date: 2021-05-07 09:16:07
     */
    public static function getLocationByAddress($address)
    {
        $lng = $lat = '';
        $res = ['lng'=>$lng, 'lat'=>$lat];
        //高德地图开发平台自己申请
        $amap_api_key =  config('amap_api_key');
        if (empty($amap_api_key)) {
            return $res;
        }
        //高德地图api，根据名称获取经纬度
        $api_url = 'https://restapi.amap.com/v3/geocode/geo?address='.$address.'&output=json&key='.$amap_api_key;
        $info = file_get_contents($api_url);
        $info = json_decode($info, true);
        if ($info['status'] != 1) {
            return $res;
        }
        if (isset($info['geocodes']) && count($info['geocodes']) > 0) {
            $location = $info['geocodes'][0]['location'];
        }
        if ($location) {
            $location_arr = explode(',', $location);
            $lng = $location_arr[0];
            $lat = $location_arr[1];
            $res = ['lng'=>$lng, 'lat'=>$lat];
        }
        return $res;
    }


    /**
     * 名称获取城市二级信息，直辖市处理
     * @param {*} $cityname
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-22 11:21:38
     */
    public static function getCityInfoByName($cityname)
    {
        if (in_array($cityname, self::$municipality)) {
            $cityname = str_replace('市', '', $cityname);
            $where['shortname'] = $cityname;
        } else {
            $where['name'] = $cityname;
        }
        $where['level'] = 2;
        $res = self::where($where)->field('id')->find();
        return $res;
    }
}
