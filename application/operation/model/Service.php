<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\model;

use think\Model as ThinkModel;
use think\helper\Hash;

/**
 * 单页模型
 * @package app\operation\model
 */
class Service extends ThinkModel
{
    protected $connection = 'mysql://zb_mkh:jzeTMcLYxY8yshRb@47.92.235.222:3306/zb_mkh#utf8';
    // 设置当前模型对应的完整数据表名称+
    protected $table = '__LB_OPERATION_SERVICE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 对密码进行加密
    public function setPasswordAttr($value)
    {
        return Hash::make((string)$value);
    }

    // 获取注册ip
    public function setSignupIpAttr()
    {
        return get_client_ip(1);
    }

    /**
     * 用户登录
     * @param string $username 用户名
     * @param string $password 密码
     * @param bool $rememberme 记住登录
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return bool|mixed
     */
    public function login($username = '', $password = '', $rememberme = false)
    {
        $username = trim($username);
        $password = trim($password);

        // 匹配登录方式
        if (preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $username)) {
            // 邮箱登录
            $map['email'] = $username;
        } elseif (preg_match("/^1\d{10}$/", $username)) {
            // 手机号登录
            $map['username'] = $username;
        } else {
            // 用户名登录
            $map['username'] = $username;
        }

        $map['status'] = 1;

        // 查找用户
        $user = $this::get($map);
        if (!$user) {
            $this->error = lang('用户不存在或被禁用');
        } else {
            // 检查是否分配用户组
            if ($user['group'] == 0) {
                $this->error = lang('禁止访问，原因：未分配角色');
                return false;
            }

            if (!Hash::check((string)$password, $user['password'])) {
                $this->error = lang('账号或者密码错误');
            } else {
                $uid = $user['id'];

                // 更新登录信息
                $user['last_login_time'] = request()->time();
                $user['last_login_ip']   = get_client_ip(1);
                if ($user->save()) {
                    // 自动登录
                    return $this->autoLogin($this::get($uid), $rememberme);
                } else {
                    // 更新登录信息失败
                    $this->error = lang('登录信息更新失败，请重新登录');
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * 自动登录
     * @param object $user 用户对象
     * @param bool $rememberme 是否记住登录，默认7天
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return bool|int
     */
    public function autoLogin($user)
    {
        // 记录登录SESSION和COOKIES
        $auth = array(
            'uid'             => $user->id,
            'group'           => $user->group,
            'avatar'          => get_file_url($user->avatar),
            'username'        => $user->username,
            'nickname'        => $user->nickname,
            'last_login_time' => $user->last_login_time,
            'last_login_ip'   => get_client_ip(1),
            'partner_id'      => $user->partner_id,
            'service_number'  => $user->service_number,
        );
        session('operation_user_auth', $auth);
        session('operation_user_auth_sign', data_auth_sign($auth));

        return $user->id;
    }

}