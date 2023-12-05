<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\model;

use think\Model as ThinkModel;
/**
 * 私信
 * Class Message
 * @package app\member\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/9 16:14
 */
class Message extends ThinkModel{
    protected  $table = "__USER_MESSAGE__";
    
       // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
}
