<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\validate;

use think\Validate;

/**
 * 菜单验证器
 * @package app\admin\validate
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Menu extends Validate
{
    //定义验证规则
    protected $rule = [
        'pid|所属菜单'    => 'require|token',
        'title|菜单名称'  => 'require',
    ];

    //定义验证提示
    protected $message = [
        'pid.require'    => '请选择所属菜单',
		'title.require'    => '请选择菜单名称',
    ];
}
