<?php

namespace app\recommend\model;

use think\Model as ThinkModel;

/**
 * 系统跑批表
 * @package app\recommend\model
 */
class Queue extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__QUEUE__';

}
