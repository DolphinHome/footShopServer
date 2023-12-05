<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2018 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

namespace addons\WeChat\controller;
require_once __DIR__ . '/../sdk/include.php';

use WeChat\Contracts\BasicWePay;
use WeChat\Contracts\Tools;
use WeChat\Exceptions\InvalidResponseException;

/**
 * 微信商户账单及评论
 * Class Bill
 * @package WePay
 */
class Bill extends BasicWePay
{
    public $app_config;

    //返回实例本身
    function __construct($config = null)
    {
        $wechat = addons_config('Wechat');
//        echo "<pre>";
//        print_r($wechat);die;

        $config = [
            'appid' => $wechat['mini_appid'],
            'appsecret' => $wechat['mini_appsecret'],
            // 配置商户支付参数（可选，在使用支付功能时需要）
            'mch_id' => $wechat['mchid'],
            'mch_key' => $wechat['key'],
            // 配置商户支付双向证书目录（可选，在使用退款|打款|红包时需要）
            'ssl_key' => '',
            'ssl_cer' => '',
            // 缓存目录配置（可选，需拥有读写权限）
            'cache_path' => '',
        ];
        parent::__construct($config);

        $this->config = $config;
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
            'ssl_key' => '',
            'ssl_cer' => '',
            // 缓存目录配置（可选，需拥有读写权限）
            'cache_path' => '',
        ];
        return $this;
    }

    /**
     * 下载对账单
     * @param array $options 静音参数
     * @param null|string $outType 输出类型
     * @return bool|string
     * @throws InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function download(array $options, $outType = null)
    {
        $this->params->set('sign_type', 'MD5');
        $params = $this->params->merge($options);
        $params['sign'] = $this->_getPaySign($params, 'MD5');
        $result = Tools::post('https://api.mch.weixin.qq.com/pay/downloadbill', Tools::arr2xml($params));
        if (($jsonData = Tools::xml2arr($result))) {
            if ($jsonData['return_code'] !== 'SUCCESS') {
                throw new InvalidResponseException($jsonData['return_msg'], '0');
            }
        }
        return is_null($outType) ? $result : $outType($result);
    }


    /**
     * 拉取订单评价数据
     * @param array $options
     * @return array
     * @throws InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function comment(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/billcommentsp/batchquerycomment';
        return $this->callPostApi($url, $options, true);
    }
}