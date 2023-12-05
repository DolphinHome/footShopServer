<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\user\model\LevelCard as LevelCardModel;
use app\user\model\User as UserModel;



use think\Db;
use service\ApiReturn;

/**
 * 用户vip接口
 * @package app\api\controller\v1
 */
class UserVip extends Base
{
    //会员类型 1 金银卡 2 年月卡 3终身卡
    /**
     * 获取用户会员信息
     * @param  array  $data [description]
     * @param  array  $user [description]
     * @return [type]       [description]
     */
    public function get_user_level($data = [], $user = [])
    {
        $userInfo = UserModel::field('id,user_nickname')->where(['id'=>$user['id']])->find();
        if (!$userInfo) {
            return ApiReturn::r(1, [], lang('获取用户信息失败'));
        }
        $info = Db::name('user_level_card_data')
            ->alias('l')
            ->join('user_level_card c','c.id = l.level_id')
            ->where('l.user_id',$user['id'])
            ->where('l.status',1)
            ->field('l.card_number,l.user_vip_start_time,l.user_vip_last_time,c.level_name,c.bg_image,c.level,c.vip_image,c.color')
            ->find();
        if (!$info){
            //设置默认值
            $info = [];
            if (module_config('user.user_card') == 1){
                $card_data = Db::name('user_level_card')->where(['level'=>0,'type'=>'1'])->find();
                $info = [
                    'level_name' => $card_data['level_name'],
                    'bg_image' => $card_data['bg_image'],
                    'vip_image' => $card_data['vip_image'],
                    'color' => $card_data['color'],
                    'level' => $card_data['level'],
                ];
            }

        }
        $userInfo['user_vip_start_time'] = empty($info['user_vip_start_time'])?'':date('Y-m-d H:i:s', $info['user_vip_start_time']);
        $userInfo['user_vip_last_time'] = empty($info['user_vip_last_time'])?'':date('Y-m-d H:i:s', $info['user_vip_last_time']);
        $userInfo['level_name'] = empty($info['level_name'])?'':$info['level_name'];
        $userInfo['card_number'] = empty($info['card_number'])?'':$info['card_number'];
        $userInfo['level_bg_image'] = empty($info['bg_image'])?'':get_file_url($info['bg_image']);
        $userInfo['level_vip_image'] = empty($info['vip_image'])?'':get_file_url($info['vip_image']);
        $userInfo['level_color'] = empty($info['color'])?'':$info['color'];
        $userInfo['user_vip'] = empty($info['level'])?'':$info['level'];
        return ApiReturn::r(1, $userInfo, lang('成功'));
    }
    /**
     * 获取vip等级列表
     * @param array $data [description]
     * @param array $user [description]
     */
    public function get_level_list($data = [], $user = [])
    {
        $list = LevelCardModel::getList(module_config("user.user_card"));

        return ApiReturn::r(1, $list, lang('成功'));
    }

    /**
     * 获取vip等级详情
     * @param  array  $data [description]
     * @param  array  $user [description]
     * @return [type]       [description]
     */
    public function get_level_info($data = [], $user = [])
    {
        $info = LevelCardModel::getInfo($data['level_id']);

        return ApiReturn::r(1, $info, lang('成功'));
    }

    /**
     * 获取会员规则
     * @param  array  $data [description]
     * @param  array  $user [description]
     * @return [type]       [description]
     */
    public function get_level_rule($data = [], $user = [])
    {
        $type = empty($data['type'])?1:$data['type'];
        $list = Db::name('user_level_rule')->where('type', $type)->where('status', 1)->order('sort asc')->select();
        return ApiReturn::r(1, $list, lang('成功'));
    }
}
