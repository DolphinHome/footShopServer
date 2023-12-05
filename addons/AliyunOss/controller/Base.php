<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace addons\AliyunOss\controller;
require ROOT_PATH . 'addons/AliyunOss/SDK/autoload.php';
use app\common\controller\Common;
use OSS\OssClient;
use OSS\Core\OssException;

/**
 * 直接上传一个文件
 * @author 晓风<215628355@qq.com>
 * @package plugins\AliyunLive\controller
 */
class Base extends Common
{	
    public $config;       
    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->config =  addons_config('AliyunOss');
    }
    /**
     * 上传一个本地文件
     * @author 晓风<215628355@qq.com>
     * @param string $path 文件路径 如 ./uploads/temp/1.txt
     * @return array
     * @throws \Exception
     */
    public function uploadPath($path){
        return $this->uploadThinkFile(new \think\File($path));
    }  
    /**
     * 上传一个think文件对象  
     * @author 晓风<215628355@qq.com>
     * @param \think\File $file thinkphp文件对象
     * @return array
     * @throws \Exception
     */
    public function uploadThinkFile(\think\File $file){           
        $path = $file->getPath() .'/'. $file->getBasename();
        $size = $file->getSize();
        if($size < 1){
            throw new \Exception('文件尺寸过小');
        }      
        $md5 = $file->hash("md5");
        $mimeType = $file->getMime();
        $ext = $file->getExtension();
        list($type) = explode("/",$mimeType);
        $newpath = substr_replace($md5,"/",2,0);
        $type = $type == "image" ? "images" : "file";
        $key = "uploads/" .$type . "/" . $newpath .".".$ext;        
        $url = $this->uploadPashToOss($path, $key);        
        //如果是视频，则提取封面
        if($type == "video"){
            $thumb = $url .'?x-oss-process=video/snapshot,t_0,f_jpg,w_0,h_0,m_fast,ar_auto';
        }else if($type == "image"){        
            $thumb = $url . ($this->config['style'] ? "?x-oss-process=" . $this->config['style'] : "");     
        }
        return [
            "md5"=>$md5,
            "sha1" => $file->hash("sha1"),
            "size"=>$size,
            "mime"=>$mimeType,
            "ext"=>$ext,  
            "key"=>$key,
            "path"=> $url,
            "thumb"=>$thumb
        ];
    }  
    /**
     * 上传本地文件文件到OSS
     * @param string $path 本地文件路径
     * @param string $key  阿里云文件KEY
     * @return string 阿里云文件地址
     * @throws \Exception
     */
    public function uploadPashToOss($path,$key){
        $config = $this->config;    
        if ($config['ak'] == '') {
            throw new \Exception('未填写阿里云OSS【AccessKeyId】');
        } 
        if ($config['sk'] == '') {
            throw new \Exception( '未填写阿里云OSS【AccessKeySecret】');
        } 
        if ($config['bucket'] == '') {
            throw new \Exception( '未填写阿里云OSS【Bucket】');
        } 
        if ($config['endpoint'] == '') {
            throw new \Exception( '未填写阿里云OSS【Endpoint】');
        }  
        if ($config['domain'] == '') {
            throw new \Exception( '未填写阿里云OSS访问域名');
        } 
        $config['domain'] = rtrim($config['domain'],'/') . '/';
        // 创建OssClient实例
        try {
            $ossClient = new OssClient($config['ak'], $config['sk'], 'http://' . $config['endpoint']);
        } catch (OssException $e) {
            throw new \Exception($e->getMessage());
        }
        if (is_null($ossClient)) {
            throw new \Exception('OssClient实例创建失败');
        }
        try {
            $ossClient->multiuploadFile($config['bucket'], $key, $path);
        } catch (OssException $e) {
            throw new \Exception($e->getMessage());
        }
        return $config["domain"] . $key;
    }
    /**
     * 获得WEB直传文件签名
     * @param string $fileKey   //文件要保存的目录
     * @param string $callback //回调地址
     * @return type
     * @throws \Exception
     */
    public function getSign($fileKey,$callback = "", $max = 0){
        
        $config = $this->config;    
        if ($config['ak'] == '') {
            throw new \Exception('未填写阿里云OSS【AccessKeyId】');
        } 
        if ($config['sk'] == '') {
            throw new \Exception( '未填写阿里云OSS【AccessKeySecret】');
        }       
        if ($config['domain'] == '') {
            throw new \Exception( '未填写阿里云OSS访问域名');
        } 
        
        $host = rtrim($config['domain'], '/');

        $now = time();
        $expire = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
    
        $expiration = $this->gmt_iso8601($end);

        //最大文件大小.用户可以自己设置
        if(0 === $max){
            $max = 1024 * 1024 * 1024; //M K  B
        }        
        $conditions[] = ['content-length-range',  0, $max];;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        //$start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
        //$conditions[] = $start;
        
        $arr = ['expiration' => $expiration, 'conditions' => $conditions];
        $base64_policy = base64_encode(json_encode($arr)); 
        $signature = base64_encode(hash_hmac('sha1', $base64_policy, $config['sk'], true)); 
        
        $response = array();  
        $response['key'] = $fileKey;
        $response['policy'] = $base64_policy;
        $response['OSSAccessKeyId'] = $config['ak'];
        $response['success_action_status'] = 200;
        $response['signature'] = $signature;
        $response['host'] = $host;
        if($callback){
            $callback = [
                    'callbackUrl'=>  $callback,
                    "callbackBody"=> 'bucket=${bucket}&object=${object}&etag=${etag}&size=${size}&mime=${mimeType}&width=${imageInfo.width}&height=${imageInfo.height}&ext=${imageInfo.format}',		
            ];
            $response['callback'] = base64_encode(json_encode($callback));
        }
        return [
            'host'         =>  $host,
            'aliyunData'   =>  $response,
            "callback"     =>  $callback
        ];
    }
    
    
     public function gmt_iso8601($time)
    {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . "Z";
    }
      
  
}
