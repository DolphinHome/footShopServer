<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\model;

use think\Model as ThinkModel;
use think\helper\Hash;

/**
 * 后台用户模型
 * @package app\admin\model
 */
class Manage extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ADMIN__';

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
}
