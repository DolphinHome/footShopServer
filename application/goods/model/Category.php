<?php
// +----------------------------------------------------------------------
// | LwwanPHP
// +----------------------------------------------------------------------
// | 版权所有 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 http://www.sitejs.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------

namespace app\goods\model;

use think\Model as ThinkModel;
use service\Tree;

/**
 * 单页模型
 * @package app\user\model
 */
class Category extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_CATEGORY__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static function getTree($id = 0, $default = '')
    {
        $result[0] = lang('顶级分类');
        $where[] = ['status', 'egt', 0];

        // 排除指定菜单及其子菜单
        if ($id !== 0) {
            $hide_ids = array_merge([$id], self::getChildsId($id));
            $where[] = ['id', 'not in', $hide_ids];
        }

        // 获取分类
        $cates = Tree::config(['title' => 'name'])->toList(self::where($where)->order('pid,id')->column('id,pid,name'));
        foreach ($cates as $cate) {
            $str = '';
            if ($cate['level'] > 1) {
                $str = '|' . str_repeat('-', $cate['level']);
            }
            $result[$cate['id']] = $str . $cate['name'];
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
    /**
     * 获取树形分类
     * @param int $id 需要隐藏的分类id
     * @param string $default 默认第一个分类项，默认为“顶级分类”，如果为false则不显示，也可传入其他名称
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public static function getMenuTree($id = 0, $default = '')
    {
        $result[0] = lang('顶级分类');
        $where[] = ['status', 'egt', 0];

        // 排除指定菜单及其子菜单
        if ($id !== 0) {
            $hide_ids = array_merge([$id], self::getChildsId($id));
            $where[] = ['id', 'not in', $hide_ids];
        }

        // 获取分类
        $cates = Tree::config(['title' => 'name'])->toList(self::where($where)->order('pid,id')->column('id,pid,name'));
        foreach ($cates as $cate) {
            $result[$cate['id']] = trim($cate['title_display'], "-");
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

    /**
     * 获取所有子菜单id
     * @param int $pid 父级id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return array
     */
    public static function getChildsId($pid = 0)
    {
        $ids = self::where('pid', $pid)->column('id');
        foreach ($ids as $value) {
            $ids = array_merge($ids, self::getChildsId($value));
        }
        return $ids;
    }

    /**
     * 获取所有父菜单id
     * @param int $id 菜单id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return array
     */
    public static function getParentsId($id = 0)
    {
        $pid = self::where('id', $id)->value('pid');
        $pids = [];
        if ($pid != 0) {
            $pids[] = $pid;
            $pids = array_merge($pids, self::getParentsId($pid));
        }
        return $pids;
    }

    /**
     * 获取所有一级菜单
     * @param int $id 菜单id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return array
     */
    public static function getParentsCate($id = 0)
    {
        $pid = self::where('pid', $id)->column('id,name');
        /*$pids = [];
        if ($pid != 0) {
            $pids[] = $pid;
            $pids = array_merge($pids, self::getParentsId($pid));
        }*/
        return $pid;
    }

    /*
     * 获取商品分类的全路径
     *@param int $id 第三级分类id
     */
    public static function getCateStr($id)
    {
        $child1 = $child = false;
        $child2 = self::where(['id' => $id])->field("pid,name")->find();
        if ($child2) {
            $child1 = self::where(['id' => $child2['pid']])->field("pid,name")->find();
            if ($child1) {
                $child = self::where(['id' => $child1['pid']])->field("pid,name")->find();
            }
        }
        if ($child && $child1 && $child2) {
            return $child['name'] . '/' . $child1['name'] . '/' . $child2['name'];
        } elseif ($child2) {
            return $child2['name'];
        } else {
            return false;
        }
    }
}
