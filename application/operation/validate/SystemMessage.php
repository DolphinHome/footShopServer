<?php


namespace app\operation\validate;


use think\Validate;

class SystemMessage extends Validate
{
    //定义规则
    protected $rule = [
        'title|标题' => 'require|length:2,49',
        'content|内容' => 'require',
    ];

    protected $message = [
        'title.length' => '标题长度请保持在2~49个字之间',
    ];
    // 定义验证场景
    protected $scene = [
        'add' => ['title','content'],
        'typeadd' => ['title']
    ];
}