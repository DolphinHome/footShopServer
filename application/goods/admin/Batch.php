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

namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\Goods;
use app\goods\model\Category;
use app\goods\model\Brand;
use app\goods\model\GoodsSku;
use app\goods\model\GoodsStockLog;
use app\goods\model\OrderGoodsExpress;
use app\goods\model\Type;
use app\goods\model\Freight;
use service\Str;
use service\Format;
use Think\Db;
use Think\Env;

use app\goods\model\GoodsAutoGrounding;

/**
 * 商品主表控制器
 * @package app\Goods\admin
 */
class Batch extends Base
{


    // 商品批量上下架选择
    public function goods_auto_grounding()
    {
        $map = input("param.");
        $category = Category::getMenuTree();

        if (isset($map['cid'])) {
            if ($map['cid']!=0) {
                $this->assign('cid', $map['cid']);
                $cid = Category::getChildsId($map['cid']);
                if (count($cid)) {
                    $map1[] = ['g.cid', 'in', $cid];
                } else {
                    $map1[] = ['g.cid', '=', $map['cid']];
                }
            }
            $map1[] = ['g.is_delete', '=', 0];
            // $map1[] = ['g.is_spec', '=', 0];
            $order = $this->getOrder('id desc');
            
            // 数据列表
            $data_list = Goods::alias('g')
                ->join('goods_category c', 'g.cid=c.id', 'left')
                ->field('g.*,c.name as cate_name')
                ->where($map1)
                ->order($order)
                ->select();

            foreach ($data_list as $k => $v) {
                $data_list[$k]['goods_comment_count'] = DB::name("goods_comment")->where(['goods_id'=>$v['id']])->count('id');
                $data_list[$k]['brand_id'] = DB::name("goods_brand")->where(['id'=>$v['brand_id']])->value('name');
                $data_list[$k]['freight_template_id'] = DB::name("goods_freight")->where(['id'=>$v['freight_template_id']])->value('name');
                if ($v['is_spec']==1) {
                    $sku = Db::name('goods_sku')->field('shop_price,member_price,stock')->where([['status','=',1],['goods_id', '=', $v['id']]])->find();
                    if ($sku) {
                        $data_list[$k]['member_price'] = $sku['member_price'];
                        $data_list[$k]['shop_price'] = $sku['shop_price'];
                        $data_list[$k]['stock'] = $sku['stock'];
                    }
                }
            }
        } else {
            $data_list = array();
        }
        $this->assign('category', $category);
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * 商品批量上下架确认
     * @return [type] [description]
     */
    public function goods_auto_grounding_edit()
    {
        $map = input("param.");

        if ($map['category']) {
            $goods_id = '';
            foreach ($map['category'] as $key => $value) {
                $goods_id .= ",".$value;
            }
            $goods_id = trim($goods_id, ",");
        }
        $goods_sn = $map['goods_sn'];

        // var_dump($map);die;
        if ($goods_id) {
            $map1[] = ['g.id', 'in', $goods_id];
        } elseif ($goods_sn) {
            $map1[] = ['g.sn', 'in', $goods_sn];
        } else {
            $this->error("数据错误");
        }

        $map1[] = ['g.is_delete', '=', 0];
        $map1[] = ['g.is_spec', '=', 0];
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = Goods::alias('g')
            ->join('goods_category c', 'g.cid=c.id', 'left')
            ->field('g.*,c.name as cate_name')
            ->where($map1)
            ->order($order)
            ->paginate(
                0,
                false,
                [
                    'query' => request()->param(),
                    'list_rows' =>15
                ]
            );

        foreach ($data_list as $k => $v) {
            $data_list[$k]['goods_comment_count'] = DB::name("goods_comment")->where(['goods_id'=>$v['id']])->count('id');
            $data_list[$k]['brand_id'] = DB::name("goods_brand")->where(['id'=>$v['brand_id']])->value('name');
            $data_list[$k]['freight_template_id'] = DB::name("goods_freight")->where(['id'=>$v['freight_template_id']])->value('name');
            if ($v['is_spec']==1) {
                $sku = Db::name('goods_sku')->field('shop_price,member_price,stock')->where([['status','=',1],['goods_id', '=', $v['id']]])->find();
                if ($sku) {
                    $data_list[$k]['member_price'] = $sku['member_price'];
                    $data_list[$k]['shop_price'] = $sku['shop_price'];
                    $data_list[$k]['stock'] = $sku['stock'];
                }
            }
        }

        $this->assign('data_list', $data_list);
        return $this->fetch();
    }
    // 商品批量上下架入库
    public function goods_auto_grounding_update()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (empty($data['goods_sn'])) {
                $this->error($goods->getError());
            }

            foreach ($data['goods_sn'] as $key => $goods_sn) {
                $add_data['good_sn'] = $goods_sn;
                // 记录上架信息
                if ($data['grounding'][$goods_sn]) {
                    $add_data['type'] = 1;
                    $add_data['run_time'] = strtotime($data['grounding'][$goods_sn]);
                    $goods = new GoodsAutoGrounding();
                    $res = $goods->save($add_data);
                    if ($res) {
                        $data_success[$goods_sn] = $goods_sn;
                        cache('goods_datail_' . $goods_sn, null);
                        action_log('goods_edit', 'goods', 0, UID, 'sn为' . $goods_sn);
                    } else {
                        $data_error[$goods_sn] = $goods_sn;
                    }
                }
                // 记录下架信息
                if ($data['undercarriage'][$goods_sn]) {
                    $add_data['type'] = 2;
                    $add_data['run_time'] = strtotime($data['undercarriage'][$goods_sn]);
                    $goods = new GoodsAutoGrounding();
                    $res = $goods->save($add_data);
                    if ($res) {
                        $data_success[$goods_sn] = $goods_sn;
                        cache('goods_datail_' . $goods_sn, null);
                        action_log('goods_edit', 'goods', 0, UID, 'sn为' . $goods_sn);
                    } else {
                        $data_error[$goods_sn] = $goods_sn;
                    }
                }
            }

            if ($data_error) {
                $goods_sn = '';
                foreach ($data_error as $key => $value) {
                    $goods_sn .= ",".$value;
                }
                $goods_sn = trim($goods_sn, ",");
                $this->redirect('goods_auto_grounding_edit', ['goods_sn' => $goods_sn ]);
            } else {
                // 测试用
                // $goods_sn = '';
                // foreach ($data_success as $key => $value) {
                //     $goods_sn .= ",".$value;
                // }
                // $goods_sn = trim($goods_sn,",");
                // $this->redirect('goods_auto_grounding_edit', ['goods_sn' => "'".$goods_sn."'" ]);
                // $this->success('编辑成功','goods_auto_grounding');
                $this->redirect('goods_auto_grounding');
            }
        }
    }


    // 商品上下架自动执行
    public function goods_auto_grounding_run()
    {
        $date = date("Y-m-d", strtotime("+1 day"));

        $where['run_time'] = $date;
        $where['run_status'] = 0;
        $list = GoodsAutoGrounding::where($where)->select();
        foreach ($list as $key => $value) {
            if ($value['type'] == 1) {
                $edit_data['is_sale'] = 1;
                $goods = new Goods();
                $res = $goods->save($edit_data, ['sn' => $value['good_sn']]);
                if ($res) {
                    $data_success[$value['good_sn']] = $value['good_sn'];
                // 上架成功通知
                } else {
                    // 上架失败通知
                    $data_error[$value['good_sn']] = $value['good_sn'];
                }
            }
            if ($value['type'] == 2) {
                $edit_data['is_sale'] = 0;
                $goods = new Goods();
                $res = $goods->save($edit_data, ['sn' => $value['good_sn']]);
                if ($res) {
                    $data_success[$value['good_sn']] = $value['good_sn'];
                // 下架成功通知
                } else {
                    // 下架失败通知
                    $data_error[$value['good_sn']] = $value['good_sn'];
                }
            }
        }
        var_dump($list);
        die;
    }
    // 商品批量导出选择确认下载页面
    public function goods_derive_all_download()
    {
        $map = input("param.");
        if ($map['category']) {
            $goods_id = '';
            foreach ($map['category'] as $key => $value) {
                $goods_id .= ",".$value;
            }
            $goods_id = trim($goods_id, ",");
        }
        // var_dump($map);die;
        if ($goods_id) {
            $map1[] = ['g.id', 'in', $goods_id];
        } else {
            $this->error("数据错误");
        }
        // var_dump($map1 );die;
        $map1[] = ['g.is_delete', '=', 0];
        $map1[] = ['g.is_spec', '=', 0];
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = Goods::alias('g')
            ->join('goods_category c', 'g.cid=c.id', 'left')
            ->field('g.*,c.name as cate_name')
            ->where($map1)
            ->order($order)
            ->paginate(
                0,
                false,
                [
                    'query' => request()->param(),
                    'list_rows' =>15
                ]
            );

        foreach ($data_list as $k => $v) {
            $data_list[$k]['goods_comment_count'] = DB::name("goods_comment")->where(['goods_id'=>$v['id']])->count('id');
            $data_list[$k]['brand_id'] = DB::name("goods_brand")->where(['id'=>$v['brand_id']])->value('name');
            $data_list[$k]['cid'] = DB::name("goods_category")->where(['id'=>$v['cid']])->value('name');

            $data_list[$k]['description'] = DB::name("goods_body")->where(['goods_id'=>$v['id']])->value('description');
            $data_list[$k]['body'] = DB::name("goods_body")->where(['goods_id'=>$v['id']])->value('body');

            $data_list[$k]['freight_template_id'] = DB::name("goods_freight")->where(['id'=>$v['freight_template_id']])->value('name');
            if ($v['is_spec']==1) {
                $sku = Db::name('goods_sku')->field('shop_price,member_price,stock')->where([['status','=',1],['goods_id', '=', $v['id']]])->find();
                if ($sku) {
                    $data_list[$k]['member_price'] = $sku['member_price'];
                    $data_list[$k]['shop_price'] = $sku['shop_price'];
                    $data_list[$k]['stock'] = $sku['stock'];
                }
            }
        }
        $excelData = $_excelData = [];
        foreach ($data_list as $k => $v) {
            $excelData[] = [
                'name' => $v['name'],//
                'sn' => $v['sn'],//
                'cid' => $v['cid'],// 分类id
                'adslogan' => $v['adslogan'], // 广告语
                'keywords' => $v['keywords'], //  商品关键词
                'brand_id' => $v['brand_id'], //   品牌
                'cost_price' => $v['cost_price'],// 商品成本价
                'member_price' => $v['member_price'],// 会员价
                'shop_price' => $v['shop_price'],//  本店价
                'market_price' => $v['market_price'],// 本店价
                'stock' => $v['stock'],// 总库存
                'weight' => $v['weight'],// 商品重量
                'description' => $v['description'],//
                'body' => $v['body'],//
                'is_shipping' => $v['is_shipping']==1?'是':'否',//
                'sales_sum' => $v['sales_sum'],//
                'freight_template_id' => $v['freight_template_id'],//
                'amount_condition' => $v['amount_condition'],//

            ];
        }
        $_excelData[0]['list'] = $excelData;
        $xlsName = lang('商品列表').'-' . date("Y-m-d H:i:s", time());
        $xlsCell = [
            ['name', lang('商品名称')],
            ['sn', lang('商品货号')],
            ['cid', lang('分类')],
            ['adslogan', lang('广告语')],
            ['keywords', lang('关键词')],
            ['brand_id', lang('品牌')],
            ['cost_price', lang('成本价')],
            ['member_price', lang('会员价')],
            ['shop_price', lang('本店价')],
            ['market_price', lang('划线价')],
            ['stock', lang('总库存')],
            ['weight', lang('商品重量')],
            ['description', lang('商品简介')],
            ['body', lang('商品详情')],
            ['is_shipping', lang('是否包邮')],
            ['sales_sum', lang('销量')],
            ['freight_template_id', lang('运费模板')],
            ['amount_condition', lang('满多少包邮')],
        ];
        $excelData = array_values($_excelData);

        $this->exportExcel($xlsName, $xlsCell, $excelData);
    }

    // 商品批量导出选择
    public function goods_derive_all()
    {
        $map = input("param.");
        $category = Category::getMenuTree();

        if (isset($map['cid'])) {
            if ($map['cid']!=0) {
                $this->assign('cid', $map['cid']);
                $cid = Category::getChildsId($map['cid']);
                if (count($cid)) {
                    $map1[] = ['g.cid', 'in', $cid];
                } else {
                    $map1[] = ['g.cid', '=', $map['cid']];
                }
            }
            $map1[] = ['g.is_delete', '=', 0];
            $map1[] = ['g.is_spec', '=', 0];
            $order = $this->getOrder('id desc');
            
            // 数据列表
            $data_list = Goods::alias('g')
                ->join('goods_category c', 'g.cid=c.id', 'left')
                ->field('g.*,c.name as cate_name')
                ->where($map1)
                ->order($order)
                ->paginate(
                    0,
                    false,
                    [
                        'query' => request()->param(),
                        'list_rows' =>15
                    ]
                );

            foreach ($data_list as $k => $v) {
                $data_list[$k]['goods_comment_count'] = DB::name("goods_comment")->where(['goods_id'=>$v['id']])->count('id');
                $data_list[$k]['brand_id'] = DB::name("goods_brand")->where(['id'=>$v['brand_id']])->value('name');
                $data_list[$k]['freight_template_id'] = DB::name("goods_freight")->where(['id'=>$v['freight_template_id']])->value('name');
                if ($v['is_spec']==1) {
                    $sku = Db::name('goods_sku')->field('shop_price,member_price,stock')->where([['status','=',1],['goods_id', '=', $v['id']]])->find();
                    if ($sku) {
                        $data_list[$k]['member_price'] = $sku['member_price'];
                        $data_list[$k]['shop_price'] = $sku['shop_price'];
                        $data_list[$k]['stock'] = $sku['stock'];
                    }
                }
            }
        } else {
            $data_list = array();
        }
        
        $this->assign('category', $category);
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * @Notes:批量编辑商品
     * @Interface goods_batch
     * @return mixed
     * @author: yuzubo
     * @Time: 2020/11/20
     */
    // 商品批量修改
    public function goods_batch()
    {
        $map = input("param.");
        $category = Category::getTree();
        if (isset($map['cid'])) {
            if ($map['cid']!=0) {
                $this->assign('cid', $map['cid']);
                $cid = Category::getChildsId($map['cid']);
                if (count($cid)) {
                    $map1[] = ['g.cid', 'in', $cid];
                } else {
                    $map1[] = ['g.cid', '=', $map['cid']];
                }
            }

            $map1[] = ['g.is_delete', '=', 0];
            $map1[] = ['g.is_spec', '=', 0];
            $order = $this->getOrder('id desc');
            
            // 数据列表
            $data_list = Goods::alias('g')
                ->join('goods_category c', 'g.cid=c.id', 'left')
                ->field('g.*,c.name as cate_name')
                ->where($map1)
                ->order($order)
                ->select();

            foreach ($data_list as $k => $v) {
                $data_list[$k]['goods_comment_count'] = DB::name("goods_comment")->where(['goods_id'=>$v['id']])->count('id');
                $data_list[$k]['brand_id'] = DB::name("goods_brand")->where(['id'=>$v['brand_id']])->value('name');
                $data_list[$k]['freight_template_id'] = DB::name("goods_freight")->where(['id'=>$v['freight_template_id']])->value('name');
                if ($v['is_spec']==1) {
                    $sku = Db::name('goods_sku')->field('shop_price,member_price,stock')->where([['status','=',1],['goods_id', '=', $v['id']]])->find();
                    if ($sku) {
                        $data_list[$k]['member_price'] = $sku['member_price'];
                        $data_list[$k]['shop_price'] = $sku['shop_price'];
                        $data_list[$k]['stock'] = $sku['stock'];
                        $data_list[$k]['goods_sn'] = $sku['sku_sn'];
                    }
                } else {
                    $data_list[$k]['goods_sn'] = $v['sn'];
                }
            }
        } else {
            $data_list = array();
        }

        $this->assign('cid', $map['cid']);
        $this->assign('category', $category);
        $this->assign('data_list', $data_list);
        return $this->fetch();
    }

    /**
     * @Notes:批量编辑商品
     * @Interface goods_batch_edit
     * @return mixed
     * @author: yuzubo
     * @Time: 2020/11/20
     */
    // 商品批量修改确认页面
    public function goods_batch_edit()
    {
        $map = input("param.");

        if ($map['category']) {
            $goods_id = '';
            foreach ($map['category'] as $key => $value) {
                $goods_id .= ",".$value;
            }
            $goods_id = trim($goods_id, ",");
        }
        $goods_sn = $map['goods_sn'];

        // var_dump($map);die;
        if ($goods_id) {
            $map1[] = ['g.id', 'in', $goods_id];
        } elseif ($goods_sn) {
            $map1[] = ['g.sn', 'in', $goods_sn];
        } else {
            $this->error("数据错误");
        }

        // var_dump($map1 );die;
        $map1[] = ['g.is_delete', '=', 0];
        $map1[] = ['g.is_spec', '=', 0];
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = Goods::alias('g')
            ->join('goods_category c', 'g.cid=c.id', 'left')
            ->field('g.*,c.name as cate_name')
            ->where($map1)
            ->order($order)
            ->paginate(
                0,
                false,
                [
                    'query' => request()->param(),
                    'list_rows' =>15
                ]
            );

        foreach ($data_list as $k => $v) {
            $data_list[$k]['goods_comment_count'] = DB::name("goods_comment")->where(['goods_id'=>$v['id']])->count('id');
            $data_list[$k]['brand_id'] = DB::name("goods_brand")->where(['id'=>$v['brand_id']])->value('name');
            $data_list[$k]['freight_template_id'] = DB::name("goods_freight")->where(['id'=>$v['freight_template_id']])->value('name');
            if ($v['is_spec']==1) {
                $sku = Db::name('goods_sku')->field('shop_price,member_price,stock')->where([['status','=',1],['goods_id', '=', $v['id']]])->find();
                if ($sku) {
                    $data_list[$k]['member_price'] = $sku['member_price'];
                    $data_list[$k]['shop_price'] = $sku['shop_price'];
                    $data_list[$k]['stock'] = $sku['stock'];
                }
            }
        }

        $this->assign('data_list', $data_list);

        return $this->fetch();
    }
    // 商品批量修改保存
    public function goods_batch_update()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            if (empty($data['goods_sn'])) {
                $this->error($goods->getError());
            }

            foreach ($data['goods_sn'] as $key => $goods_sn) {
                $edit_data['market_price'] = $data['market_price'][$goods_sn];
                $edit_data['shop_price'] = $data['shop_price'][$goods_sn];
                $edit_data['cost_price'] = $data['cost_price'][$goods_sn];
                $edit_data['member_price'] = $data['member_price'][$goods_sn];
                $edit_data['sales_sum'] = $data['sales_sum'][$goods_sn];
                $edit_data['stock'] = $data['stock'][$goods_sn];

                // var_dump($edit_data);die;
                $goods = new Goods();
                $res = $goods->save($edit_data, ['sn' => $goods_sn]);
                if ($res) {
                    $data_success[$goods_sn] = $goods_sn;
                    cache('goods_datail_' . $goods_sn, null);
                    action_log('goods_edit', 'goods', 0, UID, 'sn为' . $goods_sn);
                } else {
                    $data_error[$goods_sn] = $goods_sn;
                }
            }

            if ($data_error) {
                $goods_sn = '';
                foreach ($data_error as $key => $value) {
                    $goods_sn .= ",".$value;
                }
                $goods_sn = trim($goods_sn, ",");
                $this->redirect('goods_batch_edit', ['goods_sn' => $goods_sn ]);
            } else {
                // 测试用
                // $goods_sn = '';
                // foreach ($data_success as $key => $value) {
                //     $goods_sn .= ",".$value;
                // }
                // $goods_sn = trim($goods_sn,",");
                $this->redirect('goods_batch');
                // $this->success('编辑成功','goods_batch');
            }
        }
    }
    /**
     * @Notes:批量添加商品
     * @Interface goods_batch_add
     * @return mixed
     * @author: yuzubo
     * @Time: 2020/11/20
     */
    public function goods_batch_add()
    {
        return $this->fetch();
    }

    /**
     * @Notes:批量导出
     * @Interface goods_batch_export
     * @return mixed
     * @author: yuzubo
     * @Time: 2020/11/20
     */
    public function goods_batch_export()
    {
        return $this->fetch();
    }

    /**
     * @Notes:批量自动下架
     * @Interface goods_batch_auto
     * @return mixed
     * @author: yuzubo
     * @Time: 2020/11/20
     */
    public function goods_batch_auto()
    {
        return $this->fetch();
    }

    
    public function goodsImport()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder("upload_time desc");
        // 数据列表
        $data_list = Db::name("goods_import")
            ->where($map)
            ->order($order)
            ->paginate()
            ->each(function ($item) {
                $item['upload_time'] = date("Y-m-d H:i:s", $item['upload_time']);
                return $item;
            });
        $this->assign("data_list", json_encode($data_list, JSON_UNESCAPED_UNICODE));
        return $this->fetch();

        /*        $fields = [
                    ['file_name', '上传文件'],
                    ['upload_time', '上传时间'],
                    ['file_size', '文件大小(KB)'],
                    ['status', '处理状态','status','',['处理中','已处理']],
                    ['update_time', '处理完成时间','status','',['否','是']],
                    ['success_line', '处理成功条数'],
                    ['fail_line', '处理失败条数'],
                ];
                return Format::ins()//实例化
                ->addColumns($fields)//设置字段
                ->setTopButton(['type' => 'file', 'name' => 'zip_addr', 'title' => '帧动画zip'])
                    ->setData($data_list)//设置数据
                    ->fetch();//显示*/
    }

    //导出商品及多规格的库存
    public function goods_stock_export()
    {
        // 数据列表
        $data_list = Goods::alias('g')
            ->field('g.*')
            ->where(['is_sale'=>1,'status'=>1,'is_delete'=>0])
            ->order('id desc')
            ->select();
         
        foreach ($data_list as $k => $v) {
            $sku = [];
            if ($v['is_spec']==1) {
                $sku = Db::name('goods_sku')->field('sku_sn,key_name,stock')->where([['status','=',1],['goods_id', '=', $v['id']]])->select();
            }
            $data_list[$k]['sku'] = $sku;
        }

        $excelData = $_excelData = [];
        foreach ($data_list as $k => $v) {
            if ($v['sku']) {
                foreach ($v['sku'] as $sk) {
                    $excelData[] = [
                        'name' => $v['name'],
                        'sku_sn' => $sk['sku_sn'],
                        'key_name' => $sk['key_name'],
                        'stock' => $sk['stock'],
                    ];
                }
            } else {
                $excelData[] = [
                    'name' => $v['name'],
                    'sku_sn' => $v['sn'],
                    'key_name' => '',
                    'stock' => $v['stock'],
                ];
            }
        }

      
        $_excelData[0]['list'] = $excelData;
        $xlsName = lang('商品库存列表').'-' . date("Y-m-d H:i:s", time());
        $xlsCell = [
            ['name', lang('商品名称')],
            ['sku_sn', lang('商品货号')],
            ['key_name', lang('规格')],
            ['stock', lang('库存')],
        ];
        $excelData = array_values($_excelData);

        $this->exportExcel($xlsName, $xlsCell, $excelData);
    }

    public function goods_stock_import()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder("upload_time desc");
        // 数据列表
        $data_list = Db::name("goods_import")
            ->where($map)
            ->order($order)
            ->paginate()
            ->each(function ($item) {
                $item['upload_time'] = date("Y-m-d H:i:s", $item['upload_time']);
                $item['update_time'] = date("Y-m-d H:i:s", $item['update_time']);
                $item['status'] = $item['status'] == 1 ? '已完成' : '处理中';
                return $item;
            });
        $this->assign("data_list", json_encode($data_list, JSON_UNESCAPED_UNICODE));
        return $this->fetch();
    }

    
    //导入商品库存
    public function stock_upload()
    {
        //上传excel文件
        $file = request()->file('excel');
        if ($file) {
            //将文件保存到public/uploads目录下面
            $info = $file->validate(['size' => 1048576, 'ext' => 'xls,xlsx'])->move('./uploads');
            if ($info) {
                //获取上传到后台的文件名
                $fileName = $info->getSaveName();
                //获取文件路径
                $fileName = str_ireplace('\\','/', $fileName);
                $filePath = config('web_site_domain') . '/uploads/'.$fileName;
                $goods_import = Db::name('goods_import')->where('file_name', $_FILES['excel']['name'])->find();
                if ($goods_import) {
                    return json_encode(['code'=>0,'data'=>[],'msg'=>lang('文件名称重复')]);
                }
                //获取文件后缀
                $suffix = $info->getExtension();
                $data['file_name'] = $_FILES['excel']['name'];
                $data['upload_time'] = time();
                $data['file_size'] = $_FILES['excel']['size'];
                $data['file_url'] = $filePath;
                $data['file_suffix'] = $suffix;
                $data['admin_id'] = UID;
                $data['admin_name'] = $_SESSION['think']['admin_auth']['nickname'];
                $id = Db::name('goods_import')->insertGetId($data);
                if ($id) {
                    $this->stock_import($id);
                    return json_encode(['code'=>1,'data'=>[],'msg'=>lang('文件上传成功')]);
                }
                return json_encode(['code'=>0,'data'=>[],'msg'=>lang('文件上传失败')]);
            } else {
                return json_encode(['code'=>0,'data'=>[],'msg'=>lang('文件过大或格式不正确导致上传失败')]);
            }
        }
        return json_encode(['code'=>0,'data'=>[],'msg'=>lang('请选择上传文件')]);
    }

    //导入商品库存
    public function stock_import($id)
    {
        require  '../vendor/PHPExcel/PHPExcel.php';
        $val = Db::name("goods_import")->where(['id'=>$id])->find();
        if ($val) {
                // 循环处理文件
                //判断哪种类型
                if ($val['file_suffix'] == "xlsx") {
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007');
                } else {
                    $reader = \PHPExcel_IOFactory::createReader('Excel5');
                }

                $filePath = get_file_local_path($val['file_url']);
                $excel = $reader->load("$filePath", $encode = 'utf-8');
                //读取第一张表
                $sheet = $excel->getSheet(0);
                //获取总行数
                $row_num = $sheet->getHighestRow();
                //获取总列数
                $col_num = $sheet->getHighestColumn();

                $data = []; //数组形式获取表格数据
                $data_body = [];
                $error_data = [];
                $success_line = $fail_line = 0;
                for ($i = 3; $i <= $row_num; $i++) {
                    $data['sn'] = empty($sheet->getCell("B" . $i)->getValue())?'sn' . Str::randString(12, 1):$sheet->getCell("B" . $i)->getValue();
                    $data['key_name'] = $sheet->getCell("C" . $i)->getValue();
                    $data['stock'] = $sheet->getCell("D" . $i)->getValue();

                    if(empty($data['stock'])) {
                        continue;
                    }
                    // 启动事务
                    Db::startTrans();
                    try {
                        //处理数据
                        if ($data['key_name']) {
                            $good_info = Db::name("goods_sku")->where('sku_sn', $data['sn'])->find();
                            if(empty($good_info)){
                                exception($data['sn']
                            
                            );
                            }
                            $stock_after = $good_info['stock'] + $data['stock'];
                            $res = Db::name("goods_sku")->where('sku_sn', $data['sn'])->setInc('stock', $data['stock']);
                            if($res === false){
                                exception(Db::name("goods_sku")->getLastSql());
                            }
                            //添加库存变动日志
                            GoodsStockLog::AddStockLog(
                                $good_info['goods_id'],
                                $good_info['sku_id'],
                                '',
                                $good_info['stock'],
                                $data['stock'],
                                $stock_after,
                                1,
                                UID,
                                lang('管理员进货'),
                                $data['sn']
                            );

                        } else {
                            $good_info = Db::name("goods")->where('sn', $data['sn'])->find();
                            $stock_after = $good_info['stock'] + $data['stock'];
                            $res = Db::name("goods")->where('sn', $data['sn'])->setInc('stock', $data['stock']);
                            if($res === false){
                                exception(Db::name("goods")->getLastSql());
                            }

                            //添加库存变动日志
                            GoodsStockLog::AddStockLog(
                                $good_info['id'],
                                0,
                                '',
                                $good_info['stock'],
                                $data['stock'],
                                $stock_after,
                                1,
                                UID,
                                lang('管理员进货'),
                                $data['sn']
                            );
                        }
                        $success_line ++;
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        // 记录error
                        $fail_line ++;
                        $error_data[$i]= $e->getMessage();
                    }
                }
                if($fail_line){
                    // 记录文件处理反馈
                    $save['goods_import_id'] = $val['id'];
                    $save['content'] = serialize($error_data);
                    Db::name("goods_import_feedback")->insertGetId($save);
                }
                $updatedata = [
                    'success_line' => $success_line,
                    'fail_line' => $fail_line,
                    'update_time' => time(),
                    'status' => 1,
                ];
                Db::name("goods_import")->where(['id'=>$id])->update($updatedata);
                return true;
        }
    }
}
