<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author jxy [415782189@qq.com]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

namespace app\goods\admin;

use app\admin\admin\Base;
use app\api\controller\v1\User;
use app\common\model\Order as OrderModel;
use app\goods\model\Goods;
use app\goods\model\GoodsOutofstock;
use app\goods\model\GoodsSku;
use app\goods\model\GoodsStockLog;
use app\goods\model\GoodsTypeSpec;
use app\goods\model\GoodsTypeSpecItem;
use app\goods\model\OrderAction;
use app\goods\model\OrderGoods;
use app\goods\model\OrderGoodsExpress;
use app\goods\model\OrderInfo;
use app\goods\model\OrderRefund;
use app\goods\model\OrderRefund as RefundModel;
use app\goods\model\Type as TypeModel;
use app\user\model\Marketing;
use app\user\model\ScoreLog;
use service\ApiReturn;
use think\Db;
use service\Format;
use app\operation\model\SystemMessage as SystemMessageModel;
use app\goods\model\Category;
use app\goods\model\OrderInvoice;
use app\goods\model\OrderPickup;
use app\goods\model\ActivityDetails as ActivityDetailsModel;
use app\goods\service\Goods as GoodsService;
use \app\user\model\MoneyLog;

/**
 * 订单控制器
 * @package app\Order\admin
 */
class Order extends Base
{


    /**
     * 订单列表
     * @return mixed
     * @author jxy [415782189@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $where = array();
        $map = input("param.");
//        $map = $this->getMap(['o.order_sn', 'status:select' ,'create_time:datelimit', 'o.pay_status']);
        $status = $map['status'];
        $pay_type = $map['pay_type'];

        //2021-04-19 wangph 新增配送类型筛选
        $send_typeArr = OrderModel::$sendTypeArr;
        $send_type = $map['send_type'] ?? '';
        if ($map['send_type'] !== '' && $map['send_type'] !== null) {
            $where[] = ["o.send_type", "=", $map['send_type']];
        }

        if ($map['order_sn']) {
            $where[] = ["o.order_sn", "like", '%' . $map['order_sn'] . '%'];
        }
        if ($map['status'] != "all" && $map['status'] !== '' && $map['status'] !== null) {
            $where[] = ["o.status", "=", $map['status']];
        }
        if ($map['order_status'] != "all" && $map['order_status'] !== '' && $map['order_status'] !== null) {
            $where[] = ["o.order_status", "=", $map['order_status']];
        }
        if ($map['pay_type'] != "all" && $map['pay_type'] !== '' && $map['pay_type'] !== null) {
            $where[] = ["o.pay_type", "=", $map['pay_type']];
        }
        if ($map['create_time'] != "") {
            $time = explode(' - ', $map['create_time']);
            $where[] = ["o.create_time", "between", [strtotime($time[0]), strtotime($time[1])]];
        }
        if ($map['nickname']) {
            $userid = Db::name('user')->where('user_nickname', 'like', '%' . $map['nickname'] . '%')->column('id');
        }
        if ($map['express_serial_number']) {
            $where[] = ['oge.express_serial_number', '=', $map['express_serial_number']];
        }
        if ($map['receiver_name']) {
            $where[] = ['og.receiver_name', 'like', '%' . $map['receiver_name'] . '%'];
        }
        $order_type = input('param.order_type');
        $this->assign('order_type', $order_type);
        if ($order_type && $order_type != 'all') {
            $where[] = ['o.order_type', '=', $order_type];
        }
        // dump($status);
        if ($map['status'] === '' || $map['status'] === null) {
            $status = -99;
        }
        if ($map['order_type'] === '' || $map['order_type'] === null) {
            $order_type = 'all';
        }
        if ($map['pay_type'] === '' || $map['pay_type'] === null) {
            $pay_type = 'all';
        }

        // 排序
        $order = 'o.create_time desc';
        $where[] = ["o.order_type", "<>", 8];
        $export = $map['export'] ?? 0;
        // 数据列表
        $data_list = OrderModel::alias('o')
            ->join("order_goods_info og", "o.order_sn = og.order_sn", "left")
            ->join(" order_goods_express oge", "og.order_sn = oge.order_sn", "left")
            ->field("o.order_sn,o.aid,o.user_id,o.order_money,o.payable_money,o.real_money,
            o.pay_status,o.status,o.order_type,o.pay_type,o.send_type,og.receiver_mobile,og.receiver_address,
            og.receiver_name,og.province,og.city,og.district,oge.express_company,oge.express_no,
            og.express_price,o.create_time,o.cost_integral,oge.express_status")
            ->where($where)
            ->where(['is_delete' => 0])
            ->where(function ($query) use ($userid) {
                foreach ($userid as $v) {
                    $query->whereOr('o.user_id', 'eq', $v);
                }
            })
            ->order("o.create_time desc")
            ->group("o.order_sn");
        if ($export == 1) {
            $data_list = $data_list->select();
        } else {
            $data_list = $data_list->paginate(15, false, ['query' => request()->param()]);

        }

        foreach ($data_list as $k => $v) {
            $res = Db::name('order_refund')->where(['order_sn' => $v['order_sn']])->find();
            if ($res) {
                $data_list[$k]['order_sn_show'] = $v['order_sn'] . ' <a href="' . url('goods/order/refund', ['server_no' => $res['server_no'], 'status' => $res['status']]) . '"><span class="label label-flat label-warning">有退款</span></a>';
            } else {
                $data_list[$k]['order_sn_show'] = $v['order_sn'];
            }
            //发货单是否删除
            $is_find = Db::name("order_goods_express")->where([
                'order_sn' => $v['order_sn'],
                'is_del' => 0
            ])->find() ? true : false;
            $data_list[$k]['is_find'] = $is_find;

            $goods = OrderGoods::get_one_goods($v['order_sn']);
            $data_list[$k]['goods_name'] = $goods['goods_name'];
            $data_list[$k]['goods_thumb'] = $goods['goods_thumb'] ?: config("web_site_domain") . '/static/admin/images/default_goods.png';
            $data_list[$k]['pay_status_name'] = OrderModel::$pay_status[$v['pay_status']];
            $data_list[$k]['status_name'] = OrderModel::$order_status[$v['status']];
            $data_list[$k]['order_type_arr'] = OrderModel::order_typeArr()[$v['order_type']]['name'];
            $data_list[$k]['send_type_name'] = $send_typeArr[$v['send_type']]['name'];
            if ($v['order_type'] == 7) { // 预售订单判断是否更新过尾款
                $book_order_sn = Db::name('order_relation')->where('book_order_sn', $v['order_sn'])->value('final_order_sn');
                $final_pay_order_info = Db::name('order')->where('order_sn', $book_order_sn)->field('status, pay_status, payable_money, real_money')->find();

                if ($final_pay_order_info['pay_status'] == 1 && $final_pay_order_info['status']) {
                    $data_list[$k]['pay_status'] = 2; // 可以发货
                    $data_list[$k]['payable_money'] = $data_list[$k]['payable_money'] + $final_pay_order_info['payable_money'];
                    $data_list[$k]['real_money'] = $data_list[$k]['real_money'] + $final_pay_order_info['real_money'];
                }
            }

            //send_type=1 为自提类型,获取自提相关信息，add by wangph at 2021-4-26
            if ($v['send_type'] == 1) {
                $data_list[$k]['pick_info'] = OrderPickup::getOrderPickUp($v['order_sn']);
            }
        }
//        导出订单数据
        if ($map['export'] == 1) {
            $xlsName = lang('订单列表');
            $xlsCell = array(
                array('goods_name', lang('商品名称')),
                array('sku_name', lang('规格名称')),
                array('order_sn', lang('订单号')),
                array('goods_money', lang('金额')),
                array('num', lang('数量')),
                array('name', lang('供应商')),
                array('phone', lang('电话')),
                array('province', lang('省')),
                array('city', lang('市')),
                array('area', lang('区')),
                array('address', lang('街道')),
                array('receiver_name', lang('收件人')),
                array('receiver_mobile', lang('收件人电话')),
                array('receiver_province', lang('省')),
                array('receiver_city', lang('市')),
                array('receiver_district', lang('区')),
                array('receiver_address', lang('街道')),
            );
            foreach ($data_list as $key => $value) {
                $order_sn = $value['order_sn'];
                $xlsData[] = Db::name('order_goods_list')
                    ->alias('ogl')
                    ->field('ogl.goods_name,ogl.goods_money,ogl.sku_name,ogl.order_sn,ogl.num,g.sender_id,s.name,s.phone,s.province,s.city,s.area,s.address')
                    ->where(['ogl.order_sn' => $order_sn])
                    ->join('goods g', 'g.id=ogl.goods_id', 'left')
                    ->order('g.sender_id')
                    ->join('goods_express_sender s', 's.id=g.sender_id', 'left')
                    ->select();
            }
            foreach ($xlsData as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $receiver = Db::name('order_goods_info')->where(['order_sn' => $vv['order_sn']])->find();
                    $excelData[] = [
                        'goods_name' => $vv['goods_name'],
                        'sku_name' => $vv['sku_name'],
                        'order_sn' => $vv['order_sn'],
                        'goods_money' => $vv['goods_money'],
                        'num' => $vv['num'],
                        'name' => $vv['name'],
                        'phone' => $vv['phone'],
                        'province' => $vv['province'],
                        'city' => $vv['city'],
                        'area' => $vv['area'],
                        'address' => $vv['address'],
                        'receiver_name' => $receiver['receiver_name'],
                        'receiver_mobile' => $receiver['receiver_mobile'],
                        'receiver_city' => $receiver['city'],
                        'receiver_district' => $receiver['district'],
                        'receiver_address' => $receiver['receiver_address'],
                        'sender' => $vv['name'],
                    ];
                }
            }
            $_excelData[0]['list'] = $excelData;
            $excelData = array_values($_excelData);
            $this->exportExcel($xlsName, $xlsCell, $excelData);
        }


        $list_pay_type = [['code' => 'minipay', 'name' => lang('小程序支付')], ['code' => 'balance', 'name' => lang('余额支付')]];
        $order_status = OrderModel::$order_status;

        $this->assign('send_typeArr', $send_typeArr);
        $this->assign('status', $status);
        $this->assign('list', $data_list);
        $this->assign('list_pay_type', $list_pay_type);
        $this->assign('pay_type', $pay_type);
        $this->assign('send_type', $send_type);
        $this->assign('order_status', $order_status);
        $this->assign('bottom_button_select', $this->bottom_button_select);
        $this->assign('order_typeArr', OrderModel::order_typeArr());
        $this->assign('pages', $data_list->render());
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            /*echo "<pre>";
            print_r($data);die;*/
            Db::startTrans();
            try {
                //生成订单号
                $order_no = get_order_sn('GD');
                //实例化商品模型
                $goodinfo = new \app\goods\model\Goods();
                $goodsku = new \app\goods\model\GoodsSku();
                $nowHour = (int)date('H');
                $user_level = Db::name('user')->where(['id' => $data['user_id']])->column('user_level');
                if ($user_level[0] > 0) {
                    $price_type = 'member_price';
                } else {
                    $price_type = 'shop_price';
                }
                $list_goodsId = explode(',', $data['good_ids']);
                $good_nums = explode(',', $data['good_nums']);
                $sku_ids = explode(',', $data['sku_ids']);
                //添加订单商品表信息
                foreach ($list_goodsId as $k => $g) {
                    // 初始化变量
                    $goods = $where = [];
                    // 开始循环商品信息
                    $good_info = $goodinfo->get($g);
                    $goods['order_sn'] = $order_no;
                    $goods['goods_id'] = $g;
                    $goods['goods_name'] = $good_info['name'];
                    $goods['sku_id'] = $sku_ids[$k] ? $sku_ids[$k] : 0;
                    $goods['num'] = $good_nums[$k];
                    $goods['goods_thumb'] = $good_info['thumb'];
                    $goods['order_status'] = 0;
                    $goods['sender_id'] = $good_info['sender_id'];
                    if ($goods['sku_id']) {
                        //如果是sku商品，则查询sku的价格和库存
                        $sku_info = $goodsku->get(['sku_id' => $goods['sku_id'], 'goods_id' => $g]);
                        $goods['shop_price'] = $sku_info[$price_type];
                        $stock = $sku_info['stock'];
                        $goods['sku_name'] = $sku_info['key_name'];
                    } else {
                        $goods['shop_price'] = $good_info[$price_type];
                        $stock = $good_info['stock'];
                        $goods['sku_name'] = '';
                    }
                    if ($stock < $good_nums[$k]) {
                        // exception($goods['sku_id']?$sku_info['key_name']:$sku_info['name'] . ",库存不足，无法下单");
                        return json(['code' => 0, 'msg' => lang('库存不足，无法下单')]);
                    }
                    //计算商品总价
                    $goods['goods_money'] = bcmul($goods['shop_price'], $good_nums[$k], 2);
                    $goods['create_time'] = time();
                    $money = bcadd($money, $goods['goods_money'], 2);
                    $result = OrderModel::inventoryReduction($goods, $good_nums[$k]);
                    if ($result['state']) {
                        exception($result['info']);
                    }
                    $goods_list[] = $goods;
                    $goods_id[] = $g;
                }
                //插入订单商品表
                $res2 = Db::name('order_goods_list')->insertAll($goods_list);
                if (!$res2) {
                    return json(['code' => 0, 'msg' => lang('保存订单商品失败')]);
                }
                // 组装订单信息
                $orderData['user_id'] = $data['user_id'];
                $orderData['order_sn'] = $order_no;
                $orderData['order_money'] = $money;
                $orderData['payable_money'] = $money;
                $orderData['status'] = 0;
                $orderData['real_money'] = $money;
                $orderData['pay_status'] = 0;
                //$orderData['pay_type'] = 'xx_pay';
                $orderData['coupon_id'] = 0;
                $orderData['coupon_money'] = 0;
                $orderData['order_type'] = 3;
                $orderData['send_type'] = $data['send_type'];
                //$orderData['pay_time'] = time();
                // 插入订单信息
                $ret = OrderModel::create($orderData);
                if (!$ret) {
                    
                    return json(['code' => 0, 'msg' => lang('创建订单失败')]);
                }
                if ($data['send_type'] == 1) {
                    $user_pickup_id =  Db::name('user_pickup')->where([['user_id','=',$data['user_id']], ['is_default','=',1]])->find();
                    $order_pickup = [
                        'order_sn' => $order_no,
                        'pickup_id' => $data['pickup_id'],
                        'pickup_date' =>  date('Y-m-d'),
                        'pickup_delivery_time_id' => 1,
                        'user_pickup_id' => $user_pickup_id['id']??0
                    ];
                    $res_pickup = Db::name('order_pickup')->insert($order_pickup);
                    if (!$res_pickup) {
                        exception(lang('保存订单自提信息失败'));
                    }

                } else {
                    $address_info = Db::name('user_address')->where(['address_id' => $data['address_id']])->find();

                    $order_info['address']['address_id'] = $data['address_id'];
                    $order_info['address']['mobile'] = $address_info['mobile'];
                    $order_info['address']['address'] = $address_info['address'];
                    $order_info['address']['name'] = $address_info['name'];
                    $order_info['address']['province'] = $address_info['province'];
                    $order_info['address']['city'] = $address_info['city'];
                    $order_info['address']['district'] = $address_info['district'];
                    $order_info['remark'] = lang('后台下单');
                    $order_goods_info['express_price'] = 0;

                    $res1 = OrderModel::saveGoodsInfo($order_info, $order_no, $order_info['remark']);
                    if (!$res1) {
                        return json(['code' => 0, 'msg' => lang('保存订单附加信息失败')]);
                    }
                }
                

                Db::commit();
                return json(['code' => 1, 'msg' => lang('创建订单成功'), 'url' => url('goods/order/xx_pay',['order_sn'=>$order_no])]);
            } catch (\Exception $e) {
                Db::rollback();
                return json(['code' => 0, 'msg' => lang('创建订单失败')]);
            }
        } else {
            $list_user = Db::name('user')->where(['status' => 1])->column('id,user_nickname,mobile', 'id');
          
            $data_list = Db::name('goods')->where(['is_sale'=>1,'is_delete'=>0])->select();
            foreach ($data_list as $key => $value) {
                if ($value['is_spec'] == 1) {
                    $data_list[$key]['sku'] = Db::name('goods_sku')->where(['goods_id' => $value['id'], 'status' => 1])->select();
                }
            }
            //halt($list_user);
            $category = Category::getMenuTree();
            $this->assign('category', $category);
            $this->assign('list_user', $list_user);
            $this->assign('goods_list', $data_list);
            return $this->fetch();
        }
    }

    /**
     * 客服下单，待支付订单列表
     */
    public function  xx_pay()
    {   
        $order_sn =  input('param.order_sn');
        $info = OrderInfo::get_order_detail($order_sn);

        $order_goods = $info['order_goods'];
        // $stock_before =  GoodsService::get_stock($order_goods[0]['goods_id'], $order_goods[0]['sku_id']);
        // halt( $stock_before);
        $info['order_status'] = OrderModel::$order_status[$info['status']];
        $info['pay_type'] = OrderModel::$payTypes[$info['pay_type']];

        $info['pay_status_name'] = OrderModel::$pay_status[$info['pay_status']];
        $info['order_type_name'] = OrderModel::$oeder_type_name[$info['order_type']];

        $info['user'] = Db::name("user")->field("user_name,mobile,user_level,user_money")->where("id", $info['user_id'])->find();
        $info['order_express'] = Db::name('order_goods_express')->where([
            'order_sn' => $info['order_sn'],
            'is_del' => 0
        ])->find();
        $info['order_goods_money'] = number_format($info['order_money'] - $info['express_price'], 2, '.', '');
        $info['user_level'] = Db::name('user_level')->where(['id' => $info['user_id']])->value("name");


        //send_type=1 为自提类型,获取自提相关信息，add by wangph at 2021-4-19
        $info['pick_info'] = [];
        if ($info['send_type'] == 1) {
            $info['pick_info'] = OrderPickup::getOrderPickUp($order_sn);
        }

        
        $info['order_goods'] = json_encode($info['order_goods'], JSON_UNESCAPED_UNICODE);
        $order_action = OrderAction::getActionLogs([["order_sn", "=", $order_sn]]);
        
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $order_sn =  $data['order_sn'];
            $info = OrderInfo::get_order_detail($order_sn);
            $user = Db::name("user")->field('id,user_money')->where('id', $info['user_id'])->find();
            $real_money = $data['total_money'];
            Db::startTrans();
            try {
                if($user['user_money'] < $real_money) {
                    exception(lang('用户余额不足，请充值'));
                }

                $orderData = [
                    'status' =>1,
                    'pay_status'=>1,
                    'pay_type' => 'balance',
                    'pay_time' =>time(),
                    'real_money'=> $real_money,
                    'real_balance'=> $real_money
                ];
                
                $ret = OrderModel::where(['aid'=>$data['aid']])->update($orderData);
                if (!$ret) {
                    exception(lang('订单支付失败'));
                }
               
                foreach($info['order_goods'] as $v) {
                    //库存记录
                    $stock_before =  GoodsService::get_stock($v['goods_id'], $v['sku_id']);
                    $stock_after = $stock_before - $v['num'];
                    GoodsStockLog::AddStockLog(
                        $v['goods_id'],
                        $v['sku_id'],
                        $order_sn,
                        $stock_before,
                        $v['num'],
                        $stock_after,
                        2,
                        0,
                        lang('用户购买'),
                        $v['goods_sn']
                    );
                    //扣库存
                    $update_stock = GoodsService::update_stock($v['goods_id'], -$v['num'], $v['sku_id']);
                    if ($update_stock['code'] == 0) {
                        exception($update_stock['msg']);
                    }
                   
                    //加销量
                    $update_sale = GoodsService::update_sale($v['goods_id'], $v['num'], $v['sku_id']);
                    if ($update_sale['code'] == 0) {
                        exception($update_sale['msg']);
                    }
                }
                 
                // 变更余额记录
                $moneylog = MoneyLog::changeMoney($user['id'], $user['user_money'], -$real_money, 2, lang('会员消费'), $order_sn);
                if (!$moneylog) {
                    exception(lang('更改余额失败'));
                }

                //写入payment_log
                $payment_log = Db::name("payment_log")->insert([
                    'order_sn' => $data['order_sn'],
                    'amount' => $data['total_money'],
                    'transaction_no' => '',
                    'create_time' => time(),
                    'status' => 1,
                    'pay_type' => 'balance'
                ]);
                if (!$payment_log) {
                    exception(lang('金额日志写入失败'));
                }
                Db::commit();
                return json(['code' => 1, 'msg' => lang('支付成功'), 'url' => url('goods/order/index', ['order_sn'=>$order_no])]);
            } catch (\Exception $e) {
                Db::rollback();
                return json(['code' => 0, 'msg'=>$e->getMessage()]);
            }
        }
        $this->assign('order_action', json_encode($order_action->toArray()['data'], JSON_UNESCAPED_UNICODE));
        $this->assign('order_info', $info);
        return $this->fetch();
    }
    /**
     * 根据商品分类cid获取商品列表
     */
    public function ajaxClass()
    {
        $cate_id = input('param.cid');
        $map1[] = ['is_delete', '=', 0];
        if (isset($cate_id) && $cate_id != 0) {
            $cid = Category::getChildsId($cate_id);
            if (count($cid)) {
                $map1[] = ['cid', 'in', $cid];
            } else {
                $map1[] = ['cid', '=', $cate_id];
            }
        }
        $data_list = Db::name('goods')->where($map1)->select();
        foreach ($data_list as $key => $value) {
            if ($value['is_spec'] == 1) {
                $data_list[$key]['sku'] = Db::name('goods_sku')->where(['goods_id' => $value['id'], 'status' => 1])->select();
            }
        }
        echo json_encode(array('ret' => 2, 'data' => $data_list, 'msg' => lang('查询成功')));
    }

    /**
     * 根据用户id获取收货地址
     */
    public function ajaxClass_address()
    {
        $user_id = input('param.user_id');
        if (isset($user_id) && $user_id != 0) {
            $data_list = Db::name('user_address')->where(['user_id' => $user_id, 'status' => 1])->order('is_default DESC')->select();
            $user_level = Db::name('user')->where(['id' => $user_id, 'status' => 1])->value('user_level');
        }
        echo json_encode(array('ret' => 2, 'data' => $data_list, 'user_level' => $user_level, 'msg' => lang('查询成功')));
    }

    
    /**
     * 根据用户id获取收货地址
     */
    public function ajaxClass_pickup()
    {
        $user_id = input('param.user_id');
        if (isset($user_id) && $user_id != 0) {
            //$user_city = Db::name('user')->where(['id' => $user_id, 'status' => 1])->value('user_level');
            $data_list = Db::name('pickup_deliver')->where(['status' => 1])->select();
            
        }
        echo json_encode(array('ret' => 2, 'data' => $data_list, 'user_level' => $user_level, 'msg' => lang('查询成功')));
        
    }


    /*
     * 删除订单
     * */
    public function order_del($order_sn)
    {
        $res = Db::name("order")->where(['order_sn' => $order_sn])->setField('is_delete', 1);
        if ($res) {
            $this->success(lang('删除成功'));
        } else {
            $this->error(lang('删除失败'));
        }
    }

    public function delete($ids)
    {
        Db::startTrans();
        try {
            foreach ($ids as $k => $v) {
                Db::name("order")->where(['aid' => $v])->setField('is_delete', 1);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(lang('删除失败'));
        }
        $this->success(lang('删除成功'));
    }

    /**
     *砍价订单列表
     * @return mixed
     * @author jxy [415782189@qq.com]
     */
    public function cut()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        // $map = $this->getMap();
        $order_sn = input('param.order_sn');
        $status = input('param.status', '');
        $create_time = input('param.create_time');
        $pay_status = input('param.pay_status');
        $map[] = ['order_type', '=', 14];
        if ($order_sn) {
            $map[] = ['o.order_sn', '=', $order_sn];
        }
        if ($create_time) {
            $map[] = ['o.create_time', '>=', strtotime($create_time)];
            $map[] = ['o.create_time', '<', (strtotime($create_time . ' 23:59:59'))];
        }
        if ($status !== '') {
            $map[] = ['o.status', '=', $status];
            $field = 'status';
            $this->assign('active', $status);
        }
        if ($pay_status != '') {
            $map[] = ['o.pay_status', '=', $pay_status];
            $field = 'pay_status';
            $this->assign('active', $pay_status);
        }
        // 排序
        $order = 'o.create_time desc';
        // 数据列表
        $data_list = OrderModel::where($map)
            ->alias('o')
            ->order($order)
            ->field('o.*')
            ->paginate(15, false, ['query' => request()->param()])
            ->each(function ($item) {
                $item['status_name'] = OrderModel::$order_status[$item['status']];
                $item['pay_status_name'] = OrderModel::$pay_status[$item['pay_status']];
                $order_goods_info = Db::name('order_goods_list')->where(['order_sn' => $item['order_sn']])->find();
                $activity_goods = Db::name('goods_activity_details')->where([
                    'activity_id' => $order_goods_info['activity_id'],
                    'goods_id' => $order_goods_info['goods_id'],
                    'sku_id' => $order_goods_info['sku_id'],
                ])->find();
                $cut_price = Db::name('goods_activity_cut_price')->where(['activity_goods_id' => $activity_goods['id'], 'uid' => $item['user_id']])->find();
                $item['cut_price'] = $cut_price['cut_price'];
                return $item;
            });
        $tab[] = ['title' => lang('砍价中'), 'url' => url('goods/order/cut', 'status=-2'), 'value' => -2, 'field' => 'status'];
        $tab[] = ['title' => lang('已取消'), 'url' => url('goods/order/cut', 'status=-1'), 'value' => -1, 'field' => 'status'];
        $tab[] = ['title' => lang('待支付'), 'url' => url('goods/order/cut', 'status=0'), 'value' => 0, 'field' => 'status'];
        $tab[] = ['title' => lang('已支付'), 'url' => url('goods/order/cut', 'pay_status=1'), 'value' => 1, 'field' => 'pay_status'];
        $tab[] = ['title' => lang('待发货'), 'url' => url('goods/order/cut', 'status=1'), 'value' => 1, 'field' => 'status'];
        $tab[] = ['title' => lang('已发货'), 'url' => url('goods/order/cut', 'status=2'), 'value' => 2, 'field' => 'status'];
        $tab[] = ['title' => lang('已完成'), 'url' => url('goods/order/cut', 'status=3'), 'value' => 3, 'field' => 'status'];
        $this->assign('tab_list', $tab);
        $this->assign('field', $field);
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());
        $this->assign('orderStatus', OrderModel::$order_status);
        return $this->fetch();
    }

    /**
     * 秒杀订单列表
     * @return mixed
     * @author jxy [415782189@qq.com]
     */
    public function seckill()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        // $map = $this->getMap();
        $order_sn = input('param.order_sn');
        $receiver_mobile = input('param.receiver_mobile');
        $receiver_address = input('param.receiver_address');
        $receiver_name = input('param.receiver_name');
        $user_mobile = input('param.user_mobile');
        $user_name = input('param.user_name');

        $status = input('param.status', '');
        $create_time = input('param.create_time');
        $pay_status = input('param.pay_status');
        $map[] = ['order_type', '=', 6];
        if ($order_sn) {
            $map[] = ['o.order_sn', 'like', '%' . $order_sn . '%'];
        }
        if ($create_time) {
            $map[] = ['o.create_time', '>=', strtotime($create_time)];
            $map[] = ['o.create_time', '<', (strtotime($create_time . ' 23:59:59'))];
        }
        if ($status !== '') {
            $map[] = ['o.status', '=', $status];
            $field = 'status';
            $this->assign('active', $status);
        }
        if ($pay_status != '') {
            $map[] = ['o.pay_status', '=', $pay_status];
            $field = 'pay_status';
            $this->assign('active', $pay_status);
        }
        if ($receiver_mobile) {
            $map[] = ['og.receiver_mobile', 'like', '%' . $receiver_mobile . '%'];
        }
        if ($receiver_address) {
            $map[] = ['og.receiver_address', 'like', '%' . $receiver_address . '%'];
        }
        if ($receiver_name) {
            $map[] = ['og.receiver_name', 'like', '%' . $receiver_name . '%'];
        }
        if ($user_mobile) {
            $map[] = ['u.mobile', 'like', '%' . $user_mobile . '%'];
        }
        if ($user_name) {
            $map[] = ['u.user_nickname', 'like', '%' . $user_name . '%'];
        }
        // 排序
        $order = 'o.create_time desc';
        // 数据列表
        $data_list = OrderModel::alias('o')
            ->join("user u", "o.user_id = u.id", "left")
            ->join("order_goods_info og", "o.order_sn = og.order_sn", "left")
            ->join(" order_goods_express oge", "og.order_sn = oge.order_sn", "left")
            ->field("o.order_sn,o.aid,o.user_id,o.order_money,o.payable_money,o.real_money,
            o.pay_status,o.status,o.order_type,o.pay_type,og.receiver_mobile,og.receiver_address,
            og.receiver_name,og.province,og.city,og.district,oge.express_company,oge.express_no,
            og.express_price,o.create_time,o.cost_integral,oge.express_status,u.user_name,u.mobile")
            ->where($map)
            ->order($order)
            ->paginate(15, false, ['query' => request()->param()])
            ->each(function ($item) {
                $item['status_name'] = OrderModel::$order_status[$item['status']];
                $item['pay_status_name'] = OrderModel::$pay_status[$item['pay_status']];
                $item['pay_type_name'] = OrderModel::$payTypes[$item['pay_type']];
                return $item;
            });
        foreach ($data_list as &$value) {
            $goodsInfo = Db::name('order_goods_list')->where(['order_sn' => $value['order_sn']])->find();
            $value['goods_name'] = $goodsInfo['goods_name'];
            $value['goods_thumb'] = get_file_url($goodsInfo['goods_thumb']);
            //发货单是否删除
            $is_find = Db::name("order_goods_express")->where([
                'order_sn' => $value['order_sn'],
                'is_del' => 0
            ])->find() ? true : false;
            $value['is_find'] = $is_find;
        }
        $tab[] = ['title' => lang('已取消'), 'url' => url('goods/order/seckill', 'status=-1'), 'value' => -1, 'field' => 'status'];
        $tab[] = ['title' => lang('待支付'), 'url' => url('goods/order/seckill', 'status=0'), 'value' => 0, 'field' => 'status'];
        $tab[] = ['title' => lang('已支付'), 'url' => url('goods/order/seckill', 'pay_status=1'), 'value' => 1, 'field' => 'pay_status'];
        $tab[] = ['title' => lang('待发货'), 'url' => url('goods/order/seckill', 'status=1'), 'value' => 1, 'field' => 'status'];
        $tab[] = ['title' => lang('已发货'), 'url' => url('goods/order/seckill', 'status=2'), 'value' => 2, 'field' => 'status'];
        $tab[] = ['title' => lang('已完成'), 'url' => url('goods/order/seckill', 'status=3'), 'value' => 3, 'field' => 'status'];
        $this->assign('tab_list', $tab);
        $this->assign('field', $field);
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());
        $this->assign('orderStatus', OrderModel::$order_status);
        return $this->fetch();
    }

    /**
     * 会员限购订单列表
     * @return mixed
     * @author jxy [415782189@qq.com]
     */
    public function restriction()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        // $map = $this->getMap();
        $order_sn = input('param.order_sn');
        $status = input('param.status', '');
        $create_time = input('param.create_time');
        $pay_status = input('param.pay_status');
        $map[] = ['order_type', '=', 11];
        if ($order_sn) {
            $map[] = ['o.order_sn', '=', $order_sn];
        }
        if ($create_time) {
            $map[] = ['o.create_time', '>=', strtotime($create_time)];
            $map[] = ['o.create_time', '<', (strtotime($create_time . ' 23:59:59'))];
        }
        if ($status !== '') {
            $map[] = ['o.status', '=', $status];
            $field = 'status';
            $this->assign('active', $status);
        }
        if ($pay_status != '') {
            $map[] = ['o.pay_status', '=', $pay_status];
            $field = 'pay_status';
            $this->assign('active', $pay_status);
        }
        // 排序
        $order = 'o.create_time desc';
        // 数据列表
        $data_list = OrderModel::where($map)
            ->alias('o')
            ->order($order)
            ->field('o.*')
            ->paginate(15, false, ['query' => request()->param()])
            ->each(function ($item) {
                $item['status_name'] = OrderModel::$order_status[$item['status']];
                $item['pay_status_name'] = OrderModel::$pay_status[$item['pay_status']];
                return $item;
            });
        $tab[] = ['title' => lang('已取消'), 'url' => url('goods/order/restriction', 'status=-1'), 'value' => -1, 'field' => 'status'];
        $tab[] = ['title' => lang('待支付'), 'url' => url('goods/order/restriction', 'status=0'), 'value' => 0, 'field' => 'status'];
        $tab[] = ['title' => lang('已支付'), 'url' => url('goods/order/restriction', 'pay_status=1'), 'value' => 1, 'field' => 'pay_status'];
        $tab[] = ['title' => lang('待发货'), 'url' => url('goods/order/restriction', 'status=1'), 'value' => 1, 'field' => 'status'];
        $tab[] = ['title' => lang('已发货'), 'url' => url('goods/order/restriction', 'status=2'), 'value' => 2, 'field' => 'status'];
        $tab[] = ['title' => lang('已完成'), 'url' => url('goods/order/restriction', 'status=3'), 'value' => 3, 'field' => 'status'];
        $this->assign('tab_list', $tab);
        $this->assign('field', $field);
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());
        $this->assign('orderStatus', OrderModel::$order_status);
        return $this->fetch();
    }


    /**
     * @param int $aid 订单id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/28 11:14
     */
    public function detail($order_sn = null)
    {
        $info = OrderInfo::get_order_detail($order_sn);
        $order_goods = $info['order_goods'];

        // halt($info);
        $info['order_status'] = OrderModel::$order_status[$info['status']];
        $info['pay_type'] = OrderModel::$payTypes[$info['pay_type']];

        $info['pay_status_name'] = OrderModel::$pay_status[$info['pay_status']];
        $info['order_type_name'] = OrderModel::$oeder_type_name[$info['order_type']];
        $info['transaction_no'] = Db::name('payment_log')->where(['order_sn' => $info['user_id']])->value("transaction_no");
        $info['user'] = Db::name("user")->field("user_name,mobile,user_level")->where("id", $info['user_id'])->find();
        $info['order_express'] = Db::name('order_goods_express')->where([
            'order_sn' => $info['order_sn'],
            'is_del' => 0
        ])->find();
        $info['order_goods_money'] = number_format($info['order_money'] - $info['express_price'], 2, '.', '');
        $info['user_level'] = Db::name('user_level')->where(['id' => $info['user_id']])->value("name");
        // if($info['order_type'] != 1){
        //     $map[] = ['order_sn', '=', $info['order_sn']];
        // }
//        $info['comment_time'] = Db::name('goods_comment')->where($map)->where([['user_id', '=', $info['user_id']]])->value('create_time');
        if ($info['order_type'] == 7) {
            $final_order_sn = Db::name('order_relation')->where(['book_order_sn' => $info['order_sn']])->value('final_order_sn');
            $final_order = Db::name('order')->where(['order_sn' => $final_order_sn])->find();
            $final_order['pay_status_name'] = OrderModel::$pay_status[$final_order['pay_status']];
            $final_order['transaction_no'] = Db::name('payment_log')->where(['order_sn' => $final_order['user_id']])->value("transaction_no");
            $final_order['pay_type'] = OrderModel::$payTypes[$final_order['pay_type']];

            $info['final_order'] = $final_order;
            if ($final_order['pay_status'] == 1) {
                $info['pay_status'] = 2;
            }
        }
        if ($info['order_type'] == 5) {
            $group_id = Db::name('goods_activity_group_user')->where(['order_sn' => $order_sn])->value('group_id');
            $group = Db::name('goods_activity_group')->where(['id' => $group_id])->find();
            $info['group_info'] = $group;
            $info['group_list'] = Db::name('goods_activity_group_user')->where(['group_id' => $group_id])->select();
        }
        // halt($info['final_order']);

        //send_type=1 为自提类型,获取自提相关信息，add by wangph at 2021-4-19
        $info['pick_info'] = [];
        if ($info['send_type'] == 1) {
            $info['pick_info'] = OrderPickup::getOrderPickUp($order_sn);
        }

        $info['order_refund'] = Db::name("order_refund")->where("order_sn", $order_sn)->find();
        $info['order_goods'] = json_encode($info['order_goods'], JSON_UNESCAPED_UNICODE);
        $order_action = OrderAction::getActionLogs([["order_sn", "=", $order_sn]]);
        $this->assign('order_action', json_encode($order_action->toArray()['data'], JSON_UNESCAPED_UNICODE));
        $this->assign('order_info', $info);
//        dump($info);die;
        //按钮判断 0不显示 1显示
        $cancel_payment = 0;//取消付款
        $aftermarket = 0;//售后
        $create_invoice = 0;//生成发货单
        if ($info['status'] >= 1) {
            //判断售后 申请中的不能发货
            $goods_count = count($order_goods);
            $is_refund = false;
            if ($goods_count == 1) {
                if (empty($info['order_refund'])) {
                    $is_refund = true;
                } else {
                    $refund = Db::name("order_refund")->where([
                        ['order_sn', '=', $order_sn],
                        ['status', '>=', 0]
                    ])->order("create_time desc")
                        ->limit(1)
                        ->find();
                    if (!$refund) {
                        $is_refund = true;
                    }
                }
            }
            if ($goods_count > 1) {
                if (empty($info['order_refund'])) {
                    $is_refund = true;
                } else {
                    $where = " order_sn = '{$order_sn}' and ((refund_type=3 and status=1) or (refund_type!=3 and status!=0))";
                    $order_refund = Db::name("order_refund")->where($where)->count();
                    if ($goods_count != $order_refund) {
                        $is_refund = true;
                    }
                }
            }
            if ($info['order_type'] == 7) {
                if ($info['pay_status'] == 2 && $is_refund && empty($info['order_express'])) {
                    $create_invoice = 1;
                }
                if (empty($info['order_refund']) && $info['pay_status'] == 2) {
                    $aftermarket = 1;
                }
            } else {
                //非预售订单
                if ($is_refund && empty($info['order_express'])) {
                    if ($info['order_type'] == 5) {
                        //拼团订单
                        if (isset($group['is_full']) && $group['is_full'] == 1) {
                            $create_invoice = 1;
                        }
                    } else {
                        $create_invoice = 1;
                    }
                }
                if (empty($info['order_refund'])) {
                    $aftermarket = 1;
                }
            }
        }

        $this->assign("cancel_payment", $cancel_payment);
        $this->assign("aftermarket", $aftermarket);
        $this->assign('create_invoice', $create_invoice);

        return $this->fetch();
    }

    /**
     * 订单提醒
     * @return mixed
     */
    public function remind()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map[] = [];
        // 排序
        $order = 'id desc';
        // 数据列表
        $data_list = \think\Db::name('order_remind')->alias('r')
            ->join('order o', 'r.order_sn=o.order_sn', 'left')
            ->field('r.id,r.order_sn,r.create_time,r.status,o.create_time as order_time,o.user_id,o.order_type')
            ->order($order)
            ->paginate()
            ->each(function ($item) {
                switch ($item['order_type']) {
                    case 3:
                        $action = 'index';
                        break;
                    case 5:
                        $action = 'group';
                        break;
                    case 6:
                        $action = 'seckill';
                        break;
                    case 7:
                        $action = 'presell';
                        break;
                    case 8:
                        $action = 'presell';
                        break;
                    case 9:
                        $action = 'discount';
                        break;
                    case 10:
                        $action = 'cut';
                        break;
                    case 11:
                        $action = 'index';
                        break;
                    default:
                        $action = 'index';
                        break;
                }
                $item['order_sn_link'] = "<a href='" . url('order/' . $action, ['order_sn' => $item['order_sn']]) . "'>" . $item['order_sn'] . "</a>";
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                $item['order_time'] = date('Y-m-d H:i:s', $item['order_time']);
                return $item;
            });;

        $buttons = [
            [
                'ident' => 'detail',//按钮标识
                'title' => lang('查看详情'), //标题
                'href' => ['detail', ['order_sn' => '__order_sn__']],//链接
                'icon' => 'fa fa-eye pr5',//图标
                'class' => 'btn btn-xs mr5 btn-default btn-flat'//样式类
            ], [
                'ident' => 'read',//按钮标识
                'title' => lang('标记已读'), //标题
                'href' => ['remind_read', ['rid' => '__id__']],//链接
                'icon' => 'fa fa-eye pr5',//图标
                'class' => 'btn btn-xs mr5 btn-default btn-flat ajax-get'//样式类
            ], [
                'ident' => 'del',//按钮标识
                'title' => lang('删除'), //标题
                'href' => ['remind_del', ['rid' => '__id__']],//链接
                'icon' => 'fa fa-close pr5',//图标
                'class' => 'btn btn-xs mr5 btn-default btn-flat  ajax-get'//样式类
            ]
        ];
        $fields = [
            ['id', 'ID'],
            ['order_sn_link', lang('订单编号')],
            ['create_time', lang('提醒时间')],
            ['order_time', lang('下单时间')],
            ['user_id', lang('下单人'), 'callback', 'get_nickname'],
            ['status', lang('状态'), 'status', '', [lang('未读'), lang('已读')]],
            ['right_button', lang('操作'), 'btn']
        ];
        return Format::ins()
            ->addColumns($fields)
            ->setRightButtons($buttons)
            ->replaceRightButton(['status' => 1], '', 'read')
            ->hideCheckbox()
            ->setData($data_list)
            ->fetch();
    }

    /**
     * 提醒已读
     */
    public function remind_read($rid)
    {
        $rs = \think\Db::name('order_remind')->where('id', $rid)->setField('status', 1);
        if ($rs) {
            $this->success(lang('已读成功'));
        } else {
            $this->error(lang('修改失败'));
        }
    }

    /**
     * 提醒删除
     * @param $rid
     */
    public function remind_del($rid)
    {
        $rs = \think\Db::name('order_remind')->delete($rid);
        if ($rs) {
            //记录行为
            action_log('order_remind_delete', 'order_remind', $rid, UID, $rid);
            $this->success(lang('删除成功'));
        } else {
            $this->error(lang('删除失败'));
        }
    }

    /**
     * 订单物流
     * @return mixed
     */
    public function express()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $order_sn = input('param.order_sn');
        $express_no = input('param.express_no');
        $shipping_type = input('param.shipping_type');
        $shipping_time = input('param.shipping_time');
        $receiver_name = input('param.receiver_name');
        $receiver_mobile = input('param.receiver_mobile');

        // 查询
        $map = [];
        if ($order_sn) {
            $map[] = ['oge.order_sn', '=', $order_sn];
        }
        if ($express_no) {
            $map[] = ['oge.express_no', '=', $express_no];
        }
        if ($shipping_time) {
            $map[] = ['oge.shipping_time', '>=', strtotime($shipping_time)];
            $map[] = ['oge.shipping_time', '<', (strtotime($shipping_time . ' 23:59:59'))];
        }
        if ($shipping_type !== '' && !is_null($shipping_type)) {
            $map[] = ['oge.shipping_type', '=', $shipping_type];
        }
        if ($receiver_name) {
            $map[] = ['ogi.receiver_name', 'like', '%' . $receiver_name . '%'];
        }
        if ($receiver_mobile) {
            $map[] = ['ogi.receiver_mobile', 'like', '%' . $receiver_mobile . '%'];
        }

        // 排序
        $order = 'id desc';
        // 数据列表
        $data_list = \think\Db::name('order_goods_express')
            ->alias('oge')
            ->leftJoin('order_goods_info ogi', 'ogi.order_sn = oge.order_sn')
            ->where($map)
            ->field('oge.*, ogi.receiver_mobile, ogi.receiver_address, ogi.receiver_name')
            ->order($order)
            ->paginate(10)
            ->each(function ($item) {
                if ($item['shipping_type'] == 1) {
                    $item['shipping_type'] = lang('需要物流');
                } else {
                    $item['shipping_type'] = lang('无需物流');
                }
                return $item;
            });
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());
        return $this->fetch();
    }

    /**
     * 订单物流添加
     */
    public function express_add($order_sn)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Express');
            if (true !== $result) {
                $this->error($result);
            }
            $map = [];
            $map[] = ["order_sn", "=", $order_sn];
            $map[] = ["status", "in", [0, 1, 2]];
            $express_company = Db::name('order_refund')->where($map)->find();
            if ($express_company) {
                $this->error(lang('订单正在售后，暂不支持发货'));
            }
            $express_company = Db::name('goods_express_company')->get($data['express_company']);
            $sender = Db::name('goods_express_sender')->get($data['choice_sender']);
            $order_info = Db::name('order_goods_info')->where('order_sn', $order_sn)->find();
            // 商品列表
            $goods = OrderGoods::where('order_sn', $order_sn)->field('goods_id,sku_id')->select();
            $eorder['ShipperCode'] = $express_company['express_no'];
            $eorder['OrderCode'] = $order_sn;
            $eorder["PayType"] = $sender['pay_type'];//邮费支付方式:1现付,2到付,3月结,4第三方支付
            $_sender["Name"] = $sender['name'];  //发件人姓名
            $_sender["Mobile"] = $sender['phone'];  //发件人电话
            $_sender["ProvinceName"] = $sender['province'];  //发件人所在省
            $_sender["CityName"] = $sender['city'];  //发件人所在市
            $_sender["ExpAreaName"] = $sender['area'];  //发件人所在区
            $_sender["Address"] = $sender['address'];  //发件人地址
            $receiver["Name"] = $order_info['receiver_name'];   //收件人
            $receiver["Mobile"] = $order_info['receiver_mobile']; //收件人电话
            $receiver["ProvinceName"] = $order_info['province']; //收件人省
            $receiver["CityName"] = $order_info['city']; //收件人市
            $receiver["ExpAreaName"] = $order_info['district']; //收件人区
            $receiver["Address"] = $order_info['receiver_address']; //收件人地址
            $config = addons_config('ExpressBird');
            /**判断是否填写物流单号**/
            if ($data['express_no']) {
                $express_no = $data['express_no'];
            } else {
                if ($config['is_online']) {
                    $result = addons_action('Express/Api/submitOOrder', [$eorder, $_sender, $receiver]);
                    $result = json_decode($result, true);
                    $express_no = $result['order']['LogisticCode'];
                } else {
                    $express_no = date('YmdHis') . rand(1000, 9999);
                }
            }
            $goods_array = [];
            foreach ($goods as $item) {
                $goods_array[] = $item['goods_id'] . '_' . $item['sku_id'];
            }
            $param['order_sn'] = $order_sn;
            $param['order_goods_id_array'] = implode(',', $goods_array);
            $param['express_serial_number'] = date("Ymd", time()) . rand(100000, 999999);
            $param['express_name'] = '';
            $param['shipping_type'] = 1;
            $param['express_company_id'] = $express_company['aid'];
            $param['express_company'] = $express_company['name'];
            $param['express_no'] = $express_no;
            $param['uid'] = UID;
            $param['shipping_time'] = time();
            Db::startTrans();
            try {
                $res = OrderGoodsExpress::create($param);
                // 修改订单状态
                Db::name('order')->where('order_sn', $order_sn)->setField(['sender_id' => $data['choice_sender']]);
                //修改消息提醒
                $num = Db::name('order_remind')->where('order_sn', $order_sn)->count();
                if ($num) {
                    Db::name('order_remind')->where('order_sn', $order_sn)->setField('status', 1);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error(lang('添加失败'), cookie('__forward__'));
            }
            //记录行为
            unset($param['__token__']);
            $details = json_encode($param, JSON_UNESCAPED_UNICODE);
            action_log('order_express_add', 'order_goods_express', $res->id, UID, $details);

            /*            try {
                            $xcx_openid=Db::name('order')->alias('o')->join('user_info ui', 'ui.user_id=o.user_id', 'left')->where(['o.order_sn'=>$order_sn])->field('ui.xcx_openid,o.order_type')->find();
                            $miniprogram_state=config('app_type')=='release'?'formal':'trial';
                            $mini_option=[
                                'touser'=> $xcx_openid['xcx_openid'],
                                'template_id'=>'925vRp0JDuTBcBOVXz-xYcG5wSS5lI4J5SLkVcydyIo',
                                'page'=>'pagesC/orders/order_detail?order_sn='.$order_sn.'&order_type='.$xcx_openid['order_type'].'&openModel=share&news_type=shipped',
                                'data'=>[
                                    'character_string1'=>['value'=>$order_sn],
                                    'thing2'=>['value'=>'--订单查看--'],
                                    'phrase3'=>['value'=>lang('配送中')],
                                    'character_string4'=>['value'=>$express_no],
                                    'date5'=>['value'=>date('Y-m-d H:i:s', $param['shipping_time'])]
                                ],
                                'miniprogram_state'=>$miniprogram_state
                            ];
                            addons_action('WeChat', 'MiniPay', 'subscribe', [$mini_option]);
                        } catch (\Exception $e) {
                            //dump($e->getMessage());
                        }*/
            $this->success(lang('添加成功'), cookie('__forward__'));
        }
        $sender_list = Db::name('goods_express_sender')->column('id,name');
        $express_company = Db::name('goods_express_company')->column('aid,name');
        $fields = [
            ['type' => 'hidden', 'name' => 'order_sn', 'value' => $order_sn],
            //['type' => 'text', 'name' => 'express_name', 'title' => lang('包裹名称'), 'tips' => '', 'attr' => ''],
            //['type' => 'radio', 'name' => 'shipping_type', 'title' => lang('发货方式'), 'tips' => '', 'attr' => '', 'extra' => [lang('无需物流'), lang('需要物流')], 'value' => 1],
            //['type' => 'select', 'name' => 'express_company_id', 'title' => lang('快递公司'), 'tips' => '', 'extra' => $express_company_data],
            //['type' => 'hidden', 'name' => 'express_company', 'value' => current($express_company_data)],
            ['type' => 'select', 'name' => 'express_company', 'title' => lang('请选择快递公司'), 'tips' => '', 'extra' => $express_company],
            ['type' => 'select', 'name' => 'choice_sender', 'title' => lang('请选择发货人'), 'tips' => '', 'extra' => $sender_list],
            ['type' => 'text', 'name' => 'express_no', 'title' => lang('请输入物流单号'), 'tips' => '', 'attr' => ''],
            /*
            ['type' => 'select', 'name' => 'PayType', 'title' => lang('邮费支付方式'), 'tips' => '', 'extra' =>[1=>lang('现付'),2=>lang('到付'),3=>lang('月结'),4=>lang('第三方支付')]],
            ['type' => 'text', 'name' => 'SenderName', 'title' => lang('发货人'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'SenderMobile', 'title' => lang('电话'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'ProvinceName', 'title' => lang('省'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'CityName', 'title' => lang('市'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'ExpAreaName', 'title' => lang('区'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'Address', 'title' => lang('街道'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'memo', 'title' => lang('备注'), 'tips' => '', 'attr' => ''],*/
        ];
        $this->assign('page_title', lang('添加物流'));
        $this->assign('form_items', $fields);
        $this->assign('set_script', ['/static/goods/js/express.js']);
        return $this->fetch('admin@public/add');
    }

    /**
     * 删除物流发货单
     */
    public function express_del($eid)
    {
        $rs = \think\Db::name('order_goods_express')->delete($eid);
        if ($rs) {
            //记录行为
            action_log('order_express_delete', 'order_goods_express', $eid, UID, $eid);
            $this->success(lang('删除成功'), cookie('__forward__'));
        } else {
            $this->error(lang('删除失败'), cookie('__forward__'));
        }
    }

    /**
     * 订单售后
     * @return mixed
     */
    public function refund()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $server_no = input('param.server_no','','trim');
        $status = input('param.status', "0");
        $order_sn = input('param.order_sn','','trim');
        $status = $status ? $status : "0";
        $this->assign('status', $status);
        $refund_time = input('param.refund_time');
        if ($server_no) {
            $map[] = ['rf.server_no', '=', $server_no];
        }
        if ($order_sn) {
            $map[] = ['rf.order_sn', '=', $order_sn];
        }
        if ($refund_time) {
            $map[] = ['rf.create_time', '>=', strtotime($refund_time)];
            $map[] = ['rf.create_time', '<', (strtotime($refund_time . ' 23:59:59'))];
        }

        $map[] = ['rf.status', '=', $status];


        // 排序
        $order = 'rf.create_time desc';
        // 数据列表
        $tab[] = ['title' => lang('待处理'), 'url' => url('goods/order/refund', 'status=0'), 'value' => "0"];
        $tab[] = ['title' => lang('待收货'), 'url' => url('goods/order/refund', 'status=1'), 'value' => '1'];
        $tab[] = ['title' => lang('待退款'), 'url' => url('goods/order/refund', 'status=2'), 'value' => '2'];
        $tab[] = ['title' => lang('已驳回'), 'url' => url('goods/order/refund', 'status=-1'), 'value' => '-1'];
        $tab[] = ['title' => lang('已完成'), 'url' => url('goods/order/refund', 'status=3'), 'value' => '3'];
        $tab[] = ['title' => lang('已取消'), 'url' => url('goods/order/refund', 'status=-2'), 'value' => "-2"];
        $this->assign('tab_list', $tab);
        $data_list = RefundModel::get_list($map, $order);
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());
        $this->assign('orderStatus', [0 => lang('驳回'), 1 => lang('同意')]);
        return $this->fetch();
    }

    public function refund_express()
    {
        $rfid = input('param.rfid');
        $order_refund = Db::name('order_refund')->get($rfid);
        $order_refund['express_company'] = Db::name('goods_express_company')->where(['aid' => $order_refund['express_company_id']])->value('name');
        $express_company = Db::name('goods_express_company')->get($order_refund['express_company_id']);
        $this->assign('order_refund', $order_refund);
        $ex = addons_action('ExpressBird/Api/getOrderTracesByJson', [$order_refund['server_no'], $express_company['express_no'], $order_refund['express_no']]);
        $this->assign('ex', json_decode($ex, true));
        return $this->fetch();
    }

    public function refund_account()
    {
        $rfid = input('param.rfid');
        $order_refund = Db::name('order_refund')->get($rfid);
        $user_account = Db::name('user_withdraw_account')->where(['user_id' => $order_refund['user_id']])->find();
        $this->assign('user_account', $user_account);
        $this->assign('order_refund', $order_refund);
        return $this->fetch();
    }

    /**
     * 操作退款状态
     */
    public function refund_change_status()
    {
        $rfid = input('param.rfid');
        $val = input('param.val');
        $is_defective = input('param.is_defective');
//        $is_defective = 1;
        $key_name = '';
        Db::startTrans();
        try {
            $order_sn = Db::name('order_refund')->get($rfid);
            if (!$order_sn) {
                exception('没有对应的售后服务ID');
            }
            $goods = Db::name('goods')->where('id', $order_sn['goods_id'])->find();
            $goods_sn = $goods['sn'];
            $res = Db::name('order_refund')->where(['id' => $rfid])->update(['status' => $val, 'refund_time' => time()]);
            if (!is_null($order_sn['sku_id'])) {
                $sku = GoodsSku::get($order_sn['sku_id']);
                $key_name = $sku['key_name'];
                $goods_sn = $sku['sku_sn'];
            }
            //是否进入次品库
//            if ($res && $is_defective == 1) {
//                $admin = Db::name("admin")->get(UID);
//                $save_data = [
//                    "order_sn" => $order_sn['order_sn'],
//                    "express_no" => $order_sn['express_no'],
//                    "goods_id" => $order_sn['goods_id'],
//                    "sku_id" => $order_sn['sku_id'],
//                    "num" => $order_sn['num'],
//                    "goods_name" => $goods['name'],
//                    "sku_name" => $key_name,
//                    "remark" => lang('管理员操作'),
//                    "uid" => UID,
//                    "admin_name" => $admin['username'],
//                    "goods_sn" => $goods_sn,
//                    "create_time" => time(),
//                ];
//                $gd_res = Db::name("goods_defective")->insert($save_data);
//                if (!$gd_res) {
//                    exception(lang('录入破损商品失败'));
//                }
//                Db::name('order_refund')->where("id", $rfid)->setField("is_defective", 1);
//            }
            switch (intval($val)) {
                case 3:
                    //先处理订单状态
                    $arr = Db::name("order_goods_list")->where(['order_sn' => $order_sn['order_sn'], 'order_status' => [2, 3, 4]])->select();
                    $consumption = 0; // 定义会员成长值
                    foreach ($arr as $key => $value) {
                        $this->delShareMoney($order_sn['order_sn'], $value['goods_id']);

                        // zenghu ADD 计算商品配置的权益值
                        $goodsInfo = Db::name('goods')->field('empirical')->get($value['goods_id']);
                        $consumption += $goodsInfo['empirical']; // 累计权益值
                    }
                    $order_goods_list_num = Db::name("order_goods_list")->where(['order_sn' => $order_sn['order_sn']])->count('goods_id');
                    $order_refund_num = Db::name("order_refund")->where(['order_sn' => $order_sn['order_sn']])->count('goods_id');
                    if ($order_goods_list_num == $order_refund_num) {
                        $order = Db::name("order")->where(['order_sn' => $order_sn['order_sn']])->find();
                        if ($order['order_type'] == 5) {
                            Db::name("order")->where(['order_sn' => $order_sn['order_sn']])->update(['status' => -1]);
                            Db::name("goods_activity_group_user")->where(['order_sn' => $order_sn['order_sn']])->delete();
                        } else {
                            Db::name("order")->where(['order_sn' => $order_sn['order_sn']])->update(['status' => 3]);
                        }
                    }
                    $order = Db::name('order')->where(['order_sn' => $order_sn['order_sn']])->find();
                    //商品到残品库时不改变库存
                    if ($is_defective == 0) {
                        //修改库存
                        if ($order_sn['sku_id'] != 0) {
                            Db::name('goods_sku')->where(['sku_id' => $order_sn['sku_id']])->setInc('stock', $order_sn["num"]);
                        } else {
                            Db::name('goods')->where(['id' => $order_sn['goods_id']])->setInc('stock', $order_sn["num"]);
                        }
                    }
                    $msg = new SystemMessageModel();
                    switch ($order['pay_type']) {
                        case 'balance':
                            //$coupon_money (退款优惠券金额=优惠券额度*商品金额/订单总金额)
                            $coupon_money = bcmul($order['coupon_money'], bcdiv($order_sn['goods_money'], $order['order_money'], 2), 2);
                            //减去优惠额度
//                            $refund_money = bcsub($order_sn['goods_money'], $coupon_money, 2);
                                $refund_money = $order_sn['goods_money'];

                            /*$data = DB::query("select ls.goods_money,oc.money from (select a.goods_money,b.cid from lb_order_refund as a inner join lb_operation_coupon_record as b on a.order_sn = b.order_sn where a.order_sn='".$order_sn['order_sn']."') as ls inner join lb_operation_coupon as oc on oc.id=ls.cid limit 1");
                            if ($data) {
                                $refund_money = ($data[0]['goods_money']-$data[0]['money']);
                            } else {
                                $refund_money = $order_sn['goods_money'];
                            }*/

                            $back_refund['out_refund_no'] = $order_sn['server_no'];
                            $back_refund['refund_fee'] = $refund_money * 100;
                            $back_refund['refund_id'] = 0;
                            if (OrderModel::back_verify($back_refund)) {
                                $_user_money = Db::name('user')->where(['id' => $order_sn['user_id']])->value('user_money');
                                \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money, $refund_money, 5, $remark = lang('退款余额'), '', 0, $goods['discounts'] * $order_sn['num']);
                                $user_money_log_res = Db::name('user_money_log')->where(['order_no' => $order_sn['order_sn'], 'change_type' => 9, 'goods_id' => $order_sn['goods_id']])->find();
                                if ($user_money_log_res) {
                                    \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money + $refund_money, (0 - abs($user_money_log_res['change_money'])), 9, $remark = lang('扣除返现金额'), $order_sn['order_sn'], 0, $order_sn['goods_id']);
                                    $ms = ['type' => 1, 'msg_type' => 1, 'template_type' => 1, 'to_user_id' => $order['user_id'], 'title' => lang('系统通知'), 'content' => lang('您购买的') . $goods['name'] . '商品已为您退款，请注意查收。'];
                                    $msg->create($ms);
                                }
                            }
                            break;
                        case 'minipay_mix':
                            //$coupon_money (退款优惠券金额=优惠券额度*商品金额/订单总金额)
                            //$coupon_money=bcmul($order['coupon_money'],bcdiv($order_sn['goods_money'],$order['order_money'],2),2);
                            //减去优惠额度
                            //$refund_money = bcsub($order_sn['goods_money'],$coupon_money,2);
                            $data = DB::query("select ls.goods_money,oc.money from (select a.goods_money,b.cid from lb_order_refund as a inner join lb_operation_coupon_record as b on a.order_sn = b.order_sn where a.order_sn='" . $order_sn['order_sn'] . "') as ls inner join lb_operation_coupon as oc on oc.id=ls.cid limit 1");

                            if ($data) {
                                $refund_money = ($data[0]['goods_money'] - $data[0]['money']);
                            } else {
                                $refund_money = $order_sn['goods_money'];
                            }
                            $back_refund['out_refund_no'] = $order_sn['server_no'];
                            $back_refund['refund_fee'] = $refund_money * 100;
                            $back_refund['refund_id'] = 0;
                            if (OrderModel::back_verify($back_refund)) {
                                $_user_money = Db::name('user')->where(['id' => $order_sn['user_id']])->value('user_money');
                                \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money, $refund_money, 5, $remark = lang('退款余额'), '', 0, $goods['discounts'] * $order_sn['num']);
                                $user_money_log_res = Db::name('user_money_log')->where(['order_no' => $order_sn['order_sn'], 'change_type' => 9, 'goods_id' => $order_sn['goods_id']])->find();
                                if ($user_money_log_res) {
                                    \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money + $refund_money, (0 - abs($user_money_log_res['change_money'])), 9, $remark = lang('扣除返现金额'), $order_sn['order_sn'], 0, $order_sn['goods_id']);
                                }
                            }
                            break;
                        case 'minipay':
                            //$coupon_money (退款优惠券金额=优惠券额度*商品金额/订单总金额)
                            //$coupon_money=bcmul($order['coupon_money'],bcdiv($order_sn['goods_money'],$order['order_money'],2),2);
                            //减去优惠额度
                            //$refund_money = bcsub($order_sn['goods_money'],$coupon_money,2);
                            $data = DB::query("select ls.goods_money,oc.money from (select a.goods_money,b.cid from lb_order_refund as a inner join lb_operation_coupon_record as b on a.order_sn = b.order_sn where a.order_sn='" . $order_sn['order_sn'] . "') as ls inner join lb_operation_coupon as oc on oc.id=ls.cid limit 1");
                            if ($data) {
                                $refund_money = ($data[0]['goods_money'] - $data[0]['money']);
                            } else {
                                $refund_money = $order_sn['goods_money'];
                            }

                            $userdata = Db::name('user')->where(['id' => $order_sn['user_id']])->find();
                            //插入流水信息
                            // $arr = array(
                            //     'user_id' => $order_sn['user_id'],
                            //     'change_money' => (0-$goods['discounts']*$order_sn['num']),
                            //     'before_money' => $userdata['user_money'],
                            //     'after_money' => ($userdata['user_money']-$goods['discounts']*$order_sn['num']),
                            //     'change_type' => 10,
                            //     'remark' => lang('扣除返现金额'),
                            //     'create_time'=>time(),
                            //     'order_no' => $order_sn['order_sn'],
                            // );
                            // $result = Db::name('user_money_log')->insert($arr);
                            // if (!$result) {
                            //     throw new \Exception(lang('插入流水记录失败'));
                            // }
                            // 扣除返现金额
                            $user_money_log_res = Db::name('user_money_log')->where(['order_no' => $order_sn['order_sn'], 'change_type' => 9, 'goods_id' => $order_sn['goods_id']])->find();
                            if ($user_money_log_res) {
                                $_user_money = Db::name('user')->where(['id' => $order_sn['user_id']])->value('user_money');
                                \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money, (0 - abs($user_money_log_res['change_money'])), 9, $remark = lang('扣除返现金额'), $order_sn['order_sn'], 0, $order_sn['goods_id']);

                                // zenghu ADD 分享赚佣金售后退款后需要扣除上级已分佣金额 2020年8月28日16:26:32
                            }
                            $transaction_no = Db::name("payment_log")->where(['order_sn' => $order['order_sn'], 'status' => 1])->find();
                            $refund_option['total_fee'] = $order['payable_money'];
                            $refund_option['out_trade_no'] = $transaction_no['transaction_no'];
                            $refund_option['server_no'] = $order_sn['server_no'];
                            $refund_option['refund_fee'] = $refund_money;
                            $refund_option['refund_reason'] = $order_sn['refund_reason'];
                            $arr = addons_action('WeChat', 'MiniPay', 'backPay', [$refund_option]);
                            if (!$arr) {
                                exception(lang('微信退款失败'));
                            }
                            break;
                    }
                    $userData = Db::name('user')->get($order_sn['user_id']);
                    Marketing::add_user_marketing($refund_money, $order_sn['order_sn'], $userData, 2);

                    // zenghu ADD 会员降级操作 2020年8月17日18:19:19
                    /*$empirical = (($userData['empirical'] - $consumption) < 0) ? 0 : ($userData['empirical'] - $consumption); // 会员成长值
                    $totalConsumptionMoney = (($userData['total_consumption_money'] - $refund_money) < 0)  ? 0 : ($userData['total_consumption_money'] - $refund_money); // 会员消费金额
                    DB::query("
                        UPDATE
                            lb_user u
                        SET u.user_level = IFNULL((
                            SELECT
                                levelid
                            FROM lb_user_level
                            WHERE `status` = 1
                            AND (upgrade_score < {$empirical} OR upgrade_total_money < {$totalConsumptionMoney})
                            ORDER BY levelid DESC
                            LIMIT 1
                        ), u.user_level), u.empirical = {$empirical}, u.total_consumption_money = {$totalConsumptionMoney}
                        WHERE u.id = {$order_sn['user_id']};
                    ");
                    writeLog('会员降级操作SQL', DB::getLastsql(), 'level');*/

                    Db::name('order_refund')->where('id', $rfid)->setField('status', 3);
                    break;
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            /*            if( $is_defective == 1 ){
                            return json_encode(["code"=>0,"data"=>[],"msg"=>$e->getMessage()],true);
                        }else{*/
            $this->error($e->getMessage());
//            }
        }
        /*        if( $is_defective == 1 ){
                    echo 111;die;
                    if ($res) {
                        return json_encode(["code"=>1,"data"=>["url"=>cookie('__forward__')],"msg"=>lang('确认成功')],true);
                    } else {
                        return json_encode(["code"=>0,"data"=>[],"msg"=>lang('确认失败')],true);
                    }
                }else{
        //            echo 2222;die;*/
        if ($res) {
            $this->success(lang('确认成功'), cookie('__forward__'));
        } else {
            $this->error(lang('确认失败'));
        }
//        }
    }

    public function delShareMoney($order, $goods_id)
    {
        Db::name("share_seven_day_log")->where(['order_sn' => $order, 'goods_id' => $goods_id])->delete();
    }


    /**
     * 售后原因
     */
    public function refund_detail($rfid)
    {
        $detail = RefundModel::get($rfid);
        $detail['refund_picture'] = get_files_url($detail['refund_picture']);
        $refundCause = RefundModel::$refundCause;
//        $detail['refund_reason'] = $refundCause[$detail['refund_reason']];

        $this->assign('detail', $detail);
        $info = OrderInfo::get_order_detail($detail['order_sn']);
        $info['order_status'] = OrderModel::$order_status[$info['status']];
        $this->assign('order_info', $info);
        return $this->fetch();
    }

    /**
     * 退货确认
     */
    public function refund_sure($rfid)
    {
        $order_sn = RefundModel::where('id', $rfid)->find();
        $order_status = Db::name('order')->where('order_sn', $order_sn['order_sn'])->find();
        $goods = Db::name('goods')->where('id', $order_sn['goods_id'])->find();
        $user = Db::name('user')->where('id', $order_sn['user_id'])->find();
        if ($order_status['status'] == 3 && $order_status['status'] == 4) {
            if ($goods['discounts'] > 0) {
                if ($user['user_money'] < $goods['discounts']) {
                    $this->error(lang('操作失败，此用户账户余额不足以扣除自购反金额'));
                }
            }
        }
        Db::startTrans();
        try {
            $order = Db::name('order')->where('order_sn', $order_sn['order_sn'])->lock(true)->find();
            if (!$order) {
                exception(lang('订单不可操作'));
            }
            $order_refund_data = Db::name('order_refund')->where('id', $rfid)->find();
            if ($order_refund_data['status'] != 0) {
                exception(lang('售后状态已发生改变，请刷新后重试'));
            }
//            if ($order['cost_integral'] > 0) {
//                //积分商城兑换退积分
//                $score_log = ScoreLog::change($order['user_id'], $order['cost_integral'], 8, ScoreLog::$types[8], $order['order_sn']);
//
//                if (!$score_log) {
//                    exception(lang('积分回退失败'));
//                }
//            }

            Db::name('order_refund')->where('id', $rfid)->setField('status', 1);
            if ($order_sn['refund_type'] == 1) {
                //先处理订单状态
                $order_goods_list = Db::name("order_goods_list")->where(['order_sn' => $order_sn['order_sn']])->select();
                $consumption = 0; // 定义会员成长值
                foreach ($order_goods_list as $key => $value) {
                    // zenghu ADD 计算商品配置的权益值
                    $goodsInfo = Db::name('goods')->field('empirical')->get($value['goods_id']);
                    $consumption += $goodsInfo['empirical']; // 累计权益值
                    //减活动库存
                }

                $order_goods_list_num = count($order_goods_list);
                //售后订单数，不含驳回和用户取消的状态
                $order_refund_num = Db::name("order_refund")->where(
                    [
                        ['order_sn', '=', $order_sn['order_sn']],
                        ['status', '>=', '0']
                    ])
                    ->count('goods_id');
                //售后订单数 == 商品总数
                if ($order_goods_list_num == $order_refund_num) {
                    Db::name("order")->where(['order_sn' => $order_sn['order_sn']])->update(['status' => 3]);
                }
                //已收到货：申请退款（注意不是退货），这个情况下是不需要恢复库存的。
                if($order['status'] < 3) {
                    //修改库存
                    if ($order_sn['sku_id'] != 0) {
                        $sku_info = GoodsSku::get($order_sn['sku_id']);
                        Db::name('goods_sku')->where(['sku_id' => $order_sn['sku_id']])->setInc('stock', $order_sn["num"]);
                        $stock_before = $sku_info['stock'];
                        $stock_after = $sku_info['stock'] + $order_sn['num'];
                        $sn = $sku_info['sku_sn'];
                    } else {
                        $goods_info = Goods::get($order_sn['goods_id']);
                        $stock_before = $goods_info['stock'];
                        $stock_after = $goods_info['stock'] + $order_sn['num'];
                        $sn = $goods_info['sn'];
                        Db::name('goods')->where(['id' => $order_sn['goods_id']])->setInc('stock', $order_sn["num"]);
                    }
                    //变更活动库存
                    $activity_id = Db::name("order_goods_list")->where([
                        'order_sn' => $order_sn['order_sn'],
                        'goods_id' => $order_sn['goods_id'],
                        'sku_id' => $order_sn['sku_id']
                    ])->value("activity_id");
                    //减活动销量
                    Db::name("goods_activity_details")->where([
                        'goods_id' => $order_sn['goods_id'],
                        'sku_id' => $order_sn['sku_id'],
                        'activity_id' => $activity_id ? $activity_id : 0
                    ])->setDec("sales_sum", $order_sn['num']);
                    //增加活动商品库存
                    Db::name("goods_activity_details")->where([
                        'goods_id' => $order_sn['goods_id'],
                        'sku_id' => $order_sn['sku_id'],
                        'activity_id' => $activity_id ? $activity_id : 0
                    ])->setInc("stock", $order_sn['num']);
                    //添加库存变动日志
                    GoodsStockLog::AddStockLog(
                        $order_sn['goods_id'],
                        $order_sn['sku_id'] ?? 0,
                        $order_sn['order_sn'],
                        $stock_before,
                        $order_sn['num'],
                        $stock_after,
                        1,
                        UID,
                        lang('管理员操作退货'),
                        $sn
                    );
                }
                switch ($order['pay_type']) {
                    case 'balance':
                        //$coupon_money (退款优惠券金额=优惠券额度*商品金额/订单总金额)
                        //$coupon_money=bcmul($order['coupon_money'],bcdiv($order_sn['goods_money'],$order['order_money'],2),2);
                        //减去优惠额度
                        //$refund_money = bcsub($order_sn['goods_money'],$coupon_money,2);
//                        $res = DB::query("select ls.goods_money,oc.money from (select a.goods_money,b.cid from lb_order_refund as a inner join lb_operation_coupon_record as b on a.order_sn = b.order_sn where a.order_sn='" . $order_sn['order_sn'] . "') as ls inner join lb_operation_coupon as oc on oc.id=ls.cid limit 1
//                        ");
//                        if ($res) {
//                            $refund_money = ($res[0]['goods_money'] - $res[0]['money']);
//                        } else {
//                            $refund_money = $order_sn['goods_money'];
//                        }
//                        $order['real_money'];
                        if($order['status'] == 1){
                            $order_count = Db::name('order_goods_list')->where('order_sn',$order['order_sn'])->count();
                            if($order_count == 1){
                                $refund_money = $order['real_money'];
                            }else{
                                $refund_money = $order_sn['goods_money'];
                            }

                        }else{
                            $refund_money = $order_sn['goods_money'];
                        }


                        $back_refund['out_refund_no'] = $order_sn['server_no'];
                        $back_refund['refund_fee'] = $refund_money * 100;
                        $back_refund['refund_id'] = 0;
                        $res = OrderModel::back_verify($back_refund);
//                        if ($res) {
                        $_user_money = Db::name('user')->where(['id' => $order_sn['user_id']])->value('user_money');
                        \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money, $refund_money, 5, $remark = lang('退款余额'), $order_sn['order_sn'], 0, $order_sn['goods_id']);

//                            $user_money_log_res = Db::name('user_money_log')->where(['order_no' => $order_sn['order_sn'], 'change_type' => 9])->find();
//
//                            if ($user_money_log_res) {
//                                \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money + $refund_money, (0 - abs($user_money_log_res['change_money'])), 9, $remark = lang('退还购买回馈'), $order_sn['order_sn'], 0, $order_sn['goods_id']);
//                            }
//                        }
                        break;

                    case 'minipay_mix':
                        //$coupon_money (退款优惠券金额=优惠券额度*商品金额/订单总金额)
                        //$coupon_money=bcmul($order['coupon_money'],bcdiv($order_sn['goods_money'],$order['order_money'],2),2);
                        //减去优惠额度
                        //$refund_money = bcsub($order_sn['goods_money'],$coupon_money,2);
                        $res = DB::query("select ls.goods_money,oc.money from (select a.goods_money,b.cid from lb_order_refund as a inner join lb_operation_coupon_record as b on a.order_sn = b.order_sn where a.order_sn='" . $order_sn['order_sn'] . "') as ls inner join lb_operation_coupon as oc on oc.id=ls.cid limit 1
                        ");
                        if ($res) {
                            $refund_money = ($res[0]['goods_money'] - $res[0]['money']);
                        } else {
                            $refund_money = $order_sn['goods_money'];
                        }
                        $back_refund['out_refund_no'] = $order_sn['server_no'];
                        $back_refund['refund_fee'] = $refund_money * 100;
                        $back_refund['refund_id'] = 0;
                        $res = OrderModel::back_verify($back_refund);
                        if ($res) {
                            $_user_money = Db::name('user')->where(['id' => $order_sn['user_id']])->value('user_money');
                            \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money, $refund_money, 5, $remark = lang('退款余额'), $order_sn['order_sn'], 0, $order_sn['goods_id']);
                            $user_money_log_res = Db::name('user_money_log')->where(['order_no' => $order_sn['order_sn'], 'change_type' => 9, 'goods_id' => $order_sn['goods_id']])->find();
                            if ($user_money_log_res) {
                                \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money + $refund_money, (0 - abs($user_money_log_res['change_money'])), 9, $remark = lang('退还购买回馈'), $order_sn['order_sn'], 0, $order_sn['goods_id']);
                            }
                        }
                        break;

                    case 'minipay':
                        //$coupon_money (退款优惠券金额=优惠券额度*商品金额/订单总金额)
                        if($order['status'] == 1){
                            $order_count = Db::name('order_goods_list')->where('order_sn',$order['order_sn'])->count();
                            if($order_count == 1){
                                $refund_money = $order['real_money'];
                            }else{
                                $refund_money = $order_sn['goods_money'];
                            }

                        }else{
                            $refund_money = $order_sn['goods_money'];
                        }
                        //处理退款状态
                        $back_refund['out_refund_no'] = $order_sn['server_no'];
                        $back_refund['refund_fee'] = $refund_money * 100;
                        $back_refund['refund_id'] = 0;
                        $res = OrderModel::back_verify($back_refund);

                        $transaction_no = Db::name("payment_log")->where(['order_sn' => $order['order_sn'], 'status' => 1, 'pay_type'=>'minipay'])->find();
                        $refund_option['total_fee'] = $transaction_no['amount'];
                        $refund_option['transaction_id'] = $transaction_no['transaction_no'];
                        $refund_option['out_trade_no'] = $transaction_no['order_sn'];
                        $refund_option['server_no'] = $order_sn['server_no'];
                        $refund_option['refund_fee'] = $refund_money;
                        $refund_option['refund_reason'] = $order_sn['refund_reason'];
//                        dump($refund_option);die;
                        $refund_res = addons_action('WeChat', 'MiniPay', 'backPay', [$refund_option]);
                        if ($refund_res['result_code'] != 'SUCCESS') {
                            exception(lang('微信退款失败').';err_code:'.$refund_res['err_code'].';err_code_des:'.$refund_res['err_code_des']);
                        }
                        break;

                    case 'wxpay':
                        //$coupon_money (退款优惠券金额=优惠券额度*商品金额/订单总金额)
                        //$coupon_money=bcmul($order['coupon_money'],bcdiv($order_sn['goods_money'],$order['order_money'],2),2);
                        //减去优惠额度
                        //$refund_money = bcsub($order_sn['goods_money'],$coupon_money,2);
                        $res = DB::query("select ls.goods_money,oc.money from (select a.goods_money,b.cid from lb_order_refund as a inner join lb_operation_coupon_record as b on a.order_sn = b.order_sn where a.order_sn='" . $order_sn['order_sn'] . "') as ls inner join lb_operation_coupon as oc on oc.id=ls.cid limit 1
                        ");
                        if ($res) {
                            $refund_money = ($res[0]['goods_money'] - $res[0]['money']);
                        } else {
                            $refund_money = $order_sn['goods_money'];
                        }
                        $userdata = Db::name('user')->where(['id' => $order_sn['user_id']])->find();
                        //插入流水信息
                        $arr = array(
                            'user_id' => $order_sn['user_id'],
                            'change_money' => (0 - $goods['discounts'] * $order_sn['num']),
                            'before_money' => $userdata['user_money'],
                            'after_money' => ($userdata['user_money'] - $goods['discounts'] * $order_sn['num']),
                            'change_type' => 10,
                            'remark' => lang('扣除返现金额'),
                            'create_time' => time(),
                            'order_no' => $order_sn['order_sn'],
                        );
                        $result = Db::name('user_money_log')->insert($arr);
                        if (!$result) {
                            throw new \Exception(lang('插入流水记录失败'));
                        }
                        //扣除返现金额
                        $user_money_log_res = Db::name('user_money_log')->where(['order_no' => $order_sn['order_sn'], 'change_type' => 9, 'goods_id' => $order_sn['goods_id']])->find();
                        if ($user_money_log_res) {
                            $_user_money = Db::name('user')->where(['id' => $order_sn['user_id']])->value('user_money');
                            \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money, (0 - abs($user_money_log_res['change_money'])), 9, $remark = lang('退还购买回馈'), $order_sn['order_sn'], 0, $order_sn['goods_id']);
                        }

                        $transaction_no = Db::name("payment_log")->where(['order_sn' => $order['order_sn'], 'status' => 1])->find();
                        $transaction_id = $transaction_no['transaction_no'] ?? '';
                        $out_trade_no = $transaction_no['order_sn'] ?? '';
                        $refund_option['total_fee'] =  $transaction_no['amount'];
                        $refund_option['transaction_id'] = $transaction_id;
                        $refund_option['out_trade_no'] = $out_trade_no;
                        $refund_option['server_no'] = $order_sn['server_no'];
                        $refund_option['refund_fee'] = $transaction_no['amount'];
                        $refund_option['refund_reason'] = $order_sn['refund_reason'];
                        $refund_res = addons_action('WeChat', 'AppPay', 'backPay', [$refund_option]);
                        if (!$refund_res) {
                            exception(lang('微信退款失败'));
                        }
                        break;
                    case 'alipay':
                        //$coupon_money (退款优惠券金额=优惠券额度*商品金额/订单总金额)
                        //$coupon_money=bcmul($order['coupon_money'],bcdiv($order_sn['goods_money'],$order['order_money'],2),2);
                        //减去优惠额度
                        //$refund_money = bcsub($order_sn['goods_money'],$coupon_money,2);
                        $res = DB::query("select ls.goods_money,oc.money from (select a.goods_money,b.cid from lb_order_refund as a inner join lb_operation_coupon_record as b on a.order_sn = b.order_sn where a.order_sn='" . $order_sn['order_sn'] . "') as ls inner join lb_operation_coupon as oc on oc.id=ls.cid limit 1
                        ");
                        if ($res) {
                            $refund_money = ($res[0]['goods_money'] - $res[0]['money']);
                        } else {
                            $refund_money = $order_sn['goods_money'];
                        }
                        $userdata = Db::name('user')->where(['id' => $order_sn['user_id']])->find();
                        //插入流水信息
                        $arr = array(
                            'user_id' => $order_sn['user_id'],
                            'change_money' => (0 - $goods['discounts'] * $order_sn['num']),
                            'before_money' => $userdata['user_money'],
                            'after_money' => ($userdata['user_money'] - $goods['discounts'] * $order_sn['num']),
                            'change_type' => 10,
                            'remark' => lang('扣除返现金额'),
                            'create_time' => time(),
                            'order_no' => $order_sn['order_sn'],
                        );
                        $result = Db::name('user_money_log')->insert($arr);
                        if (!$result) {
                            throw new \Exception(lang('插入流水记录失败'));
                        }
                        //扣除返现金额
                        $user_money_log_res = Db::name('user_money_log')->where(['order_no' => $order_sn['order_sn'], 'change_type' => 9, 'goods_id' => $order_sn['goods_id']])->find();
                        if ($user_money_log_res) {
                            $_user_money = Db::name('user')->where(['id' => $order_sn['user_id']])->value('user_money');
                            \app\user\model\MoneyLog::changeMoney($order_sn['user_id'], $_user_money, (0 - abs($user_money_log_res['change_money'])), 9, $remark = lang('退还购买回馈'), $order_sn['order_sn'], 0, $order_sn['goods_id']);
                        }
                        $order_info = OrderModel::where([
                            'order_sn' => $order['order_sn'],
                            'pay_status' => 1
                        ])->field("order_sn,transaction_id")
                            ->find();
                        $transaction_id = $order_info['transaction_id'] ?? '';
                        $out_trade_no = $order_info['order_sn'] ?? '';

                        $refund_option['total_fee'] = $order['payable_money'];
                        $refund_option['transaction_id'] = $transaction_id;
                        $refund_option['out_trade_no'] = $out_trade_no;
                        $refund_option['server_no'] = $order_sn['server_no'];
                        $refund_option['refund_fee'] = $refund_money;
                        $refund_option['refund_reason'] = $order_sn['refund_reason'];
                        $refund_res = addons_action('Alipay', 'Aop', 'refund', [$refund_option]);
                        if (!$refund_res) {
                            exception(lang('支付宝退款失败'));
                        }
                        break;

                }
                $userData = Db::name('user')->get($order_sn['user_id']);
                Marketing::add_user_marketing($refund_money, $order_sn['order_sn'], $userData, 2);

                // zenghu ADD 会员降级操作 2020年8月29日11:56:35
                //$empirical = (($userData['empirical'] - $consumption) < 0) ? 0 : ($userData['empirical'] - $consumption); // 会员成长值
                //$totalConsumptionMoney = (($userData['total_consumption_money'] - $refund_money) < 0)  ? 0 : ($userData['total_consumption_money'] - $refund_money); // 会员消费金额
                /*DB::query("
                    UPDATE
                        lb_user u
                    SET u.user_level = IFNULL((
                        SELECT
                            levelid
                        FROM lb_user_level
                        WHERE `status` = 1
                        AND (upgrade_score < {$empirical} OR upgrade_total_money < {$totalConsumptionMoney})
                        ORDER BY levelid DESC
                        LIMIT 1
                    ), u.user_level), u.empirical = {$empirical}, u.total_consumption_money = {$totalConsumptionMoney}
                    WHERE u.id = {$order_sn['user_id']};
                ");*/
                writeLog('会员降级操作SQL', DB::getLastsql(), 'level');
                Db::name('order_refund')->where('id', $rfid)->update(['status' => 3, 'refund_time' => time()]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), cookie('__forward__'));
        }
        //记录行为
        action_log('order_refund_sure', 'order_refund', $rfid, UID, $order_sn);
        $this->success(lang('操作成功'), cookie('__forward__'));
    }

    /**
     * 退优惠券
     * */
    public function refund_coupon($coupon_id)
    {
        $coupon = Db::name('operation_coupon_record')->where(['id' => $coupon_id, 'status' => 3])->find();
        if (!$coupon) {
            return ['error' => 1, 'msg' => lang('没有可退的优惠券')];
        }
        $res = Db::name('operation_coupon_record')->where(['id' => $coupon_id])->update(['status' => 1]);
        return ['error' => 0, 'msg' => ''];
    }

    /**
     * 退货拒绝，驳回填写原因
     */
    public function refund_del($rfid)
    {
        $order_sn = RefundModel::where('id', $rfid)->find();
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            $refuse_reason = $data['refuse_reason'];
            if(mb_strlen($refuse_reason) > 100) {
                $this->error(lang('长度超过100个字符'));
            }
            Db::startTrans();
            try {
                $order = Db::name('order')->where('order_sn', $order_sn['order_sn'])->lock(true)->find();
                if (!$order) {
                    exception(lang('订单不可操作'));
                }
                Db::name('order_goods_list')->where(['order_sn' => $order_sn['order_sn'], 'goods_id' => $order_sn['goods_id'],'sku_id'=>$order_sn['sku_id']])->setField('is_aftersale', 0);
                Db::name('order_refund')->where('id', $rfid)->update(['status' => -1, 'refund_time' => time(), 'refuse_reason'=>$refuse_reason]);
                action_log('order_refund_refuse', 'goods', 0, UID);
                Db::commit();
    
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('操作失败', cookie('__forward__'));
            }
            //记录行为
            action_log('order_refund_del', 'order_refund', $rfid, UID, $order_sn.'驳回原因:'.$refuse_reason);
            $this->success(lang('操作成功'), cookie('__forward__'));
            
        }
        $fields = [
            ['type' => 'textarea', 'name' => 'refuse_reason', 'title' => lang('驳回原因'), 'tips' => lang('请填写驳回原因,最大长度100个字符')],
        ];

        $this->assign('page_title', lang('拒绝退款'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 拼团订单列表
     * @return mixed
     * @author jxy [415782189@qq.com]
     */
    public function group()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        // $map = $this->getMap();
        $order_sn = input('param.order_sn');
        $receiver_mobile = input('param.receiver_mobile');
        $receiver_address = input('param.receiver_address');
        $receiver_name = input('param.receiver_name');
        $user_mobile = input('param.user_mobile');
        $user_name = input('param.user_name');
        $status = input('param.status', '');
        $create_time = input('param.create_time');
        $pay_status = input('param.pay_status');
        $is_full = input('param.is_full', -1);
        $map[] = ['order_type', '=', 5];
        if ($order_sn) {
            $map[] = ['o.order_sn', 'like', '%' . $order_sn . '%'];
        }
        if ($create_time) {
            $map[] = ['o.create_time', '>=', strtotime($create_time)];
            $map[] = ['o.create_time', '<', (strtotime($create_time . ' 23:59:59'))];
        }
        if ($status !== '') {
            $map[] = ['o.status', '=', $status];
            $field = 'status';
            $this->assign('active', $status);
        }
        if ($pay_status != '') {
            $map[] = ['o.pay_status', '=', $pay_status];
            $field = 'pay_status';
            $this->assign('active', $pay_status);
        }
        if ($receiver_mobile) {
            $map[] = ['og.receiver_mobile', 'like', '%' . $receiver_mobile . '%'];
        }
        if ($receiver_address) {
            $map[] = ['og.receiver_address', 'like', '%' . $receiver_address . '%'];
        }
        if ($receiver_name) {
            $map[] = ['og.receiver_name', 'like', '%' . $receiver_name . '%'];
        }
        if ($user_mobile) {
            $map[] = ['u.mobile', 'like', '%' . $user_mobile . '%'];
        }
        if ($user_name) {
            $map[] = ['u.user_nickname', 'like', '%' . $user_name . '%'];
        }
        if (isset($is_full) && $is_full != -1) {
            $map[] = ['gu.is_full', '=', $is_full];
        }


        // 排序
        $order = 'o.create_time desc';
        // 数据列表
        $data_list = OrderModel::alias('o')
            ->join("user u", "o.user_id = u.id", "left")
            ->join("order_goods_info og", "o.order_sn = og.order_sn", "left")
            ->join(" order_goods_express oge", "og.order_sn = oge.order_sn", "left")
            ->join("goods_activity_group_user gu", "gu.order_sn=o.order_sn", "left")
            ->field("o.order_sn,o.aid,o.user_id,o.order_money,o.payable_money,o.real_money,
            o.pay_status,o.status,o.order_type,o.pay_type,og.receiver_mobile,og.receiver_address,
            og.receiver_name,og.province,og.city,og.district,oge.express_company,oge.express_no,
            og.express_price,o.create_time,o.cost_integral,oge.express_status,u.user_name,u.mobile")
            ->where($map)
            ->order($order)
            ->paginate(15, false, ['query' => request()->param()])
            ->each(function ($item) {
                $refund = Db::name('order_refund')->where([['order_sn', '=', $item['order_sn']], ['status', 'egt', 0]])->find();
                if ($refund) {
                    $item['refund'] = 1;
                    $item['refund_info'] = $refund;
                } else {
                    $item['refund'] = 0;
                }
                $item['status_name'] = OrderModel::$order_status[$item['status']];
                $item['pay_status_name'] = OrderModel::$pay_status[$item['pay_status']];
                $item['group_info'] = Db::name('goods_activity_group_user')->alias('a')->join('goods_activity_group b', 'a.group_id=b.id', 'left')->where(['a.order_sn' => $item['order_sn']])->field('b.*')->find();
                $item['pay_type_name'] = OrderModel::$payTypes[$item['pay_type']];
                return $item;
            });
        foreach ($data_list as &$value) {
            $goodsInfo = Db::name('order_goods_list')->where(['order_sn' => $value['order_sn']])->find();
            $value['goods_name'] = $goodsInfo['goods_name'];
            $value['goods_thumb'] = get_file_url($goodsInfo['goods_thumb']);
        }
        $tab[] = ['title' => lang('已取消'), 'url' => url('goods/order/group', 'status=-1'), 'value' => -1, 'field' => 'status'];
        $tab[] = ['title' => lang('待支付'), 'url' => url('goods/order/group', 'status=0'), 'value' => 0, 'field' => 'status'];
        $tab[] = ['title' => lang('已支付'), 'url' => url('goods/order/group', 'pay_status=1'), 'value' => 1, 'field' => 'pay_status'];
        $tab[] = ['title' => lang('待发货'), 'url' => url('goods/order/group', 'status=1'), 'value' => 1, 'field' => 'status'];
        $tab[] = ['title' => lang('已发货'), 'url' => url('goods/order/group', 'status=2'), 'value' => 2, 'field' => 'status'];
        $tab[] = ['title' => lang('已完成'), 'url' => url('goods/order/group', 'status=3'), 'value' => 3, 'field' => 'status'];
        $this->assign('tab_list', $tab);
        $this->assign('field', $field);
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());
        $this->assign('orderStatus', OrderModel::$order_status);
        $this->assign("is_full", $is_full);
        return $this->fetch();
    }


    /**
     * 预售订单
     */
    public function preSell()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        // $map = $this->getMap();
        $order_sn = input('param.order_sn');
        $receiver_mobile = input('param.receiver_mobile');
        $receiver_address = input('param.receiver_address');
        $receiver_name = input('param.receiver_name');
        $user_mobile = input('param.user_mobile');
        $user_name = input('param.user_name');
        $status = input('param.status', '');
        $create_time = input('param.create_time');
        $pay_status = input('param.pay_status');
        $map[] = ['order_type', '=', 7];
        if ($order_sn) {
            $map[] = ['o.order_sn', 'like', '%' . $order_sn . '%'];
        }
        if ($create_time) {
            $map[] = ['o.create_time', '>=', strtotime($create_time)];
            $map[] = ['o.create_time', '<', (strtotime($create_time . ' 23:59:59'))];
        }
        if ($status !== '') {
            $map[] = ['o.status', '=', $status];
            $this->assign('status', $status);
        }
        if ($pay_status != '') {
            $map[] = ['o.pay_status', '=', $pay_status];
            $this->assign('pay_status', $pay_status);
        }
        if ($receiver_mobile) {
            $map[] = ['og.receiver_mobile', 'like', '%' . $receiver_mobile . '%'];
        }
        if ($receiver_address) {
            $map[] = ['og.receiver_address', 'like', '%' . $receiver_address . '%'];
        }
        if ($receiver_name) {
            $map[] = ['og.receiver_name', 'like', '%' . $receiver_name . '%'];
        }
        if ($user_mobile) {
            $map[] = ['u.mobile', 'like', '%' . $user_mobile . '%'];
        }
        if ($user_name) {
            $map[] = ['u.user_nickname', 'like', '%' . $user_name . '%'];
        }
        // 排序
        $order = 'o.create_time desc';

        // 数据列表
        $data_list = OrderModel::alias('o')
            ->join("user u", "o.user_id = u.id", "left")
            ->join("order_goods_info og", "o.order_sn = og.order_sn", "left")
            ->join(" order_goods_express oge", "og.order_sn = oge.order_sn", "left")
            ->field("o.order_sn,o.aid,o.user_id,o.order_money,o.payable_money,o.real_money,
            o.pay_status,o.status,o.order_type,o.pay_type,og.receiver_mobile,og.receiver_address,
            og.receiver_name,og.province,og.city,og.district,oge.express_company,oge.express_no,
            og.express_price,o.create_time,o.cost_integral,oge.express_status,u.user_name,u.mobile")
            ->where($map)
            ->order($order)
            ->paginate(15, false, ['query' => request()->param()])
            ->each(function ($item) {
                $item['status_name'] = OrderModel::$order_status[$item['status']];
                $item['pay_status_name'] = OrderModel::$pay_status[$item['pay_status']];
                if ($item['order_type'] == 7) { // 预售订单判断是否更新过尾款
                    $book_order_sn = Db::name('order_relation')->where('book_order_sn', $item['order_sn'])->value('final_order_sn');
                    $final_pay_order_info = Db::name('order')->where('order_sn', $book_order_sn)->field('status, pay_status, payable_money, real_money')->find();
                    if ($final_pay_order_info['pay_status'] == 1 && $final_pay_order_info['status']) {
                        $item['pay_status'] = 2; // 可以发货
                    }
                }
                return $item;
            });


        $tab[] = ['title' => lang('已取消'), 'url' => url('goods/order/preSell', 'status=-1&pay_status=0'), 'value' => ['status' => -1, 'pay_status' => 0]];
        $tab[] = ['title' => lang('定金待付'), 'url' => url('goods/order/preSell', 'status=0&pay_status=0'), 'value' => ['status' => 0, 'pay_status' => 0]];
        $tab[] = ['title' => lang('尾款待付'), 'url' => url('goods/order/preSell', 'status=1&pay_status=1'), 'value' => ['status' => 1, 'pay_status' => 1]];
        $tab[] = ['title' => lang('未发货'), 'url' => url('goods/order/preSell', 'status=1&pay_status=2'), 'value' => ['status' => 1, 'pay_status' => 2]];
        $tab[] = ['title' => lang('已发货'), 'url' => url('goods/order/preSell', 'status=2&pay_status=2'), 'value' => ['status' => 2, 'pay_status' => 2]];
        $tab[] = ['title' => lang('已完成'), 'url' => url('goods/order/preSell', 'status=3&pay_status=2'), 'value' => ['status' => 3, 'pay_status' => 2]];
        $this->assign('tab_list', $tab);
        $this->assign('list', $data_list);
        $this->assign('page_title', lang('预售订单'));
        $this->assign('pages', $data_list->render());
        $this->assign('orderStatus', OrderModel::$order_status);
        return $this->fetch();
    }

    /**
     *  积分订单
     */
    public function integral()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        // $map = $this->getMap();
        $order_sn = input('param.order_sn');
        $receiver_mobile = input('param.receiver_mobile');
        $receiver_address = input('param.receiver_address');
        $receiver_name = input('param.receiver_name');
        $user_mobile = input('param.user_mobile');
        $user_name = input('param.user_name');

        $status = input('param.status', '');
        $create_time = input('param.create_time');
        $pay_status = input('param.pay_status');
        $map[] = ['order_type', '=', 12];
        if ($order_sn) {
            $map[] = ['o.order_sn', 'like', '%' . $order_sn . '%'];
        }
        if ($create_time) {
            $map[] = ['o.create_time', '>=', strtotime($create_time)];
            $map[] = ['o.create_time', '<', (strtotime($create_time . ' 23:59:59'))];
        }
        if ($status !== '') {
            $map[] = ['o.status', '=', $status];
            $field = 'status';
            $this->assign('active', $status);
        }
        if ($pay_status != '') {
            $map[] = ['o.pay_status', '=', $pay_status];
            $field = 'pay_status';
            $this->assign('active', $pay_status);
        }
        if ($receiver_mobile) {
            $map[] = ['og.receiver_mobile', 'like', '%' . $receiver_mobile . '%'];
        }
        if ($receiver_address) {
            $map[] = ['og.receiver_address', 'like', '%' . $receiver_address . '%'];
        }
        if ($receiver_name) {
            $map[] = ['og.receiver_name', 'like', '%' . $receiver_name . '%'];
        }
        if ($user_mobile) {
            $map[] = ['u.mobile', 'like', '%' . $user_mobile . '%'];
        }
        if ($user_name) {
            $map[] = ['u.user_nickname', 'like', '%' . $user_name . '%'];
        }
        // 排序
        $order = 'o.create_time desc';

        // 数据列表
        $data_list = OrderModel::alias('o')
            ->join("user u", "o.user_id = u.id", "left")
            ->join("order_goods_info og", "o.order_sn = og.order_sn", "left")
            ->join(" order_goods_express oge", "og.order_sn = oge.order_sn", "left")
            ->field("o.order_sn,o.aid,o.user_id,o.order_money,o.payable_money,o.real_money,
            o.pay_status,o.status,o.order_type,o.pay_type,og.receiver_mobile,og.receiver_address,
            og.receiver_name,og.province,og.city,og.district,oge.express_company,oge.express_no,
            og.express_price,o.create_time,o.cost_integral,oge.express_status,u.user_name,u.mobile,o.cost_integral")
            ->where($map)
            ->order($order)
            ->paginate(15, false, ['query' => request()->param()])
            ->each(function ($item) {
                $item['status_name'] = OrderModel::$order_status[$item['status']];
                $item['pay_status_name'] = OrderModel::$pay_status[$item['pay_status']];

                return $item;
            });
        foreach ($data_list as &$value) {
            $goodsInfo = Db::name('order_goods_list')->where(['order_sn' => $value['order_sn']])->find();
            $value['goods_name'] = $goodsInfo['goods_name'];
            $value['goods_thumb'] = get_file_url($goodsInfo['goods_thumb']);
        }
        $tab[] = ['title' => lang('已取消'), 'url' => url('goods/order/integral', 'status=-1&pay_status=0'), 'value' => ['status' => -1, 'pay_status' => 0]];
        $tab[] = ['title' => lang('待支付'), 'url' => url('goods/order/integral', 'status=0&pay_status=0'), 'value' => ['status' => 0, 'pay_status' => 0]];
        $tab[] = ['title' => lang('已支付'), 'url' => url('goods/order/integral', 'status=1&pay_status=1'), 'value' => ['status' => 1, 'pay_status' => 1]];
        $tab[] = ['title' => lang('已发货'), 'url' => url('goods/order/integral', 'status=2&pay_status=1'), 'value' => ['status' => 2, 'pay_status' => 1]];
        $tab[] = ['title' => lang('已完成'), 'url' => url('goods/order/integral', 'status=3&pay_status=1'), 'value' => ['status' => 3, 'pay_status' => 1]];
        $this->assign('tab_list', $tab);
        $this->assign('list', $data_list);
        $this->assign('page_title', lang('积分订单'));
        $this->assign('pages', $data_list->render());
        $this->assign('orderStatus', OrderModel::$order_status);
        return $this->fetch();
    }

    /**
     * 查询支付订单是否有效
     * @param $order_sn
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/19 18:05
     */
    public function query_order($order_sn)
    {
        $order = OrderModel::where('order_sn', $order_sn)->field('transaction_id, pay_type, order_sn')->find();
        if ($order['pay_type'] == 'wxpay' || $order['pay_type'] == 'minipay') {
            $query_order['transaction_id'] = $order['transaction_id'];
            $query_order['order_sn'] = $order['order_sn'];
            $result = addons_action('WeChat', 'MiniPay', 'queryOrder', $query_order);
            if ($result['return_code'] == 'SUCCESS' && $result['return_msg'] == 'OK') {
                $this->success($result['trade_state_desc']);
            }
        } else {
            $this->error(lang('暂未开放'));
        }
    }

    /**
     * 订单打印
     * @param $order_sn
     * @author jxy [ 415782189@qq.com ]
     * @created 2020/5/11 13:55
     */
    public function download($order_sn)
    {
        $xlsName = $order_sn;
        $xlsCell = array(
            array('goods_name', lang('商品名称')),
            array('sku_name', lang('规格名称')),
            array('order_sn', lang('订单号')),
            array('num', lang('数量')),
            array('name', lang('供应商')),
            array('phone', lang('电话')),
            array('province', lang('省')),
            array('city', lang('市')),
            array('area', lang('区')),
            array('address', lang('街道')),
            array('receiver_name', lang('收件人')),
            array('receiver_mobile', lang('收件人电话')),
            array('receiver_province', lang('省')),
            array('receiver_city', lang('市')),
            array('receiver_district', lang('区')),
            array('receiver_address', lang('街道')),
        );
        $xlsData = Db::name('order_goods_list')
            ->alias('ogl')
            ->field('ogl.goods_name,ogl.sku_name,ogl.order_sn,ogl.num,s.name,s.phone,s.province,s.city,s.area,s.address')
            ->where(['ogl.order_sn' => $order_sn])
            ->join('goods g', 'g.id=ogl.goods_id', 'left')
            ->order('g.sender_id')
            ->join('goods_express_sender s', 's.id=g.sender_id', 'left')
            ->select();
        foreach ($xlsData as $k => $v) {
            $receiver = Db::name('order_goods_info')->where(['order_sn' => $order_sn])->find();
            $excelData[$v['sender_id']]['list'][] = [
                'goods_name' => $v['goods_name'],
                'sku_name' => $v['sku_name'],
                'order_sn' => $v['order_sn'],
                'num' => $v['num'],
                'name' => $v['name'],
                'phone' => $v['phone'],
                'province' => $v['province'],
                'city' => $v['city'],
                'area' => $v['area'],
                'address' => $v['address'],
                'receiver_name' => $receiver['receiver_name'],
                'receiver_mobile' => $receiver['receiver_mobile'],
                'receiver_city' => $receiver['city'],
                'receiver_district' => $receiver['district'],
                'receiver_address' => $receiver['receiver_address'],
            ];
            $excelData[$v['sender_id']]['sender'] = $v['name'];
        }
        $excelData = array_values($excelData);
        $this->exportExcel($xlsName, $xlsCell, $excelData);
    }

    public function remind_update()
    {
        $num = \think\Db::name('order_remind')->field('id,order_sn')->where('status', 0)->order('id desc')->count();
        $remind = \think\Db::name('order_remind')->field('id,order_sn')->where('status', 0)->order('id desc')->limit(5)->select();
        foreach ($remind as $k => $v) {
            $remind[$k]['href'] = url('goods/order/detail', ['order_sn' => $v['order_sn']]);
        }
        echo json_encode([
            'num' => $num,
            'listData' => $remind
        ]);
    }

    /**
     * 调起打印页面
     */
    public function getPrintHtml()
    {
        return $this->fetch();
    }

    /**
     * 打印发货单数据查询
     * @param $orderIds 字符戳 需要打印的订单ID
     */
    public function getShipmentOrderData()
    {
        // 接收参数
        $aids = $this->request->get('aids');

        // 参数校验
        if (empty($aids)) {
            return ['status' => '5000', 'msg' => lang('参数不能为空')];
        }

        // 查询订单数据
        $orderData = OrderModel::alias('o')
            ->where("o.aid IN($aids)")
            ->field("
                o.order_sn,FROM_UNIXTIME(o.create_time, '%Y-%m-%d %H:%i:%s') date_time,ogi.receiver_name,
                ogi.receiver_mobile,ogi.province,ogi.city,ogi.district,ogi.receiver_address,ogi.remark
            ")
            ->join('order_goods_info ogi', 'ogi.order_sn = o.order_sn', 'LEFT')
            ->select();
        // echo Db::getlastsql();die;
        if ($orderData) {
            foreach ($orderData as $key => $val) {
                $orderData[$key]['goods_list'] = OrderGoods::where(['order_sn' => $val['order_sn']])
                    ->field("goods_name,shop_price,num,sku_name,remark,sales_integral,is_pure_integral")
                    ->select()
                    ->each(function ($item) {
                        if ($item['is_pure_integral'] == 1) {
                            $item['shop_price'] = 0;
                        }
                    });
                $orderData[$key]['order_num'] = Db::name('order_goods_list')
                    ->where(['order_sn' => $val['order_sn']])
                    ->sum('num');
            }
        }

        return ['status' => '2000', 'msg' => lang('数据查询成功'), 'result' => $orderData];
    }

    public function verify_status()
    {
        $param = input("param.");
        if (!$param) {
            $this->error(lang('操作失败，请检查订单'));
        }
        switch ($param['type']) {
            //设置为支付
            case 'payment':
                // 启动事务
                Db::startTrans();
                try {
                    $saveData = [
                        "status" => 1,
                        "pay_status" => 1,
                        "pay_time" => time(),
                        "update_time" => time()
                    ];
                    $res = OrderModel::where("order_sn", $param['order_sn'])->update($saveData);
                    if (!$res) {
                        exception(lang('订单状态修改失败'));
                    }
                    OrderAction::actionLog($param['order_sn'], '', 1, lang('已付款'));
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->error(lang('操作失败'));
                }
                $this->success(lang('操作成功'), 'admin.php/goods/order/detail?order_sn=' . $param['order_sn']);
                break;
            //取消支付
            case 'cancel_payment':
                // 启动事务
                Db::startTrans();
                try {
                    $saveData = [
                        "status" => 0,
                        "update_time" => time()
                    ];
                    $res = OrderModel::where("order_sn", $param['order_sn'])->update($saveData);
                    if (!$res) {
                        exception(lang('订单状态修改失败'));
                    }
                    OrderAction::actionLog($param['order_sn'], '', 0, lang('未付款'));
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->error(lang('操作失败'));
                }
                $this->success(lang('操作成功'), 'admin.php/goods/order/detail?order_sn=' . $param['order_sn']);
                break;
            //取消订单
            case 'cancel':
                $order_sn = $param['order_sn'];
                // 启动事务
                Db::startTrans();
                try {
                    $order = Db::name('order')->where('order_sn', $order_sn)->where('status', 0)->lock(true)->find();
                    if (!$order) {
                        exception(lang('订单不可操作，请刷新'));
                    }
                    Db::name('order')->where('aid', $order['aid'])->setField('status', -1);
                    Db::name('order_goods_list')->where('order_sn', $order_sn)->setField('order_status', -1);
                    $order_goods_list = Db::name('order_goods_list')->where('order_sn', $order_sn)->field('goods_id,sku_id,num')->select();
                    $goodsku = new \app\goods\model\GoodsSku();
                    $goodinfo = new \app\goods\model\Goods();
                    foreach ($order_goods_list as &$val) {
                        if ($val['sku_id'] != 0) {
                            // 加sku库存
                            $where[] = ['sku_id', '=', $val['sku_id']];
                            $goodsku->where($where)->setInc('stock', $val['num']);
                            //减销量
                            $goodsku->where(['sku_id' => $val['sku_id']])->setDec('sales_num', $val['num']);
                        } else {
                            // 加主商品库存
                            $where1[] = ['id', '=', $val['goods_id']];
                            $goodinfo->where($where1)->setInc('stock', $val['num']);
                            //减销量
                            Db::name('goods')->where('id', $val['goods_id'])->setDec('sales_sum', $val['num']);
                        }
                    }
                    if ($order['coupon_id']) {
                        Db::name('operation_coupon_record')->where('id', $order['coupon_id'])->setField('status', 1);
                    }
                    //记录管理员对订单操作日志
                    OrderAction::actionLog($param['order_sn'], '', -2, lang('取消订单'));
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success('操作成功", "admin.php/goods/order/index');
                break;
            case 'aftermarket':
                $order_sn = $param['order_sn'];
                // 获取无理由退货时间
                $refundDay = module_config('goods.refund_day') ?: 7;
                $order_status = Db::name('order')
                    ->alias('o')
                    ->join("order_goods_list og", "o.order_sn = og.order_sn", "left")
                    ->where('o.order_sn', $order_sn)
                    ->select();
                if ($order_status[0]['pay_time'] <= time() - 3600 * 24 * $refundDay) {
                    $this->error(lang('操作失败，自支付起') . $refundDay . lang('天之后不可退货'));
                }

                Db::startTrans();
                try {
                    /*                    $order = Db::name('order')->where('order_sn', $order_sn)->where('status', 'gt',1)->find();
                                        if (!$order) {
                                            exception(lang('订单不可操作，请刷新'));
                                        }*/
                    foreach ($order_status as $k => $v) {
                        $refund['goods_money'] = $v['goods_money'];
                        $refund['sku_id'] = $v['sku_id'];
                        $refund['goods_id'] = $v['goods_id'];
                        $refund['user_id'] = $v['user_id'];
                        $refund['num'] = $v['num'];
                        $refund['order_sn'] = $v['order_sn'];
                        $refund['refund_type'] = 1;
                        $refund['refund_reason'] = lang('管理员操作');
                        $refund['create_time'] = time();
                        $refund['server_no'] = 'S' . date('Ymd') . rand(1000, 9999);
                        Db::name('order_refund')->insert($refund);
                        Db::name('order_goods_list')->where(['order_sn' => $order_sn, 'goods_id' => $v['goods_id'], 'sku_id' => $v['sku_id']])->update(['is_aftersale' => 1]);
                    }
                    OrderAction::actionLog($param['order_sn'], lang('管理员发起售后'), 1, lang('已付款'));
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success("", "goods/order/index");
                break;
            default:
                break;
        }
        $this->success("123", "goods/order/index");
    }

    /**
     * 生成发货单--页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function create_invoice()
    {
        $order_sn = input("param.order_sn");
        $info = OrderInfo::get_order_detail($order_sn);
        $info['order_status'] = OrderModel::$order_status[$info['status']];
//        $info['pay_status'] = OrderModel::$pay_status[$info['pay_status']];
        $info['pay_status_name'] = OrderModel::$pay_status[$info['pay_status']];

//        $info['order_type'] = OrderModel::order_typeArr()[$info['order_type']]['name'];
        $info['order_type_name'] = OrderModel::order_typeArr()[$info['order_type']]['name'];

        $info['pay_type'] = OrderModel::$payTypes[$info['pay_type']];


        $info['order_express'] = Db::name('order_goods_express')->where(['order_sn' => $info['order_sn']])->find();
        if ($info['order_type'] != 1) {
            $map[] = ['order_sn', '=', $info['order_sn']];
        }
        $info['comment_time'] = Db::name('goods_comment')->where($map)->where([['user_id', '=', $info['user_id']]])->value('create_time');
        if ($info['order_type'] == 7) {
            $final_order_sn = Db::name('order_relation')->where(['book_order_sn' => $info['order_sn']])->value('final_order_sn');

            $final_order = Db::name('order')->where(['order_sn' => $final_order_sn])->find();
            $final_order['pay_status_name'] = OrderModel::$pay_status[$final_order['pay_status']];
            $final_order['transaction_no'] = Db::name('payment_log')->where(['order_sn' => $final_order['user_id']])->value("transaction_no");
            $final_order['pay_type'] = OrderModel::$payTypes[$final_order['pay_type']];

            $info['final_order'] = $final_order;
            if ($final_order['pay_status'] == 1) {
                $info['pay_status'] = 2;
                $info['pay_status_name'] = lang('已付款');
            } else {
                $info['pay_status_name'] = lang('未付款');
            }
        }
        $info['user'] = Db::name("user")->field("user_name,mobile,user_level")->where("id", $info['user_id'])->find();
        $info['user_level'] = Db::name('user_level')->where(['id' => $info['user_id']])->value("name");
        $info['order_goods'] = json_encode($info['order_goods'], JSON_UNESCAPED_UNICODE);
        $express_company = Db::name('goods_express_company')->field('aid,name')->select();
        $sender_list = Db::name('goods_express_sender')->field('id,name')->select();
        $order_action = OrderAction::getActionLogs([["order_sn", "=", $order_sn]]);
        $this->assign('order_action', json_encode($order_action->toArray()['data'], JSON_UNESCAPED_UNICODE));
        $this->assign('sender_list', json_encode($sender_list, JSON_UNESCAPED_UNICODE));
        $this->assign('express_company', json_encode($express_company, JSON_UNESCAPED_UNICODE));
        $this->assign('sender_company', json_encode($sender_list, JSON_UNESCAPED_UNICODE));
        $this->assign('order_info', $info);
        return $this->fetch();
    }

    /**
     * 生成发货单--操作
     */
    public function create_invoice_operation()
    {
        // 商品列表
        $data = input("param.");
        $order_sn = input("param.order_sn");
        $remark = $data['remark'] ?? '';
        $goods = OrderGoods::where('order_sn', $order_sn)->field('goods_id,sku_id')->select();
        $express_company = Db::name('goods_express_company')->get($data['express_company']);
        if (empty($express_company)) {
            $this->error(lang('快递公司不能为空'));
        }
        $sender_company = Db::name('goods_express_sender')->get($data['sender_company']);
        if (empty($sender_company)) {
            $this->error(lang('供应商不能为空'));
        }
        $goods_array = [];
        foreach ($goods as $item) {
            $goods_array[] = $item['goods_id'] . '_' . $item['sku_id'];
        }
        if (OrderGoodsExpress::where(["order_sn" => $order_sn, 'is_del' => 0])->count()) {
            return redirect("/admin.php/goods/order/express_index/order_sn/" . $order_sn);
        }
        $param['order_sn'] = $order_sn;
        $param['order_goods_id_array'] = implode(',', $goods_array);
        $param['express_serial_number'] = date("Ymd", time()) . rand(100000, 999999);
        $param['shipping_type'] = 1;
        $param['express_company_id'] = $express_company['aid'];
        $param['express_company'] = $express_company['name'];
        $param['uid'] = UID;
        $param['shipping_time'] = time();
        $res = OrderGoodsExpress::create($param);
        if (!$res) {
            $this->error(lang('生成发货单失败'));
        }
        $res = OrderModel::where('order_sn', $order_sn)->update([
            'sender_id' => $sender_company['id'],
            'update_time' => time()
        ]);
        if (!$res) {
            $this->error(lang('生成发货单失败（供应商失败）'));
        }
        OrderAction::actionLog($order_sn, '', 1, lang('管理员生成发货单'), $remark);
        return redirect("/admin.php/goods/order/express_index/order_sn/" . $order_sn);
    }

    /**
     * 发货单列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function express_index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
//        $map = $this->getMap();
        $param = input("param.");
        $order_sn = $param['order_sn'];
        $map[] = ["oge.is_del", '=', 0];

        if ($order_sn) {
            $map[] = ["oge.order_sn", '=', $order_sn];
        }
        if ($param['express_serial_number']) {
            $map[] = ["oge.express_serial_number", '=', $param['express_serial_number']];
        }
        if ($param['user_nickname']) {
            $map[] = ['u.user_nickname', 'like', '%' . $param['user_nickname'] . '%'];
        }
        if ($param['mobile']) {
            $map[] = ['u.mobile', 'like', '%' . $param['mobile'] . '%'];
        }
        if ($param['receiver_name']) {
            $map[] = ['ogi.receiver_name', 'like', '%' . $param['receiver_name'] . '%'];
            // $order_sn_data = OrderInfo::where("receiver_name","like",'%'.$param['receiver_name'].'%')->column("order_sn");
        }
        if ($param['receiver_address']) {
            $map[] = ['ogi.receiver_address', 'like', '%' . $param['receiver_address'] . '%'];
        }
        if ($param['receiver_mobile']) {
            $map[] = ['ogi.receiver_mobile', 'like', '%' . $param['receiver_mobile'] . '%'];
        }

        if ($param['express_status'] != 'all' && $param['express_status'] !== '' && $param['express_status'] !== null) {
            $map[] = ["oge.express_status", '=', $param['express_status']];
        }
        $map[] = ["oge.is_del", '=', 0];
        // 数据列表
        $data_list = OrderGoodsExpress::alias('oge')
            ->join("order o", "oge.order_sn = o.order_sn", "left")
            ->join("user u", "o.user_id = u.id", "left")
            ->join("order_goods_info ogi", "o.order_sn = ogi.order_sn", "left")
            ->field("oge.express_no,oge.id,oge.uid,oge.express_serial_number,oge.order_sn,oge.shipping_time,oge.express_status,oge.admin_name,o.create_time,o.user_id,u.user_nickname,u.mobile,ogi.receiver_name,ogi.receiver_mobile,ogi.receiver_address,o.status")
            ->where($map)
            // ->where(function ($query) use ($order_sn_data) {
            //     foreach ($order_sn_data as $v) {
            //         $query->whereOr('oge.order_sn', 'eq', $v);
            //     }
            // })
            ->order('oge.id desc')
            ->paginate()
            ->each(
                function ($item) {
                    if (in_array($item['status'], [3, 4])) {
                        $item['express_status'] = 2;
                    }
                    $item['order_sn_link'] = "<a href='" . url('order/detail', ['order_sn' => $item['order_sn']]) . "'>" . $item['order_sn'] . "</a>";
                    // $item['user_name'] = Db::name("user")->where("id",$item['user_id'])->value('user_nickname');
                    // $item['mobile'] = Db::name("user")->where("id",$item['user_id'])->value('mobile');

                    // $item['receiver_name'] = Db::name("order_goods_info")->where("order_sn",$item['order_sn'])->value('receiver_name');
                    // $item['receiver_mobile'] = Db::name("order_goods_info")->where("order_sn",$item['order_sn'])->value('receiver_mobile');
                    // $item['receiver_address'] = Db::name("order_goods_info")->where("order_sn",$item['order_sn'])->value('receiver_address');
                    $item['admin_name'] = Db::name("admin")->where("id", $item['uid'])->value('username');
                    return $item;
                }
            );
        $fields = [
            ['id', 'ID'],
            ['express_serial_number', lang('发货流水单号')],
            ['order_sn_link', lang('订单号')],
            ['express_no', lang('发货单号')],
            ['create_time', lang('下单时间'), 'callback', function ($value, $format) {
                return format_time($value, $format); // $format 在这里的值是Y-m
            }, 'Y-m-d H:i:s'],
            ['user_nickname', lang('下单人')],
            ['mobile', lang('下单人手机号')],
            ['receiver_name', lang('收货人')],
            ['receiver_mobile', lang('收货人手机号')],
            ['receiver_address', lang('收货人地址')],
            ['shipping_time', lang('发货时间'), 'callback', function ($value, $format) {
                return format_time($value, $format); // $format 在这里的值是Y-m
            }, 'Y-m-d H:i:s'],
            ['express_status', lang('状态'), 'status', '', ['all' => lang('全部'), 0 => lang('未发货'), 1 => lang('已发货'), '2' => lang('已完成')]],
            ['admin_name', lang('操作人')],
            ['right_button', lang('操作'), 'btn', '', '']
        ];
        $searchFields = [
            ['order_sn', lang('订单号'), 'text'],
            ['express_serial_number', lang('发货流水单号'), 'text'],
            ['user_nickname', lang('下单人'), 'text'],
            ['mobile', lang('下单人手机号'), 'text'],
            ['receiver_name', lang('收货人'), 'text'],
            ['receiver_mobile', lang('收货人手机号'), 'text'],
            ['receiver_address', lang('收货人地址'), 'text'],
            ['express_status', lang('状态'), 'select', '', ['all' => lang('全部'), 0 => lang('未发货'), 1 => lang('已发货')]],
        ];
        return Format::ins()//实例化
        ->setTopButtons([
            ['ident' => 'express_delete', 'title' => lang('批量删除'), 'href' => 'express_delete', 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-sm mr5 btn-default  ajax-post confirm', 'extra' => 'target-form="ids"'],

            ['ident' => 'deliver_goods', 'title' => lang('批量发货'), 'href' => 'deliver_goods', 'icon' => 'fa pr5 fa-check-square', 'class' => 'btn btn-sm mr5 btn-primary plfh', 'extra' => 'target-form="ids"']])
//            ['ident' => 'deliver_goods', 'title' => lang('批量发货'), 'href' => ['deliver_goods', ['layer' => 1, 'id' => '__id__']], 'data-toggle' => 'dialog-right', 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-xs mr5 btn-success btn-flat','extra' => 'target-form="ids"']])
            ->addColumns($fields)//设置字段
            ->setRightButtons([
                ['ident' => 'express_detail', 'title' => lang('查看'), 'href' => ['express_detail', ['id' => '__id__', 'order_sn' => '__order_sn__']], 'icon' => 'fa fa-pencil pr5', 'class' => 'btn btn-xs mr5 btn-default'],
                ['ident' => 'express_delete', 'title' => lang('删除'), 'href' => ['express_delete', ['ids' => '__id__']], 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-xs mr5 btn-default  ajax-get confirm'],
            ])
            ->setTopSearch($searchFields)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 批量发货展示发货详情
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deliver_goods()
    {
        $ids = input("param.ids");
        if (!$ids) {
            $this->error(lang('参数错误'));
        }
        $map[] = ["oge.is_del", '=', 0];
        $map[] = ["oge.id", "in", $ids];
        // 数据列表
        $data_list = OrderGoodsExpress::alias('oge')
            ->join("order_goods_info o", "oge.order_sn = o.order_sn", "left")
            ->field("oge.id,oge.express_serial_number,oge.order_sn,oge.shipping_time,
            oge.express_status,o.receiver_mobile,o.receiver_address,province,city,district,receiver_name,express_company")
            ->where($map)
            ->order('oge.id desc')
            ->select();
        $this->assign("data_list", json_encode($data_list, JSON_UNESCAPED_UNICODE));
        return $this->fetch();
    }

    public function express_delete($ids)
    {
        Db::startTrans();
        try {
            if (is_array($ids)) {
                foreach ($ids as $k => $v) {
                    $record = implode(',', $ids);
                    Db::name("order_goods_express")->where(['id' => $v])->setField('is_del', 1);
                }
            } else {
                $record = $ids;
                Db::name("order_goods_express")->where(['id' => $ids])->setField('is_del', 1);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(lang('删除失败'));
        }
        //记录行为
        action_log('order_express_delete', 'order_goods_express', $record, UID, $ids);
        $this->success(lang('删除成功'));
    }

    /**
     * 订单发货
     * @return false|string
     */
    public function express_create()
    {
        $data = input("param.");
        if (empty($data)) {
            return json_encode(["code" => 0, "data" => [], "msg" => lang('参数错误')]);
        }
        Db::startTrans();
        try {
            foreach ($data['data'] as $k => $v) {
                $order_sn = OrderGoodsExpress::where("id", $v['id'])->value("order_sn");

                $check = OrderModel::where([
                    ['order_sn', '=', $order_sn],
                    ['status', '>=', 1]
                ])->find();
                if (!$check) {
                    $this->error(lang('订单不存在，请核实'));
                }
                $info = OrderGoodsExpress::where(['express_no' => $v['express_no']])->find();
                if ($info) {
                    exception(lang('发货单号已存在，请核实'));
                }
                $save_data = [
                    "express_no" => $v['express_no'],
                    "memo" => $data['remark'],
                    "shipping_time" => time(),
                    "express_status" => 1
                ];
                $res = OrderGoodsExpress::where("id", $v['id'])->update($save_data);
                if (!$res) {
                    exception(lang('发货失败'));
                }
                $r = OrderModel::where("order_sn", $order_sn)->setField("status", 2);
                if (!$r) {
                    exception(lang('发货失败'));
                }
                $OrderInvoice = OrderInvoice::where(['order_sn' => $order_sn, 'billing_type' => 1])->find();
                if ($OrderInvoice) {
                    $OrderGoodsExpress = OrderGoodsExpress::where("id", $v['id'])->find();
                    $rs = OrderInvoice::where(['order_sn' => $order_sn, 'billing_type' => 1])->update(['invoice_status' => 2, 'invoice_send_goods_num' => $v['express_no'], 'express_company_id' => $OrderGoodsExpress['express_company_id']]);
                    if (!$rs) {
                        exception(lang('发货失败'));
                    }
                }
                $logRes = OrderAction::actionLog($order_sn, lang('管理员操作发货'), 3, lang('已发货'));
                if (!$logRes) {
                    exception(lang('插入行为日志失败'));
                }
                //发送通知消息
                $user_id = OrderModel::where([
                    'order_sn' => $order_sn
                ])->value("user_id");
                $goods_thumb = OrderGoods::where([
                        'order_sn' => $order_sn
                    ])->value("goods_thumb") ?? 0;
                $message = SystemMessageModel::send_msg(
                    $user_id,
                    lang('您的订单已发货'),
                    lang('您的订单') . '：' . $order_sn . lang('已发货，快递单号') . '：' . $v['express_no'] . lang('请注意查收') . '。',
                    1,
                    3,
                    1,
                    $goods_thumb,
                    '/pages/order/orderdetail/order-detail/index?order_sn=' . $order_sn . '&order_type=3'

                );
                if (!$message) {
                    exception(lang('订单消息发送异常'));
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json_encode(["code" => 0, "data" => [], "msg" => $e->getMessage()]);
        }
        return json_encode(["code" => 1, "data" => [], "msg" => lang('发货成功')]);
    }

    /**
     * 发货单详情
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function express_detail()
    {
        $express_id = input("param.id");
        $order_sn = input("param.order_sn");
        $info = OrderInfo::get_order_detail($order_sn);
        $info['order_express'] = Db::name('order_goods_express')->where(['order_sn' => $order_sn])->find();

        if ($info['order_type'] == 7) {
            $final_order_sn = Db::name('order_relation')->where(['book_order_sn' => $info['order_sn']])->value('final_order_sn');
            $info['final_order'] = Db::name('order')->where(['order_sn' => $final_order_sn])->find();
        }
        $info['order_goods'] = json_encode($info['order_goods'], JSON_UNESCAPED_UNICODE);
        $order_action = OrderAction::getActionLogs([["order_sn", "=", $order_sn]]);
        $this->assign('order_action', json_encode($order_action->toArray()['data'], JSON_UNESCAPED_UNICODE));

        // 查询发票信息 ADD zenghu 2020年12月8日14:07:28
        $orderInvoice = OrderInvoice::get(['order_sn' => $order_sn]);
        if (!empty($orderInvoice)) {
            $orderInvoice['invoice_type_name'] = ['1' => lang('个人发票'), '2' => lang('公司发票')][$orderInvoice['invoice_type']];
            $orderInvoice['invoice_status'] = ['1' => lang('申请开票中'), '2' => lang('已开票')][$orderInvoice['invoice_status']];
            $orderInvoice['invoice_add_time'] =
                $orderInvoice['invoice_add_time']
                    ? date("Y-m-d H:i:s", $orderInvoice['invoice_add_time']) : '';
        }

        //购物人
        $buy = Db::name("user")->where(['id' => $info['user_id']])->value("user_nickname");
        $this->assign('buy', $buy);
        $this->assign('orderInvoice', $orderInvoice);
        $this->assign('order_info', $info);
        $this->assign('order_sn', $order_sn);
        $this->assign('express_id', $express_id);
        return $this->fetch();
    }

    /**
     * 单条发货单发货
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function edit_express()
    {
        $param = input("param.");
        $remark = $param['remark'] ?? '';
        if (!$param) {
            $this->error(lang('参数错误'));
        }
        $check = OrderModel::where([
            ['order_sn', '=', $param['order_sn']],
            ['status', '>=', 1]
        ])->find();
        if (!$check) {
            $this->error(lang('订单不存在，请核实'));
        }
        if ($param['type'] == "confirm") {
            $info = OrderGoodsExpress::where(['express_no' => $param['express_no']])->find();
            if ($info) {
                $this->error(lang('发货单号已存在，请核实'));
            }
            $save_data = [
                "express_status" => 1,
                "express_no" => $param['express_no'],
                "shipping_time" => time(),
            ];
            // $res = OrderGoodsExpress::where("id",$param['express_id'])->update($save_data);
            $res = OrderGoodsExpress::where("order_sn", $param['order_sn'])->update($save_data);
            if (!$res) {
                $this->error(lang('发货失败'));
            }

            // 修改订单状态
            $orderInfo['status'] = 2;

            if (isset($param['thumb']) && !empty($param['thumb'])) {
                // 修改订单发票信息-发货单号 ADD zenghu 2020年12月8日16:23:43
//                $invoiceNum = empty($param['invoice_express_no']) ? $param['express_no'] : $param['invoice_express_no'];
                $invoiceNum = $param['express_no'] ;
                //发票图片
                $invoice_img = $param['thumb'] ;
                // 修改发票状态
                OrderInvoice::where(['order_sn' => $param['order_sn']])->update(['invoice_img' => $invoice_img, 'invoice_send_goods_num' => $invoiceNum, 'invoice_status' => 2]);
                $orderInfo['invoice_status'] = 2;
            }

            $r = OrderModel::where("order_sn", $param['order_sn'])->update($orderInfo);
            if ($r === false) {
                $this->error(lang('发货失败'));
            }

            OrderAction::actionLog($param['order_sn'], '', 2, lang('后台发货'), $remark);

            //发送通知消息
            $user_id = OrderModel::where([
                'order_sn' => $param['order_sn']
            ])->value("user_id");
            $goods_thumb = OrderGoods::where([
                    'order_sn' => $param['order_sn']
                ])->value("goods_thumb") ?: 0;
            $message = SystemMessageModel::send_msg(
                $user_id,
                lang('您的订单已发货'),
                lang('您的订单') . '：' . $param['order_sn'] . lang('已发货，快递单号') . '：' . $param['express_no'] . lang('请注意查收') . '。',
                1,
                3,
                1,
                $goods_thumb,
                '/pages/order/orderdetail/order-detail/index?order_sn=' . $param['order_sn'] . '&order_type=3'

            );
            if (!$message) {
                exception(lang('订单消息发送异常'));
            }


            $this->success(lang('发货成功'));
        }
        if ($param['type'] == "cancel") {
            $save_data = [
                "express_status" => 0,
                "express_no" => '',
            ];
            $res = OrderGoodsExpress::where("order_sn", $param['order_sn'])->update($save_data);
            if (!$res) {
                $this->error(lang('取消失败'));
            }
            $orderInfo['status'] = 1;
            $r = OrderModel::where("order_sn", $param['order_sn'])->update($orderInfo);
            if ($r === false) {
                $this->error(lang('取消失败'));
            }
            if (isset($param['thumb']) && !empty($param['thumb'])) {
                // 修改订单发票信息-发货单号 ADD zenghu 2020年12月8日16:23:43
                OrderInvoice::where(['order_sn' => $param['order_sn']])->update(['invoice_send_goods_num' => '', 'invoice_status' => 1]);
                OrderModel::where("order_sn", $param['order_sn'])->update(['invoice_status' => 1]);
            }

            OrderAction::actionLog($param['order_sn'], '', 1, lang('取消发货'), $remark);
            //发送通知消息
            $user_id = OrderModel::where([
                'order_sn' => $param['order_sn']
            ])->value("user_id");
            $message = SystemMessageModel::send_msg(
                $user_id,
                lang('您的订单取消发货'),
                lang('您的订单') . '：' . $param['order_sn'] . lang('取消发货'),
                1,
                3,
                1,
                $goods_thumb,
                '/pages/order/orderdetail/order-detail/index?order_sn=' . $param['order_sn'] . '&order_type=3'

            );
            if (!$message) {
                exception(lang('订单消息发送异常'));
            }
            $this->success(lang('取消成功'));
        }
    }


    /***
     * 缺货登记列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author zhougs
     */
    public function goods_outofstock()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $param = input("param.");
        if ($param['name']) {
            $map[] = ["g.name", 'like', '%' . $param['name'] . '%'];
        }
        if ($param['status'] != 'all' && $param['status'] !== '' && $param['status'] !== null) {
            $map[] = ["go.status", '=', $param['status']];
        }
        $map[] = ["go.is_del", '=', 0];
        // 数据列表
        $data_list = GoodsOutofstock::alias('go')
            ->join("goods g", "g.id = go.goods_id", "left")
            ->field("go.id,g.name,go.contacts,go.num,go.create_time,go.status,go.sku_id")
            ->where($map)
            ->order('go.id desc')
            ->paginate()
            ->each(
                function ($item) {
                    $item['key_name'] = GoodsSku::where("sku_id", $item['sku_id'])->value("key_name");
                    return $item;
                }
            );
        $fields = [
            ['id', 'ID'],
            ['contacts', lang('联系人')],
            ['name', lang('缺货商品名')],
            ['key_name', lang('规格')],
            ['num', lang('数量')],
            ['create_time', lang('登记时间')],
            ['status', lang('处理'), 'status', '', [lang('未处理'), lang('已处理')]],
            ['right_button', lang('操作'), 'btn']
        ];
        $searchFields = [
            ['name', lang('商品名称'), 'text'],
            ['status', lang('状态'), 'select', '', ['all' => lang('全部'), 0 => lang('未处理'), 1 => lang('已处理')]],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopSearch($searchFields)
            ->setRightButtons([
                ['ident' => 'outofstock_detail', 'title' => lang('查看'), 'href' => ['outofstock_detail', ['id' => '__id__']], 'icon' => 'fa fa-pencil pr5', 'class' => 'btn btn-xs mr5 btn-default layeredit'],
                ['ident' => 'outofstock_delete', 'title' => lang('删除'), 'href' => ['outofstock_delete', ['ids' => '__id__']], 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-xs mr5 btn-default   ajax-get confirm'],
            ])
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    public function outofstock_delete($ids)
    {
        $newIds = $ids;
        if (is_numeric($ids)) {
            $newIds = [];
            $newIds[] = $ids;
        }
        $record_id = implode(',', $newIds);
        Db::startTrans();
        try {
            foreach ($newIds as $k => $v) {
                GoodsOutofstock::where(['id' => $v])->setField('is_del', 1);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(lang('删除失败'));
        }
        //记录行为
        action_log('order_outofstock_delete', 'goods_outofstock', $record_id, UID, $newIds);
        $this->success(lang('删除成功'));
    }

    /**
     * 对参与拼团的订单且未满团添加机器人让其订单满团.
     * @return mixed
     * @author jxy [415782189@qq.com]
     */
    public function add_robot_to_group($group_id)
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
            $group = Db::name('goods_activity_group')->lock(true)->get($group_id);
            if ($group) {
                $map[] = ['goods_id', '=', $group['goods_id']];
                $map[] = ['activity_id', '=', $group['activity_id']];
                $activity = Db::name('goods_activity_details')->where($map)->find();
                if (!$activity) {
                    exception(lang('参团的活动已不存在'));
                }
                $unoccupied = $activity['join_number'] - $group['num'];
                for ($i = 1; $unoccupied >= $i; $i++) {//补充剩余
                    $rander = rand(0, (count($robot) - 1));
                    $group_user[] = [
                        'group_id' => $group['id'],
                        'order_sn' => '',
                        'uid' => 0,
                        'user_name' => $robot[$rander]['user_name'],
                        'user_head' => $robot[$rander]['user_head'],
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
                exception(lang('团已不存在'));
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), cookie('__forward__'));
        }
        $this->success(lang('操作成功'), cookie('__forward__'));
    }

    /**
     * 缺货登记
     * @author  zhougs
     */
    public function goods_outofstock_add()
    {
        $is_post = $this->request->isPost();
        if (!$is_post) {
            $this->error(lang('参数错误'));
        }
        $param = $this->request->post();
        $contacts = Db::name("admin")->where("id", UID)->value("username");
        $data = [
            "goods_id" => $param['goods_id'],
            "sku_id" => $param['sku_id'],
            "num" => $param['num'],
            "contacts" => $contacts,
            "create_time" => time(),
        ];
        $res = GoodsOutofstock::create($data);
        if ($res) {
            //记录行为
            unset($data['__token__']);
            $details = json_encode($data, JSON_UNESCAPED_UNICODE);
            action_log('order_outofstock_add', 'goods_outofstock', $res->id, UID, $details);
            $this->success(lang('登记成功'), "/admin.php/goods/order/express_index");
        }
        $this->error(lang('登记失败'));
    }

    public function outofstock_detail($id)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $data['admin_id'] = UID;
            $data['status'] = 1;
            if (GoodsOutofstock::update($data)) {
                $this->success(lang('处理成功'), cookie('__forward__'));
            } else {
                $this->error(lang('处理失败'));
            }
        }

        $info = GoodsOutofstock::alias('go')
            ->join("goods g", "g.id = go.goods_id", "left")
            ->join("goods_sku gs", "go.sku_id = gs.sku_id", "left")
            ->field("go.id,g.name,go.contacts,go.num,go.create_time,go.status,go.describe,go.remark,gs.key_name")
            ->where("go.id", $id)
            ->find();
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('缺货商品名'), 'tips' => '', 'attr' => 'disabled', 'value' => ''],
            ['type' => 'text', 'name' => 'key_name', 'title' => lang('规格'), 'tips' => '', 'attr' => 'disabled', 'value' => ''],
            ['type' => 'text', 'name' => 'create_time', 'title' => lang('登记时间'), 'tips' => '', 'attr' => 'disabled', 'value' => ''],
            ['type' => 'text', 'name' => 'num', 'title' => lang('数量'), 'tips' => '', 'attr' => 'disabled', 'value' => ''],
            ['type' => 'text', 'name' => 'contacts', 'title' => lang('联系人'), 'tips' => '', 'attr' => 'disabled', 'value' => ''],
            ['type' => 'text', 'name' => 'describe', 'title' => lang('详细描述'), 'tips' => '', 'attr' => 'disabled', 'value' => ''],
            ['type' => 'text', 'name' => 'remark', 'title' => lang('处理备注'), 'tips' => '', 'attr' => '', 'value' => ''],

        ];
        $this->assign('page_title', lang('查看详情'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }

    /**
     * 发票列表
     * @return mixed
     * @author jxy [415782189@qq.com]
     */
    public function invoice_list()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $order_sn = input('param.order_sn');
        $status = input('param.invoice_status', '');
        $create_time = input('param.invoice_add_time');
        $invoice_send_goods_num = input('param.invoice_send_goods_num');
        $invoice_title = input('param.invoice_title');
        $map = [];
        $map1 = [];
        if ($order_sn) {
            $map['oi.order_sn'] = $order_sn;
        }
        if ($create_time) {
            $map1[] = ['oi.invoice_add_time', '>=', ($create_time . ' 00:00:00')];
            $map1[] = ['oi.invoice_add_time', '<=', ($create_time . ' 23:59:59')];
        }
        if (!empty($invoice_send_goods_num)) {
            $map['oi.invoice_send_goods_num'] = $invoice_send_goods_num;
        }
        if (!empty($invoice_title)) {
            $map['oi.invoice_title'] = $invoice_title;
        }
        if ($status !== '') {
            $map['oi.invoice_status'] = $status;
            $this->assign('active', $status);
            $this->assign('field', 'invoice_status');
        }

        // 数据列表
        $data_list = OrderInvoice::alias('oi')
            ->field("o.real_money,o.aid,oi.*")
            ->where($map)
            ->where($map1)
            ->where('o.status','<>',-1)
            ->join('order o', 'o.order_sn = oi.order_sn', 'LEFT')
            ->order('oi.invoice_add_time DESC')
            ->paginate(15, false, ['query' => request()->param()])
            ->each(function ($item) {
                $item['invoice_type_name'] = ['1' => lang('个人发票'), '2' => lang('公司发票')][$item['invoice_type']];
                $item['invoice_status_name'] = ['1' => lang('申请开票中'), '2' => lang('已开票')][$item['invoice_status']];
                $item['invoice_add_time'] = date('Y-m-d H:i:s', $item['invoice_add_time']);
                return $item;
            });

        // tab栏
        $tab[] = ['title' => lang('申请开票中'), 'url' => url('goods/order/invoice_list', 'invoice_status=1'), 'value' => 1, 'field' => 'invoice_status'];
        $tab[] = ['title' => lang('已开票'), 'url' => url('goods/order/invoice_list', 'invoice_status=2'), 'value' => 2, 'field' => 'invoice_status'];

        $this->assign('tab_list', $tab);
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());

        return $this->fetch();
    }

    /**
     * 开票
     */
    public function invoice()
    {
        // 获取参数值
        $id = input('param.id');
        $order_sn = input('param.order_sn');
        $order_status = OrderModel::where(['order_sn' => $order_sn])->value('status');
        $this->assign('id', $id);
        $this->assign('order_sn', $order_sn);
        $this->assign('order_status', $order_status);
        return $this->fetch();
    }

    //获取快递公司名称
    public function get_express_company()
    {
        $express_company = Db::name('goods_express_company')->field('aid,name')->select();
        if (count($express_company) > 0) {
            echo json_encode(array('ret' => 1, 'data' => $express_company));
        } else {
            echo json_encode(array('ret' => 0, 'data' => ''));
        }
    }

    /**
     * 邮寄发票
     */
    public function invoiceUpdate()
    {
        // 获取参数值
        $invoice_express_no = input('param.invoice_express_no');
        $order_sn = input('param.order_sn');
        $express_company_id = input('param.express_company_id');
        // 参数校验
        if (empty($invoice_express_no) || empty($order_sn)) {
            return json(['status' => '5000', 'msg' => lang('参数错误')]);
        }

        if (OrderModel::where('order_sn',$order_sn)->value('status') == '-1'){
            return json(['status' => '5000', 'msg' => lang('已取消订单不能开发票')]);
        }

        // 启动事务
        Db::startTrans();
        try {
            // 修改发票发货状态
            OrderInvoice::where(['order_sn' => $order_sn])->update(['invoice_send_goods_num' => $invoice_express_no, 'invoice_status' => 2, 'invoice_update_time' => time(), 'billing_type' => 2, 'express_company_id' => $express_company_id]);
            OrderModel::where(['order_sn' => $order_sn])->update(['invoice_status' => 2]);

            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['status' => '5000', 'msg' => lang('邮寄修改失败')]);
        }

        return json(['status' => '2000', 'msg' => lang('修改成功')]);
    }

    /**
     * 上传电子发票
     */
    public function invoice_edit()
    {
        $id = $order_sn = input('param.id');
        if ($id === null) {
            $this->error(lang('参数错误'));
        }
        // 获取数据
        $info = OrderInvoice::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            $data['invoice_img'] = request()->post('invoice_img');
            if(!$data['invoice_img']){
                $this->error(lang('请上传发票'));
            }
            $data['invoice_status'] = 2;
            $data['invoice_update_time'] = time();
            $DocumentModel = new OrderInvoice();
            $result = $DocumentModel->where(['id'=>$id])->update($data);
            if (false === $result) {
                $this->error($DocumentModel->getError());
            }
            //记录行为
            unset($data['__token__']);
            $details = arrayRecursiveDiff($data, $info);
            action_log('order_invoice_edit', 'user', $id, UID, $details);
            $this->success(lang('编辑成功'), 'index');
        }
       
        $fields = [
            ['type' => 'image', 'name' => 'invoice_img', 'title' => lang('电子发票')]
        ];
        $this->assign('page_title', lang('上传电子发票'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    public function refund_change_goods()
    {
        $rfid = input("param.rfid");
        if (!$rfid) {
            return $this->error(lang('参数错误'));
        }
        $rf_detail = OrderRefund::get($rfid);
        $data = OrderModel::get(["order_sn" => $rf_detail['order_sn']]);
        $order_info = OrderGoods::where(["order_sn" => $rf_detail['order_sn'], "goods_id" => $rf_detail['goods_id'], "sku_id" => $rf_detail['sku_id']])->select();
//        halt($order_info);
        Db::startTrans();
        try {
            //生成换货订单号
            $order_no = get_order_sn('CG');
            //添加商品订单附表信息
            $order_goods_info = OrderInfo::get(["order_sn" => $rf_detail['order_sn']])->toArray();
            $order_goods_info['order_sn'] = $order_no;
            $res1 = Db::name('order_goods_info')->insert($order_goods_info);
            if (!$res1) {
                exception(lang('保存订单附加信息失败'));
            }

            $money = 0;
            //实例化商品模型
            $goodinfo = new \app\goods\model\Goods();
            $goodsku = new \app\goods\model\GoodsSku();
//            var_dump($order_info);die;
            //添加订单商品表信息
            foreach ($order_info as $g) {
                // 初始化变量
                $goods = $where = $where1 = [];
                // 开始循环商品信息
                $good_info = $goodinfo->get($g['goods_id']);
                $goods['order_sn'] = $order_no;
                $goods['goods_id'] = $g['goods_id'];
                $goods['goods_name'] = $good_info['name'];
                $goods['shop_price'] = $good_info['shop_price'];
                $goods['member_price'] = $good_info['member_price'];
                $goods['sku_id'] = $g['sku_id'] ? $g['sku_id'] : 0;
                $goods['num'] = $g['num'];
                $stock = $good_info['stock'];
                $goods['goods_thumb'] = $good_info['thumb'];
                $goods['order_status'] = 1;
                if ($goods['sku_id']) {
                    //如果是sku商品，则查询sku的价格和库存
                    $sku_info = $goodsku->get(['sku_id' => $goods['sku_id'], 'goods_id' => $g['goods_id']]);
                    $goods['shop_price'] = $sku_info['shop_price'];
                    $stock = $sku_info['stock'];
                }
                if ($stock < $g['num']) {
                    exception($sku_info['key_name'] . ",库存不足，无法下单");
                }
//                var_dump(123);die;

                $goods['sku_name'] = $sku_info['key_name'];

                $goods['goods_money'] = bcmul($goods['shop_price'], $g['num'], 2);

                $money = bcadd($money, $goods['goods_money'], 2);
                // 分享赚
//                $goods['share_sign'] = $data['share_sign'];
                if ($goods['sku_id']) {
                    // 减sku库存
                    $where[] = ['sku_id', '=', $goods['sku_id']];
                    $where[] = ['stock', '>=', $g['num']];
                    $res3 = $goodsku->where($where)->setDec('stock', $g['num']);

                    // 增加sku销量
                    $goodsku->where(['sku_id' => $goods['sku_id']])->setInc('sales_num', $g['num']);
                    if (!$res3) {
                        exception($sku_info['key_name'] . ',' . lang('库存不足，无法下单'));
                    }
                } else {
                    // 减主商品库存
                    $where1[] = ['id', '=', $g['goods_id']];
                    $where1[] = ['stock', '>=', $g['num']];
                    $res4 = $goodinfo->where($where1)->setDec('stock', $g['num']);
                    if (!$res4) {
                        exception(lang('库存不足，无法下单'));
                    }
                }
                // 增加总销量
                $goodinfo->where(['id' => $g['goods_id']])->setInc('sales_sum', $g['num']);
                $goods_list[] = $goods;
            }
            //插入订单商品表
            $res2 = Db::name('order_goods_list')->insertAll($goods_list);
            if (!$res2) {
                exception(lang('保存订单商品失败'));
            }
            // 组装订单信息
            $orderData['user_id'] = $data['user_id'];
            $orderData['order_sn'] = $order_no;
            $orderData['order_money'] = $money;
            $orderData['payable_money'] = 0;
            $orderData['status'] = 1;
            $orderData['real_money'] = 0;
            $orderData['pay_status'] = 1;
            $orderData['pay_type'] = $data['pay_type'] ?? '';
            $orderData['coupon_id'] = 0;
            $orderData['coupon_money'] = 0;
            $orderData['order_type'] = 15;
            $orderData['reduce_money'] = 0;
            $orderData['integral_reduce'] = 0;

            // 插入订单信息
            $ret = OrderModel::create($orderData);
            if (!$ret) {
                exception(lang('创建订单失败'));
            }
            $updateData = [
                "status" => 4,
                "refund_order_sn" => $order_no,
            ];
            $ret = OrderRefund::where("id", $rfid)->update($updateData);
            if (!$ret) {
                exception(lang('售后状态修改失败'));
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }

        return $this->success("OK");
    }

    /*
     *
     * 新订单提醒
     *
     */
    public function new_order()
    {
        $res = OrderModel::where([
            'pay_status' => 1,
        ])
            ->order("create_time desc")
            ->limit(1)
            ->find();
        if ($res) {
            $ip = get_client_ip(0);
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $order_message = Db::name("order_message")->where([
                'ip' => $ip,
                'user_agent' => $user_agent,
                'order_sn' => $res['order_sn']
            ])->find();
            if (!$order_message) {
                $insert = [
                    'ip' => $ip,
                    'user_agent' => $user_agent,
                    'order_sn' => $res['order_sn'],
                    'create_time' => time()
                ];
                Db::name("order_message")->insert($insert);
                return ApiReturn::r(1, $res, 'ok');
            }
        }
        return ApiReturn::r(1, [], 'ok');
    }


    /**
     * 确认订单商品已到达自提点
     * @param string order_sn 订单号
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-19 18:01:59
     */
    public function pickup_arrive()
    {
        $order_sn = input("param.order_sn");
        //订单状态变为已发货
        $r = OrderModel::where("order_sn", $order_sn)->setField("status", 2);
        if (!$r) {
            $this->error(lang('处理失败'));
        }
        //给用户发送消息通知-可以自提了
        $user_id = OrderModel::where(['order_sn' => $order_sn])->value("user_id");
        //自提点信息
        $pickup_info = OrderPickup::getOrderPickUp($order_sn);
        $goods = OrderGoods::get_one_goods($order_sn);

        $message = SystemMessageModel::send_msg(
            $user_id,
            lang('您的'),
            lang('您的货物') . '：' . $order_sn . lang('已到自提点') . $pickup_info['deliver_name'] . '，' . lang('请及时前往取货'),
            1,
            3,
            1,
            $goods['goods_thumb'],
            '/pages/order/orderdetail/order-detail/index?order_sn=' . $order_sn . '&order_type=3'

        );
        $this->success("OK");
        return;
    }
}
