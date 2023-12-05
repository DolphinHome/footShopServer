<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\model;

use think\Model as ThinkModel;

/**
 * 单页模型
 * @package app\operation\model
 */
class Servicewords extends ThinkModel
{
    protected $connection = 'mysql://zb_mkh:jzeTMcLYxY8yshRb@47.92.235.222:3306/zb_mkh#utf8';
    // 设置当前模型对应的完整数据表名称
    protected $table = '__LB_OPERATION_SERVICE_WORDS__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}