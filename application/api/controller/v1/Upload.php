<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\api\controller\Base;
use service\ApiReturn;
use app\admin\model\Upload as UploadMore;

use service\AliOss;

/**
 * 图片上传接口
 * @package app\api\controller\v1
 */
class Upload extends Base
{
    use \app\common\traits\controller\Upload;//继承中间件

    /**
     * 上传图片
     * @param $data
     * @param array $user
     * @return \think\response\Json
     * @since 2019/4/20 11:30
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function upload_img($data, $user = [])
    {
//        echo "<pre>";
//        print_r($_FILES);
//        die;
        $dir = $data['dir'] ? $data['dir'] : 'images';
        $from = "";
        $module = $data['module'] ? $data['module'] : 'user';
        $name = 'file';//表单FILE名称
        if (empty($_FILES['file'])) {
            return ApiReturn::r(lang('未获取上传文件，请检查是否开启相册权限'));
        }
        // 临时取消执行时间限制
        set_time_limit(0);
        try {
//            config('upload_image_size', 2048);//限制大小为2M
            $infos = $this->saveFilesTo($dir, $module, $name);
            $arr = [];
            foreach ($infos as $info) {
                $arr[] = [
                    'id' => $info['id'],
                    'path' => $this->_getFileUrl($info['path']),
                    'thumb' => $this->_getFileUrl($info['thumb'])
                ];
            }
        } catch (\Exception $e) {
            return ApiReturn::r(0, [], $e->getMessage());
        }
        return ApiReturn::r(1, $arr, lang('上传成功'));
    }

    /**
     *创建层级目录
     * */
    private function mkdirs_2($path)
    {
        if (!is_dir($path)) {
            self::mkdirs_2(dirname($path));
            if (!mkdir($path, 0777)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 图片流保存文件
     * @param $data
     * @param array $user
     * @return \think\response\Json
     * @since 2020/8/01 11:30
     * @author jxy [ 415782189@qq.com ]
     */
    public function pictureStream($data, $user = [])
    {
        $dir = $data['dir'] ? $data['dir'] : 'qrcode';  // 保存的目录:images
        $data['filename'] = isset($data['filename']) ? $data['filename'] : '';
        $path = ROOT_PATH . config('upload_path') . $dir . '/' . date('Ymd');
        if (!is_dir($path)) {
            self::mkdirs_2($path, 0777);
        }
        $filename = time() . rand(1000, 9999) . ".png";
        $new_file = $path . '/' . $filename;
        if (file_put_contents($new_file, $data['pictureStream'])) {
            return config('web_site_domain') . '/uploads/' . $dir . '/' . date('Ymd') . '/' . $filename;
        } else {
            return false;
        }
    }

    /*
     * 上传文件到oss
     *
     */
    public function uploadFileOss()
    {

//        echo "<pre>";
//        print_r($_FILES);die;
        $res = AliOss::uploadByFile($_FILES);
        return ApiReturn::r(1, $res, 'ok');
    }

    /**
     * 富文本上传视频到oss
     * @edit chenchen
     */
    public function editorVideoOss()
    {

//        echo "<pre>";
//        print_r($_FILES);die;
        $res = AliOss::uploadByFile($_FILES);
        $return = [
            'errno' => 0,
            'data' => [
                'url' => $res['data']['file']
            ]
        ];
        if (isset($res['error'])) {
            $return = [
                'errno' => 1
            ];
        }

        exit(json_encode($return));
    }
}
