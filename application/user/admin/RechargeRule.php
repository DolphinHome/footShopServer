<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\admin;

use app\admin\admin\Base;
use app\user\model\RechargeRule as RechargeRuleModel;
use service\Format;

/**
 * 充值规则管理
 * Class RechargeRule
 * @package app\user\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @created 2019/5/8 15:57
 */
class RechargeRule extends Base
{

    /**
     * 充值规则列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function index($group = 0)
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();// 排序

        if (isset($map['name']) && !empty($map['name'])) {
            $map[] = ['name', 'like', '%'.$map['name'].'%'];
            unset($map['name']);
        }
        if ($map['group'] == -1) {
            unset($map['group']);
        } elseif (isset($map['group'])) {
            $map[] = ['group','=',$map['group']];
            unset($map['group']);
        }
        $orders = '';
        if (isset($map['by']) && isset($map['order'])) {
            $orders .= $map['order'] . ' ' . $map['by'] . ',';
            unset($map['by'], $map['order']);
        }
        $orders .= 'id DESC,sort asc';

        $order = $this->getOrder($orders);
        $fields = [// 批量添加数据列
            ['id', 'ID'],
            ['name', lang('规则名称')],
            ['money', lang('支付价格'), 'text'],
            ['add_money', lang('充值金额'), 'text'],
            ['group', lang('类型'), 'callback', function ($value) {
                return $this->getType($value);
            }],
            ['create_time', lang('创建时间')],
            ['status', lang('状态'), 'status'],
            ['right_button', lang('操作'), 'btn']
        ];
        $dataList = RechargeRuleModel::getList($map, $order);

        $search_fields = [
            ['name', lang('规则名称'), 'text'],
            ['group', lang('类型'), 'select', '', ['-1' => lang('全部'), '0' => 'Android', '1' => 'IOS']],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setOrder('group,money,add_money')
//          ->setTabNav($tab_list, $group)//设置TAB分组
//          ->setTopButton(['title' => lang('新增'), 'href' => ['add', ['group' => $group]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary'])
            ->setTopButton(['title' => lang('新增'), 'href' => ['add'], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary'])
            ->setTopSearch($search_fields)
            ->setRightButtons($this->right_button)
            ->setData($dataList)//设置数据
            ->fetch();//显示
    }

    public function getType($type)
    {
        $data = ['0' => 'Android', '1' => 'IOS'];
        return $data[$type];
    }

    /**
     * 新增充值规则
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/5/8 19:25
     * @editor XX [ xxx@qq.com ]
     * @updated 2019.03.30
     * @return mixed
     */
    public function add($group = 0)
    {
        // 保存文档数据
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            $data['app_name'] = $data['app_name'] ?? '';
            // 验证
            $result = $this->validate($data, 'RechargeRule.add');
            if (true !== $result) {
                $this->error($result);
            }

            if ($res = RechargeRuleModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('user_recharge_rule_add', 'user_recharge_rule', $res->id, UID, $details);
                $this->success(lang('新增成功'), 'index');
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'radio', 'name' => 'group', 'title' => lang('类型'), 'extra' => [lang('一般'), 'IOS'], 'value' => 0],
            ['type' => 'text', 'name' => 'name', 'title' => lang('规则名称'), 'tips' => lang('一般规则名称和充值金额是相同的，用作前台展示')],
            ['type' => 'text', 'name' => 'money', 'title' => lang('支付价格'), 'tips' => lang('实际需要支付的价格')],
            ['type' => 'text', 'name' => 'add_money', 'title' => lang('充值金额'), 'tips' => lang('一般规则名称和充值金额是相同的，用作前台展示')],
            //['type' => 'text', 'name' => 'add_money', 'title' => lang('赠送金额')],
        ];

        $this->assign('page_title', lang('新增充值规则'));
        $this->assign('form_items', $fields);
        return $this->fetch('../../admin/view/public/add');
    }

    /**
     * 编辑充值规则
     * @param null $id 会员等级id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = RechargeRuleModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'RechargeRule.edit');
            if (true !== $result) {
                $this->error($result);
            }

            if (RechargeRuleModel::update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('user_recharge_rule_edit', 'user_recharge_rule', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'radio', 'name' => 'group', 'title' => lang('类型'), 'extra' => [lang('一般'), 'IOS']],
            ['type' => 'text', 'name' => 'name', 'title' => lang('规则名称'), 'tips' => lang('一般规则名称和充值金额是相同的，用作前台展示')],
            ['type' => 'text', 'name' => 'money', 'title' => lang('支付价格'), 'tips' => lang('实际需要支付的价格')],
            ['type' => 'text', 'name' => 'add_money', 'title' => lang('充值金额'), 'tips' => lang('一般规则名称和充值金额是相同的，用作前台展示')],
            //['type' => 'text', 'name' => 'add_money', 'title' => lang('赠送金额')],
        ];
        $this->assign('page_title', lang('编辑充值规则'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }
}
