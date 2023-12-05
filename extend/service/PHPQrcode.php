<?php
namespace service;

use think\Env;

require("./../extend/qrcode/phpqrcode.php");

/**
 * 生成二维码
 */
class PHPQrcode
{
    /**
     * 二维码生成
     */
    public function generateQrcode($request=array())
    {
        // 模拟参数
        $request = array(
            'imgUrl' => 'http://www.baidu.com',
            'params' => array(
                'params' => 'LTy02jzt7G5jWitTIWIga2znpik9uAwi6hz%252B6yI%252ByRM%253D',
                'filePath' => 'partners/bbb/123456',
                //'partner_id' => '123456',
            ),
            'user_id'=>1,
            'goods_id'=>3,
        );
        //二维码生成地址
        $request['filePath'] = ROOT_PATH.'public/qrcode/';

        // 检测参数
        if(empty($request['imgUrl']) || !filter_var($request['imgUrl'],FILTER_VALIDATE_URL)){
            return false;
        }

        // url处理
        $content = $request['imgUrl'];
        if(!empty($request['params'])){
            $content = $request['imgUrl'] . "?" . http_build_query($request['params']);
        }
//        \think\Log::write($content, date('Y年m月d日 H:i:s').'参数', true, 'Content/');

         $filename = $request['user_id'].'-'.$request['goods_id'].'.png';

        \QrCode::png($content, $request['filePath'].$filename, 'L', '6'); // 生成二维码内容

        return true;
        // $codeUpload  = json_decode(action('channel/Qiniu/uploads',['request'=>[
        //     'filePath' => file_get_contents($filename),
        //     'uploadsImgType' => 'BASE64', // 上传图片类型（URL:URL类型上传 BASE64:BASE64类型上传）*
        //     'ext' => 'png', // 文件后缀(BASE64类型必传)
        //     'fileName' => $request['params']['fillePath'].'qrcode'.$request['params']['partner_id'],
        // ]]), true);
        // if($codeUpload['status'] == '2000'){
        //     // unlink($filename);
        //     return $this->parse_data();
        // }else{
        //     return $this->parse_data('5000','失败！');
        // }
    }

    /**
     * 带logo的二维码生成
     */
    public function generateQrcodeLogo($request=array())
    {
        // 模拟参数
/*         $request = array(
             'imgUrl' => 'http://www.chaojizhangdan.com',
        'apiHost' => 'http://xinyong.chaojizhangdan.com',
             'params' => array(
                 'username' => 'zneghu',
                 'password' => '1231434',
             ),
         );*/
//        $request = request()->param();
        // 检测参数
        // 检测参数 @get_headers($request['imgUrl'])
/*        if(empty($request['imgUrl']) || !filter_var($request['imgUrl'],FILTER_VALIDATE_URL)){
            return false;
            return $this->parse_data('5000','URL参数错误，不存在或者未书写全，支持http|https!');
        }*/

        // url处理
        $content = $request['imgUrl'];
/*        if(!empty($request['params'])){
            $request['params']['params'] = urlencode($request['params']['params']);
            $params = $this->opensslEncrypt($request['params'],'',$request['params']['strs']); // 加密
            $content = $request['imgUrl'] . "?params=" . $params;
        }*/
        // 创建生成二维码对象
        $filename = \Env::get("runtime_path") . 'qrcode/';
        if(!is_dir($filename)) mkdir($filename, 0777, true);
        if (!is_readable($filename)) chmod($filename, 0777);
        $filename = $filename . rand(10000,99999) .'.png';  // 生成二维码存储位置
        \QrCode::png($content, $filename, 'H', '6'); // 生成二维码内容
        $logo = ROOT_PATH.'public/qrcode/logo.png'; // 准备好的logo图片public/logo.png
        if(file_exists($logo)){
            $QR = imagecreatefromstring(file_get_contents($filename)); // 目标图象连接资源。
            $logo = imagecreatefromstring(file_get_contents($logo)); // 源图象连接资源。
            $QR_width = imagesx($QR); // 二维码图片宽度
            $QR_height = imagesy($QR); // 二维码图片高度
            $logo_width = imagesx($logo); // logo图片宽度
            $logo_height = imagesy($logo); // logo图片高度
            $logo_qr_width = $QR_width / 4; // 组合之后logo的宽度(占二维码的1/5)
            $scale = $logo_width/$logo_qr_width; // logo的宽度缩放比(本身宽度/组合后的宽度)
            $logo_qr_height = $logo_height/$scale; // 组合之后logo的高度
            $from_width = ($QR_width - $logo_qr_width) / 2; // 组合之后logo左上角所在坐标点

            //重新组合图片并调整大小 将一幅图像(源图象)中的一块正方形区域拷贝到另一个图像中
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
        }

        // 输出图片
//        empty($request['params']['partner_id']) ?  $logofile = false : $logofile = $request['params']['partner_id'].'.png';
        $logofilename = $request['uid'].'-'.$request['activity_id'].'-'.$request['goods_id'].'-'.$request['sku_id'].'.png';

        $logofile = ROOT_PATH.'public/qrcode/'.$logofilename;
        imagepng($QR, $logofile);
        imagedestroy($QR);
        imagedestroy($logo);

        return '/qrcode/'.$logofilename;
        /*        $codeUpload  = json_decode(action('channel/Qiniu/uploads',['request'=>[
                    'filePath' => file_get_contents($logofile),
                    'uploadsImgType' => 'BASE64', // 上传图片类型（URL:URL类型上传 BASE64:BASE64类型上传）*
                    'ext' => 'png', // 文件后缀(BASE64类型必传)
                    'fileName' => $request['params']['fillePath'].'qrcode'.$request['params']['partner_id'],
                ]]), true);*/

        /*        if($codeUpload['status'] == '2000'){
                    unlink($logofile);
                    return $this->parse_data();
                }else{
                    return $this->parse_data('5000','失败！');
                }*/
        // $html =  '<body style="text-align:center"><img src="qrcode.png" /></body>';
        // return $html;
    }

}
