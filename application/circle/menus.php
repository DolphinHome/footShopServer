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
    'title' => lang('圈子'),
    'icon' => 'fa fa-fw fa-hand-lizard-o',
    'url_value' => '',
    'url_target' => '_self',
    'online_hide' => 0,
    'sort' => 1,
    'status' => 1,
    'child' => [
      [
        'title' => lang('圈子管理'),
        'icon' => 'fa fa-fw fa-folder-open-o',
        'url_value' => '',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 1,
        'status' => 1,
        'child' => [
          [
            'title' => lang('动态列表'),
            'icon' => 'fa fa-fw fa-list',
            'url_value' => 'circle/index/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 1,
            'status' => 1,
            'child' => [
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'circle/index/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 4,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'circle/index/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 5,
                'status' => 1,
              ],
              [
                'title' => lang('新增'),
                'icon' => '',
                'url_value' => 'circle/index/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 8,
                'status' => 1,
              ],
                [
                    'title' => lang('设置状态'),
                    'icon' => '',
                    'url_value' => 'circle/index/setstatus',
                    'url_target' => '_self',
                    'online_hide' => 0,
                    'sort' => 4,
                    'status' => 1,
                ],
            ],
          ],
          [
            'title' => lang('评论列表'),
            'icon' => 'fa fa-fw fa-list',
            'url_value' => 'circle/comment/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 2,
            'status' => 1,
            'child' => [
              [
                'title' => lang('新增'),
                'icon' => '',
                'url_value' => 'circle/comment/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 1,
                'status' => 1,
              ],
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'circle/comment/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 2,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'circle/comment/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 3,
                'status' => 1,
              ],
              [
                'title' => lang('设置状态'),
                'icon' => '',
                'url_value' => 'circle/comment/setstatus',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 4,
                'status' => 1,
              ],
            ],
          ],
        ],
      ],
    ],
  ],
];
