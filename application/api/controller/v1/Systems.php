<?php
/*
 * @Descripttion:
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-30 10:24:24
 */
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

// 系统公共接口控制器

namespace app\api\controller\v1;

use app\admin\model\VersionApp;
use app\admin\model\VersionLog;
use app\admin\model\Lang as LangModel;
use app\api\controller\Base;
use service\ApiReturn;
use think\Db;

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

    public function get_config($data = [])
    {
        $info = module_config($data['code']);
        return ApiReturn::r(1, $info, lang('请求成功'));
    }
    public function getConfigs($data){
        $info = config($data['config']);
        return ApiReturn::r(1,$info,'获取成功');
    }
    //获取app最新的安装包
    public function get_new_package($data = [])
    {
        $versionlog = VersionApp::where(['status'=>1,'app_ident'=>$data['app_ident']])->field('aid,app_name,app_ident')->select();
        foreach ($versionlog as &$value) {
            $value['version_app'] = VersionLog::where(['vid'=>$value['aid'],'type'=>2,'status'=>1])->field('url')->order('create_time DESC')->find();
        }
        return ApiReturn::r(1, $versionlog, lang('请求成功'));
    }


    /**
     * 获取语言包列表
     * @param {*} $data
     * @param {*} $user
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-26 13:53:28
     */
    public function get_lang_list($data = [], $user = [])
    {
        $langTypeArr = LangModel::langTypeArr();
        $data_list = LangModel::select();
        foreach ($data_list as &$item) {
            $item['upload_id'] = get_file_url($item['upload_id']);
            $item['lang_type_name'] = $langTypeArr[$item['lang_type']];
        }

        return ApiReturn::r(1, $data_list, lang('请求成功'));
    }


    /**
     * 获取自提的开关状态
     * @param {*} $data
     * @param {*} $user
     * @return int 0关闭|1开启
     * @Author: wangph
     * @Date: 2021-04-26 16:44:37
     */
    public function get_send_status($data = [], $user = [])
    {
        $send_status = module_config("goods.send_status");
        return ApiReturn::r(1, ['pickup_status'=>$send_status], lang('请求成功'));
    }

    /**
     * 获取平台基本信息
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_base()
    {
        $info = Db::name('admin_config')->where('group','base')->column('value','name');
        return ApiReturn::r(1, $info, lang('请求成功'));
    }
}
