<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\SystemMessage as SystemMessageModel;
use app\user\model\User;
use service\Format;

/**
 * 站内信
 * Class SystemMessage
 * @package app\operation\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @created 2019/4/28 11:02
 */
class SystemMessage extends Base
{
    /**
     * 任务列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();        // 排序
        if (isset($map['title'])) {
            $map[] = ['title', 'like', '%' . $map['title'] . '%'];
            unset($map['title']);
        }
        if (isset($map['is_read'])) {
            if ($map['is_read'] != -1) {
                $map[] = ['is_read', '=', $map['is_read']];
            }
            unset($map['is_read']);
        }
        if (isset($map['create_time'])) {
            $create_time = explode(' - ', $map['create_time']);
            $start_time = strtotime($create_time[0]);
            $end_time = strtotime($create_time[1]);
            $map[]=['create_time','>=',$start_time];
            $map[]=['create_time','<=',$end_time];
            unset($map['create_time']);
        }
        $order = $this->getOrder('id DESC');
        $dataList = SystemMessageModel::where($map)->order($order)->paginate();
        $fields = [
            ['id', '序号'],
            ['to_user_id', '接收人', 'callback', function ($value) {
                return User::where(['id' => $value])->value('user_nickname');
            }],
            ['title', '消息标题'],
            ['content', '消息内容','callback',function($value,$data){
                return "<a href=" . url('messageInfo', ['id' => $data['id'], 'layer' => 1]) . " class='layeredit'>详情</a>";
//                return "<a href=" . url('messageInfo', ['id' => $data['id'], 'layer' => 1]) . " class='layeredit'>详情</a>";
            }, '__data__'],
            ['is_read', '是否阅读', 'callback', function ($value) {
                return $this->getIsRead($value);
            }],
            ['create_time', '创建时间'],
            ['right_button', '操作', 'btn']
        ];
        $search_fields = [
            ['title', lang('消息标题'), 'text'],
            ['is_read', lang('是否阅读'), 'select', '', ['-1' => lang('全部'), '0' => lang('未读'), '1' => lang('已读')]],
            ['create_time', lang('创建时间'), 'daterange'],
        ];
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setTopSearch($search_fields)
            ->setTopButtons($this->top_button, ['disable', 'enable'])
            ->setTopButton(['ident' => 'type', 'title' => lang('类型管理'), 'href' => ['operation/system_message_type/index'], 'icon' => '', 'class' => 'btn btn-sm mr5 btn-info'])
//            ->setTopButton(['ident' => 'type', 'title' => lang('动作管理'), 'href' => ['operation/system_message_action/index'], 'icon' => '', 'class' => 'btn btn-sm mr5 btn-danger'])
//            ->setRightButton(['ident' => 'rest', 'title' => lang('推送'), 'href' => ['send', ['id' => '__id__', 'layer' => 1, 'reload' => 1]], 'icon' => 'fa fa-refresh pr5', 'class' => 'btn btn-xs mr5 btn-default layeredit', 'layer' => 1])
            ->setRightButtons($this->right_button, ['edit', 'disable'])
            ->setData($dataList)//设置数据
            ->fetch();//显示
    }

    public function getIsRead($status)
    {
        $data = ['0' => lang('未读'), '1' => lang('已读')];
        return $data[$status];
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = request()->post();
            $validate = new \app\operation\validate\SystemMessage();
            if($validate->scene('add')->check($data) !== true){
                $this->error($validate->getError());
            }
            $data['to_user_id'] = isset($data['to_user_id']) ? intval($data['to_user_id']) : 0;

            if (!$data['title']) {
                $this->error('请填写消息标题');
            }
            if (!$data['fit_title']) {
                $this->error('请填写消息副标题');
            }
            if (!$data['content']) {
                $this->error('请填写消息内容');
            }
            $msg = new SystemMessageModel();
            $data["custom"] = empty($data["custom"]) ? "" : json_encode($data["custom"], JSON_UNESCAPED_UNICODE);


            $ret = $msg->create($data);
            if (!$ret) {
                $this->error('创建消息失败');
            }
            $this->success('创建消息成功','index');
        }
        $types = \app\operation\model\SystemMessageType::where(1)->order("id asc")->column('id,name');
        $users = User::where('is_delete',0)->order('id asc')->column('id,user_nickname');
        $info['to_user_id'] = 0;
        $users[0] = '请选择';
        $fields = [
            ['type' => 'text', 'name' => 'title', 'title' => '消息标题'],
            ['type' => 'text', 'name' => 'fit_title', 'title' => '副标题'],
            ['type' => 'select', 'name' => 'to_user_id', 'title' => '接受会员',  'extra' => $users],
            ['type' => 'select', 'name' => 'msg_type', 'title' => '消息类型', 'extra' => $types],
//            ['type' => 'image', 'name' => 'thumb', 'title' => '缩略图'],
            ['type' => 'wangeditor', 'name' => 'content', 'title' => '详细内容'],
        ];
        $this->assign('page_title', '新增消息');
        $this->assign('form_items', $this->setData($fields,$info));
        return $this->fetch('admin@public/add');
    }

    /**
     * 重新发送
     * @param $id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/8/29 15:04
     * @editor XX [ xxx@qq.com ]
     * @updated 2019.03.30
     */
    public function send($id)
    {
        $msg = new SystemMessageModel();
        $data = $msg->get($id);
        // $ret = $msg->sendMsg($data);

        if ($this->request->isPost()) {
            $to_user_id = input('post.to_user_id', '');
            $ret = $msg->sendMsg($data, $to_user_id);
            if (true === $ret) {
                $this->success(lang('发送成功'), 'index');
            }
            $this->error($msg->getError());
        }

        $extra = $data['custom'];
        if (is_array($json = json_decode($data['custom'], true))) {
            $extra = $json;
        }
        $custom = json_encode([
            'id' => $data['id'],
            'action' => $data['action'],
            'msg_type' => $data['msg_type'],
            'extra' => $extra
        ], JSON_UNESCAPED_UNICODE);

        $fields = [
            ['type' => 'textarea', 'name' => 'to_user_id', 'title' => lang('接收人会员').'ID', 'tips' => '单推填一个ID，多推填多个，全体推不填<br />透传内容预计：<br />' . $custom, 'value' => $data['to_user_id']],
        ];
        $this->assign('page_title', lang('推送信件'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/edit');
    }

    public function users()
    {
        $q = input('q');
        $map = [];
        if ($q) {
            $map[] = ['user_nickname', 'like', '%' . $q . '%'];
        }
        $list = \app\user\model\User::where($map)->field('id,user_nickname,head_img')->order('user_nickname asc')->limit(10)->select();
        foreach ($list as &$val) {
            $val['head_img'] = get_file_url($val['head_img']);
        }
        $this->result($list, 1, '', 'json');
    }


    public function document()
    {
        $types = \app\operation\model\SystemMessageType::where(1)->order('id desc')->select();
        $action = \app\operation\model\SystemMessageAction::where(1)->order('id desc')->select();
        $this->assign('types', $types);
        $this->assign('action', $action);
        return $this->fetch('document');
    }

    /**
     * 文章详情
     * @return mixed
     */
    public function messageInfo($id = null){
        $types = \app\operation\model\SystemMessageType::where(1)->order("id asc")->column('id,name');
        $users = User::where('is_delete',0)->order('id asc')->column('id,user_name');
        $info['to_user_id'] = 0;
        $users[0] = '请选择';
        $info = SystemMessageModel::get($id);
        $fields = [
            ['type' => 'text', 'name' => 'title', 'title' => '消息标题'],
            ['type' => 'select', 'name' => 'to_user_id', 'title' => '接受会员',  'extra' => $users],
            ['type' => 'select', 'name' => 'msg_type', 'title' => '消息类型', 'extra' => $types],
//            ['type' => 'image', 'name' => 'thumb', 'title' => '缩略图'],
            ['type' => 'wangeditor', 'name' => 'content', 'title' => '详细内容'],
        ];
        $this->assign('page_title', '消息详情');
        $this->assign('form_items', $this->setData($fields,$info));
        return $this->fetch();
    }
}
