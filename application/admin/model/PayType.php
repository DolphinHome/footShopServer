<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\model;

use think\Model as ThinkModel;

/**
 * 日志记录模型
 * @package app\admin\model
 */
class PayType extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__PAY_TYPE_LIST__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


}