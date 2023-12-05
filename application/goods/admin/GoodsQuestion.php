<?php

namespace app\goods\admin;

use app\admin\admin\Base;
use app\common\model\GoodsAnswer;
use app\common\model\GoodsQuestion as GoodsQuestionModel;
use app\goods\model\GoodsCollect;
use service\Format;
use think\Db;

class GoodsQuestion extends Base
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

        $map = $this->getMap();
        // 查询
        $where = [];
        if ($map['content']) {
            $where[] = ["content","like",'%'.$map['content'].'%'];
        }
        if ($map['goods_name']) {
            $where[] = ["goods_name","like",'%'.$map['goods_name'].'%'];
        }
        if ($map['user_nickname']) {
            $where[] = ["user_nickname","like",'%'.$map['user_nickname'].'%'];
        }
        // 排序
        $order = $this->getOrder("q.id desc");
        // 数据列表
        $data_list = GoodsQuestionModel::alias('q')
            ->join('user u', 'u.id=q.user_id', 'left')
            ->field("q.status,q.id,goods_id,content,goods_name,goods_thumb,q.create_time,u.user_nickname,u.head_img,u.mobile")
            ->where($where)
            ->order($order)
            ->paginate()
            ->each(function ($item) {
                $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                $item['head_img'] = get_file_url($item['head_img']);
                $item['content'] = htmlspecialchars($item['content']);

                $goods = Db::name('goods')->where('id', $item['goods_id'])->find();
                $item['sn'] = $goods['sn'];
            });
        $fields = [
            ['id', 'ID'],
            ['content', lang('问题内容'),'link',url('goods_answer/index', ['question_id' => '__id__'])],
            ['goods_name', lang('商品名称')],
            ['sn', lang('商品货号')],
            ['goods_thumb', lang('商品图'),'picture'],
            ['user_nickname', lang('提问人')],
            ['mobile', lang('提问人手机号')],
            ['status', lang('状态'),'status','',[lang('待审核'),lang('已审核')]],
            ['create_time', lang('提问时间')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $searchFields = [
            ['content', lang('问题内容'), 'text'],
            ['goods_name', lang('商品名称'), 'text'],
            ['user_nickname', lang('提问人'), 'text'],
        ];

        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButton(['title' => lang('新增'), 'data-toggle' => 'dialog-right', 'href' => ['add', ['layer' => 1, 'reload' => 1, 'type' => 1]], 'icon'=>'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary btn-flat' ])
            ->setRightButton(['ident'=> 'delete', 'title'=>lang('删除'),'href'=>['delete_all',['ids'=>'__id__']],'icon'=>'fa fa-times pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->setRightButton(['ident'=> 'disable', 'title'=>lang('禁用'),'href'=>['setstatus',['type'=>'disable','ids'=>'__id__']],'icon'=>'fa fa-ban pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->setRightButton(['title' => lang('回答'), 'data-toggle' => 'dialog-right', 'href' => ['answer', ['id' => '__id__', 'layer' => 1, 'reload' => 1]], 'class' => 'btn btn-xs btn-default mr5 font12'])
            ->setTopSearch($searchFields)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $goods = Db::name('goods')->where('id', $data['goods_id'])->find();
            $question['goods_id'] = $data['goods_id'];
            $question['content'] = $data['content'];
            $question['acount'] = 0;
            $question['create_time'] = time();
            $question['user_id'] = 1;
            $question['goods_name'] = $goods['name'];
            $question['goods_thumb'] = $goods['thumb'];
            $question['activity_id'] = 0;
            $question['is_anonymous'] = 1;
            if ($res = GoodsQuestionModel::create($question)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_question_add', 'goods_question', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'select', 'name' => 'goods_id', 'title' => lang('请选择商品'), 'tips' => '', 'attr' => '','extra'=> '', 'value' => ''],
            ['type' => 'text', 'name' => 'content', 'title' => lang('问题内容'), 'tips' => '', 'attr' => '', 'value' => ''],

        ];

        $this->assign('page_title', lang('新增商品提问'));
        $this->assign('form_items', $fields);
        $this->assign('set_script', ['/static/admin/js/question.js']);
        return $this->fetch('admin@public/add');
    }

    public function getGoodsByCid($activity_id=0)
    {
        $map[] = ['status', '=', 1];
        $map[] = ['is_delete', '=', 0];
        $list = Db::name('goods')->where($map)->order('id desc')->select();
        echo json_encode(['code' => 1, 'msg' => lang('请求成功'), 'list' => $list, 'activity_type' => 3]);
        exit;
    }

    public function answer($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }

        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $answer['content'] = $data['description'];
            $answer['question_id'] = $data['id'];
            $answer['user_id'] = 0;
            $answer['pid'] = 0;
            $answer['flag'] = 0;
            $answer['create_time'] = time();
            $answer['is_anonymous'] = 1;
            $answer['status'] = 1;
            if ($res = GoodsAnswer::create($answer)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_question_reply', 'goods_question', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $info = GoodsQuestionModel::get($id);
        $info['description'] = "";
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'content', 'title' => lang('问题内容'), 'tips' => '', 'attr' => 'disabled', 'value' => ''],
            ['type' => 'textarea', 'name' => 'description', 'title' => lang('问题答案'), 'tips' => '', 'attr' => '', 'value' => '']

        ];
        $this->assign('page_title', lang('回答商品提问'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }



    public function delete_all($ids)
    {
        GoodsQuestionModel::where("id", $ids)->delete();
        GoodsAnswer::where("question_id", $ids)->delete();
        //记录行为
        action_log('goods_question_delete', 'goods_question', $ids, UID, $ids);
        return $this->success(lang('操作成功'));
    }
}
