<?php
return [
    ['type' => 'text', 'name' => 'app_appid', 'title' => 'APP配置-APPID', 'tips' => '同时也是绑定支付的APPID（必须配置，开户邮件中可查看）'],
    ['type' => 'text', 'name' => 'app_appsecret', 'title' => 'APP配置-APPSECRET', 'tips' => '请登录开放平台进行配置'],

    ['type' => 'text', 'name' => 'mini_appid', 'title' => '小程序配置-APPID'],
    ['type' => 'text', 'name' => 'mini_appsecret', 'title' => '小程序配置-APPSECRET'],

    ['type' => 'text', 'name' => 'code_appid', 'title' => '公众号配置-APPID', 'tips' => '同时也是绑定支付的APPID（必须配置，开户邮件中可查看）'],
    ['type' => 'text', 'name' => 'code_appsecret', 'title' => '公众号配置-APPSECRET', 'tips' => '仅JSAPI支付的时候需要配置，登录公众平台，进入开发者中心可设置'],
    ['type' => 'text', 'name' => 'code_token', 'title' => '公众号配置-自定义TOKEN', 'tips' => '需要做微信下发通知接口才可以配置'],
    ['type' => 'text', 'name' => 'code_encoding_aes_key', 'title' => '公众号配置-ENCODING_AES_KEY', 'tips' => '需要做微信下发通知接口才可以配置'],

    ['type' => 'text', 'name' => 'web_appid', 'title' => 'WEB配置-APPID', 'tips' => '在开放平台申请网站应用可以获得,用于网页版扫码登录'],
    ['type' => 'text', 'name' => 'web_appsecret', 'title' => 'WEB配置-APPSECRET', 'tips' => '在开放平台申请网站应用可以获得，用于网页版扫码登录'],

    ['type' => 'text', 'name' => 'mchid', 'title' => '商户号MCHID', 'tips' => '必须配置，开户邮件中可查看'],
    ['type' => 'text', 'name' => 'key', 'title' => '商户支付密钥KEY', 'tips' => '参考开户邮件设置（必须配置，登录商户平台自行设置）'],
    ['type' => 'file', 'name' => 'sslcert_path', 'title' => '支付商户证书CERT内容', 'tips' => '仅退款时用'],
    ['type' => 'file', 'name' => 'sslkey_path', 'title' => '支付商户证书KEY内容', 'tips' => '仅退款时用']
];
