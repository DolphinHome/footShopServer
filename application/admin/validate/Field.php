<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\validate;

use think\Validate;

/**
 * 字段验证器
 * @package app\cms\validate
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Field extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|字段名称'   => 'require|regex:^[a-z]\w{0,39}$|unique:admin_model_field,name^model',
        'title|字段标题'  => 'require|length:1,30',
        'type|字段类型'   => 'require|length:1,30',
        'define|字段定义' => 'require|length:1,100',
        'tips|字段说明'   => 'length:1,200',
    ];

    //定义验证提示
    protected $message = [
        'name.regex' => '字段名称由小写字母和下划线组成',
    ];
}
