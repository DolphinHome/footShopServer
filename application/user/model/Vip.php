<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\model;

use think\Model as ThinkModel;

/**
 * 单页模型
 * @package app\user\model
 */
class Vip extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__USER_VIP__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}