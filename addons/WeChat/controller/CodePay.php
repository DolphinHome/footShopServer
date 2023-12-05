<?php
// +----------------------------------------------------------------------
// | ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------
namespace addons\WeChat\controller;
require_once __DIR__ . '/../sdk/include.php';


/**
 * 公众号支付
 * Class CodePay
 * @package addons\WeChat\controller
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @created 2020/9/15 20:52
 */
class CodePay extends Base {

    public $app_config;

    //返回实例本身
    function __construct($config = null)
    {
        parent::__construct();
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        $this->app_config = [
            'appid' => $this->config['code_appid'],
            'appsecret' => $this->config['code_appsecret'],
            // 配置商户支付参数（可选，在使用支付功能时需要）
            'mch_id' => $this->config['mchid'],
            'mch_key' => $this->config['key'],
            // 配置商户支付双向证书目录（可选，在使用退款|打款|红包时需要）
            'ssl_key'        => '',
            'ssl_cer'        => '',
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
            'openid' => $data['wx_openid'],
            'trade_type' => 'JSAPI',
            'notify_url' => config('web_site_domain').'/index/pay/wxcode_notify'
        ];

        // 创建订单
        $result = $wechat->createOrder($options);

        if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {

            // 创建微信端发起支付参数及签名
            $options = $wechat->createParamsForJsApi($result['prepay_id']);

            // 微信端发起支付参数及签名JSON化
            $params = json_encode($options);

            // 返回数据
            return $params;
        } else {
            return $result;
        }
    }

    /**
     * 初始化实例
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/9/15 20:52
     */
    function initWechatPay()
    {
        // 创建接口实例
        $wechat = new \WeChat\Pay($this->app_config);

        // 订单数据处理
        return $wechat;
    }

}
