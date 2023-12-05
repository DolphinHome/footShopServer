<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


if (!function_exists('user_is_signin')) {
    /**
     * 判断是否登录
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    function user_is_signin()
    {
        $user = session('mall_user_auth');
        if (empty($user)) {
            return 0;
        }else{
            return session('mall_user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
        }
    }
}

if (!function_exists('user_role_auth')) {
    /**
     * 读取当前用户权限
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    function user_role_auth()
    {
        session('user_role_menu_auth', model('user/role')->roleAuth());
    }
}