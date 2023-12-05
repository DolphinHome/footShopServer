<?php

// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\SystemMessageAction as SystemMessageActionModel;
use service\Format;

/**
 * Description of SystemMessageAction
 * @author 晓风<215628355@qq.com>
 * @date 2020-8-28 9:22:56
 */
class SystemMessageAction extends Base
{

    /**
     * 文档列表
     * @author 晓风<215628355@qq.com>
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();        // 排序
        $order = $this->getOrder('id DESC');
        $dataList = SystemMessageActionModel::where($map)->order($order)->paginate();
        $fields = [
            ['id', lang('序号')],
            ['name', lang('类型名称')],
            ['create_time', lang('创建时间')],
            ['id', lang('操作'), 'callback', function ($value, $data) {
                $edit = "<a ident='edit' title=lang('编辑') href='" . url('edit', ['id' => $data['id'], 'layer' => 1]) . "' icon='fa fa-pencil pr5' class='btn btn-xs mr5 btn-default layeredit'><i class='fa fa-pencil pr5'></i>编辑</a> ";
                $delete = "<a ident='delete' title=lang('删除') href='" . url('delete', ['ids' => $data['id']]) . "' icon='fa fa-times pr5' class='btn btn-xs mr5 btn-default ajax-get confirm'><i class='fa fa-times pr5'></i>删除</a> ";
                $return = $edit . $delete;
                if ($data['id'] <= 5) {
                    $return = $edit;
                }
                return $return;
            }, '__data__'],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
//           ->setPageTips(lang('删除或修改动作内容可能会造成无法预计的后果，请谨慎操作，请勿随意配置此页面权限'))
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button, ['disable', 'enable'])
//           ->setRightButtons($this->right_button, ['disable'])
            ->setData($dataList)//设置数据
            ->fetch();//显示
    }
    
    /**
     *新增
     * @param {*}
     * @return {*}
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $request =$this->request->post();
   
            $name  = $request['name'] ?? '';
            $field = $request['field'] ?? [];
            $msg   = $request['msg'] ?? [];
            $rule = [];
            foreach ($field as $key=>$filedName) {
                if (!$filedName) {
                    $this->error(lang('字段名不可留空'));
                }
                $filedMsg = $msg[$key] ??  '';
                if (!$filedMsg) {
                    $this->error($filedName . lang('描述不可留空'));
                }
                $rule[] = [
                    'field'=>$filedName,
                    'msg'=>$filedMsg
                ];
            }
            $data = [
                'name'=>$name,
                'rule' => $rule ? json_encode($rule, JSON_UNESCAPED_UNICODE) : ''
            ];
     
            $ret = SystemMessageActionModel::create($data);
            if (!$ret) {
                $this->error(lang('创建动作失败'));
            }

            //记录行为
            unset($data['__token__']);
            $details = json_encode($data, JSON_UNESCAPED_UNICODE);
            action_log('operation_system_message_action_add', 'operation', $ret->id, UID, $details);

            $this->success(lang('创建动作成功'), 'index');
        }
        
        return $this->fetch('add');
    }
    
    /**
     * 编辑
     * @param {*} $id
     * @return {*}
     * @Date: 2021-04-28 16:46:03
     */
    public function edit($id = 0)
    {
        if (!$id) {
            $this->error(lang('参数异常'));
        }
        $info =  SystemMessageActionModel::get($id);
        $info['rule'] =  $info['rule'] ? json_decode($info['rule'], true) : [];

        if ($this->request->isPost()) {
            $request =$this->request->post();
   
            $name  = $request['name'] ?? '';
            $field = $request['field'] ?? [];
            $msg   = $request['msg'] ?? [];
            $rule = [];
     
            foreach ($field as $key=>$filedName) {
                if (!$filedName) {
                    $this->error(lang('字段名不可留空'));
                }
                $filedMsg = $msg[$key] ??  '';
                if (!$filedMsg) {
                    $this->error($filedName . lang('描述不可留空'));
                }
                $rule[] = [
                    'field'=>$filedName,
                    'msg'=>$filedMsg
                ];
            }
            $data = [
                'name'=>$name,
                'rule' => $rule ? json_encode($rule, JSON_UNESCAPED_UNICODE) : ''
            ];
    
            $ret = SystemMessageActionModel::where('id', $id)->update($data);
            if (!$ret) {
                $this->error(lang('编辑动作失败'));
            }

            //记录行为
            unset($data['__token__']);
            $details = arrayRecursiveDiff($data, $info);
            action_log('operation_system_message_action_edit', 'operation', $id, UID, $details);
            
            $this->success(lang('编辑动作成功'), 'index');
        }
        
        $this->assign('url_param', input('param.'));
        $this->assign('info', $info);
        return $this->fetch('add');
    }
}
