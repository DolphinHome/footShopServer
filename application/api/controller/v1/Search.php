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
namespace app\api\controller\v1;

use app\api\controller\Base;
use app\goods\model\Goods as GoodsModel;
use service\ApiReturn;
use think\Db;

/**
 * 搜索管理接口
 * Class Search
 * @package app\api\controller\v1
 */
class Search extends Base
{
    // 定义实例化表对象名
    private $searchHistoryDb;
    private $serchInfo; // 返回的数据
    private $notInIds; // 重新排列数据ID

    // 实例化表
    public function __construct()
    {
        parent::__construct();
        $this->searchHistoryDb = \think\Db::name('operation_article_search_history');
    }

    /**
     * 搜索历史添加
     * @param int $userInfo .id 用户ID [必须]
     * @param string $requests .history_content 搜索内容 [必须]
     * @return \think\response\Json
     * @since 2020年8月24日09:43:07
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function historySearchInsert($requests = [], $userInfo = [])
    {
        // 模拟参数
        // $userInfo['id'] = 1;
        // $requests = array(
        //     'historyContent' => '北京中关村支行酒局'
        // );

        // 为了测试添加参数
        $userInfo['id'] = !empty($requests['user_id']) ? $requests['user_id'] : $userInfo['id'];

        // 参数校验
        if (empty($userInfo['id']) || is_null($userInfo['id'])) {
            return ApiReturn::r(0, [], lang('用户ID必须'));
        }
        if (empty($requests['historyContent'])) {
            return ApiReturn::r(0, [], lang('内容必须'));
        }
        $requests['type'] = $requests['type'] ?? 0;
        // 检测重复记录值删除
        $findRe = $this->searchHistoryDb->where([
            'user_id' => $userInfo['id'],
            'type' => $requests['type'],
            'history_content' => trim($requests['historyContent'])
        ])->delete();
//        if($findRe){
//            return ApiReturn::r(1, [], lang('添加成功'));
//        }

        // 历史搜索数据添加
        $historySearch = $this->searchHistoryDb->insert([
            'user_id' => $userInfo['id'],
            'type' => $requests['type'],
            'history_content' => addons_action('DfaFilter/DfaFilter/filter', [trim($requests['historyContent'])])
        ]);

        // 返回添加结果
        if ($historySearch) {
            return ApiReturn::r(1, [], lang('添加成功'));
        }

        return ApiReturn::r(0, $result ?? [], lang('添加失败'));
    }

    /**
     * 搜索历史删除
     * @param int $userInfo .id 用户ID [必须]
     * @param string $requests .historyId 内容ID [非必须]
     * @return \think\response\Json
     * @since 2020年8月24日09:43:12
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function historySearchDelete($requests = [], $userInfo = [])
    {
        // 模拟参数
        // $requests = array(
        //     'historyId' => '22'
        // );

        // 为了测试添加参数
        $userInfo['id'] = !empty($requests['user_id']) ? $requests['user_id'] : $userInfo['id'];

        // 参数校验
        if (empty($userInfo['id']) || is_null($userInfo['id'])) {
            return ApiReturn::r(0, [], '用户ID必须');
        }
        $requests['type'] = $requests['type'] ?? 0;
        // 处理删除的条件
        $historyWhere = array();
        $historyWhere['user_id'] = $userInfo['id'];
        $historyWhere['type'] = $requests['type'];
        if (isset($requests['historyId']) && !empty($requests['historyId'])) {
            $historyWhere['history_id'] = $requests['historyId'];
        }

        // 删除历史记录
        $re = $this->searchHistoryDb->where($historyWhere)->delete();
        if ($re === false) {
            return ApiReturn::r(0, [], lang('删除失败'));
        }

        return ApiReturn::r(1, [], lang('删除成功'));
    }

    /**
     * 获取搜索历史
     * @param int $userInfo .id 用户ID [必须]
     * @param int $requests .searchLimit 搜索历史条数 [非必须]
     * @return \think\response\Json
     * @since 2020年8月22日17:55:42
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getHistorySearch($requests = [], $userInfo = [])
    {
        // 模拟参数
        // $requests = array(
        //     'searchLimit' => '5', // 搜索历史数
        // );

        // 为了测试添加参数
        $userInfo['id'] = !empty($requests['user_id']) ? $requests['user_id'] : $userInfo['id'];

        // 参数校验
        if (empty($userInfo['id']) || is_null($userInfo['id'])) {
            return ApiReturn::r(0, [], '用户ID必须');
        }

        // 参数默认值
        $searchLimit = empty($requests['searchLimit']) ? 10 : $requests['searchLimit'];
        $requests['type'] = $requests['type'] ?? 0;
        // 历史搜索数据查询
        $historySearch = $this->_searchHistory($userInfo['id'], $searchLimit, $requests['type']);
        $historySearch = $this->uniquArr($historySearch, 'history_content');//历史搜索记录去重
        return ApiReturn::r(1, $historySearch, lang('请求成功'));
    }

    //历史搜索记录去重
    private function uniquArr($array, $keyword)
    {
        $result = array();
        foreach ($array as $k => $val) {
            $code = false;
            foreach ($result as $_val) {
                if ($_val[$keyword] == $val[$keyword]) {
                    $code = true;
                    break;
                }
            }
            if (!$code) {
                $result[] = $val;
            }
        }
        return $result;
    }

    /**
     * 搜索推荐
     * @param int $userInfo .id 用户ID [必须]
     * @param int $requests .searchLimit 推荐条数 [非必须]
     * @return \think\response\Json
     * @since 2020年8月1日17:57:40
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getRecommendedSearch($requests = [], $userInfo = [])
    {
        // 模拟参数
        // $requests = array(
        //     'searchLimit' => '5', // 搜索推荐条数
        // );

        // 为了测试添加参数
        $userInfo['id'] = !empty($requests['user_id']) ? $requests['user_id'] : $userInfo['id'];

        // 参数校验
        if (empty($userInfo['id']) || is_null($userInfo['id'])) {
            return ApiReturn::r(0, [], '用户ID必须');
        }

        // 参数默认值
        $searchLimit = empty($requests['searchLimit']) ? 10 : $requests['searchLimit'];

        // 搜索历史
        $searchDiscovery = $this->_searchHistory($userInfo['id'], $searchLimit);
        if (!empty($searchDiscovery)) {
            foreach ($searchDiscovery as $val) {
                $search = \think\Db::query("
                    SELECT
                        `oa`.`id`,
                        `oa`.`title`,
                        `oa`.`click_count`
                    FROM
                        lb_operation_article oa
                    LEFT JOIN `lb_operation_article_body` `oab` ON `oa`.`id` = `oab`.`aid`
                    LEFT JOIN `lb_operation_article_column` `oac` ON `oa`.`category_id` = `oac`.`id`
                    LEFT JOIN `lb_operation_article` `oaa` ON `oa`.`category_id` = `oaa`.`category_id` AND `oaa`.`id` <> `oa`.`id`
                    WHERE `oa`.`status` = '1'
                    AND (
                        `oa`.`title` LIKE '%" . $val['history_content'] . "%'
                        OR `oab`.`body` LIKE '%" . $val['history_content'] . "%'
                    )
                    GROUP BY `oa`.`id`
                    ORDER BY `oa`.`click_count`
                ");
                if (!empty($search)) {
                    $searchDiscoveryResult[] = $search;
                }
            }

            // 处理发现数据
            $result = [];
            $searchDiscoveryResult = array_unique($searchDiscoveryResult, SORT_REGULAR);
            if (!empty($searchDiscoveryResult)) {
                foreach ($searchDiscoveryResult as $val) {
                    foreach ($val as $v) {
                        array_push($result, $v);
                    }
                }
            }
            // $result = array_unique($result, SORT_REGULAR);

            return ApiReturn::r(1, $result, lang('搜索推荐数据查询成功'));
        }

        return ApiReturn::r(1, [], lang('搜索推荐数据为空'));
    }

    /**
     * 搜索发现
     * @param int $userInfo .id 用户ID [必须]
     * @return \think\response\Json
     * @since 2020年8月1日17:57:40
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getDiscoverySearch($requests = [], $userInfo = [])
    {
        // 为了测试添加参数
        $userInfo['id'] = !empty($requests['user_id']) ? $requests['user_id'] : $userInfo['id'];

        // 参数校验
        if (empty($userInfo['id']) || is_null($userInfo['id'])) {
            return ApiReturn::r(0, [], '用户ID必须');
        }

        // 获取搜索历史
        $searchDiscovery = $this->_searchHistory($userInfo['id'], 30);

        // 根据搜索历史处理搜索发现数据
        if (!empty($searchDiscovery)) {
            $searchDiscoveryResult = array();
            foreach ($searchDiscovery as $val) {
                $search = \think\Db::query("
                    SELECT
                        `oacc`.`id`,
                        `oacc`.`name`
                    FROM
                        lb_operation_article oa
                    LEFT JOIN `lb_operation_article_body` `oab` ON `oa`.`id` = `oab`.`aid`
                    LEFT JOIN `lb_operation_article_column` `oac` ON `oa`.`category_id` = `oac`.`id`
                    LEFT JOIN `lb_operation_article_column` `oacc` ON `oac`.`pid` = `oacc`.`pid`
                    WHERE `oa`.`status` = '1'
                    AND (
                        `oa`.`title` LIKE '%" . $val['history_content'] . "%'
                        OR `oab`.`body` LIKE '%" . $val['history_content'] . "%'
                    )
                    GROUP BY `oacc`.`id`
                    ORDER BY `oa`.`click_count`
                ");

                if (!empty($search)) {
                    $searchDiscoveryResult[] = $search;
                }
            }

            // 处理发现数据
            $result = [];
            // $searchDiscoveryResult = array_unique($searchDiscoveryResult, SORT_REGULAR);
            if (!empty($searchDiscoveryResult)) {
                foreach ($searchDiscoveryResult as $val) {
                    foreach ($val as $v) {
                        array_push($result, $v);
                    }
                }
            }
            $result = array_unique($result, SORT_REGULAR);

            return ApiReturn::r(1, $result, lang('搜索发现数据查询成功'));
        }

        return ApiReturn::r(1, [], lang('搜索发现数据为空'));
    }

    /**
     * 热销榜
     * @param int $requests .searchLimit 热销榜条数 [非必须]
     * @return \think\response\Json
     * @since 2020年8月22日17:57:40
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getHotListSearch($requests = [])
    {
        // 模拟参数
        // $requests = array(
        //     'searchLimit' => '20',
        // );

        // 参数默认值
        $searchLimit = empty($requests['searchLimit']) ? 30 : $requests['searchLimit'];

        // 查询热搜榜数据
        $operationArticle = Db::name('goods')
            ->field('id,name,sales_sum,is_shipping,is_spec,is_hot,is_new,is_boutique,is_recommend')
            ->where(['is_sale' => 1, 'status' => 1, 'is_delete' => 0])
            ->order('sales_sum DESC')
            ->limit($searchLimit)
            ->select();
        if (!empty($operationArticle)) {
            foreach ($operationArticle as $key => $val) {
                $operationArticle[$key]['sku_id'] = Db::name('goods_sku')->where('goods_id', $val['id'])->value('sku_id');
                $operationArticle[$key]['ranking'] = $key + 1;
                //$operationArticle[$key]['sales_num_new'] = $val['sales_num_new'] / 10000 . lang('万');
            }
        }

        return ApiReturn::r(1, $operationArticle, lang('请求成功'));
    }

    /**
     * 搜索联想
     * @param int $requests .keyWords 联想搜索关键词 [必须]
     * @return \think\response\Json
     * @since 2020年8月24日14:11:51
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function getAssociationSearch($requests = [])
    {
        // 模拟参数
        // $requests = array(
        //     'keyWords' => '',
        // );

        // 参数校验
        if (empty($requests['keyWords'])) {
            return ApiReturn::r(0, [], lang('请输入需要搜索的关键词'));
        }

        // 接收参数
        $keyword = preg_replace('# #', '', trim($requests['keyWords'])); // 去除字符串空格

        // 拼接查询的条件
        $whereStr = 'title LIKE \'%' . $keyword . '%\'';
        $whereStrArr[] = $keyword;
        $keywords = preg_split("/[\s,'!,@,#,$,%,^,&,*,(,),_,+,~,`,,,.,\/']+/", trim($requests['keyWords'])); // 处理特殊字符
        if (count($keywords) > 1) {
            $whereStr .= ' OR ';
            foreach ($keywords as $val) {
                $whereStr .= 'title LIKE \'%' . trim($val) . '%\' OR ';
                $whereStrArr[] = trim($val);
            }
            $whereStr = rtrim($whereStr, 'OR ');
        }

        // 查询搜索的内容
        $operationArticle = \think\Db::name('operation_article')
            ->field('id,title')
            ->where(['status' => 1])
            ->where($whereStr)
            ->select();

        $operationArticle = count($operationArticle) > 0 ? $operationArticle : [];

        return ApiReturn::r(1, array_unique($operationArticle, SORT_REGULAR), lang('请求成功'));
    }

    //热搜榜
    public function searchHistoryHot($requests = [])
    {
        $searchLimit = empty($requests['searchLimit']) ? 10 : $requests['searchLimit'];
        $page = $data['page'] ?? 1;
        $size = $data['list_rows'] ?? 10;


        $sql = 'SELECT DISTINCT history_content,count(*) AS count  FROM lb_operation_article_search_history  GROUP BY history_content  ORDER BY count DESC  LIMIT ' . $searchLimit;
        $lists = $this->searchHistoryDb->query($sql);
        foreach ($lists as $key => $value) {
            $keyword = $value['history_content'];
            // 拼接查询的条件
            $whereStr = '';
            $whereStr = 'g.name LIKE \'%' . preg_replace('# #', '', $keyword) . '%\'';
            $whereStrArr[] = preg_replace('# #', '', $keyword);
            $keywords = preg_split("/[\s,'!,@,#,$,%,^,&,*,(,),_,+,~,`,,,.,\/']+/", $keyword); // 处理特殊字符
            if (count($keywords) > 1) {
                $whereStr .= ' OR ';
                foreach ($keywords as $val) {
                    $whereStr .= 'g.name LIKE \'%' . trim($val) . '%\' OR ';
                    $whereStrArr[] = trim($val);
                }
                $whereStr = rtrim($whereStr, 'OR ');
            }
            // 查询商品信息
            $goods_list[] = Db::name('goods g')
                //                ->where($where)
                ->where($whereStr)
                ->where([['is_delete', 'eq', 0], ['is_sale', 'eq', 1], ['status', 'eq', 1]])
                ->field('*')
                ->select();
            $goods_list = array_filter($goods_list);
        }


        $result = array_reduce($goods_list, 'array_merge', array());
        $result = array_slice($result, (($page - 1) * $size), $size);
        foreach ($result as $key => $value) {
            $id .= ',' . $value['id'];
        }
        $where_one[] = ['g.id', 'in', $id];

        $goods_list = GoodsModel::goods_list($where_one, ['id' => 'asc'], $size, 1);
        $yesterday_start = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));
        $yesterday_end = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1);
        foreach ($goods_list as $key => $value) {
            $old_sales_sum = Db::name('order o')->join('order_goods_list ogl', 'o.order_sn = ogl.order_sn')->where('ogl.goods_id = ' . $value['id'])->where('o.pay_time', 'between', [strtotime($yesterday_start), strtotime($yesterday_end)])->sum('num');
            $new_sales_sum = Db::name('order o')->join('order_goods_list ogl', 'o.order_sn = ogl.order_sn')->where('ogl.goods_id = ' . $value['id'])->where('o.pay_time', 'between', [strtotime($yesterday_start), strtotime($yesterday_end)])->sum('num');
            if (($new_sales_sum - $old_sales_sum) > 0) {
                $goods_list[$key]['is_rank'] = 1;
                $goods_list[$key]['is_rank_num'] = ($new_sales_sum - $old_sales_sum) * 10;
            } elseif (($new_sales_sum - $old_sales_sum) == 0) {
                $goods_list[$key]['is_rank'] = 0;
                $goods_list[$key]['is_rank_num'] = 0;
            } else {
                $goods_list[$key]['is_rank'] = -1;
                $goods_list[$key]['is_rank_num'] = ($new_sales_sum - $old_sales_sum) * 10;
            }
        }

        return ApiReturn::r(1, $goods_list, lang('请求成功'));
    }

    /**
     * 搜索历史查询
     * @param int $searchLimit 搜索历史条数 [非必须]
     * @return Arr
     * @since 2020年8月22日17:57:40
     * @author zenghu [ 1427305236@qq.com ]
     */
    private function _searchHistory($userId = 0, $searchLimit = 20, $type = 0)
    {
        // 数据为空
        if (!$userId) {
            return [];
        }

        // 历史搜索数据查询
        $searchHistory = $this->searchHistoryDb
            ->field('history_id,history_content')
            ->where(['user_id' => $userId])
            ->where(['type' => $type])
            ->limit($searchLimit)
            ->order('history_add_time DESC')
            ->select();

        return $searchHistory;
    }


    private function _searchHistoryAll($userId = 0, $searchLimit = 20, $type = 0)
    {
        $count = mt_rand(1, ($this->searchHistoryDb->count() - $searchLimit));
        // 历史搜索数据查询
        $searchHistory = $this->searchHistoryDb
            ->field('history_id,history_content')
            ->where(['type' => $type])
            ->limit($count, $searchLimit)
            ->order('history_add_time DESC')
            ->select();
        return $searchHistory;
    }

    //搜索关键词推荐
    public function keyword_recommend($data)
    {
        $words = Db::name('goods')->where([
            ["status", "=", 1],
            ["is_sale", "=", 1],
            ["is_delete", "=", 0],
            ["keywords", "<>", ""],
        ])
            ->orderRand()
            ->limit(5)->column('keywords');
        return ApiReturn::r(1, $words, lang('请求成功'));
    }


    /**
     *扫码识别
     **/
    public function scan_code($data)
    {
        $where = [];
        $bar_code = $whereStr = '';
        $sn = trim($data['sn']) ? trim($data['sn']) : ''; // 去除字符串空格

        $bar_code = preg_replace('/\s+/', '', $sn);
        //ENA-13码,商品条形码是13位数字, 如果不是此格式，仍走之前的sn商品编号匹配
        if (is_numeric($bar_code) && strlen($bar_code) == 13) {
            $where[] = ['g.bar_code', '=', $bar_code];
        } else {
            // 拼接查询的条件
            $whereStr = 'g.sn LIKE \'%' . preg_replace('# #', '', $sn) . '%\'';

            $whereStrArr[] = preg_replace('# #', '', $sn);

            $keywords = preg_split("/[\s,'!,@,#,$,%,^,&,*,(,),_,+,~,`,,,.,\/']+/", $sn); // 处理特殊字符

            if (count($keywords) > 1) {
                $whereStr .= ' OR ';
                foreach ($keywords as $val) {
                    $whereStr .= 'g.sn LIKE \'%' . trim($val) . '%\' OR ';
                    $whereStrArr[] = trim($val);
                }
                $whereStr = rtrim($whereStr, 'OR ');
            }
        }

        $where[] = ['g.status', '=', 1];
        $where[] = ['g.is_sale', '=', 1];
        $where[] = ['g.is_delete', '=', 0];

        // 查询商品信息
        $goods_list = Db::name('goods g')
            ->field('g.*,IFNULL(ga.id, 0) activity_id,IFNULL(ga.type, 0) activity_type')
            ->where($where)
            ->where($whereStr)
            ->join('goods_activity_details gad', 'g.id = gad.goods_id AND gad.status = 1', 'LEFT')
            ->join('goods_activity ga', 'ga.id = gad.activity_id', 'LEFT')
            ->group('g.id')
            ->select();
        //halt(Db::name('goods g')->getLastSql());
        // 根据关键字重新排列数组顺序
        foreach ($goods_list as $k => $v) {
            $goods_list[$k]['thumb'] = get_file_url($v['thumb']);
            if ($v['is_spec']) {
                $goods_list[$k]['sku_id'] = Db::name('goods_sku')->where(['status' => 1, 'goods_id' => $v['id']])->value('sku_id');
            } else {
                $goods_list[$k]['sku_id'] = 0;
            }
        }
        return ApiReturn::r(1, ['list' => $goods_list], lang('请求成功'));
    }
}
