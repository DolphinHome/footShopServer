<?php

namespace addons\Signin;

use app\common\controller\Addons;

/**
 * 签到插件
 */
class Signin extends Addons
{
    /**
     * @var array 插件信息
     */
    public $info = [
        // 插件名[必填]
        'name'        => 'Signin',
        // 插件标题[必填]
        'title'       => '会员签到插件',
        // 插件唯一标识[必填],格式：插件名.开发者标识.addons
        'identifier'  => 'dy_sign.zbphp.addons',
        // 插件图标[选填]
        'icon'        => 'fa fa-map-marker',
        // 插件描述[选填]
        'description' => '签到插件',
        // 插件作者[必填]
        'author'      => '哈哈哈',
        // 作者主页[选填]
        'author_url'  => '',
        // 插件版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
        'version'     => '1.0.0',
        // 是否有后台管理功能[选填]
        'admin'       => '0',
    ];

    /**
     * @var string 原数据库表前缀
     */
    public $database_prefix = 'lb_';

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

//    /**
//     * 会员中心边栏后
//     * @return mixed
//     * @throws \Exception
//     */
//    public function userSidenavAfter()
//    {
//        $request = Request::instance();
//        $controllername = strtolower($request->controller());
//        $actionname = strtolower($request->action());
//        $data = [
//            'actionname'     => $actionname,
//            'controllername' => $controllername
//        ];
//        return $this->fetch('view/hook/user_sidenav_after', $data);
//    }

}
