<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\NavType as NavTypeModel;
use service\Format;

/**
 * 广告分类控制器
 * @package app\operation\admin
 */
class NavType extends Base
{
    /**
     * 广告列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('update_time desc');
        // 数据列表
        $data_list = NavTypeModel::where($map)->order($order)->paginate();
        $fields =[
            ['id', 'ID'],
            ['name', lang('导航位位名称'), 'text'],
            ['create_time', lang('创建时间'), '','','','text-center'],
            ['update_time', lang('更新时间'), '','','','text-center'],
            ['status', lang('状态'), 'status','', [lang('禁用'),lang('正常')],'text-center'],
            ['right_button', lang('操作'), 'btn','','','text-center']
        ];

        return Format::ins() //实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setTopButton(['title'=>lang('导航管理'),'href'=>['operation/nav/index'],'icon'=>'fa fa-plus pr5','class'=>'btn btn-sm mr5 btn-success '])
            ->setRightButtons($this->right_button, ['delete'])
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
            if ($data['name'] == '') {
                $this->error(lang('导航位名称不能为空'));
            }
            if (mb_strlen($data['name'], 'utf-8') >= 20) {
                $this->error(lang('导航位名称不能超过20个字'));
            };

            // 验证
            $result = $this->validate($data, 'NavType');
            if (true !== $result) {
                $this->error($result);
            }

            if ($res = NavTypeModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('operation_nav_type_add', 'operation_nav_type', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        $fields =[
            ['type' => 'text', 'name' => 'name', 'title' => lang('导航位名称')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'tips' =>'', 'extra' => [lang('否'), lang('是')], 'value' => 1]
        ];

        $this->assign('page_title', lang('新增导航位'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 广告分类id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = NavTypeModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['name'] = $this->trimAll($data['name']);
            if ($data['name'] == '') {
                $this->error(lang('导航位名称不能为空'));
            }
            if (mb_strlen($data['name'], 'utf-8') >= 20) {
                $this->error(lang('导航位名称不能超过20个字'));
            };

            // 验证
            $result = $this->validate($data, 'AdsType');
            if (true !== $result) {
                $this->error($result);
            }

            if (NavTypeModel::update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('operation_nav_type_edit', 'operation_nav_type', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
        
        $fields =[
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('导航位名称')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'tips' =>'', 'extra' => [lang('否'), lang('是')], 'value' => 1]
        ];
        $this->assign('page_title', lang('编辑导航位'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
