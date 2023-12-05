<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\Nav as NavModel;
use app\operation\model\NavType;
use service\Format;

/**
 * 导航控制器
 * @package app\operation\admin
 */
class Nav extends Base
{
    /**
     * 导航列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        $where = [];
        if (isset($map['name'])) {
            $where[] = ['name', 'like', '%' . $map['name'] . '%'];
        }
        if (isset($map['typeid']) && $map['typeid'] != 0) {
            $where[] = ['typeid', '=', $map['typeid']];
        }
        // 排序
        $order = $this->getOrder('update_time desc');
        // 数据列表
        $data_list = NavModel::where($where)->where('status', '<>', 2)->order($order)->paginate();

        $list_type = NavType::where('status', 1)->column('id,name');
        $list_type = $list_type + [lang('全部')];
        ksort($list_type);

        $fields = [
            ['id', 'ID'],
            ['name', lang('导航名称'), 'text'],
            ['typeid', lang('所属导航位'), 'status', '', $list_type],
            ['thumb', lang('图片'), 'picture'],
            ['create_time', lang('创建时间'), '', '', '', 'text-center'],
            ['update_time', lang('更新时间'), '', '', '', 'text-center'],
            ['status', lang('状态'), 'status', '', [lang('禁用'), lang('正常')], 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $search_fields = [
            ['name', lang('导航名称'), 'text'],
//            ['typeid', lang('所属导航位'), 'select', '', $list_type],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopSearch($search_fields)
            ->setTopButtons($this->top_button)
//            ->setTopButton(['title' => lang('导航位管理'), 'href' => ['operation/NavType/index'], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-success '])
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
            $data['thumb'] = $data['images'];
            $data['name'] = $this->trimAll($data['name']);
            if ($data['name'] == '') {
                $this->error(lang('导航名称不能为空'));
            }
            if (mb_strlen($data['name'], 'utf-8') >= 20) {
                $this->error(lang('导航名称不能超过20个字'));
            };

            // 验证
            $result = $this->validate($data, 'Nav.add');
            if (true !== $result) {
                $this->error($result);
            }

            if ($res = NavModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('operation_nav_add', 'operation_nav', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $list_type = NavType::where('status', 1)->field('id,name')->select();
        $this->assign('list_type', $list_type);
        return $this->fetch();
    }

    /**
     * 编辑
     * @param null $id 广告id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = NavModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['name'] = $this->trimAll($data['name']);
            if ($data['name'] == '') {
                $this->error(lang('导航名称不能为空'));
            }
            if (mb_strlen($data['name'], 'utf-8') >= 20) {
                $this->error('导航名称不能超过20个字');
            };
            // 验证
            $result = $this->validate($data, 'Nav.edit');
            if (true !== $result) {
                $this->error($result);
            }

            if (NavModel::update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('operation_nav_edit', 'operation_nav', $id, UID, $details);
                $this->success(lang('编辑成功'), 'index');
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $list_type = NavType::where('status', 1)->field('id,name')->select();
        $this->assign('list_type', $list_type);
        $this->assign('info', $info);
        return $this->fetch();
//        $fields = [
//            ['type' => 'hidden', 'name' => 'id'],
//            ['type' => 'select', 'name' => 'typeid', 'title' => lang('所属导航位'), 'extra' => $list_type],
//            ['type' => 'text', 'name' => 'name', 'title' => lang('导航名称')],
//            ['type' => 'image', 'name' => 'thumb', 'title' => lang('图片'), 'tips' => ''],
//            ['type' => 'text', 'name' => 'href', 'title' => lang('链接'), 'tips' => ''],
//            ['type' => 'radio', 'name' => 'is_login', 'title' => lang('是否判断登录状态'), 'extra' => [lang('否'), lang('是')]],
//            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序'), 'tips' => ''],
//            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'extra' => [lang('否'), lang('是')]],
//        ];
//        $this->assign('page_title', lang('编辑广告位'));
//        $this->assign('form_items', $this->setData($fields, $info));
//        return $this->fetch('admin@public/edit');
    }

    //删除导航管理数据
    public function delete($ids = null)
    {
        if ($ids === null) {
            $this->error(lang('参数错误'));
        }
        $ret = NavModel::where('id', 'in', $ids)->setField('status', 2);
        if (false === $ret) {
            $this->error(lang('删除失败'));
        }
        return $this->success(lang('删除成功'));
    }
}
