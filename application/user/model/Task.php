<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\user\model;

use think\Model as ThinkModel;
use app\user\model\User as UserModel;
use app\user\model\ScoreLog as ScoreModel;
use think\Db;

/**
 * 单页模型
 * @package app\user\model
 */
class Task extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__USER_TASK__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    /**
     * @param $uid 用户ID
     * @param $sign 任务类型标识
     * 会员做任务获得相应的奖励
     * **/
    public static function doTask($uid, $sign)
    {
        Db::startTrans();
        try {
            $task = self::where(['sign'=>$sign])->find();
            $user = Db::name('user')->get($uid);
            if (!$task) {
                exception(lang('任务不存在'));
            }
            if (!$user) {
                exception(lang('用户不存在'));
            }
            $result = self::checkTask($uid, $task);
            if (!$result['result']) {
                exception($result['error']);
            }
            if ($task['add_empirical']>0) {//增加会员成长值
                UserModel::addUserEmpirical($uid, $task['add_empirical'], 2, lang('做任务增加成长值'));
            }
            if ($task['add_score']>0) {//增加会员积分
                ScoreModel::change($uid, $task['add_score'], 7, $task['title'].lang('增加积分'));
            }
            $data['tid']=$task['id'];
            $data['uid']=$uid;
            $data['create_time']=time();
            $data['empirical']=$task['add_empirical'];
            Db::name('user_task_log')->insertGetId($data);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }
    
    public static function checkTask($uid, $task)
    {
        $pass=1;
        switch ($task['sign']) {
            case 'firstOrder'://必须为首次下单,否则不执行
                $res=Db::name('order')->where(['user_id'=>$uid])->whereTime('create_time', 'today')->count();
                if ($res>1) {
                    $pass=0;
                    $error_info=lang('必须首次购买商品');
                }
                break;
            case 'browseGoods'://每日首次浏览商品
                $res=Db::name('user_task_log')->where(['uid'=>$uid,'tid'=>$task['id']])->whereTime('create_time', 'today')->find();
                if ($res) {
                    $pass=0;
                    $error_info=lang('必须每日首次浏览商品');
                }
                break;
            case 'firstSign':
                $res=Db::name('user_signin')->where(['user_id'=>$uid])->count();
                if ($res>1) {
                    $pass=0;
                    $error_info=lang('必须首次签到');
                }
                break;
            case 'shareGoods':
                $res=Db::name('user_task_log')->where(['uid'=>$uid,'tid'=>$task['id']])->whereTime('create_time', 'today')->find();
                if ($res) {
                    $pass=0;
                    $error_info=lang('每日首次分享');
                }
                break;
        }
        return ['result'=>$pass,'error'=>$error_info];
    }
}
