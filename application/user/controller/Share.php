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
class Share extends Controller
{
    /**
     * 分享页
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $invite_code = input('param.invite_code');
        $this->assign('invite_code', $invite_code);
        return $this->fetch();
    }
}