<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\controller;

use think\Controller;

/**
 *分享控制器
 * @package app\User\controller
 */
class Menu extends Controller
{
    /**
     * 获取侧栏菜单
     * @param string $module_id 模块id
     * @param string $module 模型名
     * @param string $controller 控制器名
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return string
     */
    public function getSidebarMenu($module_id = '', $module = '', $controller = '')
    {
        $menus = \app\user\model\Menu::getSidebarMenu($module_id, $module, $controller);

        $output = '';
        foreach ($menus as $key => $menu) {
            if (!empty($menu['url_value'])) {
                $output = $menu['url_value'];
                break;
            }
            if (!empty($menu['child'])) {
                $output = $menu['child'][0]['url_value'];
                break;
            }
        }
        return $output;
    }
}