<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\admin\model\Role as RoleModel;
use app\admin\model\Menu as MenuModel;
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
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function index()
    {
        // 非超级管理员检查可管理角色
        if (session('admin_auth.role') != 1) {
            $role_list = RoleModel::getChildsId(session('admin_auth.role'));
            $map[] = ['id', 'in', $role_list];
        }
        // 数据列表
        $data_list = RoleModel::where($map)->order('pid,id')->paginate();
        // 角色列表
        $list_role = RoleModel::column('id,name');
        $list_role[0] = lang('顶级角色');

        $fields = [
            ['id', 'ID'],
            ['name', lang('角色名称')],
            ['pid', lang('上级角色'), $list_role],
            ['description', lang('描述')],
            ['create_time', lang('创建时间'), '', '', '', 'text-center'],
            ['status', lang('状态'), 'status', '', '', 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];


        return Format::ins()//实例化
            ->hideCheckbox()
            ->setPageTitle(lang('角色管理'))
            ->addColumns($fields)//设置字段
            ->setTopButton($this->top_button[0])
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
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
            $result = $this->validate($data, 'Role');
            // 验证失败 输出错误信息
            if (true !== $result) $this->error($result);

            // 非超级管理员检查可添加角色
            if (session('admin_auth.role') != 1) {
                $role_list = RoleModel::getChildsId(session('admin_auth.role'));
                if ($data['pid'] != session('admin_auth.role') && !in_array($data['pid'], $role_list)) {
                    $this->error(lang('所属角色设置错误，没有权限添加该角色'));
                }
            }

            // 非超级管理员检查可添加的节点权限
            if (session('admin_auth.role') != 1) {
                $menu_auth = RoleModel::where('id', session('admin_auth.role'))->value('menu_auth');
                $menu_auth = json_decode($menu_auth, true);
                $menu_auth = array_intersect($menu_auth, $data['menu_auth']);
                $data['menu_auth'] = $menu_auth;
            }

            // 添加数据
            if ($role = RoleModel::create($data)) {
                // 记录行为
                action_log('admin_role_add', 'admin_role', $role['id'], UID, $data['name']);
                $this->success(lang('新增成功'), url('index'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        // 菜单列表
        $menus = cache('access_menus');
        if (!$menus) {
            $modules = Db::name('module')->where('status', 1)->column('name');
            $map[] = ['status', '=', 1];
            // 非超级管理员角色，只能分配当前角色所拥有的权限
            if (session('admin_auth.role') != 1) {
                $menu_auth = RoleModel::where('id', session('admin_auth.role'))->value('menu_auth');
                $menu_auth = json_decode($menu_auth, true);
                $map[] = ['id', 'in', $menu_auth];
            }
            //查询出所有被禁用的菜单，子类也不显示
            $disalbeId = MenuModel::where('status',0)->column('id');
            if($disalbeId) {
                foreach($disalbeId as $v) {
                    //父级菜单禁用，子菜单也禁用
                    $child_ids =   MenuModel::getChildsId($v);
                    $disalbeId = array_merge($disalbeId,$child_ids);
                }
            }
            $map[] = ['pid', 'not in', $disalbeId];
            $menus = MenuModel::where($map)
                ->order('sort,id')
                ->field('id,pid,sort,url_value,title,icon')->select();

            // 非开发模式，缓存菜单
            if (config('develop_mode') == 0) {
                cache('access_menus', $menus);
            }
        }

        if (session('admin_auth.role') != 1) {
            $role_list = RoleModel::getTree(null, false, session('admin_auth.role'));
        } else {
            $role_list = RoleModel::getTree();
        }

        $this->assign('page_title', lang('新增'));
        $this->assign('role_list', $role_list);
        $this->assign('module_list', MenuModel::where('pid', 0)->column('id,title'));
        $this->assign('menus', $menus);
        return $this->fetch();
    }

    /**
     * 编辑
     * @param null $id 角色id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error(lang('缺少参数'));
        if ($id == 1) $this->error(lang('超级管理员不可修改'));

        // 非超级管理员检查可编辑角色
        if (session('admin_auth.role') != 1) {
            $role_list = RoleModel::getChildsId(session('admin_auth.role'));
            if (!in_array($id, $role_list)) {
                $this->error(lang('权限不足，当前没有编辑该角色的权限'));
            }
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
            $result = $this->validate($data, 'Role');
            // 验证失败 输出错误信息
            if (true !== $result) $this->error($result);

            // 非超级管理员检查可添加角色
            if (session('admin_auth.role') != 1) {
                $role_list = RoleModel::getChildsId(session('admin_auth.role'));
                if ($data['pid'] != session('admin_auth.role') && !in_array($data['pid'], $role_list)) {
                    $this->error(lang('所属角色设置错误，没有权限添加该角色'));
                }
            }

            // 检查所属角色不能是自己当前角色及其子角色
            $role_list = RoleModel::getChildsId($data['id']);
            if ($data['id'] == $data['pid'] || in_array($data['pid'], $role_list)) {
                $this->error(lang('所属角色设置错误，禁止设置为当前角色及其子角色'));
            }

            // 非超级管理员检查可添加的节点权限
            if (session('admin_auth.role') != 1) {
                $menu_auth = RoleModel::where('id', session('admin_auth.role'))->value('menu_auth');
                $menu_auth = json_decode($menu_auth, true);
                $menu_auth = array_intersect($menu_auth, $data['menu_auth']);
                $data['menu_auth'] = $menu_auth;
            }

            if (RoleModel::update($data)) {
                // 更新成功，循环处理子角色权限
                RoleModel::resetAuth($id, $data['menu_auth']);
                role_auth();
                // 记录行为
                action_log('admin_role_edit', 'admin_role', $id, UID, $data['name']);
                $this->success(lang('编辑成功'), url('index'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        // 获取数据
        $info = RoleModel::get($id);
        if (session('admin_auth.role') != 1) {
            $role_list = RoleModel::getTree($id, false, session('admin_auth.role'));
        } else {
            $role_list = RoleModel::getTree($id, lang('顶级角色'));
        }

        //$modules = Db::name('module')->where('status', 1)->column('name');
        $map[] = ['status', '=', 1];
        // 非超级管理员角色，只能分配当前角色所拥有的权限
        if (session('admin_auth.role') != 1) {
            $menu_auth = RoleModel::where('id', session('admin_auth.role'))->value('menu_auth');
            $menu_auth = json_decode($menu_auth, true);
            $map[] = ['id', 'in', $menu_auth];
        }
        //查询出所有被禁用的菜单，子类也不显示
        $disalbeId = MenuModel::where('status',0)->column('id');
        if($disalbeId) {
            foreach($disalbeId as $v) {
                //父级菜单禁用，子菜单也禁用
                $child_ids =   MenuModel::getChildsId($v);
                $disalbeId = array_merge($disalbeId,$child_ids);
            }
        }
        $map[] = ['pid', 'not in', $disalbeId];

        $menus = MenuModel::where($map)
            ->order('sort,id')
            ->field('id,pid,sort,url_value,title,icon')->select();
        foreach ($menus as $k => $m) {
            if (in_array($m['id'], $info['menu_auth'])) {
                $menus[$k]['checked'] = true;
            }
        }

        $this->assign('page_title', lang('编辑角色'));
        $this->assign('menus', $menus);
        $this->assign('role_list', $role_list);
        $this->assign('module_list', MenuModel::where('pid', 0)->column('id,title'));
        $this->assign('info', $info);
        return $this->fetch();
    }
}
