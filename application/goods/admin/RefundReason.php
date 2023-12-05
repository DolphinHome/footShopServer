<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 似水星辰 [2630481389@qq.com]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

namespace app\goods\admin;

use app\goods\model\RefundReason as RefundReasonModel;
use app\admin\admin\Base;
use service\Format;
use think\Db;
use think\Exception;

/**
 * 退换货原因控制器
 * @package app\Goods\admin
 */
class RefundReason extends Base
{
    /**
     * 原因列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = $this->getMap();
        $this->assign('map', $map);
        // 数据列表
        if (isset($map['type']) && $map['type'] == 'all') {
            unset($map['type']);
        }
        $data_list = RefundReasonModel::where($map)->order('id desc')->paginate();

        $fields = [
            ['id', 'ID'],
            ['reason', lang('原因'), 'text.edit', '', '', '', 'refund_reason'],
            ['type', lang('类型'), 'status', '', [1 => lang('退换货'), 2 => lang('取消订单')]],
            ['status', lang('状态'), 'status', '', [lang('禁用'), lang('启用')]],
            ['create_time', lang('创建时间')],
            ['right_button', lang('操作'), 'btn', '', '']
        ];
        $searchFields = [
            ['reason', lang('原因'), 'text'],
            ['type', lang('类型'), 'select', '', ['all' => lang('全部'), 1 => lang('退换货'), 2 => lang('取消订单')]],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button_layer)
            ->setRightButtons($this->right_button_layer)
            ->setTopSearch($searchFields)
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
            $data['create_time'] = time();
            if (isset($data['reason']) && $this->trimAll($data['reason']) == '') {
                return $this->error(lang('请填写退货原因'));
            }

            if ($res_id = RefundReasonModel::insertGetId($data)) {
                // 记录行为
                action_log('goods_refund_reason_add', 'refund_reason', $res_id, UID, $data['reason']);
                $this->success(lang('新增成功'), cookie('__forward__'), $res_id);
            } else {
                $this->error(lang('新增失败'));
            }
        }


        $fields = [
            ['type' => 'text', 'name' => 'reason', 'title' => lang('原因'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'radio', 'name' => 'type', 'title' => lang('原因类型'), 'extra' => [1 => lang('退换货'), 2 => lang('取消订单')], 'value' => 1],
        ];
        $this->assign('page_title', lang('新增原因'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 会员主表id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = RefundReasonModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (isset($data['reason']) && $this->trimAll($data['reason']) == '') {
                return $this->error(lang('请填写退货原因'));
            }
            $res = RefundReasonModel::update($data);
            if ($res !== false) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('goods_refund_reason_edit', 'refund_reason', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'), $id);
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'reason', 'title' => lang('原因'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'radio', 'name' => 'type', 'title' => lang('原因类型'), 'extra' => [1 => lang('退换货'), 2 => lang('取消订单')], 'value' => 1],
        ];
        $this->assign('page_title', lang('编辑原因'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
