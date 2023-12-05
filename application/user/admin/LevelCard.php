<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\admin;

use app\admin\admin\Base;
use app\user\model\LevelCard as LevelModel;
use service\Format;
use think\Db;

/**
 * 会员等级控制器
 * @package app\Level\admin
 */
class LevelCard extends Base
{
    /**
     * 会员等级列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
//        $map = $this->getMap();
        $type = input('type');
        if ($type){
            $map = ['type'=>$type];
        }else{
            $map = true;
        }

        // 排序
        $order = 'type,level';
        // 数据列表
        $data_list = LevelModel::where($map)->order($order)->paginate()->each(function (&$v) {
            $v['bg_image'] = get_file_url($v['bg_image']);
            return $v;
        });
        $search_fields = [
            ['type', '类型', 'select', '', [ 0 =>'全部','1' => '会员卡类型_金银铜', '2' => '会员卡类型_年季月','3'=>'会员卡类型_终身']],
        ];
        $fields = [
            ['id', 'ID'],
            ['level_name', lang('名称')],
            ['bg_image', lang('背景图片'), 'picture'],
            ['price', lang('价格')],
            ['cost', lang('价值')],
            ['discount', lang('折扣比例')],
            ['level', lang('等级标识')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setTopButton(['title'=>'清空用户会员卡信息', 'href'=>['delete_card_data'], 'class'=>'btn btn-danger btn-flat'])
            ->setTopSearch($search_fields)
        ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'LevelCard');
            if (true !== $result) {
                $this->error($result);
            }
            if ($page = LevelModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'text', 'name' => 'level_name', 'title' => lang('名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'select', 'name' => 'type', 'title' => '类型', 'tips' => '', 'extra' => ['1'=>'会员类型_金银铜','2'=>'会员类型_年季月','3'=>'会员卡类型_终身'] , 'value' => '1'],
            ['type' => 'image', 'name' => 'vip_image', 'title' => lang('VIP图标'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'image', 'name' => 'bg_image', 'title' => lang('会员卡背景'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'color', 'name' => 'color', 'title' => lang('字体颜色'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'price', 'title' => lang('价格'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'cost', 'title' => lang('价值'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'number', 'name' => 'days', 'title' => lang('充值天数'), 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'text', 'name' => 'discount', 'title' => lang('折扣（0.00-1.00）'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'number', 'name' => 'level', 'title' => lang('等级标识'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'textarea', 'name' => 'content', 'title' => lang('特权说明'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'radio', 'name' => 'status', 'title' => '状态', 'tips' => '必填项', 'attr' => '','extra' => ['关闭','开启'], 'value' => '1'],
        ];
        $this->assign('page_title', lang('新增会员等级'));
        $this->assign('form_items', $fields);
        return $this->fetch('../../admin/view/public/add');
    }

    /**
     * 编辑
     * @param null $id 会员等级id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'LevelCard');
            if (true !== $result) {
                $this->error($result);
            }
            if (LevelModel::update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info = LevelModel::get($id);
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'level_name', 'title' => lang('名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'select', 'name' => 'type', 'title' => '类型', 'tips' => '', 'extra' => ['1'=>'会员类型_金银铜','2'=>'会员类型_年季月','3'=>'终身'] , 'value' => '1'],
            ['type' => 'image', 'name' => 'vip_image', 'title' => lang('VIP图标'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'image', 'name' => 'bg_image', 'title' => lang('会员卡背景'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'color', 'name' => 'color', 'title' => lang('字体颜色'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'price', 'title' => lang('价格'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'cost', 'title' => lang('价值'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'number', 'name' => 'days', 'title' => lang('充值天数'), 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'text', 'name' => 'discount', 'title' => lang('折扣（0.00-1.00）'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'number', 'name' => 'level', 'title' => lang('等级标识'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'textarea', 'name' => 'content', 'title' => lang('特权说明'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'radio', 'name' => 'status', 'title' => '状态', 'tips' => '必填项', 'attr' => '','extra' => ['关闭','开启'], 'value' => ''],
        ];
        $this->assign('page_title', lang('编辑会员等级'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }

    /**
     *
     */
    public function delete_card_data()
    {
        // 启动事务
        Db::startTrans();
        try {
            $res = Db::name('user_level_card_data')->where('id','<>',0)->update(['status'=>0]);
            if (!$res){
                exception(lang('更新0条数据'));
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success(lang('清除成功'));
        return $this->index();
    }
}
