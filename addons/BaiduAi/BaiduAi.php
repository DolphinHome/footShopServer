<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace addons\baiduai;
use app\common\controller\Addons;

/**
 * 百度人工智能插件
 * @package plugins\baiduai
 * @author 晓风<215628355@qq.com>
 */
class BaiduAi extends Addons
{
    /**
     * @var array 插件信息
     */
    public $info = [
        // 插件名[必填]
        'name'        => 'BaiduAi',
        // 插件标题[必填]
        'title'       => '百度人工智能',
        // 插件唯一标识[必填],格式：插件名.开发者标识.addons
        'identifier'  => 'BaiduAi.xiaofeng.addons',
        // 插件图标[选填]
        'icon'        => 'fa fa-fw fa-plug',
        // 插件描述[选填]
        'description' => '百度人工智能插件',
        // 插件作者[必填]
        'author'      => '晓风',
        // 作者主页[选填]
        'author_url'  => 'javascript:;',
        // 插件版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
        'version'     => '1.0.0',
        // 是否有后台管理功能[选填]
        'admin'       => '0',
    ];

    /**
     * @var array 插件钩子
     */
    public $hooks = [];

	public static function run()
    {

    }

	/**
     * 安装方法
     * @author 晓风<215628355@qq.com>
     * @return bool
     */
    public function install(){
        return true;
    }

    /**
     * 卸载方法必
     * @author 晓风<215628355@qq.com>
     * @return bool
     */
    public function uninstall(){
        return true;
    }
}
