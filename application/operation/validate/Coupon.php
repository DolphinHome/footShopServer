<?php

namespace app\operation\validate;

use think\Validate;

/**
 * 优惠券验证器
 * @package app\operation\validate
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Coupon extends Validate
{
    //定义验证规则
    protected $rule = [
        'name|优惠券名称' => 'require',
        'start_time|开始发放时间' => 'require|date',
        'end_time|发放结束时间' => 'require|date',
        'money|面值' => 'require',
        'min_order_money|最低使用金额' => 'require|float',
        'valid_day|有效天数' => 'require',
        'status|状态' => 'require',
        'stock|总张数' => 'require|number',

    ];

    //定义验证提示
    protected $message = [
        'name.require' => '优惠券名称不能为空',
        'start_time.require' => '请选择开始发放时间',
        'end_time.require' => '请选择发放结束时间',
        'money.require' => '请填写面值，免运费类型填写0即可',
        'min_order_money.float' => '请填写有效数字',
        'min_order_money.require' => '请填写最低使用金额',
        'valid_day.require' => '请填写有效天数',
        'status.require' => '请选择状态',
        'stock.require' => '优惠券总张数不能为空',
        'stock.number' => '优惠券总张数必须为数字'

    ];
}
