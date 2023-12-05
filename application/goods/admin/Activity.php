<?php
/*
 * 活动 
 * @Version: 1.0
 * @Author: jxy [41578218@qq.com]
 * @Date: 2021-04-28 10:45:28
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-28 14:38:47
 */

namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\Category;
use app\goods\model\Activity as ActivityModel;
use app\goods\model\ActivityDetails as ActivityDetailsModel;

class Activity extends Base
{
    public $top_button = [
        ['ident'=> 'enable', 'title'=>'批量启用','href'=>['setstatus',['type'=>'enable']],'icon'=>'fa fa-check-circle pr5','class'=>'btn btn-sm mr5 btn-default btn-flat ajax-post confirm','extra'=>'target-form="ids"'],
        ['ident'=> 'disable', 'title'=>'批量禁用','href'=>['setstatus',['type'=>'disable']],'icon'=>'fa fa-ban pr5','class'=>'btn btn-sm mr5 btn-default btn-flat ajax-post confirm','extra'=>'target-form="ids"'],
        ['ident'=> 'delete', 'title'=>'批量删除','href'=>'delete','icon'=>'fa fa-times pr5','class'=>'btn btn-sm mr5 btn-default btn-flat ajax-post confirm','extra'=>'target-form="ids"'],
    ];
    /**
     * 新增
     * @author jxy [41578218@qq.com]
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Activity');
            $times = explode(' - ', $data['edate']);
            $data['sdate'] = strtotime($times[0]);
            $data['edate'] = strtotime($times[1]);

            if ($data['type'] == 3) {
                $times = explode(' - ', $data['preselltime']);
                $data['presell_stime'] = strtotime($times[0]);
                $data['presell_etime'] = strtotime($times[1]);
                unset($data['preselltime']);
            }

            if (true !== $result) {
                $this->error($result);
            }

            if ($res = ActivityModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_activity_add', 'goods_activity', $res->id, UID, $details);
                $this->success(lang('新增成功'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $cate = Category::getMenuTree(0);
        $activity_type = input('param.type', 1);
        switch ($activity_type) {
            case 1:
                $page_title=lang('添加秒杀活动');
                break;
            case 2:
                $page_title=lang('添加拼团活动');
                break;
            case 3:
                $page_title=lang('添加预售活动');
                break;
            case 4:
                $page_title=lang('添加折扣活动');
                break;
            case 5:
                $page_title=lang('添加砍价活动');
                break;
            case 6:
                $page_title=lang('添加限购活动');
                break;
            case 7:
                $page_title=lang('添加新人0元购');
                break;
            case 10:
                $page_title=lang('添加砍价活动');
                break;
        }
        $fields = [
            ['type' => 'hidden', 'name' => 'type', 'value' => 3],
            ['type' => 'text', 'name' => 'name', 'title' => lang('分类名称'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'hidden', 'name' => 'type', 'title' => lang('活动类型'), 'tips' => '', 'attr' => '','extra'=>[1=>lang('秒杀活动'),2=>lang('拼团活动'),3=>lang('预售活动'),4=>lang('折扣活动')], 'value' => $activity_type],
            ['type' => 'image', 'name' => 'icon', 'title' => lang('分类图标'), 'tips' => '', 'attr' => '', 'value' => '0'],
            /*['type' => 'radio', 'name' => 'show_position', 'title' => lang('客户端展示位置'), 'tips' => lang('请选择客户端的展示位置'), 'extra' => ['index'=>lang('手机首页'),'cate'=>lang('分类页')], 'value' => 'index'],*/
            ['type' => 'select', 'name' => 'cid', 'title' => lang('关联商品分类'), 'tips' => '关联商品分类后则跳转到相应分类, 不绑定为顶级分类即可', 'extra' => $cate, 'value' => '0'],
            ['type' => 'image', 'name' => 'background', 'title' => lang('背景'), 'tips' => '', 'attr' => '', 'value' => '0'],
            ['type' => 'text', 'name' => 'slogan', 'title' => lang('标语'), 'tips' => '', 'attr' => '', 'value' => ''],
            //['type' => 'date', 'name' => 'edate', 'title' => lang('活动结束日期'), 'tips' => '', 'attr' => '', 'value' => ''],
        ];
        if ($activity_type == 3) {
            $fields[] = ['type' => 'daterange', 'name' => 'edate', 'title' => lang('付定金日期'), 'tips' => '只能在此日期范围内付定金，超过结束日期则开始付尾款', 'attr' => 'style="cursor: pointer;"', 'value' => date('Y-m-d').' 00:00:00 - '.date('Y-m-d', strtotime('+7 day')).' 23:59:59'];
            $fields[] = ['type' => 'daterange', 'name' => 'preselltime', 'title' => lang('付尾款日期'), 'tips' => '只能在此日期范围内付尾款，超过活动结束', 'attr' => 'style="cursor: pointer;"', 'value' => date('Y-m-d', strtotime('+8 day')).' 00:00:00 - '.date('Y-m-d', strtotime('+8 day')).' 23:59:59'];
        } else {
            $fields[] = ['type' => 'daterange', 'name' => 'edate', 'title' => lang('活动日期'), 'tips' => '', 'attr' => '', 'value' => ''];
        }

        $this->assign('page_title', $page_title);
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 活动分类id
     * @author jxy [41578218@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $cate = Category::getMenuTree(0);
        $activity_type = input('param.type', 1);
        $info = ActivityModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $times = explode(' - ', $data['edate']);
            $data['sdate'] = strtotime($times[0]);
            //截止到当天23:59:59
            $data['edate'] = strtotime($times[1]) + 86399;

            if (isset($data['preselltime'])) {
                $times = explode(' - ', $data['preselltime']);
                $data['presell_stime'] = strtotime($times[0]);
                $data['presell_etime'] = strtotime($times[1]);
                unset($data['preselltime']);
            }

            // 验证
            $result = $this->validate($data, 'Activity');
            if (true !== $result) {
                $this->error($result);
            }

            if (ActivityModel::update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('goods_activity_edit', 'goods_activity', $id, UID, $details);
                $this->success(lang('编辑成功'), url('goods/activity_details/index', ['activity_id'=>$data['id']]));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
        $fields = [
            ['type' => 'hidden', 'name' => 'id','value'=>$info['id']],
            ['type' => 'text', 'name' => 'name', 'title' => lang('活动名称'), 'tips' => '', 'attr' => '', 'value' =>$info['name']],
            ['type' => 'image', 'name' => 'icon', 'title' => lang('活动图标'), 'tips' => '', 'attr' => '', 'value' => $info['icon']],
            /*['type' => 'radio', 'name' => 'show_position', 'title' => lang('客户端展示位置'), 'tips' => lang('请选择客户端的展示位置'), 'extra' => ['index'=>lang('手机首页'),'cate'=>lang('分类页')],'value'=>$info['show_position']],*/
            ['type' => 'select', 'name' => 'cid', 'title' => lang('关联商品分类'), 'tips' => '关联商品分类后则跳转到相应分类, 不绑定为顶级分类即可', 'extra' => $cate,'value'=>$info['cid']],
            //['type' => 'daterange', 'name' => 'sdate', 'title' => lang('活动日期'), 'tips' => '', 'attr' => '', 'value' => date('Y-m-d H:i:s',$info['sdate']) .' - '.date('Y-m-d H:i:s',$info['edate'])],
            ['type' => 'text', 'name' => 'slogan', 'title' => lang('标语'), 'tips' => '', 'attr' => '', 'value' => $info['slogan']],
            ['type' => 'image', 'name' => 'background', 'title' => lang('背景'), 'tips' => '', 'attr' => '', 'value' => $info['background']],
        ];
        if ($activity_type == 3) {
            $fields[] = ['type' => 'daterange', 'name' => 'edate', 'title' => lang('付定金日期'), 'tips' => '', 'attr' => 'style="cursor: pointer;"', 'value' =>date('Y-m-d H:i:s', $info['sdate']).' - '.date('Y-m-d H:i:s', $info['edate'])];
            $fields[] = ['type' => 'daterange', 'name' => 'preselltime', 'title' => lang('付尾款日期'), 'tips' => '', 'attr' => 'style="cursor: pointer;"', 'value' => date('Y-m-d H:i:s', $info['presell_stime']).' - '.date('Y-m-d H:i:s', $info['presell_etime'])];
        } else {
            $fields[] = ['type' => 'daterange', 'name' => 'edate', 'title' => lang('活动日期'), 'tips' => '', 'attr' => '','value'=>date('Y-m-d H:i:s', $info['sdate']).' - '.date('Y-m-d H:i:s', $info['edate'])];
        }

        $this->assign('page_title', lang('编辑活动分类'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/edit');
    }

    /**
     * 设置状态
     * @param string $type 类型：disable/enable
     * @param array $record 行为日志内容
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author jxy [ 41578218@qq.com ]
     */
    public function setStatus($type = '')
    {
        $ids   = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids   = (array)$ids;
        $field = input('param.field', 'status');

        empty($ids) && $this->error(lang('缺少主键'));


        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = ActivityModel::where('id', 'IN', $ids)->setField($field, 0);
                ActivityDetailsModel::where('activity_id', 'IN', $ids)->setField($field, 0);
                break;
            case 'enable': // 启用
                $result = ActivityModel::where('id', 'IN', $ids)->setField($field, 1);
                ActivityDetailsModel::where('activity_id', 'IN', $ids)->setField($field, 1);
                break;
            case 'delete': // 删除
                $result = ActivityModel::where('id', 'IN', $ids)->delete();
                ActivityDetailsModel::where('activity_id', 'IN', $ids)->delete();
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // \Cache::clear();

            // 记录行为
            action_log('goods_activity_'.$type, 'goods', $ids, UID, 'ID：'.implode('、', $ids));
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }
}
