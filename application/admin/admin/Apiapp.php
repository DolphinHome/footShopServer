<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\common\model\Apiapp as ApiApps;
use service\Str;
use service\Format;

/**
 * 应用管理控制器
 * @package app\admin\admin
 */
class Apiapp extends Base {
    /**
     * 应用列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index() {
		cookie('__forward__', $_SERVER['REQUEST_URI']);

		$map = [];
        // 数据列表
        $data_list = ApiApps::where($map)->order('id DESC')->paginate();

		$fields =[
			['id', 'ID', 'text'],
            ['app_name', lang('应用名称'), 'text'],
            ['app_id', 'AppId', 'text'],
            ['app_secret', 'AppSecret', 'text'],
			['app_limitTime', 'Token'.lang('有效期'), 'text'],
			['create_time', lang('创建时间'),'','','','text-center'],
            ['status', lang('状态'), 'status','','','text-center'],
            ['right_button', lang('操作'), 'btn','','','text-center']
		];

		return Format::ins() //实例化
            ->hideCheckbox()
			->setPageTitle(lang('应用管理')) // 设置页面标题
			->addColumns($fields)//设置字段
			->setTopButtons($this->top_button)
			->setRightButtons($this->right_button)
			->setData($data_list)//设置数据
			->fetch();//显示
    }    

	/**
     * 新增应用
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Apiapp.add');
            if(true !== $result) $this->error($result);

            if ($res = ApiApps::create($data)) {
                // 记录行为
                action_log('admin_api_app_add', 'admin_api_app', $res->id, UID,$data['app_name']);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

		$data['app_id'] = Str::randString(8, 1);
	    $data['app_secret'] = Str::randString(32);
		$fields = [
			['type' => 'text', 'name' => 'app_name', 'title' => lang('应用名称'), 'attr' => 'data-rule="required;"'],
			['type' => 'text', 'name' => 'app_id', 'title' => 'AppId', 'tips' => lang('自动生成，请勿修改'), 'value' => $data['app_id'], 'attr' => 'readonly'],
			['type' => 'text', 'name' => 'app_secret', 'title' => 'AppSecret', 'tips' => lang('自动生成，请勿修改'), 'value' => $data['app_secret'], 'attr' => 'readonly'],
			['type' => 'text', 'name' => 'app_limitTime', 'title' => 'Token'.lang('有效期'), 'tips' => lang('单位:秒'), 'value' => 7200],
			['type' => 'textarea', 'name' => 'app_info', 'title' => lang('应用描述')],
		];
		$this->assign('page_title',lang('新增应用'));
		$this->assign('form_items',$fields);
        return $this->fetch('public/add');
    }

	/**
     * 编辑应用
     * @param int $id 应用id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function edit($id = 0)
    {
		if ($id === 0) $this->error(lang('缺少参数'));
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Apiapp.edit');
            if(true !== $result) $this->error($result);

            if ($res = ApiApps::update($data)) {
                // 记录行为
                action_log('admin_api_app_edit', 'admin_api_app', $id, UID,$data['app_name']);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

		 // 获取数据
        $info = ApiApps::get($id);

		$fields = [
			['type' => 'hidden', 'name' => 'id'],
			['type' => 'text', 'name' => 'app_name', 'title' => lang('应用名称'), 'attr' => 'data-rule="required;"'],
			['type' => 'text', 'name' => 'app_id', 'title' => 'AppId', 'tips' => lang('自动生成，请勿修改'), 'attr' => 'disabled'],
			['type' => 'text', 'name' => 'app_secret', 'title' => 'AppSecret', 'tips' => lang('自动生成，请勿修改'), 'attr' => 'disabled'],
			['type' => 'text', 'name' => 'app_limitTime', 'title' => 'Token'.lang('有效期'), 'tips' => lang('单位:秒')],
			['type' => 'textarea', 'name' => 'app_info', 'title' => lang('应用描述')],
		];
		$this->assign('page_title',lang('编辑应用'));
		$this->assign('form_items',$this->setData($fields,$info));
        return $this->fetch('public/edit');
    }
}
