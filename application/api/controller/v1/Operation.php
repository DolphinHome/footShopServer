<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\operation\model\Ads as AdsModel;
use app\operation\model\Nav as NavModel;
use app\operation\model\ServiceChat;
use app\user\model\Collection as CollectionModel;
use app\operation\model\Suggestions;
use app\operation\model\SuggestionsType;
use app\operation\model\ArticleColumn;
use app\operation\model\Article;
use app\operation\model\SystemMessage;
use app\operation\model\SystemMessageRead;
use service\ApiReturn;
use think\Db;
use think\facade\Request;
use app\operation\model\Servicereply;
use think\helper\Hash;

/**
 * 运营广告接口
 * Class Ads
 * @package app\api\controller\v1
 */
class Operation extends Base
{
    /**
     * 获取指定广告位的广告列表
     * @param array $data 参数
     * @param array $user
     * @return json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_ads($data = [], $user = [])
    {
        $result = AdsModel::where(['typeid' => $data['typeid'], 'status' => 1])->order('sort asc,id desc')->select()->toArray();
        if (count($result) >= 1) {
            foreach ($result as &$v) {
                if ($v['thumb']) {
                    $v['thumb'] = get_file_url($v['thumb']);
                }
                if ($v['video']) {
                    $v['video'] = get_file_url($v['video']);
                }
                if ($data['typeid'] == 2) {
                    $v['rgb'] = json_decode($v['rgb']);
                }
//                halt($this->fname);
//                $v = $this->filter($v, $this->fname);
            }
            return ApiReturn::r(1, $result, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));
    }

    public function img($img)
    {
        $rTotal = 0;
        $gTotal = 0;
        $bTotal = 0;
        $total = 0;
        $i = imagecreatefrompng($img);
        if (!$i) {
            $i = imagecreatefromjpeg($img);
        }
        for ($x = 0; $x < imagesx($i); $x++) {
            for ($y = 0; $y < imagesy($i); $y++) {
                $rgb = imagecolorat($i, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $rTotal += $r;
                $gTotal += $g;
                $bTotal += $b;
                $total++;
            }
        }
        if ($total == 0) {
            $rAverage = 0;
            $gAverage = 0;
            $bAverage = 0;
        } else {
            $rAverage = round($rTotal / $total);
            $gAverage = round($gTotal / $total);
            $bAverage = round($bTotal / $total);
        }

        return [$rAverage, $gAverage, $bAverage];
    }

    /**
     * Notes:获取广告位详情，主要用于PV统计
     * User: php迷途小书童
     * Date: 2020/8/24
     * Time: 17:43
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_ads_detail($data = [], $user = [])
    {
        //统计
        $date = date('Y-m-d');
        $month = date('Y-m');
        $ip = get_client_ip();
        $way = Request::header('way');
        $user_id = 0;
        $sex = 1;
        $area = '';

        //查询用户信息
        if (isset($data['user_id']) && $data['user_id'] > 0) {
            $user = Db::name('user')
                ->alias('a')
                ->join('user_info b', 'b.user_id = a.id')
                ->field('a.sex')
                ->field('b.address')
                ->find();
            $user_id = $data['user_id'];
            $sex = $user['sex'];
            $area = $user['address'];
        }

        Db::name('operation_ads_pv_data')->insert([
            'ip' => $ip,
            'user_id' => $user_id,
            'typeid' => $data['typeid'] ?? 0,//所属类型
            'adsid' => $data['adsid'] ?? 0,//广告位ID
            'sex' => $sex ?? 1,
            'way' => $way ?? 0,
            'area' => $area ?? 0,
            'date' => $date,
            'month' => $month,
            'create_time' => time()
        ]);

        return ApiReturn::r(1, [], lang('记录成功'));
    }

    /**
     * 获取指定导航位的导航列表
     * @param string $data 参数
     * @return json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function get_nav($data = '')
    {
        $result = NavModel::where(['typeid' => $data['typeid'], 'status' => 1])->select()->toArray();
        if (count($result) >= 1) {
            foreach ($result as &$v) {
                if ($v['thumb']) {
                    $v['thumb'] = get_file_url($v['thumb']);
                }
                $v = $this->filter($v, $this->fname);
            }
            return ApiReturn::r(1, $result, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));
    }

    /**
     * 投诉建议列表反馈
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @since 2019/4/20 17:28
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function suggestions($data = [], $user = [])
    {
        $result = Suggestions::where('user_id', $user['id'])->order('id desc')->select()->each(function ($item) {
            $item['type'] = SuggestionsType::where('id', $item['type'])->value('title');
            if ($item['thumb']) {
                $item['thumb'] = get_files_url($item['thumb']);
            }
            return $item;
        });
        if ($result) {
            return ApiReturn::r(1, $result, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));
    }

    /**
     * 获取投诉建议配置参数
     * *@param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @since 2019/4/20 17:28
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function suggestions_type()
    {
        $suggestions_contact_status = \app\admin\model\Module::getConfig("operation", "suggestions_contact_status");
        $suggestions_thumb_status = \app\admin\model\Module::getConfig("operation", "suggestions_thumb_status");
        $suggestions_qq_status = \app\admin\model\Module::getConfig("operation", "suggestions_qq_status");
        $suggestions_email_status = \app\admin\model\Module::getConfig("operation", "suggestions_email_status");

        $is_must_qq = \app\admin\model\Module::getConfig("operation", "is_must_qq");
        $is_must_email = \app\admin\model\Module::getConfig("operation", "is_must_email");
        $is_must_phone = \app\admin\model\Module::getConfig("operation", "is_must_phone");


        $arr = SuggestionsType::where('status', 1)->field('id,title')->select();
        $result = [
            "contact_status" => $suggestions_contact_status ?: 0,
            "thumb_status" => $suggestions_thumb_status ?: 0,
            "qq_status" => $suggestions_qq_status ?: 0,
            "email_status" => $suggestions_email_status ?: 0,
            "is_must_qq" => $is_must_qq ?: 0,
            "is_must_email" => $is_must_email ?: 0,
            "is_must_phone" => $is_must_phone ?: 0,
            "types" => $arr ?: [],
        ];

        return ApiReturn::r(1, $result, lang('请求成功'));
    }

    /**
     * 添加投诉建议
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @since 2019/4/20 17:28
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function add_suggestions($data = [], $user = [])
    {
        $data['user_id'] = $user['id'] ? $user['id'] : 0;
        $data['body'] = $this->userTextEncode($data['body']);
        $result = Suggestions::create($data);
        if ($result) {
            return ApiReturn::r(1, [], lang('提交成功'));
        }
        return ApiReturn::r(0, [], lang('提交失败'));

    }

    public function userTextEncode($str)
    {
        if (!is_string($str)) {
            return $str;
        }
        if (!$str || $str == 'undefined') {
            return '';
        }
        $text = json_encode($str);
        $text = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($str) {
            return addslashes($str[0]);
        }, $text);
        return json_decode($text);
    }

    /**
     * 获取指定的单页分类信息
     * @param array $data
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/8/26 19:12
     * @editor XX [ xxx@qq.com ]
     * @updated 2019.03.30
     */
    public function get_column($data = [])
    {
        $result = ArticleColumn::getInfo($data['category_id']);
        if ($result) {
            $result['cat_img'] = get_file_url($result['cat_img']);
            $result = $this->filter($result, $this->fname);
            return ApiReturn::r(1, $result, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));

    }

    /**
     * 获取指定栏目的文章列表
     * @param array $data
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/8/26 19:12
     */
    public function get_column_article_list($data = [], $user = [])
    {
        if (empty($data)) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        if ($data['category_id']) {
            $map[] = ['oa.category_id', '=', $data['category_id']];
        }

        if ($data['type'] == 1) {
            if ($data['keyword']) {
                $map[] = ['oa.title', 'like', '%' . $data['keyword'] . '%'];
            } else {
                return ApiReturn::r(1, [], lang('请求成功'));
            }
        }
        if ($data['is_recommend']) {
            $map[] = ['oa.is_recommend', '=', $data['is_recommend']];
        }

        $result = Article::getList($map, "oa.create_time DESC", $user);
        if ($result) {
            return ApiReturn::r(1, $result, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));

    }

    /**
     * 保存文章举报信息
     * @param array $data
     * @return void
     * @author zhogus [ zhougs110@163.com ]
     * @created 2020-9-22 20:13:45
     */
    public function add_article_report($data = [], $user = [])
    {
        if (empty($data)) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }

        $insert_data = [
            "be_report_article_id" => intval($data['be_report_article_id']),
            "report_type" => intval($data['report_type']),
            "report_user_id" => $user['id'],
            "remark" => htmlspecialchars($data['remark']),
        ];

        //举报审核期间不能再次申请
        $check = Db::name("operation_article_report")
            ->where([
                'report_user_id' => $user['id'],
                'be_report_article_id' => $data['be_report_article_id'],
                'status' => 0,
                'is_del' => 0
            ])->find();
        if ($check) {
            return ApiReturn::r(0, [], lang('您提交的举报内容正在审核中，请耐心等待'));
        }


        $result = Db::name("operation_article_report")->insert($insert_data);
        if ($result) {
            return ApiReturn::r(1, $result, lang('保存成功'));
        }
        return ApiReturn::r(0, [], lang('保存失败'));

    }

    /**
     * 获取举报类型列表
     * @param array $data
     * @return void
     * @author zhogus [ zhougs110@163.com ]
     * @created 2020-9-22 20:13:45
     */
    public function get_article_report_type($data = [])
    {
        $result = Db::name("operation_article_report_type")->where(["status" => 1])->select();
        if ($result) {
            return ApiReturn::r(1, $result, lang('获取成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));

    }

    /**
     * 获取指定的单页分类信息
     * @param array $data
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/8/26 19:12
     * @editor XX [ xxx@qq.com ]
     * @updated 2019.03.30
     */
    public function get_article_detail($data = [], $user = [])
    {
        $result = Article::getOne($data['id']);
        if ($result) {
            $user = \app\common\model\Api::get_user_info();
            $result['img_url'] = get_file_url($result['img_url']);
            $result['add_time'] = date('Y-m-d H:i:s', $result['add_time']);
            Article::where('id', $data['id'])->setInc('click_count');
            $result['like_num'] = Db::name('user_article_like')->where('article_id', $data['id'])->count();
            $is_find = Db::name('user_article_like')->where('article_id', $data['id'])->where('user_id', $user['id'])->column('id');
            $result['is_like'] = $is_find ? 1 : 0;
            $result['comment_num'] = Db::name('operation_article_comment')->where('id', $data['id'])->count();
            //根据token获取用户信息
            $user = \app\common\model\Api::get_user_info();
            $result['is_collection'] = CollectionModel::isCollection($user['id'], 2, $data['id']);
            $result['collection_num'] = CollectionModel::collectionNum($data['id'], 2);
            return ApiReturn::r(1, $result, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('暂无数据'));

    }

    /**
     * 获取站内信类型列表
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @since 2019/4/9 16:16
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getSystemMsgType($data, $user)
    {
        if (!$user) {
            return ApiReturn::r(0, '', lang('请开启USER授权'));
        }
        $user_id = $user['id'];
        $new_msg_num = 0;
        $types = SystemMessage::$msgtype;
        $info = [];
        foreach ($types as $id => $name) {
            $row['msg_type'] = $id;
            $row['name'] = $name;
            $row['new_msg'] = [];
            $new = SystemMessage::getNew($user_id, $id);
            if ($new) {
                $new['creates_time'] = date('H:i',strtotime($new['create_time']));
//                dump(date('H:i',strtotime($new['create_time'])));die;
                if($id == 3){
                    $new['fit_title'] =$new['title'];
                }
                $row['new_msg'] = $this->filter($new, $this->fname);
            }
            if($id == 1){
                $user_message = Db::name('operation_system_message')->where(['is_read'=>0,'msg_type'=>$id,'to_user_id'=>$user['id']])->count();
                $system_num = Db::name('operation_system_message')->where(['msg_type'=>$id,'to_user_id'=>0])->count();
                $system_list = Db::name('operation_system_message')->where(['msg_type'=>$id,'to_user_id'=>0])->column('id');
                $system_read = SystemMessageRead::where([['sys_msg_id','in',$system_list],['user_id','=',$user['id']]])->count();
                $row['new_msg_num'] = $user_message + $system_num - $system_read;

            }else {
                $row['new_msg_num'] = Db::name('operation_system_message')->where(['is_read'=>0,'msg_type'=>$id,'to_user_id'=>$user['id']])->count();

            }
            $info[] = $row;
        }
        return ApiReturn::r(1, $info, lang('请求成功'));
    }

    /**
     * 获取指定类型的消息列表
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @since 2019/4/9 16:17
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function getSystemMsgList($data, $user)
    {
        $user_id = $user['id'];
        $msgtype = $data['msgtype'];
        $dataList = SystemMessage::getList($user_id, $msgtype, $data['page']);
        //更改消息为已读
        SystemMessage::where([
            'to_user_id' => $user_id,
            'msg_type' => $msgtype
        ])->update(['is_read' => 1]);
        return ApiReturn::r(1, $dataList, lang('请求成功'));
    }

    /**
     * 删除站内信
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @since 2019/4/9 16:17
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function delSystemMsg($data, $user)
    {
        $user_id = $user['id'];
        $msg_type = $data["msg_type"];
        // 启动事务
        Db::startTrans();
        try {
            if ($msg_type == 1) {
                //平台公告
                Article::where(["id" => $data["id"]])->delete();
                SystemMessageRead::delMsg($data['id']);
            } else {
                $msg = SystemMessage::get($data['id']);
                if (!$msg) {
                    $res = SystemMessageRead::delMsg($data['id']);
                    if (!$res) {
                        return ApiReturn::r(1, [], lang('没有查询到此消息'));
                    }
                }
                if ($msg['type'] > 1) {
                    $read = SystemMessageRead::getread($user_id, $data['id']);
                    if (!$read) {
                        $read = SystemMessageRead::setread($user_id, $data['id']);
                    }
                    $res = SystemMessageRead::delread($read['aid']);


                    if (!$res || !$read) {
                        exception(lang('删除失败'));
                    }
                } else {
                    if ($msg['to_user_id'] == $user['id']) {
                        $res = SystemMessage::where("id", $data['id'])->delete();
                        $res1 = SystemMessageRead::delMsg($data['id']);
                        if (!$res || !$res1) {
                            exception(lang('删除失败'));
                        }
                    }
                }
            }

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }

        return ApiReturn::r(1, [], lang('删除成功'));
    }

    /**
     * 获取指定客服的详情
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/9/25 18:49
     */
    public function customer_detail($data, $user)
    {
        $info = \app\operation\model\Service::where('id', $data['id'])->field('password', true)->find();
        if ($info) {
            $info['avatar'] = get_file_url($info['avatar']);
            return ApiReturn::r(1, $this->filter($info, $this->fname), lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('没有此客服'));
    }

    /**文章分类
     * author
     * createDay 2020/8/29
     * createTime 17:14
     * return \think\response\Json
     */
    public function get_article_column()
    {
        $data = Db::name('operation_article_column')->where([
            'type' => 0,
            'status' => 1,
            'hide' => 0,
            'pid' => 0
        ])->order("sort desc")->select();
        return ApiReturn::r(1, $data, lang('文章分类'));
    }


    /**
     * 文章点赞、取消点赞
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/9/21 23:22
     */
    public function article_like_do($data = [], $user = [])
    {
        $res = Db::name('user_article_like')->where(['article_id' => $data['article_id'], 'user_id' => $user['id']])->count();
        if ($res) {
            $re = Db::name('user_article_like')->where(['article_id' => $data['article_id'], 'user_id' => $user['id']])->delete();
            if ($re) {
                return ApiReturn::r(1, ['status' => 0], lang('点赞取消成功'));
            }
        } else {
            $info = [
                'create_time' => time(),
                'article_id' => $data['article_id'],
                'user_id' => $user['id'],

            ];
            $re = Db::name('user_article_like')->insert($info);
            if ($re) {
                return ApiReturn::r(1, ['status' => 1], lang('点赞成功'));
            }
            return ApiReturn::r(0, [], lang('点赞失败'));
        }
    }

    /**获取文章评论
     * author 刘旗
     * createDay 2020/8/29
     * createTime 17:32
     * @param array $data
     */
    public function get_article_comment($data = [], $user = [])
    {
        $article_id = $data['article_id'];
        $re = \think\Db::name('operation_article_comment')->alias('a')->field('a.id,a.content,a.create_time,u.user_nickname,u.head_img')->join('user u', 'u.id = a.user_id', 'left')->where('a.article_id', $article_id)->order('a.id desc')->paginate()->each(function ($item) {
            $item['create_time'] = format_time($item['create_time'], 'Y-m-d H:i');
            $item['head_img'] = get_file_url($item['head_img']);
            return $item;
        });
        if (!$re) {
            return ApiReturn::r(1, [], lang('文章评论'));
        }
        return ApiReturn::r(1, $re, lang('文章评论'));
    }

    /**添加文章评论
     * author 刘旗
     * createDay 2020/8/29
     * createTime 17:28
     */
    public function article_comment_add($data = [], $user = [])
    {
        $info = [
            'content' => $data['content'],
            'article_id' => $data['article_id'],
            'user_id' => $user['id'],
            'create_time' => time(),
        ];
        $re = Db::name('operation_article_comment')->insert($info);
        if ($re) {
            return ApiReturn::r(1, [], lang('添加成功'));
        }
        return ApiReturn::r(0, [], lang('添加失败'));
    }

    /**
     * 获取聊天服务标签
     * @param $partner_id int 商户ID
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2020年12月30日15:00:06
     */
    public function getPartnerServiceLabels($requests = [])
    {
        // 模拟参数
        // $requests = array(
        //     'partner_id' => 0
        // );

        // 获取参数
        $partnerId = empty($requests['partner_id']) ? 0 : $requests['partner_id'];

        // 获取商户已配置的客服服务标签
        $getServiceLabels = Servicereply::getServiceLabels($partnerId);

        return ApiReturn::r(1, $getServiceLabels, lang('获取聊天服务标签成功'));
    }

    /**
     * 客服同步注册
     * @param $requests .username string 账号
     * @param $requests .password string 密码
     * @param $requests .partner_id int 商户ID
     * @author 曾虎 [ 1427305236@qq.com ]
     * @since 2021年1月29日14:25:52
     */
    public function insertCustomerService($requests = [])
    {
        // 切换客服服务数据库
        $operationService = Db::connect('mysql://zb_mkh:d2CNerP2CSkS3242@47.92.235.222:3306/zb_mkh#utf8')->table('lb_operation_service');

        // 查询是否存在客服
        $find = $operationService->where(['username' => $requests['username']])->find();
        if ($find) {
            return ApiReturn::r(0, [], lang('客服已存在，请重新注册'));
        }

        // 处理登录凭证
        $signature = md5($requests['username']);

        // 客服注册--因此接口能传输信息有限，在不影响原有流程的情况下暂时如此处理
        $re = $operationService->insert([
            'nickname' => $requests['username'], // 客服昵称
            'username' => $requests['username'], // 客服账号
            'password' => Hash::make((string)$requests['password']), // 登录密码
            'group' => 0, // 所属分组
            'avatar' => 0, // 客服头像
            'partner_id' => $requests['partner_id'], // 商户ID
            'service_number' => 5, // 客服服务人数（默认服务5人）
            'signature' => $signature, // 登录凭证
            'status' => 1, // 客服状态
            'create_time' => time(), // 创建时间
        ]);
        if (!$re) {
            return ApiReturn::r(0, [], lang('客服注册失败'));
        }

        return ApiReturn::r(1, ['signature' => $signature], lang('同步注册客服成功'));
    }

    /*
     * 砍价规则
     *
     */
    public function bargain_rule($data = [], $user = [])
    {
        $res = module_config('operation.bargain_rule') ?? '';
        return ApiReturn::r(1, $res, 'ok');
    }

    /**
     * 获取消息详情
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function msgInfo($data = [], $user = []){
        $res = db('operation_system_message')->where(['id'=>$data['id']])->field('id,title,content,create_time')->find();
        db('operation_system_message')->where(['id'=>$data['id']])->setField('is_read',1);
        $res['create_time'] = date('Y-m-d H:i',$res['create_time']);
        if(!$res){
            return ApiReturn::r(0, [], '获取失败！');
        }
        return ApiReturn::r(1, ['data'=>$res], '获取成功！');
    }
}
