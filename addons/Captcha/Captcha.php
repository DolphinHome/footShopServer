<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace addons\Captcha;

use app\common\controller\Addons;

/**
 * 会员统计
 * @package addons\UserCount
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 */
class Captcha extends Addons
{
    /**
     * @var array 插件信息
     */
    public $info = [
        // 插件名[必填]
        'name'        => 'Captcha',
        // 插件标题[必填]
        'title'       => '验证码',
        // 插件唯一标识[必填],格式：插件名.开发者标识.addons
        'identifier'  => 'Captcha.zbphp.addons',
        // 插件图标[选填]
        'icon'        => 'fa fa-user',
        // 插件描述[选填]
        'description' => '拖拽试验证码',
        // 插件作者[必填]
        'author'      => 'jxy',
        // 作者主页[选填]
        'author_url'  => 'javascript:;',
        // 插件版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
        'version'     => '1.0.0',
        // 是否有后台管理功能[选填]
        'admin'       => '0',
    ];

    /**
     * @var array 插件钩子
     */
    public $hooks = [
        'admin_captcha',
    ];

    /**
     * 后台首页钩子
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function adminCaptcha()
    {
      $this->fetch('widget');
    }
    
    private function copydir($dirsrc, $dirto) {   
        if(file_exists($dirto)) { //如果原来的文件存在， 判断是不是一个目录
            if(!is_dir($dirto)) {
                echo "目标不是一个目录， 不能copy进去<br>";
                exit;
            }
        }else{
            mkdir($dirto);
        }    
        $dir = opendir($dirsrc);
        while($filename = readdir($dir)) {
            if($filename != "." && $filename !="..") {
                $srcfile = $dirsrc."/".$filename; //原文件
                $tofile = $dirto."/".$filename; //目标文件
                if(is_dir($srcfile)) {
                    $this->copydir($srcfile, $tofile); //递归处理所有子目录
                }else{
                    //是文件就拷贝到目标目录
                    copy($srcfile, $tofile);
                }
            }
        }
    }
    
    private function movefile($filesrc, $fileto){
        if(file_exists($fileto)){
            
        }else{
            copy($filesrc, $fileto);
        }
    }

    /**
     * 安装方法
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return bool
     */
    public function install(){
        //复制必要的静态资源文件
        $this->copydir(ROOT_PATH.'addons/Captcha/static/captcha', ROOT_PATH.'public/static/admin/captcha');
        //复制验证码的控制器文件
        $this->movefile(ROOT_PATH.'addons/Captcha/application/admin/Captcha.php',APP_PATH.'admin/admin/Captcha.php');
        return true;
    }

    /**
     * 卸载方法
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return bool
     */
    public function uninstall(){
        return true;
    }
}