<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\{module}\model;

use think\Model as ThinkModel;

/**
 * 单页模型
 * @package app\{module}\model
 */
class {model} extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__{table}__';

    // 设置主键
    protected $pk = 'aid';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}