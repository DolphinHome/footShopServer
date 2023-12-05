<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace addons\Alipay\controller;

use app\common\controller\Common;
/**
 * Description of Base
 * @author 晓风<215628355@qq.com>
 */
class Base extends Common{    
    
    public $config;
       
    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->config =  addons_config('Alipay');
        if(isset($this->config['ali_public_key_path'])){
            $fileName = md5($this->config['ali_public_key_path']);            
            $path = __DIR__ .'/../cert/' . $fileName . '.pem';
            if(!file_exists($path)){
                try{
                    file_put_contents($path, $this->config['ali_public_key_path']);                    
                }catch(\Exception $e){                    
                    throw new \Exception("公钥文件写入失败");                
                }
            }
            $this->config['ali_public_key_path'] = $path;
        }
		
		if(isset($this->config['private_key_app_path'])){
            $fileName = md5($this->config['private_key_app_path']);            
            $path = __DIR__ .'/../cert/' . $fileName . '.pem';
            if(!file_exists($path)){
                try{
                    file_put_contents($path, $this->config['private_key_app_path']);                    
                }catch(\Exception $e){                    
                    throw new \Exception("私钥文件写入失败");                
                }
            }
            $this->config['private_key_app_path'] = $path;
        }
    }
    
    
}
