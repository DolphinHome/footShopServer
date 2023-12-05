<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\admin;

use app\admin\admin\Base;
use app\user\model\Level as LevelModel;
use service\Format;

/**
 * 会员等级控制器
 * @package app\Level\admin
 */
class Level extends Base
{
    /**
     * 会员等级列表
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
        $data_list = LevelModel::where($map)->order($order)->paginate()->each(function (&$v) {
            $v['icon'] = get_file_url($v['icon']);
            return $v;
        });
        $fields = [
            ['id', 'ID'],
            ['name', lang('等级名称')],
            ['icon', lang('等级图标'), 'picture'],
            ['upgrade_score', lang('升级所需分数')],
            ['levelid', lang('等级标识')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        return Format::ins()//实例化
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

            // 验证
            $result = $this->validate($data, 'Level');
            if (true !== $result) {
                $this->error($result);
            }
            if ($insertId = (new LevelModel)->insertGetId($data)) {
                // 记录行为
                unset($data['__token__']);
                $details = json_encode($data,JSON_UNESCAPED_UNICODE);
                action_log('user_level_add', 'user_level', $insertId, UID, $details);             
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('等级名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'image', 'name' => 'icon', 'title' => lang('等级图标'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'number', 'name' => 'upgrade_score', 'title' => lang('升级所需分数'), 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'number', 'name' => 'levelid', 'title' => lang('等级标识'), 'tips' => '', 'attr' => '', 'value' => '0'],

        ];
        $this->assign('page_title', lang('新增会员等级'));
        $this->assign('form_items', $fields);
        return $this->fetch('../../admin/view/public/add');
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
        $info = LevelModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Level');
            if (true !== $result) {
                $this->error($result);
            }

            if (LevelModel::update($data)) {
                 // 记录行为
                 unset($data['__token__']);
                 $details = arrayRecursiveDiff($data, $info);
                 action_log('user_level_edit', 'user_level', $id, UID, $details); 
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('等级名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'image', 'name' => 'icon', 'title' => lang('等级图标'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'number', 'name' => 'upgrade_score', 'title' => lang('升级所需分数'), 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'number', 'name' => 'levelid', 'title' => lang('等级标识'), 'tips' => '', 'attr' => '', 'value' => '0'],

        ];
        $this->assign('page_title', lang('编辑会员等级'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }
}
