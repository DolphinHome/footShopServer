<?php

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\user\model\Address as AddressModel;
use app\integral\model\Category;
use service\ApiReturn;
use service\Tree;
use Think\Db;

/**
 * 积分商品接口
 * */
class Integral extends Base
{
    /**
     * 积分商品列表
     * @param array $data
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 8:19
     */
    public function index($data=[])
    {
        $user_id = $data['user_id']?$data['user_id']:"";
        if ($data['cid']) {
            $where[] = ['gi.cid' , '=', $data['cid']];
        }
        $where[] = ['gi.status','=',1];
        $list=Db::name('goods_integral')
            ->alias('gi')
            ->join('upload u', 'gi.thumb=u.id', 'left')
            ->where($where)
            ->field("gi.*")
            ->paginate()
            ->each(function ($item, $user_id) {
                if ($user_id) {
                    $order_list = Db::name('order')->where(['order_type'=>4,'user_id'=>$user_id])->where('status', ">", 0)->column('order_sn');
                    $goods_id = Db::name('order_integral_list')->where('order_sn', 'in', $order_list)->column('goods_id');
                    if (in_array($item['id'], $goods_id)) {
                        $item['is_change'] = 1;
                    } else {
                        $item['is_change'] = 0;
                    }
                } else {
                    $item['is_change'] = 0;
                }
                $item['thumb'] = get_file_url($item['thumb']);
                return $item;
            });
        return ApiReturn::r(1, $list, lang('请求成功'));
    }

    /**
     * 获取积分商品详情
     * @param array $data
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 8:19
     */
    public function detail($data = [])
    {
        $goodsId = $data['goods_id'];
        $goods_integral=Db::name('goods_integral')->get($goodsId);
        $goods_integral['thumb']=get_file_url($goods_integral['thumb']);
        $goods_integral['images']=get_files_url($goods_integral['images']);
        $goods_integral['browse'] = $this->toNumber($goods_integral['browse']);
        $goods_integral['volume'] = $this->toNumber($goods_integral['volume']);
        //增加商品浏览量
        Db::name('goods_integral')->where('id', $goodsId)->setInc('browse');
        return ApiReturn::r(1, $goods_integral, lang('请求成功'));
    }
    /**
     * 获取积分商品下单信息
     * @param array $data
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 8:19
     */
    public function get_order_info($data = [], $user = [])
    {
        $goodsId = $data['goods_id'];
        $goods_integral=Db::name('goods_integral')->get($goodsId);
        $goods_integral['thumb']=get_file_url($goods_integral['thumb']);
        $goods_integral['images']=get_files_url($goods_integral['images']);
        $goods_integral['number']=$data['number'];
        $where[] = ['user_id', '=', $user['id']];
        $where[] = ['address_id', '=', $data['address_id']];
        $address = (new AddressModel())->get_one_address($where);
        $info['goods_integral'] = $goods_integral;
        $info['address'] = $address ? $address : [];
        $info['total_integral']=bcmul($goods_integral['integral'], $data['number'], 2);
        $info['order_type']=4;
        return ApiReturn::r(1, $info, lang('请求成功'));
    }
    /**
     * 获取积分商品分类
     * @param array $data
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 8:19
     */
    public function category($data = [], $user = [])
    {
        $where['cate.is_show'] = 1;
        $where['cate.status'] = 1;
        $categoryList = Category::alias('cate')
            ->join('upload u', 'cate.thumb=u.id', 'left')
            ->where($where)->order('cate.sort', 'asc')
            ->column("cate.id,cate.pid,cate.name,cate.thumb");
        if ($categoryList) {//格式化输出
            $categoryList = Tree::toLayer($categoryList, $data['pid'], $data['max_level']);
            $this->filterData($categoryList);
            foreach ($categoryList as &$item) {
                $item['thumb'] = get_file_url($item['thumb']);
            }
            return ApiReturn::r(1, $categoryList, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));
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

    /**
     * 获取积分商品分类
     * @param array $data
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 8:19
     */
    public function log($data = [], $user = [])
    {
        $map[]=['o.user_id','=',$user['id']];
        if (!empty($data['order_sn'])) {
            $map[] = ['oi.order_sn', '=', $data['order_sn']];
        }
        $order_integral_list=Db::name('order_integral_list')
            ->alias('oi')
            ->where($map)
            ->limit(($data['page']-1), $data['page']*$data['size'])
            ->join('order o', 'oi.order_sn = o.order_sn', 'left')
            ->field('oi.order_sn,oi.goods_name,oi.goods_integral,oi.num,oi.goods_thumb,o.create_time')
            ->order('oi.order_sn desc')
            ->paginate()->each(function ($item) {
                $item['goods_thumb']=get_file_url($item['goods_thumb']);
                $item['create_time']=date('Y-m-d', $item['create_time']);
                return $item;
            });
        return ApiReturn::r(1, $order_integral_list, lang('请求成功'));
    }

    /**
     * 积分规则说明
     * @param array $data
     * @author jxy [ 41578218@qq.com ]
     * @created 2019/11/28 8:19
     */
    public function rule($data = [])
    {
        $rule = module_config('integral.integral_rule');
        return ApiReturn::r(1, ['content'=>$rule], lang('请求成功'));
    }

    public function toNumber($num, $numerical = 10000, $v = '万')
    {
        if ($num < $numerical) {
            return $num;
        }
        return bcdiv($num, $numerical, 0) . $v;
    }

    public function get_order_detail($data = [], $user = [])
    {
        $order_sn = $data['order_sn'];
        $info = Db::name('order')->where(['order_sn'=>$order_sn])->find();
        $info['order_address'] = Db::name('order_goods_info')->where(['order_sn'=>$info['order_sn']])->find();
        $goods_integral = Db::name('order_integral_list')->where(['order_sn'=>$order_sn])->select();
        foreach ($goods_integral as &$item) {
            $item['goods_thumb'] = get_file_url($item['goods_thumb']);
        }
        $info['goods_integral'] = $goods_integral;
        $info['order_express'] = Db::name('order_goods_express')->where(['order_sn'=>$order_sn])->find();
        return ApiReturn::r(1, $info, lang('请求成功'));
    }
    /*
     * 用户积分兑换商品记录,不是订单列表
     *
     */
    public function integral_exchange($data = [], $user = [])
    {
        $res = [];
        $res = Db::name("order_goods_list")
            ->alias("l")
            ->field('u.user_nickname, u.head_img, l.goods_name, l.goods_money, l.sales_integral, l.is_pure_integral')
            ->join('order o ', 'l.order_sn=o.order_sn', 'left')
            ->join('user u', 'u.id=o.user_id', 'left')
            ->where([['u.id', '<>', $user['id']]])
            ->order('o.create_time', 'desc')
            ->limit(5)
            ->select()
            ;
        foreach($res as &$item){
            $item['head_img']=get_file_url($item['head_img']);
        }
        //halt(Db::name("order_goods_list")->getLastSql());
        return ApiReturn::r(1, $res, 'ok');
    }
}
