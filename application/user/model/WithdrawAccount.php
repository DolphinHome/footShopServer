<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\model;

/**
 * 提现账户
 * Class WithdrawAccount
 * @package app\user\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @created 2019/9/13 20:50
 */
class WithdrawAccount extends \think\Model{
    
    protected $table = '__USER_WITHDRAW_ACCOUNT__';
    
        
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    
}
