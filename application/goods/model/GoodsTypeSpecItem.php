<?php
// +----------------------------------------------------------------------
// | LwwanPHP
// +----------------------------------------------------------------------
// | 版权所有 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 http://www.sitejs.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------

namespace app\goods\model;

use think\Model as ThinkModel;

/**
 * 规格值模型
 * @package app\goods\model
 */
class GoodsTypeSpecItem extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_TYPE_SPEC_ITEM__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function deleteSpecItem($id)
    {
        /*$spec_goods_price = \think\Db::name('spec_goods_price')->whereOr('key', $id)->whereOr('key', 'LIKE', '%\_' . $id)->whereOr('key', 'LIKE', $id . '\_%')->find();
        if ($spec_goods_price) {
            $goods_name = \think\Db::name('goods')->where('goods_id', $spec_goods_price['goods_id'])->value('goods_name');
            throw new TpshopException('删除规格值', 0, ['status' => 0, 'msg' => $goods_name . $spec_goods_price['key_name'] . '在使用该规格，不能删除']);
        }
        return parent::delete(); */
        return true;
    }

    /**
     * 获得商品规格 和 规格值
     * @param $where
     * @return array
     */
    function get_spec_value($where){
        $specType = GoodsTypeSpecItem::where($where)->field("specid,item")->select();

        $specValue = [];
        foreach ($specType as $key=>$value){
           $specName = GoodsTypeSpec::where(['id' => $value['specid']])->value("name");
           $specValue[] = $specName.":".$value['item'];
        }
        return implode(" ",$specValue);
    }
}