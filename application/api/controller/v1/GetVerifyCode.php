<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

// 验证码获取和验证接口

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\user\model\User as UserModel;
use service\ApiReturn;
use app\common\model\LogSms;
use app\common\model\EmailSms;
use think\Db;

class GetVerifyCode extends Base
{

    /**
     * 获取验证码
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @param array $data 参数
     * @return json
     */
    public function get_code($data = [], $user = [])
    {
        if (!preg_match("/^1\d{10}$/", $data['mobile']) && $data['type'] != 4 && $data['mobile'] != '') {
            return ApiReturn::r(0, [], lang('请输入正确的手机号格式'));
        }
        if (!preg_match("/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i", $data['user_email']) && $data['user_email'] != '') {
            return ApiReturn::r(0, [], lang('请输入正确的邮箱格式'));
        }
        switch ($data['type']) {
           case 1:
               $userInfo = \think\Db::name('user')->where('mobile', $data['mobile'])->find();
               if ($userInfo) {
                   if ($userInfo['status'] == 0 || $userInfo['is_delete'] == 1) {
                       return ApiReturn::r(0, [], lang('此用户被禁用，请联系客服咨询'));
                   } else {
                       return ApiReturn::r(0, [], lang('手机号已存在，无法注册'));
                   }
               }
               break;
           case 2:
               $userInfo = \think\Db::name('user')->where('mobile', $data['mobile'])->find();
               if (!$userInfo) {
                   return ApiReturn::r(0, [], lang('手机号不存在'));
               }
               if ($userInfo['is_delete'] == 1 || $userInfo['status'] != 1) {
                   return ApiReturn::r(0, [], lang('此用户被禁用，请联系客服咨询'));
               }
               break;
           case 3:
               //如果开启一键登录注册的话，注释掉
               $userInfo = \think\Db::name('user')->where('mobile', $data['mobile'])->find();
              if ($userInfo && ($userInfo['is_delete'] == 1 || $userInfo['status'] == 0)) {
                  return ApiReturn::r(0, [], lang('此用户被禁用，请联系客服咨询'));
              }
               break;
           case 4:
               $user = \app\common\model\Api::get_user_info();
               if (!$user['id']) {
                   return ApiReturn::r(0, [], lang('旧手机获取失败'));
               }
               $data['mobile'] = \think\Db::name('user')->where('id', $user['id'])->value('mobile');
               if (!preg_match("/^1[345678]\d{9}$/", $data['mobile'])) {
                   return ApiReturn::r(0, [], lang('手机号错误'));
               }
               break;
           case 5:
               //新手机号和原手机号相同
               if ($user['mobile'] == $data['mobile']) {
                   return ApiReturn::r(0, [], lang('新手机号和原手机号相同'));
               }
               //检查新手机号是否已经绑定过
               $check = UserModel::where(['mobile' => $data['mobile']])->find();
               if (isset($check["id"]) && $check["id"] != $user["id"]) {
                   return  ApiReturn::r(0, [], lang('该手机号已被绑定'));
               }
               $userInfo = \think\Db::name('user')->where('mobile', $data['mobile'])->find();

               
               break;
          case 6:
               $mobile_count = \think\Db::name('user')->where('mobile', $data['mobile'])->count();
               if (!$mobile_count) {
                   return ApiReturn::r(0, [], lang('手机号不存在'));
               }
               break;
           default:
               break;

       }
        $isTest = $data['is_test']  == 1 ? true : false;
        $phoneVerify = rand(100000, 999999);
        try {
            $type = $data['type'] ? $data['type'] : 0;
            if ($data['mobile']) {
                /*$expiration_time = LogSms::where(['phone'=>$data['mobile'],'type'=>$type])->order('aid DESC')->value('expiration_time');
                if(time() <= $expiration_time){
                  return ApiReturn::r(0, [], '验证码五分钟有效，请勿重复发送！');
                }*/
                $MsgCount = LogSms::where(['phone'=>$data['mobile']])->whereTime('add_time', 'today')->count();
                if ($MsgCount >= module_config('user.smscount')) {
                    return ApiReturn::r(0, [], lang('您今日发送次数已达上限'));
                }
                $logSmsModel = new LogSms();
                //阿里短信
                $logSmsModel->sendDysms($phoneVerify, $data['mobile'], $type, $isTest);
            //互亿短信
              //$logSmsModel->sendHuyi($phoneVerify, $data['mobile'], $type,$isTest);
            } else {
                $EmailSmsModel = new EmailSms();
                //发送邮箱验证码
                $EmailSmsModel->setMyEmail($phoneVerify, $data['user_email'], $type, $isTest);
            }
        } catch (\Exception $e) {
            //$e->getMessage()
            return ApiReturn::r(0, [], lang('验证码发送失败').':'  . $e->getMessage());
        }
        if ($isTest) {
            return ApiReturn::r(1, ['code'=>$phoneVerify], lang('发送成功'));
        }
        return ApiReturn::r(1, [], lang('发送成功'));
    }
}
