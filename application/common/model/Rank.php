<?php
namespace app\common\model;

use think\Db;
use think\Model as ThinkModel;


class Rank extends ThinkModel
{
    protected $table = '__RANK__';

    protected $autoWriteTimestamp = false;

}