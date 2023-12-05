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
namespace app\statistics\admin;

use app\admin\admin\Base;
use app\statistics\model\Goods as goodsModel;
use app\statistics\model\Users;
use app\goods\model\Category;
use app\goods\model\Goods as GoodsOriginal;
use think\Db;
use service\ApiReturn;
use function mysql_xdevapi\expression;

class Goods extends Base
{
    // 定义时间区间
    private static $times = array(
        'day' => 'yesterday', // 日-昨日
        'week' => 'lastweek', // 周-上周
        'month' => 'lastmonth', // 月-上个月
        'quarter' => 'lastquarter', // 季-上个季度
        'year' => 'lastyear', // 年-去年
    );

    /**
     * 数据首页统计
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function index()
    {
        // 定义获取时间维度
        $times = 'year';
        //商品概况
        // 获取商品统计数据
        $getGoodsData = $this->getGoodsData($times);
        // 获取商品访客数-列表展示
        $getGoodsDataList = $this->getGoodsDataList($times);
        $category = Category::getMenuTree();
        //商品榜单
        $goodsTop = $this->goodsTop();
        $this->assign('goodsTop', $goodsTop);
        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');
        $this->assign('goodsData', $getGoodsData);
        $this->assign('category', $category);
        $this->assign('goodsDataList', $getGoodsDataList);
        return $this->fetch();
    }

    /*
     *
     * 商城数据-商品榜单
     */
    public function goodsTop()
    {
        $times = request()->param("times", 'year');
        $data = goodsModel::getGoodsTopList($times);
        return returnData(2000, 'ok', $data);
    }


    /*
     * 商城数据-商品明细
     *
     */
    public function getGoodsList()
    {
        //商品明细
        $param = request()->param();
        $where = [];
        if (isset($param['goodsTypeId']) && $param['goodsTypeId'] != 0) {
            $cid = Category::getChildsId($param['goodsTypeId']);
            if (count($cid)) {
                $where[] = ['cid', 'in', $cid];
            } else {
                $where[] = ['cid', '=', $param['goodsTypeId']];
            }
        }
        if (isset($param['goodsName']) && !empty($param['goodsName'])) {
            $where[] = ['name', 'like', '%' . $param['goodsName'] . '%'];
        }
        $param['startTime'] =
            (isset($param['startTime']) && !empty($param['startTime']))
                ? strtotime($param['startTime'])
                : 0;
        $param['endTime'] =
            (isset($param['endTime']) && !empty($param['endTime']))
                ? strtotime($param['endTime'])
                : 99 * 365 * 24 * 60 * 60;
        $data_list = goodsModel::getGoodsList($where, $param);
        //导出excel
        if ($param['is_import']) {
            $excelData = $_excelData = [];
            foreach ($data_list as $v) {
                $excelData[] = [
                    'name' => $v['name'],
                    'sn' => $v['sn'],
                    'goodsView' => $v['goodsView'],
                    'goodsPayNum' => $v['goodsPayNum'],
                    'goodsPayUserNum' => $v['goodsPayUserNum'],
                    'goodsPaytAmount' => $v['goodsPaytAmount'],
                    'goodsCollectUserNum' => $v['goodsCollectUserNum']

                ];
            }
            $_excelData[0]['list'] = $excelData;
            $xlsName = lang('统计明细').'-' . date("Y-m-d H:i:s", time());
            $xlsCell = [
                ['name', lang('商品名称')],
                ['sn', lang('商品货号')],
                ['goodsView', lang('商品浏览量')],
                ['goodsPayNum', lang('支付件数')],
                ['goodsPayUserNum', lang('支付买家数')],
                ['goodsPaytAmount', lang('支付金额')],
                ['goodsCollectUserNum', lang('商品收藏用户数')]
            ];
            $excelData = array_values($_excelData);
            $this->exportExcel($xlsName, $xlsCell, $excelData);
        }


        return returnData(2000, 'ok', $data_list);
    }

    /**
     * 经营概览数据统计
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function businessOverview()
    {
        //交易数据zzz
        $payData = $this->getPayData();
        //商品数据
        $goodsData = $this->goodsData();

        $this->assign('payData', $payData);
        $this->assign('goodsData', $goodsData);

        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');
        return $this->fetch();
    }

    /*
     * 经营数据总览-商品数据
     *
     */
    public function goodsData()
    {
        $times = request()->param('times', 'year');
        $getGoodsDataList = json_decode($this->getGoodsDataList($times), true);
        $result = $getGoodsDataList['result'];
        $data = [
            'goodsView' => $result['getGoodsVisitorsData']['stageGoodsVisitors'] ?: 0,//访客
            'goodsBrowse' => $result['getGoodsViewsData']['stageGoodsViews'] ?: 0,//浏览量
            'goodsBargainNum' => goodsModel::getPayGoods($times),
            'goodsSelfView' => $result['getGoodsVisitNumberData']['stageGoodsVisitNumber'] ?: 0,
            'goodsCollectNum' => $result['getGoodsCollectionData']['stageGoodsCollection'] ?: 0
        ];
        return returnData(2000, 'ok', $data);
    }

    /*
     * 商城数据-商品榜单
     *
     */
    public function getGoodsTopList()
    {
    }


    /*
     * 经营数据总览-交易数据
     *
     */
    public function getPayData()
    {
        $payData = json_decode($this->payData(), true);
        $result = $payData['result'];

        $data = [
            'payAmount' => $result['payAmountListData']['stageSalePrice'] ?: 0,
            'payUserNum' => $result['payUserNumListData']['stageOrderPayUser'] ?: 0,
            'payOrder' => $result['payOrderListData']['stageOrderNumber'] ?: 0,
            'payPercent' => $result['payPercentListData']['stagePayRate'] ?: 0,
            'payCustomPrice' => $result['payCustomPriceListData']['stageNumber'] ?: 0
        ];
        return returnData(2000, 'ok', $data);
    }

    /**
     * 交易数据统计
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function transactionData()
    {
        //实时数据
        $realTimeData = returnData('2000', 'ok', goodsModel::realTimeData());
        // halt(goodsModel::realTimeData());
        // 数据总览
        $payData = $this->payData();

        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');
        $this->assign('realTimeData', $realTimeData);
        $this->assign('payData', $payData);

        return $this->fetch();
    }

    /*
     * 交易数据-实时数据
     *
     */
    public function realTimeData()
    {
        $times = request()->param('times');
        $data = goodsModel::realTimeData($times);
        return returnData('2000', 'ok', $data);
    }


    /**
     * 获取商品统计数据
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getGoodsData($times = 'year')
    {
        // 判断接收参数
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }

        // 参数校验
        $re = self::verificationTime($times);
        if (!empty($re) && $re['status'] == 5000) {
            return returnData('5000', $re['msg']);
        }

        // 获取商品访客数
        $getGoodsVisitors = self::getGoodsVisitors($times);

        // 获取商品浏览数
        $getGoodsViews = self::getGoodsViews($times);

        // 获取支付订单数
        $getOrderNumber = self::getOrderNumber($times);

        // 获取总销售金额
        $getSalePrice = self::getSalePrice($times);

        // 获取支付转化率
        $getPaymentConversionRate = self::getPaymentConversionRate($times);

        // 获取被访问商品数
        $getGoodsVisitNumber = self::getGoodsVisitNumber($times);

        //支付买家数
        $orderPayUser = self::getOrderPayUser($times);

        //商品收藏用户数
        $goodCollectUser = self::getGoodsCollection($times);

        return returnData('2000', lang('数据查询成功'), [
            'goodsVisitors' => $getGoodsVisitors['browseCount'],
            'goodsViews' => $getGoodsViews['browseCount'],
            'orderNumber' => $getOrderNumber['orderCount'],
            'salePrice' => $getSalePrice['orderPrice'],
            'paymentConversionRate' => $getPaymentConversionRate,
            'goodsVisitNumber' => $getGoodsVisitNumber['browseCount'],
            'orderPayUser' => $orderPayUser['orderPayCount']??0,
            'goodCollectUser' => $goodCollectUser['browseCount']
        ]);
    }

    /**
     * 盈利额统计
     * @param $time 所取时间阶段标识
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月2日16:00:21
     * @return array
     */
    private function getProfit($times)
    {
        // 统计总盈利金额
        $totalProfitPrice = goodsModel::getProfit($times);

        // 本阶段盈利总额
        $stageProfitPrice = goodsModel::getProfit($times);

        // 上一阶段盈利总额
        $lastTimes = 'lastyear.' . $times;
        $lastStageProfitPrice = goodsModel::getProfit($lastTimes);

        // 计算盈利额增长比例
        $growthRatio = 0;
        if ($lastStageProfitPrice['profitPrice'] == 0) {
            if ($stageProfitPrice['profitPrice'] == 0) {
                $growthRatio = 0;
            } else {
                $growthRatio = 100;
            }
        } else {
            $growthRatio = sprintf('%.2f', ($stageProfitPrice['profitPrice'] - $lastStageProfitPrice['profitPrice']) / $lastStageProfitPrice['profitPrice']);
        }

        // 获取统计图数据列表
        $getProfitAverage = self::getSalePriceList($times, 'AVG'); // 获取订单金额平均值
        $getProfitExcellent = self::getSalePriceList($times, 'MAX'); // 获取订单金额优秀值
        $getProfitList = self::getProfitList($times); // 获取订单金额列表

        return [
            'total_profit_price' => sprintf('%.2f', $totalProfitPrice['profitPrice']), // 总盈利额
            'stage_profit_price' => sprintf('%.2f', $stageProfitPrice['profitPrice']),  // 本阶段盈利额
            'profit_price_growth_ratio' => $growthRatio, // 增长率
            'ProfitAverage' => sprintf('%.2f', $getProfitAverage[0]['browseCount']),
            'ProfitExcellent' => $getProfitExcellent[0]['browseCount'],
            'ProfitList' => $getProfitList,
        ];
    }

    /*
     *
     *获取交易数据
     *
     */
    public function payData($times = 'year')
    {
        // 判断接收参数
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }

        // 参数校验
        $re = self::verificationTime($times);
        if (!empty($re) && $re['status'] == 5000) {
            return returnData('5000', $re['msg']);
        }

        // 支付订单人数据列表
        $getOrderPayUserData = self::getOrderPayUserData($times);
        // 利润额
        $getProfit = self::getProfit($times);
        // 统计区间内订单数数据
        $getOrderNumberData = self::getOrderNumberData($times);

        // 统计支付转化率列表
        $getPaymentConversionRateData = self::getPaymentConversionRateData($times);

        // 统计支付订单金额列表
        $getSalePriceData = self::getSalePriceData($times);
        //支付客单价
        $payCustomPriceListData = $this->getCustomerPrice($times);

        //老买家占比
        $oldUserPercentData = $this->getOldUser($times);


        // 返回结果
        return returnData('2000', lang('数据查询成功'), [
            'payAmountListData' => $getSalePriceData,
            'oldUserPercentData' => $oldUserPercentData,
            'payOrderListData' => $getOrderNumberData,
            'payPercentListData' => $getPaymentConversionRateData,
            'payCustomPriceListData' => $payCustomPriceListData,
            'payUserNumListData' => $getOrderPayUserData,
            'payProfitData' => $getProfit
        ]);
    }

    /*
     * 获取老买家占比
     *
     */
    public function getOldUser($times)
    {

        // 本阶段老买家数
        $stageUserNumber = self::getOldUserNumber($times);

        // 上一阶段老买家数
        $lastTimes = 'lastyear.' . $times;
        $lastUserNumber = self::getOldUserNumber($lastTimes);

        // 计算增长比例
        $growthRatio = self::growthRate($lastUserNumber['userCount'], $stageUserNumber['userCount']);

        // 获取统计图数据列表
        $getUserNumberAverage = self::getUserNumberList($times, 'AVG'); // 获取商品支付订单人数平均值
        $getUserNumberExcellent = self::getUserNumberList($times, 'MAX'); // 获取商品支付订单人数优秀值
        $getUserNumberList = self::getUserNumberList($times); // 获取商品支付订单人数列表

        // halt($getUserNumberList);
        return [
            'stageNumber' => $stageUserNumber['userCount'],
            'growthRatio' => $growthRatio,
            'average' => sprintf('%.2f', $getUserNumberAverage[0]['browseCount']),
            'excellent' => $getUserNumberExcellent[0]['browseCount'] ?: 0,
            'oldUserPercent' => $getUserNumberList,
        ];
    }

    /*
     * 获取支付客单价
     *
     */
    public function getCustomerPrice($times)
    {
        $getSalePriceData = self::getSalePriceData($times);
        $getOrderNumberData = self::getOrderNumberData($times);
        $stageNumber = round($getSalePriceData['stageSalePrice'] / $getOrderNumberData['stageOrderNumber'], 2);
        $growthRatio = $getSalePriceData['growthRatio'];
        $average = $stageNumber;
        $excellent = $getSalePriceData['salePriceExcellent'];
        $payCustomPriceList = $getSalePriceData['salePriceList'];
        $orderNumberList =   $getOrderNumberData['orderNumberList'];
        //计算客单价，每月的总金额/每月的订单数
        foreach ($payCustomPriceList as $key => &$val) {
            foreach ($orderNumberList as $k => $v) {
                if ($val['dateFormat'] == $v['dateFormat']) {
                    if ($v['browseCount']) {
                        $val['browseCount'] = strval(round($val['browseCount']/$v['browseCount'], 2));
                    }
                }
            }
        }
        return [
            'stageNumber' => $stageNumber,
            'growthRatio' => $growthRatio,
            'average' => $average,
            'excellent' => $excellent,
            'payCustomPriceList' => $payCustomPriceList

        ];
    }

    /**
     * 获取商品统计数据列表
     * @author zenghu [ 1427305236@qq.com ]
     * @editor zhougs
     */
    public function getGoodsDataList($times = 'year')
    {

        // 判断接收参数
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }

        // 参数校验
        $re = self::verificationTime($times);
        if (!empty($re) && $re['status'] == 5000) {
            return returnData('5000', $re['msg']);
        }

        $param = input("param.");
        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }
        $where = "1=1";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }
        $map = "";
        if (isset($param['cid']) && $param['cid'] != 0) {
            $cid = Category::getChildsId($param['cid']);
            if (count($cid)) {
                $map1[] = ['g.cid', 'in', $cid];
            } else {
                $map1[] = ['g.cid', '=', $param['cid']];
            }
        } else {
            $cid = Category::getChildsId(0);

            $map1[] = ['g.cid', 'in', $cid];
        }
        // 查询分类下商品数量
        $goods_groupby_cid_list = GoodsOriginal::alias('g')
            ->join('goods_category c', 'g.cid=c.id', 'left')
            ->field('g.*,c.name as c_name')
            ->where($map1)
            ->where($where)
            ->column("g.id");
        if (count($goods_groupby_cid_list)) {
            $ids = trim(implode(",", $goods_groupby_cid_list), ',');
            $map = " in (" . $ids . ")";
        }
        // 获取商品访客数数据列表zgs
        $getGoodsVisitorsData = self::getGoodsVisitorsData($times, $map);

        // 获取商品浏览量数据列表
        $getGoodsViewsData = self::getGoodsViewsData($times, $map);

        // 支付订单人数据列表
        $getOrderPayUserData = self::getOrderPayUserData($times, $map);

        // 统计区间内订单数数据
        $getOrderNumberData = self::getOrderNumberData($times, $map);

        // 统计支付转化率列表
        $getPaymentConversionRateData = self::getPaymentConversionRateData($times, $map);

        // 统计支付订单金额列表
        $getSalePriceData = self::getSalePriceData($times, $map);

        // 商品收藏用户列表数据
        $getGoodsCollectionData = self::getGoodsCollectionData($times, $map);

        // 获取被访问商品数列表
        $getGoodsVisitNumberData = self::getGoodsVisitNumberData($times, $map);
//        halt($getGoodsVisitNumberData);

        //商品数量
        $getGoodsNumber = goodsModel::getGoodsNum($times, $map);
        $getGoodsNumber = ["stageGoodsNumber" => $getGoodsNumber];
        //支付客单价

        if (!$getOrderNumberData['stageOrderNumber']) {
            $getPaymentUnitPrice = 0;
        } else {
            $getPaymentUnitPrice = sprintf("%.2f", $getSalePriceData['stageSalePrice'] / $getOrderNumberData['stageOrderNumber']);
        }
        $getPaymentUnitPrice = ["stagePaymentUnitPrice" => $getPaymentUnitPrice];
        //商品成交件数
        $getGoodsDealNumber = self::getGoodsDealNumber($times, $map);
        $getGoodsDealNumber = ['stageGoodsDealNumber' => $getGoodsDealNumber['goodsDealNum']];
        $resData = [
            'getGoodsVisitorsData' => $getGoodsVisitorsData,
            'getGoodsViewsData' => $getGoodsViewsData,
            'getOrderPayUserData' => $getOrderPayUserData,
            'getOrderNumberData' => $getOrderNumberData,
            'getPaymentConversionRateData' => $getPaymentConversionRateData,
            'getSalePriceData' => $getSalePriceData,
            'getGoodsCollectionData' => $getGoodsCollectionData,
            'getGoodsVisitNumberData' => $getGoodsVisitNumberData,
            'getGoodsNumberData' => $getGoodsNumber,
            'getPaymentUnitPriceData' => $getPaymentUnitPrice,
            'getGoodsDealNumberData' => $getGoodsDealNumber
        ];
        // 返回结果zxx
        return returnData('2000', lang('数据查询成功'), $resData);
    }

    /**
     * 获取商品访客数数据列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsVisitorsData($times = 'year', $map = '')
    {
        // 本阶段访客数
        $stageGoodsVisitors = self::getGoodsVisitors($times, $map);

        // 上一阶段访客数
        $lastTimes = self::$times[$times];
        $lastGoodsVisitors = self::getGoodsVisitors($lastTimes);

        // 计算增长比例
        $growthRatio = self::growthRate($lastGoodsVisitors['browseCount'], $stageGoodsVisitors['browseCount']);

        // 获取统计图数据列表
        $getGoodsVisitorsAverageList = self::getGoodsVisitorsList($times, 'AVG'); // 获取商品访客数平均值列表
        $getGoodsVisitorsExcellentList = self::getGoodsVisitorsList($times, 'MAX'); // 获取商品访客优秀值列表

        $getGoodsVisitorsList = self::getGoodsVisitorsList($times, 'COUNT'); // 获取商品访客数列表

        return [
            'stageGoodsVisitors' => $stageGoodsVisitors['browseCount'],
            'growthRatio' => $growthRatio,
            'goodsVisitorsAverage' => sprintf('%.2f', $getGoodsVisitorsAverageList[0]['browseCount']),
            'goodsVisitorsExcellent' => $getGoodsVisitorsExcellentList[0]['browseCount'],
            'goodsVisitorsList' => $getGoodsVisitorsList,
        ];
    }

    /**
     * 获取商品浏览量列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsViewsData($times = 'year', $map = '')
    {
        // 本阶段浏览量
        $stageGoodsViews = self::getGoodsViews($times, $map);
        // 上一阶段浏览量
        $lastTimes = self::$times[$times];
        $lastGoodsViews = self::getGoodsViews($lastTimes);

        // 计算增长比例
        $growthRatio = self::growthRate($lastGoodsViews['browseCount'], $stageGoodsViews['browseCount']);

        // 获取统计图数据列表
        $getGoodsViewsAverageList = self::getGoodsViewsList($times, 'AVG'); // 获取商品浏览量平均值
        $getGoodsViewsExcellentList = self::getGoodsViewsList($times, 'MAX'); // 获取商品浏览量优秀值
        $getGoodsViewsList = self::getGoodsViewsList($times, "COUNT"); // 获取商品浏览量列表

        return [
            'stageGoodsViews' => $stageGoodsViews['browseCount'],
            'growthRatio' => $growthRatio,
            'goodsViewsAverage' => sprintf('%.2f', $getGoodsViewsAverageList[0]['browseCount']),
            'goodsViewsExcellent' => $getGoodsViewsExcellentList[0]['browseCount'],
            'goodsViewsList' => $getGoodsViewsList,
        ];
    }

    /**
     * 支付买家数列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getOrderPayUserData($times = 'year', $map = '')
    {
        // $times = 'day';
        // echo $times;
        // 本阶段支付订单人数
        $stageOrderPayUser = self::getOrderPayUser($times, $map);
        $stageOrderPayUser['orderPayCount'] = empty($stageOrderPayUser['orderPayCount']) ? 0 : $stageOrderPayUser['orderPayCount'];
        // halt($stageOrderPayUser);
        // 上一阶段支付订单人数
        $lastTimes = 'lastyear.' . $times;
        $lastOrderPayUser = self::getOrderPayUser($lastTimes);
        $lastOrderPayUser['orderPayCount'] = empty($lastOrderPayUser['orderPayCount']) ? 0 : $lastOrderPayUser['orderPayCount'];
        // 计算增长比例
        $growthRatio = self::growthRate($lastOrderPayUser['orderPayCount'], $stageOrderPayUser['orderPayCount']);

        // 获取统计图数据列表
        $getGoodsViewsAverageList = self::getOrderPayUserList($times, 'AVG'); // 获取商品支付订单人数平均值
        $getOrderPayUserExcellentList = self::getOrderPayUserList($times, 'MAX'); // 获取商品支付订单人数优秀值
        $getOrderPayUserList = self::getOrderPayUserList($times); // 获取商品支付订单人数列表

        return [
            'stageOrderPayUser' => $stageOrderPayUser['orderPayCount'],
            'growthRatio' => $growthRatio,
            'orderPayUserAverage' => sprintf('%.2f', $getGoodsViewsAverageList[0]['browseCount']),
            'orderPayUserExcellent' => $getOrderPayUserExcellentList[0]['browseCount'],
            'orderPayUserList' => $getOrderPayUserList,
        ];
    }

    /**
     * 统计支付订单数列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getOrderNumberData($times = 'year', $map = '')
    {
        // 本阶段订单数
        $stageOrderNumber = self::getOrderNumber($times, $map);
        // 上一阶段订单数
        $lastTimes = 'lastyear.' . $times;
        $lastOrderNumber = self::getOrderNumber($lastTimes);

        // 计算增长比例
        $growthRatio = self::growthRate($lastOrderNumber['orderCount'], $stageOrderNumber['orderCount']);

        // 获取统计图数据列表
        $getOrderNumberAverage = self::getOrderNumberList($times, 'AVG'); // 获取商品支付订单人数平均值
        $getOrderNumberExcellent = self::getOrderNumberList($times, 'MAX'); // 获取商品支付订单人数优秀值
        $getOrderNumberList = self::getOrderNumberList($times); // 获取商品支付订单人数列表
        return [
            'stageOrderNumber' => $stageOrderNumber['orderCount'],
            'growthRatio' => $growthRatio,
            'orderNumberAverage' => sprintf('%.2f', $getOrderNumberAverage[0]['browseCount']),
            'orderNumberExcellent' => $getOrderNumberExcellent[0]['browseCount'],
            'orderNumberList' => $getOrderNumberList,
        ];
    }

    /**
     * 统计支付转化率列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getPaymentConversionRateData($times = 'year', $map = '')
    {
        // 本阶段支付率
        $stagePayRate = self::getPaymentConversionRate($times, $map);

        // 上一阶段支付率
        $lastTimes = 'lastyear.' . $times;
        $lastPayRate = self::getPaymentConversionRate($lastTimes);

        // 计算增长比例
        $growthRatio = self::growthRate($lastPayRate, $stagePayRate);

        // 获取统计图数据列表
        $getPaymentConversionRateList = self::getPaymentConversionRateList($times); // 获取支付转化率列表
        $getPaymentConversionRateAverage = self::getPaymentConversionRateAverage($getPaymentConversionRateList); // 获取支付转化率平均值
        $getPaymentConversionRateMax = self::getPaymentConversionRateMax($getPaymentConversionRateList); // 获取支付转化率优秀值

        return [
            'stagePayRate' => $stagePayRate,
            'growthRatio' => $growthRatio,
            'paymentConversionRateAverage' => $getPaymentConversionRateAverage,
            'paymentConversionRateMax' => $getPaymentConversionRateMax,
            'paymentConversionRateList' => $getPaymentConversionRateList,
        ];
    }

    /**
     * 统计支付订单金额列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getSalePriceData($times = 'year', $map = '')
    {
        // 本阶段订单金额
        $stageSalePrice = self::getSalePrice($times, $map);

        // 上一阶段订单金额
        $lastTimes = 'lastyear.' . $times;
        $lastSalePrice = self::getSalePrice($lastTimes);
        // 计算增长比例
        $growthRatio = self::growthRate($lastSalePrice['orderPrice'], $stageSalePrice['orderPrice']);

        // 获取统计图数据列表
        $getSalePriceAverage = self::getSalePriceList($times, 'AVG'); // 获取订单金额平均值
        $getSalePriceExcellent = self::getSalePriceList($times, 'MAX'); // 获取订单金额优秀值
        $getSalePriceList = self::getSalePriceList($times); // 获取订单金额列表
        return [
            'stageSalePrice' => $stageSalePrice['orderPrice'],
            'growthRatio' => $growthRatio,
            'salePriceAverage' => sprintf('%.2f', $getSalePriceAverage[0]['browseCount']),
            'salePriceExcellent' => $getSalePriceExcellent[0]['browseCount'],
            'salePriceList' => $getSalePriceList,
        ];
    }

    /**
     * 获取被访问商品数列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsVisitNumberData($times = 'year', $map = '')
    {
        // 本阶段订单金额
        $stageGoodsVisitNumber = self::getGoodsVisitNumber($times, $map);
        // 上一阶段订单金额
        $lastTimes = self::$times[$times];
        $lastGoodsVisitNumber = self::getGoodsVisitNumber($lastTimes, $map);


        // 计算增长比例
        $growthRatio = self::growthRate($lastGoodsVisitNumber['browseCount'], $stageGoodsVisitNumber['browseCount']);

        // 获取统计图数据列表
        $getGoodsVisitNumberAverage = self::getGoodsVisitNumberList($times, 'AVG'); // 获取订单金额平均值
        $getGoodsVisitNumberExcellent = self::getGoodsVisitNumberList($times, 'MAX'); // 获取订单金额优秀值
        $getGoodsVisitNumberList = self::getGoodsVisitNumberList($times, "COUNT"); // 获取订单金额列表

        return [
            'stageGoodsVisitNumber' => $stageGoodsVisitNumber['browseCount'],
            'growthRatio' => $growthRatio,
            'goodsVisitNumberAverage' => sprintf('%.2f', $getGoodsVisitNumberAverage[0]['browseCount']),
            'goodsVisitNumberExcellent' => $getGoodsVisitNumberExcellent[0]['browseCount'],
            'goodsVisitNumberList' => $getGoodsVisitNumberList,
        ];
    }

    /**
     * 商品收藏用户列表数据
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsCollectionData($times = 'year', $map = '')
    {
        // 本阶段订单金额
        $stageGoodsCollection = self::getGoodsCollection($times, $map);

        // 上一阶段订单金额
        $lastTimes = self::$times[$times];
        $lastGoodsCollection = self::getGoodsCollection($lastTimes, $map);
        // 计算增长比例
        $growthRatio = self::growthRate($lastGoodsCollection['browseCount'], $stageGoodsCollection['browseCount']);

        // 获取统计图数据列表
        $getGoodsCollectionAverage = self::getGoodsCollectionList($times, 'AVG'); // 获取订单金额平均值
        $getGoodsCollectionExcellent = self::getGoodsCollectionList($times, 'MAX'); // 获取订单金额优秀值
        $getGoodsCollectionList = self::getGoodsCollectionList($times, "COUNT"); // 获取订单金额列表

        return [
            'stageGoodsCollection' => $stageGoodsCollection['browseCount'],
            'growthRatio' => $growthRatio,
            'goodsCollectionAverage' => sprintf('%.2f', $getGoodsCollectionAverage[0]['browseCount']),
            'goodsCollectionExcellent' => $getGoodsCollectionExcellent[0]['browseCount'],
            'goodsCollectionList' => $getGoodsCollectionList,
        ];
    }

    // 验证时间参数
    private static function verificationTime($times)
    {
        if (empty($times)) {
            return ['status' => '5000', 'msg' => '参数为空！'];
        }
        if (!in_array($times, array_keys(self::$times))) {
            return ['status' => '5000', 'msg' => '参数传输错误！'];
        }
    }

    /**
     * 计算增长比率
     * @param $lastNumber 上阶段数值
     * @param $stageNumber 本阶段数值
     * @author zenghu [ 1427305236@qq.com ]
     * return float exp 20.00
     */
    private static function growthRate($lastNumber = 0, $stageNumber = 0)
    {
        // 定义增长比例
        $growthRatio = 0;

        // 计算比例
        if ($lastNumber == 0) {
            if ($stageNumber == 0) {
                $growthRatio = 0;
            } else {
                $growthRatio = 100;
            }
        } else {
            if ($stageNumber - $lastNumber <= 0) {
                //增长率为0
                $growthRatio = 0;
            } else {
                $growthRatio = sprintf('%.2f', (($stageNumber - $lastNumber) / $lastNumber));
            }
        }

        return $growthRatio;
    }

    /**
     * 获取商品访客数列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsVisitorsList($times = 'year', $dimension = 'COUNT')
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
    private static function getGoodsViewsList($times = 'year', $dimension = 'COUNT')
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
     * 获取支付订单人数据列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getOrderPayUserList($times = 'year', $dimension = 'COUNT', $map = '')
    {
        // 获取商品浏览量列表
        $getOrderPayUserList = goodsModel::getOrderPayUserList($times, $dimension, $map);

        // 商品获取访客列表时处理数据
        if ($dimension == 'COUNT') {
            $getOrderPayUserList = self::processingData($getOrderPayUserList, $times);
        }

        return $getOrderPayUserList;
    }

    /**
     * 统计区间内订单数数据列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getOrderNumberList($times = 'year', $dimension = 'COUNT')
    {
        // 获取数据列表
        $getOrderNumberList = goodsModel::getOrderNumberList($times, $dimension);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getOrderNumberList = self::processingData($getOrderNumberList, $times);
        }

        return $getOrderNumberList;
    }

    /*
     *统计区间内老用户数据列表
     *
     */

    private static function getUserNumberList($times = 'year', $dimension = 'COUNT')
    {
        // 获取数据列表
        $getUserNumberList = goodsModel::getUserNumberList($times, $dimension);
        // 处理数据
        if ($dimension == 'COUNT') {
            $getUserNumberListInfo = array();
            foreach ($getUserNumberList as $key => $value) {
                if (empty($getUserNumberListInfo[$value['dateFormat']])) {
                    $getUserNumberListInfo[$value['dateFormat']]['dateFormat'] = $value['dateFormat'];
                    $getUserNumberListInfo[$value['dateFormat']]['browseCount'] = $value['browseCount'];
                } else {
                    $getUserNumberListInfo[$value['dateFormat']]['browseCount'] += $value['browseCount'];
                }
            }
            $getUserNumberListcount = self::processingData($getUserNumberListInfo, $times);
            $getUserNumberList = [];
            foreach ($getUserNumberListcount as $key => $value) {
                $getUserNumberList[] = $value;
            }
        }
        return $getUserNumberList;
    }

    /**
     * 统计区间内支付转化率列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getPaymentConversionRateList($times = 'year')
    {
        // 获取订单支付人数列表
        $getOrderPayUserList = goodsModel::getOrderPayUserList($times);
        $getOrderPayUserList = self::processingData($getOrderPayUserList, $times);

        // 获取商品访客数数列表
        $getGoodsVisitorsList = Users::getGoodsVisitorsList($times);
        $getGoodsVisitorsList = self::processingData($getGoodsVisitorsList, $times);

        // 处理支付增长率
        $payRateList = [];
        foreach ($getOrderPayUserList as $key => $val) {
            foreach ($getGoodsVisitorsList as $k => $v) {
                if ($val['dateFormat'] == $v['dateFormat']) {
                    $payRatio = self::growthRate($v['browseCount'], $val['orderPayCount']);
                    array_push($payRateList, ['dateFormat' => $v['dateFormat'], 'browseCount' => $payRatio]);
                }
            }
        }

        return $payRateList;
    }

    /**
     * 计算支付转化率平均值
     * @param $data 处理的数据
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getPaymentConversionRateAverage($data = [])
    {
        if (!empty($data)) {
            $rateAvg = 0;
            foreach ($data as $val) {
                $rateAvg += $val['browseCount'];
            }

            return sprintf('%.2f', $rateAvg / count($data));
        }
    }

    /**
     * 计算支付转化率最大值
     * @param $data 处理的数据
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getPaymentConversionRateMax($data = [])
    {
        if (!empty($data)) {
            $max = [];
            foreach ($data as $val) {
                $max[] = $val['browseCount'];
            }

            return max($max);
        }
    }

    /**
     * 统计区间内订单金额数据列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getProfitList($times = 'year', $dimension = 'COUNT')
    {
        // 获取数据列表
        $getProfitList = goodsModel::getProfitList($times, $dimension);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getProfitList = self::processingData($getProfitList, $times);
        }

        return $getProfitList;
    }


    /**
     * 统计区间内订单金额数据列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getSalePriceList($times = 'year', $dimension = 'COUNT')
    {
        // 获取数据列表
        $getSalePriceList = goodsModel::getSalePriceList($times, $dimension);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getSalePriceList = self::processingData($getSalePriceList, $times);
        }

        return $getSalePriceList;
    }
    /**
     * 获取被访问商品数列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsVisitNumberList($times = 'year', $dimension = 'COUNT')
    {
        // 获取数据列表
        $getGoodsVisitNumberList = Users::getGoodsVisitNumberList($times, $dimension);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getGoodsVisitNumberList = self::processingData($getGoodsVisitNumberList, $times);
        }

        return $getGoodsVisitNumberList;
    }

    /**
     * 商品收藏用户数列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsCollectionList($times = 'year', $dimension = 'COUNT')
    {
        // 获取数据列表
        $getGoodsCollectionList = Users::getGoodsCollectionList($times, $dimension);

        // 处理数据
        if ($dimension == 'COUNT') {
            $getGoodsCollectionList = self::processingData($getGoodsCollectionList, $times);
        }

        return $getGoodsCollectionList;
    }

    /**
     * 获取商品访客数
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsVisitors($times = 'year', $map = '')
    {
        return Users::getGoodsVisitors($times, $map);
    }

    /**
     * 获取商品浏览数
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsViews($times = 'year', $map = '')
    {
        return Users::getGoodsViews($times, $map);
    }

    /*
     * 获取老买家数
     */
    private static function getOldUserNumber($times = 'year')
    {
        return goodsModel::getOldUserNumber($times);
    }

    /**
     * 获取支付订单数
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getOrderNumber($times = 'year', $map = '')
    {
        return goodsModel::getOrderNumber($times, $map);
    }

    /**
     * 获取总销售金额
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getSalePrice($times = 'year', $map = '')
    {
        return goodsModel::getSalePrice($times, $map);
    }

    /**
     * 获取订单支付人数
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getOrderPayUser($times = 'year', $map = '')
    {
        return goodsModel::getOrderPayUser($times, $map);
    }

    /**
     * 获取支付率
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getPaymentConversionRate($times = 'year', $map = '')
    {
        // 获取订单支付人数
        $orderPayUser = self::getOrderPayUser($times, $map);
        // 获取商品访客数
        $goodsVisitors = self::getGoodsVisitors($times, $map);

        $payRatio = 0;
        if ($orderPayUser['orderPayCount'] == 0) {
            if ($goodsVisitors['browseCount'] == 0) {
                $payRatio = 0;
            } else {
                $payRatio = 100;
            }
        } else {
            // 计算支付率
            $payRatio = sprintf('%.2f', ($goodsVisitors['browseCount'] / $orderPayUser['orderPayCount']));
        }

        // halt($payRatio);
        return $payRatio;
    }

    /**
     * 获取被访问商品数
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsVisitNumber($times = 'year', $map = '')
    {
        return Users::getGoodsVisitNumber($times, $map);
    }

    /**
     * 商品收藏用户数
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function getGoodsCollection($times = 'year', $map = '')
    {
        return Users::getGoodsCollection($times, $map);
    }

    /**
     * 商品成交件数列表
     * @author zhougs
     * @createTime 2020年12月5日17:54:16
     */
    private static function getGoodsDealNumberList($times = 'year', $dimension = "COUNT", $map = '')
    {
        $getGoodsDealNumberList = goodsModel::getGoodsDealNumberData($times, $dimension, $map);
        // 处理数据
        if ($dimension == 'COUNT') {
            $getGoodsDealNumberList = self::processingData($getGoodsDealNumberList, $times);
        }

        return $getGoodsDealNumberList;
    }

    /**
     * 商品成交件数
     * @author zhougs
     * @createTime 2020年12月5日17:54:16
     */
    private static function getGoodsDealNumber($times = 'year', $dimension = "COUNT", $map = '')
    {
        $getGoodsDealNumberList = goodsModel::getGoodsDealNumber($times, $map);
        /*        // 处理数据
                if ($dimension == 'COUNT') {
                    $getGoodsDealNumberList = self::processingData($getGoodsDealNumberList, $times);
                }*/

        return $getGoodsDealNumberList;
    }

    /**
     * 处理数据列表，根据时间维度，空数据追加处理
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function processingData($data = [], $times = 'year')
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

    public function get_category_list($cid)
    {
        $list = Category::where('pid = ' . $cid)->select()->toArray();
        if ($list) {
            foreach ($list as $key => $value) {
                $category_list[$value['id']] = Category::getChildsId($value['id']);
                array_unshift($category_list[$value['id']], $value['id']);
            }
            return $category_list;
        } else {
            return array();
        }
    }

    public function get_category_pid($cid, $list)
    {
        foreach ($list as $key => $value) {
            if (in_array($cid, $value)) {
                return $key;
            }
        }
        return 0;
    }

    /**
     * 获取商品/分类 统计列表
     * @author zhougs
     * @createTime 2020年12月5日18:28:06
     */
    public function getGoodsListData($times = "year", $goods_id = 0)
    {
//    public function getGoodsListDatas($times = "year"){
        $times = input("param.times") ?? 'year';
        $param = input("param.");
        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }
        $where = $where1 = "1=1";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
            $where1 .= " AND (o.create_time >= {$timeStr['startTime']} AND o.create_time <= {$timeStr['endTime']}) ";
        }

        if (isset($param['cid']) && $param['cid'] != 0) {
            $cid = Category::getChildsId($param['cid']);
            if (count($cid)) {
                $map1[] = ['g.cid', 'in', $cid];
            } else {
                $map1[] = ['g.cid', '=', $param['cid']];
            }
        } else {
            $param['cid'] = 0;
        }
        /*else{
            $map1[] = ['c.pid', '=', 0];
        }*/
        if ($param['goods_name']) {
            $map1[] = ['g.name', 'like', "%" . $param['goods_name'] . "%"];
        }
        if ($param['goods_id'] || $goods_id) {
            if ($goods_id) {
                $param['goods_id'] = $goods_id;
            }
            $map1[] = ['g.id', '=', $param['goods_id']];
        }

        $category_list = $this->get_category_list($param['cid']);


//        halt($map1);
        if (count(Category::getChildsId($param['cid'])) == 0) {
            $goods_groupby_cid_list = GoodsOriginal::alias('g')
                ->join('goods_category c', 'g.cid=c.id', 'left')
                ->field('g.*,c.name as c_name')
                ->where($map1)
//                ->where($where)
//                ->fetchSql(true)
                ->paginate();
        } else {
            // 查询分类下商品数量
            $goods_groupby_cid_list = GoodsOriginal::alias('g')
                ->join('goods_category c', 'g.cid=c.id', 'left')
                ->field('g.*,c.name as c_name')
                ->where($map1)
//                ->where($where)
//                ->fetchSql(true)
                ->select();
        }
//        halt($goods_groupby_cid_list);

        $goods = array();
        foreach ($goods_groupby_cid_list as $key => $value) {
            $goods[$value['id']]['goods_count'] = 1;
            $goods[$value['id']]['cid'] = $value['cid'];
            $goods[$value['id']]['c_pname'] = $value['name'];
            $goods[$value['id']]['c_name'] = $value['c_name'];
            if ($category_list) {
                $goods[$value['id']]['c_pid'] = $this->get_category_pid($value['cid'], $category_list);
            }
            // 获取商品浏览数
            // 获取商品浏览数
            $goods[$value['id']]['goods_views'] = Db::name('user_collection')
                ->where(['status' => 1, 'type' => 3, 'collect_id' => $value['id']])
                ->where($where)
                ->count();
            // 获取商品成交件数
            $goods[$value['id']]['goods_transactions'] = Db::name('order as o')->join('order_goods_list g', 'g.order_sn=o.order_sn', 'left')
                ->where('o.status > 2 and g.goods_id = ' . $value['id'])
//                ->where($where)
                ->sum('g.num');
            // 获取商品收藏数
            $goods[$value['id']]['goods_collection'] = Db::name('user_collection')
                ->where(['status' => 1, 'type' => 1, 'collect_id' => $value['id']])
                ->where($where)
                ->count();
            // 获取商品支付金额
            $goods[$value['id']]['goods_paymoney'] = Db::name('order as o')->join('order_goods_list g', 'g.order_sn=o.order_sn', 'left')
                ->where('o.pay_status = 1 and g.goods_id = ' . $value['id'])
                ->where($where1)
                ->sum('o.real_money');

            // 获取支付买家数
            $goods[$value['id']]['goods_payusers'] = Db::name('order as o')->join('order_goods_list g', 'g.order_sn=o.order_sn', 'left')
                ->where('o.pay_status = 1 and g.goods_id = ' . $value['id'])
                ->where($where1)
                ->group('o.user_id')
                ->count('o.user_id');
            // 获取支付订单数
            $goods[$value['id']]['goods_payorders'] = Db::name('order as o')->join('order_goods_list g', 'g.order_sn=o.order_sn', 'left')
                ->where('o.pay_status = 1 and g.goods_id = ' . $value['id'])
                ->where($where1)
                ->group('o.order_sn')
                ->count('o.order_sn');
            // 支付转化率
            $goods[$value['id']]['goods_payment_conversion_rate'] = self::growthRate($goods[$value['id']]['goods_views'], $goods[$value['id']]['goods_payorders']);
            // 支付客单价
            $goods[$value['id']]['goods_pay_customer_price'] = bcdiv($goods[$value['id']]['goods_paymoney'], $goods[$value['id']]['goods_payorders'], 2) ?? 0;
        }
        $returnData = array();
        if ($category_list) {
            foreach ($goods as $key => $value) {
                if (empty($returnData[$value['c_pid']])) {
                    $returnData[$value['c_pid']]['goods_count'] = 1;
                    $returnData[$value['c_pid']]['cid'] = $value['c_pid'];
                    $returnData[$value['c_pid']]['c_name'] = Category::where("id", $value['cid'])->value("name");
                    $returnData[$value['c_pid']]['goods_views'] = $value['goods_views'];
                    $returnData[$value['c_pid']]['goods_transactions'] = $value['goods_transactions'];
                    $returnData[$value['c_pid']]['goods_collection'] = $value['goods_collection'];
                    $returnData[$value['c_pid']]['goods_paymoney'] = $value['goods_paymoney'];
                    $returnData[$value['c_pid']]['goods_payusers'] = $value['goods_payusers'];
                    $returnData[$value['c_pid']]['goods_payorders'] = $value['goods_payorders'];
                } else {
                    $returnData[$value['c_pid']]['goods_count'] += 1;
                    $returnData[$value['c_pid']]['cid'] = $value['c_pid'];
                    $returnData[$value['c_pid']]['c_name'] = Category::where("id", $value['c_pid'])->value("name");
                    $returnData[$value['c_pid']]['goods_views'] = bcadd($returnData[$value['c_pid']]['goods_views'], $value['goods_views']);
                    $returnData[$value['c_pid']]['goods_transactions'] = bcadd($returnData[$value['c_pid']]['goods_transactions'], $value['goods_transactions']);
                    $returnData[$value['c_pid']]['goods_collection'] = bcadd($returnData[$value['c_pid']]['goods_collection'], $value['goods_collection']);
                    $returnData[$value['c_pid']]['goods_paymoney'] = bcadd($returnData[$value['c_pid']]['goods_paymoney'], $value['goods_paymoney']);
                    $returnData[$value['c_pid']]['goods_payusers'] = bcadd($returnData[$value['c_pid']]['goods_payusers'], $value['goods_payusers']);
                    $returnData[$value['c_pid']]['goods_payorders'] = bcadd($returnData[$value['c_pid']]['goods_payorders'], $value['goods_payorders']);
                }
//                $returnData[$value['c_pid']]['c_pname'] = Category::where("id",$value['c_pid'])->value("name");
                $returnData[$value['c_pid']]['goods_payment_conversion_rate'] = self::growthRate($returnData[$value['c_pid']]['goods_views'], $returnData[$value['c_pid']]['goods_payorders']);
                $returnData[$value['c_pid']]['goods_pay_customer_price'] = bcdiv($returnData[$value['c_pid']]['goods_paymoney'], $returnData[$value['c_pid']]['goods_payorders'], 2) ?? 0;
            }
        }
        //剔除数据库数据错误 分类删除 商品还在
        foreach ($returnData as $k => $v) {
            if (!$v['c_name']) {
                unset($returnData[$k]);
            }
        }
        if (empty($returnData)) {
//            var_dump($goods);die;
            return returnData(2000, 'ok', array_merge($goods));
        } else {
//            var_dump($returnData);die;
            return returnData(2000, 'ok', array_merge($returnData));
        }
    }


    /**
     * 商品销售榜单/ 商品购物车榜单-优化,由上边的方法getGoodsListData优化精简，速度提升
     */
    public function getGoodsListDataNew($times = "year", $goods_id = 0)
    {
        $times = input("param.times") ?? 'year';
        $param = input("param.");
        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }
        $where = $where1 = "1=1";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
            $where1 .= " AND (o.create_time >= {$timeStr['startTime']} AND o.create_time <= {$timeStr['endTime']}) ";
        }

        if ($param['goods_name']) {
            $map1[] = ['g.name', 'like', "%" . $param['goods_name'] . "%"];
        }
        if ($param['goods_id'] || $goods_id) {
            if ($goods_id) {
                $param['goods_id'] = $goods_id;
            }
            $map1[] = ['g.id', '=', $param['goods_id']];
        }

         // 查询分类下商品数量
         $goods = GoodsOriginal::alias('g')
         ->join('goods_category c', 'g.cid=c.id', 'left')
         ->field('g.*,c.name as c_name')
         ->where($map1)
         ->find();
       
        // 获取商品浏览数
        $goods['goods_views'] = Db::name('user_collection')
            ->where(['status' => 1, 'type' => 3, 'collect_id' => $goods['id']])
            ->where($where)
            ->count();
        // 获取商品成交件数
        $goods['goods_transactions'] = Db::name('order as o')->join('order_goods_list g', 'g.order_sn=o.order_sn', 'left')
            ->where('o.status > 2 and g.goods_id = ' . $goods['id'])
            ->sum('g.num');
        // 获取商品收藏数
        $goods['goods_collection'] = Db::name('user_collection')
            ->where(['status' => 1, 'type' => 1, 'collect_id' => $goods['id']])
            ->where($where)
            ->count();
        // 获取商品支付金额
        $goods['goods_paymoney'] = Db::name('order as o')->join('order_goods_list g', 'g.order_sn=o.order_sn', 'left')
            ->where('o.pay_status = 1 and g.goods_id = ' . $goods['id'])
            ->where($where1)
            ->sum('o.real_money');

        // 获取支付买家数
        $goods['goods_payusers'] = Db::name('order as o')->join('order_goods_list g', 'g.order_sn=o.order_sn', 'left')
            ->where('o.pay_status = 1 and g.goods_id = ' . $goods['id'])
            ->where($where1)
            ->group('o.user_id')
            ->count('o.user_id');
        // 获取支付订单数
        $goods['goods_payorders'] = Db::name('order as o')->join('order_goods_list g', 'g.order_sn=o.order_sn', 'left')
            ->where('o.pay_status = 1 and g.goods_id = ' . $goods['id'])
            ->where($where1)
            ->group('o.order_sn')
            ->count('o.order_sn');
        // 支付转化率
        $goods['goods_payment_conversion_rate'] = self::growthRate($goods['goods_views'], $goods['goods_payorders']);
        // 支付客单价
        $goods['goods_pay_customer_price'] = bcdiv($goods['goods_paymoney'], $goods['goods_payorders'], 2) ?? 0;

        //halt($goods);
        return $goods;
    }

    /**
     * @author zhougs
     * @createTime 2020年12月7日16:42:19
     * @return false|string
     */
    public function getTransactionData()
    {
//    public function getGoodsListData(){
        $times = input("param.times") ?? 'year';
        $param = input("param.");
        if (!empty($times)) {
            // 获取时间阶段（日 周 月 季 年）
            $timeStr = getTimeConversion($times, 'stamp');
        }
        $where = "1=1";
        if (!empty($timeStr)) {
            $where .= " AND (create_time >= {$timeStr['startTime']} AND create_time <= {$timeStr['endTime']}) ";
        }
        $map = "";
        if (isset($param['cid']) && $param['cid'] != 0) {
            $cid = Category::getChildsId($param['cid']);
            if (count($cid)) {
                $map1[] = ['g.cid', 'in', $cid];
            } else {
                $map1[] = ['g.cid', '=', $param['cid']];
            }
        } else {
            $map1[] = ['c.pid', '=', 0];
        }
        // 查询分类下商品数量
        $goods_groupby_cid_list = GoodsOriginal::alias('g')
            ->join('goods_category c', 'g.cid=c.id', 'left')
            ->field('g.*,c.name as c_name')
            ->where($map1)
            ->where($where)
            ->column("g.id");
        if (count($goods_groupby_cid_list)) {
            $ids = trim(implode(",", $goods_groupby_cid_list), ',');
            $map = " in (" . $ids . ")";
        }
        //支付买家数列表
        $getOrderPayUserList = self::getOrderPayUserList($times, 'COUNT', $map);
        // 获取订单金额列表
        $getSalePriceList = self::getSalePriceList($times, 'COUNT', $map);
        // 获取商品支付订单人数列表
        $getOrderNumberList = self::getOrderNumberList($times, 'COUNT', $map);
        // 获取商品收藏量列表
        $getGoodsCollectionList = self::getGoodsCollectionList($times, 'COUNT', $map);
        // 获取商品浏览量列表
        $getGoodsViewsList = self::getGoodsViewsList($times, 'COUNT', $map);
        // 获取商品成交件数列表
        $getGoodsDealNumber = self::getGoodsDealNumberList($times, 'COUNT', $map);
        $returnData = [
            "getOrderPayUserList" => $getOrderPayUserList,
            "getSalePriceList" => $getSalePriceList,
            "getOrderNumberList" => $getOrderNumberList,
            "getGoodsCollectionList" => $getGoodsCollectionList,
            "getGoodsViewsList" => $getGoodsViewsList,
            "getGoodsDealNumber" => $getGoodsDealNumber,
        ];
        return returnData(2000, 'ok', $returnData);
    }

    //输出商品统计页面
    public function confluenceGoods()
    {
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');
        return $this->fetch('confluenceGoods');
    }

    //商品分类
    public function getGoodsCaregory()
    {
        $cid = input("param.cid");
        if (isset($cid) && $cid != 0) {
            $where[] = ["pid", "=", $cid];
        } else {
            $where[] = ["pid", "=", 0];
        }
        $arr = Category::where($where)->field("id,name")->select();
//        halt($arr);
        return returnData(2000, 'ok', $arr);
    }
}
