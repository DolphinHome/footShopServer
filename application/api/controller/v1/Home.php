<?php
/**
 * Created by PhpStorm.
 * User: I5
 * Date: 2020/11/26
 * Time: 10:12
 */

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\circle\model\Circle;
use app\goods\model\Category;
use app\goods\model\Goods as GoodsModel;
use app\goods\model\ActivityDetails as ActivityDetailsModel;
use app\goods\model\Goods;
use app\user\model\Task as TaskModel;
use service\ApiReturn;
use Think\Db;
use WeChat\Exceptions\InvalidResponseException;
use app\api\controller\v1\Upload;

class Home extends Base
{
    /*
     * 首页数据
     *
     */
    public function index($data, $user)
    {
        //商品分类
        $catagory = $this->_category();
//        echo "<pre>";
//        print_r($catagory);die;
        //广告轮播
        $ad = $this->_ad(1);
        //菜单
        $menu = $this->_menu();
        //秒杀
        $seckill = $this->_seckill();
        //发现好物
        $find = $this->_ad(2);
        //限时特卖
        $limit = $this->_ad(3);
        //热卖榜单
        $hot = $this->_hot();
        //新品首发
        $new = $this->_new();
        //新人0元购
        $buy = $this->_buy($data, $user);
        $res = [
            'catagory' => $catagory,
            'ad' => $ad,
            'menu' => $menu,
            'seckill' => $seckill,
            'find' => $find,
            'limit' => $limit,
            'hot' => $hot,
            'new' => $new,
            'buy' => $buy
        ];
        return ApiReturn::r(1, $res, lang('首页数据'));
    }

    /*
     * 商品分类
     *
     */
    public function _category()
    {
        return Category::where([
            'is_show' => 1,
            'status' => 1,
//            'is_hot' => 1,
            'pid' => 0
        ])->select();
    }

    /*
     * type
     *
     */
    public function _type($type)
    {
        $data = [
            1 => lang('首页顶部轮播'),
            2 => lang('发现好物'),
            3 => lang('限时特卖')
        ];
        return $data[$type];
    }

    /*
     *广告轮播
     *
     */
    public function _ad($type)
    {
        $type = $this->_type($type);
        $ads = db('operation_ads_type')->alias('at')
            ->field('at.name as type_name,u.id as thumb,a.href,a.width,a.height')
            ->join('operation_ads a', 'at.id=a.typeid')
            ->leftJoin('__UPLOAD__ u', 'u.id=a.thumb')
            ->where('at.status', 1)
            ->where('a.status', 1)
            ->where('at.name', $type)
            ->select();
        if ($ads) {
            foreach ($ads as &$v) {
                $v['thumb'] = get_file_url($v['thumb']);
            }
        }
        return $ads;
    }

    /*
     *菜单
     *
     */
    public function _menu()
    {
        $menu = db('operation_nav_type')->alias('nt')
            ->field('nt.name as type_name,n.name,n.thumb,n.href')
            ->join('operation_nav n', 'nt.id=n.typeid')
            ->where('nt.status', 1)
            ->where('n.status', 1)
            ->order('n.sort desc')
            ->select();
        foreach ($menu as &$vo) {
            $vo['thumb'] = get_thumb($vo['thumb']);
//            $res[$vo['type_name']][] = $vo;
        }
        return $menu;
    }

    /*
     * 今日秒杀
     *
     */
    public function _seckill()
    {
        $time = time();
        $where = " ga.sdate <= {$time} and ga.edate >= {$time} ";
        $res = Db::name("goods_activity_details")
            ->alias('gad')
            ->field("
            ga.edate,
            g.thumb,
            g.shop_price,
            g.id,
            gad.activity_price
            ")
            ->join('goods_activity ga', 'ga.id=gad.activity_id', 'left')
            ->join('goods g', 'g.id=gad.goods_id', 'left')
            ->where($where)
            ->where([
                'ga.status' => 1,
                'ga.type' => 1
            ])
//            ->fetchSql(true)
            ->limit(2)
            ->select();
//        var_dump($res);die;
        $data = [];
        if ($res) {
            foreach ($res as &$v) {
                $v['thumb'] = get_file_url($v['thumb']);
            }
            $data = [
                'time' => $res[0]['edate'] - $time,
                'list' => $res
            ];
        }
        return $data;
    }


    public function img($img)
    {
        $rTotal = 0;
        $gTotal = 0;
        $bTotal = 0;
        $total = 0;
        $i = imagecreatefrompng($img);
        if (!$i) {
            $i = imagecreatefromjpeg($img);
        }
        for ($x = 0; $x < imagesx($i); $x++) {
            for ($y = 0; $y < imagesy($i); $y++) {
                $rgb = imagecolorat($i, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $rTotal += $r;
                $gTotal += $g;
                $bTotal += $b;
                $total++;
            }
        }
        $rAverage = round($rTotal / $total);
        $gAverage = round($gTotal / $total);
        $bAverage = round($bTotal / $total);
        return [$rAverage, $gAverage, $bAverage];
    }

    /*
     * 热卖榜单
     *
     */
    public function _hot()
    {
        $id_str = '0';
        $ids_arr = Db::name("goods_hot_top")->column("category_id");
        if ($ids_arr) {
            $id_str = implode(',', $ids_arr);
        }
        $where = " cid in ({$id_str}) ";

        $res = Db::name("goods")
            ->field("id,name,thumb")
            ->where($where)
            ->limit(2)
            ->order("sales_sum desc")
            ->select();
        if ($res) {
            foreach ($res as &$v) {
                $v['thumb'] = get_file_url($v['thumb']);
            }
        }
        return $res;
    }

    /*
     * 新品首发
     *
     */
    public function _new()
    {
        $res = Db::name("goods")
            ->where([
                'is_sale' => 1,
                'is_delete' => 0,
                'status' => 1,
                'is_new' => 1

            ])
            ->field("id,name,thumb")
            ->limit(2)
            ->order("update_time desc")
            ->select();
        if ($res) {
            foreach ($res as &$v) {
                $v['thumb'] = get_file_url($v['thumb']);
            }
        }
        return $res;
    }

    /*
     * 新人0元购
     *
     */

    public function _buy($data, $user)
    {
        $res = [];
        $day = 7;
        $time = time();
        $day_time = $day * 24 * 60 * 60;
        $check = 0;
        if ($user) {
            //检查是否是新人
            $where = " {$time}-create_time <={$day_time} and status = 1";
            $check = Db::name("user")->where($where)->value("create_time");
        }
        $list = Db::name("goods_activity_details")
            ->alias('gad')
            ->field("
                        g.thumb,
                        g.name,
                        gad.activity_price,
                        gad.goods_id,
                        gad.sku_id,
                        gad.start_time,
                        gad.end_time,
                        gad.activity_id
                     ")
            ->join('goods_activity ga', 'ga.id=gad.activity_id', 'left')
            ->join('goods g', 'g.id=gad.goods_id', 'left')
            ->where([
                'ga.status' => 1,
                'ga.type' => 7
            ])
            ->limit(3)
            ->select();
        if ($list) {
            foreach ($list as &$v) {
                $v['thumb'] = get_file_url($v['thumb']);
            }
            $res = [
                'time' => $time - $check,
                'list' => $list
            ];
        }

        return $res;
    }

    /*
     * 首页底部
     *
     */
    public function bottom($data, $user)
    {
        $param = request()->param();
        if ($param['type'] == 'like') {
            $res = $this->_like();
        } elseif ($param['type'] == 'grass') {
            $res = $this->_grass($data, $user);
        } else {
            $res = $this->_share();
        }

        return ApiReturn::r(1, $res, lang('首页底部数据'));
    }

    /*
     * 猜你喜欢
     *
     */
    public function _like()
    {
        $res = Goods::where([
            'is_sale' => 1,
            'is_delete' => 0,
            'status' => 1,
            'is_recommend' => 1
        ])
            ->field("id,name,thumb,shop_price,market_price")
            ->paginate()->each(function (&$v) {
                $v['count'] = $this->_payNum($v['id']);
                $v['thumb'] = get_file_url($v['thumb']);
            });
        return $res;
    }

    /*
     * 获取商品付款人数
     *
     */
    public function _payNum($id)
    {
        $count = 0;
        $order_sn = Db::name("order_goods_list")
            ->where([
                'goods_id' => $id
            ])
            ->column("order_sn");
        if ($order_sn) {
            $order_str = implode(",", $order_sn);
            $where = " order_sn in ('{$order_str}') ";
            $count = Db::name("order")->where($where)->count();
        }
        return $count;
    }

    /*
     *种草
     *
     */
    public function _grass($data, $user)
    {
        $res = Circle::where([])
            ->field("id,image,user_id,content")
            ->paginate()->each(function (&$v) use ($user) {
                $info = Db::name("user")->where(['id' => $v['user_id']])->field("user_name,head_img")->find();
                $v['user_name'] = $info['user_name'];
                $v['head_img'] = $info['head_img'];
                $v['is_like'] = 0;
                if ($user) {
                    $v['is_like'] = Db::name("circle")->where(['user_id' => $user->user_id])
                        ->find() ? 1 : 0;
                }
                $v['count'] = Db::name("circle")->where(['id' => $v['id']])->value("likes");
            });
        return $res;
    }

    /*
     * 每日分享
     *
     */
    public function _share()
    {
        $res = Goods::where([
            'is_sale' => 1,
            'is_delete' => 0,
            'status' => 1,
            'is_hot' => 1
        ])
            ->field("id,name,thumb,shop_price,market_price")
            ->paginate()->each(function (&$v) {
                $v['thumb'] = get_file_url($v['thumb']);
            });
        return $res;
    }
}
