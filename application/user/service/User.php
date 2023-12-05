<?php
/**
 * 会员服务层
 * @author chenchen
 * @time 2021-4-20 15:32:43
 */
namespace app\user\service;

use think\Db;

class User extends Base
{
    /**
     * 获取分销会员父级id
     * @author chenchen
     * @param $user_id int 会员id （必须）
     * @time 2021年4月20日15:34:49
     */
    public static function parent_dis_id($user_id)
    {
        return Db::name("distribution")->where(['user_id' => $user_id])->value("pid");
    }

}