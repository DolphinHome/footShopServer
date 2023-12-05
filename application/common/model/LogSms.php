<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\common\model;

use think\Model as ThinkModel;

class LogSms extends ThinkModel {

    protected $table = '__ADDONS_SMS_LOG__';


    /**
     * 获取指定手机号今日发送的次数
     * 验证码专用
     * @param $phone
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/11 15:49
     */
    public function getMobileTodayCount($phone) {
        $count = self::where('phone', $phone)->whereTime('add_time', 'today')->where('code', '>', 0)->count();
        //每日每个手机号最多发送5条验证码
        if($count >= 5){
            return false;
        }
        return true;
    }

    /**
     * 插入短信验证码
     * @param $code 验证码
     * @param $phone 手机号
     * @param $type 验证类型
     * @param $content 内容
     * @param int $expiration 过期时间
     * @return false|int
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/7 16:37
     */
    public function add_sms($code, $phone, $type, $content = '',$send_type='', $expiration = 30) {
        if (empty($content)) {
            $content = '您的短信验证码为：' . $code . ',有效期为' . $expiration . '分钟';
        }
        $data = array(
            'code' => $code,
            'phone' => $phone,
            'type' => $type,
            'add_time' => time(),
            'ip' => get_client_ip(),
            'content' => $content,
            'send_type' => $send_type,
            'expiration_time' => time() + $expiration * 60
        );
        //返回业务序号，方便后续接口验证验证码
        return $this->insertGetId($data);
    }
   /**
     * 调用阿里大鱼短信发送验证码
     * @author 晓风<215628355@qq.com>
     * @param int $code 验证码
     * @param int $phone 手机号
     * @param int $type 类型
     * @param bool $isTest 是否为测试模式
     */
    public function sendDysms(int $code, $phone, int $type , $isTest = false){
        try{
            if(!$isTest){
                $count = $this->getMobileTodayCount($phone);
                if($count === false){
                    throw new \Exception(lang('验证码接收量已超上限，请明日再来'));
                }
                $content = addons_action('DySms/DySms/send', [$phone, ['code' => $code, 'type' => $type], '验证码']);
            }
        }catch(\Exception $e){
            //如果未设置测试模型，则抛出异常
            if(!$isTest){
                throw $e;
            }
            $content = "";
        }
        //支持测试模式
        return $this->add_sms($code, $phone, $type, $content, 'dysms', 5);          
    }


    /**
     * 调用互亿无线短信发送验证码
     * @author 晓风<215628355@qq.com>
     * @param int $code 验证码
     * @param int $phone 手机号
     * @param int $type 类型
     * @param bool $isTest 是否为测试模式
     */
    public function sendHuyi(int $code, $phone, int $type , $isTest = false){
        try{
            if(!$isTest){
                $count = $this->getMobileTodayCount($phone);
                if($count === false){
                    throw new \Exception(lang('验证码接收量已超上限，请明日再来'));
                }
                $content = addons_action('Huyi/Huyi/send', [$phone, ['code' => $code, 'type' => $type], '验证码']);
            }
        }catch(\Exception $e){
             //如果未设置测试模型，则抛出异常
            if(!$isTest){
                throw $e;
            }
            $content = "";
        }
        //支持测试模式
        return $this->add_sms($code, $phone, $type, $content, 'hysms', 5);
    }

    /**
     * 判断验证码正确性
     * @param $code 验证码
     * @param $phone 手机号
     * @param string $type 验证类型
     * @param int $code_id 验证码表id
     * @return array|\PDOStatement|string|Model|null
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/7 16:37
     */
    public static function verify_code($code, $phone, $type , $code_id = 0) {
        //设置万能验证码后台配置项 . IOS上架处理 会要万能验证码验证码  
        if (config('is_code') == 1) {
            if ($code == 1111) {
                return true;
            }
        }

        $where = [
            'code' => $code,
            'phone' => $phone,
            'status' => 0
        ];
        if ($type) {
            $where['type'] = $type;
        }
        if ($code_id > 0) {
            $where['aid'] = $code_id;
        }
        $time = time();
        $data = self::where($where)->where('expiration_time','EGT', $time)->order('aid desc')->find();
        if ($data){
            //更改验证码状态
            $res = self::where($where)->update(['status'=>1]);
            if ($res) {
                return $data;
            }
        }
        return false;
    }

}
