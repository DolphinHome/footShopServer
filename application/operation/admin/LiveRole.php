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

/**
 * 微信小程序直播成员管理
 * @package app\operation\admin
 */
class LiveRole extends Base
{

    /**
     * 临时素材列表
     */
    public function index()
    {
        $data = [];
        $curPage = input('page') ? input('page') : 1;
        $listRows = 10;//每页10行记录
        try {
            $res = addons_action('WeChat/MiniPay/role_list', [['role'=>-1,'offset'=>0,'limit'=>30,'keyword'=>'']]);
            $cache_key = 'live_role_list_start_0_limit_100';
            $redis = \app\common\model\Redis::handler();
            //$redis->del($cache_key);
            if ($redis->get($cache_key)) {
                $res = json_decode($redis->get($cache_key), true);
            } else {
                $res = addons_action('WeChat/MiniPay/role_list', [['role'=>-1,'offset'=>0,'limit'=>30,'keyword'=>'']]);
                $redis->set($cache_key, json_encode($res), 7000);
            }

        }catch(\Exception $e){                 
            $this->error($e->getMessage());         
        }
    
       
        $data_list = $res['list'];
        //halt($data_list);
        $roleset = [
            '超级管理员','管理员','主播','运营者'
        ];
        foreach($data_list as $k=>$v){
            foreach($v['roleList'] as $vv) {
                $data_list[$k]['rolelist_name'] .= $roleset[$vv].'+';  
            }
            $data_list[$k]['rolelist_name'] = rtrim($data_list[$k]['rolelist_name'],'+');
        }

        $fields = [
            ['nickname', lang('昵称')],
            ['headingimg', lang('头像'),'picture'],
            ['updateTimestamp', lang('更新时间'), 'callback', function ($data) {
                return date('Y-m-d', $data) . ' 00:00:00';
            }],
            ['rolelist_name', lang('成员身份')],
            ['username', lang('微信号')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];

        $showdata = array_slice($data_list, ($curPage - 1)*$listRows, $listRows, true);
        //halt($listRows);
        $p = Bootstrap::make($showdata, $listRows, $curpage, $res['total'], false, [
            'var_page' => 'page',
            'path'     => url('operation/live_role/index'),//这里根据需要修改url
            'query'    => [],
            'fragment' => '',
        ]);
        
        $p->appends($_GET);
        $pages = $p->render();

        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            //->setTopButtons($this->top_button)
            ->setData($showdata)//设置数据
            ->setPages($pages)
            ->fetch();//显示
    }


    /**
     * 上传临时素材
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $real_path = "{$_SERVER['DOCUMENT_ROOT']}";
            $name = $this->request->param("name", "");
            $filename = $real_path . '/uploads/sucai/' . $_FILES['media']['name'];
            if (!is_dir($real_path . '/uploads/sucai/')) {
                mkdir($real_path . '/uploads/sucai/', 0777, true);
            }
            try {
                move_uploaded_file($_FILES['media']['tmp_name'], $filename);
                $upres = $this->uploadPath($filename);
                $res = addons_action('WeChat/MiniPay/addmedia', $filename);
                if (isset($res['media_id'])) {
                    if ($upres['id']) {
                        $media_data = [
                            'upload_id' => $upres['id'],
                            'type' => $res['type'],
                            'media_id' => $res['media_id'],
                            'create_time' => time(),
                            'is_temp' => 1,
                            'name' => $name
                        ];
                        Db::name('live_media')->insert($media_data);
                    }
                    $this->success(lang('上传成功'), cookie('__forward__'));
                } else {
                    $this->error($res['errmsg']);
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->assign('form_items', $fields);
        return $this->fetch();
    //     <select class="select2 form-control select2-hidden-accessible" id="anchorName" name="anchorName"
    //     data-select2-id="typeid" tabindex="-1" aria-hidden="true">
    //     {volist name="roles" id="v"}
    //     <option value="{$v.nickname}">{$v.nickname}</option>
    //     {/volist}
    // </select>
    }
}
