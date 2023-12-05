<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\operation\model\CouponRecord as RecordModel;
use app\operation\model\Coupon as CouponModel;
use service\ApiReturn;
use think\Db;

/**
 * 优惠券接口
 * Class Coupon
 */
class Coupon extends Base
{

    /**
     * 获取指定获取方式的优惠券
     * @param $method 0系统自动发放 1首页弹窗 2手动领取
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_coupon($data = [])
    {
        $data['number'] = $data['number'] ? $data['number'] : 3;
        $map[] = ['method', 'eq', $data['method']];
        $map[] = ['status', 'eq', 1];
        $map[] = ['end_time', 'gt', time()];
        $map[] = ['last_stock', 'gt', 0];
        $list = CouponModel::where($map)->limit($data['number'])->order("id desc")->select();
        foreach ($list as $k => &$v) {
            $v['end_time'] = date('Y-m-d', $v['end_time']);
            if ($data['user_id']) {
                $v['is_receive'] = RecordModel::where(['user_id' => $data['user_id'], 'cid' => $v['id']])->count();
            }
            $list[$k] = $this->filter($v, $this->fname);
        }
        if ($list) {
            return ApiReturn::r(1, $list, lang('请求成功'));
        }

        return ApiReturn::r(1, [], lang('暂无数据'));
    }

    /*
     * 支付成功返一张优惠券
     *
     */

    public function coupon($data = [], $user = [])
    {
        $map[] = ['method', 'eq', 2];
        $map[] = ['status', 'eq', 1];
        $map[] = ['end_time', 'gt', time()];
        $map[] = ['last_stock', 'gt', 0];
        $record = RecordModel::where([
            'user_id' => $user['id']
        ])->column("cid");
        if (!$record) {
            $record = [0];
        }
        $map[] = ['id', 'not in', $record];
        $list = CouponModel::where($map)->find();

        if ($list) {
            return ApiReturn::r(1, $list, lang('请求成功'));
        }

        return ApiReturn::r(1, [], lang('暂无数据'));
    }

    /**
     * 领取优惠券
     * @param int $cid 优惠券id
     * @param int $uid 领取会员的id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function receive_coupon($data = [], $user = [])
    {
        $is_receive = RecordModel::where(['user_id' => $user['id'], 'cid' => $data['cid']])->count();
        if ($is_receive) {
            return ApiReturn::r(0, [], lang('请勿重复领取'));
        }
        // 启动事务
        Db::startTrans();
        try {
            $map[] = ['id', '=', $data['cid']];
            $map[] = ['status', '=', 1];
            $map[] = ['end_time', '>', time()];
            $map[] = ['last_stock', '>', 0];
            $info = Db::name('operation_coupon')->where($map)->field('valid_day,goods_id,start_time')->lock(true)->find();
            if (!$info) {
                exception(lang('优惠券已经被领取完了'));
            }
            //减少优惠券库存
            $res = Db::name('operation_coupon')->where($map)->setDec('last_stock');
            if (!$res) {
                exception(lang('优惠券已经被领取完了'));
            }

            //增加会员优惠券领取记录
            $receive_data['user_id'] = $user['id'];
            $receive_data['cid'] = $data['cid'];
            $receive_data['start_time'] = time();
            $receive_data['end_time'] = $info['start_time'] + 86400 * $info['valid_day'];
            $receive_data['status'] = 1;
            $receive_data['goods_id'] = $info['goods_id'];
            $res = Db::name('operation_coupon_record')->insert($receive_data);
            if (!$res) {
                exception(lang('优惠券领取失败'));
            }

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        return ApiReturn::r(1, [], lang('领取成功'));
    }

    /**
     * 优惠券发现接口，例如结算时请求一下这个接口，返回可使用的优惠金额最多的一张券
     * @param $uid 会员id
     * @param $money 订单金额
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function user_find_coupon($data = [], $user = [])
    {
        $category_id = $data['category_id']??0;
        if (isset($data['all_money'])) {
            $all_money = $data['all_money'];
            if (is_array($all_money)) {
                $goods_id = array_keys($all_money);
            } else {
                $goods_id = array_keys(json_decode($all_money, true));
            }
            $category_id = explode(',' , $data['category_id']);
        }
        $list = RecordModel::get_best_coupon($user['id'], $data['money'], $category_id, $goods_id);

        if ($data['is_single']) {
            if ($list) {
                return ApiReturn::r(1, $list[0] ? $list[0] : [], lang('请求成功'));
            }
        }
        foreach ($list as &$value) {
            $value['end_time'] = date("Y-m-d", $value['end_time']);
        }
        if ($list) {
            return ApiReturn::r(1, $list, lang('请求成功'));
        }

        return ApiReturn::r(1, [], lang('暂无数据'));
    }


    /**
     * 优惠券列表
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/28 11:55
     */
    public function coupon_list($data = [], $user = [])
    {
        $type = $data['type'];
        $orderPrice = $data['order_price'] ? $data['order_price'] : 0;
        RecordModel::edit_coupon($user['id']); //修改优惠券是否过期
        $lists = RecordModel::get_coupon_list($user['id'], $type, $orderPrice);
        if ($lists) {
            foreach ($lists as &$value) {
                $value['end_time'] = date("Y-m-d H:i:s", $value['end_time']);
                $value['is_url'] = 0;
                if ($value['goods_id']) {
                    $value['is_url'] = 1;
                    $value['sku_id'] = 0;
                }
            }
            return ApiReturn::r(1, $lists, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据信息'));
    }

    /**
     * 优惠券详情
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/28 11:56
     */
    public function coupon_detail($data = [], $user = [])
    {
        $userCouponId = $data['user_coupon_id'];
        $where[] = ['cr.id', 'eq', $userCouponId];
        $where[] = ['cr.user_id', 'eq', $user['id']];
        $result = RecordModel::get_user_coupon($where);
        if ($result) {
            return ApiReturn::r(1, $result, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据信息'));
    }
}
