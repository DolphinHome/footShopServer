<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\common\model\Order as OrderModel;
use app\goods\model\Goods;
use app\goods\model\OrderInfo;
use app\operation\model\SystemMessage;
use app\user\model\Address;
use app\user\model\Marketing;
use app\user\model\MoneyLog;
use app\user\model\ScoreLog;
use app\operation\model\SystemMessage as SystemMessageModel;
use app\goods\model\OrderGoods as OrderGoodsModel;
use app\goods\model\OrderRefund;
use app\user\model\User;
use app\user\model\User as UserModel;
use app\user\service\Money;
use service\ApiReturn;
use think\Db;
use app\goods\model\OrderInvoice;
use app\goods\model\OrderPickup;
use think\facade\Log;
use app\goods\service\Goods as GoodsService;
use app\user\service\User as UserService;

/**
 * 订单接口
 * Class UserLabel
 * @package app\api\controller\v1
 */
class Order extends Base
{
    /*
     *
     * 获取支付方式列表
     *
     */
    public function pay_type()
    {
        $res = Db::name("pay_type_list")->where(['status' => 1])->order("sort desc")->select();
        return ApiReturn::r(1, $res, 'ok');
    }

    /**
     * 创建订单
     * @author 晓风<215628355@qq.com>
     */
    public function add_order($data, $user)
    {
        $order_type = $data['order_type'];
        Db::startTrans();
        try {
            switch ($order_type) {
                case '1':
                    $order = OrderModel::addRechargeOrder($data, $user);
                    break;
                case '2':
                    $order = OrderModel::addRechargeOrder($data, $user);
                    break;
                case '3':
                    $order = OrderModel::addGoodsOrder($data, $user);
                    break;
                case '12'://积分商品
                    $order = OrderModel::addIntegralOrder($data, $user);
                    break;
                case '5'://拼团订单
                    $order = OrderModel::addGroupOrder($data, $user);
                    break;
                case '6'://秒杀订单
                    $order = OrderModel::addSeckillOrder($data, $user);
                    break;
                case '7'://预售订单
                    $order = OrderModel::addPreorderOrder($data, $user);
                    break;
                case '14'://砍价订单
                    $order = OrderModel::addBargainOrder($data, $user);
                    break;
                case '16': // 会员购买订单
                    $order = OrderModel::addUserLevelOrder($data, $user);
                    break;
                default:
                    throw new \Exception(lang('暂不支持该类型订单下单'));
                    break;
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, '', $e->getMessage());
        }
        if ($order) {
            return ApiReturn::r(1, $order, lang('下单成功，请向预支付接口获取支付信息'));
        }
        return ApiReturn::r(0, '', lang('下单失败'));
    }

    /**
     * 查询订单详情
     * @param $data
     * @param $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/23 19:14
     */
    public function get_order_detail($data, $user)
    {
        $order_type = $data['order_type'];

        /*$types = OrderModel::$orderTypes;
        if (!isset($types[$order_type]) && $order_type!=12) {
            return ApiReturn::r(0, '', lang('暂不支持该订单类型'));
        }*/
        $final_type = $order_type;
        if ($order_type == 12 || $order_type == 14) {
            $order_type = 3;
        }
        switch ($order_type) {
            case '1':
                // 如果是充值类型，则只查订单主表即可
                $order = OrderModel::where(['order_sn' => $data['order_sn'], 'user_id' => $user['id']])->find();
                if (!$order) {
                    return ApiReturn::r(0, [], lang('无效订单号'));
                }
                break;
            case '2':
                // 如果是充值类型，则只查订单主表即可
                $order = OrderModel::where(['order_sn' => $data['order_sn'], 'user_id' => $user['id']])->find();
                if (!$order) {
                    return ApiReturn::r(0, [], lang('无效订单号'));
                }
                break;
            case '3':
            case '5':
                $order = OrderModel::where(['order_sn' => $data['order_sn'], 'user_id' => $user['id']])->find();
                $order['activity_type'] = 2;
                $group_id = Db::name('goods_activity_group_user')->where(['order_sn' => $order['order_sn']])->value('group_id');
                $group = Db::name('goods_activity_group')->where(['id' => $group_id])->find();
                $group['create_time'] = date("Y-m-d H:i:s", $group['create_time']);
                $order['group_info'] = $group;

                $refund_day = module_config('goods.refund_day');
                $time = time();
                $refund_max_time = $order['pay_time'] + $refund_day * 24 * 3600;
                $order_receive = module_config('goods.order_receive');
                if ($order['pay_time'] != 0) {
                    $order['order_receive_time'] = $order['pay_time'] + $order_receive * 24 * 3600;
                    $order['pay_time'] = date('Y-m-d H:i:s', $order['pay_time']);
                }
                $order['order_info'] = Db::name('order_goods_info')->get(['order_sn' => $data['order_sn']]);
                /*一个订单多个商品针对一个申请售后，只展示单个商品信息*/
                $map = [];
                $map['order_sn'] = $data['order_sn'];
                if (isset($data['goods_id'])) {
                    $map['goods_id'] = $data['goods_id'];
                }
                $goods_list = Db::name('order_goods_list')->where($map)->select();
                $goods_info = [];
                foreach ($goods_list as $ks => &$item) {
                    $is_wholesale = Goods::where(['id' => $item['goods_id']])->value('is_wholesale');
                    if ($is_wholesale == 1) {
                        $goods_list[$ks]['shop_price'] = $goods_list[$ks]['goods_money'] = bcdiv($item['goods_money'], $item['num'], 2);
                    }
                    if ($item['is_aftersale'] == 1) {
                        $refund_id = Db::name('order_refund')->where(['order_sn' => $item['order_sn'], 'goods_id' => $item['goods_id']])->value('id');
                        if ($refund_id) {
                            $item['refund_id'] = $refund_id;
                        } else {
                            $item['is_aftersale'] = 0;
                        }
                    }
                    $refund = Db::name('order_refund')->where(['order_sn' => $item['order_sn'], 'goods_id' => $item['goods_id']])->order('id desc')->find();
                    if (!is_null($item['sku_id'])) {
                        $refund = Db::name('order_refund')->where(['order_sn' => $item['order_sn'], 'goods_id' => $item['goods_id'], 'sku_id' => $item['sku_id']])->order('id desc')->find();
                        $item['refund_id'] = $refund['id'];
                    }
                    $item['is_refund'] = 1;
                    if ($refund) {
                        $item['refund_info'] = $refund;
                        $item['order_refund_status'] = $refund['status'];
                        $item['is_refund'] = 0;//判断是否售后
                    }
                    $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                    $gmap[] = ['sku_id', '=', $item['sku_id']];
                    $gmap[] = ['goods_id', '=', $item['goods_id']];
                    $gmap[] = ['status', '=', 1];
                    $gmap[] = ['activity_id', '=', $item['activity_id']];
//                    $gcd = Db::name('goods_activity_details')->where($gmap)->find();
//                    $item['sales_integral'] = $gcd['sales_integral'];
//                    $item['is_pure_integral'] = $gcd['is_pure_integral'];
//                    dump($refund);die;
                    if ($refund) {
                        $item['refund_info'] = $refund;
                    }
                    $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                    if ($time > $refund_max_time) {
                        $item['nonrefundable'] = 1;
                    } else {
                        $item['nonrefundable'] = 0;
                    }
                    $item['refund_money'] = bcsub($item['goods_money'], $order['coupon_money'], 2);
                    $goods_info[] = Goods::get($item['goods_id']);
                }
                //积分抵扣显示
                $order['is_integral_reduce'] = 0;
                if ($order['reduce_money'] > 0) {
                    $order['is_integral_reduce'] = 1;
                }

                unset($item);
                $order['order_goods_list'] = $goods_list;
                break;
            case '6':
            case '9':
            case '11':
                // 商城订单
                $order = OrderModel::where(['order_sn' => $data['order_sn'], 'user_id' => $user['id']])->find();
                if (!$order) {
                    return ApiReturn::r(0, [], lang('无效订单号'));
                }
                $refund_day = module_config('goods.refund_day');
                $time = time();
                $refund_max_time = $order['pay_time'] + $refund_day * 24 * 3600;
//                $order['pay_type']=$order['pay_type'];
                $order['pay_time'] = empty($order['pay_time']) ? '' : date('Y-m-d H:i:s', $order['pay_time']);
                $order['order_info'] = Db::name('order_goods_info')->get(['order_sn' => $data['order_sn']]);

                $goods_list = Db::name('order_goods_list')->where(['order_sn' => $data['order_sn']])->select();
                foreach ($goods_list as $ks => &$item) {
                    if ($item['is_aftersale'] == 1) {
                        $item['refund_id'] = Db::name('order_refund')->where(['order_sn' => $item['order_sn'], 'goods_id' => $item['goods_id']])->value('id');
                    }

                    $refund = Db::name('order_refund')->where(['order_sn' => $item['order_sn'], 'goods_id' => $item['goods_id']])->order('id desc')->find();
                    if (!is_null($item['sku_id'])) {
                        $refund = Db::name('order_refund')->where(['order_sn' => $item['order_sn'], 'goods_id' => $item['goods_id'], 'sku_id' => $item['sku_id']])->order('id desc')->find();
                    }
                    $item['is_refund'] = 1;
                    if ($refund) {
                        $item['refund_info'] = $refund;
                        $item['order_refund_status'] = $refund['status'];
                        $item['is_refund'] = 0;//判断是否售后
                    }
                    $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                    $gmap[] = ['sku_id', '=', $item['sku_id']];
                    $gmap[] = ['goods_id', '=', $item['goods_id']];
                    $gmap[] = ['status', '=', 1];
                    $gmap[] = ['activity_id', '=', $item['activity_id']];
                    $gcd = Db::name('goods_activity_details')->where($gmap)->find();
                    $item['sales_integral'] = $gcd['sales_integral'];
                    $item['is_pure_integral'] = $gcd['is_pure_integral'];
                    if ($refund) {
                        $item['refund_info'] = $refund;
                    }
                    $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                    if ($time > $refund_max_time) {
                        $item['nonrefundable'] = 1;
                    } else {
                        $item['nonrefundable'] = 0;
                    }
                }
                unset($item);
                $order['order_goods_list'] = $goods_list;
                break;
            case '7':
                // 商城订单-预售
                $order = OrderModel::where(['order_sn' => $data['order_sn'], 'user_id' => $user['id']])->find();

                if (!$order) {
                    return ApiReturn::r(0, [], lang('无效订单号'));
                }
//                $order['pay_type']=$order['pay_type'];
                $order['pay_time'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '';
                $order['order_info'] = Db::name('order_goods_info')->get(['order_sn' => $data['order_sn']]);
                $goods_list = Db::name('order_goods_list')->where(['order_sn' => $data['order_sn']])->select();
                $gmap = [];
                foreach ($goods_list as &$item) {
                    $refund = Db::name('order_refund')->where(['order_sn' => $item['order_sn'], 'goods_id' => $item['goods_id']])->order('id desc')->find();
                    $item['is_refund'] = 1;
                    if ($refund) {
                        $item['is_refund'] = 0;
                        $item['refund_id'] = $refund['id'];
                        $item['refund_info'] = $refund;
                        $item['order_refund_status'] = $refund['status'];
                    }
                    $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                    $gmap[] = ['sku_id', '=', $item['sku_id']];
                    $gmap[] = ['goods_id', '=', $item['goods_id']];
                    $gmap[] = ['status', '=', 1];
                    $gmap[] = ['activity_id', '=', $item['activity_id']];
                    $gcd = Db::name('goods_activity_details')->where($gmap)->find();
                }
                $order['order_goods_list'] = $goods_list;
                $final_order_sn = Db::name('order_relation')->where(['book_order_sn' => $order['order_sn']])->value('final_order_sn');
                $final_order = Db::name('order')->where(['order_sn' => $final_order_sn])->find();
                $order['final_order'] = $final_order;
                $order_receive = module_config('goods.order_receive');
                if ($final_order['pay_time'] != 0) {
                    $order['order_receive_time'] = $final_order['pay_time'] + $order_receive * 24 * 3600;
                    $order['pay_time'] = date('Y-m-d H:i:s', $final_order['pay_time']);
                }
                // 获取关联商品
                $order_goods = Db::name('order_goods_list')->where(['order_sn' => $order['order_sn']])->find();
                // 获取活动信息
                $activity_goods = Db::name('goods_activity_details')->where(['goods_id' => $order_goods['goods_id'], 'sku_id' => $order_goods['sku_id'], 'activity_id' => $order_goods['activity_id']])->find();
                $activity = Db::name('goods_activity')->where(['id' => $activity_goods['activity_id']])->field('sdate,edate,presell_stime,presell_etime')->find();
                $time = time();
                if ($time < $activity['presell_etime'] && $time > $activity['presell_stime']) {
                    $activity['is_pay'] = 1;
                } else {
                    $activity['is_pay'] = 0;
                }
                $order['activity_time'] = $activity;
                if ($final_order['status'] == 1 && $final_order['pay_status'] == 1) {
                    $order['final_status'] = 1;
                    $order['real_money'] = $order['real_money'] + $final_order['real_money'];
                    $order['payable_money'] = $order['payable_money'] + $final_order['payable_money'];
                } else {
                    $order['final_status'] = 0;
                    $order['payable_money'] = $gcd['deposit'];
                }
                break;
            case '8':
                $order = OrderModel::where(['order_sn' => $data['order_sn'], 'user_id' => $user['id']])->find();
                if (!$order) {
                    return ApiReturn::r(0, [], lang('无效订单号'));
                }
                $order['pay_time'] = date('Y-m-d H:i:s', $order['pay_time']);
//                $order['pay_type']=$order['pay_type'];
                $goods_list = Db::name('order_goods_list')->where(['order_sn' => $data['order_sn']])->select();
                $gmap = [];
                foreach ($goods_list as &$item) {
                    $refund = Db::name('order_refund')->where(['order_sn' => $item['order_sn'], 'goods_id' => $item['goods_id']])->order('id desc')->find();
                    $item['is_refund'] = 1;
                    if ($refund) {
                        $item['is_refund'] = 0;
                        $item['refund_id'] = $refund['id'];
                        $item['refund_info'] = $refund;
                        $item['order_refund_status'] = $refund['status'];
                    }
                    $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                    $gmap[] = ['sku_id', '=', $item['sku_id']];
                    $gmap[] = ['goods_id', '=', $item['goods_id']];
                    $gmap[] = ['status', '=', 1];
                    $gmap[] = ['activity_id', '=', $item['activity_id']];
                    $gcd = Db::name('goods_activity_details')->where($gmap)->find();
                }
                $order['order_goods_list'] = $goods_list;
                break;
            case '12':
                $orderGood = OrderGoodsModel::where(['order_sn' => $data['order_sn'], 'goods_id' => $data['goods_id']])->find();
                $orderGood['goods_thumb'] = get_file_url($orderGood['goods_thumb']);
                return ApiReturn::r(1, $orderGood, lang('请求成功'));
                break;
            case '16':
                // 如果是购买会员卡类型，则只查订单主表即可
                $order = OrderModel::where(['order_sn' => $data['order_sn'], 'user_id' => $user['id']])->find();
                if (!$order) {
                    return ApiReturn::r(0, [], lang('无效订单号'));
                }
                $order['order_goods_list'] = '';
                break;
            default:
                break;
        }
        $hour = 6;

        $is_remind = Db::name('order_remind')->where([['order_sn', '=', $data['order_sn'], ['create_time', 'gt', (time() - $hour * 3600)]]])->find();
        $order['is_remind'] = $is_remind ? ['info' => 1, 'hour' => $hour] : ['info' => 0, 'hour' => $hour];
        /*如果是预售尾款，订单的结束支付时间为预售设置的结束时间，其他订单的结束支付时间为后台设置的到期时间*/
        if ($data['order_type'] == 8) {
            $book_order_sn = Db::name('order_relation')->where(['final_order_sn' => $order['order_sn']])->value('book_order_sn');
            $order_goods = Db::name('order_goods_list')->where(['order_sn' => $book_order_sn])->find();
            // 获取活动信息
            $activity_goods = Db::name('goods_activity_details')->where(['goods_id' => $order_goods['goods_id'], 'sku_id' => $order_goods['sku_id'], 'activity_id' => $order_goods['activity_id']])->find();

            $order['cancel_time'] = Db::name('goods_activity')->where(['id' => $activity_goods['activity_id']])->value('presell_etime');
        } else {
            $order['cancel_time'] = bcadd(module_config('goods.order_timeout') * 60, strtotime($order['create_time']));
        }
        $order['refund_day'] = $refund_day;
        $is_sale = 1;
        if (count($order['order_goods_list']) > 0) {
            foreach ($order['order_goods_list'] as $ko => $vo) {
                $is_sale = Db::name("goods")->where("id", $vo['goods_id'])->value('is_sale');
                if ($is_sale != 1) {
                    break;
                }
            }
        }
        //2022、3、25 订单退款修改
        $order_refund_count = Db::name('order_refund')->where(['order_sn' => $order['order_sn']])->count();
        $order_goods_count = Db::name('order_goods_list')->where(['order_sn' => $order['order_sn']])->count();

        $refund_status = Db::name('order_refund')->where(['order_sn' => $order['order_sn']])->where('status','in',[1,2])->find();
        if($refund_status){
            $order['status'] = 6;
        }
        $refund_finish_status = Db::name('order_refund')->where(['order_sn' => $order['order_sn']])->where('status','=',3)->count();
        if($refund_finish_status == $order_goods_count){
            $order['status'] = 7;
        }

        $is_has_aftersale = Db::name('order_goods_list')->where(['order_sn' => $data['order_sn'], 'is_aftersale' => 0])->find();
        if (!$is_has_aftersale) {
            $order_refund_status = Db::name('order_refund')->where(['order_sn' => $data['order_sn'], 'status' => 3])->find();
            if ($order_refund_status) {
                $order['order_refund_status'] = 1;  //不显示申请发票按钮
            } else {
                $order['order_refund_status'] = 0;  //显示申请发票按钮
            }
        } else {
            $order['order_refund_status'] = 0;  //显示申请发票按钮
        }

        $order['goods_is_sale'] = $is_sale;

        //自提类型获取自提点信息，add by wangph at 2021-4-19
        if ($order['send_type'] == 1) {
            $order['pick_info'] = OrderPickup::getOrderPickUp($data['order_sn']);
            if (empty($order['order_info'])) {
                //自提备注替换order_goods_info表的remark,因为自提没写入order_goods_info表
                $order_info = [
                    'remark' => $order['pick_info']['remark']
                ];
                $order->order_info = $order_info;
            }
        }
        $order_goods_count = Db::name('order_goods_list')->where('order_sn',$order['order_sn'])->count();
        if($order_goods_count == 1){
            if($order['status'] == 1){
                $order['is_contain'] = 1;
            }else{
                $order['is_contain'] = 0;
            }
        }else{
            $order['is_contain'] = 0;
        }

        if ($order) {
            $order['order_money'] = $order['order_money']-$order['order_info']['express_price'];
            return ApiReturn::r(1, $order, lang('请求成功'));
        }
        return ApiReturn::r(0, [], lang('无效订单号'));
    }

    //二维数组根据某字段去重
    public function array_unset($arr, $key)
    {
        $res = array();
        foreach ($arr as $value) {
            if (isset($res[$value[$key]])) {
                unset($value[$key]);
            } else {
                $res[$value[$key]] = $value;
            }
        }
        return $res;
    }

    /**
     * 获取订单列表
     * @param $data
     * @param $user
     */
    public function get_list($data, $user)
    {
        $user_id = $user['id'];
        $type = $data['type'] ? $data['type'] : 'all';
        $map = [];
        switch ($type) {
            case 'unpay':
                $map[] = ['g.status', '=', 0];
                break;
            case 'unship':
                $map[] = ['g.status', '=', 1];
                break;
            case 'unreceive':
                $map[] = ['g.status', '=', 2];
                break;
            case 'finish':
                $map[] = ['g.status', '=', 3];
                break;
            case 'evaluate':
                $map[] = ['g.status', '=', 4];
                break;
            case 'cancel':
                $map[] = ['g.status', '=', -1];
                break;
            default:
                if ($data['order_type'] != 12) {
                    $map[] = ['g.status', 'egt', -1];
                }
                break;
        }
        if ($data['order_type'] != 12) {
            $map[] = ['g.order_type', 'in', '3,5,6,7,9,10,11,14'];
        } elseif ($data['order_type'] == 12) {
            $map[] = ['g.order_type', '=', '12'];
        }

        if ($data['search_name']) {
            $map[] = ['g.order_sn|ogi.receiver_name|ogl.goods_name', 'like', '%' . $data['search_name'] . '%'];
        }
        $map[] = ['g.user_id', '=', $user_id];
        $map[] = ['g.is_delete', '=', 0];
        $map[] = ['g.status', 'neq', -2];
        $order = OrderModel::alias("g")
            ->join("order_goods_info ogi", 'g.order_sn = ogi.order_sn', 'left')
            ->join("order_goods_list ogl", 'g.order_sn = ogl.order_sn', 'left')
            ->field('g.order_sn,g.cost_integral,g.send_type,status,payable_money,real_money,order_type,g.create_time,invoice_status,order_money,reduce_money,integral_reduce')
            ->where($map)
            ->order('g.aid desc')
            ->group('g.order_sn')
            ->paginate();

        $total = 0;
        if (count($order) > 0) {
            $order_arr = $order->toArray();
            $order = $order_arr['data'];
            $total = $order_arr['total'];
        } else {
            $order = [];
        }

        foreach ($order as $k => $item) {
            switch ($item['order_type']) {
                case 3:
                case 4:
                    $order[$k]['activity_type'] = 0;
                    break;
                case 5://拼团订单
                    $order[$k]['activity_type'] = 2;
                    $group_id = Db::name('goods_activity_group_user')->where(['order_sn' => $item['order_sn']])->value('group_id');
                    $group = Db::name('goods_activity_group')->where(['id' => $group_id])->find();
                    $order[$k]['group_info'] = $group;
                    break;
                case 6://秒杀订单
                    $order[$k]['activity_type'] = 1;
                    break;
                case 7://预售订单
                    $order[$k]['activity_type'] = 3;
                    $final_order_sn = Db::name('order_relation')->where(['book_order_sn' => $item['order_sn']])->value('final_order_sn');
                    $final_order = Db::name('order')->where(['order_sn' => $final_order_sn])->find();
                    $order[$k]['final_order'] = $final_order;
                    // 获取关联商品
                    $order_goods = Db::name('order_goods_list')->where(['order_sn' => $item['order_sn']])->find();
                    // 获取活动信息
                    $activity_goods = Db::name('goods_activity_details')->where(['goods_id' => $order_goods['goods_id'], 'sku_id' => $order_goods['sku_id'], 'activity_id' => $order_goods['activity_id']])->find();
                    $activity = Db::name('goods_activity')->where(['id' => $activity_goods['activity_id']])->field('sdate,edate,presell_stime,presell_etime')->find();
                    $time = time();
                    if ($time < $activity['presell_etime'] && $time > $activity['presell_stime']) {
                        $activity['is_pay'] = 1;
                    } else {
                        $activity['is_pay'] = 0;
                    }
                    $order[$k]['activity_time'] = $activity;
                    if ($final_order['status'] == 1 && $final_order['pay_status'] == 1) {
                        $order[$k]['final_status'] = 1;
                        $order[$k]['real_money'] = $order[$k]['real_money'] + $final_order['real_money'];
                        $order[$k]['payable_money'] = $order[$k]['payable_money'];
                    } else {
                        $order[$k]['final_status'] = 0;
                    }
                    break;
                case 9://折扣订单
                    $order[$k]['activity_type'] = 4;
                    break;
                case 10://砍价订单
                    $order[$k]['activity_type'] = 5;
                    break;
                case 11://限购 订单
                    $order[$k]['activity_type'] = 6;
                    break;
                case 12://积分商品 订单
                    $order[$k]['activity_type'] = 8;
                    break;
            }
            $hour = 6;
            $goods = Db::name('order_goods_list')->where(['order_sn' => $item['order_sn']])->select();
            $is_remind = Db::name('order_remind')->where([['order_sn', '=', $item['order_sn'], ['create_time', 'gt', (time() - $hour * 3600)]]])->find();
            $order[$k]['is_remind'] = $is_remind ? ['info' => 1, 'hour' => $hour] : ['info' => 0, 'hour' => $hour];
            $i = 0;
            $goods_info = [];
            foreach ($goods as $ks => $g) {
                $is_wholesale = Goods::where(['id' => $g['goods_id']])->value('is_wholesale');
                if ($is_wholesale == 1) {
                    $goods[$ks]['shop_price'] = $goods[$ks]['goods_money'] = bcdiv($g['goods_money'], $g['num'], 2);
                }
//                $act_where = [];
                // 获取积分活动信息
//                $activity_id = Db::name('goods_activity')->where(['type'=>8,'status'=>1])->value("id") ?? 0;
//                $act_where[] = ['goods_id','=',$g['goods_id']];
//                $act_where[] = ['sku_id','=',$g['sku_id']];
//                $act_where[] = ['activity_id','=',$activity_id];
//                $activity_goods = Db::name('goods_activity_details')->where($act_where)->find();

//                $order[$k]['sales_integral'] = $activity_goods['sales_integral']*$g['num'];
//                $order[$k]['is_pure_integral'] = $activity_goods['is_pure_integral'];
//                $goods[$ks]['sales_integral'] = $activity_goods['sales_integral'];
//                $goods[$ks]['is_pure_integral'] = $activity_goods['is_pure_integral'];

                $order[$k]['sales_integral'] = $order[$k]['cost_integral'];
                $order[$k]['is_pure_integral'] = $g['is_pure_integral'];

                $goods[$ks]['sales_integral'] = $g['sales_integral'];
                $goods[$ks]['is_pure_integral'] = $g['is_pure_integral'];

                $goods[$ks]['goods_thumb'] = get_file_url($g['goods_thumb']);

                $arr = Db::name("order_refund")
                    ->order('create_time desc')
                    ->where([
                        'order_sn' => $item['order_sn'],
                        'goods_id' => $goods[$ks]['goods_id'],
                        'sku_id' => $goods[$ks]['sku_id']
                    ])
                    ->find();

                if ($arr) {
                    $goods[$ks]['order_refund_status'] = $arr['status'];
                    if (!in_array($arr['status'], [0, -2])) {
                        $i++;
                    }
                    /**如果该订单有售后，重新给订单状态赋值**/
//                    if($arr['status'] == 1 || $arr['status'] ==2){
//                        $order[$k]['status'] = 6;   //售后进行中
//                    }
//                    if($arr['status'] == 3){
//                        $order[$k]['status'] = 7;   //售后已完成
//                    }
                }


                $goods_info[] = Goods::where(['id' => $g['goods_id']])->find();
            }
            $order[$k]['goods'] = $goods;
            $order[$k]['cancel_time'] = bcadd(module_config('goods.order_timeout') * 60, strtotime($item['create_time']));
            if ($item['status'] == 6) {
                $order[$k]['refund_info'] = Db::name('order_refund')->where(['order_sn' => $item['order_sn']])->find();
            }
            //2022、3、25 订单退款修改
            $order_refund_count = Db::name('order_refund')->where(['order_sn' => $item['order_sn']])->count();
            $order_goods_count = Db::name('order_goods_list')->where(['order_sn' => $item['order_sn']])->count();

            $refund_status = Db::name('order_refund')->where(['order_sn' => $item['order_sn']])->where('status','in',[1,2])->find();
            if($refund_status){
                $order[$k]['status'] = 6;
            }
            $refund_finish_status = Db::name('order_refund')->where(['order_sn' => $item['order_sn']])->where('status','=',3)->count();
            if($refund_finish_status == $order_goods_count){
                $order[$k]['status'] = 7;
            }


            $is_has_aftersale = Db::name('order_goods_list')->where(['order_sn' => $item['order_sn'], 'is_aftersale' => 0])->find();
            if (!$is_has_aftersale) {
                $order_refund_status = Db::name('order_refund')->where(['order_sn' => $item['order_sn'], 'status' => 3])->find();
                if ($order_refund_status) {
                    $order[$k]['order_refund_status'] = 1;  //不显示申请发票按钮
                } else {
                    $order[$k]['order_refund_status'] = 0;  //显示申请发票按钮
                }
            } else {
                $order[$k]['order_refund_status'] = 0;  //显示申请发票按钮
            }

            //chen add
            $order[$k]['is_integral_reduce'] = 0;
            if ($order[$k]['reduce_money'] > 0) {
                $order[$k]['is_integral_reduce'] = 1;
            }
        }
        return ApiReturn::r(1, ['total' => $total, 'list' => $order], lang('订单列表'));
    }

    /**
     * 提醒发货
     * @param $data
     * @param $user
     */
    public function remind_order($data, $user)
    {

        // order_status = 1
        $order_sn = $data['order_sn'];
        // 判断订单是否可提醒
        $order = \think\Db::name('order')->where('order_sn', $order_sn)->where('status', 1)->find();
        if (!$order) {
            return ApiReturn::r(0, '', lang('已发货，请刷新订单'));
        }
        // 判断是否已提醒
        $remind = \think\Db::name('order_remind')->where('order_sn', $order_sn)->where('user_id', $user['id'])->find();
        if ($remind) {
            return ApiReturn::r(0, '', lang('已提醒'));
        }

        $idata = [
            'order_sn' => $data['order_sn'],
            'user_id' => $user['id'],
            'create_time' => time()
        ];
        $rs = \think\Db::name('order_remind')->insert($idata);
        if ($rs) {
            // 通知后端pc
            return ApiReturn::r(1, '', lang('提醒成功'));
        } else {
            return ApiReturn::r(0, '', lang('提醒失败'));
        }
    }

    /**
     * 确认收货
     * @param $data
     * @param $user
     */
    public function receive_order($data, $user)
    {
        // order_status = 2 => 3
        $order_sn = $data['order_sn'];
        $integral = 0;
        Db::startTrans();
        try {
            $order = Db::name('order')->where('order_sn', $order_sn)->where('status', 2)->find();
            if (!$order) {
                exception(lang('订单不可操作，请刷新'));
            }
            //变更订单状态
            $order_status = Db::name('order')->where('aid', $order['aid'])->update(['status' => 3]);
            if (!$order_status) {
                exception(lang('订单状态变更失败'));
            }

            //自提类型订单不需要物流, wangph 修改于 2021-4-22
            if ($order['send_type'] != 1) {
                //变更订单物流信息表
                $express = Db::name('order_goods_express')->where('order_sn', $order_sn)->update(['receive_time' => time()]);
                if (!$express) {
                    exception(lang('订单物流信息更新失败'));
                }
            }

            //变更订单商品表
            Db::name('order_goods_list')->where('order_sn', $order_sn)->update(['order_status' => 3]);
            // zhougs  2020年12月15日16:50:09
            $order_goods = Db::name('order_goods_list')->where('order_sn', $order_sn)->select();
            $user = Db::name('user')->get($user['id']);
            $empirical = 0;
            $msg = new SystemMessageModel();
            foreach ($order_goods as $v) {
                $freeze_money_log = Db::name('user_freeze_money_log')->where([
                    'order_no' => $order_sn,
                    'is_delete' => 0,
                    'goods_id' => $v['goods_id'],
                    'sku_id' => $v['sku_id']
                ])->find();

                $is_refund = Db::name('order_refund')->where([
                    'order_sn' => $data['order_sn'],
                    'goods_id' => $v['goods_id'],
                    'sku_id' => $v['sku_id']
                ])->find();
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
                        exception(lang('自购返转为正常金额失败'));
                    }
                    $ms = ['type' => 1, 'msg_type' => 1, 'template_type' => 1, 'to_user_id' => $user['id'], 'title' => lang('系统通知'), 'content' => lang('恭喜你获得返现金额') . $earnings . '元,请在个人中心返佣资产查收。'];
                    $msg->create($ms);
                    if ($v['share_sign']) {
                        //冻结的分享赚转为正常金额
                        $share_uid = Db::name('goods_share')->where(['share_sign' => $v['share_sign']])->find();//获取分享链接
                        if ($share_uid['uid'] != $user['id']) {
                            $share_user = Db::name('user')->get($share_uid['uid']);//获取分享人的用户信息
                            $money_log = Money::money_update($share_uid['uid'], $share_user['share_money'], $earnings, 8, $order_sn, $v['goods_id'], $v['sku_id']);
                            if ($money_log['code'] == 0) {
                                exception(lang('分享赚转为正常金额失败'));
                            }
                            $ms = ['type' => 1, 'msg_type' => 1, 'template_type' => 1, 'to_user_id' => $share_uid['uid'], 'title' => lang('系统通知'), 'content' => lang('用户') . $user['user_nickname'] . lang('购买了您分享的商品，您获得金额为') . $earnings . "，七日后到您余额。"];
                            $msg->create($ms);
                        }
                    }
                }
                //分销佣金
//                $commission = GoodsService::goods_commission($v['goods_id']);
//                if ($commission['first_profit'] > 0 && $commission['second_profit'] > 0) {
//                    //上级
//                    $first_id = UserService::parent_dis_id($user['id']);
//                    if ($first_id) {
//                        $user_commission = User::where(['id' => $first_id])->value("commission");
//                        $first_profit = Money::money_update($first_id, $user_commission, $commission['first_profit'], 10, $order_sn, $v['goods_id'], $v['sku_id']);
//                        if ($first_profit['code'] == 0) {
//                            exception(lang('分销佣金处理异常'));
//                        }
//                        //上上级
//                        $second_id = UserService::parent_dis_id($first_id);
//                        if ($second_id) {
//                            $user_commission = User::where(['id' => $second_id])->value("commission");
//                            $second_profit = Money::money_update($second_id, $user_commission, $commission['first_profit'], 10, $order_sn, $v['goods_id'], $v['sku_id']);
//                            if ($second_profit['code'] == 0) {
//                                exception(lang('分销佣金处理异常'));
//                            }
//                        }
//                    }
//                }
            }
//            if ($integral > 0) {
//                $score_log = ScoreLog::change($user['id'], $integral, 2, lang('购买商品增加积分'), $order_sn);
//                if (!$score_log) {
//                    exception(lang('积分变更失败'));
//                }
//            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, '', $e->getMessage());
        }
        return ApiReturn::r(1, '', lang('确认成功'));
    }


    /**
     * 订单评价
     * @param $data
     * @param $user
     */
    public function comment($data, $user)
    {

        $order_sn = $data['order_sn'];
        $comment = $data;
        $goods_comment = json_decode($data['goods_comment'], true);
        Db::startTrans();
        try {
            if ($goods_comment) {
                $order_goods_num = Db::name('order_goods_list')->where(['order_sn' => $order_sn, 'order_status' => 3])->count();
                $new_goods_comment = [];
                foreach ($goods_comment as $k => $v) {
                    $new_goods_comment[$k]['order_sn'] = $order_sn;
                    $new_goods_comment[$k]['user_id'] = $user['id'];
                    $new_goods_comment[$k]['type'] = $v['type'];
                    $new_goods_comment[$k]['thumb'] = implode($goods_comment[$k]['thumb'], ',');
                    $new_goods_comment[$k]['video'] = $goods_comment[$k]['video'];
                    $new_goods_comment[$k]['create_time'] = time();
                    $new_goods_comment[$k]['star'] = $v['star'];
                    $new_goods_comment[$k]['goods_id'] = $v['goods_id'];
                    $new_goods_comment[$k]['sku_id'] = $v['sku_id'] ?? 0;
                    $new_goods_comment[$k]['pid'] = empty($v['pid']) ? 0 : $v['pid'];
                    $integral = $v['star'];
                    $new_goods_comment[$k]['content'] = $v['content'];
                    Db::name('order_goods_list')->where([
                        'order_sn' => $order_sn,
                        'goods_id' => $v['goods_id'],
                        'sku_id' => $v['sku_id'],
                    ])->update(['order_status' => 4, 'is_evaluate' => 1]);
                    Db::name("goods")->where(['id' => $v['goods_id']])->setInc('comment_count');
                    // 先注释 功能模块开启后使用
                    // ScoreLog::change($user['id'],$integral,3,'评论商品增加积分',$order_sn);
                }
                $order_goods_comment_num = Db::name('order_goods_list')->where(['order_sn' => $order_sn, 'order_status' => 4])->count();
                if ($order_goods_comment_num == $order_goods_num) {
                    Db::name('order')->where('order_sn', $order_sn)->where('status', 3)->update(['status' => 4]);
                }
                $all_data = $new_goods_comment;
            } else {
                $order = Db::name('order')->where('order_sn', $order_sn)->where('status', 3)->find();
                if (!$order) {
                    exception(lang('订单不可操作，请刷新'));
                }
                Db::name('order')->where('order_sn', $order_sn)->update(['status' => 4]);
                Db::name('order_goods_list')->where('order_sn', $order_sn)->update(['order_status' => 4, 'is_evaluate' => 1]);
                $goods_list = Db::name('order_goods_list')->where('order_sn', $order_sn)->select();
                foreach ($goods_list as $item) {
                    $comment['create_time'] = time();
                    $comment['goods_id'] = $item['goods_id'];
                    $comment['sku_id'] = $item['sku_id'];
                    $comment['user_id'] = $user['id'];
                    $comment['type'] = $data['type'];
                    $all_data[] = $comment;
                }
            }
            Db::name('goods_comment')->insertAll($all_data);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, '', $e->getMessage());
        }
        if ($goods_comment) {
            $data['integral'] = $integral;
            return ApiReturn::r(1, $data, lang('评论成功'));
        } else {
            return ApiReturn::r(1, '', lang('评论成功'));
        }
    }

    /**
     * 退单申请
     * @param $data
     * @param $user
     * @editor zhougs
     * @updateTime 2020年10月31日09:46:33
     */
    public function refund_apply($data, $user)
    {
        // order_status = 4 => 5
        // 获取无理由退货时间
        $refundDay = module_config('goods.refund_day') ?: 7;
        $order_sn = $data['order_sn'];
        $refund_status = OrderRefund::where([
            'order_sn' => $data['order_sn'],
            'goods_id' => $data['goods_id'],
            'sku_id' => $data['sku_id']
        ])->value("status");

        if (!empty($data['id'])) {
            if(empty($data['refund_picture'])){
                $data['refund_picture'] = '';
            }
            $data['status'] = 0;
            $data['update_time'] = time();
            $res = OrderRefund::where([
                'id' => $data['id'],
            ])->update($data);
            if($res){
                return ApiReturn::r(1, [], '更改成功');
            }else{
                return ApiReturn::r(0, [], '更改失败');
            }

        }
        $order_status = Db::name('order')->where('order_sn', $order_sn)->find();
        // $res = Db::name('goods')->where('id',$data['goods_id'])->find();
        if ($order_status['pay_time'] <= time() - 3600 * 24 * $refundDay) {
            return ApiReturn::r(0, '', lang('操作失败，自支付起') . $refundDay . lang('天之后不可退货'));
        }
        /*        if($order_status['status']==2){
                    return ApiReturn::r(0, '', '操作失败,请先确认收货才可以申请售后退款');
                }*/
        //        if($order_status['status'] ==3 && $order_status['status'] ==4){
        //            if($res['discounts']>0){
        //                if($user['user_money']<$res['discounts']){
        //                    return ApiReturn::r(0, '', '操作失败,此用户账户余额不足以扣除自购反金额');
        //                }
        //            }
        //        }
        //        $res = $this->validate($data, 'goods/Refund');
        //        if (true !== $res) return ApiReturn::r(0, '', lang('参数有误'));
        $refund = $data;
        Db::startTrans();
        try {
            $order = Db::name('order')->where('order_sn', $order_sn)->where('status', 'gt', 0)->find();
            if (!$order) {
                exception(lang('订单不可操作，请刷新'));
            }

            //暂时用户取消不可以再申请
            /*$find = Db::name("order_refund")->where([
                'order_sn' => $order_sn,
                'goods_id' => $data['goods_id'],
                'sku_id'   => $data['sku_id'],
                'status' => '-2'
            ])->find();
            if ($find) {
                return ApiReturn::r(0, [], lang('已取消不可以再次申请'));
            }*/
            $order_goods_count = Db::name('order_goods_list')->where('order_sn',$order_sn)->count();
            if($order_goods_count == 1){
                if($order_status['status'] == 1){
                    $refund['refund_money'] = $order_status['real_money'];
                }else{
                    $refund['refund_money'] = $data['goods_money'];
                }
            }else{
                $refund['refund_money'] = $data['goods_money'];
            }
            $refund['goods_money'] = $data['goods_money'];
            $refund['sku_id'] = $data['sku_id'];
            $refund['goods_id'] = $data['goods_id'];
            $refund['user_id'] = $order['user_id'];
            $refund['num'] = $data['num'];
            $refund['create_time'] = time();
            $refund['server_no'] = 'S' . date('Ymd') . rand(1000, 9999);
            Db::name('order_refund')->insert($refund);
            Db::name('order_goods_list')->where(['order_sn' => $order_sn, 'goods_id' => $data['goods_id'], 'sku_id' => $data['sku_id']])->update(['is_aftersale' => 1]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, '' . $e->getMessage());
        }

        return ApiReturn::r(1, '', lang('申请成功'));
    }

    /**
     * 取消订单
     * @param $data
     * @param $user
     */
    public function cancel_order($data, $user)
    {
        // order_status = 0 => -1
        $order_sn = $data['order_sn'];
        // 软删除
        Db::startTrans();
        try {
            $order = OrderModel::where('order_sn', $order_sn)->where('status', 0)->lock(true)->find();
            if (!$order) {
                exception(lang('订单不可操作，请刷新'));
            }
            OrderModel::where('aid', $order['aid'])->update(['status' => -1, 'return_id' => $data['return_id']]);
            OrderGoodsModel::where('order_sn', $order_sn)->setField('order_status', -1);
            $order_goods_list = Db::name('order_goods_list')->where('order_sn', $order_sn)->field('goods_id,sku_id,num,activity_id')->select();
            $goodsku = new \app\goods\model\GoodsSku();
            $goodinfo = new \app\goods\model\Goods();
            foreach ($order_goods_list as &$val) {
                $where = $where1 = [];//修复条件BUG 2021年3月1日16:12:35
                if ($val['sku_id'] != 0) {
                    // 加sku库存
                    $where[] = ['sku_id', '=', $val['sku_id']];
                    $goodsku->where($where)->setInc('stock', $val['num']);
                    //减销量
                    if ($goodsku->where(['sku_id' => $val['sku_id']])->value('sales_num') >= $val['num']) {
                        $goodsku->where(['sku_id' => $val['sku_id']])->setDec('sales_num', $val['num']);
                    }

                } else {
                    // 加主商品库存
                    $where1[] = ['id', '=', $val['goods_id']];
                    $goodinfo->where($where1)->setInc('stock', $val['num']);
                }
                //减主表销量
                //减销量
                if (Db::name('goods')->where('id', $val['goods_id'])->value('sales_sum') >= $val['num']) {
                    Db::name('goods')->where('id', $val['goods_id'])->setDec('sales_sum', $val['num']); //减主表销量
                }

                //减活动商品销量
                if ($val['activity_id']) {
                    $act_sales_sum = Db::name("goods_activity_details")->where([
                        'goods_id' => $val['goods_id'],
                        'sku_id' => $val['sku_id'],
                        'activity_id' => $val['activity_id']
                    ])->value('sales_sum');
                    if ($act_sales_sum >= $val['num']) {
                        Db::name("goods_activity_details")->where([
                            'goods_id' => $val['goods_id'],
                            'sku_id' => $val['sku_id'],
                            'activity_id' => $val['activity_id']
                        ])->setDec("sales_sum", $val['num']);
                    }
                }
                //加活动商品库存
                Db::name("goods_activity_details")->where([
                    'goods_id' => $val['goods_id'],
                    'sku_id' => $val['sku_id'],
                    'activity_id' => $val['activity_id']
                ])->setInc("stock", $val['num']);
            }
            if ($order['coupon_id']) {
                Db::name('operation_coupon_record')->where('id', $order['coupon_id'])->setField('status', 1);
            }


            $user_info = User::get($user['id']);
            //写入金额变更日志
            if ($order['real_balance'] != 0) {
                $after_money = \app\user\model\MoneyLog::changeMoney($user['id'], $user_info['user_money'], $order['real_balance'], 11, '退款余额', $order['order_sn']);
            }

            //退回余额
            $res = Db::name('user')->where('id', $user['id'])->setDec("user_money", $order['real_balance']);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, '', $order_sn . 'whart' . $e->getMessage());
        }
        return ApiReturn::r(1, '', lang('订单取消成功'));
    }

    /**
     * 删除订单
     * @param $data
     * @param $user
     */
    public function remove_order($data, $user)
    {
        // order_status = 3,4,6 => -2
        $order_sn = $data['order_sn'];

        // 软删除
        Db::startTrans();
        try {
            $order = Db::name('order')->where('order_sn', $order_sn)->where(['status' => [-1, 0, 3, 4, 6]])->lock(true)->find();
            if (!$order) {
                exception(lang('订单不可操作，请刷新'));
            }
            Db::name('order')->where('aid', $order['aid'])->setField('is_delete', 1);
            Db::name('order_goods_list')->where('order_sn', $order_sn)->setField('order_status', -2);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, '', $order_sn . 'whart' . $e->getMessage());
        }
        return ApiReturn::r(1, '', lang('删除成功'));
    }

    /**
     * 查看物流
     */
    public function express($data, $user)
    {
        $order_sn = $data['order_sn'];
        $server_no = $data["server_no"];
        if ($server_no) {
            $express = \think\Db::name('order_refund')->where('server_no', $server_no)->find();
        } else {
            $express = \think\Db::name('order_goods_express')->where('order_sn', $order_sn)->find();
        }

        if ($express) {
            $express_company = Db::name('goods_express_company')->get($express['express_company_id']);
            $ex = addons_action('ExpressBird/Api/getOrderTracesByJson', [$express['order_sn'], $express_company["express_no"], $express["express_no"]]);
            Log::write('物流信息查询' . $ex);
            $ex = json_decode($ex, true);

            /*if (!$ex['Success']) {
                return ApiReturn::r(0, [], '物流信息查询失败');
            }*/
            if (isset($ex['ShipperCode'])) {
                $goods_express_company_data = Db::name("goods_express_company")->where([
                    'express_no' => $ex['ShipperCode']
                ])->field('name,logo')->find();
                $ex['ShipperCode'] = $goods_express_company_data['name'];
                $ex['logo'] = get_file_url($goods_express_company_data['logo']);
            }
            return ApiReturn::r(1, $ex, lang('物流信息'));
        } else {
            return ApiReturn::r(0, '', lang('没有物流数据'));
        }
    }

    /**
     * 再来一单-添加购物车
     * @param string $requests .order_sn 原订单号[必须]
     * @param string $requests .failure 检测商品是否失效[非必须|1检测0不检测直接排除无效商品]
     * @return \think\response\Json
     * @since 2020年9月1日12:00:49
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function reorder($requests, $user)
    {
        // 接收再来一单参数
        $order_sn = $requests['order_sn'];

        // 查询订单信息
        $order = DB::name("order")->where(['order_sn' => $order_sn])->find();
        if (empty($order)) {
            return ApiReturn::r(0, [], '查无此单,请重新下单购买');
        }
        $orderGoodsList = DB::name("order_goods_list gl")
            ->field("
                gl.goods_id,gl.goods_name,gl.num,gl.sku_id,gl.sku_name,
                g.id,g.name,g.thumb,g.status,g.is_sale,g.is_delete,g.stock,g.shop_price,g.member_price
            ")
            ->join('goods g', 'gl.goods_id=g.id', 'left')
            ->where('order_sn', $order_sn)
            ->select();

        // 检测无效商品信息
        $failureGoodsList = []; // 无效商品信息
        if ($requests['failure'] == 1) {
            foreach ($orderGoodsList as $val) {
                // 如果商品不存在或状态不可用或售完或删除
                if (!$val["id"] || $val["status"] != 1 || $val["is_sale"] != 1 || $val["is_delete"] != 0) {
                    array_push($failureGoodsList, ['goods_id' => $val['id'], 'goods_name' => $val['name'], 'goods_thumb' => get_thumb($val['thumb'])]);
                    continue;
                }
                // 查询商品库存
                if ($val['sku_id'] > 0) { // 存在SKU
                    // 判断SKU信息
                    $sku = DB::name("goods_sku")->where(['sku_id' => $val['sku_id'], "goods_id" => $val['id']])->find();
                    if (!$sku || $sku["status"] != 1 || $sku["stock"] < 1) {
                        array_push($failureGoodsList, ['goods_id' => $val['id'], 'goods_name' => $val['name'], 'goods_thumb' => get_thumb($sku['spec_img'])]);
                        continue;
                    }
                } else { // 无SKU
                    $sku = DB::name("goods_sku")->where(["goods_id" => $val['id']])->find();
                    if (!$sku || $sku["status"] != 1 || $sku["stock"] < 1) {
                        array_push($failureGoodsList, ['goods_id' => $val['id'], 'goods_name' => $val['name'], 'goods_thumb' => get_thumb($val['thumb'])]);
                        continue;
                    }
                }
            }
        }

        // 检测无效商品信息
        if ($requests['failure'] == 1 && !empty($failureGoodsList)) {
            return ApiReturn::r(666, [
                'goodsCount' => count($orderGoodsList),
                'failureGoodsCount' => count($failureGoodsList),
                'failureGoodsList' => $failureGoodsList
            ], '以下商品卖光了,先将其他商品加入购物车?');
        }

        // 之前购物车商品置为不选择
        Db::name('goods_cart')->where('user_id', $order["user_id"])->update(["is_choose" => 0]);

        // 执行添加购物车
        foreach ($orderGoodsList as $val) {
            // 如果商品不存在或状态不可用或售完或删除
            if (!$val["id"] || $val["status"] != 1 || $val["is_sale"] != 1 || $val["is_delete"] != 0) {
                continue; // 直接跳过
            }

            // 查询商品库存
            if ($val['sku_id'] > 0) { // 存在SKU
                // SKU信息
                $sku = DB::name("goods_sku")->where(['sku_id' => $val['sku_id'], "goods_id" => $val['id']])->find();
                if (!$sku || $sku["status"] != 1 || $sku["stock"] < 1) {
                    continue; // 直接跳过
                }

                // 查询购物车条件
                $goodsCartWhere = array(
                    'user_id' => $order['user_id'],
                    'goods_id' => $val['id'],
                    'sku_id' => $val['sku_id']
                );
                $skuStock = $sku['stock'];

                // 组装添加购物车的参数信息
                $goodsCartParams1 = array(
                    'sku_id' => $sku["sku_id"],
                    'sku_name' => $sku['key_name'],
                    'shop_price' => $sku['shop_price'],
                    'member_price' => $sku['member_price'],
                    'goods_thumb' => $sku['spec_img'],
                );
            } else { // 无SKU
                $sku = DB::name("goods_sku")->where(["goods_id" => $val['id']])->find();
                if (!$sku || $sku["status"] != 1 || $sku["stock"] < 1) {
                    continue; // 直接跳过
                }

                // 查询购物车条件
                $goodsCartWhere = array(
                    'user_id' => $order['user_id'],
                    'goods_id' => $val['id'],
                    'sku_id' => 0
                );
                $skuStock = $val['stock'];

                // 组装添加购物车的参数信息
                $goodsCartParams1 = array(
                    'sku_id' => 0,
                    'sku_name' => "",
                    'shop_price' => $val['shop_price'],
                    'member_price' => $val['member_price'],
                    'goods_thumb' => $val['thumb'],
                );
            }

            // 是否有相同的商品在购物车
            $info = Db::name('goods_cart')->field('id,num')->where($goodsCartWhere)->find();
            // 计算购买商品数量 如果大于剩余数量 只取sku剩余数量
            $goodsCartNum = !empty($info) ? ($info['num'] + $val['num']) : $val['num'];
            $goodsCartNum = ($goodsCartNum > $skuStock) ? $skuStock : $goodsCartNum;

            // 组装添加购物车的参数信息
            $goodsCartParams = array(
                'user_id' => $order["user_id"],
                'goods_id' => $val["id"],
                'goods_name' => $val['name'],
                "num" => $goodsCartNum,
                "is_choose" => 1
            );
            $goodsCartParams = array_merge($goodsCartParams, $goodsCartParams1);
            // 添加购物车
            if (!empty($info)) {
                Db::name('goods_cart')->where(['id' => $info['id']])->update($goodsCartParams);
            } else {
                Db::name('goods_cart')->insert($goodsCartParams);
            }
        }

        return ApiReturn::r(1, [], lang('加入购物车成功'));
    }

    /**
     * 售后list
     * @param $data
     * @param $user
     */
    public function refund_list($data, $user)
    {
        $map[] = ['user_id', '=', $user['id']];
        $order = ' create_time DESC,refund_time DESC ';

        if ($data['type']) {
            switch ($data['type']) {
                case 'apply':
                    $map[] = ['status', '=', 0];
                    $order = 'create_time DESC';
                    break;
                case 'deal':
                    $map[] = ['status', 'neq', 0];
                    $order = 'refund_time DESC';
                    break;
            }
        }
        $map[] = ['is_delete','=',0];
        $list = Db::name('order_refund')
            ->where($map)
            ->order($order)
            ->paginate()->each(function ($item) {
                $goods = Db::name('goods')->get($item['goods_id']);
                $sku = Db::name('goods_sku')->get($item['sku_id']);
                $item['goods_name'] = $goods['name'];
                $item['goods_thumb'] = get_thumb($goods['thumb']);
                $item['sku_name'] = $sku['key_name'];
                $item['refund_picture'] = get_files_url($item['refund_picture']);
                if($item['sku_id'] != 0){
                    $item['shop_price'] = $sku['shop_price'];
                }else{
                    $item['shop_price'] = $goods['shop_price'];
                }

                //$item['refund_type']=\app\goods\model\OrderRefund::$refundGoodsState[$item['refund_type']];
                return $item;
            });
        return ApiReturn::r(1, $list, lang('请求成功'));
    }

    /**
     *获取供应商的退货地址
     * @param {*} $data
     * @param {*} $user
     * @return {*}
     * @Date: 2021-05-07 10:30:57
     */
    public function refund_sender($data, $user)
    {
        $order_refund = Db::name('order_refund')
            ->where(['id' => $data['id']])
            ->find();
        //$goods = Db::name('goods')->get($order_refund['goods_id']);
        $sender_id = Db::name('order')->where('order_sn', $order_refund['order_sn'])->value('sender_id');
        $sender = Db::name('goods_express_sender')->get($sender_id);
        return ApiReturn::r(1, ['sender' => $sender], lang('请求成功'));
    }

    /**
     * 退单取消
     * @param $data
     * @param $user
     */
    public function refund_cancel($data, $user)
    {
        $order_refund = Db::name('order_refund')->where('id', $data['id'])->field('order_sn,goods_id,sku_id')->find();
        if ($order_refund) {
            Db::name('order_goods_list')->where(['order_sn' => $order_refund['order_sn'], 'goods_id' => $order_refund['goods_id'], 'sku_id' => $order_refund['sku_id']])->update(['is_aftersale' => 0]);
        }
        $res = Db::name('order_refund')->where(['id' => $data['id']])->update(['status' => -2, "update_time" => time()]);
        if ($res) {
            return ApiReturn::r(1, '', lang('取消成功'));
        } else {
            return ApiReturn::r(0, '', lang('取消失败'));
        }
    }

    /**
     * 售后详情
     * @param $data
     * @param $user
     */
    public function refund_detaile($data, $user)
    {
        $refund = Db::name('order_refund')->get($data['id']);
        if (!$refund) {
            return ApiReturn::r(0, [], lang('未找到售后申请'));
        }
        $refund['refund_time'] = date('Y-m-d H:i:s',$refund['refund_time']);
        $goods = Db::name('goods')->field('name,thumb,sender_id,shop_price')->get($refund['goods_id']);
        $goods['thumb'] = get_thumb($goods['thumb']);
        $goods['num'] = $refund['num'];
        $goods_list = Db::name("order_goods_list")->where([
            'order_sn' => $refund['order_sn'],
            'goods_id' => $refund['goods_id'],
            'sku_id' => $refund['sku_id'],
        ])->find();
        if ($goods_list['is_pure_integral'] == 1) {
            //纯积分兑换商品
            $goods['goods_money'] = $goods_list['sales_integral'] . lang('积分');
        } else {
            if ($goods_list['sales_integral']) {
                $goods['goods_money'] = $refund['goods_money'] . '+' . $goods_list['sales_integral'] . lang('积分');
            } else {
                $goods['goods_money'] = $refund['goods_money'];
            }
        }
        $sku = Db::name('goods_sku')->field('key_name,shop_price')->get($refund['sku_id']);
        $goods['sku'] = $sku;
        $goods['sender'] = Db::name('goods_express_sender')->get($goods['sender_id']);
        if($refund['sku_id'] != 0){
            $goods['shop_price'] = $sku['shop_price'];
        }
        $refund['goods_info'] = $goods;
        //$refund['refund_type']=\app\goods\model\OrderRefund::$refundGoodsState[$refund['refund_type']];
//        $refund['refund_reason'] = \app\goods\model\OrderRefund::$refundCause[$refund['refund_reason']];
        $refund['refund_picture'] = get_files_url($refund['refund_picture']);
        $refund['express_company_name'] = Db::name('goods_express_company')->where('aid', $refund['express_company_id'])->value('name');
        $sender_id = Db::name('order')->where('order_sn', $refund['order_sn'])->value('sender_id');
        $refund['address'] = Db::name('goods_express_sender')->where('id', $sender_id)->find();
        $refund["refund_account"] = "原路返回";
        $order = OrderModel::where(['order_sn' => $refund['order_sn']])->find();
        $refund['order_type'] = $order['order_type'];
        $refund['order_status'] = $order['status'];
        $order_goods_count = Db::name('order_goods_list')->where('order_sn',$order['order_sn'])->count();
        if($order_goods_count == 1){
            if($order['status'] == 1){
                $refund['is_contain'] = 1;
            }else{
                $refund['is_contain'] = 0;
            }
        }else{
            $refund['is_contain'] = 0;
        }
//        $refund['id'] = $data['id'];
        return ApiReturn::r(1, [$refund], lang('请求成功'));
    }

    /**
     * 售后物流信息填写
     * @param $data
     * @param $user
     */
    public function refund_express($data, $user)
    {
        $express_no = OrderRefund::where(['id' => $data['id']])->value('express_no');
        if ($express_no == $data['express_no']) {
            return ApiReturn::r(0, '', '此快递单号已存在');
        }
        $res = OrderRefund::where(['id' => $data['id']])->update([
            'express_no' => $data['express_no'],
            'express_company_id' => $data['express_company_id'],
            'mobile' => $data["mobile"],
            "remark" => $data["remark"],
            "img" => $data["img"]
        ]);
        if ($res) {
            return ApiReturn::r(1, '', lang('已保存物流信息'));
        } else {
            return ApiReturn::r(0, '', lang('保存物流信息失败'));
        }
    }

    /**
     * 订单角标
     * @author 上官琳 [ 2814356964@qq.com ]
     * @created 2020/2/17 11:02
     */
    public function orderNum($data = [], $user = [])
    {
        $result = [
            "no_pay" => 0,//待付款
            "deliver" => 0,//待发货
            "receiv" => 0,//待收货
            "refund" => 0,//售后
            "evaluate" => 0,//待评价
        ];
        $list = OrderModel::where([
            'user_id' => $user['id']
        ])
            ->where('order_type', 'in', [3, 5, 6, 7, 9, 10, 11])
            ->where('is_delete!=1')
            ->group('status')
            ->column("status,count(aid) as num");
        if (count($list) > 0) {
            $result["no_pay"] = $list[0]?:0;
            $result["deliver"] = $list[1]?:0;
            $result["receiv"] = $list[2]?:0;
            $result["evaluate"] = $list[3]?:0;
        }
        //售后
        $refund = OrderRefund::where(["user_id" => $user["id"],'is_delete'=>0])->count();
        $message = SystemMessage::getList($user['id'], 0)->toArray();
        $result['refund'] = $refund;
        $result['msg_num'] = $message['total'];

        return ApiReturn::r(1, $result, lang('查询成功'));
    }

    /**
     * 售后
     * @param $data
     * @param $user
     */
    public function order_refund($data, $user)
    {
        $map[] = ['user_id', '=', $user['id']];

        $order = 'create_time DESC';
        $list = Db::name('order_refund')
            ->where($map)
            ->order($order)
            ->paginate()->each(function ($item) {
                $order_goods = OrderGoodsModel::where(['goods_id'=>$item['goods_id'],'sku_id'=>$item['sku_id'],'order_sn'=>$item['order_sn']])->field('num,goods_money')->find();
                $goods = Db::name('goods')->get($item['goods_id']);
                $sku = Db::name('goods_sku')->get($item['sku_id']);
                $item['goods_name'] = $goods['name'];
                $item['goods_thumb'] = get_thumb($goods['thumb']);
                $item['sku_name'] = $sku['key_name'];
                $item['refund_picture'] = get_files_url($item['refund_picture']);
                $item['num'] = $order_goods['num'];
                $item['goods_money'] = $order_goods['shop_price'];
                //$item['refund_type']=\app\goods\model\OrderRefund::$refundGoodsState[$item['refund_type']];
                return $item;
            });
        return ApiReturn::r(1, $list, lang('请求成功'));
    }

    /**
     * 申请开票
     * @return \think\response\Json
     * @since 2020年12月8日17:41:27
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function invoiceApply($requests, $user)
    {
        // // 模拟参数
        // $requests = array(
        //     'order_sn' => 'GD20201207141547727170',
        //     'invoice_type' => 2,
        //     'invoice_title' => '北京中犇科技有限责任公司',
        //     'invoice_company_duty_paragraph' => '913204045873302315',
        //     'invoice_company_bank' => '北京招商银行上地七街分行',
        //     'invoice_company_bank_num' => '913204045873302315',
        //     'invoice_company_address' => '北京中关村软件园A区2110栋',
        //     'invoice_company_phone' => '400-123423424',
        //     'invoice_price' => '100',
        // );

        // 参数校验
        if ($requests['invoice_type'] == 2 && empty($requests['invoice_company_duty_paragraph'])) {
            return ApiReturn::r(0, [], lang('公司类型发票请填写税号'));
        }
        if ($requests['invoice_price'] <= '0') {
            return ApiReturn::r(0, [], lang('请正确填写申请开票金额'));
        }

        // 查询是否已申请开票
        $orderSn = $requests['order_sn'];
        $invoice = OrderInvoice::field('id')->where(['order_sn' => $orderSn])->find();
        $orderInvoice = OrderModel::field('aid')->where(['order_sn' => $orderSn, 'invoice_status' => ['1', '2']])->find();
        if ($invoice || $orderInvoice) {
            return ApiReturn::r(0, [], lang('您已申请过发票'));
        }

        // 申请开票
        Db::startTrans();
        try {
            $requests['invoice_add_time'] = time();
            $orderInvoice = OrderInvoice::insert($requests);
            if (!$orderInvoice) {
                exception(lang('申请开票失败'));
            }
            $order = OrderModel::where(['order_sn' => $requests['order_sn']])->update(['invoice_status' => 1]);
            if (!$order) {
                exception(lang('申请开票修改失败'));
            }
            //默认抬头
            if ($requests['invoice_is_default'] == 1) {
                if ($requests['invoice_type'] == 1) {
                    $where['invoice_title'] = $requests['invoice_title'];
                } elseif ($requests['invoice_type'] == 2) {
                    $where['invoice_company_title'] = $requests['invoice_title'];
                } else {
                    exception(lang('请填写发票抬头'));
                }
                Db::name('user')->where('id', $user['id'])->update($where);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, '', $e->getMessage());
        }

        return ApiReturn::r(1, [], lang('申请开票成功'));
    }

    /**
     * 修改发票信息
     * @return \think\response\Json
     * @since 2020年12月9日09:57:34
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function invoiceUpdate($requests, $user)
    {
        // 查询是否支持修改开票信息
        $invoice = OrderInvoice::field('id')->get(['order_sn' => $requests['order_sn'], 'invoice_status' => 1]);
        $orderInvoice = OrderModel::where(['order_sn' => $requests['order_sn'], 'invoice_status' => 1])->field('aid')->find();
        if (!($invoice && $orderInvoice)) {
            return ApiReturn::r(0, [], lang('不支持修改此发票'));
        }

        // 参数过滤
        /*foreach($requests as $key=>$val){
            if(empty(trim($val))){
                unset($requests[$key]);
            }
        }*/
        if ($requests['invoice_type'] == 1) {
            $requests['invoice_company_duty_paragraph'] = '';
            $requests['invoice_company_bank_num'] = '';
            $requests['invoice_company_bank'] = '';
            $requests['invoice_company_address'] = '';
            $requests['invoice_company_phone'] = '';
        }

        // 修改发票信息
        $orderInvoice = OrderInvoice::where(['order_sn' => $requests['order_sn']])->update($requests);
        if ($orderInvoice === false) {
            return ApiReturn::r(0, [], lang('发票信息修改失败'));
        }

        return ApiReturn::r(1, [], lang('发票信息修改成功'));
    }

    /**
     * 查看发票信息
     * @return \think\response\Json
     * @since 2020年12月18日16:54:38
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function invoiceSelect($requests, $user)
    {
        // // 模拟参数
        // $requests = array(
        //     'order_sn' => 'GD20201207141547727170',
        // );

        // 查看发票信息
        $invoiceInfo = OrderInvoice::get(['order_sn' => $requests['order_sn']]);
        $invoiceInfo['invoice_add_time'] = date('Y-m-d H:i:s', $invoiceInfo['invoice_add_time']);
        if ($invoiceInfo['invoice_update_time'] != 0) {
            $invoiceInfo['invoice_update_time'] = date('Y-m-d H:i:s', $invoiceInfo['invoice_update_time']);
        }
        if ($invoiceInfo['invoice_send_goods_num'] != '') {
            $invoiceInfo['express_company'] = Db::name('goods_express_company')->where(['aid' => $invoiceInfo['express_company_id']])->value('name');
        }
        //电子发票图片路径
        if ($invoiceInfo['invoice_img']) {
            $invoiceInfo['invoice_img'] = get_file_url($invoiceInfo['invoice_img']);
        }
        $invoiceInfo['invoice_rule'] = module_config('operation.invoice_rule') ?? '';
        //默认抬头
        $userData = DB::name('user')->where('id', $user['id'])->field('invoice_title,invoice_company_title')->find();
        $invoiceInfo['userData'] = $userData;
        return ApiReturn::r(1, $invoiceInfo, lang('查询成功'));
    }

    /**
     * 我的发票列表
     * @return \think\response\Json
     * @since 2020年12月9日09:34:17
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getMyInvoiceList($requests, $user)
    {
        // 模拟参数
        // $requests = array(
        //     'invoice_status' => 0,
        //     'invoice_title' => '大圣',
        //     'page' => '',
        //     'size' => '',
        // );
        // $user['id'] = 28;

        // 组装查询的条件
        $where[] = ['o.user_id', '=', $user['id']];
        if (!empty($requests['invoice_status']) && in_array($requests['invoice_status'], [1, 2])) {
            $where[] = ['oi.invoice_status', '=', $requests['invoice_status']];
        }
        if (!empty($requests['invoice_title'])) {
            $where[] = ['oi.invoice_title|ogl.goods_name', 'LIKE', "%{$requests['invoice_title']}%"];
        }
        $where[] = ['o.status', 'in', [1, 2, 3, 4]];
        // 查询我的发票列表
        $myInvoiceList = OrderInvoice::alias('oi')
            ->field('oi.*,o.real_money')
            ->where($where)
            ->join('order o', 'o.order_sn = oi.order_sn', 'LEFT')
            ->join('order_goods_list ogl', 'o.order_sn = ogl.order_sn', 'LEFT')
            ->group('o.order_sn')
            ->paginate();
        $count = 0;
        $last_pape = 0;
        if (count($myInvoiceList) > 0) {
            $myInvoiceList_arr = $myInvoiceList->toArray();
            $myInvoiceList = $myInvoiceList_arr['data'];
            $count = $myInvoiceList_arr['total'];
            $last_pape = $myInvoiceList_arr['last_pape'];
        } else {
            $myInvoiceList = [];
        }
        foreach ($myInvoiceList as $key => $val) {
            $myInvoiceList[$key]['goods_list'] = OrderGoodsModel::field('goods_name,goods_thumb')->where(['order_sn' => $val['order_sn']])->select()->each(function ($item) {
                $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                return $item;
            });
            if ($myInvoiceList[$key]['invoice_img']) {
                $myInvoiceList[$key]['invoice_img'] = get_file_url($myInvoiceList[$key]['invoice_img']);
            }
        }

        return ApiReturn::r(1, ['list' => $myInvoiceList, 'count' => $count, 'last_pape' => $last_pape], lang('查询成功'));
    }

    /**
     * 修改收货地址
     * @author zhougs
     * @createTime 2020年12月18日17:40:54
     */
    public function updateOrderAddress($data, $user)
    {
        if (!$data['order_sn'] || !$data['address_id']) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $orderWhere[] = ["status", "gt", 1];
        $orderWhere[] = ["order_sn", "=", $data['order_sn']];
        $orderdetail = OrderModel::where($orderWhere)->find();
        if ($orderdetail) {
            return ApiReturn::r(0, [], '该订单不支持修改地址。');
        }
        $userAddress = Address::get(['address_id' => $data['address_id']]);

        $updateData = [
            "address_id" => $userAddress['address_id'],
            "receiver_mobile" => $userAddress['mobile'],
            "receiver_address" => $userAddress['address'],
            "receiver_name" => $userAddress['name'],
            "province" => $userAddress['province'],
            "city" => $userAddress['city'],
            "district" => $userAddress['district'],
        ];
        $OrderInfoWhere[] = ["order_sn", "=", $data['order_sn']];
        $res = OrderInfo::where($OrderInfoWhere)->update($updateData);
        if (!$res) {
            return ApiReturn::r(0, [], lang('修改失败'));
        }
        return ApiReturn::r(1, [], lang('修改成功'));
    }

    /**
     * 获取退换货原因/取消原因
     */
    public function refundReason($data = [], $user = [])
    {
        $list = Db::name('refund_reason')->field("id,reason")->where(['status' => 1, 'type' => $data['type']])->select();
        return ApiReturn::r(1, $list, lang('查询成功'));
    }

    /*
 * 积分使用规则
 *
 */
    public function integralRule($data = [], $user = [])
    {
        $integral_deduction = module_config('integral.integral_deduction');
        $content = '1' . lang('元') . lang('抵扣') . $integral_deduction . lang('积分');
        return ApiReturn::r(1, ['content' => $content], 'ok');
    }

    public function get_pay_info($data = [], $user = [])
    {
        $order_sn = $data['order_sn'];
        $result = OrderModel::where(['order_sn' => $order_sn])->field('pay_type,pay_time')->find();
        $result['pay_type'] = OrderModel::$payTypes[$result['pay_type']];
        return ApiReturn::r(1, $result, 'ok');
    }

    /**
     * 售后信息删除
     */
    public function del_refound($data = [], $user = [])
    {
        $id = $data["id"] ?? 0;
        OrderRefund::where([
            ["id", "=", $id],
            ["status", "in", [-2, -1, 3]],
            ["user_id", "=", $user["id"]]
        ])->update(['is_delete'=>1]);
        return ApiReturn::r(1, [], 'ok');
    }
}
