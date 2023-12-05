<?php
// +----------------------------------------------------------------------
// | LwwanPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.lwwan.com
// +----------------------------------------------------------------------
// | Author 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 QQ群331378225
// +----------------------------------------------------------------------

namespace app\goods\model;

use think\Model as ThinkModel;
use think\Db;

/**
 * 单页模型
 * @package app\goods\model
 */
class ActivityDetails extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_ACTIVITY_DETAILS__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 查找商品是否在做活动
     * @param $list 商品数组
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/29 22:10
     */
    public static function findActivityGoodsByList($list,$sortById=[])
    {

        $nowhour = (int)date('H');
        //$map[]=['gad.start_time','lt',$nowhour];
        //$map[]=['gad.end_time','gt',$nowhour];
        //$map[]=['ga.type','=',1];
        $map[]=['ga.status','=',1];
        foreach ($list as $k => $v) {
            $map = [];
            $map[] = ['gad.status', '=', 1];
            //商品id
            $map[] = ['gad.goods_id', '=', $v['id']];
            //获取活动商品信息，如果能找到，则标识该商品存在活动
            $goods_activity = self::alias('gad')->leftJoin('goods_activity ga', 'ga.id=gad.activity_id')
                ->field('gad.*,ga.type')
                ->where($map)
                ->order('gad.member_activity_price asc, gad.activity_price asc')
                ->find();
            //构造商品的活动信息
            if($goods_activity['unlimited']){
                if($goods_activity['start_time']<=$nowhour && $goods_activity['end_time']>$nowhour){
                    
                }else{
                    $goods_activity='';
                }
            }
            $list[$k]['activity_type'] = $goods_activity['type'] ? $goods_activity['type'] : 0;
            $list[$k]['activity_id'] = $goods_activity['activity_id'] ? $goods_activity['activity_id'] : 0;
            //若该商品的活动不存在,则使用goods_sku表的本店价和会员价
            if($v['is_spec']){
                 $sku = Db::name('goods_sku')->order('member_price asc, sku_id asc')->where([['goods_id', '=', $v['id']],['status', '=',1]])->find();
                 $list[$k]['sku_id'] = $sku['sku_id'] ? $sku['sku_id'] : 0;
                 $list[$k]['shop_price'] = $sku['shop_price'] ? $sku['shop_price'] : $v['shop_price'];
                 //$list[$k]['member_price'] = $sku['member_price'] ? $sku['member_price'] : $v['member_price'];
            }           

            if($goods_activity){//若该商品的活动存在,则使用goods_activity_details表的活动价和活动会员价
                $list[$k]['sku_id'] = $goods_activity['sku_id'];
                $list[$k]['activity_price'] = $goods_activity['activity_price'];
                $list[$k]['member_activity_price'] = $goods_activity['member_activity_price'];
            }
            $by_id_list[$v['id']]=$v;
        }
        $_list=[];
        foreach($sortById as $ks=>$id){
            if($by_id_list[$id]){
                $_list[]=$by_id_list[$id];
            }
        }
        if(!empty($_list)){
            $list=$_list;
        }
        return $list;
    }
    /**
     * 生成砍价第一刀的数额
     * @param  [type] $data         [description]
     * @param  [type] $user         [description]
     * @return [type]               [description]
     */
    public function cutPriceForFirst($data,$user){
        $price_start = $data['bargain_min'];
        $price_end = $data['bargain_max'];
        $min = bcmul($price_start,100);
        $max = bcmul($price_end,100);
        $cutPrice = bcdiv(bcadd($min, mt_rand(0, bcsub($max, $min))), 100, 2);
        $insertData  = [
            'bargain_order_id' => $data['bargain_id'],
            'bargain_money' => $cutPrice,
            'assistor_id' => $user['id'],
            'nick_name' => $user['user_nickname'],
            'head_img' => $user['head_img'],
            'create_time' => time()
        ];
        return Db::name('goods_bargain_order_list')->insertGetId($insertData);
    }

    /**
     * 生成砍价每一笔的数额
     * @param int $count 砍价的次数
     * @param double $price 砍价的金额
     * @param int $id 活动商品ID
     * @return void
     */
    public function cutPrice($count, $price, $id, $user_id)
    {
        //已砍价格的集合
        $alreadyList = [];
        //已砍的钱的总和
        $alreadyCut = 0;
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            //此次砍价的最低钱数（总价-已砍总价/总次数-已砍次数）（相当于是向上随机）（转换为单位分）
            $min = bcmul(bcdiv( bcsub($price, $alreadyCut,2), bcsub($count, count($alreadyList),2),2  ), 100);
            //此次砍价的最高钱数（最低价格的2倍）
            //这个倍数越高，砍价的幅度跳动越大。建议设置到1-2.（不能超过2.因为有可到导致总刀数不准确）
            $max = $min * 2;
            //此次砍的价格（最低钱数到最高钱数的随机）
            $cutPrice = bcdiv(bcadd($min, mt_rand(0, bcsub($max, $min))), 100, 2);
            //最后一刀保证价格准确
            if ($i == ($count - 1)) {
                $cutPrice = bcsub($price, $alreadyCut, 2);
            }
            if($cutPrice <= 0.01){
                $cutPrice = bcsub($price, $alreadyCut, 2);
                $data[] = ['activity_goods_id' => $id, 'cut_price' => $cutPrice, 'uid'=>$user_id];
                break;
            }
            $alreadyCut = bcadd($alreadyCut, $cutPrice, 2);
            $alreadyList[] = $cutPrice;
            $data[] = ['activity_goods_id' => $id, 'cut_price' => $cutPrice, 'uid'=>$user_id];
        }
        if( count($data) > 0 ){
            Db::name('goods_activity_cut_price')->insertAll($data);
        }

        return true;
    }

    /**
     * 计算砍价剩余百分比
     * @param $bargain_id
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBargainSurplusAmount($bargain_id){
        if( !$bargain_id ){
            return false;
        }
        $info = Db::name("goods_bargain_order")->where("id",$bargain_id)->find();
        if( $info ){
            $detail_info = self::get($info['activity_detail_id']);
            $bargain_money = Db::name("goods_bargain_order_list")->where("bargain_order_id",$bargain_id)->sum("bargain_money");
            $surplusAmount = bcsub($detail_info['activity_price'],$bargain_money,2);
            if( $surplusAmount == 0 ){
                $percentage = 0;
            }else{
                $percentage = bcdiv($surplusAmount,$detail_info['activity_price'],2)*100;
            }
            $data = [
                "bargain_money"=>$bargain_money,//已砍金额
                "percentage"=>$percentage,//剩余百分比
            ];
            return $data;
        }
        return false;
    }


    /**
     * 判断商品是否在正启用的活动商品
     */
    public function isActivityGoods($goods_id)
    {
        if(empty($goods_id)) {
            return false;
        }
        $map = [
            ['goods_id', '=', $goods_id],
            ['status', '=', 1],
        ];
        $res = self::where($map)->field('activity_id')->find();
        if ($res['activity_id']) {
            $map2 = [
                ['id', '=', $res['activity_id']],
                ['status', '=', 1],
                ['sdate', '<', time()],
                ['edate', '>', time()],
            ];
            $activity = Db::name('goods_activity')->where($map2)->field('id')->find();
            if ($activity) {
                return true;
            }
            return false;
        }
        return false;
    }

}