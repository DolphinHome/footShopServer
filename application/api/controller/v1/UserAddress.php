<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/10
 * Time: 15:54
 */

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\user\model\Address as AddressModel;
use app\common\model\Area;
use service\ApiReturn;
use think\Db;

/**
 * 用户收货地址
 * Class UserAddress
 * @package app\api\controller\v1
 */
class UserAddress extends Base
{
    /**
     * 地址列表
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @since 2019/4/11 13:40
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function address_list($data = [], $user = [])
    {
        $where[] = ['user_id', 'eq', $user['id'] ? $user['id'] : 2];
        $addressList = AddressModel::where($where)->order("address_id desc")->select();
        if ($addressList) {
            return ApiReturn::r(1, $addressList, lang('请求成功'));
        }
        return ApiReturn::r(0, [], lang('暂无收货地址'));
    }

    /**
     * 添加收货地址
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/23 17:40
     */
    public function add_address($data = [], $user = [])
    {
        if ($data['is_default'] == 1) {
            //查看用户是否存在默认收货地址
            $where[] = ['user_id', 'eq', $user['id'] ? $user['id'] : 2];
            $where[] = ['is_default', 'eq', 1];
            $userAddress = AddressModel::get_one_address($where);
            if ($userAddress) {
                //修改用户默认地址
                @AddressModel::where($where)->update(['is_default' => 0]);
            }
        } else {
            $where[] = ['user_id', 'eq', $user['id'] ? $user['id'] : 2];
            $res = AddressModel::get_all_address($where);
            if (count($res) == 0) {
                $data['is_default'] = 1;
            }
        }

        //进行添加
        $data['user_id'] = $user['id'];
        $data['status'] = 1;
//        $data['province_id'] = Area::getIdByName($data['province'], 1);
//        $data['city_id'] = Area::getIdByName($data['city'], 2, $data['province_id']);
//        $data['district_id'] = Area::getIdByName($data['district'], 3, $data['city_id']);
        $data['province_id'] = Area::where([
            'region_code' => $data['provinceCode']
        ])->value("id");
        $data['city_id'] = Area::where([
            'region_code' => $data['cityCode']
        ])->value("id");
        $data['district_id'] = Area::where([
            'region_code' => $data['districtCode']
        ])->value("id");
        $result = AddressModel::insertGetId($data);
        if ($result) {
            $userAddr = AddressModel::get_one_address(['address_id' => $result]);
            return ApiReturn::r(1, $userAddr, lang('添加成功'));
        }
        return ApiReturn::r(0, [], lang('添加失败'));
    }

    /**
     * 获得单条收货地址
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @author  风轻云淡
     */
    public function get_one_address($data = [], $user = [])
    {
        if ($data['address_id']) {
            $addressId = $data['address_id'];
            $where[] = ['address_id', 'eq', $addressId];
        } else {
            $where[] = ['is_default', 'eq', 1];
        }
        $where[] = ['user_id', 'eq', $user['id']];
        $getAddress = AddressModel::get_one_address($where);
        $getAddress['provinceCode'] = Area::where([
            'id' => $getAddress['province_id']
        ])->value("region_code");
        $getAddress['cityCode'] = Area::where([
            'id' => $getAddress['city_id']
        ])->value("region_code");
        $getAddress['districtCode'] = Area::where([
            'id' => $getAddress['district_id']
        ])->value("region_code");
//        dump($getAddress);die;
        if ($getAddress) {
            return ApiReturn::r(1, $getAddress, lang('请求成功'));
        } else {
            return ApiReturn::r(1, [], lang('暂无数据'));
        }
    }

    /**
     * 修改收货地址
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/23 17:39
     */
    public function edit_address($data = [], $user = [])
    {
        Db::startTrans();
        try {
            if ($data['is_default'] == 1) {
                //取消所有的默认地址
                if (AddressModel::where(['user_id' => $user['id'], 'is_default' => 1])->find()) {
                    $userAddress = AddressModel::where(['user_id' => $user['id'], 'is_default' => 1])->update(['is_default' => 0, 'update_time' => time()]);

                    if (!$userAddress) {
                        exception(lang('操作无效'));
                    }
                }
            } else {
                $data['is_default'] = 0;
            }
//            $data['province_id'] = Area::getIdByName($data['province'], 1);
//            $data['city_id'] = Area::getIdByName($data['city'], 2, $data['province_id']);
//            $data['district_id'] = Area::getIdByName($data['district'], 3, $data['city_id']);
//            dump($data['provinceCode']);
//            dump($data['cityCode']);
//            dump($data['districtCode']);
            $data['province_id'] = Area::where([
                'region_code' => $data['provinceCode']
            ])->value("id");
            $data['city_id'] = Area::where([
                'region_code' => $data['cityCode']
            ])->value("id");
            $data['district_id'] = Area::where([
                'region_code' => $data['districtCode']
            ])->value("id");
//            $data['province_id'] = $data['provinceCode'];
//            $data['city_id'] = $data['cityCode'];
//            $data['district_id'] = $data['districtCode'];
            $result = AddressModel::where(['address_id' => $data['address_id'], 'user_id' => $user['id']])->update($data);
            /*            if(!$result){
                            exception(lang('修改失败'));
                        }*/
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        return ApiReturn::r(1, [], lang('修改成功'));
    }

    /**
     * 删除收货地址
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @author  风轻云淡
     */
    public function del_address($data = [], $user = [])
    {
        $addressIds = $data['address_ids'];
        $where[] = ['address_id', 'in', explode(",", rtrim($addressIds, ","))];
        $where[] = ['user_id', 'eq', $user['id']];
        $result = AddressModel::where($where)->delete();
        if ($result) {
            return ApiReturn::r(1, [], lang('删除成功'));
        }
        return ApiReturn::r(0, [], lang('删除失败'));
    }

    /**
     * 修改为默认收货地址
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/23 17:39
     */
    public function change_default_address($data = [], $user = [])
    {
        Db::startTrans();
        try {
            $check = AddressModel::where(
                [
                    'user_id' => $user['id'],
                    'is_default' => 1]
            )->find();
            if ($check) {
                //取消所有的默认地址
                $userAddress = AddressModel::where(['user_id' => $user['id'], 'is_default' => 1])->update(['is_default' => 0]);
                if (!$userAddress) {
                    exception(lang('设置默认地址失败'));
                }
            }
            //修改默认收货地址
            $result = AddressModel::where(['user_id' => $user['id'], 'address_id' => $data['address_id']])->update(['is_default' => 1]);
            if (!$result) {
                exception(lang('设置默认地址失败'));
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], lang('操作失败'));
        }
        return ApiReturn::r(1, [], lang('操作成功'));
    }

    /*
     * 默认地址
     *
     */
    public function is_default($data = [], $user = [])
    {
        $address_id = $data['address_id'] ?? 0;
        \app\operation\model\UserAddress::where([
            'user_id' => $user['id']
        ])->update(['is_default' => 0]);
        \app\operation\model\UserAddress::where([
            'address_id' => $address_id
        ])->update(['is_default' => 1]);
        return ApiReturn::r(1, [], 'ok');
    }

    /**
     * Notes: 获取国际手机区号
     * User: chenchen
     * Date: 2021/5/22
     * Time: 14:56
     * @param array $data
     * @param array $user
     */
    public function area_code($data = [], $user = [])
    {
        $list = Db::name("phone_prefix")->select();
        $res = [];
        foreach ($list as $v) {
            $res[$v['letter']][] = [
                'country' => $v["country"] . ' +' . $v["prefix"],
                'prefix' => $v["prefix"]
            ];
        }
        if (!empty($res)) {
            ksort($res);
        }
        return ApiReturn::r(1, $res, 'ok');


    }


}
