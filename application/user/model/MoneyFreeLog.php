<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\model;

use think\Model as ThinkModel;

/**
 * 会员冻结金额变动模型
 * @author chenchen
 * @time 2021年4月17日16:41:09
 */
class MoneyFreeLog extends ThinkModel
{

    protected $table = "__USER_FREEZE_MONEY_LOG__";

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    //记录类型。你有新的类型，请添加到这里
    public static $types = [
        '1' => '自购返',
        '2' => '分享赚',
        '3' => '分销佣金',
    ];


}
