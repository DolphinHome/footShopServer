<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\common\model\Apilist as ApiLists;
use app\admin\model\Apigroup as ApigroupModel;
use app\common\model\ApiFields;
use service\Format;
use service\ApiReturn;
use service\Tree;
use Think\Db;

/**
 * api列表及其操作
 * @package app\admin\admin
 */
class Apilist extends Base
{

    /**
     * api列表
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index($module = "")
    {

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        // 配置分组信息
        $list_group = \app\admin\model\Module::column('name,title');

        $tab_list = [];
        foreach ($list_group as $key => $value) {
            $tab_list[$key]['title'] = $value;
            $tab_list[$key]['url'] = url('index', ['module' => $key]);
        }
        //查询条件补全，不能漏掉module ，wangph修改于2021-4-20
        if (!isset($map['module']) && $module) {
            $map[] = ['api.module', 'eq', $module];
        }

        $data_list = ApiLists::alias('api')->join('module m', 'api.module = m.name', 'left')->field('api.*,m.title')->where($map)->order('id DESC')->paginate();

        $fields = [
            ['id', 'ID', 'text'],
            ['title', lang('所属模块'), 'link', url('index', ['module' => '__module__']), '', 'text-center'],
            ['apiName', lang('真实地址'), 'text'],
            ['hash', lang('接口标识'), 'link', url('apiinfo', ['hash' => '__hash__', 'layer' => 1]), 'data-toggle="dialog" data-width="1200" data-height="900"'],
            ['method', lang('请求方式'), 'status', '', [lang('不限'), 'POST', 'GET']],
            ['needLogin', lang('登录验证'), 'status', '', [lang('否'), lang('是')], 'text-center'],
            ['checkSign', 'sign验证', 'status', '', [lang('否'), lang('是')], 'text-center'],
            ['isTest', lang('运行环境'), 'status', '', ['-1' => 'MOCK' . lang('数据'), 0 => lang('测试环境'), 1 => lang('生产环境')], 'text-center'],
            ['info', lang('接口说明'), 'text'],
            ['create_time', lang('创建时间'), '', '', '', 'text-center'],
            ['status', lang('状态'), 'status', '', '', 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $tips = 'API统一访问地址： ' . config('web_site_domain') . '/api/版本号/接口唯一标识'
            . '<p><span class="label label-warning">测试模式</span> 系统将严格过滤请求字段，不进行sign的认证，但在必要的情况下会进行UserToken的认证！</p>'
            . '<p><span class="label label-success">生产模式</span> 系统将严格过滤请求字段，并且进行全部必要认证！</p>'
            . '<p><span class="label label-warning">警告</span> 修改API必须更新缓存才可以生效！</p>'
            . '<p><span class="label label-danger">禁用</span> 系统将拒绝所有请求，一般应用于危机处理！</p>'
            . '<p><a target="_blank" href="' . url('check') . '" class="label label-success">查看签名算法<a></p>';
        return Format::ins()//实例化
        ->setPageTitle('API' . lang('接口管理'))// 设置页面标题
        //->setPageTips($tips)
        ->setTabNav($tab_list, $module)//设置TAB分组
        ->setSearch(['api.apiName' => lang('接口名称'), 'api.hash' => lang('接口映射'), 'api.info' => lang('接口说明')])// 设置搜索框
        ->addColumns($fields)//设置字段
        ->setTopButtons($this->top_button)
            ->setTopButton(['title' => lang('状态码说明'), 'data-url' => url('errorlist'), 'icon' => 'fa fa-plug pr5', 'class' => 'btn btn-sm mr5 btn-default ', 'data-toggle' => 'dialog'])
            ->setTopButton(['title' => lang('一键同步'), 'id' => 'importData', 'class' => 'btn btn-sm mr5 btn-success btn-flat'])
            ->setRightButton(['title' => lang('请求参数'), 'href' => ['request', ['type' => 0, 'hash' => '__hash__']], 'icon' => 'fa fa-random pr5', 'class' => 'btn btn-xs mr5 btn-default '])
            ->setRightButton(['title' => lang('返回参数'), 'href' => ['request', ['type' => 1, 'hash' => '__hash__']], 'icon' => 'fa fa-plug pr5', 'class' => 'btn btn-xs mr5 btn-default '])
            ->setRightButtons($this->right_button)
            ->js('/static/admin/js/apigroup.js')
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 一键同步mock文档接口数据
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     * @author M
     */
    public function import_data()
    {
        header('Access-Control-Allow-Origin:*');
        $data = $this->request->post();
        \Cache::rmbatch('apiFields_*');//接口字段缓存
        \Cache::rmbatch('apiInfo_*');//接口缓存
        \Cache::rmbatch('apiRule_*');//接口验证缓存
        $result = $this->post_curls('http://mock.zhongbenruanjian.com/index/index/export', $data);
        if ($result['code'] == 1) {
            Db::startTrans();
            try {
                foreach ($result['data']['list_apilist'] as &$value) {
                    $info = ApiLists::where(['hash' => $value['hash']])->field('apiName,hash,module,checkSign,needLogin,status,method,info,isTest,returnStr,create_time,readme,mock')->find();
                    if (!$info) {
                        foreach ($result['data']['list_field'] as &$v) {
                            if ($v['hash'] == $value['hash']) {
                                unset($v['id']);
                                unset($v['project_id']);
                                $result1 = ApiFields::insert($v);
                            }
                        }
                        $api_list_data = [
                            'apiName'=>$value['apiName'],
                            'hash'=>$value['hash'],
                            'module'=>$value['module'],
                            'checkSign'=>$value['checkSign'],
                            'needLogin'=>$value['needLogin'],
                            'status'=>$value['status'],
                            'method'=>$value['method'],
                            'info'=>$value['info'],
                            'isTest'=>1,
                            'returnStr'=>$value['returnStr'],
                            'create_time'=>$value['create_time'],
                            'group'=>$value['group'],
                            'readme'=>$value['readme'],
                            'mock'=>$value['mock'],
                            'type'=>0,
                        ];
                        $result2 = ApiLists::insert($api_list_data);
                    }
                }
                if (!$result1 || !$result2) {
                    throw new \think\Exception('操作失败1');
                }
                Db::commit();
                echo json_encode(array('code' => 1, 'msg' => '操作成功'));
            } catch (\Exception $e) {
                Db::rollback();
                echo json_encode(array('code' => 0, 'msg' => '操作失败2'));
            }
        } else {
            echo json_encode(array('code' => 0, 'msg' => '操作失败3'));
        }
    }

    function check()
    {
        $keyword = $_POST['keyword'] ?? '';
        $app_secret = $_POST['app_secret'] ?? '';
        $code = '';
        $arr = explode('&', $keyword);
        foreach ($arr as $v) {
            $key = explode('=', $v);
            $newarr[$key[0]] = $key[1];
        }
        $newarr['appsecret'] = $app_secret;
        ksort($newarr);
        $string = [];
        foreach ($newarr as $key => $val) {
            $string[] = $key . '=' . $val;
        }
        if ($keyword) {
            $code = sha1(implode("&", $string));
        }
        $this->assign('keyword', $keyword);
        $this->assign('app_secret', $app_secret);
        $this->assign('code', $code);
        return $this->fetch(); // 渲染模板
    }

    /**
     * 新增api
     * @param int $id api的id
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function add($module = "admin")
    { //新增
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Apilist.add');
            if (true !== $result)
                $this->error($result);

            if ($res = ApiLists::create($data)) {
                // 记录行为
                action_log('admin_api_list_add', 'admin_api_list', $res->id, UID, $data['apiName']);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        } else {
            $group = \app\admin\model\Apigroup::where('module', $module)->column('aid,name');
            $group[0] = lang('无分组');
            $fields = [
                ['type' => 'text', 'name' => 'apiName', 'title' => lang('接口名称'), 'tips' => '控制器名/方法名。如：user/index', 'attr' => 'data-rule="required;"'],
                ['type' => 'text', 'name' => 'hash', 'title' => lang('接口映射'), 'tips' => lang('系统自动生成，不允许修改'), 'value' => uniqid(), 'attr' => 'readonly'],
                ['type' => 'text', 'name' => 'info', 'title' => lang('接口标题'), 'attr' => 'data-rule="required;"'],
                ['type' => 'select', 'name' => 'module', 'title' => lang('所属模块'), 'extra' => \app\admin\model\Module::column('name,title')],
                ['type' => 'select', 'name' => 'group', 'title' => lang('所属接口分组'), 'extra' => $group],
                ['type' => 'radio', 'name' => 'method', 'title' => lang('请求方式'), 'extra' => [lang('不限'), 'POST', 'GET'], 'value' => 1],
                ['type' => 'radio', 'name' => 'needLogin', 'title' => lang('登录验证'), 'extra' => [lang('忽略验证'), lang('需要验证')], 'value' => 0],
                ['type' => 'radio', 'name' => 'checkSign', 'title' => 'sign' . lang('验证'), 'extra' => [lang('忽略验证'), lang('需要验证')], 'value' => 0],
                ['type' => 'radio', 'name' => 'isTest', 'title' => lang('运行环境'), 'extra' => ['-1' => 'MOCK' . lang('数据'), 0 => lang('测试环境'), 1 => lang('生产环境')], 'value' => -1],
                ['type' => 'radio', 'name' => 'mock', 'title' => 'MOCK' . lang('类型'), 'extra' => [0 => lang('展示信息'), 1 => lang('展示列表')], 'value' => 0],
                ['type' => 'textarea', 'name' => 'readme', 'title' => lang('接口详细说明')],
                ['type' => 'textarea', 'name' => 'returnStr', 'title' => lang('返回数据示例')],
                ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), 'extra' => [lang('禁用'), lang('启用')], 'value' => 1],
            ];
            $this->assign('page_title', lang('新增') . 'API' . lang('接口'));
            $this->assign('form_items', $fields);
            $this->assign('set_script', ['/static/admin/js/apigroup.js']);
            return $this->fetch('public/add');
        }
    }

    /**
     * 编辑api
     * @param int $id api的id
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function edit($id = 0)
    { //编辑
        if ($id === 0)
            $this->error(lang('缺少参数'));
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Apilist.edit');
            if (true !== $result)
                $this->error($result);

            if ($res = ApiLists::update($data)) {
                cache('apiInfo_' . $data['hash'], null);
                // 记录行为
                action_log('admin_api_list_edit', 'admin_api_list', $id, UID, $data['apiName']);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        } else {
            $data = ApiLists::get(['id' => $id]);
            $group = \app\admin\model\Apigroup::where('module', $data['module'])->column('aid,name');
            $group[0] = lang('无分组');
            $fields = [
                ['type' => 'hidden', 'name' => 'id'],
                ['type' => 'text', 'name' => 'apiName', 'title' => lang('接口地址'), 'tips' => '控制器名/方法名。如：user/index', 'attr' => 'data-rule="required;"'],
                ['type' => 'text', 'name' => 'info', 'title' => lang('接口标题'), 'attr' => 'data-rule="required;"'],
                ['type' => 'text', 'name' => 'hash', 'title' => lang('接口映射'), 'tips' => lang('系统自动生成，不允许修改'), 'attr' => 'readonly'],
                ['type' => 'select', 'name' => 'module', 'title' => lang('所属模块'), 'extra' => \app\admin\model\Module::column('name,title')],
                ['type' => 'select', 'name' => 'group', 'title' => lang('所属接口分组'), 'extra' => $group],
                ['type' => 'radio', 'name' => 'method', 'title' => lang('请求方式'), 'extra' => [lang('不限'), 'POST', 'GET']],
                ['type' => 'radio', 'name' => 'needLogin', 'title' => lang('登录验证'), 'extra' => [lang('忽略验证'), lang('需要验证')]],
                ['type' => 'radio', 'name' => 'checkSign', 'title' => 'sign' . lang('验证'), 'extra' => [lang('忽略验证'), lang('需要验证')]],
                ['type' => 'radio', 'name' => 'isTest', 'title' => lang('运行环境'), 'extra' => ['-1' => 'MOCK' . lang('数据'), 0 => lang('测试环境'), 1 => lang('生产环境')]],
                ['type' => 'radio', 'name' => 'mock', 'title' => 'MOCK' . lang('类型'), 'extra' => [0 => lang('展示信息'), 1 => lang('展示列表')], 'value' => 0],
                ['type' => 'textarea', 'name' => 'readme', 'title' => lang('接口详细说明')],
                ['type' => 'textarea', 'name' => 'returnStr', 'title' => lang('返回数据示例')],
                ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), 'extra' => [lang('禁用'), lang('启用')]],
            ];
            $this->assign('page_title', lang('编辑') . 'API' . lang('接口'));
            $this->assign('form_items', $this->setData($fields, $data));
            $this->assign('set_script', ['/static/admin/js/apigroup.js']);
            return $this->fetch('public/edit');
        }
    }

    /**
     * ajax获取分组
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/9 14:10
     */
    public function get_group($module = "admin")
    {
        $group = \app\admin\model\Apigroup::where('module', $module)->column('aid,name');
        $group[0] = lang('无分组');

        return $group;
    }


    /**
     * 删除接口
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function delete($ids)
    {
        $api = ApiLists::get(['id' => $ids]);
        $res = $this->batch_api_fields($api['hash']);
        if ($res) {
            parent::delete(); // TODO: Change the autogenerated stub
        }
    }

    public function batch_api_fields($hash)
    {
        $where['hash'] = $hash;
        $count = ApiFields::where($where)->count();
        if ($count == 0) {
            return true;
        }
        $result = ApiFields::where($where)->delete();
        if ($result) {
            cache('apiInfo_' . $hash, null);
            cache("apiFields_" . $hash . '_0', null);
            cache("apiFields_" . $hash . '_1', null);
            // 记录行为
            action_log('admin_api_fields_delete', 'admin_api_fields', $hash, UID);
            return true;
        } else {
            $this->error(lang('删除失败'));
        }
    }

    /**
     * 请求和返回参数列表
     * @param int $type 0代表请求字段参数，1代表返回字段参数
     * @param string $hash 接口映射
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function request($hash, $type)
    { // 请求/返回 参数列表
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map['hash'] = $hash;
        $map['type'] = $type;
        $data_list = ApiFields::where($map)->order('sort asc')->paginate(15);
        $data_list1 = Tree::config(['title' => 'fieldName'])->toList($data_list);
        $fields = [
            ['id', 'ID', 'text'],
            ['title_display', lang('字段名称'), 'text'],
            ['info', lang('字段说明'), 'text.edit'],
            ['dataType', lang('数据类型'), 'select', '', [1 => 'Integer[整数]', 2 => 'String[字符串]',
                3 => 'Boolean[布尔]',
                4 => 'Enum[枚举]',
                5 => 'Float[浮点数]',
                6 => 'File[文件]',
                7 => 'Mobile[手机号]',
                8 => 'Object[对象]',
                9 => 'Array[数组]',
                10 => 'Email[邮箱]',
                11 => 'Date[日期]',
                12 => 'Url',
                13 => 'IP',
            ]],
            ['isMust', lang('是否必须'), 'select', '', [lang('否'), lang('是')]],
            ['default', lang('默认值'), 'text'],
            ['sort', lang('排序'), 'text.edit', '', '', '', 'admin_api_fields'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];

        return Format::ins()//实例化
        ->setPrimaryKey('id')
            ->setTableName('admin_api_fields')
            ->addColumns($fields)//设置字段
            ->setTopButton(['title' => lang('新增参数'), 'href' => ['editrs', ['type' => $type, 'hash' => $hash]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-success layeradd'])
            ->setTopButton(['title' => lang('批量导入'), 'href' => ['import', ['type' => $type, 'hash' => $hash]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-success layeradd'])
            ->setRightButton(['title' => lang('编辑'), 'href' => ['editrs', ['type' => $type, 'hash' => '__hash__', 'id' => '__id__']], 'icon' => 'fa fa-edit pr5', 'class' => 'btn btn-xs mr5 btn-success layeredit'])
            ->setRightButton(['title' => lang('删除'), 'href' => ['deleters', ['type' => $type, 'hash' => $hash, 'id' => '__id__']], 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-xs mr5 btn-danger  ajax-get confirm'])
            ->setData($data_list1)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增和编辑参数列表
     * @param int $type 0代表请求字段参数，1代表返回字段参数
     * @param string $hash 接口映射
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function editrs($type, $hash)
    { //编辑/新增 参数字段
        $id = input('id');
        if ($this->request->isPost()) {
            $data = input('post.');
            if (!$id) { //新增字段 提交
                $data['type'] = $type;
                $data['hash'] = $hash;
                $data['fieldName'] = trim($data['fieldName']);
                if (!empty($data['fieldName'])) {
                    $data['showName'] = $data['fieldName'];
                }
                $result = $this->validate($data, 'ApiFields.add');
                if (true !== $result) {
                    $this->error($result);
                } else {
                    if ($res = ApiFields::create($data)) {
                        cache('apiInfo_' . $hash, null);
                        cache("apiFields_" . $hash . '_' . $type, null);
                        // 记录行为
                        action_log('admin_api_fields_add', 'admin_api_fields', $res->id, UID, $data['fieldName']);
                        $this->success(lang('新增成功'), cookie('__forward__'));
                    } else {
                        $this->error(lang('新增失败'));
                    }
                }
            } else { //编辑字段 提交
                $data['fieldName'] = trim($data['fieldName']);
                if (!empty($data['fieldName'])) {
                    $data['showName'] = $data['fieldName'];
                }
                if (count($data) == 2) {
                    foreach ($data as $k => $v) {
                        $fv = $k != 'id' ? $k : '';
                    }
                    $result = $this->validate($data, 'ApiFields.' . $fv);
                } else {
                    $result = $this->validate($data, 'ApiFields.edit');
                }
                if (true !== $result) {
                    $this->error($result);
                } else {
                    if ($res = ApiFields::update($data)) {
                        cache('apiInfo_' . $hash, null);
                        cache("apiFields_" . $hash . '_' . $type, null);
                        // 记录行为
                        action_log('admin_api_fields_edit', 'admin_api_fields', $data['id'], UID, 'ID：' . $data['id'] . ' 字段名：' . $data['fieldName']);
                        $this->success(lang('编辑成功'), cookie('__forward__'));
                    } else {
                        $this->error(lang('编辑失败'));
                    }
                }
            }
        } else {
            if (!$id) { //新增字段
                if ($type == 0) { //新增请求字段
                    $title = lang('新增请求字段');
                } else { //新增返回字段
                    $title = lang('新增返回字段');
                }
                $data['hash'] = $hash;
                $data['id'] = 0;
                $data['isMust'] = '';
            } else { //编辑字段
                if ($type == 0) { //编辑请求字段
                    $title = lang('编辑请求字段');
                } else { //新增返回字段
                    $title = lang('编辑返回字段');
                }
                $data = ApiFields::get(['id' => $id]);
            }
            $biao_list = \think\Db::query("SHOW TABLE STATUS"); // 获取数据库的所有表信息
            $biao_data = [];
            foreach ($biao_list as $k => $v) {
                $b_name_arr = explode(config('database.prefix'), $v['Name']);
                $biao_data[$k]['name'] = $b_name_arr[1];
                $biao_data[$k]['info'] = $v['Comment'];
            }
            //读取所有字段
            $fields = ApiFields::where(['hash' => $hash, 'type' => $type])->column('id,fieldName,pid');
            $fields1 = Tree::config(['title' => 'fieldName', 'step' => 3])->toList($fields);
            $this->assign('fields', $fields1);
            $this->assign('type', $type);
            $this->assign('biao_data', $biao_data);
            $this->assign('title', $title);
            $this->assign('data', $data);
            return $this->fetch();
        }
    }

    public function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 数组类型判断
     * @since 2020年12月10日17:28:38
     * @author zenghu [ 1427305236@qq.com ]
     */
    public static function isAssoc($array)
    {
        if (is_array($array)) {
            $keys = array_keys($array);
            return $keys === array_keys($keys);
        }

        return false;
    }

    /**
     * 接口字段返回值批量入库
     * @since 2020年12月10日16:24:38
     * @author zenghu [ 1427305236@qq.com ]
     */
    public static function handleArr($data = [], $hash = '', $pid = 0, $falg = 0)
    {
        if (is_array($data) && !empty($data)) {
            foreach ($data as $key => $val) {
                if (!is_numeric($key)) {
                    $fieldId = ApiFields::insertGetId([
                        'fieldName' => ($falg == 0) ? $key : ($falg . '.' . $key),
                        'hash' => $hash,
                        'type' => 1,
                        'showName' => $key,
                        'pid' => $pid,
                        'mock' => $val,
                        'dataType' => 2,
                    ]);
                }
                if (is_array($val)) {
                    $fieldId = empty($fieldId) ? 0 : $fieldId;
                    if (self::isAssoc($val)) {
                        self::handleArr($val[0], $hash, $fieldId, $key);
                    } else {
                        self::handleArr($val, $hash, $fieldId, $key);
                    }
                }
            }
        }
        return true;
    }

    /**
     * 批量导入
     * @param $type
     * @param $hash
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/18 21:06
     */
    public function import($type, $hash)
    {
        if ($this->request->isPost()) {
            $data = input('param.');
            $rs = $this->is_json($data['param']);
            if ($rs) {
                $data_field = json_decode($data['param'], true);
                $result = self::handleArr($data_field['data'], $hash);
            } else {
                $str = str_replace('"', '', $data['param']);
                $arr = explode(',', trim($str));
                foreach ($arr as $v) {
                    $val = explode(':', $v);
                    $param['fieldName'] = trim($val[0]);
                    $param['info'] = trim($val[1]);
                    $param['type'] = $type;
                    $param['hash'] = $hash;
                    $param['isMust'] = 1;
                    $param['dataType'] = 2;
                    $params[] = $param;
                }
                $ApiFields = new ApiFields();
                $result = $ApiFields->saveAll($params);
            }
            if ($result) {
                $this->success(lang('导入成功'));
            }
            $this->error(lang('导入失败'));
        } else {
            $fields = [
                ['type' => 'hidden', 'name' => 'type', 'value' => $type],
                ['type' => 'hidden', 'name' => 'hash', 'value' => $hash],
                ['type' => 'textarea', 'name' => 'param', 'title' => lang('快速导入的参数'), 'tips' => '格式例如id:ID,name:名称，每组请用逗号分隔,导入的字段格式默认为字符串，必填项，其他类型需要自行修改，原则上不建议使用快速导入'],
            ];
            $this->assign('page_title', lang('快速导入'));
            $this->assign('form_items', $fields);
            return $this->fetch('public/add');
        }
    }

    /**
     * 删除字段参数
     * @param string $hash 接口映射
     * @param int $type 0代表请求字段参数，1代表返回字段参数
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function deleters($hash, $type)
    { //删除 参数字段
        $id = input('id');
        if (isset($id) && !empty($id)) {
            $where['id'] = $id;
            $where['hash'] = $hash;
            $where['type'] = $type;
            $result = ApiFields::where($where)->delete();
            if ($result) {
                cache('apiInfo_' . $hash, null);
                cache("apiFields_" . $hash . '_' . $type, null);
                // 记录行为
                action_log('admin_api_fields_delete', 'admin_api_fields', $id, UID);
                $this->success(lang('删除成功'), url('request', ['type' => $type, 'hash' => $hash]));
            } else {
                $this->error(lang('删除失败'));
            }
        }
    }

    /**
     * 获取表信息
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getInfo()
    {
        if (request()->isPost()) {
            $name = input('name');
            $biaoInfo = \think\Db::query("SHOW FULL COLUMNS FROM " . config('database.prefix') . $name); // 获取 [xzyn_user] 表的所有字段信息
            $biao_info = [];
            foreach ($biaoInfo as $k => $v) {
                $biao_info[$k]['name'] = $v['Field'];
                $biao_info[$k]['info'] = $v['Comment'];
                $biao_info[$k]['type'] = $v['Type'];
            }
            $this->success(lang('操作成功'), '', $biao_info);
        }
    }

    /**
     * API接口详情
     * @param $hash 接口标识
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function apiinfo($hash)
    {
        $apiinfo = ApiLists::get(['hash' => $hash]);
        if (empty($hash) || empty($apiinfo)) {
            return ApiReturn::r('-1');
        }
        $f_field = ApiFields::all(['hash' => $hash, 'type' => 1]); //返回字段
        $q_field = ApiFields::all(['hash' => $hash, 'type' => 0]); //请求字段
        $this->assign('f_field', $f_field);
        $this->assign('q_field', $q_field);
        $this->assign('data', $apiinfo);
        return $this->fetch();
    }

    /**
     * 错误码列表
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function errorlist()
    {
        $errorlist = ApiReturn::$Code;
        $this->assign('errorlist', $errorlist);
        return $this->fetch();
    }

    /**
     * 用户字段
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function userlist()
    {
        $userFields = ApiReturn::$userFields;
        $this->assign('userFields', $userFields);
        return $this->fetch();
    }


    /**
     * 自测
     */
    public function test($version, $hash)
    {

        $apiinfo = ApiLists::get(['hash' => $hash]);
        if (!$apiinfo) {
            return ApiReturn::r('-1');
        }
        $data = request()->post();
        $user = null;
        if ($data["UserToken"]) {
            $user = \app\user\model\User::get($data["UserToken"]);
            if (!$user) {
                $this->error(lang('会员信息填写失败'));
            }
        }
        if ($apiinfo["needLogin"] && !$user) {
            return ApiReturn::r('-202');
        }
        $data["UserToken"] = null;
        unset($data["UserToken"]);
        ApiReturn::$user = $user;
        $rule = ApiFields::getCacheFields($hash, 0); //获取数据库的 请求字段
        $newRule = ApiFields::cacheBuildValidateRule($rule);
        if ($newRule) {
            $validate = new \think\Validate($newRule);
            if (!$validate->check($data)) {  //验证
                return ApiReturn::r('-900', [], $validate->getError()); // 参数错误
            }
        }
        $newData = [];
        foreach ($rule as $item) {
            if ($data[$item['fieldName']] == '') {
                if ($item['default'] != '') {
                    $newData[$item['fieldName']] = $item['default'];
                }
            } else {
                $newData[$item['fieldName']] = $data[$item['fieldName']];
            }
        }
        try {

            return action("api/" . $apiinfo['apiName'], [$newData, $user], 'controller\\' . $version);
        } catch (\Exception $e) {
            return ApiReturn::r($e->getCode(), "", $e->getMessage());
        }
    }

    public function post_curls($url, $post)
    {
        $post = http_build_query($post);
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $res = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno' . curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        $res = trim($res);
        $value_array = json_decode($res, true);
        return $value_array; // 返回数据，arr格式
    }
}
