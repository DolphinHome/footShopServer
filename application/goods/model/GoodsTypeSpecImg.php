<?php
// +----------------------------------------------------------------------
// | LwwanPHP
// +----------------------------------------------------------------------
// | 版权所有 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 http://www.sitejs.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------

namespace app\goods\model;

use think\Model as ThinkModel;

/**
 * 单页模型
 * @package app\user\model
 */
class GoodsTypeSpecImg extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_TYPE_SPEC_IMAGE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}