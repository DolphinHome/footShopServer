<?php
/**
 * Created by PhpStorm.
 * User: I5
 * Date: 2020/12/9
 * Time: 15:53
 */
namespace app\admin\admin;

use app\admin\admin\Base;
use app\admin\model\Apiprocess as ApiprocessModel;
use app\common\model\Apilist as ApiLists;
use service\Format;
use think\Db;

class PayType extends Base
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
        $order = 'sort desc,id desc';
        // 数据列表
        $data_list = Db::name("pay_type_list")->where($map)->order($order)->paginate();
        $fields = [
            ['id', 'ID'],
            ['name', lang('支付名称')],
            ['pay_type', lang('支付方式')],
            ['sort', lang('排序'), 'text.edit', '', '', '', 'pay_type_list'],
            ['status', lang('状态'), 'status'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
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
            $data['create_time'] = time();
            if ($page = Db::name("pay_type_list")->insert($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('支付名称')],
            ['type' => 'text', 'name' => 'pay_type', 'title' => lang('支付方式')],
            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('是否启用'), 'tips' => '', 'attr' => '', 'extra' => [lang('禁用'), lang('启用')], 'value' => '1'],
        ];
        $this->assign('page_title', lang('新增支付方式'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
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

            if (Db::name("pay_type_list")->where('id', $id)->update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('支付名称')],
            ['type' => 'text', 'name' => 'pay_type', 'title' => lang('支付方式')],
            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('是否启用'), 'tips' => '', 'attr' => '', 'extra' => [lang('禁用'), lang('启用')], 'value' => '1'],
        ];
        $info = Db::name("pay_type_list")->where(['id' => $id])->find();
        $this->assign('info', $info);
        $this->assign('page_title', lang('编辑支付方式'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

}