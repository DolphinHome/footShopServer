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
  'name' => '{name}',
  'title' => '{title}',
  'identifier' => '{identifier}',
  'icon' => '{icon}',
  'description' => '{description}',
  'author' => '{author}',
  'author_url' => 'javascript:;',
  'version' => '{version}',
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
