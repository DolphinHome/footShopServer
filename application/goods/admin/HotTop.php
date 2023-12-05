<?php

namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\Activity as ActivityModel;
use service\Format;
use think\Db;
use app\goods\model\HotTop as HotTopModel;
use app\goods\model\Category;

class HotTop extends Base
{
    /**
     * 秒杀活动列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author jxy [41578218@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $map = $this->getMap();
        if (isset($map['status']) && $map['status'] == -1) {
            unset($map['status']);
        }

        // 数据列表
        $data_list = HotTopModel::where($map)->order("id desc")->paginate();


        $fields = [
            ['id', 'ID'],
            ['name', lang('名称')],
            ['category_id', lang('商品分类'), 'callback', function ($value, $data) {
                return Db::name("goods_category")->where(['id' => $value])->value("name");
            }, '__data__'],
            ['create_time', lang('添加时间')],
            ['status', lang('状态'), 'status', '', [lang('禁用'), lang('启用')], 'text-center'],
            ['right_button', lang('操作'), 'btn']
        ];

        $searchFields = [
            ['name', lang('活动名称'), 'text'],
            ['status', lang('状态'), 'select', '', [-1 => lang('全部'), 1 => lang('启用'), 0 => lang('禁用')]],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->setOrder('user_money,score,total_consumption_money,count_score')
            ->setTopSearch($searchFields)
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
//            ->bottom_button_select($this->bottom_button_select)
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /*
     *新增
     *
     */
    public function add()
    {        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['create_time'] = time();

            if ($res = HotTopModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_hot_top_add', 'goods_hot_top', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        $list_type = Category::getMenuTree();

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('名称')],
            ['type' => 'select', 'name' => 'category_id', 'title' => lang('商品分类'), 'extra' => $list_type],
        ];
        $this->assign('page_title', lang('新增榜单'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /*
     *
     * 编辑
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = HotTopModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $res = HotTopModel::where(['id' => $id])->update($data);
            if ($res) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('goods_hot_top_edit', 'goods_hot_top', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
        $list_type = Category::getMenuTree();
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('名称'), 'tips' => '', 'attr' => ''],
            ['type' => 'select', 'name' => 'category_id', 'title' => lang('商品分类'), 'tips' => '', 'extra' => $list_type, 'value' => $info['category_id']],

        ];
        $this->assign('page_title', lang('编辑热卖榜单'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
    /**
     * 设置状态
     * @param string $type 类型：disable/enable
     * @param array $record 行为日志内容
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author jxy [ 41578218@qq.com ]
     */
    public function setStatus($type = '')
    {
        $ids   = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids   = (array)$ids;
        $field = input('param.field', 'status');

        empty($ids) && $this->error(lang('缺少主键'));


        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = HotTopModel::where('id', 'IN', $ids)->setField($field, 0);
                break;
            case 'enable': // 启用
                $result = HotTopModel::where('id', 'IN', $ids)->setField($field, 1);
                break;
            case 'delete': // 删除
                $result = HotTopModel::where('id', 'IN', $ids)->delete();
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // \Cache::clear();

            // 记录行为
            action_log('goods_activity_'.$type, 'goods', $ids, UID, 'ID：'.implode('、', $ids));
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }
}
