<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\model;

use think\Model as ThinkModel;
/**
 * 会员收入等级
 * Class LevelVotes
 * @package app\member\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/9 17:52
 */
class LevelVotes extends ThinkModel{
    
    protected $table = "__USER_LEVEL_VOTES__";

    /**
     * 获取等级名称
     * @param $votes_total
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/9 17:52
     * @return mixed
     */
    public static function getLevelName($votes_total){
        $level = self::getLevel($votes_total);
        return $level['name'];
    }

    /**
     * 获取等级ID
     * @param $votes_total
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/9 17:52
     * @return mixed
     */
    public static function getLevelId($votes_total){
        $level = self::getLevel($votes_total);
        return $level['levelid'];
    }

    /**
     * 获取收益等级
     * @param $votes_total
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/9 17:52
     * @return array|mixed
     */
    public static function getLevel($votes_total){
        $levels =  cache("levels_votes_total");
        if(!$levels){
            $levels = self::where(1)->order("levelid asc")->select();
            cache("levels_votes_total",$levels,7200);
        }
        $info = [];
        foreach($levels as $val){
            $info = $val;
            if($val['upgrade_score'] >= $votes_total){               
                break;
            }   
        }
        return $info;
    }

    /**
     * 获取升级进度
     * @param $upgrade_score
     * @param $votes_total
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/9 17:52
     * @return int
     */
    public static function getProgress($upgrade_score, $votes_total){
        $cha   = $upgrade_score + 1 - $votes_total;      
        $baifen = ($votes_total/($cha+$votes_total))*100; 
        return intval($baifen);
    }
    
}
