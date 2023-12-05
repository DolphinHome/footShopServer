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
use app\goods\model\Cart;
use app\goods\model\Goods as GD;
use app\goods\model\GoodsComment;
use app\goods\model\GoodsSku;
use app\goods\model\OrderRefund;
use app\user\model\Collection;
use app\user\model\MoneyLog;
use app\user\model\User;
use think\Db;
use app\statistics\model\Goods as goodsModel;

/**
 * 客户数据统计
 * @author zenghu < 1427305236@qq.com >
 * @since 2020年11月2日19:19:16
 */
class Users
{
    /**
     * 统计区间内新增用户
     * @param $times 所取时间阶段标识
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月2日19:21:03
     * @return array
     */
    public static function getNewUsers($times = '')
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
        $where = " 1 = 1"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        return User::where($where)->field("IFNULL(COUNT(id), 0) userCount")->find();
    }

    /**
     * 待处理预警数据统计 -- 提现申请
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月3日08:43:33
     * @return array
     */
    public static function getWithdrawalApply()
    {
        return Db::name('user_withdraw')->field("IFNULL(COUNT(id), 0) withdrawalCount")->where(['check_status' => 0])->find();
    }

    /**
     * 待处理预警数据统计 -- 用户咨询人数
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月3日08:43:33
     * @return array
     */
    public static function getUserConsultation()
    {
        return Db::name('operation_service_data')->field("is_talking userConsultationCount")->find();
    }

    /**
     * 会员来源渠道分布统计
     * @author zenghu < 1427305236@qq.com >
     * @link /statistics/model/Users/getUserSource
     * @since 2020年11月7日09:41:38
     * @return array
     */
    public static function getUserSource($times = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " 1 = 1"; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (u.create_time >= {$timeStr['startTime']} AND u.create_time <= {$timeStr['endTime']}) ";
        }

        return Db::query("
            SELECT 
                ui.user_source,
                IFNULL(COUNT('ui.user_id'), 0) source_count
            FROM lb_user u
            LEFT JOIN lb_user_info ui ON ui.user_id = u.id
            WHERE $where
            GROUP BY ui.user_source
            -- ORDER BY source_count DESC
        ");
    }

    /**
     * 会员区域分布统计
     * @author zenghu < 1427305236@qq.com >
     * @link /statistics/model/Users/getUserRegion
     * @since 2020年11月7日10:07:46
     * @return array
     */
    public static function getUserRegion($times = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " ua.is_default = 1 "; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= " AND (u.create_time >= {$timeStr['startTime']} AND u.create_time <= {$timeStr['endTime']}) ";
        }
        return Db::name('user_address')
            ->alias("ua")
            ->join("user u", 'ua.user_id=u.id', 'left')
            ->where($where)
            ->field("ua.province,IFNULL(COUNT('ua.user_id'), 0) user_count")
            ->group('ua.province')
            ->order('user_count DESC')
            ->limit(5)
            ->select();
    }

    /**
     * 会员消费排行
     * @author zenghu < 1427305236@qq.com >
     * @link /statistics/model/Users/getUserRegion
     * @since 2020年11月7日10:07:46
     * @return array
     */
    public static function getUserConsumptionList($times = '', $page = '', $param = [])
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
            $where .= " AND (o.pay_time >= {$timeStr['startTime']} AND o.pay_time <= {$timeStr['endTime']})";
        }
        //搜索条件
        if (isset($param['user_nickname'])) {
            $user_nickname = $param['user_nickname'];
            $where .= " and u.user_nickname like '%{$user_nickname}%' "; // 成功的订单状态

        }
        //IFNULL(u.user_name, user_nickname) user_name,
        if ($page) {
            $res = User::alias('u')
                ->field("
                    u.id user_id,
                    user_nickname user_name,
                    u.user_type,
                    u.mobile,
                    IFNULL(SUM(o.real_money), '0.00') user_consumption_price
                ")
                ->where($where)
                ->join('order o', 'o.user_id = u.id', 'LEFT')
                ->group('u.id')
                ->limit(15)
                ->paginate()
                ->each(function ($v) {
                    $v['user_name'] = mb_substr($v['user_name'], 0, 10, 'utf-8');  //我们都是中国人
                    return $v;
                });
            return $res;


        } else {
            return User::alias('u')
                ->field("
                    u.id user_id,
                    user_nickname user_name,
                    u.user_type,
                    u.mobile,
                    IFNULL(SUM(o.real_money), '0.00') user_consumption_price
                ")
                ->where($where)
                ->join('order o', 'o.user_id = u.id', 'LEFT')
                ->group('u.id')
                // ->order('user_consumption_price DESC')
                // ->limit(8)
                ->select()
                ->each(function ($v) {
                    $v['user_name'] = mb_substr($v['user_name'], 0, 10, 'utf-8');  //我们都是中国人
                    return $v;
                })
                ->toArray();
//             echo User::getLastSql();die;
        }

    }


    /**
     * 会员
     * @author zenghu < 1427305236@qq.com >
     * @link /statistics/model/Users/getUserRegion
     * @since 2020年11月7日10:07:46
     * @return array
     */
    public static function getUserList($times = '', $page = '', $param = [])
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " u.is_delete = 0";
        if (!empty($timeStr)) {
            $where .= " AND (u.create_time >= {$timeStr['startTime']} AND u.create_time <= {$timeStr['endTime']})";
        }
        //搜索条件
        if (isset($param['user_nickname'])) {
            $user_nickname = $param['user_nickname'];
            $where .= " and u.user_nickname like '%{$user_nickname}%' ";
        }
        if ($page) {
            $res = User::alias('u')
                ->field("
                    u.id user_id,
                    user_nickname user_name,
                    u.user_type,
                    u.mobile,
                    u.create_time,
                    o.user_source
                ")
                ->where($where)
                ->join('user_info o', 'o.user_id = u.id', 'LEFT')
                ->group('u.id')
                ->limit(15)
                ->paginate();
            return $res;
        } else {
            return User::alias('u')
                ->field("
                    u.id user_id,
                    user_nickname user_name,
                    u.user_type,
                    u.mobile,
                    u.create_time,
                    o.user_source
                ")
                ->where($where)
                ->join('user_info o', 'o.user_id = u.id', 'LEFT')
                ->group('u.id')
                // ->order('user_consumption_price DESC')
                // ->limit(8)
                ->select()
                ->toArray();
        }

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
     * 商品访客数
     * @param $times 所取时间阶段标识
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月12日10:22:44
     * @return array
     */
    public static function getGoodsVisitors($times = '', $map)
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
        $where = "1=1"; // 成功的订单状态

        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }
        if (!empty($map) && !is_null($map)) {
            $where .= " AND collect_id " . $map;
        }
        $res = Db::name('user_collection')
            ->field("IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount")
            ->where(['status' => 1, 'type' => 3])
            ->where($where)
            ->find();
        return $res;
    }

    /**
     * 获取商品浏览数
     * @param $times 所取时间阶段标识
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月12日11:28:35
     * @return array
     */
    public static function getGoodsViews($times = '', $map)
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }
        // 定义查询的条件
        $where = " 1=1 "; // 成功的订单状态
        if (!empty($map)) {
            $where .= " AND collect_id " . $map;
        }
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        return Db::name('user_collection')
            ->field('IFNULL(count(aid), 0) browseCount')
            ->where(['status' => 1, 'type' => 3])
            ->where($where)
            ->find();
    }

    /**
     * 获取被访问商品数
     * @param $times 所取时间阶段标识
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月12日18:36:29
     * @return array
     */
    public static function getGoodsVisitNumber($times = '', $map = '')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }
        // 定义查询的条件
        $where = "1=1"; // 成功的订单状态
        if (!empty($map)) {
            $where .= " AND collect_id " . $map;
        }
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }
        return Db::name('user_collection')
            ->field('IFNULL(COUNT(DISTINCT(collect_id)), 0) browseCount')
            ->where(['status' => 1, 'type' => 3])
            ->where($where)
            ->find();
    }

    /**
     * 商品收藏用户数
     * @param $times 所取时间阶段标识
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月13日16:48:23
     * @return array
     */
    public static function getGoodsCollection($times = '', $map)
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = "1=1"; // 成功的订单状态
        if (!empty($map)) {
            $where .= " AND collect_id " . $map;
        }
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }
        return Db::name('user_collection')
            ->field('IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount')
            ->where(['status' => 1, 'type' => 1])
            ->where($where)
            ->find();
    }

    /**
     * 商品访客列表
     * @param $times 所取时间阶段标识
     * @param $dimension 数据维度(MAX：优秀值；AVG：平均值；COUNT：总数)，默认COUNT
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月12日15:03:02
     * @return array
     */
    public static function getGoodsVisitorsList($times = '', $dimension = 'COUNT')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " `status` = 1 AND `type` = 3 ";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_user_collection
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
     * 获取商品浏览数列表
     * @param $times 所取时间阶段标识
     * @param $dimension 数据维度(MAX：优秀值；AVG：平均值；COUNT：总数)，默认COUNT
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月12日11:28:35
     * @return array
     */
    public static function getGoodsViewsList($times = '', $dimension = 'COUNT')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " `status` = 1 AND `type` = 3 ";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(count(aid), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_user_collection
            WHERE $where
            $group
        ";

        // 获取时间段内每时间段总数
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
     * 获取被访问商品数列表
     * @param $times 所取时间阶段标识
     * @param $dimension 数据维度(MAX：优秀值；AVG：平均值；COUNT：总数)，默认COUNT
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月13日16:27:19
     * @return array
     */
    public static function getGoodsVisitNumberList($times = '', $dimension = 'COUNT')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = "`status` = 1 AND `type` = 3 "; // 成功的订单状态
        if (!empty($timeStr)) {
            $where .= "AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(DISTINCT(collect_id)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_user_collection
            WHERE $where
            $group
        ";

        // 获取时间段内每时间段总数
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
     * 商品收藏用户列表
     * @param $times 所取时间阶段标识
     * @param $dimension 数据维度(MAX：优秀值；AVG：平均值；COUNT：总数)，默认COUNT
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月13日16:46:54
     * @return array
     */
    public static function getGoodsCollectionList($times = '', $dimension = 'COUNT', $distinctUser = 1)
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " `status` = 1 AND `type` = 1 ";

        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        if($distinctUser) {
            $field = 'IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount';
        } else {
            $field = 'IFNULL(COUNT(user_id), 0) browseCount';
        }
        
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_user_collection
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

    /*
     *
     * 获取用户基本信息
     *
     */
    public static function detail($id)
    {
        $res = User::get($id);
        if ($res) {
            $res['sex'] = self::getUserSex($res['sex']);
        }
        return $res;

    }

    public static function getUserSex($sex)
    {
        $data = ['0' => lang('保密'), '1' => lang('男'), '2' => lang('女')];
        return $data[$sex];
    }

    /*
     * 统计会员数据总量
     *
     */
    public static function userStatistics($data)
    {

        $times = $data['times'];
        $user_type = $data['user_type'];


        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where_total_user
            = $where_total_order
            = $where_total_money
            = $where_total_score
            = $where_total_collect
            = $where_total_visit
            = ""; //
        if (!empty($timeStr)) {
            $where_total_user =
                "
                 is_delete = 0
                 and (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) 
                 and user_type ={$user_type}
            ";
            $where_total_order =
                "
                 o.status > 0 
                 and (o.pay_time >= {$timeStr['startTime']} AND o.pay_time <= {$timeStr['endTime']}) 
                 and u.user_type = {$user_type}
                ";
            $where_total_money =
                "
                  m.change_type = 1
                  and (m.create_time >= {$timeStr['startTime']} AND m.create_time <= {$timeStr['endTime']}) 
                  and u.user_type = {$user_type}
                ";
            $where_total_score =
                "
                  is_delete =0 
                  and (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) 
                  and user_type = {$user_type}
                
                ";
            $where_total_collect =
                "
                  c.type = 1
                  and c.status = 1
                  and (c.create_time >= {$timeStr['startTime']} AND c.create_time <= {$timeStr['endTime']}) 
                  and u.user_type = {$user_type}
                ";
            $where_total_visit =
                "
                  c.type = 3
                  and c.status = 1
                  and (c.create_time >= {$timeStr['startTime']} AND c.create_time <= {$timeStr['endTime']}) 
                  and u.user_type = {$user_type}
                ";


        }
        //会员总量
        $total_user = User::where($where_total_user)->count();
        //会员下单总量
        $total_order = Order::alias("o")
            ->join("user u", "o.user_id = u.id", "left")
            ->where($where_total_order)
            ->count();
        //会员总充值量
        $total_money = MoneyLog::alias("m")
            ->join("user u", "m.user_id = u.id", "left")
            ->where($where_total_money)
            ->count();
        //会员总积分
        $total_score = User::where($where_total_score)->sum("score");
        //会员总收藏数
        $total_collect = Collection::alias("c")
            ->join("user u", " c.user_id = u.id ", 'left')
            ->where($where_total_collect)
            ->count();
        //会员浏览总量
        $total_visit = Collection::alias("c")
            ->join("user u", " c.user_id = u.id ", 'left')
            ->where($where_total_visit)
            ->count();

        return [
            'total_user' => $total_user,
            'total_order' => $total_order,
            'total_money' => $total_money,
            'total_score' => $total_score,
            'total_collect' => $total_collect,
            'total_visit' => $total_visit
        ];

    }

    /*
     * 会员数据
     *
     */
    public static function userData($data)
    {
        $times = $data['times'];
        //数据总览
        $user_statistics = self::userStatistics($data);
        //交易数据
        $payData = self::payData($times);
        //会员概览
        $UserView = self::UserView($times);
        return [
            'userStatistics' => $user_statistics,
            'payData' => $payData,
            'UserView' => $UserView
        ];

    }

    /*
     *
     * 会员交易数据折线图
     *
     */

    public static function payData($times)
    {
        // 支付买家数
        $getOrderPayUserData = self::getOrderPayUserData($times);
        //支付金额
        $getSalePriceData = self::getSalePriceData($times);
        //支付订单数
        $getOrderNumberData = self::getOrderNumberData($times);
        return [
            'getOrderPayUserData' => $getOrderPayUserData,
            'getSalePriceData' => $getSalePriceData,
            'getOrderNumberData' => $getOrderNumberData
        ];

    }

    /*
     * 会员概览折线图
     *
     */
    public static function UserView($times)
    {
        //访问数
        $getGoodsVisitorsData = self::getGoodsVisitorsData($times);
        //浏览数
        $getGoodsViewsData = self::getGoodsViewsData($times);
        //收藏数
        $getGoodsCollectionData = self::getGoodsCollectionData($times);
        return [
            'getGoodsVisitorsData' => $getGoodsVisitorsData,
            'getGoodsViewsData' => $getGoodsViewsData,
            'getGoodsCollectionData' => $getGoodsCollectionData
        ];

    }


    /**
     * 获取商品访客数数据列表
     *
     */
    private static function getGoodsVisitorsData($times = 'year', $dimension = 'COUNT')
    {

        // 获取商品访客数数列表
        $getGoodsVisitorsList = Users::getGoodsVisitorsList($times, $dimension);

        // 商品获取访客列表时处理数据
        if ($dimension == 'COUNT') {
            $getGoodsVisitorsList = self::processingData($getGoodsVisitorsList, $times);
        }

        return $getGoodsVisitorsList;
    }

    /**
     * 获取商品浏览量列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsViewsData($times = 'year', $dimension = 'COUNT')
    {
        // 获取商品浏览量列表
        $getGoodsViewsList = Users::getGoodsViewsList($times, $dimension);

        // 商品获取访客列表时处理数据
        if ($dimension == 'COUNT') {
            $getGoodsViewsList = self::processingData($getGoodsViewsList, $times);
        }

        return $getGoodsViewsList;

    }


    /**
     * 商品收藏用户列表数据
     *
     */
    private static function getGoodsCollectionData($times = 'year', $dimension = 'COUNT')
    {

        // 获取数据列表
        $getGoodsCollectionList = Users::getGoodsCollectionList($times, $dimension, 0);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getGoodsCollectionList = self::processingData($getGoodsCollectionList, $times);
        }


        return $getGoodsCollectionList;
    }


    /**
     * 统计支付订单数列表
     *
     */
    private static function getOrderNumberData($times = 'year', $dimension = 'COUNT')
    {
        // 获取数据列表
        $getOrderNumberList = goodsModel::getOrderNumberList($times, $dimension);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getOrderNumberList = Users::processingData($getOrderNumberList, $times);
        }

        return $getOrderNumberList;

    }


    /**
     * 统计支付订单金额列表
     *
     */
    private static function getSalePriceData($times = 'year', $dimension = 'COUNT')
    {

        // 获取数据列表
        $getSalePriceList = goodsModel::getSalePriceList($times, $dimension);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getSalePriceList = Users::processingData($getSalePriceList, $times);
        }

        return $getSalePriceList;

    }


    /**
     * 支付买家数列表
     *
     */
    private static function getOrderPayUserData($times = 'year', $dimension = 'COUNT')
    {

        //买家数
        $getOrderPayUserList = goodsModel::getOrderPayUserList($times, $dimension);

        // 商品获取访客列表时处理数据
        if ($dimension == 'COUNT') {
            $getOrderPayUserList = Users::processingData($getOrderPayUserList, $times);
        }

        return $getOrderPayUserList;

    }


    /**
     * 处理数据列表，根据时间维度，空数据追加处理
     * @author zenghu [ 1427305236@qq.com ]
     */
    public static function processingData($data = [], $times = 'year')
    {
        // 定义处理的数据
        $arrDate = [];
        $weekArr = ['1' => lang('星期1'), '2' => lang('星期2'), '3' => lang('星期3'), '4' => lang('星期4'), '5' => lang('星期5'), '6' => lang('星期6'), '0' => lang('星期日')];
        foreach ($data as $key => $val) {
            switch ($times) {
                case 'year':
                case 'quarter':
                    $data[$key]['dateFormat'] = $val['dateFormat'] . lang('月份');
                    array_push($arrDate, $val['dateFormat']);
                    break;

                case 'month':
                    $data[$key]['dateFormat'] = $val['dateFormat'] . lang('号');
                    array_push($arrDate, $val['dateFormat']);
                    break;

                case 'week':
                    $data[$key]['dateFormat'] = $weekArr[$val['dateFormat']];
                    array_push($arrDate, $val['dateFormat']);
                    break;

                case 'day':
                    if ($val['dateFormat'] == '00') {
                        $data[$key]['dateFormat'] = '24' . lang('时');
                        array_push($arrDate, '24');
                    } else {
                        $data[$key]['dateFormat'] = $val['dateFormat'] . lang('时');
                        array_push($arrDate, $val['dateFormat']);
                    }
                    break;
            }
        }

        // 根据时间维度处理返回的数据
        switch ($times) {
            case 'year':
                for ($i = 1; $i < 13; $i++) {
                    if (!in_array($i, $arrDate)) {
                        $dateFormat = sprintf('%02d', $i) . lang('月份');
                        array_push($data, ['dateFormat' => $dateFormat, 'browseCount' => 0]);
                    }
                }
                break;
            case 'quarter':
                $season = ceil($arrDate['0'] / 3); // 获取季度
                $start = $season * 3 - 2;
                $end = $season * 3;
                for ($i = $start; $i <= $end; $i++) {
                    if (!in_array($i, $arrDate)) {
                        $dateFormat = sprintf('%02d', $i) . lang('月份');
                        array_push($data, ['dateFormat' => $dateFormat, 'browseCount' => 0]);
                    }
                }
                break;
            case 'month':
                $end = date("t");
                for ($i = 1; $i <= $end; $i++) {
                    if (!in_array($i, $arrDate)) {
                        $dateFormat = sprintf('%02d', $i) . lang('号');
                        array_push($data, ['dateFormat' => $dateFormat, 'browseCount' => 0]);
                    }
                }
                break;
            case 'week': // 周
                for ($i = 0; $i <= 6; $i++) {
                    if (!in_array($i, $arrDate)) {
                        $dateFormat = $weekArr[$i];
                        array_push($data, ['dateFormat' => $dateFormat, 'browseCount' => 0]);
                    }
                }
                break;
            case 'day': // 日
                for ($i = 1; $i <= 24; $i++) {
                    if (!in_array($i, $arrDate)) {
                        $dateFormat = sprintf('%02d', $i) . lang('时');
                        array_push($data, ['dateFormat' => $dateFormat, 'browseCount' => 0]);
                    }
                }
                break;

            default:
                # TO DO LIST
                break;
        }
        // 数据排序
        $dateFormatSort = array_column($data, 'dateFormat');
        array_multisort($dateFormatSort, SORT_ASC, $data);

        return $data;
    }

    /*
     * 会员等级
     *
     */
    public static function userLevel()
    {
        return [
            ['label' => lang('普通会员'), 'value' => 0],
            ['label' => lang('白银会员'), 'value' => 1],
            ['label' => lang('黄金会员'), 'value' => 2],
        ];

    }

    /*
     * 会员列表
     *
     */
    public static function userList($data)
    {
        $times = $data['times'];
        $user_type = $data['user_type'];
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = ""; //
        if (!empty($timeStr)) {
            $where = " 
            (u.create_time >= {$timeStr['startTime']} AND u.create_time <= {$timeStr['endTime']}) 
            and u.user_type = {$user_type}
            ";
        }
        $list = User::where($where)
            ->alias("u")
            ->join("order o ", " u.id = o.user_id ", "left")
            ->field("
            u.user_nickname as name,
            u.user_type as type,
            u.score as integral,
            u.user_money balance_money,
            u.id
            ")
            ->group("u.id")
            ->paginate();
        if ($list) {
            foreach ($list as &$v) {

                $v['type'] = self::getLevelName($v['type']);
                //会员浏览量
                $v['browse'] = Db::name("user_collection")->where([
                    'status' => 1,
                    'user_id' => $v['id'],
                    'type' => 3
                ])->count();
                //会员收藏量
                $v['collect'] = Db::name("user_collection")->where([
                    'status' => 1,
                    'user_id' => $v['id'],
                    'type' => 1
                ])->count();
                //支付金额
                $v['pay_money'] = Db::name("order")->where([
                    'pay_status' => 1,
                    'user_id' => $v['id'],
                ])->sum("payable_money");
                //支付订单数
                $v['pay_order'] = Db::name("order")->where([
                    'pay_status' => 1,
                    'user_id' => $v['id']
                ])->count();
                //支付转换率
                $all_order = Db::name("order")->where([
                    'user_id' => $v['id']
                ])->count();
                $v['pay_change'] = $all_order ? round($v['pay_order'] / $all_order, 4) * 100 : 0;
                $v['pay_change'] = $v['pay_change'] . '%';

            }
        }

        return $list;


    }

    public function getLevelName($userType)
    {
        //会员类型0普通会员1白银会员2黄金会员
        $data = [0 => lang('普通会员'), 1 => lang('白银会员'), 2 => lang('黄金会员')];
        return $data[$userType];

    }

    /*
     * 会员总浏览量
     *
     */
    public static function userVisit($times, $user_id)
    {
        //会员浏览总数
        $tatal = self::userVisitTatal($times, $user_id);
        //会员折线数据
        $data = self::userVisitNumberData($times, $user_id);
        return [
            'tatal' => $tatal,
            'data' => $data
        ];
    }

    /*
     * 会员浏览
     *
     */

    public static function userVisitNumberData($times = 'year', $user_id = 0, $dimension = 'COUNT')
    {

        // 获取数据列表
        $getOrderNumberList = self::userVisitList($times, $dimension, $user_id);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getOrderNumberList = Users::processingData($getOrderNumberList, $times);
        }

        return $getOrderNumberList;
    }


    /*
     * 会员浏览
     *
     */
    public static function userVisitList($times = 'year', $dimension = 'COUNT', $user_id = 0)
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " `status` = 1 AND `type` = 3 and user_id = {$user_id} ";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_user_collection
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

    /*
     * 浏览总数
     *
     */
    public static function userVisitTatal($times, $user_id)
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
            $where = " 
            (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) 
            and user_id = {$user_id}
            ";
        }

        $res = Collection::where($where)->count();

        return $res;

    }

    /*
     *
     * 评价
     *
     */
    public static function userComment($times, $user_id)
    {

        //评价总数
        $tatal = self::userCommentTatal($times, $user_id);
        //折线图数据
        $data = self::userCommentNumberData($times, $user_id);
        return [
            'tatal' => $tatal,
            'data' => $data
        ];
    }


    /*
     * 评论统计
     *
     */

    public static function userCommentList($times = 'year', $dimension = 'COUNT', $user_id = 0)
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " user_id = {$user_id} ";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_goods_comment
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

    /*
     * 评论
     *
     */
    public static function userCommentNumberData($times = 'year', $user_id = 0, $dimension = 'COUNT')
    {

        // 获取数据列表
        $getOrderNumberList = self::userCommentList($times, $dimension, $user_id);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getOrderNumberList = Users::processingData($getOrderNumberList, $times);
        }

        return $getOrderNumberList;
    }


    /*
     * 评价总数
     *
     */
    public static function userCommentTatal($times, $user_id)
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
            $where = " 
            (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) 
            and user_id = {$user_id}
            ";
        }

        $res = Db::name("goods_comment")->where($where)->count();

        return $res;

    }


    /*
     * 订单数量
     *
     */
    public static function userOrder($times, $user_id)
    {
        //订单总数
        $userOrderTatal = self::userOrderTatal($times, $user_id);
        //预售订单
        $preOrder = self::userOrderNumberData($times, 'pre', $user_id);
        //普通订单
        $saleOrder = self::userOrderNumberData($times, 'sale', $user_id);
        //取消订单
        $cancelOrder = self::userOrderNumberData($times, 'cancel', $user_id);
        $data = [
            'userOrderTatal' => $userOrderTatal,
            'preOrder' => $preOrder,
            'saleOrder' => $saleOrder,
            'cancelOrder' => $cancelOrder
        ];
        return $data;
    }

    /*
 *购物车数量
 *
 */

    public static function userCart($times, $user_id)
    {
        //购物车总数
        $tatal = self::userCartTotal($times, $user_id);
        //购物车折线图
        $data = self::userCartNumberData($times, $user_id);
        return [
            'tatal' => $tatal,
            'data' => $data
        ];

    }

    /*
     * 退款数量
     *
     */
    public static function userRefund($times, $user_id)
    {
        //退款总数
        $tatal = self::userRefundTotal($times, $user_id);
        //退款折线图
        $data = self::userRefundNumberData($times, $user_id);
        return [
            'tatal' => $tatal,
            'data' => $data
        ];

    }


    /*
* 统计退款
*/
    public static function userReundList($times = 'year', $dimension = 'COUNT', $user_id = 0)
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = "  user_id = {$user_id} ";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_order_refund
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

    /*
 * 统计退款
 *
 */
    public static function userRefundNumberData($times = 'year', $user_id = 0, $dimension = 'COUNT')
    {

        // 获取数据列表
        $getOrderNumberList = self::userReundList($times, $dimension, $user_id);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getOrderNumberList = Users::processingData($getOrderNumberList, $times);
        }

        return $getOrderNumberList;
    }

    /*

*会员购物车总数
*
*/
    public function userRefundTotal($times = 'year', $user_id = 0)
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
            $where = "
            (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']})
            and user_id = {$user_id}
            ";
        }

        $res = OrderRefund::where($where)->count();

        return $res;

    }

    /*
 * 统计会员购物车
 *
 */
    public static function userCartNumberData($times = 'year', $user_id = 0, $dimension = 'COUNT')
    {

        // 获取数据列表
        $getOrderNumberList = self::userCartList($times, $dimension, $user_id);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getOrderNumberList = Users::processingData($getOrderNumberList, $times);
        }

        return $getOrderNumberList;
    }


    /*
 * 购物车
 */
    public static function userCartList($times = 'year', $dimension = 'COUNT', $user_id = 0)
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " user_id = {$user_id} ";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_goods_cart
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

    /*
 * 统计会员收藏数量
 *
 */
    public static function userCollectNumberData($times = 'year', $user_id = 0, $dimension = 'COUNT')
    {

        // 获取数据列表
        $getOrderNumberList = self::userCollectList($times, $dimension, $user_id);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getOrderNumberList = Users::processingData($getOrderNumberList, $times);
        }

        return $getOrderNumberList;
    }


    /*
     * 统计区间内订单数数据列表
     */
    public static function userCollectList($times = 'year', $dimension = 'COUNT', $user_id = 0)
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " `status` = 1 AND `type` = 3 and user_id = {$user_id} ";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(DISTINCT(user_id)), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(create_time,\'%m\')';
                break;
        }

        // 拼接需要查询数据的sql
        $sql = "
            SELECT 
                {$field}
            FROM lb_user_collection
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

    /*
     * 会员收藏数量
     *
     */

    public static function userCollect($times, $user_id)
    {
        //收藏总数
        $tatal = self::userCartTotal($times, $user_id);
        //收藏折线图
        $data = self::userCartNumberData($times, $user_id);
        return [
            'tatal' => $tatal,
            'data' => $data
        ];

    }


    /*
 *会员购物车总数
 *
 */
    public function userCartTotal($times = 'year', $user_id = 0)
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
            $where = "
            (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']})
            and user_id = {$user_id}
            ";
        }

        $res = Cart::where($where)->count();

        return $res;

    }


    /*
     *会员收藏总数
     *
     */
    public function userCollectTotal($times = 'year', $user_id = 0)
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
            $where = " 
            (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) 
            and user_id = {$user_id}
            ";
        }

        $res = Collection::where($where)->count();

        return $res;

    }

    public function userOrderTatal($times = 'year', $user_id = 0)
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
            $where = " 
            (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) 
            and user_id = {$user_id}
            ";
        }

        $res = Order::where($where)->count();

        return $res;
    }

    /*
     * 统计会员下单数量
     *
     */
    public static function userOrderNumberData($times = 'year', $type = 'sale', $user_id = 0, $dimension = 'COUNT')
    {

        // 获取数据列表
        $getOrderNumberList = self::userOrderList($times, $dimension, $type, $user_id);


        // 处理数据
        if ($dimension == 'COUNT') {
            $getOrderNumberList = Users::processingData($getOrderNumberList, $times);
        }

        return $getOrderNumberList;
    }


    /**
     * 统计区间内订单数数据列表
     */
    public static function userOrderList($times = 'year', $dimension = 'COUNT', $type = 'sale', $user_id = 0)
    {
        // 定义时间区间数组
        $timeStr = [];
        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " user_id = {$user_id} "; // 成功的订单状态
        if ($type == 'sale') {
            //销售订单
            $where .= " and order_type = 3 ";
        } elseif ($type == 'pre') {
            //预售订单
            $where .= " and order_type = 7 ";
        } else {
            //取消订单
            $where .= " and status = -1 ";
        }
        if (!empty($timeStr)) {
            $where .= " AND (o.create_time >= {$timeStr['startTime']} AND o.create_time <= {$timeStr['endTime']}) ";
        }

        // 根据不同的时间统计维度查询
        $group = '';
        $field = 'IFNULL(COUNT(aid), 0) browseCount';
        switch ($times) {
            case 'day': // 日
                $field .= ',FROM_UNIXTIME(o.create_time,\'%H\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%H\')';
                break;

            case 'week': // 周
                $field .= ',FROM_UNIXTIME(o.create_time,\'%w\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%w\')';
                break;

            case 'month': // 月
                $field .= ',FROM_UNIXTIME(o.create_time,\'%d\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%d\')';
                break;

            case 'quarter': // 季
                $field .= ',FROM_UNIXTIME(o.create_time,\'%m\') dateFormat';
                $group = 'GROUP BY FROM_UNIXTIME(pay_time,\'%m\')';
                break;

            case 'year': // 年
                $field .= ',FROM_UNIXTIME(o.create_time,\'%m\') dateFormat';
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

    /*
     * 会员详情列表
     *
     */
    public static function userDetailList($times = 'year', $user_id = 0, $type = 'order')
    {
        // 定义时间区间数组
        $timeStr = [];

        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }

        // 定义查询的条件
        $where = " user_id = {$user_id} ";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";


        }
        switch ($type) {

            case 'collect':
                //收藏数量
                $where .= " and status = 1 and type = 1 ";
                $res = Collection::where($where)->paginate();
                if ($res) {
                    foreach ($res as &$v) {
                        $goods = GD::get($v['collect_id']);
                        $goods_sku = GoodsSku::where(['sku_id' => $v['sku_id']])->value("key_name");
                        $v['name'] = $goods['name'];
                        $v['thumb'] = get_file_url($goods['thumb']);
                        $v['price'] = $goods['shop_price'];
                        $v['collect_num'] = Collection::where(['collect_id' => $v['collect_id']])->count();
                        $v['sku_name'] = $goods_sku;
                    }
                }

                break;
            case 'cart':

                //购物车
                $res = Cart::where($where)->paginate();
                if ($res) {
                    foreach ($res as &$v) {
                        $goods = GD::get($v['goods_id']);
                        $goods_sku = GoodsSku::where(['sku_id' => $v['sku_id']])->value("key_name");
                        $v['name'] = $goods['name'];
                        $v['sn'] = $goods['sn'];
                        $v['thumb'] = get_file_url($goods['thumb']);
                        $v['price'] = $goods['shop_price'];
                        $v['sales_sum'] = $goods['sales_sum'];
                        $v['sku_name'] = $goods_sku;
                    }
                }
                break;
            case 'refund':
                //退款
                $res = OrderRefund::where($where)->paginate();
                if ($res) {
                    foreach ($res as &$v) {
                        $v['name'] = GD::where(['id' => $v['goods_id']])->value("name");
                    }
                }

                break;
            case 'comment':
                //评价
                $res = GoodsComment::where($where)->paginate();
                if ($res) {
                    foreach ($res as &$v) {
                        $v['goods_id'] = GD::where(['id' => $v['goods_id']])->value("name");
                    }
                }
                break;
            case 'visit':
                //浏览
                $where .= " and status = 1 and type = 3 ";
                $res = Collection::where($where)->paginate();
                if ($res) {
                    foreach ($res as &$v) {
                        $goods = GD::where([
                            'id' => $v['collect_id']
                        ])->find();
                        $v['name'] = $goods["name"];
                        $v['price'] = $goods["shop_price"];
                    }
                }
                break;
            default:
                //最近消费记录
                $res = Order::where($where)->paginate();
                if ($res) {
                    foreach ($res as &$v) {
                        $v['status'] = Order::$order_status[$v['status']];
                    }
                }

                break;
        }

        return $res;

    }


}
