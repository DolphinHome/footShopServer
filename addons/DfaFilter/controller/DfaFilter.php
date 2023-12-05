<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace addons\DfaFilter\controller;
require ROOT_PATH . 'addons/DfaFilter/sdk/SensitiveHelper.php';
require ROOT_PATH . 'addons/DfaFilter/sdk/HashMap.php';

use DfaFilter\SensitiveHelper;
use app\common\controller\Common;

/**
 * 敏感词过虑
 * @package addons\DfaFilter\controller
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * https://github.com/FireLustre/php-dfa-sensitive
 * @created 2020/9/14 20:44
 */
class DfaFilter extends Common
{
    public $client;

    public function __construct(){
        $word = \think\Db::name('sensitive_words')->column('sw_id, sw_content');
        $this->client = SensitiveHelper::init()->setTree($word);
    }

    /**
     * 检测敏感词
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * 调用方式 addons_action('DfaFilter/DfaFilter/check', [$content]]);
     * @created 2020/9/14 20:51
     */
    public function check($content){
        $islegal = $this->client->islegal($content);
        return $islegal;
    }

    /**
     * 过虑敏感词
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/9/14 20:51
     *
     */
    public function filter($content, $symbol = "***"){
        $islegal = $this->client->replace($content, $symbol);
        return $islegal;
    }

    /**
     * 标记敏感词
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/9/14 20:51
     */
    public function fffilter($content, $symbol){
        $islegal = $this->client->replace($content, $symbol);
        return $islegal;
    }

    /**
     * 获取文字中的敏感词
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/9/14 20:51
     */
    public function fffffilter($content, $symbol){
        $islegal = $this->client->replace($content, $symbol);
        return $islegal;
    }
}
