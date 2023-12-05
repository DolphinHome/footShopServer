<?php
namespace app\index\controller;


use think\Db;
use think\Controller;

class Index extends Controller
{
    public function index1()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    public function index()
    {
        $this->redirect('/admin.php');
    }
    public function getInformation(){
            $data = input();
            $info = $data['data'];
            dump($info);die;
            // $end_string = strstr($info,'https:');
            // dump($end_string);
            $health_url = file_get_contents($end_string);
            dump($health_url);die;
            $health_info_json = iconv("GBK","UTF-8",$health_url);
//            dump($data);die;
            $health_info_arr = json_decode($health_info_json,true);
            dump($health_info_arr);die;
//            $indert_str = json_encode($data);
//            Db::name('health_report')->insert(['content'=>$health_info_json,'create_time'=>time()]);
//            $this->assign('data',$health_info_json);
//            return $this->fetch('/index/get_information');
    }
    /**
     * 15分钟的计时器访问该地址
     * 取消15分钟内未支付的订单
     *
     */
    public function batchCancelOrder()
    {
        Db::startTrans();
        try {
            $timeout = module_config('goods.order_timeout') * 60;
            $order_map[] = ['og.order_status', '=', 0];
            $order_map[] = ['o.status', '=', 0];
            $order_map[] = ['o.create_time', 'lt', (time() - $timeout)];
            $order_goods = Db::name('order_goods_list')->field('og.*')->alias('og')->where($order_map)->join('order o', 'o.order_sn=og.order_sn', 'left')->select();
            foreach ($order_goods as $v) {
                $acd[] = [['goods_id', '=', $v['goods_id']]];
                if ($v['sku_id']) {
                    $acd[] = [['sku_id', '=', $v['sku_id']]];
                }
                $acd[] = [['activity_id', '=', $v['activity_id']]];
                $activityGoods = Db::name('goods_activity_details')->where($acd)->find();
                if ($activityGoods) {
                    Db::name('goods_activity_details')->where([['id', '=', $activityGoods['id']]])->setInc('stock', $v['num']);
                    $group_user = Db::name('goods_activity_group_user')->where([['order_sn', '=', $v['order_sn']]])->find();
                    if ($group_user) {
                        if (count($group_user) == $activityGoods['join']) {
                            Db::name('goods_activity_group')->where([['id', '=', $group_user['group_id']]])->setFields('is_full', 0);
                        }
                        Db::name('goods_activity_group')->where([['id', '=', $group_user['group_id']]])->setDec('num');
                        Db::name('goods_activity_group_user')->where([['order_sn', '=', $v['order_sn']]])->delete();
                    }
                }
                $goods = Db::name('goods')->where([['id', '=', $v['goods_id']]])->lock(true)->find();
                if ($goods['is_spec']) {
                    if($v['activity_id'] != 0){
                        Db::name('goods_activity_details')->where(['goods_id'=>$v['goods_id'],'sku_id'=>$v['sku_id'],'activity_id'=>$v['activity_id']])->setInc('stock',$v['num']);
                        Db::name('goods_activity_details')->where(['goods_id'=>$v['goods_id'],'sku_id'=>$v['sku_id'],'activity_id'=>$v['activity_id']])->setDec('sales_sum',$v['num']);
                        Db::name('goods')->where([['id', '=', $v['goods_id']]])->setDec('sales_sum', $v['num']);
                    }else {
                        Db::name('goods_sku')->where([['sku_id', '=', $v['sku_id']]])->lock(true)->find();
                        Db::name('goods_sku')->where([['sku_id', '=', $v['sku_id']]])->setInc('stock', $v['num']);
                        Db::name('goods_sku')->where([['sku_id', '=', $v['sku_id']]])->setDec('sales_num', $v['num']);
                        Db::name('goods')->where([['id', '=', $v['goods_id']]])->setDec('sales_sum', $v['num']);
                    }
                }else{
                    if($v['activity_id'] != 0){
                        Db::name('goods_activity_details')->where(['goods_id'=>$v['goods_id'],'sku_id'=>0,'activity_id'=>$v['activity_id']])->setInc('stock',$v['num']);
                        Db::name('goods_activity_details')->where(['goods_id'=>$v['goods_id'],'sku_id'=>0,'activity_id'=>$v['activity_id']])->setDec('sales_sum',$v['num']);
                        Db::name('goods')->where([['id', '=', $v['goods_id']]])->setDec('sales_sum', $v['num']);
                    }else {
                        Db::name('goods')->where([['id', '=', $v['goods_id']]])->setInc('stock', $v['num']);
                        Db::name('goods')->where([['id', '=', $v['goods_id']]])->setDec('sales_sum', $v['num']);
                    }
                }


                Db::name('order')->where('order_sn', $v['order_sn'])->setField('status', -1);
                Db::name('operation_coupon_record')->where(['order_sn'=>$v['order_sn']])->update(['status'=>1,'use_time'=>0,'order_sn'=>'']);
                Db::name('order_goods_list')->where('order_sn', $v['order_sn'])->setField('order_status', -1);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
}
