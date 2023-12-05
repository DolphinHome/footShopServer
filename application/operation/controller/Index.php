<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\controller;

use app\operation\model\ServiceChat;
use app\operation\model\Servicegroup;
use app\operation\model\ServiceLog;
use app\operation\model\Servicewords;

/**
 * 后台默认控制器
 * @package app\admin\controller
 */
class Index extends Admin
{
    /**
     * 后台首页
     * @author 似水星辰 <2630481389@qq.com>
     * @return string
     */
    public function index()
    {
        // 客服信息
        $userInfo = session('operation_user_auth');
        $word = Servicewords::where(['status' => 1, 'partner_id' => $userInfo['partner_id']])->select();
        $groups = Servicegroup::where(['status' => 1, 'partner_id' => $userInfo['partner_id']])
            ->field("aid as id,create_time,update_time,status,partner_id,name")
            ->select();
        // 渲染查询数据
        $this->assign([
            'uinfo' => $userInfo,
            'word' => $word,
            'groups' => $groups,
            'socket'=>'zbphp.zhongbenzx.com/socket/'
        ]);

        return $this->fetch();
    }

    /**
     * 获取服务客户列表
     * @return \think\response\Json
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/7/3 18:03
     * @editor 晓风 [ 215628355@qq.com ]
     * @updated 2020-3-27
     */
    public function getUserList()
    {
        if (request()->isAjax()) {
            // 此处只查询过去 三个小时 内的未服务完的用户
            $userList = ServiceLog::field('user_id as id,user_name as name,user_avatar as avatar,user_ip as ip')
                ->where('kf_id', session('operation_user_auth.uid'))
                //->where('create_time', '>', time() - 3600 * 3)
                ->where('end_time', 0)
                ->group('id')
                ->select();
            //halt((new ServiceLog())->getLastSql());
            return json(['code' => 1, 'data' => $userList, 'msg' => 'ok']);
        }
    }

    // 获取聊天记录
    public function getChatLog()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = 20; // 一次显示10 条聊天记录
            $offset = ($param['page'] - 1) * $limit;

            $logs = ServiceChat::where(function ($query) use ($param) {
                $query->where('from_id', $param['uid'])->where('to_id', 'KF' . session('operation_user_auth.uid'));
            })->whereOr(function ($query) use ($param) {
                $query->where('from_id', 'KF' . session('operation_user_auth.uid'))->where('to_id', $param['uid']);
            })
                ->limit($offset, $limit)->order('aid', 'desc')->select()->each(function (&$vo) {
                    $vo['type'] = 'user';
                    $vo['time_line'] = $vo['create_time'];

                    if ($vo['from_id'] == 'KF' . session('operation_user_auth.uid')) {
                        $vo['type'] = 'mine';
                    }
                    return $vo;
                });

            $total = ServiceChat::where(function ($query) use ($param) {
                $query->where('from_id', $param['uid'])->where('to_id', 'KF' . session('operation_user_auth.uid'));
            })->whereOr(function ($query) use ($param) {
                $query->where('from_id', 'KF' . session('operation_user_auth.uid'))->where('to_id', $param['uid']);
            })
                ->count();


            return json(['code' => 1, 'data' => $logs, 'msg' => intval($param['page']), 'total' => ceil($total / $limit)]);
        }
    }

    // ip 定位
    public function getCity()
    {
        $ip = input('param.ip');

        $ip2region = new \Ip2Region();
        $info = $ip2region->btreeSearch($ip);

        $city = explode('|', $info['region']);

        if (0 != $info['city_id']) {
            return json(['code' => 1, 'data' => $city['2'] . $city['3'] . $city['4'], 'msg' => 'ok']);
        } else {
            return json(['code' => 1, 'data' => $city['0'], 'msg' => 'ok']);
        }
    }
}
