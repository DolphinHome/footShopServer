<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\admin;

use app\admin\admin\Base;
use app\common\model\Area;
use app\user\model\Address as AddressModel;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use service\Format;

/**
 * 会员地址控制器
 * Class Address
 * @package app\user\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/3 9:30
 */
class Address extends Base
{
    /**
     * 会员地址列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        $map1 = $map2 = [];
        if (isset($map['mobile'])) {
            $map2[] = ['ad.mobile', 'like', '%' . $map['mobile'] . '%'];
        }
        if (isset($map['name'])) {
            $map2[] = ['ad.name', 'like', '%' . $map['name'] . '%'];
        }
        if (isset($map['address'])) {
            $map1[] = ['ad.province', 'like', '%' . $map['address'] . '%'];
            $map1[] = ['ad.city', 'like', '%' . $map['address'] . '%'];
            $map1[] = ['ad.district', 'like', '%' . $map['address'] . '%'];
            $map1[] = ['ad.address', 'like', '%' . $map['address'] . '%'];
        }
        if (isset($map['name'])) {
            unset($map['name']);
        }
        if (isset($map['address'])) {
            unset($map['address']);
        }
        if (isset($map['mobile'])) {
            unset($map['mobile']);
        }

        // 排序
        $order = $this->getOrder("ad.address_id desc");
        // 数据列表
        $data_list = AddressModel::alias('ad')->join('user u', 'ad.user_id=u.id', 'left')
            ->field('ad.address_id,ad.name,ad.mobile,ad.address,ad.is_default,u.user_nickname,ad.province,ad.city,ad.district')
            ->where($map)
            ->where($map2)
            ->where(function ($query) use ($map1) {
                $query->whereOr($map1);
            })
//            ->fetchSql(true)
            ->order($order)
//            ->select();
//        var_dump($data_list);
//        die;
            ->paginate()
            ->each(function ($item) {
                $item['address'] = $item['province'] . $item['city'] . $item['district'] . $item['address'];
                return $item;
            });
        $fields = [
            ['address_id', 'ID'],
            ['name', lang('姓名')],
            ['mobile', lang('收货电话')],
            ['address', lang('收货人地址')],
            ['is_default', lang('是否默认'), 'status', '', [lang('否'), lang('是')]],
//            ['user_nickname', lang('会员昵称')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        //添加姓名搜索、昵称搜索、手机号搜索、地址搜索（手动输入的即可）
        $search_fields = [
            ['name', lang('姓名'), 'text'],
            ['mobile', lang('手机号'), 'text'],
            ['address', lang('地址'), 'text'],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->setTopSearch($search_fields)
            ->addColumns($fields)//设置字段
            ->setRightButton(['title' => lang('查看详情'), 'href' => ['detail', ['id' => '__address_id__', 'layer' => 1]], 'icon' => 'fa fa-eye pr5', 'class' => 'btn btn-xs mr5 btn-default layeredit'])
            ->setRightButton(['title' => lang('编辑'), 'href' => ['edit', ['id' => '__address_id__', 'layer' => 1]], 'icon' => 'fa fa-pencil pr5', 'class' => 'btn btn-xs mr5 btn-default layeredit'])
            ->setData($data_list)//设置数据
            ->fetch();//显示
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

            // 验证
            $result = $this->validate($data, 'Address');
            if (true !== $result) {
                $this->error($result);
            }

            if ($res = AddressModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('user_address_add', 'user_address', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'text', 'name' => 'user_name', 'title' => lang('姓名'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'name', 'title' => lang('姓名')],
            ['type' => 'text', 'name' => 'mobile', 'title' => lang('收货电话')],
            ['type' => 'text', 'name' => 'province', 'title' => lang('省')],
            ['type' => 'text', 'name' => 'city', 'title' => lang('市')],
            ['type' => 'text', 'name' => 'district', 'title' => lang('区')],
            ['type' => 'text', 'name' => 'address', 'title' => lang('详细地址')],
            ['type' => 'radio', 'name' => 'is_default', 'title' => lang('是否默认'), 'tips' => '', 'attr' => '', 'value' => '1'],

        ];
        $this->assign('page_title', lang('新增会员地址'));
        $this->assign('form_items', $fields);
        return $this->fetch('../../admin/view/public/add');
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
        $info = AddressModel::where(['address_id' => $id])->find();
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $district_id = $data['district_id'][3]??0;
            $district_info = Area::get($district_id);
            if ($district_info['level'] != 3) {
                $this->error(lang('请选择地址'));
            }
            //所在区
            $district = $district_info['name'];
            //所在市
            $city_area = Area::get($district_info['pid']);
            if (!$city_area) {
                $this->error(lang('请选择地址'));
            }
            $city_id = $city_area['id'];
            $city = $city_area['name'];
            //所在省
            $province_area = Area::get($city_area['pid']);
            if (!$province_area) {
                $this->error(lang('请选择地址'));
            }
            $province_id = $province_area['id'];
            $province = $province_area['name'];

            $data['district'] = $district;
            $data['district_id'] = $district_id;
            $data['city'] = $city;
            $data['city_id'] = $city_id;
            $data['province'] = $province;
            $data['province_id'] = $province_id;
            // 验证
            $result = $this->validate($data, 'Address');
            if (true !== $result) {
                $this->error($result);
            }
            if ($data['is_default'] == 1) {
                AddressModel::where(['user_id' => $info['user_id']])->update([
                    'is_default' => 0
                ]);
            }
            AddressModel::where(['address_id' => $id])->update($data);
            //记录行为
            unset($data['__token__']);
            $details = arrayRecursiveDiff($data, $info);
            action_log('user_address_edit', 'user_address', $id, UID, $details);
            $this->success(lang('编辑成功'), cookie('__forward__'));
        }

        $fields = [
            ['type' => 'hidden', 'name' => 'address_id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('姓名')],
            ['type' => 'text', 'name' => 'mobile', 'title' => lang('收货电话')],
            ['type' => 'linkages', 'name' => 'district_id', 'title' => lang('选择地址'), 'table' => 'china_area', 'level' => 3, 'option' => 'name', 'pid' => 'pid', 'key' => 'id'],
            ['type' => 'text', 'name' => 'address', 'title' => lang('详细地址')],
            ['type' => 'radio', 'name' => 'is_default', 'title' => lang('是否默认'), 'tips' => lang('必填项'), 'attr' => '', 'extra' => [lang('否'), lang('是')], 'value' => 1],

        ];
        $this->assign('page_title', lang('编辑会员地址'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
}
