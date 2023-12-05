<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\admin\model\Addons as AddonsModel;
use app\admin\model\HookAddons as HookAddonsModel;
use app\admin\model\Module as ModuleModel;
use service\File;
use think\Db;
use service\Sql;
use service\Format;

/**
 * 插件管理控制器
 * @package app\admin\controller
 */
class Addons extends Base
{
    /**
     * 首页
     * @param string $group 分组
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index($group = 'local')
    {
        // 配置分组信息
        $list_group = ['local' => lang('本地插件')];
        foreach ($list_group as $key => $value) {
            $tab_list[$key]['title'] = $value;
            $tab_list[$key]['url'] = url('index', ['group' => $key]);
        }

        switch ($group) {
            case 'local':
                // 查询条件
                $keyword = $this->request->get('keyword', '');

                if (input('?param.status') && input('param.status') != '_all') {
                    $status = input('param.status');
                } else {
                    $status = '';
                }

                $AddonsModel = new AddonsModel;
                $result = $AddonsModel->getAll($keyword, $status);

                if ($result['addons'] === false) {
                    $this->error($AddonsModel->getError());
                }


                $this->assign('page_title', lang('插件管理'));
                $this->assign('addons', $result['addons']);
                $this->assign('total', $result['total']);
                $this->assign('tab_nav', ['tab_list' => $tab_list, 'active' => $group]);
                return $this->fetch();
                break;
            case 'online':
                break;
        }
    }

    /**
     * 安装插件
     * @param string $name 插件标识
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function install($name = '')
    {
        // 设置最大执行时间和内存大小
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1024M');

        $addons_name = trim($name);
        if ($addons_name == '') $this->error(lang('插件不存在'));

        $addons_class = get_addons_class($addons_name);

        if (!class_exists($addons_class)) {
            $this->error(lang('插件不存在'));
        }

        // 实例化插件
        $addons = new $addons_class;
        // 插件预安装
        if (!$addons->install()) {
            $this->error('插件预安装失败!原因：' . $addons->getError());
        }

        // 添加钩子
        if (isset($addons->hooks) && !empty($addons->hooks)) {
            if (!HookAddonsModel::addHooks($addons->hooks, $name)) {
                $this->error(lang('安装插件钩子时出现错误，请重新安装'));
            }
            cache('hook_addons', null);
        }

        // 执行安装插件sql文件
        $sql_file = realpath(ROOT_PATH . 'addons/' . $name . '/install.sql');
        if (file_exists($sql_file)) {
            if (isset($addons->database_prefix) && $addons->database_prefix != '') {
                $sql_statement = Sql::getSqlFromFile($sql_file, false, [$addons->database_prefix => config('database.prefix')]);
            } else {
                $sql_statement = Sql::getSqlFromFile($sql_file);
            }

            if (!empty($sql_statement)) {
                foreach ($sql_statement as $value) {
                    Db::execute($value);
                }
            }
        }

        // 插件配置信息
        $addons_info = $addons->info;

        // 验证插件信息
        $result = $this->validate($addons_info, 'Addons');
        // 验证失败 输出错误信息
        if (true !== $result) $this->error($result);

        // 并入插件配置值
        $addons_info['config'] = $addons->getConfigValue();

        // 将插件信息写入数据库
        if (AddonsModel::create($addons_info)) {
            cache('addons_all', null);
            // 记录行为
            action_log('admin_addons_install', 'admin_addons', 0, UID, $addons_name);
            $this->success(lang('插件安装成功'));
        } else {
            $this->error(lang('插件安装失败'));
        }
    }

    /**
     * 卸载插件
     * @param string $name 插件标识
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function uninstall($name = '')
    {
        $addons_name = trim($name);
        if ($addons_name == '') $this->error(lang('插件不存在'));

        $class = get_addons_class($addons_name);
        if (!class_exists($class)) {
            $this->error(lang('插件不存在'));
        }

        // 实例化插件
        $addons = new $class;
        // 插件预卸
        if (!$addons->uninstall()) {
            $this->error('插件预卸载失败!原因：' . $addons->getError());
        }

        // 卸载插件自带钩子
        if (isset($addons->hooks) && !empty($addons->hooks)) {
            if (false === HookAddonsModel::deleteHooks($addons_name)) {
                $this->error(lang('卸载插件钩子时出现错误，请重新卸载'));
            }
            cache('hook_addons', null);
        }

        // 执行卸载插件sql文件
        $sql_file = realpath(ROOT_PATH . 'addons/' . $addons_name . '/uninstall.sql');
        if (file_exists($sql_file)) {
            if (isset($addons->database_prefix) && $addons->database_prefix != '') {
                $sql_statement = Sql::getSqlFromFile($sql_file, true, [$addons->database_prefix => config('database.prefix')]);
            } else {
                $sql_statement = Sql::getSqlFromFile($sql_file, true);
            }

            if (!empty($sql_statement)) {
                Db::execute($sql_statement);
            }
        }

        // 删除插件信息
        if (AddonsModel::where('name', $addons_name)->delete()) {
            cache('addons_all', null);
            // 记录行为
            action_log('admin_addons_uninstall', 'admin_addons', 0, UID, $addons_name);
            $this->success(lang('插件卸载成功'));
        } else {
            $this->error(lang('插件卸载失败'));
        }
    }

    /**
     * 插件参数设置
     * @param string $name 插件名称
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function config($name = '')
    {
        // 更新配置
        if ($this->request->isPost()) {
            $data = $this->request->post();
            unset($data['__token__']);
            $data = json_encode($data);

            if (false !== AddonsModel::where('name', $name)->update(['config' => $data])) {
                // 记录行为
                action_log('admin_addons_config', 'admin_addons', 0, UID, $name);
                $this->success(lang('更新成功'), 'index');
            } else {
                $this->error(lang('更新失败'));
            }
        }

        $addons_class = get_addons_class($name);
        // 实例化插件
        $addons = new $addons_class;
        $trigger = isset($addons->trigger) ? $addons->trigger : [];

        // 插件配置值
        $info = AddonsModel::where('name', $name)->field('id,name,config')->find();
        $db_config = json_decode($info['config'], true);

        // 插件配置项
        $config = include ROOT_PATH . 'addons/' . $name . '/config.php';

        if (empty($db_config)) {
            $this->assign('form_items', $config);
        } else {
            $this->assign('form_items', $this->setData($config, $db_config));
        }
        $this->assign('page_title', lang('参数设置'));
        return $this->fetch('public/edit');

    }

    /**
     * 插件管理
     * @param string $name 插件名
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function manage($name = '')
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 加载自定义后台页面
        if (addons_action_exists($name, 'Admin', 'index')) {
            return addons_action($name, 'Admin', 'index');
        }

        // 加载系统的后台页面
        $class = get_addons_class($name);
        if (!class_exists($class)) {
            $this->error($name . lang('插件不存在'));
        }

        // 实例化插件
        $plugin = new $class;

        // 获取后台字段信息，并分析
        if (isset($plugin->admin)) {
            $admin = $this->parseAdmin($plugin->admin);
        } else {
            $admin = $this->parseAdmin();
        }

        if (!addons_model_exists($name)) {
            $this->error(lang('插件') . ': ' . $name . ' ' . lang('缺少模型文件'));
        }

        // 获取插件模型实例
        $PluginModel = get_addons_model($name);

        $data_list = $PluginModel->paginate();

        return Format::ins() //实例化
        ->addColumns($admin['columns'])//设置字段
        ->setTopButton(['title' => lang('返回插件列表'), 'href' => ['index'], 'icon' => 'fa fa-reply pr5', 'class' => 'btn btn-sm mr5 btn-default '])
            ->setTopButton(['title' => lang('新增'), 'href' => ['add', ['name' => $name]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-default '])
            ->setRightButtons($admin['right_buttons']) // 批量添加右侧按钮
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 插件新增方法
     * @param string $name 插件名称
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    /*public function add($name = '')
    {

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (AddonsModel::where('name', $data['name'])->count()) {
                $this->error('模块名已经存在，请更换');
            }
            if (AddonsModel::where('name', $data['name'])->count()) {
                $this->error('插件名已经存在，请更换');
            }
            $data['identifier'] = $data['name'] . '.zbphp.addons';
            if (!File::mk_dir(APP_PATH . $data['name'])) {
                $this->error(lang('创建插件') . $data['name'] . '目录失败,请检查权限或是否已存在');
            }
            if (!File::mk_dir(APP_PATH . $data['name'] . "/admin")) {
                $this->error('创建admin目录失败,请检查权限或是否已存在');
            }
            if (!File::mk_dir(APP_PATH . $data['name'] . "/controller")) {
                $this->error('创建controller目录失败,请检查权限或是否已存在');
            }
            if (!File::mk_dir(APP_PATH . $data['name'] . "/model")) {
                $this->error('创建model目录失败,请检查权限或是否已存在');
            }
            if (!File::mk_dir(APP_PATH . $data['name'] . "/validate")) {
                $this->error('创建validate目录失败,请检查权限或是否已存在');
            }
            if (!File::mk_dir(APP_PATH . $data['name'] . "/view")) {
                $this->error('创建view目录失败,请检查权限或是否已存在');
            }
            if (!File::mk_dir(APP_PATH . $data['name'] . "/sql")) {
                $this->error('创建sql目录失败,请检查权限或是否已存在');
            }
            if (!File::mk_dir(APP_PATH . $data['name'] . "/api/controller/v1")) {
                $this->error('创建api目录失败,请检查权限或是否已存在');
            }

            //生成默认接口
            $c_file_dir = APP_PATH . $data['name'] . '/' . '/api/controller/v1' . '/Index.php';
            $c_file_content = File::read_file(ROOT_PATH . 'data/page/addons/api.tpl');
            $c_file_content = str_replace(['{module}', '{model}', '{title}', '{fields}', '{fields_add}'], [$data['name'], 'Index', $data['title'], '', ''], $c_file_content);
            if (!File::write_file($c_file_dir, $c_file_content)) {
                $this->error('创建默认api文件失败,请检查权限或是否已存在');
            }
            AddonsModel::create($data);
            $this->success(lang('新增成功'), cookie('__forward__'));

        }
        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('插件名称'), 'tips' => '插件名称，如：快递鸟，ExpressBird'],
            ['type' => 'text', 'name' => 'title', 'title' => lang('插件标题')],
            ['type' => 'icon', 'name' => 'icon', 'title' => lang('图标'), 'tips' => '例如： fa fa-fw fa-user'],
            ['type' => 'textarea', 'name' => 'description', 'title' => lang('插件描述')],
            ['type' => 'text', 'name' => 'author', 'title' => lang('作者')],
            ['type' => 'text', 'name' => 'author_url', 'title' => lang('作者主页'), "value" => 'javascript:;'],
            ['type' => 'text', 'name' => 'config', 'title' => lang('配置'), 'tips' => '插件配置信息，json格式，如：{"AppID":"1271973","AppSecret":"4eecd5a5-03ec-4f8c-a215-e748630f6990","is_online":"0"}', 'attr' => ''],
            ['type' => 'text', 'name' => 'version', 'title' => lang('版本'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序')],
            ['type' => 'radio', 'name' => 'status', 'title' => '开关', 'tips' => '', 'attr' => '', 'extra' => [0 => '禁用', 1 => '启用'], 'value' => 0]
        ];
        $this->assign('page_title', lang('新增'));
        $this->assign('form_items', $fields);
        return $this->fetch('public/add');

    }*/

    /**
     * 插件新增方法
     * @param string $name 插件名称
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function add($name = '')
    {
        // 如果存在自定义的新增方法，则优先执行
        if (addons_action_exists($name, 'Admin', 'add')) {
            $params = $this->request->param();
            return addons_action($name, 'Admin', 'add', $params);
        }

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 执行插件的验证器（如果存在的话）
            if (addons_validate_exists($name)) {
                $plugin_validate = get_addons_validate($name);
                if (!$plugin_validate->check($data)) {
                    // 验证失败 输出错误信息
                    $this->error($plugin_validate->getError());
                }
            }

            // 实例化模型并添加数据
            $PluginModel = get_addons_model($name);
            if ($PluginModel->create($data)) {
                $this->success('新增成功', cookie('__forward__'));
            } else {
                $this->error('新增失败');
            }
        }

        // 获取插件模型
        $class = get_addons_class($name);
        if (!class_exists($class)) {
            $this->error('插件不存在！');
        }

        // 实例化插件
        $plugin = new $class;
        if (!isset($plugin->fields)) {
            $this->error('插件新增、编辑字段不存在！');
        }

        $this->assign('page_title','新增');
        $this->assign('form_items',$plugin->fields);
        return $this->fetch('public/add');

    }

    /**
     * 编辑插件方法
     * @param string $id 数据id
     * @param string $plugin_name 插件名称
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = '', $name = '')
    {
        // 如果存在自定义的编辑方法，则优先执行
        if (addons_action_exists($name, 'Admin', 'edit')) {
            $params = $this->request->param();
            return addons_action($name, 'Admin', 'edit', $params);
        }

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 执行插件的验证器（如果存在的话）
            if (addons_action_exists($name)) {
                $plugin_validate = get_addons_validate($name);
                if (!$plugin_validate->check($data)) {
                    // 验证失败 输出错误信息
                    $this->error($plugin_validate->getError());
                }
            }

            // 实例化模型并添加数据
            $PluginModel = get_addons_model($name);
            if (false !== $PluginModel->isUpdate(true)->save($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        // 获取插件类名
        $class = get_addons_class($name);
        if (!class_exists($class)) {
            $this->error(lang('插件不存在'));
        }

        // 实例化插件
        $plugin = new $class;
        if (!isset($plugin->fields)) {
            $this->error(lang('插件新增、编辑字段不存在'));
        }

        // 获取数据
        $PluginModel = get_addons_model($name);
        $info = $PluginModel->find($id);
        if (!$info) {
            $this->error(lang('找不到数据'));
        }

        $this->assign('page_title', lang('编辑'));
        $this->assign('form_items', $this->setData($plugin->fields, $info));
        return $this->fetch('public/edit');
    }


    /**
     * 设置状态
     * @param string $type 状态类型:enable/disable
     * @return mixed|void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function setStatus($type = '')
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        empty($ids) && $this->error(lang('缺少主键'));

        $status = $type == 'enable' ? 1 : 0;

        $addonss = AddonsModel::where('id', 'in', $ids)->value('name');
        if ($addonss) {
            HookAddonsModel::$type($addonss);
        }

        if (false !== AddonsModel::where('id', 'in', $ids)->setField('status', $status)) {
            // 记录日志
            call_user_func_array('action_log', ['admin_addons_' . $type, 'admin_addons', 0, UID, $addonss]);
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }

    /**
     * 禁用插件
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function disable()
    {
        $this->setStatus('disable');
    }

    /**
     * 启用插件
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function enable()
    {
        $this->setStatus('enable');
    }

    /**
     * 删除插件数据
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function delete($name = '')
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        empty($ids) && $this->error(lang('缺少主键'));

        // 获取插件类名
        $class = get_addons_class($name);
        if (!class_exists($class)) {
            $this->error(lang('插件不存在'));
        }
        // 实例化模型并添加数据
        $PluginModel = get_addons_model($name);
        if (false !== $PluginModel::where('id', 'in', $ids)->delete()) {
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }


    /**
     * 分析后台字段信息
     * @param array $data 字段信息
     * @return array
     * @author 似水星辰 [2630481389@qq.com]
     */
    private function parseAdmin($data = [])
    {
        $admin = [
            'title' => lang('数据列表'),
            'search_title' => '',
            'search_field' => [],
            'order' => '',
            'filter' => '',
            'table_name' => '',
            'columns' => [],
            'right_buttons' => [],
            'top_buttons' => [],
            'customs' => [],
        ];

        if (empty($data)) {
            return $admin;
        }

        // 处理工具栏按钮链接
        if (isset($data['top_buttons']) && !empty($data['top_buttons'])) {
            $this->parseButton('top_buttons', $data);
        }

        // 处理右侧按钮链接
        if (isset($data['right_buttons']) && !empty($data['right_buttons'])) {
            $this->parseButton('right_buttons', $data);
        }

        return array_merge($admin, $data);
    }

    /**
     * 解析按钮链接
     * @param string $button 按钮名称
     * @param array $data 字段信息
     * @return array
     * @author 似水星辰 [2630481389@qq.com]
     */
    private function parseButton($button = '', &$data)
    {
        foreach ($data[$button] as $key => &$value) {
            // 处理自定义按钮
            if ($key === 'customs') {
                if (!empty($value)) {
                    foreach ($value as &$custom) {
                        if (isset($custom['href']['url']) && $custom['href']['url'] != '') {
                            $params = isset($custom['href']['params']) ? $custom['href']['params'] : [];
                            $custom['href'] = plugin_url($custom['href']['url'], $params);
                            $data['custom_' . $button][] = $custom;
                        }
                    }
                }
                unset($data[$button][$key]);
            }
            if (!is_numeric($key) && isset($value['href']['url']) && $value['href']['url'] != '') {
                $value['href'] = plugin_url($value['href']['url']);
            }
        }
    }
}
