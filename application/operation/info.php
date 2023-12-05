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
    'name' => 'operation',
    'title' => lang('运营'),
    'identifier' => 'operation.zbphp.module',
    'icon' => 'fa fa-fw fa-briefcase',
    'description' => lang('运营模块'),
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
        'operation_ads',
        'operation_ads_type',
        'operation_coupon',
        'operation_coupon_record',
        'operation_nav',
        'operation_nav_type',
        'operation_article',
        'operation_article_body',
        'operation_article_column',
        'operation_message',
        'operation_message_push',
        'operation_system_message',
        'operation_system_message_read',
        'operation_service',
        'operation_service_chat',
        'operation_service_data',
        'operation_service_group',
        'operation_service_log',
        'operation_service_now_data',
        'operation_service_reply',
        'operation_service_words'
    ],
    'database_prefix' => 'mb_',
    'config' => [
        [
            'type' => 'wangeditor',
            'name' => 'bargain_rule',
            'title' => lang('砍价规则'),
            'tips' => lang('砍价规则说明'),
            'value' => '',
            '' => '',
        ],
        [
            'type' => 'textarea',
            'name' => 'invoice_rule',
            'title' => lang('开票金额说明'),
            'tips' => lang('开票金额说明'),
            'value' => '',
            '' => '',
        ],
        [
            'type' => 'number',
            'name' => 'max_service',
            'title' => lang('客服最大服务人数'),
            'tips' => lang('客服最大服务人数'),
            'value' => 5,
        ],
        [
            'type' => 'radio',
            'name' => 'change_status',
            'title' => lang('是否启用转接'),
            'tips' => lang('启用转接会自动切换客服'),
            'extra' => [lang('否'), lang('是')],
            'value' => 5,
        ],
        [
            'type' => 'radio',
            'name' => 'is_auto_reply',
            'title' => lang('是否启用自动回复语'),
            'tips' => lang('客户连接客服时自动发送'),
            'extra' => [lang('否'), lang('是')],
            'value' => 5,
        ],
        [
            'type' => 'text',
            'name' => 'auto_reply',
            'title' => lang('自动回复语'),
            'tips' => '',
            'value' => lang('您好，欢迎您咨询问题'),
        ],
        [
            'type' => 'radio',
            'name' => 'suggestions_contact_status',
            'title' => lang('是否启用投诉手机号'),
            'tips' => '',//启用投诉建议联系方式输入 suggestions_contact_status
            'extra' => [lang('否'), lang('是')],
            'value' => 0,
        ],
        [
            'type' => 'radio',
            'name' => 'suggestions_qq_status',
            'title' => lang('是否启用QQ号'),
            'tips' => '',
            'extra' => [lang('否'), lang('是')],
            'value' => 0,
        ],
		[
            'type' => 'radio',
            'name' => 'suggestions_email_status',
            'title' => lang('是否启用邮箱'),
            'tips' => '',
            'extra' => [lang('否'), lang('是')],
            'value' => 0,
        ],
        [
            'type' => 'radio',
            'name' => 'is_must_qq',
            'title' => lang('是否必须QQ号'),
            'tips' => '',
            'extra' => [lang('否'), lang('是')],
            'value' => 0,
        ],
        [
            'type' => 'radio',
            'name' => 'is_must_email',
            'title' => lang('是否必须邮箱'),
            'tips' => '',
            'extra' => [lang('否'), lang('是')],
            'value' => 0,
        ],
        [
            'type' => 'radio',
            'name' => 'is_must_phone',
            'title' => lang('是否必须手机号'),
            'tips' => '',
            'extra' => [lang('否'), lang('是')],
            'value' => 0,
        ],
		[
            'type' => 'radio',
            'name' => 'suggestions_thumb_status',
            'title' => lang('是否启用投诉图片'),
            'tips' => '',//启用投诉建议图片输入 suggestions_thumb_status
            'extra' => [lang('否'), lang('是')],
            'value' => 0,
        ],
    ],
    'action' => [],
    'access' => [],
];
