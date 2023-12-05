<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\common\controller;

use think\Controller;

/**
 * 项目公共控制器
 * @package app\common\controller
 */
class Authorization extends Controller
{
    /**
     * 初始化
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    protected function initialize()
    {
        $domain = $_SERVER['SERVER_NAME'];
        $ignore_domain = ['127.0.0.1', 'localhost', 'feiniu'];
        //先过虑域名，默认设置2个本地过虑
        if(!in_array($domain, $ignore_domain)){
            $module = request()->module();
            $ignore = ['api', 'index'];
            //过虑模块，这些模块不校验
            $c = cache('system_check_auth');
            if (!in_array($module, $ignore) && (!$c || (time() - $c) > 2*60*60*24)) {
                // 检查是否授权
                $content = \service\File::read_file('./../data/install.lock');
                if (!$content) {
                    $this->error('授权码无效');
                } else {
                    $verifyurl = 'http://mk.zhongbenruanjian.com/api/v2/5e031f1d5228a';
                    $da = ["domain_url" => $domain, "password" => config('license_code'), "verify" => $content];
                    $result = curl_post($verifyurl, $da);
                    $result1 = json_decode($result, true);
                    if ($result1['code'] == 0) {
                        // $this->error('请获取授权后再使用');
                    }
                    cache('system_check_auth', time());
                }
            }
        }

        // 模块后台公共模板
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');
        // 输出弹出层参数
        $this->assign('layer', $this->request->param('layer'));
    }

    /**
     * 授权判断
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function is_authorization_time(){
        return cache('system_check_auth');
    }

}