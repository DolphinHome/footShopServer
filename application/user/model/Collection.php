<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\model;

use think\Model as ThinkModel;

/**
 * 单页模型
 * @package app\user\model
 */
class Collection extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__USER_COLLECTION__';

    // 设置主键
    protected $pk = 'aid';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 是否收藏
     * @param int $uid 收藏人ID
     * @param $type 收藏的对象类型
     * @param $collect_id 被收藏对象ID
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function isCollection($uid, $type, $collect_id)
    {
        return self::where(['user_id' => $uid, 'type'=>$type, 'collect_id' => $collect_id])->count();
    }

    /**
     * 收藏数量
     * @param $collect_id
     * @param $type
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/9/16 21:16
     */
    public function collectionNum($collect_id , $type)
    {
        return self::where([ 'type'=>$type, 'collect_id' => $collect_id])->count();
    }
    /**
     * @param $uid
     * @param $fuid
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/24 18:59
     */
    public function delCollection($uid, $type, $collect_id)
    {
        return self::where(['user_id' => $uid, 'type'=>$type, 'collect_id' => $collect_id])->delete();
    }

}