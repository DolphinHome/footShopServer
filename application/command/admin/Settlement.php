<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\command\admin;


use app\common\model\Order;
use app\goods\model\Goods;
use app\goods\model\GoodsSku;
use app\operation\model\Coupon;
use app\operation\model\CouponRecord;
use think\Db;
use app\user\model\MoneyLog;
use app\operation\model\SystemMessage as SystemMessageModel;
use app\user\model\Marketing;
use app\user\model\ScoreLog;
use app\goods\service\Goods as GoodsService;
use app\user\service\User as UserService;
use app\user\service\Money;
use app\goods\model\OrderPickup;
use app\goods\model\OrderGoods;
use app\goods\service\Order as OrderService;
use app\user\model\User;

/*
 * 结算脚本
 *
 */

class Settlement extends Base
{

    /*
     *
     * 定时任务脚本
     */
    public function index()
    {
        $this->wx_downloadbill();
        $this->ali_downloadbill();
        $this->cancel_order();
        $this->receive_order();
        $this->join_order_success();
        $this->pickup_arrive();
        $this->statistics();
        $this->check_payment_log();
        $this->update_coupon();
        $this->lock_show_user();
        echo '执行完毕';

    }

    /**
     * 对外演示的后台登录账号定时锁定
     */
    public function lock_show_user()
    {
        //后台配置的演示账户锁定间隔时间，天数
        $day = config('show_user_lock_day')??2;
        $day_time = $day * 86400;
        //演示账号角色ID, 犇腾家是10
        $role_show = 10;
        $admin_list = Db::name('admin')->where(['role'=>$role_show, 'status'=>1])->field('id,status,last_login_time,update_time')->select();
        if(empty($admin_list)){
            exit;
        }
        foreach($admin_list as $v){
            if ($v['last_login_time'] && (time() - $v['last_login_time'] > $day_time)) {
                //判断解封时间，是否在锁定之后，如果是锁定之后重新解封则不再锁定
                if ($v['update_time'] - $v['last_login_time'] > $day_time) {
                    exit;
                }
                //用户上次登录时间距离现在时间大于锁定间隔时间,则禁用此账号，不能再登录，需要联系超管解封
                Db::name('admin')->where('id', $v['id'])->update(['status'=>0]);
            }
        }
    }
    /*
     * 分享赚自动到账
     *
     */
    public function share_money_auto()
    {
        $list = Db::name("user_freeze_money_log")->where([
            'type' => 2,
            'is_delete' => 0
        ])->select();
        $day = module_config('goods.share_money_day') ?? 7;
        foreach ($list as $v) {
            if ($v['create_time'] - time() <= $day * 24 * 3600) {
                $user_money = Db::name("user")->where(['id' => $v['user_id']])->value("user_money");
                MoneyLog::share_changeMoney($v['user_id'], $user_money, $v['change_money'], 8, $remark = '分享赚返钱', $v['order_no'], 0, $v['goods_id']);
                Db::name("user")->where(['id' => $v['user_id']])->setInc("user_money", $v['change_money']);
                Db::name("user_freeze_money_log")->where([
                    'aid' => $v['aid']
                ])->update(['is_delete' => 1]);
            }

        }

    }

    /*
 * 分销佣金自动到账
 *
 */
    public function commission_money_auto()
    {
        $list = Db::name("user_freeze_money_log")->where([
            'type' => 3,
            'is_delete' => 0
        ])->select();
        $day = module_config('user.commission_day') ?? 7;
        foreach ($list as $v) {
            if (time() - $v['create_time'] >= $day * 24 * 3600) {
                $user_money = Db::name("user")->where(['id' => $v['user_id']])->value("user_money");
                MoneyLog::share_changeMoney($v['user_id'], $user_money, $v['change_money'], 10, $remark = '分销佣金返钱', $v['order_no'], 0, $v['goods_id']);
                Db::name("user")->where(['id' => $v['user_id']])->setInc("user_money", $v['change_money']);
                Db::name("user_freeze_money_log")->where([
                    'aid' => $v['aid']
                ])->update(['is_delete' => 1]);
                Db::name("distribution_commission")->where([
                    'order_sn' => $v['order_no']
                ])->update(['is_settlement' => 1]);

            }

        }
    }

    /*
 * 微信自动对账 下载30天的账单
 *
 */
    public function wx_downloadbill()
    {

        $bill_date = date('yymd', time() - 30 * 24 * 3600);
        $data[] = [
            'bill_date' => $bill_date,
            'bill_type' => 'ALL'
        ];
        //$result = addons_action("WeChat", "Bill", "download", $data);


    }

    /*
 * 支付宝自动对账 下载30天的账单
 *
 */
    public function ali_downloadbill()
    {
        $bill_date = date('yymmdd', time() - 30 * 24 * 3600);
        $data = [
            'bill_date' => $bill_date,
            'bill_type' => 'ALL'
        ];
        //$result = addons_action("Alipay", "Bill", "download", $data);

    }

    /*
     * 订单自动取消
     *
     */
    public function cancel_order()
    {
        $order_timeout = module_config('goods.order_timeout') ?? 30;
        $order = Order::where([
            'status' => 0,
        ])->select();
        if (count($order) > 0) {
            foreach ($order as $v) {
                $is_timestamp = is_timestamp($v['create_time']);
                if ($is_timestamp) {
                    $create_time = $v['create_time'];
                } else {
                    $create_time = strtotime($v['create_time']);
                }
                if ((time() - $create_time) >= ($order_timeout * 60)) {
                    $res = OrderService::cancel_order($v['order_sn']);
                    if ($res['code'] == 0) {
                        //记录失败日志
                        file_put_contents("./../runtime/cancel_order" . date("Y-m-d", time()) . ".log", "订单号：" . $v['order_sn'] . " 取消失败 " . "\n", FILE_APPEND);
                    }
                    Db::name("order_cancel_log")->insert([
                        "order_sn" => $v['order_sn'],
                        "create_time" => time(),
                        "remark" => "脚本自动取消，自动取消时间：" . $order_timeout . "分钟"
                    ]);
                }
            }
        }
        echo "ok";
    }

    /*
     * 拼团超时自动成团
     *
     */
    public function join_order_success()
    {
        $order_timeout = module_config('goods.join_timeout') ?? 0;
        if ($order_timeout == 0) {
            return true;
        }
        $end_time = time() - ($order_timeout * 60);
        $goods_activity_group = Db::name('goods_activity_group')->where(['is_full' => 0, 'status' => 1])->whereTime('create_time', '<', $end_time)->field('id')->select();
        if (count($goods_activity_group) > 0) {
            $result = $this->add_robot_to_group($goods_activity_group);
        } else {
            return true;
        }
    }

    /*
     * 拼团超时自动成团
     *
     */
    public function add_robot_to_group($goods_activity_group)
    {
        $host = config('web_site_domain');
        $robot = [
            ['user_name' => '天色2653', 'user_head' => $host . '/static/service/images/user-head1.jpg'],
            ['user_name' => '用户06826', 'user_head' => $host . '/static/service/images/user-head2.jpg'],
            ['user_name' => '用户04325', 'user_head' => $host . '/static/service/images/user-head3.jpg'],
            ['user_name' => '兮兮06855', 'user_head' => $host . '/static/service/images/user-head4.jpg'],
            ['user_name' => '天行01826', 'user_head' => $host . '/static/service/images/user-head.png'],
            ['user_name' => '王老师', 'user_head' => $host . '/static/service/images/login_logo.png'],
            ['user_name' => 'wuliyun', 'user_head' => $host . '/static/service/images/login-img.png'],
            ['user_name' => '兔兔096', 'user_head' => $host . '/static/service/images/user-head9.jpg'],
            ['user_name' => '安然9656', 'user_head' => $host . '/static/service/images/user-head5.jpg'],
            ['user_name' => '阳光9561', 'user_head' => $host . '/static/service/images/user-head6.jpg'],
            ['user_name' => '自由本派', 'user_head' => $host . '/static/service/images/user-head7.jpg'],
            ['user_name' => '瞬息999', 'user_head' => $host . '/static/service/images/user-head8.jpg'],
        ];
        Db::startTrans();
        try {
            foreach ($goods_activity_group as $key => $value) {
                $group_user = '';
                $group = Db::name('goods_activity_group')->lock(true)->get($value['id']);
                if ($group) {
                    $activity = Db::name('goods_activity_details')->where(['goods_id' => $group['goods_id'], 'activity_id' => $group['activity_id']])->find();
                    if (!$activity) {
                        continue;
                    }
                    $unoccupied = $activity['join_number'] - $group['num'];
                    if ($unoccupied <= 0) {
                        continue;
                    }

                    for ($i = 1; $unoccupied >= $i; $i++) {//补充剩余
                        $rander = rand(0, (count($robot) - 1));
                        $group_user[] = [
                            'group_id' => $group['id'],
                            'order_sn' => '',
                            'uid' => 0,
                            'user_name' => $robot[$rander]['user_name'],
                            'user_head' => $robot[$rander]['user_head'],
                            'status' => 1,
                            'is_full' => 1,
                        ];
                        unset($robot[$rander]);
                        $robot = array_values($robot);
                    }
                    Db::name('goods_activity_group_user')->insertAll($group_user);
                    Db::name('goods_activity_group_user')->where([
                        'group_id' => $group['id']
                    ])->update([
                        'is_full' => 1
                    ]);
                    Db::name('goods_activity_group')->where(['id' => $group['id']])->update(['is_full' => 1, 'num' => $activity['join_number']]);
                } else {
                    exception('团已不存在');
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }

    }

    /*
     * 订单自动确认收货
     *
     */
    public function receive_order()
    {

        $day = module_config('goods.order_receive') ?? 7;
        $order = Db::name("order")->where([
            ['status', '=', 2],
            ['pay_time', '<=', time() - $day * 24 * 3600]
        ])->field("order_sn,user_id")->select();
        if (count($order) > 0) {
            foreach ($order as $v) {
                $data['order_sn'] = $v['order_sn'];
                $user = Db::name("user")->get($v['user_id']);
                $this->_receive_order($data, $user);
            }
        }


    }

    /*
     * 订单自动确认收货
     *
     */
    public function _receive_order($data, $user)
    {
        // order_status = 2 => 3
        $order_sn = $data['order_sn'];
        $integral = 0;
        Db::startTrans();
        try {
            $order = Db::name('order')->where('order_sn', $order_sn)->where('status', 2)->find();

            if (!$order) {
                exception('订单不可操作，请刷新');
            }
            Db::name('order')->where('aid', $order['aid'])->update(['status' => 3]);
            Db::name('order_goods_express')->where('order_sn', $order_sn)->update(['receive_time' => time()]);
            Db::name('order_goods_list')->where('order_sn', $order_sn)->update(['order_status' => 3]);
            // zhougs  2020年12月15日16:50:09
            $order_goods = Db::name('order_goods_list')->where('order_sn', $order_sn)->select();
            $user_ = Db::name('user')->get($user['id']);
            // zenghu ADD 分销分佣日志及等级晋升 2020年8月5日14:27:23
            Marketing::add_user_marketing($order['order_money'], $order_sn, $user_);

            $empirical = 0;
            $msg = new SystemMessageModel();
            foreach ($order_goods as $v) {
                $freeze_money_log = Db::name('user_freeze_money_log')->where(['order_no' => $order_sn, 'is_delete' => 0])->where(['goods_id' => $v['goods_id']])->where(['sku_id' => $v['sku_id']])->find();

                $is_refund = Db::name('order_refund')->where(['order_sn' => $data['order_sn'], 'goods_id' => $v['goods_id'], 'sku_id' => $v['sku_id']])->find();
                if ($is_refund) {
                    continue;
                }
                $goods = Db::name('goods')->get($v['goods_id']);
                $integral += $goods['give_integral'];
                $empirical += $goods['empirical'];
                if ($freeze_money_log) {
                    //冻结的自购返转为正常金额
                    $earnings = $freeze_money_log['change_money'];
                    $money_log2 = Money::money_update($user['id'], $user['buy_back_money'], $earnings, 9, $order_sn, $v['goods_id'], $v['sku_id']);
                    if ($money_log2['code'] == 0) {
                        exception("自购返转为正常金额失败");
                    }
                    $ms = ['type' => 1, 'msg_type' => 1, 'template_type' => 1, 'to_user_id' => $user['id'], 'title' => '系统通知', 'content' => '恭喜你获得返现金额' . $earnings . '元,请在个人中心返佣资产查收。'];
                    $msg->create($ms);
                    if ($v['share_sign']) {
                        //冻结的分享赚转为正常金额
                        $share_uid = Db::name('goods_share')->where(['share_sign' => $v['share_sign']])->find();//获取分享链接
                        if ($share_uid['uid'] != $user['id']) {
                            $share_user = Db::name('user')->get($share_uid['uid']);//获取分享人的用户信息
                            $money_log = Money::money_update($share_uid['uid'], $share_user['share_money'], $earnings, 8, $order_sn, $v['goods_id'], $v['sku_id']);
                            if ($money_log['code'] == 0) {
                                exception("分享赚转为正常金额失败");
                            }
                            $ms = ['type' => 1, 'msg_type' => 1, 'template_type' => 1, 'to_user_id' => $share_uid['uid'], 'title' => '系统通知', 'content' => '用户' . $user['user_nickname'] . '购买了您分享的商品，您获得金额为' . $earnings . "，七日后到您余额。"];
                            $msg->create($ms);
                        }
                    }

                }
                //分销佣金
                $commission = GoodsService::goods_commission($v['goods_id']);
                if ($commission['first_profit'] > 0 && $commission['second_profit'] > 0) {
                    //上级
                    $first_id = UserService::parent_dis_id($user['id']);
                    if ($first_id) {
                        $user_commission = User::where(['id' => $first_id])->value("commission");
                        $first_profit = Money::money_update($first_id, $user_commission, $commission['first_profit'], 10, $order_sn, $v['goods_id'], $v['sku_id']);
                        if ($first_profit['code'] == 0) {
                            file_put_contents("./../runtime/distribution.log", "订单号：" . $order_sn . "\n", FILE_APPEND);
                        }
                        //上上级
                        $second_id = UserService::parent_dis_id($first_id);
                        if ($second_id) {
                            $user_commission = User::where(['id' => $second_id])->value("commission");
                            $second_profit = Money::money_update($second_id, $user_commission, $commission['first_profit'], 10, $order_sn, $v['goods_id'], $v['sku_id']);
                            if ($second_profit['code'] == 0) {
                                file_put_contents("./../runtime/distribution.log", "订单号：" . $order_sn . "\n", FILE_APPEND);
                            }
                        }
                    }
                }

            }
            if ($integral > 0) {
                $score_log = ScoreLog::change($user['id'], $integral, 2, lang('购买商品增加积分'), $order_sn);
                if (!$score_log) {
                    exception(lang('积分变更失败'));
                }
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }


    /**
     * 自提订单更改发货状态
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-19 18:01:59
     */
    public function pickup_arrive()
    {
        Db::startTrans();
        try {
            $map = [
                ['status', '=', 1],
                ['send_type', '=', 1]
            ];
            $orders = [];
            $orders = Order::where($map)->field('user_id, order_sn, order_type')->select();
            if (count($orders) < 1) {
                exception('no data');
            }
            foreach ($orders as $o) {
                $order_sn = $o['order_sn'];
                $user_id = $o['user_id'];
                $order_type = $o['order_type'];
                $where = [
                    ['order_sn', '=', $order_sn]
                ];
                //如果是拼团订单
                $res_update = $send_sysmsg = false;
                if ($order_type == 5) {
                    //判断是否成团，is_full=1为成团
                    $is_full = Db::name('goods_activity_group_user')->where($where)->value('is_full');
                    if ($is_full == 1) {
                        //订单状态变为已发货
                        $res_update = Order::where($where)->setField('status', 2);
                        if (!$res_update) {
                            //写入日志
                            file_put_contents("./../runtime/pickup_arrive_fail.log", "订单号：" . $order_sn . "\n", FILE_APPEND);
                        } else {
                            $send_sysmsg = true;
                        }
                    }

                } elseif ($order_type == 7) {
                    //预售订单, 查询关联的尾款订单
                    $final_order_sn = Db::name('order_relation')->where(['book_order_sn' => $order_sn])->value('final_order_sn');
                    $final_order_status = Order::where(['order_sn' => $final_order_sn])->value('status');
                    //尾款订单已支付状态
                    if ($final_order_status == 1) {
                        //首款订单状态变为已发货
                        $res_update_book = Order::where($where)->setField('status', 2);
                        if (!$res_update_book) {
                            //写入日志
                            file_put_contents("./../runtime/pickup_arrive_fail.log", "订单号：" . $order_sn . "\n", FILE_APPEND);
                        } else {
                            $send_sysmsg = true;
                        }
                        //尾款订单状态变为已发货
                        $res_update_final = Order::where(['order_sn' => $final_order_sn])->setField('status', 2);
                        if (!$res_update_final) {
                            //写入日志
                            file_put_contents("./../runtime/pickup_arrive_fail.log", "订单号：" . $final_order_sn . "\n", FILE_APPEND);
                        }
                    }
                } else {
                    //订单状态变为已发货
                    $res_update = Order::where($where)->setField('status', 2);
                    if (!$res_update) {
                        //写入日志
                        file_put_contents("./../runtime/pickup_arrive_fail.log", "订单号：" . $order_sn . "\n", FILE_APPEND);
                    } else {
                        $send_sysmsg = true;
                    }
                }
                //发货状态更新成功，发送系统推送
                if ($send_sysmsg) {
                    //自提点信息
                    $pickup_info = OrderPickup::getOrderPickUp($order_sn);
                    $goods = OrderGoods::get_one_goods($order_sn);
                    //给用户发送消息通知-可以自提了
                    $message = SystemMessageModel::send_msg(
                        $user_id,
                        '您的货物已到自提点',
                        '您的货物：' . $order_sn . '已到自提点' . $pickup_info['deliver_name'] . '，请及时前往取货',
                        1,
                        3,
                        1,
                        $goods['goods_thumb'],
                        '/pages/order/orderdetail/order-detail/index?order_sn=' . $order_sn . '&order_type=3'
                    );
                }

            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    /**
     * 更新order表的总成本字段
     */
    public function statistics()
    {
        set_time_limit(0);
        $list = Order::where([
            ["cost_price_total", "<=", 0]
        ])->select();
        if (count($list) > 0) {
            foreach ($list as $v) {
                $cost_price_total = 0;
                $order_goods_list = OrderGoods::where([
                    "order_sn" => $v['order_sn']
                ])->select();
                foreach ($order_goods_list as $m) {
                    if ($m['sku_id']) {
                        $cost_price = GoodsSku::where([
                            "goods_id" => $m["goods_id"],
                            "sku_id" => $m["sku_id"]
                        ])->value("cost_price");
                    } else {
                        $cost_price = Goods::where([
                            "id" => $m["goods_id"]
                        ])->value("cost_price");
                    }
                    $cost_price_total += $m['num'] * $cost_price;
                }
                Order::where([
                    "order_sn" => $v['order_sn']
                ])->update([
                    "cost_price_total" => $cost_price_total
                ]);
            }
            echo "ok";
        }
    }


    /**
     * 对账中心核对状态修改
     */
    public function check_payment_log()
    {
        set_time_limit(0);
        $list = Db::name('payment_log')->where([
            ['check_status', '=', 0]
        ])->select();
        if (count($list) > 0) {
            foreach ($list as $v) {
                $amount = Db::name('payment_log')->where(["order_sn" => $v['order_sn']])->sum('amount');
                $orderinfo = Order::where(["order_sn" => $v['order_sn']])->field('payable_money')->find();
                $payable_money = $orderinfo['payable_money'];

                if (bccomp($payable_money, $amount, 2) == 0) {
                    Db::name('payment_log')->where(["order_sn" => $v['order_sn']])->update([
                        "check_status" => 1
                    ]);
                } else {
                    Db::name('payment_log')->where(["order_sn" => $v['order_sn']])->update([
                        "check_status" => 2
                    ]);
                }
            }
        }
    }

//    /*
//     * 写入流水号
//     *
//     */
//    public function tran_no()
//    {
//        set_time_limit(0);
//        $order = Order::where([
////            ['transaction_id', '<>', ''],
//            ['pay_status', '=', 1]
//        ])->select()->toArray();
//        foreach ($order as $v) {
//            if ($v['payable_money'] > 0) {
//                if ($v['real_balance'] > 0) {
//                    Db::name("payment_log")->insert([
//                        'order_sn' => $v['order_sn'],
//                        'amount' => $v['real_money'],
//                        'transaction_no' => $v['transaction_id'],
//                        'status' => 1,
//                        'create_time' => $v['pay_time'],
//                        'pay_type' => $v['pay_type']
//                    ]);
//                    Db::name("payment_log")->insert([
//                        'order_sn' => $v['order_sn'],
//                        'amount' => $v['real_balance'],
//                        'transaction_no' => '',
//                        'status' => 1,
//                        'create_time' => $v['pay_time'],
//                        'pay_type' => 'balance'
//                    ]);
//                } else {
//                    Db::name("payment_log")->insert([
//                        'order_sn' => $v['order_sn'],
//                        'amount' => $v['payable_money'],
//                        'transaction_no' => $v['transaction_id'],
//                        'status' => 1,
//                        'create_time' => $v['pay_time'],
//                        'pay_type' => $v['pay_type']
//                    ]);
//                }
//            }
//
//        }
//        echo 'ok';
//
//    }
//
//    public function read_file()
//    {
//        header("content-type:text/html;charset=utf-8");
//        // 获取文件夹中的所有txt文件名
//        $dir = "./../runtime/talk/"; //这里输入其他路径
//        $handle = opendir($dir . ".");
//        $row = array();
//        $file = readdir($handle);
//        while (false != ($file = readdir($handle))) {
//            if ($file != "." && $file != "..") {
//                $row[]['name'] = $file;//输出文件名
//            }
//        }
//        $files = [];
//        foreach ($row as $k => $v) {
//            $files[] = $row[$k]['name'];
//        }
//        //$files是该文件夹下所有txt文件的名字
//        foreach ($files as $k => $v) {
//            $this->read_txt($files[$k]);//这里循环读取每一个txt文件内的内容并做数据库处理
//        }
//        echo "success";
//        closedir($handle);
//        exit;
//    }
//
////读取txt文件
//    protected function read_txt($file_name)
//    {
//        header("content-type:text/html;charset=utf-8");
//        $file = "./../runtime/talk/" . $file_name;
//        ###判断该文件是否存在
//        if (file_exists($file)) {
//            $get_file = file_get_contents($file);
//            $get_file = htmlspecialchars($get_file);
////            var_dump($get_file);die;
//            $pattern = '/\d{7}[*]{4}/';
//            preg_match_all($pattern, $get_file, $m);   //返回一个匹配结果
//            $i = 0;
//            foreach ($m[0] as $v) {
//                $get_file = str_replace($v, " ", $get_file);
//                $i++;
//                if ($i == count($m[0])) {
//                    file_put_contents($file, htmlspecialchars_decode($get_file));
//                }
//            }
//
//        } else {
//            echo "文件不存在";
//        }
//    }
//
//
//    public function letter()
//    {
//        $list = Db::name("phone_prefix")->where([])->select();
//        foreach ($list as $v) {
//            $letter = $this->getFirstCharters($v['country']);
//            Db::name("phone_prefix")->where(["id" => $v["id"]])->update(["letter" => $letter]);
//        }
//        echo "ok";
//    }
//
//
////获取汉字的首字母
//    function getFirstCharters($str)
//    {
//        if (empty($str)) {
//            return '';
//        }
//        //取出参数字符串中的首个字符
//        $temp_str = substr($str, 0, 1);
//        if (ord($temp_str) > 127) {
//            $str = substr($str, 0, 3);
//        } else {
//            $str = $temp_str;
//            $fchar = ord($str{0});
//            if ($fchar >= ord('A') && $fchar <= ord('z')) {
//                return strtoupper($temp_str);
//            } else {
//                return null;
//            }
//        }
//        $s1 = iconv('UTF-8', 'gb2312//IGNORE', $str);
//        if (empty($s1)) {
//            return null;
//        }
//        $s2 = iconv('gb2312', 'UTF-8', $s1);
//        if (empty($s2)) {
//            return null;
//        }
//        $s = $s2 == $str ? $s1 : $str;
//        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
//        if ($asc >= -20319 && $asc <= -20284)
//            return 'A';
//        if ($asc >= -20283 && $asc <= -19776)
//            return 'B';
//        if ($asc >= -19775 && $asc <= -19219)
//            return 'C';
//        if ($asc >= -19218 && $asc <= -18711)
//            return 'D';
//        if ($asc >= -18710 && $asc <= -18527)
//            return 'E';
//        if ($asc >= -18526 && $asc <= -18240)
//            return 'F';
//        if ($asc >= -18239 && $asc <= -17923)
//            return 'G';
//        if ($asc >= -17922 && $asc <= -17418)
//            return 'H';
//        if ($asc >= -17417 && $asc <= -16475)
//            return 'J';
//        if ($asc >= -16474 && $asc <= -16213)
//            return 'K';
//        if ($asc >= -16212 && $asc <= -15641)
//            return 'L';
//        if ($asc >= -15640 && $asc <= -15166)
//            return 'M';
//        if ($asc >= -15165 && $asc <= -14923)
//            return 'N';
//        if ($asc >= -14922 && $asc <= -14915)
//            return 'O';
//        if ($asc >= -14914 && $asc <= -14631)
//            return 'P';
//        if ($asc >= -14630 && $asc <= -14150)
//            return 'Q';
//        if ($asc >= -14149 && $asc <= -14091)
//            return 'R';
//        if ($asc >= -14090 && $asc <= -13319)
//            return 'S';
//        if ($asc >= -13318 && $asc <= -12839)
//            return 'T';
//        if ($asc >= -12838 && $asc <= -12557)
//            return 'W';
//        if ($asc >= -12556 && $asc <= -11848)
//            return 'X';
//        if ($asc >= -11847 && $asc <= -11056)
//            return 'Y';
//        if ($asc >= -11055 && $asc <= -10247)
//            return 'Z';
//        return $this->rare_words($asc);
//    }
//
//    //百家姓中的生僻字
//    function rare_words($asc = '')
//    {
//        $rare_arr = array(
//            -3652 => array('word' => "窦", 'first_char' => 'D'),
//            -8503 => array('word' => "奚", 'first_char' => 'X'),
//            -9286 => array('word' => "酆", 'first_char' => 'F'),
//            -7761 => array('word' => "岑", 'first_char' => 'C'),
//            -5128 => array('word' => "滕", 'first_char' => 'T'),
//            -9479 => array('word' => "邬", 'first_char' => 'W'),
//            -5456 => array('word' => "臧", 'first_char' => 'Z'),
//            -7223 => array('word' => "闵", 'first_char' => 'M'),
//            -2877 => array('word' => "裘", 'first_char' => 'Q'),
//            -6191 => array('word' => "缪", 'first_char' => 'M'),
//            -5414 => array('word' => "贲", 'first_char' => 'B'),
//            -4102 => array('word' => "嵇", 'first_char' => 'J'),
//            -8969 => array('word' => "荀", 'first_char' => 'X'),
//            -4938 => array('word' => "於", 'first_char' => 'Y'),
//            -9017 => array('word' => "芮", 'first_char' => 'R'),
//            -2848 => array('word' => "羿", 'first_char' => 'Y'),
//            -9477 => array('word' => "邴", 'first_char' => 'B'),
//            -9485 => array('word' => "隗", 'first_char' => 'K'),
//            -6731 => array('word' => "宓", 'first_char' => 'M'),
//            -9299 => array('word' => "郗", 'first_char' => 'X'),
//            -5905 => array('word' => "栾", 'first_char' => 'L'),
//            -4393 => array('word' => "钭", 'first_char' => 'T'),
//            -9300 => array('word' => "郜", 'first_char' => 'G'),
//            -8706 => array('word' => "蔺", 'first_char' => 'L'),
//            -3613 => array('word' => "胥", 'first_char' => 'X'),
//            -8777 => array('word' => "莘", 'first_char' => 'S'),
//            -6708 => array('word' => "逄", 'first_char' => 'P'),
//            -9302 => array('word' => "郦", 'first_char' => 'L'),
//            -5965 => array('word' => "璩", 'first_char' => 'Q'),
//            -6745 => array('word' => "濮", 'first_char' => 'P'),
//            -4888 => array('word' => "扈", 'first_char' => 'H'),
//            -9309 => array('word' => "郏", 'first_char' => 'J'),
//            -5428 => array('word' => "晏", 'first_char' => 'Y'),
//            -2849 => array('word' => "暨", 'first_char' => 'J'),
//            -7206 => array('word' => "阙", 'first_char' => 'Q'),
//            -4945 => array('word' => "殳", 'first_char' => 'S'),
//            -9753 => array('word' => "夔", 'first_char' => 'K'),
//            -10041 => array('word' => "厍", 'first_char' => 'S'),
//            -5429 => array('word' => "晁", 'first_char' => 'C'),
//            -2396 => array('word' => "訾", 'first_char' => 'Z'),
//            -7205 => array('word' => "阚", 'first_char' => 'K'),
//            -10049 => array('word' => "乜", 'first_char' => 'N'),
//            -10015 => array('word' => "蒯", 'first_char' => 'K'),
//            -3133 => array('word' => "竺", 'first_char' => 'Z'),
//            -6698 => array('word' => "逯", 'first_char' => 'L'),
//            -9799 => array('word' => "俟", 'first_char' => 'Q'),
//            -6749 => array('word' => "澹", 'first_char' => 'T'),
//            -7220 => array('word' => "闾", 'first_char' => 'L'),
//            -10047 => array('word' => "亓", 'first_char' => 'Q'),
//            -10005 => array('word' => "仉", 'first_char' => 'Z'),
//            -3417 => array('word' => "颛", 'first_char' => 'Z'),
//            -6431 => array('word' => "驷", 'first_char' => 'S'),
//            -7226 => array('word' => "闫", 'first_char' => 'Y'),
//            -9293 => array('word' => "鄢", 'first_char' => 'Y'),
//            -6205 => array('word' => "缑", 'first_char' => 'G'),
//            -9764 => array('word' => "佘", 'first_char' => 'S'),
//            -9818 => array('word' => "佴", 'first_char' => 'N'),
//            -9509 => array('word' => "谯", 'first_char' => 'Q'),
//            -3122 => array('word' => "笪", 'first_char' => 'D'),
//            -9823 => array('word' => "佟", 'first_char' => 'T'),
//        );
//        if (array_key_exists($asc, $rare_arr) && $rare_arr[$asc]['first_char']) {
//            return $rare_arr[$asc]['first_char'];
//        } else {
//            return null;
//        }
//    }

    /**
     * Notes: 优惠券过期脚本
     * User: chenchen
     * Date: 2021/7/17
     * Time: 9:35
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function update_coupon()
    {
        $time = time();
        $where = "(start_time+valid_day*24*60*60)<={$time} ";
        $coupon_id = Coupon::where($where)->field("id")->select();
        if (count($coupon_id) > 0) {
            Coupon::where($where)->update([
                "status" => 3
            ]);
            foreach ($coupon_id as $v) {
                CouponRecord::where([
                    "cid" => $v["id"]
                ])->update([
                    "status" => 4
                ]);
            }
        }
    }


}