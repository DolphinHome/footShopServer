<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\common\controller\Common;
use app\admin\model\Login as LoginModel;
use app\admin\model\Role as RoleModel;
use app\admin\model\Menu as MenuModel;

/**
 * 后台登录控制器，不经过权限认证
 * @package app\admin\login
 */
class Login extends Common
{
    /**
     * 用户登录
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function signin()
    {
        if ($this->request->isPost()) {
            // 获取post数据
            $data = $this->request->post();

            // 验证数据
            $result = $this->validate($data, 'Login.signin');
            if(true !== $result){
                // 验证失败 输出错误信息
                $this->error($result);
            }

            // 验证码
            if (config('captcha_signin')) {
                $captcha = $this->request->post('captcha', '');
                if(addons_config('captcha.is_first')==1){
                    $captcha == '' && $this->error(lang('请点击完成验证'));
                    if($captcha != session('CaptchaToken')){
                        dump(session('CaptchaToken'));
                        $this->error(lang('验证码错误或失效'));
                    }
                }else{
                    if(!captcha_check($captcha, 'admin', config('captcha'))){
                        //验证失败
                        $this->error(lang('验证码错误或失效'));
                    };
                }
            }

            // 登录
            $UserModel = new LoginModel;
            $uid = $UserModel->login($data['username'], $data['password']);
            if ($uid) {
                //记录登录行为
                action_log('admin_login_signin', 'admin', $uid, $uid);
                $this->goUrl();
            } else {
                session('CaptchaToken', '');
                $this->error($UserModel->getError());
            }
        } else {

            if (is_signin()) {
                $this->goUrl();
            } else {
                if (!$this->request->isAjax()) {
                    return $this->fetch();
                } else {
                    echo "<script> window.location.href(" . url('signin') . ")</script>";
                }

            }
        }
    }

    /**
     * 跳转到第一个有权限访问的url
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed|string
     */
    private function goUrl()
    {
        if (session('admin_auth.role') == 1) {
            if($this->request->isAjax()){
                $this->success(lang('登录成功'), url('admin/index/index'));
            }
            $this->redirect('admin/index/index');
        }

        $default_module = RoleModel::where('id', session('admin_auth.role'))->value('default_module');
        $menu = MenuModel::get($default_module);
        if (!$menu) {
            $this->error(lang('当前角色未指定默认跳转模块'));
        }


        $menu_url = explode('/', $menu['url_value']);
        role_auth();
        $url = action('admin/menu/getSidebarMenu', ['module_id' => $default_module, 'module' => $menu['module'], 'controller' => $menu_url[1]], 'admin');
        if ($url == '') {
            $this->error(lang('权限不足'));
        } else {
            $this->success(lang('登录成功'), $url);
        }
    }

    /**
     * 退出登录
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function signout()
    {
        $hook_result = \Hook::listen('signout_sso');
        if (!empty($hook_result) && true !== $hook_result[0]) {
            if (isset($hook_result[0]['url'])) {
                $this->redirect($hook_result[0]['url']);
            }
            if (isset($hook_result[0]['error'])) {
                $this->error($hook_result[0]['error']);
            }
        }

        session(null);
        cookie('uid', null);
        cookie('signin_token', null);
        $this->redirect('signin');
    }
}
