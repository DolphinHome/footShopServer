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
use app\statistics\model\Goods;
use app\statistics\model\Users;
use app\user\model\Certified as CertifiedModel;
use app\operation\model\Suggestions as SuggestionsModel;
use app\operation\model\Article as ArticleModel;

use think\Db;

class Index extends Base
{
    // 定义时间区间
    private $times = array(
        'day' => 'yesterday', // 日-昨日
        'week' => 'lastweek', // 周-上周
        'month' => 'lastmonth', // 月-上个月
        'quarter' => 'lastquarter', // 季-上个季度
        'year' => 'lastyear', // 年-去年
    );

    // 验证时间参数
    private function verificationTime($times)
    {
        if (empty($times)) {
            return ['status' => '5000', 'msg' => lang('参数为空')];
        }
        if (!in_array($times, array_keys($this->times))) {
            return ['status' => '5000', 'msg' => lang('参数传输错误')];
        }
    }

    /**
     * 数据首页统计
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function index()
    {


        if (input('param.updateUpload')) {
            // 更新 upload 表中 图片路径
            $where = array();
            //$where[] = ['create_time','<',strtotime('2020-04-01')];
            $where[] = ['thumb', 'like', "https://btj.yuanjiwei.cn/%"];
            $count = Db::name('upload')->where($where)->count();
            dump($count);

            $list = Db::name('upload')->where($where)->order('id DESC')->limit(100)->select();
            foreach ($list as $v) {
                $data = array();
                $data['id'] = $v['id'];
                $data['thumb'] = str_replace("https://btj.yuanjiwei.cn/", "http://zbphp.zhongbenzx.com/", $v['thumb']);
                if (Db::name('upload')->update($data)) {
                    dump($v['id'] . '：更新成功');
                } else {
                    dump($v['id'] . '：更新失败');
                }
            }
            // dump($list);
            exit;

            //return $this->fetch('utils/auto_reload');
        }


        // 交易数据
        $getTransactionData = $this->getTransactionData('month');

        // 待处理数据
        $getPendingData = $this->getPendingData();

        // 订单数据统计
        $getOrderData = $this->getOrderData('month');

        // 消费区域查询
        $getConsumptionArea = $this->getConsumptionArea('month');

        // 单品销售榜单
        $getGoodsSalesList = $this->getGoodsSalesList('month');

        // 单品加购榜单
        $getGoodsCartList = $this->getGoodsCartList();

        // 用户注册来源统计
        $getUserSource = $this->getUserSource('month');

        // 用户分布区域统计
        $getUserRegion = $this->getUserRegion('month');

        // 会员消费排行
        $getUserConsumptionList = $this->getUserConsumptionList('month');

        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');
        $this->assign('getTransactionData', $getTransactionData);
        $this->assign('getPendingData', $getPendingData);
        $this->assign('getOrderData', $getOrderData);
        $this->assign('getConsumptionArea', $getConsumptionArea);
        $this->assign('getGoodsSalesList', $getGoodsSalesList);
        $this->assign('getGoodsCartList', $getGoodsCartList);
        $this->assign('getUserSource', $getUserSource);
        $this->assign('getUserRegion', $getUserRegion);
        $this->assign('getUserConsumptionList', $getUserConsumptionList);

        return $this->fetch();
    }

    /**
     * 数据首页统计--交易数据
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getTransactionData($times = 'year')
    {
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }

        // 参数校验
        $this->verificationTime($times);

        // 商城数据统计信息
        $getShopMallData = $this->getShopMallData($times);  // 数据已核算

        $getNewUsers = $this->getNewUsers($times);


        // halt($getShopMallData);
        return returnData('2000', lang('数据返回成功'), ['shop_mall_date' => $getShopMallData, 'new_user_date' => $getNewUsers]);
    }

    /**
     * 数据首页统计--待处理数据
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getPendingData()
    {
        // 获取待支付订单数据
        $getPayingCount = Goods::getPendingData('paying');

        // 获取待发货订单数据
        $getDeliveringCount = Goods::getPendingData('delivering');

        // 获取售后订单数
        $afterSaleCount = Goods::getAfterSaleCount();

        // 获取库存预警数量
        $inventory = 10; // 定义库存数量
        $inventoryEarlyWarning = Goods::getInventoryEarlyWarning($inventory);

        // 获取用户咨询数量
        $userConsultationCount = Users::getUserConsultation();

        // 获取提现申请数
        $withdrawalApplyCount = Users::getWithdrawalApply();

        // 缺货登记 数量  
        $goods_outofstock = Db::name('goods_outofstock')->where(['status' => 0])->count();
        // 实名认证数量   user/certified/realname.html 
        $user_certified = CertifiedModel::where(array('status' => 0))->count();

        // 文章举报数量    /operation/article/reportlist.html?title=&user_name=&report_type=-1&status=0 

        $map[] = ['operation_article_report.status', '=', 0];
        $report_count = ArticleModel::getReportList($map);


        $report_count = count($report_count);
        // 投诉建议数量     /operation/suggestions/index.html?type=-1&contact=&is_replay=0
        $suggestions_count = SuggestionsModel::where(array('is_replay' => 0, 'type' => '-1'))->count();
        return returnData('2000', lang('数据返回成功'), [
            'paying_count' => $getPayingCount['orderCounte'],
            'delivering_count' => $getDeliveringCount['orderCounte'],
            'after_sale_count' => $afterSaleCount['orderCounte'],
            'inventory_early_warning' => $inventoryEarlyWarning,
            'withdrawal_apply_count' => $withdrawalApplyCount['withdrawalCount'],
            'user_consultation_count' => empty($userConsultationCount['userConsultationCount']) ? 0 : $userConsultationCount['userConsultationCount'],
            'user_certified' => $user_certified,
            'report_count' => $report_count,
            'suggestions_count' => $suggestions_count,
            'goods_outofstock' => $goods_outofstock
        ]);
    }

    /**
     * 数据首页统计--订单数据统计
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getOrderData($times = 'year')
    {
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }

        // 参数校验
        $this->verificationTime($times);

        // 获取订单数据
        $orderData = Goods::getOrderData($times);
        $arrDate = [];
        $weekArr = ['1' => '星期1', '2' => '星期2', '3' => '星期3', '4' => '星期4', '5' => '星期5', '6' => '星期6', '0' => '星期7'];
        foreach ($orderData as $key => $val) {
            switch ($times) {
                case 'lastyear':
                case 'year':
                case 'quarter':
                    $orderData[$key]['dateFormat'] = $val['dateFormat'] . '月份';
                    array_push($arrDate, $val['dateFormat']);
                    break;

                case 'month':
                    $orderData[$key]['dateFormat'] = $val['dateFormat'] . '号';
                    array_push($arrDate, $val['dateFormat']);
                    break;

                case 'week':
                    $orderData[$key]['dateFormat'] = $weekArr[$val['dateFormat']];
                    array_push($arrDate, $val['dateFormat']);
                    break;

                case 'day':
                    if ($val['dateFormat'] == '00') {
                        $orderData[$key]['dateFormat'] = '24' . '时';
                        array_push($arrDate, '24');
                    } else {
                        $orderData[$key]['dateFormat'] = $val['dateFormat'] . '时';
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
                        $dateFormat = sprintf('%02d', $i) . '月份';
                        array_push($orderData, ['dateFormat' => $dateFormat, 'orderCount' => 0, 'orderPrice' => 0, 'profitPrice' => 0]);
                    }
                }
                break;
            case 'quarter':
                $season = ceil($arrDate['0'] / 3); // 获取季度
                $start = $season * 3 - 2;
                $end = $season * 3;
                for ($i = $start; $i <= $end; $i++) {
                    if (!in_array($i, $arrDate)) {
                        $dateFormat = sprintf('%02d', $i) . '月份';
                        array_push($orderData, ['dateFormat' => $dateFormat, 'orderCount' => 0, 'orderPrice' => 0, 'profitPrice' => 0]);
                    }
                }
                break;
            case 'month':
                $end = date("t");
                for ($i = 1; $i <= $end; $i++) {
                    if (!in_array($i, $arrDate)) {
                        $dateFormat = sprintf('%02d', $i) . '号';
                        array_push($orderData, ['dateFormat' => $dateFormat, 'orderCount' => 0, 'orderPrice' => 0, 'profitPrice' => 0]);
                    }
                }
                break;
            case 'week': // 周
                for ($i = 0; $i <= 6; $i++) {
                    if (!in_array($i, $arrDate)) {
                        $dateFormat = $weekArr[$i];
                        array_push($orderData, ['dateFormat' => $dateFormat, 'orderCount' => 0, 'orderPrice' => 0, 'profitPrice' => 0]);
                    }
                }
                break;
            case 'day': // 日
                for ($i = 1; $i <= 24; $i++) {
                    if (!in_array($i, $arrDate)) {
                        $dateFormat = sprintf('%02d', $i) . '时';
                        array_push($orderData, ['dateFormat' => $dateFormat, 'orderCount' => 0, 'orderPrice' => 0, 'profitPrice' => 0]);
                    }
                }
                break;

            default:
                # code...
                break;
        }
        // 数据排序
        $dateFormatSort = array_column($orderData, 'dateFormat');
        array_multisort($dateFormatSort, SORT_ASC, $orderData);

        return returnData('2000', lang('数据返回成功'), $orderData);
    }

    /**
     * 数据首页统计--消费区域查询
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getConsumptionArea($times = 'year')
    {
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }

        // 参数校验
        $this->verificationTime($times);

        // 获取总消费金额
        $orderTotalPrice = Goods::getSalePrice();

        // 获取区域消费金额
        $orderRateSum = 0;
        $orderPriceSum = 0;
        $consumptionArea = Goods::getConsumptionArea($times);
        if (!empty($consumptionArea)) {
            foreach ($consumptionArea as $key => $val) {
                // 计算销售占比
                $orderRate = sprintf('%.2f', ($val['orderPrice'] / $orderTotalPrice['orderPrice']) * 100);
                $orderRateSum += $orderRate; // 计算比率和
                $orderPriceSum += $val['orderPrice']; // 计算总消费金额
                $consumptionArea[$key]['orderRate'] = $orderRate;
            }
        }

        // 其他消费
        array_push($consumptionArea, [
            'province' => '其他',
            'orderPrice' => $orderTotalPrice['orderPrice'] - $orderPriceSum,
            'orderRate' => number_format(100 - $orderRateSum, 2),
        ]);

        return returnData('2000', lang('数据返回成功'), $consumptionArea);
    }

    /**
     * 数据首页统计--单品销售榜单
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getGoodsSalesList($times = 'year')
    {
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }

        // 参数校验
        $this->verificationTime($times);

        // 获取单品销售榜单
        $orderTotalPrice = Goods::getGoodsSalesList($times);

        return returnData('2000', lang('数据返回成功'), $orderTotalPrice);
    }

    /**
     * 数据首页统计--单品购物车榜单
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getGoodsCartList()
    {
        // 获取单品销售榜单
        $goodsCartList = Goods::getGoodsCartList();

        return returnData('2000', lang('数据返回成功'), $goodsCartList);
    }

    /**
     * 数据首页统计--用户注册来源统计
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getUserSource($times = 'year')
    {
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }

        // 参数校验
        $this->verificationTime($times);

        // 定义用户注册来源
        $userSourceSign = [
            'IOS'        => lang('苹果').'APP',
            'Android'    => lang('安卓').'APP',
            'Web'        => lang('Web端'),
            'Wechat'     => lang('微信平台'),
            'Alipay'     => lang('支付宝平台'),
            'Baidu'      => lang('百度平台'),
            'ByteBounce' => lang('字节跳动平台'),
            'QQ'         => 'QQ'.lang('平台'),
            '360'        => '360'.lang('平台'),
            'other'      => lang('其他平台'),
            ''           => lang('其他平台')
        ];
        // 获取用户注册来源统计
        $userSource = Users::getUserSource($times);
        foreach ($userSource as $key => $val) {
            $userSource[$key]['user_source'] = $userSourceSign[$val['user_source']];
        }
        // halt($userSource);
        return returnData('2000', lang('数据返回成功'), $userSource);
    }

    /**
     * 数据首页统计--用户分布区域统计
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getUserRegion($times = 'year')
    {
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }

        // 参数校验
        $this->verificationTime($times);

        // 获取用户分布区域统计
        $userRegion = Users::getUserRegion($times);

        return returnData('2000', lang('数据返回成功'), $userRegion);
    }

    /**
     * 数据首页统计--会员消费排行
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getUserConsumptionList($times = 'year')
    {
        if ($this->request->isPost()) {
            // 接收参数
            $requests = $this->request->post();
            $times = $requests['times'];
        }
        // 参数校验
        $this->verificationTime($times);

        // 获取会员消费排行
        $userConsumptionList = Users::getUserConsumptionList($times);

        $userConsumptionList = $this->arraySort($userConsumptionList, 'user_consumption_price', SORT_ASC, 8);
        return returnData('2000', lang('数据返回成功'), $userConsumptionList);
    }

    public function arraySort($array, $keys, $sort = SORT_DESC, $count = 8)
    {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);


        return array_slice($array, -8, 8);
    }

    /**
     * 商城数据统计模块
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月2日16:00:21
     * @return array
     */
    private function getShopMallData($times)
    {
        // 商城销售额统计
        $goodsSalePrice = $this->getSalePrice($times);

        // 商城订单数统计
        $goodsOrderNumber = $this->getOrderNumber($times);

        // 商城盈利额统计
        $goodsProfit = $this->getProfit($times);

        // 商城利润率统计：利润率=（售价-成本）/成本 X100%
        $goodsProfitRate = $this->getProfitRate($times);
        //halt($goodsProfitRate);
        // 计算本阶段商城客单价
        if ($goodsOrderNumber['stage_order_number'] == 0) {
            $orderPriceAverage = 0;
        } else {
            $orderPriceAverage = $goodsSalePrice['stage_order_price'] / $goodsOrderNumber['stage_order_number'];
        }
        // 计算上一阶段商城客单价
        $lastOrderPriceAverage = 0;
        if ($goodsOrderNumber['last_stage_order_number'] != 0) {
            $lastOrderPriceAverage = $goodsSalePrice['last_stage_order_price'] / $goodsOrderNumber['last_stage_order_number'];
        }
        // 计算客单价增长比例
        $growthRatio = 0;
        if ($lastOrderPriceAverage == 0) {
            if ($orderPriceAverage == 0) {
                $growthRatio = 0;
            } else {
                $growthRatio = 100;
            }
        } else {
            $growthRatio = sprintf('%.2f', ($orderPriceAverage - $lastOrderPriceAverage) / $lastOrderPriceAverage);
        }
        $averageOrder = [
            'average_order_price' => sprintf('%.2f', $orderPriceAverage),
            'average_order_price_growth_ratio' => $growthRatio,
        ];

        return [
            'goods_sale_price' => $goodsSalePrice,
            'goods_order_number' => $goodsOrderNumber,
            'goods_profit' => $goodsProfit,
            'goods_profit_rate' => $goodsProfitRate,
            'average_order' => $averageOrder,
        ];
    }

    /**
     * 销售额统计
     * @param $time 所取时间阶段标识
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月2日16:00:21
     * @return array
     */
    private function getSalePrice($times)
    {
        // 统计总销售金额
        $orderTotalPrice = Goods::getSalePrice();

        // 本阶段销售总额
        $stageOrderPrice = Goods::getSalePrice($times);

        // halt($times);
        // 上一阶段销售总额
        // $lastTimes = $this->times[$times];
        $lastTimes = 'lastyear.' . $times;

        // halt($lastTimes);
        $lastStageOrderPrice = Goods::getSalePrice($lastTimes);
        // halt($lastStageOrderPrice);
        // 计算销售额增长比例
        $growthRatio = 0;
        if ($lastStageOrderPrice['orderPrice'] == 0) {
            if ($stageOrderPrice['orderPrice'] == 0) {
                $growthRatio = 0;
            } else {
                $growthRatio = 100;
            }
        } else {
            $growthRatio = sprintf('%.2f', ($stageOrderPrice['orderPrice'] - $lastStageOrderPrice['orderPrice']) / $lastStageOrderPrice['orderPrice']);


        }
        // halt($growthRatio);
        return [
            'order_total_price' => sprintf('%.2f', $orderTotalPrice['orderPrice']), // 总销售额
            'stage_order_price' => sprintf('%.2f', $stageOrderPrice['orderPrice']),  // 本阶段销售额
            'last_stage_order_price' => sprintf('%.2f', $lastStageOrderPrice['orderPrice']),  // 上一阶段销售额
            'order_price_growth_ratio' => $growthRatio // 增长率
        ];
    }

    /**
     * 订单数统计
     * @param $time 所取时间阶段标识
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月2日16:00:21
     * @return array
     */
    private function getOrderNumber($times)
    {
        // 统计总订单数
        $orderTotalNumber = Goods::getOrderNumber();

        // 本阶段订单数
        $stageOrderNumber = Goods::getOrderNumber($times);

        // 上一阶段订单数
        $lastTimes = 'lastyear.' . $times;
        $lastStageOrderNumber = Goods::getOrderNumber($lastTimes);

        // 计算订单数增长比例
        $growthRatio = 0;
        if ($lastStageOrderNumber['orderCount'] == 0) {
            if ($stageOrderNumber['orderCount'] == 0) {
                $growthRatio = 0;
            } else {
                $growthRatio = 100;
            }
        } else {
            $growthRatio = sprintf('%.2f', ($stageOrderNumber['orderCount'] - $lastStageOrderNumber['orderCount']) / $lastStageOrderNumber['orderCount']);
        }

        return [
            'order_total_number' => $orderTotalNumber['orderCount'], // 总销售订单数
            'stage_order_number' => $stageOrderNumber['orderCount'],  // 本阶段销售订单数
            'last_stage_order_number' => $lastStageOrderNumber['orderCount'],  // 上一阶段销售订单数
            'order_number_growth_ratio' => $growthRatio // 增长率
        ];
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
        $totalProfitPrice = Goods::getProfit();

        // 本阶段盈利总额
        $stageProfitPrice = Goods::getProfit($times);

        // 上一阶段盈利总额
        $lastTimes = 'lastyear.' . $times;
        $lastStageProfitPrice = Goods::getProfit($lastTimes);

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
        // halt($lastStageProfitPrice);
        return [
            'total_profit_price' => sprintf('%.2f', $totalProfitPrice['profitPrice']), // 总盈利额
            'stage_profit_price' => sprintf('%.2f', $stageProfitPrice['profitPrice']),  // 本阶段盈利额
            'profit_price_growth_ratio' => $growthRatio // 增长率
        ];
    }

    /**
     * 利润率统计
     * @param $time 所取时间阶段标识
     * @since 2021年6月23日16:00:21
     * @return array
     */
    private function getProfitRate($times)
    {
        // 统计总利润率
        $totalProfitPrice = Goods::getProfit();
        $totalCostPrice = Goods::getCostPrice();
        if ($totalCostPrice['costPrice']>0){
            $totalProfitRate =  sprintf('%.2f', $totalProfitPrice['profitPrice']/$totalCostPrice['costPrice']*100) ;
        } else {
            $totalProfitRate = '-';
        }
        

        // 本阶段利润率
        $stageProfitPrice = Goods::getProfit($times);
        $stageCostPrice = Goods::getCostPrice($times);
        if ($stageCostPrice['costPrice'] > 0 ) {
            $stageProfitRate =  sprintf('%.2f', $stageProfitPrice['profitPrice']/$stageCostPrice['costPrice']*100) ;
        } else {
            $stageProfitRate = '-';
        }

        // 上一阶段利润率
        $lastTimes = 'lastyear.' . $times;
        $lastStageProfitPrice = Goods::getProfit($lastTimes);
        $lastStageCostPrice = Goods::getCostPrice($lastTimes);
        if ($lastStageCostPrice['costPrice'] > 0 ) {
            $lastStageProfitRate =  sprintf('%.2f', $lastStageProfitPrice['profitPrice']/$lastStageCostPrice['costPrice']*100) ;
        } else {
            $lastStageProfitRate = '-';
        }

        // 计算盈利额率长比例
        $growthRatio = 0;
        if ($lastStageProfitRate == '-') {
            if ($stageProfitRate == '-') {
                $growthRatio = 0;
            } else {
                $growthRatio = 100;
            }
        } else {
            $growthRatio = sprintf('%.2f', ($stageProfitRate - $lastStageProfitRate) / $lastStageProfitRate);
        }
        //halt($stageProfitRate);
        return [
            'total_profit_price' => $totalProfitRate, // 利润率
            'stage_profit_price' => $stageProfitRate,  // 本阶段盈利额
            'profit_price_growth_ratio' => $growthRatio // 增长率
        ];
    }



    /**
     * 新增用户统计
     * @param $time 所取时间阶段标识
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年11月2日16:00:21
     * @return array
     */
    private function getNewUsers($times)
    {
        // 统计总会员数
        $totalNewUsers = Users::getNewUsers();

        // 本阶段新增会员数
        $stageNewUsers = Users::getNewUsers($times);

        // 上一阶段新增会员数
        $lastTimes = 'lastyear.' . $times;
        $lastStageNewUsers = Users::getNewUsers($lastTimes);

        // 计算新增会员数增长比例
        $growthRatio = 0;
        if ($lastStageNewUsers['userCount'] == 0) {
            if ($stageNewUsers['userCount'] == 0) {
                $growthRatio = 0;
            } else {
                $growthRatio = 100;
            }
        } else {
            $growthRatio = sprintf('%.2f', ($stageNewUsers['userCount'] - $lastStageNewUsers['userCount']) / $lastStageNewUsers['userCount']);
        }

        return [
            'total_new_users' => $totalNewUsers['userCount'], // 总会员数
            'stage_new_users' => $stageNewUsers['userCount'],  // 本阶段新增会员数
            'last_stage_new_users' => $lastStageNewUsers['userCount'],  // 上一阶段新增会员数
            'new_users_growth_ratio' => $growthRatio // 增长率
        ];
    }

}
