<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\admin\model\Hook as HookModel;
use app\admin\model\HookAddons;
use service\Format;

/**
 * 钩子控制器
 * @package app\admin\controller
 */
class Hook extends Base
{
    /**
     * 钩子管理
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index()
    {

        // 数据列表
        $data_list = HookModel::order('id desc')->paginate();

		$fields = [
			['name', lang('名称')],
            ['description', lang('描述')],
            ['plugin', lang('所属插件'), 'callback', function($plugin){
                return $plugin == '' ? lang('系统') : $plugin;
            }],
            ['system', lang('系统钩子'), 'status', '', [lang('否'),lang('是')],'text-center'],
            ['status', lang('状态'), 'status','','','text-center'],
            ['right_button', lang('操作'), 'btn','','','text-center']
		];
		return Format::ins() //实例化
            ->hideCheckbox()
			->setPageTitle(lang('钩子管理')) // 设置页面标题
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
            // 表单数据
            $data = $this->request->post();
            $data['system'] = 0;

            // 验证
            $result = $this->validate($data, 'Hook');
            if(true !== $result) $this->error($result);

            if ($hook = HookModel::create($data)) {
                cache('hook_plugins', null);
                // 记录行为
                action_log('hook_add', 'hook', $hook['id'], UID, $data['name']);
                $this->success(lang('新增成功'), 'index');
            } else {
                $this->error(lang('新增失败'));
            }
        }

		$fields =[
			['type' => 'text', 'name' => 'name', 'title' => lang('钩子名称'), 'tips' =>lang('由字母和下划线组成'),'attr' => 'data-rule="required;name;" data-rule-name="[/^[a-zA-Z][a-zA-Z0-9_]*$/, \'请输入正确的配置标识，只能使用英文和下划线，必须以英文字母开头\']" data-msg-required="钩子名称不能为空"'],
            ['type' => 'textarea', 'name' => 'description', 'title' => lang('钩子描述')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), '', 'extra' => [lang('禁用'), lang('启用')], 'value' => 1]
		];
		$this->assign('page_title',lang('新增钩子'));
		$this->assign('form_items', $fields);
        return $this->fetch('public/add');

    }

    /**
     * 编辑
     * @param int $id 钩子id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function edit($id = 0)
    {
        if ($id === 0) $this->error(lang('参数错误'));

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Hook');
            if(true !== $result) $this->error($result);

            if ($hook = HookModel::update($data)) {
                // 调整插件顺序
                if ($data['sort'] != '') {
                    HookAddons::sort($data['name'], $data['sort']);
                }
                cache('hook_plugins', null);
                // 记录行为
                action_log('hook_edit', 'hook', $hook['id'], UID, $data['name']);
                $this->success(lang('编辑成功'), 'index');
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        // 获取数据
        $info = HookModel::get($id);

        // 该钩子的所有插件
        $hooks = HookAddons::where('hook', $info['name'])->order('sort')->column('plugin');
        $hooks = parse_array($hooks);

		$fields =[
			['type' => 'hidden', 'name' => 'id', ],
			['type' => 'text', 'name' => 'name', 'title' => lang('钩子名称'), 'tips' =>lang('由字母和下划线组成'),'attr' => 'data-rule="required;name;" data-rule-name="[/^[a-zA-Z][a-zA-Z0-9_]*$/, \'请输入正确的配置标识，只能使用英文和下划线，必须以英文字母开头\']" data-msg-required="钩子名称不能为空"'],
            ['type' => 'textarea', 'name' => 'description', 'title' => lang('钩子描述')],
			['type' => 'sort', 'name' => 'sort', 'title' => lang('插件排序'), 'extra' => $hooks , 'value' => implode(',',$hooks)],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), '', 'extra' => [lang('禁用'), lang('启用')], 'value' => 1]
		];
		$this->assign('page_title',lang('编辑钩子'));
		$this->assign('set_style',['/static/plugins/jquery-nestable/jquery.nestable.css']);
		$this->assign('set_script',['/static/plugins/jquery-nestable/jquery.nestable.js']);
		$this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('public/edit');
    }
}
