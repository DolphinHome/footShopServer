<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\common\model;
use think\Facade\Config;
use think\Facade\Cache;

/**
 * 独立REDIS设置
 * Class Redis
 * @package app\common\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/8 8:42
 */
class Redis {
    /**
     * 创建一个新的Cache对象
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/8 8:42
     * @return \think\cache\Driver
     */
    public static function init(){
        $config = Config::get("redis.");
        return Cache::connect($config);
    }

    /**
     * 获取独立redis句柄
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/8 8:42
     * @return object
     */
    public static function handler(){
        return self::init()->handler();
    }
}

