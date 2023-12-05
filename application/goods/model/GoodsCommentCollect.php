<?php

namespace app\goods\model;

use app\operation\admin\SellLevel;
use think\Model as ThinkModel;

class GoodsCommentCollect extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_COMMENT_COLLECT__';

    protected $autoWriteTimestamp = true;

    /**
     * 是否点赞
     * @param int $uid 操作人ID
     * @param $collect_id 被操作对象ID
     * @return mixed
     * @author zhougs
     */
    public function isCollection($uid, $collect_id)
    {
        return self::where(['user_id' => $uid, 'collect_id' => $collect_id])->count();
    }

    /**
     * 数量
     * @param $collect_id
     * @author zhougs
     * @created 2020年12月30日11:10:33
     */
    public function collectionNum($collect_id)
    {
        return self::where(['collect_id' => $collect_id])->count();
    }
    /**
     * @param $uid
     * @param $fuid
     * @author zhougs
     * @created 2020年12月30日11:10:42
     */
    public function delCollection($uid, $collect_id)
    {
        return self::where(['user_id' => $uid, 'collect_id' => $collect_id])->delete();
    }
    /**
     * 商品点赞的
     * @param $goods_id
     * @author zhougs
     * @created 2020年12月30日11:10:42
     */
    public function getGoodsCollectionNum($goods_id)
    {
        $num = self::alias("gcc")
            ->leftJoin("goods_comment gc","gc.id=gcc.collect_id")
            ->where(['gc.goods_id' =>$goods_id, 'gc.status' => 1])
            ->group("gcc.collect_id")
            ->count("gcc.id");
        return $num ?: 0;
    }


    /**
     * 商品点赞的
     * @param $goods_id
     * @author zhougs
     * @created 2020年12月30日11:10:42
     */
    public function getGoodsCollectionId($goods_id)
    {
        $ids = self::alias("gcc")
            ->leftJoin("goods_comment gc","gc.id=gcc.collect_id")
            ->where(['gc.goods_id' =>$goods_id, 'gc.status' => 1])
            ->distinct(true)
            ->field('gcc.collect_id')
            ->select();
      
        $new_ids = [0];
        if(count($ids)) {
            foreach($ids as $v){
                $new_ids[] = $v['collect_id'];
            }
        }
        return $new_ids;
    }

}