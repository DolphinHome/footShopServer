<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\validate;

use think\Validate;

/**
 * 客服列表验证器
 * @package app\operation\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Service extends Validate
{
    //定义规则
    protected $rule = [
        'nickname' => 'require',
        'username' => 'require',
        'password' => 'require',
        'group' => 'require',
        'avatar' => 'require',
    ];

    protected $message = [
        'nickname.require' => '昵称不能为空',
        'username.require' => '账号不能为空',
        'password.require' => '密码不能为空',
        'group.require' => '请选择分组',
        'avatar.require' => '请上传头像',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => ['nickname','username','password','group','avatar'],
        'edit' => ['nickname','username','group','avatar'],
    ];
}
