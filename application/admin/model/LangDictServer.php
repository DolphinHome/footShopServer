<?php
/*
 * 语言包
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-04-26 13:45:16
 * @LastEditors: wangph
 * @LastEditTime: 2021-05-04 14:56:39
 */

namespace app\admin\model;

use think\Model as ThinkModel;

/**
 * 语言包字典 模型
 * @package app\admin\model
 */
class LangDictServer extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__LANG_DICT_SERVER__';

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
}
