<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\SystemMessageType as SystemMessageTypeModel;
use service\Format;

/**
 * 消息类型
 * Class SystemMessage
 * @package app\operation\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @created 2019/4/28 11:02
 */
class SystemMessageType extends Base
{
    /**
     * 任务列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();        // 排序
        $order = $this->getOrder('id DESC');
        $dataList = SystemMessageTypeModel::where($map)->order($order)->paginate();
        $fields = [
            ['id', lang('序号')],
            ['thumb', lang('图标'),'picture'],
            ['name', lang('类型名称')],
            ['create_time', lang('创建时间')],
            ['id', lang('操作'), 'callback', function ($value, $data) {
                $edit = "<a ident='edit' title=lang('编辑') href='" . url('edit', ['id' => $data['id'], 'layer' => 1]) . "' icon='fa fa-pencil pr5' class='btn btn-xs mr5 btn-default layeredit'><i class='fa fa-pencil pr5'></i>编辑</a> ";
                $delete = "<a ident='delete' title=lang('删除') href='" . url('delete', ['ids' => $data['id']]) . "' icon='fa fa-times pr5' class='btn btn-xs mr5 btn-default ajax-get confirm'><i class='fa fa-times pr5'></i>删除</a> ";
                $return = $edit . $delete;
                if ($data['id'] <= 4) {
                    $return = $edit;
                }
                return $return;
            }, '__data__'],
        ];

        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button, ['disable', 'enable'])
//          ->setRightButtons($this->right_button, ['disable'])
            ->setData($dataList)//设置数据
            ->fetch();//显示
    }

    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = request()->post();

            if (!$data['name']) {
                $this->error(lang('请填写类型名称'));
            }

            $ret = SystemMessageTypeModel::create($data);
            if (!$ret) {
                $this->error(lang('创建失败'));
            }
            //记录行为
            unset($data['__token__']);
            $details = json_encode($data, JSON_UNESCAPED_UNICODE);
            action_log('operation_suggestions_type_add', 'operation', $ret->id, UID, $details);
            $this->success(lang('创建成功'), 'index');
        }

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('类型名称')],
        ];
        $this->assign('page_title', lang('新增消息类型'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    public function edit($id = 0)
    {
        $info = SystemMessageTypeModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            $data = request()->post();
            if (!$data['name']) {
                $this->error(lang('请填写类型名称'));
            }
            $ret = SystemMessageTypeModel::update($data);
            if (false === $ret) {
                $this->error(lang('修改失败'));
            }

            //记录行为
            unset($data['__token__']);
            $details = arrayRecursiveDiff($data, $info);
            action_log('operation_suggestions_type_edit', 'operation', $id, UID, $details);
            $this->success(lang('修改成功'), 'index');
        }
        
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],

            ['type' => 'image', 'name' => 'thumb', 'title' => lang('图片'), 'tips' => ''],
            ['type' => 'text', 'name' => 'name', 'title' => lang('类型名称')],
        ];
        $this->assign('page_title', lang('修改消息类型'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/add');
    }
}
