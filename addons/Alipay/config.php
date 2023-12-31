<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

// 支付宝配置信息
return [

   	['type' => 'text', 'name' => 'appid', 'title' => '开放平台签约ID', 'tips' => '开放平台签约appid，https://openhome.alipay.com/platform/appManage.htm#/apps'],
	['type' => 'textarea', 'name' => 'public_key', 'title' => '支付宝公钥 RSA2', 'tips' => '去掉空格和换行，去掉开头和结尾的横线注释,请到开放平台生成'],
	['type' => 'textarea', 'name' => 'private_key', 'title' => '开放平台openapi的私钥 RSA2', 'tips' => '去掉空格和换行，去掉开头和结尾的横线注释,请到开放平台生成'],
	['type' => 'text', 'name' => 'notify_url', 'title' => '网页支付 支付异步回调地址', 'tips' => '如果你在程序中设置，此处可不填'],
	['type' => 'text', 'name' => 'return_url', 'title' => '网页支付 支付同步回调地址', 'tips' => '如果你在程序中设置，此处可不填'],	
    ['type' => 'text', 'name' => 'app_notify_url', 'title' => 'APP支付异步回调地址', 'tips' => '如果你在程序中设置，此处可不填'],  	
];
