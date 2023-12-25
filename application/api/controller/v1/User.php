<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\operation\model\Article;
use app\operation\model\ServiceChat;
use app\operation\model\SystemMessageRead;
use app\user\model\Certified;
use app\user\model\Collection;
use app\user\model\Level;
use app\user\model\User as UserModel;
use app\common\model\Api;
use app\common\model\LogSms;
use app\common\model\EmailSms;
use app\user\model\Task as TaskModel;


use think\Db;
use service\ApiReturn;
use service\Str;
use think\helper\Hash;

/**
 * 用户接口
 * @package app\api\controller\v1
 */
class User extends Base
{

    /**
     * 手机号一键登录
     * @param string $data
     * @author 李盼望
     * @created 2020/9/5 15:42
     */
    public function mobile_login($data = '')
    {
        $result = addons_action('Dypnsapi/Dypnsapi/GetMobile', $data);
        if ($result['code']) {
            $mobile = $result['mobile'];

            Db::startTrans();
            try {
                //检查手机号有没有注册
                $map['mobile'] = $mobile;
                $user = UserModel::where($map)->find();
                if (!$user) {
                    //不存在直接注册
                    if (isset($data['invite_code']) && $data['invite_code'] != "") {
                        $lastid = Db::name('user_info')->where('invite_code', $data['invite_code'])->value('user_id');
                        if ($lastid) {
                            $user_data['lastid'] = $lastid;
                        }
                    }
                    $register_integral = module_config("integral.register_integral") ?? 0;
                    $user_data['mobile'] = $mobile;
                    $user_data['user_name'] = $data['user_nickname'] ? $data['user_nickname'] : lang('用户') . rand(10000, 99999);
                    $user_data['client_id'] = $data['client_id'];
                    $user_data['user_type'] = 0;
                    $user_data['head_img'] = $data['head_img'] ? $data['head_img'] : 0;
                    $user_data['user_nickname'] = $data['user_nickname'] ? $data['user_nickname'] : $user_data['user_name'];
                    $user_data['create_time'] = time();
                    $user_data['score'] = $register_integral;
                    $user_data['status'] = 1;
                    $user_data['sex'] = $data['sex'] ? $data['sex'] : 0;
                    $user_data['birthday'] = time();

                    //注册账号
                    $result = UserModel::create($user_data);
                    $id = $result->id;
                    if (!$id) {
                        return ApiReturn::r(-999, [], lang('注册会员失败'));
                    }
                    //注册赠送积分记录
                    if ($register_integral > 0) {
                        ScoreLog::change($id, $register_integral, 7);
                    }
                    // 新增会员附加信息
                    $userinfo = Db::name('user_info')->insert(['user_id' => $id, 'invite_code' => 'IC00' . $id]);
                    if (!$userinfo) {
                        return ApiReturn::r(-999, [], lang('注册附加信息失败'));
                    }

                    $user = UserModel::where($map)->find();

                    //获取用户附加信息
                    $user_info = Db::name('user_info')->where('user_id', $user['id'])->find();
                    //获取登录需要返回的信息
                    $jsonList = $this->get_login_info($user, $user_info);
                } else {
                    if (!$user['status']) {
                        return ApiReturn::r(-999, [], lang('此用户被禁用，请联系客服咨询'));
                    }
                    //获取用户附加信息
                    $user_info = Db::name('user_info')->where('user_id', $user['id'])->find();
                    //获取登录需要返回的信息
                    $jsonList = $this->get_login_info($user, $user_info);
                }

                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return ApiReturn::r(0, [], $e->getMessage());
            }
            return ApiReturn::r(1, ['userinfo' => $jsonList], lang('登录成功')); //返回给客户端token信息
        } else {
            return ApiReturn::r(-999, [], $result['msg']);
        }
    }


    /**
     * 获取用户登录信息
     * @param string $data 传入的数据，包含username和password
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_name_login($data = '')
    {
        if ($data['mobile'] != '') {
            //手机号登录
            $map['mobile'] = $data['mobile'];
            if (!preg_match("/^1\d{10}$/", $data['mobile'])) {
                return ApiReturn::r(-999, [], lang('请输入正确的手机号格式'));
            }
        }
        if ($data['user_email'] != '') {
            //邮箱登录
            $map['user_email'] = $data['user_email'];
            if (!preg_match("/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i", $data['user_email'])) {
                return ApiReturn::r(-999, [], lang('请输入正确的邮箱格式'));
            }
        }
        $user = UserModel::where($map)->find();
        if (!$user) {
            return ApiReturn::r(-999, [], lang('该手机号尚未注册'));
        }

        if ($user) {
            $type = 1;
            $time = strtotime(date("Y-m-d"));
            $where[] = ['user_id', '=', $user['id']];
            $where[] = ['time', 'gt', $time];
            $where[] = ['type', '=', $type];
            $count = Db::name('user_login_info')->where($where)->count();

            if ($count >= module_config("user.login_count")) {
                return ApiReturn::r(-999, [], '账号已被锁定,请明天再试');
            }
            if (!Hash::check((string)$data['password'], $user['password'])) {
                $now_time = time();
                Db::name('user_login_info')->insert([
                    'mobile' => $user['mobile'],
                    'time' => $now_time,
                    'user_id' => $user['id'],
                    'type' => $type
                ]);

                return ApiReturn::r(0, [], lang('账号或者密码错误'));
            }
            if (!$user['status'] || $user['is_delete'] == 1) {
                return ApiReturn::r(-999, [], lang('此用户被禁用，请联系客服咨询'));
            }
            // Db::name('user_login_info')->where($where)->delete();
            unset($user['password']);
            //获取用户附加信息
            $user_info = Db::name('user_info')->where('user_id', $user['id'])->find();
            //获取登录需要返回的信息
            $jsonList = $this->get_login_info($user, $user_info);

            return ApiReturn::r(1, ['userinfo' => $jsonList], lang('登录成功')); //返回给客户端token信息
        } else {
            return ApiReturn::r(0, [], lang('该用户不存在'));
        }
    }

    /**
     * 用户使用手机验证码登录
     * @param string $data
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/7 16:31
     */
    public function user_mobile_login($data = '')
    {
        if ($data['mobile'] != '') {
            $map['mobile'] = $data['mobile'];
            if (!preg_match("/^1\d{10}$/", $data['mobile'])) {
                return ApiReturn::r(-999, [], lang('请输入正确的手机号格式'));
            }
            $logSmsModel = new LogSms();
            $verify_info = $data['mobile'];
        } else {
            $map['user_email'] = $data['user_email'];
            if (!preg_match("/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i", $data['user_email'])) {
                return ApiReturn::r(-999, [], lang('请输入正确的邮箱格式'));
            }
            $logSmsModel = new EmailSms();
            $verify_info = $data['user_email'];
        }

        // $user = UserModel::where($map)->field('id,user_name,user_nickname,status,head_img,sex,user_type,user_level,mobile,birthday,client_id')->find();
        // if (!$user) {
        //     return ApiReturn::r(-999, [], lang('没有此用户，请注册后登录'));
        // }

        // if ($user) {

        Db::startTrans();
        try {
            $result = $logSmsModel->verify_code($data['code'], $verify_info, $data['type']);
            if (!$result) {
                return ApiReturn::r(-999, [], '登录失败,验证码无效或已过期');
            }

            //查询用户信息
            $user = UserModel::where($map)->find();
            if (!$user) {
                //不存在直接注册
                if (isset($data['invite_code']) && $data['invite_code'] != "") {
                    $lastid = Db::name('user_info')->where('invite_code', $data['invite_code'])->value('user_id');
                    if ($lastid) {
                        $user_data['lastid'] = $lastid;
                    }
                }
                $user_data['mobile'] = $data['mobile'];
                $user_data['user_name'] = $data['user_nickname'] ? $data['user_nickname'] : lang('用户') . rand(10000, 99999);
                $user_data['client_id'] = $data['client_id'];
                $user_data['user_email'] = $data['user_email'] ?? '';
                $user_data['user_type'] = 0;
                $user_data['head_img'] = $data['head_img'] ? $data['head_img'] : 0;
                $user_data['user_nickname'] = $data['user_nickname'] ? $data['user_nickname'] : $user_data['user_name'];
                $user_data['create_time'] = time();
                $user_data['status'] = 1;
                $user_data['sex'] = $data['sex'] ? $data['sex'] : 1;
                $user_data['birthday'] = time();

                //注册账号
                $result = UserModel::create($user_data);
                $id = $result->id;
                if (!$id) {
                    return ApiReturn::r(-999, [], lang('注册会员失败'));
                }
                $user_source = empty($data['user_source']) ? 'other' : $data['user_source'];
                // 新增会员附加信息
                $userinfo = Db::name('user_info')->insert(['user_id' => $id, 'invite_code' => 'IC00' . $id, 'user_source' => $user_source]);
                if (!$userinfo) {
                    return ApiReturn::r(-999, [], lang('注册附加信息失败'));
                }
                $user = UserModel::where($map)->find();

                //获取用户附加信息
                $user_info = Db::name('user_info')->where('user_id', $user['id'])->find();
                //获取登录需要返回的信息
                $jsonList = $this->get_login_info($user, $user_info);
            } else {
                if (!$user['status']) {
                    return ApiReturn::r(-999, [], lang('此用户被禁用，请联系客服咨询'));
                }
                //获取用户附加信息
                $user_info = Db::name('user_info')->where('user_id', $user['id'])->find();
                //获取登录需要返回的信息
                $jsonList = $this->get_login_info($user, $user_info);
            }


            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        return ApiReturn::r(1, ['userinfo' => $jsonList], lang('登录成功')); //返回给客户端token信息
        // } else {
        //     return ApiReturn::r(0, [], lang('该用户不存在'));
        // }
    }

    /**
     * 社会化第三方登录
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/9 22:28
     */
    public function user_social_login($data = [], $user = [])
    {
        if ($data['type'] == 1) {
            if (!$data['wx_unionid']) {
                return ApiReturn::r(0, [], lang('参数错误，登录失败'));
            }
            // 获取会员附加表信息
            $user_info = Db::name('user_info')->where('wx_unionid', $data['wx_unionid'])->find();
        }

        if ($data['type'] == 2) {
            if (!$data['qq_unionid']) {
                return ApiReturn::r(0, [], lang('参数错误，登录失败'));
            }
            // 获取会员附加表信息
            $user_info = Db::name('user_info')->where('qq_unionid', $data['qq_unionid'])->find();
        }

        if (!$user_info) {
            return ApiReturn::r(-999, [], lang('没有此用户，开始注册绑定跳转'));
        }
        $user = UserModel::where('id', $user_info['user_id'])->find();
        if ($user) {
            Db::startTrans();
            try {
                if (!$user['status']) {
                    return ApiReturn::r(-999, [], lang('此用户被禁用，请联系客服咨询'));
                }
                // 获取登录需要返回的信息
                $jsonList = $this->get_login_info($user, $user_info);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return ApiReturn::r(0, [], $e->getMessage());
            }
            return ApiReturn::r(1, ['userinfo' => $jsonList], lang('登录成功')); //返回给客户端token信息
        } else {
            return ApiReturn::r(0, [], lang('该用户不存在'));
        }
    }

    /**
     * 第三方绑定账号，无账号则自动注册绑定
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/10 16:08
     */
    public function bind_wechat_account($data)
    {
        $logSmsModel = new LogSms();
        Db::startTrans();
        try {
            $result = $logSmsModel->verify_code($data['code'], $data['mobile'], $data['type']);
            if (!$result) {
                return ApiReturn::r(-999, [], lang('登录失败，验证码无效或已过期'));
            }
            $map['mobile'] = $data['mobile'];
            $user = UserModel::where($map)->find();
            $password = $data['password'] ?: '123456a';
            if ($user) {
                /*$is_pass = Hash::check($data['password'], $user['password']);//原密码
                if (!$is_pass) {
                    exception(lang('手机号已存在，验证密码错误，绑定失败'));
                }*/
                $rs = UserModel::where('id', $user['id'])->update(['password' => Hash::make($password)]);
                if (!$rs) {
                    exception(lang('绑定失败'));
                }
                if ($user['head_img'] == "" || $user['head_img'] == 0) {
                    //如果头像为空，则更新头像,更新性别
                    UserModel::where('id', $user['id'])->update(['head_img' => $data['avatarUrl'], 'sex' => $data['gender']]);
                    $user['head_img'] = $data['avatarUrl'];
                    $user['sex'] = $data['gender'];
                }
                //添加wx_unionid
                $res = Db::name('user_info')->where('user_id', $user['id'])->update(['wx_unionid' => $data['unionId'], 'wx_openid' => $data['openId']]);
                if (!$res) {
                    exception(lang('绑定失败'));
                }
            } else {
                $info['user_nickname'] = $data['nickName'];
                $info['sex'] = $data['gender'];
                $info['head_img'] = $data['avatarUrl'];
                $info['mobile'] = $data['mobile'];
                $info['client_id'] = $data['client_id'];
                $info['password'] = $data['password'];
                $info['invite_code'] = $data['invite_code'];
                $info['password'] = $password;
                $id = $this->get_reg_data($info);
                if ($id) {
                    //添加wx_unionid
                    $res1 = Db::name('user_info')->where('user_id', $id)->update(['wx_unionid' => $data['unionId'], 'wx_openid' => $data['openId']]);

                    /*//添加微信提现账号
                    $account = [
                        'user_id' => $id,
                        'account_id' => $data['openId'],
                        'true_name' => $data['nickName'],
                        'account_type' => 1,
                        'is_default' => 1,
                        'create_time' => time(),
                    ];
                    $res2 = Db::name('user_withdraw_account')->insert($account);*/
                    if (!$res1) {
                        exception(lang('绑定失败'));
                    }
                } else {
                    exception(lang('注册失败'));
                }
                $user = UserModel::where('id', $id)->find();
            }
            //获取会员附加信息
            $user_info = Db::name('user_info')->where('user_id', $id)->find();
            //获取登录需要返回的信息
            $jsonList = $this->get_login_info($user, $user_info);

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        TaskModel::doTask($id, 'bindWx');
        return ApiReturn::r(1, ['userinfo' => $jsonList], lang('绑定成功')); //返回给客户端token信息
    }

    /**
     * 生成登录需要的信息
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/10 14:58
     */
    public function get_login_info($user, $user_info)
    {
        // 请求接口的token
        $exp_time1 = 2592000; //token过期时间,这里设置30天
        $scopes1 = 'role_access'; //token标识，请求接口的token
        $access_token = Api::createToken($user, $exp_time1, $scopes1);
        $uuid = Str::uuid();
        cache('userinfo_' . $user['id'], null);
        cache('user_token_' . $uuid, $access_token);


        $jsonList = [
            'user_token' => $uuid,
            'id' => $user['id'],
            'head_img' => get_file_url($user['head_img']),
            'user_name' => $user['user_name'],
            'user_nickname' => $user['user_nickname'],
            'user_email' => $user['user_email'] ?? '',
            'sex' => $user['sex'],
            'user_type' => $user['user_type'],
            'user_level' => $user['user_level'],
            'mobile' => preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $user['mobile']),

            'birthday' => $user['birthday'],
            'address' => $user_info['address'],
            'address_code' => $user_info['address_code'],
            'client_id' => $user['client_id'],
            'invite_code' => $user_info['invite_code'],
            'is_finger' => $user['is_finger'],
            'autograph' => $user_info['autograph']

        ];

        return $jsonList;
    }

    /**
     * 获取会员详细信息
     * @param $data .user_id int 会员ID
     * @return \think\response\Json
     * @since 2020年8月28日09:49:52
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function get_user_info($data = [], $user = [])
    {
        // 获取会员ID
        $user_id = $data['user_id'] ?? $user['id'];

        // 查询会员信息
        $info = UserModel::alias('u')
            ->field('
                i.*,u.id,u.user_level,u.user_nickname,u.mobile,u.user_email,u.user_name,u.head_img,u.birthday,u.is_finger,
                u.user_money,u.score,u.empirical,u.vip_last_time,u.invoice_title,u.invoice_company_title,osl.name sell_name,osl.percent,
                ul.name level_name,u.total_consumption_money,u.sex,ul.icon
            ')
            ->where('u.id', $user_id)
            ->join('user_info i', 'u.id=i.user_id', 'left')
            ->join('user_level ul', 'ul.levelid = u.user_level', 'left')
            ->join('operation_sell_level osl', 'osl.aid = u.spread_level', 'left')
            ->find();
        if ($info) {
            $info['phone'] = config("phone");
            $info['online'] = config("online");
//            //校正会员等级错误
//            $empirical = $info['empirical'];
//            $user_level = $info['user_level'];
//            $levelid = Db::name("user_level")->where([['upgrade_score', '<=', $empirical]])->order("levelid desc")->value('levelid');
//            if ($user_level != $levelid) {
//                $info['user_level'] = $levelid;
//                UserModel::where([
//                    'id' => $user_id
//                ])->update([
//                    'user_level' => $levelid
//                ]);
//            }


            $info['icon'] = get_file_url($info['icon']);
            $info['_mobile'] = $info['mobile'];
            $info['mobile'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $info['mobile']);
            $info['head_img'] = get_file_url($info['head_img']);
            if (strpos($info['head_img'], 'images/none.png') !== false) {
                $info['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
            }
            $nowTime = time();
            $where[] = ['status', 'eq', 1];
            $where[] = ['user_id', 'eq', $user_id];
            $where[] = ['start_time', 'lt', $nowTime];
            $where[] = ['end_time', 'gt', $nowTime];
            $info['coupon'] = Db::name('operation_coupon_record')->where($where)->count();
            $info['age'] = get_age($info['birthday']);
            $info['vip_last_time'] = date('Y-m-d H:i:s', $info['vip_last_time']);
            $info['level_info'] = $info['level_title'] = [];
            $res_level = Db::name('user_level')->where([['levelid', '>=', $info['user_level']]])->select();
            foreach ($res_level as $l) {
                if ($l['levelid'] ==  $info['user_level']) {
                    $info['level_title'] = $l;
                }
                if ($l['levelid'] >  $info['user_level']) {
                    $info['level_info'][$l['levelid']] = $l['upgrade_score'];
                }
            }

            // $info['red_packet'] = Db::name('user_ticket')->where(['uid'=>$user_id])->sum('amount');
            
            $user_money_log = Db::name("user_money_log")->fieldRaw("sum(if(change_type=8,change_money,0)) as share_bonus,sum(if(change_type=9,change_money,0)) as self_uintio, sum(change_money) as total_change_money")->where(['user_id'=>$user_id])->find();
            //用户端变动总金额
            $info['total_change_money'] = sprintf('%.2f', $user_money_log['total_change_money']??0);
            //用户端分享赚
            $info['share_bonus'] = sprintf('%.2f', $user_money_log['share_bonus']??0);
            //用户端自购返
            $info['self_untio'] = sprintf('%.2f', $user_money_log['self_uintio']??0);

            // zenghu ADD 商品关注数量 && 浏览足迹数量 2020年8月22日14:26:55
            $collectionCount = Db::name('user_collection')
                ->field('IFNULL(count(aid), 0) collectionCount')
                ->where(['user_id' => $user_id, 'status' => 1, 'type' => 1])
                ->find();
            $info['collection_count'] = $collectionCount['collectionCount'];
            $browseCount = Db::name('user_collection')
                ->field('IFNULL(count(aid), 0) browseCount')
                ->where(['user_id' => $user_id, 'status' => 1, 'type' => 3])
                ->find();
            $info['browse_count'] = $browseCount['browseCount'];

            //会员认证状态 状态-1 未申请 0待审核1已通过2已拒绝
            $info['certified'] = -1;
            $certified = Db::name("user_certified")
                ->where([
                    'user_id' => $user_id,
                ])->find();
            if ($certified) {
                $info['certified'] = $certified['status'];
            }
            //升级到下一级的信息
            $info['next_level'] = Db::name('user_level')->where([['levelid', '>', $info['user_level']]])
                ->field('name,upgrade_score')
                ->order("levelid desc")
                ->find();

            return ApiReturn::r(1, $info, lang('请求成功'));
        }

        return ApiReturn::r(0, [], lang('请登录'));
    }

    /**
     * 会员注册
     * @param array $data
     * @param array $user
     * @return void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/10 15:45
     */
    public function registerUser($data = [], $user = [])
    {
        // 启动事务
        Db::startTrans();
        try {
            $logSmsModel = new LogSms();
            $result = $logSmsModel->verify_code($data['code'], $data['mobile'], $data['type']);
            if (!$result) {
                exception(lang('手机号或验证码错误'));
            }

            $id = $this->get_reg_data($data);
            if (!$id) {
                exception(lang('注册失败'));
            }
            //保存分销关系
            $invite_code = isset($data['invite_code']) ? $data['invite_code'] : '';
            $pid = Db::name("user_info")->where(['invite_code' => $invite_code])->value("user_id");
            Db::name("distribution")->insert([
                'user_id' => $id,
                'pid' => $pid ?: 0,
                'create_time' => time()
            ]);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $msg = $e->getMessage();
            return ApiReturn::r(0, [], $msg);
        }

        $user = UserModel::where('id', $id)->find();
        //获取用户附加信息
        $user_info = Db::name('user_info')->where('user_id', $id)->find();
        // 获取登录需要返回的信息
        $jsonList = $this->get_login_info($user, $user_info);
        return ApiReturn::r(1, ['userinfo' => $jsonList], lang('注册成功'));
    }

    /**
     * 注册用户信息
     * @param $data
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/10 17:03
     */
    public function get_reg_data($data)
    {
        // 启动事务
        Db::startTrans();
        try {
            $res = $this->check_password($data['password']);
            if (!$res) {
                exception('请设置密码为6-32位字母加数字的组合');
            }
            $rs = Db::name('user')->where('mobile', $data['mobile'])->find();
            if ($rs) {
                exception(lang('该手机号已注册，请核对'));
            }
            if (isset($data['invite_code']) && $data['invite_code'] != "") {
                $lastid = Db::name('user_info')->where('invite_code', $data['invite_code'])->value('user_id');
                if ($lastid) {
                    $user_data['lastid'] = $lastid;
                }
            }
            $user_data['password'] = $data['password'];
            $user_data['mobile'] = $data['mobile'];
            $user_data['user_name'] = $data['user_nickname'] ? $data['user_nickname'] : lang('用户') . rand(10000, 99999);
            $user_data['client_id'] = $data['client_id'];
            $user_data['user_type'] = 0;
            $user_data['head_img'] = $data['head_img'] ? $data['head_img'] : 0;
            $user_data['user_nickname'] = $data['user_nickname'] ? $data['user_nickname'] : $user_data['user_name'];
            $user_data['create_time'] = time();
            $user_data['status'] = 1;
            $user_data['sex'] = $data['sex'] ? $data['sex'] : 0;
            $user_data['birthday'] = time();

            //注册账号
            $result = UserModel::create($user_data);
            $id = $result->id;
            if (!$id) {
                exception(lang('注册会员失败'));
            }
            $user_source = empty($data['user_source']) ? 'other' : $data['user_source'];
            // 新增会员附加信息
            $userInfo = [
                'user_id' => $id,
                'invite_code' => 'IC00' . $id,
                'user_source' => $user_source,
                'wx_unionid' => $data['wx_unionid'] ?? '',
                'qq_unionid' => $data['qq_unionid'] ?? '',
                'wx_openid' => $data['wx_openid'] ?? '',
                'xcx_openid' => $data['openid'] ?? '',
                'xcx_qrcode' => $data['xcx_qrcode'] ?? ''
            ];
            $userinfo = Db::name('user_info')->insert($userInfo);
            if (!$userinfo) {
                exception(lang('注册附加信息失败'));
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $msg = $e->getMessage();
            exception($msg);
        }
        return $id;
    }

    /**
     * 验证码重置密码
     * @param array $data
     * @param array $user
     * @return void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/10 15:45
     */
    public function forgetPassword($data = [], $user = [])
    {
        // 启动事务
        Db::startTrans();
        try {
            $map['mobile'] = $data['mobile'];
            //自定义信息，不要定义敏感信息
            $user = UserModel::where($map)->field('id,user_name,user_nickname,status,head_img,sex,user_type,user_level,mobile,password')->find();
            if (!$user) {
                return ApiReturn::r(-999, [], lang('没有此用户，请注册后登录'));
            }
            if (Hash::check($data['password'], $user['password'])) {
                return ApiReturn::r(0, [], lang('新密码和原密码一致'));
            }
            $logSmsModel = new LogSms();
            $result = $logSmsModel->verify_code($data['code'], $data['mobile'], $data['type']);
            if (!$result) {
                exception(lang('手机号或验证码错误'));
            }

            $password = Hash::make($data['password']);
            $result1 = UserModel::where('mobile', $data['mobile'])->update(['password' => $password]);
            if (!$result1) {
                exception(lang('重置密码失败'));
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $msg = $e->getMessage();
            return ApiReturn::r(0, [], $msg);
        }

        return ApiReturn::r(1, [], lang('重置密码成功'));
    }

    /**
     * 旧密码验证修改密码
     * @author 朱龙飞 [ 2420541105 @qq.com ]
     * @created 2019/10/17 0003 14:13
     */
    public function forgetPassword_code($data = [], $user = [])
    {
        $userinfo_password = UserModel::where('id', $user['id'])->value('password');
        if (isset($data['security_code'])) {
            if (!$userinfo_password) {
                return ApiReturn::r(0, [], lang('您还没有设置密码'));
            }
            $resd = Hash::check($data['security_code'], $userinfo_password);//原密码
            if (!$resd) {
                return ApiReturn::r(0, [], lang('原密码错误'));
            }
        }

        $res = $this->check_password($data['password']);
        if (!$res) {
            return ApiReturn::r(0, [], lang('密码只能是6-32位字母加数字'));
        }
        if ($data['password'] != $data['password_code']) {
            return ApiReturn::r(0, [], lang('新密码和确定密码不一致，请重新输入'));
        }
        $password = Hash::make($data['password']);

        if (Hash::check($data['password'], $userinfo_password)) {
            return ApiReturn::r(0, [], lang('新密码和原密码一致'));
        }
        $result = UserModel::where('id', $user['id'])->update(['password' => $password]);
        if ($result) {
            return ApiReturn::r(1, [], lang('重置密码成功'));
        }
        return ApiReturn::r(0, [], lang('重置密码失败'));
    }

    /**
     * 验证密码
     * @author 朱龙飞 [ 2420541105@qq.com ]
     * @created 2019/9/16 0003 10:57
     */
    public function check_password($password)
    {
        if (preg_match("/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,32}$/", $password)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 修改会员个人资料
     * @param string $data
     * @param string $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 杨志刚 [ 1909507511@qq.com ]
     * @since 2019/4/16 15:36
     */
    public function edit_user_info($data = [], $user = [])
    {
        if ($data['birthday']) {
            $result = \think\Validate::make()
                ->rule('birthday', 'date')
                ->check($data);
            if (!$result) {
                return ApiReturn::r(0, [], lang('生日格式有误，请重新选择'));
            }
            $datetime = new \DateTime($data['birthday'], new \DateTimeZone('PRC'));
            $data['birthday'] = $datetime->format('U');//时间格式转化时间戳
        }

        if ($data['user_email']) {
            $res = UserModel::where(['user_email' => $data['user_email']])->find();
            if ($res && $user['id'] != $res['id']) {
                return ApiReturn::r(0, [], lang('该邮箱已绑定，请核实'));
            }
        }

        $data['update_time'] = time();
        if ($data['head_img'] == "") {
            unset($data['head_img']);
        }
        // 启动事务
        Db::startTrans();
        try {
            $result = UserModel::where('id', $user['id'])->update($data);
            if (!$result) {
                throw new \Exception(lang('更新会员信息失败'));
            }
            $data['updatetime'] = $data['update_time'];
            //修改会员附表
            $user_info = Db::name('user_info')->where('user_id', $user['id'])->find();
            if ($user_info) {
                $res = Db::name('user_info')->where('user_id', $user['id'])->update($data);
                if (!$res) {
                    throw new \Exception(lang('更新会员附加信息出错'));
                }
            }

            Db::commit();
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        cache('userinfo_' . $user['id'], null);
        $user_data = UserModel::where('id', $user['id'])->find();
        $user_info = Db::name('user_info')->where('user_id', $user['id'])->find();
        $jsonList = $this->get_login_info($user_data, $user_info);
        return ApiReturn::r(1, ['userinfo' => $jsonList], lang('操作成功'));
    }

    /**
     * 新增或更换绑定的手机号
     * @return \think\response\Json
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/8/23 10:16
     */
    public function bind_mobile($data = [], $user = [])
    {
        //注意，step=1时，mobile是旧手机号，step=2时，mobile是新手机号
        // 启动事务
        Db::startTrans();
        try {
            $logSmsModel = new LogSms();
            if ($data['step'] == 1) {
                $data['mobile'] = Db::name('user')->where('id', $user['id'])->value('mobile');
                if (!preg_match("/^1\d{10}$/", $data['mobile'])) {
                    throw new \Exception(lang('手机号码格式错误'));
                }
            }
            $result = $logSmsModel->verify_code($data['code'], $data['mobile'], $data['type']);
            if (!$result) {
                exception(lang('手机号或验证码错误'));
            }

            if ($data['step'] == 1) {
                Db::commit();
                return ApiReturn::r(1, [], lang('验证成功'));
            }

            $res = UserModel::where(['mobile' => $data['mobile']])->count();
            if ($res) {
                throw new \Exception(lang('此手机号已存在，请更换手机号'));
            }

            $res1 = UserModel::where('id', $user['id'])->update(['mobile' => $data['mobile']]);
            if (!$res1) {
                throw new \Exception(lang('绑定失败'));
            }
            Db::commit();
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }

        cache('userinfo_' . $user['id'], null);
        return ApiReturn::r(1, ['userinfo' => ['mobile' => $data['mobile']]], lang('绑定成功'));
    }

    /**
     * 更新用户表中用户的设备client_id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/25 14:18
     */
    public function set_client_id($data, $user)
    {
        $result = UserModel::where('id', $user['id'])->update(['client_id' => $data['client_id']]);
        if ($result) {
            return ApiReturn::r(1, [], '更新client_id成功');
        }
        return ApiReturn::r(0, [], lang('操作失败'));
    }

    /**
     * 修改会员签名
     * @param string $data .autograph 会员签名
     * @return \think\response\Json
     * @since 2020年8月11日17:05:16
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function edit_user_autograph($data = [], $user = [])
    {
        // if(!$user){
        //     return ApiReturn::r(1, [], lang('登录状态已失效，请重新登录'));
        // }

        try {
            $data['updatetime'] = time();
            //修改会员附表
            $res = Db::name('user_info')->where('user_id', $user['id'])->update($data);
            if (!$res) {
                throw new \Exception(lang('更新会员附加信息出错'));
            }
            $user_info = Db::name('user_info')->where('user_id', $user['id'])->find();
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            return ApiReturn::r(0, [], $e->getMessage());
        }
        cache('userinfo_' . $user['id'], null);
        $user_data = UserModel::where('id', $user['id'])->find();
        $jsonList = $this->get_login_info($user_data, $user_info);

        return ApiReturn::r(1, ['userinfo' => $jsonList], lang('操作成功'));
    }


    //设置支付密码
    public function setPayPassword($data = [], $user = [])
    {
        $data['mobile'] = Db::name('user')->where('id', $user['id'])->value('mobile');
        $logSmsModel = new LogSms();
        $result = $logSmsModel->verify_code($data['code'], $data['mobile'], $data['type']);
        if (!$result) {
            return ApiReturn::r(0, [], lang('验证码错误'));
        }
        $password = Hash::make($data['password']);
        $result = UserModel::where(['mobile' => $data['mobile'], 'id' => $user['id']])->update(['pay_password' => $password]);
        if ($result) {
            return ApiReturn::r(1, [], lang('设置支付成功'));
        }
        return ApiReturn::r(0, [], lang('设置支付失败'));
    }

    //判断用户是否设置支付密码
    public function setPayPasswordsten($data = [], $user = [])
    {
        $result = UserModel::where(['id' => $user['id']])->field('pay_password,user_money')->find();
        if ($result['pay_password']) {
            if ($result['user_money'] < $data['pay_money']) {
                return ApiReturn::r(0, [], lang('余额不足'));
            } else {
                return ApiReturn::r(1, [], lang('通过'));
            }
        } else {
            return ApiReturn::r(-1, [], lang('请您设置支付密码'));
        }
    }


    /**
     * 解密小程序信息，获取用户信息，登录
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/15 18:26
     */
    public function get_mini_user($data)
    {
        $result = addons_action('WeChat', 'AuthCode', 'get_mini_user', [$data['code'], $data['iv'], $data['encryptedData']]);
        unset($result['session_key'], $result['watermark']);//删掉微信加密信息
        if ($result) {
            $result['openId'] = $result['openid'];
            $user_info = Db::name('user_info')->where(['xcx_openid' => $result['openid']])->find();
            unset($result['openid']);
            if ($user_info) {
                $map['id'] = $user_info['user_id'];
                $user = UserModel::where($map)->find();
                if ($user) {
                    if (!$user['status']) {
                        return ApiReturn::r(-999, [], lang('此用户被禁用，请联系客服咨询'));
                    }
                }
                // 获取登录需要返回的信息
                $jsonList = $this->get_login_info($user, $user_info);
                return ApiReturn::r(1, ['userinfo' => $jsonList], lang('登录成功')); //返回给客户端token信息
            } else {
                return ApiReturn::r(1, ['result' => $result], lang('未查询到注册信息')); //返回给客户端token信息
            }
        }
        return ApiReturn::r(0, $result, lang('请求失败'));
    }


    /**
     * Notes: 手机号快捷登录
     * User: chenchen
     * Date: 2021/5/18
     * Time: 15:38
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function mobile_quick_login($data = [], $user = [])
    {
        $mobile = $data['mobile'];
        //查询手机号是否注册 如果未注册则自动注册
        $user = UserModel::where(['mobile' => $mobile])->find();
        if (!$user) {
            Db::startTrans();
            try {
                $info['user_nickname'] = lang('用户') . rand(10000, 99999);
                $info['sex'] = 0;
                $info['password'] = '123456a';
                $info['source'] = 2; //从小程序注册来的
                $info['invite_code'] = $data['invite_code'];
                $info['user_source'] = empty($data['user_source']) ? 'other' : $data['user_source'];
                $id = $this->get_reg_data($info);
                if (!$id) {
                    exception(lang('注册失败'));
                }
                Db::commit();
            } catch (\Exception $e) {
                // 更新失败 回滚事务
                Db::rollback();
                return ApiReturn::r(0, [], $e->getMessage());
            }
            //查询用户信息
            $user = UserModel::where('id', $id)->find();
        } else {
            $id = $user['id'];
        }
        //获取用户附加信息
        $user_info = Db::name('user_info')->where('user_id', $id)->find();
        // 获取登录需要返回的信息
        $jsonList = $this->get_login_info($user, $user_info);


        return ApiReturn::r(1, ['userinfo' => $jsonList], lang('登录成功')); //返回给客户端token信息
    }

    /**
     * 解密小程序信息，获取用户信息,自动注册
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/15 18:26
     */
    public function bind_mini_mobile($data)
    {
        try {
            $result = addons_action('WeChat', 'AuthCode', 'get_mini_user', [$data['code'], $data['iv'], $data['encryptedData']]);
        } catch (\Exception $e) {
            return ApiReturn::r(0, [], $e->getMessage());
        }
        if ($result) {
            $wxinfo = json_decode($data['wxinfo'], true);
            $phone = UserModel::alias('u')->where('mobile', $result['purePhoneNumber'])->leftJoin('user_info ui', 'u.id=ui.user_id')->field('u.id,u.mobile,ui.xcx_openid')->find();
            if ($phone['mobile']) {
                //手机号存在的话，是在APP端注册的
                if (!$phone['xcx_openid']) {
                    //如果此手机号对应的openid为空，则绑定
                    Db::name('user_info')->where('user_id', $phone['id'])->update([
                        'xcx_openid' => $wxinfo['openId'],
//                        'wx_unionid' => $wxinfo['unionId'],
                    ]);
                    UserModel::where('id', $phone['id'])->update([
                        'sex' => $wxinfo['gender'],
                        'user_name' => $wxinfo['nickName'],
                        'user_nickname' => $wxinfo['nickName'],
                        'head_img' => $wxinfo['avatarUrl'],
                    ]);
                    $id = $phone['id'];
                } else {
                    //不为空则表示已经绑定过了，需要先解绑
                    return ApiReturn::r(0, $result, lang('此手机号已经绑定过账号了，无法绑定'));
                }
            } else {
                Db::startTrans();
                try {
                    $info['user_nickname'] = $wxinfo['nickName'];
                    $info['sex'] = $wxinfo['gender'];
                    $info['head_img'] = $wxinfo['avatarUrl'];
                    $info['password'] = '123456a';
//                    $info['unionid'] = $wxinfo['unionId'];
                    $info['openid'] = $wxinfo['openId'];
                    $info['mobile'] = $result['purePhoneNumber'];
                    $info['source'] = 2; //从小程序注册来的
                    $info['user_id'] = $data['user_id'];
                    $info['invite_code'] = $data['invite_code'];
                    $info['user_source'] = empty($data['user_source']) ? 'other' : $data['user_source'];
                    $id = $this->get_reg_data($info);
                    if (!$id) {
                        exception(lang('注册失败'));
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    // 更新失败 回滚事务
                    Db::rollback();
                    return ApiReturn::r(0, [], $e->getMessage());
                }
            }

            //查询用户信息
            $user = UserModel::where('id', $id)->find();
            //获取用户附加信息
            $user_info = Db::name('user_info')->where('user_id', $id)->find();
            // 获取登录需要返回的信息
            $jsonList = $this->get_login_info($user, $user_info);

            return ApiReturn::r(1, ['userinfo' => $jsonList], lang('登录成功')); //返回给客户端token信息
        }
        return ApiReturn::r(0, $result, lang('授权失败'));
    }

    /**
     * 检测是否设置支付密码
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/15 18:26
     */
    public function check_pay_password($data = [], $user = [])
    {
        $pay_password = UserModel::where('id', $user['id'])->value('pay_password');
        //如果传递pay_password，则检查是否正确
        if ($data['pay_password']) {
            //校验旧密码和新密码是否一致

            if (!Hash::check($data['pay_password'], $pay_password)) {
                return ApiReturn::r(0, [], lang('支付密码错误'));
            }
            return ApiReturn::r(1, [], lang('支付密码正确'));
        }

        //如果不传递pay_password，则检查是否设置密码
        if ($pay_password) {
            return ApiReturn::r(1, ['falg' => 1], lang('已设置支付密码'));
        }
        return ApiReturn::r(1, ['falg' => 0], lang('未设置支付密码'));
    }

    /**
     * 修改支付密码
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/15 18:26
     */
    public function update_pay_password($data = [], $user = [])
    {
        $type = $data['type'] ?? 1;
        $pay_password = UserModel::where('id', $user['id'])->value('pay_password');
        if (Hash::check($data['new_pay_password'], $pay_password)) {
            return ApiReturn::r(0, [], lang('旧密码和新密码一致'));
        }
        if ($type == 1) {
            $resd = Hash::check($data['pay_password'], $pay_password);//原密码
            if (!$resd) {
                return ApiReturn::r(0, [], lang('支付密码输入错误'));
            }
            $result = UserModel::where('id', $user['id'])->update(['pay_password' => Hash::make($data['new_pay_password'])]);
        } else {
//            $data['mobile'] = Db::name('user')->where('id', $user['id'])->value('mobile');
//            $logSmsModel = new LogSms();
//            $verify_code = $logSmsModel->verify_code($data['code'], $data['mobile'], 4);
//            if (!$verify_code) {
//                return ApiReturn::r(0, [], lang('验证码错误'));
//            }

            $result = UserModel::where('id', $user['id'])->update(['pay_password' => Hash::make($data['new_pay_password'])]);
        }
        if ($result) {
            return ApiReturn::r(1, [], lang('更新支付密码成功'));
        }
        return ApiReturn::r(0, [], lang('操作失败'));
    }

    /**
     *  校验原支付密码
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/15 18:26
     */
    public function check_old_password($data = [], $user = [])
    {
        $pay_password = UserModel::where('id', $user['id'])->value('pay_password');
        $resd = Hash::check($data['pay_password'], $pay_password);//原密码
        if (!$resd) {
            return ApiReturn::r(0, [], lang('原密码错误'));
        } else {
            return ApiReturn::r(1, [], 'ok');
        }
    }

    /**
     * 设置支付密码
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/15 18:26
     */
    public function set_pay_password($data = [], $user = [])
    {
        $password = UserModel::where('id', $user['id'])->value('pay_password');
        if ($password != '') {
            return ApiReturn::r(1, [], lang('密码已存在，请勿重复设置'));
        }

        $info['id'] = $user['id'];
        $data['set_pay_password'] = trim($data['set_pay_password']);
        $info['pay_password'] = Hash::make((string)$data['set_pay_password']);
        $result = UserModel::update($info);
        if ($result) {
            return ApiReturn::r(1, [], lang('设置支付密码成功'));
        }
        return ApiReturn::r(0, [], lang('操作失败'));
    }

    /**
     * 忘记支付密码
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/15 18:26
     */
    public function forget_pay_password($data = [], $user = [])
    {
        $LogSms = new LogSms();
        $time = time();
        //校验验证码
        $status = $LogSms->where(['code' => $data['code'], 'phone' => $data['mobile'], 'type' => $data['type']])->where('expiration_time', 'EGT', $time)->value('status');
        if (!$status) {
            return ApiReturn::r(0, [], lang('验证码错误'));
        }
        //校验旧密码和新密码是否一致
        $pay_password = UserModel::where('id', $user['id'])->value('pay_password');
        if (Hash::check($data['pay_password'], $pay_password)) {
            return ApiReturn::r(0, [], lang('旧密码和新密码一致'));
        }

        $result = UserModel::where('id', $user['id'])->update(['pay_password' => Hash::make($data['pay_password'])]);
        if ($result) {
            return ApiReturn::r(1, [], lang('支付密码设置成功'));
        }
        return ApiReturn::r(0, [], lang('操作失败'));
    }

    /**
     * 获取用户手机号
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/15 18:26
     */
    public function get_user_phone($data = [], $user = [])
    {
        $mobile = UserModel::where(['id' => $user['id']])->value('mobile');
        return ApiReturn::r(1, $mobile, lang('查询成功'));
    }

    /**
     * 修改手机号
     * @return \think\response\Json
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/8/23 10:16
     */
    public function update_mobile($data = [], $user = [])
    {
        // 启动事务
        Db::startTrans();
        try {
            //校验手机号是否存在
            $res = UserModel::where(['mobile' => $user['mobile']])->count();
            if (!$res) {
                return ApiReturn::r(0, [], lang('手机号不存在'));
            }
            //新手机号和原手机号相同
            if ($user['mobile'] == $data['mobile']) {
                return ApiReturn::r(0, [], lang('新手机号和原手机号相同'));
            }
            //检查新手机号是否已经绑定过
            $check = UserModel::where(['mobile' => $data['mobile']])->find();
            if (isset($check["id"]) && $check["id"] != $user["id"]) {
                return ApiReturn::r(0, [], lang('该手机号已被绑定'));
            }
            $logSmsModel = new LogSms();
            $result = $logSmsModel->verify_code($data['code'], $data['mobile'], $data['type']);
            if (!$result) {
                exception(lang('手机号或验证码错误'));
            }
            $res1 = UserModel::where('id', $user['id'])->update(['mobile' => $data['mobile']]);
            if (!$res1) {
                throw new \Exception(lang('绑定失败'));
            }
            Db::commit();
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }

        cache('userinfo_' . $user['id'], null);
        return ApiReturn::r(1, ['userinfo' => ['mobile' => $data['mobile']]], lang('修改手机号成功'));
    }


    /**
     * 校验验证码
     * @return \think\response\Json
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/8/23 10:16
     */
    public function check_verify_code($data = [])
    {
        if ($data['mobile'] != '') {
            $LogSms = new LogSms();
            //校验验证码
            $verify_code = $LogSms->verify_code($data['code'], $data['mobile'], $data['type']);
        } else {
            $EmailSms = new EmailSms();
            //校验验证码
            $verify_code = $EmailSms->verify_code($data['code'], $data['user_email'], $data['type']);
        }

        if (!$verify_code) {
            return ApiReturn::r(0, [], lang('验证码错误'));
        }
        return ApiReturn::r(1, [], lang('验证码正确'));
    }

    /**
     * 会员权益列表
     * @param array $requests .member_limit 会员权益人数
     * @return \think\response\Json
     * @since 2020年8月13日15:34:38
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function user_privilege_list($requests = array())
    {
        // 模拟参数
        // $requests['member_limit'] = 10;

        // 默认值
        $memberLimit = empty($requests['member_limit']) ? '10' : $requests['member_limit'];

        // 排行榜查询
        $userPrivilegePist = UserModel::alias('u')
            ->join('user_level ul', 'ul.levelid=u.user_level', 'left')
            ->field('u.user_nickname,u.head_img,ul.name user_level')
            ->order('u.user_level DESC')
            ->limit($memberLimit)
            ->select();

        return ApiReturn::r(1, $userPrivilegePist, lang('会员权益列表获取成功'));
    }

    /**
     * 会员排行榜
     * @param array $requests .member_limit 排行榜人数
     * @return \think\response\Json
     * @since 2020年8月11日14:39:33
     * @author zenghu [ 1427305236@qq.com ]
     */
    public function member_rankings($requests = array())
    {
        // 模拟参数
        // $requests['member_limit'] = 10;
        $user_id = $requests['user_id'];
        // 默认值
        $memberLimit = empty($requests['member_limit']) ? '10' : $requests['member_limit'];
        // 排行榜查询
        $lists = Db::name('user')->alias('u')
            ->join('user_level ul', 'ul.levelid=u.user_level', 'left')
            /*->where(['u.is_activation'=>1,'u.status'=>1])*/
            ->where(['u.status' => 1])
            ->field('u.user_nickname,u.id,u.head_img,ul.name user_level,u.empirical,u.total_consumption_money')
            ->order('u.user_level DESC,u.empirical DESC')
            ->select();
        foreach ($lists as $key => $value) {
            $lists[$key]['level'] = $key + 1;
        }
        foreach ($lists as $key => $value) {
            if ($user_id == $value['id']) {
                $user_data['user_nickname'] = $value['user_nickname'];
                $user_data['head_img'] = $value['head_img'];
                $user_data['level'] = $value['level'];
                $kk = $key - 1;
                $user_data['diff'] = $lists[$kk]['empirical'] - $value['empirical'];
            }
        }
        $dataArr['level_data']['lists'] = array_slice($lists, 0, $memberLimit);
        $dataArr['level_data']['user_data'] = $user_data;

        $lists = Db::name('user')->alias('b')->join('order a', 'a.user_id=b.id', 'left')->where('a.pay_status', 1)->field('sum(a.real_money) as money,sum(1) as number,b.id,b.user_nickname,b.head_img')->group('b.id')->order('money DESC')->select();
        foreach ($lists as $key => $value) {
            $lists[$key]['level'] = $key + 1;
        }
        foreach ($lists as $key => $value) {
            if ($user_id == $value['id']) {
                $user_data['user_nickname'] = $value['user_nickname'];
                $user_data['head_img'] = $value['head_img'];
                $user_data['level'] = $value['level'];
                $kk = $key - 1;
                $user_data['diff'] = $lists[$kk]['money'] - $value['money'];
            }
        }
        $dataArr['shopping_data']['lists'] = array_slice($lists, 0, $memberLimit);
        $dataArr['shopping_data']['user_data'] = $user_data;


        $lists = Db::name('user')->alias('b')->join('user_signin a', 'a.user_id=b.id', 'left')->where('b.status', 1)->field('sum(a.integral) as total,sum(1) as number,b.id,b.user_nickname,b.head_img')->group('b.id')->order('total DESC')->select();
        foreach ($lists as $key => $value) {
            $lists[$key]['level'] = $key + 1;
        }
        foreach ($lists as $key => $value) {
            if ($user_id == $value['id']) {
                $user_data['user_nickname'] = $value['user_nickname'];
                $user_data['head_img'] = $value['head_img'];
                $user_data['level'] = $value['level'];
                $kk = $key - 1;
                $user_data['diff'] = $lists[$kk]['total'] - $value['total'];
            }
        }

        $dataArr['views_data']['lists'] = array_slice($lists, 0, $memberLimit);
        $dataArr['views_data']['user_data'] = $user_data;

        return ApiReturn::r(1, $dataArr, lang('会员排行榜获取成功'));
    }

    /*
     * 保存用户手势密码
     *
     */
    public function hand_password($data = [], $user = [])
    {
        Db::name("user_info")->where([
            'user_id' => $user['id']
        ])
            ->update([
                'hand_password' => $data['hand_password']
            ]);
        return ApiReturn::r(1, [], 'ok');
    }

    /*
     * 会员认证
     *
     */
    public function certified($data = [], $user = [])
    {
        $data['card_type'] = 1;
        $data['create_time'] = time();
        $data['status'] = 0;
        $data['user_id'] = $user['id'];
        (new Certified())->insert($data);
        return ApiReturn::r(1, [], 'ok');
    }

    /*
     * 开启关闭指纹支付
     *
     */
    public function finger_payment($data = [], $user = [])
    {
        //支付密码验证
        if ($data['is_finger'] == 1) {
            $pay_password = UserModel::where(['id' => $user['id']])->value("pay_password");
            $resd = Hash::check($data['pay_password'], $pay_password);//原密码
            if (!$resd) {
                return ApiReturn::r(0, [], lang('支付密码输入错误'));
            }
        }

        UserModel::where(['id' => $user['id']])->update([
            'is_finger' => $data['is_finger']
        ]);
        return ApiReturn::r(1, [], 'ok');
    }

    /*
     * 我的足迹
     *
     */
    public function foot_print($data = [], $user = [])
    {
        $type = $data['type'] ?? 1;
        $time = $data['time'] ?? date("Y-m-d", time());
        $start_time = $data['start_time'] ?? date("Y-m-d", time());
        $end_time = $data['end_time'] ?? date("Y-m-d", time());
        $keywords = $data['keywords'] ?? '';
        $list = [];
        if ($type == 1) {
            //足迹管理1
            $collect = Collection::where([
                ['type', '=', 3],
                ['user_id', '=', $user['id']],
                ['status', '=', 1],
                ['collect_title', 'like', '%' . $keywords . '%'],

            ])
//                ->field("aid,collect_id,MAX(create_time) as max_times,create_time")
                ->order("update_time desc")
//                ->group(['FROM_UNIXTIME(create_time,\'%d\')', 'collect_id'])
                ->select()
                ->toArray();
        } elseif ($type == 2) {
            //足迹管理2

            $collect = Collection::where([
                ['type', '=', 3],
                ['user_id', '=', $user['id']],
                ['create_time', '>=', strtotime($time)],
                ['create_time', '<=', strtotime($time) + 24 * 3600],
            ])
//                ->field("aid,collect_id,MAX(create_time) as max_times,create_time")
                ->order("update_time desc")
//                ->group(['FROM_UNIXTIME(create_time,\'%d\')', 'collect_id'])
                ->select()
                ->toArray();
        } else {
            //足迹管理3

            $collect = Collection::where([
                ['type', '=', 3],
                ['user_id', '=', $user['id']],
                ['create_time', '>=', strtotime($start_time)],
                ['create_time', '<=', strtotime($end_time) + 24 * 3600],
            ])
//                ->field("aid,collect_id,MAX(create_time) as max_times,create_time")
                ->order("update_time desc")
//                ->group(['FROM_UNIXTIME(create_time,\'%d\')', 'collect_id'])
                ->select()
                ->toArray();
        }
        if ($collect) {
            foreach ($collect as &$item) {
                $goods = Db::name('goods')->where('id', $item['collect_id'])->find();
                $item['collect_img'] = get_file_url($goods['thumb']);
                $item['market_price'] = $goods['market_price'];
                $item['shop_price'] = $goods['shop_price'];
                $item['collect_sales'] = $goods['sales_sum'];
                $item['collect_title'] = $goods['name'];
                $a = date("Y");
                $month = date("n", strtotime($item['create_time']));
                $day = date("d", strtotime($item['create_time']));
                $list[$month . "月" . $day . "日"][] = $item;
            }
        }
        return ApiReturn::r(1, $list, 'ok');
    }

    /*
     * 未读消息数
     *
     */
    public function message_num($data = [], $user = [])
    {
        $num = Db::name("operation_system_message")
            ->where([
                'to_user_id' => $user['id'],
                'is_read' => 0
            ])->count();
        $list = Db::name("operation_system_message")
            ->where([
                'to_user_id' => 0,
            ])->column('id');
        $system_num = Db::name("operation_system_message")
            ->where([
                'to_user_id' => 0,
            ])->count();
        $rr = SystemMessageRead::where([['sys_msg_id','in',$list]])->where('status',1)->where('user_id',$user['id'])->count();
        $end_num = $num + ($system_num - $rr);
        //平台公告
//        $category_id = 31;
//        $num1 = Article::where([
//            ["category_id", "=", $category_id],
//            ["status", "=", 1]
//        ])->where([
//            ["id", "not in", function ($query) use ($user) {
//                $query->name("operation_article_read")->where('user_id', $user["id"])->field("article_id");
//            }]
//        ])->count();
        //聊天
        $num2 = ServiceChat::where([
            "to_id" => $user["id"],
            "status" => 1,
            "is_read" => 0
        ])->count();
        return ApiReturn::r(1, $end_num, 'ok');
    }

    /*
     * 已读
     *
     */
    public function is_read($data = [], $user = [])
    {
        Db::name("operation_system_message")
            ->where([
                'to_user_id' => $user['id'],
                'is_read' => 0
            ])->update(['is_read' => 1]);

        return ApiReturn::r(1, [], 'ok');
    }

    /*
     * 保存用户地址标签
     *
     */
    public function save_label($data = [], $user = [])
    {
        $insert = [
            'name' => $data['name'],
            'user_id' => $user['id'],
            'create_time' => time(),
            'is_public' => 0
        ];
        Db::name("user_address_label")->insert($insert);
        return ApiReturn::r(1, [], 'ok');
    }

    /*
     * 编辑用户地址标签
     *
     */
    public function edit_label($data = [], $user = [])
    {
        Db::name("user_address_label")->where([
            'user_id' => $user['id'],
            'id' => $data['id']
        ])->update(['name' => $data['name']]);
        return ApiReturn::r(1, [], 'ok');
    }

    /*
     * 获取用户地址标签
     *
     */
    public function get_label($data = [], $user = [])
    {
        $res = Db::name("user_address_label")->where([
            'user_id' => $user['id']
        ])
            ->whereOr([
                'is_public' => 1
            ])->select();
        return ApiReturn::r(1, $res, 'ok');
    }

    //获取用户是否设置密码、支付密码
    public function is_password($data = [], $user = [])
    {
        $passwordInfo = Db::name('user')->where(['id' => $user['id']])->field('password,pay_password')->find();
        $result['is_password'] = $passwordInfo['password'] ? 1 : 0;
        $result['is_pay_password'] = $passwordInfo['pay_password'] ? 1 : 0;
        return ApiReturn::r(1, $result, 'ok');
    }

    /**
     * 会员等级规则
     * @author chenchen
     * @time 2021年4月19日08:51:45
     */
    public function user_level_rule($data = [], $user = [])
    {
        $rule = module_config("user.user_level_rule") ?? '';
        return ApiReturn::r(1, ['rule' => $rule], 'ok');
    }

    /**
     * 会员权益
     * @author chenchen
     * @time 2021年4月19日09:28:03
     */
    public function user_rights($data = [], $user = [])
    {
        $list = Level::getLevelList([], "levelid,name,upgrade_score");
        if (!empty($list)) {
            foreach ($list as &$v) {
                //会员是否解锁 0未解锁1解锁
                $user_level = UserModel::where(['id' => $user['id']])->value("user_level");
                $v['is_lock'] = $user_level >= $v['levelid'] ? 1 : 0;
                //会员权益列表
                $v['rights'] = [
                    [
                        'img' => config('web_site_domain') . '/static/admin/images/member_price.png',
                        'name' => lang('会员价')
                    ]
                ];
            }
        }
        return ApiReturn::r(1, $list, 'ok');
    }

    /**验证邀请码
     * @param $data
     * @return \think\response\Json
     */
    public function verify_invite_code($data)
    {
        if (isset($data['invite_code']) && $data['invite_code'] != "") {
            $lastid = Db::name('user_info')->where('invite_code', $data['invite_code'])->value('user_id');
            if (!$lastid) {
                return ApiReturn::r(0,[], '未找到该推荐人是否继续注册');
            }else{
                return ApiReturn::r(1,[], '推荐人有效');
            }
        }
        return ApiReturn::r(1,[], '未填写推荐人,无需验证');
    }

    /**
     * 获取我的健康档案详情
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserHealthArchives($data = [],$user = []){

         $info = Db::name('user_health_archives')->where('id',$data['id'])->find();
//         $info['focus_left_sole_img'] = get_file_url($info['focus_left_sole_img']);
//         $info['focus_left_heal_img'] = get_file_url($info['focus_left_heal_img']);
//         $info['focus_rightt_heal_img'] = get_file_url($info['focus_rightt_heal_img']);
        if($info['thumb_left_status'] <= 15){
            $info['thumb_left_text'] = '正常';
        }else if($info['thumb_left_status']   > 15 && $info['thumb_left_status'] <= 30){
            $info['thumb_left_text'] = '轻度';
        }else if($info['thumb_left_status']   > 30 && $info['thumb_left_status'] <= 45){
            $info['thumb_left_text'] = '中度';
        }else if($info['thumb_left_status']   > 45 && $info['thumb_left_status'] <= 60){
            $info['thumb_left_text'] = '重度';
        }else if($info['thumb_left_status']   > 60){
            $info['thumb_left_text'] = '变形';
        }
        if($info['thumb_right_status'] <= 15){
            $info['thumb_right_text'] = '正常';
        }else if($info['thumb_right_status']   > 15 && $info['thumb_right_status'] <= 30){
            $info['thumb_right_text'] = '轻度';
        }else if($info['thumb_right_status']   > 30 && $info['thumb_right_status'] <= 45){
            $info['thumb_right_text'] = '中度';
        }else if($info['thumb_right_status']   > 45 && $info['thumb_right_status'] <= 60){
            $info['thumb_right_text'] = '重度';
        }else if($info['thumb_right_status']   > 60){
            $info['thumb_right_text'] = '变形';
        }

        if($info['heel_left_status'] <= 4 && $info['heel_left_status'] >= -4){
            $info['heel_left_text'] = '正常';
        }else if($info['heel_left_status'] > 4 && $info['heel_left_status'] <= 8){
            $info['heel_left_text'] = '外翻（轻）';
        }else if($info['heel_left_status'] < -4 && $info['heel_left_status'] >= -8){
            $info['heel_left_text'] = '内翻（轻）';
        }else if($info['heel_left_status'] < -8){
            $info['heel_left_text'] = '外翻（重）';
        }else if($info['heel_left_status'] > 8){
            $info['heel_left_text'] = '内翻（重）';
        }

        if($info['heel_right_status'] <= 4 && $info['heel_right_status'] >= -4){
            $info['heel_right_text'] = '正常';
        }else if($info['heel_right_status'] > 4 && $info['heel_right_status'] <= 8){
            $info['heel_right_text'] = '外翻（轻）';
        }else if($info['heel_right_status'] < -4 && $info['heel_right_status'] >= -8){
            $info['heel_right_text'] = '内翻（轻）';
        }else if($info['heel_right_status'] < -8){
            $info['heel_right_text'] = '外翻（重）';
        }else if($info['heel_right_status'] > 8){
            $info['heel_right_text'] = '内翻（重）';
        }

        if($info['arch_foot_left_status'] < 0.17){
            $info['arch_left_text'] = '高弓（重）';
        }else if($info['arch_foot_left_status']   >= 0.17 && $info['arch_foot_left_status'] < 0.21){
            $info['arch_left_text'] = '高弓（轻）';
        }else if($info['arch_foot_left_status']   >= 0.21 && $info['arch_foot_left_status'] <= 0.26){
            $info['arch_left_text'] = '正常';
        }else if($info['arch_foot_left_status']   > 0.26 && $info['arch_foot_left_status'] <= 0.3){
            $info['arch_left_text'] = '扁平（轻）';
        }else if($info['arch_foot_left_status']   > 0.3){
            $info['arch_left_text'] = '扁平（重）';
        }

        if($info['arch_foot_right_status'] < 0.17){
            $info['arch_right_text'] = '高弓（重）';
        }else if($info['arch_foot_right_status']   >= 0.17 && $info['arch_foot_right_status'] < 0.21){
            $info['arch_right_text'] = '高弓（轻）';
        }else if($info['arch_foot_right_status']   >= 0.21 && $info['arch_foot_right_status'] <= 0.26){
            $info['arch_right_text'] = '正常';
        }else if($info['arch_foot_right_status']   > 0.26 && $info['arch_foot_right_status'] <= 0.3){
            $info['arch_right_text'] = '扁平（轻）';
        }else if($info['arch_foot_right_status']   > 0.3){
            $info['arch_right_text'] = '扁平（重）';
        }
        $info['thumb_explain'] = '正常<轻度（15-30）<中度（30-40）<重度（40-60）<变型';
        $info['heel_explain'] = '足后跟负立线夹角在4度以内为正常状态';
        $info['arch_explain'] = '高足弓<正常（15%-45%）<扁平足';
        $info['goods_tag'] = '';
        //推送文章
        switch ($info['thumb_status']) {
            case 0:
                $info['thumb_article'] = 0;
                break;
            case 1:
                $info['thumb_article'] = 30;
                $info['goods_tag'] = $info['goods_tag'].',3';
                break;
            case 2:
                $info['thumb_article'] = 31;
                $info['goods_tag'] = $info['goods_tag'].',4';
                break;
            case 3:
                $info['thumb_article'] = 32;
                $info['goods_tag'] = $info['goods_tag'].',5';
                break;
            case 4:
                $info['thumb_article'] = 33;
                $info['goods_tag'] = $info['goods_tag'].',6';
                break;
        }
        switch ($info['heel_status']) {
            case 0:
                $info['heel_article'] = 0;
                break;
            case 1:
                $info['heel_article'] = 26;
                $info['goods_tag'] = $info['goods_tag'].',8';
                break;
            case 2:
                $info['heel_article'] = 28;
                $info['goods_tag'] = $info['goods_tag'].',7';
                break;
            case 3:
                $info['heel_article'] = 29;
                $info['goods_tag'] = $info['goods_tag'].',7';
                break;
            case 4:
                $info['heel_article'] = 27;
                $info['goods_tag'] = $info['goods_tag'].',8';
                break;
        }
        switch ($info['arch_status']) {
            case 0:
                $info['arch_article'] = 23;
                $info['goods_tag'] = $info['goods_tag'].',1';
                break;
            case 1:
                $info['arch_article'] = 22;
                $info['goods_tag'] = $info['goods_tag'].',1';
                break;
            case 2:
                $info['arch_article'] = 0;
                break;
            case 3:
                $info['arch_article'] = 24;
                $info['goods_tag'] = $info['goods_tag'].',2';
                break;
            case 4:
                $info['arch_article'] = 25;
                $info['goods_tag'] = $info['goods_tag'].',2';
                break;
        }
        if($info['goods_tag'] != ''){
            $info['goods_tag'] = trim($info['goods_tag'],',');
        }else{
            $info['goods_tag'] = '0';
        }
        return ApiReturn::r(1,$info,'获取成功');
    }

    /**
     *用户将抗报告列表
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function userHealthArchivesList($data = [],$user = []){
        $list = Db::name('user_health_archives')->where('user_id',$user['id'])->field('id,create_time')->order('create_time desc')->paginate()->each(function($query){
            $query['archives_name'] = "我的专属脚型报告";
            $query['create_time'] = date('Y-m-d H:i:s',$query['create_time']);
            return $query;
        });

        return ApiReturn::r(1,$list,'获取成功');
    }
}
