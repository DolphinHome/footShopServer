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

use app\goods\model\GoodsLabelService as GoodsLabelServiceModel;
use app\admin\admin\Base;
use service\Format;
use think\Db;
use think\Exception;

/**
 * 商品服务标签控制器
 * @package app\Goods\admin
 */
class GoodsLabelService extends Base
{
    /**
     * 标签列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = $this->getMap();
        $this->assign('map', $map);
        // 数据列表
        if (isset($map['type']) && $map['type'] == 'all') {
            unset($map['type']);
        }
        $data_list = GoodsLabelServiceModel::where($map)->order('id desc')->paginate();
        $fields = [
            ['id', 'ID'],
            ['name', lang('名称')],
            ['type', lang('类型'), 'status', '', [1 => "服务", 2 => "活动"]],
            ['right_button', lang('操作'), 'btn', '', '']
        ];
        $searchFields = [
            ['name', lang('名称'), 'text'],
            ['type', lang('类型'), 'select', '', ['all' => "全部", 1 => "服务", 2 => "活动"]],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button_layer)
            ->setRightButtons($this->right_button_layer, ['disable'])
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
            $data['name'] = $this->trimAll($data['name']);
            if (mb_strlen($data['name'], 'utf8') == 0) {
                $this->error(lang('标签名称不能为空'));
            };
            if ($res = GoodsLabelServiceModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_label_add', 'goods_label', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'), $res->id);
            } else {
                $this->error(lang('新增失败'));
            }
        }


        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('标签名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'radio', 'name' => 'type', 'title' => lang('标签类型'), 'extra' => [1 => lang('服务'), 2 => lang('活动')], 'value' => 1],
        ];
        $this->assign('page_title', lang('新增标签'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 主表id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = GoodsLabelServiceModel::get($id);
        $reload_type = input("param.reload_type");
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['name'] = $this->trimAll($data['name']);
            if (mb_strlen($data['name'], 'utf8') == 0) {
                $this->error(lang('标签名称不能为空'));
            };
            if (GoodsLabelServiceModel::update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('goods_label_edit', 'goods_label', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('标签名称'), 'tips' => '', 'attr' => ''],
            ['type' => 'radio', 'name' => 'type', 'title' => lang('标签类型'), 'extra' => [1 => lang('服务'), 2 => lang('活动')], 'value' => 1],
        ];
        $this->assign('page_title', lang('编辑标签'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
