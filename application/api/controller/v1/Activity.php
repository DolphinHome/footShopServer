<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\goods\model\ActivityDetails;
use app\goods\model\GoodsRewardDetails;
use app\goods\model\GoodsRewardRecord;
use app\operation\model\Coupon;
use app\user\model\User;

use app\operation\model\CouponRecord as RecordModel;
use service\ApiReturn;
use think\Db;
use app\common\model\Order as OrderModel;
use app\goods\model\Activity as ActivityModel;

class Activity extends Base
{

    /**
     * 根据小时区间获取所有活动
     * @param $data
     * @author jxy<41578218@qq.com>
     * @created 2019/11/25 19:14
     */
    public function index($data = [])
    {
        //type 1秒杀活动2拼团活动3预售活动4折扣活动5砍价活动6首次限购8积分商品9抽奖9宫格10砍价活动
        $map_act[] = ['status', '=', 1];
        $map_act[] = ['type', '=', $data['type']];
//        $map_act[] = ['show_position', '=', 'index'];
        $time = time();
        if (!in_array($data['type'], ActivityModel::$no_time)) {
            $map_act[] = ['sdate', 'lt', $time];
            $map_act[] = ['edate', 'gt', $time];
        }
        $activity = Db::name('goods_activity')->where($map_act)->column('type', 'id');
        if (!$activity) {
            return ApiReturn::r(0, ['list' => []], lang('活动不存在'));
        }
        $map[] = ['a.activity_id', 'in', implode(',', array_keys($activity))];
        $map[] = ['a.status', '=', 1];
        if ($data['type'] == 1) {
            //秒杀活动条件调用
            $map[] = ['ga.type', '=', 1];
            $map1[] = ['a.start_time', '>=', $data["start_time"]];
            $map1[] = ['a.end_time', '<=', $data["end_time"]];
            $map2[] = ['a.unlimited', '=', 0];
            $goodActivityDetails = Db::name('goods_activity_details')
                ->alias('a')
                ->join('goods_sku gs', 'gs.sku_id=a.sku_id and gs.status=1 ', 'left')
                ->join('goods_activity ga', 'ga.id=a.activity_id', 'left')
                ->field('a.*,gs.market_price,gs.shop_price,ga.type as activity_type')
                ->where($map)
                ->where(function ($query) use ($map1, $map2) {
                    $query->whereOr([$map1, $map2]);
                });

            // zenghu ADD 查询用户是否已设置提醒 2020年8月27日16:19:29
            if (!empty($data['user_id'])) {
                // 由于通知时间设置提前三分钟 因此此处设定区间2分钟浮动 防止在59秒请求添加为下一分钟的值且客户端小幅度调整通知的时间
                // 此处MYSQL中查询必须是时间格式才可以比较成功，否则无法比较，因为必须格式化为时间格式yy-mm-dd hh:ii:ss
                $startTime = date('Y-m-d ') . sprintf("%02d", ($data['start_time'] - 1)) . ':55:00';
                $endTime = date('Y-m-d ') . sprintf("%02d", $data['start_time']) . ':00:00';
                $goodActivityDetails = $goodActivityDetails
                    ->field("IF(q.q_id IS NULL, 'false', 'true') message_notify")
                    ->join(
                        'queue q',
                        "q.q_goods_id = a.goods_id 
                        AND q.q_user_id = {$data['user_id']} 
                        AND ( 
                            CONCAT(CURDATE(), ' ', q.q_implement_time, ':00') >= '{$startTime}' 
                            AND CONCAT(CURDATE(), ' ', q.q_implement_time, ':00') <= '{$endTime}'
                        )",
                        'LEFT'
                    );
            }

            // 查询秒杀商品数量
            $total = $goodActivityDetails->count();

            // 查询商品数据
            $goods_activity = $goodActivityDetails->group('a.goods_id')->paginate();
            if (count($goods_activity) > 0) {
                $goods_activity = $goods_activity->toArray()['data'];
            } else {
                $goods_activity = [];
            }

        } else {
            //拼团，预售，折扣，砍价

            //判断拼团 如果库存小于拼团人数 拼团不显示

            $where = [];
            if ($data['type'] == 2) {
                $where = " a.stock >= a.join_number ";
            }

            $total = Db::name('goods_activity_details')
                ->alias('a')
                ->join('goods_sku gs', 'gs.sku_id=a.sku_id', 'left')
                ->join('goods_activity ga', 'ga.id=a.activity_id', 'left')
                ->where($map)
                ->where($where)
                ->count();
            
            $goods_activity = Db::name('goods_activity_details')
                ->alias('a')->field('a.*,gs.market_price,gs.shop_price,ga.type as activity_type,a.deposit,a.sales_integral,a.is_pure_integral,a.sales_sum')
                ->join('goods_sku gs', 'gs.sku_id=a.sku_id', 'left')
                ->join('goods_activity ga', 'ga.id=a.activity_id', 'left')
                ->where($map)
                ->where($where)
                ->paginate();
            $tmp_goods_activity = $goods_activity;
            $goods_activity = [];
            foreach ($tmp_goods_activity as $item) {
                if (isset($goods_activity[$item['goods_id']])) {
                    if ($goods_activity[$item['goods_id']]['activity_price'] < $item['activity_price']) {
                        continue;
                    }
                }
                $goods_activity[$item['goods_id']] = $item;
            }
            
        }
        foreach ($goods_activity as $k => $v) {
            $goods = Db::name('goods')->find($v['goods_id']);
            if (!$goods['is_sale']) {
                unset($goods_activity[$k]);
                continue;
            }
            $goods_activity[$k]['market_price'] = $goods['is_spec'] ? $v['market_price'] : $goods['market_price'];
            $goods_activity[$k]['balance'] = $v['activity_price'] - $v['deposit'];
            $goods_activity[$k]['thumb'] = get_file_url($goods['thumb']);
            $goods_activity[$k]['shop_price'] = $v['activity_price'];
            //$goods_activity[$k]['sales_sum'] = $goods['sales_sum'];
            $goods_activity[$k]['id'] = $goods['id'];
            $goods_activity[$k]['have_sum'] = Db::name('goods_activity_details')->where('activity_id', $v['activity_id'])->where('goods_id', $v['goods_id'])->sum('stock') + $goods_activity[$k]['sales_sum'];
            if ($goods_activity[$k]['have_sum'] != 0) {
                $goods_activity[$k]['rate'] = ceil($goods_activity[$k]['sales_sum'] / $goods_activity[$k]['have_sum'] * 100);
            } else {
                $goods_activity[$k]['rate'] = 0;
            }
            $goods_activity[$k]['discounts'] = $goods['discounts'];
            $goods_activity[$k]['share_award_money'] = $goods['share_award_money'];
            //活动还未开始的时候 销量和占比显示为0
            if ((int)date("H") < (int)$data['start_time']) {
                $goods_activity[$k]['rate'] = 0;
                $goods_activity[$k]['sales_sum'] = 0;

            }
            if ($goods['is_spec']) {
                $goods_activity[$k]['member_price'] = Db::name('goods_sku')->where(['goods_id' => $goods['id']])->value('member_price');
            } else {
                $goods_activity[$k]['member_price'] = $goods['member_price'];
            }

        }

        if ($goods_activity) {
            $goods_activity = array_values($goods_activity);
            return ApiReturn::r(1, ['list' => $goods_activity, 'total' => $total], lang('请求成功'));
        } else {
            return ApiReturn::r(0, ['list' => [], 'total' => 0], lang('时间点') . $data["start_time"] . '~' . $data["end_time"] . lang('暂无活动数据'));
        }
    }

    /**
     * 根据活动类型获取所有活动的海报图
     * @param $data
     * @author jxy<41578218@qq.com>
     * @created 2019/11/25 19:14
     */
    public function lists($data = [])
    {
        if ($data['type']) {
            $type = explode(',', $data['type']);
            $map[] = ['ga.type', 'in', $type];
        }
        $map[] = ['ga.show_position', '=', 'index'];
        $map[] = ['ga.status', '=', 1];
        $activity = Db::name('goods_activity ga')->field('ga.id,ga.slogan,ga.name,u.path,ga.background,ga.type')->where($map)->join('upload u', 'ga.icon=u.id', 'left')->select();
        foreach ($activity as $k => $v) {
            $activity[$k]['background'] = get_file_url($v['background']);
            $act_list[$v['type']][] = $activity[$k];
        }
        return ApiReturn::r(1, ['list' => $act_list], lang('请求成功'));
    }


    /**
     * 获取拼团活动的拼团列表
     * @param $data
     * @param $user
     * @author jxy<41578218@qq.com>
     * @created 2019/11/25 19:14
     */
    public function myGroup($data = [], $user = [])
    {
        $map = [
            ['gagu.uid', '=', $user['id']],
            ['o.status', 'in', '0,1']
        ];
        switch ($data['status']) {
            case 'full':
                $map[] = ['gag.is_full', '=', 1];
                break;
            case 'going':
                $map[] = ['gag.is_full', '=', 0];
                break;
            case 'fail':
                $map[] = ['gag.is_full', '=', 0];
                // $map[]=['a.edate','<',time()];
                $map[] = ['o.status', '=', '-1'];
                break;
            case 'all':
                break;
        }/*
        $nowhour=date('H');$nowtime=time();
        $activity_map[]=['gad.start_time','lt',$nowhour];
        $activity_map[]=['gad.end_time','gt',$nowhour];
        $activity_map[]=['ga.sdate','lt',$nowtime];
        $activity_map[]=['ga.edate','gt',$nowtime];
        $activity_map[]=['ga.type','=',2];
        $activity = Db::name('goods_activity_details')->alias('gad')->where($activity_map)->join('goods_activity ga','gad.activity_id=ga.id','left')->column('ga.id');
        $activity=implode(',',array_unique($activity));
        if(!$activity){
            return ApiReturn::r(1, ['list'=>[],'total'=>0 ], "请求成功");
        }
       $map[]=['gag.activity_id','in',$activity];
       */
        $group_self = Db::name('goods_activity_group_user gagu')
            ->limit(($data['page'] - 1), $data['page'] * $data['size'])
            ->join('goods_activity_group gag', 'gagu.group_id=gag.id', 'left')
            ->join('order o', 'o.order_sn=gagu.order_sn', 'left')
            ->join('goods_activity a', 'a.id=gag.activity_id')
            ->field('gagu.group_id,gagu.order_sn,gag.is_full,gag.status as activity_status,gag.activity_id,o.pay_status,o.status as order_status')
            ->where($map)
            ->paginate();
        $total = 0;
        if (count($group_self) > 0) {
            $group_self_arr = $group_self->toArray();
            $group_self = $group_self_arr['data'];
            $total = $group_self_arr['total'];
        } else {
            $group_self = [];
        }
        foreach ($group_self as $k => $v) {
            $order_goods = Db::name('order_goods_list')->where('order_sn', $v['order_sn'])->find();
            $group_self[$k]['pay_status'] = $v['pay_status'];
            $group_self[$k]['goods_name'] = $order_goods['goods_name'];
            $group_self[$k]['sku_name'] = $order_goods['sku_name'];
            $group_self[$k]['shop_price'] = $order_goods['shop_price'];
            $group_self[$k]['num'] = $order_goods['num'];
            $group_self[$k]['goods_thumb'] = get_file_url($order_goods['goods_thumb']);
            $activity = Db::name('goods_activity')->get($v['activity_id']);
            $group_self[$k]['sdate'] = $activity['sdate'];
            $group_self[$k]['edate'] = $activity['edate'];
            $group_self[$k]['activity_info'] = Db::name('goods_activity_details')->where([['activity_id', '=', $v['activity_id']], ['goods_id', '=', $order_goods['goods_id']]])->find();
            $group_self[$k]['group_user'] = Db::name('goods_activity_group_user')->where(['group_id' => $v['group_id']])->select();

            // if($group_self[$k]['activity_status'] == 1 && $group_self[$k]['is_full'] == 1){
            //     $group_self[$k]['group_status'] = 1;  // 已完成
            // }elseif ($v['order_status'] == -1 ) {
            //     $group_self[$k]['group_status'] = 3;  // 已关闭
            // }else{
            //     $group_self[$k]['group_status'] = 2;   // 正在拼团
            // }

        }
        return ApiReturn::r(1, ['list' => $group_self, 'total' => $total], lang('请求成功'));
    }

    /**
     * 对活动商品进行砍价
     * @param $data
     * @param $user
     * @author jxy<41578218@qq.com>
     * @created 2019/11/25 19:14
     */
    public function cutPrice($data = [], $user = [])
    {
        $map[] = ['goods_id', '=', $data['goods_id']];
        $map[] = ['sku_id', '=', $data['sku_id']];
        $map[] = ['activity_id', '=', $data['activity_id']];
        $goods_activity = Db::name('goods_activity')->get($data['activity_id']);
        if ($goods_activity['type'] != 5) {
            return ApiReturn::r(0, [], lang('该商品的砍价活动不存在'));
        }
        $goods_activity_details = Db::name('goods_activity_details')->where($map)->find();
        $res = Db::name('goods_activity_cut_price')->where([['activity_goods_id', '=', $goods_activity_details['id']], ['uid', '=', $user['id']]])->find();
        if ($res) {
            return ApiReturn::r(0, [], lang('你已砍过该商品的价格'));
        }
        Db::startTrans();
        try {
            $cut_price = Db::name('goods_activity_cut_price')->order('id')->lock(true)->where([['activity_goods_id', '=', $goods_activity_details['id']], ['uid', '=', 0]])->find();
            if (!$cut_price) {
                exception(lang('没有用于可砍的价格'));
            }
            $is_cut = Db::name('goods_activity_cut_price')->where([['id', '=', $cut_price['id']]])->update(['uid' => $user['id'], 'cut_time' => time()]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, '', $e->getMessage());
        }
        if ($is_cut) {
            $order_info = json_decode($data['order_info'], true);
            if ($order_info) {
                //TO DO LIST
                $order_sn = OrderModel::addCutPriceOrder($data, $user, $goods_activity_details, $cut_price['id']);
            }
            $this->IsfinishCutPrice($goods_activity_details);
            return ApiReturn::r(1, ['order_sn' => $order_sn['order_sn']], lang('砍价成功'));
        } else {
            return ApiReturn::r(0, ['order_sn' => ''], lang('砍价失败'));
        }
    }

    /**
     * 判断活动商品砍价是否完成
     * @param $goods_activity_details
     * @author jxy<41578218@qq.com>
     * @created 2019/11/25 19:14
     */
    private function IsfinishCutPrice($goods_activity_details)
    {
        $map[] = ['activity_goods_id', '=', $goods_activity_details['id']];
        $map[] = ['uid', '=', 0];
        $cut_price_list = Db::name('goods_activity_cut_price')->where($map)->find();
        if (!$cut_price_list) {
            $map[] = ['activity_goods_id', '=', $goods_activity_details['id']];
            $order_sn_list = array_filter(Db::name('goods_activity_cut_price')->where($map)->column('order_sn'));
            if ($order_sn_list) {
                Db::name('order')->where(['order_sn', 'in', $order_sn_list])->setField('status', 0);
                Db::name('order_goods_list')->where(['order_sn', 'in', $order_sn_list])->setField('order_status', 0);
            }
        }
    }

    /**
     * 我参与的砍价列表
     * @param $data
     * @param $user
     * @author jxy<41578218@qq.com>
     * @created 2019/11/25 19:14
     */
    public function MyCutPriceList($data = [], $user = [])
    {
        $cutlist = Db::name('goods_activity_cut_price')->alias('c')
            ->join('goods_activity_details gad', 'gad.id=c.activity_goods_id', 'left')
            ->field('c.cut_price,c.cut_time,gad.name,gad.activity_price,gad.goods_id,gad.sku_id,gad.activity_id')
            ->where(['c.uid' => $user['id']])
            ->select();
        foreach ($cutlist as $k => $v) {
            $goods = Db::name('goods')->get($v['goods_id']);
            $cutlist[$k]['thumb'] = get_file_url($goods['thumb']);
        }

        return ApiReturn::r(1, $cutlist, lang('请求成功'));
    }

    /*
     * 抽奖九宫格
     *
     */
    public function reward($data = [], $user = [])
    {
        $list = GoodsRewardDetails::where([])
            ->select()
            ->toArray();
        $count = count($list);
        if ($list) {
            foreach ($list as &$v) {
                $v['img'] = get_file_url($v['img']);
            }
        }

        if ($count <= 7) {
            $thanks = [];//谢谢参与
            $n = 7 - $count;
            for ($x = 0; $x <= $n; $x++) {
                $thanks[] = [
                    'id' => time() + $x,
                    'chance' => 0,
                    'level_name' => '谢谢参与',
                    'img' => ''
                ];
            }
            $list = array_merge($list, $thanks);
        }
        foreach ($list as $k => &$v) {
            $v['ids'] = $k + 1;
        }
        $details = $this->get_gift($list);
        $win = [
            'id' => 0
        ];

        if ($details['status'] == 1) {
            $win = [
                'id' => $details['details_id'],
                'type' => $details['type'],
                'name' => $this->getName($details['type'], $details['details_id']),
                'money' => $this->getMoney($details['type'], $details['reward_id']),
                'img' =>
                    get_file_url(Db::name("goods_reward_details")
                        ->where(['id' => $details['details_id']])
                        ->value("img")),
            ];
            //写入中奖纪录
            $insert_data = [
                'reward_id' => $details['reward_id'],
                'user_id' => $user['id'],
                'type' => $details['type'],
                'create_time' => time(),
                'details_id' => $details['details_id']
            ];
            GoodsRewardRecord::create($insert_data);

            //插入优惠券
            if ($details['type'] == 2) {

                $data['cid'] = $details['reward_id'];
                $is_receive = RecordModel::where(['user_id' => $user['id'], 'cid' => $data['cid']])->count();
                if ($is_receive) {
                    return ApiReturn::r(0, [], lang('请勿重复领取'));
                }
                // 启动事务
                Db::startTrans();
                try {
                    $map[] = ['id', '=', $data['cid']];
                    $map[] = ['status', '=', 1];
                    $map[] = ['end_time', '>', time()];
                    $map[] = ['last_stock', '>', 0];
                    $info = Db::name('operation_coupon')->where($map)->field('valid_day')->lock(true)->find();
                    if (!$info) {
                        exception(lang('优惠券已经被领取完了'));
                    }
                    //减少优惠券库存
                    $res = Db::name('operation_coupon')->where($map)->setDec('last_stock');
                    if (!$res) {
                        exception(lang('优惠券已经被领取完了'));
                    }

                    //增加会员优惠券领取记录
                    $receive_data['user_id'] = $user['id'];
                    $receive_data['cid'] = $data['cid'];
                    $receive_data['start_time'] = time();
                    $receive_data['end_time'] = $receive_data['start_time'] + 86400 * $info['valid_day'];
                    $receive_data['status'] = 1;
                    $res = Db::name('operation_coupon_record')->insert($receive_data);
                    if (!$res) {
                        exception(lang('优惠券领取失败'));
                    }

                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return ApiReturn::r(0, [], $e->getMessage());
                }
            }


        }
        $data = [
            'list' => $list,
            'win' => $win
        ];


        return ApiReturn::r(1, $data, 'ok');

    }

    /*
     * 抽奖
     *
     */

    public function get_gift($data)
    {


        foreach ($data as $key => $val) {
            $arr[$val['ids']] = $val['chance'];//概率数组
        }

        $rid = $this->get_rand($arr); //根据概率获取奖项id
        $res = [
            'yes' => $data[$rid - 1]['level_name'],
            'id' => $data[$rid - 1]['reward_id'],
            'type' => $data[$rid - 1]['type'],
            'details_id' => $data[$rid - 1]['id']
        ];
        unset($data[$rid - 1]); //将中奖项从数组中剔除，剩下未中奖项
        shuffle($data); //打乱数组顺序
        for ($i = 0; $i < count($data); $i++) {
            $pr[] = $data[$i]['level_name']; //未中奖项数组
        }
        $res['no'] = $pr;
        // var_dump($res);


        if ($res['yes'] != '谢谢参与') {
            $result = [
                'status' => 1,
                'reward_id' => $res['id'],
                'type' => $res['type'],
                'details_id' => $res['details_id'],
                'msg' => $res['yes']
            ];
        } else {
            $result = [
                'status' => -1,
                'msg' => $res['no'],

            ];
        }
        return $result;
    }

    //计算中奖概率
    public function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum); //返回随机整数

            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

    /*
     * 获取奖品名称
     *
     */
    public function getName($type, $id)
    {
        $name = GoodsRewardDetails::where(['id' => $id])->value("name");
        if ($type == 3) {
            $name = '谢谢参与';
        }
        return $name;
    }

    /*
     * 获取优惠券金额
     *
     */
    public function getMoney($type, $id)
    {
        $res = 0;
        if ($type == 2) {
            $res = Coupon::where(['id' => $id])->value("money");
        }
        return $res;

    }

    /*
     * 领取奖品
     *
     */
    public function getReward($data = [], $user = [])
    {


        return ApiReturn::r(1, [], lang('领取成功'));
    }

    /**
     * 获取我的砍价列表
     * @param $data
     * @param $user
     * @return \think\response\Json
     */
    public function getBargaining($data, $user)
    {

        $resData = [];
        $time = time();
        $overTime = $time - 86400;
        $where[] = ["ad.create_time", "gt", $overTime];
        $where[] = ["b.user_id", "=", $user['user_id']];
        $where[] = ["b.is_addorder", "=", 0];
        $resData = ActivityDetails::alias("ad")
            ->join("goods_bargain_order b", "ad.id = b.activity_detail_id", "left")
            ->join("goods g", "g.id = b.goods_id", "left")
            ->field("g.thumb,b.create_time,ad.goods_id,ad.sku_id,g.name,b.id bargain_id,activity_price")
            ->where($where)
            ->select();
        if (count($resData) > 0) {
            foreach ($resData as $key => $val) {
                $resData[$key]['count_down'] = strtotime($val['create_time']) + 86400 - $time;
                $resData[$key]['thumb'] = get_file_url($val['thumb']);
                //统计总共砍掉多少
                $resData[$key]['cut_price'] = Db::name("goods_bargain_order_list")->where("bargain_order_id", $val['bargain_id'])->sum("bargain_money") ?: 0;
//                halt($resData[$key]['cut_price']);
//                halt(bcsub($val['activity_price'],$resData[$key]['cut_price'],2));
                //计算剩余百分比 剩余价格/原价
                $resData[$key]['surplus_percentage'] = bcdiv(bcsub($val['activity_price'], $resData[$key]['cut_price'], 2), $val['activity_price'], 2) * 100;
            }
        }
        return ApiReturn::r(1, $resData, lang('获取成功'));
    }

    /**
     * 获取砍价记录
     * @param $data
     * @param $user
     * @return \think\response\Json
     */
    public function getBargainRecord($data, $user)
    {
        if (!$data['bargain_id']) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $resData = [];
        $resData = Db::name("goods_bargain_order_list")
            ->field("nick_name,head_img,bargain_money,create_time")
            ->where("bargain_order_id", $data['bargain_id'])
            ->paginate()
            ->each(function ($item) {
                $item['head_img'] = get_file_url($item['head_img']);
                return $item;
            });

        return ApiReturn::r(1, $resData, lang('获取成功'));
    }

    /**
     * 帮TA砍价
     * @param $data
     * @author zhougs
     * @createTime 2020年12月24日16:34:59
     */
    public function bargainCutPrice($data, $user)
    {
        if (empty($data)) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        if ($data['user_id']) {
            /*            $shareDetail = Db::name("goods_share")->get(["share_sign"=>$data['share_sign']]);
                        if( !$shareDetail ){
                            return ApiReturn::r(0, [], lang('无邀请记录'));
                        }*/
//            $where[] = ["activity_id","=",$data['activity_id']];
//            $where[] = ["goods_id","=",$data['goods_id']];
//            $where[] = ["sku_id","=",$data['sku_id']];
//            $where[] = ["user_id","=",$data['user_id']];
            $where[] = ["id", "=", $data['bargain_id']];
            $where[] = ["is_addorder", "=", 0];
            $bargain_order = Db::name("goods_bargain_order")->where($where)->find();
//            halt($bargain_order);
            if (!$bargain_order) {
                return ApiReturn::r(0, [], lang('无此活动'));
            }
            $activity_details = Db::name("goods_activity_details")->where('id', $bargain_order['activity_detail_id'])->find();
            if (!$activity_details) {
                return ApiReturn::r(0, [], lang('无此活动商品'));
            }
            //["user_id"=>$user['id',"bargain_order_id"=>$bargain_order['id']]
            $gbWhere[] = ["assistor_id", "=", $user['user_id']];
            $gbWhere[] = ["bargain_order_id", "=", $bargain_order['id']];

            if ($user['user_id'] == $bargain_order['user_id']) {
                //自己砍一刀接口
                $res = Db::name("goods_bargain_order_list")->where($gbWhere)->find();
                if ($res) {
                    return ApiReturn::r(0, [], lang('您已为自己砍过一刀了'));
                } else {
                    $resData = [];
                    $adData = [
                        "bargain_min" => $activity_details['bargain_min'] / 100 * $activity_details['activity_price'],
                        "bargain_max" => $activity_details['bargain_max'] / 100 * $activity_details['activity_price'],
                        "bargain_id" => $bargain_order['id']
                    ];
                    $adUser = User::get($user['user_id']);
                    $bargain_order_list_id = ActivityDetails::cutPriceForFirst($adData, $adUser);
                    $bargain_money = Db::name("goods_bargain_order_list")->where("id", $bargain_order_list_id)->value("bargain_money");
                    ActivityDetails::cutPrice($activity_details['least_count'] - 1, bcsub($activity_details['activity_price'], $bargain_money, 2), $activity_details['id'], $bargain_order['user_id']);
                    $resData['cut_price'] = $bargain_money;
                    $resData['surplus_percentage'] = bcdiv(bcsub($activity_details['activity_price'], $bargain_money, 2), $activity_details['activity_price'], 2) * 100;
                    $resData['is_finish'] = 0;
                    return ApiReturn::r(1, $resData, lang('砍价成功'));
                }
            } else {
                $gbWhere[0] = ["assistor_id", "=", $user['user_id']];
                //帮砍一刀接口
                $res = Db::name("goods_bargain_order_list")->where($gbWhere)->find();
                if ($res) {
                    return ApiReturn::r(0, [], lang('您已为朋友砍过一刀了'));
                }
                //查询出砍了多少刀
                $cutCount = Db::name("goods_bargain_order_list")->where(["bargain_order_id" => $bargain_order['id']])->count();
//                halt($cutCount);
                if ($cutCount > 0) {
                    if ($cutCount == $activity_details['least_count']) {
                        return ApiReturn::r(0, [], lang('该商品已砍价成功'));
                    }
                    $cutPrice = Db::name("goods_activity_cut_price")
                        ->where(["activity_goods_id" => $bargain_order['activity_detail_id'], "uid" => $bargain_order['user_id']])
                        ->order("id ASC")->limit($cutCount - 1, 1)
                        ->column("cut_price");
                    $cutPrice = $cutPrice[0];
                    $adUser = User::get($user['user_id']);
                    $saveData = [
                        "bargain_order_id" => $bargain_order['id'],
                        "assistor_id" => $user['user_id'],
                        "nick_name" => $adUser['user_nickname'],
                        "head_img" => $adUser['head_img'],
                        "bargain_money" => $cutPrice,
                        "create_time" => time(),
                    ];
                    Db::name("goods_bargain_order_list")->insertGetId($saveData);
                    $is_finish = 0;

                    if ($cutCount == $activity_details['least_count'] - 1) {
                        $updateData = [
                            "pay_status" => 1,
                            "status" => 1,
                        ];
                        OrderModel::where("order_sn", $bargain_order['order_sn'])->update($updateData);
                        Db::name("goods_bargain_order")->where("id", $bargain_order['id'])->update([
                            "is_addorder" => 1,
                            "update_time" => time(),
                        ]);
                        $is_finish = 1;
                    }
                    if ($is_finish) {
                        $resData['surplus_percentage'] = 100;
                    } else {
                        $bargain_money = Db::name("goods_bargain_order_list")->where("id", $data['bargain_id'])->value("bargain_money");
                        $resData['surplus_percentage'] = bcdiv(bcsub($activity_details['activity_price'], $bargain_money, 2), $activity_details['activity_price'], 2) * 100;
                    }
                    $resData['is_finish'] = $is_finish;
                    $resData['cut_price'] = $cutPrice;
                    return ApiReturn::r(1, $resData, lang('砍价成功'));
                } else {
                    return ApiReturn::r(0, [], lang('砍价异常'));
                }
            }
        } else {
            return ApiReturn::r(0, [], lang('未指定砍价人'));
        }
    }

    /**
     * 砍价商品详情
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @author zhougs
     * @createTime 2020年12月26日15:53:02
     */
    public function getBargainSurplusAmount($data, $user)
    {
        if (!$data['bargain_id']) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $resData = [];
        $info = Db::name("goods_bargain_order")->where("id", $data['bargain_id'])->find();
        if ($info['sku_id']) {
            $resData['key_name'] = Db::name("goods_sku")->where("sku_id", $info['sku_id'])->value("key_name");
        }
        $userInfo = User::field("user_nickname,head_img")->where("id", $info['user_id'])->find();
        $goods = Db::name("goods")->field("name,thumb")->where("id", $info['goods_id'])->find();
        $resData['user_nickname'] = $userInfo['user_nickname'];
        $resData['head_img'] = $userInfo['head_img'];
        $resData['name'] = $goods['name'];
        $resData['goods_id'] = $info['goods_id'];
        $resData['sku_id'] = $info['sku_id'];
        $resData['bargain_id'] = $data['bargain_id'];
        $resData['thumb'] = get_file_url($goods['thumb']);
        $res = ActivityDetails::getBargainSurplusAmount($data['bargain_id']);
        if ($res) {
            $resData['bargain_money'] = $res['bargain_money'];//已砍金额
            $resData['surplus_percentage'] = $res['percentage'];//剩余百分比
        }
        return ApiReturn::r(1, $resData, lang('获取成功'));
    }

    /**
     * 查询砍价单是否砍成
     * @param $data
     * @param $user
     * @return \think\response\Json
     */
    public function getBargainStatus($data, $user)
    {
        if (!$data['bargain_id']) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $is_addorder = Db::name("goods_bargain_order")->where("id", $data['bargain_id'])->value("is_addorder");
        if ($is_addorder) {
            return ApiReturn::r(1, [], lang('已砍成功'));
        } else {
            return ApiReturn::r(0, [], lang('还未砍成'));
        }
    }

}