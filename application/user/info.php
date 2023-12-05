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
    'name' => 'user',
    'title' => lang('会员'),
    'identifier' => 'user.zbphp.module',
    'icon' => 'fa fa-fw fa-user',
    'description' => lang('会员模块'),
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
        'user',
        'user_address',
        'user_certified',
        'user_follow',
        'user_info',
        'user_label',
        'user_level',
        'user_level_votes',
        'user_money_log',
        'user_recharge_rule',
        'user_score_log',
        'user_signin',
        'user_signin_log',
        'user_suggestions',
        'user_vip',
        'user_virtual_log',
        'user_withdraw',
        'user_withdraw_account',
    ],
    'database_prefix' => 'mb_',
    'config' => [
        [
            'type' => 'select',
            'name' => 'user_card',
            'title' => lang('会员卡模板'),
            'extra' => [0=>lang('关闭'),1=>lang('金银铜'),2=>lang('年季月'),3=>lang('终身')],
            'value' => 0,
            'tips' => lang('会员卡模板')
        ],
        [
            'type' => 'number',
            'name' => 'smscount',
            'title' => lang('发送验证码最大次数'),
            'value' => 10,
            'tips' => lang('当天一个手机号最多发送验证码的次数')
        ],
        [
            'type' => 'number',
            'name' => 'pay_count',
            'title' => lang('支付密码错误次数'),
            'value' => 3,
            'tips' => lang('当天输入支付密码错误次数')
        ],
        [
            'type' => 'number',
            'name' => 'login_count',
            'title' => lang('登录密码输入错误次数'),
            'value' => 3,
            'tips' => lang('当天登录密码输入错误次数')
        ],
        [
            'type' => 'number',
            'name' => 'withdraw_count',
            'title' => lang('申请提现次数'),
            'value' => 3,
            'tips' => lang('当天一个账户最多申请提现的次数')
        ],
        [
            'type' => 'radio',
            'name' => 'auto_withdraw',
            'title' => lang('提现转账方式'),
            'extra' => [lang('手动打款'), lang('系统转账')],
            'value' => 0,
            'tips' => lang('手动打款需要前台上传微信支付宝收款二维码，系统打款则需要绑定微信支付宝账号，并开通配置微信支付宝等开放平台的转账功能')
        ],
        [
            'type' => 'text',
            'name' => 'virtual_money',
            'title' => lang('虚拟币和现金兑换比例'),
            'value' => 1,
            'tips' => lang('例如1，代表1:1兑换，例如10，代表10虚拟币兑换1元现金，例如100，代表100虚拟币兑换1元现金,以此类推')
        ],
        [
            'type' => 'number',
            'name' => 'min_withdraw_money',
            'title' => lang('最小提现金额'),
            'value' => 100,
            'tips' => lang('大于此金额才能提现')
        ],
        [
            'type' => 'radio',
            'name' => 'withdraw_handling_type',
            'title' => lang('手续费收取方式'),
            'extra' => [lang('固定金额'), lang('百分比')],
            'value' => 0,
            'tips' => ''
        ],
        [
            'type' => 'number',
            'name' => 'withdraw_handling_fee',
            'title' => lang('手续费'),
            'value' => 2,
            'tips' => lang('请输入整数，根据收取方式决定，例如:输入2，代表2元或者2%')
        ],
        [
            'type' => 'radio',
            'name' => 'is_invite_code',
            'title' => lang('是否开启邀请码'),
            'extra' => [lang('关闭'), lang('开启')],
            'value' => 0,
            'tips' => ''
        ],
        [
            'type' => 'text',
            'name' => 'sign_rule',
            'title' => lang('签到规则'),
            'value' => '1:10;2:20;3:30;4:40;5:50;6:88;7:100',
            'tips' => lang('七天签到规则,例如1:10;2:20;表示连续1天签到获取积分10;表示连续2天签到获取积分20')
        ],

        [
            'type' => 'radio',
            'name' => 'is_commission',
            'title' => lang('是否开启分销佣金'),
            'extra' => [lang('关闭'), lang('开启')],
            'value' => 0,
            'tips' => ''
        ],
//        [
//            'type' => 'text',
//            'name' => 'commission_day',
//            'title' => lang('分销佣金到账时间'),
//            'tips' => '分销佣金到账时间,单位天',
//            'value' => '0',
//            '' => '',
//        ],
        [
            'type' => 'radio',
            'name' => 'commission_type',
            'title' => lang('分销佣金收取方式'),
            'extra' => [lang('固定金额'), lang('百分比')],
            'value' => 0,
            'tips' => ''
        ],
        [
            'type' => 'number',
            'name' => 'commission_first',
            'title' => lang('一级分销佣金'),
            'value' => 2,
            'tips' => lang('请输入整数，根据收取方式决定，例如:输入2，代表2元或者2%')
        ],
        [
            'type' => 'number',
            'name' => 'commission_second',
            'title' => lang('二级分销佣金'),
            'value' => 2,
            'tips' => lang('请输入整数，根据收取方式决定，例如:输入2，代表2元或者2%')
        ],
        [
            'type' => 'wangeditor',
            'name' => 'freeze_money_rule',
            'title' => lang('冻结金额规则'),
            'tips' => lang('冻结金额规则说明'),
            'value' => '',
            '' => '',
        ],
        
        [
            'type' => 'ueditor',
            'name' => 'user_level_rule',
            'title' => lang('会员等级规则'),
            'tips' => lang('会员等级规则说明'),
            'value' => '',
            '' => '',
        ],
    ],
    'action' => [],
    'access' => [],
];
