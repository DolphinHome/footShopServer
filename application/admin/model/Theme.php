<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\model;

use think\Model as ThinkModel;;

/**
 * 主题模型
 * @package app\admin\model
 */
class Theme extends ThinkModel
{
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
	
	/*
	 * 获取并过滤模板文件列表
	 * @param $data array 模板文件列表
	 * @param $suffix string 模板后缀
	 * @param $mark string模板标识
	 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return array
     */
	public function get_html_templets($data, $mark = 'list', $suffix = '.html'){
		foreach($data as $k=>$t){
			if(strpos($t,$suffix) === false || strpos($t,$mark) === false){
				unset($data[$k]);
			}
		}
		return $data;
	}
}