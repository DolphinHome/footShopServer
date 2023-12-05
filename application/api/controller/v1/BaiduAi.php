<?php

// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\admin\admin\Base;
use think\facade\Request;

/**
 * 百度AI相关接口
 * @author 晓风<215628355@qq.com>
 * @date 2020-9-10 14:02:02
 */
class BaiduAi extends Base
{

    /**
     * 查询身份证信息
     * @author 晓风<215628355@qq.com>
     * @return mixed
     */
    public function idcard($data)
    {
        $file = Request::instance()->file("file");
        if (!$file) {
            return ApiReturn::r(0, lang('获取文件信息失败'));
        }
        $mine = $file->getMime();
        list($type) = explode("/", $mine);
        if ($type !== "image") {
            return ApiReturn::r(0, lang('请上传图片文件'));
        }
        $img = file_get_contents($file->getRealPath());
        
        $idCardSide = $data["side"] ==  "back"  ? "back" : "front";
        
        try {
            $result = addons_action('BaiduAi', 'Ocr', 'idcard', [$img, $idCardSide]);
        } catch (\Exception $e) {
            return ApiReturn::r(0, $e->getMessage());
        }
        return ApiReturn::r(1, $result);
    }
    /**
    * 人脸注册
    * @author 晓风<215628355@qq.com>
    * @return mixed
    */
    public function faceAdd($data)
    {
        $file = Request::instance()->file("file");
        if (!$file) {
            return ApiReturn::r(0, lang('获取文件信息失败'));
        }
        $mine = $file->getMime();
        list($type) = explode("/", $mine);
        if ($type !== "image") {
            return ApiReturn::r(0, lang('请上传图片文件'));
        }
        $img = file_get_contents($file->getRealPath());
        $groupId=  !empty($data["groupId"]) ?  $data["groupId"] :   "zhongbenface";
        try {
            $result = addons_action('BaiduAi', 'Face', 'addUser', [ $data["uid"],$data["userInfo"], $groupId, $img,[
                "action_type"=>"replace"
            ]]);
        } catch (\Exception $e) {
            return ApiReturn::r(0, $e->getMessage());
        }
        return ApiReturn::r(1, $result);
    }
    /**
     * 人脸认证
     * @author 晓风<215628355@qq.com>
     * @return mixed
     */
    public function faceVerify($data)
    {
        $file = Request::instance()->file("file");
        if (!$file) {
            return ApiReturn::r(0, lang('获取文件信息失败'));
        }
        $mine = $file->getMime();
        list($type) = explode("/", $mine);
        if ($type !== "image") {
            return ApiReturn::r(0, lang('请上传图片文件'));
        }
        $img = file_get_contents($file->getRealPath());
        $groupId=  !empty($data["groupId"]) ?  $data["groupId"] :   "zhongbenface";
        try {
            $result = addons_action('BaiduAi', 'Face', 'verifyUser', [ $data["uid"], $groupId, $img]);
        } catch (\Exception $e) {
            return ApiReturn::r(0, $e->getMessage());
        }
        return ApiReturn::r(1, $result);
    }
    
    /**
    * 人脸比对
    * @author 晓风<215628355@qq.com>
    * @return mixed
    */
    public function faceMatch($data)
    {
        $file = Request::instance()->file("file");
        if (!$file) {
            return ApiReturn::r(0, lang('获取原文件信息失败'));
        }
        $tofile = Request::instance()->file("tofile");
        if (!$tofile) {
            return ApiReturn::r(0, lang('获取目标文件信息失败'));
        }
        $mine = $file->getMime();
        $mine2 = $tofile->getMime();
        list($type) = explode("/", $mine);
        list($type2) = explode("/", $mine2);
        if ($type !== "image" || $type2 !== "image") {
            return ApiReturn::r(0, lang('请上传图片文件'));
        }
        $img[] = file_get_contents($file->getRealPath());
        $img[] = file_get_contents($tofile->getRealPath());
        try {
            $result = addons_action('BaiduAi', 'Face', 'match', [$img,[
                "image_liveness"=> $data["image_liveness"] ?? "faceliveness,faceliveness", //默认两张都做活体检测
                "types"=> $data['types'] ??  "7,13"     //默认第一张是自拍照，第二张是证件照
            ]]);
        } catch (\Exception $e) {
            return ApiReturn::r(0, $e->getMessage());
        }
        return ApiReturn::r(1, $result);
    }
}
