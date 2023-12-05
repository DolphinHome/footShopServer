<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\admin\model\Action as ActionModel;
use app\admin\model\Module as ModuleModel;
use service\Format;

/**
 * 行为管理控制器
 * @package app\admin\controller
 */
class Action extends Base
{
    /**
     * 首页
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 数据列表
        $data_list = ActionModel::order('id desc')->paginate();
        // 所有模块的名称和标题
        $list_module = ModuleModel::getModule();

        // 新增或编辑页面的字段
        $fields = [
            ['id', 'ID'],
            ['name', lang('标识')],
            ['title', lang('名称')],
            ['remark', lang('描述')],
            ['module', lang('所属模块'), 'callback', function ($module, $list_module) {
                return isset($list_module[$module]) ? $list_module[$module] : lang('未知');
            }, $list_module, 'text-center'],
            ['status', lang('状态'), 'status', '', '', 'text-center'],
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
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Action.add');
            // 验证失败 输出错误信息
            if (true !== $result) $this->error($result);

            if (ActionModel::create($data)) {
                $id=ActionModel::getLastInsID();
                // 记录行为
                action_log('admin_action_add', 'admin_action', $id, UID, $data['name']);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'select', 'name' => 'module', 'title' => lang('所属模块'), 'extra' => ModuleModel::getModule()],
            ['type' => 'text', 'name' => 'name', 'title' => lang('行为标识'), 'tips' => lang('必填，可由英文字母、数字组成,必须使用英文字母开头'), 'attr' => 'data-rule="required;name;" data-rule-name="[/^[\w\d]{3,100}$/, \'请输入正确的行为标识\']" data-msg-required="行为标识不能为空"'],
            ['type' => 'text', 'name' => 'title', 'title' => lang('行为名称'), 'tips' => lang('可以是中文'), 'attr' => 'data-rule="required;" data-msg-required="行为名称不能为空"'],
            ['type' => 'textarea', 'name' => 'remark', 'title' => lang('行为描述')],
            ['type' => 'textarea', 'name' => 'rule', 'title' => lang('行为规则'), 'tips' => '示例：table:member|field:score|condition:uid={$self} AND status>-1|rule:9-2+3+score*1/1|cycle:24|max:1;<br>表示修改think_member表的score字段，修改条件为uid={$self} AND status>-1，修改的值为9-2+3+score*1/1，每24个小时最多执行一次。<br>用TP的写法来表示：Db::name("Member")->where("uid={$self} AND status>-1")->setField("score", "9-2+3+score*1/1");'],
            ['type' => 'textarea', 'name' => 'log', 'title' => lang('日志规则'), 'tips' => '记录日志备注时按此规则来生成，支持[变量|函数]。目前变量有：user,time,model,record,data,details  例如：[user|get_adminname] 添加了数据模型：[details]'],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), '', 'extra' => [lang('禁用'), lang('启用')], 'value' => 1]
        ];
        $this->assign('page_title', lang('新增行为'));
        $this->assign('form_items', $fields);
        return $this->fetch('public/add');
    }

    /**
     * 编辑
     * @param int $id 行为id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error(lang('缺少参数'));

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Action.update');
            // 验证失败 输出错误信息
            if (true !== $result) $this->error($result);

            if (ActionModel::update($data)) {
                // 记录行为
                action_log('admin_action_edit', 'admin_action', $id, UID, $data['name']);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        // 获取数据
        $info = ActionModel::where('id', $id)->find();

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'select', 'name' => 'module', 'title' => lang('所属模块'), 'extra' => ModuleModel::getModule()],
            ['type' => 'text', 'name' => 'name', 'title' => lang('行为标识'), 'tips' => lang('必填，可由英文字母、数字组成,必须使用英文字母开头'), 'attr' => 'data-rule="required;name;" data-rule-name="[/^[\w\d]{3,100}$/, \'请输入正确的行为标识\']" data-msg-required="行为标识不能为空"'],
            ['type' => 'text', 'name' => 'title', 'title' => lang('行为名称'), 'tips' => lang('可以是中文'), 'attr' => 'data-rule="required;" data-msg-required="行为名称不能为空"'],
            ['type' => 'textarea', 'name' => 'remark', 'title' => lang('行为描述')],
            ['type' => 'textarea', 'name' => 'rule', 'title' => lang('行为规则'), 'tips' => '示例：table:member|field:score|condition:uid={$self} AND status>-1|rule:9-2+3+score*1/1|cycle:24|max:1;<br>表示修改think_member表的score字段，修改条件为uid={$self} AND status>-1，修改的值为9-2+3+score*1/1，每24个小时最多执行一次。<br>用TP的写法来表示：Db::name("Member")->where("uid={$self} AND status>-1")->setField("score", "9-2+3+score*1/1");'],
            ['type' => 'textarea', 'name' => 'log', 'title' => lang('日志规则'), 'tips' => '记录日志备注时按此规则来生成，支持[变量|函数]。目前变量有：user,time,model,record,data,details'],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), '', 'extra' => [lang('禁用'), lang('启用')], 'value' => 1]
        ];
        $this->assign('page_title', lang('新增行为'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('public/add');
    }
}