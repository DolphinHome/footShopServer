<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\admin;

use app\admin\admin\Base;
use app\user\model\WithAccount;
use app\user\model\Withdraw as WithdrawModel;
use service\Format;
use app\common\builder\ZBuilder;
use service\ApiReturn;

/**
 * 提现管理
 * Class Withdraw
 * @package app\user\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/3 18:33
 */
class Withdraw extends Base
{

    /**
     * 提现列表
     * @return mixed
     * @since 2019/4/3 18:33
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap(['true_name', 'mobile']);
        $order = $this->getOrder('id DESC');
        $fields = [
            ['id', 'ID'],
            ['user_id', lang('会员ID')],
            ['true_name', lang('收款人姓名')],
            ['account_type', lang('账户类型'), 'callback', function ($item) {
                return ['--', lang('微信'), lang('支付宝'), lang('银行卡')][$item];
            }],
            ['account_id', lang('账户')],
            ['qrcode', lang('收款二维码'), 'picture'],
            ['cash_fee', lang('提现金额'), 'text'],
            ['handling_fee', lang('手续费'), 'text'],
            ['pay_fee', lang('转账金额'), 'callback', function ($item, $data) {
                if ($data['cash_status'] == 1) {
                    $item = '-' . $item;
                }
                return $item;
            }, '__data__'],
            ['create_time', lang('创建时间')],
            ['check_status', lang('审核状态'), 'callback', function ($item) {
                return [lang('未审核'), lang('已审核'), lang('已拒绝')][$item];
            }],
            ['check_time', lang('审核时间'), 'datetime'],
            ['check_reason', lang('拒绝原因'), 'text.tip'],
            ['cash_status', lang('转账状态'), 'callback', function ($item) {
                return [lang('未转账'), lang('已转账'), lang('转账异常')][$item];
            }],
            ['cash_time', lang('转账时间'), 'datetime'],
            ['name', lang('转账账户名'), 'text'],
            ['account', lang('转账账户'), 'text'],
            ['transfer_img', lang('转账截图'), 'callback', function ($value) {
                if ($value) {
                    return '<div class="js-gallery"><a data-magnify="gallery" id="iview" href="' . get_file_url($value) . '"> <img class="image" style="height:30px;" data-original="" src="' . get_file_url($value) . '"> </a></div>';
                }
            }],
            ['is_auto', lang('转账方式'), 'callback', function ($value, $data) {
                if ($data['cash_status'] == 1) {
                    return $this->transfer($value);
                }
            }, '__data__'],
            ['remark', lang('转账备注'), 'text'],
//            ['order_id', lang('第三方订单号')],
            ['transaction_id', lang('操作'), 'callback', function ($value, $data) {
                //审核状态 0 待审核 1审核通过 2拒绝
                $check = "<a ident='check' title=lang('审核') href='" . url('check', ['id' => $data['id'], 'layer' => 1]) . "' icon='fa fa-check pr5' class='btn btn-xs mr5 btn-default layeredit'><i class='fa fa-check pr5'></i>审核</a> ";
                if (module_config('user.auto_withdraw') == 0) {
                    //手动付款
                    $pay = "<a ident='pay' title=lang('付款') href='" . url('pay', ['id' => $data['id'], 'layer' => 1]) . "' icon='fa fa-check pr5' class='btn btn-xs mr5 btn-default layeredit'><i class='fa fa-check pr5'></i>付款</a> ";
                } else {
                    //系统自动付款
                    $pay = "<a ident='pay' title=lang('系统自动付款') href='javascript:void(0);'  icon='fa fa-check pr5' class='btn btn-xs mr5 btn-default '><i class='fa  pr5'></i>系统自动付款</a> ";
                }
                $return = '';
                if ($data['check_status'] == 0) {
                    $return = $check;
                }
                if ($data['check_status'] == 1 && $data['cash_status'] == 0) {
                    $return = $pay;
                }

                return $return;
            }, '__data__'],
        ];

        //搜索
        $search_fields = [
            ['mobile', lang('手机号'), 'text'],
            ['true_name', lang('收款人姓名'), 'text'],
            ['create_time', lang('申请时间'), 'daterange'],
            ['check_status', lang('审核状态'), 'select', '', ['-1' => lang('全部'), '0' => lang('待审核'), '1' => lang('通过'), '2' => lang('拒绝')]],
        ];

        if (isset($map['check_status'])) {
            if ($map['check_status'] >= 0) {
                $map[] = ['user_withdraw.check_status', "=", $map['check_status']];
            }
            unset($map['check_status']);
        }
        if (isset($map['mobile']) && $map['mobile']) {
            $map[] = ['user.mobile', "=", $map['mobile']];
            unset($map['mobile']);
        }
        if (isset($map['true_name']) && $map['true_name']) {
            $map[] = ['user_withdraw.true_name', "like", '%' . $map['true_name'] . '%'];
            unset($map['true_name']);
        }
        if (isset($map['create_time']) && $map['create_time']) {
            $create_time = explode(' - ', $map['create_time']);
            $start_time = strtotime($create_time[0].' 00:00:00');
            $end_time = strtotime($create_time[1].' 23:59:59');
            $map[] = ['user_withdraw.create_time', ">=", $start_time];
            $map[] = ['user_withdraw.create_time', "<=", $end_time];
            unset($map['create_time']);
        }
        //是否显示导出excel按钮 1显示
        $this->assign('excel_show', 1);
        //导出excel
        if (isset($map['is_import'])) {
            unset($map['is_import']);
            $list = WithdrawModel::getList($map, $order, true);
            $excelData = $_excelData = [];
            foreach ($list as $v) {
                $excelData[] = [
                    'true_name' => $v['true_name'],
                    'account_type' => $this->account_type($v['account_type']),
                    'account_id' => $v['account_id'],
                    'qrcode' => $v['qrcode'],
                    'cash_fee' => $v['cash_fee'],
                    'handling_fee' => $v['handling_fee'],
                    'pay_fee' => $v['pay_fee'],
                    'create_time' => $v['create_time'],
                    'check_status' => $this->check_status($v['check_status']),
                    'check_time' => $v['check_time'],
                    'check_reason' => $v['check_reason'],
                    'cash_status' => $this->cash_status($v['cash_status']),
                    'cash_time' => $v['cash_time'],
//                    'order_id' => $v['order_id']
                ];
            }
            $_excelData[0]['list'] = $excelData;
            $xlsName = lang('提现申请').'-' . date("Y-m-d H:i:s", time());
            $xlsCell = [
                ['true_name', lang('收款人姓名')],
                ['account_type', lang('账户类型')],
                ['account_id', lang('账户')],
                ['qrcode', lang('收款二维码')],
                ['cash_fee', lang('提现金额')],
                ['handling_fee', lang('手续费')],
                ['pay_fee', lang('转账金额')],
                ['create_time', lang('创建时间')],
                ['check_status', lang('审核状态')],
                ['check_time', lang('审核时间')],
                ['check_reason', lang('拒绝原因')],
                ['cash_status', lang('转账状态')],
                ['cash_time', lang('转账时间')],
                ['account', lang('转账账户')],
                ['cash_time', lang('转账时间')],
//              ['order_id', lang('第三方订单号')],
            ];
            $excelData = array_values($_excelData);
            $this->exportExcel($xlsName, $xlsCell, $excelData);
        }
        $data_list = WithdrawModel::getList($map, $order);
        //待审核提现金额
        $withdrawal_amount = WithdrawModel::where(['check_status' => 0])->sum('cash_fee');
        //已付款金额
        $pay_amount = WithdrawModel::where(['check_status' => 1])->sum('cash_fee');

        $top_statistics = [
            ['value' => $withdrawal_amount, 'title' => lang('待审核提现金额'), 'icon' => 'fa fa-check-circle pr5', 'class' => 'btn btn-sm mr5 btn-default  ajax-post confirm'],
            ['value' => $pay_amount, 'title' => lang('已付款金额'), 'icon' => 'fa fa-ban pr5', 'class' => 'btn btn-sm mr5 btn-default  ajax-post confirm'],
        ];
        if (count($data_list) <= 0) {
            $bottom_button_select = [];
        } else {
            $bottom_button_select = [['ident' => 'check', 'title' => lang('批量审核通过'), 'href' => ['setstatus', ['type' => 'enable']], 'icon' => 'fa fa-check-circle pr5', 'class' => 'btn btn-sm mr5 btn-default  ajax-post confirm', 'extra' => 'target-form="ids"']];
        }
        return Format::ins()
            ->addColumns($fields)
            ->bottom_button_select($bottom_button_select)
            ->top_statistics($top_statistics)
            ->setTopSearch($search_fields)
            ->setData($data_list)
            ->fetch();
    }

    public function transfer($type)
    {
        $data = ['0' => lang('手动打款'), '1' => lang('系统自动打款')];
        return $data[$type];
    }

    public function account_type($type)
    {
        $data = ['1' => lang('微信'), '2' => lang('支付宝'), '3' => lang('银行卡')];
        return $data[$type];
    }

    public function check_status($status)
    {
        $data = ['0' => lang('待审核'), '1' => lang('通过'), '2' => lang('拒绝')];
        return $data[$status];
    }

    public function cash_status($staus)
    {
        $data = ['0' => lang('待转账'), '1' => lang('已转账'), '2' => lang('转账异常')];
        return $data[$staus];
    }

    /**
     * 审核
     * @param type $aid
     * @return type
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function check($id = 0)
    {
        if ($id === 0) {
            $this->error(lang('缺少参数'));
        }
        $info = WithdrawModel::get($id);
        // 保存文档数据
        if ($this->request->isAjax()) {
            if ($info['check_status'] > 0) {
                $this->error(lang('您已经审核过了，不能再次审核'));
            }
            // 验证
            $check_status = input('post.check_status/d', 0);
            $check_reason = input('post.check_reason/', '');
            if (!in_array($check_status, [1, 2])) {
                $this->error(lang('请选择正确的审核状态'));
            }
            if ($check_status == 2 && !$check_reason) {
                $this->error(lang('请填写拒绝原因'));
            }
            //回退操作
            if ($check_status == 2) {
                $ret = WithdrawModel::checkBack($info['user_id'], $info['cash_fee'], $id, $check_reason);
                if ($ret !== true) {
                    $this->error(lang('操作失败') . $ret);
                }
                $this->success(lang('操作成功'), 'index');
            }
            //不回退直接改状态
            $data = ['check_status' => 1, 'check_time' => time()];
            $res = WithdrawModel::where("id", $id)->update($data);
            if ($res) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('user_withdraw_check', 'user', $id, UID, $details);
                $this->success(lang('操作成功'), 'index');
            } else {
                $this->error(lang('操作失败'));
            }
        }

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'radio', 'name' => 'check_status', 'title' => lang('审核状态'), 'extra' => [1 => lang('审核通过'), 2 => lang('审核拒绝')], 'value' => 1],
            ['type' => 'textarea', 'name' => 'check_reason', 'title' => lang('备注'), 'tips' => lang('如果拒绝，请填写原因')],
        ];

        $this->assign('page_title', lang('审核提现'));
        $this->assign('form_items', $fields);
        return $this->fetch('../../admin/view/public/add');
    }

    public function setStatus()
    {
        $data = request()->param();
        $ids = isset($data['id']) ? $data['id'] : (isset($data['ids']) ? $data['ids'] : 0);
        $type = isset($data['action']) ? $data['action'] : (isset($data['type']) ? $data['type'] : '');
        $ids = (array)$ids;

        empty($ids) && $this->error(lang('缺少主键'));

        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = WithAccount::where('id', 'IN', $ids)->setField('status', 0);
                action_log('withaccount_disable', 'user', 0, UID, lang('批量禁用提现账户').'ID:' . $ids);
                break;
            case 'enable': // 启用
                $result = WithAccount::where('id', 'IN', $ids)->setField('status', 1);
                action_log('withaccount_enable', 'user', 0, UID, lang('批量启用提现账户').'ID:' . $ids);
                break;
            case 'delete': // 删除
                $result = WithAccount::where('id', 'IN', $ids)->delete();
                action_log('withaccount_delete', 'user', 0, UID, lang('批量删除提现账户').'ID:' . $ids);
                break;
            case 'check': // 批量审核通过
                $result = WithdrawModel::where('id', 'IN', $ids)->update(['check_status' => 1, 'check_time' => time()]);
                action_log('user_withdraw', 'withdraw', 0, UID, lang('批量审核提现').'ID:' . $ids);
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // \Cache::clear();

            // 记录行为
            action_log('admin_user_' . $type, 'user', $ids, UID, 'ID：' . implode('、', $ids));
            return ApiReturn::r(1, [], lang('操作成功'));
        } else {
            return ApiReturn::r(0, [], lang('操作失败'));
        }
    }

    /**
     * 出纳
     * @param type $aid
     * @return type
     * @author 晓风<215628355@qq.com>
     */
    public function pay($id = 0)
    {
        $data = request()->param();
//        $is_auto = module_config('user.auto_withdraw');
        $is_auto = $data['is_auto'] ?? 0;


        if ($id === 0) {
            $this->error(lang('缺少参数'));
        }

        $oldinfo = $info = WithdrawModel::get($id);
        // 保存文档数据
        if ($_POST) {
            if (!isset($data['cash_status'])) {
                $this->error(lang('请选择转账状态'));
            }
            if ($is_auto == 0) {
                if (isset($data['account']) && $data['account'] == -1) {
                    $this->error(lang('请选择转账账户'));
                }
            }
            if ($info['check_status'] != 1) {
                $this->error(lang('请先审核通过再进行此操作'));
            }

            if ($info['cash_status'] == 1) {
                $this->error(lang('您已经转过账了，不能再次操作'));
            }
            // 验证
//            $cash_status = input('post.cash_status/d', 0);
//            $is_auto = input('post.is_auto/d', 0);
//            $cash_reason = input('post.cash_reason/', '');
//            $password = input("post.password");
//            $checkPass = 'zzebz_ChuNa!@';
//            if ($password !== $checkPass) {
//                $this->error(lang('操作密码不正确'));
//            }

            $cash_status = $data['cash_status'];
            $cash_reason = $data['cash_reason'];
            if (!in_array($cash_status, [1, 2])) {
                $this->error(lang('请选择正确的转账状态'));
            }
//            if ($cash_status == 2 && !$cash_reason) {
//                $this->error(lang('请填写拒绝原因'));
//            }
            //回退操作
            if ($cash_status == 2) {
                $ret = WithdrawModel::cashBack($info['user_id'], $info['cash_fee'], $id, $cash_reason);
                if ($ret !== true) {
                    $this->error(lang('操作失败') . $ret);
                }
                $this->success(lang('操作成功'));
            }
            //成功操作
            if (!$data['is_auto']) {
                if (!$data['transfer_img']) {
                    $this->error(lang('请上传转账截图'));
                }
            }
            $info['cash_reason'] = $data['cash_reason'];
            $info['is_auto'] = $data['is_auto'];
            $info['account'] = $data['account'];
            $info['transfer_img'] = $data['transfer_img'];

            $ret = WithdrawModel::cashSuccess($info, $is_auto);
            if ($ret === true) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $oldinfo);
                action_log('user_withdraw_pay', 'user', $id, UID, $details);
                $this->success(lang('操作成功'));
            } else {
                $this->error(lang('操作失败') . $ret);
            }
        }
//        $fields = [
//            ['hidden', 'aid'],
//            ['radio', 'cash_status', lang('转账状态'), lang('必填'), [1 => lang('已转账'), 2 => lang('转账失败')]],
//            ['textarea', 'cash_reason', lang('失败原因'), lang('必填')],
//            ['radio', 'is_auto', lang('自动转账'), lang('目前仅支付宝支持自动转账'), [0 => lang('人工'), 1 => lang('自动')], 0],
//            ['password', 'password', lang('出纳操作密码'), lang('必填')],
//        ];
//        $this->assign('page_title', lang('新增用户'));
//        $this->assign('info', $info);
//        $this->assign('form_items', $fields);
//        return $this->fetch('admin@public/add');

        $account = WithAccount::where(['status' => 1])->column('id,account,name');
        $account_arr[-1] = lang('请选择');
        if ($account) {
            foreach ($account as $v) {
                $account_arr[$v['id']] = $v['name'] . '(' . $v['account'] . ')';
            }
        }

        ksort($account_arr);

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'radio', 'name' => 'cash_status', 'title' => lang('转账状态'), 'tips' => '', 'attr' => '', 'extra' => [1 => lang('已转账'), 2 => lang('转账失败')], 'value' => 2],
            ['type' => 'radio', 'name' => 'is_auto', 'title' => lang('自动转账'), 'tips' => lang('目前仅支付宝支持自动转账'), 'attr' => '', 'extra' => [0 => lang('人工'), 1 => lang('自动')], 'value' => $is_auto],
            ['type' => 'select', 'name' => 'account', 'title' => lang('转账账户'), 'tips' => '', 'extra' => $account_arr, 'value' => '-1'],
            ['type' => 'image', 'name' => 'transfer_img', 'title' => lang('转账截图')],
            ['type' => 'textarea', 'name' => 'cash_reason', 'title' => lang('备注'), 'tips' => '', 'attr' => ''],

        ];
        $this->assign('page_title', lang('付款'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /*
     * 提现账户管理
     *
     */
    public function account()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        $order = $this->getOrder('id DESC');
        $data_list = WithAccount::where($map)->order($order)->paginate();
        $fields = [
            ['id', 'ID'],
            ['name', lang('账户名')],
            ['account', lang('账户')],
            ['status', lang('账户状态'), 'status', '', [lang('禁用'), lang('启用')]],
            ['sort', lang('排序'), 'text.edit'],
            ['remark', lang('备注')],
            ['create_time', lang('添加时间')],
            ['right_button', lang('操作'), 'btn']
        ];
        return Format::ins()
            ->hideCheckbox()
            ->addColumns($fields)
            ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
            ->setData($data_list)
            ->fetch();
    }

    /*
     * 提现账户添加
     *
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['create_time'] = time();
            $data['sort'] = 0;
            if (empty($data['account'])) {
                $this->error(lang('请输入账户'));
            }
            try {
                $res = WithAccount::create($data);
                if (!$res) {
                    exception(lang('新增失败'));
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            //记录行为
            unset($data['__token__']);
            $details = json_encode($data, JSON_UNESCAPED_UNICODE);
            action_log('user_withdraw_add', 'user_withdraw', $res->id, UID, $details);
            $this->success(lang('新增成功'), cookie('__forward__'));
        }

        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('账户名')],
            ['type' => 'text', 'name' => 'account', 'title' => lang('账户')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('账户状态'), 'tips' => '', 'attr' => '', 'extra' => [1 => lang('启用'), 0 => lang('禁用')], 'value' => '1'],
            ['type' => 'textarea', 'name' => 'remark', 'title' => lang('备注'), 'placeholder' => lang('请输入备注'), 'tips' => '', 'attr' => '', 'value' => ''],

        ];
        $this->assign('page_title', lang('新增用户'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 会员主表id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = WithAccount::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (empty($data['account'])) {
                $this->error(lang('请输入账户'));
            }
            $res = WithAccount::where(['id' => $id])->update($data);
            if ($res) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('user_withdraw_edit', 'user_withdraw', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('账户名')],
            ['type' => 'text', 'name' => 'account', 'title' => lang('账户')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('账户状态'), 'tips' => '', 'attr' => '', 'extra' => [1 => lang('启用'), 0 => lang('禁用')], 'value' => '1'],
            ['type' => 'textarea', 'name' => 'remark', 'title' => lang('备注'), 'placeholder' => lang('请输入备注'), 'tips' => '', 'attr' => '', 'value' => ''],

        ];
        $this->assign('page_title', lang('编辑提现账户'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
