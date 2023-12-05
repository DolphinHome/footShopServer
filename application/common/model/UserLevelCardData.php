<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\common\model;


use think\Model as ThinkModel;

class UserLevelCardData extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__USER_LEVEL_CARD_DATA__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
}