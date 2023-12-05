<?php
/**
 * Created by PhpStorm.
 * User: 7howe
 * DateTime: 2019/11/25 17:50
 */

namespace app\goods\validate;


use think\Validate;

class OederRefund extends Validate
{
    //定义规则
    protected $rule = [
        'refund_type' => 'require',
        'refund_reason' => 'require',
    ];

    protected $message = [
        'refund_type.require' => '退款方式必选',
        'refund_reason.require' => '退款原因必填',
    ];

    // 定义验证场景
    protected $scene = [

    ];
}
