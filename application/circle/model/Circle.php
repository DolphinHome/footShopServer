<?php
namespace app\circle\model;

use think\Model as ThinkModel;
use think\helper\Hash;
use think\Db;
/**
 * 单页模型
 * @package app\user\model
 */
class Circle extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CIRCLE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

}