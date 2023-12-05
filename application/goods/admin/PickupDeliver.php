<?php
/*
 * @Descripttion: 用户自提点
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-04-13 18:55:55
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-28 14:16:37
 */
 
/**
 * 用户自提点管理
 */
namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\PickupDeliver as PickupDeliverModel;
use app\common\model\Area;
use service\Format;
use service\ApiReturn;
use think\Db;

class PickupDeliver extends Base
{
    /**
     * 自提点列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author zhougs
     * @since 2020年12月31日10:56:45
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        
        $map = $this->getMap();
        $map1 = $map2 = [];
        // 查询
        $where = [];
        if ($map['deliver_name']) {
            $where[] = ["deliver_name","like",'%'.$map['deliver_name'].'%'];
        }
        if ($map['full_address']) {
            //地址or查询，三个地区字段
            $map_ad[] = ["full_address|city_name|district_name", "like", '%'.$map['full_address'].'%'];
        }
        if ($map['deliver_mobile']) {
            $where[] = ["deliver_mobile","like",'%'.$map['deliver_mobile'].'%'];
        }
        // 排序
        $order = $this->getOrder("q.id desc");

        // 数据列表
        $data_list = PickupDeliverModel::alias('q')
            ->where($where)
            ->where($map_ad)
            ->order($order)
            ->paginate()
            ->each(function ($item) {
                $item['full_address'] = $item['city_name'] . $item['district_name'] . $item['full_address'];
                return $item;
            });

        $fields = [
            ['id', 'ID'],
            ['thumb', lang('门店图'), 'picture'],
            ['deliver_name', lang('自提点名称')],
            ['full_address', lang('自提点地址')],
            ['deliver_mobile', lang('自提点电话')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $searchFields = [
            ['deliver_name', lang('自提点名称'), 'text'],
            ['full_address', lang('自提点地址'), 'text',''],
            ['deliver_mobile', lang('自提点电话'), 'text'],
        ];

        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setTopButton(['title' => lang('新增'), 'data-toggle' => 'dialog-right', 'href' => ['add', ['layer' => 1, 'reload' => 1, 'type' => 1]], 'icon'=>'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary btn-flat' ])
        ->setTopButtons(['ident' => 'delete_all', 'title' => lang('批量删除'), 'href' => 'delete_all', 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-sm mr5 btn-default  ajax-post confirm', 'extra' => 'target-form="ids"'])
            ->setRightButton(['title' => lang('编辑'), 'data-toggle' => 'dialog-right', 'href' => ['edit', ['id' => '__id__', 'layer' => 1, 'reload' => 1]], 'icon'=>'fa fa-pencil pr5', 'class' => 'mr5 font12 btn btn-xs btn-default'])
            ->setRightButton(['ident'=> 'delete', 'title'=>lang('删除'),'href'=>['delete',['id'=>'__id__']],'icon'=>'fa fa-times pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->setRightButton(['ident'=> 'disable', 'title'=>lang('禁用'),'href'=>['setstatus',['type'=>'disable','id'=>'__id__']],'icon'=>'fa fa-ban pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->setTopSearch($searchFields)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (empty($data['full_address'])) {
                $this->error(lang('详细地址不能为空'));
            }
            if (empty($data['thumb'])) {
                $this->error(lang('需上传封面图'));
            }

            //城市信息
            $city_info = Area::getCityInfoByName($data['city_name']);
            $district_id = Area::getIdByName($data['district_name'], 3, $city_info['id']);

            //表数组
            $pickup['city_id'] = $city_info['id'];
            $pickup['district_id'] = $district_id;
            $pickup['city_name'] = $data['city_name'];
            $pickup['district_name'] = $data['district_name'];
            $pickup['deliver_name'] = $this->trimAll($data['deliver_name']);
            $pickup['full_address'] = $data['full_address'];
            $pickup['deliver_mobile'] = $this->trimAll($data['deliver_mobile']);
            $pickup['thumb'] = $data['thumb'];
            $pickup['lng'] = $data['lng'];
            $pickup['lat'] = $data['lat'];
            $pickup['create_time'] = time();

            if (!preg_match("/^1\d{10}$/", $pickup['deliver_mobile'])) {
                $this->error(lang('手机号格式不对'));
            }
            
            if (PickupDeliverModel::where('deliver_name', $pickup['deliver_name'])->find()) {
                $this->error(lang('名称已存在'));
            }
            
            if (empty($pickup['lng']) || empty($pickup['lat'])) {
                //根据填写的地址获取经纬度
                $address = $pickup['city_name'] . $pickup['district_name'] . $pickup['full_address'];
                $address_loc = Area::getLocationByAddress($address);
                $pickup['lng'] = $address_loc['lng'];
                $pickup['lat'] = $address_loc['lat'];
            }
           

            if ($res = PickupDeliverModel::create($pickup)) {
                //记录行为
                unset($pickup['__token__']);
                $details = json_encode($pickup, JSON_UNESCAPED_UNICODE);
                action_log('goods_pickup_add', 'pickup_deliver', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'image', 'name' => 'thumb', 'title' => lang('门店封面图'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'deliver_name', 'title' => lang('自提点名称'), 'tips' => '', 'attr' => '','extra'=> '', 'value' => ''],
            ['type' => 'text', 'name' => 'deliver_mobile', 'title' => lang('自提点电话'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'city_name', 'title' => lang('市'), 'attr'=>'readonly'],
            ['type' => 'text', 'name' => 'district_name', 'title' => lang('区'), 'attr'=>'readonly'],
            ['type' => 'text', 'name' => 'full_address', 'title' => lang('详细地址'), 'tips'=>'使用下方的【地图选点】功能选取详细地址'],
            ['type' => 'callmap',  'name' => 'full_address', 'title' => lang('使用地图')],
            ['type' => 'text', 'name' => 'lng', 'title' => lang('经度'), 'attr'=>'readonly'],
            ['type' => 'text', 'name' => 'lat', 'title' => lang('纬度'), 'attr'=>'readonly'],

        ];

        $this->assign('page_title', lang('新增自提点'));
        //定义地图默认城市
        $this->assign('city', '郑州');
        $this->assign('form_items', $fields);
        
        return $this->fetch('admin@map/add');
    }


    /**
     * 编辑
     */
    public function edit($id)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = PickupDeliverModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (empty($data['full_address'])) {
                $this->error(lang('地址为空'));
            }
            if (empty($data['thumb'])) {
                $this->error(lang('需上传封面图'));
            }
            //城市信息
            $city_info = Area::getCityInfoByName($data['city_name']);
            $district_id = Area::getIdByName($data['district_name'], 3, $city_info['id']);

            $data['city_id'] = $city_info['id'];
            ;
            $data['district_id'] = $district_id;
            $data['deliver_name'] = $this->trimAll($data['deliver_name']);
            $data['deliver_mobile'] = $this->trimAll($data['deliver_mobile']);
            if (empty($data['lng']) || empty($data['lat'])) {
                //根据填写的地址获取经纬度
                $address = $data['city_name'] . $data['district_name'] . $data['full_address'];
                $address_loc = Area::getLocationByAddress($address);
                $data['lng'] = $address_loc['lng'];
                $data['lat'] = $address_loc['lat'];
            }
            
            
            if (empty($data['deliver_name'])) {
                $this->error("不能为空");
            };

            if (!preg_match("/^1\d{10}$/", $data['deliver_mobile'])) {
                $this->error(lang('手机号格式不对'));
            }
            

            //编辑的数据和旧数据一样，返回的影响rows=0，但不代表更新失败了
            $update_res = PickupDeliverModel::where(['id' => $id])->update($data);
            if ($update_res === false) {
                $this->error(lang('编辑失败'));
            } else {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('goods_pickup_edit', 'pickup_deliver', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'), $res['id']);
            }
        }

        $fields = [
            ['type' => 'image', 'name' => 'thumb', 'title' => lang('门店封面图'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'deliver_name', 'title' => lang('自提点名称'), 'tips' => '', 'attr' => '','extra'=> '', 'value' => ''],
            ['type' => 'text', 'name' => 'deliver_mobile', 'title' => lang('自提点电话'), 'tips' => '', 'attr' => '', 'value' => ''],
            ['type' => 'text', 'name' => 'city_name', 'title' => lang('市'), 'attr'=>'readonly'],
            ['type' => 'text', 'name' => 'district_name', 'title' => lang('区'), 'attr'=>'readonly'],
            ['type' => 'text', 'name' => 'full_address', 'title' => lang('详细地址'),'tips'=>'建议使用下方的【地图选点】功能选取详细地址'],
            ['type' => 'callmap',  'name' => 'full_address', 'title' => lang('使用地图')],
            ['type' => 'text', 'name' => 'lng', 'title' => lang('经度'), 'attr'=>'readonly'],
            ['type' => 'text', 'name' => 'lat', 'title' => lang('纬度'), 'attr'=>'readonly'],
           
        ];
        $amap_api_key = config('amap_api_key')??'b97fe867e61667fc6e7babbf1075388a';
        $this->assign('amap_api_key', $amap_api_key);
        $this->assign('page_title', lang('编辑自提点'));
        //定义地图默认城市
        $this->assign('city', '郑州');
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@map/add');
    }

    /**
     * 删除
     */
    public function delete($id)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
       
        PickupDeliverModel::where(['id' => $id])->delete();
        $this->success(lang('删除成功'));
    }



    /**
     * 批量删除
     */
    public function delete_all($ids)
    {
        Db::startTrans();
        try {
            foreach ($ids as $k=>$v) {
                PickupDeliverModel::where("id", $v)->delete();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(lang('删除失败'));
        }
        return $this->success(lang('操作成功'));
    }


    /**
     * 状态修改
     * @param {*} $type
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-25 11:48:43
     */
    public function setstatus($type = '')
    {
        $data = input("param.");
        $ids = $data['id'];
        $type = $data['type']??$data['action'];
        $ids = (array)$ids;

        empty($ids) && $this->error(lang('缺少主键'));
        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = PickupDeliverModel::where('id', 'IN', $ids)->setField('status', 0);
                action_log('pickup_disable', 'pickup', 0, UID, '批量禁用用户ID:' . $ids);
                break;
            case 'enable': // 启用
                $result = PickupDeliverModel::where('id', 'IN', $ids)->setField('status', 1);
                action_log('pickup_enable', 'pickup', 0, UID, '批量启用用户ID:' . $ids);
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // 记录行为
            action_log('admin_user_' . $type, 'pickup', $ids, UID, 'ID：' . implode('、', $ids));
            return ApiReturn::r(1, [], lang('操作成功'));
        } else {
            return ApiReturn::r(0, [], lang('操作失败'));
        }
    }
}
