<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\user\model\Collection;
use service\ApiReturn;
use app\user\model\Address as AddressModel;
use app\goods\model\Goods as GoodsModel;
use app\goods\model\GoodsSku;
use app\goods\model\Cart as CartModel;
use app\goods\model\CartLog;
use Think\Db;

/**
 * 购物车接口
 * @package app\api\controller\v1
 */
class Cart extends Base
{
    /**
     * 获取购物车列表
     * @edit 晓风<215628355@qq.com>  2021年3月1日13:46:06 优化查询
     */
    public function get_list($data, $user)
    {
        $userid = $user['id'];
        $time = time();
        $list = CartModel::where('user_id', $userid)->order('id desc')->select();
        foreach ($list as &$item) {
            $gs = GoodsModel::where("id", $item['goods_id'])->field("member_price,shop_price,stock,is_sale,is_delete,status,is_wholesale")->find();
            //商品是否失效 1是0否
            $is_valid = (!$gs || $gs['is_sale'] == 0 || $gs['is_delete'] == 1 || $gs['status'] == 2) ? 1 : 0;
            if ($item['sku_id']) {
                $sku = GoodsSku::where("goods_id", $item['goods_id'])->where('status', 1)->where("sku_id", $item['sku_id'])->field("member_price,shop_price,stock")->find();
                $gs["stock"] = $sku["stock"] ?? 0;
                $gs["member_price"] = $sku["member_price"] ?? 0;
                $gs["shop_price"] = $sku["shop_price"] ?? 0;
                $is_valid = $sku ? $is_valid : 1;
            }
            $item['is_valid'] = $is_valid;
            $is_shop_reduce_price = $shop_reduce_price = 0;
            $flag = bcsub($item['shop_price'], ($gs['shop_price'] ?? 0), 2);
            if ($flag > 0) {
                $is_shop_reduce_price = 1;
                $shop_reduce_price = abs($flag);
            }
            $item['is_shop_reduce_price'] = $is_shop_reduce_price;
            $item['shop_reduce_price'] = $shop_reduce_price;
            $item['stock'] = $gs['stock'] ?? 0;
            $item['member_price'] = $gs['member_price'] ?? 0;
            $item['shop_price'] = $gs['shop_price'] ?? 0;
            $item['goods_thumb'] = get_file_url($item['goods_thumb']);
            $item['is_sale'] = $gs['is_sale'] ?? 0;
            $item['is_wholesale'] = $gs['is_wholesale'] ?? 0;
            if (!$item['shop_price']) {
                $item['coupon'] = 0;
                continue;
            }
            if ($gs['is_wholesale'] == 1) {
                $item['batch_data'] = Db::name('goods_wholesale')->where(['goods_id' => $item['goods_id'], 'sku_id' => $item['sku_id']])->field('start_batch,trade_price')->order('start_batch ASC')->select();
            }
            //计算优惠券金额
            $coupon = \app\operation\model\Coupon::view("operation_coupon")
                ->view("operation_coupon_record", false, "operation_coupon_record.cid=operation_coupon.id")
                ->where("operation_coupon_record.status", 1)
                ->where("operation_coupon_record.start_time", "<=", $time)
                ->where("operation_coupon_record.end_time", ">=", $time)
                ->where("operation_coupon_record.user_id", $userid)
                ->where("operation_coupon.min_order_money", "<=", $item['shop_price'])
                ->value("operation_coupon.money");
            $item['coupon'] = $coupon ?: 0;
        }
        return ApiReturn::r(1, $list, lang('购物车列表'));
    }

    /**
     * 获取购物车商品数量
     * @param $data
     * @param $user
     */
    public function get_cart_num($data,$user)
    {
        $count = CartModel::where('user_id', $user['id'])->sum('num');
        return ApiReturn::r(1, $count, lang('购物车商品数量'));
    }

    /*
     * 移入收藏夹
     *
     */
    public function add_collect($data, $user)
    {
        $cart_ids = $data['cart_ids'];
        $cart_ids_arr = explode(',', $cart_ids);
        $insert_data = [];
        foreach ($cart_ids_arr as $v) {
            $cart = CartModel::get($v);
            $goods = GoodsModel::where(['id' => $cart['goods_id']])->find();
            $check = Collection::where([
                'user_id' => $user['id'],
                'type' => 1,
                'collect_id' => $cart['goods_id'],
                'sku_id' => $cart['sku_id']
            ])->find();
            if (!$check) {
                $insert_data[] = [
                    'create_time' => time(),
                    'update_time' => time(),
                    'status' => 1,
                    'user_id' => $user['id'],
                    'type' => 1,
                    'collect_title' => $goods['name'],
                    'collect_img' => get_file_url($goods['thumb']),
                    'collect_price' => $goods['shop_price'],
                    'collect_sales' => $goods['sales_sum'],
                    'collect_id' => $cart['goods_id'],
                    'sku_id' => $cart['sku_id']
                ];
            }
            //移除失效商品
            \app\goods\model\Cart::where(['id' => $v])->delete();
        }

        (new Collection())->insertAll($insert_data);
        return ApiReturn::r(1, [], lang('移入收藏夹成功'));
    }

    /*
     * 清除购物车失效商品
     *
     */
    public function delete_valid($data, $user)
    {
        $cart_ids = $data['cart_ids'];
        $where = [
            ['user_id', '=', $user['id']],
            ['id', 'in', $cart_ids]
        ];
        \app\goods\model\Cart::where($where)->delete();
        return ApiReturn::r(1, [], lang('清除成功'));
    }

    /*
     * 修改购物车规格
     *
     */
    public function edit_cart($data, $user)
    {
        $cart_id = $data['cart_id'];
        $sku_id = $data['sku_id'];
        $shop_price = $data['shop_price'];
        $sku_name = $data['sku_name'];
        $update_data = [
            'sku_id' => $sku_id,
            'shop_price' => $shop_price,
            'sku_name' => $sku_name
        ];
        CartModel::where(['id' => $cart_id])->update($update_data);
        //合并修改好规格相同的商品
        $cart_data = CartModel::where(['user_id' => $user['id']])->field('count(id) count_id,id')->group('goods_id,sku_id')->select();
        foreach ($cart_data as $v){
            if ($v['count_id']>1){
                $search_data = CartModel::where(['id' => $v['id']])->field('goods_id,sku_id')->find();
                $cart_ids= CartModel::where(['user_id' => $user['id']])->where(['goods_id'=>$search_data['goods_id'],'sku_id'=>$search_data['sku_id']])->order('create_time DESC')->column('id');
                $cart_num = CartModel::where(['user_id' => $user['id']])->where(['goods_id'=>$search_data['goods_id'],'sku_id'=>$search_data['sku_id']])->sum('num');
                CartModel::where(['id'=>$cart_ids[0]])->update(['num'=>$cart_num]);
                unset($cart_ids[0]);
                Db::name('goods_cart')->delete($cart_ids);
            }
        }
        return ApiReturn::r(1, [], 'ok');
    }

    /**
     * 添加或减少批发商品到购物车
     * @param $data .sku_id 商品sku
     * @param $data .num 数量，默认为1
     * @edit 晓风<215628355@qq.com> 2021年3月1日15:36:44 优化BUG,改为模型查询
     */
    public function add_wholesale_cart($data, $user)
    {
        $userid = $user['id'];
        $cartinfo = json_decode($data['cartinfo'], true);
        foreach ($cartinfo as $k => $v) {
            $extend = '';
            $cart_arr = '';
            $skuid = $v['sku_id'];
            $goodsid = $v['goods_id'];
            if (empty($goodsid) && empty($skuid)) {
                return ApiReturn::r(0, '', lang('缺少参数'));
            }
            if ($skuid) {
                $goods = GoodsSku::view('goods_sku', "goods_id,key_name as sku_name,shop_price,member_price,stock,spec_img")
                    ->view('goods', 'name as goods_name,thumb', 'goods_sku.goods_id=goods.id')
                    ->where('goods_sku.sku_id', $skuid)
                    ->where(['goods.status' => 1, 'goods.is_sale' => 1, 'goods.is_delete' => 0, 'goods_sku.status' => 1])
                    ->where("goods.id", $goodsid)//若前端传错$goodsid 可能造成BUG，这里加条件限制下
                    ->find();
                $rc = CartModel::field('id,num')->where('user_id', $userid)->where(['goods_id' => $goodsid, 'sku_id' => $skuid])->find();
            } else {
                $goods = GoodsModel::field('id as goods_id,name as goods_name,shop_price,member_price,stock,thumb')
                    ->where(['status' => 1, 'is_sale' => 1, 'is_delete' => 0])
                    ->where('id', $goodsid)
                    ->find();
                $rc = CartModel::field('id,num')->where('user_id', $userid)->where('goods_id', $goodsid)->find();
            }
            if (!$goods) {
                return ApiReturn::r(0, '', lang('商品已售罄'));
            }
            $extend = [
                'user_id' => $userid,
                'goods_id' => $goods['goods_id'],
                'goods_name' => $goods['goods_name'],
                'sku_name' => $goods['sku_name'] ?? '',
                'shop_price' => $goods['shop_price'],
                'member_price' => $goods['member_price'],
                'goods_thumb' => empty($goods['spec_img']) ? $goods['thumb'] : $goods['spec_img'],
            ];

            $cart_arr = array_merge($cartinfo[$k], $extend);
            $stock = intval($goods['stock']);
            $num = intval($v['num']);
            $cart_arr['num'] = $rc ? bcadd($rc['num'], $num) : $num;
            // 检测最大库存
            if ($stock < $cart_arr['num']) {
                return ApiReturn::r(0, '', lang('已无多余的库存'));
            }
            // 判断是否已加入购物车
            if ($rc) {
                $rs = CartModel::where('id', $rc['id'])->update(['num' => $cart_arr['num']]);
            } else {
                $rs = CartModel::create($cart_arr);
                CartLog::AddCartLog([
                    "goods_id" => $goodsid,
                    "sku_id" => $skuid,
                    "user_id" => $user['user_id'],
                    "operation" => "加入购物车",
                ]);
            }
            if (!$rs) {
                return ApiReturn::r(0, [], lang('操作失败'));
            }
        }
        return ApiReturn::r(1, [], lang('添加成功'));
    }

    /**
     * 添加或减少购物车商品数量
     * @param $data .sku_id 商品sku
     * @param $data .num 数量，默认为1
     * @edit 晓风<215628355@qq.com> 2021年3月1日15:36:44 优化BUG,改为模型查询
     */
    public function add_cart($data, $user)
    {
        $skuid = $data['sku_id'];
        $userid = $user['id'];
        $goodsid = $data['goods_id'];


        if (empty($goodsid) && empty($skuid)) {
            return ApiReturn::r(0, '', lang('缺少参数'));
        }

        $data['create_time'] = time();
        if ($skuid) {
            $goods = GoodsSku::view('goods_sku', "goods_id,key_name as sku_name,shop_price,member_price,stock,spec_img")
                ->view('goods', 'name as goods_name,thumb', 'goods_sku.goods_id=goods.id')
                ->where('goods_sku.sku_id', $skuid)
                ->where(['goods.status' => 1, 'goods.is_sale' => 1, 'goods.is_delete' => 0, 'goods_sku.status' => 1])
                ->where("goods.id", $goodsid)//若前端传错$goodsid 可能造成BUG，这里加条件限制下
                ->find();
            $rc = CartModel::field('id,num')->where('user_id', $userid)->where(['goods_id' => $goodsid, 'sku_id' => $skuid])->find();
        } else {
            $goods = GoodsModel::field('id as goods_id,name as goods_name,shop_price,member_price,stock,thumb')
                ->where(['status' => 1, 'is_sale' => 1, 'is_delete' => 0])
                ->where('id', $goodsid)
                ->find();
            $rc = CartModel::field('id,num')->where('user_id', $userid)->where('goods_id', $goodsid)->find();
        }


        if (!$goods) {
            return ApiReturn::r(0, '', lang('商品已售罄'));
        }

        $extend = [
            'user_id' => $userid,
            'goods_id' => $goods['goods_id'],
            'goods_name' => $goods['goods_name'],
            'sku_name' => $goods['sku_name'] ?? '',
            'shop_price' => $goods['shop_price'],
            'member_price' => $goods['member_price'],
            'goods_thumb' => empty($goods['spec_img']) ? $goods['thumb'] : $goods['spec_img'],
        ];
        $data = array_merge($data, $extend);
        $stock = intval($goods['stock']);
        $num = intval($data['num']);
        $data['num'] = $rc ? bcadd($rc['num'], $num) : $num;
        // 检测最大库存
        if ($stock < $data['num']) {
            return ApiReturn::r(0, '', lang('已无多余的库存'));
        }
        // 判断是否已加入购物车
        if ($rc) {
            $rs = CartModel::where('id', $rc['id'])->update(['num' => $data['num']]);
        } else {
            $rs = CartModel::create($data);
            CartLog::AddCartLog([
                "goods_id" => $goodsid,
                "sku_id" => $skuid,
                "user_id" => $user['user_id'],
                "operation" => "加入购物车",
            ]);
        }
        if ($rs) {
            return ApiReturn::r(1, [], lang('添加成功'));
        }
        return ApiReturn::r(0, [], lang('操作失败'));
    }

    /**
     * 推荐插件->添加购物车收集数据
     * @param $data .sku_id 商品sku
     * @param $data .num 数量，默认为1
     */
    public function recommend_add_cart($data, $user)
    {
        $goods = Db::name('goods')->get($data['goods_id']);
        addons_action('Recommend/Api/UserCart', [$data['goods_id'], $goods['name'], $user['age'], $user['id'], ($user['sex'] == 1 ? "男" : "女")]);
    }

    public function set_goods($data, $user)
    {
        $cart_id = $data['cart_id'];

        if (empty($cart_id) && array_key_exists('num', $data)) {
            return ApiReturn::r(0, '', lang('缺少参数'));
        }
        $num = intval($data['num']);
        $cart = Db::name('goods_cart')->get($cart_id);
        if ($num > 0) {
            // 检测最大库存
            if ($cart['sku_id']) {
                $stock = Db::name('goods_sku')->where('sku_id', $data['sku_id'])->value('stock');
            } else {
                $stock = Db::name('goods')->where('id', $cart['goods_id'])->value('stock');
            }
            $stock = intval($stock);
            if ($stock >= $num) {
                $rs = Db::name('goods_cart')->where('id', $cart_id)->setField('num', $num);
            } else {
                return ApiReturn::r(0, '', lang('已无多余的库存'));
            }
        } else {
            return ApiReturn::r(0, '', lang('数量参数有误'));
        }

        if ($rs) {
            return ApiReturn::r(1, [], lang('添加成功'));
        } else {
            return ApiReturn::r(0, [], lang('操作失败'));
        }
    }

    /**
     * 删除购物车商品
     * @param $data .cart_ids
     * @param $user
     */
    public function remove_goods($data, $user)
    {
        $rs = \think\Db::name('goods_cart')->where('user_id', $user['id'])->delete($data['cart_ids']);
        if ($rs) {
            return ApiReturn::r(1, $rs, lang('删除成功'));
        } else {
            return ApiReturn::r(0, $rs, lang('删除失败'));
        }
    }

    /**
     * 提交订单
     * @param $data
     * @param $user
     */
    public function make_order($data, $user)
    {
        $userid = $user['id'];
        $cartids = explode(',', $data['cart_ids']);
        $cart = \app\goods\model\Cart::where('id', 'in', $cartids)->where('user_id', $userid)->select();
        //获取默认地址信息
        $where[] = ['user_id', '=', $user['id']];
        $where[] = ['is_default', '=', 1];
        $address = AddressModel::get_one_address($where);
        $info['address'] = $address ? $address : [];

        $GoodsSku = new GoodsSku();
        //处理商品，过滤掉库存不足的，下架的，禁用的商品
        $money = 0;
        $express = 0;
        foreach ($cart as &$v) {
            if ($v['sku_id']) {
                //获取sku商品信息
                $goods = $GoodsSku->alias('sku')->leftJoin('goods g', 'g.id=sku.goods_id')
                    ->where('sku_id', $v['sku_id'])
                    ->field('g.id,sku.sku_id,g.name,sku.shop_price,sku.key_name,sku.market_price,sku.sku_weight,g.thumb,sku.stock,g.is_shipping,g.freight_template_id,g.is_sale,g.status,sku.status as skustatus')
                    ->find();
                if (!$goods['skustatus']) {
                    //无效的sku
                    continue;
                }
            } else {
                //获取单商品信息
                $goods = GoodsModel::where('id', $v['goods_id'])->field('id,name,shop_price,market_price,thumb,stock,is_shipping,freight_template_id,is_sale,status')->find();
            }

            if (!$goods['is_sale']) {
                //下架的商品
                continue;
            }
            if (!$goods['status']) {
                //禁用的商品
                continue;
            }
            if ($goods['stock'] < $v['num']) {
                //库存不足的商品
                continue;
            }
            // 不包邮开始计算运费
            if ($goods['is_shipping'] == 0) {
                $freight = new \app\goods\model\Freight();
                $res = $freight->checkAddress($address['city_id'], $goods['freight_template_id']);
                if (!$res) {
                    // 收货地址不在配送范围
                    continue;
                }
                // 商品总重量
                if ($v['sku_id']) {
                    $goods_total_weight = bcmul($goods['sku_weight'], $v['num'], 2);
                } else {
                    $goods_total_weight = bcmul($goods['weight'], $v['num'], 2);
                }
                // 计算配送费用
                $express = bcadd($express, ($res ? $freight->get($goods['freight_template_id'], ['rule'])->calcTotalFee($v['num'], $goods_total_weight, $address['city_id'], $goods['freight_template_id']) : 0), 2);
            }
            $goods['number'] = $v['num'];
            $goods['thumb'] = get_file_url($goods['thumb']);
            $goodslist[] = $goods;
            $money = bcadd($money, bcmul($v['num'], $goods['shop_price'], 2), 2);
        }
        $info['tip'] = 0;
        $info['msg'] = '';
        $info['order_money'] = $money;
        if (count($cart) != count($goodslist)) {
            $info['tip'] = 1;
            $info['msg'] = lang('购物车中有商品库存不足或者状态异常，已为您自动过滤');
            return ApiReturn::r(0, $info, $info['msg']);
        }

        $info['goods'] = $goodslist ? $goodslist : [];
        // 可用优惠券
        $cou = new \app\operation\model\CouponRecord();
        $coupon = $cou->get_best_coupon($user['id'], $money);
        $info['coupon'] = $coupon[0];
        $info['express_price'] = $express;
        // 计算应付金额
        // 加运费
        $payable_money = bcadd($money, $express, 2);
        // 减优惠
        if ($info['coupon']) {
            $payable_money = bcsub($payable_money, $info['coupon']['money'], 2);
        }
        $info['payable_money'] = $payable_money;
        return ApiReturn::r(1, $info, lang('请求成功'));
    }

    /**
     * 检查商品库存数量
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check_goods_num($data, $user)
    {
        $cart_ids = input('param.cart_ids');
        $cart_ids_data = explode(",", $cart_ids);
        foreach ($cart_ids_data as $key => $val) {
            $where = [];
            $cart_detail = CartModel::where(['id' => $val])->find();

            if ($cart_detail['sku_id'] > 0) {
                $where[] = ['sku_id', '=', $cart_detail['sku_id']];
                $where[] = ['stock', '>=', $cart_detail['num']];
                $detail = GoodsSku::where($where)->find();
            } else {
                $where[] = ['id', '=', $cart_detail['goods_id']];
                $where[] = ['stock', '>=', $cart_detail['num']];
                $detail = GoodsModel::where($where)->find();
            }
            if (!$detail) {
                return ApiReturn::r(0, [], $cart_detail['goods_name'] . lang('库存不足'));
            }
            /*            $res[$key]['cart_id'] = $val;
                        $res[$key]['stock_num'] = $detail['stock'];*/
        }
        return ApiReturn::r(1, [], lang('请求成功'));
    }
}
