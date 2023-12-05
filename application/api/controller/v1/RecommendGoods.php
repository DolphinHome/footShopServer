<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\goods\model\Goods as GoodsModel;
use app\recommend\model\RecommendUsers;
use service\ApiReturn;

/**
 * 用户商品推荐
 * @package app\api\controller\v1
 */
class RecommendGoods extends Base
{
    /**
     * 基于用户行为内容推荐
     * @param array $requests.businessSign 推荐业务维度
     * @param array $requests.page 页码
     * @param array $requests.size 每页展示条数
     * @param array $user.id 行为人ID
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月16日10:09:33
     */
    public function getRecommendGoodsList($requests=[], $userInfo=[])
    {

        // 参数校验
        $page  = empty($requests['page']) ? 1 : $requests['page'];
        $size  = empty($requests['list_rows']) ? 10 : $requests['list_rows'];

        // 用户未登录直接系统推荐
        if (empty($userInfo['id'])) {
            $map[] = ['g.is_recommend', '=', 1];
        } else {
            // 查询用户行为数据分析数据 购物行为、加购行为、浏览行为、收藏行为、品牌维度、商品分类维度、商品标签综合维度,细分业务维度推荐,猜你喜欢数据
            $recommendMap = RecommendUsers::field('ru_recommend_goods_list,ru_recommend_goods_cart_list,ru_recommend_goods_order_list,ru_recommend_goods_goods_list,ru_guess_you_like_goods_list')->get(['user_id'=>$userInfo['id']]);
            if (empty($recommendMap)) { // 行为库中没有用户数据则视为新用户
                // TO DO LIST 暂且使用系统推荐商品数据，下一步采用K邻算法推荐相似用户购物数据
                $map[] = ['g.is_recommend', '=', 1];
            } else {
                // 根据数据推荐业务维度进行数据推荐
                switch ($requests['businessSign']) {
                    case 'index': // 首页推荐
                    case 'userCenter': // 用户中心推荐
                        $map[] = ['g.id', 'IN', $recommendMap['ru_recommend_goods_list']];
                        break;
                    case 'cart': // 购物车推荐
                        $map[] = ['g.id', 'IN', $recommendMap['ru_recommend_goods_cart_list']];
                        break;
                    case 'goodsDetail': // 商品详情推荐
                        $map[] = ['g.id', 'IN', $recommendMap['ru_recommend_goods_goods_list']];
                        break;
                    case 'orderDetail': // 订单详情推荐
                        $map[] = ['g.id', 'IN', $recommendMap['ru_recommend_goods_order_list']];
                        break;
                    case 'guessYouLike': // 猜你喜欢
                        $ids = self::getGuessYouLike(json_decode($recommendMap['ru_guess_you_like_goods_list'], true), $requests['weight']);
                        $map[] = ['g.id', 'IN', $ids];
                        break;
                    
                    default: // 默认首页推荐
                        $map[] = ['g.id', 'IN', $recommendMap['ru_recommend_goods_list']];
                        break;
                }
            }
        }

        $map[] = ['g.is_delete', '=', 0];
        $map[] = ['g.is_sale','=',1];
        $map[] = ['g.status', '=', 1];
        $map[] = ['g.stock', '>', 0];
        // 根据行为获取推荐给会员的数据
        $total = GoodsModel::alias("g")->where($map)->count();
        $recommendGoodsList = GoodsModel::goods_list($map, '', $size, $page);

        return ApiReturn::r(1, ['goods_list'=>$recommendGoodsList, 'current_page'=>$page, 'last_page'=>ceil($total/$size), 'total'=>$total], lang('推荐数据成功'));
    }

    /**
     * 猜你喜欢数据计算
     * 此推荐更加精准，采用用户商城综合行为交集+行为权重（默认权重：推荐1，品牌2，品类3，标签4）
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月17日16:03:27
     */
    private static function getGuessYouLike($arr=[], $weight=[])
    {
        // 重新计算权重
        if (!empty($weight)) {
            // 计算权重总值
            $weightSum = 0;
            foreach ($weight as $val) {
                $weightSum += $val;
            }

            // 重新计算权重
            foreach ($arr['recommendList'] as $key=>$val) {
                switch ($val['label']) {
                    case 'shoppingLabelRecommend': // 购物标签
                        $weightCount = empty($weight['shoppingLabelRecommend']) ? 0.4 : ($weight['shoppingLabelRecommend']/$weightSum);
                        $arr['recommendList'][$key]['weight'] = floor($arr['count'] * $weightCount);
                        break;
                    case 'goodsTypeRecommend': // 商品品类
                        $weightCount = empty($weight['goodsTypeRecommend']) ? 0.3 : ($weight['goodsTypeRecommend']/$weightSum);
                        $arr['recommendList'][$key]['weight'] = floor($arr['count'] * $weightCount);
                        break;
                    case 'brandRecommend': // 品牌
                        $weightCount = empty($weight['brandRecommend']) ? 0.2 : ($weight['brandRecommend']/$weightSum);
                        $arr['recommendList'][$key]['weight'] = floor($arr['count'] * $weightCount);
                        break;
                    case 'systemRecommend': // 系统推荐
                        $weightCount = empty($weight['systemRecommend']) ? 0.1 : ($weight['systemRecommend']/$weightSum);
                        $arr['recommendList'][$key]['weight'] = floor($arr['count'] * $weightCount);
                        break;
                    
                    default:
                        // TO DO LIST
                        break;
                }
            }
        }

        return implode(',', array_merge($arr['intersectDate'], self::guessYouLike($arr['recommendList'])));
    }

    /**
     * 猜你喜欢数据计算
     * 此推荐更加精准，采用用户商城综合行为交集+行为权重（默认权重：推荐1，品牌2，品类3，标签4）
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月17日16:03:27
     */
    private static function guessYouLike($arr=[])
    {
        $newArr = [];
        foreach ($arr as $val) {
            for ($i=1; $i<=$val['weight']; $i++) {
                if (empty($val['goodsList'])) {
                    continue;
                }
                if ($i <= count($val['goodsList'])) {
                    $newArr = self::randDate($val['goodsList'], $newArr);
                }
            }
        }

        return array_unique($newArr);
    }

    /**
     * 生成不重复的随机数
     */
    private static function randDate($arr, $newArr)
    {
        $data = $arr[mt_rand(0, count($arr) - 1)];
        if (in_array($data, $newArr)) {
            // return self::randDate($arr, $newArr); // 正确的写法应该是这样，因数据不足推荐权重比例的时候会一直处理，因此采用允许重复的行为，然后进行去重
            self::randDate($arr, $newArr);
        }

        $newArr[] = $data;

        return $newArr;
    }
}
