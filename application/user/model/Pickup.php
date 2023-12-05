<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

/*
 *  用户自提预留信息表
 */
namespace app\user\model;

use think\Model as ThinkModel;

class Pickup extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__USER_PICKUP__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


    /**
     * 获取默认提货人
     * @param {*} $userid
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-20 18:34:56
     */
    public static function getDefaultInfo($userid)
    {
        $map['user_id'] =  $userid;
        $map['is_default'] = 1;
        $data = self::where($map)->field('id,name,mobile,is_default')->find();
        return $data??[];
    }

    /**
     * 获取默认提货人列表 
     * @param {*} $userid
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-20 18:34:53
     */
    public static function getList($userid)
    {
        $map['user_id'] =  $userid;
        $data = self::where($map)->field('id,name,mobile,is_default')->select();
        return $data;
    }


    /**
     * 获取指定提货人
     * @param {*} $id
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-20 18:39:56
     */
    public static function getInfoById($userid, $id)
    {
        $map['id'] =  $id;
        if($userid) {
            $map['user_id'] =  $userid;
        }
        $data = self::where($map)->field('id,name,mobile,is_default')->find();
        return $data??[];
    }

    /**
     * 修改默认提货人
     * @param {*} $userid
     * @param {*} $id
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-21 14:15:34
     */
    public static function setDefault($userid, $id)
    {

        $map['id'] =  $id;
        $map['user_id'] =  $userid;
        $res = self::where($map)->find();
        if (empty($res)) {
            return ['code'=>0,'msg'=>'no data'];
        }

        //其他改为非默认
        $where['user_id'] = $userid;
        self::where($where)->update(['is_default' => 0]);
        //指定id改为默认
        $res = self::where($map)->update(['is_default' => 1]);
        return ['code'=>1,'msg'=>'ok'];
    }

    
}