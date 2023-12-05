<?php
/*
 * 新人0元购
 * @Version: 1.0
 * @Author: jxy [41578218@qq.com]
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-28 14:49:28
 */

namespace app\goods\admin;

use app\goods\model\Activity as ActivityModel;
use app\goods\model\ActivityDetails as ActivityDetailsModel;
use service\Format;
use Think\Db;

class Zero extends Activity
{
    /**
     * 新人0元购列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author jxy [41578218@qq.com]
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
        $map[] = ['ga.type', 'eq', 7];
        if (input('param.type') > 0) {
            $map[] = ['ga.type', '=', input('param.type')];
        }
        $aid = ActivityModel::where("type", 7)->value('id');
        $top_add = ['title' => lang('新增'), 'data-toggle' => 'dialog-right', 'href' => ['add', ['layer' => 1, 'reload' => 1, 'id' => $aid, 'type' => 7]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary btn-flat'];
//        if (input('param.activity_id')) {
//            $map[] = ['a.activity_id', '=', input('param.activity_id')];
//        }
        $list_group = [
            0 => lang('全部'),
            1 => lang('秒杀活动'),
            2 => lang('拼团活动'),
            3 => lang('预售活动'),
            4 => lang('折扣活动'),
            5 => lang('砍价活动'),
            6 => lang('会员限购'),
        ];
        $tab_list = [];
        foreach ($list_group as $key => $value) {
            $tab_list[] = [
                'title' => $value,
                'url' => url('index', ['module' => $key]),
                'field' => 'module',
                'val' => $key,
            ];
        }
        // 排序
        $order = $this->getOrder("a.id desc");
        // 数据列表
        $data_list = ActivityDetailsModel::alias('a')
            ->join('goods_sku gs', 'gs.sku_id=a.sku_id', 'left')
            ->join('goods_activity ga', 'ga.id=a.activity_id', 'left')
            ->field('a.id,a.goods_id,a.deposit,a.stock,a.status,a.activity_id,a.start_time,a.create_time,a.end_time,a.activity_price,a.member_activity_price,a.name,a.join_number,gs.key_name,gs.shop_price,ga.type,ga.name as activity_name')
            ->where($map)->order($order)->paginate();
        foreach ($data_list as $k => $v) {
            $goods = Db::name('goods')->get($v['goods_id']);
            $data_list[$k]['activity_type'] = ActivityModel::$activity_type[$v['type']];
            if ($v['type'] == 2) {
                $data_list[$k]['activity_name'] = $data_list[$k]['activity_name'] . ' (' . $v['join_number'] . lang('单成团') .')';
            }
            $data_list[$k]['shop_price'] = $goods['is_spec'] ? $data_list[$k]['shop_price'] : $goods['shop_price'];
        }
        $fields = [
            ['id', 'ID'],
            ['activity_name', lang('活动名称')],
            ['activity_type', lang('类型')],
            ['name', lang('商品名称')],
            ['key_name', lang('规格名称')],
            ['stock', lang('活动库存')],
            ['shop_price', lang('售卖价格'), 'callback', function ($v) {
                return '<del>' . $v . '</del>';
            }],
            ['activity_price', lang('活动价格'), 'text.edit'],
//          ['member_activity_price', lang('会员价格'),'text.edit'],//['activity_price', lang('活动价格'),'text.edit']
        ];
        if (input('param.type') == 3) {
            $fields[] = ['deposit', lang('定金'), 'text.edit'];
        }
        if ($module == 2 || $module == 3 || $module == 5 || $module == 6) {
        } else {
            $fields[] = ['start_time', lang('开始时间'), 'callback', function ($v) {
                return $v . ':00:00';
            }];
            $fields[] = ['end_time', lang('结束时间'), 'callback', function ($v) {
                if ($v == 23) { //23默认截止到全天结束
                    return $v . ':59:59';
                }
                return $v . ':00:00';
            }];
        }
        $fields[] = ['create_time', lang('添加时间')];
        $fields[] = ['status', lang('状态'), 'status', '', [lang('禁用'), lang('启用')], 'text-center'];
        $fields[] = ['right_button', lang('操作'), 'btn', '', '', 'text-center'];
        $right_button = [
            ['ident' => 'disable', 'title' => lang('禁用'), 'href' => ['setstatus', ['type' => 'disable', 'ids' => '__id__']], 'class' => 'mr5 ajax-get confirm'],
            ['ident' => 'delete', 'title' => lang('删除'), 'href' => ['delete', ['ids' => '__id__']], 'class' => 'mr5 ajax-get confirm'],
        ];
        $search_fields = [
            ['name', lang('商品名称'), 'text'],
//          ['type',lang('活动类型'),'select','',$list_group],
            ['status', lang('状态'), 'select', '', ['all' => lang('全部'), '0' => lang('禁用'), '1' => lang('启用')]],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
//          ->setTabNav($tab_list, $module,'module')//设置TAB分组
            ->setTopButton($top_add)
//          ->setTopButtons($this->top_button,['add'])
            ->setRightButtons($right_button)
            ->setTopSearch($search_fields)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 添加活动商品信息
     * @return mixed
     * @throws \think\exception\DbException
     * @author jxy [41578218@qq.com]
     */
    public function add()
    {
        //type 1秒杀活动2拼团活动3预售活动4折扣活动5砍价活动7积分商品
        //$timeSlice = explode(',', module_config('goods.timeSlice'));
        $timeSlice = [
            0 => 0, 2 => 2, 4 => 4, 6 => 6, 8 => 8, 10 => 10, 12 => 12, 14 => 14, 16 => 16, 18 => 18, 20 => 20, 22 => 22
        ];//步进的2小时
        $timeSlice[24] = lang('全天');
        $groupPerson = explode(',', module_config('goods.joinNumber'));
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (Db::name('goods_activity_details')->where(['goods_id' => $data['goods_id'], 'activity_id' => $data['activity_id']])->find()) {
                //先检查有没有设置不限时间段的，如果有就不用再设置了
                $this->error(lang('该商品已参加其他活动，请勿重复'));
            }
            Db::startTrans();
            try {
                if ($data['activity_type'] == 2 || $data['activity_type'] == 4 || $data['activity_type'] == 3 || $data['activity_type'] == 5 || $data['activity_type'] == 6 || $data['activity_type'] == 8) {
                    $savedata['unlimited'] = 0;
                } else {
                    if ($data['activity_time'] == 24) {
                        //全天
                        $data['unlimited'] = 0;
                    } else {
                        $start_time = $data['activity_time'];
                        $end_time = $data['activity_time'] + 2;//2代表步进的2小时
                        $savedata['unlimited'] = 1;
                    }
                }
                $savedata['activity_id'] = $data['activity_id'];
                $savedata['goods_id'] = $data['goods_id'];
                $savedata['start_time'] = $start_time ? $start_time : 0;
                $savedata['end_time'] = $end_time ? $end_time : 0;
                $savedata['create_time'] = time();
                $savedata['join_number'] = $groupPerson[$data['join_number']];

                foreach ($data['sku_id'] as $k => $v) {
                    $j = $k + 1;
                    if (!$data['stock'][$k]) {
                        exception(lang('请设置第') . $j . lang('个规格商品的活动库存'));
                    }
                    if ($data['price'][$k] <= 0) {
                        exception(lang('请设置第') . $j . lang('个规格商品的活动价格大于0'));
                    }
                    if ($data['member_price'][$k] <= 0) {
                        exception(lang('请设置第') . $j . lang('个规格商品的活动会员价格大于0'));
                    }
                    if ($data['activity_type'] == 8) {
                        if ($data['sales_integral'][$k] <= 0) {
                            exception(lang('请设置第') . $j . lang('个规格商品的销售积分大于0'));
                        }
                    }

                    //$goods = [];
                    //$res = '';
                    $savedata['sku_id'] = $v;
                    if ($v) {
                        $goods = Db::name('goods_sku')->alias('a')->field('a.stock,a.shop_price,g.name')->where([['a.sku_id', '=', $v]])->join('goods g', 'g.id=a.goods_id', 'left')->find();
                        if (!$goods) {
                            exception(lang('商品规格不存在'));
                        }
                        if ($goods['stock'] < $data['stock'][$k]) {
                            exception(lang('商品库存不足') . $data['stock'][$k] . lang('个'));
                        }
                        $savedata['stock'] = $data['stock'][$k];
                        $savedata['limit'] = $data['limit'][$k] ?? 1;
                    //$res = Db::name('goods_sku')->where([['sku_id', '=', $v]])->setDec('stock', $data['stock'][$k]);
                    } else {
                        $goods = Db::name('goods')->get($data['goods_id']);
                        if (!$goods) {
                            exception(lang('商品不存在'));
                        }
                        if ($goods['stock'] < $data['stock'][$k]) {
                            exception(lang('商品库存不足') . $data['stock'][$k] . lang('个'));
                        }
                        $savedata['stock'] = $data['stock'][$k];
                        $savedata['limit'] = $data['limit'][$k] ?? 1;
                        //$res = Db::name('goods')->where([['id', '=', $data['goods_id']]])->setDec('stock', $data['stock'][$k]);
                    }
                    $savedata['name'] = $goods['name'];
                    if ($data['activity_type'] == 3) {
                        $savedata['deposit'] = $data['price'][$k];
                        if (empty($data['member_price2'][$k])) {
                            exception(lang('商品店铺价格未设置'));
                        }
                        $savedata['activity_price'] = $data['member_price2'][$k];
                    } else {
                        $savedata['activity_price'] = $data['price'][$k];
                    }
                    if ($data['activity_type'] == 8) {
                        $is_pure_integral = 0;
                        $savedata['sales_integral'] = $data['sales_integral'][$k];
                        if (in_array($data['sku_id'][$k], $data['is_pure_integral'])) {
                            $is_pure_integral = 1;
                        }
                        $savedata['is_pure_integral'] = $is_pure_integral;
                    }
                    $savedata['member_activity_price'] = $data['member_price'][$k] ? $data['member_price'][$k] : $data['price'][$k];
                    $savedata['join_number'] = $data['activity_type'] == 5 ? $data['stock'][$k] : $savedata['join_number'];
                    $savedata['limit'] = $data['limit'][$k] ?? 1;
                    //if ($res) {
                    $goods_activity_details_id = Db::name('goods_activity_details')->insertGetId($savedata);
                    if ($data['activity_type'] == 5) {
                        $price = bcsub($goods['shop_price'], $savedata['activity_price'], 2);
                        $count = $data['stock'][$k];
                        $this->cutPrice($count, $price, $goods_activity_details_id);
                    }
                    //}
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            //记录行为
            unset($savedata['__token__']);
            $details = json_encode($savedata, JSON_UNESCAPED_UNICODE);
            action_log('goods_zero_add', 'goods_activity_details', $goods_activity_details_id, UID, $details);

            $this->success(lang('新增成功'), cookie('__forward__'));
        }
        $id = input('param.id');

        $activity = Db::name('goods_activity')->get($id);

        $fields = [
            ['type' => 'hidden', 'name' => 'activity_id', 'value' => $id],
            ['type' => 'hidden', 'name' => 'activity_type', 'value' => $activity['type']],
            ['type' => 'select', 'name' => 'goods_id', 'title' => lang('请选择商品'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'table', 'title' => lang('子商品列表'), 'id' => 'sku'],
        ];
        switch ($activity['type']) {
            case 1:
                $tip = lang('不限时秒杀，场次选择全天即可');
                //秒杀显示时间
                $fields[] = ['type' => 'radio', 'name' => 'activity_time', 'title' => lang('活动时间场次'), 'tips' => lang('整点秒杀请选择时间段，一个商品只能设置一个时间段，活动有效期内每天都可以在这个时间点内购买'), 'extra' => $timeSlice, 'value' => 24];
                break;
            case 2:
                //拼团显示人数
                $fields[] = ['type' => 'select', 'name' => 'join_number', 'title' => lang('拼团人数'), 'tips' => lang('请选择参团人数'), 'extra' => $groupPerson];
                break;
            case 3:
                $tip = lang('预售全款为商品本店价，活动填写定金即可，余款系统会自动计算');
                break;
            case 4:
                $tip = lang('折扣只在活动有效期内有效');
                break;
            case 5:
                $tip = lang('砍价全款为商品本店价，活动填写可砍至最低金额即可');
                break;
        }

        $this->assign('page_title', lang('新增活动商品'));
        $this->assign('page_tip', $tip);
        $this->assign('form_items', $fields);
        $this->assign('set_script', ['/static/admin/js/activity.js']);
        return $this->fetch('admin@public/add');
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
//                $result = ActivityModel::where('id','IN',$ids)->setField($field, 0);
                $result = ActivityDetailsModel::where('id', 'IN', $ids)->setField($field, 0);
                break;
            case 'enable': // 启用
//                $result = ActivityModel::where('id','IN',$ids)->setField($field, 1);
                $result = ActivityDetailsModel::where('id', 'IN', $ids)->setField($field, 1);
                break;
            case 'delete': // 删除
//                $result = ActivityModel::where('id','IN',$ids)->delete();
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
