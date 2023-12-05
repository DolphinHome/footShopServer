<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\common\model\Order;
use app\goods\model\GoodsSku;
use app\goods\service\Goods;
use app\user\model\Certified;
use app\user\model\Collection;
use service\ApiReturn;
use think\Db;
use app\user\model\Task as TaskModel;

/**
 * 用户扩展接口
 * @package app\api\controller\v1
 */
class UserAddons extends Base
{
    /**
     * 会员签到
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/11 16:51
     */
    public function userSignin($data = [], $user = [])
    {
        //实例化签到模型
        $sign = new \app\user\model\Signin();
        $result = $sign->userSignin($user['id'], $data['type']);
        if (false === $result) {
            return ApiReturn::r(0, [], $sign->getError());
        }

        //签到成功
        if ($result['status'] == 1) {
            TaskModel::doTask($user['id'], 'firstSign');
            return ApiReturn::r(1, $this->filter($result, $this->fname), lang('已连续签到') . $result['days'] . lang('天'));
        }
        //重复签到
        if ($result['status'] == 2) {
            return ApiReturn::r(1, $this->filter($result, $this->fname), $result['msg']);
        }
    }

    /**
     * 获取签到信息
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/11 16:35
     */
    public function get_user_signin($data = [], $user = [])
    {
        $sign = new \app\user\model\Signin();
        //获取今日签到数据
        $todayData = $sign->todayData($user['id']);
        if (false === $todayData) {
            return ApiReturn::r(0, [], $sign->getError());
        }
        $result = $sign->getInsertData($user['id']);
        if ($todayData['is_sign'] == 0) {
            $result['days'] = $result['days'] - 1;//因为是组装的数据，如果没签到的话，就-1
        }
        $result['today'] = $todayData['is_sign'] ? $todayData['is_sign'] : 0;
        $result['score'] = \app\user\model\User::where('id', $user['id'])->value('score');
        $result['sign_total'] = $sign::where(['user_id' => $user['id']])->count();
        //$result = $this->filter($result, $this->fname);
        $sign_rule = module_config('user.sign_rule');
        $sign_rule = explode(';', $sign_rule);
        $sign_day = [];
        foreach ($sign_rule as $v) {
            $day = explode(':', $v);
            $st = date('Y-m-d', strtotime('Sunday ' . ($day[0] - 7) . ' day'));
            $res = Db::name('user_signin')
                ->where(['user_id' => $user['id']])
                ->whereTime('create_time', 'between', [$st . ' 00:00:00', $st . ' 23:59:59'])
                ->find();
            $sign_day[] = ['day' => $day[0], 'integral' => $day[1], 'is_sign' => ($res ? 1 : 0)];
        }
        $result['week_sign_info'] = $sign_day;
        $y = "";
        $m = "";
        $start_time = $data['start_time'] ?? '';
        if ($start_time) {
            $y = explode("-", $start_time)[0];
            $m = explode("-", $start_time)[1];
        }
        $now_month = $this->mFristAndLast($y, $m);
        $result2 = $sign::where(['user_id' => $user['id']])->whereTime('create_time', 'between', [$now_month['firstday'], $now_month['lastday']])->order("create_time desc")->paginate();
        $result = $result + $result2->toArray();
        foreach ($result['data'] as &$value) {
            $value['create_time'] = date("Y-m-d", strtotime($value['create_time']));
        }
        return ApiReturn::r(1, $result, lang('请求成功'));
    }


    //获取当月的开始时间和结束时间
    public function mFristAndLast($y = "", $m = "")
    {
        if ($y == "") {
            $y = date("Y");
        }
        if ($m == "") {
            $m = date("m");
        }
        $m = sprintf("%02d", intval($m));
        $y = str_pad(intval($y), 4, "0", STR_PAD_RIGHT);
        $m > 12 || $m < 1 ? $m = 1 : $m = $m;
        $firstday = strtotime($y . $m . "01000000");
        $firstdaystr = date("Y-m-01", $firstday);
        $lastday = strtotime(date('Y-m-d 23:59:59', strtotime("$firstdaystr +1 month -1 day")));

        return array(
            "firstday" => $firstday,
            "lastday" => $lastday
        );
    }


    /**
     * 会员实名认证
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/1 19:36
     */
    public function certification($data = [], $user = [])
    {
        // 启动事务
        Db::startTrans();
        try {
            $data['user_id'] = $user['id'];
            // 验证
            $result = $this->validate($data, 'user/Certified.add');
            if (true !== $result) {
                exception($result);
            }
            //同一个用户，每种认证类型，只能有一条记录
            if ($data['is_reset']) {
                //如果是重新提交，则保存资料
                $data['update_time'] = time();
                $data['status'] = 0; //待审核
                $res = Certified::where(['user_id' => $user['id'], 'auth_type' => $data['auth_type']])->update($data);
                if (!$res) {
                    exception(lang('提交认证材料失败'));
                }
            } else {
                $userCertified = Certified::where(['user_id' => $user['id'], 'auth_type' => $data['auth_type']])->find();
                if ($userCertified && $userCertified['status'] != 2) {
                    exception(lang('认证中，请勿重复提交'));
                } else {
                    if ($userCertified['user_id']) {
                        $data['status'] = 0; // 认证失败重新提交状态为待审核
                        $res = Certified::where(['user_id' => $user['id'], 'auth_type' => $data['auth_type']])->update($data);
                    } else {
                        $res = Certified::create($data);
                    }
                    if (!$res) {
                        exception(lang('提交认证材料失败'));
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }

        return ApiReturn::r(1, [], lang('提交成功'));
    }

    /**
     * 获取认证状态
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/1 20:22
     */
    public function certification_status($data = [], $user = [])
    {
        $res = Certified::where('user_id', $user['id'])->find();
        if ($res) {
            $result = $this->filter($res, $this->fname);
            return ApiReturn::r(1, $result, lang('请求成功'));
        } else {
            return ApiReturn::r(1, ['status' => 99], lang('未认证'));
        }
    }

    /**
     * 获取积分明细
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/9 10:38
     */
    public function get_score_list($data = [], $user = [])
    {
        $data['type'] = $data['type'] ?? 0;
        $data['user_id'] = $user['id'];
        $result = \app\user\model\ScoreLog::getList($data);

        if ($result) {
            return ApiReturn::r(1, $result, lang('请求成功'));
        } else {
            return ApiReturn::r(1, [], lang('暂无数据'));
        }
    }

    /**
     * 获取积分交易明细
     * @return void
     * @since 2019/4/23 18:30
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_score_detail($data = [], $user = [])
    {
        if ($data['date']) {
            $start_time = strtotime($data['date']);
            $end_time = strtotime('+1 day', $start_time);
            $whereTime = "create_time BETWEEN $start_time AND $end_time";
        }

        $data = \think\Db::name('user_integral_log')->where('user_id', $user['id'])->where($whereTime)->field('order_no', true)->order('aid', 'desc')->paginate();
        if ($data) {
            return ApiReturn::r(1, $data, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('请求成功'));
    }

    /**
     * 上传背景图
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @since 2019/4/26 14:33
     * @author zlf [2420541105@qq.com ]
     */
    public function user_background($data = [], $user = [])
    {
        if (!$data['background']) {
            return ApiReturn::r(0, [], lang('上传图片不能为空'));
        }
        $update['background'] = $data['background'];
        $update['updatetime'] = time();
        $result = \think\Db::name('user_info')->where('user_id', $user['id'])->update($update);
        if ($result) {
            cache("userinfo_" . $user['id'], null);
            return ApiReturn::r(1, [], lang('修改成功'));
        }
        return ApiReturn::r(0, [], lang('修改失败'));
    }

    /**
     * 关注/取关
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/9 13:55
     */
    public function follow($data = [], $user = [])
    {
        $follow = new \app\user\model\Follow();
        $user_id = $data['user_id']; //我的ID
        $fans_id = $user['id']; //主播ID
        if ($user_id == $fans_id) {
            return ApiReturn::r(0, '', lang('你不能自己关注自己'));
        }
        $res = $follow->isFollow($user_id, $fans_id);
        if ($res) {
            $ret = $follow->delFollow($user_id, $fans_id);
            if ($ret) {
                return ApiReturn::r(1, ['follow' => 0], lang('取消关注成功'));
            }
        } else {
            $ret = $follow->saveFollow($user_id, $fans_id);
            if ($ret) {
                return ApiReturn::r(1, ['follow' => 1], lang('关注成功'));
            }
        }
        return ApiReturn::r(0, [], lang('关注失败'));
    }

    /**
     *
     * 关注 和 粉丝列表
     * @param  $data
     * @param  $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhulongfei [ 242054105@qq.com ]
     * autograph  nickname avatar
     *
     */
    public function follow_attention($data = [], $user = [])
    {
        $type = $data["type"];
        //我的关注
        if ($type == 1) {
            $uid = $user['id'];
            $res = Db::name("user_follow")
                ->alias("f")
                ->field("f.id,u.head_img,u.user_nickname,f.create_time,f.fans_id,f.user_id,ui.autograph")
                ->join("user u", "f.user_id = u.id")
                ->join("user_info ui", "ui.user_id = u.id")
                ->where("f.fans_id", $uid)
                ->order("f.create_time desc")
                ->paginate()
                ->each(function ($item, $key) {
                    $item['is_follow'] = 1;
                    $item['head_img'] = get_file_url($item['head_img']);
                    return $item;
                });
        } elseif ($type == 2) {
            //我的粉丝
            $res = Db::name("user_follow")
                ->alias("f")
                ->field("f.id,u.head_img,u.user_nickname,f.create_time,f.fans_id,f.user_id,ui.autograph")
                ->join("user u", "f.fans_id = u.id")
                ->join("user_info ui", "ui.user_id = u.id")
                ->where("f.user_id", $user['id'])
                ->order("f.create_time desc")
                ->paginate()
                ->each(function ($item, $key) {
                    $item['is_follow'] = \think\Db::name('user_follow')->where(['user_id' => $item['fans_id'], 'fans_id' => $item['user_id']])->count();
                    $item['head_img'] = get_file_url($item['head_img']);
                    return $item;
                });
        }
        if ($res) {
            return ApiReturn::r(1, $res, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('没有更多数据了'));
    }

    /**
     * 获取我的收藏
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/9 10:38
     */
    public function get_collection_list($data = [], $user = [])
    {
        $keywords = isset($data['keywords']) ? $data['keywords'] : '';
        $where = " user_id = {$user['id']} ";
        $where .= " and type = {$data['type']} ";
        if (!empty($keywords)) {
            $where .= " and collect_title like '%{$keywords}%' ";
        }
        if ($data['type'] == 2) {
            $result = Collection::where($where)->field('aid,collect_id')->order("create_time DESC")->paginate(8);
            foreach ($result as $key => $value) {
                $list = Db::name('operation_article')->where('id', '=', $value['collect_id'])->find();
                $count = Db::name('operation_article_comment')->where('article_id', '=', $value['collect_id'])->count();
                $list['article_comment_num'] = $count;
                $list['img_url'] = get_files_url($list['img_url']);
                $result[$key]['list'] = $list;
            }
        } else {
            $result = Collection::where($where)->field('aid,collect_id,user_id,type,collect_title,collect_img,collect_price,collect_sales,sku_id')->order("create_time DESC")->paginate(8)->each(function ($item) {
                //设计图上没有商品规格显示
                if ($item['type'] == 1 || $item['type'] == 3) {
                    //收藏人数
                    $collect_num = Collection::where([
                        'collect_id' => $item['collect_id'],
                        'sku_id' => $item['sku_id'],
                        'status' => 1
                    ])->group("user_id")
                        ->count();
                    //月销量
                    $sales_sum = Goods::sales_num($item['collect_id'], 'month', 0);
                    $item['collect_num'] = $collect_num;
                    $item['sales_sum'] = $sales_sum;
                    if ($item['sku_id']) {
                        $sku = GoodsSku::where([
                            'sku_id' => $item['sku_id'],
                            'goods_id' => $item['collect_id']
                        ])->find();
                        if ($sku["key_name"]) {
                            $item['collect_title'] = "（规格：" . $sku["key_name"] . "）" . $item['collect_title'];
                        }
                        $item['market_price'] = $sku['market_price'];
                        $item['shop_price'] = $sku['shop_price'];
                    } else {
                        $goods = Db::name('goods')->where('id', $item['collect_id'])->find();
                        $item['market_price'] = $goods['market_price'];
                        $item['shop_price'] = $goods['shop_price'];
                    }
                }
                return $item;
            });
        }
        if ($result) {
            return ApiReturn::r(1, $result, lang('请求成功'));
        } else {
            return ApiReturn::r(1, [], lang('暂无数据'));
        }
    }

    /**
     * 添加/取消收藏（本接口适用于商品详情页，文章内容页等场景）
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/24 18:55
     */
    public function set_collection($data = [], $user = [])
    {
        $collect = new \app\user\model\Collection();
        $res = $collect->isCollection($user['id'], $data['type'], $data['collect_id']);
        if ($res) {
            // 取消收藏
            $ret = $collect->delCollection($user['id'], $data['type'], $data['collect_id']);
            if ($ret) {
                return ApiReturn::r(1, ['is_collection' => 0], lang('取消收藏'));
            }
        } else {
            $data['user_id'] = $user['id'];
            $thumb = \app\goods\model\Goods::where([
                'id' => $data['collect_id']
            ])->value("thumb");
            $data['collect_img'] = get_file_url($thumb);
            $ret = $collect->create($data);
            if ($ret) {
                return ApiReturn::r(1, ['is_collection' => 1], lang('收藏成功'));
            }
        }
        return ApiReturn::r(0, [], lang('操作失败'));
    }

    /**
     * 取消收藏（适用于个人中心-我的收藏-取消收藏）
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/24 21:44
     */
    public function cancel_collection($data = [], $user = [])
    {
        $collect = new \app\user\model\Collection();
        $aid_str = $data['aid'] ?? 0;
        $aid_arr = explode(',', $aid_str);
        $type = $data['type'];
        $res = $collect->where([
            ['aid', 'in', $aid_arr],
            ['type', '=', $type],
            ['user_id', '=', $user['id']]
        ])->delete();

        if ($res) {
            return ApiReturn::r(1, ['is_collection' => 0], lang('取消收藏成功'));
        }
        return ApiReturn::r(0, [], lang('操作失败'));
    }

    /**
     * 我邀请的人员列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/26 20:23
     */
    public function get_my_invite_user($data = [], $user = [])
    {
        $list = \app\user\model\User::where('lastid', $user['id'])->field('user_nickname,create_time,head_img,id')->paginate()
            ->each(function ($item) {
                $item['is_consum'] = \app\common\model\Order::where(['user_id' => $item['id'], 'order_type' => 2])->count();
            });
        if ($list) {
            return ApiReturn::r(1, $list, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));
    }

    /**
     * 手动绑定关系
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/29 11:20
     */
    public function set_user_lastid($data = [], $user = [])
    {
        //先查询是否有推荐人
        $now_lastid = Db::name('user')->where('id', $user['id'])->value('lastid');
        if ($now_lastid) {
            return ApiReturn::r(0, [], lang('您已经有推荐人了，请勿重复绑定'));
        }
        //查询邀请码所属的用户id
        $lastid = Db::name('user_info')->where('invite_code', $data['invite_code'])->value('user_id');
        if ($lastid) {
            $res = Db::name('user')->where('id', $user['id'])->update(['lastid' => $lastid]);
            if ($res) {
                return ApiReturn::r(1, [], lang('绑定成功'));
            }
        } else {
            return ApiReturn::r(0, [], lang('未找到推荐人信息'));
        }
        return ApiReturn::r(0, [], lang('绑定失败'));
    }

    /**
     * 获取VIP列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/3 23:17
     */
    public function get_vip($data = [], $user = [])
    {
        $result = \app\user\model\Vip::where('status', 1)->field('create_time,update_time,status', true)->select()->each(function ($item) {
            $item['thumb'] = get_file_url($item['thumb']);
            $item['interest'] = get_file_url($item['interest']);
            return $item;
        });

        if ($result) {
            return ApiReturn::r(1, $result, lang('请求成功'));
        } else {
            return ApiReturn::r(1, [], lang('暂无数据'));
        }
    }

    /**
     * 获取VIP详情
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/3 23:17
     */
    public function get_one_vip($data = [], $user = [])
    {
        $result = \app\user\model\Vip::where('aid', $data['aid'])->field('create_time,update_time,status', true)->find();

        if ($result) {
            return ApiReturn::r(1, $result, lang('请求成功'));
        } else {
            return ApiReturn::r(1, [], lang('暂无数据'));
        }
    }

    /**
     * 我的收益->收益统计
     * @param $data
     * @param $user
     * @author jxy[ 415782189@qq.com ]
     * @created 2020/2/23 20:55
     */
    public function earningsStatistics($data, $user)
    {
        $map[] = [['user_id', '=', $user['id']]];
        $map[] = [['change_type', 'in', [8, 9]]];
        $now = Db::name('user_money_log')->where($map)->sum('change_money');
        $today = Db::name('user_money_log')->whereTime('create_time', 'today')->where($map)->sum('change_money');
        $week = Db::name('user_money_log')->whereTime('create_time', 'week')->where($map)->sum('change_money');
        $month = Db::name('user_money_log')->whereTime('create_time', 'month')->where($map)->sum('change_money');
        $lastmonth = Db::name('user_money_log')->whereTime('create_time', 'last month')->where($map)->sum('change_money');
        for ($i = -1; $i >= -7; $i--) {
            $sDate = date("Y-m-d", strtotime($i . " day")) . ' 00:00:00';
            $eDate = date("Y-m-d", strtotime($i . " day")) . ' 23:59:59';
            $days[] = ['date' => date("Y-m-d", strtotime($i . " day")), 'total' => Db::name('user_money_log')->whereTime('create_time', 'between', [$sDate, $eDate])->where($map)->sum('change_money')];
        }
        return ApiReturn::r(1, ['now' => $now, 'today' => $today, 'week' => $week, 'month' => $month, 'lastmonth' => $lastmonth, 'days' => $days], lang('请求成功'));
    }

    /**
     * 我的收益->本月收益额
     * @param $data
     * @param $user
     * @author jxy[ 415782189@qq.com ]
     * @created 2020/2/23 20:55
     */
    public function myEarnings($data, $user)
    {
        // 模拟参数
        // $data = array(
        //     'change_type' => 8, // 获取数据类型 8：会员分享收益 9：商品购买回馈 89 : 8和9
        //     'page' => 1, // 页码
        //     'size' => 10, // 每页条数
        // );

        // 设置默认值
        $page = empty($data['page']) ? 1 : $data['page'];
        $size = empty($data['size']) ? 10 : $data['size'];

        // 设置where条件
        $where = array(
            'uml.user_id' => $user['id']
        );
        if (isset($data['change_type']) && !empty($data['change_type']) && in_array($data['change_type'], [8, 9])) {
            $where['uml.change_type'] = $data['change_type'];
        } elseif ($data['change_type'] == 89) {
            $where['uml.change_type'] = ['8', '9'];
        }

        // 查询数据
        $userMoneyLog = Db::name('user_money_log uml');
        $myEarnings = $userMoneyLog
            ->where($where)
            ->join('order o', 'o.order_sn=uml.order_no', 'left')
            ->join('user u', 'o.user_id=u.id', 'left')
            ->limit((($page - 1) * $size) . ',' . $size)
            ->field('uml.change_money as discounts,uml.order_no as order_sn,uml.create_time,u.user_name,u.head_img')
            ->order('uml.create_time DESC')
            ->select();
        // echo $userMoneyLog->getLastSql();exit;
        $total = $userMoneyLog->where($where)->count();
        foreach ($myEarnings as $k => $v) {
            $myEarnings[$k]['goods_list'] = Db::name('order_goods_list')
                ->field('ogl.goods_name,ogl.sku_name,ogl.num,ogl.shop_price,u.path')
                ->alias('ogl')
                ->join('upload u', 'u.id=ogl.goods_thumb', 'left')
                ->where(['ogl.order_sn' => $v['order_sn']])
                ->select();
        }
        $monthEarnings = Db::name('user_money_log  uml')->where($where)->whereTime('create_time', 'month')->sum('change_money');

        return ApiReturn::r(1, ['month' => $monthEarnings, 'total' => $total, 'earnings' => $myEarnings], lang('请求成功'));
    }

    /*
     * 我的任务日志
     *
     */
    public function myFinishTask($data, $user)
    {
        $finishTask = Db::name('user_task_log utl')
            ->where(['utl.uid' => $user['id']])
            ->order('utl.create_time desc')
            ->join('user_task ut', 'ut.id=utl.tid', 'left')
            ->field('utl.id,ut.title,ut.sign,ut.add_score,ut.add_empirical,utl.create_time')
            ->limit(($data['page'] - 1) * $data['size'] . ',' . $data['size'])
            ->select();
        $total = Db::name('user_task_log utl')
            ->where(['utl.uid' => $user['id']])
            ->join('user_task ut', 'ut.id=utl.tid', 'left')
            ->count();
        $empirical = Db::name('user_task_log')->where(['uid' => $user['id']])->sum('empirical');
        $score = Db::name('user_score_log')->where(['user_id' => $user['id'], 'change_type' => 7])->sum('change_score');
        return ApiReturn::r(1, ['list' => $finishTask, 'total' => $total, 'empirical' => $empirical, 'score' => $score], lang('请求成功'));
    }

    /**
     * 添加用户脚型数据
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setUserHealth($data = [],$user = []){
        $info = $data['info'];
        $end_string = strstr($info,'https:');
        $health_url = file_get_contents($end_string);
        $health_info_json = iconv("GBK","UTF-8",$health_url);
        $health_info_arr = json_decode($health_info_json,true);
        //脚型
        switch ($health_info_arr['l_r_zhixing']){
            case 0:
                $foot_type = '希腊脚';
                break;
            case 1:
                $foot_type = '罗⻢脚';
                break;
            case 2:
                $foot_type = '埃及脚';
                break;
            case 3:
                $foot_type = '未知';
                break;
            default:
                $foot_type = '脚模不合要求';
                break;
        }
        $exists_health = Db::name('user_health_archives')->where('scan_id',$health_info_arr['scanID'])->find();
        if($exists_health){
            return ApiReturn::r(1,$exists_health,'获取成功');
        }
        $insert_arr = [
            'foot_type' => $foot_type,
            'fit_shoes' => $health_info_arr['advise1'][0],
            'foot_type_influence' => $health_info_arr['advise2'],
            'foot_type_introduce' => $health_info_arr['zhixing_descrp'],
            'foot_length_left' => str_replace('mm','',$health_info_arr['left_length']),
            'foot_length_right' => str_replace('mm','',$health_info_arr['right_length']),
            'foot_width_left' => str_replace('mm','',$health_info_arr['left_width']),
            'foot_width_right' => str_replace('mm','',$health_info_arr['right_width']),
            'foot_type_analysis' => $health_info_arr['zugong_descrp'],
            'thumb_left' => str_replace('度','',$health_info_arr['left_mwfdushu']),
            'thumb_right' => str_replace('度','',$health_info_arr['right_mwfdushu']),
            'thumb_testing' => $health_info_arr['muwaifan_descrp'],
            'heel_left' => str_replace('mm','',$health_info_arr['lft_zuwaifan']),
            'heel_right' => str_replace('mm','',$health_info_arr['rft_zuwaifan']),
            'heel_testing' => $health_info_arr['zuwaifan_descrp'],
            'arch_foot_left' => $health_info_arr['left_zgzhishu'],
            'arch_foot_right' => $health_info_arr['right_zgzhishu'],
            'archl_testing' => $health_info_arr['zugong_descrp'],
            'scan_id' => $health_info_arr['scanID'],
            'scan_time' => strtotime($health_info_arr['scanTime']),
            'sex' => $health_info_arr['sex'],
            'fit_size_cn' => $health_info_arr['fit_size_CN'],
            'fit_size_eur' => $health_info_arr['fit_size_EUR'],
            'fit_size_uk' => $health_info_arr['fit_size_UK'],
            'fit_size_us' => $health_info_arr['fit_size_US'],
            'thumb_left_status' => str_replace('度','',$health_info_arr['left_mwfdushu']),
            'thumb_right_status' => str_replace('度','',$health_info_arr['right_mwfdushu']),
            'heel_left_status' => str_replace('mm','',$health_info_arr['lft_zuwaifan']),
            'heel_right_status' => str_replace('mm','',$health_info_arr['rft_zuwaifan']),
            'arch_foot_left_status' => $health_info_arr['left_zgzhishu'],
            'arch_foot_right_status' => $health_info_arr['right_zgzhishu'],
            'create_time' => time(),
            'user_id' => $user['id'],
        ];
        //判断脚趾状态 0正常 1轻度 2中度 3重度 4变形
        if($insert_arr['thumb_left'] > $insert_arr['thumb_right']){
            $thumb_status = $insert_arr['thumb_left'];
        }else if($insert_arr['thumb_left'] == $insert_arr['thumb_right']){
            $thumb_status = $insert_arr['thumb_left'];
        }else if($insert_arr['thumb_left'] < $insert_arr['thumb_right']){
            $thumb_status = $insert_arr['thumb_right'];
        }
        if($thumb_status <= 15){
            $insert_arr['thumb_status'] = 0;
        }else if($thumb_status   > 15 && $thumb_status <= 30){
            $insert_arr['thumb_status'] = 1;
        }else if($thumb_status   > 30 && $thumb_status <= 45){
            $insert_arr['thumb_status'] = 2;
        }else if($thumb_status   > 45 && $thumb_status <= 60){
            $insert_arr['thumb_status'] = 3;
        }else if($thumb_status   > 60){
            $insert_arr['thumb_status'] = 4;
        }
        //判断足外翻状态 -4 ~ 4正常 -4 ~ -8内翻轻度 4 - 8外翻轻度 小于-8 内翻严重 大于8 外翻严重
        if(abs($health_info_arr['lft_zuwaifan']) > abs($health_info_arr['rft_zuwaifan'])){
            $heel_status = $health_info_arr['lft_zuwaifan'];
        }else if(abs($health_info_arr['lft_zuwaifan']) < abs($health_info_arr['rft_zuwaifan'])){
            $heel_status = $health_info_arr['rft_zuwaifan'];
        }else{
            $heel_status = $health_info_arr['rft_zuwaifan'];
        }
        if($heel_status <= 4 && $heel_status >= -4){
            $insert_arr['heel_status'] = 0;
        }else if($heel_status > 4 && $heel_status <= 8){
            $insert_arr['heel_status'] = 1;
        }else if($heel_status < -4 && $heel_status >= -8){
            $insert_arr['heel_status'] = 2;
        }else if($heel_status < -8){
            $insert_arr['heel_status'] = 3;
        }else if($heel_status > 8){
            $insert_arr['heel_status'] = 4;
        }
        //判断足弓状态 0.21~0.26正常 0.17~0.21轻度高弓 0.26~0.3轻度扁平 小于0.17高弓 大于0.3 扁平
        if($health_info_arr['left_zgzhishu'] > $health_info_arr['right_zgzhishu']){
            if($health_info_arr['left_zgzhishu'] >= 0.21 && $health_info_arr['left_zgzhishu'] <= 0.26){
                $arch_status = $health_info_arr['right_zgzhishu'];
            }else{
                $arch_status = $health_info_arr['left_zgzhishu'];
            }

        }else if($health_info_arr['left_zgzhishu'] == $health_info_arr['right_zgzhishu']){
            $arch_status = $health_info_arr['left_zgzhishu'];
        }else if($health_info_arr['left_zgzhishu'] < $health_info_arr['right_zgzhishu']){
            if($health_info_arr['left_zgzhishu'] >= 0.21 && $health_info_arr['left_zgzhishu'] <= 0.26){
                $arch_status = $health_info_arr['right_zgzhishu'];
            }else{
                $arch_status = $health_info_arr['right_zgzhishu'];
            }
//            $arch_status = $health_info_arr['right_zgzhishu'];
        }
        if($arch_status < 0.17){
            $insert_arr['arch_status'] = 0;
        }else if($arch_status   >= 0.17 && $arch_status < 0.21){
            $insert_arr['arch_status'] = 1;
        }else if($arch_status   >= 0.21 && $arch_status <= 0.26){
            $insert_arr['arch_status'] = 2;
        }else if($arch_status   > 0.26 && $arch_status <= 0.3){
            $insert_arr['arch_status'] = 3;
        }else if($arch_status   > 0.3){
            $insert_arr['arch_status'] = 4;
        }
        $insert_id = Db::name('user_health_archives')->insertGetId($insert_arr);
        //储存发送的数据
        Db::name('health_report')->insert([
            'user_id'=>$user['id'],
            'create_time' => time(),
            'content'=>$health_info_json
        ]);
        if($insert_id){
            $insert_arr['id'] = $insert_id;
            return ApiReturn::r(1,$insert_arr,'添加成功');
        }else{
            return ApiReturn::r(0,'','添加失败');
        }
    }

    /**
     * 查询用户档案是否存在
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function existsUserHealth($data = [], $user = []){
        $user_health_id = $data['health_id'];
        $info = Db::name('user_health_img')->where('user_health_id',$user_health_id)->find();
        if($info){
            return ApiReturn::r(1,$info,'获取成功');
        }
        return ApiReturn::r(0,'','暂无数据');
    }

    /**
     * 设置用户生成健康档案海报
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setUserHealthImg($data = [],$user = []){
        $info = Db::name('user_health_img')->where('user_health_id',$data['user_health_id'])->find();
        if($info){
            return ApiReturn::r(1,'','成功');
        }
        $res = Db::name('user_health_img')->insert($data);
        if($res){
            return ApiReturn::r(1,'','成功');
        }
        return ApiReturn::r(0,'','失败');
    }
}
