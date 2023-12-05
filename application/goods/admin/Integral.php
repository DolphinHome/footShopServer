<?php

namespace app\goods\admin;

use app\goods\model\Activity as ActivityModel;
use app\goods\model\ActivityDetails as ActivityDetailsModel;
use service\Format;
use Think\Db;

class Integral extends Activity
{
    /**
     * 积分商品列表页
     * @return mixed
     * @throws \think\exception\DbException
     * @author zhougs
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $module = input('param.type');
        if (input('param.name')) {
            $map[] = ['a.name', 'like', '%' . input('param.name') . '%'];
        }
        if (is_numeric(input('param.status'))) {
            $map[] = ['a.status', '=', input('param.status')];
        }
        if (is_numeric(input('param.is_pure_integral'))) {
            $map[] = ['a.is_pure_integral', '=', input('param.is_pure_integral')];
        }

        $map[] = ['ga.type', '=', 8];
        /*        if (input('param.activity_id')) {
                    $top_add = ['title' => lang('新增'), 'data-toggle' => 'dialog-right', 'href' => ['add', ['layer' => 1, 'reload' => 1, 'id' => input('param.activity_id'),'type'=>$type]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary btn-flat'];
                    $map[] = ['a.activity_id', '=', input('param.activity_id')];
                }*/

        // 排序
        $order = $this->getOrder("a.id desc");
        // 数据列表
        $data_list = ActivityDetailsModel::alias('a')
            ->join('goods_sku gs', 'gs.sku_id=a.sku_id', 'left')
            ->join('goods_activity ga', 'ga.id=a.activity_id', 'left')
            ->field('a.id,a.goods_id,a.stock,a.status,a.activity_id,a.start_time,a.create_time,a.end_time,a.activity_price,a.member_activity_price,a.name,a.join_number,gs.key_name,gs.shop_price,ga.type,ga.name as activity_name,a.sales_integral,a.is_pure_integral')
            ->where($map)->order($order)->paginate();
        foreach ($data_list as $k => $v) {
            $goods = Db::name('goods')->get($v['goods_id']);
            $data_list[$k]['activity_type'] = ActivityModel::$activity_type[$v['type']];
            if ($v['type'] == 2) {
                $data_list[$k]['activity_name'] = $data_list[$k]['activity_name'] . ' (' . $v['join_number'] . '单成团)';
            }
            $data_list[$k]['shop_price'] = $goods['is_spec'] ? $data_list[$k]['shop_price'] : $goods['shop_price'];
        }
        $fields = [
            ['id', 'ID'],
//            ['activity_name', lang('活动名称')],
            ['activity_type', lang('类型')],
            ['name', lang('商品名称')],
            ['key_name', lang('规格名称')],
            ['stock', lang('活动库存')],
            ['shop_price', lang('售卖价格'), 'callback', function ($v) {
                return '<del>' . $v . '</del>';
            }],
            ['activity_price', lang('活动价格'),'text.edit'],
            ['member_activity_price', lang('会员活动价格'),'text.edit'],
            ['sales_integral', lang('销售积分'),'text.edit'],
            ['is_pure_integral', lang('是否纯积分兑换'),'status','',[lang('否'),lang('是')]],
//            ['member_activity_price', lang('会员价格'),'text.edit'],//['activity_price', lang('活动价格'),'text.edit']
        ];

        $fields[]=['create_time', lang('添加时间')];
        $fields[]=['status', lang('状态'), 'status', '', [lang('禁用'), lang('启用')], 'text-center'];
        $fields[]=['right_button', lang('操作'), 'btn', '', '', 'text-center'];
        $right_button = [
            ['ident' => 'disable', 'title' => lang('禁用'), 'href' => ['setstatus', ['type' => 'disable', 'ids' => '__id__']], 'class' => 'mr5 ajax-get confirm'],
            ['ident' => 'delete', 'title' => lang('删除'), 'href' => ['delete', ['ids' => '__id__']], 'class' => 'mr5 ajax-get confirm'],
        ];
        $search_fields = [
            ['name', lang('商品名称'), 'text'],
            ['status',lang('状态'),'select','',['all'=>lang('全部'),'0'=>lang('禁用'),'1'=>lang('启用')]],
            ['is_pure_integral',lang('是否纯积分兑换'),'select','',['all'=>lang('全部'),'0'=>lang('否'),'1'=>lang('是')]],
        ];
        /*        return Format::ins()//实例化
                ->addColumns($fields)//设置字段
        //        ->setTabNav($tab_list, $module,'module')//设置TAB分组
                ->setTopButton($top_add)
                    ->setTopButtons($this->top_button,['add'])
                    ->setRightButtons($right_button)
                    ->setTopSearch($search_fields)
                    ->setData($data_list)//设置数据
                    ->fetch();//显示*/

        $aid = ActivityModel::where("type", 8)->value('id');
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButton(['title' => lang('新增'), 'data-toggle' => 'dialog-right', 'href' => ['goods/activity_details/add', ['id' => $aid,'layer' => 1, 'reload' => 1, 'type' => 8]], 'icon'=>'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary btn-flat' ])
//            ->setRightButton(['title' => lang('活动商品'), 'href' => ['goods/activity_details/index', ['activity_id' => '__id__', 'type' => 7]], 'class' => 'mr5 font12'])
//            ->setRightButton(['title' => lang('添加商品'), 'data-toggle' => 'dialog-right', 'href' => ['goods/activity_details/add', ['id' => '__id__', 'layer' => 1, 'reload' => 0]],  'class' => 'mr5 font12'])
//            ->setRightButton(['title' => lang('编辑'), 'data-toggle' => 'dialog-right', 'href' => ['goods/activity_details/edit', ['id' => '__id__', 'layer' => 1, 'reload' => 1]], 'class' => 'mr5 font12'])
            ->setRightButtons($this->right_button, ['edit'])
            ->setTopSearch($search_fields)
            ->setData($data_list)//设置数据
            ->fetch();//显示
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
                $result = ActivityDetailsModel::where('id', 'IN', $ids)->setField($field, 0);
                break;
            case 'enable': // 启用
                $result = ActivityDetailsModel::where('id', 'IN', $ids)->setField($field, 1);
                break;
            case 'delete': // 删除
                $result = ActivityDetailsModel::where('id', 'IN', $ids)->delete();
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
}
