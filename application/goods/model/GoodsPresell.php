<?php
/**
 * Created by PhpStorm.
 * User: 7howe
 * DateTime: 2019/12/28 14:07
 */
namespace app\goods\model;


use think\Model;

class GoodsPresell extends Model
{
    protected $table = '__GOODS_PRESELL__';

    protected $autoWriteTimestamp = true;


    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id')->bind([
            'goods_name' => 'name'
        ]);
    }

    public function setDespoitStartAttr($value)
    {
        return strtotime($value);
    }

    public function setDespoitEndAttr($value)
    {
        return strtotime($value);
    }

    public function setTailtimeAttr($value)
    {
        return strtotime($value);
    }
}
