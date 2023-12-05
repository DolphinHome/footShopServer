<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\admin;

use app\admin\admin\Base;
use app\user\model\Task as TaskModel;
use service\Format;

/**
 * 会员任务控制器
 * @package app\Level\admin
 */
class Task extends Base
{
    /**
     * 会员任务列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder();
        // 数据列表
        $data_list = TaskModel::where($map)->order($order)->paginate()->each(function ($item) {
            $item['add_score']='+ '.$item['add_score'];
            $item['add_empirical']='+ '.$item['add_empirical'];
            return $item;
        });
        ;
        $fields = [
            ['id', 'ID'],
            ['title', lang('任务名称')],
            ['add_score', lang('增加积分')],
            ['add_empirical', lang('增加成长值')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        return Format::ins()//实例化
            ->addColumns($fields)//设置字段
            //->setTopButtons($this->top_button)
            ->setRightButtons([['ident'=> 'edit', 'title'=>lang('编辑'),'href'=>'edit?layer=1&reload=0','data-toggle'=>"dialog-right",'class'=>'mr5 font12']])
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 编辑
     * @param null $id 会员等级id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
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
            $result = $this->validate($data, 'Task');
            if (true !== $result) {
                $this->error($result);
            }

            if (TaskModel::update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info = TaskModel::get($id);
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'title', 'title' => lang('任务名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'number', 'name' => 'add_score', 'title' => lang('增加积分'), 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'number', 'name' => 'add_empirical', 'title' => lang('增加成长值'), 'tips' => '', 'attr' => '', 'value' => '0'],

        ];
        $this->assign('page_title', lang('编辑任务'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }
}
