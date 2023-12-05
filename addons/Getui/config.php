<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


/**
 * 插件配置信息
 */
return [
    ['type' => 'radio', 'name' => 'status', 'title' => '启用个推', 'tips' => '请在DCLOUD开发者中心获取以下参数https://dev.dcloud.net.cn', 'extra' => ['1' => '开启', '0' => '关闭'], 'value'=>1],
    ['type' => 'text', 'name' => 'AppID', 'title' => 'AppID'],
    ['type' => 'text', 'name' => 'AppSecret', 'title' => 'AppSecret'],
	['type' => 'text', 'name' => 'AppKey', 'title' => 'AppKey'],
    ['type' => 'text', 'name' => 'MasterSecret', 'title' => 'MasterSecret']
];