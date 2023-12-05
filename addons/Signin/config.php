<?php
/**
 * 插件配置信息
 */
return [
    [
        'type' => 'number',
        'name' => 'signinscore',
        'title' => '签到积分',
        'tips' => '签到赠送的积分',
        'extra' => [],
        'value' => 1
    ],
    [
        'type' => 'number',
        'name' => 'add_day',
        'title' => '累计天数',
        'tips' => '累计天数多少天赠送积分',
        'extra' => [],
        'value' => 1
    ],
    [
        'type' => 'number',
        'name' => 'totalscore',
        'title' => '累计天数赠送积分',
        'tips' => '累计天数赠送积分',
        'extra' => [],
        'value' => 1
    ],
//    [
//        'type' => 'number',
//        'name' => 'day_max_score',
//        'title' => '累计天数最大赠送积分',
//        'tips' => '累计天数最大赠送积分',
//        'extra' => [],
//        'value' => 1
//    ],
    [
        'type' => 'radio',
        'name' => 'isfillup',
        'title' => '是否开启补签',
        'tips' => '是否开启补签',
        'extra' => ['否', '是'],
        'value' => 1
    ], [
        'type' => 'number',
        'name' => 'fillupscore',
        'title' => '补签消耗积分',
        'tips' => '补签时消耗的积分',
        'extra' => [],
        'value' => 100
    ], [
        'type' => 'number',
        'name' => 'fillupdays',
        'title' => '补签天数内',
        'tips' => '多少天数内漏签的可以补签',
        'extra' => [],
        'value' => 3
    ], [
        'type' => 'number',
        'name' => 'fillupnumsinmonth',
        'title' => '每月可补签次数',
        'tips' => '每月可补签次数',
        'extra' => [],
        'value' => 1
    ],

];
