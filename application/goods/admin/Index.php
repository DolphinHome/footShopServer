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
use app\goods\model\GoodsLabelService;
use app\goods\model\Type;
use app\goods\model\Freight;
use service\Str;
use service\Format;
use Think\Db;
use app\goods\model\ActivityDetails as ActivityDetailsModel;

/**
 * 商品主表控制器
 * @package app\Goods\admin
 */
class Index extends Base
{
    /**
     * 商品主表列表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index($type = 'all')
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map_data = input('param.params');

        if ($map_data) {
            $this->assign('map_data', $map_data);
            $where = json_decode($map_data, true);

            foreach ($where as $k => $v) {
                if (
                    $v['key'] == 'name'
                    || $v['key'] == 'status'
                    || $v['key'] == 'is_hot'
                    || $v['key'] == 'sales_sum'
                ) {
                    $map1[] = ['g.' . $v['key'], $v['expression'], $v['value']];
                } else {
                    $map1[] = [$v['key'], $v['expression'], $v['value']];
                }
            }
        }
        $map1[] = ['g.is_delete', '=', 0];

        $map = input("param.");
//                halt($map);


        if (isset($map['name'])) {
            $map1[] = ['g.name', 'like', '%' . $map['name'] . '%'];
        }
        if (isset($map['cid']) && $map['cid'] != 0) {
            $this->assign('cid', $map['cid']);
            $cid = Category::getChildsId($map['cid']);
            if (count($cid)) {
                array_push($cid, $map['cid']);
                $map1[] = ['g.cid', 'in', $cid];
            } else {
                $map1[] = ['g.cid', '=', $map['cid']];
            }
        }
        //chen add 增加上下架搜索
        $map['is_sale'] = $map['is_sale'] ?? -1;
        if ($map['is_sale'] != -1) {
            $map1[] = ['g.is_sale', '=', $map['is_sale']];
        }
        $this->assign('map', $map);
        $category = Category::getMenuTree();

        // 排序
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
                    'list_rows' => 15
                ]
            );
        foreach ($data_list as $k => $v) {
            $data_list[$k]['goods_comment_count'] = DB::name("goods_comment")->where(['goods_id' => $v['id']])->count('id');
            $data_list[$k]['brand_id'] = DB::name("goods_brand")->where(['id' => $v['brand_id']])->value('name');
            $data_list[$k]['freight_template_id'] = DB::name("goods_freight")->where(['id' => $v['freight_template_id']])->value('name');
            /*            if($v['is_spec']==1){
                            $sku = Db::name('goods_sku')->field('shop_price,member_price,stock')->where([['status','=',1],['goods_id', '=', $v['id']]])->find();
                            if ($sku) {
                                $data_list[$k]['member_price'] = $sku['member_price'];
                                $data_list[$k]['shop_price'] = $sku['shop_price'];
                                $data_list[$k]['stock'] = $sku['stock'];
                            }
                        }*/
        }
        $tableData = $this->getSearchData($category);
        $pages = $data_list->render();
        $tab[] = ['title' => lang('上架'), 'type' => 'up_sale', 'url' => url('goods/index/index', 'type=up_sale')];
        $tab[] = ['title' => lang('下架'), 'type' => 'down_sale', 'url' => url('goods/index/index', 'type=down_sale')];
        $tab[] = ['title' => lang('推荐'), 'type' => 'recommend', 'url' => url('goods/index/index', 'type=recommend')];
        $tab[] = ['title' => lang('新品'), 'type' => 'new', 'url' => url('goods/index/index', 'type=new')];
        $tab[] = ['title' => lang('热卖'), 'type' => 'hot', 'url' => url('goods/index/index', 'type=hot')];
        $tab[] = ['title' => lang('精品'), 'type' => 'boutique', 'url' => url('goods/index/index', 'type=boutique')];
        $tab[] = ['title' => lang('售馨'), 'type' => 'xin', 'url' => url('goods/index/index', 'type=xin')];
        $tab[] = ['title' => lang('预警'), 'type' => 'warning', 'url' => url('goods/index/index', 'type=warning')];
        $this->assign('data_list', $data_list);
        $this->assign('table_data', json_encode($tableData, JSON_UNESCAPED_UNICODE));
        $this->assign('pages', $pages);
        $this->assign('count', count($data_list));
        $this->assign('tab_list', $tab);
        $this->assign('type', $type);
        $this->assign('param', $map);
        $this->assign('category', $category);
        $this->assign('bottom_button_select', $this->bottom_button_select);
        return $this->fetch();
    }

    /**
     * 搜索框
     * @param {*} $category
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-28 11:46:19
     */
    public function getSearchData($category)
    {
        foreach ($category as $key => $val) {
            $cateData['id'] = $key;
            $cateData['name'] = trim($val, "&nbsp;");
            $cateNewData[] = $cateData;
        }
        $searchData = [
            [
                'name' => [
                    'field' => 'name',
                    'value' => lang('商品名称')
                ],
                'type' => 'text',
            ],
            [
                'name' => [
                    'field' => 'cid',
                    'value' => lang('商品分类')
                ],
                'type' => 'select',
                'select' => $cateNewData
            ],
            [
                'name' => [
                    'field' => 'shop_price',
                    'value' => lang('价格')
                ],
                'type' => 'text',
            ],
            [
                'name' => [
                    'field' => 'sn',
                    'value' => lang('货号')
                ],
                'type' => 'text',
            ],
            [
                'name' => [
                    'field' => 'is_shipping',
                    'value' => lang('包邮')
                ],
                'type' => 'select',
                'select' => [
                    [
                        'id' => 0,
                        'name' => lang('否')
                    ], [
                        'id' => 1,
                        'name' => lang('是')
                    ]
                ]
            ],
            [
                'name' => [
                    'field' => 'is_spec',
                    'value' => lang('是否规格')
                ],
                'type' => 'select',
                'select' => [
                    [
                        'id' => 0,
                        'name' => lang('否')
                    ], [
                        'id' => 1,
                        'name' => lang('是')
                    ]
                ]
            ],
            [
                'name' => [
                    'field' => 'sales_sum',
                    'value' => lang('虚拟销量')
                ],
                'type' => 'text',
            ],
            [
                'name' => [
                    'field' => 'sale_num_new',
                    'value' => lang('真实销量')
                ],
                'type' => 'text',
            ],
            [
                'name' => [
                    'field' => 'is_new',
                    'value' => lang('新品')
                ],
                'type' => 'select',
                'select' => [
                    [
                        'id' => 0,
                        'name' => lang('否')
                    ], [
                        'id' => 1,
                        'name' => lang('是')
                    ]
                ]
            ],
            [
                'name' => [
                    'field' => 'is_hot',
                    'value' => lang('热卖')
                ],
                'type' => 'select',
                'select' => [
                    [
                        'id' => 0,
                        'name' => lang('否')
                    ], [
                        'id' => 1,
                        'name' => lang('是')
                    ]
                ]
            ],
            [
                'name' => [
                    'field' => 'is_recommend',
                    'value' => lang('推荐')
                ],
                'type' => 'select',
                'select' => [
                    [
                        'id' => 0,
                        'name' => lang('否')
                    ], [
                        'id' => 1,
                        'name' => lang('是')
                    ]
                ]
            ],
            [
                'name' => [
                    'field' => 'is_boutique',
                    'value' => lang('精品')
                ],
                'type' => 'select',
                'select' => [
                    [
                        'id' => 0,
                        'name' => lang('否')
                    ], [
                        'id' => 1,
                        'name' => lang('是')
                    ]
                ]
            ],
            [
                'name' => [
                    'field' => 'is_sale',
                    'value' => lang('上架')
                ],
                'type' => 'select',
                'select' => [
                    [
                        'id' => 0,
                        'name' => lang('否')
                    ], [
                        'id' => 1,
                        'name' => lang('是')
                    ]
                ]
            ],
            [
                'name' => [
                    'field' => 'stock',
                    'value' => lang('库存')
                ],
                'type' => 'text',
            ],
            [
                'name' => [
                    'field' => 'status',
                    'value' => lang('状态')
                ],
                'type' => 'select',
                'select' => [
                    [
                        'id' => 0,
                        'name' => lang('禁用')
                    ], [
                        'id' => 1,
                        'name' => lang('启用')
                    ]
                ]
            ],
        ];
        return $searchData;
//        return json_encode($searchData);
    }


    /**
     * 添加商品【商品-->商品-->添加商品】
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
            if (empty($data['sn'])) {
                $data['sn'] = 'sn' . Str::randString(12, 1);
            }
            $data['goods_label_service'] = trim($data['goods_label_service'], ',');
            $data['goods_label_activity'] = trim($data['goods_label_activity'], ',');
            // 验证
            $result = $this->validate($data, 'Goods.add');
            if (true !== $result) {
                $this->error($result);
            }
            $goods = new Goods();
            if ($res = $goods->save_update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_index_add', 'goods', $res['goods_id'], UID, $details);
                //$result=addons_action('Recommend/Api/addGoods', [$res['goods_id'],$data['name'],$data['cid']]);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error($goods->getError());
            }
        }

        // 分类
        $cate = Category::getMenuTree(0);

        // 品牌
        $brand = Brand::where('status', 1)->column('id,name');
        $goods_label_service = GoodsLabelService::where("type", 1)->field("id,name")->select();
        $goods_label_activity = GoodsLabelService::where("type", 2)->field("id,name")->select();

        $goods_lable = Db::name('goods_lable')->column('id,lable_name');
        $this->assign('goods_lable', $goods_lable);
        // 运费模板
        $freight_template = Freight::where('status = 1')->order('sort asc')->column('id,name');
        $this->assign('category', $cate);
        $this->assign('goods_label_service', $goods_label_service);
        $this->assign('goods_label_activity', $goods_label_activity);
        $this->assign('brand', $brand);
        $this->assign('cid', $cid);
        $this->assign('freight_template', $freight_template);
        //        $this->assign('sender', Db::name('goods_express_sender')->column('id,name'));
        $this->assign('page_title', lang('新增商品'));
        return $this->fetch();
    }

    /**
     * TODO:【2023-12-11】 编辑商品
     * @param null $id 商品id
     * @return mixed
     * @throws \think\exception\PDOException
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        $is_copy = input("param.is_copy");
        if ($is_copy !== true) {
            if ($id === null) {
                $this->error(lang('缺少参数'));
            }
        }
        //商品
        $goods = Goods::get_goods_info($id);

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (empty($data['sn'])) {
                $data['sn'] = 'sn' . Str::randString(12, 1);
            }
            // 验证
            $result = $this->validate($data, 'Goods.edit');
            $data['goods_label_service'] = trim($data['goods_label_service'], ',');
            $data['goods_label_activity'] = trim($data['goods_label_activity'], ',');
            if (true !== $result) {
                $this->error($result);
            }
            $goodsModel = new Goods();
            if ($res = $goodsModel->save_update($data)) {
                if ($is_copy === true) {
                    $msg = "复制成功";
                    action_log('goods_add', 'goods', $res['goods_id'], UID, 'ID为' . $res['goods_id']);
                } else {
                    $msg = "编辑成功";
                    cache('goods_datail_' . $id, null);
                    //记录行为
                    unset($data['__token__']);
                    $details = arrayRecursiveDiff($data, $goods);
                    action_log('goods_index_edit', 'goods', $res['goods_id'], UID, $details);
                }
                $this->success($msg, cookie('__forward__'));
            } else {
                $this->error($goodsModel->getError());
            }
        }

        // 分类
        $cate = Category::getMenuTree(0);
        // 品牌
        $brand = Brand::where('status', 1)->column('id,name');
        // 运费模板
        $freight_template = Freight::order('sort asc')->column('id,name');

        if ($goods['is_wholesale'] == 1 && $goods['is_spec'] == 0) {
            $wholesale = Db::name('goods_wholesale')->where(['goods_id' => $id, 'sku_id' => 0])->field('start_batch,trade_price')->select();
            $goods['wholesale'] = json_encode($wholesale, true);
        } else {
            $goods['wholesale'] = '';
        }
        //商品标签
        $tags = Db::name('goods_label')->where(['goods_id' => $id])->column("name");
        /*        foreach ($tags as $k => $t) {
                    $tags_list[] = $t['label_id'] . ':' . $t['name'];
                }*/
        //商品的自定义小标签
        $goods_labels = Db::name('goods_lable')->where('id', 'in', $goods['lable_id'])->column('id,lable_name');
        $goods_label_service = GoodsLabelService::where("type", 1)->field("id,name")->select();
        $goods_label_activity = GoodsLabelService::where("type", 2)->field("id,name")->select();
        $this->assign('goods_label_service', $goods_label_service);
        $this->assign('goods_label_activity', $goods_label_activity);

        $goods_lable = Db::name('goods_lable')->column('id,lable_name');
        $this->assign('goods_lable', $goods_lable);
        $this->assign('goods_lables', $goods_labels);
        $this->assign('formula_award_mode', module_config('goods.formula_award_mode'));
        $this->assign('id', $id);
        $this->assign('is_copy', input("param.is_copy"));
        $this->assign('category', $cate);
        $this->assign('brand', $brand);
        $this->assign('goods', $goods);
        $this->assign('tags', implode(',', $tags));
        $attr = Db::name('goods_type_attribute')->where([['typeid', '=', $goods['spectypeid']]])->find();
        $goods_attr_val = Db::name('goods_type_attr')->where([['goods_id', '=', $goods['id']]])->find();
        $this->assign('attr_val', $goods_attr_val['value']);
        $this->assign('attr', explode(',', $attr['value']));
        $this->assign('attr_name', $attr['name']);
        $this->assign('freight_template', $freight_template);
//        $this->assign('sender', Db::name('goods_express_sender')->column('id,name'));
        $this->assign('page_title', lang('编辑商品'));
        return $this->fetch();
    }

    public function get_label()
    {
        $resData = Db::name('goods_lable')->field('id,lable_name')->select();
        $res = [
            "code" => 1,
            "data" => $resData,
            "msg" => "获取成功",
        ];
        return json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 删除商品自定义标签
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function label_del($label_id)
    {
        Db::name('goods_label')->where(['label_id' => $label_id])->delete();
    }

    /**
     * 获取某个商品所有规格值
     * @return json
     * @author zhougs
     */
    public function getGoodsSku()
    {
        $data = $this->request->get();
        $resData = Db::name('goods_sku')->field("sku_id,key_name,sku_sn,shop_price,market_price,member_price,cost_price,stock,stock_warning")->where(['goods_id' => $data['goods_id'], 'status' => 1])->select();
        $res = [
            "code" => 1,
            "data" => $resData,
            "msg" => "获取成功",
        ];
        return json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 更新某个商品某个规格值
     * @return json
     * @author zhougs
     */
    public function editGoodsSku()
    {
        // 启动事务
        Db::startTrans();
        try {
            $data = $this->request->get();
            $retData = $data;
            $goods_id = $data['goods_id'];
            $sku_id = $data['sku_id'];
            unset($data['goods_id']);
            unset($data['sku_id']);
            $resData = Db::name('goods_sku')->where(['goods_id' => $goods_id, "sku_id" => $sku_id])->update($data);
            if (!$resData) {
                exception("更新失败");
            }
            $info = GoodsSku::get($sku_id);
            $stock = $info['stock'];
            if ($stock != $data['stock']) {
                if ($stock > $data['stock']) {
                    $type = 2;
                } else {
                    $type = 1;
                }
                $stock_change = abs($stock - $data['stock']);
                $order_sn = '';
                $remark = lang('管理员操作');
                $operator = UID;

                $res = GoodsStockLog::AddStockLog($goods_id, $sku_id, $order_sn, $stock, $stock_change, $data['stock'], $type, $operator, $remark, $info['sku_sn']);
                if (!$res) {
                    exception("更新失败");
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json_encode(["code" => 0, "data" => $retData, "msg" => $e->getMessage()]);
        }
        return json_encode(["code" => 1, "data" => $retData, "msg" => "更新成功"]);
    }

    /**
     * 下载批量上传商品模板
     */
    public function download_model()
    {
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

        $list = [
            ['name', ''],
            ['sn', ''],
            ['cid', ''],
            ['adslogan', ''],
            ['keywords', ''],
            ['brand_id', ''],
            ['cost_price', ''],
            ['member_price', ''],
            ['shop_price', ''],
            ['market_price', ''],
            ['stock', ''],
            ['weight', ''],
            ['description', ''],
            ['body', ''],
            ['is_shipping', ''],
            ['sales_sum', ''],
            ['freight_template_id', ''],
            ['amount_condition', ''],
        ];
        $_excelData[0]['list'] = $list;
        $this->exportExcel(lang('批量导入商品模板'), $xlsCell, $_excelData);
    }

    public function goodsImport()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        if ($map['admin_name']) {
            $where[] = ["admin_name", "=", $map['admin_name']];
        }
        if ($map['time'] != "") {
            $time = explode(' - ', $map['time']);
            $where[] = ["upload_time", "between", [strtotime($time[0]), strtotime($time[1])]];
        }
        // 排序
        $order = $this->getOrder("upload_time desc");
        // 数据列表
        $data_list = Db::name("goods_import")
            ->where($where)
            ->order($order)
            ->paginate()
            ->each(function ($item) {
                $item['upload_time'] = date("Y-m-d H:i:s", $item['upload_time']);
                $item['update_time'] = date("Y-m-d H:i:s", $item['update_time']);
                $status_set = ['处理中', '已完成'];
                $item['status'] = $status_set[$item['status']];
                $item['del_url'] = 'goods_import_del/id/' . $item['id'];
                return $item;
            });
        $this->assign("data_list", json_encode($data_list, JSON_UNESCAPED_UNICODE));
        $this->assign("map", json_encode($map, JSON_UNESCAPED_UNICODE));
        return $this->fetch();
    }


    public function goods_import_del()
    {
        $id = input('id') ?? 1;
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        Db::name('goods_import')->where(['id' => $id])->delete();
        $this->success(lang('删除成功'));
    }

    /**
     * 导入商品excel
     * @return mixed|string
     */
    public function import()
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
//                $filePath = \Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $fileName;
                $filePath = config('web_site_domain') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $fileName;
                $goods_import = Db::name('goods_import')->where('file_name', $_FILES['excel']['name'])->find();
                if ($goods_import) {
                    return json_encode(['code' => 0, 'data' => [], 'msg' => lang('文件名称重复')]);
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
                    $this->getImport($id);
                    return json_encode(['code' => 1, 'data' => [], 'msg' => lang('文件上传成功')]);
                }
                return json_encode(['code' => 0, 'data' => [], 'msg' => lang('文件上传失败')]);
            } else {
                return json_encode(['code' => 0, 'data' => [], 'msg' => lang('文件过大或格式不正确导致上传失败')]);
            }
        }
        return json_encode(['code' => 0, 'data' => [], 'msg' => lang('请选择上传文件')]);
    }

    /**
     * 批量导入商品操作
     * @return mixed
     */
    public function getImport($id)
    {
        require '../vendor/PHPExcel/PHPExcel.php';
        $val = Db::name("goods_import")->where(["status" => 0, 'id' => $id])->find();

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
            for ($i = 2; $i <= $row_num; $i++) {

                $data['sn'] = empty($sheet->getCell("B" . $i)->getValue()) ? 'sn' . Str::randString(12, 1) : $sheet->getCell("B" . $i)->getValue();
                $data['name'] = $sheet->getCell("A" . $i)->getValue();

                $cate_name = $sheet->getCell("C" . $i)->getValue();
                $brand_name = $sheet->getCell("F" . $i)->getValue();
                $cid = Db('goods_category')->where(['name' => trim($cate_name)])->value('id');
                if (!$cid) {
                    $error_data[$i]['line'] = $i;
                    $error_data[$i]['msg'] = lang('分类名称暂无匹配数据');
                    continue;
                }

                $brand_id = Db('goods_brand')->where(['name' => trim($brand_name)])->value('id');
                if (!$brand_id) {
                    $error_data[$i]['line'] = $i;
                    $error_data[$i]['msg'] = lang('品牌名称暂无匹配数据');
                    continue;
                }

                $is_shiping = $sheet->getCell("O" . $i)->getValue();
                if ($is_shiping == '否') {
                    $is_shiping_status = 0;
                } elseif ($is_shiping == '是') {
                    $is_shiping_status = 1;
                }

                $freight_template = $sheet->getCell("Q" . $i)->getValue();
                $freight_template_id = Db('goods_freight')->where(['name' => trim($freight_template)])->value('id');
                if (!$freight_template_id) {
                    $error_data[$i]['line'] = $i;
                    $error_data[$i]['msg'] = lang('运费模板暂无匹配数据');
                    continue;
                }

                $data['cid'] = $cid;
                $data['adslogan'] = $sheet->getCell("D" . $i)->getValue() ?? '';
                $data['keywords'] = $sheet->getCell("E" . $i)->getValue() ?? '';
                $data['brand_id'] = $brand_id;
                $data['cost_price'] = $sheet->getCell("G" . $i)->getValue() ?? 0;
                $data['member_price'] = $sheet->getCell("H" . $i)->getValue() ?? 0;
                $data['shop_price'] = $sheet->getCell("I" . $i)->getValue() ?? 0;
                $data['market_price'] = $sheet->getCell("J" . $i)->getValue() ?? 0;
                $data['stock'] = $sheet->getCell("K" . $i)->getValue() ?? 0;
                $data['weight'] = $sheet->getCell("L" . $i)->getValue() ?? 0;
                $data['is_shipping'] = $is_shiping_status ?? 0;
                $data['sales_sum'] = $sheet->getCell("P" . $i)->getValue() ?? 0;
                $data['freight_template_id'] = $freight_template_id ?? 0;
                $data['amount_condition'] = $sheet->getCell("R" . $i)->getValue() ?? 0;

                // 查询商品主表
                $good_info = Db::name("goods")->where('sn', $data['sn'])->find();

                // 启动事务
                Db::startTrans();
                try {
                    // 处理数据 如有数据 则更新数据
                    if ($good_info) {
                        //将数据保存到商品主表
                        $res = Db::name("goods")->where(['sn' => $good_info['sn']])->update($data);
                        if ($res === false) {
                            exception(lang('商品表修改失败') . $i);
                        }

                        $data_body['description'] = $sheet->getCell("M" . $i)->getValue() ?? '';
                        $data_body['body'] = $sheet->getCell("N" . $i)->getValue() ?? '';
                        $goods_body_id = Db::name("goods_body")->where(['goods_id' => $good_info['id']])->update($data_body);
                        if ($goods_body_id === false) {
                            exception(lang('商品详情表修改失败') . $i);
                        }
                    } else {
                        // 查询无数据 则新增数据
                        //将数据保存到商品主表
                        $goods_id = Db::name("goods")->insertGetId($data);
                        if (!$goods_id) {
                            exception(lang('商品表插入失败') . $i);
                        }
                        $data_body['goods_id'] = $goods_id;
                        $data_body['description'] = $sheet->getCell("M" . $i)->getValue() ?? '';
                        $data_body['body'] = $sheet->getCell("N" . $i)->getValue() ?? '';
                        $goods_body_id = Db::name("goods_body")->insert($data_body);
                        if (!$goods_body_id) {
                            exception(lang('商品详情表插入失败') . $i);
                        }
                    }
                    $success_line++;
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    // 记录error
                    $fail_line++;
                    $error_data[$i] = $e->getMessage();
                }
            }

            // 记录文件处理反馈，失败信息
            if ($fail_line) {
                $save['goods_import_id'] = $val['id'];
                $save['content'] = serialize($error_data);
                Db::name("goods_import_feedback")->insertGetId($save);
            }
            //更新上传文件的处理状态和成功、失败条数
            $updatedata = [
                'success_line' => $success_line,
                'fail_line' => $fail_line,
                'update_time' => time(),
                'status' => 1,
            ];
            Db::name("goods_import")->where(['id' => $id])->update($updatedata);
            return true;
        }
    }

    public function sku_pop()
    {
        $this->fetch();
    }

    /**
     * 添加商品自定义标签
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function label_add($goods_id, $name)
    {
        $label_id = Db::name('goods_label')->insertGetId(['goods_id' => $goods_id, 'name' => $name]);
        echo json_encode(['code' => 1, 'label_id' => $label_id]);
    }

    /**
     * 获取商品的规格
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getAllSpec()
    {
        $goods_spec = Type::where('status', 1)->field('id,name')->order('id desc')->select();
        $this->success(lang('请求成功'), '', $goods_spec);
    }

    /**
     * 获取商品的规格
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getSpecByCid()
    {
        $data = $this->request->post();
        //$data['cid'] = 3;
        $goods_spec = Type::where('status', 1)->where(['cid' => $data['cid']])->field('id,name')->order('id desc')->select();
        echo json_encode($goods_spec, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 启用禁用规则
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function setSkuInfo()
    {
        $data = $this->request->post();
        $info = GoodsSku::where(['sku_id' => $data['sku_id']])->update(['status' => $data['status']]);
        if ($info !== false) {
            echo json_encode(['code' => 1, 'msg' => lang('更新成功')]);
        } else {
            echo json_encode(['code' => 0, 'msg' => lang('更新失败')]);
        }
    }

    /**
     * 获取商品的规格
     * @param $cid 商品分类id
     * @param $aid 规格主id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/12/7 14:50
     */
    public function getGoodsSpec($cid = 0, $aid = 0)
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
    public function getGoodsAttr($cid = 0, $aid = 0)
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
    public function setStatus()
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $type = input('param.type');
        $ids = (array)$ids;

        empty($ids) && $this->error(lang('缺少主键'));
        //正参与活动的商品不能下架/删除/禁用
        $ids = $this->filterActivityGoods($ids);
        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                if (count($ids) < 1) {
                    $this->error(lang('商品有参与活动，不可禁用'));
                }
                $result = Goods::where('id', 'IN', $ids)->setField('status', 0);
                Db::name('goods_cart')->where([['goods_id', 'in', $ids]])->delete();
                action_log('goods_disable', 'goods', 0, UID, '批量禁用商品ID:' . $ids);
                break;
            case 'enable': // 启用
                $result = Goods::where('id', 'IN', $ids)->setField('status', 1);
                action_log('goods_enable', 'goods', 0, UID, '批量启用商品ID:' . $ids);
                break;
            case 'delete': // 删除
                if (count($ids) < 1) {
                    $this->error(lang('商品有参与活动，不可删除'));
                }
                $result = Goods::where('id', 'IN', $ids)->setField('is_delete', 1);
                Db::name('goods_cart')->where([['goods_id', 'in', $ids]])->delete();
                Db::name('goods_sku')->where([['goods_id', 'in', $ids]])->delete();
                Db::name('goods_wholesale')->where([['goods_id', 'in', $ids]])->delete();
                action_log('goods_delete', 'goods', 0, UID, '批量删除商品ID:' . $ids);
                break;
            case 'on': // 上架
                $result = Goods::where('id', 'IN', $ids)->setField('is_sale', 1);
                action_log('goods_on', 'goods', 0, UID, '批量上架商品ID:' . $ids);
                break;
            case 'off': // 下架
                if (count($ids) < 1) {
                    $this->error(lang('商品有参与活动，不可下架'));
                }
                $result = Goods::where('id', 'IN', $ids)->setField('is_sale', 0);
                Db::name('goods_cart')->where([['goods_id', 'in', $ids]])->delete();
                $result = action_log('goods_off', 'goods', 0, UID, '批量下架商品ID:' . $ids);
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // \Cache::clear();

            // 记录行为
            action_log('admin_user_' . $type, 'user', $ids, UID, 'ID：' . implode('、', $ids));
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }

    /**
     * 过滤参与活动的商品
     */
    public function filterActivityGoods($goods_ids)
    {
        if (count($goods_ids)) {
            foreach ($goods_ids as $k => $gid) {
                //判断是否参与活动，如果是，过滤
                if (ActivityDetailsModel::isActivityGoods($gid)) {
                    unset($goods_ids[$k]);
                }
            }
        }
        return $goods_ids;
    }

    public function set_ads_url()
    {
        $ids = input('param.ids');
        $good_info = Goods::where(['is_sale' => 1, 'status' => 1, 'is_delete' => 0, 'id' => $ids])->find();
        if (!$good_info) {
            $this->error(lang('商品信息不存在'));
        }
        if ($good_info['is_spec'] == 1) {
            $sku_info = Db::name('goods_sku')->where(['goods_id' => $ids, 'status' => 1])->select();
        } else {
            $sku_info = [];
        }
        $str_url = '/pagesD/simple/goodsDetail?id=' . $ids;
        $this->assign('str_url', $str_url);
        $this->assign('sku_info', $sku_info);
        $this->assign('activity_info', []);
        return $this->fetch();
    }

    /**
     * 保存搜索记录
     * @return string
     */
    public function saveSearchKey()
    {
        $data['search_model_value'] = [
            [
                'name' => lang('字段名称'),
                'expression' => lang('表达式'),
                'value' => lang('值'),
            ],
            [
                'name' => 2,
                'expression' => 2,
                'value' => 2,
            ],
        ];
        $data['search_model_name'] = lang('场景名字');
        $data['uid'] = UID;
//        $data = $this->request()->post();
        if (!$data) {
            return json_encode(['code' => 0, 'data' => [], 'msg' => lang('参数错误')]);
        }
        $res = Db::name("search_sence")->insertGetId($data);
        if (!$res) {
            return json_encode(['code' => 0, 'data' => [], 'msg' => lang('保存失败')]);
        }
        return json_encode(['code' => 1, 'data' => $data, 'msg' => lang('成功')]);
    }

    /**
     * 获取当前操作人的搜索列表
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSearchKey()
    {
        $data = $this->request()->post();
        if (!$data) {
            return json_encode(['code' => 0, 'data' => [], 'msg' => lang('参数错误')]);
        }
        $where[] = ["uid", "=", UID];
        if (isset($data['id']) && $data['id']) {
            $where[] = ["id", "=", $data['id']];
        }
        $res = Db::name("search_sence")->where($where)->select();
        return json_encode(['code' => 1, 'data' => $res, 'msg' => lang('获取成功')]);
    }


    public function goods_stock()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $param = input("param.");
        $order_sn = $param['order_sn'];
        if ($order_sn) {
            $map[] = ["gsl.order_sn", '=', $order_sn];
        }
        if ($param['goods_sn']) {
            $map[] = ["gsl.goods_sn", '=', $param['goods_sn']];
        }
        if ($param['type'] != 'all' && $param['type'] !== '' && $param['type'] !== null) {
            $map[] = ["gsl.type", '=', $param['type']];
        }
        if ($param['create_time']) {
            $create_time = explode(' - ', $param['create_time']);
            $start_time = strtotime($create_time[0] . ' 00:00:00');
            $end_time = strtotime($create_time[1] . ' 23:59:59');
            $map[] = ["gsl.create_time", 'between', [$start_time, $end_time]];
        }

        if ($param['goods_name']) {
            $map[] = ["g.name", 'like', '%' . $param['goods_name'] . '%'];
        }

        //导出excel
        if (isset($param['is_import'])) {
            if (isset($param['ids']) && !empty($param['ids'])) {
                $ids_arr = explode(',', $param['ids']);
                $map[] = ['gsl.id', 'in', $ids_arr];
            }
            // 数据列表
            $list = GoodsStockLog::alias('gsl')
                ->join("goods g", "g.id = gsl.goods_id", "left")
                ->field("gsl.id,g.name,g.brand_id,gsl.order_sn,gsl.stock_change,gsl.type,gsl.operator,gsl.create_time,gsl.remark,g.is_spec,gsl.sku_id")
                ->where($map)
                ->order('gsl.id desc')
                ->select()
                ->each(
                    function ($item) {
                        if ($item['is_spec'] == 1) {
                            $item['key_name'] = GoodsSku::where("sku_id", $item['sku_id'])->value("key_name");
                        } else {
                            $item['key_name'] = "";
                        }
                        $item['brand_id'] = Db::name("goods_brand")->where("id", $item['brand_id'])->value('name');
                        if ($item['operator'] > 0) {
                            $item['admin_name'] = Db::name("admin")->where("id", $item['operator'])->value('username');
                        } else {
                            $item['admin_name'] = lang('前台会员下单');
                        }
                        if ($item['type'] == 2) {
                            $item['stock_change'] = '-' . $item['stock_change'];
                            $item['type'] = lang('出库');
                        } else {
                            $item['type'] = lang('入库');
                        }
                        unset($item['operator']);
                        unset($item['is_spec']);
                        unset($item['sku_id']);
                        unset($item['id']);
                        return $item;
                    }
                );

            $_excelData[0]['list'] = $list;
            $xlsName = lang('库存变动信息') . '-' . date("Y-m-d H:i:s", time());
            $xlsCell = [
                ['name', lang('昵称')],
                ['brand_id', lang('品牌')],
                ['order_sn', lang('订单号')],
                ['stock_change', lang('库存变动数量')],
                ['type', lang('类型')],
                ['create_time', lang('注册时间')],
                ['remark', lang('备注')],
                ['key_name', lang('规格')],
                ['admin_name', lang('操作人')],
            ];
            $excelData = array_values($_excelData);
            $this->exportExcel($xlsName, $xlsCell, $excelData);
        }
        // 数据列表
        $data_list = GoodsStockLog::alias('gsl')
            ->join("goods g", "g.id = gsl.goods_id", "left")
            ->field("gsl.id,g.name,g.brand_id,gsl.order_sn,gsl.stock_change,
            gsl.type,gsl.operator,gsl.create_time,gsl.remark,g.is_spec,
            gsl.sku_id,gsl.stock_before,gsl.stock_after,gsl.goods_sn")
            ->where($map)
            ->order('gsl.id desc')
            ->paginate()
            ->each(
                function ($item) {
                    if ($item['is_spec'] == 1) {
                        $item['key_name'] = GoodsSku::where("sku_id", $item['sku_id'])->value("key_name");
                    } else {
                        $item['key_name'] = "";
                    }
                    $item['brand_id'] = Db::name("goods_brand")->where("id", $item['brand_id'])->value('name');
                    if ($item['operator'] > 0) {
                        $item['admin_name'] = Db::name("admin")->where("id", $item['operator'])->value('username');
                    } else {
                        $item['admin_name'] = lang('前台会员下单');
                    }
                    if ($item['type'] == 2) {
                        $item['stock_change'] = '-' . $item['stock_change'];
                    } elseif ($item['type'] == 1) {
                        $item['stock_change'] = '+' . $item['stock_change'];
                    }
                    return $item;
                }
            );


        $fields = [
            ['id', 'ID'],
            ['name', lang('商品名称')],
            ['goods_sn', lang('货号')],
            ['brand_id', lang('品牌')],
            ['key_name', lang('商品属性')],
            ['order_sn', lang('订单号')],
            ['stock_before', lang('库存变化前')],
            ['stock_change', lang('库存变化')],
            ['stock_after', lang('库存变化后')],
            ['remark', lang('备注')],
            ['type', lang('类型'), 'status', '', [1 => lang('入库'), 2 => lang('出库')]],
            ['admin_name', lang('操作人')],
            ['create_time', lang('操作时间')],
        ];
        $searchFields = [
            ['goods_name', lang('商品名'), 'text'],
            ['order_sn', lang('订单号'), 'text'],
            ['goods_sn', lang('货号'), 'text'],
            ['type', lang('状态'), 'select', '', ['all' => lang('全部'), 1 => lang('入库'), 2 => lang('出库')]],
            ['create_time', lang('操作时间'), 'daterange'],
        ];
        $this->assign('excel_show', 1);
        return Format::ins() //实例化
        ->addColumns($fields)//设置字段
        ->setTopSearch($searchFields)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /***
     *残次品库
     */
    public function gooods_exefective()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $param = input("param.");
        $order_sn = $param['order_sn'];
        if ($order_sn) {
            $map[] = ["order_sn", '=', $order_sn];
        }
        if ($param['goods_sn']) {
            $map[] = ["goods_sn", '=', $param['goods_sn']];
        }
        if ($param['express_no']) {
            $map[] = ["express_no", '=', $param['express_no']];
        }
        if ($param['status'] != 'all' && $param['status'] !== '' && $param['status'] !== null) {
            $map[] = ["status", '=', $param['status']];
        }
        if ($param['create_time']) {
            $create_time = explode(' - ', $param['create_time']);
            $start_time = strtotime($create_time[0] . ' 00:00:00');
            $end_time = strtotime($create_time[1] . ' 23:59:59');
            $map[] = ["create_time", 'between', [$start_time, $end_time]];
        }

        // 数据列表
        $data_list = Db::name("goods_defective")->where($map)
            ->order('id desc')
            ->paginate();
        $fields = [
            ['id', 'ID'],
            ['goods_name', lang('商品名称')],
            ['goods_sn', lang('货号')],
            ['express_no', lang('发货单号')],
            ['sku_name', lang('商品属性')],
            ['order_sn', lang('订单号')],
            ['remark', lang('备注')],
            ['status', lang('是否售卖'), 'status', '', ['all' => lang('全部'), 0 => lang('否'), 1 => lang('是')]],
            ['admin_name', lang('操作人')],
            ['create_time', lang('操作时间'), 'callback', function ($value, $format) {
                return format_time($value, $format); // $format 在这里的值是“Y-m”
            }, 'Y-m-d H:i:s'],
        ];
        $searchFields = [
            ['order_sn', lang('订单号'), 'text'],
            ['goods_sn', lang('货号'), 'text'],
            ['express_no', lang('发货单号'), 'text'],
            ['type', lang('是否售卖'), 'select', '', ['all' => lang('全部'), 0 => lang('否'), 1 => lang('是')]],
            ['create_time', lang('操作时间'), 'daterange'],
        ];
        return Format::ins() //实例化
        ->addColumns($fields)//设置字段
        ->setTopSearch($searchFields)
            ->setTopButtons([
                ['ident' => 'gooods_exefective_delete', 'title' => lang('批量删除'), 'href' => 'gooods_exefective_delete', 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-sm mr5 btn-default  ajax-post confirm', 'extra' => 'target-form="ids"'],
                ['ident' => 'defective_to_sale', 'title' => lang('批量售卖'), 'href' => 'defective_to_sale', 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-sm mr5 btn-primary ajax-post confirm ', 'extra' => 'target-form="ids"']])
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /***
     * 残次品售卖
     */
    public function defective_to_sale()
    {
        $ids = input('param.ids');
        if (!$ids) {
            $this->error(lang('请选择操作项'));
        }
        $where = [];
        $ids = implode(',', $ids);
        $where[] = ['id', 'in', $ids];
        $where[] = ['status', '=', 0];

        $data = Db::name('goods_defective')->where($where)->select();

        Db::startTrans();
        try {
            if (count($data) > 0) {
                foreach ($data as $key => $val) {
                    $update_data = [];
                    if ($val['sku_id']) {
                        $sku_info = GoodsSku::get($val['sku_id']);
                        $gsr = GoodsSku::where('sku_id', $val['sku_id'])->setInc('stock', $val['num']);
                        if (!$gsr) {
                            exception(lang('更改规格库存失败'));
                        }
                        $stock_before = $sku_info['stock'];
                        $stock_after = $stock_before + $val['num'];
                        $goods_id = $sku_info['goods_id'];
                        $sku_id = $val['sku_id'];
                        $goods_sn = $sku_info['sku_sn'];
                    } else {
                        $goods_info = GoodsSku::get($val['goods_id']);
                        $gr = Goods::where('id', $val['goods_id'])->setInc('stock', $val['num']);
                        if (!$gr) {
                            exception(lang('更改商品库存失败'));
                        }
                        $stock_before = $goods_info['stock'];
                        $stock_after = $stock_before + $val['num'];
                        $goods_id = $goods_info['goods_id'];
                        $sku_id = 0;
                        $goods_sn = $goods_info['sn'];
                    }
                    GoodsStockLog::AddStockLog(
                        $goods_id,
                        $sku_id,
                        $val['order_sn'],
                        $stock_before,
                        $val['num'],
                        $stock_after,
                        1,
                        UID,
                        lang('管理员操作残次品入库'),
                        $goods_sn
                    );
                    $update_data = [
                        'status' => 1,
                        'update_time' => time(),
                    ];
                    $res = Db::name('goods_defective')->where('id', $val['id'])->update($update_data);
                    if (!$res) {
                        exception(lang('更改次品记录状态失败'));
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success(lang('操作成功'));
    }

    /**
     * 批量删除
     * @param $ids
     */
    public function gooods_exefective_delete($ids)
    {
        Db::startTrans();
        try {
            foreach ($ids as $k => $v) {
                Db::name('goods_defective')->where(['id' => $v])->delete();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(lang('删除失败'));
        }
        $this->success(lang('删除成功'));
    }
}
