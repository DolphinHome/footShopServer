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

namespace app\goods\validate;

use think\Validate;

/**
 * 会员主表验证器
 * @package app\user\validate
 * @author 似水星辰 [2630481389@qq.com]
 */
class Category extends Validate
{
    //定义规则
    protected $rule = [
        'name' => 'require|token',
        'pid' => 'require|number',
    ];

    protected $message = [
        'name.require' => '分类名称必须填写',
        'pid.require' => '上级分类必须选择',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => ['name', 'pid'],
        'edit' => ['name', 'pid']
    ];
}
