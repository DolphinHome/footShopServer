<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\common\model\Order;
use app\user\model\ScoreLog;
use service\ApiReturn;
use think\helper\Hash;
use app\user\model\MoneyLog;
use think\Db;

/**
 * 支付签名接口
 * Class UserLabel
 * @package app\api\controller\v1
 */
class Pay extends Base
{
    /**
     * 获取微信开放平台预支付订单
     * @param $data
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_wxpay($data)
    {
        //处理平台自己的订单
        $order = Order::where("order_sn", $data['order_sn'])->find();
        if (!$order) {
            return ApiReturn::r(0, [], lang('订单不存在'));
        }
        if ($order['pay_status'] > 0) {
            return ApiReturn::r(0, [], lang('该订单支付状态已发生改变'));
        }

        if ($order['pay_type'] == 'alipay') {
            return ApiReturn::r(0, [], lang('请使用发起支付宝支付接口'));
        }

        if ($order['pay_type'] == 'appleiap') {
            return ApiReturn::r(0, [], lang('请使用发起苹果支付接口'));
        }
        $data_pay = array(
            'body' => Order::$orderTypes[$order['order_type']],
            'total_fee' => $order['payable_money'],
            'out_trade_no' => $order['order_sn'],
        );
        Db::startTrans();
        try {
            switch ($data['wxpaytype']) {
                case 'minipay':
                    $data_pay['xcx_openid'] = Db::name('user_info')->where('user_id', $order['user_id'])->value('xcx_openid');
                    if (!$data_pay['xcx_openid']) {
                        return ApiReturn::r(-201, [], '获取xcx_openid失败');
                    }
                    $arr = addons_action('WeChat', 'MiniPay', 'pay', [$data_pay]);
                    break;
                case 'codepay':
                    $data_pay['wx_openid'] = Db::name('user_info')->where('user_id', $order['user_id'])->value('wx_openid');
                    if (!$data_pay['wx_openid']) {
                        return ApiReturn::r(-201, [], '获取wx_openid失败');
                    }
                    $arr = addons_action('WeChat', 'CodePay', 'pay', [$data_pay]);
                    break;
                default:
                    $arr = addons_action('WeChat', 'AppPay', 'pay', [$data_pay]);
                    break;
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, [], lang('调取支付失败') . $e->getMessage());
        }

        if ($arr['result_code'] == 'FAIL') {
            Db::rollback();
            return ApiReturn::r(0, [], $arr ? $arr['err_code_des'] : lang('调取支付失败'));
        }

        return ApiReturn::r(1, $arr, lang('请按照此配置调起支付'));
    }

    /**
     * 获取微信开放平台预支付订单
     * @param $data
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_wxpay_fill($data)
    {
        //处理平台自己的订单
        $order = Order::where("order_sn", $data['order_sn'])->find();
        if (!$order) {
            return ApiReturn::r(0, [], lang('订单不存在'));
        }
        if ($order['pay_status'] > 0) {
            return ApiReturn::r(0, [], lang('该订单支付状态已发生改变'));
        }

        if ($order['pay_type'] == 'alipay') {
            return ApiReturn::r(0, [], lang('请使用发起支付宝支付接口'));
        }

        if ($order['pay_type'] == 'appleiap') {
            return ApiReturn::r(0, [], lang('请使用发起苹果支付接口'));
        }
        $transaction_no = $data['order_sn'];
        Db::startTrans();
        try {
            $user_info = \app\user\model\User::where('id', $order['user_id'])->field('id,user_money,pay_password')->find();
            if ($user_info['user_money'] < $data['balance']) {
                exception(lang('可用余额不足'));
            }
            Db::name('order')->where(['aid' => $order['aid']])->update([
                'real_balance' => $data['balance']
            ]);


            $data_pay = array(
                'body' => Order::$orderTypes[$order['order_type']],
                'total_fee' => bcsub($order['payable_money'], $data['balance'], 2),
                'out_trade_no' => $transaction_no,
            );

            if ($data['ismini']) {
                $data_pay['xcx_openid'] = Db::name('user_info')->where('user_id', $order['user_id'])->value('xcx_openid');
                if (!$data_pay['xcx_openid']) {
                    return ApiReturn::r(-201, [], '获取xcx_openid失败');
                }
                $arr = addons_action('WeChat', 'MiniPay', 'pay', [$data_pay]);
            } else {
                $arr = addons_action('WeChat', 'AppPay', 'pay', [$data_pay]);
            }
            Db::commit();
            return ApiReturn::r(1, $arr, lang('请按照此配置调起支付'));

        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }

    }

    /**
     * 获取支付宝预支付订单
     * @param $data
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_alipay($data)
    {
        //处理平台自己的订单
        $order = Order::where(["order_sn" => $data['order_sn']])->find();
        if (!$order) {
            return ApiReturn::r(0, [], lang('订单不存在'));
        }
        if ($order['pay_status'] > 0) {
            return ApiReturn::r(0, [], lang('该订单支付状态已发生改变'));
        }

        if ($order['pay_type'] == 'wxpay') {
            return ApiReturn::r(0, [], lang('请使用发起微信支付接口'));
        }

        if ($order['pay_type'] == 'appleiap') {
            return ApiReturn::r(0, [], lang('请使用发起苹果支付接口'));
        }

        $data = array(
            'subject' => Order::$orderTypes[$order['order_type']],
            'body' => Order::$orderTypes[$order['order_type']],
            'out_trade_no' => $order['order_sn'],
            'total_amount' => $order['payable_money'],
            'notify_url' => config('web_site_domain') . '/index/pay/ali_notify'
        );
        try {
            $string = addons_action('Alipay', 'Aop', 'AlipayTradeAppPayRequest', [$data]);
        } catch (\Exception $e) {
            return ApiReturn::r(0, [], lang('调取支付失败') . $e->getMessage());
        }

        if ($string) {
            return ApiReturn::r(1, $string, lang('请按照此配置调起支付'));
        }
        return ApiReturn::r(0, [], lang('调取支付失败'));
    }

    /**
     * 获取内购预支付订单
     * @param $data
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_iospay($data)
    {
        $order = Order::where("order_sn", $data['order_sn'])->find();
        if (!$order) {
            return ApiReturn::r(0, [], lang('订单不存在'));
        }
        if ($order['pay_status'] > 0) {
            return ApiReturn::r(0, [], lang('该订单支付状态已发生改变'));
        }
        if ($order['pay_type'] == 'wxpay') {
            return ApiReturn::r(0, [], lang('请使用发起微信支付接口'));
        }

        if ($order['pay_type'] == 'alipay') {
            return ApiReturn::r(0, [], lang('请使用发起苹果支付接口'));
        }
        if (!$order['app_name']) {
            return ApiReturn::r(0, [], lang('获取内购项目失败'));
        }
        //使用INIT方法注入参数
        $data = [];
        $data['config'] = array(
            'pro_id' => $order['app_name'],
            'order_no' => $order['order_sn'],
        );
        try {
            $ios = addons_action('Iospay', 'App', 'init', [$data]);
            $arr = $ios->run();
        } catch (\Exception $e) {
            return ApiReturn::r(0, [], lang('调取支付失败') . $e->getMessage());
        }
        if ($arr) {
            return ApiReturn::r(1, $arr, lang('请按照此配置调起支付'));
        }
        return ApiReturn::r(0, [], lang('调取支付失败'));
    }

    /**
     * 余额支付
     * @param $data
     * @param $user
     * @author 上官琳
     * @created 2020/2/19 16:39
     */
    public function balancePay($data, $user)
    {
        $order = Db::name('order')->where(['order_sn' => $data['order_sn'], 'status' => 0])->find();
        if (!$order) {
            return ApiReturn::r(0, '', lang('订单不存在'));
        }
        if ($order['pay_status'] == 1) {
            return ApiReturn::r(0, '', lang('订单已支付，请勿重复支付'));
        }
        $user_info = \app\user\model\User::where('id', $user['id'])->field('id,user_money,pay_password,score')->find();

        //指纹支付不校验支付密码
        $type = $data['type'] ?? 0;
        if ($type == 0) {
            // 判断支付密码 是否存在
            if (!$user_info['pay_password']) {
                return ApiReturn::r(-2, [], lang('未设置支付密码'));
            }
            $type = 2;
            $time = strtotime(date("Y-m-d"));
            $where[] = ['user_id', '=', $user['id']];
            $where[] = ['time', 'gt', $time];
            $where[] = ['type', '=', $type];
            $count = Db::name('user_login_info')->where($where)->count();
            if ($count >= module_config("user.pay_count")) {
                return ApiReturn::r(2, [], lang('账号已被锁定，请明天再试'));
            }

            // 验证支付密码
            if (!Hash::check((string)$data['pay_password'], $user_info['pay_password'])) {
                $now_time = time();
                Db::name('user_login_info')->insert([
                    'mobile' => $user['mobile'],
                    'time' => $now_time,
                    'user_id' => $user['id'],
                    'type' => $type
                ]);
                return ApiReturn::r(0, [], lang('支付密码错误'));
            }
        }
        if ($user_info['user_money'] < $order['real_money']) {
            return ApiReturn::r(-1, '', lang('可用余额不足'));
        }
        Db::startTrans();
        try {
            $remark = lang('余额支付');
            // 记录日志
            MoneyLog::changeMoney($user['id'], $user_info['user_money'], -$order['payable_money'], 2, $remark, $data['order_sn']);
            //商户流程
            $res = \app\common\model\Order::verify($data['order_sn'], 'balance', '', $order['payable_money']);

            if (!$res) {
                exception(lang('支付失败，请稍后再试'));
            }
            if ($order['order_type'] == 12) {
                if ($user_info['score'] < $order['cost_integral']) {
                    exception(lang('积分不足'));
                }
            }
            $order_integral_config = module_config("integral.order_integral") ?? 0;
            $order_integral = intval($order['payable_money'] * $order_integral_config / 100);
            if ($order_integral > 0) {
                ScoreLog::change($order['user_id'], $order_integral, 2);
                $s_res = \app\user\model\User::where('id', $user['id'])->setInc("score", $order_integral);
                if (!$s_res) {
                    exception(lang('增加积分失败'));
                }
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        return ApiReturn::r(1, [], lang('支付成功'));
    }
}
