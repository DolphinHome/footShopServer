<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

// 系统公共接口控制器

namespace app\api\controller\v1;

use app\admin\model\VersionApp;
use app\admin\model\VersionLog;
use app\api\controller\Base;
use service\ApiReturn;

class Systems extends Base
{

    /**
     * 获取系统最新版本
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/8/29 19:09
     */
    public function get_version($data = [])
    {
        //获取app应用标识
        $app_ident = $data['app_ident'];
        $app_info  = VersionApp::field('aid')->where('app_ident', '=', $app_ident)->find();
        if (!$app_info) {
            return ApiReturn::r(0, [], lang('该应用不存在'));
        } else {
            $log_info = VersionLog::where('vid', '=', $app_info['aid'])->order('aid desc')->find();
            if (!$app_info) {
                return ApiReturn::r(0, [], lang('该应用无更新记录'));
            } else {
                $info['is_take'] = 1;
                //判断时间是否到期生效,2为还未到生效时间
                if ($log_info['is_plan'] == 2 && time() < $log_info['plan_time']) {
                    $info['is_take'] = 2;
                }
                //更新包链接
                $info['url'] = $log_info['url'];
                //更新说明
                $info['readme'] = $log_info['readme'];
                //版本号名称，1.2.0
                $info['version_name'] = $log_info['version_name'];
                //版本号标识，120
                $info['version'] = $log_info['version'];
                //更新类型，1热更新，2整包更新
                $info['type'] = $log_info['type'];
                //是否强制更新，1强制，2可跳过，针对整包生效
                $info['is_force'] = $log_info['is_force'];
                return ApiReturn::r(1, $info, lang('请求成功'));
            }
        }
    }
}
