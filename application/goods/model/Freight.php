<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\goods\model;

use think\Model as ThinkModel;
use think\Db;

/**
 * 单页模型
 * @package app\goods\model
 */
class Freight extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__GOODS_FREIGHT__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 关联配送模板区域及运费
     * @return \think\model\relation\HasMany
     */
    public function rule()
    {
        return $this->hasMany('FreightRule');
    }

    /**
     * 运费模板详情
     * @param $freight_id
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function detail($freight_id)
    {
        return self::get($freight_id, ['rule']);
    }

    /**
     * 编辑记录
     * @param $data
     * @return bool|int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function post_update($data)
    {
        if (!isset($data['freight']) || empty($data['freight'])) {
            $this->error = lang('请选择可配送区域');
            return false;
        }
        $save = [];
        $connt = count($data['freight']['region']);
        for ($i = 0; $i < $connt; $i++) {
            $save[] = [
                'region' => $data['freight']['region'][$i],
                'first' => $data['freight']['first'][$i],
                'first_fee' => $data['freight']['first_fee'][$i],
                'additional' => $data['freight']['additional'][$i],
                'additional_fee' => $data['freight']['additional_fee'][$i]
            ];
        }
        return $this->rule()->saveAll($save);
    }


    /**
     * 删除记录
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove($freight_id)
    {
        // 判断是否存在商品
        /*if ($goodsCount = (new \app\goods\model\Goods)->where(['freight_template_id' => $freight_id])->count()) {
            $this->error = '该模板被' . $goodsCount . '个商品使用，不允许修改和删除';
            return false;
        }*/
        return $this->rule()->where(['freight_id' => $freight_id])->delete();
    }

    /**
     * 计算配送费用
     * @param $total_num 购买商品数量
     * @param $total_weight 购买商品重量
     * @param $city_id 城市id
     * @return float|int|mixed
     */
    public function calcTotalFee($total_num, $total_weight, $city_id)
    {
        $rule = [];  // 当前规则
        foreach ($this['rule'] as $item) {
            if (in_array($city_id, explode(',', $item['region']))) {
                $rule = $item;
                break;
            }
        }
        // 商品总数量or总重量
        $total = $this['method'] == 1 ? $total_weight : $total_num;

        if ($total <= $rule['first']) {
            return number_format($rule['first_fee'], 2, '.', '');
        }

        // 续件or续重 数量
        $additional = $total - $rule['first'];

        if ($additional <= $rule['additional']) {
            return number_format($rule['first_fee'] + $rule['additional_fee'], 2, '.', '');
        }
        // 计算续重/件金额
        if ($rule['additional'] < 1) {
            // 配送规则中续件为0
            $additionalFee = 0.00;
        } else {
            $additionalFee = bcdiv($rule['additional_fee'], $rule['additional'], 2) * $additional;
        }
        return number_format($rule['first_fee'] + $additionalFee, 2, '.', '');
    }

    /**
     * 验证用户收货地址是否存在运费规则中
     * @param $city_id
     * @return bool
     */
    public function checkAddress($city_id, $freight_id)
    {
        $array = Db::name('goods_freight_rule')->where('freight_id', $freight_id)->field('region')->select();
        $cityIds = explode(',', implode(',', array_column($array, 'region')));
        return in_array($city_id, $cityIds);
    }

    /**
     * 根据运费组合策略 计算最终运费
     * @param $allExpressPrice
     * @return float|int|mixed
     */
    public static function freightRule($allExpressPrice)
    {
        $freight_rule = Setting::getItem('trade')['freight_rule'];
        $expressPrice = 0.00;
        switch ($freight_rule) {
            case '10':    // 策略1: 叠加
                $expressPrice = array_sum($allExpressPrice);
                break;
            case '20':    // 策略2: 以最低运费结算
                $expressPrice = min($allExpressPrice);
                break;
            case '30':    // 策略3: 以最高运费结算
                $expressPrice = max($allExpressPrice);
                break;
        }
        return $expressPrice;
    }
}
