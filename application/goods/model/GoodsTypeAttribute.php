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
class GoodsTypeAttribute extends ThinkModel {

    /*public function getAttrValueToArrayAttr($value, $data)
    {
        if($data['value'] != ''){
            return explode(',', $data['value']);
        }
        return [];
    }*/

    public function GoodsTypeAttr()
    {
        return $this->hasOne('GoodsTypeAttr','attr_id','attr_id');
    }

    public function deleteAttr($id)
    {
        /*$goods_attr = \think\Db::name('goods_attr')->where('attr_id', $id)->find();
        if($goods_attr){
            $goods_name = \think\Db::name('goods')->where('goods_id', $goods_attr['goods_id'])->value('goods_name');
            throw new TpshopException('删除规格值', 0, ['status' => 0, 'msg' => $goods_name . '在使用该属性项，不能删除']);
        }
        return parent::delete();*/
        return true;
    }

}
