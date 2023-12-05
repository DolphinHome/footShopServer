<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\validate;

use think\Validate;

/**
 * 投诉建议验证器
 * @package app\user\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Suggestions extends Validate
{
    //定义规则
    protected $rule = [
        'type' => 'require',
		'body' => 'require',
		'title' => 'require',
    ];

    protected $message = [
        'type.require' => '类型不能为空',
        'body.require' => '内容不能为空',
		'title.require' => '名称不能为空',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => ['type','body'],
		'typeadd' => ['title']
    ];
}
