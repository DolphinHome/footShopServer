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
    'title' => lang('会员'),
    'icon' => 'fa fa-fw fa-user',
    'url_value' => 'user/index/index',
    'url_target' => '_self',
    'online_hide' => 0,
    'sort' => 100,
    'status' => 1,
    'child' => [
      [
        'title' => lang('会员管理'),
        'icon' => 'fa fa-fw fa-folder-open-o',
        'url_value' => '',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 100,
        'status' => 1,
        'child' => [
          [
            'title' => lang('会员列表'),
            'icon' => 'fa fa-fw fa-list',
            'url_value' => 'user/index/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 100,
            'status' => 1,
            'child' => [
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'user/index/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'user/index/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('启用'),
                'icon' => '',
                'url_value' => 'user/index/enable',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('禁用'),
                'icon' => '',
                'url_value' => 'user/index/disable',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
            ],
          ],
          [
            'title' => lang('会员等级'),
            'icon' => 'fa fa-fw fa-list-ol',
            'url_value' => 'user/level/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 100,
            'status' => 1,
            'child' => [
              [
                'title' => lang('新增'),
                'icon' => '',
                'url_value' => 'user/level/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'user/level/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'user/level/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('设置状态'),
                'icon' => '',
                'url_value' => 'user/level/setstatus',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
            ],
          ],
          [
            'title' => lang('会员地址'),
            'icon' => 'fa fa-fw fa-map',
            'url_value' => 'user/address/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 100,
            'status' => 1,
            'child' => [
              [
                'title' => lang('地址详情'),
                'icon' => 'fa fa-fw',
                'url_value' => 'user/address/detail',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
            ],
          ],
          [
            'title' => lang('会员标签'),
            'icon' => 'fa fa-fw fa-bookmark-o',
            'url_value' => 'user/label/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 100,
            'status' => 1,
            'child' => [
              [
                'title' => lang('新增'),
                'icon' => '',
                'url_value' => 'user/label/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'user/label/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'user/label/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('设置状态'),
                'icon' => '',
                'url_value' => 'user/label/setstatus',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
            ],
          ],
          [
            'title' => lang('投诉建议'),
            'icon' => 'fa fa-fw fa-sticky-note-o',
            'url_value' => 'user/suggestions/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 100,
            'status' => 1,
            'child' => [
              [
                'title' => lang('新增'),
                'icon' => '',
                'url_value' => 'user/suggestions/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'user/suggestions/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'user/suggestions/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('设置状态'),
                'icon' => '',
                'url_value' => 'user/suggestions/setstatus',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
            ],
          ],
        ],
      ],
      [
        'title' => lang('会员认证'),
        'icon' => 'fa fa-fw fa-odnoklassniki-square',
        'url_value' => '',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 100,
        'status' => 1,
        'child' => [
          [
            'title' => lang('实名认证'),
            'icon' => 'fa fa-fw fa-y-combinator',
            'url_value' => 'user/certified/realname',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 100,
            'status' => 1,
          ],
        ],
      ],
      [
        'title' => lang('财务管理'),
        'icon' => 'fa fa-fw fa-gg-circle',
        'url_value' => '',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 100,
        'status' => 1,
        'child' => [
          [
            'title' => 'VIP'. lang('规则'),
            'icon' => 'fa fa-fw fa-codepen',
            'url_value' => 'user/vip/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 99,
            'status' => 1,
            'child' => [
              [
                'title' => lang('新增'),
                'icon' => '',
                'url_value' => 'user/Vip/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'user/Vip/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'user/Vip/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('设置状态'),
                'icon' => '',
                'url_value' => 'user/Vip/setstatus',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
            ],
          ],
          [
            'title' => lang('充值消费'),
            'icon' => 'fa fa-fw fa-cc-diners-club',
            'url_value' => 'user/finance/money_log',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 100,
            'status' => 1,
            'child' => [
              [
                'title' => lang('虚拟币充值'),
                'icon' => '',
                'url_value' => 'user/finance/virtual_add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 99,
                'status' => 1,
              ],
              [
                'title' => lang('虚拟币记录'),
                'icon' => '',
                'url_value' => 'user/finance/virtual_log',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 99,
                'status' => 1,
              ],
              [
                'title' => lang('手动充值'),
                'icon' => 'fa fa-fw',
                'url_value' => 'user/finance/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
            ],
          ],
          [
            'title' => lang('提现管理'),
            'icon' => 'fa fa-fw fa-diamond',
            'url_value' => 'user/withdraw/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 100,
            'status' => 1,
          ],
          [
            'title' => lang('充值规则'),
            'icon' => 'fa fa-fw fa-list-ul',
            'url_value' => 'user/recharge_rule/index',
            'url_target' => '_self',
            'online_hide' => 0,
            'sort' => 100,
            'status' => 1,
            'child' => [
              [
                'title' => lang('新增'),
                'icon' => '',
                'url_value' => 'user/recharge_rule/add',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('编辑'),
                'icon' => '',
                'url_value' => 'user/recharge_rule/edit',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('删除'),
                'icon' => '',
                'url_value' => 'user/recharge_rule/delete',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
              [
                'title' => lang('设置状态'),
                'icon' => '',
                'url_value' => 'user/recharge_rule/setstatus',
                'url_target' => '_self',
                'online_hide' => 0,
                'sort' => 100,
                'status' => 1,
              ],
            ],
          ],
        ],
      ],
    ],
  ],
];
