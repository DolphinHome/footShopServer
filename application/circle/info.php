<?php
/*
 * @Descripttion: 
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-30 09:54:03
 */
/**
 * 模块信息
 */
return [
    'name' => 'circle',//模块名
    'title' => lang('圈子'),//模块标题
    'identifier' => 'circle.lwwanphp.module',//模块唯一标识[必填]，格式：模块名.开发者标识.module
    'icon' => 'fa fa-fw fa-cubes',//图标
    'description' => lang('圈子'),//描述
    'author' => lang('杜鹏'),//作者
    'author_url' => 'javascript:;',//作者网址
    'version' => '1.0.0',//版本号
    'need_module' => [//依赖的其他模块
        ['admin', 'admin.zbphp.module', '1.0.0',],//依赖后台，必填此项
    ],
    'need_plugin' => [],//依赖插件
    'tables' => [
        'circle',
        'circle_comment',
        'circle_like'
    ],//表列表【数组】
    'database_prefix' => 'lb_',//表前缀
    'config' => [//配置项【数组】
        [
            'type' => 'text',
            'name' => 'stock',
            'title' => lang('库存配置'),
            'tips' => lang('库存配置'),
            'value' => 0,
            '' => '',
        ],
    ],
    'action' => [],//行为配置
];