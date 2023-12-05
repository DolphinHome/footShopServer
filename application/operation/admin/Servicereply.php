<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\Servicereply as ServicereplyModel;
use service\Format;

/**
 * 快捷问题消息设置
 * @package app\Service\admin
 */
class Servicereply extends Base
{
    /**
     * 快捷问题消息列表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 数据列表
        $replyList = ServicereplyModel::paginate();
        $fields = [
            ['id', 'ID'],
            ['problem', lang('问题')],
            ['answer', lang('回复')],
            ['status', lang('是否开启自动回复'), ['1'=>lang('开启'), '0'=>lang('未开启')]],
            ['type', lang('消息类型'), 'status', '', ServicereplyModel::$replyType],
            ['url', lang('跳转链接')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
            ->setData($replyList)//设置数据
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
            if (ServicereplyModel::where('problem', $data['problem'])
                ->count()) {
                $this->error(lang('该内容已存在'));
            }
            if ($page = ServicereplyModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        // TO DO LIST 多商户情况下可以查询商户列表
        // ['type' => 'select', 'name' => 'partner_id', 'title' => lang('选择商家'), 'extra' => $partnerList, 'tips' => '', 'attr' => '', 'value' => '0'],

        $fields = [
            ['type'=>'select', 'name'=>'type', 'title'=>lang('回复配置类型'), 'extra'=>ServicereplyModel::$replyType, 'value'=>'1'],
            ['type'=>'text', 'name'=>'problem', 'title'=>lang('问题'),'attr' => 'data-rule="required;" data-msg-required="不能为空"'],
            ['type'=>'text', 'name'=>'answer', 'title'=>lang('回复')],
            ['type'=>'text', 'name'=>'url', 'title'=>lang('跳转链接')],
            ['type'=>'hidden', 'name'=>'partner_id',  'value'=>'0'],
        ];
        $this->assign('page_title', lang('回复配置新增'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }
        

    /**
     * 编辑
     * @param null $id 客服列表id
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
            if (ServicereplyModel::where('problem', $data['problem'])
                ->where('id', '<>', $id)
                ->count()) {
                $this->error(lang('该内容已存在'));
            }
            if (ServicereplyModel::update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
    
        // 获取编辑的信息
        $info = ServicereplyModel::get($id);
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type'=>'select', 'name'=>'type', 'title'=>lang('回复配置类型'), 'extra'=>ServicereplyModel::$replyType],
            ['type'=>'text', 'name'=>'problem', 'title'=>lang('问题'),'attr' => 'data-rule="required;" data-msg-required="不能为空"'],
            ['type'=>'text', 'name'=>'answer', 'title'=>lang('回复')],
            ['type'=>'text', 'name'=>'url', 'title'=>lang('跳转链接')],
            ['type'=>'hidden', 'name'=>'partner_id',  'value'=>'0'],
        ];
        $this->assign('page_title', lang('编辑回复配置'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
