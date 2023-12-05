<?php
/**
 * Created by PhpStorm.
 * User: 7howe
 * DateTime: 2019/11/25 18:03
 */

namespace app\goods\validate;


use think\Validate;

class Comment extends Validate
{
    protected $rule = [
        'star' => 'require|between:1,5',
        'type' => 'in:0,1',
        'content' => 'require'
    ];

    protected $message = [
        'star.require' => '评分值必须',
        'star.between' => '评分值必须为1~5',
        'type.in' => '评论是否匿名',
        'content.require' => '评论内容必须'
    ];
}
