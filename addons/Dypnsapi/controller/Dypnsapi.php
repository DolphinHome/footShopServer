<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 小飞侠 [ 2207524050@qq.com ]
// +----------------------------------------------------------------------
// | 中犇软件 技术一部 出品
// +----------------------------------------------------------------------

namespace addons\Dypnsapi\controller;

require_once(dirname(dirname(__FILE__))."/sdk/vendor/autoload.php");
use app\common\controller\Common;
use \AlibabaCloud\Client\AlibabaCloud;
use \AlibabaCloud\Client\Exception\ClientException;
use \AlibabaCloud\Client\Exception\ServerException;

/**
 * Dypnsapi控制器
 * @package addons\Dypnsapi\controller
 * @author 小飞侠 <2207524050@qq.com>
 */
class Dypnsapi extends Common
{

    /**
     * 取得Mobile
     * @return Mobile
     */
    public static function GetMobile($access_token) {

         // AccessKeyId
        $accessKeyId = addons_config('Dypnsapi.appkey');

        // AccessKeySecret
        $accessKeySecret = addons_config('Dypnsapi.secret');

        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
                        ->regionId('cn-hangzhou')
                        ->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()
                      ->product('Dypnsapi')
                      ->scheme('https')
                      ->version('2017-05-25')
                      ->action('GetMobile')
                      ->method('POST')
                      ->host('dypnsapi.aliyuncs.com')
                      ->options([
                                    'query' => [
                                      'RegionId' => "cn-hangzhou",
                                      'AccessToken' => $access_token,
                                    ],
                                ])
                      ->request();
                $data = json_decode($result,true);
                if($data['Code']=='OK'){
                    return ['code'=>1,'mobile'=>$data['GetMobileResultDTO']['Mobile']];
                }else{
                    return ['code'=>0,'msg'=>$data['Message']];
                }

        } catch (ClientException $e) {
            return ['code'=>0,'msg'=>$e->getErrorMessage() . PHP_EOL];
        } catch (ServerException $e) {
            return ['code'=>0,'msg'=>$e->getErrorMessage() . PHP_EOL];
        }
        
    }

}