<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\validate;

use think\Validate;
/**
 * 充值规则验证器
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class RechargeRule extends Validate {



    protected $rule = [
        'name' => 'require',
        'money' => ['require','float','gt:0'],
        'add_money' => ['require','float','gt:0'],
        'status' => 'require|in:0,1',
    ];
    protected $message = [
        'name.require' => '规则名称不能为空',
        'money.require' => '支付金额不能为空',
        'money.float' => '支付金额必须为数字',
        'money.gt' => '支付金额必须大于0',
        'add_money.require' => '充值金额不能为空',
        'add_money.float' => '充值金额必须为数字',
        'add_money.gt' => '充值金额必须大于0',
        'status' => '状态必须为数字整数（0,1）',
        'status.require' => '状态不能为空',
    ];
    protected $scene = [
        'add' => ['name', 'money'],
        'edit' => ['name', 'money'],
    ];

}
