<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\goods\model;

use think\Model as ThinkModel;

/**
 * 单页模型
 * @package app\goods\model
 */
class ExpressCompany extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_EXPRESS_COMPANY__';

    // 设置主键
    protected $pk = 'aid';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}