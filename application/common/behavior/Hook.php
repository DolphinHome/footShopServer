<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\common\behavior;

use app\admin\model\Hook as HookModel;
use app\admin\model\HookAddons;
use app\admin\model\Addons;

/**
 * 注册钩子
 * @package app\common\behavior
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Hook
{
    /**
     * 执行行为 run方法是Behavior唯一的接口
     * @access public
     * @return void
     */
    public function run()
    {
		// 安装操作直接return
        if (defined('INSTALL_ENTRANCE')) return;

        $hook_addons  = cache('hook_addons');
        $hooks        = cache('hooks');
        $addons      = cache('addons');

        if (!$hook_addons) {
            // 所有钩子
            $hooks = HookModel::where('status', 1)->column('status', 'name');
            // 所有插件
            $addons = Addons::where('status', 1)->column('status', 'name');
            // 钩子对应的插件
            $hook_addons = HookAddons::where('status', 1)->order('hook,sort')->select();
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('hook_addons', $hook_addons);
                cache('hooks', $hooks);
                cache('addons', $addons);
            }
        }

        if ($hook_addons) {
            foreach ($hook_addons as $value) {
                if (isset($hooks[$value['hook']]) && isset($addons[$value['plugin']])) {
                    \think\facade\Hook::add($value['hook'], get_addons_class($value['plugin']));
                }
            }
        }
    }
}
