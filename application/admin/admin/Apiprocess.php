<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\admin\admin\Base;
use app\admin\model\Apiprocess as ApiprocessModel;
use app\common\model\Apilist as ApiLists;
use service\Format;

/**
 * 业务流程控制器
 * @package app\Apiprocess\admin
 */
class Apiprocess extends Base
{
    /**
     * 业务流程列表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = 'sort asc,aid asc';
        // 数据列表
        $data_list = ApiprocessModel::where($map)->order($order)->paginate();
        $fields = [
            ['aid', 'ID'],
            ['name', lang('业务流程名称')],
            ['sort', lang('排序'),'text.edit','','','','admin_api_process'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->setPrimaryKey('aid')
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if($data['name'] == ''){
                $this->error(lang('业务流程名称不能为空'));
            }
            if ($page = ApiprocessModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $api = ApiLists::where('isTest','1')->column('id,info');
        $this->assign('page_title', lang('新增业务流程'));
        $this->assign('api', $api);
        return $this->fetch();
    }

    /**
     * 编辑
     * @param null $id 业务流程id
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error(lang('缺少参数'));

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['update_time'] = time();

            if($data['name'] == ''){
                $this->error(lang('业务流程名称不能为空'));
            }

            if (ApiprocessModel::where('aid',$id)->update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info = ApiprocessModel::get(['aid' => $id]);
        $ids = $info['content'];
        $order = "FIND_IN_SET(id,'".$ids."')";
        $check_api = ApiLists::where('id','in',$ids)->orderRaw($order)->column('id,info');
        $api = ApiLists::where('isTest','1')->column('id,info');
        $this->assign('api', $api);
        $this->assign('check_api', $check_api);
        $this->assign('info', $info);
        $this->assign('page_title', lang('编辑业务流程'));
        return $this->fetch();
    }
}