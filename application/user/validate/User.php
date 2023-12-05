<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\validate;

use think\Validate;

/**
 * 会员主表验证器
 * @package app\user\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class User extends Validate
{
    //定义规则
    protected $rule = [
        'mobile' => 'require',
        'user_name' => 'require',
        'head_img' => 'require',
        'password|密码'=>'/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,32}$/'
    ];

    protected $message = [
        'password./^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,32}$/' =>'密码只能是6-32位字母加数字',
        'mobile.require' => '手机号码必须填写',
        'user_name.require' => '用户姓名必须填写',
        'head_img.require' => '用户头像必须上传',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => [
            'mobile',
            'user_name',
            'head_img',
            'password',
        ],
        'edit' => [
            'mobile',
            'user_name',
            'head_img',
            'password',
        ]
    ];
}
