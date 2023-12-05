<?php
/*
 * @Descripttion: 
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-30 10:51:23
 */
namespace app\circle\admin;

use app\admin\admin\Base;
use app\circle\model\Circle as CircleModel;
use service\Format;
use think\Db;

/**
 * 动态主表控制器
 * @package app\User\admin
 */
class Index extends Base
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = $this->getMap(['user_id']);
        $search_fields = [
            ['user_id', lang('用户').'id', 'text'],
            ['is_report',lang('举报状态'),'text']
        ];
        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = CircleModel::where($map)->order($order)->paginate();
        $fields = [
            ['id', 'ID'],
            ['user_id', lang('用户').'id'],
            ['content', lang('内容')],
            ['image',lang('图片'),'pictures'],
            ['comments',lang('评论数')],
            ['likes',lang('点赞数')],
            ['createtime',lang('发布时间')],
            ['is_report', lang('是否被举报').'(1'.lang('是').'0'.lang('是').')'],
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
        ->setRightButtons([
            ['ident' => 'edit','title' => lang('查看评论'),'href' =>['delete',['circle_id'=>'__id__'],'circle/comment/index'],'icon' => 'fa fa-pencil pr5','class' => 'btn btn-xs mr5 btn-default btn-flat'],
            ['ident' => 'delete','title' => lang('删除'),'href' =>['delete',['ids'=>'__id__']],'icon' => 'fa fa-times pr5','class' => 'btn btn-xs mr5 btn-default btn-flat ajax-get confirm']
        ])
        ->setData($data_list)//设置数据
        ->fetch();//显示
    }
    //删除
    public function delete($ids = null)
    {
        if ($ids === null) {
            $this->error(lang('参数错误'));
        }
        $list = CircleModel::where('id', 'in', $ids)->select();
        $count=0;
        Db::startTrans();
        try {
            foreach ($list as $k => $v) {
                $count += $v->delete();
                Db::name('circle_comment')->where('circle_id', $v->id)->delete();
                Db::name('circle_like')->where('circle_id', $v->id)->delete();
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
