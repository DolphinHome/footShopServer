<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\shop\validate;

use think\Validate;

/**
 * 会员地址验证器
 * @package app\user\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Shop extends Validate
{
    //定义规则
    protected $rule = [
        'address' => 'require|max:100',
        'shop_name' => 'require|max:50',
        'thumb' => 'require|number',
        'province' => 'require',
        'city' => 'require',
        'area' => 'require',
    ];

    protected $message = [
        'address.require' => '详细地址必填',
        'address.max' => '详细地址不能大于100字',
        'shop_name.require' => '请填写店铺名称',
        'shop_name.max' => '店铺名称不能大于50个字符',
        'thumb.require' => '请上传门店logo',
        'province.require' => '请选择所属省',
        'city.require' => '请选择所属市',
        'area.require' => '请选择所属区',
    ];

    // 定义验证场景
    protected $scene = [
        //'title' => ['title']
    ];
}
