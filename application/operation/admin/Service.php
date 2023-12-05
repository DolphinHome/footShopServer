<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\Service as ServiceModel;
use app\operation\model\Servicegroup;
use service\Format;

/**
 * 客服列表控制器
 * @package app\Service\admin
 */
class Service extends Base
{
    /**
     * 客服列表列表
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
        $group = Servicegroup::where('status', 1)->column('aid,name');
        // 数据列表
        $data_list = ServiceModel::where($map)->order($order)->paginate();
        $fields = [
            ['id', 'ID'],
            ['avatar', lang('客服头像'),'picture'],
            ['nickname', lang('客服昵称')],
            ['username', lang('客服账号')],
            ['group', lang('所属分组'), 'status', '', $group],
            ['service_number', lang('客服服务人数')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
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
            if (ServiceModel::where('username', $data['username'])
                ->count()) {
                $this->error(lang('该内容已存在'));
            }
            // 验证
            $result = $this->validate($data, 'Service.add');
            if (mb_strlen($this->trimAll($data['nickname']), 'utf8') >= 8) {
                return $this->error(lang('客服昵称过长'));
            }
            if (mb_strlen($this->trimAll($data['username']), 'utf8') >= 15) {
                return $this->error(lang('客服账号过长'));
            }
            if (mb_strlen($this->trimAll($data['password']), 'utf8') >= 20) {
                return $this->error(lang('客服密码过长'));
            }
            if (true !== $result) {
                $this->error($result);
            }

            if ($page = ServiceModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        //分组列表
        $group = Servicegroup::where('status', 1)->column('aid,name');
        
        // TO DO LIST 多商户情况下可以查询商户列表
        // ['type' => 'select', 'name' => 'partner_id', 'title' => lang('选择商家'), 'extra' => $partnerList, 'tips' => '', 'attr' => '', 'value' => '0'],

        $fields = [
            ['type' => 'text', 'name' => 'nickname', 'title' => lang('客服昵称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'username', 'title' => lang('客服账号'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'password', 'name' => 'password', 'title' => lang('登录密码'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'select', 'name' => 'group', 'title' => lang('所属分组'), 'extra' => $group, 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'hidden', 'name' => 'partner_id', 'title' => '商家ID', 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'text', 'name' => 'service_number', 'title' => lang('客服服务人数'), 'tips' => '', 'attr' => '', 'value' => '5'],
            ['type' => 'image', 'name' => 'avatar', 'title' => lang('客服头像'), 'tips' => '', 'attr' => '', 'value' => '0'],
        ];
        $this->assign('page_title', lang('新增客服列表'));
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
            if (ServiceModel::where('username', $data['username'])
                ->where('id', '<>', $id)
                ->count()) {
                $this->error(lang('该内容已存在'));
            }
            // 如果没有填写密码，则不更新密码
            if ($data['password'] == '') {
                unset($data['password']);
            }

            // 验证
            $result = $this->validate($data, 'Service.edit');
            if (mb_strlen($this->trimAll($data['nickname']), 'utf8') >= 8) {
                return $this->error(lang('客服昵称过长'));
            }
            if (mb_strlen($this->trimAll($data['username']), 'utf8') >= 15) {
                return $this->error(lang('客服账号过长'));
            }
            if (mb_strlen($this->trimAll($data['password']), 'utf8') >= 20) {
                return $this->error(lang('客服密码过长'));
            }
            if (true !== $result) {
                $this->error($result);
            }

            if (ServiceModel::update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        //分组列表
        $group = Servicegroup::where('status', 1)->column('aid,name');

        $info = ServiceModel::get($id);
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'nickname', 'title' => lang('客服昵称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'username', 'title' => lang('客服账号'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'password', 'name' => 'password', 'title' => lang('登录密码'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'select', 'name' => 'group', 'title' => lang('所属分组'), 'extra' => $group, 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'service_number', 'title' => lang('客服服务人数'), 'tips' => '', 'attr' => '', 'value' => '5'],
            ['type' => 'image', 'name' => 'avatar', 'title' => lang('客服头像'), 'tips' => '', 'attr' => '', 'value' => '0'],

        ];
        $this->assign('page_title', lang('编辑客服列表'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
