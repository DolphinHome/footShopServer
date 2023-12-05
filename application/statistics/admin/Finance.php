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
use app\common\model\Order;
use service\ApiReturn;
use think\Db;
use app\statistics\model\Finance as FinanceModel;

class Finance extends Base
{
    // 定义时间区间
    private $times = array(
        'day' => 'yesterday', // 日-昨日
        'week' => 'lastweek', // 周-上周
        'month' => 'lastmonth', // 月-上个月
        'quarter' => 'lastquarter', // 季-上个季度
        'year' => 'lastyear', // 年-去年
    );

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
            $xlsName = lang('退款数据') . '-' . date("Y-m-d H:i:s", time());
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
        $map = [];
        if (isset($param['orderSn']) && !empty($param['orderSn'])) {
            $map[] = ['log.order_sn', 'like', '%' . $param['orderSn'] . '%'];
        }
        if (isset($param['startTime']) && !empty($param['startTime'])) {
            $startTime = strtotime($param['startTime']);
            $map[] = ['log.create_time', '>=', $startTime];
            $map[] = ['log.create_time', '<=', $startTime + 24 * 60 * 60];

        }
        if (isset($param['payType']) && $param['payType'] != -1 && $param['payType'] != '') {
            $map[] = ['log.pay_type', '=', $param['payType']];
        }
        $map[] = ['log.status', '=', 1];
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
            $xlsName = lang('退款数据') . '-' . date("Y-m-d H:i:s", time());
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

        $map = [];
        if (isset($param['orderSn']) && !empty($param['orderSn'])) {
            $map[] = ['o.order_sn', 'like', '%' . $param['orderSn'] . '%'];
        }
        if (isset($param['userPhone']) && !empty($param['userPhone'])) {
            $map[] = ['og.receiver_mobile', 'like', '%' . $param['userPhone'] . '%'];
        }
        if (isset($param['startTime']) && !empty($param['startTime'])) {
            $startTime = strtotime($param['startTime']);
            $map[] = ['o.create_time', '>=', $startTime];
        }
        if (isset($param['endTime']) && !empty($param['endTime'])) {
            $map[] = ['o.create_time', '<=', strtotime($param['endTime'])];
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
            $xlsName = lang('交易数据') . '-' . date("Y-m-d H:i:s", time());
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
            $map[] = ['o.order_sn', 'like', '%' . $param['orderSn'] . '%'];
        }
        if (isset($param['userPhone']) && !empty($param['userPhone'])) {
            $map[] = ['og.receiver_mobile', 'like', '%' . $param['userPhone'] . '%'];
        }
        if (isset($param['startTime']) && !empty($param['startTime'])) {
            $startTime = strtotime($param['startTime']);
            $map[] = ['o.create_time', '>=', $startTime];
        }
        if (isset($param['endTime']) && !empty($param['endTime'])) {
            $map[] = ['o.create_time', '<=', strtotime($param['endTime'])];
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

    /*
     *
     * 对账
     *
     */
    public function bill()
    {
        if ($this->request->isAjax()) {
            $param = request()->param();
            $pay_type = $param['payType'] ?? '';
            $start_time = $param['startTime'] ?? '';
            $end_time = $param['endTime'] ?? '';
            $where[] = ['log.status', '=', 1];
            $map[] = ['status', '=', 1];
            if ($pay_type) {
                $where[] = ['log.pay_type', '=', $pay_type];
            }
            if ($start_time) {
                $where[] = ['log.create_time', '>=', strtotime($start_time)];
                $map[] = ['create_time', '>=', strtotime($start_time)];

            }
            if ($end_time) {
                $where[] = ['log.create_time', '<=', strtotime($end_time)];
                $map[] = ['create_time', '<=', strtotime($end_time)];

            }
            $payTypes = Order::$payTypes;
            unset($payTypes['minipay_mix'], $payTypes['xx_pay']);
            $data = [];
            $subQuery = Db::table('lb_payment_log')
                ->alias("log")
                ->leftJoin("order o", "log.order_sn=o.order_sn ")
                ->where($where)
                ->field(" log.order_sn ,log.create_time,o.order_money ,sum(log.amount) as amount ")
                ->order("log.create_time desc")
                ->group(['log.order_sn'])
                ->buildSql();
            $list = Db::table($subQuery . ' payment')
                ->field("
                count(payment.order_sn) as num,payment.create_time,sum(payment.order_money) as order_money,
                sum(payment.amount) as payable_money")
                ->order("payment.create_time desc")
                ->group('FROM_UNIXTIME(payment.create_time,"%Y-%m-%d")')
                ->paginate()->each(function (&$v) use ($payTypes) {
                    $create_time = date("Y-m-d", $v['create_time']);
                    $v['create_time'] = $create_time;
                    //state 0 未核对 1已核对 2核对异常
                    $v['state'] = 1;
                    foreach ($payTypes as $k => $value) {
                        $income = Db::name("payment_log")->where("
                                    `status`=1
                                    and pay_type='{$k}'
                                    and FROM_UNIXTIME(create_time,'%Y-%m-%d')=" . "'" . $create_time . "'"
                        )
                            ->sum("amount");
                        $v['info'][] = [
                            'income' => $income,
                            'check' => $income,
                            'name' => $value
                        ];
                    }
                    return $v;
                });
            foreach ($payTypes as $k => $v) {
                $income = Db::name("payment_log")->where($map)
                    ->where([
                        ['pay_type', '=', $k]
                    ])
                    ->sum("amount");
                $data[] = [
                    'income' => $income,
                    'check' => $income,
                    'name' => $v
                ];
            }
            $return = [
                'list' => $list,
                'data' => $data
            ];
            return ApiReturn::r(1, $return, 'ok');
        } else {
            return $this->fetch();

        }
    }

    /**
     * 对账
     *
     */
    public function verify_bill()
    {        
        if ($this->request->isAjax()) {
            $param = request()->param();
            $pay_type = $param['payType'] ?? '';
            $start_time = $param['startTime'] ?? "";
            $end_time = $param['endTime'] ?? "";
            $check_status = $param['check_status'] ??0;
            $where[] = ['log.status', '=', 1];
            $map[] = ['status', '=', 1];
            if ($pay_type) {
                $where[] = ['log.pay_type', '=', $pay_type];
            }
            if ($start_time) {
                $where[] = ['log.create_time', '>=', strtotime($start_time)];
                $map[] = ['create_time', '>=', strtotime($start_time)];

            }
            if ($end_time) {
                $where[] = ['log.create_time', '<=', strtotime($end_time)];
                $map[] = ['create_time', '<=', strtotime($end_time)];

            }
            if ($check_status) {
                $where[] = ['log.check_status', '=', $check_status];
            }
            $payTypes = Order::$payTypes;
            unset($payTypes['minipay_mix'], $payTypes['xx_pay']);
            $payTypes = array_merge($payTypes, ['total' => '总计']);
            $data = [];
            $list = FinanceModel::getCheckCenterData($where);
            $order_sn_have_modify = [1];
            $order_sn_have_modify =  Db::name("payment_log")->where(['check_status'=>3])->column('order_sn');
            foreach ($payTypes as $k => $v) {
                if ($k == 'total') {
                    $where_log = [];
                    $income = Db::name("order")->where([
                        ['pay_status', '=', 1],
                        ['pay_time', '>=', strtotime($start_time)],
                        ['pay_time', '<=', strtotime($end_time)],
                        ['order_sn', 'not in', $order_sn_have_modify],
                        ])
                        ->sum("payable_money");
                } elseif ($k == 'balance') {
                    $where_log = [
                        ['pay_type', '=', $k]
                    ];
                    $income = Db::name("order")->where([
                        ['pay_status', '=', 1],
                        ['pay_time', '>=', strtotime($start_time)],
                        ['pay_time', '<=', strtotime($end_time)],
                        ['order_sn', 'not in', $order_sn_have_modify]
                        ])
                        
                        //->where($where_log)
                        ->sum("real_balance");

                } else {
                    $where_log = [
                        ['pay_type', '=', $k]
                    ];
                    $income = Db::name("order")->where([
                        ['pay_status', '=', 1],
                        ['pay_time', '>=', strtotime($start_time)],
                        ['pay_time', '<=', strtotime($end_time)],
                        ['order_sn', 'not in', $order_sn_have_modify],
                        ])
                        ->where($where_log)
                        ->sum("real_money");
                }
                
                //实收
                $check = Db::name("payment_log")->where($map)
                    ->where($where_log)
                    ->where([['check_status', '<>', 3]])
                    ->sum("amount");
                    
                //差额=应收-实收    
                $data[] = [
                    'income' => $income,
                    'check' => $check,
                    'diff' => bcsub($income, $check, 2),
                    'name' => $v
                ];
            }
            $return = [
                'list' => $list,
                'data' => $data
            ];
            return ApiReturn::r(1, $return, 'ok');
        } else {
            return $this->fetch();

        }
    }

    /**
     * 核对账单，对有差额的进行修正
     */
    public function check_bill()
    {
        if ($this->request->isAjax()) {
            $order_sn = request()->param('order_sn');
            Db::name('payment_log')->where(["order_sn" => $order_sn])->update([
                "check_status" => 3
            ]);
            //管理员操作日志记录
            action_log('admin.statistics_finance_check_bill', 'admin', 0, UID, '订单号：'.$order_sn);
        }
    }
 
}
