<?php
// +----------------------------------------------------------------------
// | LwwanPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.lwwan.com
// +----------------------------------------------------------------------
// | Author 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 QQ群331378225
// +----------------------------------------------------------------------
namespace app\goods\model;

use think\Model as ThinkModel;
/**
 * 订单商品列表
 * @package app\goods\model
 */
class OrderGoods extends  ThinkModel
{

    // 设置当前模型对应的完整数据表名称
    protected $table = '__ORDER_GOODS_LIST__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = false;
    public static function get_one_goods($order_sn = null){
        return self::where('order_sn',$order_sn)->find();
    }
}