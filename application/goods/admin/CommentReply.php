<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 似水星辰[2630481389@qq.com]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

namespace app\goods\admin;

use app\admin\admin\Base;
use Think\Db;
use service\Format;
use app\goods\model\GoodsCommentReply;

/**
 * 商品评论回复表控制器
 * @package app\Goods\admin
 */
class CommentReply extends Base
{
    /**
     * 商品评论列表
     * @param int $type 商品ID
     * @return mixed|void
     * @author jxy [ 41578218@qq.com ]
     */
    public function index()
    {
        $gc_id = $this->request->param('id', 0);

        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $map = ['gc_id'=>$gc_id];
        $data_list = Db::name('goods_comment_reply')->alias('r')
        ->where($map)
        ->order('id desc')
        ->paginate()->each(function ($v) {
            $v['create_time']= date('Y-m-d H:i:s');
            return $v;
        });
        //halt($data_list);
        $this->assign('data_list', $data_list);
        $this->assign('pages', $data_list->render());
        $fields = [
            ['id', 'ID'],
            ['user_nickname',lang('昵称')],
            ['content', lang('回复内容'),'text.tip'],
            ['create_time',lang('回复时间')],
            ['is_merchant',lang('是否商家回复'),[0=>'否',1=>'是']],
            ['right_button', lang('操作'), 'btn']
        ];
        $right_button = [
            ['ident'=> 'edit', 'title'=>'编辑','href'=>'edit','icon'=>'fa fa-pencil pr5','class'=>'btn btn-xs mr5 btn-default layeredit',
            'layer' => 1],
            ['ident'=> 'delete', 'title'=>'删除','href'=>['delete',['id'=>'__id__']],'icon'=>'fa fa-times pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'],
        ];
        return Format::ins()
            ->hideCheckbox()
            ->addColumns($fields)
            ->setRightButtons($right_button)
            ->setData($data_list)
            ->fetch();
    }


    public function add()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $goods_id = input('param.ids');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['order_sn'] = get_order_sn('GD');
            $data['goods_id'] = $goods_id;
            $data['user_id'] = 0;
            $data['create_time'] = time();
            $data['status'] = 1;
            $data['type'] = 1;
            $data['sku_id'] = 0;
            if (Db::name('goods_comment')->insert($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        $fields = [
            ['type' => 'textarea', 'name' => 'content', 'title' => lang('评论内容'), 'tips' => '', 'attr' => ''],
        ];
        $this->assign('page_title', lang('编辑评论'));
        $this->assign('form_items', $this->setData($fields));
        return $this->fetch('admin@public/add');
    }

    /**
     * 商品评论编辑
     * @param int $id 评论ID
     * @return mixed|void
     * @author jxy [ 41578218@qq.com ]
     */
    public function edit($id)
    {
        $comment=Db::name('goods_comment_reply')->get($id);
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (Db::name('goods_comment_reply')->update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $comment);
                action_log('goods_comment_reply_edit', 'goods_comment_reply', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
       
        $fields = [
             ['type' => 'hidden', 'name' => 'id'],
             ['type' => 'textarea', 'name' => 'content', 'title' => lang('评论内容'), 'tips' => '', 'attr' => ''],
         ];
        $this->assign('page_title', lang('编辑回复'));
        $this->assign('form_items', $this->setData($fields, $comment));
        return $this->fetch('admin@public/edit');
    }

    /**
     * 删除
     */
    public function delete($id)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
       
        GoodsCommentReply::where(['id' => $id])->delete();
        $this->success(lang('删除成功'));
    }

}
