<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\validate;

use think\Validate;

/**
 * 接口字段验证器
 * @package app\admin\validate
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class ApiFields extends Validate
{
    protected $rule = [
        //'fieldName' => 'require|alphaDash',
    ];

    protected $message = [
        'fieldName.require' => '字段名称不能为空',
        //'fieldName.alphaDash' => '字段名称只能是字母和数字，下划线_及破折号-',
    ];

    protected $scene = [
        'add'   => ['fieldName'],
        'edit'  => ['fieldName'],
        'fieldName' => ['fieldName'],
        'default' => ['default'],
        'info' => ['info'],
    ];
}