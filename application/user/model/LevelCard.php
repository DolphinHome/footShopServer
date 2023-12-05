<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\model;

use think\Model as ThinkModel;
use think\Db;
/**
 * 单页模型
 * @package app\user\model
 */
class LevelCard extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__USER_LEVEL_CARD__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getList($type = 1)
    {
    	$list = self::where('status = 1 and type = '.$type)->order('level')->select()->toArray();
    	foreach ($list as $key => &$value) {
//    		$rights = Db::name('user_level_new_rights')
//						->alias('r')
//						->join('user_level_with_rights w','r.id = w.rights_id')
//						->where('w.level_id',$value['id'])
//						->order('w.sort','asc')
//						->field('r.id,r.name,r.type')
//						->select();
//    		$list[$key]['rights'] = empty($rights)?[]:$rights;
            $value['bg_image'] = get_file_url($value['bg_image']);
            $value['vip_image'] = get_file_url($value['vip_image']);
    	}
    	return $list;
    }

    public function getInfo($id = 0){
    	if(empty($id)){
    		return array();
    	}
    	$info = self::where('status = 1 and id='.$id)->find();
    	if($info){
    		$rights = Db::name('user_level_new_rights')
						->alias('r')
						->join('user_level_with_rights w','r.id = w.rights_id')
						->where('w.level_id',$id)
						->order('w.sort','asc')
						->field('r.id,r.name,r.type')
						->select();
    		$info['rights'] = empty($rights)?[]:$rights;
    	}
    	return $info;
    }


    public function getInfoForLevel($level= 0,$type= 1){
    	$info = self::where([
    	    'status'=> 1,
            'level'=> $level,
            'type'=> $type
        ])->find();
    	if($info){
    		$rights = Db::name('user_level_new_rights')
						->alias('r')
						->join('user_level_with_rights w','r.id = w.rights_id')
						->where('w.level_id',$info['id'])
						->order('w.sort','asc')
						->field('r.id,r.name,r.type')
						->select();
    		$info['rights'] = empty($rights)?[]:$rights;
    	}
    	return $info;
    }











}