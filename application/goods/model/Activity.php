<?php

namespace app\goods\model;

use think\Model as ThinkModel;

class Activity extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_ACTIVITY__';

    // 活动类型
    public static $activity_type = [
        0 => '无',
        1 => '秒杀',
        2 => '拼团',
        3 => '预售',
        4 => '折扣',
        5 => '砍价',
        6 => '首次限购',
        7 => '新人0元购',
        8 => '积分商城',
        9 => '抽奖9宫格',
        10 => '砍价活动',
    ];
    //活动不受时间限制
    public static $no_time = [
        '积分商城' => 8
    ];


    protected $autoWriteTimestamp = true;

}