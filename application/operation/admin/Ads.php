<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\goods\model\Activity;
use app\goods\model\GoodsSku;
use app\operation\model\Ads as AdsModel;
use app\operation\model\AdsType;
use service\Format;
use think\Db;
use service\Tree;
use service\ApiReturn;
use app\goods\model\Goods as GoodsModel;
use app\goods\model\Category;
use app\operation\model\Article;
use app\goods\model\ActivityDetails;

/**
 * 广告控制器
 * @package app\operation\admin
 */
class Ads extends Base
{

    /**
     * 广告列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        if (isset($map['name'])) {
            $map[] = ['am.name', 'like', '%' . $map['name'] . '%'];
            unset($map['name']);
        }
        if (isset($map['typeid'])) {
            if ($map['typeid'] != -1) {
                $map[] = ['am.typeid', '=', $map['typeid']];
            }
            unset($map['typeid']);
        }
        if (isset($map['status'])) {
            if ($map['status'] != -1) {
                $map[] = ['am.status', '=', $map['status']];
            }
            unset($map['status']);
        }
        // 排序
        $order = $this->getOrder('sort asc,id desc');
        // 数据列表
        $data_list = AdsModel::alias('am')->where($map)->order($order)->field('am.*')->join('upload u', 'am.thumb=u.id', 'left')->paginate();

        $list_type = AdsType::where('status', 1)->column('id,name');

        $fields = [
            ['id', 'ID'],
            ['name', lang('广告名称'), 'text'],
            ['typeid', lang('所属广告位'), 'status', '', $list_type],
            ['thumb', lang('图片'), 'picture'],
            ['width', lang('宽'), 'text'],
            ['height', lang('高'), 'text'],
            //['create_time', lang('创建时间'), '','','','text-center'],
            //['update_time', lang('更新时间'), '','','','text-center'],
            ['sort', lang('排序'), 'text.edit', '', '', 'text-center'],
            ['status', lang('状态'), 'status', '', '', 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $list_type[-1] = lang('全部');
        ksort($list_type);
        $search_fields = [
            ['name', lang('广告名称'), 'text'],
            ['typeid', lang('所属广告位'), 'select', '', $list_type],
            ['status', lang('状态'), 'select', '', ['-1' => lang('全部'), '0' => lang('禁用'), '1' => lang('启用')]],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopSearch($search_fields)
            ->setTopButtons($this->top_button)
//            ->setTopButton(['title' => lang('广告位管理'), 'href' => ['operation/AdsType/index'], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary btn-flat'])
            ->setRightButtons($this->right_button)
//            ->bottom_button_select($this->bottom_button_select)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['thumb'] = $data['images'];
            unset($data['images']);
            if ($data['typeid'] == -1) {
                $this->error(lang('请选择广告位'));
            }
            $typeid_count = AdsModel::where('typeid',$data['typeid'])->count();
            if($data['typeid'] == 1){
                if($typeid_count >= 1){
                    $this->error(lang('首页广告位只能添加一个'));
                }
            }

            $data['name'] = $this->trimAll($data['name']);
            if ($data['name'] == '') {
                $this->error(lang('广告名称不能为空'));
            }
            if (mb_strlen($data['name'], 'utf-8') >= 50) {
                $this->error(lang('广告名称不能超过50个字'));
            };


            // 验证
            $result = $this->validate($data, 'Ads.add');
            if (true !== $result) {
                $this->error($result);
            }

            $range_time = explode(' - ', $data['start_time']);

            $data['start_time'] = strtotime($range_time[0]);
            $data['end_time'] = strtotime($range_time[1]);
            $data['rgb'] = json_encode(get_img_rgb(get_files_url($data['thumb'])[0]));
            if ($res = AdsModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('operation_ads_add', 'operation_ads', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $list_type = AdsType::where('status', 1)->column('id,name');
        $list[-1] = [
            'id' => -1,
            'name' => lang('请选择'),
        ];
        foreach ($list_type as $k => $v) {
            $list[] = [
                'id' => $k,
                'name' => $v
            ];
        }
        $this->assign('page_title', lang('新增广告'));
        $this->assign('list_type', $list);
        return $this->fetch();
    }

    /**
     * 编辑
     * @param null $id 广告id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = AdsModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($data['typeid'] == -1) {
                $this->error(lang('请选择广告位'));
            }
            $data['name'] = $this->trimAll($data['name']);
            if ($data['name'] == '') {
                $this->error(lang('广告名称不能为空'));
            }
//            $this->error(lang('该广告位只能为1个'));
//            dump($data['id']);die;
            if($data['typeid'] == 1){
                $uuu = AdsModel::where(['typeid'=>1])->count();
                if($uuu == 1){
                    $uuy = AdsModel::where(['typeid'=>1])->find();
                    if($uuy['id'] != $id){
                        $this->error(lang('该广告位只能为1个'));
                    }
                }
//                if(!$uuu){
//
//                }
            }
            if (mb_strlen($data['name'], 'utf-8') >= 50) {
                $this->error(lang('广告名称不能超过50个字'));
            };
            // 验证
            $result = $this->validate($data, 'Ads.edit');
            if (true !== $result) {
                $this->error($result);
            }
            $data['rgb'] = json_encode(get_img_rgb(get_files_url($data['thumb'])[0]));
            if (AdsModel::update($data)) {
                // 记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('operation_ads_edit', 'operation_ads', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $list_type = AdsType::where('status', 1)->field('id,name')->select();
       
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('广告名称')],
            ['type' => 'select', 'name' => 'typeid', 'title' => lang('所属广告位'), 'extra' => $list_type],
            ['type' => 'image', 'name' => 'thumb', 'title' => lang('图片'), 'tips' => ''],
            //['type' => 'text', 'name' => 'content', 'title' => lang('文字内容'), 'tips' => ''],
            ['type' => 'text', 'name' => 'href', 'title' => lang('链接'), 'tips' => ''],
            //['type' => 'text', 'name' => 'width', 'title' => lang('宽度'), 'tips' => lang('不用填写单位，只需填写具体数字')],
            //['type' => 'text', 'name' => 'height', 'title' => lang('高度'), 'tips' => lang('不用填写单位，只需填写具体数字')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'extra' => [lang('否'), lang('是')]],
        ];
        $this->assign('page_title', lang('编辑广告位'));
        $this->assign('list_type', $list_type);
        $this->assign('info', $info);
        $this->assign('form_items', $this->setData($fields, $info));
//        return $this->fetch('admin@public/edit');
        return $this->fetch();
    }

    /**
     * Notes:广告位点击量统计
     * User: php迷途小书童
     * Date: 2020/8/24
     * Time: 19:41
     * @param int $adsid
     * @return mixed
     */
    public function statistics($adsid = 0)
    {
        $get = $this->request->get();
        $adsid = $get['adsid'] ?: $adsid;
        $page_traffic_time = $get['page_traffic_time'] ?? '-7';
        $page_traffic_input = $get['page_traffic_input'] ?? date('Y-m-d', strtotime('-7 days')) . '~' . $now;
        $now = date('Y-m-d');

        ############页面流量统计 begin #########################
        $pv = [];
        $uv_date = '';
        for ($i = abs($page_traffic_time); $i >= 0; $i--) {
            $date_search = date('Y-m-d', strtotime('-' . $i . 'days'));
            $day = date('m-d', strtotime('-' . $i . 'days'));
            $pvnum = Db::name('operation_ads_pv_data')
                ->where('date', $date_search)
                ->where('adsid', $adsid)
                ->sum('adspv');
            array_push($pv, $pvnum);
            $uv_date .= "'{$day}',";
        }

        $page_traffic_data['pv'] = implode(',', $pv);
        $page_traffic_data['date'] = rtrim($uv_date, ',');
        $this->assign('adsid', $adsid);
        $this->assign('page_traffic_data', $page_traffic_data);
        $this->assign('page_traffic_input', $page_traffic_input);
        ############页面流量统计 end #########################

        ############浏览占比 begin #########################
        $browse_input = $get['browse_input'] ?? date('Y-m-d', strtotime('-7 days')) . '~' . $now;
        $this->assign('browse_input', $browse_input);
        $this->assign('page_traffic_time', $page_traffic_time);

        $ios = Db::name('operation_ads_pv_data')
            ->where('way', 1)
            ->whereTime('date', $browse_input)
            ->sum('adspv');

        $android = Db::name('operation_ads_pv_data')
            ->where('way', 2)
            ->whereTime('date', $browse_input)
            ->sum('adspv');
        $mini = Db::name('operation_ads_pv_data')
            ->where('way', 3)
            ->whereTime('date', $browse_input)
            ->sum('adspv');

        $pc = Db::name('operation_ads_pv_data')
            ->where('way', 4)
            ->whereTime('date', $browse_input)
            ->sum('adspv');

        $browse['ios'] = $ios;
        $browse['android'] = $android;
        $browse['mini'] = $mini;
        $browse['pc'] = $pc;

        $this->assign('browse', $browse);
        ############浏览占比 end ###########################

        return $this->fetch('ads_statistics');
    }

    /**
     * Notes:
     * User: php迷途小书童
     * Date: 2020/8/24
     * Time: 19:54
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function leaderboard()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 数据列表
        $data_list = Db::name('operation_ads_pv_data')
            ->alias('a')
            ->join('operation_ads b', 'b.id = a.adsid')
            ->field('a.sex,a.way,a.typeid,a.area,a.adsid,a.typeid,sum(a.adspv) as pv_total')
            ->field('b.name,b.thumb,b.create_time')
            ->group('adsid')
            ->order('pv_total', 'desc')
            ->select();

        $list_type = AdsType::column('id,name');

        $fields = [
            ['adsid', 'ID'],
            ['name', lang('广告名称'), 'text'],
            ['typeid', lang('所属广告位'), 'status', '', $list_type],
            ['thumb', lang('图片'), 'picture'],
            ['pv_total', 'PV'],
            ['sex', lang('性别'), 'callback', function ($sex) {
                switch ($sex) {
                    case 1:
                        $sex = lang('男');
                        break;
                    case 2:
                        $sex = lang('女');
                        break;
                    default:
                        $sex = lang('未知');
                        break;
                }
                return $sex;
            }, '', 'text-center'],
            ['way', lang('来源'), 'callback', function ($way) {
                switch ($way) {
                    case 1:
                        $way = 'ios';
                        break;
                    case 2:
                        $way = 'android';
                        break;
                    case 3:
                        $way = 'mini';
                        break;
                    case 4:
                        $way = 'pc';
                        break;
                    default:
                        $way = lang('未知');
                        break;
                }
                return $way;
            }, '', 'text-center'],
            ['area', lang('所在地区')],
            ['pv_total', 'PV'],
            ['create_time', lang('创建时间'), 'callback', function ($create_time) {
                return date('Y-m-d H:i:s', $create_time);
            }, '', 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];

        array_push($this->right_button, [
            'ident' => 'statistics',//按钮标识
            'title' => ' 统计', //标题
            'href' => ['statistics', ['adsid' => '__adsid__']],//链接
            'icon' => 'fa fa-industry',
            'class' => 'btn btn-xs mr5 btn-default'
        ]);

        return Format::ins()//实例化
            ->addColumns($fields)//设置字段
            ->setTopButton(['title' => lang('广告管理'), 'href' => ['operation/Ads/index'], 'class' => 'btn btn-sm mr5 btn-success '])
            ->setRightButtons($this->right_button, ['edit', 'enable', 'disable', 'delete'])
            ->setData($data_list)//设置数据
            ->hideCheckbox()
            ->fetch();//显示
    }

    /*
     *广告模块列表
     *
     */
    public function ad_list()
    {
        $data = request()->param();
        $type = isset($data['type']) ? $data['type'] : 'goods';
        $keywords = isset($data['keywords']) ? $data['keywords'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = 20;
        $retust['cate'] = [
            ['name' => lang('商品'), 'type' => 'goods'],
            ['name' => lang('分类'), 'type' => 'category'],
//            ['name' => lang('标签'), 'type' => 'label'],
            ['name' => lang('文章'), 'type' => 'article'],
//            ['name' => lang('软文'), 'type' => 'native_article'],
//            ['name' => lang('页面'), 'type' => 'page'],
//            ['name' => lang('营销'), 'type' => 'market'],
//            ['name' => lang('活动入口'), 'type' => 'active_list'],
//            ['name' => lang('活动商品'), 'type' => 'active_goods'],
//            ['name' => lang('秒杀'), 'type' => 'seckill'],
//            ['name' => lang('自定义页面'), 'type' => 'custom_page'],
//            ['name' => lang('直播'), 'type' => 'live'],
//            ['name' => lang('外链'), 'type' => 'link']
        ];
        switch ($type) {
            case 'goods':
                $where = [];
                $count = GoodsModel::where([['is_delete', '=', 0], ['is_sale', '=', 1], ['status', '=', 1]])->count();
                if (!empty($keywords)) {
                    $where[] = ['g.name', 'like', '%' . $keywords . '%'];
                    $count = GoodsModel::where(" name like '%{$keywords}%' ")->count();
                }
                $list = GoodsModel::goods_list($where, ['id'=>'desc'], $page_size, $page);
                foreach ($list as &$v) {
                    $v['name'] = mb_substr($v['name'], 0, 30);
                    $v['url'] = '/pages/goods/goodsdetail/goods-detail/index?goods_id=' . $v['id'] . '&sku_id=0';
                }
                break;
            case 'category':
                $where = [
                    ['is_show','=', 1],
                    ['status','=', 1]
                ];
                if (!empty($keywords)) {
                    $where[] = ['name', 'like', '%' . $keywords . '%'];
                }
                $list = Category::where($where)->order('id asc')
                        ->column("id,pid,name");
//                foreach ($list as &$v) {
//                    $v['url'] = '/pages/goods/goodslist/goods-search/index?cid=' . $v['id'];
//                }

//            foreach($list as &$value){
//                if($value['pid'] == 0){
//
//                    $children = Category::where(['pid'=>$value['id']])->order('id asc')->column("id,pid,name");
//                    if(!empty($children)){
//                        foreach($children as &$v){
//                            $v['url'] = '/pages/goods/goodslist/goods-search/index?cid=' . $v['id'];
//                        }
//                    }
//                    $value['children'] = $children;
//                    $value['url'] = '/pages/goods/goodslist/goods-search/index?cid=' . $value['id'];
//                }
//            }
                $list = Tree::reSorts($list, 0);
//                foreach ($list as &$v) {
//                    $v['url'] = '/pages/goods/goodslist/goods-search/index?cid=' . $v['id'];
//                }
//                dump($list);die;
//                dump($list);die;
                $count = Category::where($where)->count();
                break;
            case 'article':
                $where = [
                    ['trash','=', 0]
                ];
                if (!empty($keywords)) {
                    $where[] = ['title', 'like', '%' . $keywords . '%'];
                }
                $list = Article::where($where)->field("id,title as name")
                    ->limit((($page - 1) * $page_size) . ',' . $page_size)->select();
                foreach ($list as &$v) {
                    $v['name'] = mb_substr($v['name'], 0, 30);

//                    $v['url'] = '/pages/news/article/article-detail/index?id=' . $v['id'];
                    $v['url'] = '/pages/index/article/index?id=' . $v['id'];
                }

                $count = Article::where($where)->count();
                break;
            case 'link':
                $list = [];
                $count = 1;
                break;
            case 'active_goods':
                $where = " gad.status=1 ";
                if (!empty($keywords)) {
                    $where .= " and gad.name   like '%{$keywords}%'";
                }
                $where .= " and ga.type in (1, 2, 3) ";
                $list = ActivityDetails::where($where)
                    ->alias("gad")
                    ->join("goods_activity ga", "gad.activity_id=ga.id")
                    ->field("gad.id, gad.name,gad.sku_id,gad.activity_id,gad.goods_id,ga.type")
                    ->limit((($page - 1) * $page_size) . ',' . $page_size)->select();
                foreach ($list as &$v) {
                    $key_name = GoodsSku::where([
                            'sku_id' => $v['sku_id']
                        ])->value("key_name")??'';

                    if (mb_strlen($v['name'], 'utf-8') >= 30) {
                        $v['name'] = mb_substr($v['name'], 0, 30) . '...';
                    }
                    if ($key_name != '') {
                        $v['name'] .= "({$key_name})";
                    }
                    if ($v['type'] == 1) {
                        //秒杀
                        $v['url'] = "/pages/activity/seckill/seckill-detail/index?goods_id={$v['goods_id']}&sku_id={$v['sku_id']}&activity_id={$v['activity_id']}";
                    } elseif ($v['type'] == 2) {
                        //拼团
                        $v['url'] = "/pages/activity/assemble/assemble-detail/index?goods_id={$v['goods_id']}&sku_id={$v['sku_id']}&activity_id={$v['activity_id']}";
                    } elseif ($v['type'] == 3) {
                        $v['url'] = "/pages/activity/advance/advance-detail/index?goods_id={$v['goods_id']}&sku_id={$v['sku_id']}&activity_id={$v['activity_id']}";
                    } else {
                        $v['url'] = '';
                    }
                }
                $count = ActivityDetails::where($where)
                    ->alias("gad")
                    ->join("goods_activity ga", "gad.activity_id=ga.id")
                    ->count();
                break;
            case 'active_list':
                $where = " status=1 ";
                if (!empty($keywords)) {
                    $where .= " and name   like '%{$keywords}%'";
                }
                $where .= " and type in (1, 2, 3) ";
                $list = Activity::where($where)
                    ->field("id, name,type")
                    ->group("type")
                    ->limit((($page - 1) * $page_size) . ',' . $page_size)->select();
                foreach ($list as &$v) {
                    $v['name'] = mb_substr($v['name'], 0, 30);
                    if ($v['type'] == 1) {
                        //秒杀
                        $v['url'] = "/pages/activity/seckill/seckill-list/index";
                    } elseif ($v['type'] == 2) {
                        //拼团
                        $v['url'] = "/pages/activity/assemble/assemble-list/index";
                    } elseif ($v['type'] == 3) {
                        //预售
                        $v['url'] = "/pages/activity/advance/advance-list/index";
                    } else {
                        $v['url'] = '';
                    }
                }
                $count = Activity::where($where)
                    ->group("type")
                    ->count();
                break;
            default:
                $list = [];
                $count = 1;
                return $list;
        }
        $retust['list'] = $list;
        $page_num = ceil($count / $page_size);
        $retust['page_num'] = $page_num;
        return ApiReturn::r(1, $retust, 'ok');
    }

    /*
     * 广告弹窗
     *
     */
    public function ad_layer()
    {
        return $this->fetch();
    }

    /*
     * 广告图裁切
     *
     */
    public function cut()
    {
        return $this->fetch();
    }
}
