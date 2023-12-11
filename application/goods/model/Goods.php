<?php
// +----------------------------------------------------------------------
// | LwwanPHP
// +----------------------------------------------------------------------
// | 版权所有 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 http://www.sitejs.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------

namespace app\goods\model;

use think\Model as ThinkModel;
use think\Db;
use app\goods\model\ActivityDetails as ActivityDetailsModel;

/**
 * 单页模型
 * @package app\user\model
 */
class Goods extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取商品详情
     * @param $id
     * @param $map
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function get_goods_info($id, $map = [])
    {
        $data = self::view('goods g', true);
        $data = $data->view('goods_body gb', true, 'g.id=gb.goods_id', 'left')
            ->view('upload u', 'path', 'g.thumb=u.id', 'left')
            ->where('g.id', $id)->where($map)->find();
        return $data;
    }

    /**
     * 新增或编辑商品
     * @param $data
     * @return bool
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function save_update($data)
    {
        $spec_data = $data['item'];
        $item_img = $data['item_img'];
        $attr = $data['attr'];
        unset($data['item'], $data['item_img'], $data['attr']);

        $stock_li = [];
        if ($data['is_spec']) {
            foreach ($spec_data as $y) {
                if ($y['status'] == '0') {
                    $stock_li[] = $y['stock'];
                }
            }
        }
        if (!empty($stock_li)) {
            //总库存
            $stock = array_sum($stock_li);
            $data['stock'] = $stock;
        }
        if ($data['is_spec']) {
            if (!$spec_data) {
                $this->error = lang('未设置规格数据');
                return false;
            }
            //$spec_first_data = array_values(array_slice($spec_data,0,1));
            //$data['shop_price'] = $spec_first_data[0]['shop_price'];
            //$data['member_price'] = $spec_first_data[0]['member_price'];
            $isActGoods = false;
            if (isset($data['id'])) {
                $isActGoods = ActivityDetailsModel::isActivityGoods($data['id']);
            }
            //修改商品的sku时检查商品是否已经添加活动，如果商品是活动商品,不允许修改商品的sku
            if ($isActGoods) {
                //获取编辑前的规格数据
                $goods_spec = Goods::getGoodsSpecEdit($data['id'], $data['spectypeid'], $data['cid']);
                //比较是否做了修改，是的话提示
                $isDiff = $this->checkDiff($goods_spec['items'], $spec_data);
                if ($isDiff) {
                    $this->error = lang('该商品正参与活动,不允许修改规格');
                    return false;
                }
            }
        }

        self::startTrans();
        try {
            $save_goods_label = $data['goods_label'];

            //保存主表信息
            if (isset($data['id']) && $data['id'] > 0) {
                if ($data['is_wholesale'] == 1) {
                    if ($data['is_spec'] == 0) {
                        $wholesale = json_decode($data['wholesale'], true);
                        Db::name('goods_wholesale')->where(['goods_id' => $data['id']])->delete();
                        foreach ($wholesale as &$value) {
                            $item_data = [];
                            $item_data['trade_price'] = $value['trade_price'];
                            $item_data['start_batch'] = $value['start_batch'];
                            $item_data['sku_id'] = 0;
                            $item_data['goods_id'] = $data['id'];
                            Db::name('goods_wholesale')->insert($item_data);
                        }
                        unset($data['wholesale']);
                    }
                }

                $res = self::update($data);
                $res1 = Db::name('goods_body')->where('goods_id', $data['id'])->update(['description' => $data['description'], 'body' => $data['body'], 'mbody' => $data['mbody'], 'update_time' => time()]);
                if (!$res || !$res1) {
                    exception(lang('保存商品失败'));
                }
                //编辑商品标签
                if ($save_goods_label) {
                    if (strpos($save_goods_label, ',')) {
                        $goods_label = array_unique(explode(',', $save_goods_label));
                    } else {
                        $goods_label[] = $save_goods_label;
                    }
                    $old_goods_label = Db::name("goods_label")->where("goods_id", $data['id'])->column("name");
                    //比较数组差异--删除操作
                    $del_goods_label = array_diff($old_goods_label, $goods_label);
                    //比较数组差异--添加操作
                    $add_goods_label = array_diff($goods_label, $old_goods_label);
                    if (count($del_goods_label) > 0) {
                        $del_where[] = ["goods_id", "=", $data['id']];
                        $del_where[] = ["name", "in", $del_goods_label];
                        Db::name("goods_label")->where($del_where)->delete();
                    }
                    if (count($add_goods_label) > 0) {
                        $save_data = [];
                        foreach ($add_goods_label as $key => $value) {
                            $save_data[] = [
                                "goods_id" => $data['id'],
                                "name" => $value
                            ];
                        }
                        Db::name("goods_label")->insertAll($save_data);
                    }
                }
            } else {
                if (!empty($data['wholesale'])) {
                    $wholesale = json_decode($data['wholesale'], true);
                    unset($data['wholesale']);
                }
                $res = self::create($data);
                if (!$res) {
                    exception(lang('新增商品失败'));
                }
                $data['id'] = $res->getLastInsID();
                if (count($wholesale) > 0) {
                    foreach ($wholesale as &$value) {
                        $item_data = [];
                        $item_data['trade_price'] = $value['trade_price'];
                        $item_data['start_batch'] = $value['start_batch'];
                        $item_data['sku_id'] = 0;
                        $item_data['goods_id'] = $data['id'];
                        Db::name('goods_wholesale')->insert($item_data);
                    }
                }
                //添加商品标签
                if ($save_goods_label) {
                    if (strpos($save_goods_label, ',')) {
                        $goods_label = array_unique(explode(',', $save_goods_label));
                        $save_data = [];
                        foreach ($goods_label as $key => $value) {
                            $save_data[] = [
                                "goods_id" => $data['id'],
                                "name" => $value
                            ];
                        }
                        Db::name("goods_label")->insertAll($save_data);
                    } else {
                        Db::name("goods_label")->insert(["goods_id" => $data['id'], "name" => $save_goods_label]);
                    }
                }
                $res1 = Db::name('goods_body')->insert(['goods_id' => $data['id'], 'description' => $data['description'], 'body' => $data['body'], 'mbody' => $data['mbody'], 'update_time' => time()]);
                if (!$res1) {
                    exception(lang('新增商品详情失败'));
                }
            }

            //如果没有规格和属性，就提交返回了
            if ($data['is_spec'] == 0) {
                // 提交事务
                self::commit();
                return ['goods_id' => $data['id'], 'info' => 1];
            }
            //设置所有状态都为失效
            GoodsSku::where(['goods_id' => $data['id']])->update(['status' => 0]);
            $sn = 1;
            //保存SKU
            foreach ($spec_data as $key => $sd) {
                $sd['update_time'] = time();
                if (isset($sd['sku_id']) && $sd['sku_id'] > 0) {
                    if (!empty($sd['wholesale'])) {
                        $wholesale = json_decode($sd['wholesale'], true);
                    }

                    unset($sd['wholesale']);
                    $res = GoodsSku::where(['sku_id' => $sd['sku_id'], 'key' => $key])->update($sd);
                    Db::name('goods_wholesale')->where(['goods_id' => $data['id'], 'sku_id' => $sd['sku_id']])->delete();
                    foreach ($wholesale as &$v) {
                        $res1 = Db::name('goods_wholesale')->insert(['goods_id' => $data['id'], 'sku_id' => $sd['sku_id'], 'trade_price' => $v['trade_price'], 'start_batch' => $v['start_batch']]);
                    }
                } else {
                    $sd['goods_id'] = $data['id'];
                    $sd['key'] = $key;
                    if (empty($sd['sku_sn'])) {
                        $sd['sku_sn'] = $data['sn'] . '-' . $sn;
                        $sn++;
                    } else {
                        $getId = GoodsSku::where(['sku_sn' => $sd['sku_sn']])->value("sku_id");
                        if ($getId) {
                            exception(lang('规格货号已存在'));
                        }
                    }
                    if (!empty($sd['wholesale'])) {
                        $wholesale = json_decode($sd['wholesale'], true);
                    }
                    unset($sd['wholesale']);
                    $res = GoodsSku::insertGetId($sd);
                    foreach ($wholesale as &$value) {
                        $item_data = [];
                        $item_data['trade_price'] = $value['trade_price'];
                        $item_data['start_batch'] = $value['start_batch'];
                        $item_data['sku_id'] = $res;
                        $item_data['goods_id'] = $sd['goods_id'];
                        $res1 = Db::name('goods_wholesale')->insert($item_data);
                    }
                    if (!$res && !$res1) {
                        exception('新增商品SKU失败');
                    }
                }
            }

            if ($item_img) {
                //保存规格图片
                foreach ($item_img as $k => $items) {
                    if (isset($items['id'])) {
                        if (GoodsTypeSpecImg::where(['goods_id' => $data['id'], 'spec_image_id' => $k])->count()) {
                            $res = GoodsTypeSpecImg::where(['goods_id' => $data['id'], 'spec_image_id' => $k])->update(['update_time' => time(), 'thumb' => $items['thumb']]);
                        } else {
                            $res = GoodsTypeSpecImg::insert(['goods_id' => $data['id'], 'spec_image_id' => $k, 'update_time' => time(), 'thumb' => $items['thumb']]);
                        }

                        if (!$res) {
                            exception(lang('保存规格图片失败'));
                        }
                    } else {
                        $img[$k]['goods_id'] = $data['id'];
                        $img[$k]['spec_image_id'] = $k;
                        $img[$k]['thumb'] = $items;
                    }
                }

                if ($img) {
                    $res = GoodsTypeSpecImg::insertAll($img);
                    if (!$res) {
                        exception(lang('规格图片插入失败'));
                    }
                }
            }

            //保存或新增属性
            foreach ($attr as $t => $a) {
                if (GoodsTypeAttr::where(['attr_id' => $t, 'goods_id' => $data['id']])->count()) {
                    GoodsTypeAttr::where(['attr_id' => $t, 'goods_id' => $data['id']])->update(['value' => $a]);
                } else {
                    GoodsTypeAttr::insert(['attr_id' => $t, 'goods_id' => $data['id'], 'value' => $a]);
                }
            }

            // 提交事务
            self::commit();
        } catch (\Exception $e) {
            // 回滚事务
            self::rollback();
            $this->error = $e->getMessage();
            return false;
        }
        return ['goods_id' => $data['id'], 'info' => 1];
    }

    /**
     * 获取对应分类的规格
     * @param $cid 分类id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function getGoodsSpec($cid, $aid = 0)
    {
        if ($cid && $aid == 0) {
            //获取分类对应的类型
            $typeid = Category::where('id', $cid)->value('typeid');
        } else {
            $typeid = $aid;
        }

        //获取类型的规格
        $specList = GoodsTypeSpec::where("typeid", $typeid)->order('sort asc')->column('id,typeid,name,is_upload_image');
        foreach ($specList as $k => $v) {
            $specList[$k]['spec_item'] = GoodsTypeSpecItem::where("specid = " . $v['id'])->order('id')->column('id,specid,item'); // 获取规格项
            foreach ($specList[$k]['spec_item'] as &$spec_item) {
                //默认不选中，前端使用
                $spec_item['active'] = 1;
            }
        }

        return ['typeid' => $typeid, 'specList' => $specList];
    }

    /**
     * 获取商品对应分类的规格以及商品sku
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function getGoodsSpecEdit($goods_id, $spectypeid, $cid)
    {
        if ($cid && !$spectypeid) {
            //获取分类对应的类型
            $typeid = Category::where('id', $cid)->value('typeid');
        } else {
            $typeid = $spectypeid;
        }

        //获取类型的规格
        $specList = GoodsTypeSpec::where("typeid", $typeid)->order('sort asc')->column('id,typeid,name,is_upload_image');

        foreach ($specList as $k => $v) {
            $specList[$k]['spec_item'] = GoodsTypeSpecItem::where("specid = " . $v['id'])->order('id')->column('id,specid,item'); // 获取规格项

            foreach ($specList[$k]['spec_item'] as $s => &$spec_item) {
                if ($v['is_upload_image'] == 1) {
                    $imglist[$s]['path'] = '';
                }

                $specList[$k]['spec_item'][$s]['active'] = 1;
            }
        }

        // SKU列表
        $items = GoodsSku::where('goods_id', $goods_id)->order('key asc')->column("key,sku_id,shop_price,cost_price,market_price,member_price,stock,status,commission,sku_weight,key_name,sku_sn,stock_warning");

        // 获取商品规格图片
        if ($goods_id) {
            $specImageList = Db::name('goods_type_spec_image')->alias('sp')->join('upload u', 'sp.thumb = u.id', 'left')->where("sp.goods_id", $goods_id)->column('sp.spec_image_id,sp.thumb,sp.id,u.path');
            if (!$specImageList) {
                $specImageList = $imglist;
            } else {
                $diffarr = array_diff_key($imglist, $specImageList);
                if ($diffarr) {
                    $specImageList = $specImageList + $diffarr;
                }
            }
        }
        $is_wholesale = Db::name('goods')->where(['id' => $goods_id])->value('is_wholesale');
        if ($is_wholesale == 1) {
            foreach ($items as &$value) {
                $value['wholesale'] = Db::name('goods_wholesale')->where(['goods_id' => $goods_id, 'sku_id' => $value['sku_id']])->field('start_batch,trade_price')->select();
            }
        }
        $data['specs'] = $specList;
        $data['spec_image_list'] = $specImageList;
        $data['items'] = $items;

        return $data;
    }

    /**
     * 获取对应分类的属性
     * @param $cid 分类id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function getGoodsAttr($cid, $aid)
    {
        //获取分类对应的类型
        if ($cid && !$aid) {
            //获取分类对应的类型
            $typeid = Category::where('id', $cid)->value('typeid');
        } else {
            $typeid = $aid;
        }

        //获取类型的规格
        $attrList = GoodsTypeAttribute::where(["typeid" => $typeid, 'is_show' => 1])->order('sort asc')->column('id,name,typeid,value');
        foreach ($attrList as $k => &$v) {
            $v['value'] = explode(',', $v['value']); // 获取规格项
        }

        return $attrList;
    }

    /**
     * 获取对应分类的属性
     * @param $cid 分类id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function getGoodsAttrEdit($goods_id, $cid)
    {
        //获取类型的规格
        $attrList = GoodsTypeAttribute::where(["typeid" => $cid, 'is_show' => 1])->order('sort asc')->column('id,name,typeid,value');
        foreach ($attrList as $k => &$v) {
            $v['change'] = GoodsTypeAttr::where(['attr_id' => $v['id'], 'goods_id' => $goods_id])->value('value');
            $v['value'] = explode(',', $v['value']); // 获取规格项
        }

        return $attrList;
    }

    /**
     * 获得商品列表
     * @param array $where 条件
     * @param int $userId 用户id值
     * @param int $page 当前页
     * @return \think\db\Query
     * @author 风轻云淡
     * @editor 似水星辰 [ 2630481389@qq.com ]
     */
    public function goods_list($where = [], $order = ['id' => 'desc'], $pagesize = '16', $page = 1, $where1 = '')
    {
        $lists = Goods::alias("g")
            //->join("__UPLOAD__ u", "u.id=g.thumb")
            ->where($where)
            ->where($where1)
            ->where([['g.is_delete', '=', 0], ['g.is_sale', '=', 1], ['g.status', '=', 1]])
            ->field("g.stock,g.id,g.keywords,g.name,g.thumb,g.sales_sum,g.shop_price,g.member_price,g.market_price,
            g.is_shipping,g.is_spec,g.is_hot,g.is_new,g.empirical,g.goods_label_service,g.goods_label_activity,g.discounts,g.share_award_money,g.is_wholesale")
            ->order($order)
            ->limit((($page - 1) * $pagesize) . ',' . $pagesize)
            ->select();
        foreach ($lists as $k => $v) {
            $lists[$k]['thumb'] = get_file_url($v['thumb']);
            if ($v['is_spec']) {
                $sku_stock = 0;
                $sku_list = DB::name("goods_sku")->field("sku_id, stock")->where(['goods_id' => $v['id']])->select();
                foreach ($sku_list as $key => $value) {
                    $sku_stock += $value['stock'];
                }
                /*  $sku_where = [];
                    $sku_where[] = ['goods_id','=',$v['id']];
                    $sku_where[] = ['stock','gt',0];
                    $sku_detail = DB::name("goods_sku")->field("shop_price, market_price")->where()->order("sku_id ASC")->find();
                    $lists[$k]['shop_price'] = $sku_detail['shop_price'];
                    $lists[$k]['market_price'] = $sku_detail['market_price'];
                    $lists[$k]['member_price'] = $sku_detail['member_price'];*/
                $lists[$k]['stock'] = $sku_stock;
                $lists[$k]['sku_id'] = $value['sku_id'];
            }
            if ($v['goods_label_service']) {
                $v['goods_label_service'] = trim($v['goods_label_service'], ',');
                $lists[$k]['goods_label_service'] = Db::name("goods_label_service")->where("id in (" . $v['goods_label_service'] . ")")->column("name");
            }
            if ($v['goods_label_activity']) {
                $lists[$k]['goods_label_activity'] = Db::name("goods_label_service")->where("id in (" . $v['goods_label_activity'] . ")")->column("name");
            }
            $lists[$k]['goods_label'] = Db::name("goods_label")->where("goods_id =" . $v['id'])->column("name") ?: '';
        }
        return $lists;
    }

    /**
     * 获取活动商品的规格的价格和库存
     */
    public static function get_activity_goods_sku($goods_id, $activity_id)
    {
        $activity_map[] = ['goods_id', '=', $goods_id];
        $activity_map[] = ['activity_id', '=', $activity_id];
        $activity_map[] = ['status', '=', 1];
        $activity_sku = Db::name('goods_activity_details')->where($activity_map)->select();
        foreach ($activity_sku as $v) {
            $ids[] = $v['sku_id'];
            $activity_price_stock[$v['sku_id']] = ['shop_price' => $v['activity_price'], 'member_activity_price' => $v['member_activity_price'], 'stock' => $v['stock']];
        }
        $sku['ids'] = implode(',', $ids);
        $sku['ps'] = $activity_price_stock;
        return $sku;
    }

    /**
     * 商品详情
     * @param $where
     * @return array|false|null|\PDOStatement|string|ThinkModel
     * @author 风轻云淡
     * @editor 似水星辰 [ 2630481389@qq.com ]
     */
    public function goods_detail($where, $activity_id = 0)
    {
        $goodsInfo = Goods::alias("g")->join("__GOODS_BODY__ gb", "gb.goods_id=g.id")
            /*->join("__UPLOAD__ u", "u.id=g.thumb")*/
            ->where($where)
            ->field("g.id,g.cid,g.adslogan,g.name,g.sales_sum,g.sales_num_new,g.spectypeid,g.click,g.is_recommend,
            g.is_new,g.is_hot,g.shop_price,g.member_price,g.market_price,g.images,g.thumb,gb.body,
            gb.description,g.stock,g.is_spec,g.is_shipping,g.freight_price,g.discounts,g.share_award_money,
            g.goods_label_service,g.goods_label_activity,g.freight_template_id,video,video_img,is_wholesale")
            ->find();
        if ($goodsInfo) {
            //销量=真实销量+虚拟销量
//            $goodsInfo["sales_sum"] = $goodsInfo["sales_sum"];
            if ($goodsInfo['images']) {
                $imgList = get_files_url($goodsInfo['images']);
            }
            $goodsInfo['thumb'] = get_file_url($goodsInfo['thumb']);

            //2021-06-08修改wph
            if ($goodsInfo['video']) {
                $goodsInfo['video'] = get_file_url($goodsInfo['video']);
            } else {
                $goodsInfo['video'] = '';
            }
            if ($goodsInfo['video_img']) {
                $goodsInfo['video_img'] = get_file_url($goodsInfo['video_img']);
            } else {
                $goodsInfo['video_img'] = '';
            }

            $goodsInfo['images'] = $imgList ? $imgList : [];
            $goodsInfo['goods_label_service'] = trim($goodsInfo['goods_label_service'], ',');
            if ($goodsInfo['goods_label_service']) {
                $goodsInfo['goods_label_service'] = Db::name("goods_label_service")->where("id in (" . $goodsInfo['goods_label_service'] . ")")->column("name");;
            }
            if ($goodsInfo['goods_label_activity']) {
                $goodsInfo['goods_label_activity'] = Db::name("goods_label_service")->where("id in (" . $goodsInfo['goods_label_activity'] . ")")->column("name");;
            }
            $goodsInfo['goods_label'] = Db::name("goods_label")->where("goods_id =" . $goodsInfo['id'])->column("name");
            //查看商品规格
            $goodsInfo['spec_list'] = [];
            if ($goodsInfo['is_spec'] == 1) {
                //查询商品对应规格
                $map[] = ['goods_id', '=', $goodsInfo['id']];
                $map[] = ['status', '=', 1];
                //获取原价商品规格
                $goodsSpec = GoodsSku::where($map)->field("sku_id, key, key_name, member_price, shop_price,market_price,member_price,stock")->select();
                $goodsSpecIds = GoodsSku::where($map)->column('key');
                $sku_spec = self::sku_spec($goodsInfo, $goodsSpec, $goodsSpecIds);

                if (isset($sku_spec['spec_list'])) {
                    $sku_spec['spec_list'] = $sku_spec['spec_list']->toArray();

                    foreach ($sku_spec['spec_list'] as $k => $v) {
                        if (empty($v['spec_value'])) {
                            unset($sku_spec['spec_list'][$k]);
                        }
                    }
                }
                $goodsInfo['spec_list'] = $sku_spec['spec_list'];
                $goodsInfo['sku_list'] = $sku_spec['sku_'];
                //获取活动商品规格
                if ($activity_id) {
                    $sku = self::get_activity_goods_sku($goodsInfo['id'], $activity_id);
                    $activity_stock = Db::name('goods_activity_details')->where(['goods_id' => $goodsInfo['id'], 'status' => 1, 'activity_id' => $activity_id])->sum('stock');
                    $activity_sales_sum = Db::name('goods_activity_details')->where(['goods_id' => $goodsInfo['id'], 'status' => 1, 'activity_id' => $activity_id])->sum('sales_sum');
                    foreach ($map as $k => $m) {
                        $map[$k][0] = 'gad.' . $m[0];
                    }
                    $goodsSpec = GoodsSku::where($map)->alias('gs')->join('goods_activity_details gad', 'gs.sku_id=gad.sku_id', 'left')
                        ->field("gs.sku_id, gs.key, gs.key_name, gad.activity_price,gad.member_activity_price,gad.deposit,gs.member_price,gs.shop_price,gs.market_price,gad.stock,gad.limit,gad.sales_integral,gad.is_pure_integral")->where(['gad.activity_id' => $activity_id, 'gs.status' => 1])->select();
                    foreach ($goodsSpec as $key => $value) {
                        $goodsSpec[$key]['balance'] = bcsub($value['activity_price'], $value['deposit'], 2);
                    }
                    $goodsSpecIds = GoodsSku::where($map)->alias('gs')->join('goods_activity_details gad', 'gs.sku_id=gad.sku_id', 'left')->column('gs.key');
                    $sku_spec_act = self::sku_spec($goodsInfo, $goodsSpec, $goodsSpecIds);
                    $goodsInfo['activity_spec_list'] = $sku_spec_act['spec_list'];
                    $goodsInfo['activity_sku_list'] = $sku_spec_act['sku_'];
                }
                //商品属性获得
                $typeAttribute = GoodsTypeAttribute::where(['typeid' => $goodsInfo['spectypeid'], 'is_show' => 1])->order("sort asc")->field("id,name")->select();
                $goodsInfo['attr_value'] = [];
                if ($typeAttribute) {
                    foreach ($typeAttribute as $key => $value) {
                        $attValue = GoodsTypeAttr::where(['goods_id' => $goodsInfo['id'], 'attr_id' => $value['id']])->value("value");
                        $typeAttribute[$key]['attr_value'] = $attValue;
                    }
                    $goodsInfo['attr_value'] = $typeAttribute;
                }
                $stock = GoodsSku::where(['goods_id' => $goodsInfo['id'], 'status' => 1])->sum('stock');
                $goodsInfo['stock'] = $activity_id ? $activity_stock : $stock;
                $goodsInfo['sales_sum'] = $activity_id ? $activity_sales_sum : $goodsInfo['sales_sum'];

                //如果启用的规格列表为空，库存取所有规格库存之和
                if (empty($goodsInfo['sku_list'])) {
                    $goodsInfo['is_spec'] = 0;
                    $goodsInfo['stock'] = GoodsSku::where(['goods_id' => $goodsInfo['id']])->sum('stock');
                }

            } else {
                if ($activity_id) {
                    $activity_map[] = ['activity_id', '=', $activity_id];
                    $activity_map[] = ['goods_id', '=', $goodsInfo['id']];
                    $goods_activity = Db::name('goods_activity_details')->where($activity_map)->find();
                    $goodsInfo['member_activity_price'] = $goods_activity['member_activity_price'];
                    $goodsInfo['activity_price'] = $goods_activity['activity_price'];
                    $goodsInfo['activity_stock'] = $goods_activity['stock'];
                }
                if ($goodsInfo['is_wholesale'] == 1) {
                    $goodsInfo['batch_data'] = Db::name('goods_wholesale')->where(['goods_id' => $goodsInfo['id']])->field('start_batch,trade_price')->order('start_batch ASC')->select();
                }
                $goodsInfo['attr_value'] = [];
                $goodsInfo['spec_list'] = [];
            }
        }
        return $goodsInfo ? $goodsInfo : [];
    }

    public static function sku_spec($goodsInfo, $goodsSpec, $goodsSpecIds)
    {
        $specValue = [];
        if ($goodsSpec) {
            $specValueStr = implode("_", $goodsSpecIds);
            $specValue = array_unique(explode("_", trim($specValueStr, "_")));
            $goodsSpecValue = [];
            foreach ($goodsSpec as $key => $value) {
                $goodsSpecValue[$value['key']] = $value;
            }
        }
        if ($goodsInfo['is_wholesale'] == 1) {
            foreach ($goodsSpecValue as &$v) {
                $v['batch_data'] = Db::name('goods_wholesale')->where(['goods_id' => $goodsInfo['id'], 'sku_id' => $v['sku_id']])->field('start_batch,trade_price')->order('start_batch ASC')->select();
            }
        }
        $condition[] = ['typeid', '=', $goodsInfo['spectypeid']];
        $sku_spec['spec_list'] = GoodsTypeSpec::get_spec($condition, $specValue, $goodsInfo['id']);
        $sku_spec['sku_'] = $goodsSpecValue;
        return $sku_spec;
    }

    /**
     * 商品评论
     * @param $where
     * @param int $userId
     * @return array|false|null|\PDOStatement|string|ThinkModel
     * @author zlf
     */
    public static function goods_comment($goodsId)
    {
        $comment = Db::name('goods_comment')->alias('c')
            ->join('user u', 'c.user_id = u.id')
            ->field('c.id,c.thumb,c.create_time,c.content,c.star,u.head_img,u.user_nickname,c.sku_id')
            ->where(['c.goods_id' => $goodsId, 'c.status' => 1])
            ->order('c.create_time desc')
            ->paginate()->each(function ($item) {
                $item['thumb'] = get_file_url($item['thumb']);
                $item['head_img'] = get_file_url($item['head_img']);
                $item['create_time'] = date('Y-m-d', $item['create_time']);
                if ($item['sku_id']) {
                    $item['key_name'] = Db::name('goods_sku')->where('sku_id', $item['sku_id'])->value('key_name');
                }
                return $item;
            });

        return $comment ? $comment : [];
    }

    /**
     * 获取推荐商品
     */
    public function goods_index($where, $page)
    {
        $recommend = \think\Db::name('goods')
            ->where($where)
            ->field("id,name,thumb,sales_sum,shop_price")
            ->page($page, 4)
            ->select();
        if ($recommend) {
            foreach ($recommend as $k => $v) {
                $recommend[$k]['thumb'] = get_files_url($v['thumb']);
            };
        } else {
            $recommend = [];
        }
        return $recommend;
    }

    /**
     * 获取推荐商品
     */
    public function goods_search($where, $page, $limit)
    {
        $recommend = Db::name('goods')
            ->where($where)
            ->field("id,cid,name,thumb,sales_sum,shop_price")
            ->page($page, $limit)
            ->select();
        /*           var_dump(Db::name('goods')->getLastSql());*/
        if ($recommend) {
            foreach ($recommend as $k => $v) {
                $recommend[$k]['thumb'] = get_files_url($v['thumb']);
            };
        }

        return $recommend ? $recommend : [];
    }

    /**
     * 查询商品数据
     * @param $where str 查询条件
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月21日16:46:51
     */
    public static function getGoodsList($where = '')
    {
        return self::field("
            GROUP_CONCAT(cid) cids, 
            GROUP_CONCAT(brand_id) brand_ids, 
            GROUP_CONCAT(goods_label_service) goods_label_services
        ")->where($where)
            ->find();
    }

    /**
     * 查询商品数据条数
     * @param $where str 查询条件
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月21日16:51:06
     */
    public static function getGoodsListCount($where = '')
    {
        return self::alias('g')->where($where)->count();
    }


    /**
     * 比较两个二维数组，新数组的值在老数组是否存在且相等
     * 用于判断是否修改了规格数据，数组内元素顺序是打乱的
     */
    public function checkDiff($olddata, $newdata)
    {
        $diff = false;
        foreach ($newdata as $k => $v) {
            if (!isset($olddata[$k])) {
                $diff = true;
            }
            foreach ($v as $vk => $vv) {
                if (!isset($olddata[$k][$vk])) {
                    $diff = true;
                }
                if ($vv != $olddata[$k][$vk]) {
                    $diff = true;
                }
            }
        }
        return $diff;
    }
}
