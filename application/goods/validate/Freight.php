<?php
// +----------------------------------------------------------------------
// | LwwanPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.lwwan.com
// +----------------------------------------------------------------------
// | Author 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 QQ群331378225
// +----------------------------------------------------------------------

namespace app\goods\validate;

use think\Validate;

/**
 * 品牌验证器
 * @package app\goods\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Freight extends Validate
{
    //定义规则
    protected $rule = [
        'name' => 'require',
		'method' => 'require|number'
    ];

    protected $message = [
        'name.require' => '请填写模板名称',
        'method.require' => '请选择方式',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => ['name','method'],
		'edit' => ['name','method'],
    ];
}
