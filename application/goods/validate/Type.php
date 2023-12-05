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
class Type extends Validate
{
    //定义规则
    protected $rule = [
        'name' => 'require|unique:goods_type',
    ];

    protected $message = [
        'name.require' => '名称必须填写',
        'name.unique' => '名称已存在',
    ];

    // 定义验证场景
    protected $scene = [
        'add' => ['name'],
        'edit' => ['name']
    ];
}
