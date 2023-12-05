<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\validate;

use think\Validate;

/**
 * 角色验证器
 * @package app\admin\validate
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Role extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|角色名称' => 'require|unique:user_role|token',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => ['name'],
        'edit' => []
    ];
}