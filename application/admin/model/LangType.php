<?php
/*
 * 语言包类型
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-04-26 13:45:16
 * @LastEditors: wangph
 * @LastEditTime: 2021-05-04 18:29:41
 */

namespace app\admin\model;

use think\Model as ThinkModel;

/**
 * 语言包类型
 * @package app\admin\model
 */
class LangType extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__LANG_TYPE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 定义的语音类型
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-30 10:21:07
     */
    public static function langTypeArr()
    {
        $langArr = lang_array();
        $langTypeArr = array_flip($langArr);
        return $langTypeArr;
    }

    /**
     * 获取启用的语言包类型
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-05-04 16:53:41
     */
    public static function langTypeAble()
    {
        $res = $data = [];
        $data = self::where(['status'=>1])->order('id asc')->select();
        if (count($data) <1) {
            return [];
        }

        return $data;
    }
}
