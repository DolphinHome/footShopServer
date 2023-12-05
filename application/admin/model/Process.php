<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\model;

use app\admin\model\Role as RoleModel;
use app\admin\model\Quick;
use think\Model as ThinkModel;
use think\Exception;
use service\Tree;

/**
 * 业务流程
 * @package app\admin\model
 */
class Process extends ThinkModel
{	
    // 设置当前模型对应的完整数据表名称
    protected $table = '__PROCESS_CONFIG__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = false;

    protected $type = [
        'detail'    =>  'json',
        'synopsis'     =>  'json',
    ];

    public static function get_process_list($where = '1 = 1')
    {
        $data = self::where('status',1)->where($where)->select()->each(function($item){
                
                $item['url'] = get_file_url($item['url']);
                // $item['detail'] = json_decode($item['detail']);
                // $item['synopsis'] = json_decode($item['synopsis']);
                return $item;
            });
        return $data;
    }

    public static function add_process($data){
    	return self::insert($data);
    }


}