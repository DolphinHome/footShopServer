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
 * 订单行为记录表
 * @package app\goods\model
 */
class OrderAction extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ORDER_ACTION__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


    public static function actionLog($order_sn, $action, $order_status, $order_status_text, $remark = '')
    {
        $data = [
            "order_sn" => $order_sn,
            "action" => $action,
            "user_id" => UID,
            "order_status" => $order_status,
            "order_status_text" => $order_status_text,
            "create_time" => time(),
            "remark" => $remark
        ];
        self::create($data);
        return true;
    }

    public function getActionLogs($where = '')
    {
        return self::alias('oa')->join("admin a", "oa.user_id=a.id")->field("order_sn,action,order_status,order_status_text,oa.create_time,username,remark")->where($where)->paginate();
    }

}