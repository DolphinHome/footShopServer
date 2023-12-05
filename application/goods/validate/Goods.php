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
class Goods extends Validate
{
    //定义规则
    protected $rule = [
        'name' => 'require',
        'sn' => 'unique:goods',
        'thumb' => 'require',
        'body' => 'require',
        'is_spec' => 'require',
        'item' => 'require',
        'market_price'=>'require|egt:0.01',
        'shop_price'=>'require|egt:0.01|elt:market_price',
        'member_price'=>'require|egt:0.01|elt:shop_price',
        'cid' => 'require|gt:0',
        'stock' => 'require|gt:0|max:100',
        'brand_id' => 'require|gt:0',


    ];

    protected $message = [
        'name.require' => '商品名称必须填写',
        'sn.unique' => '商品货号已存在',
        'thumb.require' => '商品主图必须上传',
        'body.require' => '商品详情必须填写',
        'is_spec.require' => '请开启规格属性',
        'item.require' => '规格属性必须填写',
        'market_price.require'=>'请填写划线价',
        'shop_price.require'=>'本店价格请填写',
        'member_price.require'=>'会员价格请填写',
        'market_price.egt'=>'划线价价格不小于0.01',
        'shop_price.egt'=>'本店价价格不小于0.01',
        'member_price.egt'=>'会员价价价格不小于0.01',
        'shop_price.elt'=>'本店价必须不大于划线价',
        'member_price.elt'=>'会员价必须不大于本店价',
        'cid.require' => '商品分类必须填写',
        'stock.require' => '商品库存必须填写',
        'brand_id.require' => '商品品牌必须填写',
        'cid.gt' => '商品分类必须填写',
        'stock.gt' => '商品库存必须填写',
        'stock.max' => '商品库存需小于1百万',
        'brand_id.gt' => '商品品牌必须填写',

    ];

    // 定义验证场景
    protected $scene = [
        'add' => [
            'name',
            'body',
            'thumb',
            'sn',
            'is_spec',
//            'member_price',
            'shop_price',
            'cid',
            'stock',
//            'brand_id',
            'body',
            'market_price'
        ],
        'edit' => [
            'name',
            'body',
            'thumb',
            'sn',
            'is_spec',
//            'member_price',
            'shop_price',
            'cid',
            'stock',
//            'brand_id',
            'body',
            'market_price'
        ]
    ];
}
