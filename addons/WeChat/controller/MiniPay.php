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
class MiniPay extends Base
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
            'ssl_key' => get_file_local_path(get_file_url($this->config['sslkey_path'], 1)),
            'ssl_cer' => get_file_local_path(get_file_url($this->config['sslcert_path'], 1)),
            // 缓存目录配置（可选，需拥有读写权限）
            'cache_path' => '',
        ];
        return $this;
    }

    /**
     * 创建支付一个订单
     * @param $data
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/19 16:41
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
        $wechat = new \WeChat\Pay($this->mini_config);

        // 组装参数，可以参考官方商户文档
        $options = [
            'body' => $body,
            'total_fee' => $total_fee * 100,
            'out_trade_no' => $out_trade_no,
            'openid' => $data['xcx_openid'],
            'trade_type' => 'JSAPI',
            'notify_url' => config('web_site_domain') . '/index/pay/mini_notify'
        ];

        // 创建订单
        $result = $wechat->createOrder($options);

        if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {
            // 返回数据
            $order = new \WePay\Order($this->mini_config);
            $pay = $order->jsapiParams($result['prepay_id']);
            return $pay;
        } else {
            return $result;
        }
    }


    /**
     * 查询订单
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/19 16:43
     */
    function queryOrder($transaction_id, $out_trade_no)
    {
        try {
            // 创建接口实例
            $wechat = new \WeChat\Pay($this->mini_config);

            // 组装参数，可以参考官方商户文档
            $options = [
                'transaction_id' => $transaction_id,
                'out_trade_no' => $out_trade_no,
            ];

            // 尝试创建订单
            $result = $wechat->queryOrder($options);

            // 订单数据处理
            return $result;

        } catch (\Exception $e) {

            // 出错啦，处理下吧
            echo $e->getMessage() . PHP_EOL;

        }
    }

    /**
     * 退款
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/19 16:41
     */
    function backPay($data)
    {
        $total_fee = floatval($data['total_fee']);
        $refund_fee = floatval($data['refund_fee']);
        $out_trade_no = $data['out_trade_no'];
        //组装订单数据
        $wechat = new \WeChat\Pay($this->mini_config);
        $options = [
            'total_fee' => $total_fee * 100, //订单总金额
            'out_refund_no' => $data['server_no'],
            'out_trade_no' => $out_trade_no, //商户订单号
            'refund_fee' => $refund_fee * 100,//退款金额
            'refund_desc' => $data['refund_reason'],//退款理由
            'notify_url' => config('web_site_domain') . '/index/pay/mini_notify'
        ];
        // 创建退款订单
        $result = $wechat->createRefund($options);
        return $result;
        // if ($result['result_code'] == 'SUCCESS') {
        //     return $result;
        // } else {
        //     return false;
        // }
    }

    //退款状态查询
    function bcakQueryPay($out_refund_no)
    {

    }

    //同步回调
    function verifyReturn($out_trade_no)
    {


    }

    //异步回调
    function initWechatPay()
    {
        // 创建接口实例
        $wechat = new \WeChat\Pay($this->mini_config);

        // 订单数据处理
        return $wechat;
    }

    //媒体文件鉴黄
    function imgSecCheck($media)
    {
        $WeMini = new \WeMini\Security($this->mini_config);
        return $WeMini->imgSecCheck($media);
    }

    //异步媒体文件鉴黄
    function mediaCheckAsync($url, $media_type)
    {
        $WeMini = new \WeMini\Security($this->mini_config);
        return $WeMini->mediaCheckAsync($url, $media_type);
    }

    //异步文本内容鉴黄
    public function msgSecCheck($content)
    {
        $WeMini = new \WeMini\Security($this->mini_config);
        return $WeMini->msgSecCheck($content);
    }

    //获取直播间信息
    public function getliveinfo($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->getliveinfo($data);
    }

    //创建直播间
    public function createlive($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->createlive($data);
    }

    //编辑直播间
    public function editroom($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->editroom($data);
    }

    //删除直播间
    public function deleteroom($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->deleteroom($data);
    }
    
    //往指定直播间导入已入库的商品
    public function room_add_goods($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->room_add_goods($data);
    }

    //直播间导入的商品上下架
    public function room_goods_onsale($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->room_goods_onsale($data);
    }

    //删除直播间商品
    public function room_goods_delete($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->room_goods_delete($data);
    }

    //推送直播间商品
    public function room_goods_push($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->room_goods_push($data);
    }

    //添加素材
    public function addmedia($data)
    {
        $wechat = new \WeChat\Media($this->mini_config);
        return $wechat->add($data);
    }

    //查询小程序直播成员列表
    public function role_list($data)
    {
        $wechat = new \WeMini\Live($this->mini_config);
        return $wechat->role_list($data);
    }


    //获取微信的AccessToken
    public function getWxAccessToken()
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->getWxAccessToken();
    }

    //消息订阅
    public function subscribe($data)
    {
        $WeMini = new \WeMini\Message($this->mini_config);
        return $WeMini->subscribe($data);
    }

    public function createDefault($data)
    {
        $WeMini = new \WeMini\Qrcode($this->mini_config);
        return $WeMini->createDefault($data);
    }


    /**
     * Notes: 添加直播间商品并提交审核
     * User: chenchen
     * Date: 2021/7/6
     * Time: 14:13
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function add_goods($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->add_goods($data);
    }

    /**
     * Notes: 撤回直播商品的提审申请
     * User: chenchen
     * Date: 2021/7/6
     * Time: 14:14
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function goods_reset_audit($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->goods_reset_audit($data);
    }

    /**
     * Notes: 已撤回提审的商品再次发起提审申请
     * User: chenchen
     * Date: 2021/7/6
     * Time: 14:15
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function goods_audit($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->goods_audit($data);
    }

    /**
     * Notes: 删除直播间商品
     * User: chenchen
     * Date: 2021/7/6
     * Time: 14:15
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function del_goods($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->del_goods($data);
    }


    /**
     * Notes: 更新直播间商品
     * User: chenchen
     * Date: 2021/7/6
     * Time: 14:16
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function update_goods($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->update_goods($data);
    }


    /**
     * Notes: 获取直播间商品的信息与审核状态
     * User: chenchen
     * Date: 2021/7/6
     * Time: 14:17
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function get_goods_warehouse($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->get_goods_warehouse($data);
    }

    /**
     * Notes: 获取直播间商品列表
     * User: chenchen
     * Date: 2021/7/6
     * Time: 14:17
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function get_goods_approved($data)
    {
        $WeMini = new \WeMini\Live($this->mini_config);
        return $WeMini->get_goods_approved($data);
    }



}
