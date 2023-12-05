<?php
/**
 * Created by PhpStorm.
 * User: I5
 * Date: 2020/12/3
 * Time: 15:47
 */

namespace app\operation\model;

use think\Model as ThinkModel;


class ArticleReportType extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__OPERATION_ARTICLE_REPORT_TYPE__';


    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

}