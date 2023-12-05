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
class GoodsTypeAttr extends ThinkModel {

    public function GoodsTypeAttribute(){
        return $this->hasOne('GoodsTypeAttribute','attr_id','attr_id');
    }
}
