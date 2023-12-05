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
use app\statistics\admin\Goods as GoodsContr;
use think\controller;
use app\statistics\model\Users;
use app\user\model\User;
use think\Db;
use service\Format;

class Detail extends Base
{
    /**
     * 单品销售榜单详情
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getGoodsSalesDetail()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $times = request()->param("times", "year");
        $param = request()->param();

        // 数据列表
        $orderTotalPrice = Goods::getGoodsSalesList($times, 'page', $param);
        $GoodsContr = new GoodsContr();

        foreach ($orderTotalPrice as $key => $val) {
            $orderTotalPrice[$key]['id'] = $key + 1;
            $goods_info = $GoodsContr->getGoodsListDataNew("year", $val['gid']);
            $orderTotalPrice[$key]['goods_views'] = $goods_info['goods_views']??0;
            $orderTotalPrice[$key]['goods_transactions'] = $goods_info['goods_transactions']??0;
            $orderTotalPrice[$key]['goods_collection'] = $goods_info['goods_collection']??0;
            $orderTotalPrice[$key]['goods_paymoney'] = $goods_info['goods_paymoney']??0;
            $orderTotalPrice[$key]['goods_payusers'] = $goods_info['goods_payusers']??0;
            $orderTotalPrice[$key]['goods_payorders'] = $goods_info['goods_payorders']??0;
            $orderTotalPrice[$key]['goods_payment_conversion_rate'] = $goods_info['goods_payment_conversion_rate']??0;
            $orderTotalPrice[$key]['goods_pay_customer_price'] = $goods_info['goods_pay_customer_price']??0;
        }

        $fields = [
            ['id', lang('排行')],
            ['name', lang('商品名称')],
            ['sn', lang('商品货号')],
            ['sales_sum', lang('商品销量')],
            ['goods_views', lang('商品浏览量')],
            ['goods_transactions', lang('商品成交件数')],
            ['goods_collection', lang('商品收藏总数')],
            ['goods_paymoney', lang('支付金额')],
            ['goods_payusers', lang('支付买家数')],
            ['goods_payorders', lang('支付订单数')],
            ['goods_payment_conversion_rate', lang('支付转化率')],
            ['goods_pay_customer_price', lang('支付客单价')],
        ];
        $search_fields = [
            ['name', lang('商品名称'), 'text'],
            ['sn', lang('商品货号'), 'text'],
            ['times', lang('日期'), 'select', '', ['day' => lang('今日'), 'week' => lang('本周'), 'month' => lang('本月'), 'quarter' => lang('本季度'), 'year' => lang('本年')]],


        ];

        return Format::ins()//实例化
        ->setTopSearch($search_fields)
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setData($orderTotalPrice)//设置数据
            ->fetch();//显示
    }

    public function getuserconsumptiondetail()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $times = request()->param("times", 'year');
        $param = request()->param();
        // 数据列表
        $userConsumptionList = Users::getUserConsumptionList($times, 'page', $param);
        // 修复数据
        // foreach ($userConsumptionList as $key => $value) {
        //     $update['total_consumption_money'] = $value['user_consumption_price'];

        //     User::where('id = '.$value['user_id'])->update($update);
        // }

        // halt($userConsumptionList);

        $fields = [
            ['user_id', lang('用户id')],
            ['user_name', lang('用户名')],
            ['user_type', lang('会员类型'), 'callback', function ($value) {
                return User::$user_type[$value];
            }],
            ['mobile', lang('手机号')],
            ['order_sum', lang('下单次数'), 'callback', function ($value, $data) {
                return $this->order_sum($data['user_id']);
            }, '__data__'],
            ['order_time', lang('最后一次下单时间'), 'callback', function ($value, $data) {
                return $this->order_time($data['user_id']);
            }, '__data__'],

            ['user_consumption_price', lang('消费金额')],

        ];
        //会员类型、手机号、下单次数、最后一次下单时间
        $search_fields = [
            ['user_nickname', lang('名称'), 'text'],
            ['times', lang('日期'), 'select', '', ['day' => lang('今日'), 'week' => lang('本周'), 'month' => lang('本月'), 'quarter' => lang('本季度'), 'year' => lang('本年')]],


        ];

        return Format::ins()//实例化
        ->setTopSearch($search_fields)
            ->setPrimaryKey('user_id')
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setData($userConsumptionList)//设置数据
            ->fetch();//显示
    }

    //获取用户来源渠道详情页
    public function getuseraddressdetail()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $times = request()->param("times", 'year');
        $param = request()->param();
        // 数据列表
        $userConsumptionList = Users::getUserList($times, 'page', $param);
        $fields = [
            ['user_id', '用户id'],
            ['user_source', lang('来源渠道'), 'callback', function ($value, $data) {
                return $this->source($data['user_source']);
            }, '__data__'],
            ['user_name', lang('用户名')],
            ['user_type', lang('会员类型'), 'callback', function ($value) {
                return User::$user_type[$value];
            }],
            ['mobile', lang('手机号')],
            ['create_time', lang('创建时间')],

        ];
        //会员类型、手机号、下单次数、最后一次下单时间
        $search_fields = [
            ['user_nickname', lang('名称'), 'text'],
            ['times', lang('日期'), 'select', '', ['day' => lang('今日'), 'week' => lang('本周'), 'month' => lang('本月'), 'quarter' => lang('本季度'), 'year' => lang('本年'), 'lastyear' => lang('上一年')]],
        ];
        return Format::ins()//实例化
            ->setTopSearch($search_fields)
            ->setPrimaryKey('user_id')
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setData($userConsumptionList)//设置数据
            ->fetch();//显示
    }
    // 定义用户注册来源
    public function source($type)
    {
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
        return $userSourceSign[$type];
    }

    /*
     * 获取会员下单次数
     *
     */
    public function order_sum($user_id)
    {
        return Db::name("order")->where([
            'user_id' => $user_id
        ])->count();
    }

    /*
     * 最后一次下单次数
     *
     */
    public function order_time($user_id)
    {
        return Db::name("order")->where([
            'user_id' => $user_id
        ])->order("create_time desc")
            ->value("create_time");
    }

    /**
     * 加入购物车榜单详情
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getGoodsCartDetail()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $param = request()->param();
        // 数据列表
        $orderTotalPrice = Goods::getGoodsCartList('page', $param);
        $GoodsContr = new GoodsContr();
        foreach ($orderTotalPrice as $key => $val) {
            $orderTotalPrice[$key]['id'] = $key + 1;
            $goods_info = $GoodsContr->getGoodsListDataNew("year", $val['gid']);
            $orderTotalPrice[$key]['goods_views'] = $goods_info['goods_views'];
            $orderTotalPrice[$key]['goods_transactions'] = $goods_info['goods_transactions'];
            $orderTotalPrice[$key]['goods_collection'] = $goods_info['goods_collection'];
            $orderTotalPrice[$key]['goods_paymoney'] = $goods_info['goods_paymoney'];
            $orderTotalPrice[$key]['goods_payusers'] = $goods_info['goods_payusers'];
            $orderTotalPrice[$key]['goods_payorders'] = $goods_info['goods_payorders'];
            $orderTotalPrice[$key]['goods_payment_conversion_rate'] = $goods_info['goods_payment_conversion_rate'];
            $orderTotalPrice[$key]['goods_pay_customer_price'] = $goods_info['goods_pay_customer_price'];
        }

        $fields = [
            ['id', lang('排行')],
            ['goods_name', lang('商品名称')],
            ['sn', lang('商品货号')],
            ['goodsCount', lang('加入购物车数量')],
            ['sales_sum', lang('商品销量')],
            ['goods_views', lang('商品浏览量')],
            ['goods_transactions', lang('商品成交件数')],
            ['goods_collection', lang('商品收藏总数')],
            ['goods_paymoney', lang('支付金额')],
            ['goods_payusers', lang('支付买家数')],
            ['goods_payorders', lang('支付订单数')],
            ['goods_payment_conversion_rate', lang('支付转化率')],
            ['goods_pay_customer_price', lang('支付客单价')],
        ];
        $search_fields = [
            ['goods_name', lang('商品名称'), 'text'],
            ['sn', lang('商品货号'), 'text'],
        ];

        return Format::ins()//实例化
        ->setTopSearch($search_fields)
            ->addColumns($fields)//设置字段
            ->setData($orderTotalPrice)//设置数据
            ->fetch();//显示
    }
}
