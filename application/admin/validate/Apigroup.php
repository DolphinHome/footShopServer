<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\validate;

use think\Validate;

/**
 * 接口分组验证器
 * @package app\admin\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Apigroup extends Validate
{
    //定义规则
    protected $rule = [
        //'app_limitTime' => 'require|number'
    ];

    protected $message = [
        //'app_limitTime.require' => '',
        //'app_limitTime.number' => '',
    ];

    // 定义验证场景
    protected $scene = [
        //'title' => ['title']
    ];
}
