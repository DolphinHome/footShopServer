<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\command\admin;

use app\admin\model\Upload;
use app\common\controller\Common;
use app\admin\model\Menu as MenuModel;
use app\goods\model\GoodsStockLog;
use app\goods\model\Goods;
use app\user\model\MoneyLog;
use app\user\model\ScoreLog;
use app\user\model\User;
use service\ApiReturn;
use think\Db;

/**
 * 后台公共控制器
 * @package app\admin\controller
 */
class Base extends Common
{
    /**
     * 初始化
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    protected function initialize()
    {
        parent::initialize();

    }


}