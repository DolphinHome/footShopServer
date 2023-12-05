<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\model;
use think\Model as ThinkModel;

/**
 * 充值规则管理
 * Class RechargeRule
 * @package app\user\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @created 2019/5/8 16:08
 */
class RechargeRule extends ThinkModel{
    
    protected $table = '__USER_RECHARGE_RULE__';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @param array $map
     * @param array $order
     * @return object
     * @throws \think\exception\DbException
     */
    
    public static function getList($map=[],$order=[]){
        return self::where($map)->order($order)->paginate();
    }
    
}
