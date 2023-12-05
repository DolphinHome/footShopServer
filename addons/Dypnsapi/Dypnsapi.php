<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 小飞侠 [ 2207524050@qq.com ]
// +----------------------------------------------------------------------
// | 中犇软件 技术一部 出品
// +----------------------------------------------------------------------
namespace addons\Dypnsapi;

use app\common\controller\Addons;

/**
 * 阿里云云通讯认证插件
 * @package Addons\Dypnsapi
 * @author 小飞侠 [ 2207524050@qq.com ]
 */
class Dypnsapi extends Addons
{
    /**
     * @var array 插件信息
     */
    public $info = [
        // 插件名[必填]
        'name'        => 'Dypnsapi',
        // 插件标题[必填]
        'title'       => '手机号一键登录',
        // 插件唯一标识[必填],格式：插件名.开发者标识.addons
        'identifier'  => 'dypnsapi.zbphp.addons',
        // 插件图标[选填]
        'icon'        => 'fa fa-user',
        // 插件描述[选填]
        'description' => '阿里云云通讯认证',
        // 插件作者[必填]
        'author'      => 'lpw',
        // 作者主页[选填]
        'author_url'  => '',
        // 插件版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
        'version'     => '1.0.0',
        // 是否有后台管理功能[选填]
        'admin'       => '0',
    ];

    
    /**
     * 安装方法
     * @return bool
     */
    public function install(){
        return true;
    }

    /**
     * 卸载方法必
     * @return bool
     */
    public function uninstall(){
        return true;
    }
}