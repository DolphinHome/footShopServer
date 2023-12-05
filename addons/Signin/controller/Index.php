<?php

namespace addons\signin\controller;

use app\common\controller\Common;
use addons\Signin\model\Date;
use think\Db;
use app\user\model\User;
use app\user\model\ScoreLog;

/**
 * Class Index签到执行页面
 * @package addons\signin\controller
 */
class Index extends Common
{


    public function index($userId)
    {
        $config = addons_config('signin');
        $date = $this->request->request('date', date("Y-m-d"), "trim");
        $time = strtotime($date);

        $lastdata = \addons\Signin\model\Signin::where('user_id', $userId)->order('createtime', 'desc')->find();
        $successions = $lastdata && $lastdata['createtime'] > Date::unixtime('day', -1) ? $lastdata['successions'] : 0;
        $signin = \addons\Signin\model\Signin::where('user_id', $userId)->whereTime('createtime', 'today')->find();

        $calendar = new \addons\signin\library\Calendar();
        $list = \addons\Signin\model\Signin::where('user_id', $userId)
            ->field('id,createtime')
            ->whereTime('createtime', 'between', [date("Y-m-1", $time), date("Y-m-1", strtotime("+1 month", $time))])
            ->select();
        foreach ($list as $index => $item) {
            $calendar->addEvent(date("Y-m-d", $item->createtime), date("Y-m-d", $item->createtime), "", false, "signed");
        }
        $successions++;
        $data = [
            'fillupscore' => $config['fillupscore'],  //补签消耗积分
            'isfillup' => $config['isfillup'],
            'calendar' => $calendar,
            'date' => $date,
            'successions' => $successions,
            'signin' => $signin,
            'signinscore' => $config['signinscore'],
            'title' => '每日签到',
        ];
        return $data;
    }

    /**
     *立即签到
     */
    public function dosign($userId)
    {
        $config = addons_config('signin');
        $score = $config['signinscore'];
        $lastdata = \addons\Signin\model\Signin::where('user_id', $userId)->order('createtime', 'desc')->find();
        $successions = $lastdata && $lastdata['createtime'] > Date::unixtime('day', -1) ? $lastdata['successions'] : 0;
        $signin = \addons\Signin\model\Signin::where('user_id', $userId)->whereTime('createtime', 'today')->find();
        if ($signin) {
            return $msg = [
                'code' => 2,
                'msg' => '今天已签到,请明天再来!'
            ];
        } else {
            $successions++;
//                $score = isset($signdata['s' . $successions]) ? $signdata['s' . $successions] : $signdata['sn'];
            //累计签到赠送积分
            $add_day = $config['add_day'];
            $totalscore = $config['totalscore'];

            if ($add_day == $successions) {
                $score = $totalscore;
            }
            Db::startTrans();
            try {
                \addons\Signin\model\Signin::create(['user_id' => $userId, 'successions' => $successions, 'createtime' => time()]);
                ScoreLog::change($userId, $score, 1, "连续签到{$successions}天");
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                return $msg = [
                    'code' => 0,
                    'msg' => '签到失败,请稍后重试'
                ];
            }
            return $msg = [
                'code' => 1,
                'msg' => '签到成功!连续签到' . $successions . '天!获得' . $score . '积分'
            ];
        }
        return $msg = [
            'code' => 1,
            'msg' => '请求错误'
        ];
    }

    /**
     * Notes:签到补签
     */
    public function fillup($userId, $score,$date)
    {
        $time = strtotime($date);
        $config = addons_config('signin');
        if (!$config['isfillup']) {
            return $msg=[
                'code'=>0,
                'msg'=>'暂未开启签到补签'
            ];
        }
        if ($time > time()) {
            return $msg=[
                'code'=>0,
                'msg'=>'无法补签未来的日期'
            ];
        }
        if ($config['fillupscore'] > $score) {
            return $msg=[
                'code'=>0,
                'msg'=>'你当前积分不足'
            ];
        }
        $days = Date::span(time(), $time, 'days');
        if ($config['fillupdays'] < $days) {
            return $msg=[
                'code'=>0,
                'msg'=>"只允许补签{$config['fillupdays']}天的签到"
            ];
        }
        $count = \addons\Signin\model\Signin::where('user_id', $userId)
            ->where('type', 'fillup')
            ->whereTime('createtime', 'between', [Date::unixtime('month'), Date::unixtime('month', 0, 'end')])
            ->count();

        if ($config['fillupnumsinmonth'] <= $count) {
            return $msg=[
                'code'=>0,
                'msg'=>"每月只允许补签{$config['fillupnumsinmonth']}次"
            ];
        }
        Db::name('signin')->whereTime('createtime', 'd')->select();
        $signin = \addons\Signin\model\Signin::where('user_id', $userId)
            ->where('type', 'fillup')
            ->whereTime('createtime', 'between', [$date, date("Y-m-d 23:59:59", $time)])
            ->count();
        if ($signin) {
            return $msg=[
                'code'=>0,
                'msg'=>"该日期无需补签到"
            ];
        }
        $successions = 1;
        $prev = $signin = \addons\Signin\model\Signin::where('user_id', $userId)
            ->whereTime('createtime', 'between', [date("Y-m-d", strtotime("-1 day", $time)), date("Y-m-d 23:59:59", strtotime("-1 day", $time))])
            ->find();
        if ($prev) {
            $successions = $prev['successions'] + 1;
        }
        Db::startTrans();
        try {

            //寻找日期之后的
            $nextList = \addons\Signin\model\Signin::where('user_id', $userId)
                ->where('createtime', '>=', strtotime("+1 day", $time))
                ->order('createtime', 'asc')
                ->select();
            foreach ($nextList as $index => $item) {
                //如果是阶段数据，则中止
                if ($index > 0 && $item->successions == 1) {
                    break;
                }
                $day = $index + 1;
                if (date("Y-m-d", $item->createtime) == date("Y-m-d", strtotime("+{$day} day", $time))) {
                    $item->successions = $successions + $day;
                    $item->save();
                }
            }
            \addons\Signin\model\Signin::create(['user_id' => $userId, 'type' => 'fillup', 'successions' => $successions, 'createtime' => $time + 43200]);
            ScoreLog::change($userId, -$config['fillupscore'], 2,  '签到补签');
            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            return $msg=[
                'code'=>0,
                'msg'=>'补签失败,请稍后重试'
            ];
        } catch (Exception $e) {
            Db::rollback();
            return $msg=[
                'code'=>0,
                'msg'=>'补签失败,请稍后重试'
            ];
        }
        return $msg=[
            'code'=>1,
            'msg'=>'补签成功'
        ];
    }

    /**
     * 排行榜
     */
    public function rank()
    {
        $data = \addons\Signin\model\Signin::with(["user"])
            ->where("createtime", ">", Date::unixtime('day', -1))
            ->field("user_id,MAX(successions) AS days")
            ->group("user_id")
            ->order("days", "desc")
            ->limit(10)
            ->select();
        foreach ($data as $index => $datum) {
            $datum->getRelation('user')->visible(['id', 'user_name', 'user_nickname', 'head_img']);
        }

        return $msg=[
            'code'=>1,
            'msg'=>'操作成功',
            'ranklist'=>$data
        ];
    }

}
