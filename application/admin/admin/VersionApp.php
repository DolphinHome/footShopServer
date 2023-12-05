<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\admin\admin\Base;
use app\admin\model\VersionApp as VersionAppModel;
use service\Format;

/**
 * 版本更新应用主表控制器
 * @package app\VersionApp\admin
 */
class VersionApp extends Base
{
    /**
     * 版本更新应用主表列表
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
        $data_list = VersionAppModel::where($map)->order($order)->paginate();
        $fields    = [
            ['aid', 'ID', '', '', '',],
            ['app_name', 'app名称', '', '', '',],
            ['app_ident', 'app标识', '', '', '',],
            [
                'client',
                lang('类型'),
                'status',
                '',
                [
                    1 => 'uniapp',
                    2 => 'iOS',
                    3 => lang('安卓'),
                ],
            ],
            ['app_readme', lang('说明备注'), 'text.tip', '', '',],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $buttons   = [
            [
                'title' => lang('更新记录'), //标题
                'href'  => ['admin/version_log/index'],//链接
                'icon'  => 'fa fa-fw fa-child',//图标
                'class' => 'btn btn-xs mr5 btn-success '//样式类
            ]
        ];
        return Format::ins() //实例化
            ->hideCheckbox()
            ->setPrimaryKey('aid')
            ->addColumns($fields)//设置字段
            ->setRightButtons($this->right_button)
            ->setTopButtons($this->top_button)
            ->setRightButtons($buttons)
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
            $result = $this->validate($data, 'VersionApp');
            if (true !== $result) {
                $this->error($result);
            }

            if ($page = VersionAppModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            [
                'type'  => 'text',
                'name'  => 'app_name',
                'title' => 'app名称',
                'value' => '',
                'tips'  => '如用户iOS端，骑手安卓端',
                'attr'  => 'data-rule="required;"'
            ],
            [
                'type'  => 'text',
                'name'  => 'app_ident',
                'title' => 'app标识',
                'value' => '',
                'tips'  => '如user，rider',
                'attr'  => 'data-rule="required;"'
            ],
            [
                'type'  => 'radio',
                'name'  => 'client',
                'title' => 'app类型',
                'value' => '1',
                'extra' => [
                    1 => 'uniapp',
                    2 => 'iOS',
                    3 => lang('安卓'),
                ],
                'tips'  => ''
            ],
            ['type' => 'textarea', 'name' => 'app_readme', 'title' => lang('说明备注'), 'value' => '', 'tips' => lang('内部说明备注'),],

        ];
        $this->assign('page_title', lang('新增应用'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 版本更新应用主表id
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
            $data                = $this->request->post();
            $data['update_time'] = time();

            // 验证
            $result = $this->validate($data, 'VersionApp');
            if (true !== $result) {
                $this->error($result);
            }

            if (VersionAppModel::update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info   = VersionAppModel::get(['aid' => $id]);
        $fields = [
            ['type' => 'hidden', 'name' => 'aid'],
            [
                'type'  => 'text',
                'name'  => 'app_name',
                'title' => 'app名称',
                'value' => '',
                'tips'  => '如用户iOS端，骑手安卓端',
                'attr'  => 'data-rule="required;"'
            ],
            [
                'type'  => 'text',
                'name'  => 'app_ident',
                'title' => 'app标识',
                'value' => '',
                'tips'  => '如user，rider',
                'attr'  => 'data-rule="required;"'
            ],
            [
                'type'  => 'radio',
                'name'  => 'client',
                'title' => 'app类型',
                'value' => '',
                'extra' => [
                    1 => 'uniapp',
                    2 => 'iOS',
                    3 => lang('安卓'),
                ],
                'tips'  => ''
            ],
            ['type' => 'textarea', 'name' => 'app_readme', 'title' => lang('说明备注'), 'value' => '', 'tips' => lang('内部说明备注'),],

        ];
        $this->assign('page_title', lang('编辑应用'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}