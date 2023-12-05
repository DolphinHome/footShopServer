<?php
/**
 * Created by PhpStorm.
 * User: 7howe
 * DateTime: 2019/11/22 20:33
 */

namespace app\goods\model;

use think\Model as ThinkModel;

class OrderGoodsExpress extends ThinkModel
{
   // 设置当前模型对应的完整数据表名称
   protected $table = '__ORDER_GOODS_EXPRESS__';

   // 自动写入时间戳
   protected $autoWriteTimestamp = false;
}
