<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


/**
 * 菜单信息
 */
return [
  [
    'title' => '{title}',
    'icon' => '{icon}',
    'url_value' => '{name}/index/index',
    'url_target' => '_self',
    'online_hide' => 0,
    'sort' => 100,
    'status' => 1,
    'child' => [
      [
        'title' => '{title}管理',
        'icon' => 'fa fa-fw fa-folder-open-o',
        'url_value' => '',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 1,
        'status' => 1,
        'child' => [
          [
            'title' => '{title}列表',
            'icon' => 'fa fa-fw fa-list',
            'url_value' => '{name}/index/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 1,
            'status' => 1,
            'child' => [
              [
                'title' => '编辑',
                'icon' => '',
                'url_value' => '{name}/index/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 1,
                'status' => 1,
              ],
              [
                'title' => '删除',
                'icon' => '',
                'url_value' => '{name}/index/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 2,
                'status' => 1,
              ],
              [
                'title' => '启用',
                'icon' => '',
                'url_value' => '{name}/index/enable',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 3,
                'status' => 1,
              ],
              [
                'title' => '禁用',
                'icon' => '',
                'url_value' => '{name}/index/disable',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 4,
                'status' => 1,
              ],
              [
                'title' => '新增',
                'icon' => '',
                'url_value' => '{name}/index/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 5,
                'status' => 1,
              ],
            ],
          ],
        ],
      ],
    ],
  ],
];
