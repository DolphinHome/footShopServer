<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\model;

use think\Model as ThinkModel;

/**
 * 导航分类模型
 * @package app\operation\model
 */
class UserAddress extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__USER_ADDRESS__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获得单条收货地址信息
     * @param $where
     * @return array|false|null|\PDOStatement|string|ThinkModel
     */
    public function get_one_address($where){
        $address = UserAddress::where($where)->field("id,user_name,user_phone,user_address,is_default,province_name,city_name,country_name,detailInfo")->find();
        return $address;
    }

    public function get_region($where){
//        $region
    }

}