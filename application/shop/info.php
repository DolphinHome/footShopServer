<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


/**
 * 模块信息
 */
return [
  'name' => 'shop',
  'title' => '附近门店管理',
  'identifier' => 'shop.zbphp.module',
  'icon' => 'fa fa-fw fa-bank',
  'description' => '附近门店管理',
  'author' => 'derui',
  'author_url' => 'javascript:;',
  'version' => '1.0.0',
  'need_module' => [
    [
      'admin',
      'admin.zbphp.module',
      '1.0.0',
    ],
  ],
  'need_plugin' => [],
  'tables' => [],
  'database_prefix' => 'lb_',
  'config' => [
    [
      'type' => 'number',
      'name' => 'test',
      'title' => '测试配置',
      'tips' => '测试提示',
      'value' => '',
      'min' => 0,
      '' => '',
    ],
  ],
  'action' => [],
  'access' => [],
];
