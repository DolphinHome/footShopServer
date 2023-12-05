<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\common\controller\Common;
use app\admin\model\Upload as UploadModel;

/**
 * 后台公共控制器
 * @package app\admin\admin
 */
class Upload extends Common
{
    
    use \app\common\traits\controller\Upload;//继承中间件
	/**
     * 保存文件
     * @param string $dir 附件存放的目录
     * @param string $from 来源
     * @param string $module 来自哪个模块
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return string|\think\response\Json
     */
    public function save($dir = '', $from = '', $module = 'admin' , $returnarray = false)
    {
        
         set_time_limit(0);
        if ($dir == '') $this->error(lang('没有指定上传目录'));
        if ($from == 'ueditor') return $this->ueditor();
        if ($from == 'jcrop') return $this->jcrop();
        return $this->saveFile($dir, $from, $module);
    }
    
    public function get_ueditor(){
        return $this->ueditor();
    }

    /**
     * 检查附件是否存在
     * @param string $md5 文件md5
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return \think\response\Json
     */
    public function check($md5 = '')
    {
//        $md5 == '' && $this->error(lang('参数错误'));
//
//        // 判断附件是否已存在
//        if ($file_exists = UploadModel::get(['md5' => $md5])) {
//            $file_path = $file_exists['path'];
//            return json([
//                'code'   => 1,
//                'info'   => lang('上传成功'),
//                'class'  => 'success',
//                'id'     => $file_exists['id'],
//                'path'   => $file_path
//            ]);
//        } else {
//            $this->error(lang('文件不存在'));
//        }
        $this->error(lang('文件不存在'));

    }
    
   
}