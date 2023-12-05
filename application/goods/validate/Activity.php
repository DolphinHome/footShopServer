<?php


namespace app\goods\validate;


use think\Validate;

class Activity extends Validate
{
    //定义规则
    protected $rule = [
        'name' => 'require',
        'edate' => 'require',
        //'app_limitTime' => 'require|number'
    ];

    protected $message = [
        'name.require' => '请输入活动名称',
        'edate.require' => '请选择活动日期',

    ];

    // 定义验证场景
    protected $scene = [
    ];
}