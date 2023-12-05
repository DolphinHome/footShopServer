<?php
/*
 * @Descripttion:
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-04-20 11:52:25
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-28 15:25:43
 */
// +----------------------------------------------------------------------
// | LwwanPHP
// +----------------------------------------------------------------------
// | 版权所有 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 http://www.sitejs.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------

namespace app\integral\model;

use think\Model as ThinkModel;
use service\Tree;

/**
 * 单页模型
 * @package app\user\model
 */
class Category extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_INTEGRAL_CATEGORY__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取树形分类
     * @param int $id 需要隐藏的分类id
     * @param string $default 默认第一个分类项，默认为-顶级分类，如果为false则不显示，也可传入其他名称
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public static function getMenuTree($id = 0, $default = '')
    {
        $result[0] = lang('顶级分类');
        $where[] = ['status' , 'egt', 0];

        // 排除指定菜单及其子菜单
        if ($id !== 0) {
            $hide_ids    = array_merge([$id], self::getChildsId($id));
            $where[] = ['id','not in', $hide_ids];
        }

        // 获取分类
        $cates = Tree::config(['title' => 'name'])->toList(self::where($where)->order('pid,id')->column('id,pid,name'));
        foreach ($cates as $cate) {
            $result[$cate['id']] = $cate['title_display'];
        }

        // 设置默认分类项标题
        if ($default != '') {
            $result[0] = $default;
        }

        // 隐藏默认分类项
        if ($default === false) {
            unset($result[0]);
        }

        return $result;
    }
}
