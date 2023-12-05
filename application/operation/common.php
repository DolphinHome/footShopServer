<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


/**
 * 获取广告位名称
 * @param $id 广告位id
 * @return void
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
function get_ads_type($id){
    return Db::name('operation_ads_type')->where('id',$id)->value('name');
}

/**
 * 获取导航位名称
 * @param $id 导航位id
 * @return void
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
function get_nav_type($id){
    return Db::name('operation_nav_type')->where('id',$id)->value('name');
}

if (!function_exists('operation_is_signin')) {
    /**
     * 判断是否登录
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    function operation_is_signin()
    {
        $user = session('operation_user_auth');
        if (empty($user)) {
            return 0;
        }else{
            return session('operation_user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
        }
    }
}