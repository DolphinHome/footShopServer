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
 * 商品上下架
 * @package app\goods\model
 */
class GoodsAutoGrounding extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_AUTO_GROUNDING__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


    /**
     * 获取商品上架状态
     * @param  [type] $goods_sn [description]
     * @return [type]           [description]
     */
    public static function get_goods_status($goods_sn)
    {
    	
    	$where['good_sn'] = $goods_sn;
    	$where['type'] = 1;
        $res_grounding = self::where(array('good_sn'=>$goods_sn,'type'=>1))->find();
        $res_ungrounding = self::where(array('good_sn'=>$goods_sn,'type'=>2))->find();
        $time_grounding = empty($res_grounding['run_time'])?0:strtotime($res_grounding['run_time']);
        $time_ungrounding = empty($res_ungrounding['run_time'])?0:strtotime($res_ungrounding['run_time']);
        
        if($time_grounding== 0 && $time_ungrounding == 0){
        	return 0;
        }elseif (time() < $time_grounding ) {
        	return 0;
        }elseif (time() > $time_ungrounding ) {
        	return 0;
        }else{
        	return 1;
        }

    }

}