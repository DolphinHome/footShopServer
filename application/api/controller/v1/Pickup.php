<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\user\model\Collection;
use service\ApiReturn;
use app\user\model\Address as AddressModel;
use app\goods\model\PickupDeliver;
use app\goods\model\OrderDeliveryTime;
use app\common\model\Area;
use app\user\model\Pickup as UserPickupModel;
use Think\Db;

/**
 * 定位获取自提点
 * @package app\api\controller\v1
 */
class Pickup extends Base
{

    /**
     * 获取自提点列表
     * @param {*} $data
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-14 09:52:22
     */
    public function get_deliver_list($data=[])
    {
        $post_json = json_decode(file_get_contents("php://input"), true);
        //参数校验
        $region_code = $data['region_code']??0;
        $district_id = $data['district_id']??0;
        //根据地区码反查id
        if ($district_id < 1 && $region_code) {
            $district_info = Area::where('region_code', $region_code)->field('id')->find();
            $district_id = $district_info['id'];
        }
        $deliver_name  = $data['deliver_name'];
        $lng  = $data['lng']??$post_json['lng'];
        $lat  = $data['lat']??$post_json['lat'];
        $data_list = PickupDeliver::getNearPickUp($district_id, $deliver_name, $lng, $lat);
        // 数据列表
        return ApiReturn::r(1, ['total'=>count($data_list), 'data'=>$data_list], 'ok');
    }

    
    /**
     * 获取市的区列表
     * @param {*} $data
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-14 17:25:42
     */
    public function get_area_list($data=[])
    {
        //默认为郑州市
        $city_name = $data['city']??'';
        $region_code = $data['code']??0;
        $city = [];
        if ($region_code) {
            //优先使用code获取
            $city = Area::where('region_code', $region_code )->field('id')->find();
        } 
        if ($city_name && empty($city)) {
            //code为空，city不为空时
            $city = Area::getCityInfoByName($city_name);
        }
         if(empty($city_name) && empty($region_code)){
            //city/code都为空，默认取郑州市
            $city = Area::getCityInfoByName('郑州市');
        }
        $city_area = Area::where('pid', $city['id'])->field('id,name,region_code')->select();
        return ApiReturn::r(1, $city_area, 'ok');
    }


    /**
     * 获取订单的配送时间段
     * @Author: wangph
     * @Date: 2021-04-15 10:23:04
     */
    public function get_delivery_time_list($data=[])
    {
        //默认获取两天内的时间段，支持多天
        $day = $data['day']??2;
        $time_list = OrderDeliveryTime::getTimeList($day);
        return ApiReturn::r(1, $time_list, 'ok');
    }

    
    /**
     * 获取默认提货人信息
     * @Author: wangph
     * @Date: 2021-04-15 10:23:04
     */
    public function get_user_pickup_default($data=[], $user)
    {
        $userid = $user['id'];
        $res = UserPickupModel::getDefaultInfo($userid);
        return ApiReturn::r(1, $res, 'ok');
    }

    /**
     * 获取提货人信息列表
     * @Author: wangph
     * @Date: 2021-04-15 10:23:04
     */
    public function get_user_pickup_list($data=[], $user)
    {
        $userid = $user['id'];
        $res = UserPickupModel::getList($userid);
        return ApiReturn::r(1, $res, 'ok');
    }

    /**
     * 增加提货人信息
     * @Author: wangph
     * @Date: 2021-04-15 10:23:04
     */
    public function add_user_pickup($data=[], $user)
    {
        $userid = $user['id'];
        $newdata['user_id'] = $userid;
        $newdata['name'] = $data['name'];
        $newdata['mobile'] = $data['mobile'];
        
        if ($data['is_default'] == 1) {
            //查看用户是否存在默认提货人
            $userDefault = UserPickupModel::getDefaultInfo($userid);
            if ($userDefault) {
                //修改用户默认地址
                $where['user_id'] = $userid;
                @UserPickupModel::where($where)->update(['is_default' => 0]);
            }
        }

        $newdata['is_default'] = $data['is_default'];
        $result = UserPickupModel::insertGetId($newdata);
        if ($result) {
            return ApiReturn::r(1, $newdata, lang('添加成功'));
        }
        return ApiReturn::r(0, [], lang('添加失败'));
    }

    
    /**
     * 删除提货人信息
     * @Author: wangph
     * @Date: 2021-04-15 10:23:04
     */
    public function del_user_pickup($data=[], $user)
    {
        $userid = $user['id'];
        $map['id'] = $data['id'];
        $map['user_id'] = $userid;
        $data = UserPickupModel::where($map)->delete();
        return ApiReturn::r(1, [], 'ok');
    }
    

    /**
     * 通过id 查看提货人详情 
     * @Author: wangph
     * @Date: 2021-04-15 10:23:04
     */
    public function get_user_pickup_by_id($data=[], $user)
    {
        $userid = $user['id'];
        $id = $data['id'];
        $res = UserPickupModel::getInfoById($userid, $id);
        return ApiReturn::r(1, $res, 'ok');
    }

    /**
     * 修改默认提货人 
     * @Author: wangph
     * @Date: 2021-04-15 10:23:04
     */
    public function set_user_pickup_default($data=[], $user)
    {
        $userid = $user['id'];
        $id = $data['id'];
        $res = UserPickupModel::setDefault($userid, $id);
        return ApiReturn::r($res['code'], [], $res['msg']);
    }

    /**
     * 编辑提货人
     * @Author: wangph
     * @Date: 2021-04-15 10:23:04
     */
    public function edit_user_pickup($data=[], $user)
    {
        $where['id'] = $data['id'];
        $where['user_id'] =  $user['id'];
        $pickdata['name'] = $data['name']??'';
        $pickdata['mobile'] = $data['mobile']??'';
        $pickdata['is_default'] = $data['is_default'];
       
        if (empty($pickdata['mobile']) &&  empty($pickdata['name'])){
            return ApiReturn::r(0, [], lang('姓名和手机号全为空'));
        }
        
        $res = UserPickupModel::where($where)->update($pickdata);
        if($res === false) {
            return ApiReturn::r(0, [], lang('编辑失败'));
        }
        //设置默认
        if($pickdata['is_default'] == 1) {
            UserPickupModel::setDefault($user['id'], $data['id']);
        }
        return ApiReturn::r(1, [], 'ok');
    }
    


}
