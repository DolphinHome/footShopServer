<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\model;

use think\Model as ThinkModel;

/**
 * 附件模型
 * @package app\admin\model
 */
class Upload extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__UPLOAD__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
	
    
    protected $public_path = "/";
    
    /**
     * 在地址前加上域名
     * @param int $path 附件保存在数据库的路径，本地上传的一般是 uploads开头的地址，OSS上传的是http/https开头的地址
     * @param bool $is_default 无图片时是否用默认图片代替,可以传默认图片的名称  放到 public/images目录下 格式为png
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return string
     */
    public function getFileUrl($path, $is_default = true ,$type = 0) {
        //如果图片不存在，判断是否用默认图片
        $domain = \think\Facade\Config::get('web_site_domain');
        $domain = rtrim($domain, '/');
        if (!$path) {
            if(!$is_default){
                return '';
            }          
            $is_default = true=== $is_default ?  'none' : $is_default;       
            return $domain . '/static/admin/images/'.$is_default.'.png';
        }
        //分析图片的URL地址，如果存在scheme协议头则是OSS上传的
        $parse_url = parse_url($path);
        if (!empty($parse_url['scheme'])) {
            return $path;
        }
        //若地址是uploads开头，则添加 PUBLIC_PATH常量
        if ($type == 1 && 0 === strpos($path, "uploads")) {
             return $this->public_path . $path;
        }       
        return $domain. '/' . ltrim($path, "/");
    }
    //获得单个文件路径
    public function getCacheFile($id){    
        return $this->where('id', $id)->cache(86400)->find();
    }
    /**
     * 根据附件id获取路径
     * @param  string|array $id 附件id
     * @param  int $type 类型：0-补全目录，1-直接返回数据库记录的地址
     * @return string|array     路径
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getFilePath($id = '', $type = 0)
    {
        if (is_array($id)) {
            $paths = [];
            foreach ($id as $iid) {
                $file = $this->getCacheFile($iid);
                $path = $file["path"] ?? "";
                $paths[] = $this->getFileUrl($path,false,$type);
            }
            return $paths;
        } 
        $file = $this->getCacheFile($id);
        $path = $file["path"] ?? "";
        return $this->getFileUrl($path,false,$type);
    }

    /**
     * 根据图片id获取缩略图路径，如果缩略图不存在，则返回原图路径
     * @param string $id 图片id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function getThumbPath($id = '')
    {
        $file = $this->getCacheFile($id);
        $path = empty($file["thumb"]) ? ($file["path"] ??  "" ) : $file["thumb"];
        return $this->getFileUrl($path);
    }

    /**
     * 根据附件id获取名称
     * @param  string $id 附件id
     * @return string     名称
     */
    public function getFileName($id = '')
    {
        $file =  $this->getCacheFile($id);
        return $file["name"] ?? "";
    }
    
    //根据IDS获取文件对象键值对
    public function getFileObject($ids,$isDefault = false){
        $ids = is_array($ids) ? $ids : explode(",",$ids);   
        $info = [];
        foreach($ids as $id){
            $file = $this->getCacheFile($id);
            $path = $file["path"] ?? "";
            $row["id"]= $id;
            $row["path"] = $this->getFileUrl($path,$isDefault);
            $info[] = $row;
        }
        return $info;
    }
}
