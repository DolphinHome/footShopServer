<?php
/**
 * Created by PhpStorm.
 * User: I5
 * Date: 2020/11/18
 * Time: 17:54
 */

namespace app\user\model;


class WithAccount extends \think\Model
{

    protected $table = '__WITHDRAW_ACCOUNT__';


    // 自动写入时间戳
    protected $autoWriteTimestamp = true;


}