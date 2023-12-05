<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\admin;

use app\admin\admin\Base;
use app\operation\model\SystemMessage as SystemMessageModel;
use app\user\model\Certified as CertifiedModel;
use think\Db;
use service\Format;

/**
 * 会员认证控制器
 * Class Certified
 * @package app\member\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/3 11:18
 */
class Certified extends Base
{

    /**
     * 实名认证
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function realname()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 实名认证
        $map = $this->getMap();
        $map[] = ['user_certified.auth_type', '=', 1];
        if (isset($map['name'])) {
            $map[] = ['user_certified.name', '=', $map['name']];
            unset($map['name']);
        }
        if (isset($map['mobile'])) {
            $map[] = ['user.mobile', '=', $map['mobile']];
            unset($map['mobile']);
        }
        if (isset($map['status'])) {
            if ($map['status'] != -1) {
                $map[] = ['user_certified.status', '=', $map['status']];
            }
            unset($map['status']);
        }
        if (isset($map['create_time'])) {
            $create_time = explode(' - ', $map['create_time']);
            $start_time = strtotime($create_time[0].' 00:00:00');
            $end_time = strtotime($create_time[1].' 23:59:59');
            $map[] = ['user_certified.create_time', '>=', $start_time];
            $map[] = ['user_certified.create_time', '<=', $end_time];
            unset($map['create_time']);
        }
        // 排序
        $order = $this->getOrder('user_certified.status asc, user_certified.id desc');

        // 数据列表
        $data_list = CertifiedModel::getList($map, $order);

        $fields = [
            ['id', 'ID'],
            ['name', lang('姓名')],
            ['mobile', lang('手机号')],
            ['idcard_front', lang('身份证正面'), 'picture'],
            ['idcard_reverse', lang('身份证反面'), 'picture'],
            ['idcard_no', lang('身份证号码')],
            ['user_id', lang('申请人'), 'callback', 'get_nickname'],
            ['create_time', lang('申请时间')],
            ['reason', lang('失败或拒绝的原因'), 'text'],
            ['status', lang('认证状态'), 'status', '', [lang('待审核'), lang('已通过'), lang('已拒绝')]],
            ['right_button', lang('操作'), 'btn']
        ];
        $search_fields = [
            ['name', lang('姓名'), 'text'],
            ['mobile', lang('手机号'), 'text'],
            ['status', lang('认证状态'), 'select', '', ['-1' => lang('全部'), '0' => lang('待审核'), '1' => lang('已通过'), '2' => lang('已拒绝')]],
            ['create_time', lang('申请时间'), 'daterange'],
        ];
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setTopSearch($search_fields)
            ->setRightButton(['ident' => 'enable', 'title' => lang('审核'), 'href' => ['enable', ['id' => '__id__', 'type' => 1]], 'icon' => 'fa fa-check pr5', 'class' => 'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->setRightButton(['ident' => 'disable', 'title' => lang('拒绝'), 'href' => ['disable', ['id' => '__id__']], 'icon' => 'fa fa-close pr5', 'data-toggle' => 'prompt', 'class' => 'btn btn-xs mr5 btn-default '])
            ->replaceRightButton(['status' => 1], '', ['enable', 'disable'])
            ->replaceRightButton(['status' => 2], '', ['enable', 'disable'])
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 审核会员
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 14:26
     * @param int $id 会员id
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function enable($id = 0, $type = 0)
    {
        if ($id == 0 || $type = 0) {
            $this->error(lang('参数错误'));
        }

        // 启动事务
        Db::startTrans();
        try {
            $info = CertifiedModel::where(['id' => $id, 'status' => 0])->find();
            $res = CertifiedModel::where(['id' => $id, 'status' => 0])->update(['status' => 1]);
            $res1 = \app\user\model\User::where(['id' => $info['user_id']])->update(['user_name' => $info['name'], 'update_time' => time()]);
            if (!$res || !$res1) {
                $res3 = 0;
                exception(lang('审核失败'));
            }

            $res3 = 1;
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($res3) {
            $data['to_user_id'] = $info['user_id'];
            $data['title'] = config('web_site_title') . lang('友情提示');
            $data['content'] = lang('您的实名认证信息已通过');
            $data['type'] = 1;
            $data['template_type'] = 1;
            $msg = new SystemMessageModel();
            $ret = $msg->create($data);
            if (!$ret) {
                $this->error(lang('创建消息失败'));
            }

            $ret = $msg->sendMsg($data);
        }

        $this->success(lang('审核通过'));
    }

    public function disable($id = 0)
    {
        if ($id == 0) {
            $this->error(lang('参数错误'));
        }
        $msg = input('param.msg');

        $result = CertifiedModel::where(['id' => $id, 'status' => 0])->update(['status' => 2, 'reason' => $msg]);

        if ($result) {
            $this->success(lang('拒绝成功'));
        }
    }
}
