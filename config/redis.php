<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | Redis配置
// +----------------------------------------------------------------------

return [
	// 缓存类型，必须有，主要是使用cache链接，会判断类型
	'type'   => 'redis',
    // 缓存前缀
    'prefix' => 'lbphp_',
    // redis主机
    'host' => '172.30.165.74',
    // redis端口
    'port' => '7076',
    // 数据库编号
    'select' => 12, 
    // redis 密码
    'password' => 'Ab@H&98ab%2024',
    // 缓存有效期 0表示永久缓存
    'expire' => 0,
];
