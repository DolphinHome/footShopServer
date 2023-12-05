<?php
namespace app\circle\admin;

use app\admin\admin\Base;
use app\circle\model\Comment as CommentModel;
use service\Format;
use think\Db;

/**
 * 会员主表控制器
 * @package app\User\admin
 */
class Comment extends Base
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = $this->getMap(['user_id']);
        $search_fields = [
            ['user_id', lang('用户').'id', 'text'],
            ['circle_id',lang('动态').'id','text']
        ];
        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = CommentModel::where($map)->order($order)->paginate();
        $fields = [
            ['id', 'ID'],
            ['user_id', lang('用户').'id'],
            ['circle_id',lang('动态').'id'],
            ['content', lang('内容')],
            ['createtime',lang('发布时间')],
            ['right_button', lang('操作'), 'btn']
        ];
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setTopSearch($search_fields)
        //->setTopButtons($this->top_button)
        ->setTopButtons([
                ['ident' => 'delete','title' => lang('批量删除'),'href' => 'delete','icon' => 'fa fa-times pr5','class' => 'btn btn-sm mr5 btn-default btn-flat ajax-post confirm','extra' => 'target-form="ids"']
            ])
        //->setRightButtons($this->right_button)
        ->setRightButtons(array(array(
            'ident' => 'delete',
            'title' => lang('删除'),
            'href' =>
            array(
              0 => 'delete',
              1 =>
              array(
                'ids' => '__id__',
              ),
            ),
            'icon' => 'fa fa-times pr5',
            'class' => 'btn btn-xs mr5 btn-default btn-flat ajax-get confirm',
        )))
        ->setData($data_list)//设置数据
        ->fetch();//显示
    }
    //删除
    public function delete($ids = null)
    {
        if ($ids === null) {
            $this->error(lang('参数错误'));
        }
        $list = CommentModel::where('id', 'in', $ids)->select();
        $count=0;
        Db::startTrans();
        try {
            foreach ($list as $k => $v) {
                $count += $v->delete();
                Db::name('circle')->where('id', $v->circle_id)->setDec('comments');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success(lang('删除成功'));
        } else {
            $this->success(lang('删除失败'));
        }
    }
}
