<?php

namespace app\goods\model;

use think\Model as ThinkModel;

class GoodsCollect extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_QA_COLLECT__';

    protected $autoWriteTimestamp = true;

    /**
     * 是否点赞、关注
     * @param int $uid 收藏人ID
     * @param $type 收藏的对象类型
     * @param $collect_id 被收藏对象ID
     * @return mixed
     * @author zhougs
     */
    public static function isCollection($uid, $type, $collect_id)
    {
        return self::where(['user_id' => $uid, 'type'=>$type, 'collect_id' => $collect_id])->count();
    }

    /**
     * 数量
     * @param $collect_id
     * @param $type
     * @author zhougs
     * @created 2020年12月30日11:10:33
     */
    public static function collectionNum($collect_id , $type)
    {
        return self::where([ 'type'=>$type, 'collect_id' => $collect_id])->count();
    }
    /**
     * @param $uid
     * @param $fuid
     * @author zhougs
     * @created 2020年12月30日11:10:42
     */
    public static function delCollection($uid, $type, $collect_id)
    {
        return self::where(['user_id' => $uid, 'type'=>$type, 'collect_id' => $collect_id])->delete();
    }

}