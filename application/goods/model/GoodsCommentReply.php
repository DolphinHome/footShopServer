<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\goods\model;

use think\Model as ThinkModel;

/**
 * 商品评论回复列表
 * @package app\user\model
 */
class GoodsCommentReply extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_COMMENT_REPLY__';

    // 设置主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}