<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

//$goods_list = db('goods')->where('is_delete',0)->column('name,name');
/**
 * 模块信息
 */
return [
    'name' => 'goods',
    'title' => lang('商品'),
    'identifier' => 'goods.zbphp.module',
    'icon' => 'fa fa-fw fa-cubes',
    'description' => lang('商品模块'),
    'author' => lang('似水星辰'),
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
    'tables' => [
        'goods',
        'goods_activity',
        'goods_activity_details',
        'goods_body',
        'goods_brand',
        'goods_category',
        'goods_sku',
        'goods_type',
        'goods_type_attr',
        'goods_type_attribute',
        'goods_type_spec',
        'goods_type_spec_image',
        'goods_type_spec_item',
        'goods_comment',
        'goods_cart',
        'goods_label',
        'goods_express_sender',
        'goods_express_company',
        'goods_freight',
        'goods_freight_rule',
        'order',
        'order_relation',
        'order_action',
        'order_goods_info',
        'order_goods_list',
        'order_goods_express',
        'order_refund',
        'order_remind',
        'refund_reason',
    ],
    'database_prefix' => 'lb_',
    'config' => [
        [
            'type' => 'number',
            'name' => 'order_timeout',
            'title' => lang('订单超时时间'),
            'tips' => lang('请填写整数，单位：分钟'),
            'value' => '',
            '' => '',
        ],
        [
            'type' => 'text',
            'name' => 'order_receive',
            'title' => lang('订单自动收货'),
            'tips' => lang('订单自动收货时间,单位天'),
            'value' => '7',
        ],
//        [
//            'type' => 'text',
//            'name' => 'order_share_val',
//            'title' => lang('订单分享的金额'),
//            'tips' => '',
//            'value' => '0',
//            '' => '',
//        ],
        [
            'type' => 'text',
            'name' => 'refund_day',
            'title' => lang('退款时限'),
            'tips' => lang('商品可退款时限,购买XX天后不可退款,单位天'),
            'value' => '0',
            '' => '',
        ],
//        [
//            'type' => 'select',
//            'name' => 'search_keys',
//            'title' => lang('搜索默认词'),
//            'tips' => lang('搜索时显示的默认词'),
//            'value' => '0',
//            'extra' => $goods_list,
//        ],
//        [
//            'type' => 'text',
//            'name' => 'joinNumber',
//            'title' => lang('拼团人数设置'),
//            'tips' => lang('商城参团人数设置,多个人数逗号分开例如').'2,3,4,5,6,7,8,9,10,11,12,13,14,15',
//            'value' => '2,3,4,5',
//            '' => '',
//        ],
//        [
//            'type' => 'number',
//            'name' => 'join_timeout',
//            'title' => lang('拼团超时自动成团时间'),
//            'tips' => lang('请填写整数，单位：分钟'),
//            'value' => '',
//            '' => '',
//        ],
//
//        [
//            'type' => 'radio',
//            'name' => 'send_status',
//            'title' => lang('自提开关'),
//            'tips' => lang('控制客户端配送方式【用户自提】开启与关闭'),
//            'extra' => [lang('关闭'), lang('开启')],
//            'value' => 0,
//        ],
//        [
//            'type' => 'text',
//            'name' => 'share_money_day',
//            'title' => '分享赚到账时间',
//            'tips' => '分享赚到账时间,单位天',
//            'value' => '0',
//            '' => '',
//        ],
    ],
    'action' => [],
    'access' => [],
];
