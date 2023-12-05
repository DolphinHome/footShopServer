<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\Activity;
use app\goods\model\GoodsRewardDetails;
use app\goods\model\GoodsRewardRecord;
use app\user\model\Address as AddressModel;
use service\ApiReturn;
use service\Format;
use app\goods\model\Goods;
use app\operation\model\Coupon;

/**
 * 会员地址控制器
 * Class Address
 * @package app\user\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/3 9:30
 */
class Reward extends Base
{
    /**
     * 会员地址列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        return $this->fetch();
    }

    public function getList()
    {
        $list = GoodsRewardDetails::where([])->select();
        if ($list) {
            foreach ($list as &$v) {
                $v['thumb_img'] = get_file_url($v['img']);
                $v['thumb'] = $v['img'];
            }
        }
        return ApiReturn::r(1, $list, 'ok');
    }


    public function typeList()
    {
        $data = [
            ['type' => 1, 'name' => lang('商品')],
            ['type' => 2, 'name' => lang('优惠券')],
            ['type' => 3, 'name' => lang('谢谢参与')]
        ];
        return ApiReturn::r(1, $data, 'ok');
    }

    /*
     * 奖品列表
     *
     */
    public function getReward()
    {
        $Goods = Goods::where([
            'is_delete' => 0,
            'is_sale' => 1,
            'status' => 1,
        ])
            ->field('id,name')
            ->order('id desc')
            ->select();

        if ($Goods) {
            foreach ($Goods as &$v) {
                $v['name'] = lang('商品').'id：' . $v['id'] . $v['name'];
            }
        }
        $Coupon = Coupon::where([
            ['start_time', '<=', time()],
            ['end_time', '>=', time()],
            ['method', '=', 2],
            ['last_stock', '>=', 0],
            ['status', '=', 1]

        ])->order('id desc')
            ->field('id,name')
            ->select();
        if ($Coupon) {
            foreach ($Coupon as &$v) {
                $v['name'] = lang('优惠券').'id：' . $v['id'] . $v['name'];
            }
        }

        $list = [
            1 => $Goods,
            2 => $Coupon,
        ];
        return ApiReturn::r(1, $list, 'ok');
    }


    public function getType($type)
    {
        $data = ['1' => lang('商品'), '2' => lang('优惠券'), '3' => lang('谢谢参与')];
        return $data[$type];
    }

    /**
     * 查看详细地址
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 9:49
     * @return void
     */
    public function detail($id = 0)
    {
        if ($id == 0) {
            $this->error(lang('参数错误'));
        }

        $info = AddressModel::alias('ad')->join('user u', 'ad.user_id=u.id', 'left')->where('ad.address_id', $id)->field('ad.*,u.user_nickname')->find();
        $info['address'] = $info['province'] . $info['city'] . $info['district'] . $info['address'];
        $this->assign('info', $info);
        $fields = [
            ['type' => 'static', 'name' => 'name', 'title' => lang('收货人姓名')],
            ['type' => 'static', 'name' => 'mobile', 'title' => lang('收货电话')],
            ['type' => 'static', 'name' => 'address', 'title' => lang('收货人地址')],
            ['type' => 'radio', 'name' => 'is_default', 'title' => lang('是否默认'), 'extra' => [lang('否'), lang('是')], 'attr' => 'disabled'],
            ['type' => 'static', 'name' => 'user_nickname', 'title' => lang('所属会员昵称')],

        ];
        $this->assign('page_title', lang('会员地址详情'));
        $this->assign('form_items', $this->setData($fields, $info));
        $this->assign('btn_hide', 1);
        return $this->fetch('../../admin/view/public/edit');
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
            foreach ($data as $k => &$v) {
                $v['img'] = $v['thumb'];
                if (isset($v['id']) && !empty($v['id'])) {
                    GoodsRewardDetails::where(['id' => $v['id']])->update($data[$k]);
                } else {
                    $v['create_time'] = time();
                    $res = GoodsRewardDetails::create($data[$k]);
                    if ($res) {
                          //记录行为
                        $details = json_encode($data[$k], JSON_UNESCAPED_UNICODE);
                        action_log('goods_reward_add', 'goods_reward_details', $res->id, UID, $details);
                    }
                }
            }
            return ApiReturn::r(1, [], lang('保存成功'));
        }
        return $this->fetch();
    }

    /*
     *删除
     *
     */
    public function delete()
    {
        $id = request()->param('ids', 0);
        //检测
        $find = GoodsRewardRecord::where([
            'details_id' => $id,
        ])->find();
        if ($find) {
            $this->error(lang('商品已被抽中，不能删除'));
        }
        GoodsRewardDetails::where(['id' => $id])->delete();
        $this->success(lang('删除成功'));
    }

    /**
     * 编辑
     * @param null $id 会员地址id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
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

            if (GoodsRewardDetails::where(['id' => $id])->update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info = GoodsRewardDetails::get($id);
        $reward = $this->_reward($info['type']);
        $reward_type = [1 => lang('商品'), 2 => lang('优惠券'), 3 => lang('谢谢参与')];
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('奖品名称'), 'tips' => '', 'attr' => ''],
            ['type' => 'select', 'name' => 'type', 'title' => lang('奖品类型'), 'tips' => '', 'extra' => $reward_type, 'value' => $info['type']],
            ['type' => 'select', 'name' => 'reward_id', 'title' => lang('奖品'), 'tips' => '', 'extra' => $reward, 'value' => $info['reward_id']],
            ['type' => 'text', 'name' => 'chance', 'title' => lang('中奖概率'), 'tips' => '', 'attr' => ''],
        ];
        $this->assign('page_title', lang('编辑奖品'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
        return $this->fetch();
    }

    /*
     * 奖品
     *
     */
    public function _reward($type)
    {
        $list = [];
        if ($type == 1) {
            $list = Goods::where([
                'is_delete' => 0,
                'is_sale' => 1,
                'status' => 1,
            ])
                ->order('id desc')
                ->column('id,name');

            if ($list) {
                foreach ($list as $k => &$v) {
                    $v = lang('商品').'id：' . $k . $v;
                }
            }
        } elseif ($type == 2) {
            $list = Coupon::where([
                ['start_time', '<=', time()],
                ['end_time', '>=', time()],
                ['method', '=', 2],
                ['last_stock', '>=', 0],
                ['status', '=', 1]

            ])->order('id desc')
                ->column('id,name');
            if ($list) {
                foreach ($list as $k => &$v) {
                    $v = lang('优惠券').'id：' . $k . $v;
                }
            }
        }
        return $list;
    }
}
