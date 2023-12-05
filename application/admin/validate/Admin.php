<?php
// +----------------------------------------------------------------------
// | 中犇多商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\admin\validate;

use think\Validate;

/**
 * 管理员主表验证器
 * @package app\user\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Admin extends Validate
{
    //定义规则
    protected $rule = [
        //至少八个字符，至少一个大写字母，一个小写字母，一个数字和一个特殊字符：
        'password|密码'=>'/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&#])[A-Za-z\d$@$!%*?&#]{8,32}$/'
    ];

    protected $message = [
        'password./^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&#])[A-Za-z\d$@$!%*?&#]{8,32}$/' =>'至少8个字符，包含大写字母，小写字母，数字和特殊字符'
    ];

    // 定义验证场景
    protected $scene = [

    ];
}
