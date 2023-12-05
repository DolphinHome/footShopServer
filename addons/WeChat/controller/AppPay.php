<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace addons\WeChat\controller;

require_once __DIR__ . '/../sdk/include.php';
/**
 * Class AppPay 微信开放平台支付联调
 * @package plugins\WeChat\controller
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @created 2019/9/9 22:07
 */
class AppPay extends Base {

    public $app_config;

    //返回实例本身
    function __construct($config = null)
    {
        parent::__construct();
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
//        echo "<pre>";
//        print_r($this->config);die;

        $this->app_config = [
            'appid' => $this->config['app_appid'],
            'appsecret' => $this->config['app_appsecret'],
            // 配置商户支付参数（可选，在使用支付功能时需要）
            'mch_id' => $this->config['mchid'],
            'mch_key' => $this->config['key'],
            // 配置商户支付双向证书目录（可选，在使用退款|打款|红包时需要）
            'ssl_key' => get_file_local_path(get_file_url($this->config['sslkey_path'], 1)),
            'ssl_cer' => get_file_local_path(get_file_url($this->config['sslcert_path'], 1)),
            // 缓存目录配置（可选，需拥有读写权限）
            'cache_path'     => '',
        ];
        return $this;
    }

    /**
     * 创建支付一个订单
     * @param $data
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/19 16:40
     */
    function pay($data)
    {
        $body = $data['body'];
        $total_fee = $data['total_fee'];
        $out_trade_no = $data['out_trade_no'];
        //组装订单数据
        $body = preg_replace('/\r\n/', '', $body);
        $body = mb_strlen($body) > 40 ? mb_substr($body, 0, 40, 'utf-8') . '...' : $body;

        $time = time();

        // 创建接口实例
        $wechat = new \WeChat\Pay($this->app_config);

        // 组装参数，可以参考官方商户文档
        $options = [
            'body' => $body,
            'total_fee' => $total_fee * 100,
            'out_trade_no' => $out_trade_no,
            'trade_type' => 'APP',
            'notify_url' => config('web_site_domain').'/index/pay/wxapp_notify'
        ];

        // 创建订单
        $result = $wechat->createOrder($options);

        if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {
            // 返回数据
            $order = new \WePay\Order($this->app_config);
            $pay = $order->appParams($result['prepay_id']);
            return $pay;
        } else {
            return $result;
        }
    }

    //异步回调
    function initWechatPay()
    {
        // 创建接口实例
        $wechat = new \WeChat\Pay($this->app_config);

        // 订单数据处理
        return $wechat;
    }
    function backPay($data)
    {
        $total_fee = floatval($data['total_fee']);
        $refund_fee = floatval($data['refund_fee']);
        $out_trade_no = $data['out_trade_no'];
        $transaction_id = $data['transaction_id'];

        //组装订单数据
        $wechat = new \WeChat\Pay($this->app_config);
        $options = [
            'total_fee' => $total_fee * 100, //订单总金额
            'out_refund_no'=>$data['server_no'],
            'out_trade_no' => $out_trade_no, //商户订单号
            'transaction_id' => $transaction_id, //微信支付单号

            'refund_fee' => $refund_fee * 100,//退款金额
            'refund_desc'=> $data['refund_reason'],//退款理由
            'notify_url' => config('web_site_domain').'/index/pay/wxapp_notify'
        ];
        // 创建退款订单
        $result = $wechat->createRefund($options);
        if ($result['result_code'] == 'SUCCESS') {
            return $result;
        } else {
            return false;
        }
    }

}
