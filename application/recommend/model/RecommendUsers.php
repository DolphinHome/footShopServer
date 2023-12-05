<?php

namespace app\recommend\model;

use think\Model as ThinkModel;

/**
 * 推荐系统用户表
 * @package app\recommend\model
 */
class RecommendUsers extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__RECOMMEND_USERS__';

}