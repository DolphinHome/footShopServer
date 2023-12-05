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
 * 单页模型
 * @package app\user\model
 */
class GoodsTypeSpec extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_TYPE_SPEC__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    //关联详情
    public function specItem()
    {
        return $this->hasMany('GoodsTypeSpecItem', 'specid', 'id')->order('sort asc');
    }

    public function deleteSpec($id)
    {
        $specs = GoodsTypeSpecItem::where('specid', $id)->select()->toArray();
        if ($specs) {
            $spec_item_ids = [];
            foreach ($specs as $spec_item) {
                array_push($spec_item_ids, $spec_item['id']);
                //$spec_goods_price = \think\Db::name('spec_goods_price')->whereOr('key', $spec_item['id'])->whereOr('key', 'LIKE', '%\_' . $spec_item['id'])->whereOr('key', 'LIKE', $spec_item['id'] . '\_%')->find();
                /*if ($spec_goods_price) {
                    $goods_name = \think\Db::name('goods')->where('goods_id', $spec_goods_price['goods_id'])->value('goods_name');
                    throw new TpshopException('删除规格项', 0, ['status' => 0, 'msg' => $goods_name . '在使用该规格项，不能删除']);
                }*/
            }
            return GoodsTypeSpecItem::where('id', 'in', $spec_item_ids)->delete();
        }else{
            return true;
        }
    }
    /**
     * 获得规格 规格对应规格值
     * @param array $where
     * @param array $specValue
     * @author 风情云淡
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function get_spec($where = [], $specValue = [],$goods_id){
        $where[] = ['status','eq',1];
        $specList = GoodsTypeSpec::where($where)->field("id,name,is_upload_image")->select(); //->order("sort desc")
        foreach($specList as $key => $value){
            if($value['is_upload_image'] == 1){
                $specCondition[] = ['gtsi.id','in',$specValue];
                $specCondition[] = ['gtsi.specid','eq',$value['id']];
                $specCondition[] = ['img.goods_id','eq',$goods_id];
                $specValues = GoodsTypeSpecItem::alias('gtsi')->join('goods_type_spec_image img','gtsi.id=img.spec_image_id','right')->join('upload u','img.thumb=u.id','left')->where($specCondition)->field("gtsi.id,gtsi.item,u.path as thumb")->select()->each(function ($item) {
                    if($item['thumb']){
                        $item['thumb'] = config('web_site_domain') .$item['thumb'];
                        return $item;
                    }                    
                });
            }else{
                $specCondition[] = ['id','in',$specValue];
                $specCondition[] = ['specid','eq',$value['id']];
                $specValues = GoodsTypeSpecItem::where($specCondition)->field("id,item")->select();
            }
            $specList[$key]['spec_value'] = $specValues ? $specValues : [];
            $specCondition = [];
        }
        return $specList;
    }
}