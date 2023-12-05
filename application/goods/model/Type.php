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
use think\Db;

/**
 * 单页模型
 * @package app\user\model
 */
class Type extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_TYPE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 关联规格
     * @return \think\model\relation\HasMany
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function spec()
    {
        return $this->hasMany('goods_type_spec', 'typeid', 'id')->order('sort asc');
    }

    /**
     * 关联属性
     * @return \think\model\relation\HasMany
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function goodsTypeAttribute()
    {
        return $this->hasMany('GoodsTypeAttribute', 'typeid', 'id')->order('sort asc');
    }

    /**
     * 保存数据
     * @param array $data
     * @param array $where
     * @param null $sequence
     * @return ThinkModel
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function data_save($data = [], $where = [], $sequence = null)
    {
        // 启动事务
        Db::startTrans();
        try {
            if ($data['id'] > 0) {
                //先更新类型信息
                $result = parent::update($data, $where, $sequence);
            } else {
                //新增类型
                $result = parent::create($data);
                $data['id'] = $result->getLastInsID();
            }

            //规格数组
            $spec = $data['goods_spec'];
            //属性数组
            $attribute = $data['goods_attr'];
            //处理规格数组
            foreach ($spec as $spec_item) {
                if ($spec_item['name'] == '') {
                    exception(lang('规格名不能为空'));
                }
                //删除临时框输入，避免报错
                unset($spec_item['tempSpec']);

                $specs = $spec_item['spec'];
                if (array_key_exists('id', $spec_item) && $spec_item['id'] > 0) {
                    $res = GoodsTypeSpec::update($spec_item);
                    if (!$res) {
                        exception(lang('编辑失败'));
                    }
                } else {
                    unset($spec_item['id'],$spec_item['spec'],$spec_item['tempSpec']);
                    $goodsSpec = new GoodsTypeSpec();
                    $spec_item['typeid'] = $data['id'];
                    $spec_item['id'] = $goodsSpec->insertGetId($spec_item);
                    if (!$spec_item['id']) {
                        exception(lang('插入失败'));
                    }
                }
                $item_order_index = 0;
                if ($specs) {
                    foreach ($specs as $item) {
                        if (array_key_exists('id', $item) && $item['id'] > 0) {
                            $res1 = GoodsTypeSpecItem::update($item);
                            if (!$res1) {
                                exception(lang('编辑失败'));
                            }
                        } else {
                            $item['sort'] = $item_order_index;
                            $item['specid'] = $spec_item['id'];
                            unset($item['id']);
                            $res2 = GoodsTypeSpecItem::insert($item);
                            if (!$res2) {
                                exception(lang('插入失败'));
                            }
                        }
                        $item_order_index ++;
                    }
                }
            }

            //处理属性数组
            foreach ($attribute as $attr) {
                if (array_key_exists('id', $attr) && $attr['id'] > 0) {
                    $res = GoodsTypeAttribute::update($attr);
                    if (!$res) {
                        exception(lang('编辑失败'));
                    }
                } else {
                    unset($attr['id']);
                    $goodsAttr = new GoodsTypeAttribute();
                    $attr['typeid'] = $data['id'];
                    $attr['id'] = $goodsAttr->insertGetId($attr);
                    if (!$attr['id']) {
                        exception(lang('插入失败'));
                    }
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }

        return $result;
    }
}
