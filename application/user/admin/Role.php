<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\admin;

use app\admin\admin\Base;
use app\user\model\Role as RoleModel;
use app\user\model\Menu as MenuModel;
use service\Format;
use think\Db;

/**
 * 角色控制器
 * @package app\admin\admin
 */
class Role extends Base
{
    /**
     * 角色列表页
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index()
    {
        // 数据列表
        $data_list = RoleModel::order('pid,id')->paginate();
        // 角色列表
        $list_role = RoleModel::column('id,name');
        $list_role[0] = lang('顶级角色');

        $fields = [
            ['id', 'ID'],
            ['name', lang('角色名称')],
            ['description', lang('描述')],
            ['create_time', lang('创建时间'), '', '', '', 'text-center'],
            ['status', lang('状态'), 'status', '', '', 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];

        return Format::ins()//实例化
            ->setPageTitle(lang('会员角色管理'))
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (!isset($data['menu_auth'])) {
                $data['menu_auth'] = [];
            } else {
                $data['menu_auth'] = explode(',', $data['menu_auth']);
            }
            // 验证
            $result = $this->validate($data, 'Role.add');
            // 验证失败 输出错误信息
            if (true !== $result) {
                $this->error($result);
            }

            // 添加数据
            if ($role = RoleModel::create($data)) {
                $this->success(lang('新增成功'), url('index'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        // 菜单列表
        $menus = cache('user_access_menus');
        if (!$menus) {
            $menus = MenuModel::where(['status' => 1])
                ->order('sort,aid')
                ->field('aid,pid,sort,url_value,title,icon')->select();

            // 非开发模式，缓存菜单
            if (config('develop_mode') == 0) {
                cache('user_access_menus', $menus);
            }
        }

        $this->assign('page_title', lang('新增'));
        $this->assign('menu_list', MenuModel::where('pid', 0)->column('aid,title'));
        $this->assign('menus', $menus);
        return $this->fetch();
    }

    /**
     * 编辑
     * @param null $id 角色id
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!isset($data['menu_auth'])) {
                $data['menu_auth'] = [];
            } else {
                $data['menu_auth'] = explode(',', $data['menu_auth']);
            }
            // 验证
            $result = $this->validate($data, 'Role.edit');
            // 验证失败 输出错误信息
            if (true !== $result) {
                $this->error($result);
            }


            if (RoleModel::update($data)) {
                $this->success(lang('编辑成功'), url('index'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        // 获取数据
        $info = RoleModel::get($id);

        $menus = MenuModel::where(['status' => 1])
            ->order('sort,aid')
            ->field('aid,pid,sort,url_value,title,icon')->select();
        foreach ($menus as $k => $m) {
            if (in_array($m['aid'], $info['menu_auth'])) {
                $menus[$k]['checked'] = true;
            }
        }

        $this->assign('page_title', lang('编辑会员角色'));
        $this->assign('menus', $menus);
        $this->assign('menu_list', MenuModel::where('pid', 0)->column('aid,title'));
        $this->assign('info', $info);
        return $this->fetch();
    }
}
