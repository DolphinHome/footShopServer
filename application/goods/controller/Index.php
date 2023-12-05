<?php
// +----------------------------------------------------------------------
// | 易倍增CRM
// +----------------------------------------------------------------------
// | 版权所有 2008~2018 郑州易倍增软件科技有限公司 [ http://www.zzebz.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.zzebz.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\goods\controller;

use think\Controller;
use think\Db;

/**
 * 商品分享H5页面
 * Class Index
 * @author jxy <415782189@qq.com>
 */
class Index extends Controller
{
    public function index(){
        $param = input('param.');
        $this->assign('param',$param);
        return $this->fetch();
    }
}
