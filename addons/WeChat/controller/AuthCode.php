<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace addons\WeChat\controller;

use think\File;

require_once __DIR__ . '/../sdk/include.php';

/**
 * Class AuthCode 公众号授权相关基类
 * @package addons\WeChat\controller
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @created 2019/9/9 22:09
 */
class AuthCode extends Base
{

    public $mini_config;
    public $code_config;

    //返回实例本身
    function __construct($config = null)
    {
        parent::__construct();
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->mini_config = [
            'appid' => $this->config['mini_appid'],
            'appsecret' => $this->config['mini_appsecret'],
        ];
        return $this;
    }

    public function get_mini_user($code, $iv, $encryptedData)
    {
        // 实例SDK
        $mini = new \WeMini\Crypt($this->mini_config);
        //解码，获取用户信息
        $data = $mini->userInfo($code, $iv, $encryptedData);
        return $data;
    }

    /**
     * 获取微信小程序二维码
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/23 18:03
     */
    public function get_qrcode($user_id, $path, $width, $auto_color, $line_color, $is_hyaline = false)
    {
        // 实例SDK
        $mini = new \WeMini\Qrcode($this->mini_config);
        $path_url = $path . '?user_id=' . $user_id;
        try {
            $data = $mini->createMiniPath($path_url, $width, $auto_color, $line_color, $is_hyaline);

            $img_dir = ROOT_PATH . '/public/uploads/qrcode/' . date('Y-m-d') . '/';

            \service\File::mk_dir($img_dir);
            $img_name = 'qrcode_' . $user_id . '.png';

            file_put_contents($img_dir . '/' . $img_name, $data);

            return config('web_site_domain') . '/uploads/qrcode/' . date('Y-m-d') . '/' . $img_name;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取微信小程序临时二维码
     * @author 晓风<215628355@qq.com>
     * @created 2020/8/20 18:03
     * @link:<https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/qr-code/wxacode.getUnlimited.html>
     */
    public function get_qrcode_limit($scene, $page, $width = 430, $auto_color = false, $line_color = ["r" => "0", "g" => "0", "b" => "0"], $is_hyaline = true)
    {
        // 实例SDK
        $mini = new \WeMini\Qrcode($this->mini_config);
        $data = $mini->createMiniScene($scene, $page, $width, $auto_color, $line_color, $is_hyaline);
        //可以自行拼接文件头，这里返回不要改
        //data:image/jpeg;base64,
        return base64_encode($data);
    }

    /**
     * 微信小程序通过code置换session
     * @author chenchen
     * @created 2021年4月20日08:48:56
     */
    public function code2session($code)
    {
        // 实例SDK
        $mini = new \WeMini\Crypt($this->mini_config);
        $data = $mini->session($code);
        return $data;
    }

}
