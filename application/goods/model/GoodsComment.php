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
 * @package app\user\model
 */
class GoodsComment extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_COMMENT__';

    // 设置主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 查询评论条数
    public static function getGoodsCommentCount($where=[])
    {
    	return self::alias('c')->where($where)->count();
    }

}