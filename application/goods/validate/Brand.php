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
class Brand extends Validate
{
    //定义规则
    protected $rule = [
        'name' => 'require',
        'logo' => 'require|gt:0',
        //'app_limitTime' => 'require|number'
    ];

    protected $message = [
        'name.require' => '品牌名称必须填写',
        'logo.require' => '品牌logo必须上传',
        'logo.gt' => '品牌logo必须上传',

    ];

    // 定义验证场景
    protected $scene = [
        'add' => [
            'name',
            'logo',
        ],
        'edit' => [
            'name',
            'logo',
        ]
    ];
}
