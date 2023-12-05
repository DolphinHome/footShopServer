<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\admin\model\Log as LogModel;
use app\common\model\Log as logSysModel;
use app\admin\model\Role; 
use service\Format;
use think\facade\Env;

/**
 * 行为日志控制器
 * @package app\admin\controller
 */
class Log extends Base
{

    /**
     * 日志列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index()
    {
        $p_time = input('param.p_time');
        if (input('param.role')) {
            $map[] = ['admin.role', '=', input('param.role')];
        }
        if (input('param.username')) {
            $map[] = ['admin.username', '=', input('param.username')];
        }
        if (input('param.action_name')) {
            $map[] = ['admin_action.title', '=', input('param.action_name')];
        }
        if ($p_time) {
            $p_time = explode(' - ', $p_time);
            $start_time = strtotime($p_time[0]);
            $end_time = strtotime($p_time[1]) + 86399;
            $map[] = [
                'admin_log.create_time','>=', $start_time
            ];
            $map[] = [
                'admin_log.create_time','<=', $end_time
            ];
        }
        // 数据列表
        $data_list = LogModel::getAll($map, 'admin_log.id desc');

        $fields = [
            ['id', lang('ID')],
            ['title', lang('行为名称')],
            ['username', lang('执行者用户名')],
            ['action_ip', lang('执行').'IP', 'callback', 'long2ip'],
            ['module_title', lang('所属模块')],
            ['create_time', lang('执行时间')],
            ['right_button', lang('操作'), 'btn']
        ];
       
        $roles = Role::where('status', 1)->column('id,name');
        $roles[0] = '全部';
        ksort($roles);        
        $searchFields = [
            ['role', lang('角色'), 'select', '', $roles],
            ['username', lang('用户名'), 'text'],
            ['action_name', lang('行为名称'), 'text'],
            ['p_time', lang('开始结束时间'), 'daterange'],
        ];
        return Format::ins()//实例化
            ->setPageTitle(lang('行为日志'))
            ->setTopSearch($searchFields)
            ->hideCheckbox()
            ->setTopButton(['title' => lang('清空日志'), 'href' => 'clear', 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-sm mr5 btn-danger  ajax-get confirm'])
            ->setRightButton(['title' => lang('查看详情'), 'href' => 'details', 'icon' => 'fa fa-columns pr5', 'class' => 'btn btn-xs mr5 btn-default '])
            ->addColumns($fields)//设置字段
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 日志详情
     * @param null $id 日志id
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function details($id = null)
    {
        if ($id === null) $this->error(lang('缺少参数'));
        $info = LogModel::getAll(['admin_log.id' => $id]);
        $info = $info[0];
        $info['action_ip'] = long2ip($info['action_ip']);
        $fields = [
            ['type' => 'static', 'name' => 'title', 'title' => lang('行为名称')],
            ['type' => 'static', 'name' => 'username', 'title' => lang('执行者')],
            ['type' => 'static', 'name' => 'record_id', 'title' => lang('目标').'ID'],
            ['type' => 'static', 'name' => 'action_ip', 'title' => lang('执行').'IP'],
            ['type' => 'static', 'name' => 'module_title', 'title' => lang('所属模块')],
            ['type' => 'static', 'name' => 'remark', 'title' => lang('备注')]
        ];

        $this->assign('page_title', lang('行为日志'));
        $this->assign('btn_hide', 1);
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('public/edit');
    }

    /**
     * 清空日志
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function clear()
    {
        $res = logModel::destroy(['status' => 1]);
        if ($res) {
            $this->success(lang('清空日志成功'));
        } else {
            $this->error(lang('清空日志失败'));
        }
    }

    /**
     * 获取系统日志
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/12 8:33
     */
    public function get_system_log()
    {
        $logSysModel = new logSysModel();
        if (false === $logSysModel->isAllow()) {
            die($logSysModel->getError());
        }
        $directory = $logSysModel->getDirectory();
        $file_paths = input('param.file_paths');
        $filePaths = isset($file_paths) ? $file_paths : '/'.date('Ym').'/'.date('d').'.log';
        if (mb_strpos($filePaths, '_cli.log') !== false) {
            $path = $logSysModel->complementLogPath($filePaths);
            if (false === $path) {
                $this->error(404);
            }
            $content = file_get_contents($path);
            $this->success('', '', $content);
        }
        $rows = $logSysModel->getLogs($filePaths);
        $info = $logSysModel->getInfo($filePaths);
        if (false === $rows) {
            $this->error(lang('获取失败'));
        }

        $this->assign(compact('rows'));
        $this->assign(compact('info'));
        $this->assign(compact('directory'));
        $this->assign(compact('file_paths'));
        return $this->fetch();
    }

    /**
     * 删除对应日志文件
     * @param string $ids
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/12 11:43
     */
    public function del()
    {
        $filePaths = input('param.file_paths');
        $file = Env::get('runtime_path') . 'log'.$filePaths;
        if (file_exists($file)) {
            if(unlink($file)){
                $this->success(lang('删除成功'));
            }
        }
        $this->error(lang('删除失败'));
    }
}