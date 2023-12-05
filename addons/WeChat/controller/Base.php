<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace addons\WeChat\controller;

use app\common\controller\Common;
/**
 * 设置配置参数
 * @author 晓风<215628355@qq.com>
 */
class Base extends Common {

    public $config;

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);         
        $config =  addons_config('Wechat');
        $this->config = $config;       
    }
}
