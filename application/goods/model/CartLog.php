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
 * 购物车模型
 * @package app\goods\model
 */
class CartLog extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_CART_LOG__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 添加购物车日志
     * @param $data
     * @return bool|int|string
     */
    public static function AddCartLog($data){
        if( empty($data) ){
            return false;
        }
        $save_data = [
            "goods_id"=>$data['goods_id'],
            "user_id"=>$data['user_id'],
            "sku_id"=>$data['sku_id'],
            "num"=>$data['num'],
            "is_delete"=>0,
            "operation"=>$data['operation'],
            "create_time"=>time(),
        ];
        return self::insertGetId($save_data);
    }

}