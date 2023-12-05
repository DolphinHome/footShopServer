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
use service\ApiReturn;
use think\Db;
use app\statistics\model\Finance as FinanceModel;

class User extends Base
{
    // 定义时间区间
    private $times = array(
        'day' => 'yesterday', // 日-昨日
        'week' => 'lastweek', // 周-上周
        'month' => 'lastmonth', // 月-上个月
        'quarter' => 'lastquarter', // 季-上个季度
        'year' => 'lastyear', // 年-去年
    );

    /*
     * 会员统计列表
     *
     */
    public function index()
    {
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');
        return $this->fetch('list');
    }

    /*
     * 获取会员数据
     *
     */
    public function userData()
    {
        $data = request()->param();
        $res = Users::userData($data);
        return ApiReturn::r(1, $res, 'ok');
    }


    /*
     * 会员详情
     *
     */
    public function detail()
    {
        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');
        return $this->fetch();
    }

    /*
     * 获取会员基本信息
     *
     */

    public function getDetail()
    {
        $id = request()->param("id", 0);
        $user_info = Users::detail($id);
        return ApiReturn::r(1, $user_info, 'ok');
    }

    /*
     * 会员详情数据概览
     *
     */
    public function userDetailData()
    {
        $times = request()->param('times', 'year');
        $user_id = request()->param('id', 0);

        //下单数量
        $userOrder = Users::userOrder($times, $user_id);
        //收藏数量
        $userCollect = Users::userCollect($times, $user_id);
        //购物车
        $userCart = Users::userCart($times, $user_id);
        //退款数量
        $userRefund = Users::userRefund($times, $user_id);
        //评价数量
        $userComment = Users::userComment($times, $user_id);
        //总浏览量
        $userVisit = Users::userVisit($times, $user_id);
        $data = [
            'userOrder' => $userOrder,
            'userCollect' => $userCollect,
            'userCart' => $userCart,
            'userRefund' => $userRefund,
            'userComment' => $userComment,
            'userVisit' => $userVisit
        ];
        return ApiReturn::r(1, $data, 'ok');
    }

    /*
     * 会员详情列表
     */
    public function userDetailList()
    {
        $times = request()->param('times', 'year');
        $user_id = request()->param('id', 0);
        $type = request()->param('type', 'order');
        $res = Users::userDetailList($times, $user_id, $type);
        return ApiReturn::r(1, $res, 'ok');
    }

    /*
     * 会员等级
     *
     */
    public function userLevel()
    {
        $data = Users::userLevel();
        return ApiReturn::r(1, $data, 'ok');
    }

    /*
     * 会员列表
     *
     */
    public function userList()
    {
        $data = request()->param();
        $res = Users::userList($data);
        return ApiReturn::r(1, $res, 'ok');
    }


    /**
     * 资金中心
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function capitalCenter()
    {
        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');

        return $this->fetch();
    }

    /**
     * 订单详情
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function orderDetail()
    {
        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');

        return $this->fetch();
    }

    /**
     * 对账中心
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function reconciliationCenter()
    {
        $map = $this->_searchCenter();
        $getCheckCenterData = returnData(2000, 'ok', FinanceModel::getCheckCenterData($map));
        $this->assign('getCheckCenterData', $getCheckCenterData);
        $isExport = request()->param('isExport', 0);
        //导出excel
        if ($isExport == 1) {
            $getCheckCenterData = FinanceModel::getCheckCenterData($map, 'o.aid desc', false);
            $excelData = $_excelData = [];
            foreach ($getCheckCenterData as $v) {
                $excelData[] = [
                    'orderSn' => $v['orderSn'],
                    'transactionNo' => $v['transactionNo'],
                    'orderAmount' => $v['orderAmount'],
                    'payType' => $v['payType'],
                    'isFinish' => $v['isFinish'],
                    'createTime' => $v['createTime'],
                ];
            }
            $_excelData[0]['list'] = $excelData;
            $xlsName = lang('退款数据').'-' . date("Y-m-d H:i:s", time());
            $xlsCell = [
                ['orderSn', lang('订单号')],
                ['transactionNo', lang('交易单号')],
                ['orderAmount', lang('订单金额')],
                ['payType', lang('支付方式')],
                ['isFinish', lang('是否完成对账')],
                ['createTime', lang('交易时间')],
            ];
            $excelData = array_values($_excelData);
            $this->exportExcel($xlsName, $xlsCell, $excelData);
        }

        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');

        return $this->fetch();
    }

    /*
     * 获取对账单
     *
     */
    public function getCheckCenterData()
    {
        $map = $this->_searchCenter();
        return returnData(2000, 'ok', FinanceModel::getCheckCenterData($map));
    }

    /*
     * 对账中心搜索头
     *
     */
    public function _searchCenter()
    {
        $param = request()->param();
//        echo "<pre>";
//        print_r($param);
//        die;
        $map = [];
        if (isset($param['orderSn']) && !empty($param['orderSn'])) {
            $map[] = ['o.order_sn', '=', $param['orderSn']];
        }
        if (isset($param['startTime']) && !empty($param['startTime'])) {
            $startTime = strtotime($param['startTime']);
            $map[] = ['o.create_time', '>=', $startTime];
        }
        if (isset($param['endTime']) && !empty($param['endTime'])) {
            $map[] = ['o.create_time', '=', strtotime($param['endTime'])];
        }
        if (isset($param['payType']) && $param['payType'] != -1) {
            $map[] = ['o.pay_type', '=', $param['payType']];
        }
        $map[] = ['o.pay_status', '=', 1];
        return $map;
    }

    /**
     * 退款
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function refund()
    {
        $map = $this->_searchRefund();
        $getRefundRecord = returnData(2000, 'ok', FinanceModel::getRefundRecord($map));
        $this->assign('getRefundRecord', $getRefundRecord);
        $isExport = request()->param('isExport', 0);
        //导出excel
        if ($isExport == 1) {
            $getRefundRecord = FinanceModel::getRefundRecord($map, 'or.id desc', false);
            $excelData = $_excelData = [];
            foreach ($getRefundRecord as $v) {
                $excelData[] = [
                    'orderSn' => $v['orderSn'],
                    'consigneeName' => $v['consigneeName'],
                    'transactionNo' => $v['transactionNo'],
                    'consigneePhone' => $v['consigneePhone'],
                    'consigneeAdress' => $v['consigneeAdress'],
                    'orderAmount' => $v['orderAmount'],
                    'realAmount' => $v['realAmount'],
                    'discountsAmount' => $v['discountsAmount'],
                    'payStatus' => $v['payStatus'],
                    'payType' => $v['payType'],
                    'orderStatus' => $v['orderStatus'],
                    'refundCause' => $v['refundCause'],
                    'refundStatus' => $v['refundStatus'],
                    'refundAmount' => $v['refundAmount'],
                    'applyRefundTime' => $v['applyRefundTime'],
                ];
            }
            $_excelData[0]['list'] = $excelData;
            $xlsName = lang('退款数据').'-' . date("Y-m-d H:i:s", time());
            $xlsCell = [
                ['orderSn', lang('订单号')],
                ['consigneeName', lang('收货人姓名')],
                ['transactionNo', lang('交易单号')],
                ['consigneePhone', lang('收货人手机号')],
                ['consigneeAdress', '收货人地址	'],
                ['orderAmount', lang('订单金额')],
                ['realAmount', lang('实付金额')],
                ['discountsAmount', lang('优惠金额')],
                ['payStatus', lang('支付状态')],
                ['payType', lang('支付方式')],
                ['orderStatus', lang('订单状态')],
                ['refundCause', lang('退款原因')],
                ['refundStatus', lang('退货状态')],
                ['refundAmount', lang('退款金额')],
                ['applyRefundTime', lang('申请退款时间')],
            ];
            $excelData = array_values($_excelData);
            $this->exportExcel($xlsName, $xlsCell, $excelData);
        }
        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');

        return $this->fetch();
    }

    /*
     *
     *获取退款
     *
     */
    public function getRefundRecord()
    {
        $map = $this->_searchRefund();
        return returnData(2000, 'ok', FinanceModel::getRefundRecord($map));
    }

    /*
     * 退款单详情
     *
     */
    public function getRefundRecordDetail()
    {
        $orderSn = request()->param("orderSn", 0);
        return returnData(2000, 'ok', FinanceModel::getRefundRecordDetail($orderSn));
    }

    /*
     * 退款搜索头
     *
     */

    public function _searchRefund()
    {
        $param = request()->param();
//        echo "<pre>";
//        print_r($param);
//        die;
        $map = [];
        if (isset($param['orderSn']) && !empty($param['orderSn'])) {
            $map[] = ['o.order_sn', '=', $param['orderSn']];
        }
        if (isset($param['userPhone']) && !empty($param['userPhone'])) {
            $map[] = ['og.receiver_mobile', '=', $param['userPhone']];
        }
        if (isset($param['startTime']) && !empty($param['startTime'])) {
            $startTime = strtotime($param['startTime']);
            $map[] = ['o.create_time', '>=', $startTime];
        }
        if (isset($param['endTime']) && !empty($param['endTime'])) {
            $map[] = ['o.create_time', '=', strtotime($param['endTime'])];
        }
        if (isset($param['payStatus']) && $param['payStatus'] != -1) {
            $map[] = ['o.pay_status', '=', $param['payStatus']];
        }
        return $map;
    }

    /**
     * 退款失败列表
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function refundLost()
    {
        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');

        return $this->fetch();
    }

    /**
     * 交易数据
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function transaction()
    {
        $map = $this->_serach();
        $getPayData = returnData(2000, 'ok', FinanceModel::payData($map));
        $this->assign('getPayData', $getPayData);
        $isExport = request()->param('isExport', 0);
        //导出excel
        if ($isExport == 1) {
            $getPayData = FinanceModel::payData($map, 'o.aid desc', false);
            $excelData = $_excelData = [];
            foreach ($getPayData as $v) {
                $excelData[] = [
                    'orderSn' => $v['orderSn'],
                    'consigneeName' => $v['consigneeName'],
                    'transactionNo' => $v['transactionNo'],
                    'consigneePhone' => $v['consigneePhone'],
                    'consigneeAdress' => $v['consigneeAdress'],
                    'orderAmount' => $v['orderAmount'],
                    'realAmount' => $v['realAmount'],
                    'discountsAmount' => $v['discountsAmount'],
                    'payStatus' => $v['payStatus'],
                    'payType' => $v['payType'],
                    'orderStatus' => $v['orderStatus'],
                    'createTime' => $v['createTime'],
                ];
            }
            $_excelData[0]['list'] = $excelData;
            $xlsName = lang('交易数据').'-' . date("Y-m-d H:i:s", time());
            $xlsCell = [
                ['orderSn', lang('订单号')],
                ['consigneeName', lang('收货人姓名')],
                ['transactionNo', lang('交易单号')],
                ['consigneePhone', lang('收货人手机号')],
                ['consigneeAdress', '收货人地址	'],
                ['orderAmount', lang('订单金额')],
                ['realAmount', lang('实付金额')],
                ['discountsAmount', lang('优惠金额')],
                ['payStatus', lang('支付状态')],
                ['payType', lang('支付方式')],
                ['orderStatus', lang('订单状态')],
                ['createTime', lang('创建时间')],
            ];
            $excelData = array_values($_excelData);
            $this->exportExcel($xlsName, $xlsCell, $excelData);
        }

        // 渲染数据
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');

        return $this->fetch();
    }

    /*
     * 搜索条件
     *
     */
    public function _serach()
    {
        $param = request()->param();
//        echo "<pre>";
//        print_r($param);
//        die;
        $map = [];
        if (isset($param['orderSn']) && !empty($param['orderSn'])) {
            $map[] = ['o.order_sn', '=', $param['orderSn']];
        }
        if (isset($param['userPhone']) && !empty($param['userPhone'])) {
            $map[] = ['og.receiver_mobile', '=', $param['userPhone']];
        }
        if (isset($param['startTime']) && !empty($param['startTime'])) {
            $startTime = strtotime($param['startTime']);
            $map[] = ['o.create_time', '>=', $startTime];
        }
        if (isset($param['endTime']) && !empty($param['endTime'])) {
            $map[] = ['o.create_time', '=', strtotime($param['endTime'])];
        }
        if (isset($param['payStatus']) && $param['payStatus'] != -1) {
            $map[] = ['o.pay_status', '=', $param['payStatus']];
        }
        return $map;
    }

    /*
     * 获取交易数据
     *
     */
    public function getPayData()
    {
        $map = $this->_serach();
        return returnData(2000, 'ok', FinanceModel::payData($map));
    }

    /*
     * 交易数据-订单详情
     *
     */
    public function ordersDetail()
    {
        $orderSn = request()->param("orderSn", 0);
        return returnData(2000, 'ok', FinanceModel::ordersDetail($orderSn));
    }
}
