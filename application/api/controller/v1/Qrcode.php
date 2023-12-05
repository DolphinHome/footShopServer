<?php
/*
 * @Descripttion:
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-29 10:24:44
 */

namespace app\api\controller\v1;

use service\ApiReturn;
use app\api\controller\Base;
use think\Db;

require_once("./../extend/qrcode/phpqrcode.php");

class Qrcode extends Base
{

    /*
     * 生成二维码
     */
    public function index($data = [], $user = [])
    {
        $invite_code = Db::name("user_info")->where([
            'user_id' => $user['id']
        ])->value("invite_code");
        $url = config('web_site_domain') . '/h5/registered/index.html?id=' . $invite_code;

        $data = [
            'url' => $url,
            'id' => $invite_code
        ];
        $code_url = $this->generateQrcodeLogo($data);
        if (!$code_url) {
            throw new \Exception(lang('生成分享二维码失败'));
        }
        $info['qrcode_url'] = config('web_site_domain') . $code_url;
        $info['url'] = $url;
        return ApiReturn::r(1, $info, 'ok');
    }
    /**
     * 获取小程序用户分享吗
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     */
    public function qrcode($data = [], $user = []){
        //生成二维码图片
        $look_file = 'health_share/health_'.$data['health_id'].'_qr.png';
        $is_png=file_exists($look_file);
        //判断二维码是否存在
        if(!$is_png){
            $access_token=$this->getAccessToken();
            if(empty($access_token)){
                return ApiReturn::r(0, '', '网络错误，请稍后从试');
            }
            $postArr=array();
//            $invitation_code = db("user")->where(["id" => $user['id']])->value("invitation_code");
            $postArr['scene']=$data['health_id'];
            $postArr['page']="pages/newPages/archives/detail/index";
            $postArr['width']=500;
            $postArr['is_hyaline']=false;
            $url_post="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
            $postJson=json_encode($postArr);
            $qrdatas=$this->api_notice_increment($url_post,$postJson);
            $file = fopen($look_file, "w"); //打开文件准备写入
            fwrite($file, $qrdatas); //写入
            fclose($file); //关闭
        }
        $path =  config('web_site_domain').'/'.$look_file;
        $rs_arr['data']["path"] = $path;
        return ApiReturn::r(1, $rs_arr['data'], '请求成功');
    }
    public function getAccessToken(){
        $wechat = addons_config('Wechat');
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wechat['mini_appid'].'&secret='.$wechat['mini_appsecret'];
        $res = json_decode(file_get_contents($url),true);
        return $res['access_token'];
    }
    function api_notice_increment($url, $data){
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        }else{
            return $tmpInfo;
        }
    }
}
