<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\user\model\RechargeRule;
use app\user\model\User;
use app\user\model\MoneyLog;
use app\user\model\WithAccount;
use app\user\model\Withdraw as WithdrawModel;
use app\user\model\WithdrawAccount;
use think\Db;
use service\ApiReturn;
use think\helper\Hash;

/**
 * 余额以及积分接口
 * Class Money
 * @package app\api\controller
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/23 19:33
 */
class Money extends Base
{
    /**
     * 我的余额
     * @return void
     * @since 2019/4/23 18:21
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_my_money($data = [], $user = [])
    {
        $money = \think\Db::name('user')->where('id', $user['id'])->find();
        $money['min_withdraw_money'] = module_config('user.min_withdraw_money');
        $money['withdraw_handling_type'] = module_config('user.withdraw_handling_type');
        $money['withdraw_handling_fee'] = module_config('user.withdraw_handling_fee');
        $money['total_revenue_money'] = bcadd($money['user_money'], $money['freeze_money'], 2);   //用户总金额=用户余额+冻结金额
        $money['freeze_money_rule'] = module_config('user.freeze_money_rule');  //冻结金额规则显示
        //分享赚
        $money['share_money'] = Db::name("user_money_log")->where([
            'user_id' => $user['id'],
            'change_type' => 8
        ])->sum("change_money");
        //自购返
        $money['discounts_money'] = Db::name("user_money_log")->where([
            'user_id' => $user['id'],
            'change_type' => 9
        ])->sum("change_money");
        //可提现余额
        $money['money'] = User::money($user['id']);


        return ApiReturn::r(1, $this->filter($money, $this->fname), lang('请求成功'));
    }

    /**
     * 获取余额交易明细
     * @return void
     * @throws DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/23 18:30
     */
    public function get_money_detail($data = [], $user = [])
    {
        $change_type = $data['change_type'] ? intval($data['change_type']) : 0;

        if ($data['date']) {
            $start_time = strtotime($data['date']);
            $end_time = strtotime('+1 month', $start_time);
            $whereTime = "create_time BETWEEN $start_time AND $end_time";
        }

        if ($data['start_data'] && $data['end_data']) {
            $start_time = strtotime($data['start_data']);
            $end_time = strtotime($data['end_data'] . " 23:59:59");
            $whereTime = "create_time BETWEEN $start_time AND $end_time";
        }

        $where = [];
        if ($change_type == 3) {
            $where = [["change_money", ">", 0]];
        }
        if ($change_type == 4) {
            $where = [["change_money", "<", 0]];
        }
        if ($change_type) {
            switch ($change_type) {
                //充值记录
                case 1:
                    $change_type_where[] = ['change_type', 'in', '1,3'];
                    break;
                //消费明细
                case 2:
                    $change_type_where[] = ['change_type', 'in', '2,4,5,6,7,8,9,10'];
                    break;
                //收入记录
                case 3:
                    $change_type_where[] = ['change_type', 'in', '1,3,5,6,8,9,10'];
                    break;
                //支出记录
                case 4:
                    $change_type_where[] = ['change_type', 'in', '2,4,7'];
                    break;
                // 分享赚记录
                case 8:
                    $change_type_where[] = ['change_type', 'in', '8'];
                    break;
                //自购返记录
                case 9:
                    $change_type_where[] = ['change_type', 'in', '9'];
                    break;
                default:
                    $change_type_where = [];
                    break;
            }
        }
        $data = \think\Db::name('user_money_log')->where($where)->where('user_id', $user['id'])->where($whereTime)->where($change_type_where)->order('aid', 'desc')
            ->paginate()
            ->each(function ($item) {
                $item['check_reason'] = '';
                $user_withdraw = Db::name('user_withdraw')->where('order_no', $item['order_no'])->find();
                if ($user_withdraw) {
                    $item['check_status'] = $user_withdraw['check_status'];
                    $item['check_reason'] = $user_withdraw['check_reason'];
                }
                if (floatval($item['change_money']) > 0) {
                    $item['t'] = 1;
                } else {
                    $item['t'] = 2;
                }

                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                if ($item['change_type'] == 3) {
                    if ($item['change_money'] > 0) {
                        $item['remark'] = lang('系统充值');
                    } else {
                        $item['remark'] = lang('系统扣除');
                    }
                }
                if ($item['cash_status'] == 1) {
                    $item['check_status'] = 3;
                } elseif ($item['cash_status'] == 2) {
                    $item['check_status'] = 4;
                }
                return $item;
            });

        if ($data) {
            return ApiReturn::r(1, $data, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('请求成功'));
    }

    /**
     * 我的资产
     * @author chenchen
     * @since 2021年4月14日16:34:45
     */
    public function my_money($data = [], $user = [])
    {
        //资产类型 8分享赚 9自购返
        $change_type = $data['change_type'] ?? 8;
        //累计
        $total = Db::name("user_money_log")->where([
            'user_id' => $user['id'],
            'change_type' => $change_type
        ])->sum("change_money");
        //本日
        //求当天0点 24点 时间戳
        $dateStr = date('Y-m-d', time());
        //获取当天0点的时间戳
        $timestamp0 = strtotime($dateStr);
        //获取当天24点的时间戳
        $timestamp24 = strtotime($dateStr) + 24 * 60 * 60;
        $today = Db::name("user_money_log")->where([
            ['user_id', '=', $user['id']],
            ['change_type', '=', $change_type],
            ['create_time', '>=', $timestamp0],
            ['create_time', '<=', $timestamp24],
        ])->sum("change_money");
        //本周
        $timeStr = date('w') == 1 ? 'Monday' : 'last Monday';
        $week_start = strtotime(date("Y-m-d", strtotime("$timeStr")) . ' 00:00:00');
        $week_end = strtotime(date("Y-m-d", strtotime("Sunday")) . ' 23:59:59');
        $week = Db::name("user_money_log")->where([
            ['user_id', '=', $user['id']],
            ['change_type', '=', $change_type],
            ['create_time', '>=', $week_start],
            ['create_time', '<=', $week_end],
        ])->sum("change_money");
        $list_data = Db::name("user_money_log")->where([
            ['user_id', '=', $user['id']],
            ['change_type', '=', $change_type],
            ['create_time', '>=', $week_start],
            ['create_time', '<=', $week_end],
        ])->field('SUM(change_money) y,FROM_UNIXTIME(create_time,"%w") as x')
            ->group(' FROM_UNIXTIME(create_time,"%w")')
            ->select();
        $list_data = $this->format_data($list_data);
        //本月
        $month_start = strtotime(date("Y-m-01") . ' 00:00:00');
        $month_end = strtotime(date('Y-m-t') . ' 23:59:59');
        $month = Db::name("user_money_log")->where([
            ['user_id', '=', $user['id']],
            ['change_type', '=', $change_type],
            ['create_time', '>=', $month_start],
            ['create_time', '<=', $month_end],
        ])->sum("change_money");

        //上一月
        $timestamp = time();
        $last_month_start = date('Y-m-01', strtotime(date('Y', $timestamp) . '-' . (date('m', $timestamp) - 1) . '-01'));
        $last_month_end = date('Y-m-d', strtotime("$last_month_start +1 month -1 day"));
        $last_month = Db::name("user_money_log")->where([
            ['user_id', '=', $user['id']],
            ['change_type', '=', $change_type],
            ['create_time', '>=', $last_month_start],
            ['create_time', '<=', $last_month_end],
        ])->sum("change_money");

        //柱状图
        $list = [
            'categories' => $list_data['x'],
            'data' => $list_data['y'],
        ];
        $res = [
            'total' => $total,
            'today' => $today,
            'week' => $week,
            'month' => $month,
            'last_month' => $last_month,
            'list' => $list
        ];
        return ApiReturn::r(1, $res, 'ok');
    }

    /**
     * 格式数据
     * @author chenchen
     * @since 2021年4月14日16:34:45
     */
    public function format_data($data = [])
    {
        $x = [
            '0' => '星期日',
            '1' => '星期一',
            '2' => '星期二',
            '3' => '星期三',
            '4' => '星期四',
            '5' => '星期五',
            '6' => '星期六',
        ];
        $y = [];
        for ($i = 0; $i <= 6; $i++) {
            $y[$i] = 0;
        }
        if (count($data) > 0) {
            foreach ($data as $v) {
                $y[$v['x']] = $v['y'];
            }
        }
        $list = [
            'x' => $x,
            'y' => $y
        ];
        return $list;
    }
//    public function get_money_detail($data = [], $user = [])
//    {
//        $change_type = $data['change_type'] ? intval($data['change_type']) : 0;
//
//        if ($data['date']) {
//            $start_time = strtotime($data['date']);
//            $end_time = strtotime('+1 month', $start_time);
//            $whereTime = "create_time BETWEEN $start_time AND $end_time";
//        }
//        $change_type_where = [];
//        if ($change_type) {
//            if ($change_type == 1) {
//                $change_type_where[] = ['change_type', 'in', '1,3'];
//            } else {
//                $change_type_where[] = ['change_type', 'in', '2,4,5,6,7,8,9,10'];
//            }
//        }
//        $data = \think\Db::name('user_money_log')
//            ->where('user_id', $user['id'])
//            ->where($whereTime)
//            ->where($change_type_where)
//            ->order('aid', 'desc')
//            ->paginate();
//        $res = [];
//        if ($data) {
//            foreach ($data as $item) {
//                if (floatval($item['change_money']) > 0) {
//                    $item['t'] = 1;
//                } else {
//                    $item['t'] = 2;
//                }
//
//                $item['month'] = date("Y-m", $item['create_time']);
//                if ($item['month'] == date("Y-m", time())) {
//                    $item['month'] = '本月';
//                };
//
//                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
//                if ($item['change_type'] == 4) {
//                    $item['check_status'] = Db::name('user_withdraw')->where('order_no', $item['order_no'])->value('check_status');
//                } else {
//                    $item['check_status'] = -1;
//                }
//                $res['data'][$item['month']][] = [
//                    'aid' => $item['aid'],
//                    'user_id' => $item['user_id'],
//                    'before_money' => $item['before_money'],
//                    'change_money' => $item['change_money'],
//                    'change_type' => $item['change_type'],
//                    'remark' => $item['remark'],
//                    'order_no' => $item['order_no'],
//                    'goods_id' => $item['goods_id'],
//                    't' => $item['t'],
//                    'check_status' => $item['check_status'],
//                    'create_time' => $item['create_time']
//                ];
//            }
//        }
//        $res['current_page'] = $data->currentPage();
//        $res['last_page'] = $data->lastPage();
//        $res['total'] = $data->total();
//
//        if ($data) {
//            return ApiReturn::r(1, $res, '请求成功');
//        }
//        return ApiReturn::r(1, [], '请求成功');
//    }

    /**
     * 上传微信或支付宝提现账号信息
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/13 20:46
     */
    public function bind_withdraw_account($data, $user)
    {
        $data['user_id'] = $user['id'];
        $WithdrawAccount = new \app\user\model\WithdrawAccount();
        $info = $WithdrawAccount->where(['user_id' => $user['id'], 'account_type' => $data['account_type']])->find();
        // 启动事务
        Db::startTrans();
        try {
            if (empty($data['account_id'])) {
                exception(lang('绑定账号不能为空'));
            }
            if ($info) {
                $res1 = $WithdrawAccount->where(['user_id' => $user['id']])->update(['is_default' => 0, 'update_time' => time()]);
                $data['update_time'] = time();
                $res = $WithdrawAccount->where(['user_id' => $user['id'], 'account_type' => $data['account_type']])->update($data);
                if ($res === false || $res1 == false) {
                    exception(lang('绑定失败'));
                }
            } else {
                $data['status'] = 1;
                $res = $WithdrawAccount->create($data);
                if (!$res) {
                    exception(lang('绑定失败'));
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        $info = $WithdrawAccount->where(['user_id' => $user['id'], 'account_type' => $data['account_type']])->find();
        return ApiReturn::r(1, $info, lang('绑定成功'));
    }

    /**
     * 获取绑定的提现账号
     * @param $data
     * @param $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/13 21:39
     */
    public function get_withdraw_account($data, $user)
    {
        $res = WithdrawAccount::where(['user_id' => $user['id'], 'account_type' => $data['account_type']])->find();

        if ($res) {
            if ($res['status'] == 1) {
                if ($data['account_type'] != 3 && $res['qrcode'] == '') {
                    return ApiReturn::r(0, [], lang('暂未绑定'));
                }
            } else {
                return ApiReturn::r(0, [], lang('暂未绑定'));
            }
        } else {
            return ApiReturn::r(0, [], lang('暂未绑定'));
        }
        $result = $this->filter($res, $this->fname);
        return ApiReturn::r(1, $result, lang('请求成功'));
    }

    /**
     * 申请提现
     * @return void
     * @since 2019/4/23 19:05
     * @editor 李志豪 [ 995562569@qq.com ]
     * @updated 2019.05.21
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function withdraw($data = [], $user = [])
    {
        $money = $data['money'];
        $type = $data['type'];
        $pay_password = User::where('id', $user['id'])->value('pay_password');
        if (empty($pay_password)) {
            return ApiReturn::r(1001, [], lang('请设置支付密码'));
        }
        $_type = 2;
        $time = strtotime(date("Y-m-d"));
        $_where[] = ['user_id', '=', $user['id']];
        $_where[] = ['time', 'gt', $time];
        $_where[] = ['type', '=', $_type];

        $count = Db::name('user_login_info')->where($_where)->count();
        if ($pay_password != Hash::check($data['pay_password'], $pay_password)) {
            $now_time = time();
            Db::name('user_login_info')->insert([
                'mobile' => $user['mobile'],
                'time' => $now_time,
                'user_id' => $user['id'],
                'type' => $_type
            ]);
            if ($count >= module_config("user.pay_count")) {
                return ApiReturn::r(0, [], '账号支付密码已被锁定,请明天再试');
            } else {
                return ApiReturn::r(1002, [], lang('支付密码错误'));
            }
        }
        // 启动事务
        Db::startTrans();
        try {
            //判断提现申请次数

            $time = strtotime(date("Y-m-d"));
            $where[] = ['user_id', '=', $user['id']];
            $where[] = ['create_time', 'gt', $time];
            $count = Db::name("user_withdraw")->where($where)->count();
            if ($count >= module_config("user.withdraw_count")) {
                return ApiReturn::r(0, [], lang('您今日') . module_config("user.withdraw_count") . lang('提现次数已用完'));
            }
            // 读取实时金额
//            $now_money = User::where('id', $user['id'])->lock(true)->value('user_money');
            $now_money = User::money($user['id']);
            //后台配置的最低提现标准
            $wd_min_money = module_config('user.min_withdraw_money');

            // 提现金额起提标准判断
            if ($now_money < $wd_min_money) {
                return ApiReturn::r(0, [], lang('您的余额暂未达到提现标准'));
            }

            // 提现金额最低标准判断
            if ($wd_min_money > $money) {
                exception(lang('提现金额最低为') . $wd_min_money . lang('元'));
            }

            // 提现金额不能大于余额
            if ($money > $now_money) {
                exception(lang('余额不足，无法提现'));
            }
            //提现余额为减法
            $tx_money = -$money;

            $order_no = get_order_sn('TX');
            // 变更余额记录
            $moneylog = MoneyLog::changeMoney($user['id'], $now_money, $tx_money, 4, $remark = lang('会员申请提现'), $order_no);
            if (!$moneylog) {
                exception(lang('更改余额失败'));
            }

            //提现到余额就不录入提现表
            if ($type != -1) {
                //组合用户提现信息
                $account = Db::name('user_withdraw_account')->where(['user_id' => $user['id'], 'account_type' => $type])->field('id,true_name')->find();
                $withdraw_data = [
                    'user_id' => $user['id'],
                    'true_name' => $account['true_name'] ?? '',
                    'order_no' => $order_no,
                    'cash_fee' => $money,
                    'check_status' => 0,
                    'account_type' => $type,
                    'account_id' => $account['id'] ?? '',
                    'create_time' => time(),
                ];
                //精度计算手续费
                $withdraw_handling_type = module_config('user.withdraw_handling_type');
                if ($withdraw_handling_type == 0) {
                    //固定金额手续费
                    $withdraw_handling_fee = module_config('user.withdraw_handling_fee');
                } else {
                    //百分比手续费
                    $withdraw_handling_fee = bcmul($money, module_config('user.withdraw_handling_fee') * 0.01, 2);
                }
                $withdraw_data['pay_fee'] = bcsub($money, $withdraw_handling_fee, 2);
                $withdraw_data['handling_fee'] = $withdraw_handling_fee;
                // 新增提现记录
                $withdraw = Db::name('user_withdraw')->insertGetId($withdraw_data);

                if (!$withdraw) {
                    exception(lang('创建提现记录失败'));
                }
            }

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        return ApiReturn::r(1, [], lang('申请提现成功'));
    }

    /**
     * 获取充值规则
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/5/8 18:49
     */
    public function recharge_rule($data, $user)
    {
        $list = \app\user\model\RechargeRule::where("status", 1)->where('group', $data['group'])->order("sort asc,id asc")->select();
        $info = [];
        foreach ($list as $val) {
            $info[] = $this->filter($val, $this->fname);
        }
        return ApiReturn::r(1, $info, lang('成功'));
    }

    /**
     * 获取冻结资金列表
     * @param  [type] $data [description]
     * @param  [type] $user [description]
     * @return [type]       [description]
     */
    public function get_freeze_money_list($data, $user)
    {
        $change_type = $data['change_type'] ? intval($data['change_type']) : 0;
        $change_type_where = [];
        if ($change_type) {
            switch ($change_type) {
                case 1:
                    $change_type_where[] = ['type', 'eq', '1'];
                    break;
                case 2:
                    $change_type_where[] = ['type', 'eq', '2'];
                    break;
                case 3:
                    $change_type_where[] = ['type', 'in', '3'];
                    break;
                default:
                    $change_type_where = [];
                    break;
            }
        }
        $list = Db::name('user_freeze_money_log')
            ->where($change_type_where)
            ->order("create_time desc")
            ->paginate();
        $returndata = [];
        if (count($list) > 0) {
            $list_arr = $list->toArray();
            $list = $list_arr['data'];
            $returndata['total'] = $list_arr['total'];
            $returndata['last_page'] = $list_arr['last_page'];
        } else {
            $list = [];
            $returndata['total'] = 0;
        }
        foreach ($list as $key => $value) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $list[$key]['status_text'] = $value['is_delete'] == 1 ? lang('已发放') : lang('冻结中');
        }
        $returndata['list'] = $list;
        return ApiReturn::r(1, $returndata, lang('成功'));
    }

    /**
     * 获取提现记录
     * @param  [type] $data [description]
     * @param  [type] $user [description]
     * @return [type]       [description]
     */
    public function get_withdraw_list($data, $user)
    {
        $userInfo = User::get($user['id']);
        $page_size = $data['list_rows'] ?? 10;
        if (!$userInfo) {
            return ApiReturn::r(0, [], lang('暂无用户'));
        }

        if ($data['start_data'] && $data['end_data']) {
            $start_time = strtotime($data['start_data']);
            $end_time = strtotime($data['end_data'] . " 23:59:59");
            $whereTime = "create_time BETWEEN $start_time AND $end_time";
        }

        $list = WithdrawModel::where('user_id', $user['id'])
            ->where($whereTime)
            ->limit((($data['page'] - 1) * $page_size) . ',' . $page_size)
            ->order("create_time desc")
            ->select();
        if (count($list) > 0) {
            $list = $list->each(function (&$item) {
                if ($item['cash_status'] == 1) {
                    $item['check_status'] = 3;
                } elseif ($item['cash_status'] == 2) {
                    $item['check_status'] = 4;
                }
                return $item;
            })
                ->toArray();
        } else {
            $list = [];
        }
        $count = WithdrawModel::where([
            'user_id' => $user['id']
        ])->where($whereTime)
            ->count();
        return ApiReturn::r(1, ['list' => $list, 'count' => $count, 'last_page' => ceil($count / $page_size)], lang('成功'));
    }

    /**
     * 获取提现规则
     * @param  [type] $data [description]
     * @param  [type] $user [description]
     * @return [type]       [description]
     */
    public function withdraw_rule($data, $user)
    {
        $list = Db::name('user_withdraw_rule')->where('status', 1)->order('sort asc')->select();
        return ApiReturn::r(1, $list, lang('成功'));
    }
}
