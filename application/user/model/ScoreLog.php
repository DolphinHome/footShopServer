<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\model;

use think\Model as ThinkModel;

/**
 * 积分变动记录表
 *
 * @author 似水星辰 [2630481389@qq.com]
 */
class ScoreLog extends ThinkModel
{

    protected $table = "__USER_SCORE_LOG__";
    //记录类型。你有新的类型，请添加到这里
    public static $types = [
        '1' => '签到赠送积分',
        '2' => '商城赠送积分',
        '3' => '积分商城兑换',
        '5' => '商城抵扣',
        '6' => '管理员操作',
        '7' => '注册赠送积分',
        '8' => '退单返回积分'
    ];
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 会员积分变动
     * @param int $user_id 会员ID
     * @param int $score 操作数值 负数就是减少
     * @param int $type 类型
     * @param string $remark 备注
     * @param int $count_score 传入这个数计入到累计收益 影响等级
     * @return boolean
     * @throws \Exception
     */
    public static function change($user_id, $score, $type = 1, $remark = '', $ordeNo = '', $count_score = 0)
    {
        //0无变动，不记录
        if ( 0 == $score) {
            return true;
        }
        self::startTrans();
        try {
            $before_score = \app\user\model\User::where('id', $user_id)->value('score');
            $after_score = bcadd($before_score, $score, 2);

            //如果变动结果小于0 则返回失败
            if ($after_score < 0) {
                throw new \Exception(lang('变动后收益小于0'));
            }
            if ($score < 0) {
                $map[] = ['score', '>=', abs($score)];
            }
            $ret = \app\user\model\User::where('id', $user_id)->where($map)->update([
                'score' => $after_score
            ]);
            if ($ret === false) {
                throw new \Exception(lang('会员消费积分更新失败'));
            }


            $data = array(
                'user_id' => $user_id,
                'change_score' => $score,
                'before_score' => $before_score,
                'after_score' => $after_score,
                'change_type' => $type,
                'remark' => $remark ? $remark : self::$types[$type],
                'order_no' => $ordeNo
            );

            $result = self::create($data);

            if (!$result) {
                throw new \Exception(lang('会员积分变更失败'));
            }
            // 提交事务
            self::commit();
        } catch (\Exception $e) {
            // 回滚事务
            self::rollback();
            throw new \Exception($e->getMessage());

            return false;
        }
        return true;
    }

    /**
     * 获取列表
     * @param $data
     * @return \think\Paginator
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/3 18:11
     */
    public static function getList($data)
    {
        $user_id = $data['user_id'];
        $type = $data['type'];
        $starTime = $data['start_time'];
        $endTime = $data['end_time'];
        $where = [];
        //不显示变动为0的数据，其实不应该写入
        $where[] = ['change_score', '<>', 0];
        $where[] = ['user_id', '=', $user_id];
        if ($type == 1) {
            $where[] = ['change_score', '>', 0];
        }
        if ($type == 2) {
            $where[] = ['change_score', '<', 0];
        }
        if ($starTime && $endTime) {
            $where[] = ["create_time", "between", [strtotime($starTime), strtotime($endTime)]];
        }
        $res = self::where($where)->order("aid desc")->paginate();
        return $res;
    }

}
