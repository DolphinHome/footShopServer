<?php

/**
 * 用法：
 *
 * class index
 * {
 *     use \app\common\traits\controller\Upload;
 *     public function index(){
 * 
 *        
 *         $this->saveFile( ... );
 *     }
 * }
 */
/**
 * 上传中间件
 * @author 晓风<215628355@qq.com>
 * 优化并简化了上传流程
 * 抽出来的中间件 可以在其他模块内使用了
 * 只是对原方法依样打包，没有修改内容
 */

namespace app\common\traits\controller;

use app\admin\model\Upload as AttachmentModel;
use think\Image;

use think\facade\Hook;
use think\File;
use think\facade\Request;

trait Upload {
    
    protected $resultType = "editor";

    //在地址前加上域名
    //@author 晓风<215628355@qq.com>
    protected function _getFileUrl($file_path) {
        if(!$file_path){
            return "";
        }
        $parse_url = parse_url($file_path);
        if (!empty($parse_url['scheme'])) {
            return $file_path;
        }
        return config('web_site_domain') . $file_path;
    }

     /**
     * 返回正确信息
     * @author 晓风<215628355@qq.com>
     * @param array $info 文件内容
     * @param string $from 来源
     * @return string|\think\response\Json
     */
    protected function resultInfo($info ,$from){
        
         $file_path = $this->_getFileUrl($info['path']);
         
        switch ($from) {
            case 'wangeditor':
                $str = [
                    'errno' => 0,
                    'data' => [
                        $file_path
                    ]
                ];
                return json($str);
            case 'ueditor':
                return json([
                    "state" => "SUCCESS",          // 上传状态，上传成功时必须返回"SUCCESS"
                    "url" => $file_path, // 返回的地址
                    "title" => $info['name'], // 附件名
                ]);
            case 'ueditor_scrawl':
                return json([
                    "state" => "SUCCESS",          // 上传状态，上传成功时必须返回"SUCCESS"
                    "url" => $file_path, // 返回的地址
                    "title" => $info['name'], // 附件名
                ]);
            case 'editormd':
                return json([
                    "success" => 1,
                    "message" => '上传成功',
                    "url" => $file_path,
                ]);
            //case 'ckeditor':
            //    $callback = Request::instance()->get('CKEditorFuncNum');
           //     return ck_js($callback, $file_path);

            default:
                return json([
                    'code' => 1,
                    'info' => '上传成功',
                    'class' => 'success',
                    'id' =>    $info['id'],
                    'path' => $file_path
                ]);
        }
    }
    /**
     * 返回错误信息
     * @author 晓风<215628355@qq.com>
     * @param string $msg 消息内容
     * @param string $from 来源
     * @return string|\think\response\Json
     */
    protected function resultMsg($msg,$from){
        switch ($from) {
            case 'wangeditor':
                return "error|$msg";                   
            case 'ueditor':
                return json(['state' => $msg]);                
            case 'editormd':
                return json(["success" => 0, "message" => $msg]);

            //case 'ckeditor':
            //    $callback = Request::instance()->get('CKEditorFuncNum');
            //    return ck_js($callback, '', $msg);          
            default:
                return json([
                    'code' => 0,
                    'class' => 'danger',
                    'info' => $msg
                ]);
        }
    }
    
    /**
     * 保存附件返回JSON
     * @param string $type 附件类型
     * @param string $from 来源
     * @param string $module 来自哪个模块
     * @param string $name 表单名  
     * @author 晓风<215628355@qq.com>
     * @return string|\think\response\Json
     */
    protected function saveFile($type = '', $from = '', $module = '' , $name="file")
    {      
        try{
            $reuslt = $this->saveFileTo($type, $from , $module , $name);
            return $this->resultInfo($reuslt , $from);
        }catch(\Exception $e){
            return $this->resultMsg($e->getMessage(),$from);   
        }       
    }
    
  

    /**
     * 保存附件返回数组
     * @param string $type 附件类型
     * @param string $from 来源
     * @param string $module 来自哪个模块
     * @param string $name 表单名   
     * @author 晓风<215628355@qq.com>
     * @return array
     * @throws \Exception
     */  
    protected function saveFileTo($type = '', $from = '', $module = '' , $name="file")
    {
         // 获取附件数据     
        switch ($from) {
            case 'editormd':
                $file_input_name = 'editormd-image-file';
                break;
            case 'ckeditor':
                $file_input_name = 'upload';              
                break;
            case 'ueditor_scrawl':
                return $this->saveScrawl();     
            default:
                $file_input_name = $name;
        }    
    
        $file = Request::instance()->file($file_input_name);
        if(!$file){
            throw new \Exception("附件过大");
        }       
        if($file instanceof \think\File){
            return $this->saveFileInfoTo($file, $type , $module , "input");
        }
        throw new \Exception("该方法不支持多文件上传");        
    }
    /**
     * 保存多文件上传
     * @param string $type 附件类型
     * @param string $module 来自哪个模块
     * @param string $name 表单名   
     * @author 晓风<215628355@qq.com>
     * @return array
     * @throws \Exception
     */  
    protected function saveFilesTo($type = '', $module = '' , $name="file")
    {
        $files = Request::instance()->file($name);
        if(!$files){
            throw new \Exception("附件过大");
        }       
        if($files instanceof \think\File){
            $info =  $this->saveFileInfoTo($files, $type , $module , "input");
            return [$info];
        }
        $arr = [] ;
        foreach($files as $file){            
            $arr[] =  $this->saveFileInfoTo($file, $type , $module , "input");
        }
        return $arr;      
    }
     /**
     * 保存文件对象返回数组
     * @param thik\File $file think文件对象
     * @param string $type 类型
     * @param string $module 来自哪个模块
     * @param string $fileType 文件对象类型 input 表单上传对象 file 普通文件对象   
     * @author 晓风<215628355@qq.com>
     * @return array
     * @throws \Exception
     */  
    protected function saveFileInfoTo(File $file, $type = '', $module = '' , $fileType="file")
    {
         // 附件大小限制
        $size_limit = $type == 'images' ? config('upload_image_size') : config('upload_file_size');
        $size_limit = $size_limit * 1024;
        // 附件类型限制
        $ext_limit = $type == 'images' ? config('upload_image_ext') : config('upload_file_ext');
        $ext_limit = $ext_limit  ? explode(",",$ext_limit) : '';

        // 缩略图参数
        $thumb = Request::instance()->post('thumb', '');
        // 水印参数
        $watermark = Request::instance()->post('watermark', '');

        
        /*********************************************/
        //获得文件的基础信息         
        $md5 = $file->hash('md5');   
        $fileMime = $file->getMime();
         //为了节约磁盘空间，采用MD5命名 同名的上传将覆盖，而保存目录则使用固定的mimeType的前缀
        $fileName = $fileType == "input" ? $file->getInfo("name") : $file->getBasename();        
        $fileExt = \addons\AliyunOss\SDK\FileMimeType::getExt($fileMime);      
        if(!$fileExt){
            throw new \Exception("文件mimeType设置错误，不在允许上传列中");
        }
        $fileExt = strtolower(pathinfo($fileName,PATHINFO_EXTENSION)); 
        
        $fileSize = $file->getSize();
        //获得文件KEY                                                    
        list($dir) = explode("/",$fileMime);
        $is_image = $dir == "image";
        $path = substr_replace($md5,"/",2,0);    
        
        $nDir = $dir == "image" ? "images" : "files";
        $key     =  "uploads/" .$nDir . "/" . $path .($fileExt ? ".".$fileExt : ""); 
        $tmpFile =  "uploads/temp/" . $path .($fileExt ? ".".$fileExt : "");  
         /*********************************************/
        
        //若已存在此文件，则直接返回
        $file_exists =  AttachmentModel::where(['md5' => $md5])->find();
        if($file_exists){
            //$saveFile = str_replace('\\', '/',$file->getRealPath()) ;
            //$file = null;                     
            //@unlink($saveFile);          
            return $file_exists->toArray();
        }
       
         // 判断附件大小是否超过限制
        if ($size_limit > 0 && ($fileSize > $size_limit)) {
              throw new \Exception("附件过大");            
        }  
        
         // 判断附件格式是否符合      
        if ($ext_limit == '') {     
            throw new \Exception("获取文件信息失败");      
        }
        if ($fileMime == 'text/x-php' || $fileMime== 'text/html' || $fileExt == "php") {               
            throw new \Exception("禁止上传非法文件"); 
        }     
        if (!in_array($fileExt, $ext_limit)) {  
            throw new \Exception("附件类型不正确");   
        }     
        if ($type == "images" && !$is_image) {       
            throw new \Exception("附件类型不正确"); 
        }
        $sha1 = $file->hash('sha1');
        //若使用第三方上传，将文件先保存到临时文件目录
        if (config('upload_driver') != 'local') { 
            $newkey = $tmpFile;
        }else{
            $newkey = $key;
        }
        
        $saveFile = realpath ( "./" ) . DIRECTORY_SEPARATOR . str_replace('/',DIRECTORY_SEPARATOR,$newkey) ;
        $saveDir  = pathinfo($saveFile,PATHINFO_DIRNAME);
        $saveName = pathinfo($saveFile,PATHINFO_BASENAME);
        
        //若是表单上传文件，则进行移动
        if($fileType == "input"){
            //$tmp_name = $file->getInfo("tmp_name");
            //首先移动到框架目录下
            $move = $file->move($saveDir,$saveName,true);
            if(!$move){
                throw new \Exception($file->getError());
            }   
            $move = $file = null;
       }else{  
           $realPath = $file->getRealPath() ;
           if($saveFile != $realPath){
                //否则copy
                $file = null;
                mkdir($saveDir,0766,true);
                copy($realPath,$saveFile);           
                @unlink($realPath);
           }
       }
      
        //写入到INFO
        $info = [
            "uid"=> defined("UID") ? UID : 0 ,    
            "name"=> $fileName,
            "mime"=> $fileMime,
            'key' => $key,
            'path' => "/".$key,
            'ext' => $fileExt,
            'size' => $fileSize,
            'md5' =>  $md5,
            'sha1' => $sha1,
            'thumb' => "",
            'module' => $module,
            "driver"=> config('upload_driver'),
            'width' => "",
            'height' => "",            
        ];
        //不是图片跳过水印部分    
        if ($type != "images") {
            goto U;
        }

        //取得图片宽高
     
        $img_info = @getimagesize($saveFile);      
        $info["width"] = $img_info[0] ?? 0;
        $info["height"] = $img_info[1] ?? 0;  
        $img_info = null;

         // 水印功能，OSS上传需要先打水印
        if (!$watermark) {
            if (config('upload_thumb_water') == 1 && config('upload_thumb_water_pic') > 0) {
                $this->create_water($saveFile, config('upload_thumb_water_pic'));
            }
        } else {
            if (strtolower($watermark) != 'close') {
                list($watermark_img, $watermark_pos, $watermark_alpha) = explode('|', $watermark);
                $this->create_water($saveFile, $watermark_img, $watermark_pos, $watermark_alpha);
            }
        }   
        U:
        // 附件上传钩子，用于第三方文件上传扩展
        if (config('upload_driver') != 'local') {  
        
            $thumb =  $type == "images" ? $thumb : false;
            $result = Hook::listen('upload_attachment',["file"=>$saveFile,"key"=>$key,"thumb"=> $thumb,"mime"=>$fileMime],true);      
            if(!$result){
                 throw new \Exception("请安装上传插件");
            }            
            $info["path"] = $result["url"];
            $info["thumb"] = $result["thumb"];     
        
            $ret = AttachmentModel::create($info);            
            if(!$ret){
                throw new \Exception("上传附件失败");
            }
            $info["id"] = $ret["id"];  
            $file = $move = null;
            @unlink($saveFile);                 
           return $info;
        }
        //不是图片跳过缩图部分
        if ($type != "images"){
            goto E;
        }      

        // 生成缩略图
        $thumb_path_name = "";
        $thumbDir        =  $saveDir . "/thumb/";
        $thumb_path_name = pathinfo($key,PATHINFO_DIRNAME). "/thumb/"  . $saveName;        
        
        if (!$thumb) {     
            if (config('upload_image_thumb') != '') {
                 $this->create_thumb($saveFile, $thumbDir,$saveName);
                 $info["thumb"]   = "/".$thumb_path_name;
            }
        } else {
            if (strtolower($thumb) != 'close') {
                list($thumb_size, $thumb_type) = explode('|', $thumb);
                $this->create_thumb($saveFile, $thumbDir, $saveName, $thumb_size, $thumb_type);
                $info["thumb"]   = "/".$thumb_path_name;
            }
        }
       
        E:
        $ret = AttachmentModel::create($info);
        if(!$ret){
            throw new \Exception('保存附件失败');          
        }
        $info["id"] = $ret["id"];
        return $info;        
    }
    
    /**
     * 上传本地文件
     * @param type $base64
     */
    protected function uploadPath($path,$module = "user"){       
       
         $file = new File($path);
         return $this->saveFileInfoTo($file,"images",$module,"file");         
    }

    /**
     * 添加水印
     * @param string $file 要添加水印的文件路径
     * @param string $watermark_img 水印图片id
     * @param string $watermark_pos 水印位置
     * @param string $watermark_alpha 水印透明度
     * @author 晓风<215628355@qq.com>
     */
    protected function create_water($file = '', $watermark_img = '', $watermark_pos = '', $watermark_alpha = '') {
        //如果是watermark_img传的是upload表id
        if (is_numeric($watermark_img)) {
            //附件表读取水印图片路径
            $path = AttachmentModel::where('id', $watermark_img)->cache(true)->value('path');
            $thumb_water_pic = realpath(ROOT_PATH . 'public' . $path);
        } else {
            $thumb_water_pic = realpath(ROOT_PATH . 'public/' . $watermark_img);
        }

        if (is_file($thumb_water_pic)) {
            // 读取图片
            $image = Image::open($file);
            // 添加水印
            $watermark_pos = $watermark_pos == '' ? config('upload_thumb_water_position') : $watermark_pos;
            $watermark_alpha = $watermark_alpha == '' ? config('upload_thumb_water_alpha') : $watermark_alpha;
            $image->water($thumb_water_pic, $watermark_pos, $watermark_alpha);
            // 保存水印图片，覆盖原图
            $image->save($file);
        }
    }
    
    /**
     * 创建缩略图
     * @param string $file 目标文件，可以是文件对象或文件路径
     * @param string $dir 保存目录，即目标文件所在的目录
     * @param string $save_name 缩略图名
     * @param string $thumb_size 尺寸
     * @param string $thumb_type 裁剪类型
     * @author 晓风<215628355@qq.com>
     * @return string 缩略图路径
     */
    protected function create_thumb($file = '', $dir = '', $save_name = '', $thumb_size = '', $thumb_type = '')
    {
        // 获取要生成的缩略图最大宽度和高度
        $thumb_size = $thumb_size == '' ? config('upload_image_thumb') : $thumb_size;
        list($thumb_max_width, $thumb_max_height) = explode(',', $thumb_size);
        // 读取图片
        $image = Image::open($file);
        // 生成缩略图
        $thumb_type = $thumb_type == '' ? config('upload_image_thumb_type') : $thumb_type;
        $image->thumb($thumb_max_width, $thumb_max_height, $thumb_type);
      
        // 保存缩略图    
        if (!is_dir($dir)) {
        
            mkdir($dir, 0766, true);
        }
        $image->save($dir . $save_name);   
    }
    
    /**
     * 保存涂鸦（ueditor）
     * @author 晓风<215628355@qq.com>
     * @return \think\response\Json
     */
    protected function saveScrawl()
    {
        $file = Request::instance()->post('file');
        $file_content = base64_decode($file);
        $file_name = md5($file_content) . '.jpg';
        $dir = realpath(ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'temp '. DIRECTORY_SEPARATOR . 'savescrawl' );
        $file_path = $dir . DIRECTORY_SEPARATOR . $file_name;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (false === file_put_contents($file_path, $file_content)) {
            throw new \Exception("涂鸦上传出错");
        }

        return $this->saveFileInfoTo(new File($file_path),"images","","file"); 
    }
    
    /**
     * 处理ueditor上传
     * @author 晓风<215628355@qq.com>
     * @return string|\think\response\Json
     */
    protected function ueditor()
    {
        $action = Request::instance()->get('action');
        $config_file = './static/plugins/ueditor/php/config.json';
        $config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($config_file)), true);
  
        switch ($action) {
            /* 获取配置信息 */
            case 'config':
                $result = $config;
                break;

            /* 上传图片 */
            case 'uploadimage':
                return $this->saveFile('images', 'ueditor');
                break;
            /* 上传涂鸦 */
            case 'uploadscrawl':
                return $this->saveFile('images', 'ueditor_scrawl');
                break;

            /* 上传视频 */
            case 'uploadvideo':
                return $this->saveFile('videos', 'ueditor');
                break;

            /* 上传附件 */
            case 'uploadfile':
                return $this->saveFile('files', 'ueditor');
                break;

            /* 列出图片 */
            case 'listimage':
                return $this->showFile('listimage', $config);
                break;

            /* 列出附件 */
            case 'listfile':
                return $this->showFile('listfile', $config);
                break;

            /* 抓取远程附件 */
//            case 'catchimage':
//                $result = include("action_crawler.php");
//                break;

            default:
                $result = ['state' => '请求地址出错'];
                break;
        }

        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                return htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                return json(['state' => 'callback参数不合法']);
            }
        } else {
            return json($result);
        }
    }

    /**
     * 显示附件列表（ueditor）
     * @param string $type 类型
     * @param $config
     * @author 晓风<215628355@qq.com>
     * @return \think\response\Json
     */
    protected function showFile($type = '', $config)
    {
        /* 判断类型 */
        switch ($type) {
            /* 列出附件 */
            case 'listfile':
                $allowFiles = $config['fileManagerAllowFiles'];
                $listSize = $config['fileManagerListSize'];
                $path = realpath( './uploads/files/');
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowFiles = $config['imageManagerAllowFiles'];
                $listSize = $config['imageManagerListSize'];
                $path = realpath( './uploads/images/');
        }
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $end = $start + $size;

        /* 获取附件列表 */
        $files = $this->getfiles($path, $allowFiles);
        if (!count($files)) {
            return json(array(
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => count($files)
            ));
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--) {
            $list[] = $files[$i];
        }
        //倒序
        //for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
        //    $list[] = $files[$i];
        //}

        /* 返回数据 */
        $result = array(
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        );

        return json($result);
    }

    /**
     * 处理Jcrop图片裁剪
    * @author 晓风<215628355@qq.com>
     */
    protected function jcrop()
    {
        $file_path = Request::instance()->post('path', '');
        $cut_info = Request::instance()->post('cut', '');
        $thumb = Request::instance()->post('thumb', '');
        $watermark = Request::instance()->post('watermark', '');
        $module = Request::instance()->param('module', '');

        // 上传图片
        if ($file_path == '') {
            $file = Request::instance()->file('file');
            if (!is_dir(config('upload_temp_path'))) {
                mkdir(config('upload_temp_path'), 0766, true);
            }
            $info = $file->move(config('upload_temp_path'), $file->hash('md5'));
            if ($info) {
                return json(['code' => 1, 'src' =>  '/uploads/temp/' . $info->getFilename()]);
            } else {
                $this->error('上传失败');
            }
        }

        $file_path = config('upload_temp_path') . str_replace( '/uploads/temp/', '', $file_path);

        if (is_file($file_path)) {
            // 获取裁剪信息
            $cut_info = explode(',', $cut_info);

            // 读取图片
            $image = Image::open($file_path);

            $dir_name = date('Ymd');
            $file_dir = config('upload_path') .  '/images/' . $dir_name . '/';
            if (!is_dir($file_dir)) {
                mkdir($file_dir, 0766, true);
            }
            $file_name = md5(microtime(true)) . '.' . $image->type();
            $new_file_path = $file_dir . $file_name;

            // 裁剪图片
            $image->crop($cut_info[0], $cut_info[1], $cut_info[2], $cut_info[3], $cut_info[4], $cut_info[5])->save($new_file_path);

            // 水印功能
            if ($watermark == '') {
                if (config('upload_thumb_water') == 1 && config('upload_thumb_water_pic') > 0) {
                    $this->create_water($new_file_path, config('upload_thumb_water_pic'));
                }
            } else {
                if (strtolower($watermark) != 'close') {
                    list($watermark_img, $watermark_pos, $watermark_alpha) = explode('|', $watermark);
                    $this->create_water($new_file_path, $watermark_img, $watermark_pos, $watermark_alpha);
                }
            }

            // 是否创建缩略图
            $thumb_path_name = '';
            if ($thumb == '') {
                if (config('upload_image_thumb') != '') {
                    $thumb_path_name = $this->create_thumb($new_file_path, $dir_name, $file_name);
                }
            } else {
                if (strtolower($thumb) != 'close') {
                    list($thumb_size, $thumb_type) = explode('|', $thumb);
                    $thumb_path_name = $this->create_thumb($new_file_path, $dir_name, $file_name, $thumb_size, $thumb_type);
                }
            }

            // 保存图片
            $file = new File($new_file_path);
            $file_info = [
                'uid' => session('user_auth.uid'),
                'name' => $file_name,
                'mime' => $image->mime(),
                'path' => '/uploads/images/' . $dir_name . '/' . $file_name,
                'ext' => $image->type(),
                'size' => $file->getSize(),
                'md5' => $file->hash('md5'),
                'sha1' => $file->hash('sha1'),
                'thumb' => $thumb_path_name,
                'module' => $module,
                'width' => $image->width(),
                'height' => $image->height()
            ];

            if ($file_add = AttachmentModel::create($file_info)) {
                // 删除临时图片
                unlink($file_path);
                // 返回成功信息
                return json([
                    'code' => 1,
                    'id' => $file_add['id'],
                    'src' => $file_info['path'],
                    'thumb' => $thumb_path_name == '' ? '' : "/" . $thumb_path_name,
                ]);
            } else {
                $this->error('上传失败');
            }
        }
        $this->error('文件不存在');
    }
    
    /**
     * 遍历获取目录下的指定类型的附件
     * @param string $path 路径
     * @param string $allowFiles 允许查看的类型
     * @param array $files 文件列表
     * @author 晓风<215628355@qq.com>
     * @return array|null
     */
    protected function getfiles($path = '', $allowFiles = '', &$files = array())
    {
        if (!is_dir($path)) return null;
        if (substr($path, strlen($path) - 1) != '/') $path .= '/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(" . $allowFiles . ")$/i", $file)) {
                        $url =  str_replace("\\", "/", substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])));
                        $files[] = array(
                            'url' => $this->_getFileUrl($url),
                            'mtime' => filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }

}
