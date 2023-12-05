<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\common\model;

use think\Model;

/**
 * 商品问题
 * Class GoodsAnswer
 * @package app\common\model
 * @author zhougs
 * @since 2020年12月29日10:32:00
 */
class GoodsQuestion extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_QUESTION__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}
