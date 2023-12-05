<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\admin;

use app\admin\admin\Base;
use service\Format;
use think\paginator\driver\Bootstrap;
use think\facade\Config;
use think\Db;
use service\ApiReturn;


/**
 * 微信小程序直播管理
 * @package app\operation\admin
 */
class Live extends Base
{

    /**
     * 直播间列表
     */
    public function index()
    {
        $data = [];
        $curPage = input('page') ? input('page') : 1;
      
        $listRows = 10;//每页2行记录
        try {
          
            $arr = ["start"=>0, "limit"=>100];
            $cache_key = 'livelist_start_0_limit_100';
            $redis = \app\common\model\Redis::handler();
            //$redis->del($cache_key);
            if ($redis->get($cache_key)) {
                $res = json_decode($redis->get($cache_key), true);
            } else {
                $res = addons_action('WeChat/MiniPay/getliveinfo', [$arr]);
                $redis->set($cache_key, json_encode($res), 7000);
            }

        }catch(\Exception $e){                 
            $this->error($e->getMessage());         
        }
    
        $data_list = $res['room_info'];      
        $fields = [
            ['roomid', lang('直播间ID')],
            ['name', lang('直播间名称')],
           // ['cover_img', lang('直播间背景图'),'picture'],
            ['start_time', lang('直播开始时间'), 'callback', function ($data) {
                return date('Y-m-d H:i:s', $data);
            }],
            ['end_time', lang('直播结束时间'), 'callback', function ($v) {
                return date('Y-m-d H:i:s', $v);
            }],
            ['anchor_name', lang('主播名')],
            ['live_status', lang('直播间状态'),'status','', [101=>lang('直播中'), lang('未开始'), lang('已结束'),lang('禁播'),lang('暂停'),lang('异常'),lang('已过期')], 'text-center'],
            ['live_type', lang('直播类型'),'status','', [lang('手机直播'), lang('推流')]],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];

        $showdata = array_slice($data_list, ($curPage - 1)*$listRows, $listRows, true);
        //halt($listRows);
        $p = Bootstrap::make($showdata, $listRows, $curpage, $res['total'], false, [
            'var_page' => 'page',
            'path'     => url('operation/live/index'),//这里根据需要修改url
            'query'    => [],
            'fragment' => '',
        ]);
        
        $p->appends($_GET);
        $pages = $p->render();
       
        foreach($showdata as $k=>$v) {
            if(empty($v['goods'])){
                $showdata[$k]['goods_num'] = 0;
            } else {
                $showdata[$k]['goods_num'] = count($v['goods']);
            }
        }
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setRightButton(['ident' => 'goodslist', 'title' => lang('关联商品'), 'href' => ['goodslist', ['id' => '__roomid__']], 'icon' => 'fa fa-list pr5', 'class' => 'btn btn-xs mr5 btn-success ']) 
            ->setRightButtons([
                ['ident'=> 'addgoods', 'title'=>'导入商品','href'=>['goods_approved',['id'=>'__roomid__']],'icon'=>'fa fa-list pr5','class'=>'btn btn-xs mr5 btn-default'],
                ['ident'=> 'edit', 'title'=>'编辑','href'=>['edit',['id'=>'__roomid__']],'icon'=>'fa fa-pencil pr5','class'=>'btn btn-xs mr5 btn-default layeredit'],
                ['ident'=> 'delete', 'title'=>'删除','href'=>['delete',['id'=>'__roomid__']],'icon'=>'fa fa-times pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'],
            ])
            ->setData($showdata)//设置数据
            ->setPages($pages)
            ->replaceRightButton(['goods_num' =>0], '', ['goodslist'])
            ->fetch();//显示
    }


    
    /**
     * 创建直播间
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $datapost = $this->request->post();

            $datapost['startTime'] = strtotime($datapost['startTime']);
            $datapost['endTime'] = strtotime($datapost['endTime']);
            //halt($datapost);
            unset($datapost['__token__']);
            try {
                $res = addons_action('WeChat/MiniPay/createlive', [$datapost]);
               
                if ($res['errcode'] == 0 ) {
                    $datapost['roomid'] = $res['roomId'];
                    $this->saveLiveRoom($datapost, 1);
                    $cache_key = 'livelist_start_0_limit_100';
                    $redis = \app\common\model\Redis::handler();
                    $redis->del($cache_key);
                    return ApiReturn::r(1, [], lang('创建成功'));
                    
                } else {
                    return ApiReturn::r(0, [], lang('创建失败'));
                }
            }catch(\Exception $e){  
                $this->error($e->getMessage());         
            }               

        }
        $this->assign('form_items', $fields);
        return $this->fetch();
          
        
    }

    /**
     * 编辑直播间
     */
    public function edit()
    {
        $roomid = input('id')??1;
        $info = $this->getRoom($roomid);

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $datapost = $this->request->post();

            $datapost['startTime'] = strtotime($datapost['startTime']);
            $datapost['endTime'] = strtotime($datapost['endTime']);
            //halt($datapost);
            unset($datapost['__token__']);
            try {
                $res = addons_action('WeChat/MiniPay/editroom', [$datapost]);
                
                if ($res['errcode'] == 0) {
                    $datapost['roomid'] = $datapost['id'];
                    unset($datapost['id']);
                    $this->saveLiveRoom($datapost, 0);

                    $cache_key = 'livelist_start_0_limit_100';
                    $redis = \app\common\model\Redis::handler();
                    $redis->del($cache_key);
                    return ApiReturn::r(1, [], lang('编辑成功'));   
                    
                } else {
                    return ApiReturn::r(0, [], lang('编辑失败'));
                }
            }catch(\Exception $e){
                $this->error($e->getMessage());         
            }               

        }
        $this->assign('form_items', $fields);
        $this->assign('info', $info);
        return $this->fetch();
    }


    /**
     * 删除直播间
     */
    public function delete()
    {
        $roomid = input('id')??1;
        try {
            $res = addons_action('WeChat/MiniPay/deleteroom', [["id"=>$roomid]]);
     
            if ($res['errcode'] == 0 ) {
                $cache_key = 'livelist_start_0_limit_100';
                $redis = \app\common\model\Redis::handler();
                $redis->del($cache_key);
                return ApiReturn::r(1, [], lang('删除成功'));                
            } else {
                return ApiReturn::r(0, [], lang('删除失败'));
            }
        }catch(\Exception $e){  
            $this->error($e->getMessage());       
        }               
    }



    /**
     * 获取可导入商品
     */
    public function goods_approved()
    {
        $roomid = input('id')??1;
       

        $limit = $request["list_rows"] ?? 15;
        $offset = $request["page"] ?? 1;
        $arr = [
            "offset" => $offset,
            "limit" => $limit,
            "status" => 2
        ];
        $list = addons_action('WeChat/MiniPay/get_goods_approved', [$arr]);
       
        $data_list = $list['goods'];
        foreach($data_list as $k=>$v) {
            $data_list[$k]['id']=$v['goodsId'];
        }
        $fields = [
            ['goodsId', lang('商品ID')],
            //['coverImgUrl', lang('商品图片')],
            ['name', lang('商品名称')],
            ['price', lang('商品价格')],
            ['priceType', lang('价格类型'),'status','', [1=>lang('一口价'), lang('价格区间'), lang('显示折扣价')], 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];

        $showdata = array_slice($data_list, ($offset - 1) * $limit, $limit, true);
        
        $p = Bootstrap::make($showdata, $limit, $offset, $list['total'], false, [
            'var_page' => 'page',
            'path' => url('admin/live_goods/index'),//这里根据需要修改url
            'query' => [],
            'fragment' => '',
        ]);

        $p->appends($_GET);
        $pages = $p->render();
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setData($showdata)//设置数据
        ->setPages($pages)
        ->setRightButtons([
            ['ident'=> 'addgoods', 'title'=>'导入','href'=>['add_goods',['roomid'=>$roomid,'ids'=>'__goodsId__']],'icon'=>'fa fa-check pr5','class'=>'btn btn-xs mr5 btn-default   ajax-get confirm']
        ])
        ->setTopButtons( [['ident' => 'add_goods', 'title' => '批量导入', 'href' =>['add_goods',['roomid'=>$roomid]], 'icon' => 'fa fa-check-circle pr5', 'class' => 'btn btn-sm mr5 btn-default  ajax-post confirm', 'extra' => 'target-form="ids"']])
        ->fetch();//显示
    }

    /**
     * 直播间导入商品
     */
    public function add_goods()
    {
        $ids= input('ids')??1;
        $roomid = input('roomid')??1;
        $arr_ids = $ids;
    
        if (!is_array($ids)) {
            $arr_ids = [$ids];
        } 
        $arr  =[
            'ids'=> $arr_ids,
            'roomId'=>$roomid
        ];
      
        try {
            $res = addons_action('WeChat/MiniPay/room_add_goods', [$arr]);
      
            if ($res['errcode'] == 0 ) {
                $cache_key = 'livelist_start_0_limit_100';
                $redis = \app\common\model\Redis::handler();
                $redis->del($cache_key);
                return ApiReturn::r(1, [], lang('导入成功'));   
            } else {
                return ApiReturn::r(0, [], lang('导入失败'));  
            }
        }catch(\Exception $e){  
            $this->error($e->getMessage());         
        }
    }

    /**
     * 直播间商品列表
     */
    public function goodslist()
    {
        $roomid = input('id')??1;
        $info = $this->getRoom($roomid);
        $showdata = $info['goods'];
        $fields = [
            ['goods_id', lang('商品id')],
            ['name', lang('商品名称')],
            ['price', lang('商品价格（分）')],
            ['price2', lang('商品价格2（分）')],
           // ['cover_img', lang('直播间背景图'),'picture'],
            ['price_type', lang('价格类型'),'status','', [1=>lang('一口价'), lang('价格区间'), lang('显示折扣价')], 'text-center'],
            ['url', lang('商品小程序路径')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        
        return Format::ins()//实例化
        ->hideCheckbox()
        ->addColumns($fields)//设置字段
        ->setTopButtons( [['ident'=> 'addgoods', 'title'=>'导入商品', 'href'=>['goods_approved',['id'=>$roomid]], 'icon'=>'fa fa-plus pr5','class'=>'btn btn-sm mr5 btn-primary ']])
        ->setRightButtons([
            ['ident'=> 'onsale', 'title'=>'上架','href'=>['room_goods_onsale',['roomid'=>$roomid,'goodsid'=>'__goods_id__','onsale'=>1]],'icon'=>'fa fa-pencil pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'],
            ['ident'=> 'offsale', 'title'=>'下架','href'=>['room_goods_onsale',['roomid'=>$roomid,'goodsid'=>'__goods_id__','onsale'=>0]],'icon'=>'fa fa-pencil pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'],
            ['ident'=> 'delete', 'title'=>'删除','href'=>['room_goods_delete',['roomid'=>$roomid,'goodsid'=>'__goods_id__']],'icon'=>'fa fa-times pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'],
        ])
        ->setData($showdata)//设置数据
        ->setPages($pages)
        ->fetch();//显示
    }


    /**
     * 上下架商品
     */
    public function room_goods_onsale()
    {
        $arr= [
            'roomId' => input('roomid')??1,
            'goodsId' => input('goodsid')??1,
            'onSale' => input('onsale')??1,
        ];
       
        try {
            $res = addons_action('WeChat/MiniPay/room_goods_onsale', [$arr]);
            
            if ($res['errcode'] == 0 ) {
                $cache_key = 'livelist_start_0_limit_100';
                $redis = \app\common\model\Redis::handler();
                $redis->del($cache_key);
                return ApiReturn::r(1, [], lang('修改成功'));   
            } else {
                return ApiReturn::r(0, [], lang('修改失败'));
            }
        }catch(\Exception $e){  
            $this->error($e->getMessage());         
        }       
    }


    /**
     * 删除直播间商品
     */
    public function room_goods_delete()
    {
        $arr = [
            'roomId' => input('roomid')??1,
            'goodsId' => input('goodsid')??1
        ];
        try {
            $res = addons_action('WeChat/MiniPay/room_goods_delete', [$arr]);
            
            if ($res['errcode'] == 0 ) {
                $cache_key = 'livelist_start_0_limit_100';
                $redis = \app\common\model\Redis::handler();
                $redis->del($cache_key);
                $this->success(lang('删除成功'), cookie('__forward__'));
            } else {
                $this->error(lang('删除失败'));
            }
        }catch(\Exception $e){  
            $this->error($e->getMessage());         
        }       
    }


    /**
     * 推送直播间商品
     */
    public function room_goods_push()
    {
        $arr = [
            'roomId' => input('roomid')??1,
            'goodsId' => input('goodsid')??1
        ];
        try {
            $res = addons_action('WeChat/MiniPay/room_goods_push', [$arr]);
            
            if ($res['errcode'] == 0 ) {
                $cache_key = 'livelist_start_0_limit_100';
                $redis = \app\common\model\Redis::handler();
                $redis->del($cache_key);
                $this->success(lang('推送成功'), cookie('__forward__'));
            } else {
                $this->error(lang('推送失败'));
            }
        }catch(\Exception $e){  
            $this->error($e->getMessage());         
        }       
    }


    /**
     * 根据返回的直播间列表，房间ID匹配返回单个直播间信息
     */
    public function getRoom($roomid) 
    {
        $cache_key = 'livelist_start_0_limit_100';
        $redis = \app\common\model\Redis::handler();
        $info = $res = [];
        if ($redis->get($cache_key)) {
            $res = json_decode($redis->get($cache_key), true);
        } else {
            $arr = ["start"=>0, "limit"=>100];
            $res = addons_action('WeChat/MiniPay/getliveinfo', [$arr]);
            $redis->set($cache_key, json_encode($res), 7000);
        }
       
        foreach($res['room_info'] as $r){
            if($r['roomid'] == $roomid){
               $info = $r;
            }
        }
        if (!empty($info)) {
            $dbinfo =  Db::name('live_room')->where(['roomid'=>$roomid])->find();
            $info['anchor_wechat'] = $dbinfo['anchor_wechat'];
            $info['cover_img'] = $dbinfo['cover_img'];
            $info['share_img'] = $dbinfo['share_img'];
            $info['feeds_img'] = $dbinfo['feeds_img'];
        }
        return $info;
    }

    /**
     * 保存直播间数据表
     */
    public function saveLiveRoom($data, $isnew = 1)
    {
        $newdata = [];
        foreach ($data as $k=>$v) {
            $new_key = uncamelize($k);
            $newdata[$new_key] = $v;
        }
       
        if ($isnew){
            Db::name('live_room')->insert($newdata);
        } else {
            Db::name('live_room')->where(['roomid'=>$newdata['roomid']])->update($newdata);
        }
    }



    /**
     * 返回直播间成员列表
     */
    public function getRoleList() 
    {
        try {
            $arr = ["role"=>-1,"offset"=>0, "limit"=>30,"keyword"=>''];
            $res = addons_action('WeChat/MiniPay/role_list', [$arr]);
            $cache_key = 'live_role_list_start_0_limit_30';
            $redis = \app\common\model\Redis::handler();
            //$redis->del($cache_key);
            if ($redis->get($cache_key)) {
                $res = json_decode($redis->get($cache_key), true);
            } else {
                $res = addons_action('WeChat/MiniPay/role_list', [$arr]);
                $redis->set($cache_key, json_encode($res), 7000);
            }

        }catch(\Exception $e){                 
            $this->error($e->getMessage());         
        }
    
       
        $data_list = $res['list'];
        $roleset = [
            '超级管理员','管理员','主播','运营者'
        ];
        return $data_list;
    }

}