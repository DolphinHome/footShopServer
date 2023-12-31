<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\model;

use think\Model as ThinkModel;
use think\Db;

/**
 * 关注模型
 * Class Follow
 * @package app\user\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/9 14:02
 */
class Follow extends ThinkModel
{

    //表名
    protected $table = '__USER_FOLLOW__';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取列表
     * @param array $map
     * @param array $order
     * @return \think\Paginator
     * @throws \think\exception\DbException
     * @since 2019/4/9 14:02
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getList($map = [], $order = [])
    {

        return self::where($map)->order($order)->paginate();
    }

    /**
     * 是否关注
     * @param int $uid 关注人ID
     * @param $fuid 被关注人ID
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function isFollow($uid, $fuid)
    {
        return self::where(['user_id' => $uid, 'fans_id' => $fuid])->count();
    }

    /**
     * 取消关注
     * @param int $uid 关注人ID
     * @param int $fuid 被关注人ID
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function delFollow($uid, $fuid)
    {
        return self::where(['user_id' => $uid, 'fans_id' => $fuid])->delete();
    }

    /**
     * 添加关注
     * @param int $uid 关注人ID
     * @param int $fuid 被关注人ID
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function saveFollow($uid, $fuid)
    {
        return self::create(['user_id' => $uid, 'fans_id' => $fuid]);
    }

    /**
     * 获取粉丝数量
     * @param int $fuid 被关注人ID
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getFans($fuid)
    {
        return self::where('fans_id', $fuid)->count();
    }

    /**
     * 获取关注数量
     * @param int $uid 会员ID
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getFollows($uid)
    {
        return self::where('user_id', $uid)->count();
    }


    /**
     * 获取粉丝
     * @param int $tid $fuid 被关注人ID
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getFansList($fuid)
    {
        return Db::name("user_follow")
            ->alias("f")
            ->field("u.*,f.create_time as follow_time")
            ->join("__USER__ u", "f.user_id = u.id")
            ->where("f.fans_id", $fuid)
            ->order("f.create_time desc")
            ->paginate();
    }

    /**
     * 获取关注
     * @param int $uid 会员ID
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getFollowsList($uid)
    {
        return Db::name("user_follow")
            ->alias("f")
            ->field("u.*,f.create_time as follow_time,f.tid")
            ->join("__USER__ u", "f.tid = u.id")
            ->where("f.user_id", $uid)
            ->order("f.create_time desc")
            ->paginate();
    }

}
