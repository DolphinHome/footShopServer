<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\controller;

use app\common\controller\Common;


/**
 * 客服后台公共控制器
 * @package app\admin\controller
 */
class Admin extends Common
{
    /**
     * 初始化
     * @author 似水星辰 [2630481389@qq.com]
     */
    protected function initialize()
    {
        parent::initialize();
        // 是否拒绝ie浏览器访问
        if (config('system.deny_ie') && get_browser_type() == 'ie') {
            $this->redirect('admin/ie/index');
        }

        // 判断是否登录，并定义ID常量
        $uid = $this->isLogin();
        //因PHP7缓存问题 使用defined可能造成无法保存
        define('ACCOUNT_ID',$uid);

        // 设置分页参数
        $this->setPageParam();
        $this->assign([
            'socket' => config('socket')
        ]);

    }

    /**
     * 设置分页参数
     * @author 似水星辰 [2630481389@qq.com]
     */
    final protected function setPageParam()
    {
        $list_rows = input('?param.list_rows') ? input('param.list_rows') : config('list_rows');
        config('paginate.list_rows', $list_rows);
        config('paginate.query', input('get.'));
    }

    /**
     * 检查是否登录，没有登录则跳转到登录页面
     * @author 似水星辰 [2630481389@qq.com]
     * @return int
     */
    final protected function isLogin()
    {
        // 判断是否登录
        if ($uid = operation_is_signin()) {
            // 已登录
            return $uid;
        } else {
            // 未登录
            $this->redirect('login/signin');
        }
    }
}
