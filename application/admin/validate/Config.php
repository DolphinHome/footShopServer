<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------



namespace app\admin\validate;

use think\Validate;

/**
 * 配置验证器
 * @package app\admin\validate
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Config extends Validate
{
    // 定义验证规则
    protected $rule = [
        'group|配置分组' => 'require|token',
        'type|配置类型'  => 'require',
        'name|配置名称'  => 'require|regex:^[a-zA-Z]\w{0,39}$|unique:admin_config',
        'title|配置标题' => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'name.regex' => '配置名称由字母和下划线组成',
    ];

    // 定义场景，供快捷编辑时验证
    protected $scene = [
        'name'  => ['name'],
        'title' => ['title'],
    ];
}
