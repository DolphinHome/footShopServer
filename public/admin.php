<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


// [ 后台入口文件 ]
namespace think;

// 定义应用目录
use think\facade\Request;

define('TEMPLETE_PATH', __DIR__ . '/../public/theme/');
define('LOG_PATH', __DIR__ . '/../runtime/log/');
define('CACHE_PATH', __DIR__ . '/../runtime/cache/');
define('TEMP_PATH', __DIR__ . '/../runtime/temp/');
define('ROOT_PATH', __DIR__ . '/../');
define('APP_PATH', ROOT_PATH . 'application/');

// 定义为后台入口
define('ENTRANCE', 'admin');

// if (!extension_loaded('beast')) {
//     exit('请先安装beast扩展');
// } elseif (!beast_file_expire(APP_PATH . 'common.php')) {
//     exit('加密文件已过期，请联系管理人员！');
// }

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';
// 检查是否安装
if (!is_file('./../data/install.lock')) {
    header('location: /');
} else {
    // 执行应用并响应
    Container::get('app')->run()->send();
}
