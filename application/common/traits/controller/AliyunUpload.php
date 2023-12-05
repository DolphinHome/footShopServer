<?php

/**
 * 用法：
 *
 * class index
 * {
 *     use \app\common\traits\controller\AliyunUpload;
 *     public function index(){
 * 
 *        
 *         $this->saveFile( ... );
 *     }
 * }
 */
/**
 * 阿里云直传中间件
 * @author 晓风<215628355@qq.com>
 * 优化并简化了上传流程
 * 抽出来的中间件 可以在其他模块内使用了
 */

namespace app\common\traits\controller;
use app\admin\model\Upload;
trait AliyunUpload {
    
    /**     
     * 在地址前加上域名     
     * 解决本地驱动上传的半路径问题
     * @author 晓风<215628355@qq.com>
     * @param string $path
     * @return string
     */
    private function _getFileUrl($path){
          //在地址前加上域名          
        $parse_url = parse_url($path);
        if ($path && empty($parse_url['scheme'])) {
            return config('web_site_domain') . '/' . ltrim($path, "/");
        } 
        return $path;
    }
    /**     
     * 阿里云OSS直传服务端签名
     * @return \think\response\Json
     * @author 晓风<215628355@qq.com>
     * @param string $fileMd5 md5信息
     * @param string $fileName 文件名
     * @param string $fileSize 文件大小
     * @param string $mimeType 文件mime
     * @param int $duration  音视频时长
     * @param string $callback 回调地址（外网可访问，不可带get参数）
     * @return type
     * @throws Exception
     */
    protected function getSign($fileMd5,$fileName,$fileSize,$mimeType,$duration = 0 ,$callback = "" )
    {        
        if (!preg_match("/^[a-fA-F0-9]{32}$/", $fileMd5)) {
            throw new \Exception("filemd5参数不正确");
        }
        
        $info = Upload::where("md5", $fileMd5)->field("id,path,name,md5,duration")->find();
        if ($info) {
            $info["path"] = $this->_getFileUrl($info["path"]);
            return ["code"=>304,"response"=>$info];
        }
        
        //mimeType检测
        $_ext = \addons\AliyunOss\SDK\FileMimeType::getExt($mimeType);
        if(!$_ext){
            throw new \Exception("文件mimeType设置错误");      
        }
        //获取mimeType前缀
        list($type) = explode("/",$mimeType);
        //扩展名检测
        $ext = strtolower(pathinfo($fileName,PATHINFO_EXTENSION)); 
        $upload_file_ext = explode(",",config("upload_file_ext"));
        $upload_image_ext =explode(",",config("upload_image_ext"));
        //图片
        if($type == "image"  && !in_array($ext,$upload_image_ext)){
            throw new \Exception("上传的文件不在允许上传列中");   
        }
        //非图片
        if($type != "image"  && !in_array($ext,$upload_file_ext)){
             throw new \Exception("上传的文件不在允许上传列中");  
        }    
        //音视频时长检测
        if(($type == 'audio' || $type == 'video') && $duration <=0){
            throw new \Exception("上传的是音视频文件，时长参数(duration)必传");  
        }
        
        //29628196
        $max = 1024 * 1024 * 1024; //M K  B
        if ($fileSize > $max) {
            throw new \Exception('文件尺寸过大，请上传不超过1G的文件');
        }
        
        //拼装文件路径
        $path = $type == "image" ? "images" : "files";
        $path .= "/".substr_replace($fileMd5,"/",2,0);
        $key =  'uploads/'. $path  . '.' . $ext;     
        
        //回调地址  
        if($callback){
            $callback .= "?aliyun=1&filemd5=" . $fileMd5;
        }
        $response =  addons_action("AliyunOss","Base","getSign",[$key,$callback]);
        //如果是视频，则提取封面
        $url = $response['host']."/" . $response['aliyunData']['key'];
        $thumb = "";
        if($type == "video"){
            $thumb = $url .'?x-oss-process=video/snapshot,t_0,f_jpg,w_0,h_0,m_fast,ar_auto';
        }
        if($type == "image"){      
            $style = addons_config('AliyunOss.style');
            $thumb = $url . ($style ? "?x-oss-process=" . $style : "") ;
        }
        $jsonData = \json_encode([
            "uid"       => defined("UID") ? UID : 0 ,
            'name'      => $fileName,
            'md5'       => $fileMd5,
            "key"       =>  $response['aliyunData']['key'],
            'path'      =>  $url,
	        'thumb'     =>  $thumb,
            'ext'       =>  $ext,
            'size'      =>  $fileSize,
            'duration'  =>  $duration,
            'mime'      =>  $mimeType,
        ]);
        
        //使用独立redis缓存      
        //$redis = \app\common\model\Redis::init();
        //$redis->rm("aliyun_ossapi:" . $fileMd5);
        //$redis->set("aliyun_ossapi:" . $fileMd5, $jsonData, 86400);  
        
        //如果未配置redis报NOAUTH Authentication required 错误，请切换为cache缓存
        cache("aliyun_ossapi:" . $fileMd5,null);
        cache("aliyun_ossapi:" . $fileMd5, $jsonData, 86400);           
        
        return ["code"=>200,"response"=>$response];
    }
    /**
     * 获取回调并保存文件
     * @param string $filemd5 文件MD5
     * @author 晓风<215628355@qq.com>
     * @return array
     */  
    protected function getCallback() {
        $filemd5 = input("filemd5","");
        if(!$filemd5){         
            throw new \Exception("缺少关键参数");
        }
        if (!preg_match("/^[a-fA-F0-9]{32}$/", $filemd5)) {
            throw new \Exception("参数不正确");
        }
        
        $file = Upload::where("md5",$filemd5)->find();
        if ($file) {
            // 返回成功信息
            $file["path"] = $this->_getFileUrl($file["path"]);
            return $file;
        }
        //获取独立redis缓存 ,
        //$redis = \app\common\model\Redis::init();
        //$data = $redis->get("aliyun_ossapi:" . $filemd5);
        
        //如果未配置redis请切换为一般缓存
        $data = cache("aliyun_ossapi:" . $filemd5);
        
        if (!$data) {
            throw new \Exception("缓存已失效");
        }        
        
        //删除独立redis缓存 ,
        //$redis->rm("aliyun_ossapi:" . $filemd5);        
         //如果未配置redis请切换为一般缓存
        cache("aliyun_ossapi:" . $filemd5,null);
        
        $data = \json_decode($data, true);     
        
        //'bucket=${bucket}&object=${object}&etag=${etag}&size=${size}&mime=${mimeType}&width=${imageInfo.width}&height=${imageInfo.height}&ext=${imageInfo.format}'
        $width =  input("width/d",0);
        $height = input("height/d",0);
        $size =   input("size/d",0);
        //$ext =    input("ext","");
        $mime =   input("mime","");
        
        $info = [
            'uid'       => $data['uid'],
            'name'      => $data['name'],
            'md5'       => $data['md5'],
            'key'       => $data['key'],
            'path'      => $data['path'],
            'thumb'     => $data['thumb'],
            'ext'       => $data['ext'],//$ext ?: $data['ext'],
            'size'      => $size ?: $data['size'] ,
            'width'     => $width,
            'height'    => $height,
            'mime'      => $mime ?: $data['mime'],
            'duration'  => $data['duration'],
            'module'    => 'api',
            'driver'    => 'aliyunoss',
        ];      
        $file = Upload::create($info);
        if ($file) {
            // 返回成功信息
            return $file;
        }
        throw new \Exception("上传失败");
    }

}
