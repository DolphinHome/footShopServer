<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\model;

use think\Model as ThinkModel;

/**
 * 消息类型
 * Class SystemMessage
 * @package app\user\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/9 16:24
 */
class SystemMessageType extends ThinkModel
{

    //设置表名
    protected $table = '__OPERATION_SYSTEM_MESSAGE_TYPE__';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


   
}
