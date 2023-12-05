<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author jxy [ 41578218@qq.com ]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

namespace app\goods\admin;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Think\Db;

/**
 * 后台首页统计页面
 * @package app\ControlPanel\admin
 */
class ControlPanel
{
    private $order_type=['order_type','gt','2'];
    /**
    * 订单统计接口
    * @author jxy [41578218@qq.com]
    * @return mixed
    */
    public function index()
    {
//        var_dump(config('turntable_rules'));
        $total = \think\Db::name('order')->where([$this->order_type])->whereTime('create_time', 'd')->where('status', "in", '1,2,3,4')->sum('real_money');
        $user = \think\Db::name('user')->count();
        ;
        $gnum = \think\Db::name('goods')->where(['is_delete'=>0])->count();
        $num = \think\Db::name('order')->where([$this->order_type])->whereTime('create_time', 'd')->where('status', ">", 0)->count();
        $mnum = \think\Db::name('order')->where([$this->order_type])->whereTime('pay_time', 'month')->where('status', ">", 0)->count();
        $refundnum = \think\Db::name('order_refund')->whereTime('create_time', 'month')->count();
        $mfnum = \think\Db::name('order')->where([$this->order_type])->whereTime('create_time', 'm')->where([['status','in','3,4']])->count();
        $total_goods_money = 0;
        $total_sku_money = 0;
        $goods_money = \think\Db::name('goods')->where(['is_spec'=>0,'is_delete'=>0,'status'=>1])->field('sales_sum,stock,cost_price,is_spec,is_delete')->select();
        foreach ($goods_money as $k=>$v) {
            $total_goods_money += $v['cost_price']*($v['sales_sum']+$v['stock']);
        }
        $sku_goods_money = Db::name('goods')->alias('g')->field('g.id,s.stock,s.cost_price,s.sales_num')->join('lb_goods_sku s', 's.goods_id = g.id', 'left')->where(['g.is_spec'=>1,'g.is_delete'=>0,'g.status'=>1,'s.status'=>1])->select();
        foreach ($sku_goods_money as $k=>$v) {
            $total_sku_money += $v['cost_price']*($v['sales_sum']+$v['stock']);
        }
        $totalmoney = $total_goods_money+$total_sku_money;

        $sales = Db::query('SELECT real_money FROM lb_order WHERE pay_status=1 AND status=1 OR status=2 OR status=3 OR status=4');
        $sales_refund = Db::query('SELECT refund_money FROM lb_order_refund WHERE status=3');
        $sale_money = 0;
        $refund_money = 0;
        foreach ($sales as $k=>$v) {
            $sale_money += $v['real_money'];
        }
        foreach ($sales_refund as $k=>$v) {
            $refund_money += $v['refund_money'];
        }
        $sale_money = $sale_money-$refund_money;

        $nospec_stock = DB::name("goods")->field("id,stock")->where(['is_spec'=>0,'is_delete'=>0,'is_sale'=>1,'status'=>1])->sum('stock');
        $isspec_stock = Db::name('goods')->alias('g')->join('lb_goods_sku s', 's.goods_id = g.id', 'left')->where(['g.is_delete'=>0,'g.is_spec'=>1,'g.is_sale'=>1,'g.status'=>1,'s.status'=>1])->where('s.stock!=0')->sum('s.stock');
        $total_stock = $nospec_stock+$isspec_stock;

        $nospec_stock_price = DB::name("goods")->field("shop_price,stock")->where(['is_spec'=>0,'is_delete'=>0,'is_sale'=>1,'status'=>1])->select();
        $isspec_stock_price = Db::name('goods')->field("s.shop_price,s.stock")->alias('g')->join('lb_goods_sku s', 's.goods_id = g.id', 'left')->where(['g.is_delete'=>0,'g.is_spec'=>1,'g.is_sale'=>1,'g.status'=>1,'s.status'=>1])->where('s.stock!=0')->select();
        $total_nospec_stock_price = 0;
        $total_isspec_stock_price = 0;
        foreach ($nospec_stock_price as $v) {
            $total_nospec_stock_price += $v['stock'] * $v['shop_price'];
        }
        foreach ($isspec_stock_price as $v) {
            $total_isspec_stock_price += $v['stock'] * $v['shop_price'];
        }
        $total_price = $total_nospec_stock_price+$total_isspec_stock_price;


        $order_all = Db::name("order")->where("status=1 or status=2 or status=3 or status=4")->select();
        $refund_order_all = Db::name("order_refund")->where("status=3")->select();
        $total_order_yingli = 0;
        $refund_sku_cost = 0;
        $refund_goods_cost = 0;
        $discounts = 0;
        foreach ($order_all as $k=>$v) {
            $order_goods_list = DB::name("order_goods_list")->where(['order_sn'=>$v['order_sn']])->select();
            foreach ($order_goods_list as $key=>$value) {
                $goods = Db::name("goods")->where(['id'=>$value['goods_id']])->find();
                $discounts += $goods['discounts']*$value['num'];
            }
            $total_order_yingli += $v['real_money']-$v['cost_price_total'];
        }
        //var_dump($discounts);
        $total_order_yingli = $total_order_yingli-$discounts;
        $refund_discounts = 0;
        foreach ($refund_order_all as $k=>$v) {
            $goods = Db::name("goods")->where(['id'=>$v['goods_id']])->find();
            $refund_discounts += $goods['discounts']*$v['num'];

            if ($v['sku_id']>0) {
                $sku = Db::name("goods_sku")->where(['sku_id'=>$v['sku_id']])->find();
                $refund_sku_cost += $v['refund_money']-($sku['cost_price']*$v['num']);
            } else {
                $good = Db::name("goods")->where(['id'=>$v['goods_id']])->find();
                $refund_goods_cost += $v['refund_money']-($good['cost_price']*$v['num']);
            }
        }
//        var_dump($total_order_yingli);
//        var_dump($refund_goods_cost);
//        var_dump($refund_sku_cost);
        $all_gain = $total_order_yingli-($refund_goods_cost+$refund_sku_cost)+$refund_discounts;
        //zhougs 2020年8月17日11:42:05  取小数点前两位
        $all_gain = floor($all_gain*100)/100;
        $data = [
            'total' => $this->feeHandle($total),//今日订单总金额
            'user' => $user,//用户关注数
            'gnum'=>$gnum, //今日商品发布个数
            'num' => $num,//今日订单总数量
            'mnum' => $mnum,//本月订单总数量
            'mfnum' => $mfnum,//本月已交易完成
            'totalmoney' => $this->feeHandle($totalmoney),
            'sale_money' => $this->feeHandle($sale_money),
            'total_stock' => $this->feeHandle($total_stock),
            'total_price' => $this->feeHandle($total_price),
            'refundnum' => $this->feeHandle($refundnum),
            'all_gain'=>$this->feeHandle($all_gain)
        ];
        echo json_encode(['code'=>1,'data'=>$data]);
    }
    public function feeHandle($fee)
    {
        if (is_numeric($fee)) {
            list($int, $decimal) =  explode('.', $fee);
            $int = preg_replace('/(?<=[0-9])(?=(?:[0-9]{3})+(?![0-9]))/', ',', $int);
            if ($decimal) {
                $int .= '.'. $decimal;
            }
            return $int;
        }
    }
    /**
     * 订单状态统计接口
     * @author jxy [41578218@qq.com]
     * @return mixed
     */
    public function order()
    {
        $data[]=\think\Db::name('order')->where([$this->order_type])->where([['status','=','0']])->count();
        $data[]=\think\Db::name('order')->where([$this->order_type])->where([['status','=','1']])->count();
        $data[]=\think\Db::name('order')->where([$this->order_type])->where([['status','=','2']])->count();
        $data[]=\think\Db::name('order')->where([$this->order_type])->where([['status','=','3']])->count();
        $data[]=\think\Db::name('order')->where([$this->order_type])->where([['status','=','5']])->count();
        $data[]=\think\Db::name('order')->where([$this->order_type])->where([['status','=','6']])->count();
        echo json_encode(['code'=>1,'data'=>$data]);
    }

    /**
     * 订单时间段统计接口
     * @author jxy [41578218@qq.com]
     * @return mixed
     */
    public function order_time()
    {
        $time=[
            ['st'=>'00:00:00','et'=>'03:00:00'],
            ['st'=>'03:00:00','et'=>'06:00:00'],
            ['st'=>'06:00:00','et'=>'09:00:00'],
            ['st'=>'09:00:00','et'=>'12:00:00'],
            ['st'=>'12:00:00','et'=>'15:00:00'],
            ['st'=>'15:00:00','et'=>'18:00:00'],
            ['st'=>'18:00:00','et'=>'21:00:00'],
            ['st'=>'21:00:00','et'=>'23:59:59'],
        ];
        foreach ($time as $v) {
            $map=[];
            $stime = strtotime(date('Y-m-d').' '.$v['st']);
            $etime = strtotime(date('Y-m-d').' '.$v['et']);
            $map[]=['create_time','gt',$stime];
            $map[]=['create_time','lt',$etime];
            $map[]=[$this->order_type];
            $map[]=['order_type','neq',4];
            $map[]=['status','egt',0];
            $data[substr($v['st'], 0, 5)]=\think\Db::name('order')->where($map)->count();
        }
        echo json_encode(['code'=>1,'data'=>$data]);
    }
    
    /**
     * 用户注册时间段统计接口
     * @author jxy [41578218@qq.com]
     * @return mixed
     */
    public function user_time()
    {
        $time=[
            ['st'=>'00:00:00','et'=>'03:00:00'],
            ['st'=>'03:00:00','et'=>'06:00:00'],
            ['st'=>'06:00:00','et'=>'09:00:00'],
            ['st'=>'09:00:00','et'=>'12:00:00'],
            ['st'=>'12:00:00','et'=>'15:00:00'],
            ['st'=>'15:00:00','et'=>'18:00:00'],
            ['st'=>'18:00:00','et'=>'21:00:00'],
            ['st'=>'21:00:00','et'=>'23:59:59'],
        ];
        foreach ($time as $v) {
            $map=[];
            $stime = strtotime(date('Y-m-d').' '.$v['st']);
            $etime = strtotime(date('Y-m-d').' '.$v['et']);
            $map[]=['create_time','>',$stime];
            $map[]=['create_time','<',$etime];
            $data[substr($v['st'], 0, 5)]=\think\Db::name('user')->where($map)->count();
        }
        echo json_encode(['code'=>1,'data'=>$data]);
    }
    
    /**
     * 销售统计接口
     * @author jxy [41578218@qq.com]
     * @return mixed
     */
    public function sales()
    {
        $data['d_order_money'] = \think\Db::name('order')->where([$this->order_type])->whereTime('create_time', 'yesterday')->where('status', ">", 0)->sum('order_money');
        $data['d_order_total'] = \think\Db::name('order')->where([$this->order_type])->whereTime('create_time', 'yesterday')->where('status', ">", 0)->count();
        $data['m_order_money'] = \think\Db::name('order')->where([$this->order_type])->whereTime('create_time', 'month')->where('status', ">", 0)->sum('order_money');
        $data['m_order_total'] = \think\Db::name('order')->where([$this->order_type])->whereTime('create_time', 'month')->where('status', ">", 0)->count();
        echo json_encode(['code'=>1,'data'=>$data]);
    }
    
    /**
     * 商品排名接口
     * @author jxy [41578218@qq.com]
     * @return mixed
     */
    public function goods()
    {
        $goods = Db::name('goods')->where(['status'=>1,'is_delete'=>0,'is_sale'=>1])->order('sales_sum desc')->limit(0, 8)->select();
        echo json_encode(['code'=>1,'data'=>$goods]);
    }
    /**
     * 首页基础数据接口
     * @author jxy [41578218@qq.com]
     * @return mixed
     */
    public function userData()
    {
        $date[] = ['sdate'=>date('Y-m-d', strtotime(date('Y-m-d', strtotime("-1 day")))),'edate'=>(date('Y-m-d', 86400+strtotime(date('Y-m-d', strtotime("-1 day")))))];
        $date[] = ['sdate'=>date('Y-m-d', strtotime(date('Y-m-d', strtotime("-2 day")))),'edate'=>(date('Y-m-d', 86400+strtotime(date('Y-m-d', strtotime("-2 day")))))];
        $date[] = ['sdate'=>date('Y-m-d', strtotime(date('Y-m-d', strtotime("-3 day")))),'edate'=>(date('Y-m-d', 86400+strtotime(date('Y-m-d', strtotime("-3 day")))))];
        $date[] = ['sdate'=>date('Y-m-d', strtotime(date('Y-m-d', strtotime("-4 day")))),'edate'=>(date('Y-m-d', 86400+strtotime(date('Y-m-d', strtotime("-4 day")))))];
        $date[] = ['sdate'=>date('Y-m-d', strtotime(date('Y-m-d', strtotime("-5 day")))),'edate'=>(date('Y-m-d', 86400+strtotime(date('Y-m-d', strtotime("-5 day")))))];
        $date[] = ['sdate'=>date('Y-m-d', strtotime(date('Y-m-d', strtotime("-6 day")))),'edate'=>(date('Y-m-d', 86400+strtotime(date('Y-m-d', strtotime("-6 day")))))];
        $date[] = ['sdate'=>date('Y-m-d', strtotime(date('Y-m-d', strtotime("-7 day")))),'edate'=>(date('Y-m-d', 86400+strtotime(date('Y-m-d', strtotime("-7 day")))))];
        foreach ($date as $v) {
            $map=[];
            $map[] = ['create_time','egt',$v['sdate']];
            $map[] = ['create_time','elt',$v['edate']];
            $addUser[]=Db::name('user')->where($map)->count();
            $map[] = ['status','egt',1];
            $orderUser[]=Db::name('order')->group('user_id')->where($map)->count();
        }
        echo json_encode(['code'=>1,'date'=>$date,'addUser'=>$addUser,'orderUser'=>$orderUser]);
    }
}
