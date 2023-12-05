<?php


namespace app\recommend\admin;

use think\Controller;
use app\user\model\User;
use app\recommend\model\RecommendUsers as RecommendUsersModel;
use app\recommend\model\Queue;
use app\common\model\Order;
use app\goods\model\Cart;
use app\goods\model\Goods;
use app\user\model\Collection;
use think\Db;

/**
 * 推荐系统用户操作
 * Class RecommendUsers
 * @package app\recommend\controller
 */
class RecommendUsers extends Controller
{
    /**
     * 批量添加需要更新任务的用户
     * @author zenghu<1427305236@qq.com>
     * @since 2020年12月15日14:50:06
     * @return boole 
     */
    public static function insertUsers()
    {
        // 查询用户信息
        $userInfo = User::alias('u')
            ->field('u.id,u.sex,u.age,ui.hobby,ui.address,u.birthday')
            ->join('user_info ui', 'ui.user_id = u.id', 'LEFT')
            ->select();
        if(empty($userInfo)){
            \think\facade\Log::instance()->write("recommend/RecommendUsers/insertUsers数据为空不用执行" . Db::getlastsql(), "error");
            return true;
        }

        // 处理查询到的数据添加跑批表
        $inserData = [];
        foreach($userInfo as $key=>$val){
            $data['q_user_id'] = $val['id'];
            $data['q_type'] = 3;
            $data['q_implement_time'] = '02:00';
            $data['q_extent'] = json_encode(['sex'=>$val['sex'], 'age'=>$val['age'], 'hobby'=>$val['hobby'], 'address'=>$val['address'], 'birthday'=>$val['birthday']]);

            array_push($inserData, $data);
        }

        // 加入批量任务表
        $re = Queue::insertAll($inserData);
        if(!$re){
            \think\facade\Log::instance()->write("recommend/RecommendUsers/insertUsers加入批量任务表失败", "error");
            return false;
        }

        return true;
    }

    /**
     * 更新推荐系统用户表数据
     * @author zenghu<1427305236@qq.com>
     * @since 2020年12月15日14:35:32
     * @return \think\response\Json
     */
    public static function updateUser()
    {
        // 读取需要跑批的数据 每次读取100条
        $queueDate = Queue::where(['q_type'=>3])->limit(100)->select();
        if(empty($queueDate)){
            \think\facade\Log::instance()->write("recommend/RecommendUsers/updateUser数据为空不用执行" . Db::getlastsql(), "error");
            return true;
        }

        // 处理需要跑批的数据
        foreach($queueDate as $val){
            if(true === self::updateUserModel($val)){
                Queue::where(['q_id'=>$val['q_id']])->delete();
            }
        }
    }

    /**
     * 更新推荐系统用户表数据
     * @author zenghu<1427305236@qq.com>
     * @since 2020年12月15日15:53:40
     * @return boole
     */
    private static function updateUserModel($arr=[])
    {
        if(empty($arr)){
            return false;
        }

        // 处理数据
        $userId = $arr['q_user_id'];
        $extent = json_decode($arr['q_extent'], true);

        // 获取用户购物行为标签
        $getUserLabel = self::getUserLabel($userId);

        // 查询是否存在用户
        $getRecommendUsersInfo = RecommendUsersModel::field('ru_id')->get(['user_id'=>$userId]);
        if($getRecommendUsersInfo){
            $re = RecommendUsersModel::where(['user_id'=>$userId])->update([
                'ru_sex' => $extent['sex'],
                'ru_age' => $extent['age'],
                'ru_hobby' => $extent['hobby'],
                'ru_address' => $extent['address'],
                'ru_shopping_label' => $getUserLabel['ru_shopping_label'],
                'ru_goods_list' => '',
                'ru_brand_label' => $getUserLabel['ru_brand_label'],
                'ru_goodstype_label' => $getUserLabel['ru_goodstype_label'],
                'ru_recommend_goods_list' => self::getBehaviorGoodsList($getUserLabel),
                'ru_recommend_goods_cart_list' => self::getBehaviorGoodsCartList($getUserLabel['cart_recommend_goods_list']),
                'ru_recommend_goods_goods_list' => self::getBehaviorGoodsOrderList($getUserLabel),
                'ru_recommend_goods_order_list' => self::getBehaviorGoodsOrderList($getUserLabel),
                'ru_guess_you_like_goods_list' => self::getGuessYouLikeGoodsList($getUserLabel), 
                'ru_based_demographic_recommend' => self::getBasedDemographicRecommend($userId, $extent),
                'ru_update_time' => date('Y-m-d H:i:s'),
            ]);
        }else{
            $re = RecommendUsersModel::insert([
                'user_id' => $userId,
                'ru_sex' => $extent['sex'],
                'ru_age' => $extent['age'],
                'ru_hobby' => $extent['hobby'],
                'ru_address' => $extent['address'],
                'ru_shopping_label' => $getUserLabel['ru_shopping_label'],
                'ru_goods_list' => '',
                'ru_brand_label' => $getUserLabel['ru_brand_label'],
                'ru_goodstype_label' => $getUserLabel['ru_goodstype_label'],
                'ru_recommend_goods_list' => self::getBehaviorGoodsList($getUserLabel),
                'ru_recommend_goods_cart_list' => self::getBehaviorGoodsCartList($getUserLabel['cart_recommend_goods_list']),
                'ru_recommend_goods_goods_list' => self::getBehaviorGoodsOrderList($getUserLabel),
                'ru_recommend_goods_order_list' => self::getBehaviorGoodsOrderList($getUserLabel),
                'ru_guess_you_like_goods_list' => self::getGuessYouLikeGoodsList($getUserLabel), 
                'ru_based_demographic_recommend' => self::getBasedDemographicRecommend($userId, $extent),
            ]);
        }

        return $re ? true : false;
    }

    /**
     * 获取用户购物行为标签
     * @author zenghu<1427305236@qq.com>
     * @since 2020年12月15日16:18:03
     * @return string
     */
    private static function getUserLabel($userId=0)
    {
        // 定义需要处理的数据
        $keywords = '';
        $cids = [];
        $brand_ids = [];

        // 获取用户购物行为商品标签
        $userShoppingLabel = self::getUserShoppingLabel($userId);
        if($userShoppingLabel){
            $keywords .= $userShoppingLabel['keywords'];
            $cids = array_merge($cids, $userShoppingLabel['cid']);
            $brand_ids = array_merge($brand_ids, $userShoppingLabel['brand_id']);
        }

        // 用户加购行为
        $userCartLabel = self::getUserCartLabel($userId);
        if($userCartLabel){
            $keywords .= $userCartLabel['keywords'];
            $cids = array_merge($cids, $userCartLabel['cid']);
            $brand_ids = array_merge($brand_ids, $userCartLabel['brand_id']);

            // 计算用户购物车行为商品
            $cartKeyWords = $keywords;
            $cartCids = $cids;
            $cartBrandIds = $brand_ids;
        }

        // 用户浏览行为
        $userBrowse = self::getUserBehaviorLabel($userId, 3);
        if($userBrowse){
            $keywords .= $userBrowse['keywords'];
            $cids = array_merge($cids, $userBrowse['cid']);
            $brand_ids = array_merge($brand_ids, $userBrowse['brand_id']);
        }

        // 用户收藏行为
        $userCollection = self::getUserBehaviorLabel($userId, 1);
        if($userCollection){
            $keywords .= $userCollection['keywords'];
            $cids = array_merge($cids, $userCollection['cid']);
            $brand_ids = array_merge($brand_ids, $userCollection['brand_id']);
        }

        // 处理返回数据
        return $data = [
            'ru_shopping_label' => self::uniqueStr($keywords),
            'ru_goodstype_label' => self::handleDate($cids),
            'ru_brand_label' => self::handleDate($brand_ids),
            'cart_recommend_goods_list' => [
                'cartKeyWords' => self::uniqueStr($cartKeyWords),
                'cartCids' => self::handleDate($cartCids),
                'cartBrandIds' => self::handleDate($cartBrandIds),
            ],
        ];
    }

    /**
     * 基于用户行为内容推荐(新老用户均适用，无权重)
     * 根据用户购物行为、加购行为、浏览行为、收藏行为、系统推荐商品
     * TO DO LIST 此处暂且采用或品牌或分类查询条件，如果数据量过大，则采用品牌下已有品类的推荐，已有品类的其他品牌推荐等
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月16日10:24:21
     */
    private static function getBehaviorGoodsList($arr=[], $isRecommend=1)
    {
        // 行为标签
        $whereStr = '';
        if(!empty($arr['ru_shopping_label'])){
            $whereStr = self::handleSql($whereStr, $arr['ru_shopping_label'], 'keywords', 'LIKE');
        }
        if(!empty($arr['ru_goodstype_label'])){
            $whereStr = self::handleSql($whereStr, $arr['ru_goodstype_label'], 'cid');
        }
        if(!empty($arr['ru_brand_label'])){
            $whereStr = self::handleSql($whereStr,$arr['ru_brand_label'], 'brand_id');
        }
        if($isRecommend){
            if($whereStr){
                $whereStr .= ' OR (is_recommend = 1) ';
            }else{
                $whereStr .= ' (is_recommend = 1) ';
            }
        }
        
        // 查询推荐的商品数据
        $goodsList = Goods::field('GROUP_CONCAT(id) ids')->where(['is_sale'=>1, 'status'=>1, 'is_delete'=>0])->where("{$whereStr}")->find();

        return $goodsList['ids'] ?? '';
    }

    /**
     * 用户购物车推荐商品
     * 根据用户购物行为、加购行为
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月16日17:39:09
     */
    private static function getBehaviorGoodsCartList($arr=[])
    {
        if(empty($arr)){
            return '';
        }

        // 行为标签
        $whereStr = '';
        if(!empty($arr['cartKeyWords'])){
            $whereStr = self::handleSql($whereStr, $arr['cartKeyWords'], 'keywords', 'LIKE');
        }
        if(!empty($arr['cartCids'])){
            $whereStr = self::handleSql($whereStr, $arr['cartCids'], 'cid');
        }
        if(!empty($arr['cartBrandIds'])){
            $whereStr = self::handleSql($whereStr,$arr['cartBrandIds'], 'brand_id');
        }

        // 查询推荐的商品数据
        $goodsList = Goods::field('GROUP_CONCAT(id) ids')->where(['is_sale'=>1, 'status'=>1, 'is_delete'=>0])->where("{$whereStr}")->find();

        return $goodsList['ids'] ?? '';
    }

    /**
     * 用户订单列表和商品列表推荐商品
     * 根据用户购物行为、加购行为、浏览行为、收藏行为
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月16日17:42:45
     */
    private static function getBehaviorGoodsOrderList($arr=[])
    {
        if(empty($arr)){
            return '';
        }

        // 行为标签
        $whereStr = '';
        if(!empty($arr['ru_shopping_label'])){
            $whereStr = self::handleSql($whereStr, $arr['ru_shopping_label'], 'keywords', 'LIKE');
        }
        if(!empty($arr['ru_goodstype_label'])){
            $whereStr = self::handleSql($whereStr, $arr['ru_goodstype_label'], 'cid');
        }
        if(!empty($arr['ru_brand_label'])){
            $whereStr = self::handleSql($whereStr,$arr['ru_brand_label'], 'brand_id');
        }

        // 查询推荐的商品数据
        $goodsList = Goods::field('GROUP_CONCAT(id) ids')->where(['is_sale'=>1, 'status'=>1, 'is_delete'=>0])->where("{$whereStr}")->find();

        return $goodsList['ids'] ?? '';
    }

    /**
     * 获取用户购物商品list倒序
     * @author zenghu<1427305236@qq.com>
     * @since 
     * @return string
     */
    private static function getUserGoodsList()
    {

    }

    /**
     * 获取用户购物行为商品标签
     * @author zenghu<1427305236@qq.com>
     * @since 2020年12月15日16:33:57
     * @return Arr
     */
    private static function getUserShoppingLabel($userId=0)
    {
        if(empty($userId)){ return []; }

        // 获取用户购物商品标签
        $info = Order::alias('o')
            ->field('g.keywords,g.cid,g.brand_id')
            ->where(['o.user_id'=>$userId, 'g.is_sale'=>1, 'g.status'=>1, 'g.is_delete'=>0])
            ->join('order_goods_list ogl', 'o.order_sn = ogl.order_sn', 'LEFT')
            ->join('goods g', 'g.id = ogl.goods_id', 'LEFT')
            ->select();
        if(!$info){ return []; }

        // 处理用户购物行为商品标签
        $keywords = '';
        $cids = [];
        $brand_ids = [];
        foreach($info as $val){
            $keywords .= ($val['keywords'] . ',');
            array_push($cids, $val['cid']);
            array_push($brand_ids, $val['brand_id']);
        }

        return [
            'keywords' => $keywords,
            'cid' => $cids,
            'brand_id' => $brand_ids
        ];
    }

    /**
     * 获取用户加购行为商品标签信息
     * @author zenghu<1427305236@qq.com>
     * @since 2020年12月15日17:22:52
     * @return Arr
     */
    private static function getUserCartLabel($userId=0)
    {
        if(empty($userId)){ return []; }

        // 获取用户购物商品标签
        $info = Cart::alias('c')
            ->field('g.keywords,g.cid,g.brand_id')
            ->where(['c.user_id'=>$userId, 'g.is_sale'=>1, 'g.status'=>1, 'g.is_delete'=>0])
            ->join('goods g', 'g.id = c.goods_id')
            ->select();
        if(!$info){ return []; }

        // 处理用户加购行为商品标签信息
        $keywords = '';
        $cids = [];
        $brand_ids = [];
        foreach($info as $val){
            $keywords .= ($val['keywords'] . ',');
            array_push($cids, $val['cid']);
            array_push($brand_ids, $val['brand_id']);
        }

        return [
            'keywords' => $keywords,
            'cid' => $cids,
            'brand_id' => $brand_ids
        ];
    }

    /**
     * 获取用户浏览行为或者收藏行为商品标签信息
     * @author zenghu<1427305236@qq.com>
     * @since 2020年12月15日17:30:57
     * @return Arr
     */
    private static function getUserBehaviorLabel($userId=0, $beType=1)
    {
        if(empty($userId)){ return []; }

        // 获取用户行为商品标签
        $info = Collection::alias('c')
            ->field('g.keywords,g.cid,g.brand_id')
            ->where(['c.user_id'=>$userId, 'c.type'=>$beType, 'c.status'=>1, 'g.is_sale'=>1, 'g.status'=>1, 'g.is_delete'=>0])
            ->join('goods g', 'g.id = c.collect_id')
            ->select();
        if(!$info){ return []; }

        // 处理用户行为商品标签信息
        $keywords = '';
        $cids = [];
        $brand_ids = [];
        foreach($info as $val){
            $keywords .= ($val['keywords'] . ',');
            array_push($cids, $val['cid']);
            array_push($brand_ids, $val['brand_id']);
        }

        return [
            'keywords' => $keywords,
            'cid' => $cids,
            'brand_id' => $brand_ids
        ];
    }

    /**
     * 获取猜你喜欢数据信息入库（推荐1，品牌2，品类3，标签4）
     * @author zenghu<1427305236@qq.com>
     * @since 2020年12月17日13:47:04
     * @return Arr
     */
    private static function getGuessYouLikeGoodsList($arr=[])
    {
        // 定义喜欢商品列表
        $guessYouLikeGoodsList = [];

        // 获取系统推荐商品列表
        $systemRecommendList = Goods::field('GROUP_CONCAT(id) ids')->where(['is_sale'=>1, 'status'=>1, 'is_delete'=>0, 'is_recommend'=>1])->find();
        $systemRecommendList = explode(',', $systemRecommendList['ids']);
        if($arr['ru_shopping_label']){
            $whereStr1 = self::handleSql('', $arr['ru_shopping_label'], 'keywords', 'LIKE');
            $shoppingLabelRecommendList = Goods::field('GROUP_CONCAT(id) ids')->where(['is_sale'=>1, 'status'=>1, 'is_delete'=>0])->where("$whereStr1")->find();
            $shoppingLabelRecommendList = explode(',', $shoppingLabelRecommendList['ids']);
        }
        if(!empty($arr['ru_goodstype_label'])){
            $whereStr2 = self::handleSql('', $arr['ru_goodstype_label'], 'cid');
            $goodsTypeRecommendList = Goods::field('GROUP_CONCAT(id) ids')->where(['is_sale'=>1, 'status'=>1, 'is_delete'=>0])->where("$whereStr2")->find();
            $goodsTypeRecommendList = explode(',', $goodsTypeRecommendList['ids']);
        }
        if(!empty($arr['ru_brand_label'])){
            $whereStr3 = self::handleSql('',$arr['ru_brand_label'], 'brand_id');
            $brandRecommendList = Goods::field('GROUP_CONCAT(id) ids')->where(['is_sale'=>1, 'status'=>1, 'is_delete'=>0])->where("$whereStr3")->find();
            $brandRecommendList = explode(',', $brandRecommendList['ids']);
        }

        // 处理返回的数据
        $intersectDate = array_values(array_intersect($systemRecommendList,$shoppingLabelRecommendList,$goodsTypeRecommendList,$brandRecommendList)); // 求数据交际
        $guessYouLikeGoodsList['intersectDate'] = $intersectDate;
        $shoppingLabelRecommendList = self::handleArr($shoppingLabelRecommendList, $intersectDate);
        $goodsTypeRecommendList = self::handleArr($goodsTypeRecommendList, $intersectDate);
        $brandRecommendList = self::handleArr($brandRecommendList, $intersectDate);
        $systemRecommendList = self::handleArr($systemRecommendList, $intersectDate);
        $guessYouLikeGoodsList['count'] = count($shoppingLabelRecommendList) + count($goodsTypeRecommendList) + count($brandRecommendList) + count($systemRecommendList);
        $guessYouLikeGoodsList['recommendList'] = [
            [
                'goodsList' => $shoppingLabelRecommendList,
                'label' => 'shoppingLabelRecommend',
                'weight' => 4,
            ], // 标签推荐
            [
                'goodsList' => $goodsTypeRecommendList,
                'label' => 'goodsTypeRecommend',
                'weight' => 3,
            ], // 品类推荐
            [
                'goodsList' => $brandRecommendList,
                'label' => 'brandRecommend',
                'weight' => 2,
            ], // 品牌推荐
            [
                'goodsList' => $systemRecommendList,
                'label' => 'systemRecommend',
                'weight' => 1,
            ], // 系统推荐
        ];
        
        return json_encode($guessYouLikeGoodsList);
    }

    /**
     * 基于人口统计学算法推荐（适合新用户推荐）
     * 优点：不需要历史数据，没有冷启动问题，不依赖于物品的属性，因此其他领域的问题都可无缝接入
     * 缺点：算法比较粗糙，效果很难令人满意，只适合简单的推荐
     * @param $userId int 用户主键ID
     * @param $userInfo arr 用户特征信息
     * @author zenghu < 1427305236@qq.com >
     * @since 2020年12月19日13:47:33
     * @return Arr
     * 目前此算法存在较大问题，主要原因是系统中用户特征（性别、年龄、生日、城市、爱好等维度）数据不完整，无法精准找到合适的用户进行推荐，后期完善数据后可使用
     */
    private static function getBasedDemographicRecommend($userId=0, $userInfo=[])
    {
        if($userInfo['birthday']){
            // 处理年龄区间 年龄区间此处取（之前三岁和之后三岁）
            $birthday = $userInfo['birthday'];
            $after = strtotime('-3 Year', $birthday); // 大三岁
            $before = strtotime('+3 Year', $birthday); // 小三岁
        }else{
            $after = 0;
            $before = 0;
        }
        
        // 查询用户信息
        $usersInfo = User::alias('u')
            ->field('u.id')
            ->where("u.id <> {$userId} AND u.sex = {$userInfo['sex']} AND (u.birthday >= {$after} AND u.birthday <= {$before}) AND ui.address = '{$userInfo[address]}'")
            ->join('user_info ui', 'ui.user_id = u.id', 'LEFT')
            ->select();
        // 处理用户行为信息 求出用户数据交际
        $arr = [];
        foreach($usersInfo as $key=>$val){
            $list = self::handleIds(self::getBehaviorGoodsList(self::getUserLabel($val['id']), 0));
            if($key == 0){
                $arr = $list;
            }else{
                $arr = array_intersect($arr, $list);
            }
        }

        return explode(',', $arr);
    }

    /**
     * IDS处理，允许有重复值
     */
    private static function handleIds($ids)
    {
        return array_filter(explode(',', $ids));
    }

    /**
     * 处理数组
     */
    private static function handleArr($arr=[], $intersect=[])
    {
        if(empty($arr)){ 
            return [];
        }
        if(empty($intersect)){
            return $arr;
        }

        foreach($arr as $key=>$val){
            if(in_array($val, $intersect)){
                unset($arr[$key]);
            }
        }

        return array_values($arr);
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
     * 处理数据
     */
    private static function handleDate($arr)
    {
        return implode(',', array_filter(array_unique($arr)));
    }

    /**
     * SQL处理
     */
    private static function handleSql($sqlStr='', $keysArr='', $tableKey='', $Symbol='')
    {
        if($sqlStr){
            $sqlStr .= ' OR (';
        }else{
            $sqlStr = '(';
        }
        $label = explode(',', $keysArr); // 品牌 2
        foreach($label as $key=>$val){
            if($Symbol == 'LIKE'){
                $sqlStr .= "{$tableKey} LIKE '%{$val}%' OR ";
            }else{
                $sqlStr .= "{$tableKey} = '{$val}' OR ";
            }
            
        }
        $sqlStr = rtrim($sqlStr, ' OR ');
        return $sqlStr .= ')'; 
    }

}
