<?php
/*
 * @Descripttion:
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-04-06 08:50:37
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-28 11:35:11
 */

namespace app\goods\admin;

use app\admin\admin\Base;
use app\common\model\GoodsAnswer as GoodsAnswerModel;
use app\common\model\GoodsQuestion as GoodsQuestionModel;
use app\goods\model\GoodsCollect;
use service\Format;

class GoodsAnswer extends Base
{
    /**
     * 问题列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author zhougs
     * @since 2020年12月31日10:56:45
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $question_id = input("param.question_id");
        $map = $this->getMap();
        // 查询
        $where = [];
        if ($map['question_content']) {
            $where[] = ["q.content","like",'%'.$map['question_content'].'%'];
        }
        if ($map['answer_content']) {
            $where[] = ["ga.content","like",'%'.$map['answer_content'].'%'];
        }
        if ($map['goods_name']) {
            $where[] = ["goods_name","like",'%'.$map['goods_name'].'%'];
        }
        if ($map['user_nickname']) {
            $where[] = ["user_nickname","like",'%'.$map['user_nickname'].'%'];
        }
        if ($question_id) {
            $where[] = ["ga.question_id","=",$question_id];
        }
        // 排序
        $order = $this->getOrder("ga.id desc");
        // 数据列表
        $data_list = GoodsAnswerModel::alias('ga')
            ->leftJoin("goods_question q", "q.id=ga.question_id")
            ->leftJoin('user u', 'u.id=ga.user_id')
            ->field("ga.user_id,ga.status,ga.id,q.id question_id,q.content question_content,ga.content answer_content,goods_name,goods_thumb,ga.create_time,u.user_nickname,u.head_img")
            ->where($where)
            ->order($order)
            ->paginate()
            ->each(function ($item) {
                $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                $item['head_img'] = get_file_url($item['head_img']);
                $item['question_content'] = htmlspecialchars($item['question_content']);
                $item['answer_content'] = htmlspecialchars($item['answer_content']);
                if ($item['user_id'] == 0) {
                    $item['user_nickname'] = lang('商家');
                }                
            });
        $fields = [
            ['id', 'ID'],
            ['question_content', lang('问题内容')],
            ['answer_content', lang('答案内容')],
            ['goods_thumb', lang('商品图'),'picture'],
            ['user_nickname', lang('回答人')],
            ['head_img', lang('回答人头像'),'picture'],
            ['status', lang('状态'),'status','',[lang('待审核'),lang('已审核')]],

            ['create_time', lang('回答时间')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $searchFields = [
            ['question_content', lang('问题内容'), 'text'],
            ['answer_content', lang('答案内容'), 'text'],
            ['goods_name', lang('商品名称'), 'text'],
            ['user_nickname', lang('提问人'), 'text'],
        ];

        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setRightButton(['ident'=> 'disable', 'title'=>lang('禁用'),'href'=>['setstatus',['type'=>'disable','ids'=>'__id__']],'icon'=>'fa fa-ban pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->setTopSearch($searchFields)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }
}
