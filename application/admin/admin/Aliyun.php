<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

/**
 * 阿里云上传专用
 * @package app\admin\admin
 */
class Aliyun extends \think\Controller
{
    
    use \app\common\traits\controller\AliyunUpload;//继承中间件

     /**
     * 阿里云OSS直传服务端签名
     * @return \think\response\Json
     * @author 晓风 <215628355@qq.com>
     */
    public function get_oss_sign()
    {           
        $data = request()->post();
        $token = $data["token"] ??  "";
        $checkToken = session("osstoken");       
        if(!$token || $token !== $checkToken){
            $this->result(null,0,"token不正确","json");          
        }
        
        try{
            $callback = url("admin/aliyun/get_callback",false,null,true);
            $result = $this->getSign($data["filemd5"], $data["filename"], $data["filesize"], $data["mimeType"], $data["duration"], $callback);
        }catch(\Exception $e){
             $this->result(null,0,$e->getMessage(),"json");          
        }
        $this->result( $result['response'],$result["code"],"","json");
    }
    /**
     * 阿里云直传回调
     * @author 晓风 <215628355@qq.com>
     * @return mixed
     */
    public function get_callback() {
        $filemd5 = input("filemd5","");
        if(!$filemd5){
            $this->result(null,0,lang('缺少关键参数'),"json");  
        }
        try{      
            $file = $this->getCallback($filemd5);
        }catch(\Exception $e){
            $this->result(null,0,$e->getMessage(),"json");     
        }
        $this->result( [
            "id"=>$file["id"],
            "name"=>$file["name"],
            "path"=>$file["path"],
            "md5"=>$file["md5"],
            "duration"=>$file["duration"],
        ],1,"","json");    
    }
    
   
}