<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\Coupon as CouponModel;
use app\goods\model\Category;
use app\goods\model\Goods;
use app\operation\model\CouponRecord;
use app\user\model\User as UserModel;
use service\Format;
use think\Request;
use think\Db;

/**
 * 优惠券控制器
 * Class Coupon
 * @package app\admin\controller
 */
class Coupon extends Base
{

    /**
     * 优惠券列表
     * @return \think\response\View
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */

    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = CouponModel::where($map)->order($order)->paginate()->each(function ($item) {
            if ($item['start_time'] + $item['valid_day'] * 24 * 3600 < time()) {
                $item['status'] = 3;
            }
        });
        $fields = [
            ['id', 'ID'],
            ['name', lang('优惠券名称')],
            ['start_time', lang('开始领取时间'), 'callback', function ($data) {
                return date('Y-m-d', $data) . ' 00:00:00';
            }],
            ['end_time', lang('领取结束时间'), 'callback', function ($v) {
                return date('Y-m-d', $v) . ' 23:59:59';
            }],
            ['money', lang('面额')],
            ['min_order_money', lang('最低使用金额')],
            ['valid_day', lang('有效天数')],
            ['stock', lang('总张数')],
            ['last_stock', lang('剩余张数')],
            ['method', lang('领取方式'), 'status', '', [lang('系统发放'), lang('首页弹窗'), lang('手动领取')]],
            ['create_time', lang('创建时间'), '', '', '', 'text-center'],
            ['status', lang('状态'), 'status', '', [lang('未开启'), lang('可领取'), lang('已领完'), lang('已过期')], 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];

        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setRightButton(['title' => lang('领取记录'), 'href' => ['receiving', ['id' => '__id__']], 'icon' => 'fa fa-list pr5', 'class' => 'btn btn-xs mr5 btn-success '])
            ->setRightButton(['title' => lang('手动发放'), 'href' => ['send_coupon', ['id' => '__id__']], 'icon' => 'fa fa-send-o pr5', 'class' => 'btn btn-xs mr5 btn-success '])
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 添加优惠券
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['last_stock'] = $data['stock'];
            // 验证
            $result = $this->validate($data, 'Coupon');
            if (true !== $result) {
                $this->error($result);
            }

            $data['start_time'] = strtotime($data['start_time'] . ' 00:00:00');
            $data['end_time'] = strtotime($data['end_time'] . ' 23:59:59');
            if ($data['money'] >= $data['min_order_money']) {
                $this->error(lang('优惠券金额设置有误'));
            }
            if ($data['valid_day']) {
                if (time() >= strtotime($data['valid_day'] . '23:59:59')) {
                    $this->error(lang('有效期不能小于当前时间'));
                }
                $data['valid_day'] = floor((strtotime($data['valid_day'] . ' 23:59:59') - time()) / (24 * 3600));
            }

            if ($data['goods_id']) {
                $goodsinfo = Goods::where(['id'=>$data['goods_id']])->field('shop_price')->find();
                if (intval(100*$goodsinfo['shop_price']) < intval(100*$data['min_order_money'])) {
                     $this->error(lang('选择的商品定价小于优惠券最低使用金额'));
                }
             }

            if ($res = CouponModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('operation_coupon_add', 'operation', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        $category = Category::getTree(0);
        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('优惠券名称')],

            ['type' => 'linkage', 'name' => 'cid', 'title' => lang('商品分类'), 'extra' => $category, 'value' => '', 'ajax_url' => url('coupon/getGoods'), 'next_items' => 'goods_id'],
            ['type' => 'select', 'name' => 'goods_id', 'title' => lang('所属商品'), 'extra' => $this->getGoodsList(), 'value' => ''],

            ['type' => 'date', 'name' => 'start_time', 'title' => lang('开始领取时间'), 'tips' => ''],
            ['type' => 'date', 'name' => 'end_time', 'title' => lang('领取结束时间'), 'tips' => ''],
            ['type' => 'text', 'name' => 'money', 'title' => lang('面额'), 'tips' => ''],
            ['type' => 'number', 'name' => 'min_order_money', 'title' => lang('最低使用金额'), 'tips' => lang('不用填写单位，只需填写具体数字')],
            ['type' => 'date', 'name' => 'valid_day', 'title' => lang('有效期'), 'tips' => ''],
            ['type' => 'number', 'name' => 'stock', 'title' => lang('总张数'), 'tips' => lang('不用填写单位，只需填写具体数字')],
            ['type' => 'radio', 'name' => 'method', 'title' => lang('领取方式'), 'extra' => [lang('系统发放'), lang('首页弹窗'), lang('手动领取')], 'value' => 0],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), 'tips' => '', 'extra' => [lang('关闭'), lang('可领取')], 'value' => 0],
            ['type' => 'textarea', 'name' => 'content', 'title' => lang('请填写优惠券内容'), 'tips' => '']
        ];

        $this->assign('page_title', lang('新增优惠券'));
        $this->assign('form_items', $fields);
        $this->assign('set_script', ['/static/plugins/layer/laydate/laydate.js']);
        return $this->fetch('admin@public/add');
    }

    public function getGoods($cid = '')
    {
        $menus = $this->getGoodsList($cid);
        $result = [
            'code' => 1,
            'msg' => lang('请求成功'),
            'list' => format_linkage($menus)
        ];
        return json($result);
    }

    public function getGoodsList($cid = '')
    {
        $where[] = ['status', '=', 1];
        $where[] = ['is_sale', '=', 1];
        if ($cid) {
            $where[] = ['cid', '=', $cid];
        }
        $menus = Goods::where($where)->column('id,name');
        $newMenus = ['请选择：'];
        foreach ($menus as $key => $val) {
            $newMenus[$key] = $val;
        }
        return $newMenus;
    }

    /**
     * 编辑优惠券
     * @param int $id 优惠券id
     * @return \think\response\View
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function edit($id = 0)
    {
        if (!$id) {
            $this->error(lang('参数错误'));
        }

        // 读取优惠券信息
        $info = CouponModel::get($id);
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            if ($data['money'] >= $data['min_order_money']) {
                $this->error(lang('优惠券金额设置有误'));
            }

            if ($data['valid_day']) {
                if (time() >= strtotime($data['valid_day'] . '23:59:59')) {
                    $this->error(lang('有效期不能小于当前时间'));
                }
                $data['valid_day'] = floor((strtotime($data['valid_day'] . ' 23:59:59') - time()) / (24 * 3600));
            }
            
            if ($data['goods_id']) {
               $goodsinfo = Goods::where(['id'=>$data['goods_id']])->field('shop_price')->find();
               if (intval(100*$goodsinfo['shop_price']) < intval(100*$data['min_order_money'])) {
                    $this->error(lang('选择的商品定价小于优惠券最低使用金额'));
               }
            }

            // 验证
            $result = $this->validate($data, 'Coupon');
            if (true !== $result) {
                $this->error($result);
            }

            $data['start_time'] = strtotime($data['start_time'] . ' 00:00:00');
            $data['end_time'] = strtotime($data['end_time'] . ' 23:59:59');

            if (CouponModel::update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('operation_coupon_edit', 'operation_coupon', $id, UID, $details);
                $this->success(lang('编辑成功'), 'index');
            } else {
                $this->error(lang('编辑失败'));
            }
        }
        $info['valid_day'] = date('Y-m-d', strtotime($info['create_time']) + $info['valid_day'] * 24 * 60 * 60);
        $info['start_time'] = date('Y-m-d', $info['start_time']);
        $info['end_time'] = date('Y-m-d', $info['end_time']);
        $category = Category::getTree(0);
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('优惠券名称')],

            ['type' => 'linkage', 'name' => 'cid', 'title' => lang('商品分类'), 'extra' => $category, 'value' => $info['cid'], 'ajax_url' => url('coupon/getGoods'), 'next_items' => 'goods_id'],
            ['type' => 'select', 'name' => 'goods_id', 'title' => lang('所属商品'), 'extra' => $this->getGoodsList($info['cid']), 'value' => ''],

            ['type' => 'date', 'name' => 'start_time', 'title' => lang('开始领取时间'), 'tips' => ''],
            ['type' => 'date', 'name' => 'end_time', 'title' => lang('领取结束时间'), 'tips' => ''],
            ['type' => 'text', 'name' => 'money', 'title' => lang('面额'), 'tips' => ''],
            ['type' => 'number', 'name' => 'min_order_money', 'title' => lang('最低使用金额'), 'tips' => lang('不用填写单位，只需填写具体数字')],
            ['type' => 'date', 'name' => 'valid_day', 'title' => lang('有效期'), 'tips' => ''],
            ['type' => 'number', 'name' => 'stock', 'title' => lang('总张数'), 'tips' => lang('不用填写单位，只需填写具体数字')],
            ['type' => 'radio', 'name' => 'method', 'title' => lang('领取方式'), 'extra' => [lang('系统发放'), lang('首页弹窗'), lang('手动领取')]],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), 'tips' => '', 'extra' => [lang('关闭'), lang('可领取')]],
            ['type' => 'textarea', 'name' => 'content', 'title' => lang('请填写优惠券内容'), 'tips' => '']
        ];

        $this->assign('page_title', lang('编辑优惠券'));
        $this->assign('set_script', ['/static/plugins/layer/laydate/laydate.js']);
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /**
     * 优惠券领取记录
     * @param int $id 优惠券id
     * @return mixed
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function receiving($id = 0)
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map['c.id'] = $id;
        // 排序
        $order = $this->getOrder('cr.start_time desc');
        // 数据列表
        $data_list = CouponRecord::alias('cr')->join('operation_coupon c', 'cr.cid=c.id')->where($map)->field('cr.*,c.name,c.money,c.min_order_money')->order($order)->paginate();

        $fields = [
            ['id', 'ID'],
            ['name', lang('优惠券名称')],
            ['user_id', lang('领取人'), 'callback', 'get_nickname'],
            ['start_time', lang('领取时间'), 'callback', function ($data) {
                return date('Y-m-d H:i:s', $data);
            }, '', 'text-center'],
            ['end_time', lang('过期时间'), 'callback', function ($v) {
                return date('Y-m-d H:i:s', $v);
            }, '', 'text-center'],
            ['money', lang('面额')],
            ['min_order_money', lang('最低使用金额')],
            ['status', lang('状态'), 'status', '', [lang('已过期'), lang('未使用'), lang('占用中'), lang('已使用'), lang('已失效')], 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $this->assign('back_show', 1);
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setRightButton(['title' => lang('删除优惠券'), 'href' => ['delete_coupon_record', ['id' => '__id__']], 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-xs mr5 btn-danger  ajax-get confirm'])
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /** 删除会员的优惠券
     * @param $id
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function delete_coupon_record($id)
    {
        if (!$id) {
            $this->error(lang('参数错误'));
        }

        $res = CouponRecord::where('id', $id)->delete();
        if ($res) {
            $this->success(lang('删除成功'));
        }

        $this->error(lang('删除失败'));
    }

    /**
     * 发放优惠券
     * @param $id
     * @author 风情云淡
     * @return mixed
     */
    public function send_coupon($id = 0)
    {
        $couponId = $id;
        $Iswhere = [];
        if ($this->request->isPost() && input('post.searchData') == 1) {
            $data = $this->request->post();
            unset($data['mobile']);
            $couponId = $data['coupon_id'];
            $couponType = CouponModel::where(['id' => $data['coupon_id']])->field('start_time,end_time,stock,last_stock,method')->find();
            if ($couponType['method'] != 0) {
                return json(['status' => 0, 'msg' => lang('该优惠券类型不支持发放')]);
            }
            if ($couponType['last_stock'] <= 0 && $couponType['stock'] > 0) {
                //$this->error(lang('已经发放完了'));
                return json(['status' => 0, 'msg' => lang('已经发放完了')]);
            }
            $hasCouponUserId = CouponRecord::where(['cid' => $couponId])->column('user_id');
            $insertData = [];
            if ($data['type'] == 0) {
                return json(['status' => 0, 'msg' => lang('请选择发放类型')]);
            } elseif ($data['type'] == 1) {
                //随机发放
                $where = [];
                if ($hasCouponUserId) {
                    $where[] = ['id', 'not in', $hasCouponUserId];
                }
                $userIds = UserModel::where($where)->column('id');
                if (count($userIds) > $couponType['last_stock']) {
                    $userIds = array_rand($userIds, $couponType['last_stock']);
                    $number = count($userIds);
                } else {
                    $number = count($userIds);
                }
            } else {
                //指定用户发放
                $userIds = $data['user_ids'];
                $number = count($userIds);
                if ($number > $couponType['last_stock']) {
                    //$this->error(lang('发放数量超过总数量'));
                    return json(['status' => 0, 'msg' => lang('发放数量超过总数量')]);
                }
            }

            foreach ($userIds as &$value) {
                if ($hasCouponUserId) {
                    if (in_array($value, $hasCouponUserId)) {
                        continue;
                    }
                }
                $insertData[] = [
                    'cid' => $couponId,
                    'user_id' => $value,
                    'start_time' => time(),
                    'end_time' => $couponType['end_time'],
                    'status' => 1
                ];
            }
            CouponRecord::startTrans();
            try {
                CouponRecord::insertAll($insertData);
                CouponModel::where(['id' => $data['coupon_id']])->setDec('last_stock', count($insertData));

                // 提交事务
                CouponRecord::commit();
            } catch (\Exception $e) {
                // 回滚事务
                CouponRecord::rollback();
                return json(['status' => 0, 'msg' => lang('发放失败')]);
            }
            return json(['status' => 1, 'msg' => lang('发放成功')]);
        } else {
            $mobile = input('param.mobile');
            $type = input('param.type') ?? 0;
            if ($mobile != '') {
                //$Iswhere['mobile'] = $mobile;
                $Iswhere[] = ['mobile','like','%'.$mobile.'%'];
            }
        }
        //获得用户信息
        $hasCouponUserId = CouponRecord::where(['cid' => $couponId])->column('user_id');
        $condition = [];
        if ($hasCouponUserId) {
            $condition[] = ['id', 'not in', $hasCouponUserId];
        }
        $userList = UserModel::where($condition)->where($Iswhere)->field('id,mobile,user_nickname')->select();
        $this->assign('user_list', $userList);
        $this->assign('coupon_id', $couponId);
        $this->assign('type', $type);
        $this->assign('Iswhere', $Iswhere);
        return $this->fetch();
    }
}
