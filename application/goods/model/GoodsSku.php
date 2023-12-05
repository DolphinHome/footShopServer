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
 * 商品SKU
 * @package app\user\model
 */
class GoodsSku extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_SKU__';

    // 设定主键
    protected $pk = 'sku_id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}