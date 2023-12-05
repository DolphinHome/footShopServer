<?php
/**
 * Created by PhpStorm.
 * User: 7howe
 * DateTime: 2019/11/22 20:33
 */

namespace app\goods\model;

use think\Model as ThinkModel;

class GoodsStockLog extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_STOCK_LOG__';

    public $remarkType = [
        '用户购买',
        '管理员操作',
        '管理员进货',
        '管理员操作退货',
        '管理员操作残次品入库'
    ];

    public static function AddStockLog($goods_id,$sku_id,$order_sn,$stock_before,$stock_change = 0,$stock_after,$type = 0,$operator = 0,$remark,$goods_sn)
    {

        if( !$goods_id || !$stock_change ){
            return false;
        }
        $save_data = [
            "goods_id"=>$goods_id,
            "sku_id"=>$sku_id,
            "order_sn"=>$order_sn,
            "stock_before"=>$stock_before,
            "stock_change"=>$stock_change,
            "stock_after"=>$stock_after,
            "type"=>$type,
            "operator"=>$operator,
            "remark"=>$remark,
            "goods_sn"=>$goods_sn,
            "create_time"=>time()
        ];
        self::create($save_data);
        return true;
    }
}
