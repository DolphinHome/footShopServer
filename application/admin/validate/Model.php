<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\validate;

use think\Validate;

/**
 * 文档模型验证器
 * @package app\admin\validate
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Model extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|模型标识'  => 'require|regex:^[a-z]+[a-z0-9_]{0,39}$|unique:admin_model|token',
        'title|模型标题' => 'require|length:1,30|unique:admin_model',
        'table|附加表'  => 'regex:^[#@a-z]+[a-z0-9#@_]{0,60}$|unique:admin_model',
		'module|所属模块'  => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'name.regex' => '模型标识由小写字母、数字或下划线组成，不能以数字开头',
        'table.regex' => '附加表由小写字母、数字或下划线组成，不能以数字开头',
    ];

    // 定义场景
    protected $scene = [
        'edit' =>  ['title'],
    ];
}
