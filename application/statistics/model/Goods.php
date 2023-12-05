<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 曾虎 [ 1427305236@qq.com ]
// +----------------------------------------------------------------------
// | 中犇软件 技术四部 出品
// +----------------------------------------------------------------------

namespace app\statistics\model;

use app\common\model\Order;
use app\goods\model\Goods as GoodsModel;
use think\Db;

/**
 * 商城数据统计模块
 * @author zenghu < 1427305236@qq.com >
 * @since 2020年11月2日16:00:21
 */
class Goods
{
    /*+----------------------------------------------------------------------
      |                      销售数据统计开始                                 |
      +----------------------------------------------------------------------*/
    /**
     * 统计区间内销售额
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年11月2日16:00:21
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getSalePrice($times = '', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            $arr_time = explode('.', $times);
            // 获取时间阶段（日 周 月 季 年）
            if (count($arr_time) > 1) {
                switch ($arr_time[1]) {
                    case 'day': // 昨天
                    case 'd':
                        $intervalType = 8;
                        break;
                    case 'week': // 上周
                    case 'w':
                        $intervalType = 1;
                        break;
                    case 'month': // 上月
                    case 'm':
                        $intervalType = 3;
                        break;

                    case 'quarter': // 上季度
                    case 'q':
                        $intervalType = 5;
                        break;

                    case 'year': // 上年
                    case 'y':
                        $intervalType = 7;
                        break;
                    default:
                        # code...
                        break;
                }
                $timeStr = getIntervalTime(time(), $intervalType);

            } else {
                $timeStr = getTimeConversion($arr_time[0], 'stamp');
            }
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }
        if (!empty($map)) {
            $where .= " AND goods_id " . $map;
            return Order::alias("o")
                ->join("order_goods_list ogl", "o.order_sn = ogl.order_sn", "left")
                ->where($where)
                ->field("IFNULL(SUM(real_money), 0) orderPrice")
                ->find();
        }
        return Order::where($where)->field("IFNULL(SUM(real_money), 0) orderPrice")->find();
    }


    /**
     * 统计区间内订单数
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年11月2日16:00:21
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getOrderNumber($times = '', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            $arr_time = explode('.', $times);
            // 获取时间阶段（日 周 月 季 年）
            if (count($arr_time) > 1) {
                $timeStr = self::getLastTimes($arr_time[1]);
            } else {
                $timeStr = getTimeConversion($arr_time[0], 'stamp');
            }
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }
        if (!empty($map)) {
            $where .= " AND goods_id " . $map;
            return Order::alias("o")
                ->join("order_goods_list ogl", "o.order_sn = ogl.order_sn", "left")
                ->where($where)
                ->field("IFNULL(COUNT(aid), 0) orderCount")
                ->find();
        }
        return Order::where($where)->field("IFNULL(COUNT(aid), 0) orderCount")->find();
    }

    /*
     * 统计区间内老买家数
     *
     */

    public static function getOldUserNumber($times = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            $arr_time = explode('.', $times);
            // 获取时间阶段（日 周 月 季 年）
            if (count($arr_time) > 1) {
                $timeStr = self::getLastTimes($arr_time[1]);
            } else {
                $timeStr = getTimeConversion($arr_time[0], 'stamp');
            }
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }

        return Order::where($where)->field("IFNULL(COUNT(aid), 0) userCount")->group('user_id')->find();
    }

    public static function getLastTimes($type = '')
    {
        switch ($type) {
            case 'day': // 昨天
            case 'd':
                $intervalType = 8;
                break;
            case 'week': // 上周
            case 'w':
                $intervalType = 1;
                break;
            case 'month': // 上月
            case 'm':
                $intervalType = 3;
                break;

            case 'quarter': // 上季度
            case 'q':
                $intervalType = 5;
                break;

            case 'year': // 上年
            case 'y':
                $intervalType = 7;
                break;
            default:
                # code...
                break;
        }
        return $timeStr = getIntervalTime(time(), $intervalType);
    }

    /**
     * 统计区间内盈利额
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年11月2日16:00:21
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getProfit($times = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            $arr_time = explode('.', $times);
            // 获取时间阶段（日 周 月 季 年）
            if (count($arr_time) > 1) {
                $timeStr = self::getLastTimes($arr_time[1]);
            } else {
                $timeStr = getTimeConversion($arr_time[0], 'stamp');
            }
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }

        return Order::where($where)->field("IFNULL(SUM((payable_money-cost_price_total)), 0) profitPrice")->find();
    }


    /**
     * 统计区间内成本
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2021年6月23日
     */
    public static function getCostPrice($times = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            $arr_time = explode('.', $times);
            // 获取时间阶段（日 周 月 季 年）
            if (count($arr_time) > 1) {
                $timeStr = self::getLastTimes($arr_time[1]);
            } else {
                $timeStr = getTimeConversion($arr_time[0], 'stamp');
            }
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }

        return Order::where($where)->field("IFNULL(SUM((cost_price_total)), 0) costPrice")->find();
    }
    /*+----------------------------------------------------------------------
      |                      销售数据统计结束                                 |
      +----------------------------------------------------------------------*/


    /*+----------------------------------------------------------------------
      |                      待处理数据统计开始                               |
      +----------------------------------------------------------------------*/
    /**
     * 待处理预警数据统计--待付款、待发货
     * @param $status 所取状态标识
     * @return array
     * @since 2020年11月3日08:43:33
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getPendingData($status = '')
    {
        // 定义查询的状态
        $statusType = array(
            'paying' => '0', // 待付款
            'delivering' => '1' // 待发货
        );

        // 定义搜索的条件
        $where = [];
        $where['is_delete'] = 0;
        if (!empty($status) && in_array($status, array_keys($statusType))) {
            $where['status'] = $statusType[$status];
        }

        return Order::where($where)->field("IFNULL(COUNT(aid), 0) orderCounte")->find();
    }

    /**
     * 待处理预警数据统计--售后订单数
     * @return array
     * @since 2020年11月3日08:43:33
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getAfterSaleCount()
    {
        // 查询售后申请中订单数
        return Order::alias('o')
            ->join('order_refund or', 'o.order_sn = or.order_sn', 'RIGHT')
            ->field("IFNULL(COUNT(o.aid), 0) orderCounte")
            ->where(['or.status' => 0])
            ->find();
    }

    /**
     * 待处理预警数据统计--库存预警
     * @param $inventory 预警数量规则
     * @return array
     * @since 2020年11月3日08:43:33
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getInventoryEarlyWarning($inventory = 50)
    {
        // 查询商品
        $goodsList = GoodsModel::where(['is_sale' => 1, 'status' => 1, 'is_delete' => 0])->select();

        // 处理商品库存
        $goodsIds = []; // 定义库存预警商品ID
        $goodsIdsSku = ""; // 定义预警商品SKUID
        foreach ($goodsList as $val) {
            $goodsSku = Db::name('goods_sku')->where(['status' => 1, 'goods_id' => $val['id']])->find();
            if (empty($goodsSku)) {
                if ($val['stock'] < $inventory) {
                    array_push($goodsIds, $val['id']);
                }
            } else {
                $goodsIdsSku .= $val['id'] . ',';
            }
        }

        // 处理SKU库存
        $goodsIdsSku = rtrim($goodsIdsSku, ',');
        $goodsIdsSkuCount = count(explode(',', $goodsIdsSku));
        $skuList = [];
        if ($goodsIdsSkuCount > 0) {
            $goodsSkuList = Db::name('goods_sku')->where("goods_id IN('$goodsIdsSku')")->select();
            foreach ($goodsSkuList as $val) {
                if ($val['stock'] < $inventory) {
                    array_push($skuList, $val['sku_id']);
                    array_push($goodsIds, $val['goods_id']);
                }
            }
        }

        return [
            'goods_inventory_early_warning' => count($goodsIds),
            'inventory_early_warning_goods_ids' => array_unique($goodsIds),
            'inventory_early_warning_sku_ids' => $skuList,
        ];
    }

    /*+----------------------------------------------------------------------
      |                      待处理数据统计结束                               |
      +----------------------------------------------------------------------*/

    /**
     * 订单数据统计
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年11月3日13:45:37
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getOrderData($times = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }

        // 根据时间节点组装需要查询的数据
        $group = '';
        $field = '';
        switch ($times) {
            case 'day': // 日
                $field = ',FROM_UNIXTIME(pay_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%H\') ORDER BY pay_time ASC';
                break;

            case 'week': // 周
                $field = ',FROM_UNIXTIME(pay_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%w\') ORDER BY pay_time ASC';
                break;

            case 'month': // 月
                $field = ',FROM_UNIXTIME(pay_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%d\') ORDER BY pay_time ASC';
                break;

            case 'quarter': // 季
                // $field = ',CONCAT(FROM_UNIXTIME(pay_time,"%Y "), "第", QUARTER(FROM_UNIXTIME(pay_time,"%Y-%m-%d")),"季度") dateFormat';
                // $group = 'GROUP BY QUARTER(FROM_UNIXTIME(pay_time,"%Y-%m-%d")) ORDER BY pay_time ASC LIMIT 4';
                $field = ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\') ORDER BY pay_time ASC';
                break;

            case 'year': // 年
                $field = ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\') ORDER BY pay_time ASC';
                break;
            case 'lastyear': // 上一年
                $field = ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\') ORDER BY pay_time ASC';
                break;
        }

        return Db::query("
            SELECT 
                IFNULL(COUNT(aid), 0) orderCount,
                IFNULL(SUM(real_money), 0) orderPrice, 
                IFNULL(SUM((real_money-cost_price_total)), 0) profitPrice
                {$field}
            FROM lb_order 
            WHERE $where
            $group
        ");
    }

    /**
     * 消费区域统计
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年11月3日14:51:39
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getConsumptionArea($times = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " o.status > 0"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (o.pay_time >= {$timeStr['startTime']} AND o.pay_time <= {$timeStr['endTime']}) AND ogi.province <> '' ";
        }

        // 根据区域统计消费额
        return Db::query("
            SELECT 
                ogi.province,
                IFNULL(SUM(real_money), 0) orderPrice
            FROM lb_order o 
            RIGHT JOIN lb_order_goods_info ogi ON o.order_sn = ogi.order_sn
            WHERE 
                $where
            GROUP BY ogi.province
            ORDER BY orderPrice DESC
            LIMIT 5
        ");
    }

    /**
     * 单品销售榜单
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年11月3日15:51:32
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getGoodsSalesList($times = '', $page = '', $param = [])
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " g.is_sale = 1 AND g.status = 1 AND g.is_delete = 0 AND o.status > 0"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (o.pay_time >= {$timeStr['startTime']} AND o.pay_time <= {$timeStr['endTime']}) ";
        }
        //搜索条件
        if (isset($param['name']) && !empty($param['name'])) {
            $name = $param['name'];
            $where .= " and g.name like '%{$name}%' ";
        }
        if (isset($param['sn']) && !empty($param['sn'])) {
            $sn = $param['sn'];
            $where .= " and g.sn = '{$sn}' ";
        }
        if ($page) {
            $res = GoodsModel::alias('g')
                ->field("g.id gid,g.name,g.sales_sum,g.sn")
                ->where($where)
                ->join('order_goods_list ogl', 'ogl.goods_id = g.id', 'LEFT')
                ->join('order o', 'o.order_sn = ogl.order_sn', 'RIGHT')
                ->group('g.id')
                ->order('sales_sum desc')
                ->limit(15)
                ->paginate();
            return $res;
        }
        $res = GoodsModel::alias('g')
            ->field("g.id gid,g.name,g.sales_sum,g.sn")
            ->where($where)
            ->join('order_goods_list ogl', 'ogl.goods_id = g.id', 'LEFT')
            ->join('order o', 'o.order_sn = ogl.order_sn', 'RIGHT')
            ->group('g.id')
            ->order('sales_sum desc')
            ->limit(8)
            ->select();
        return $res;
    }

    /**
     * 单品计入购物车榜单
     * @return array
     * @since 2020年11月3日15:51:32
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getGoodsCartList($page = '', $param = [])
    {

        //搜索条件
        $where = [];
        if (isset($param['goods_name']) && !empty($param['goods_name'])) {
            $where[] = ['gc.goods_name', 'like', '%' . $param['goods_name'] . '%'];
        }
        if (isset($param['sn']) && !empty($param['sn'])) {
            $where[] = ['g.sn', '=', $param['sn']];
        }
        if ($page) {
            $res = GoodsModel::alias('g')
                ->field("gc.goods_name,IFNULL(COUNT(g.id), 0) goodsCount,g.id gid,g.sales_sum,g.sn")
                ->join('goods_cart gc', 'gc.goods_id = g.id', 'RIGHT')
                ->group('g.id')
                ->where($where)
                ->order('goodsCount desc')
                ->limit(15)
                ->paginate();
            return $res;

        }

        return GoodsModel::alias('g')
            ->field("gc.goods_name,IFNULL(COUNT(g.id), 0) goodsCount,g.id gid,g.sales_sum,g.sn")
            ->join('goods_cart gc', 'gc.goods_id = g.id', 'RIGHT')
            ->group('g.id')
            ->where($where)
            ->order('goodsCount desc')
            ->limit(8)
            ->select();
    }

    /**
     * 统计区间内支付订单人数
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年11月12日11:46:08
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getOrderPayUser($times = '', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            $arr_time = explode('.', $times);
            // 获取时间阶段（日 周 月 季 年）
            if (count($arr_time) > 1) {
                $timeStr = self::getLastTimes($arr_time[1]);
            } else {
                $timeStr = getTimeConversion($arr_time[0], 'stamp');
            }
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态

        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }
        if (!empty($map)) {
            $where .= " AND goods_id " . $map;
            return Order::alias("o")
                ->join("order_goods_list ogl", "o.order_sn = ogl.order_sn", "left")
                ->where($where)
                ->field("IFNULL(COUNT(DISTINCT(user_id)), 0) orderPayCount")
                ->find();
        }
        return Order::where($where)->field("IFNULL(COUNT(DISTINCT(user_id)), 0) orderPayCount")->find();
    }

    /**
     * 支付订单人数据列表
     * @param $times 所取时间阶段标识
     * @param $dimension 数据维度(MAX：优秀值；AVG：平均值；COUNT：总数)，默认COUNT
     * @return array
     * @since 2020年11月12日15:03:02
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getOrderPayUserList($times = '', $dimension = 'COUNT', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($map)) {
            $where .= " AND goods_id" . $map;
        }
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(pay_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(pay_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(pay_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_order o
            left join lb_order_goods_list ogl
            on o.order_sn = ogl.order_sn
            WHERE $where
            $group
        ";
//        halt($sql);
//        halt(Db::query($sql));
        // 获取时间段内每时间端总数
        if ($dimension == 'COUNT') {
            return Db::query($sql);
        }

        // 根据不同的数据统计维度查询不同的值
        $fields = '';
        switch ($dimension) {
            case 'AVG': // 平均值
                $fields = 'IFNULL(AVG(a.browseCount), 0) browseCount';
                break;

            case 'MAX': // 优秀值
                $fields = 'IFNULL(MAX(a.browseCount), 0) browseCount';
                break;
        }

        return Db::query("
            SELECT 
                {$fields}
            FROM(
                $sql
            ) a
        ");
    }

    /**
     * 统计区间内订单数数据列表
     * @param $times 所取时间阶段标识
     * @param $dimension 数据维度(MAX：优秀值；AVG：平均值；COUNT：总数)，默认COUNT
     * @return array
     * @since 2020年11月13日11:16:54
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getOrderNumberList($times = '', $dimension = 'COUNT', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($map)) {
            $where .= " AND goods_id" . $map;
        }
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(Distinct(aid)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(pay_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(pay_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(pay_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_order o
            left join lb_order_goods_list ogl
            on o.order_sn = ogl.order_sn
            WHERE $where
            $group
        ";

        // 获取时间段内每时间端总数
        if ($dimension == 'COUNT') {
            return Db::query($sql);
        }

        // 根据不同的数据统计维度查询不同的值
        $fields = '';
        switch ($dimension) {
            case 'AVG': // 平均值
                $fields = 'IFNULL(AVG(a.browseCount), 0) browseCount';
                break;

            case 'MAX': // 优秀值
                $fields = 'IFNULL(MAX(a.browseCount), 0) browseCount';
                break;
        }

        return Db::query("
            SELECT 
                {$fields}
            FROM(
                $sql
            ) a
        ");
    }


    /**
     * 统计区间内订单数数据列表
     * @param $times 所取时间阶段标识
     * @param $dimension 数据维度(MAX：优秀值；AVG：平均值；COUNT：总数)，默认COUNT
     * @return array
     * @since 2020年11月13日11:16:54
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getUserNumberList($times = '', $dimension = 'COUNT')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(aid), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(pay_time,\'%H\') dateFormat';
//                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(pay_time,\'%w\') dateFormat';
//                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(pay_time,\'%d\') dateFormat';
//                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
//                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
//                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_order
            WHERE {$where}
            GROUP BY user_id
        ";

        // 获取时间段内每时间端总数
        if ($dimension == 'COUNT') {
            return Db::query($sql);
        }

        // 根据不同的数据统计维度查询不同的值
        $fields = '';
        switch ($dimension) {
            case 'AVG': // 平均值
                $fields = 'IFNULL(AVG(a.browseCount), 0) browseCount';
                break;

            case 'MAX': // 优秀值
                $fields = 'IFNULL(MAX(a.browseCount), 0) browseCount';
                break;
        }

        return Db::query("
            SELECT 
                {$fields}
            FROM(
                $sql
            ) a
        ");
    }

    public static function getProfitList($times = '', $dimension = 'COUNT', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($map)) {
            $where .= " AND goods_id" . $map;
        }
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
//        $field = 'IFNULL(SUM((payable_money-cost_price_total)), 0) browseCount';
        $field = ' (payable_money-cost_price_total) as profit_money ';

        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(pay_time,\'%H\') dateFormat,pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(pay_time,\'%w\') dateFormat,pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(pay_time,\'%d\') dateFormat,pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat,pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat,pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            select IFNULL(SUM(profit_money), 0) browseCount,dateFormat
            from (
            SELECT
                {$field}
            FROM lb_order o
            left join lb_order_goods_list ogl
            on o.order_sn = ogl.order_sn
            WHERE $where
            group by o.order_sn
            ) b 
            $group
        ";
        // 获取时间段内每时间端总数
        if ($dimension == 'COUNT') {
            return Db::query($sql);
        }

        // 根据不同的数据统计维度查询不同的值
        $fields = '';
        switch ($dimension) {
            case 'AVG': // 平均值
                $fields = 'IFNULL(AVG(a.browseCount), 0) browseCount';
                break;

            case 'MAX': // 优秀值
                $fields = 'IFNULL(MAX(a.browseCount), 0) browseCount';
                break;
        }

        return Db::query("
            SELECT 
                {$fields}
            FROM(
                $sql
            ) a
        ");
    }


    /**
     * 统计区间内销售额列表
     * @param string $times 所取时间阶段标识
     * @param string $dimension 数据维度(MAX：优秀值；AVG：平均值；COUNT：总数)，默认COUNT
     * @return array
     * @since 2020年11月13日15:59:32
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getSalePriceList($times = '', $dimension = 'COUNT', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态
        if (!empty($map)) {
            $where .= " AND goods_id" . $map;
        }
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = ' payable_money ';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(pay_time,\'%H\') dateFormat , pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(pay_time,\'%w\') dateFormat , pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(pay_time,\'%d\') dateFormat , pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat ,pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat ,pay_time';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            select IFNULL(SUM(payable_money), 0) browseCount,dateFormat
            from (
            SELECT
                {$field}
            FROM lb_order o
            left join lb_order_goods_list ogl
            on o.order_sn = ogl.order_sn
            WHERE $where
            group by o.order_sn
            ) b 
            $group
        ";
        // 获取时间段内每时间端总数
        if ($dimension == 'COUNT') {
            return Db::query($sql);
        }

        // 根据不同的数据统计维度查询不同的值
        $fields = '';
        switch ($dimension) {
            case 'AVG': // 平均值
                $fields = 'IFNULL(AVG(a.browseCount), 0) browseCount';
                break;

            case 'MAX': // 优秀值
                $fields = 'IFNULL(MAX(a.browseCount), 0) browseCount';
                break;
        }

        return Db::query("
            SELECT 
                {$fields}
            FROM(
                $sql
            ) a
        ");
    }

    /*
     * 交易数据-实时统计
     *
     *
     */
    public static function realTimeData($times = 'year')
    {

        switch ($times) {
            //天
            case 'day':
                $start = date('Y-m-d 00:00:00');
                $end = date('Y-m-d H:i:s');
                $where = " pay_status =1 and pay_time >= unix_timestamp( '$start' ) AND `pay_time` <= unix_timestamp( '$end' ) ";
                break;
            case 'week':
                //周
                $where = " pay_status =1 and YEARWEEK( FROM_UNIXTIME( `pay_time`, '%Y-%m-%d %H:%i:%s' ) ,1) = YEARWEEK( now( ),1 ) ";
                break;
            //月
            case 'month':
                $start = date('Y-m-01 00:00:00');
                $end = date('Y-m-d H:i:s');
                $where = " pay_status =1 and `pay_time` >= unix_timestamp('”.$start.”') AND `pay_time` <= unix_timestamp('$end') ";
                break;
            //季度
            case 'quarter':
                $where = " pay_status =1 and quarter( FROM_UNIXTIME( `pay_time` ) ) = quarter( curdate( )) ";
                break;
            //年
            default:
                $start = date('Y-01-01 00:00:00');
                $end = date('Y-m-d H:i:s');
                $where = " pay_status =1 and `pay_time` >= unix_timestamp( '$start' ) AND `pay_time` <= unix_timestamp( '$end' ) ";
                break;
        }
        //支付金额
        $payAmount = Order::where($where)->field('sum(payable_money) as payAmount')->find()['payAmount'] ?: 0;
        //支付买家数
        $payUserNum = Order::where($where)->field('count(aid) as payUserNum')->group('user_id')->find()['payUserNum'] ?: 0;
        //支付订单数
        $payOrderNum = Order::where($where)->field('count(aid) as payOrderNum')->find()['payOrderNum'];

        //支付金额折线图
        $today_date = strtotime(date('Y-m-d', time()));
        $yesterday_date = strtotime('yesterday');
        $payAmountList = [];
        for ($i = 1; $i <= 24; $i++) {
            $todayCount = Order::where([
                ['status', '=', 1],
                ['create_time', '>=', $today_date + $i * 60 * 60]
            ])->field('count(payable_money) as todayCount')
                ->find()['todayCount'];
            $yesterdayCount = Order::where([
                ['status', '=', 1],
                ['create_time', '>=', $yesterday_date + $i * 60 * 60]
            ])->field('count(payable_money) as yesterdayCount')
                ->find()['yesterdayCount'];
            $payAmountList[] = [
                'dateFormat' => $i . '时',
                'yesterdayCount' => $yesterdayCount,
                'todayCount' => $todayCount
            ];
        }
        return [
            'payAmount' => $payAmount,
            'payUserNum' => $payUserNum,
            'payOrderNum' => $payOrderNum,
            'payAmountList' => $payAmountList
        ];
    }

    /*
     * 经营总览-商品数据成交商品件数
     *
     *
     */
    public static function getPayGoods($times = 'year')
    {
        switch ($times) {
            //天
            case 'day':
                $start = date('Y-m-d 00:00:00');
                $end = date('Y-m-d H:i:s');
                $where = " o.pay_status =1  and o.pay_time >= unix_timestamp( '$start' ) AND o.pay_time <= unix_timestamp( '$end' ) ";
                break;
            case 'week':
                //周
                $where = " o.pay_status =1 and YEARWEEK( FROM_UNIXTIME( o.pay_time, '%Y-%m-%d %H:%i:%s' ) ,1) = YEARWEEK( now( ),1 ) ";
                break;
            //月
            case 'month':
                $start = date('Y-m-01 00:00:00');
                $end = date('Y-m-d H:i:s');
                $where = " o.pay_status =1  and o.pay_time >= unix_timestamp('”.$start.”') AND o.pay_time <= unix_timestamp('$end') ";
                break;
            //季度
            case 'quarter':
                $where = " o.pay_status =1 and quarter( FROM_UNIXTIME( o.pay_time ) ) = quarter( curdate( ))";
                break;
            //年
            default:
                $start = date('Y-01-01 00:00:00');
                $end = date('Y-m-d H:i:s');
                $where = " o.pay_status =1 and o.pay_time >= unix_timestamp( '$start' ) AND o.pay_time <= unix_timestamp( '$end' ) ";
                break;
        }
        //成交商品件数
        return Db::name("order_goods_list")
            ->alias("og")
            ->join('order o', 'og.order_sn=o.order_sn', 'LEFT')
            ->where($where)
//            ->fetchSql(true)
            ->count('og.num');


    }

    /*
     * 商城数据-商品明细
     *
     */
    public static function getGoodsList($map, $where, $order = 'id desc')
    {


        $list = GoodsModel::where($map)
            ->field('id,name,sn')
//            ->fetchSql(true)
            ->order($order)
            ->paginate();
        foreach ($list as &$v) {
            //商品浏览量

            $v['goodsView'] = Db::name('user_collection')
                ->field('IFNULL(count(aid), 0) browseCount')
                ->where([
                    ['status', '=', 1],
                    ['type', '=', 3],
                    ['collect_id', '=', $v['id']],
                    ['create_time', '>=', $where['startTime']],
                    ['create_time', '<=', $where['endTime']]
                ])
//                ->fetchSql(true)
                ->find()['browseCount'];
            //支付件数
            $v['goodsPayNum'] = Db::name("order_goods_list")
                ->alias('og')
                ->join('order o', 'og.order_sn=o.order_sn', 'left')
                ->where([
                    ['og.goods_id', '=', $v['id']],
                    ['o.pay_time', '>=', $where['startTime']],
                    ['o.pay_time', '<=', $where['endTime']],
                    ['og.order_status', '=', 1]
                ])
                ->count('og.num');
            //支付买家数
            $v['goodsPayUserNum'] = Db::name("order")
                ->alias('o')
                ->join('order_goods_list og', 'og.order_sn=o.order_sn', 'left')
                ->where([
                    ['o.status', '=', 1],
                    ['o.pay_time', '>=', $where['startTime']],
                    ['o.pay_time', '<=', $where['endTime']],
                    ['og.goods_id', '=', $v['id']]
                ])
                ->group('o.user_id')
                ->count('o.aid');
            //支付金额
            $v['goodsPaytAmount'] = Db::name("order")
                ->alias('o')
                ->join('order_goods_list og', 'og.order_sn=o.order_sn', 'left')
                ->where([
                    ['o.status', '=', 1],
                    ['o.pay_time', '>=', $where['startTime']],
                    ['o.pay_time', '<=', $where['endTime']],
                    ['og.goods_id', '=', $v['id']]
                ])
                ->sum('o.payable_money');
            //支付转化率
            $pay = Db::name("order")
                ->alias("o")
                ->join('order_goods_list og', 'og.order_sn=o.order_sn', 'left')
                ->where([
                    ['o.status', '=', 1],
                    ['o.pay_time', '>=', $where['startTime']],
                    ['o.pay_time', '<=', $where['endTime']],
                    ['og.goods_id', '=', $v['id']]
                ])
                ->group('o.aid')
                ->count('o.aid');
            $all = Db::name("order")
                ->alias("o")
                ->join('order_goods_list og', 'og.order_sn=o.order_sn', 'left')
                ->where([
                    ['o.pay_time', '>=', $where['startTime']],
                    ['o.pay_time', '<=', $where['endTime']],
                    ['og.goods_id', '=', $v['id']]
                ])
                ->group('o.aid')
                ->count('o.aid');
            $v['goodsPayPercent'] = $all ? round($pay / $all, 2) * 100 . '%' : 0;

            //商品收藏用户数
            $v['goodsCollectUserNum'] = Db::name('user_collection')
                ->field('IFNULL(count(aid), 0) browseCount')
                ->where(['status' => 1, 'type' => 3])
                ->where([
                    ['collect_id', '=', $v['id']],
                    ['create_time', '>=', $where['startTime']],
                    ['create_time', '<=', $where['endTime']]
                ])
                ->group("user_id")
                ->find()['browseCount'];
            //流量损失指数
            $v['goodsFlowLoss'] = $all ? round($pay / $all, 2) * 100 . '%' : 0;
        }

        return $list;
    }

    /*
     *
     *商品榜单
     *
     */
    public static function getGoodsTopList($times = 'year')
    {
        $viewTopList = self::visitorTop($times);
        $payTopList = self::payTopList($times);
        $res = [
            'viewTopList' => $viewTopList,
            'payTopList' => $payTopList
        ];
        return $res;
    }

    /*
     *
     * 访客榜单
     *
     */
    public static function visitorTop($times = 'year')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = ""; //
        if (!empty($timeStr)) {
            $where = " (u.create_time >= {$timeStr['startTime']} AND u.create_time <= {$timeStr['endTime']}) ";
        }
        $collect_id = Db::name("user_collection")
            ->where([
                'status' => 1,
                'type' => 3
            ])
            ->group("collect_id")
            ->column("collect_id");
        //访客榜单
        $data = $viewTopList = [];
        if ($collect_id) {
            foreach ($collect_id as $k => $v) {
                $data[] = Db::name('user_collection')
                    ->alias('u')
                    ->field('IFNULL(COUNT(DISTINCT(u.user_id)), 0) viewNum,g.name as goodsName')
                    ->join("goods g", "u.collect_id=g.id", 'left')
                    ->where(['u.status' => 1, 'u.type' => 3, 'u.collect_id' => $v])
                    ->where($where)
                    ->find();
            }
        }
        arsort($data);
        $i = 1;
        foreach ($data as $k => $v) {
            if ($i <= 10 && !empty($v['goodsName'])) {
                $viewTopList[] = [
                    'ranking' => $i,
                    'goodsName' => $v['goodsName'],
                    'viewNum' => $v['viewNum']
                ];
                $i++;
            }
        }
        return $viewTopList;

    }

    /*
     * 商城数据支付榜单
     *
     */
    public static function payTopList($times = 'year')
    {

        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = ""; //
        if (!empty($timeStr)) {
            $where = " (o.create_time >= {$timeStr['startTime']} AND o.create_time <= {$timeStr['endTime']}) ";
        }

        //支付榜单
        $goods_id = Db::name("order_goods_list")
            ->alias('og')
            ->where([
                'og.order_status' => 1,
            ])
            ->where($where)
            ->join("order o", 'o.order_sn=og.order_sn', 'left')
            ->column("og.goods_id");
        $payTopList = [];
        if ($goods_id) {
            $goods_id = array_count_values($goods_id);
            arsort($goods_id);
            $i = 1;
            foreach ($goods_id as $k => $v) {
                if ($i <= 10 && count($goods_id) >= $i) {
                    $payTopList[] = [
                        'ranking' => $i,
                        'goodsName' => Db::name("goods")->where(['id' => $k])->value('name'),
                        'payNum' => $v
                    ];
                    $i++;
                }
            }
        }
        return $payTopList;
    }

    /**
     * 获取商品数量
     * @param $times 所取时间阶段标识
     * @param $cids  所属分类ID
     * @return array
     * @since 2020年12月5日17:19:41
     * @author zhougs
     */
    public static function getGoodsNum($time = "year", $map)
    {

        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }
        // 定义查询的条件
        $where = "1=1"; //
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }
        if (!empty($map)) {
            $where .= " AND id " . $map;
        }
        return GoodsModel::where($where)->count() ?? 0;

    }
    /**
     * 商品成交件数
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年12月5日17:19:41
     * @author zhougs
     */
    /*    public static function getGoodsDealNumber($times = '')
        {
            // 定义时间区间数组
            $timeStr = [];

            if (!empty($times)) {
                // 获取时间阶段（日 周 月 季 年）
                $timeStr = getTimeConversion($times, 'stamp');
            }

            // 定义查询的条件
            $where = "o.status > 2"; // 成功的订单状态
            if (!empty($timeStr)) {
                $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
            }
            $goods_transactions = Db::name('order as o')->join('order_goods_list g', 'g.order_sn=o.order_sn', 'left')
                ->where($where)
                ->sum('g.num');

            return $goods_transactions;
        }*/

    /**
     * 商品成交件数列表
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年12月5日17:19:41
     * @author zhougs
     */
    public static function getGoodsDealNumberData($times = '', $dimension = 'COUNT', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " status > 2"; // 成功的订单状态
        if (!empty($map)) {
            $where .= " AND goods_id" . $map;
        }
        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }
        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(DISTINCT(num)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(pay_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(pay_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(pay_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(pay_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_order o
            left join lb_order_goods_list ogl
            on o.order_sn = ogl.order_sn
            WHERE $where
            $group
        ";
        // 获取时间段内每时间端总数
        if ($dimension == 'COUNT') {
            return Db::query($sql);
        }
    }

    /**
     * 统计区间内商品成交数
     * @param $times 所取时间阶段标识
     * @return array
     * @since 2020年11月12日11:46:08
     * @author zenghu < 1427305236@qq.com >
     */
    public static function getGoodsDealNumber($times = 'year', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " status > 0"; // 成功的订单状态

        if (!empty($timeStr)) {
            $where .= " AND (pay_time >= {$timeStr['startTime']} AND pay_time <= {$timeStr['endTime']}) ";
        }
        if (!empty($map)) {
            $where .= " AND goods_id " . $map;
        }
        return Order::alias("o")
            ->join("order_goods_list ogl", "o.order_sn = ogl.order_sn", "left")
            ->where($where)
            ->field("IFNULL(SUM(num), 0) goodsDealNum")
            ->find();
    }
}
