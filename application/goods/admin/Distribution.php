<?php

namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\DistributionGoods;
use app\goods\model\Goods;
use app\goods\model\GoodsSku;
use service\Format;
use think\Db;
use app\goods\model\Category;

class Distribution extends Base
{
    /**
     * 秒杀活动列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author jxy [41578218@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $map = $this->getMap();


        // 数据列表
        $data_list = DistributionGoods::where($map)->order("id desc")->paginate();
        foreach ($data_list as $key => $value) {
            $goods = Goods::where(['id' => $value['goods_id']])->find();
            $data_list[$key]['name'] = $goods['name'];

            $data_list[$key]['type'] = $this->type($value['type']);
            $data_list[$key]['first_profit'] = $this->profit($value)['first_profit'];
            $data_list[$key]['second_profit'] = $this->profit($value)['second_profit'];

            $data_list[$key]['sn'] = $goods['sn'];
            $data_list[$key]['market_price'] = $goods['market_price'];
            $data_list[$key]['shop_price'] = $goods['shop_price'];
            $data_list[$key]['member_price'] = $goods['member_price'];
            $data_list[$key]['cost_price'] = $goods['cost_price'];
            if ($value['sku_id']) {
                $goods_sku = GoodsSku::where(['goods_id' => $value['goods_id'], 'sku_id' => $value['sku_id']])->find();
                $data_list[$key]['sn'] = $goods_sku['sku_sn'];
                $data_list[$key]['market_price'] = $goods_sku['market_price'];
                $data_list[$key]['shop_price'] = $goods_sku['shop_price'];
                $data_list[$key]['member_price'] = $goods_sku['member_price'];
                $data_list[$key]['cost_price'] = $goods_sku['cost_price'];
            }
        }

        $fields = [
            ['id', 'ID'],
            ['name', lang('商品名称')],
            ['sn', lang('货号')],
            ['market_price', lang('市场价')],
            ['shop_price', lang('本店价')],
            ['member_price', lang('会员价')],
            ['cost_price', lang('成本价')],
            ['type', lang('分销佣金收取方式')],
            ['first_profit', lang('一级收益')],
            ['second_profit', lang('二级收益')],
//            ['status', lang('状态'), 'status', '', [lang('禁用'), lang('启用')], 'text-center'],
            ['right_button', lang('操作'), 'btn']
        ];

//        $searchFields = [
//            ['name', lang('活动名称'), 'text'],
//            ['status', lang('状态'), 'select', '', [-1 => lang('全部'), 1 => lang('启用'), 0 => lang('禁用')]],
//        ];
        unset($this->right_button[1]);
        return Format::ins()//实例化
            ->hideCheckbox()
//            ->setTopSearch($searchFields)
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
//            ->bottom_button_select($this->bottom_button_select)
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /*
     * 分销佣金收取
     *
     */
    public function profit($data)
    {
        $res = [
            'first_profit' => 0,
            'second_profit' => 0

        ];
        if ($data['type'] == 0) {
            $res = [
                'first_profit' => $data['first_profit'],
                'second_profit' => $data['second_profit']
            ];
        }
        if ($data['type'] == 1) {
            $res = [
                'first_profit' => $data['first_profit'] . '%',
                'second_profit' => $data['second_profit'] . '%'
            ];
        }
        return $res;
    }

    /*
     *
     * 分销佣金收取方式
     */
    public function type($type)
    {
        $data = [
            0 => lang('固定金额'),
            1 => lang('固定比例')
        ];
        return $data[$type];
    }

    /*
     *新增
     *
     */
    public function add()
    {        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['create_time'] = time();

            if (isset($data['goods_id']) && $data['goods_id'] == 0) {
                return $this->error(lang('请选择商品'));
            }
            if (isset($data['first_profit']) && $data['first_profit'] == '') {
                return $this->error(lang('请填写一级收益'));
            }
            if (isset($data['second_profit']) && $data['second_profit'] == '') {
                return $this->error(lang('请填写二级收益'));
            }

            if ($res = DistributionGoods::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('goods_distribution_add', 'distribution_goods', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $category = Category::getTree(0);
        $fields = [
            ['type' => 'linkage', 'name' => 'cid', 'title' => lang('商品分类'), 'extra' => $category, 'value' => '', 'ajax_url' => url('Distribution/getGoods'), 'next_items' => 'goods_id'],
            ['type' => 'select', 'name' => 'goods_id', 'title' => lang('所属商品'), 'extra' => $this->getGoodsList(), 'value' => ''],
//            ['type' => 'select', 'name' => 'goods_id', 'title' => lang('商品名称'), 'extra' => $goods_list],
            ['type' => 'radio', 'name' => 'type', 'title' => lang('分销佣金收取方式'), 'attr' => '', 'extra' => [lang('固定金额'), lang('固定比例')], 'value' => '0'],
            ['type' => 'text', 'name' => 'first_profit', 'title' => lang('一级收益'), 'tips' => '请输入整数，根据收取方式决定，例如:输入2，代表2元或者2%'],
            ['type' => 'text', 'name' => 'second_profit', 'title' => lang('二级收益'), 'tips' => '请输入整数，根据收取方式决定，例如:输入2，代表2元或者2%'],
        ];
        $this->assign('page_title', lang('新增'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /*
     *
     * 编辑
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = DistributionGoods::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (isset($data['goods_id']) && $data['goods_id'] == 0) {
                return $this->error(lang('请选择商品'));
            }
            if (isset($data['first_profit']) && $data['first_profit'] == '') {
                return $this->error(lang('请填写一级收益'));
            }
            if (isset($data['second_profit']) && $data['second_profit'] == '') {
                return $this->error(lang('请填写二级收益'));
            }
            $res = DistributionGoods::where(['id' => $id])->update($data);
            if ($res) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('goods_distribution_edit', 'distribution_goods', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $category = Category::getTree(0);
        $fields = [
            ['type' => 'linkage', 'name' => 'cid', 'title' => lang('商品分类'), 'extra' => $category, 'value' => '', 'ajax_url' => url('distribution/getGoods'), 'next_items' => 'goods_id'],
            ['type' => 'select', 'name' => 'goods_id', 'title' => lang('商品名称'), 'extra' => $this->getGoodsList($info['cid']), 'value' => $info['goods_id']],
//            ['type' => 'select', 'name' => 'goods_id', 'title' => lang('商品名称'), 'extra' => $goods_list, 'value' => $info['goods_id']],
            ['type' => 'radio', 'name' => 'type', 'title' => lang('分销佣金收取方式'), 'attr' => '', 'extra' => [lang('固定金额'), lang('固定比例')], 'value' => $info['type']],
            ['type' => 'text', 'name' => 'first_profit', 'title' => lang('一级收益'), 'tips' => '请输入整数，根据收取方式决定，例如:输入2，代表2元或者2%'],
            ['type' => 'text', 'name' => 'second_profit', 'title' => lang('二级收益'), 'tips' => '请输入整数，根据收取方式决定，例如:输入2，代表2元或者2%'],
        ];
        $this->assign('page_title', lang('编辑'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /**
     * 设置状态
     * @param string $type 类型：disable/enable
     * @param array $record 行为日志内容
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author jxy [ 41578218@qq.com ]
     */
    public function setStatus($type = '')
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;
        $field = input('param.field', 'status');

        empty($ids) && $this->error(lang('缺少主键'));


        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = DistributionGoods::where('id', 'IN', $ids)->setField($field, 0);
                break;
            case 'enable': // 启用
                $result = DistributionGoods::where('id', 'IN', $ids)->setField($field, 1);
                break;
            case 'delete': // 删除
                $result = DistributionGoods::where('id', 'IN', $ids)->delete();
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // \Cache::clear();

            // 记录行为
            action_log('goods_activity_' . $type, 'goods', $ids, UID, 'ID：' . implode('、', $ids));
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }

    public function getGoodsList($category_id = '')
    {
        $goods_id = Db::name("distribution_goods")
            ->where([])
            ->column("goods_id");
        if (!$goods_id) {
            $goods_id = [0];
        }
        $where[] = ['is_delete', '=', 0];
        $where[] = ['id', 'not in', $goods_id];
        $where[] = ["status", "=", 1];
        $where[] = ["is_sale", "=", 1];
        if ($category_id) {
            $pidData = Category::where("pid", $category_id)->count();
            if (count($pidData) > 0) {
                $cids = Category::getChildsId($category_id);
                array_unshift($cids, $category_id);
                $where[] = ["cid", "in", $cids];
            } else {
                $where[] = ["cid", "=", $category_id];
            }
        }
        $menus = Goods::where($where)->column("id,name,shop_price");
        $newMenus = ['请选择：'];
        foreach ($menus as $key => $val) {
            $newMenus[$key] = $val['name'] . "￥" . $val['shop_price'];
        }

        return $newMenus;
    }

    public function getGoods($category_id = '')
    {
        $cid = input('param.cid');
        if (isset($cid)) {
            $c_id = $cid;
        } else {
            $c_id = $category_id;
        }
        $menus = $this->getGoodsList($c_id);
        $result = [
            'code' => 1,
            'msg' => lang('请求成功'),
            'list' => format_linkage($menus)
        ];
        return json($result);
    }
}
