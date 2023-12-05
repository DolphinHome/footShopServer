<?php
/*
 * @Descripttion: 
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-28 14:41:09
 */
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author jxy [ 41578218@qq.com ]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

namespace app\goods\admin;

use app\goods\model\Activity as ActivityModel;
use service\Format;

/**
 * 活动分类控制器
 * @package app\Activity\admin
 */
class Group extends Activity
{
    /**
     * 拼团活动列表
     * @author jxy [41578218@qq.com]
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $map = $this->getMap();
        // 查询
        $map['a.type'] = 2;
        if ($map['name']) {
            $map['a.name'] = $map['name'];
            unset($map['name']);
        }
        if ($map['show_position'] == 'all') {
            unset($map['show_position']);
        }
        // 排序
        $order = $this->getOrder("a.id desc");
        // 数据列表
        $data_list = ActivityModel::alias('a')->join('goods_category gc', 'gc.id=a.cid', 'left')->field('a.*,gc.name as title')->where($map)->order($order)->paginate();
        $fields = [
            ['id', 'ID'],
            ['name', lang('活动名称')],
            ['show_position', lang('展示位置'),'callback',function ($v) {
                if ($v=='index') {
                    return lang('手机首页');
                }
            }],
            ['sdate', lang('活动开始日期'),'callback',function ($v) {
                return date('Y-m-d H:i:s', $v);
            }],
            ['edate', lang('活动结束日期'),'callback',function ($v) {
                return date('Y-m-d H:i:s', $v);
            }],
            ['type', lang('活动类型'),'callback',function ($v) {
                if ($v==1) {
                    return lang('秒杀活动');
                } elseif ($v==2) {
                    return lang('拼团活动');
                }
            }],
            ['icon', lang('活动海报'),'picture', '', '', 'text-center'],
            ['title', lang('关联商品分类'),'callback',function ($v) {
                return $v ? $v : lang('未绑定');
            }],
            ['status', lang('状态'), 'status', '', [lang('禁用'), lang('启用')], 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
            ];
        array_unshift($this->top_button, ['ident'=> 'add', 'title'=>lang('新增'), 'href'=>'add?type=2', 'icon'=>'fa fa-plus pr5','class'=>'btn btn-sm mr5 btn-primary btn-flat']);
        $searchFields = [
            ['name', lang('活动名称'), 'text'],
            ['show_position',lang('展示位置'),'select','',['all'=>lang('全部'),'index'=>lang('手机首页'),'cate'=>lang('分页类')]],
        ];
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setTopButton(['title' => lang('新增'), 'data-toggle' => 'dialog-right', 'href' => ['add', ['layer' => 1, 'reload' => 1, 'type' => 2]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary btn-flat'])
            ->setTopButtons($this->top_button, ['add'])
        ->setRightButton(['title'=>lang('活动商品'),'href'=>['goods/activity_details/index',['activity_id'=>'__id__','type'=>2]],'class'=>'mr5 font12'])
            ->setRightButton(['title' => lang('添加商品'), 'data-toggle' => 'dialog-right', 'href' => ['goods/activity_details/add', ['id' => '__id__', 'layer' => 1, 'reload' => 0]],  'class' => 'mr5 font12'])
            ->setRightButton(['title' => lang('编辑'), 'data-toggle' => 'dialog-right', 'href' => ['edit', ['id' => '__id__', 'layer' => 1, 'reload' => 1]],  'class' => 'mr5 font12'])
        ->setRightButtons($this->right_button, ['edit'])
        ->setTopSearch($searchFields)
        ->setData($data_list)//设置数据
        ->fetch();//显示
    }
}
