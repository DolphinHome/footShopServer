<?php
/**
 * 商品服务层
 * @author chenchen
 * @time 2021年4月20日13:46:38
 */

namespace app\goods\service;

use app\goods\model\Activity;
use app\goods\model\ActivityDetails;
use app\goods\model\Category;
use app\goods\model\Goods as GD;
use app\goods\model\GoodsSku;
use app\goods\model\OrderGoods;
use app\operation\model\Coupon;
use app\operation\model\CouponRecord;
use app\user\model\User;
use app\user\service\Money;
use think\Db;
use app\user\service\User as UserService;

class Goods extends Base
{

    /**
     * 计算分销商品佣金
     * @param $goods_id int 商品id （必须）
     * @time 2021-4-20 14:20:26
     * @author chenchen
     */
    public static function goods_commission($goods_id)
    {
        $first_profit = $second_profit = 0;
        $is_commission = module_config('user.is_commission');
        $goods_money = GD::where(['id' => $goods_id])->value('shop_price');
        //开启分销商品佣金开关
        if ($is_commission == 1 && $goods_money) {
            //单独商品设置收益
            $distribution_goods = Db::name('distribution_goods')
                ->where([
                    'goods_id' => $goods_id,
                ])->find();
            if ($distribution_goods) {
                if ($distribution_goods['type'] == 0) {
                    //固定金额
                    $first_profit = $distribution_goods['first_profit'];
                    $second_profit = $distribution_goods['second_profit'];
                } else {
                    //固定百分比
                    $first_profit = ($distribution_goods['first_profit'] * $goods_money) / 100;
                    $second_profit = ($distribution_goods['second_profit'] * $goods_money) / 100;
                    $first_profit = Money::format_money($first_profit, 1);
                    $second_profit = Money::format_money($second_profit, 1);
                }
            } else {
                //全局设置收益
                //固定金额
                $commission_type = module_config('user.commission_type');
                $commission_first = module_config('user.commission_first');
                $commission_second = module_config('user.commission_second');
                if ($commission_type == 0) {
                    //固定金额
                    $first_profit = $commission_first;
                    $second_profit = $commission_second;
                } else {
                    //固定比例
                    $first_profit = ($distribution_goods['first_profit'] * $goods_money) / 100;
                    $second_profit = ($distribution_goods['second_profit'] * $goods_money) / 100;
                    $first_profit = Money::format_money($first_profit, 1);
                    $second_profit = Money::format_money($second_profit, 1);
                }
            }
        }
        $res = [
            'first_profit' => $first_profit,//一级收益
            'second_profit' => $second_profit//二级收益
        ];
        return $res;
    }


    /**
     * 统计商品销量
     * @param $goods_id int 商品id （必须）
     * @param $sku_id  int sku_id （选填）
     * @param $time  string day=> 日销量,week=>周销量,month=>月销量,
     * quarter=>季销量,year=>年销量,total=>总销量
     * @time 2021年4月21日16:15:55
     * @author chenchen
     */
    public static function sales_num($goods_id, $time = 'total', $sku_id = 0)
    {
        $where = [];
        if ($goods_id) {
            $where[] = [
                ['goods_id', '=', $goods_id],
            ];
        }
        if($sku_id) {
            $where[] = [
                ['sku_id', '=', $sku_id],
            ];
        }
        
        switch ($time) {
            case 'day':
                $dateStr = date('Y-m-d', time());
                $start_time = strtotime($dateStr);
                $end_time = strtotime($dateStr) + 24 * 60 * 60;
                $where[] = [
                    ['create_time', '>=', $start_time],
                    ['create_time', '<=', $end_time]
                ];
                break;
            case 'week':
                $timeStr = date('w') == 1 ? 'Monday' : 'last Monday';
                $start_time = strtotime(date('Y-m-d', strtotime("$timeStr")) . ' 00:00:00');
                $end_time = strtotime(date('Y-m-d', strtotime('Sunday')) . ' 23:59:59');
                $where[] = [
                    ['create_time', '>=', $start_time],
                    ['create_time', '<=', $end_time]
                ];
                break;
            case 'month':
                $start_time = strtotime(date('Y-m-01') . ' 00:00:00');
                $end_time = strtotime(date('Y-m-t') . ' 23:59:59');
                $where[] = [
                    ['create_time', '>=', $start_time],
                    ['create_time', '<=', $end_time]
                ];
                break;
            case 'quarter':
                $quarter = ceil((date('n')) / 3);//获取当前季度
                $start_time = mktime(0, 0, 0, $quarter * 3 - 2, 1, date('Y'));
                $end_time = mktime(0, 0, 0, $quarter * 3 + 1, 1, date('Y'));
                $where[] = [
                    ['create_time', '>=', $start_time],
                    ['create_time', '<=', $end_time]
                ];
                break;
            case 'year':
                $start_time = strtotime(date('Y-01-01') . ' 00:00:00');
                $end_time = strtotime(date('Y-12-31') . ' 23:59:59');
                $where[] = [
                    ['create_time', '>=', $start_time],
                    ['create_time', '<=', $end_time]
                ];
                break;
            default:
                //默认总销量

        }
        $res = OrderGoods::where($where)->sum('num');
        return $res;
    }

    /**
     * 计算商品价格
     * @param $user_id int 会员id （必须）
     * @param $goods_id int 商品id （必须）
     * @param $sku_id  int sku_id （选填）
     * @param $activity_id int 活动id （选填）
     * @param $number int 批发购买数量 （选填）
     * @time 2021-4-22 16:35:15
     * @author chenchen
     */
    public static function calculate_price($user_id, $goods_id, $sku_id = 0, $activity_id = 0, $number = 0)
    {
        //$shop_price 本店成交价 $market_price 市场价 $cost_price  成本价
        $res = [
            'shop_price' => 0,
            'market_price' => 0,
            'cost_price' => 0
        ];
        $user_level = User::where(['id' => $user_id])->value('user_level') ?: 0;
        if ($sku_id) {
            $goods_info = GoodsSku::where([
                'sku_id' => $sku_id,
                'goods_id' => $goods_id
            ])->field('shop_price,member_price,market_price,cost_price')
                ->find();
        } else {
            $goods_info = GD::where([
                'id' => $goods_id
            ])->field('shop_price,member_price,market_price,is_wholesale,cost_price')
                ->find();
        }
        if (count($goods_info) == 0) {
            return format_data(0, $res, lang('没有商品信息'));
        }
        //会员价
//        if ($user_level > 0) {
//            $shop_price = $goods_info['member_price'];
//            $market_price = $goods_info['market_price'];
//            $cost_price = $goods_info['cost_price'];
//        } else {
            $shop_price = $goods_info['shop_price'];
            $market_price = $goods_info['market_price'];
            $cost_price = $goods_info['cost_price'];
//        }
//        //批发价
//        if (isset($goods_info['is_wholesale']) && $goods_info['is_wholesale'] == 1) {
//            if (!is_numeric($number)) {
//                return format_data(0, $res, lang('批发购买数量异常'));
//            }
//            $calculate_price = Db::name('goods_wholesale')->where([
//                ['goods_id', '=', $goods_id],
//                ['sku_id', '=', $sku_id],
//                ['start_batch', '<=', $number]
//            ])->order('start_batch DESC')
//                ->value('trade_price');
//            if ($calculate_price) {
//                $shop_price = $calculate_price;
//            }
//        }
//        //活动价
//        if ($activity_id) {
//            $gcd = self::activity_details($activity_id, $goods_id, $sku_id);
//            if ($gcd['code'] == 0) {
//                return format_data(0, $res, lang($gcd['msg']));
//            }
//            $shop_price = $gcd['data']['shop_price'];
//        }
        $res = [
            'shop_price' => $shop_price,
            'market_price' => $market_price,
            'cost_price' => $cost_price
        ];
        return format_data(1, $res, lang('获取商品价格成功'));
    }

    /**
     * 变更商品库存
     * @param $goods_id int 商品id （必须）
     * @param $number int 变更数量 正数增加 负数减少 （必须）
     * @param $sku_id  int sku_id （选填）
     * @param $activity_id int 活动id （选填）
     * @time 2021年4月23日11:01:37
     * @author chenchen
     */
    public static function update_stock($goods_id, $number, $sku_id = 0, $activity_id = 0)
    {
        try {
            Db::startTrans();
            if ($number == 0) {
                exception(lang('变更数量不能为0'));
            }
            if ($activity_id) {
                //活动商品
                $model = new ActivityDetails();
                $where = [
                    'goods_id' => $goods_id,
                    'sku_id' => $sku_id,
                    'activity_id' => $activity_id
                ];
            } else {
                //普通商品
                if ($sku_id) {
                    $model = new GoodsSku();
                    $where = [
                        ['sku_id', '=', $sku_id],
                        ['goods_id', '=', $goods_id]
                    ];
                } else {
                    $model = new GD();
                    $where = [
                        ['id', '=', $goods_id]
                    ];
                }
            }
            if ($number < 0) {
                //扣库存
                $number = abs($number);
                $stock = $model->where($where)->value('stock') ?: 0;
                if ($stock < $number) {
                    exception(lang('库存不足'));
                }
                $stock_update = $model->where($where)->setDec('stock', $number);
            } else {
                //加库存
                $stock_update = $model->where($where)->setInc('stock', $number);
            }
            if (!$stock_update) {
                exception(lang('商品库存更新失败'));
            }
            Db::commit();
            //释放内存
            unset($model);
            return format_data(1, [], lang('库存更新成功'));
        } catch (\Exception $exception) {
            Db::rollback();
            return format_data(0, [], $exception->getMessage());
        }
    }

    /**
     * 变更商品销量
     * @param $goods_id int 商品id （必须）
     * @param $number int 变更数量 正数增加 负数减少 （必须）
     * @param $sku_id  int sku_id （选填）
     * @param $activity_id int 活动id （选填）
     * @time 2021年4月23日11:01:37
     * @author chenchen
     */
    public static function update_sale($goods_id, $number, $sku_id = 0, $activity_id = 0)
    {
        try {
            Db::startTrans();
            if ($number == 0) {
                exception(lang('变更数量不能为0'));
            }
            if ($activity_id) {
                //活动商品
                $model = new ActivityDetails();
                $where = [
                    'goods_id' => $goods_id,
                    'sku_id' => $sku_id,
                    'activity_id' => $activity_id
                ];
                $field = 'sales_sum';

            } else {
                //普通商品
                if ($sku_id) {
                    $model = new GoodsSku();
                    $where = [
                        ['sku_id', '=', $sku_id],
                        ['goods_id', '=', $goods_id]
                    ];
                    $field = 'sales_num';

                } else {
                    $model = new GD();
                    $where = [
                        ['id', '=', $goods_id]
                    ];
                    $field = 'sales_sum';

                }
            }
            if ($number < 0) {
                //减销量
                $number = abs($number);
                $stock = $model->where($where)->value($field) ?: 0;
                if ($stock < $number) {
                    exception(lang('销量不足扣减'));
                }
                $sale_update = $model->where($where)->setDec($field, $number);
            } else {
                //加销量
                $sale_update = $model->where($where)->setInc($field, $number);
            }
            if (!$sale_update) {
                exception(lang('销量更新失败'));
            }
            Db::commit();
            //释放内存
            unset($model);
            return format_data(1, [], lang('商品销量更新成功'));
        } catch (\Exception $exception) {
            Db::rollback();
            return format_data(0, [], $exception->getMessage());
        }
    }

    /**
     * 获取商品详情可领取优惠券列表
     * @param $goods_id int 商品id （必须）
     * @param $user_id  int 会员id （选填）
     * @time 2021年4月25日14:12:11
     * @author chenchen
     */
    public static function coupon_list($goods_id, $user_id = 0)
    {
        $where = [
            ['method', '=', 2],
            ['status', 'eq', 1],
            ['end_time', 'gt', time()],
            ['last_stock', 'gt', 0]
        ];
        //查询优惠券
        $lists = Coupon::where($where)->select();
        if (count($lists) == 0) {
            return [];
        }
        //查询商品分类id
        $goods_cid = GD::where(['id' => $goods_id])->field('cid')->find();
        $lists = $lists->toArray();
        foreach ($lists as $k => &$v) {
            if ($v['cid'] > 0) {
                //优惠券绑定了商品分类
                if ($v['goods_id'] > 0) {
                    //优惠券绑定了商品
                    if ($v['goods_id'] != $goods_id) {
                        unset($lists[$k]);
                        continue;
                    }
                } else {
                    //获取优惠券绑定的商品分类id 和 所有子级商品分类id
                    $cids = Category::getChildsId($v['cid']);
                    array_push($cids, $v['cid']);
                    if (!in_array($goods_cid, $cids)) {
                        unset($lists[$k]);
                        continue;
                    }
                }
            }
            $v['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
            if ($user_id) {
                $v['is_receive'] = CouponRecord::where(['user_id' => $user_id, 'cid' => $v['id']])->count();
            }
        }
        $lists = array_values($lists);
        return $lists;
    }

    /**
     * 获取活动商品基本信息
     * @param $goods_id int 商品id （必须）
     * @param $user_id  int 会员id （选填）
     * @time 2021年4月25日14:12:11
     * @author chenchen
     */
    public static function activity_details($activity_id = 0, $goods_id, $sku_id = 0)
    {
        $time = time();
        $where[] = ['id', '=', $activity_id];
        $where[] = ['status', '=', 1];
        $ga = Activity::where($where)->find();
        if (count($ga) == 0) {
            return format_data(0, [], lang('活动不存在'));
        }
        if (!in_array($ga['type'], Activity::$no_time)) {
            $where[] = ['sdate', '<=', $time];
            $where[] = ['edate', '>=', $time];
        }
        $ga = Activity::where($where)->find();
        if (count($ga) == 0) {
            return format_data(0, [], lang('活动未开始或已结束'));
        }
        $gmap = [
            ['sku_id', '=', $sku_id],
            ['goods_id', '=', $goods_id],
            ['status', '=', 1],
            ['activity_id', '=', $activity_id]
        ];
        $gcd = ActivityDetails::where($gmap)->find();
        if (count($gcd) == 0) {
            return format_data(0, [], lang('活动未开始或已结束'));
        }
        if ($gcd['unlimited'] == 1) {
            $nowHour = (int)date('H');
            if ($gcd['start_time'] > $nowHour || $gcd['end_time'] <= $nowHour) {
                return format_data(0, [], lang('活动未开始或已结束'));
            }
        }
        $res = [
            'shop_price' => $gcd['activity_price'],
            'activity_id' => $gcd['activity_id'],
            'cost_integral' => $gcd['sales_integral'],
            'sales_integral' => $gcd['sales_integral'],
            'is_pure_integral' => $gcd['is_pure_integral'],
            'stock' => $gcd['stock'],
            'join_number' => $gcd['join_number']
        ];
        return format_data(1, $res, lang('获取数据成功'));
    }

    /**
     * 获取商品/多规格商品的库存
     */
    public function get_stock($goods_id, $sku_id=0)
    {
        //普通商品
        if ($sku_id) {
            $model = new GoodsSku();
            $where = [
                ['sku_id', '=', $sku_id],
                ['goods_id', '=', $goods_id]
            ];
        } else {
            $model = new GD();
            $where = [
                ['id', '=', $goods_id]
            ];
        }

        $res = $model->where($where)->field('stock')->find();
        return $res['stock']??0;
    }
}
