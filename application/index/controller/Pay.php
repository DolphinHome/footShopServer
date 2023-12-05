<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\index\controller;

use think\Controller;
use app\common\model\Order;


/**
 * 支付回调
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Pay extends Controller {

    /**
     * 支付宝异步回调
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/12 17:11
     */
    function ali_notify() {
        //注意旧版是Web,新版是Aop
        $alipay = addons_action('Alipay', 'Aop', 'init');
        try {
            $arr = $alipay->verifyNotify();
        } catch (\Exception $e) {
            $alipay->NotifyProcess(false, $e);
        }

        $order_no = $arr['out_trade_no'];
        $res = Order::verify($order_no, 'alipay', $arr['trade_no'], $arr['total_amount']);
        if(!$res){
            $alipay->NotifyProcess(false, 'ERROR');
        }
        $alipay->NotifyProcess(true);
    }

    /**
     * 支付宝同步回调
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    function ali_return() {

        try {
            //注意旧版是Web,新版是Aop
            $alipay = addons_action('Alipay', 'Aop', 'init');
            $arr = $alipay->verifyReturn();
        } catch (\Exception $e) {

            $this->error("支付失败");
        }

        $this->success("支付成功", '/index/recharge');
    }

    /**
     * IOS异步回调
     * @author 晓风<215628355@qq.com>
     */
    function ios_notify() {
        try {
            $ios = addons_action('Iospay', 'App', 'init');
            $arr = $ios->verifyNotify();
        } catch (\Exception $e) {
            $ios->NotifyProcess(0, 'FAIL');
        }
        //商户流程	
        if ($arr['status'] == 0) {
            $order_no = $arr['out_trade_no'];
            $pay_type = $arr['production'] == 1 ? 'appleiap' : 'IosPayTest';
            Order::verify($order_no, $pay_type, $arr['transaction_id']) or $ios->NotifyProcess(0, 'error');
            $ios->NotifyProcess(1, 'success');
        }
        $ios->NotifyProcess(0, 'STATUS FAIL');
    }

    /**
     * 微信小程序异步回调
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/17 21:57
     */
    function mini_notify() {

        try {
            $weChat = addons_action('WeChat', 'MiniPay', 'initWechatPay');
            $arr = $weChat->getNotify();
        } catch (\Exception $e) {
            $str = "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }
        $order_no = $arr['out_trade_no'];
        //商户流程
        if(Order::verify($order_no, 'minipay', $arr['transaction_id'], $arr['total_fee'])){
            $str = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }else{
            $str = "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }
    }


    /**
     * 微信APP异步回调
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/18 18:30
     */
    function wxapp_notify() {
        try {
            $weChat = addons_action('WeChat', 'AppPay', 'initWechatPay');
            $arr = $weChat->getNotify();
        } catch (\Exception $e) {
            $str = "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }
        $order_no = $arr['out_trade_no'];
        //商户流程
        if(Order::verify($order_no, 'wxpay', $arr['transaction_id'], $arr['total_fee'])){
            $str = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }else{
            $str = "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }
    }

    /**
     * 微信APP异步回调
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/18 18:30
     */
    function wxcode_notify() {
        try {
            $weChat = addons_action('WeChat', 'CodePay', 'initWechatPay');
            $arr = $weChat->getNotify();
        } catch (\Exception $e) {
            $str = "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }
        $order_no = $arr['out_trade_no'];
        //商户流程
        if(Order::verify($order_no, 'wxcode', $arr['transaction_id'], $arr['total_fee'])){
            $str = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }else{
            $str = "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }
    }

    /**
     * 微信公众号同步查询
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    function wxcode_return() {
        $order_no = input('order_no', '');
        if (!$order_no) {
            $this->error(lang('订单号错误'));
        }
        try {
            $weChat = addons_action('WeChat', 'CodePay', 'initWechatPay');
            $arr = $weChat->getNotify();
        } catch (\Exception $e) {
            $str = "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
            return $str;
        }

        if(Order::verify($order_no, 'wxcode', $arr['transaction_id'], $arr['total_fee'])){
            $this->success(lang('支付成功'), '', [
                'trade_state' => $arr['trade_state'],
                'trade_state_desc' => $arr['trade_state_desc'],
            ]);
        }
    }


}
