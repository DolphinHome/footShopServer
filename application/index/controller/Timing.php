<?php

namespace app\index\controller;

use app\common\model\Rank as RankModel;
use app\goods\model\OrderGoods as OrderGoodsModel;

use think\Controller;


/**
 * www.zbphp.com/index/timing
 * www.zbphp.com/index/timing?sales_goods_history=1&ymd20201125 跑入定时任务销量排行榜
 */
class Timing extends Controller
{
    public function index()
    {
        if (input('sales_goods_history')) {
            $ymd = input('ymd', '');
            if (empty($ymd)) {
                return 'ymd'.lang('不能为空');
            }
            $this->salesGoods($ymd);
            return;
        }
        $this->setDailyTasks();
    }

    // 每天凌晨1点跑数据
    public function setDailyTasks()
    {
        if (cache('daily_tasks_timing')) {
            return;
        }
        if (date(H) === '01') {
            cache('daily_tasks_timing', 1, 24*3600);
        }
        return $this->salesGoods();
    }

    /**
     * 统计商品销量
     * @param string $historyYmd 历史哪天 格式：20201130 年月日 $historyYmd = 20201130
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function salesGoods($historyYmd = '')
    {
        if ($historyYmd) {
            // 抛入历史数据
            $startTime = strtotime(date('Y-m-d' . ' 00:00:00', strtotime($historyYmd)));
            $endTime =  strtotime(date('Y-m-d' . ' 23:59:59', strtotime($historyYmd)));
            $lastDataCreateTime = RankModel::where('type', 1)
                ->where('create_time', 'between', [$startTime, $endTime])
                ->order('create_time desc')
                ->limit(1)
                ->value('create_time');
        } else {
            // 判断今天是否添加过
            $lastDataCreateTime = RankModel::where('type', 1)
                ->order('create_time desc')
                ->limit(1)
                ->value('create_time');
            $startTime = strtotime(date('Y-m-d' . ' 00:00:00'));
            $endTime =  strtotime(date('Y-m-d' . ' 23:59:59'));
        }

        if ($lastDataCreateTime >= $startTime) {
            echo date('Y-m-d H:i:s', $lastDataCreateTime) . lang('已经执行过') . PHP_EOL;
            return;
        }
        $where = [];
        $where[] = ['og.order_status', '=', 1]; // 已付款
        $where[] = ['o.pay_time', 'between', [
            $startTime,
            $endTime
            ]];
        $GoodsList = OrderGoodsModel::alias('og')
            ->leftJoin('order o', 'o.order_sn = og.order_sn')
            ->where($where)
            ->field('og.goods_id, o.pay_time, og.num')
            ->select();
        $list = [];
        foreach ($GoodsList as $item) {
            if (isset($list[$item['goods_id']])) {
                $list[$item['goods_id']] += $item['num'];
            } else {
                $list[$item['goods_id']] = $item['num'];
            }
        }

        $insertData = [];
        foreach ($list as $goods_id => $item) {
            $_data = [
                'type' => 1,
                'genre_id' => $goods_id,
                'num' => $item,
                'create_time' => $startTime
            ];
            $insertData[] = $_data;
        }

        // 防止一次失败
        $rankModel = new RankModel();
        for ($i = 0; $i < 5; $i++) {
            $res = $rankModel->saveAll($insertData);
            if ($res) {
                break;
            }
        }
        echo lang('执行成功') . PHP_EOL;
        return;
    }
}