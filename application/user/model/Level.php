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
class Level extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__USER_LEVEL__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取等级名称
     * @param $consumption
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/4/27 11:25
     * @return mixed
     */
    public static function getLevelName($consumption)
    {
        $level = self::getLevel($consumption);
        return $level['name'];
    }

    /**
     * 简报
     * @param float $consumption
     * @return int
     */
    public static function getLevelId($consumption)
    {
        $level = self::getLevel($consumption);
        return $level['levelid'];
    }

    /**
     * 获取消费等级
     * @param $consumption
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/4/27 11:25
     * @editor XX [ xxx@qq.com ]
     * @updated 2019.03.30
     * @return array|mixed
     */
    public static function getLevel($consumption)
    {
        $levels = cache("levels_consumption");
        if (!$levels) {
            $levels = self::where(1)->order("levelid asc")->select();
            cache("levels_consumption", $levels, 7200);
        }
        $info = [];
        foreach ($levels as $val) {
            $info = $val;
            if ($val['upgrade_score'] > $consumption) {
                break;
            }
        }
        return $info;
    }

    /**
     * 获取指定等级需要的分数
     * @param $levelid
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/4/27 11:25
     * @editor XX [ xxx@qq.com ]
     * @updated 2019.03.30
     * @return mixed
     */
    public static function getLevelScore($levelid)
    {
        return self::where("levelid", $levelid)->cache(3600)->value("upgrade_score");
    }


    /**
     * 获取升级进度
     * @param $upgrade_score
     * @param $consumption
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/4/27 11:23
     * @return int
     */
    public static function getProgress($upgrade_score, $consumption)
    {
        $cha = $upgrade_score + 1 - $consumption;
        $baifen = ($consumption / ($cha + $consumption)) * 100;
        return intval($baifen);
    }

    /**
     * 获取消费等级
     * @param $consumption
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/4/27 11:24
     * @return type
     */
    public static function getMLevel($consumption)
    {
        return self::getLevel($consumption);
    }

    /**
     * 获取收益等级
     * @param $votes_total
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/4/27 11:24
     * @return array|mixed
     */
    public static function getVLevel($votes_total)
    {
        return LevelVotes::getLevel($votes_total);
    }

    /**
     * 获取会员等级列表
     * @param $where 查询条件 array（选填）
     * @param $filed 查询字段 string（选填）
     * @author chenchen
     * @created 2021年4月19日09:41:40
     */
    public static function getLevelList($where=[], $field = "*")
    {
        $res = self::where($where)
            ->field($field)
            ->select();
        if (count($res) > 0) {
            $res = $res->toArray();
        } else {
            $res = [];
        }
        return $res;
    }
}