<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\admin;

use app\admin\admin\Base;
use app\user\model\BobiLog;
use app\user\model\MoneyLog;
use app\user\model\User;
use app\user\model\Raward;
use app\user\model\User as UserModel;
use app\user\model\VirtualMoneyLog;
use app\user\model\VotesLog;
use think\Db;
use service\Format;
use function GuzzleHttp\Psr7\str;
use app\common\model\Order;

/**
 * 财务统计
 * Class Finance
 * @package app\user\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/3 16:02
 */
class Finance extends Base
{

    /**
     * 财务统计
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 16:02
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $t1 = strtotime(date("Y-m"));
        $t2 = strtotime("+1 month", $t1) - 1;

        $count = [];
        $count['month_order_price'] = Db::name("order")->whereBetween("create_time", [$t1, $t2])->where("pay_status", '>', 0)->sum('paid_money');

        $count['month_cash_price'] = Db::name("user_withdraw")->whereBetween("create_time", [$t1, $t2])->where("cash_status", 1)->sum('cash_fee');

        $t3 = strtotime("-1 month", $t1);
        $t4 = $t1 - 1;

        $count['prevmonth_order_price'] = Db::name("order")->whereBetween("create_time", [$t3, $t4])->where("pay_status", '>', 0)->sum('paid_money');

        $count['prevmonth_cash_price'] = Db::name("user_withdraw")->whereBetween("create_time", [$t3, $t4])->where("cash_status", 1)->sum('cash_fee');

        //根据日统计订单

        $where = "create_time > $t1 and create_time < $t2 ";
        $order = Db::name("order")
            ->fieldRaw("FROM_UNIXTIME(create_time, '%Y-%m-%d') as day, sum(paid_money) as price")
            ->whereBetween("create_time", [$t1, $t2])
            ->where("pay_status", '>', 0)
            ->group("FROM_UNIXTIME(create_time, '%Y-%m-%d')")
            ->select();

        //根据日统计提现
        $cash = Db::name("user_withdraw")
            ->fieldRaw("FROM_UNIXTIME(create_time, '%Y-%m-%d') as day, sum(cash_fee) as price")
            ->whereBetween("create_time", [$t1, $t2])
            ->where("cash_status", 1)
            ->group("FROM_UNIXTIME(create_time, '%Y-%m-%d')")
            ->select();
        $this->assign("count", $count);
        $this->assign("order", $order);
        $this->assign("cash", $cash);
        return $this->fetch(); // 渲染模板
    }

    /**
     * 充值消费
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/5/9 9:35
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function money_log()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();        // 排序
        $order = $this->getOrder('user_money_log.aid DESC');
        $this->assign('excel_show', 1);
        //导出excel
        if (isset($map['is_import'])) {
            unset($map['is_import']);
            $list = MoneyLog::getAllList($map, $order, false);
            $excelData = $_excelData = [];
            foreach ($list as $v) {
                $excelData[] = [
                    'user_nickname' => $v['user_nickname'],
                    'before_money' => $v['before_money'],
                    'change_money' => $v['change_money'],
                    'after_money' => $v['after_money'],
                    'change_type' => $v['change_type'],
                    'create_time' => $v['create_time'],
                    'remark' => $v['remark'],
                ];
            }
            $_excelData[0]['list'] = $excelData;
            $xlsName = lang('会员余额变更记录').'-' . date("Y-m-d H:i:s", time());
            $xlsCell = [
                ['user_nickname', lang('会员名称')],
                ['before_money', lang('变动前金额')],
                ['change_money', lang('变动金额')],
                ['after_money', lang('变动后金额')],
                ['change_type', lang('类型')],
                ['create_time', lang('变动时间')],
                ['remark', lang('备注')],
            ];
            $excelData = array_values($_excelData);
            $this->exportExcel($xlsName, $xlsCell, $excelData);
        }

        $data_list = MoneyLog::getAllList($map, $order);

        $types = MoneyLog::$types;
        $fields = [
            ['aid', lang('序号')],
            ['user_id', lang('会员').'ID'],
            ['user_nickname', lang('会员名称')],
            ['mobile', lang('手机号')],
            ['before_money', lang('变动前金额')],
            ['change_money', lang('变动金额'), 'callback', function ($item) {
                if ($item > 0) {
                    $item = '+' . $item;
                }
                return $item;
            }],
            ['after_money', lang('变动后金额')],
            ['change_type', lang('类型'), '', '', $types],
            ['create_time', lang('变动时间')],
            ['remark', lang('备注')],
        ];

        //搜索
        array_unshift($types, lang('全部'));
        $payTypes = Order::$payTypes;
        array_unshift($payTypes, lang('全部'));
        $search_fields = [
            ['user_nickname', lang('昵称'), 'text'],
            ['mobile', lang('手机号'), 'text'],
            ['change_type', lang('类型'), 'select', '', $types],
            ['create_time', lang('变动时间'), 'daterange'],
            ['pay_type', lang('支付类型'), 'select', '', $payTypes],
        ];
        //统计会员充值金额
        $recharge = MoneyLog::where(['change_type' => 1])->sum('change_money');
        //统计会员提现金额
        $withdraw = MoneyLog::where(['change_type' => 4])->sum('change_money');
        //统计会员消费金额
        $spend = MoneyLog::where(['change_type' => 2])->sum('change_money');

        return Format::ins()
            ->hideCheckbox()
            ->setTopSearch($search_fields)
            ->addColumns($fields)
//            ->setTopButton(['title' => lang('手动充值积分'), 'href' => ['add_integral'], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary '])
//            ->setTopButton(['title' => lang('手动充值余额'), 'href' => ['add'], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary '])
//            ->setTopButton(['title' => lang('会员充值金额').'：' . $recharge, 'href' => '', 'icon' => 'fa  pr5', 'class' => 'btn btn-sm mr5 btn-default '])
//            ->setTopButton(['title' => lang('会员提现金额').'：' . abs($withdraw), 'href' => '', 'icon' => 'fa  pr5', 'class' => 'btn btn-sm mr5 btn-default '])
            ->setTopButton(['title' => lang('会员消费金额').'：' . abs($spend), 'href' => '', 'icon' => 'fa  pr5', 'class' => 'btn btn-sm mr5 btn-default '])
//            ->setTopButton(['title' => lang('手动充值虚拟币'), 'href' => ['virtual_add'], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary '])
//            ->setTopButton(['title' => lang('虚拟币充值消费记录'), 'href' => ['virtual_log'], 'icon' => 'fa fa-list pr5', 'class' => 'btn btn-sm mr5 btn-primary '])
            ->setData($data_list)
            ->fetch();
    }

    public function virtual_log()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();        // 排序
        $order = $this->getOrder('user_virtual_log.aid DESC');

        $data_list = VirtualMoneyLog::getAllList($map, $order);

        $types = VirtualMoneyLog::$types;
        $fields = [
            ['aid', lang('序号')],
            ['user_id', lang('会员').'ID'],
            ['user_nickname', lang('会员名称')],
            ['before_money', lang('变动虚拟币')],
            ['change_money', lang('变动虚拟币')],
            ['after_money', lang('变动后虚拟币')],
            ['change_type', lang('类型'), '', '', $types],
            ['create_time', lang('变动时间')],
            ['remark', lang('备注')],
        ];

        return Format::ins()
            ->hideCheckbox()
            ->addColumns($fields)
            ->setTopButton(['title' => lang('手动充值余额'), 'href' => ['add'], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-success '])
            ->setTopButton(['title' => lang('手动充值虚拟币'), 'href' => ['virtual_add'], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-success '])
            ->setTopButton(['title' => lang('余额消费记录'), 'href' => ['money_log'], 'icon' => 'fa fa-list pr5', 'class' => 'btn btn-sm mr5 btn-success '])
            ->setData($data_list)
            ->fetch();
    }

    /**
     * 添加积分
     */
    public function add_integral()
    {
        // 保存文档数据
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            // 启动事务
            Db::startTrans();
            try {
                $user = User::where('id', $data['user_id'])->lock(true)->field('id,score,user_money')->find();
                if (!$user) {
                    throw new \Exception(lang('会员不存在'));
                }
                $score = bcadd(intval($data['money']), $user['score'], 0);
                $ret = User::where('id', $user['id'])->update([
                    'score' => $score,
                    'update_time' => time()
                ]);

                if (!$ret) {
                    throw new \Exception(lang('更新会员积分失败'));
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            //记录行为
            $details = [
                'before_score' => $user['score'],
                'change_score' => $user['money'],
            ];
            $details = json_encode($details, JSON_UNESCAPED_UNICODE);
            action_log('user_finance_add_integral', 'user', $data['user_id'], UID, $details);
            $this->success(lang('充值积分成功'));
        }
        /*  $fields = [
              ['type' => 'text', 'name' => 'user_id', 'title' => '会员ID'],
              ['type' => 'text', 'name' => 'money', 'title' => lang('充值金额')],
          ];*/
        $this->assign('page_title', lang('手动充值积分'));
        /*  $this->assign('form_items', $fields);*/
        return $this->fetch('add_integral');
    }

    /**
     * 手动充值余额
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/5/9 9:36
     * @return mixed
     */
    public function add()
    {
        // 保存文档数据
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            if ($data['user_id'] == '') {
                $this->error(lang('请选择会员'));
            }
            if ($data['money'] == '') {
                $this->error(lang('请输入金额'));
            }
            if (mb_strlen($this->trimAll($data['money']), 'utf8') >= 8) {
                $this->error(lang('充值金额过大， 最大为99999999'));
            };
            if ($data['remark'] == '') {
                $this->error(lang('请输入充值理由'));
            }
            // 启动事务
            Db::startTrans();
            try {
                $user = User::where('id', $data['user_id'])->lock(true)->field('id,user_money')->find();
                if (!$user) {
                    $this->error(lang('会员不存在'));
                }
                $money = $data['money'];
                $remark = lang('系统充值余额') . $money . lang('，').lang('操作管理员工号').':' . UID. ',充值理由：'.$data['remark'];
                $ordeNo = get_order_sn('CZ');
                $money = bcadd($money, 0, 2);
                MoneyLog::changeMoney($user['id'], $user['user_money'], $money, 3, $remark, $ordeNo);

                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            //记录行为
            $details = [
                'before_money' => $user['user_money'],
                'change_money' => $money,
                'remark' =>$data['remark']
            ];
            $details = json_encode($details, JSON_UNESCAPED_UNICODE);
            action_log('user_finance_add_money', 'user', $data['user_id'], UID, $details);

            $this->success(lang('充值成功'));
        }
        /*  $fields = [
              ['type' => 'text', 'name' => 'user_id', 'title' => '会员ID'],
              ['type' => 'text', 'name' => 'money', 'title' => lang('充值金额')],
          ];*/

        $this->assign('page_title', lang('手动充值'));
        /*  $this->assign('form_items', $fields);*/
        return $this->fetch('add');
    }

    /**
     * 手动充值虚拟币
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/5/9 9:36
     * @return mixed
     */
    public function virtual_add()
    {
        // 保存文档数据
        if ($this->request->isAjax()) {
            $data = $this->request->post();
            // 启动事务
            Db::startTrans();
            try {
                $user = User::where('id', $data['user_id'])->lock(true)->field('id,user_virtual_money')->find();
                if (!$user) {
                    $this->error(lang('会员不存在'));
                }
                $money = $data['money'];
                $remark = lang('系统充值虚拟币') . $money . '，'.lang('操作管理员工号').':' . UID;
                $ordeNo = get_order_sn('CZ');
                $money = bcadd($money, 0, 2);
                VirtualMoneyLog::changeMoney($user['id'], $user['user_virtual_money'], $money, 3, $remark, $ordeNo);

                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success(lang('充值成功'));
        }
        $fields = [
            ['type' => 'text', 'name' => 'user_id', 'title' => '会员ID'],
            ['type' => 'text', 'name' => 'money', 'title' => lang('充值金额')],
        ];

        $this->assign('page_title', lang('手动充值虚拟币'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }


    public function searchUser()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $key = input('key/s', '');
        if ($key == '') {
            return '';
        }
        $page = input('page/d', 1);
        // 数据列表
        $data_list = UserModel::where('id', 'like', '%' . $key . '%')
            ->whereOr('user_name', 'like', '%' . $key . '%')
            ->whereOr('user_nickname', 'like', '%' . $key . '%')
            ->whereOr('mobile', 'like', '%' . $key . '%')
            ->field('id,user_name,user_nickname,mobile')
            ->paginate(20);
        $results = [];
        foreach ($data_list as $v) {
            $str = $v['id'] . '/' . $v['user_name'] . '/' . $v['user_nickname'] . '/' . $v['mobile'];
            $results[] = ['id' => $v['id'], 'text' => $str];
        }
        $data['results'] = $results;
        $data['pagination'] = $data_list->currentPage() == $data_list->lastPage() ? ['more' => true] : ['more' => false];
        return json($data, JSON_UNESCAPED_UNICODE);
    }
}
