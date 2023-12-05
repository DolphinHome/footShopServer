<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\admin;

use app\admin\admin\Base;
use app\user\model\Vip as VipModel;
use service\Format;

/**
 * VIP规则控制器
 * @package app\Vip\admin
 */
class Vip extends Base
{
    /**
     * VIP规则列表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder();
        // 数据列表
        $data_list = VipModel::where($map)->order($order)->paginate();
        $fields = [
            ['aid', 'ID'],
            ['name', 'vip名称'],
            ['thumb', 'vip图片', 'picture'],
            ['month_price', 'vip月价格'],
            ['season_price', 'vip季价格'],
            ['year_price', 'vip年价格'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->setPrimaryKey('aid')
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
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
            $result = $this->validate($data, 'Vip');
            if (true !== $result) {
                $this->error($result);
            }

            if ($page = VipModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => 'vip名称', 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'image', 'name' => 'thumb', 'title' => 'vip图片', 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'number', 'name' => 'month_price', 'title' => 'vip月价格', 'tips' => lang('购买一个月的价格'), 'attr' => '', 'value' => '0'],
            ['type' => 'number', 'name' => 'season_price', 'title' => 'vip季价格', 'tips' => lang('购买一个季度的价格'), 'attr' => '', 'value' => '0'],
            ['type' => 'number', 'name' => 'year_price', 'title' => 'vip年价格', 'tips' => lang('购买一年的价格'), 'attr' => '', 'value' => '0'],

        ];
        $this->assign('page_title', '新增VIP规则');
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id VIP规则id
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Vip');
            if (true !== $result) {
                $this->error($result);
            }

            if (VipModel::update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info = VipModel::get(['aid' => $id]);
        $fields = [
            ['type' => 'hidden', 'name' => 'aid'],
            ['type' => 'text', 'name' => 'name', 'title' => 'vip名称', 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'image', 'name' => 'thumb', 'title' => 'vip图片', 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'number', 'name' => 'month_price', 'title' => 'vip月价格', 'tips' => lang('购买一个月的价格'), 'attr' => '', 'value' => '0'],
            ['type' => 'number', 'name' => 'season_price', 'title' => 'vip季价格', 'tips' => lang('购买一个季度的价格'), 'attr' => '', 'value' => '0'],
            ['type' => 'number', 'name' => 'year_price', 'title' => 'vip年价格', 'tips' => lang('购买一年的价格'), 'attr' => '', 'value' => '0'],

        ];
        $this->assign('page_title', lang('编辑VIP规则'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
