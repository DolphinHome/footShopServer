<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\goods\model;

use service\Tree;
use think\Model as ThinkModel;
use think\Db;

/**
 * 单页模型
 * @package app\goods\model
 */
class FreightRule extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_FREIGHT_RULE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    protected $append = ['region_content'];

    static $regionAll;
    static $regionTree;

    /**
     * 可配送区域
     * @param $value
     * @param $data
     * @return string
     */
    public function getRegionContentAttr($value, $data)
    {
        // 当前区域记录转换为数组
        $regionIds = explode(',', $data['region']);
        $city_all = Db::name('china_area')->where('level',2)->count();
        if (count($regionIds) === $city_all) return '全国';

        // 所有地区
        $area_all = cache('area_all');
        if (!$area_all) {
            $area_all = Db::name('china_area')->column('id,pid,name,level');
            cache('area_all', $area_all);
        }
        // 格式化地区
        $area = cache('area_region');
        if(!$area){
            $area = Tree::config(['child' => 'city'])->toLayer($area);
            cache('area_region', $area);
        }

        self::$regionAll = $area_all;
        self::$regionTree = $area;

        // 将当前可配送区域格式化为树状结构
        $alreadyTree = [];
        foreach ($regionIds as $regionId)
            $alreadyTree[self::$regionAll[$regionId]['pid']][] = $regionId;
        $str = '';
        foreach ($alreadyTree as $provinceId => $citys) {
            $str .= self::$regionTree[$provinceId]['name'];
            if (count($citys) !== count(self::$regionTree[$provinceId]['city'])) {
                $cityStr = '';
                foreach ($citys as $cityId)
                    $cityStr .= self::$regionTree[$provinceId]['city'][$cityId]['name'];
                $str .= ' (<span class="am-link-muted">' . mb_substr($cityStr, 0, -1, 'utf-8') . '</span>)';
            }
            $str .= '、';
        }
        return mb_substr($str, 0, -1, 'utf-8');
    }

}