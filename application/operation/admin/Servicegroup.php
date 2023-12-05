<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\Servicegroup as ServicegroupModel;
use service\Format;

/**
 * 客户分组控制器
 * @package app\Servicegroup\admin
 */
class Servicegroup extends Base
{
    /**
     * 客户分组列表
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
        $data_list = ServicegroupModel::where($map)->order($order)->paginate();
        $fields = [
            ['aid', 'ID'],
            ['name', lang('分组名称')],
            ['status', lang('状态'), 'status'],
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
            if (ServicegroupModel::where('name', $data['name'])
                ->count()
            ) {
                $this->error(lang('该内容已存在'));
            }
            if (mb_strlen($this->trimAll($data['name'], 'utf8')) >= 8) {
                $this->error(lang('分组名字过长'));
            }
            // 验证
            $result = $this->validate($data, 'Servicegroup');
            if (true !== $result) {
                $this->error($result);
            }

            if ($page = ServicegroupModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('分组名称'), 'tips' => '', 'attr' => 'data-rule="required;" data-msg-required="不能为空"', 'value' => ''],

        ];
        $this->assign('page_title', lang('新增客户分组'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 客户分组id
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
            if (ServicegroupModel::where('name', $data['name'])
                ->where('aid', '<>', $id)
                ->count()
            ) {
                $this->error(lang('该内容已存在'));
            }
            if (mb_strlen($this->trimAll($data['name'], 'utf8')) >= 8) {
                $this->error(lang('分组名字过长'));
            }
            $data['update_time'] = time();
            // 验证
            $result = $this->validate($data, 'Servicegroup');
            if (true !== $result) {
                $this->error($result);
            }

            if (ServicegroupModel::where('aid', $data['aid'])->update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info = ServicegroupModel::get(['aid' => $id]);
        $fields = [
            ['type' => 'hidden', 'name' => 'aid'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('分组名称'), 'tips' => '', 'attr' => 'data-rule="required;" data-msg-required="不能为空"', 'value' => ''],

        ];
        $this->assign('page_title', lang('编辑客户分组'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
