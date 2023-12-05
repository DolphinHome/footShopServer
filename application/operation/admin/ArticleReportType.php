<?php
/**
 * Created by PhpStorm.
 * User: I5
 * Date: 2020/12/3
 * Time: 15:37
 */
namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\Article as ArticleModel;
use app\operation\model\ArticleColumn;
use service\Format;
use think\Db;

class ArticleReportType extends Base
{
    /*
 * 文章举报类型
 *
 */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 数据列表
        $data_list = Db::name('operation_article_report_type')->where($map)->order('id desc')->paginate(15, false, [
            'query' => $this->request->param()
        ]);
        $fields = [
            ['id', 'ID'],
            ['name', lang('名称')],
            ['status', lang('状态'), 'status'],
            ['right_button', lang('操作'), 'btn']
        ];

        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /*
    * 举报类型新增
    *
    */
    public function add()
    {
        // 保存文档数据
        if ($this->request->isAjax() || $this->request->isPost()) {
            $data = request()->param();
            $res =Db::name('operation_article_report_type')->insertGetId($data);
            if ($res) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('operation_article_report_type_add', 'operation_article_report_type', $res, UID, $details);
                $this->success(lang('新增成功'), 'index');
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('名称')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('是否禁用'), 'extra' => [lang('否'), lang('是')], 'value' => 0],
        ];
        $this->assign('page_title', lang('新增举报类型'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /*
     * 编辑
     *
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('参数错误'));
        }
        // 获取数据
        $info = Db::name('operation_article_report_type')->where(['id' => $id])->find();
        // 保存文档数据
        if ($this->request->isPost()) {
            $data = request()->param();
            $result = Db::name('operation_article_report_type')->update($data);
            if (false === $result) {
                $this->error(lang('编辑失败'));
            }
            // 记录行为
            unset($data['__token__']);
            $details = arrayRecursiveDiff($data, $info);
            action_log('operation_article_report_type_edit', 'operation_article_report_type', $id, UID, $details);
            $this->success(lang('编辑成功'), 'index');
        }
        
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('名称')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('是否启用'), 'extra' => [lang('否'), lang('是')]],
        ];
        $this->assign('page_title', lang('编辑举报类型'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /*
     * 删除
     */
    public function delete()
    {
        $id = request()->param('ids', 0);
        $res = Db::name('operation_article_report_type')->where([
            'id' => $id
        ])->delete();
        if ($res) {
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }
}
