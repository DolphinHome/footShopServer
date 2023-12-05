<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\model;

use think\Model as ThinkModel;

/**
 * 用户签到模型
 * Class Signin
 * @package app\usermodel
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/3 17:57
 */
class Signin extends ThinkModel
{

    protected $table = "__USER_SIGNIN__";

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 执行当天签到   也就是点击签到调用的接口
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/11 16:17
     * @param int $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function userSignin($uid = 0,$type)
    {
        if($uid == 0){
            $this->error(lang('参数错误'));
        }
        //获取今日签到数据
        $todayData = $this->todayData($uid);
        if ($todayData['is_sign'] == 1) {
            return ['status'=>2,'days'=>$todayData['days'],'date'=>date("Y-m-d",strtotime($todayData['create_time'])),'score'=>$todayData['integral'],'msg'=>lang('已连续签到').$todayData['days'].lang('天')];
        } else {
            self::startTrans();
            try{
                $data = $this->getInsertData($uid,$type);
                $data['user_id'] = $uid;
                $id = self::insertGetId($data);
                if (!$id){
                    exception(lang('签到失败'));
                }
                //组装签到记录数据
                $score = $this->getTodayScores($data['days']);
                $result = self::where('id',$id)->update(['integral'=>$score]);
                $res = \app\user\model\ScoreLog::change($uid, $score, 1);
                if (!$res){
                    exception(lang('签到失败'));
                }

                // 提交事务
                self::commit();
            } catch (\Exception $e) {
                // 回滚事务
                self::rollback();
                $this->error = $e->getMessage();
                return false;
            }
            return ['status'=>1,'days'=>$data['days'],'date'=>date("Y-m-d",strtotime($todayData['create_time'])),'score'=>$score,'msg'=>lang('已连续签到').$data['days'].lang('天')];
        }

    }

    /**
     * 用户当天签到的数据
     * @param int $uid
     * @return array|\PDOStatement|string|ThinkModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/11 16:22
     */
    public function todayData($uid = 0)
    {
        if($uid == 0){
            $this->error = lang('参数错误');
            return fasle;
        }
        $time = time();
        $start_stime = strtotime(date('Y-m-d 0:0:0', $time)) - 1;
        $end_stime = strtotime(date('Y-m-d 23:59:59', $time)) + 1;
        return self::where("user_id", $uid)->where("create_time > $start_stime and create_time < $end_stime")->find();
    }

    /**
     * 返回每次签到要插入的数据
     * @param int $uid 用户id
     * @return array
     *  'days'  天数
     *  'is_sign'  是否签到,用1表示已经签到,
     *  'create_time'  签到时间,
     * 
     */

    public function getInsertData($uid = 0)
    {
        // 昨天的连续签到天数
        $start_time = strtotime(date('Y-m-d 0:0:0', time() - 86400)) - 1;
        $end_time = strtotime(date('Y-m-d 23:59:59', time() - 86400)) + 1;
        $days = self::where("user_id = $uid and create_time > $start_time and create_time < $end_time")->value('days');
        if ($days) {
            $days++;
            if($type == 1){
                $num = 7;       //当月天数
            }else{
                $num = date("t");
            }            
            if ($days > $num) { //7代表最大签到天数
                $days = 1;
            }
        } else {
            $days = 1;
        }
        return array(
            'days' => $days,
            'is_sign' => 1,
            'create_time' => time()
        );
    }

    /**
     * 积分规则，返回连续签到的天数对应的积分
     * @param int $days 当天应该得的分数
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/11 16:30
     * @return int 积分
     */
    protected function getTodayScores($days)
    {
        if($days > 7 ){
            $days = intval($days % 7);
        }

        switch ($days){
            case 1:
                return 10;
                break;
            case 2:
                return 20;
                break;
            case 3:
                return 30;
                break;
            case 4:
                return 40;
                break;
            case 5:
                return 50;
                break;
            case 6:
                return 88;
                break;
            case 7:
                return 100;
                break;
            default:
                return 0;
        }
    }


    /**
     * 获取当月签到的天数
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/11 16:26
     * @param $uid
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getMonthSign($uid = 0)
    {
        $time = time();
        $year = date('Y', $time);
        $month = date('m', $time);
        $day = date("t", strtotime("$year-$month"));
        $start_stime = strtotime("$year-$month-1 0:0:0") - 1;
        $end_stime = strtotime("$year-$month-$day 23:59:59") + 1;
        $list = self::field('create_time')->where("user_id = $uid and create_time > $start_stime and create_time < $end_stime")->order('create_time asc')->select();
        foreach ($list as $key => $value) {
            $list[$key] = date("d", $value["create_time"]);
        }

        return $list;
    }
}