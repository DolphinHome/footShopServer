<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\validate;

use think\Validate;
/**
 * 接口验证器
 * @package app\admin\validate
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Apilist extends Validate
{
    protected $rule = [
        'apiName' => 'require',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'apiName.require' => '接口名称不能为空',
        'status' => '状态必须为数字整数（0,1）',
        'status.require' => '状态不能为空',
    ];

    protected $scene = [
        'add'   => ['apiName'],
        'edit'  => ['apiName'],
        'apiName' => ['apiName'],
        'status' => ['status'],
        'info' => ['info'],
    ];
}