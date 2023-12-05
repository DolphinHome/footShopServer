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
class Media extends Base
{

    public $mini_config;


    /**
     * 返回实例本身
     * MiniPay constructor.
     * @param null $config
     */
    function __construct($config = null)
    {
        parent::__construct();
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        $this->mini_config = [
            'appid' => $this->config['mini_appid'],
            'appsecret' => $this->config['mini_appsecret'],
            // 配置商户支付参数（可选，在使用支付功能时需要）
            'mch_id' => $this->config['mchid'],
            'mch_key' => $this->config['key'],
            // 配置商户支付双向证书目录（可选，在使用退款|打款|红包时需要）
            'ssl_key'        => __DIR__ . '/../sdk/cert/apiclient_key.pem',
            'ssl_cer'        => __DIR__ . '/../sdk/cert/apiclient_cert.pem',
            // 缓存目录配置（可选，需拥有读写权限）
            'cache_path'     => '',
        ];
        return $this;
    }
    
    function add($data)
    {
        $wechat = new \WeChat\Media($this->mini_config);
        return $wechat->add($data);
    }
    
}
