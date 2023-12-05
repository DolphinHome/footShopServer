<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\validate;

use think\Validate;

/**
 * 用户验证器
 * @package app\admin\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Account extends Validate
{
    //定义验证规则
    protected $rule = [
        'username|用户名' => 'require|alphaNum|unique:operation_service',
        'nickname|昵称'  => 'require|unique:operation_service',
        'group|分组'      => 'require',
        'password|密码'  => 'require|length:6,20',
    ];

    //定义验证提示
    protected $message = [
        'username.require' => '请输入用户名',
        'password.require' => '密码不能为空',
        'password.length'  => '密码长度6-20位',
    ];

    //定义验证场景
    protected $scene = [
		'auth_add'  => ['username', 'password'],
		'auth_edit'  => ['password' => 'length:6,20'],
        //更新
        'update'  =>  ['email', 'password' => 'length:6,20', 'mobile', 'role'],
        //登录
        'signin'  =>  ['username' => 'require', 'password' => 'require'],
    ];
}
