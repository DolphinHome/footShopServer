<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\shop\admin;

use app\admin\admin\Base;
use service\ApiReturn;
use service\Format;
use think\Db;
/**
 * 附近门店管理控制器
 * @package app\Index\admin
 */
class Index extends Base
{
    /**
     * 附近门店管理列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder();



        $list = Db::name('nearby_shop')->where($map)->order($order)->where('is_delete',0)->paginate()->each(function($value){
            $value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
            $value['address'] = $value['province'].$value['city'].$value['area'].$value['address'];
            return $value;
        });

        $fields =[
            ['id','ID'],
            ['shop_name',lang('店铺名称')],
            ['thumb',lang('门店图片') ,'picture'],
            ['address',lang('详细地址') ],
            ['create_time',lang('创建时间') ],
            ['right_button', lang('操作'), 'btn','','','text-center']
        ];
        $right_button = [
            ['ident'=> 'edit', 'title'=>'编辑','href'=>['edit', ['id'=>'__id__', 'layer' => 1]],'icon'=>'fa fa-pencil pr5','class'=>'btn btn-xs mr5 btn-default layeredit'],
            ['ident'=> 'delete', 'title'=>'删除','href'=>['delete',['id'=>'__id__']],'icon'=>'fa fa-times pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'],
        ];
        return Format::ins() //实例化
//		->setPrimaryKey('aid')
		->addColumns($fields)//设置字段
		->setTopButtons($this->top_button)
            ->hideCheckbox()
		->setRightButtons($right_button)
		->setData($list)//设置数据
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

            $data['thumb'] = $data['thumb_id'];
            $data['province'] = $data['store_province_name'];
            $data['city'] = $data['store_city_name'];
            $data['area'] = $data['store_district_name'];
            $data['province_id'] = $data['store_province_id'];
            $data['city_id'] = $data['store_city_id'];
            $data['area_id'] = $data['store_district_id'];
            // 验证
            $result = $this->validate($data, 'Shop');
            $data['create_time'] = time();
            $data['update_time'] = time();
            if(true !== $result) $this->error($result);
            if ($page = Db::name('nearby_shop')->insert($data)) {
                $this->success('新增成功', cookie('__forward__'));
            } else {
                $this->error('新增失败');
            }
        }
        return $this->fetch();
    }

    /**
     * 编辑
     * @param null $id 附近门店管理id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $data['thumb'] = $data['thumb_id'];
            $data['province'] = $data['store_province_name'];
            $data['city'] = $data['store_city_name'];
            $data['area'] = $data['store_district_name'];
            $data['province_is'] = $data['store_province_id'];
            $data['city_id'] = $data['store_city_id'];
            $data['area_id'] = $data['store_district_id'];


            // 验证
            $result = $this->validate($data, 'Shop');
            if(true !== $result) $this->error($result);
            $data['update_time'] = time();
            if ($page = Db::name('nearby_shop')->where('id',$data['id'])->update($data)) {
                $this->success('更新成功', cookie('__forward__'));
            } else {
                $this->error('更新失败');
            }
        }
        $this->assign('id',$id);
        return $this->fetch();
    }


    public function delete($id)
    {
        Db::startTrans();
        try {
//            dump($id);die;
            Db::name("nearby_shop")->where(['id' => $id])->update(['is_delete'=> 1,'update_time'=>time()]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(lang('删除失败'));
        }
        $this->success(lang('删除成功'));
    }


    /**
     * 获取商铺详情
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopInfo(){
        $id = input('id');
        $map[] = ['id','=',$id];
        $info  = Db::name('nearby_shop')->where($map)->where('is_delete',0)->find();
        $thumb = $info['thumb'];
        $info['thumb_id'] = $thumb;
        $info['thumb'] = get_file_url($thumb);

        $info['store_province_name'] = $info['province'];
        $info['store_city_name'] = $info['city'];
        $info['store_district_name'] = $info['area'];
        $info['store_province_id'] = $info['province_id'];
        $info['store_city_id'] = $info['city_id'];
        $info['store_district_id'] = $info['area_id'];
        return ApiReturn::r(1,$info,'获取成功');

    }
}