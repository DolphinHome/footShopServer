<?php
/**
 * Notes:
 * User: chenchen
 * Date: 2021/7/8
 * Time: 11:28
 * @return
 */

namespace app\operation\model;



use think\Model as ThinkModel;

/**
 * 素材模型
 * @package app\operation\model
 */
class LiveMedia extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__LIVE_MEDIA__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}