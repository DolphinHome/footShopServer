<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\validate;

use think\Validate;

/**
 * 插件验证器
 * @package app\admin\validate
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Addons extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|插件名称'  => 'require|unique:Addons',
        'title|插件标题' => 'require',
    ];
}
