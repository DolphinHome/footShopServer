<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\model;

use think\Model as ThinkModel;

/**
 * 导航分类模型
 * @package app\operation\model
 */
class NavType extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__OPERATION_NAV_TYPE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}