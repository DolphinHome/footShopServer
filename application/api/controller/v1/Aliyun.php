<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\api\controller\Base;
use service\ApiReturn;

/**
 * 阿里云系列插件接口控制器
 * @author 似水星辰 [2630481389@qq.com]
 * @package app\api\aliyun
 */
class Aliyun extends Base
{

    use \app\common\traits\controller\AliyunUpload;
    /**
     * 阿里云OSS直传服务端签名
     * @return \think\response\Json
     * @author 晓风 <215628355@qq.com>
     */
    public function get_oss_sign($data= [],$user = [])
    {      
        try{
            $callback = \think\facade\Request::domain() . "/api/v1/5f4365b314957";
            $result = $this->getSign($data["filemd5"], $data["filename"], $data["filesize"], $data["mimeType"], $data["duration"], $callback);
        }catch(\Exception $e){
             return ApiReturn::r(0, '', $e->getMessage());
        }
        return ApiReturn::r($result["code"], $result['response']);
    }
    /**
     * 阿里云直传回调
     * @author 晓风 <215628355@qq.com>
     * @return mixed
     */
    public function get_callback() {
       
        try{      
            $file = $this->getCallback();
        }catch(\Exception $e){
            return ApiReturn::r(0, '', $e->getMessage());
        }
        return ApiReturn::r(1, [
            "id"=>$file["id"],
            "name"=>$file["name"],
            "path"=>$file["path"],
        ]);
    }

}
