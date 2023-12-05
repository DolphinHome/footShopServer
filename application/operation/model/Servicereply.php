<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\model;

use think\Model as ThinkModel;

/**
 * 单页模型
 * @package app\operation\model
 */
class Servicereply extends ThinkModel
{
    protected $connection = 'mysql://zb_mkh:jzeTMcLYxY8yshRb@47.92.235.222:3306/zb_mkh#utf8';
    // 设置当前模型对应的完整数据表名称
    protected $table = '__LB_OPERATION_SERVICE_REPLY__';
    
    public static $replyType = [
        '1' => '开场白',
        '2' => '快捷问题',
        '3' => '离线消息',
        '4' => '猜你喜欢'
    ];

    /**
     * 获取聊天服务标签
     * @param $partner_id int 商户ID
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月30日15:14:18
     */
    public static function getServiceLabels($partnerId=0)
    {
    	return self::field('id,problem,answer,url')->where(['type'=>2, 'status'=>1, 'partner_id'=>$partnerId])->select();
    }

}