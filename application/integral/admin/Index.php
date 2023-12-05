<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 似水星辰[2630481389@qq.com]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

namespace app\integral\admin;

use app\admin\admin\Base;
use app\integral\model\Category;
use Think\Db;

/**
 * 积分商品主表控制器
 * @package app\Goods\admin
 */
class Index extends Base
{
    /**
     * 积分商品主表列表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = $this->getMap();
        $this->assign('map', $map);
        if (is_numeric($map['status'])) {
            $map['g.status']=$map['status'];
            unset($map['status']);
        }
        if ($map['name']) {
            $map[] = ['g.name', 'like', '%' . $map['name'] . '%'];
            unset($map['name']);
        }
        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = Db::name('goods_integral')->alias('g')
            ->join('goods_integral_category c', 'g.cid=c.id', 'left')
            ->field('g.*,c.name as cate_name')
            ->where($map)
            ->order($order)
            ->paginate();
        $pages = $data_list->render();
        $tab['status_1'] = ['title'=>lang('启用'),'url'=>url('integral/index/index', 'status=1'),'field'=>'status','val'=>1];
        $tab['status_2'] = ['title'=>lang('禁用'),'url'=>url('integral/index/index', 'status=2'),'field'=>'status','val'=>2];
        $this->assign('data_list', $data_list);
        $this->assign('pages', $pages);
        $this->assign('tab_list', $tab);
        $this->assign('bottom_button_select', $this->bottom_button_select);
        return $this->fetch();
    }

    /**
     * 新增
     * @return mixed
     * @throws \think\exception\PDOException
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function add($cid = 0)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($res = Db::name('goods_integral')->insertGetId($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error($this->getError());
            }
        }

        // 分类
        $cate = Category::getMenuTree(0);
        $this->assign('category', $cate);
        $this->assign('page_title', lang('新增积分商品'));
        return $this->fetch();
    }

    /**
     * 编辑
     * @param null $id 商品id
     * @return mixed
     * @throws \think\exception\PDOException
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($res = Db::name('goods_integral')->where('id', $data['id'])->update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error($goods->getError());
            }
        }

        // 分类
        $cate = Category::getMenuTree(0);

        //商品
        $goods = Db::name('goods_integral')->get($id);
       
        $this->assign('id', $id);
        $this->assign('category', $cate);
        $this->assign('integral', $goods);
        $this->assign('page_title', lang('编辑商品'));
        return $this->fetch();
    }
    
    /**
     * 删除商品自定义标签
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function label_del($label_id)
    {
        Db::name('goods_label')->where(['label_id'=>$label_id])->delete();
    }
    
    /**
     * 添加商品自定义标签
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function label_add($goods_id, $name)
    {
        $label_id = Db::name('goods_label')->insertGetId(['goods_id'=>$goods_id,'name'=>$name]);
        echo json_encode(['code'=>1,'label_id'=>$label_id]);
    }

    /**
     * 获取商品的规格
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getAllSpec()
    {
        $goods_spec = Type::where('status', 1)->field('id,name')->select();
        $this->success(lang('请求成功'), '', $goods_spec);
    }

    /**
     * 获取商品的规格
     * @param $cid 商品分类id
     * @param $aid 规格主id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/12/7 14:50
     */
    public function getGoodsSpec($cid, $aid)
    {
        $goods_spec = Goods::getGoodsSpec($cid, $aid);
        $this->success(lang('请求成功'), '', $goods_spec);
    }

    /**
     * 获取商品的属性
     * @param $cid 商品分类id
     * @param $aid 规格主id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/12/7 14:52
     */
    public function getGoodsAttr($cid, $aid)
    {
        $goods_attr = Goods::getGoodsAttr($cid, $aid);
        $this->success(lang('请求成功'), '', $goods_attr);
    }

    /**
     * 获取商品的规格以及对应商品的规格值
     * @param $goodsid 商品id
     * @param $spectypeid 规格主id
     * @param $cid 商品分类id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/12/7 9:17
     */
    public function getGoodsSpecEdit($goodsid, $spectypeid, $cid)
    {
        $goods_spec = Goods::getGoodsSpecEdit($goodsid, $spectypeid, $cid);
        $this->success(lang('请求成功'), '', $goods_spec);
    }

    /**
     * 获取商品的属性和值
     * @param $goodsid 商品id
     * @param $cid 商品分类id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/12/7 14:53
     */
    public function getGoodsAttrEdit($goodsid, $cid)
    {
        $goods_attr = Goods::getGoodsAttrEdit($goodsid, $cid);
        $this->success(lang('请求成功'), '', $goods_attr);
    }

    /**
     * 获取商品信息
     * @param $id 商品id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_info($id)
    {
        $goods = Goods::get_goods_info($id);
        $this->success(lang('请求成功'), '', $goods);
    }

    /**
     * 设置状态
     * @param string $type 类型：disable/enable
     * @param array $record 行为日志内容
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function setStatus($type = '')
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;

        empty($ids) && $this->error(lang('缺少主键'));

        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = Db::name('goods_integral')->where('id', 'IN', $ids)->setField('status', 0);
                break;
            case 'enable': // 启用
                $result = Db::name('goods_integral')->where('id', 'IN', $ids)->setField('status', 1);
                break;
            case 'delete': // 删除
                $result = Db::name('goods_integral')->where('id', 'IN', $ids)->delete();
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // \Cache::clear();
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }
}
