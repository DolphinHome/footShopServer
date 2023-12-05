<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\validate;

use think\Validate;

/**
 * 会员菜单验证器
 * @package app\user\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Menu extends Validate
{
    //定义规则
    protected $rule = [
        'title' => 'require'
    ];

    protected $message = [
        'title.require' => '请填写菜单名称',
        //'app_limitTime.number' => '',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => ['title'],
		'edit' => ['title']
    ];
}
