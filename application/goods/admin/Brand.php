<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\Brand as BrandModel;
use app\goods\model\Category;
use app\goods\model\Goods;
use service\Format;

/**
 * 品牌控制器
 * @package app\Brand\admin
 */
class Brand extends Base
{
    /**
     * 品牌列表页面
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        if ($map['status'] == "all") {
            unset($map['status']);
        }
//        halt($map);
        // 排序
        $order = $this->getOrder("id desc");
        // 数据列表
//        halt($map);
        $data_list = BrandModel::where($map)->order($order)->paginate();
        foreach ($data_list as $k => $v) {
            if ($v['cid']) {
                $data_list[$k]['category_name'] = Category::where(['id' => $v['cid']])->value('name');
            } else {
                $data_list[$k]['category_name'] = lang('顶级分类');
            }
        }
        $category1[''] = lang('全部分类');
        $category_Data = Category::getTree(0);
        $category_Data = $category1 + $category_Data;
        $fields = [
            ['id', 'ID'],
            ['logo', 'LOGO', 'picture'],
            ['name', lang('品牌名称')],
            ['url', lang('网址'), 'url'],
            ['category_name', lang('绑定分类')],
            ['description', lang('简介'), 'text.tip'],
            ['sort', lang('排序')],
            ['status', lang('状态'), 'status', '', '', 'text-center'],
            ['is_hot', lang('是否推荐'), 'status', '', [lang('否'), lang('是')]],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $searchFields = [
            ['name', lang('品牌名称'), 'text'],
            ['cid', lang('绑定分类'), 'multistage_select', '', ''],
            ['status', lang('状态'), 'select', '', ['all' => lang('全部'), 1 => lang('启用'), 0 => lang('禁用')]],
        ];
        return Format::ins()//实例化
        ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button_layer)
            ->setRightButtons($this->right_button_layer)
            ->setTopSearch($searchFields)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Brand');
            if (true !== $result) {
                $this->error($result);
            }

            if ($res = BrandModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_brand_add', 'goods_brand', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        //分类
        //$cate = Category::getMenuTree(0);
        $cate = Category::getParentsCate(0);

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('品牌名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'url', 'title' => lang('网址'), 'tips' => lang('无需填写') . 'http://', 'attr' => '', 'value' => ''],
            ['type' => 'image', 'name' => 'logo', 'title' => 'LOGO', 'tips' => '', 'attr' => '', 'value' => '0'],
            /*['type' => 'multistage_select', 'name' => 'cid', 'title' => lang('绑定分类'), 'tips' => '', 'extra' => $cate, 'value' => '0'],*/
            ['type' => 'select', 'name' => 'cid', 'title' => lang('绑定分类'), 'tips' => '', 'extra' => $cate, 'value' => '0'],
            ['type' => 'textarea', 'name' => 'description', 'title' => lang('简介'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'radio', 'name' => 'is_hot', 'title' => lang('是否推荐'), 'tips' => '', 'attr' => '', 'extra' => [lang('否'), lang('是')], 'value' => '0'],
            ['type' => 'number', 'name' => 'sort', 'title' => lang('排序'), 'tips' => '', 'attr' => '', 'value' => '99'],

        ];
        $this->assign('page_title', lang('新增品牌'));
        $this->assign('form_items', $fields);
        return $this->fetch('../../admin/view/public/add');
    }

    /**
     * 编辑
     * @param null $id 品牌id
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = BrandModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Brand');
            if (true !== $result) {
                $this->error($result);
            }

            if (BrandModel::update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('goods_brand_edit', 'goods_brand', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        //分类
        /*$cate = Category::getMenuTree(0);*/
        $cate = Category::getParentsCate(0);
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('品牌名称'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'url', 'title' => lang('网址'), 'tips' => lang('无需填写') . 'http://', 'attr' => ''],
            ['type' => 'image', 'name' => 'logo', 'title' => 'LOGO', 'tips' => '', 'attr' => ''],
            /*['type' => 'multistage_select', 'name' => 'cid', 'title' => lang('绑定分类'), 'tips' => '', 'extra' => $cate],*/
            ['type' => 'select', 'name' => 'cid', 'title' => lang('绑定分类'), 'tips' => '', 'extra' => $cate],
            ['type' => 'textarea', 'name' => 'description', 'title' => lang('简介'), 'tips' => '', 'attr' => ''],
            ['type' => 'radio', 'name' => 'is_hot', 'title' => lang('是否推荐'), 'tips' => '', 'attr' => '', 'extra' => [lang('否'), lang('是')]],
            ['type' => 'number', 'name' => 'sort', 'title' => lang('排序'), 'tips' => '', 'attr' => ''],

        ];
        $this->assign('page_title', lang('编辑品牌'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }

    /**
     * Notes: 删除品牌
     * User: chenchen
     * Date: 2021/6/25
     * Time: 18:07
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delete()
    {
        $ids = $this->request->param("ids", 0);
        $goods = Goods::where(["brand_id" => $ids, "is_delete" => 0])->find();
        if (count($goods) > 0) {
            $this->error("有商品使用该品牌，不能删除");
        }
        BrandModel::where(["id" => $ids])->delete();
        //记录行为
        action_log('goods_brand_delete', 'goods_brand', $ids, UID, $ids);
        $this->success("删除成功");

    }
}
