<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\admin\admin\Base;
use app\admin\model\VersionLog as VersionLogModel;
use service\Format;

/**
 * 版本更新记录子表控制器
 * @package app\VersionLog\admin
 */
class VersionLog extends Base
{
    /**
     * 版本更新记录子表列表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $typeList = \app\admin\model\VersionApp::field('aid,app_name')->column('aid,app_name');
        array_unshift($typeList, lang('全部'));
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder();
        // 数据列表
        $data_list = VersionLogModel::alias('vl')
            ->join('admin_version_app va', 'vl.vid=va.aid', 'left')
            ->field('vl.*,va.app_name')
            ->where($map)->order($order)->paginate();
        $fields    = [
            ['aid', 'ID', '', '', '',],
            ['app_name', lang('关联应用'), '', '', '',],
            ['readme', lang('更新说明'), '', '', '',],
            ['remark', lang('内部更新备注'), '', '', '',],
            ['size', lang('包大小'), '', '', '',],
            ['url', lang('链接地址'), 'link', '', '',],
            ['version_name', lang('版本号名称'), '', '', '',],
            ['version', lang('版本号标识'), '', '', '',],
            [
                'type',
                lang('更新类型'),
                'status',
                '',
                [
                    1 => lang('热更新'),
                    2 => lang('整包更新'),
                ],
            ],
            [
                'is_force',
                lang('是否强制更新'),
                'status',
                '',
                [
                    1 => lang('强制'),
                    2 => lang('可跳过'),
                ],
            ],
            [
                'is_plan',
                lang('是否计划更新'),
                'status',
                '',
                [
                    1 => lang('即时更新'),
                    2 => lang('计划更新'),
                ],
            ],
            ['plan_time', lang('计划更新时间'), '', '', '',],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $this->assign("back_show",1);
        return Format::ins() //实例化
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
            $result = $this->validate($data, 'VersionLog');
            if (true !== $result) {
                $this->error($result);
            }

            if ($page = VersionLogModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        $typeList = \app\admin\model\VersionApp::field('aid,app_name')->column('aid,app_name');
        $fields   = [
            ['type' => 'select', 'name' => 'vid', 'title' => lang('关联应用'), 'extra' => $typeList,],
            [
                'type'  => 'text',
                'name'  => 'version_name',
                'title' => lang('版本号名称'),
                'value' => '',
                'tips'  => '例：1.2.0',
                'attr'  => 'data-rule="required;"'
            ],
            [
                'type'  => 'number',
                'name'  => 'version',
                'title' => lang('版本号标识'),
                'value' => '',
                'tips'  => '例：120',
                'attr'  => 'data-rule="required;"'
            ],
            [
                'type'  => 'file',
                'name'  => 'url',
                'title' => lang('链接地址'),
                'value' => '',
                'tips'  => '',
                'attr'  => 'data-rule="required;"'
            ],
            ['type' => 'number', 'name' => 'size', 'title' => lang('更新包大小'), 'value' => '', 'tips' => '单位MB',],
            [
                'type'  => 'textarea',
                'name'  => 'readme',
                'title' => lang('更新说明'),
                'value' => '',
                'tips'  => lang('对前端用户展示'),
                'attr'  => 'data-rule="required;"'
            ],
            ['type' => 'textarea', 'name' => 'remark', 'title' => lang('内部更新备注'), 'value' => '', 'tips' => lang('仅内部查看使用'),],
            [
                'type'  => 'radio',
                'name'  => 'type',
                'title' => lang('更新类型'),
                'value' => '1',
                'extra' => [
                    1 => lang('热更新'),
                    2 => lang('整包更新'),
                ]
            ],
            [
                'type'  => 'radio',
                'name'  => 'is_force',
                'title' => '是否强制更新（仅针对整包生效）',
                'value' => '1',
                'extra' => [
                    1 => lang('强制'),
                    2 => lang('可跳过'),
                ],
                'tips'  => ''
            ],
            [
                'type'  => 'radio',
                'name'  => 'is_plan',
                'title' => lang('是否计划更新'),
                'value' => '1',
                'extra' => [
                    1 => lang('即时更新'),
                    2 => lang('计划更新'),
                ]
            ],
            [
                'type'  => 'datetime',
                'name'  => 'plan_time',
                'title' => lang('计划更新时间'),
                'value' => '',
            ],
        ];
        $this->assign('page_title', lang('新增更新记录'));
        $this->assign('form_items', $fields);
        $this->assign('set_script', ['/static/plugins/layer/laydate/laydate.js']);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 版本更新记录子表id
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
            $result = $this->validate($data, 'VersionLog');
            if (true !== $result) {
                $this->error($result);
            }

            if (VersionLogModel::update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info     = VersionLogModel::get(['aid' => $id]);
        $typeList = \app\admin\model\VersionApp::field('aid,app_name')->column('aid,app_name');
        $fields   = [
            ['type' => 'hidden', 'name' => 'aid'],
            ['type' => 'select', 'name' => 'vid', 'title' => lang('关联应用'), 'extra' => $typeList,],
            [
                'type'  => 'text',
                'name'  => 'version_name',
                'title' => lang('版本号名称'),
                'value' => '',
                'tips'  => '例：1.2.0',
                'attr'  => 'data-rule="required;"'
            ],
            [
                'type'  => 'number',
                'name'  => 'version',
                'title' => lang('版本号标识'),
                'value' => '',
                'tips'  => '例：120',
                'attr'  => 'data-rule="required;"'
            ],
            
            [
                'type'  => 'file',
                'name'  => 'url',
                'title' => lang('链接地址'),
                'value' => '',
                'tips'  => '',
                'attr'  => 'data-rule="required;"'
            ],
            ['type' => 'number', 'name' => 'size', 'title' => lang('更新包大小'), 'value' => '', 'tips' => '单位MB',],
            [
                'type'  => 'textarea',
                'name'  => 'readme',
                'title' => lang('更新说明'),
                'value' => '',
                'tips'  => lang('对前端用户展示'),
                'attr'  => 'data-rule="required;"'
            ],
            ['type' => 'textarea', 'name' => 'remark', 'title' => lang('内部更新备注'), 'value' => '', 'tips' => lang('仅内部查看使用'),],
            [
                'type'  => 'radio',
                'name'  => 'type',
                'title' => lang('更新类型'),
                'value' => '1',
                'extra' => [
                    1 => lang('热更新'),
                    2 => lang('整包更新'),
                ]
            ],
            [
                'type'  => 'radio',
                'name'  => 'is_force',
                'title' => '是否强制更新（仅针对整包生效）',
                'value' => '1',
                'extra' => [
                    1 => lang('强制'),
                    2 => lang('可跳过'),
                ],
                'tips'  => ''
            ],
            [
                'type'  => 'radio',
                'name'  => 'is_plan',
                'title' => lang('是否计划更新'),
                'value' => '1',
                'extra' => [
                    1 => lang('即时更新'),
                    2 => lang('计划更新'),
                ]
            ],
            [
                'type'  => 'datetime',
                'name'  => 'plan_time',
                'title' => lang('计划更新时间'),
                'value' => '0',
            ],

        ];
        $this->assign('page_title', lang('编辑更新记录'));
        $this->assign('form_items', $this->setData($fields, $info));
        $this->assign('set_script', ['/static/plugins/layer/laydate/laydate.js']);
        return $this->fetch('admin@public/edit');
    }
}