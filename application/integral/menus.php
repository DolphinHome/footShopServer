<?php
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
 * 菜单信息
 */
return [
  [
    'title' => lang('积分'),
    'icon' => 'fa fa-fw fa-trophy',
    'url_value' => 'integral/index/index',
    'url_target' => '_self',
    'online_hide' => 0,
    'sort' => 100,
    'status' => 1,
    'child' => [    
        [
        'title' => lang('商品管理'),
        'icon' => 'fa fa-fw fa-folder-open-o',
        'url_value' => '',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 1,
        'status' => 1,
        'child' => [
           [
            'title' => lang('商品分类'),
            'icon' => 'fa fa-fw fa-list',
            'url_value' => 'integral/category/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 2,
            'status' => 1,
            'child' => [
              [
                'title' => lang('新增'),
                'icon' => '',
                'url_value' => 'integral/category/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 1,
                'status' => 1,
              ],
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'integral/category/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 2,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'integral/category/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 3,
                'status' => 1,
              ],
              [
                'title' => lang('设置状态'),
                'icon' => '',
                'url_value' => 'integral/category/setstatus',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 4,
                'status' => 1,
              ],
           ],
        ],
        [
            'title' => lang('商品列表'),
            'icon' => 'fa fa-fw fa-list',
            'url_value' => 'integral/index/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 1,
            'status' => 1,
            'child' => [   
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'integral/index/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 1,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'integral/index/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 2,
                'status' => 1,
              ],
              [
                'title' => lang('启用'),
                'icon' => '',
                'url_value' => 'integral/index/enable',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 3,
                'status' => 1,
              ],
              [
                'title' => lang('禁用'),
                'icon' => '',
                'url_value' => 'integral/index/disable',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 4,
                'status' => 1,
              ],
              [
                'title' => lang('新增'),
                'icon' => '',
                'url_value' => 'integral/index/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 5,
                'status' => 1,
              ],
            ],       
          ],
        ],
      ],
      [
        'title' => lang('发货管理'),
        'icon' => 'fa fa-fw fa-list',
        'url_value' => '',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 2,
        'status' => 1,
        'child' => [
          [
            'title' => lang('发货列表'),
            'icon' => 'fa fa-fw fa-list-ol',
            'url_value' => 'integral/order/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 1,
            'status' => 1,
            'child' => [
                 [
                    'title' => lang('发货'),
                    'icon' => '',
                    'url_value' => 'integral/order/express_add',
                    'url_target' => '_self',
                    'online_hide' => 0,
                    'sort' => 1,
                    'status' => 1,
                 ],
                 [
                    'title' => lang('查看详情'),
                    'icon' => '',
                    'url_value' => 'integral/order/detail',
                    'url_target' => '_self',
                    'online_hide' => 0,
                    'sort' => 1,
                    'status' => 1,
                  ],
                  [
                    'title' => lang('编辑'),
                    'icon' => '',
                    'url_value' => 'integral/order/edit',
                    'url_target' => '_self',
                    'online_hide' => 0,
                    'sort' => 2,
                    'status' => 1,
                  ],
                  [
                    'title' => lang('删除'),
                    'icon' => '',
                    'url_value' => 'integral/order/delete',
                    'url_target' => '_self',
                    'online_hide' => 0,
                    'sort' => 3,
                    'status' => 1,
                  ],
                  [
                    'title' => lang('设置状态'),
                    'icon' => '',
                    'url_value' => 'integral/order/setstatus',
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
