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
use app\goods\model\GoodsComment;
use app\goods\model\GoodsCommentReply;

/**
 * 商品评论主表控制器
 * @package app\Goods\admin
 */
class Comment extends Base
{
    /**
     * 商品评论列表
     * @param int $type 商品ID
     * @return mixed|void
     * @author jxy [ 41578218@qq.com ]
     */
    public function index()
    {
        $goods_id = $this->request->param('goods_id', 0);

        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $map = [];
        if (!empty($goods_id)) {
            $map[]=['g.goods_id','=',$goods_id];
        }

        $param = $this->getMap();
        if (isset($param['goods_name'])) {
            $map[] = ['goods.name', 'like', '%' . $param['goods_name'] . '%'];
        }
        if (isset($param['user_name'])) {
            $map[] = ['u.user_name', '=', $param['user_name']];
        }
        if (isset($param['status']) && $param['status'] != 'all') {
            $map[] = ['g.status', '=', $param['status']];
        }
        if (isset($param['create_time'])) {
            $time = explode(' - ', $param['create_time']);
            $map[] = ['g.create_time', '>=', strtotime($time[0])];
            $map[] = ['g.create_time', '<=', strtotime($time[1])];
        }
        $data_list = Db::name('goods_comment')->alias('g')
        ->join('user u', 'g.user_id=u.id', 'left')
        ->join('goods', 'g.goods_id=goods.id', 'left')
        ->field('g.*,goods.sn,goods.name,u.user_nickname,u.head_img,u.mobile,g.video')
        ->where($map)
        ->order('id desc')
        ->paginate()->each(function ($v) {
            $v['thumb']=get_files_url($v['thumb']);
//            $v['video']=get_files_url($v['video']);
//            $v['video']=$v['video'][0];
            //$v['name']= mb_substr($v['name'],0,8);
            return $v;
        });
        //halt($data_list);
        $this->assign('data_list', $data_list);
        $this->assign('pages', $data_list->render());
        $fields = [
            ['id', 'ID'],
            ['user_nickname',lang('昵称')],
            ['mobile',lang('手机号')],
            ['sn',lang('货号')],
            ['name',lang('商品名称'),'text.tip'],
            ['content', lang('评论内容'),'text.tip'],
            ['thumb', lang('评论图片'),'pictures'],
            /*['video', lang('评论视频')],*/
//            ['video', lang('视频'), 'callback', function ($item, $data) {
//                $str = '[暂无文件]';
//                if ($item) {
//                    $str = '[<a data-toggle="dialog" data-width="800" data-height="600" onclick="access_statistics(1, '.$data['id'].')" href="'.get_file_url($item).'">评论视频</a>]';
//                }
//                return $str;
//            }, '__data__'],
            ['status', lang('状态'), 'status', '', [lang('禁用'),lang('启用')]],
            ['right_button', lang('操作'), 'btn']
        ];

        $search_fields = [
            ['goods_name', lang('商品'), 'text'],
            ['user_name', lang('会员'), 'text'],
            ['status', lang('状态'), 'select', '', ['all' => '全部', '0' => '禁用', '1' => '启用']],
            ['create_time', lang('时间'), 'daterange'],
        ];
        if (count($data_list)<=0) {
            $this->bottom_button_select = [];
        }
        return Format::ins()
            ->addColumns($fields)
            ->setPrimaryKey('id')
            ->setTopSearch($search_fields)
            ->setTopButtons($this->top_button, ['add'])
            ->setRightButtons($this->right_button)
//            ->setRightButton([
//                'ident' => 'reply',
//                'title' => lang('回复'),
//                'href' => ['reply', ['id' => '__id__', 'layer' => 1, 'reload' => 1]],
//                'icon' => 'fa fa-check-circle pr5',
//                'class' => 'btn btn-xs mr5 btn-default layeredit',
//                'layer' => 1,
//            ])
//            ->setRightButton(['title' => lang('查看回复'), 'href'=>['/goods/comment_reply/index',['id'=>'__id__']], 'icon' => 'fa fa-list pr5', 'class' => 'btn btn-xs mr5 btn-default '])
            ->bottom_button_select($this->bottom_button_select)
            ->replaceRightButton(['status'=>1], '', 'read')
            ->js('/static/admin/js/statistical.js')
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
            if ($res = Db::name('goods_comment')->insertGetId($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_comment_add', 'goods_comment', $res, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        $fields = [
            ['type' => 'textarea', 'name' => 'content', 'title' => lang('评论内容'), 'tips' => '', 'attr' => ''],
            ['type' => 'images', 'name' => 'thumb', 'title' => lang('评论图片'), 'tips' => '', 'attr' => ''],
            ['type' => 'number', 'name' => 'star', 'title' => lang('评价等级'), 'tips' => '', 'attr' => ''],
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
        $comment = Db::name('goods_comment')->get($id);
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (Db::name('goods_comment')->update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $comment);
                action_log('goods_comment_edit', 'goods_comment', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
        
        $fields = [
             ['type' => 'hidden', 'name' => 'id'],
             ['type' => 'textarea', 'name' => 'content', 'title' => lang('评论内容'), 'tips' => '', 'attr' => ''],
             ['type' => 'images', 'name' => 'thumb', 'title' => lang('评论图片'), 'tips' => '', 'attr' => '','limit'=>9],
             ['type' => 'number', 'name' => 'star', 'title' => lang('评价等级'), 'tips' => '', 'attr' => ''],
             ['type' => 'alivideo', 'name' => 'video', 'title' => '评论视频', 'tips' => '', 'attr' => ''],
         ];
        $this->assign('page_title', lang('编辑评论'));
        $this->assign('form_items', $this->setData($fields, $comment));
        return $this->fetch('admin@public/edit');
    }

    /**
     * 设置评论状态
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


        empty($ids) && $this->error(lang('缺少主键'));

        if (empty($type)) {
            $type= input('param.action');
        }

        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = Db::name('goods_comment')->where('id', 'IN', $ids)->setField('status', 0);
                //action_log('goods_disable', 'goods', 0, UID,'批量禁用商品ID:'.$ids);
                break;
            case 'enable': // 启用
                $result = Db::name('goods_comment')->where('id', 'IN', $ids)->setField('status', 1);
                // action_log('goods_enable', 'goods', 0, UID,'批量启用商品ID:'.$ids);
                break;
            case 'delete': // 删除
                $result = Db::name('goods_comment')->where('id', 'IN', $ids)->delete();
                //action_log('goods_delete', 'goods', 0, UID,'批量删除商品ID:'.$ids);
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // \Cache::clear();

            // 记录行为
            action_log('admin_user_' . $type, 'user', $ids, UID, 'ID：' . implode('、', $ids));
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }

        //回复
    public function reply($id = 0)
    {
        if (!$id) {
            $this->error(lang('缺少参数'));
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($data['reply_id']) {
                $res = Db::name("goods_comment_reply")->where(['id'=>$data['reply_id']])->update(['content'=>$data['reply_content']]);
            } else {
                // 验证
                $user = Db::name('admin')->where('id', UID)->field('avatar','nickname')->find();
                $insData = [
                    'user_id' => UID,
                    'user_nickname' => $user['nickname'],
                    'head_img' => $user['avatar'],
                    'content' => $data['reply_content'],
                    'gc_id' => $id,
                    'is_merchant'=>1,
                    'create_time' => time(),
                ];
                $res = Db::name("goods_comment_reply")->insertGetId($insData);
            }
            
            if ($res !== false) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_comment_reply', 'goods_comment_reply', $res, UID, $details);
                $this->success(lang('回复成功'), cookie('__forward__'));
            } else {
                $this->error(lang('回复失败'));
            }
            
        }

        $comment_main = GoodsComment::get($id); 
        $reply_merchant = GoodsCommentReply::where(['gc_id'=>$id, 'is_merchant'=>1,  'user_id' => UID])->field('id,content')->order('id desc')->find();
        $info['reply_content'] = $reply_merchant['content']??'';
        $info['reply_id'] = $reply_merchant['id']??'';
        $info['comment_main'] = $comment_main ['content'];
        $fields = [
            ['type' => 'hidden', 'name' => 'reply_id'],
            ['type' => 'textarea', 'name' => 'comment_main', 'title' => lang('原评论'), 'tips' => '', 'attr' => 'readonly', 'value' => ''],
            ['type' => 'textarea', 'name' => 'reply_content', 'title' => lang('填写回复'), 'tips' => '', 'attr' => '', 'value' => ''],
        ];
        $this->assign('page_title', lang('提交回复内容'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }
}
