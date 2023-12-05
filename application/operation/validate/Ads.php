<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\validate;

use think\Validate;

/**
 * 广告验证器
 * @package app\operation\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Ads extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|广告名称'    => 'require|unique:operation_ads',
		'typeid'         => 'require',
        'content'         => 'require',
        'width'         => 'integer',
        'height'        => 'integer',
        'start_time' => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'thumb'           => '请上传图片',
        'content'         => '文字内容不能为空',
		'typeid'           => '请选择所属广告位',
        'width'         => '宽度只能填写数字',
        'height'        => '高度只能填写数字',
        'start_time'        => '请选择展示时间',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => ['name', 'typeid', 'height','thumb'],
		'edit' => ['name', 'typeid', 'height','thumb']
    ];
}
