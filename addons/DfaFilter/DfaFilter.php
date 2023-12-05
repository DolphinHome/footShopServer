<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace addons\DfaFilter;

use app\common\controller\Addons;

/**
 * 系统环境信息插件
 * @package addons\DfaFilter
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class DfaFilter extends Addons
{
    /**
     * @var array 插件信息
     */
    public $info = [
        // 插件名[必填]
        'name'        => 'DfaFilter',
        // 插件标题[必填]
        'title'       => '敏感词过虑',
        // 插件唯一标识[必填],格式：插件名.开发者标识.addons
        'identifier'  => 'DfaFilter.zbphp.addons',
        // 插件图标[选填]
        'icon'        => 'fa fa-user',
        // 插件描述[选填]
        'description' => '敏感词过虑',
        // 插件作者[必填]
        'author'      => '似水星辰',
        // 作者主页[选填]
        'author_url'  => 'javascript:;',
        // 插件版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
        'version'     => '1.0.0',
        // 是否有后台管理功能[选填]
        'admin'       => '0',
    ];


    /**
     * 安装方法
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return bool
     */
    public function install(){
        return true;
    }

    /**
     * 卸载方法
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return bool
     */
    public function uninstall(){
        return true;
    }
}