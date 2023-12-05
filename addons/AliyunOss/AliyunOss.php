<?php

// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace addons\AliyunOss;

require ROOT_PATH . 'addons/AliyunOss/SDK/autoload.php';

use app\common\controller\Addons;
use think\Db;
use OSS\OssClient;
use OSS\Core\OssException;

/**
 * 阿里云OSS上传插件
 * @package plugins\AliyunOss
 * @author 晓风 <215628355@qq.com>
 */
class AliyunOss extends Addons {

    /**
     * @var array 插件信息
     */
    public $info = [
        // 插件名[必填]
        'name' => 'AliyunOss',
        // 插件标题[必填]
        'title' => '阿里云OSS上传插件',
        // 插件唯一标识[必填],格式：插件名.开发者标识.plugin
        'identifier' => 'aliyun_oss.zbphp.plugin',
        // 插件图标[选填]
        'icon' => 'fa fa-fw fa-upload',
        // 插件描述[选填]
        'description' => '安装后，需将【<a href="/admin.php/admin/system/index/group/upload.html">上传驱动</a>】将其设置为“阿里云OSS”。在附件管理中删除文件，并不会同时删除阿里云OSS上的文件。',
        // 插件作者[必填]
        'author' => '晓风',
        // 作者主页[选填]
        'author_url' => 'javascript:;',
        // 插件版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
        'version' => '1.0.0',
        // 是否有后台管理功能[选填]
        'admin' => '0',
    ];

    /**
     * @var array 插件钩子
     */
    public $hooks = [
        'upload_attachment'
    ];    

    /**
     * 上传附件钩子    
     * @param array $params 参数  
     * @author 晓风<215628355@qq.com>
     * @return mixed
     */
    public function uploadAttachment($params) {
        $file = $params["file"] ?? "";
        $key = $params["key"] ?? "";
        $thumb = $params["thumb"] ?? false;
        $mime = $params["mime"] ?? "";
        if(!$file){
            throw new \Exception('请指定本地文件路径');
        }
        if(!$key){
            throw new \Exception('请指定文件KEY');
        }
        $config = $this->getConfigValue();
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
            $ossClient->multiuploadFile($config['bucket'], $key, $file);
        } catch (OssException $e) {
            throw new \Exception($e->getMessage());
        }
        list($type) = explode("/",$mime);
        $url = $config["domain"] . $key;      
        $thumb_path_name = "";
        if($type == "video"){
            $thumb_path_name = $url .'?x-oss-process=video/snapshot,t_0,f_jpg,w_0,h_0,m_fast,ar_auto';
        }else if($type == "image" || $thumb){        
            $thumb_path_name = $url . ($config['style'] ? "?x-oss-process=" . $config['style'] : "");              
        }            
        return [
            "domain"=>$config["domain"],
            "key"=>$key,
            "url"=>$url,
            "thumb"=>$thumb_path_name
        ];
    }
    /**
     * 安装方法
     * @author 晓风 <215628355@qq.com>
     * @return bool
     */
    public function install() {
     
        $upload_driver = Db::name('admin_config')->where(['name' => 'upload_driver', 'group' => 'upload'])->find();
        if (!$upload_driver) {
            $this->error = '未找到【上传驱动】配置upload_driver';
            return false;
        }
        $options = parse_attr($upload_driver['options']);
        if (isset($options['aliyunoss'])) {
            $this->error = '已存在名为【aliyunoss】的上传驱动';
            return false;
        }
        $upload_driver['options'] .= PHP_EOL . 'aliyunoss:阿里云OSS';

        $result = Db::name('admin_config')
                ->where(['name' => 'upload_driver', 'group' => 'upload'])
                ->setField('options', $upload_driver['options']);

        if (false === $result) {
            $this->error = '上传驱动设置失败';
            return false;
        }
        return true;
    }

    /**
     * 卸载方法
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public function uninstall() {
        $upload_driver = Db::name('admin_config')->where(['name' => 'upload_driver', 'group' => 'upload'])->find();
        if ($upload_driver) {
            $options = parse_attr($upload_driver['options']);
            if (isset($options['aliyunoss'])) {
                unset($options['aliyunoss']);
            }
            $options = implode_attr($options);
            $result = Db::name('admin_config')
                    ->where(['name' => 'upload_driver', 'group' => 'upload'])
                    ->update(['options' => $options, 'value' => 'local']);

            if (false === $result) {
                $this->error = '上传驱动设置失败';
                return false;
            }
        }
        return true;
    }

}
