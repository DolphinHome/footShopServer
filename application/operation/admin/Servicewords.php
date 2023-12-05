<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\Servicewords as ServicewordsModel;
use service\Format;

/**
 * 客服常用词控制器
 * @package app\Servicewords\admin
 */
class Servicewords extends Base
{
    /**
     * 客服常用词列表
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
        $data_list = ServicewordsModel::where($map)->order($order)->paginate();
        $fields =[
            ['id','ID'],
            ['body',lang('内容')],
            ['right_button', lang('操作'), 'btn','','','text-center']
        ];
        return Format::ins() //实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
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
            if (mb_strlen($data['body']) > 100) {
                $this->error(lang('内容长度过长'));
            }
            if (ServicewordsModel::where('body', $data['body'])->count()) {
                $this->error(lang('该内容已存在'));
            }
            // 验证
            $result = $this->validate($data, 'Servicewords');
            if (true !== $result) {
                $this->error($result);
            }

            if ($page = ServicewordsModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields =[
            ['type'=>'text','name'=>'body','title'=>lang('内容'),'tips'=>'','attr' => 'data-rule="required;" data-msg-required="不能为空"','value'=>''],
        ];
        $this->assign('page_title', lang('新增客服常用词'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 客服常用词id
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
            if (mb_strlen($data['body']) > 100) {
                $this->error(lang('内容长度过长'));
            }
            if (ServicewordsModel::where('body', $data['body'])
                ->where('id', '<>', $id)
                ->count()) {
                $this->error(lang('该内容已存在'));
            }
            // 验证
            $result = $this->validate($data, 'Servicewords');
            if (true !== $result) {
                $this->error($result);
            }

            if (ServicewordsModel::update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
    
        $info = ServicewordsModel::get($id);
        $fields =[
            ['type' => 'hidden', 'name' => 'id'],
            ['type'=>'text','name'=>'body','title'=>lang('内容'),'tips'=>'','attr' => 'data-rule="required;" data-msg-required="不能为空"','value'=>''],
        ];
        $this->assign('page_title', lang('编辑客服常用词'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
