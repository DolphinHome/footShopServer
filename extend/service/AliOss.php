<?php
namespace service;


require_once "./../extend/ali_oss/autoload.php";
use OSS\OssClient;
use OSS\Core\OssException;
use think\Db;

// 对象存储 OSS
class AliOss
{
    public static function upload($post = [])
    {
        $file = $_FILES['file'];
        $md5 = md5_file($file['tmp_name']);
        $info = Db::name('upload')->where('md5', $md5)->find();
        if ($info) {
            $result = array();
            $result['success'] = '上传成功AliOss';
            $result['data']['file'] = $info['path'];
            $result['data']['fileId'] = $info['id'];
            return $result;
        }

        $accessKeyId = config('alibaba.accessKeyId');
        $accessSecret = config('alibaba.accessSecret');
        $endpoint = config('alibaba.endpoint');
        $bucket = config('alibaba.bucket');

        $path = isset($post['path']) ? $post['path'] : 'uploads';
        $dir = isset($post['dir']) ? $post['dir'] : 'images';
        $path .= '/' . $dir . '/' . date('Ymd') . '/' . $md5;

        try {
            $ossClient = new OssClient($accessKeyId, $accessSecret, $endpoint);
            return self::uploadFile($ossClient, $bucket, $path, $file, $post);
        } catch (OssException $e) {
            $errorMsg = $e->getMessage();
            return array('error' => $errorMsg);
        }
    }

    // 文件上传
    private static function uploadFile($ossClient, $bucket, $path = 'upload', $file, $post = [])
    {
        $fileName = $file['name'];
        $fileType = strtolower(substr(strrchr($fileName, '.'), 1));
        $object = $path . "." . $fileType;
        $filePath = $file['tmp_name'];
        try {
            $ossClient->uploadFile($bucket, $object, $filePath);
        } catch (OssException $e) {
            return array('error' => '上传失败AliOss');
        }
        $endpoint = 'oss-cn-zhangjiakou.aliyuncs.com';

        $fileName = 'https://' . $bucket . '.' . $endpoint . '/' . $object;

        $uid = isset($post['uid']) ? $post['uid'] : 0;
        if (!$uid && session('admin_auth.uid')) {
            $uid = session('admin_auth.uid');
        }

        // 保存到数据库
        $data = array();
        $data['uid'] = $uid;
        $data['name'] = $file['name'];
        $data['mime'] = $file['type'];
        $data['path'] = $fileName;
        $data['ext'] = self::fileSuffix($fileName);
        $data['size'] = $file['size'];
        $data['md5'] = md5_file($file['tmp_name']);
        $data['sha1'] = sha1_file($file['tmp_name']);
        $data['thumb'] = '';
        $data['module'] = isset($post['module']) ? $post['module'] : 'admin';
        $data['width'] = 0;
        $data['height'] = 0;
        $data['update_time'] = time();
        $data['create_time'] = time();
        $fileId = Db::name('upload')->insertGetId($data);
        if (!$fileId) {
            return array('error' => '保存到数据库失败');
        }

        $result = array();
        $result['success'] = '上传成功AliOss';
        $result['data']['file'] = $fileName;
        $result['data']['fileId'] = $fileId;
        return $result;
    }

    // 获取文件后缀
    public static function fileSuffix($str)
    {
        $arr = explode('.', $str);

        return count($arr) > 0 ? $arr[count($arr) - 1] : '';
    }

    public static function uploadByFile($post = [])
    {
        $file = $post['file'];
//        echo "<pre>";
//        print_r($file);die;
//        $file = $file->getinfo();
        $md5 = md5_file($file['tmp_name']);
        $info = Db::name('upload')->where('md5', $md5)->find();
        if ($info) {
            $result = array();
            $result['success'] = '上传成功AliOss';
            $result['data']['file'] = $info['path'];
            $result['data']['fileId'] = $info['id'];
            return $result;
        }

//        $accessKeyId = config('alibaba.accessKeyId');
//        $accessSecret = config('alibaba.accessSecret');
//        $endpoint = config('alibaba.endpoint');
//        $bucket = config('alibaba.bucket');


        $accessKeyId = 'LTAI4G4URGbMzQVZt2mh267N';
        $accessSecret = 'MxRoU2U2sRT0IZy6DsGj2N2iACm3kG';
        $endpoint = 'oss-cn-zhangjiakou.aliyuncs.com';
        $bucket = 'zhongben-crm';

        $path = isset($post['path']) ? $post['path'] : 'uploads';
        $dir = isset($post['dir']) ? $post['dir'] : 'images';
        $path .= '/' . $dir . '/' . date('Ymd') . '/' . $md5;
        try {
            $ossClient = new OssClient($accessKeyId, $accessSecret, $endpoint);
            return self::uploadFile($ossClient, $bucket, $path, $file, $post);
        } catch (OssException $e) {
            $errorMsg = $e->getMessage();
            return array('error' => $errorMsg);
        }
    }

    /**
     * 文件流上传
     * @param $fileName 文件名
     * @param $filePath 文件绝对路径
     * @author zenghu [1427305236@qq.com]
     * @since 2020年11月24日11:38:55
     */
    public static function uploadFileStream($fileName = '', $filePath = '')
    {
        // 获取配置
        $accessKeyId = config('alibaba.accessKeyId');
        $accessKeySecret = config('alibaba.accessSecret');
        $endpoint = config('alibaba.endpoint');
        $bucket = config('alibaba.bucket');

        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            return self::uploadFile($ossClient, $bucket, 'upload', ['name' => $fileName, 'tmp_name' => $filePath, 'type' => 'image/jpeg', 'size' => '219268']);
        } catch (OssException $e) {
            $errorMsg = $e->getMessage();
            return array('error' => $errorMsg);
        }
    }

}
