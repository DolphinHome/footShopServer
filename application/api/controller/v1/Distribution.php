<?php
/**
 * Created by PhpStorm.
 * User: I5
 * Date: 2020/12/26
 * Time: 10:04
 */
namespace app\api\controller\v1;

use app\api\controller\Base;
use app\user\model\User;
use service\ApiReturn;
use think\Db;

class Distribution extends Base
{
    /*
     *我的分销
     *
     */
    public function index($data = [], $user = [])
    {
        //账户佣金
        $commission = Db::name("distribution_commission")
            ->where([
                'user_id' => $user['id'],
                'is_settlement' => 1
            ])
            ->sum("money");
        //冻结金额
        $freeze_money = Db::name("distribution_commission")
            ->where([
                'user_id' => $user['id'],
                'is_settlement' => 0
            ])->sum("money");
        $withdraw = Db::name("user_withdraw")
            ->where([
                'user_id' => $user['id'],
                'check_status' => 0
            ])->sum("cash_fee");
        $time = time();

        $sql = " user_id={$user['id']} and {$time}-create_time >=3600*24 and {$time}-create_time <=3600*24*2 ";
        $yesterday_profit = Db::name("distribution_commission")
            ->where($sql)
            ->sum("money");
        //会员余额
        $user_money = User::where([
            'id' => $user['id']
        ])->value("user_money");


        $res = [
            'commission' => $commission,
            'yesterday_profit' => $yesterday_profit,
            'total_profit' => $commission,
            'total_withdrawal' => $withdraw,
            'freeze_money' => $freeze_money,
            'user_money' => $user_money

        ];
        return ApiReturn::r(1, $res, 'ok');
    }

    /*
     *
     * 推广人数统计
     *
     */
    public function extension($data = [], $user = [])
    {
        $user_id = $user['id'];
        //直推人数
        $first_push = Db::name("distribution")->where('pid', $user_id)->count();

        //间推人数
        if ($first_push > 0) {
            $second_push = Db::name("distribution")->where("pid", "in", function ($query) use ($user_id) {
                $query->name("distribution")->where('pid', $user_id)->field("user_id");
            })->count();
        } else {
            $second_push = 0;
        }


        //推广总人数
        $total_push = $first_push + $second_push;
        $data = [
            'first_push' => $first_push,
            'second_push' => $second_push,
            'total_push' => $total_push
        ];

        return ApiReturn::r(1, $data, 'ok');
    }

    /*
     * 推广列表搜索
     *
     */
    public function push_search($data = [], $user = [])
    {
        $keywords = $data['keywords'] ?: '';

        //直推人id
        $first_push = Db::name("distribution")->where([
            'pid' => $user['id']
        ])->column("user_id");
        if (empty($first_push)) {
            $first_push = [-1];
        }
        //间推人id
        $second_push = Db::name("distribution")->where([
            ['pid', 'in', $first_push]
        ])->column("user_id");
        $user_ids = array_merge($first_push, $second_push);
        if (!$user_ids) {
            $user_ids = [-1];
        }
        $where = [];
        $where[] = ['u.id', 'in', $user_ids];

        if (!empty($keywords)) {
            $where[] = ['u.user_nickname', 'like', '%' . $keywords . '%'];
        }
        $sort = isset($data['sort']) ? $data['sort'] : 'desc';
        $list = Db::name("user")
            ->alias("u")
            ->join("distribution d", "u.id=d.user_id", "left")
            ->field("u.id,u.user_nickname,d.create_time")
            ->order("d.create_time " . $sort)
            ->where($where)
            ->paginate()
            ->each(function ($v) use ($first_push, $second_push) {
                $type = '';
                if (in_array($v['id'], $first_push)) {
                    $type = '直推会员';
                }
                if (in_array($v['id'], $second_push)) {
                    $type = '间推会员';
                }
                $v['type'] = $type;
                $v['create_time'] = date('Y-m-d', $v['create_time']);
                $v['user_nickname'] = mb_substr($v['user_nickname'], 0, 10, 'utf-8');  //我们都是
                return $v;
            });
        return ApiReturn::r(1, $list, 'ok');
    }


    /*
     * 推广人佣金列表
     *
     */
    public function push_list($data = [], $user = [])
    {

        //佣金
        $commission = Db::name("distribution_commission")
            ->where([
                'user_id' => $user['id'],
                'is_settlement' => 1
            ])->sum("money");
        //列表
        $list = Db::name("distribution_commission")
            ->where([
                'user_id' => $user['id']
            ])
            ->field("money,create_time")
            ->order("create_time desc")
            ->paginate()
            ->each(function ($v) {
                $v['type'] = '订单佣金';
                $v['settlement_text'] = '未结算';
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                if ($v['is_settlement'] == 1) {
                    $v['settlement_text'] = '已结算';
                }
                return $v;
            });
        $res = [
            'commission' => $commission,
            'list' => $list
        ];
        return ApiReturn::r(1, $res, 'ok');
    }

    /*
     * 推广订单
     *
     */
    public function push_order($data = [], $user = [])
    {
        //佣金
        $commission = Db::name("distribution_commission")
            ->where([
                'user_id' => $user['id'],
                'is_settlement' => 1
            ])->sum("money");
        $keyword = isset($data['keyword']) ? $data['keyword'] : '';
        $where = [];
        $where[] = ['d.user_id', '=', $user['id']];
        if (!empty($keyword)) {
            $where[] = ['d.order_sn', 'like', '%' . $keyword . '%'];
        }
        //列表
        $list = Db::name("distribution_commission")->alias('d')
            ->leftJoin('order o', 'o.order_sn=d.order_sn')
            ->leftJoin('user u', 'o.user_id=u.id')
            ->where($where)
            ->field("sum(d.money) as money,d.order_sn,d.user_id,d.create_user_id,d.create_time,d.is_settlement,o.payable_money as order_money,u.user_nickname as user_name")
            ->group("d.order_sn")
            ->order("d.create_time desc")
            ->paginate()
            ->each(function ($v) {
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $v['user_type'] = '直推会员';
                $v['settlement_text'] = '未结算';
                if ($v['is_settlement'] == 1) {
                    $v['settlement_text'] = '已结算';
                }

                return $v;
            });

        //本月累计订单数

//        $month_start = date('Y-m-01', time());
//        $end = date('Y-m-d H:i:s', time());
//        $sql_month = "
//         `create_time` >= UNIX_TIMESTAMP( '" . $month_start . "' )
//         AND `create_time` <= UNIX_TIMESTAMP('" . $end . "' )
//         and user_id = {$user['id']}
//
//         ";

        $sql_month = "
         user_id = {$user['id']}
         
         ";
        $order_total = Db::name("distribution_commission")
            ->where($sql_month)
            ->group("order_sn")
            ->count();


        $res = [
            'commission' => $commission,
            'list' => $list,
            'order_total' => $order_total
        ];
        return ApiReturn::r(1, $res, 'ok');
    }

    /*
     * 分销商品
     *
     */
    public function goods()
    {
    }
}
