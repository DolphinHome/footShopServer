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
class Nav extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|导航名称'    => 'require',
		'typeid'         => 'require',
        'content'         => 'require',
        'width'         => 'integer',
        'height'        => 'integer',
    ];

    // 定义验证提示
    protected $message = [
        'thumb'           => '请上传图片',
		'typeid'           => '请选择所属广告位',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => ['name','typeid'],
		'edit' => ['name','typeid']
    ];
}
