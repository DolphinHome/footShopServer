<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\integral\admin;

use app\admin\admin\Base;
use app\integral\model\Category as CategoryModel;
use app\goods\model\Goods;
use app\goods\model\Type;
use app\goods\model\ActivityDetails as AD;
use service\ApiReturn;
use think\Db;

/**
 * 商品分类控制器
 * @package app\Category\admin
 */
class Category extends Base
{
    /**
     * 会员主表列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        // 保存排序
        if ($this->request->isPost()) {
            $modules = $this->request->post('sort/a');
            if ($modules) {
                $data = [];
                foreach ($modules as $key => $module) {
                    $data[] = [
                        'id' => $module,
                        'sort' => $key + 1
                    ];
                }
                $CategoryModel = new CategoryModel();
                if (false !== $CategoryModel->saveAll($data)) {
                    $this->success(lang('保存成功'));
                } else {
                    $this->error(lang('保存失败'));
                }
            }
        }

        return $this->fetch();
    }

    /*
     * 会员购物车列表
     *
     */
    public function cart()
    {
//        $data=$this->request->get();
//
//        $cart=Db::name("goods_cart")->where([
//            'user_id'=>
//        ])
    }

    /*
     *保存分类
     *
     *
     */
    public function saveCate()
    {
        $data = $this->request->post('data');
        $data['create_time'] = time();
        $data['is_show'] = 1;
        $id = $data['id'];
        if (isset($id) && !empty($id)) {
            //更新
            CategoryModel::where(['id' => $data['id']])->update($data);
        } else {
            $id = (new CategoryModel())->insertGetId($data);
        }
        return ApiReturn::r(1, ['id' => $id], 'ok');
    }

    /*
     * 获取商品分类数据
     *
     */
    public function getCate()
    {
        // 查询
        $map = $this->getMap();
        $where = [];
        if (isset($map['keyword'])) {
            $where[] = ['name', 'like', '%' . $map['keyword'] . '%'];
        }
        // 数据集
        $data_list = CategoryModel::where($where)->order('sort,id')->column(true);
        $data_list = $this->generateTree($data_list);
        foreach ($data_list as &$v) {
            $v['thumb_img'] = get_file_url($v['thumb']);
            foreach ($v['children'] as &$m) {
                $m['thumb_img'] = get_file_url($m['thumb']);
                foreach ($m['children'] as &$n) {
                    $n['thumb_img'] = get_file_url($n['thumb']);
                }
            }
        }
        return ApiReturn::r(1, $data_list, 'ok');
    }

    /*
     * 分类删除
     *
     */
    public function del()
    {
        $id = $this->request->post('data')['id'];
        $find = CategoryModel::where(['pid' => $id])->find();
        if ($find) {
            return ApiReturn::r(0, [], lang('有下级分类，不可以删除'));
        }
        CategoryModel::where(['id' => $id])->delete();
        return ApiReturn::r(1, [], 'ok');
    }


    /**
     * 新增
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function add($pid = 0)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Category.add');
            if (true !== $result) {
                $this->error($result);
            }
            if ($page = CategoryModel::create($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        $type = Type::where('status', 1)->column('id,name');
        $type[0] = lang('无');

        $fields = [
            ['type' => 'select', 'name' => 'pid', 'title' => lang('所属分类'), 'extra' => CategoryModel::getMenuTree(0), 'value' => $pid],
            ['type' => 'text', 'name' => 'name', 'title' => lang('分类名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            //['type' => 'text', 'name' => 'mobile_name', 'title' => lang('手机端分类名称'), 'tips' => lang('手机端显示的分类名称'), 'attr' => '', 'value' => ''],
            ['type' => 'select', 'name' => 'typeid', 'title' => lang('绑定规格属性'), 'extra' => $type, 'value' => '0', 'tips' => lang('绑定后分类下商品优先使用此多规格')],
            ['type' => 'image', 'name' => 'thumb', 'title' => lang('分类缩略图'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'radio', 'name' => 'is_hot', 'title' => lang('是否推荐为热门'), 'tips' => '', 'extra' => [lang('否'), lang('是')], 'value' => 0],
            ['type' => 'radio', 'name' => 'is_show', 'title' => lang('是否显示'), 'tips' => '', 'extra' => [lang('否'), lang('是')], 'value' => 1],
            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序'), 'tips' => '', 'attr' => '', 'value' => '99'],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), 'tips' => '', 'extra' => [lang('禁用'), lang('启用')], 'value' => 1]
        ];
        $this->assign('page_title', lang('新增商品分类'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 会员主表id
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
            $result = $this->validate($data, 'Category.add');
            if (true !== $result) {
                $this->error($result);
            }
            if (CategoryModel::update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info = CategoryModel::get($id);
        $type = Type::where('status', 1)->column('id,name');
        $type[0] = lang('无');

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'select', 'name' => 'pid', 'title' => lang('所属分类'), 'extra' => CategoryModel::getMenuTree(0)],
            ['type' => 'text', 'name' => 'name', 'title' => lang('分类名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            //['type' => 'text', 'name' => 'mobile_name', 'title' => lang('手机分类名称'), 'tips' => lang('手机端显示的分类名称'), 'attr' => ''],
            ['type' => 'select', 'name' => 'typeid', 'title' => lang('绑定规格属性'), 'extra' => $type, 'tips' => lang('绑定后分类下商品优先使用此多规格')],
            ['type' => 'image', 'name' => 'thumb', 'title' => lang('分类缩略图'), 'tips' => '', 'attr' => ''],
            ['type' => 'radio', 'name' => 'is_hot', 'title' => lang('是否推荐为热门'), 'tips' => '', 'extra' => [lang('否'), lang('是')]],
            ['type' => 'radio', 'name' => 'is_show', 'title' => lang('是否显示'), 'tips' => '', 'extra' => [lang('否'), lang('是')]],
            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序'), 'tips' => '', 'attr' => ''],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), 'tips' => '', 'extra' => [lang('禁用'), lang('启用')]]
        ];
        $this->assign('page_title', lang('编辑商品分类'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /**
     * 保存分类排序
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!empty($data)) {
                $cates = $this->parseMenu($data['cates']);
                foreach ($cates as $cate) {
                    if ($cate['pid'] == 0) {
                        continue;
                    }
                    CategoryModel::update($cate);
                }
                \Cache::clear();
                $this->success(lang('保存成功'));
            } else {
                $this->error(lang('没有需要保存的菜单'));
            }
        }
        $this->error(lang('非法请求'));
    }

    /**
     * 递归解析分类
     * @param array $cates 菜单数据
     * @param int $pid 上级菜单id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return array
     */
    private function parseMenu($cates = [], $pid = 0)
    {
        $sort = 1;
        $result = [];
        foreach ($cates as $cate) {
            $result[] = [
                'id' => (int)$cate['id'],
                'pid' => (int)$pid,
                'sort' => $sort,
            ];
            if (isset($cate['children'])) {
                $result = array_merge($result, $this->parseMenu($cate['children'], $cate['id']));
            }
            $sort++;
        }
        return $result;
    }

    /**
     * 设置状态
     * @param string $type 类型：disable/enable
     * @param array $record 行为日志内容
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function setStatus($type = '')
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;
        $field = input('param.field', 'status');

        empty($ids) && $this->error(lang('缺少主键'));

        $goods_id_list = Goods::where('cid', 'IN', $ids)->column('id');
        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = CategoryModel::where('id', 'IN', $ids)->setField($field, 0);
                Goods::where('cid', 'IN', $ids)->setField($field, 0);
                AD::where('goods_id', 'IN', $goods_id_list)->setField('status', 0);
                Db::name('goods_cart')->where([['goods_id', 'in', $goods_id_list]])->delete();
                break;
            case 'enable': // 启用
                $result = CategoryModel::where('id', 'IN', $ids)->setField($field, 1);
                Goods::where('cid', 'IN', $ids)->setField($field, 1);
                AD::where('goods_id', 'IN', $goods_id_list)->setField('status', 1);
                break;
            case 'delete': // 删除
                $goods_count = Goods::where('cid', 'IN', $ids)->where(['is_delete' => 0])->count();
                if ($goods_count) {
                    $this->error(lang('此分类下有商品，请先删除商品'));
                }
                $category_count = CategoryModel::where('pid', 'IN', $ids)->count();
                if ($category_count) {
                    $this->error(lang('请先删除子分类'));
                }
                $result = CategoryModel::where('id', 'IN', $ids)->delete();
                Db::name('goods_cart')->where([['goods_id', 'in', $goods_id_list]])->delete();
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // \Cache::clear();

            // 记录行为
            action_log('admin_goods_category_' . $type, 'user', $ids, UID, 'ID：' . implode('、', $ids));
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }

    /**
     * 设为/取消热门
     * $id int 分类id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function set_hot($type = '')
    {
        $id = $this->request->isPost() ? input('post.id/d') : input('param.id');
        if ($id == 0) {
            $this->error(lang('参数错误'));
        }

        switch ($type) {
            case 'enable': // 设为热门
                $result = CategoryModel::where('id', $id)->setField('is_hot', 1);
                break;
            case 'disable': // 取消热门
                $result = CategoryModel::where('id', $id)->setField('is_hot', 0);
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }
        if (false !== $result) {
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }

    /**
     * 获取嵌套式菜单
     * @param array $lists 原始菜单数组
     * @param int $pid 父级id
     * @param int $max_level 最多返回多少层，0为不限制
     * @param int $curr_level 当前层数
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return string
     */
    public function getNestMenu($lists = [], $max_level = 0, $pid = 0, $curr_level = 1)
    {
        $result = '';
        foreach ($lists as $key => $value) {
            if ($value['pid'] == $pid) {
                $disable = $value['status'] == 0 ? 'dd-disable' : '';

                // 组合菜单
                $result .= '<li class="dd-item dd3-item ' . $disable . '" data-id="' . $value['id'] . '">';
                $result .= '<div class="dd-handle dd3-handle">拖拽</div><div class="dd3-content"> ID：' . $value['id'] . ' ' . $value['name'] . ' <a style="font-weight: normal;font-size:12px;" href="' . url('goods/index/index') . '?cid=' . $value['id'] . '">[查看内容]</a>';

                $result .= '<div class="action">';
                $result .= '<a data-toggle="dialog-right" href="javascript:void(0);" data-url="' . url('add', ['pid' => $value['id'], 'layer' => 1, 'reload' => 0]) . '"  style="font-weight: normal">新增</a> | <a href="javascript:void(0);" data-url="' . url('edit', ['id' => $value['id'], 'layer' => 1, 'reload' => 0]) . '" data-original-title="编辑" style="font-weight: normal" data-toggle="dialog-right"> 编辑</a> |';
//                if ($value['is_hot'] == 0) {
//                    // 设为热门
//                    $result .= '<a href="'.url('set_hot',['id' => $value['id'], 'type' => 'enable']).'" class="ajax-get confirm"  style="font-weight: normal"> 设为热门</a> |';
//                } else {
//                    // 取消热门
//                    $result .= '<a href="'.url('set_hot',['id' => $value['id'], 'type' => 'disable']).'" class="ajax-get confirm" style="font-weight: normal"> 取消热门</a> |';
//                }
                if ($value['status'] == 0) {
                    // 启用
                    $result .= '<a href="' . url('setstatus', ['ids' => $value['id'], 'type' => 'enable']) . '" class="ajax-get" style="font-weight: normal"> 启用</a> |';
                } else {
                    // 禁用
                    $result .= '<a href="' . url('setstatus', ['ids' => $value['id'], 'type' => 'disable']) . '" class="ajax-get confirm" style="font-weight: normal"> 禁用</a> |';
                }
                $result .= '<a href="' . url('delete', ['ids' => $value['id']]) . '" data-original-title="删除" class="ajax-get confirm" style="font-weight: normal">删除</a></div>';
                $result .= '</div>';

                if ($max_level == 0 || $curr_level != $max_level) {
                    unset($lists[$key]);
                    // 下级菜单
                    $children = $this->getNestMenu($lists, $max_level, $value['id'], $curr_level + 1);
                    if ($children != '') {
                        $result .= '<ol class="dd-list">' . $children . '</ol>';
                    }
                }

                $result .= '</li>';
            }
        }
        return $result;
    }
}
