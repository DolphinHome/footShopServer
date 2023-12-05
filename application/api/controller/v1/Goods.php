<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\common\model\Api;
use app\goods\model\Category;
use app\goods\model\Brand as BrandModel;
use app\goods\model\Freight;
use app\goods\model\Goods as GoodsModel;
use app\goods\model\GoodsCommentCollect;
use app\goods\service\Order;
use app\user\model\Task as TaskModel;
use app\goods\model\GoodsSku;
use app\user\model\Address as AddressModel;
use app\goods\model\ActivityDetails as ActivityDetailsModel;
use app\user\model\User;
use service\ApiReturn;
use service\PHPQrcode;
use think\Db;
use service\Tree;
use service\SphinxClient;
use app\operation\model\Coupon as CouponModel;
use app\operation\model\CouponRecord as RecordModel;
use app\goods\model\GoodsLabelService;
use app\goods\model\Type;
use app\goods\model\GoodsTypeSpecItem;
use app\goods\model\GoodsComment;
use app\common\model\GoodsQuestion;
use app\user\model\Pickup as UserPickupModel;

/**
 * 商品接口
 * @package app\api\controller\v1
 */
class Goods extends Base
{
    /**
     * 获取商品分类
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/22 8:58
     */
    public function get_category_list($data = [], $user = [])
    {

        //查询所有分类
        $where['cate.is_show'] = 1;
        $where['cate.status'] = 1;
        $categoryList = Category::alias('cate')->join('upload u', 'cate.thumb=u.id', 'left')
            ->where($where)->order('cate.sort asc,cate.id desc')
            ->column("cate.id,cate.pid,cate.name,cate.thumb");
        foreach ($categoryList as &$value) {
            $value['thumb'] = get_file_url($value['thumb']);
        }
        if ($categoryList) {
            //格式化输出
            $categoryList = Tree::toLayer($categoryList, $data['pid'], $data['max_level']);
            foreach ($categoryList as &$value) {
                if ($value['pid'] == 0) {
                    $value['bind_list'] = BrandModel::alias('a')->join('upload u', 'a.logo=u.id', 'left')->where(['cid' => $value['id'], 'is_hot' => 1])->field('a.id,a.logo,a.name,u.path')->select();
                }
            }
            $this->filterData($categoryList);
            return ApiReturn::r(1, $categoryList, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));
    }

    //商品分类接口
    public function get_goods_brand($data = [], $user = [])
    {
        $where = [];
        if (isset($data['cid'])) {
            if ($data['cid'] != 0) {
                $category_ids = $this->getAllCodeTypeByCid($data['cid']);   //获取商品分类ID
                $where[] = ['cid', 'IN', $category_ids];
            } else {
                $where[] = ['cid', '=', $data['cid']];
            }
        }

        $lists = BrandModel::where(['status' => 1])->where($where)->order('sort DESC')->select();
        foreach ($lists as &$value) {
            $value['logo'] = get_file_url($value['logo']);
        }
        return ApiReturn::r(1, $lists, lang('请求成功'));
    }

    //推荐分类接口
    public function get_category_hot($data = [], $user = [])
    {
        $lists = Category::where(['status' => 1, 'is_hot' => 1])->order('sort DESC')->select();
        foreach ($lists as &$value) {
            $value['thumb'] = get_file_url($value['thumb']);
        }
        return ApiReturn::r(1, $lists, lang('请求成功'));
    }

    protected function filterData(&$data)
    {
        if ($data) {
            $data = array_values($data);
            foreach ($data as &$item) {
                if (isset($item['child'])) {
                    $item['child'] = $this->filterData($item['child']);
                }
            }
            return $data;
        }
    }

    //输入商品分类ID返回分类下级的所有ID(包括自己)
    public static function getAllCodeTypeByCid($cid)
    {
        $allIds = [$cid];
        while (true) {
            $ids = Category::where('pid', 'in', $cid)
                ->where('is_show', 1)
                ->where('status', 1)
                ->column('id');
            if (empty($ids)) {
                break;
            }
            $allIds = array_merge($allIds, $ids);
            $cid = $ids;
        }
        return array_unique($allIds);
    }

    /**
     * 获取指定栏目的商品列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/24 11:02
     */
    public function get_goods_list($data)
    {
        // 模拟参数
        // $data = array(
        //     'keyword' => lang('白酒'), // 搜索关键字
        //     'type' => '', // 品牌搜索标识[当此标识为1时，cid为brand_id]
        //     'cid' => '', // 分类ID 或 品牌ID
        //     'id' => '1', // 商品ID
        //     'service_lable_id' => '1,2,3,4', // 服务标签ID
        //     'min_price' => '100', // 最小金额
        //     'max_price' => '500', // 最大金额
        //     'brand_id' => '2,3,4', // 品牌ID
        //     'goods_cid' => '3', // 分类ID 
        //     'sku_id' => '68,74,75,76,67,62,34,50', // 分类ID 
        //     'business_parameters' => 'synthesize', // 分类ID['synthesize'=>lang('综合'), 'goods'=>lang('商品'), 'products'=>lang('课程或视频'), 'stores'=>lang('店铺')] 
        // );

        // 处理搜索条件（zenghu update 更新之前的写法 并未做其他处理 2020年12月22日10:29:28）
        $where = '';
        if ($data['type'] == 1 && $data['cid']) {
            $where = " g.brand_id = {$data['cid']} ";
        } else {
            // 商品分类
            if ($data['cid']) {
                $category_ids = implode(',', self::getAllCodeTypeByCid($data['cid']));
                $where = " g.cid IN({$category_ids}) ";
            }
            // 商品ID
            if ($data['id']) {
                if ($where) {
                    $where .= " AND g.id = {$data['id']} ";
                } else {
                    $where = " g.id = {$data['id']} ";
                }
            }
            // 是否是新品
            if ($data['is_new'] == 1) {
                if ($where) {
                    $where .= " AND g.is_new = 1 ";
                    $where .= " AND g.id = {$data['id']} ";
                } else {
                    $where = " g.is_new = 1 ";
                }
                // 拼接处理关键字搜索
                
            }
            // 搜索关键字
            if ($data['keyword']) {
                // 拼接处理关键字搜索
                $whereStr = self::keywordsHandle($data['keyword'], 'g.name');
                if ($where) {
                    $where .= " AND {$whereStr} ";
                } else {
                    $where = $whereStr;
                }
            }
            // 服务标签ID
            if ($data['service_lable_id']) {
                $labelWhereStr = self::serviceLabelHandel($data['service_lable_id']);
                if ($where) {
                    $where .= " AND {$labelWhereStr} ";
                } else {
                    $where = $labelWhereStr;
                }
            }
            // 商品价格区间搜索
            $priceWhereStr = '';
            if (!empty($data['min_price']) && $data['min_price'] > 0) {
                $priceWhereStr = "g.shop_price >= {$data['min_price']}";
            }
            if (!empty($data['max_price']) && $data['max_price'] > 0) {
                $priceWhereStr = "g.shop_price <= {$data['max_price']}";
            }
            if (!empty($data['min_price']) && ($data['min_price'] > 0) && !empty($data['max_price']) && ($data['max_price'] > 0)) {
                if ($data['min_price'] < $data['max_price']) {
                    $priceWhereStr = "(g.shop_price >= {$data['min_price']} AND g.shop_price <= {$data['max_price']})";
                }
                if ($data['min_price'] > $data['max_price']) {
                    $priceWhereStr = "(g.shop_price >= {$data['max_price']} AND g.shop_price <= {$data['min_price']})";
                }
                if ($data['min_price'] == $data['max_price']) {
                    $priceWhereStr = "(g.shop_price = {$data['min_price']})";
                }
            }
            if ($priceWhereStr) {
                if ($where) {
                    $where .= " AND {$priceWhereStr} ";
                } else {
                    $where = $priceWhereStr;
                }
            }
            // 品牌ID
            if ($data['brand_id']) {
                $brandWhereStr = self::brandLabelHandel($data['brand_id']);
                if ($where) {
                    $where .= " AND {$brandWhereStr} ";
                } else {
                    $where = $brandWhereStr;
                }
            }
            // 商品分类单独查询
            if ($data['goods_cid']) {
                //如果是有子分类
                $category_ids = implode(',', self::getAllCodeTypeByCid($data['goods_cid']));
                if ($where) {
                    $where .= " AND g.cid IN({$category_ids}) ";
                } else {
                    $where = " g.cid IN({$category_ids}) ";
                }
            }
            // SKU检索商品
            if ($data['sku_id']) {
                $goodsIds = self::getGoodsIdsForSkuId($data['sku_id']);
                if ($where) {
                    $where .= " AND g.id IN({$goodsIds}) ";
                } else {
                    $where = " g.id IN({$goodsIds}) ";
                }
            }
        }

        // 排序处理
        $order = ['id' => 'desc'];
        if ($data['order']) {
            switch ($data['sort']) {
                case 1:
                    $order = ['sort' => 'asc'];
                    break;
                case 2:
                    $order = ['g.sales_sum' => $data['order']];
                    break;
                case 3:
                    $order = ['g.shop_price' => $data['order']];
                    break;
                case 4:
                    $order = ['g.member_price' => $data['order']];
                    break;
                default:
                    $order = "";
            }
        }
        $page = $data['page'] ?? 1;
        $size = $data['list_rows'] ?? 10;
        // 查询商品数据
        $goods_list = GoodsModel::goods_list($where, $order, $size, $page);
        $total = GoodsModel::alias("g")->where($where)
            ->where([['g.is_delete', '=', 0], ['g.is_sale', '=', 1], ['g.status', '=', 1]])
            ->count();
        if ($goods_list) {
            return ApiReturn::r(1, ['data' => $goods_list, 'total' => $total], lang('请求成功'));
        }

        return ApiReturn::r(0, [], lang('暂无数据'));
    }

    //获取批发商品列表
    public function getWholesale($data, $user)
    {
        $where = " g.is_wholesale = {$data['is_wholesale']} ";
        $order = ['g.sort' => 'asc'];
        $size = $data['list_rows'] ?? 10;
        // 查询商品数据
        $goods_list = GoodsModel::goods_list($where, $order, $size, $data['page']);
        $total = GoodsModel::alias("g")->where($where)
            ->where([['g.is_delete', '=', 0], ['g.is_sale', '=', 1], ['g.status', '=', 1]])
            ->count();
        if ($goods_list) {
            return ApiReturn::r(1, ['data' => $goods_list, 'total' => $total], lang('请求成功'));
        }

        return ApiReturn::r(0, [], lang('暂无数据'));
    }

    /**
     * 获取商品详情
     * @param array $data
     * @param array $user
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 8:19
     */
    public function get_goods_detail($data = [])
    {
        try {
            $goodsId = $data['goods_id'];
            //先取商品缓存
            if (!$data['activity_id']) {
                $goods = '';//cache('goods_datail_'.$goodsId);
            }
            if (!$goods || $data['is_cache']) {
                //缓存不存在或者强制更新，则重新查询商品信息
                $where[] = ['g.id', 'eq', $goodsId];
                $where[] = ['g.is_sale', 'eq', 1];
                $where[] = ['g.is_delete', 'eq', 0];
                $GoodsModel = new GoodsModel();
                //根据活动ID修改商品对应规格库存和价格
                if ($data['activity_id']) {
                    $goods = $GoodsModel->goods_detail($where, $data['activity_id']);
                    $activity = Db::name('goods_activity')->get($data['activity_id']);
                    $whereSku = $data['sku_id'] ? 'sku_id = ' . $data['sku_id'] : '';
                    $activity_goods = Db::name('goods_activity_details')
                        ->where(['goods_id' => $goods['id'], 'activity_id' => $data['activity_id']])
                        ->where($whereSku)
                        ->find();
                    $surplus_time = bcsub($activity['edate'], time());
                    if ($activity['type'] == 2 || $activity['type'] == 3) {
                        $goods['activity_info'] = [
                            'activity_type' => $activity['type'] ? $activity['type'] : 0,
                            'start_time' => $activity['start_time'],
                            'end_time' => $activity['end_time'],
                            'presell_stime' => date("m月d日 H:i", $activity['presell_stime']),
                            'presell_etime' => date("m月d日 H:i", $activity['presell_etime']),
                            'sdate' => $activity['sdate'],
                            'edate' => $activity['edate'],
                            'surplus_time' => $surplus_time,
                        ];
                        $goods['stock'] = $activity_goods['stock'];
                        $goods['activity_stock'] = $activity_goods['stock'];
                        $goods['sales_sum'] = $activity_goods['sales_sum'];
                    } else {
                        if ($activity_goods['unlimited'] != 0) {
                            $surplus_time = bcsub($activity_goods['end_time'], time());
                        } else {
                            $now_hour = date('H');
                            if ($now_hour % 2 == 0) {
                                $time = 7200;
                            } else {
                                $time = 3600;
                            }
                            $surplus_time = strtotime(date('Y-m-d H:0:0')) + $time - time();
                        }

                        $goods['activity_info'] = [
                            'activity_type' => $activity['type'] ? $activity['type'] : 0,
                            'start_time' => $activity_goods['start_time'],
                            'end_time' => $activity_goods['end_time'],
                            'sdate' => $activity['sdate'],
                            'edate' => $activity['edate'],
                            'surplus_time' => $surplus_time ?? 0,
                        ];
                        $goods['stock'] = $activity_goods['stock'];
                        $goods['sales_sum'] = $activity_goods['sales_sum'];
                    }
                    $goods['count_down'] = 0;
                    if ($goods['activity_info']['activity_type'] == 10) {
                        $bwhere = [];
                        $bwhere[] = ["goods_id", "=", $data['goods_id']];
                        $bwhere[] = ["user_id", "=", $data['user_id']];
                        $bwhere[] = ["create_time", "gt", time() - 86400];
                        $bwhere[] = ["is_addorder", "=", 0];
                        $bargainInfor = Db::name("goods_bargain_order")->where($bwhere)->find();
                        if ($bargainInfor) {
                            $goods['count_down'] = $bargainInfor['create_time'] + 86400 - time();
                            $goods['bargain_id'] = $bargainInfor['id'];
                        }
                    }
                    $goods['activity_price'] = $activity_goods['activity_price'];
                    $goods['deposit'] = $activity_goods['deposit'];
                    $goods['balance'] = $activity_goods['activity_price'] - $activity_goods['deposit'];
                    $goods['unlimited'] = $activity_goods['unlimited'];
                    $goods['limit'] = $activity_goods['limit'];
                    $goods['sales_integral'] = $activity_goods['sales_integral'];
                    $goods['is_pure_integral'] = $activity_goods['is_pure_integral'];
                    if ($activity['type'] == 2) {
                        $group = Db::name('goods_activity_group')->where(['status' => 1, 'goods_id' => $goods['id'], 'activity_id' => $data['activity_id'], 'is_full' => 0])->select();
                        $gorup_tmp = array();
                        foreach ($group as $k => $v) {
                            if ($data['user_id']) {
                                $group_user = Db::name('goods_activity_group_user')->where([['status', '=', 1], ['group_id', '=', $v['id']], ['uid', '=', $data['user_id']]])->find();
                                if ($group_user) {
                                    // unset($group[$k]);
                                    // continue;
                                }
                            }
                            $group_user_list = Db::name('goods_activity_group_user')->alias('gr')
                                ->join('order o', 'o.order_sn=gr.order_sn', 'left')
                                ->field('gr.uid,gr.user_name,gr.user_head')
                                ->where([['gr.group_id', '=', $v['id']], ['o.status', 'in', '1']])
                                ->select();

                            $group[$k]['is_self_partake'] = 0;
                            foreach ($group_user_list as $key => $value) {
                                $group_user_list[$key]['pay_time'] = date('Y-m-d H:i',$value['pay_time']);
                                if ($value['uid'] == $data['user_id']) {
                                    $group[$k]['is_self_partake'] = 1;
                                    break;
                                }
                                $userinfo = User::where('id',$value['uid'])->field('user_name,head_img,user_nickname')->find();
                                if($userinfo){
                                    $group_user_list[$key]['user_name'] = $userinfo['user_nickname'];
                                    $group_user_list[$key]['head_img'] = get_file_url($userinfo['head_img']);
                                }
                            }

                            if ($group_user_list) {
                                $group[$k]['user_list'] = $group_user_list;
                            } else {
                                //                               unset($group[$k]);
                                //                               continue;
                            }
                            $gorup_tmp[] = $group[$k];
                        }
                        $goods['activity_group'] = ['join_list' => $gorup_tmp, 'join_num' => $activity_goods['join_number']];
                    } else if ($activity['type'] == 5) {
                        $goods['cut_price'] = Db::name('goods_activity_cut_price')->
                        alias('cp')->
                        where([['cp.activity_goods_id', '=', $activity_goods['id']], ['cp.uid', 'neq', 0]])->
                        field('u.user_nickname,u.head_img,cp.cut_price,cp.uid,cp.cut_time')->
                        join('user u', 'cp.uid=u.id', 'left')->select();
                        if ($data['user_id']) {
                            $gacp = Db::name('goods_activity_cut_price')->where([['activity_goods_id', '=', $activity_goods['id']], ['uid', '=', $data['user_id']]])->find();
                            if ($gacp) {
                                $goods['is_cut'] = 1;
                            } else {
                                $goods['is_cut'] = 0;
                            }
                        }
                    } else if ($activity['type'] == 6 && $data['user_id']) {
                        $ever_buy = Db::name('order_goods_list')
                            ->alias('og')
                            ->join('order o', 'og.order_sn=o.order_sn', 'left')
                            ->where(['og.goods_id' => $activity_goods['goods_id'],
                                'og.sku_id' => $activity_goods['sku_id'],
                                'og.activity_id' => $activity_goods['activity_id'],
                                'o.user_id' => $data['user_id'],
                                'o.status' => [0, 1, 2, 3, 4]
                            ])
                            ->find();
                        if ($ever_buy) {
                            $goods['activity_spec_list'] = [];
                            $goods['activity_sku_list'] = [];
                            $goods['activity_price'] = 0;
                            $goods['activity_stock'] = 0;
                            $goods['activity_info'] = ['activity_type' => 0, 'start_time' => 0, 'end_time' => 0, 'sdate' => 0, 'edate' => 0,];
                            $goods['ever_buy'] = 1;
                        } else {
                            $goods['ever_buy'] = 0;
                        }
                    }
                } else {
                    $lists = [];
                    $goods = $GoodsModel->goods_detail($where);
                    $mapCoupon[] = ['method', 'eq', 2];
                    $mapCoupon[] = ['status', 'eq', 1];
                    $mapCoupon[] = ['end_time', 'gt', time()];
                    $mapCoupon[] = ['last_stock', 'gt', 0];
                    $lists = CouponModel::where($mapCoupon)->select();
                    if (count($lists) > 0) {
                        $lists = $lists->toArray();
                        foreach ($lists as $k => &$v) {
                            if ($v['cid'] > 0) {
                                if ($v['cid'] != $goods['cid']) {
                                    unset($lists[$k]);

                                    continue;
                                } else {
                                    if ($v['goods_id'] > 0) {
                                        if ($v['goods_id'] != $goods['id']) {
                                            unset($lists[$k]);
                                            continue;
                                        }
                                    }
                                }
                            }
                            $v['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
                            if ($data['user_id']) {
                                $v['is_receive'] = RecordModel::where(['user_id' => $data['user_id'], 'cid' => $v['id']])->count();
                            }

                            // $list[$k] = $this->filter($v, $this->fname);

                        }
                        $lists = array_values($lists);

                    }


                    // if( $data['user_id'] ){
                    //     $lists = RecordModel::get_coupon_list($data['user_id'], 1);
                    //     if( count($lists) > 0 ){
                    //         foreach ( $lists as $key=>$val ){
                    //             if( $val['cid'] > 0 ){
                    //                 if( $val['cid'] != $goods['cid'] ){
                    //                     unset($lists[$key]);
                    //                     continue;
                    //                 }else{
                    //                     if( $val['goods_id'] > 0 ){
                    //                         unset($lists[$key]);
                    //                         continue;
                    //                     }
                    //                 }
                    //             }
                    //         }
                    //     }

                    // }

                    $goods['coupon_list'] = $lists;
                    $goods['activity_info'] = [
                        'activity_type' => 0,
                    ];
                }
                // cache('goods_datail_'.$goodsId, $goods, 3600);
            }

            if ($data['user_id']) {
                $user = Db::name('user')->get($data['user_id']);
                $goods['user_level'] = $user['user_level'];
            } else {
                $goods['user_level'] = 0;
            }
            //是否收藏
            if ($data['user_id']) {
                $collect = Db::name('user_collection')->where(['type' => 1, 'collect_id' => $goodsId, 'user_id' => $data['user_id']])->count();
            }
            $comment = Db::name('goods_comment')->alias('c')
                ->join('user u', 'c.user_id = u.id')
                ->field('c.id,c.type,c.thumb,c.video,c.create_time,c.content,c.star,u.head_img,u.user_nickname,c.sku_id')
                ->where(['c.goods_id' => $data['goods_id'], 'c.status' => 1])
                ->order('c.create_time desc')
                ->limit('0,3')
                ->select();
            $goods_sku_id = Db::name('goods_sku')->where('goods_id', $goodsId)->value('sku_id');
            if (empty($goods_sku_id)) {
                $goods_sku_id = 0;
            }
            $freight = Freight::where("id", $goods['freight_template_id'])->field("name,freight_explain")->find();
            $goods['freight_explain'] = $freight['freight_explain'];
            $goods['freight_name'] = $freight['name'];
            if ((int)$goods['freight_price'] == 0) {
                $goods['freight_price'] = 0;
            }
            foreach ($comment as &$item) {
                if ($item['type'] == 1) {
                    $item['user_nickname'] = lang('匿名用户');
                    $item['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
                } else {
                    $str_len = mb_strlen($item['user_nickname']);
                    $replace_str = '';
                    for ($i=0;$i<$str_len-1;$i++) {
                        $replace_str .= '*';
                    }
                    $item['user_nickname'] = str2sub($item['user_nickname'], 1, $replace_str);
                    $item['head_img'] = get_file_url($item['head_img']);
                }
                if (strpos($item['head_img'], 'images/none.png') !== false) {
                    $item['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
                }
                $item['thumb'] = get_files_url($item['thumb']);
                $item['video'] = get_files_url($item['video']);
                $item['create_time'] = date('Y-m-d', $item['create_time']);
                if ($item['sku_id']) {
                    $item['key_name'] = Db::name('goods_sku')->where('sku_id', $item['sku_id'])->value('key_name');
                }
                //是否点赞
                $item['is_likes'] = GoodsCommentCollect::isCollection($data['user_id'], $item['id']) ?? 0;
            }
            $goods['comment'] = $comment;
            $goods['comment_total'] = Db::name('goods_comment')->alias('c')
                ->field('c.id,c.type,c.thumb,c.video,c.create_time,c.content,c.star,c.sku_id')
                ->where(['c.goods_id' => $data['goods_id'], 'c.status' => 1])
                ->order('c.create_time desc')
                ->count();
            $goods['is_collect'] = $collect ? 1 : 0;

            //计算好评率 zhougs

            $commentWhere[] = ["goods_id", "=", $data['goods_id']];
            $commentWhere[] = ["status", "=", 1];
            $commentWhere[] = ["star", "gt", 3];
            $niceCommentNumber = Db::name('goods_comment')->where($commentWhere)->count('id');
            $goods['praise_rate'] = 0;
            if ($goods['comment_total'] > 0) {
                $goods['praise_rate'] = round(($niceCommentNumber / $goods['comment_total']) * 100);
            }
            $sale_top_name = '';
            //商品分类是否存在排行榜
            $topName = Db::name("goods_hot_top")->where(["status" => 1, "category_id" => $goods['cid']])->value("name");
            if ($topName) {
                $topNum = $this->getGoodsSalesRanking($goods['id'], $goods['cid']);
                if ($topNum == 0) {
                    $sale_top_name = '';
                } else {
                    $sale_top_name = $topName . lang('第') . $topNum . lang('名');
                }
            }
            $goods['sale_top_name'] = $sale_top_name;
            $goods['question_total'] = GoodsQuestion::alias("q")->join("goods_answer a", "q.id=a.question_id", "left")
                ->where("q.goods_id", $data['goods_id'])
                ->where(['q.status' => 1])
                ->group("q.id")
                ->order("q.create_time DESC")
                ->count();
            //商品问答
            $goods['question_list'] = GoodsQuestion::alias("q")->join("goods_answer a", "q.id=a.question_id and a.status=1", "left")
                ->field("q.id,q.goods_id,q.goods_name,q.goods_thumb,q.content question_content,a.content answer_content,count(a.user_id) answer_number")
                ->where("q.goods_id", $data['goods_id'])
                ->where(['q.status' => 1])
                ->group("q.id")
                ->order("q.create_time DESC")
                ->limit(3)
                ->select()
                ->each(function ($item) {
                    $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                    return $item;
                });

            //记录浏览历史
            if ($data['user_id']) {
                //同一个用户 同一个商品 浏览记录一天存一次
                //求当天0点 24点 时间戳
                $dateStr = date('Y-m-d', time());
                //获取当天0点的时间戳
                $timestamp0 = strtotime($dateStr);
                //获取当天24点的时间戳
                $timestamp24 = strtotime($dateStr) + 86400;
                $collect_where = [
                    ['user_id', '=', $data['user_id']],
                    ['type', '=', 3],
//                    ['sku_id', '=', $goods_sku_id],
                    ['collect_id', '=', $goods['id']],
                    ['create_time', '>=', $timestamp0],
                    ['create_time', '<=', $timestamp24],
                    ['status', '=', 1]
                ];
                $check = Db::name("user_collection")->where($collect_where)->find();
                if (!$check) {
                    $history['create_time'] = time();
                    $history['update_time'] = time();
                    $history['user_id'] = $data['user_id'];
                    $history['type'] = 3;
//                    $history['sku_id'] = $goods_sku_id;
                    $history['collect_title'] = $goods['name'];
                    //$history['collect_img'] = $goods['thumb'];
                    //$history['collect_price'] = $goods['shop_price'];
                    //$history['collect_sales'] = $goods['sales_sum'];
                    $history['collect_id'] = $goods['id'];
                    Db::name('user_collection')->insert($history);
                } else {
                    Db::name("user_collection")->where($collect_where)
                        ->update(['update_time' => time()]);
                }

                TaskModel::doTask($data['user_id'], 'browseGoods');
            }
            //商品类型
            $goods['goods_type'] = $goods["activity_info"]["activity_type"] ?? 0;//默认普通商品
            //聊天价格显示
            $calculate_price = \app\goods\service\Goods::calculate_price($data['user_id'], $goodsId, $goods_sku_id, $data['activity_id']);
            $goods['chat_price'] = $calculate_price["data"]["shop_price"];
            if (isset($goods['is_pure_integral']) && $goods['is_pure_integral'] == 1 && $goods['sales_integral'] > 0) {
                //纯积分
                $goods['chat_price'] = $goods['sales_integral'] . lang('积分');
                $goods['goods_type'] = 8;
            } else {
                if (isset($goods['sales_integral']) && $goods['sales_integral'] > 0) {
                    //积分+金额
                    $goods['chat_price'] = $goods['chat_price'] . '+' . $goods['sales_integral'] . lang('积分');
                    $goods['goods_type'] = 8;

                }
            }
            if ($goods) {
                GoodsModel::where('id', $goodsId)->setInc('click');
                //格式化输出
                return ApiReturn::r(1, $goods, lang('请求成功'));
            }
            return ApiReturn::r(1, [], lang('暂无数据'));
        } catch (\Exception $e) {
            \think\facade\Log::instance()->write("v1/Goods/get_goods_detail发生异常:" . $e->getMessage(), "error");
            return ApiReturn::r(0, [], $e->getMessage());
        }
    }

    /**
     * 推荐插件->浏览商品收集数据
     * @param array $data
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 15:19
     */
    public function recommend_browse($data, $user)
    {
        $res = addons_action('Recommend/Api/UserBrowse', [$data['goods_id'], $data['user_id'], $data['sex'], $data['age'], $data['nickname']]);
        return ApiReturn::r(1, $res, lang('请求结果'));
    }

    /**
     * 推荐插件->搜索商品收集数据
     * @param array $data
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 15:19
     */
    public function recommend_search($data, $user)
    {
        $res = addons_action('Recommend/Api/UserSearch', [$data['keyword'], $data['user_id']]);
        return ApiReturn::r(1, $res, lang('请求结果'));
    }

    /**
     * 获取商品评论列表
     * @param array $data
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 15:19
     */
    public function commentList($data = [])
    {
        if ($data['type'] == 1) {
            $order = 'c.create_time DESC';
        } else if ($data['type'] == 2) {
            $order = 'c.star DESC,c.create_time DESC';
        } else {
            $order = 'c.create_time DESC';
        }
        $map = [];
        if ($data['star_type'] == "good") {
            $map[] = ["c.star", "gt", 3];
        } elseif ($data['star_type'] == "middle") {
            $map[] = ["c.star", "=", 3];
        } elseif ($data['star_type'] == 'bad') {
            $map[] = ["c.star", "lt", 3];
        } elseif ($data['star_type'] == 'is_likes') {
            $collect_ids = GoodsCommentCollect::getGoodsCollectionId($data['goods_id']);
            $map[] = ['c.id', 'IN', $collect_ids];
        } elseif ($data['star_type'] == 'pictures') {
            $map[] = ['thumb', '<>', ''];
        } else {
            $map = [];
        }
        if (isset($data['filter_type']) && $data['filter_type']) {
            if ($data['filter_type'] == 'thumb') {
                $map[] = ['thumb', '<>', ''];
            } elseif ($data['filter_type'] == 'video') {
                $map[] = ['video', '>', 0];
            } else {
                $map[] = ['content', '<>', ''];
            }
        }
        $comment = Db::name('goods_comment')->alias('c')
            ->leftJoin('user u', 'c.user_id = u.id')
            ->leftJoin("goods_comment_collect gcc", "c.id = gcc.collect_id")
            ->field('c.id,c.type,c.thumb,c.video,c.create_time,c.content,c.star,u.head_img,u.user_nickname,c.sku_id,c.pid,count(gcc.id) as likes_num')
            ->where(['c.goods_id' => $data['goods_id'], 'c.status' => 1])
            ->where($map)
            ->group("c.id")
            ->order($order)
            ->paginate();
        $total = 0;
        if (count($comment) > 0) {
            $comment_arr = $comment->toArray();
            $comment = $comment_arr['data'];
            $total = $comment_arr['total'];
        } else {
            $comment = [];
        }
        //halt(Db::name('goods_comment')->getLastSql());
        foreach ($comment as $key => &$item) {
            if ($item['type'] == 1) {
                $item['user_nickname'] = lang('匿名用户');
                $item['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
            } else {
                $str_len = mb_strlen($item['user_nickname']);
                $str_replace = '';
                for($i=0;$i<$str_len - 1;$i++){
                    $str_replace .= '*';
                }
                $item['user_nickname'] = str2sub($item['user_nickname'], 1, $str_replace);
                $item['head_img'] = get_file_url($item['head_img']);
            }
            if (strpos($item['head_img'], 'images/none.png') !== false) {
                $item['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
            }
            $item['thumb'] = get_files_url($item['thumb']);
            $item['video'] = get_files_url($item['video']);
            $item['create_time'] = date('Y-m-d', $item['create_time']);
            if ($item['sku_id']) {
                $item['key_name'] = Db::name('goods_sku')->where('sku_id', $item['sku_id'])->value('key_name');
            }
            $comment[$key]['is_likes'] = GoodsCommentCollect::isCollection($data['user_id'], $item['id']) ?? 0;

        }
        // zenghu ADD 返回评论列表tab栏 2020年12月25日14:05:43
        $tabList = [
            [
                'type_title' => lang('全部'),
                'type_name' => 'all',
                'type_count' => GoodsComment::getGoodsCommentCount(['goods_id' => $data['goods_id'], 'status' => 1]),
            ],
            [
                'type_title' => lang('好评'),
                'type_name' => 'good',
                'type_count' => GoodsComment::getGoodsCommentCount("goods_id = {$data['goods_id']} AND status = 1 AND star > 3"),
            ],
            [
                'type_title' => lang('中评'),
                'type_name' => 'middle',
                'type_count' => GoodsComment::getGoodsCommentCount("goods_id = {$data['goods_id']} AND status = 1 AND star = 3"),
            ],
            [
                'type_title' => lang('差评'),
                'type_name' => 'bad',
                'type_count' => GoodsComment::getGoodsCommentCount("goods_id = {$data['goods_id']} AND status = 1 AND star < 3"),
            ],
            [
                'type_title' => lang('有图'),
                'type_name' => 'pictures',
                'type_count' => GoodsComment::getGoodsCommentCount("goods_id = {$data['goods_id']} AND status = 1 AND thumb != ''"),
            ],
//            [
//                'type_title' => lang('点赞'),
//                'type_name' => 'is_likes',
//                'type_count' => GoodsCommentCollect::getGoodsCollectionNum($data['goods_id']),
//            ],
        ];

        return ApiReturn::r(1, ['list' => $comment, 'total' => $total, 'tabList' => $tabList], lang('请求成功'));
    }

    /**
     * 获取评论详情
     * @param array $data
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月26日16:06:04
     */
    public function getCommentDetail($requests = [])
    {
        // 模拟参数
        // $requests = array( 
        //     'comment_id' => 1183, // 评论ID
        // );

        // 查询评论信息
        $commentInfo = GoodsComment::alias('c')
            ->field('c.id,c.type,c.thumb,c.video,c.create_time,c.content,c.star,u.head_img,u.user_nickname,c.sku_id')
            ->where(['c.id' => $requests['comment_id']])
            ->join('user u', 'c.user_id = u.id', 'RIGHT')
            ->find();
        if ($commentInfo) {
            if ($commentInfo['type'] == 1) {
                $commentInfo['user_nickname'] = lang('匿名用户');
                $commentInfo['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
            } else {
                $commentInfo['head_img'] = get_file_url($commentInfo['head_img']);
            }
            $commentInfo['thumb'] = get_files_url($commentInfo['thumb']);
            $commentInfo['video'] = get_files_url($commentInfo['video']);
            //$commentInfo['create_time'] = date('Y-m-d', $commentInfo['create_time']);
            if ($commentInfo['sku_id']) {
                $commentInfo['key_name'] = Db::name('goods_sku')->where('sku_id', $commentInfo['sku_id'])->value('key_name');
            }

            // 查询二级评论
            if (empty($commentInfo['pid'])) {
                $commentInfo['comment_list'] = GoodsComment::alias('c')
                    ->field('c.id,c.type,c.thumb,c.video,c.create_time,c.content,c.star,u.head_img,u.user_nickname,c.sku_id')
                    ->where(['c.pid' => $commentInfo['id']])
                    ->join('user u', 'c.user_id = u.id', 'RIGHT')
                    ->select()->each(function ($item) {
                        if ($item['type'] == 1) {
                            $item['user_nickname'] = lang('匿名用户');
                            $item['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
                        } else {
                            $item['user_nickname'] = str2sub($item['user_nickname'], 1, '***');
                            $item['head_img'] = get_file_url($item['head_img']);
                        }
                        $item['thumb'] = get_files_url($item['thumb']);
                        $item['video'] = get_files_url($item['video']);
                        //$item['create_time'] = date('Y-m-d', $item['create_time']);
                        if ($item['sku_id']) {
                            $item['key_name'] = Db::name('goods_sku')->where('sku_id', $item['sku_id'])->value('key_name');
                        }

                        return $item;
                    });
            }
        }

        return ApiReturn::r(1, empty($commentInfo) ? [] : $commentInfo, lang('请求成功'));
    }

    /**
     * 直接购买获取创建订单的信息
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/24 18:01
     */
    public function get_one_order_info($data = [], $user = [])
    {
        $send_type = $data['send_type'] ?? 0;
        if ($data['sku_id']) {
            //获取sku商品信息
            $goods = GoodsSku::alias('sku')->leftJoin('goods g', 'g.id=sku.goods_id')
                ->where('sku_id', $data['sku_id'])
                ->field('g.id,sku.sku_id,g.cid,g.discounts,g.name,g.freight_template_id,g.is_shipping,sku.shop_price,sku.member_price,sku.key_name,sku.market_price,g.thumb,sku.stock,g.is_sale,g.status,sku.status as skustatus,sku.sku_weight,g.amount_condition,g.is_integral')
                ->find();
            if (!$goods['skustatus']) {
                return ApiReturn::r(0, [], lang('商品无效，无法购买'));
            }
        } else {
            //获取单商品信息
            $goods = GoodsModel::where('id', $data['goods_id'])->field('id,cid,discounts,name,shop_price,member_price,market_price,thumb,stock,is_shipping,is_sale,status,freight_template_id,weight as sku_weight,amount_condition,is_integral')->find();
        }

        if (!$goods['is_sale']) {
            return ApiReturn::r(0, [], lang('商品下架，无法购买'));
        }
        if (!$goods['status']) {
            return ApiReturn::r(0, [], lang('商品无效，无法购买'));
        }
        if (!$data['activity_id']) {
            if ($goods['stock'] < $data['number']) {
                return ApiReturn::r(0, [], lang('库存不足，无法购买'));
            }
        }

        $goods['number'] = $data['number']; // 购买数量
        $goods['thumb'] = get_file_url($goods['thumb']);//商品缩略图
        $info['order_type'] = 3;//订单类型
        $user_level = Db::name('user')->where(['id' => $user['id']])->value('user_level');
        //是否活动减价
        if ($data['activity_id']) {
            $act_map = [];
            $nowTime = time();
            $act_map[] = ['status', '=', 1];
            $act_map[] = ['activity_id', '=', $data['activity_id']];
            if ($data['sku_id']) {
                $act_map[] = ['sku_id', '=', $data['sku_id']];
            }
            if ($data['goods_id']) {
                $act_map[] = ['goods_id', '=', $data['goods_id']];
            }
            Db::startTrans();
            try {
                $activity_where = [];
                $goods_activity = Db::name('goods_activity_details')->where($act_map)->find();
                if ($goods_activity['sales_integral'] == 0 && $goods_activity['least_count'] == 0) {
                    $activity_where = [['sdate', 'lt', $nowTime], ['edate', 'gt', $nowTime]];
                }
                $activity = Db::name('goods_activity')->where(
                    [['id', '=', $data['activity_id']],
                        ['status', '=', 1],
                    ]
                )->where($activity_where)->find();
                if (!$goods_activity || !$activity) {
                    exception(lang('活动不存在'));
                }
                if ($goods_activity['stock'] < $data['number']) {
                    exception(lang('活动库存不足'));
                }
                if ($activity['type'] == 8) {
                    $user_score = User::where(['id' => $user['id']])->value("score") ?? 0;
                    if ($user_score < $goods_activity['sales_integral']) {
                        exception(lang('积分余额不足'));
                    }
                }
                $goods['initial_price'] = $user_level <= 0 ? $goods['shop_price'] : $goods['member_price'];
                $goods['activity_price'] = $goods_activity['activity_price'];
                $goods['member_activity_price'] = $goods_activity['member_activity_price'];

                $goods['activity_type'] = $activity['type'];
                $goods['sales_integral'] = $goods_activity['sales_integral'];
                $goods['is_pure_integral'] = $goods_activity['is_pure_integral'];
                $calculate_price = $user_level <= 0 ? $goods['activity_price'] : $goods['member_activity_price'];
                //$calculate_price = $goods['activity_price'];
                switch ($activity['type']) {
                    case 1:
                        $info['order_type'] = 6;
                        break;
                    case 2:
                        $info['order_type'] = 5;
                        break;
                    case 3:
                        $info['order_type'] = 7;
                        break;
                    case 4:
                        $info['order_type'] = 9;
                        break;
                    case 6:
                        $info['order_type'] = 11;
                        break;
                    case 8:
                        $info['order_type'] = 12;
                        break;
                    case 10:
                        $info['order_type'] = 14;
                        break;
                    default:
                        $info['order_type'] = 3;
                        break;
                }
            } catch (\Exception $e) {
                Db::rollback();
                return ApiReturn::r(0, [], $e->getMessage());
            }
        } else {//非活动的商品
//            if ($user_level > 0) {
//                $calculate_price = $goods['member_price'];
//            } else {
                $calculate_price = $goods['shop_price'];
//            }
        }

        $goods['activity_id'] = $data['activity_id'] ? $data['activity_id'] : 0;
        $freight = Freight::where("id", $goods['freight_template_id'])->field("name,freight_explain")->find();
        $goods['freight_explain'] = $freight['freight_explain'];
        $goods['freight_name'] = $freight['name'];
        $info['goods'][] = $goods;
        $goods_price = bcmul($calculate_price, $data['number'], 2);//商品总价

        //如果提交了优惠券id，则查询数据库中的优惠券
        $cou = new \app\operation\model\CouponRecord();

        if ($info['order_type'] != 7) {
            if ($info['order_type'] != 12) {
                if ($data['coupon_id']) {
                    $coupon = $cou->get_user_coupon(['cr.user_id' => $user['id'], 'cr.id' => $data['coupon_id'], 'cr.status' => 1, 'oc.type' => 0]);
                    if ($coupon) {
                        $info['coupon'] = $coupon;
                    }
                } else {
                    if (!$data['use_not_coupon']) {
                        $goods_cid[] = $goods['cid'];
                        $coupon = $cou->get_best_coupon($user['id'], $goods_price, $goods_cid);
                        $info['coupon'] = $coupon[0];
                    }
                }
            }
        } else {
            $info['sdate'] = date("m月d日 H:i", $activity['sdate']);
            $info['edate'] = date("m月d日 H:i", $activity['edate']);
            $info['deposit'] = $goods_activity['deposit'];
            $info['balance'] = bcsub($goods_activity['activity_price'], $goods_activity['deposit'], 2);
            $info['presell_stime'] = date("m月d日 H:i", $activity['presell_stime']);
            $info['presell_etime'] = date("m月d日 H:i", $activity['presell_etime']);
        }

        //获取默认地址信息
        $where[] = $map1[] = ['user_id', '=', $user['id']];
        if ($data['address_id'] != 0) {
            $where[] = ['address_id', '=', $data['address_id']];
        } else {
            $where[] = ['is_default', '=', 1];
        }
        $address = (new AddressModel())->get_one_address($where);
        $info['address'] = $address ? $address : (new AddressModel())->get_one_address($map1);
        // 不包邮开始计算运费
        if ($info['order_type'] != 12) {
            if ($goods['is_shipping'] == 0) {
                $shipping_coupon = $cou->get_shipping_coupon($user['id']);
                //非自提类型判断包邮范围
                if ($send_type != 1) {
                    $freight = new \app\goods\model\Freight();
//                    dump($goods['freight_template_id']);
                    $res = $freight->checkAddress($address['city_id'], $goods['freight_template_id']);
//                    dump($res);die;
                    if (!$res && $address['city_id']) {
                        $info['is_freight'] = $res;
//                        return ApiReturn::r(0, [], lang('很抱歉，您的收货地址不在配送范围内'));
                        $info['address']['is_distribution'] = 1;   /**不在配送范围给新字段赋值，前端判断处理**/
                    }
                }
                
                // 商品总重量
                $goods_total_weight = ceil(bcdiv(bcmul($goods['sku_weight'], $data['number'], 2), 1000, 2));   //商品总重量（g）转（kg）
                // 计算配送费用,不再配送范围内邮费无法计算，应阻止下单
                $info['express_price'] = $res ?
                    $freight->get($goods['freight_template_id'], ['rule'])->calcTotalFee($data['number'], $goods_total_weight, $address['city_id'], $goods['freight_template_id']) : 0;
                if ($goods['amount_condition'] > 0 && $goods['amount_condition'] <= $goods_price) {
                    $info['express_price'] = 0;
                }
            }
        }
        // halt($goods_price);
        // 商品总价
        $info['goods_price'] = $goods_price;

        //选择自提，运费为0
        if ($send_type == 1) {
            $info['express_price'] = 0;
        }

        $express_price = $info['express_price'];

        if ($info['order_type'] != 7) {
            if ($shipping_coupon[0]) {
                $express_price = 0;
                $info['shipping_coupon'] = $shipping_coupon[0];
            }
        }
        if ($info['order_type'] != 7 && $info['order_type'] != 12) {
            //加上运费后的价格
            $total_money = bcadd($goods_price, $express_price, 2);
            // 减去优惠券后的价格
            $info['payable_money'] = bcsub($total_money, $info['coupon']['money'], 2);
            if ($data['coin_id']) {
                $info['payable_money'] = bcsub($info['payable_money'], $coin[0]['num'], 2);
            }
        } else if ($info['order_type'] == 7) {
            $info['payable_money'] = $goods_activity['deposit'];
        } else if ($info['order_type'] == 12) {
            $info['payable_money'] = $goods_activity['activity_price'];
            $info['sales_integral'] = $goods_activity['sales_integral'];
            $info['is_pure_integral'] = $goods_activity['is_pure_integral'];
        }


        //积分抵扣金额
        $info['is_integral_reduce'] = 0;
        if ($goods['is_integral'] == 1) {
            $integral_reduce = Order::integral_reduce($user['id'], $info['payable_money']);
            if ($integral_reduce['is_integral_reduce'] == 1) {
                $info['score'] = $integral_reduce['score'];
                $info['reduce_money'] = $integral_reduce['reduce_money'];
                $info['integral_reduce'] = $integral_reduce['integral_reduce'];
                $info['integral_payable_money'] = $integral_reduce['integral_payable_money'];
                $info['is_integral_reduce'] = 1;
            }
        }

        //获取用户默认的提货人信息 wangph 修改于 2021-4-21
        $info['pickup_default'] = UserPickupModel::getDefaultInfo($user['id']);
        return ApiReturn::r(1, $info, lang('请求成功'));
    }


    /**
     * 直接购买批发商品获取创建订单的信息
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/24 18:01
     */
    public function get_wholesale_order_info($data = [], $user = [])
    {
        if ($data['sku_ids']) {
            //获取sku商品信息
            $sku_arr = json_decode($data['sku_ids'], true);
            foreach ($sku_arr as &$value) {
                $goods[] = GoodsSku::alias('sku')->leftJoin('goods g', 'g.id=sku.goods_id')
                    ->where('sku_id', $value['sku_id'])
                    ->field('g.id,sku.sku_id,g.cid,g.discounts,g.name,g.freight_template_id,g.is_shipping,sku.shop_price,sku.member_price,sku.key_name,sku.market_price,g.thumb,sku.stock,g.is_sale,g.status,sku.status as skustatus,sku.sku_weight,g.amount_condition,g.is_integral,g.is_wholesale')
                    ->find();
            }
            foreach ($goods as $k => $v) {
                if (!$v['is_sale']) {
                    return ApiReturn::r(0, [], lang('商品下架，无法购买'));
                }
                if (!$v['status']) {
                    return ApiReturn::r(0, [], lang('商品无效，无法购买'));
                }
                $goods[$k]['number'] = $sku_arr[$k]['number'];
                $goods[$k]['thumb'] = get_file_url($goods[$k]['thumb']);//商品缩略图
                $freight = Freight::where("id", $v['freight_template_id'])->field("name,freight_explain")->find();
                $goods[$k]['freight_explain'] = $freight['freight_explain'];
                $goods[$k]['freight_name'] = $freight['name'];
                $info['goods'] = $goods;
                $calculate_price = Db::name('goods_wholesale')->where(['sku_id' => $v['sku_id'], 'goods_id' => $data['goods_id']])->field('start_batch,trade_price')->select();
                foreach ($calculate_price as &$vl) {
                    if ($sku_arr[$k]['number'] >= $vl['start_batch']) {
                        $goods[$k]['price'] = $vl['trade_price'];
                    }
                }
            }
            $goods_price = 0;
            foreach ($goods as $key => $value) {
                if (isset($value['price'])) {
                    $price = $value['price'];
                } else {
                    $price = $value['shop_price'];
                }
                $goods_price += bcmul($price, $value['number'], 2);
            }
            $info['goods_price'] = $goods_price;

            $where[] = $map1[] = ['user_id', '=', $user['id']];
            if ($data['address_id'] != 0) {
                $where[] = ['address_id', '=', $data['address_id']];
            } else {
                $where[] = ['is_default', '=', 1];
            }
            $address = (new AddressModel())->get_one_address($where);
            $info['address'] = $address ? $address : (new AddressModel())->get_one_address($map1);
            foreach ($goods as $key => $value) {
                //不包邮
                if ($value['is_shipping'] == 0) {
                    $shipping_coupon = $cou->get_shipping_coupon($user['id']);
                    $freight = new \app\goods\model\Freight();
                    $res = $freight->checkAddress($address['city_id'], $value['freight_template_id']);
                    $info['is_freight'] = $res;
                    // 商品总重量
                    $goods_total_weight = ceil(bcdiv(bcmul($value['sku_weight'], $value['number'], 2), 1000, 2));   //商品总重量（g）转（kg）
                    // 计算配送费用
                    $express_price = $res ?
                        $freight->get($value['freight_template_id'], ['rule'])->calcTotalFee($value['number'], $goods_total_weight, $address['city_id'], $value['freight_template_id']) : 0;
                    if ($value['amount_condition'] > 0 && $value['amount_condition'] <= $goods_price) {
                        $express_price = 0;
                    }
                }
                $info['express_price'] += $express_price;
            }
        } else {
            //获取单商品信息
            $goods = GoodsModel::where('id', $data['goods_id'])->field('id,cid,discounts,name,shop_price,member_price,market_price,thumb,stock,is_shipping,is_sale,status,freight_template_id,weight as sku_weight,amount_condition,is_integral')->find();
            if (!$goods['is_sale']) {
                return ApiReturn::r(0, [], lang('商品下架，无法购买'));
            }
            if (!$goods['status']) {
                return ApiReturn::r(0, [], lang('商品无效，无法购买'));
            }
            $goods['number'] = $data['number']; // 购买数量
            $goods['thumb'] = get_file_url($goods['thumb']);//商品缩略图

            $calculate_price = $goods['member_price'];

            $freight = Freight::where("id", $goods['freight_template_id'])->field("name,freight_explain")->find();
            $goods['freight_explain'] = $freight['freight_explain'];
            $goods['freight_name'] = $freight['name'];
            $info['goods'][] = $goods;


            $calculate_price = Db::name('goods_wholesale')->where(['sku_id' => 0, 'goods_id' => $data['goods_id']])->field('start_batch,trade_price')->select();
            foreach ($calculate_price as &$vl) {
                if ($data['number'] >= $vl['start_batch']) {
                    $goods['price'] = $vl['trade_price'];
                }
            }

            if (isset($goods['price'])) {
                $price = $goods['price'];
            } else {
                $price = $goods['shop_price'];
            }
            $goods_price = bcmul($price, $data['number'], 2);//商品总价

            //获取默认地址信息
            $where[] = $map1[] = ['user_id', '=', $user['id']];
            if ($data['address_id'] != 0) {
                $where[] = ['address_id', '=', $data['address_id']];
            } else {
                $where[] = ['is_default', '=', 1];
            }
            $address = (new AddressModel())->get_one_address($where);
            $info['address'] = $address ? $address : (new AddressModel())->get_one_address($map1);
            if ($goods['is_shipping'] == 0) {
                $shipping_coupon = $cou->get_shipping_coupon($user['id']);
                $freight = new \app\goods\model\Freight();
                $res = $freight->checkAddress($address['city_id'], $goods['freight_template_id']);
                if (!$res) {
                    $info['is_freight'] = $res;
                    //return ApiReturn::r(0, [], lang('很抱歉，您的收货地址不在配送范围内'));
                }
                // 商品总重量
                $goods_total_weight = ceil(bcdiv(bcmul($goods['sku_weight'], $data['number'], 2), 1000, 2));   //商品总重量（g）转（kg）
                // 计算配送费用
                $info['express_price'] = $res ?
                    $freight->get($goods['freight_template_id'], ['rule'])->calcTotalFee($data['number'], $goods_total_weight, $address['city_id'], $goods['freight_template_id']) : 0;
                if ($goods['amount_condition'] > 0 && $goods['amount_condition'] <= $goods_price) {
                    $info['express_price'] = 0;
                }
            } else {
                $info['express_price'] = 0;
            }
            $info['goods_price'] = $goods_price;
        }
        return ApiReturn::r(1, $info, lang('请求成功'));
    }


    /**
     * 购物车购买获取创建订单的信息
     * @param array $data .coin_id int 牛币券ID[选填]
     * @param array $data .cart_ids String 购物车ID 1,2,3,4,5[必填]
     * @param array $data .use_not_coupon int 不使用优惠券1;使用优惠券:0[选填]
     * @param array $data .coupon_id int 优惠券ID[选填]
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/24 18:01
     */
    public function get_cart_order_info($data = [], $user = [])
    {
        $send_type = $data['send_type'] ?? 0;
        $cart_ids = explode(',', $data['cart_ids']);
        $map[] = ['user_id', '=', $user['id']];
        $map[] = ['id', 'in', $cart_ids];
        $cart = \app\goods\model\Cart::where($map)->select();
        //获取默认地址信息
        $where[] = ['user_id', '=', $user['id']];
        $where1[] = ['user_id', '=', $user['id']];
        if ($data['address_id']) {
            $where[] = ['address_id', '=', $data['address_id']];
        }
//        $where[] = ['is_default', '=', 1];
        $address = AddressModel::get_one_address($where);
        if($address){
            $info['address'] = $address;
        }else{
            $where1[] = ['is_default', '=', 1];
            $address = AddressModel::get_one_address($where1);
            $info['address'] = $address ? $address : [];
        }

        $GoodsSku = new GoodsSku();
        //处理商品，过滤掉库存不足的，下架的，禁用的商品
        $money = 0;
        $express = 0;
        $freight = new \app\goods\model\Freight();
        //list_fail保存不能购买的商品
        $list_fail = [];
        foreach ($cart as $v) {
            if ($v['sku_id'] != 0) {
                //获取sku商品信息
                $goods = $GoodsSku->alias('sku')->leftJoin('goods g', 'g.id=sku.goods_id')
                    ->where('sku_id', $v['sku_id'])
                    ->field('g.id,g.discounts,sku.sku_id,g.cid,g.name,g.amount_condition,sku.shop_price,sku.member_price,sku.key_name,sku.market_price,sku.sku_weight,g.thumb,sku.stock,g.is_shipping,g.freight_template_id,g.is_sale,g.status,sku.status as skustatus,g.is_wholesale')
                    ->find();
                if (!$goods['skustatus']) {
                    //无效的sku
                    $goods['thumb'] = get_file_url($goods['thumb']);
                    $goods['reason'] = lang('无效的sku');
                    $list_fail[] = $goods;
                    continue;
                }
            } else {
                //获取单商品信息
                $goods = GoodsModel::where('id', $v['goods_id'])->field('id,cid,discounts,name,shop_price,member_price,market_price,thumb,stock,is_shipping,freight_template_id,is_sale,status,weight,amount_condition,is_wholesale')->find();
            }
            $goods['number'] = $v['num'];
            $goods['thumb'] = get_file_url($goods['thumb']);
            if (!$goods['is_sale']) {
                //下架的商品
                $goods['reason'] = lang('下架的商品');
                $list_fail[] = $goods;
                continue;
            }
            if (!$goods['status']) {
                $goods['reason'] = lang('禁用的商品');
                $list_fail[] = $goods;
                //禁用的商品
                continue;
            }
            if ($goods['stock'] < $v['num']) {
                $goods['reason'] = lang('库存不足的商品');
                $list_fail[] = $goods;
                //库存不足的商品
                continue;
            }
            // 不包邮开始计算运费
            if ($goods['is_shipping'] == 0) {
                //send_type =1  自提类型，不判断配送范围
                if ($data['send_type'] != 1) {
//                    dump($address);die;$address
                    if($address){
                        $res = $freight->checkAddress($address['city_id'], $goods['freight_template_id']);
                        if (!$res) {
                            $goods['reason'] = lang('收货地址不在配送范围');
                            // 收货地址不在配送范围
                            $list_fail[] = $goods;
                            continue;
                        }
                    }

                }

                // 商品总重量
                if ($v['sku_id'] != 0) {
                    //$goods_total_weight = ceil(bcdiv(bcmul($goods['sku_weight'], $v['num'], 2), 1000, 2));
                    $goods_total_weight = $goods['sku_weight'] * $v['num'];

                } else {
                    //$goods_total_weight = ceil(bcdiv(bcmul($goods['weight'], $v['num'], 2), 1000, 2));
                    $goods_total_weight = $goods['weight'] * $v['num'];

                }
                // 计算配送费用
                if ($goods['amount_condition'] > 0 && $goods['amount_condition'] <= ($v['num'] * $goods['shop_price'])) {

                } else {
                    $express_arr[] = [
                        'freight_template_id' => $goods['freight_template_id'],
                        'weight' => $goods_total_weight,
                        'num' => $v['num']
                    ];
                }
            }

            $freight = Freight::where("id", $goods['freight_template_id'])->field("name,freight_explain")->find();
            $goods['freight_explain'] = $freight['freight_explain'];
            $goods['freight_name'] = $freight['name'];

            $goodslist[] = $goods;

            $goods_id[] = $v['goods_id'];
            $goods_cid[] = $goods['cid'];

            if ($goods['is_wholesale'] == 1) {
                $calculate_price = Db::name('goods_wholesale')->where(['goods_id' => $v['goods_id'], 'sku_id' => $v['sku_id']])->where("start_batch <= " . $v['num'])->order('start_batch DESC')->value('trade_price');
                if (!$calculate_price) {
                    $calculate_price = $goods['shop_price'];
                }
                $goods['shop_price'] = $calculate_price;
                $jiage[$v['goods_id']]['price'] = $v['num'] * $calculate_price;
            } else {
                $user_level = Db::name('user')->where(['id' => $user['id']])->column('user_level');
//                if ($user_level[0] > 0) {
//                    $calculate_price = $goods['member_price'];
//                    $jiage[$v['goods_id']]['price'] = $v['num'] * $goods['member_price'];
//                } else {
                    $calculate_price = $goods['shop_price'];
                    $jiage[$v['goods_id']]['price'] = $v['num'] * $goods['shop_price'];
//                }
            }
            $money += $v['num'] * $calculate_price;
            $jiage[$v['goods_id']]['cid'] = $goods['cid'];
        }
        //结束循环
        //购物车，最后过滤掉无法结算的商品，如果为空
        if (count($goodslist) < 1) {
            //return ApiReturn::r(0, [], lang('商品不在配送范围或库存不足'));
        }

        // if (count($cart) != count($goodslist)) {
        //     $info['tip'] = 1;
        //     $info['msg'] = lang('购物车中有商品库存不足或者状态异常，已为您自动过滤');
        // }

        //运费计算
        $express_num = $express_weight = $express_arr_sum = [];
        if (!empty($express_arr)) {
            foreach ($express_arr as $key => $val) {
                $express_weight[$val['freight_template_id']][] = $val['weight'];
                $express_num[$val['freight_template_id']][] = $val['num'];
                $express_arr_sum[$val['freight_template_id']]['weight'] = ceil(array_sum($express_weight[$val['freight_template_id']]) / 1000);
                $express_arr_sum[$val['freight_template_id']]['num'] = array_sum($express_num[$val['freight_template_id']]);

            }
        }
        if (!empty($express_arr_sum)) {
            foreach ($express_arr_sum as $k => $v) {
                $res = $freight->checkAddress($address['city_id'], $k);
                $express += $res ? $freight->get($k, ['rule'])->calcTotalFee($v['num'], $v['weight'], $address['city_id'], $k) : 0;

            }
        }

        $info['tip'] = 0;
        $info['msg'] = '';
        $info['order_money'] = $money;
        $info['goods'] = $goodslist ? $goodslist : [];
        // 可用优惠券
        $cou = new \app\operation\model\CouponRecord();
        if (!$data['use_not_coupon']) {
            if ($data['coupon_id']) {
                $coupon = $cou->get_user_coupon(['cr.user_id' => $user['id'], 'cr.id' => $data['coupon_id'], 'cr.status' => 1, 'oc.type' => 0]);
                $coupon = [$coupon];
            } else {
                $coupon = $cou->get_best_coupon_new($user['id'], $goods_cid, $jiage);
            }
        } else {
            $coupon = [];
        }
        $info['coupon'] = $coupon[0];
        //选择自提，运费为0
        if ($send_type == 1) {
            $express = 0;
        }

        $info['express_price'] = $express;

        // 计算应付金额
        $shipping_coupon = $cou->get_shipping_coupon($user['id']);
        if ($shipping_coupon[0]) {
            $info['shipping_coupon'] = $shipping_coupon[0];
            $express = 0;
        }
        $info['order_type'] = 3;
        $info['payable_money'] = $info['coupon'] ? bcadd($money, bcsub($express, $info['coupon']['money'], 2), 2) : bcadd($money, $express, 2);
        if ($data['coin_id']) {
            $info['payable_money'] = bcsub($info['payable_money'], $coin[0]['num'], 2);
        }

        //获取用户默认的提货人信息 wangph 修改于 2021-4-21
        $info['pickup_default'] = UserPickupModel::getDefaultInfo($user['id']);
        $info['list_fail'] = $list_fail;
        return ApiReturn::r(1, $info, lang('请求成功'));
    }

    /**
     * 商品分享链接生成-H5+二维码
     * @param array $data
     * @param array $user
     * @author jxy [ 415782189@qq.com ]
     * @created 2020/3/24 16:01
     */
    public function share($data = [], $user = [])
    {
        $map[] = ['goods_id', '=', $data['goods_id']];
        $map[] = ['activity_id', '=', $data['activity_id']];
        $map[] = ['sku_id', '=', $data['sku_id']];
        $map[] = ['uid', '=', $user['id']];
        //安卓下载地址
        $is_update_apk = Db::name("admin_version_log")->where(["vid" => 1, "type" => 2, "status" => 1])->order("create_time DESC")->value("url");
        $is_update_apk = urlencode($is_update_apk);
        $shareGoods = Db::name('goods_share')->where($map)->find();
        $html = "/h5/index.html";
        if ($shareGoods) {
            $end_url = 'goods_id=' . $data['goods_id'] . '&activity_id=' . $data['activity_id'] . '&sku_id=' . $data['sku_id'] . '&user_id=' . $user['id'] . '&apk_download_url=' . $is_update_apk . '&share_sign=' . $shareGoods['share_sign'];
            $url = config('web_site_domain') . $html . '#/?' . $end_url;
            $info = ['url' => $url,
                'qrcode_url' => config('web_site_domain') . $shareGoods['qrcode_url']
            ];
            return ApiReturn::r(1, $info, lang('请求成功'));
        } else {
            Db::startTrans();
            try {
                $parms['goods_id'] = $data['goods_id'];
                $parms['activity_id'] = $data['activity_id'];
                $parms['uid'] = $user['id'];
                $parms['sku_id'] = $data['sku_id'];
                $parms['share_sign'] = \service\Str::randString(10, 0);
                $end_url = 'goods_id=' . $data['goods_id'] . '&activity_id=' . $data['activity_id'] . '&sku_id=' . $data['sku_id'] . '&user_id=' . $user['id'] . '&apk_download_url=' . $is_update_apk . '&share_sign=' . $parms['share_sign'];
                $url = config('web_site_domain') . $html . '#/?' . $end_url;
                $info = ['url' => $url];
                $request = $parms;
                $request['imgUrl'] = $info['url'];
                //生成二维码   二维码命名规则  用户ID-活动ID(默认0)-商品ID.png
                $code_url = PHPQrcode::generateQrcodeLogo($request);
                if (!$code_url) {
                    throw new \Exception(lang('生成分享二维码失败'));
                }
                $parms['qrcode_url'] = $code_url;
                $parms['create_time'] = time();
                $res = Db::name('goods_share')->insertGetId($parms);
                if (!$res) {
                    throw new \Exception(lang('生成分享识别码失败'));
                }
                $info['qrcode_url'] = config('web_site_domain') . $code_url;
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return ApiReturn::r(0, '', $e->getMessage());
            }
            TaskModel::doTask($user['id'], 'shareGoods');
            return ApiReturn::r(1, $info, lang('请求成功'));
        }
    }

    /**
     * 商品分享链接
     * @param array $data
     * @param array $user
     * @author zhougs
     * @created 2020年12月15日10:58:34
     */
    public function goods_share($data = [], $user = [])
    {
        $map[] = ['goods_id', '=', $data['goods_id']];
        $map[] = ['activity_id', '=', $data['activity_id']];
        $map[] = ['uid', '=', $user['id']];
        $shareGoods = Db::name('goods_share')->where($map)->find();
        if ($shareGoods) {
            $info = ['url' => config('web_site_domain') . '/goods/index/index?share_sign=' . $shareGoods['share_sign']];
            return ApiReturn::r(1, $info, lang('请求成功'));
        } else {
            Db::startTrans();
            try {
                $parms['goods_id'] = $data['goods_id'];
                $parms['activity_id'] = $data['activity_id'];
                $parms['uid'] = $user['id'];
                $parms['share_sign'] = \service\Str::randString(10, 0);
                $parms['create_time'] = time();


                $res = Db::name('goods_share')->insertGetId($parms);
                if (!$res) {
                    throw new \Exception(lang('生成分享识别码失败'));
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return ApiReturn::r(0, '', $e->getMessage());
            }
            $info = ['url' => config('web_site_domain') . '/goods/index/index?share_sign=' . $parms['share_sign']];
            TaskModel::doTask($user['id'], 'shareGoods');
            return ApiReturn::r(1, $info, lang('请求成功'));
        }
    }

    /**
     * 获取临时二维码
     * @param $requests .scene string 场景
     * @param $requests .page url 已经发布的小程序存在的页面路径
     * @param $requests .with int 二维码宽度
     * @return \think\response\Json
     * @since 2020年8月1日17:57:40
     * @author zenghu<1427305236@qq.com>
     */
    public function getMiniQrcode($requests = [], $user = [])
    {
        $width = empty($requests['width']) ? 430 : $requests['width'];

        try {
            $result = addons_action('WeChat', 'AuthCode', 'get_qrcode_limit', [$requests['scene'], $requests['page'], $width]);
            return ApiReturn::r(1, [
                // 请勿修改文件头长度 避免客户端出错
                "base64" => "data:image/png;base64," . $result
            ], lang('请求成功'));
        } catch (\Exception $e) {
            return ApiReturn::r(0, [], $e->getMessage());
        }
    }

    /**
     * 发送通知
     * @param $user .id int 发送人ID [必须]
     * @param $requests .goodsId int 秒杀活动商品ID [必须]
     * @param $requests .type int 发送消息的类型1为秒杀提醒 [必须]
     * @param $requests .notifyTime string 秒杀活动提醒用户的时间 [必须|10:30]
     * @param $requests .pageUrl string 秒杀活动跳转的路径 [非必须]
     * @return \think\response\Json
     * @since 2020年8月27日09:20:09
     * @author zenghu<1427305236@qq.com>
     */
    public function sendNotify($requests = [], $user = [])
    {
        // 检测参数值
        if (empty($user['id']) || empty($requests['goodsId']) || empty($requests['type']) || empty($requests['notifyTime'])) {
            return ApiReturn::r(0, [], lang('参数不能为空'));
        }

        // 组装需要提醒的数据
        $queueDate = array(
            'q_user_id' => $user['id'],
            'q_goods_id' => $requests['goodsId'],
            'q_type' => $requests['type'],
            'q_implement_time' => $requests['notifyTime']
        );

        // 限制重复提醒
        $queue = Db::name('queue');
        if ($queue->where($queueDate)->find()) {
            return ApiReturn::r(1, [], lang('已预约发送提醒'));
        }

        // 添加需要通知的数据
        $queueDate['q_extent'] = json_encode(['page_url' => $requests['pageUrl']]);
        $queueRe = $queue->insert($queueDate);

        // 判断添加是否成功
        if ($queueRe) {
            return ApiReturn::r(1, [], lang('预约发送执行成功'));
        }

        return ApiReturn::r(0, [], lang('预约发送执行失败'));
    }

    /**
     * 取消发送通知
     * @param $user .id int 发送人ID [必须]
     * @param $requests .goodsId int 秒杀活动商品ID [必须]
     * @param $requests .type int 发送消息的类型1为秒杀提醒 [必须]
     * @param $requests .notifyTime string 秒杀活动提醒用户的时间 [必须|10:30]
     * @return \think\response\Json
     * @since 2020年8月27日09:20:09
     * @author zenghu<1427305236@qq.com>
     */
    public function sendCancelNotify($requests = [], $user = [])
    {
        // 检测参数值
        if (empty($user['id']) || empty($requests['goodsId']) || empty($requests['type']) || empty($requests['notifyTime'])) {
            return ApiReturn::r(0, [], lang('参数不能为空'));
        }

        // 取消通知
        $queueRe = Db::name('queue')->where([
            'q_user_id' => $user['id'],
            'q_goods_id' => $requests['goodsId'],
            //'q_type' => $requests['type'],
            'q_implement_time' => $requests['notifyTime'],
        ])->delete();

        // 判断添加是否成功
        if ($queueRe) {
            return ApiReturn::r(1, [], lang('预约发送取消成功'));
        }

        return ApiReturn::r(0, [], lang('预约发送取消失败'));
    }

    /**
     * 商品跑马灯
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhougs
     */
    public function getUserBuyGoods($data, $user)
    {
        // 检测参数值
        if (empty($data['goods_id'])) {
            return ApiReturn::r(0, [], lang('参数不能为空'));
        }
        $userData = [];
        $where[] = ["ogl.goods_id", "=", $data['goods_id']];
        $where[] = ["o.status", "gt", 0];
        $where[] = ["o.pay_status", "=", 1];

        $orderUser = \app\common\model\Order::alias("o")
            ->join("order_goods_list ogl", 'o.order_sn = ogl.order_sn', 'left')
            ->field('user_id')
            ->where($where)
            ->orderRaw("RAND()")
            ->find();
        if ($orderUser) {
            $userData = Db::name("user")->field("id,user_nickname,head_img")->where("id", $orderUser['user_id'])->find();
            if ($userData) {
                $userData['head_img'] = get_file_url($userData['head_img']);
            }
        }
        return ApiReturn::r(1, $userData, lang('获取成功'));
    }

    /**
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @author zhougs
     */
    public function GoodsSaleNumTop($data, $user)
    {
        if (!$data['cid']) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $resData = [];
        $resData['list'] = $this->GoodsSaleNum($data['cid']);
        if (count($resData) > 0) {
            $topName = Db::name("goods_hot_top")->where(["status" => 1, "category_id" => $data['cid']])->value("name");
            $resData['top_name'] = $topName;
        }
        return ApiReturn::r(1, $resData, lang('请求成功'));
    }

    //获取商品销售情况
    public function GoodsSaleNum($cid)
    {
        if (!$cid) {
            return [];
        }
        $goods = GoodsModel::where("cid", $cid)->order("sales_sum DESC")->limit(10)->select();
        $resData = [];
        if (count($goods) > 0) {
            $eTime = time();
            $sTime = $eTime - 86400 * 7;
            foreach ($goods as $key => $val) {
                $where = [];
                $resData[$key]['id'] = $val['id'];
                $resData[$key]['name'] = $val['name'];
                $resData[$key]['thumb'] = get_file_url($val['thumb']);
                $resData[$key]['shop_price'] = $val['shop_price'];
                $where[] = ["o.create_time", "between", [$sTime, $eTime]];
                $where[] = ["ogl.goods_id", "=", $val['id']];
                $where[] = ["o.status", "gt", 0];
                //七天内购买件数
                $sevenDaysSale = Db::name("order")
                    ->alias("o")
                    ->join("order_goods_list ogl", "o.order_sn = ogl.order_sn", "left")
                    ->where($where)
                    ->sum("ogl.num") ?: 0;
                $resData[$key]['seven_days_sale'] = $sevenDaysSale;

                //累计会员购买量
                $userBuyNum = Db::name("order")
                    ->alias("o")
                    ->join("order_goods_list ogl", "o.order_sn = ogl.order_sn", "left")
                    ->where([["ogl.goods_id", "=", $val['id']], ["o.status", "gt", 0]])
                    ->count("o.user_id") ?: 0;
                $resData[$key]['user_buy_num'] = $userBuyNum;
            }
        }
        return $resData;
    }

    //获取商品销售排行
    public function getGoodsSalesRanking($goods_id, $cid)
    {
        $goods = GoodsModel::where("cid", $cid)->order("sales_sum DESC")->column("id");
        if (count($goods) > 0) {
            $array = array_flip($goods);
            return $array[$goods_id] + 1;
        }
        return 0;
    }

    /**
     * 高级搜索栏数据返回
     * @param array $requests .keywords 搜索关键字
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月21日15:19:36
     */
    public function getAdvancedSearch($requests = [])
    {
        // 模拟参数
        // $requests = array(
        //     'keyword' => lang('白酒'),
        //     'goods_cid' => '1',
        // );

        // 获取商品信息
        $where = self::keywordsHandle($requests['keyword']);
        if ($requests['goods_cid']) {
            if ($where) {
                $where .= " AND cid = " . $requests['goods_cid'];
            } else {
                $where = "cid = " . $requests['goods_cid'];
            }
        }
        $goodsList = GoodsModel::getGoodsList($where);

        // 获取品牌信息
        $brandList = [];
        if (!empty($goodsList['brand_ids'])) {
            $brand_ids = self::uniqueStr($goodsList['brand_ids']);
            if (!empty($brand_ids)) {
                $brandList = BrandModel::field('id brand_id, name brand_name')->where("id IN ({$brand_ids}) AND status = 1")->select();
            }
        }

        // 获取商品分类信息
        $typeList = self::getTypeList(self::uniqueStr($goodsList['cids']), $brandList);

        // 获取商品服务标签
        $goodsServiceLabel = [];
        if (!empty($goodsList['goods_label_services'])) {
            $goods_label_services = self::uniqueStr($goodsList['goods_label_services']);
            if (!empty($goods_label_services)) {
                $goodsServiceLabel = GoodsLabelService::field("id label_id, name label_name")->where("id IN({$goods_label_services})")->select();
            }
        }

        // 信息返回
        $data = array(
            'goodsServiceLabel' => $goodsServiceLabel,
            'brandList' => $brandList,
            'typeList' => $typeList,
        );

        return ApiReturn::r(1, $data, lang('获取成功'));
    }

    /**
     * 高级搜索栏获取多条件商品条数
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月22日14:11:01
     */
    public function getGoodsCount($requests = [])
    {
        // 模拟参数
        // $requests = array(
        //     'keyword' => lang('白酒'), // 搜索关键字
        //     'service_lable_id' => '1,2,3,4', // 服务标签ID
        //     'min_price' => '100', // 最小金额
        //     'max_price' => '500', // 最大金额
        //     'brand_id' => '2,3,4', // 品牌ID
        //     'goods_cid' => '3', // 分类ID 
        //     'sku_id' => '68,74,75,76,67,62,34,50', // 分类ID
        // );

        // 处理搜索条件
        $where = '';
        // 搜索关键字
        if ($requests['keyword']) {
            $where = self::keywordsHandle($requests['keyword'], 'g.name');
        }
        // 服务标签ID
        if ($requests['service_lable_id']) {
            $labelWhereStr = self::serviceLabelHandel($requests['service_lable_id']);
            if ($where) {
                $where .= " AND {$labelWhereStr} ";
            } else {
                $where = $labelWhereStr;
            }
        }
        // 商品价格区间搜索
        $priceWhereStr = '';
        if (!empty($requests['min_price']) && $requests['min_price'] > 0) {
            $priceWhereStr = "(g.shop_price >= {$requests['min_price']})";
        }
        if (!empty($requests['max_price']) && $requests['max_price'] > 0) {
            $priceWhereStr = "(g.shop_price <= {$requests['max_price']})";
        }
        if (!empty($requests['min_price']) && ($requests['min_price'] > 0) && !empty($requests['max_price']) && ($requests['max_price'] > 0)) {
            if ($requests['min_price'] < $requests['max_price']) {
                $priceWhereStr = "(g.shop_price >= {$requests['min_price']} AND g.shop_price <= {$requests['max_price']})";
            }
            if ($requests['min_price'] > $requests['max_price']) {
                $priceWhereStr = "(g.shop_price >= {$requests['max_price']} AND g.shop_price <= {$requests['min_price']})";
            }
        }
        if ($priceWhereStr) {
            if ($where) {
                $where .= " AND {$priceWhereStr} ";
            } else {
                $where = $priceWhereStr;
            }
        }
        // 品牌ID
        if ($requests['brand_id']) {
            $brandWhereStr = self::brandLabelHandel($requests['brand_id']);
            if ($where) {
                $where .= " AND {$brandWhereStr} ";
            } else {
                $where = $brandWhereStr;
            }
        }
        // 商品分类单独查询
        if ($requests['goods_cid']) {
            //如果是有子分类
            $cid = Category::getChildsId($requests['goods_cid']);
            if (count($cid)) {
                $cids = implode(',', $cid);

                if ($where) {
                    $where .= " AND g.cid IN({$cids}) ";
                } else {
                    $where = " g.cid IN({$cids}) ";
                }

            } else {
                if ($where) {
                    $where .= " AND g.cid = {$requests['goods_cid']} ";
                } else {
                    $where = " g.cid = {$requests['goods_cid']} ";
                }
            }
        }
        // SKU检索商品
        if ($requests['sku_id']) {
            $goodsIds = self::getGoodsIdsForSkuId($requests['sku_id']);
            if ($where) {
                $where .= " AND g.id IN({$goodsIds}) ";
            } else {
                $where = " g.id IN({$goodsIds}) ";
            }
        }

        // 根据条件获取商品条数
        $count = GoodsModel::getGoodsListCount($where);

        return ApiReturn::r(1, $count, lang('获取成功'));
    }

    /**
     * 根据商品分类获取分类下支持的SKU列表
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月23日10:11:31
     */
    public function getGoodsSkuList($requests = [])
    {
        // 模拟参数
        // $requests = array(
        //     'goods_cid' => '202', // 分类ID 
        // );

        // 查询分类下的规格信息
        $skuTypeList = Type::alias('t')
            ->field('gts.id,gts.name')
            ->where(['t.cid' => $requests['goods_cid'], 't.status' => 1, 'gts.status' => 1])
            ->join('goods_type_spec gts', 'gts.typeid = t.id', 'LEFT')
            ->select();
        // 处理sku数据
        $goodsSkuList = [];
        foreach ($skuTypeList as $val) {
            $goodsSkuList[$val['name']] = GoodsTypeSpecItem::field('id,item')->where(['specid' => $val['id']])->select();
        }

        return ApiReturn::r(1, $goodsSkuList, lang('获取成功'));
    }

    /**
     * 根据SKUID获取商品IDS
     * @param $skuIds string SKUIDS（1,2,3,4）
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月23日11:29:33
     */
    private static function getGoodsIdsForSkuId($skuIds = '')
    {
        $where = self::serviceLabelHandel($skuIds, "REPLACE(`key`, '_', ',')");

        // 获取商品IDS
        $goodsIds = GoodsSku::field('GROUP_CONCAT(goods_id) goods_ids')->where($where)->find();

        // 处理并返回商品IDS
        return self::uniqueStr($goodsIds['goods_ids']);
        die;
    }

    /**
     * 获取商品分类信息
     * @param $cids string 商品分类IDS（1,2,3,4）
     * @param $brandList array 商品品牌列表
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月21日18:09:49
     */
    private static function getTypeList($cids = '', $brandList = [])
    {
        // 返回分类信息
        $categoryList = [];

        // 获取匹配条件的所有分类信息
        $categoryList['allType'] = self::getCategoryList($cids);

        // 获取品牌下分类标签
        if (!empty($brandList) && !empty($cids)) {
            foreach ($brandList as $key => $val) {
                $goodsList = GoodsModel::getGoodsList("cid IN({$cids}) AND brand_id = {$val['brand_id']}");
                $categoryList[$val['brand_id']] = self::getCategoryList(self::uniqueStr($goodsList['cids']));
            }
        }

        return $categoryList;
    }

    /**
     * 获取商品分类查询
     */
    private static function getCategoryList($cids = '')
    {
        if ($cids == '') {
            return [];
        }

        return Category::field('id cid, name cname')->where("id IN ({$cids}) AND status = 1")->select();
    }

    /**
     * 字符戳去重
     */
    private static function uniqueStr($str)
    {
        $arr = array_unique(explode(',', $str));
        return trim(implode(',', array_filter($arr)), ',');
    }

    /**
     * 服务标签处理
     */
    private static function serviceLabelHandel($str, $field = 'g.goods_label_service')
    {
        $serviceLabel = array_filter(array_unique(explode(',', $str)));
        if (!empty($serviceLabel)) {
            $labelWhereStr = '(';
            foreach ($serviceLabel as $key => $val) {
                $labelWhereStr .= " FIND_IN_SET({$val}, {$field}) OR ";
            }
        }
        $labelWhereStr = rtrim($labelWhereStr, 'OR ');

        return $labelWhereStr . ')';
    }

    /**
     * 品牌标签处理
     */
    private static function brandLabelHandel($str)
    {
        $brandLabel = array_filter(array_unique(explode(',', $str)));
        if (!empty($brandLabel)) {
            $labelWhereStr = '(';
            foreach ($brandLabel as $key => $val) {
                $labelWhereStr .= " g.brand_id = {$val} OR ";
            }
        }
        $labelWhereStr = rtrim($labelWhereStr, 'OR ');

        return $labelWhereStr . ')';
    }

    /**
     * 处理商品搜索关键字
     */
    private static function keywordsHandle($keyword = '', $name = 'name')
    {
        $where = '';
        if ($keyword) {
            $whereStr = "({$name} LIKE '%" . trim($keyword) . "%'";
            $keywords = preg_split("/[\s]+/", $keyword); // 处理特殊字符
            if (count($keywords) > 1) {
                $whereStr .= ' OR ';
                foreach ($keywords as $val) {
                    $whereStr .= "{$name} LIKE '%" . trim($val) . "%' OR ";
                }
                $whereStr = rtrim($whereStr, 'OR ');
            }
            $whereStr .= ')';

            $where = $whereStr;
        }

        return $where;
    }

    /**
     * 商品评论回复
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @author zhougs
     * @since 2021年1月6日14:25:34
     */
    public function goodsCommentReply($data, $user)
    {
        if (empty($data)) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $goodsComment = GoodsComment::get($data['gc_id']);
        if (!$goodsComment) {
            return ApiReturn::r(0, [], lang('没有此评论'));
        }
        $head_img = User::where("id", $user['user_id'])->value("head_img");
        $insData = [
            "user_id" => $user['user_id'],
            "user_nickname" => $user['user_nickname'],
            "head_img" => $head_img,
            "content" => $data['content'],
            "gc_id" => $data['gc_id'],
            "create_time" => time(),
        ];
        $res = Db::name("goods_comment_reply")->insertGetId($insData);
        if (!$res) {
            return ApiReturn::r(0, [], lang('回复失败'));
        }
        return ApiReturn::r(1, [], lang('回复成功'));
    }

    /**
     * 获取商品评论详情
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhougs
     * @since 2021年1月6日16:54:46
     */
    public function getGoodsCommentDetail($data, $user)
    {
        if (empty($data)) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $retData = [];
        $goods_comment = [];
        $goods_comment_reply_list = [];
        $comment = Db::name("goods_comment")->alias('c')
            ->join('user u', 'c.user_id = u.id')
            ->field('c.id,c.type,c.thumb,c.video,c.create_time,c.content,c.star,u.head_img,u.user_nickname,c.sku_id')
            ->where(["c.id" => $data['gc_id'], 'c.status' => 1])
            ->select();
        if (count($comment) > 0) {
            foreach ($comment as $key => $value) {
                if ($value['type'] == 1) {
                    $comment[$key]['user_nickname'] = lang('匿名用户');
                    $comment[$key]['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
                } else {
                    $comment[$key]['user_nickname'] = str2sub($value['user_nickname'], 1, '***');
                    $comment[$key]['head_img'] = get_file_url($value['head_img']);
                }
                $comment[$key]['thumb'] = get_files_url($value['thumb']);
                $comment[$key]['video'] = get_files_url($value['video']);
                $comment[$key]['create_time'] = date('Y-m-d', $value['create_time']);
                if ($value['sku_id']) {
                    $comment[$key]['key_name'] = GoodsSku::where('sku_id', $value['sku_id'])->value('key_name');
                }
                $comment[$key]['is_likes'] = GoodsCommentCollect::isCollection($user['user_id'], $value['id']) ?? 0;
                $comment[$key]['likes_num'] = GoodsCommentCollect::collectionNum($value['id']) ?? 0;
            }
            $goods_comment = $comment[0];
        }
        $retData['goods_comment_detail'] = $goods_comment;
        $goods_comment_reply = Db::name("goods_comment_reply")
            ->where(["gc_id" => $data['gc_id']])
            ->paginate()
            ->each(function ($item) {
                if ($item['is_merchant'] == 1) {
                    $item['user_nickname'] = lang('商家回复');
                } else {
                    $item['user_nickname'] = str2sub($item['user_nickname'], 1, '***');
                }

                $item['head_img'] = get_file_url($item['head_img']);
                $item['create_time'] = date("Y-m-d H:i:s", $item['create_time']);
                return $item;
            });
        if (count($goods_comment_reply) > 0) {
            $goods_comment_reply_list = $goods_comment_reply;
        }
        $retData['goods_comment_reply_list'] = $goods_comment_reply_list;
        return ApiReturn::r(1, $retData, lang('获取成功'));
    }

    /**
     * 评论点赞
     * @param array $data
     * @param array $user
     * @author zhougs
     * @created 2021年1月9日10:21:46
     */
    public function set_goods_comment_collection($data = [], $user = [])
    {
        $res = GoodsCommentCollect::isCollection($user['id'], $data['collect_id']);
        if ($res) {
            // 取消点赞/关注
            $ret = GoodsCommentCollect::delCollection($user['id'], $data['collect_id']);
            if ($ret) {
                return ApiReturn::r(1, ['is_collection' => 0], lang('已取消'));
            }
        } else {
            $data['user_id'] = $user['id'];
            $data['create_time'] = time();
            $ret = GoodsCommentCollect::create($data);
            if ($ret) {
                return ApiReturn::r(1, ['is_collection' => 1], lang('成功'));
            }
        }
        return ApiReturn::r(0, [], lang('操作失败'));
    }
}
