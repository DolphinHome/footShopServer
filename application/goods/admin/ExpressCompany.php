<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 似水星辰 [2630481389@qq.com]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\ExpressCompany as ExpressCompanyModel;
use service\Format;

/**
 * 快递公司控制器
 * @package app\ExpressCompany\admin
 */
class ExpressCompany extends Base
{
    /**
     * 快递公司列表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder();
        // 数据列表
        $data_list = ExpressCompanyModel::where($map)->order($order)->paginate();
        $fields = [
            ['aid', 'ID', '', '', '',],
            ['name', lang('公司名称'), '', '', '',],
            ['express_no', lang('公司编号'), '', '', '',],
            ['tel', lang('联系电话'), '', '', '',],
            ['logo', lang('公司').'LOGO', 'picture'],
            ['sort', lang('排序'), '', '0', '',],
            //['is_default', lang('是否设置默认'), 'status', '', [lang('否'),lang('是')]],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $searchFields = [
            ['name', lang('公司名称'), 'text'],
            ['express_no', lang('公司编号'), 'text'],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->setPrimaryKey('aid')
            ->addColumns($fields)//设置字段
            ->setTopSearch($searchFields)
            ->setTopButtons($this->top_button_layer)
            ->setRightButton(['title' => lang('运费模板'), 'href' => ['goods/freight/index', ['company_id' => '__aid__']], 'icon' => 'fa fa-list pr5', 'class' => 'btn btn-xs mr5 btn-default btn-flat'])
            ->setRightButtons($this->right_button_layer)
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

            // 验证
            $result = $this->validate($data, 'ExpressCompany');
            if (true !== $result) {
                $this->error($result);
            }

            if ($res = ExpressCompanyModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_express_add', 'goods_express_company', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('公司名称'),],
            ['type' => 'text', 'name' => 'express_no', 'title' => lang('公司编号'),'tips'=> lang('请对照').' <a target="_blank" href="'.url('express_code').'">'.lang('物流公司编码表').'</a>'.lang('输入对应快递查询的物流公司编码')],
            ['type' => 'text', 'name' => 'tel', 'title' => lang('联系电话'),],
            ['type' => 'image', 'name' => 'logo', 'title' =>  lang('公司').'LOGO'],
            ['type' => 'number', 'name' => 'sort', 'title' => lang('排序'), 'value' => '100',],
            ['type' => 'radio', 'name' => 'is_default', 'title' => lang('是否设置默认'), 'value' => '0','extra'=>[lang('否'),lang('是')]]

        ];
        $this->assign('page_title', lang('新增快递公司'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 快递公司id
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = ExpressCompanyModel::get(['aid' => $id]);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['update_time'] = time();

            // 验证
            $result = $this->validate($data, 'ExpressCompany');
            if (true !== $result) {
                $this->error($result);
            }

            if (ExpressCompanyModel::update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('goods_express_edit', 'goods_express_company', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
        
        $fields = [
            ['type' => 'hidden', 'name' => 'aid'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('公司名称')],
            ['type' => 'text', 'name' => 'express_no', 'title' => lang('公司编号')],
            ['type' => 'text', 'name' => 'tel', 'title' => lang('联系电话')],
            ['type' => 'image', 'name' => 'logo', 'title' =>  lang('公司').'LOGO'],
            ['type' => 'number', 'name' => 'sort', 'title' => lang('排序')],
            ['type' => 'radio', 'name' => 'is_default', 'title' => lang('是否设置默认'), 'extra'=>[lang('否'),lang('是')]]

        ];
        $this->assign('page_title', lang('编辑快递公司'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /**
     * 查看快递编码
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/28 16:58
     */
    public function express_code()
    {
        return $this->fetch();
    }
}
