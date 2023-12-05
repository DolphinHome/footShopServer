<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\ArticleColumn as ColumnModel;
use app\operation\model\Article;
use service\Tree;
use service\Format;
use service\ApiReturn;


/**
 * 栏目控制器
 * Class Column
 * @package app\cms\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/4 14:20
 */
class ArticleColumn extends Base
{
    /**
     * 栏目列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /*
     * 文章分类
     *
     */
    public function article_cate()
    {
        // 查询
        $map = $this->getMap();
        $where = [];
        if (isset($map['keyword'])) {
            $where[] = ['name', 'like', '%' . $map['keyword'] . '%'];
        }
        $where[] = ['is_system', '=', 0];
        // 数据集
        $data_list = ColumnModel::where($where)->order('sort,id')->column(true);
        $data_list = $this->generateTree($data_list);
//        foreach ($data_list as &$v) {
//            $v['thumb_img'] = get_file_url($v['thumb']);
//            foreach ($v['children'] as &$m) {
//                $m['thumb_img'] = get_file_url($m['thumb']);
//                foreach ($m['children'] as &$n) {
//                    $n['thumb_img'] = get_file_url($n['thumb']);
//                }
//            }
//        }
        return ApiReturn::r(1, $data_list, 'ok');
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
        $data['is_system'] = 0;
        $id = $data['id'];
        if (isset($id) && !empty($id)) {
            //更新
            ColumnModel::where(['id' => $data['id']])->update($data);
        } else {
            $id = (new ColumnModel())->insertGetId($data);
        }
        return ApiReturn::r(1, ['id' => $id], 'ok');
    }

    /**
     * 系统设置页面
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function article_system()
    {
        // 查询
        $map = $this->getMap();
        $map[] = ['is_system', '=', 1];
        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = ColumnModel::where($map)->order($order)->column(true);
        if (empty($map)) {
            $data_list = Tree::config(['title' => 'name'])->toList($data_list);
        }

        $fields = [
            ['id', 'ID'],
            ['name', lang('页面名称'), 'callback', function ($value, $data) {
                return isset($data['title_prefix']) ? $data['title_display'] : $value;
            }, '__data__'],
//            ['thumb', lang('栏目图'), 'picture'],
//            ['hide', lang('是否隐藏'), 'status', '', [lang('否'), lang('是')]],
            ['update_time', lang('更新时间'), 'datetime'],
            ['sort', lang('排序'), 'text.edit'],
            ['status', lang('状态'), 'status', '', [lang('禁用'), lang('正常')]],
            ['right_button', lang('操作'), 'btn']
        ];

        unset($this->right_button[2]);
        $this->right_button[0]['href'] = 'edit_sys';
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButton(['title' => lang('新增系统页面'), 'href' => ['add_sys', ['pid' => '0']], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary layeredit', 'layer' => 1])
//            ->setRightButton(['title' => lang('新增子栏目'), 'href' => ['add_sys', ['pid' => '__id__']], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-xs mr5 primary '])
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示


    }

    /**
     * 新增系统页面
     * @param int $pid 父级id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function add_sys($pid = 0)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['is_system'] = 1;
            $data['pid'] = 0;
            $data['type'] = 0;
            $data['hide'] = 0;

            // 验证
            $result = $this->validate($data, 'Column');

            if (true !== $result) $this->error($result);

            if ($column = ColumnModel::create($data)) {
                cache('cms_column_list', null);
                // 记录行为
                action_log('column_add', 'cms_column', $column['id'], UID, $data['name']);
                $this->success(lang('新增成功'), 'article_system');
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
//            ['type' => 'select', 'name' => 'pid', 'title' => lang('所属栏目'), 'tips' => lang('必选'), 'extra' => ColumnModel::getTreeList(), 'value' => $pid],
            ['type' => 'text', 'name' => 'name', 'title' => lang('页面名称'), 'tips' => lang('必填')],
//            ['type' => 'image', 'name' => 'cat_img', 'title' => lang('栏目图'), 'tips' => lang('请上传图片')],
//            ['type' => 'radio', 'name' => 'type', 'title' => lang('栏目类型'), '', 'extra' => [lang('最终列表栏目'), lang('单页')], 'value' => 0],
//            ['type' => 'radio', 'name' => 'hide', 'title' => lang('是否隐藏栏目'), 'tips' => lang('隐藏后前台不可见'), 'extra' => [lang('显示'), lang('隐藏')], 'value' => 0],
            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序'), 'value' => 100],
            ['type' => 'wangeditor', 'name' => 'content', 'title' => lang('内容')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'extra' => [lang('否'), lang('是')], 'value' => 1],
        ];
        $this->assign('page_title', lang('新增系统页面'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑系统页面
     * @param string $id 栏目id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function edit_sys($id = '')
    {
        if ($id === 0) $this->error(lang('参数错误'));

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['pid'] = 0;
            $data['pid'] = 0;
            $data['type'] = 0;
            $data['hide'] = 0;
            $data['update_time'] = time();
            // 验证
            $result = $this->validate($data, 'Column');
            // 验证失败 输出错误信息
            if (true !== $result) $this->error($result);

            if (ColumnModel::update($data)) {
                // 记录行为
                action_log('column_edit', 'cms_column', $id, UID, $data['name']);

                return $this->success(lang('编辑成功'), 'article_system');
            } else {
                return $this->error(lang('编辑失败'));
            }
        }

        // 获取数据
        $info = ColumnModel::get($id);

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
//            ['type' => 'select', 'name' => 'pid', 'title' => lang('所属栏目'), 'tips' => lang('必选'), 'extra' => ColumnModel::getTreeList()],
            ['type' => 'text', 'name' => 'name', 'title' => lang('页面名称'), 'tips' => lang('必填')],
//            ['type' => 'image', 'name' => 'cat_img', 'title' => lang('栏目图'), 'tips' => lang('请上传图片')],
//            ['type' => 'radio', 'name' => 'type', 'title' => lang('栏目类型'), '', 'extra' => [lang('最终列表栏目'), lang('单页')]],
//            ['type' => 'radio', 'name' => 'hide', 'title' => lang('是否隐藏栏目'), 'tips' => lang('隐藏后前台不可见'), 'extra' => [lang('显示'), lang('隐藏')]],
            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序'), '', 100],
            ['type' => 'wangeditor', 'name' => 'content', 'title' => lang('内容')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'extra' => [lang('否'), lang('是')]],
        ];
        $this->assign('page_title', lang('编辑系统页面'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /**
     * 新增栏目
     * @param int $pid 父级id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function add($pid = 0)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Column');

            if (true !== $result) $this->error($result);

            if ($ret = ColumnModel::create($data)) {
                cache('cms_column_list', null);
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('operation_article_column_add', 'operation_article_column', $ret->id, UID, $details);
                $this->success(lang('新增成功'), 'index');
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'select', 'name' => 'pid', 'title' => lang('所属栏目'), 'tips' => lang('必选'), 'extra' => ColumnModel::getTreeList(), 'value' => $pid],
            ['type' => 'text', 'name' => 'name', 'title' => lang('栏目名称'), 'tips' => lang('必填')],
            ['type' => 'image', 'name' => 'cat_img', 'title' => lang('栏目图'), 'tips' => lang('请上传图片')],
            ['type' => 'radio', 'name' => 'type', 'title' => lang('栏目类型'), '', 'extra' => [lang('最终列表栏目'), lang('单页')], 'value' => 0],
//            ['type' => 'radio', 'name' => 'hide', 'title' => lang('是否隐藏栏目'), 'tips' => lang('隐藏后前台不可见'), 'extra' => [lang('显示'), lang('隐藏')], 'value' => 0],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'extra' => [lang('否'), lang('是')], 'value' => 1],
            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序'), 'value' => 100],
            ['type' => 'wangeditor', 'name' => 'content', 'title' => lang('栏目内容'), 'tips' => lang('可作为单页使用')],
        ];
        $this->assign('page_title', lang('新增文章分类'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑栏目
     * @param string $id 栏目id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function edit($id = '')
    {
        if ($id === 0) $this->error(lang('参数错误'));
        // 获取数据
        $info = ColumnModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Column');
            // 验证失败 输出错误信息
            if (true !== $result) $this->error($result);

            if (ColumnModel::update($data)) {
                // 记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('operation_article_column_edit', 'operation_article_column', $id, UID, $details);
                return $this->success(lang('编辑成功'), 'index');
            } else {
                return $this->error(lang('编辑失败'));
            }
        }

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'select', 'name' => 'pid', 'title' => lang('所属栏目'), 'tips' => lang('必选'), 'extra' => ColumnModel::getTreeList()],
            ['type' => 'text', 'name' => 'name', 'title' => lang('栏目名称'), 'tips' => lang('必填')],
            ['type' => 'image', 'name' => 'cat_img', 'title' => lang('栏目图'), 'tips' => lang('请上传图片')],
            ['type' => 'radio', 'name' => 'type', 'title' => lang('栏目类型'), '', 'extra' => [lang('最终列表栏目'), lang('单页')]],
//            ['type' => 'radio', 'name' => 'hide', 'title' => lang('是否隐藏栏目'), 'tips' => lang('隐藏后前台不可见'), 'extra' => [lang('显示'), lang('隐藏')]],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'extra' => [lang('否'), lang('是')]],
            ['type' => 'text', 'name' => 'sort', 'title' => lang('排序'), '', 100],
            ['type' => 'wangeditor', 'name' => 'content', 'title' => lang('栏目内容'), lang('可作为单页使用')],
        ];
        $this->assign('page_title', lang('编辑文章分类'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /**
     * 删除栏目
     * @param null $ids 栏目id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function delete($ids = null)
    {
        if ($ids === null) $this->error(lang('参数错误'));

        // 检查是否有子栏目
        if (ColumnModel::where('pid', $ids)->find()) {
            $this->error(lang('请先删除或移动该栏目下的子栏目'));
        }

        // 检查是否有文档
        if (Article::where('category_id', $ids)->find()) {
            $this->error(lang('请先删除或移动该栏目下的所有文档'));
        }

        // 检查是否有子栏目
        if (ColumnModel::where('id', 'in', $ids)->delete()) {
            $this->success(lang('删除成功'));
        } else {
            $this->success(lang('删除失败'));
        }

    }
}