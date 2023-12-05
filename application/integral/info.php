<?php
/*
 * @Descripttion: 
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-30 09:45:29
 */
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author jxy [ 415782189@qq.com ]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

/**
 * 模块信息
 */
return [
  'name' => 'integral',
  'title' => lang('积分'),
  'identifier' => 'integral.zbphp.module',
  'icon' => 'fa fa-fw fa-trophy',
  'description' => lang('积分模块'),
  'author' => 'jxy',
  'author_url' => 'javascript:;',
  'version' => '1.0.0',
  'need_module' => [
    [
      'admin',
      'admin.zbphp.module',
      '1.0.0',
    ],
    [
      'goods',
      'goods.zbphp.module',
      '1.0.0',
    ],
  ],
  'need_plugin' => [],
  'tables' => [
    'goods_integral',
    'goods_integral_category',
    'order_integral_list',
  ],
  'database_prefix' => 'lb_',
  'config' => [
      [
      'type' => 'wangeditor',
      'name' => 'integral_rule',
      'title' => lang('积分规则'),
      'tips' => lang('积分规则说明'),
      'value' => '',
      '' => '',
      ],
      [
          'type' => 'number',
          'name' => 'register_integral',
          'title' => lang('注册赠送积分'),
          'tips' => lang('注册赠送积分'),
          'value' => 0,
      ],
      [
          'type' => 'number',
          'name' => 'order_integral',
          'title' => lang('下单赠送积分').'('.lang('百分比').')',
          'tips' => lang('下单赠送积分').'('.lang('百分比').')',
          'value' => 0,
      ],
      [
          'type' => 'number',
          'name' => 'integral_deduction',
          'title' => '1'.lang('元').lang('抵扣多少积分'),
          'tips' => '1'.lang('元').lang('抵扣多少积分'),
          'value' => 0,
      ],
  ],
  'action' => [],
  'access' => [],
];
