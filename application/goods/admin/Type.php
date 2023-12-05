<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 似水星辰 [2630481389@qq.com]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\GoodsTypeAttribute;
use app\goods\model\Type as TypeModel;
use app\goods\model\GoodsTypeSpec;
use app\goods\model\GoodsTypeSpecItem;
use app\goods\model\Category;
use service\Format;
use think\Db;
use think\Exception;

/**
 * 规格属性控制器
 * @package app\Goods\admin
 */
class Type extends Base
{
    /**
     * 会员主表列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        $this->assign('map', $map);
        $returnData = [];
        $category_id = $map['cid'];
        $cids = [];
        $where = [];
        if ($map['name']) {
            $where[] = ['name','=',$map['name']];
        }
        if ($category_id) {
            $pidData = Category::where('pid', $category_id)->find();
            if (!empty($pidData)) {
                $cids = Category::getChildsId($category_id);
                array_unshift($cids, $category_id);
                $where[] = ['cid','in',$cids];
            } else {
                $where[] = ['cid','=',$category_id];
            }
        }
        // 数据列表
        $data_list = TypeModel::where($where)->order('id desc')->paginate();
//        halt($data_list);
        foreach ($data_list as $k=>$v) {
            $arr = [];
            $GoodsTypeSpecData = GoodsTypeSpec::where('typeid', $v['id'])->field('id,typeid,name')->select();
            if ($GoodsTypeSpecData) {
                foreach ($GoodsTypeSpecData as $key=>$val) {
                    $GoodsTypeSpecItemData = GoodsTypeSpecItem::where('specid', $val['id'])->column('item');
                    if ($GoodsTypeSpecItemData) {
                        $itemData = implode(',', $GoodsTypeSpecItemData);
                        $arr[] = $val['name'].':'.$itemData;
                    }
                }
                $data_list[$k]['items'] = implode(' ; ', $arr);
            }
        }
        $category1[''] = lang('全部分类');
        $category_Data = Category::getTree(0);
        $category_Data = array_merge($category1, $category_Data);
        $fields =[
            ['id','ID'],
            ['name', lang('名称')],
            ['cid', lang('所属分类'), 'callback', function ($value, $data) {
                return Category::getCateStr($value);
            }, '__data__'],
            ['items', lang('规格属性'),'text.tip'],
            ['right_button', lang('操作'), 'btn','','']
        ];
        $searchFields = [
            ['name', lang('名称'), 'text'],
            // ['cid',lang('所属分类'),'select','',$category_Data],
            ['cid',lang('所属分类'),'multistage_select','',''],
        ];
        return Format::ins() //实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button_layer)
            ->setRightButtons([['ident'=> 'edit', 'title'=>lang('编辑'),'href'=>['edit', ['id'=>'__id__', 'layer' => 1,'reload_type'=>'type_index']],'icon'=>'fa fa-pencil pr5','class'=>'btn btn-xs mr5 btn-default layeredit'],])
            ->setRightButtons($this->right_button_layer, ['edit'])
            ->setTopSearch($searchFields)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Type.add');
            if (true !== $result) {
                $this->error($result);
            }

            $list_count = Category::where(['pid'=>$data['cid']])->count();
            if ($list_count > 0) {
                $this->error(lang('请选择最后一级分类'));
            }
            // $pid = Category::where(['id'=>$data['cid']])->value('pid');
            // $pids = Category::where(['id'=>$pid])->value('pid');
            // if( $pids < 1 ){
            //     $this->error(lang('请选择最后一级分类'));
            // }
            if ($res = TypeModel::data_save($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_type_add', 'goods_type', $res['id'], UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'), $res['id']);
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $this->assign('page_title', lang('新增商品规格属性'));
        $this->assign('goodsid', input('param.goodsid'));
        $this->assign('category', json_encode($this->getCateTree(), JSON_UNESCAPED_UNICODE));
        $this->assign('layer', input('param.layer'));
        return $this->fetch('edit');
    }

    /**
     * 编辑
     * @param null $id 会员主表id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = TypeModel::get($id);
        $reload_type = input('param.reload_type');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Type.edit');
            if (true !== $result) {
                $this->error($result);
            }

            if ($res = TypeModel::data_save($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('goods_type_edit', 'goods_type', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'), $res['id']);
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $this->assign('page_title', lang('编辑商品规格'));
        $this->assign('info', $info);
        $this->assign('reload_type', $reload_type);
        $this->assign('category', json_encode($this->getCateTree(), JSON_UNESCAPED_UNICODE));
        $this->assign('goodsid', input('param.goodsid'));
        $this->assign('layer', input('param.layer'));
        return $this->fetch();
    }

    public function delete($ids)
    {
        // 启动事务
        Db::startTrans();
        try {
            $specs = GoodsTypeSpec::where('typeid', $ids)->select()->toArray();
            if ($specs) {
                $spec_item_ids = [];
                foreach ($specs as $spec) {
                    $spec_items = GoodsTypeSpecItem::where('specid', $spec['id'])->select()->toArray();
                    if ($spec_items) {
                        foreach ($spec_items as $spec_item) {
                            array_push($spec_item_ids, $spec_item['id']);
                            $find = Db::name('goods_sku')->where([
                                ['key_name', 'like', '%' . $spec_item['item'] . '%']
                            ])->find();
                            if ($find) {
                                throw new Exception(lang('在使用该规格项，不能删除'));
                            }
                            /*$spec_goods_price = \think\Db::name('spec_goods_price')->whereOr('key', $spec_item['id'])
                                ->whereOr('key', 'LIKE', '%\_' . $spec_item['id'])->whereOr('key', 'LIKE', $spec_item['id'] . '\_%')->find();
                            if ($spec_goods_price) {
                                $goods_name = \think\Db::name('goods')->where('goods_id', $spec_goods_price['goods_id'])->value('goods_name');
                                throw new TpshopException(lang('删除商品模型'), 0, ['status' => 0, 'msg' => $goods_name . lang('在使用该规格项，不能删除')]);
                            }*/
                        }
                    }
                }
                //删除包含的规格值
                GoodsTypeSpecItem::where('id', 'in', $spec_item_ids)->delete();
            }
            //删除规格
            GoodsTypeSpec::where('typeid', $ids)->delete();
            //删除属性
            GoodsTypeAttribute::where('typeid', $ids)->delete();
            //删除类型
            TypeModel::where('id', $ids)->delete();

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        //记录行为
        $details = json_encode($param, JSON_UNESCAPED_UNICODE);
        action_log('goods_type_delete', 'goods_type', $ids, UID, $details);
        $this->success(lang('删除成功'));
    }

    //删除规格项
    public function deleteSpec()
    {
        $id = input('id/d');
        if (empty($id)) {
            $this->error(lang('参数错误'));
        }
        // 启动事务
        Db::startTrans();
        try {
            $Spec = new GoodsTypeSpec();
            $res = $Spec->deleteSpec($id);
            if (!$res) {
                exception(lang('规格值删除失败'));
            }
            $res1 = $Spec->where('id', $id)->delete();
            if (!$res1) {
                exception(lang('规格删除失败'));
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success(lang('删除成功'));
    }

    //删除规格值
    public function deleteSpecItem()
    {
        $id = input('id/d');
        if (empty($id)) {
            $this->error(lang('参数错误'));
        }
        // 启动事务
        Db::startTrans();
        try {
            $SpecItem = new GoodsTypeSpecItem();
            $res = $SpecItem::deleteSpecItem($id);
            if (!$res) {
                exception($res);
            }
            $res = $SpecItem::where('id', $id)->delete();
            if (!$res) {
                exception(lang('删除失败'));
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success(lang('删除成功'));
    }

    //删除属性
    public function deleteAttribute()
    {
        $id = input('id/d');
        if (empty($id)) {
            $this->error(lang('参数错误'));
        }
        // 启动事务
        Db::startTrans();
        try {
            $attr = new GoodsTypeAttribute();
            $res = $attr::deleteAttr($id);
            if (!$res) {
                exception($res);
            }
            $res = $attr::where('id', $id)->delete();
            if (!$res) {
                exception(lang('删除失败'));
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success(lang('删除成功'));
    }

    //验证分类（只能选择最后一级）
    public function verifyCate()
    {
        $cid = input('param.');
        if (empty($cid)) {
            $this->error(lang('参数错误'));
        }
        $pid = Category::where(['id'=>$cid])->value('pid');
        $pids = Category::where(['id'=>$pid])->value('pid');
        if ($pids < 1) {
            $this->error(lang('请选择最后一级分类'));
        }
        $this->success();
    }

    public function getCateTree()
    {
        $cate = Category::getTree(0);
        $list = [];
        foreach ($cate as $cid => $item) {
            $list[] = [
                'cid' => $cid,
                'name' => $item,
            ];
        }
        return $list;
    }
}
