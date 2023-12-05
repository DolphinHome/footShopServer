<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\common\model\GoodsAnswer;
use app\common\model\GoodsQuestion;
use app\goods\model\Goods as GoodsModel;
use app\user\model\User;
use service\ApiReturn;
use think\Db;
use app\goods\model\GoodsCollect;

/**
 * 商品问答模块
 * @author zhougs
 * @time 2020年12月29日10:29:47
 * @package app\api\controller\v1
 */
class GoodsQandA extends Base
{
    /**
     * 提出问题
     * @param $data
     * @param $user
     * @author zhogus
     * @since 2020年12月29日10:48:29
     * @return \think\response\Json
     */
    public function goodsQuestion($data, $user)
    {
        if (empty($data)) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $goods = GoodsModel::where("id", $data['goods_id'])->field("name,thumb")->find();
        $data['goods_name'] = $goods['name'];
        $data['goods_thumb'] = $goods['thumb'];
        $data['user_id'] = $user['user_id'];
        $data['create_time'] = time();
        $res = GoodsQuestion::insertGetId($data);
        if ($res) {
            return ApiReturn::r(1, [], lang('提交成功'));
        }
        return ApiReturn::r(0, [], lang('提交失败'));
    }

    /**
     * 回答问题
     * @param $data
     * @param $user
     * @author zhogus
     * @since 2020年12月29日10:48:29
     * @return \think\response\Json
     */
    public function goodsAnswer($data, $user)
    {
        if (empty($data)) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $data['user_id'] = $user['user_id'];
        $data['create_time'] = time();
        $res = GoodsAnswer::insertGetId($data);
        if ($res) {
            return ApiReturn::r(1, [], lang('回答成功'));
        }
        return ApiReturn::r(0, [], lang('回答失败'));
    }

    /**
 * 问答列表
 * @param $data
 * @param $user
 * @author zhogus
 * @since 2020年12月29日14:08:03
 * @return \think\response\Json
 * @throws \think\exception\DbException
 */
    public function getQuestionList($data, $user)
    {
        if (!$data['goods_id']) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $where[] = ["goods_id","=",$data['goods_id']];
        if ($data['question_content']) {
            $where[] = ["q.content","like",'%'.$data['question_content'].'%'];
        }
        $list = [];
        $list =GoodsQuestion::alias("q")->join("goods_answer a", "q.id=a.question_id and a.status=1", "left")
            ->field("q.id,q.goods_id,q.goods_name,q.goods_thumb,q.content question_content,a.content answer_content,count(a.user_id) answer_number,activity_id")
            ->where($where)
            ->where(['q.status'=>1])
            ->group("q.id")
            ->order("q.create_time DESC")
            ->paginate();
        return ApiReturn::r(1, $list, lang('获取成功'));
    }
    /**
     * 问答详情
     * @param $data
     * @param $user
     * @author zhogus
     * @since 2020年12月29日14:08:03
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getAnswerList($data, $user)
    {
        if (!$data['question_id']) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        $resData = [];
        $questionInfo = [];
        $questionInfo =GoodsQuestion::where("id", $data['question_id'])
            ->field("id,goods_id,goods_name,goods_thumb,content question_content,create_time,user_id,activity_id,is_anonymous")
            ->find();
        if ($questionInfo['is_anonymous'] == 1) {
            $questionInfo['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
            $questionInfo['user_nickname'] = lang('匿名用户');
        } else {
            $userInfo = Db::name('user')->where(['id'=>$questionInfo['user_id']])->field('head_img,user_nickname')->find();
            $questionInfo['head_img'] = get_file_url($userInfo['head_img']);
            $questionInfo['user_nickname'] = $userInfo['user_nickname'];
        }
        //是否关注
        $questionInfo['is_follow'] = GoodsCollect::isCollection($user['id'], 1, $questionInfo['id']);
        $questionInfo['goods_thumb'] = get_file_url($questionInfo['goods_thumb']);
        $resData["question_info"] = $questionInfo;
        $list = GoodsAnswer::alias("ga")
            ->join("user u", "ga.user_id = u.id", "left")
            ->where("ga.question_id", $data['question_id'])
            ->where("ga.status", 1)
            ->field("ga.id,ga.content answer_content,ga.question_id,ga.user_id,u.head_img,u.user_nickname,ga.create_time,ga.is_anonymous")
            ->order("ga.create_time DESC")
            ->paginate();
        if (count($list) > 0) {
            foreach ($list as $key=>$value) {
                //匿名
                if ($value['is_anonymous'] == 1) {
                    $list[$key]['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
                    $list[$key]['user_nickname'] = lang('匿名用户');
                } else {
                    $list[$key]['head_img'] = get_file_url($value['head_img']);
                }
                if (strpos($list[$key]['head_img'], 'images/none.png') !== false) {
                    $list[$key]['head_img'] = config('web_site_domain') . '/static/admin/images/user_none.png';
                }
                //是否点赞
                $list[$key]['is_likes'] = GoodsCollect::isCollection($user['id'], 2, $value['id']);
                //点赞数
                $list[$key]['like_num'] = GoodsCollect::collectionNum($value['id'], 2);
            }
        }
        $resData["answer_list"] = $list;
        return ApiReturn::r(1, $resData, lang('获取成功'));
    }
    /**
     * 添加/取消关注、点赞（本接口适用于商品问答，问题关注，回答点赞）
     * @param array $data
     * @param array $user
     * @author zhougs
     * @created 2020年12月30日11:07:57
     */
    public function set_collection($data = [], $user = [])
    {
        $res = GoodsCollect::isCollection($user['id'], $data['type'], $data['collect_id']);
        if ($res) {
            // 取消点赞/关注
            $ret = GoodsCollect::delCollection($user['id'], $data['type'], $data['collect_id']);
            if ($ret) {
                return ApiReturn::r(1, ['is_collection' => 0], lang('已取消'));
            }
        } else {
            $data['user_id'] = $user['id'];
            $ret = GoodsCollect::create($data);
            if ($ret) {
                return ApiReturn::r(1, ['is_collection' => 1], lang('成功'));
            }
        }
        return ApiReturn::r(0, [], lang('操作失败'));
    }

    /**
     * 我的问答列表
     * @param array $data
     * @param array $user
     * @author zhougs
     * @created 2020年12月30日15:21:50
     */
    public function getMyQandA($data, $user)
    {
        $resData = [];

        switch ($data['type']) {
            //提问数
            case "question":
                $resData =GoodsQuestion::alias("q")->join("goods_answer a", "q.id=a.question_id", "left")
                    ->field("q.id question_id,q.goods_id,q.goods_name,q.goods_thumb,q.content question_content,a.content answer_content,count(a.user_id) answer_number,q.create_time,activity_id")
                    ->where("q.user_id", $user['id'])
                    ->where(['q.status'=>1,'a.status'=>1])
                    ->group("q.id")
                    ->order("q.create_time DESC")
                    ->paginate()
                    ->each(function ($item) {
                        $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                        return $item;
                    });
                break;
            //回答数
            case "answer":
                $resData =GoodsQuestion::alias("q")->join("goods_answer a", "q.id=a.question_id", "left")
                    ->field("a.question_id,a.id answer_id,q.goods_id,q.goods_name,q.goods_thumb,q.content question_content,a.content answer_content,activity_id")
                    ->where("a.user_id", $user['id'])
                    ->where(['q.status'=>1,'a.status'=>1])
                    ->group("q.id")
                    ->order("a.create_time DESC")
                    ->paginate()
                    ->each(function ($item) {
                        $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                        return $item;
                    });
                break;
            //关注数
            case "follow":
                $resData =GoodsCollect::alias("gc")
                    ->join("goods_question q", "q.id = gc.collect_id", "left")
                    ->join("goods_answer a", "a.question_id = q.id", "left")
                    ->field("q.id question_id,q.goods_id,q.goods_name,q.goods_thumb,q.content question_content,count(a.user_id) answer_number,q.create_time,activity_id")
                    ->where(["gc.user_id"=>$user['id'],"gc.type"=>1])
                    ->where(['q.status'=>1,'a.status'=>1])
                    ->group("q.id")
                    ->order("q.create_time DESC")
                    ->paginate()
                    ->each(function ($item) {
                        $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                        return $item;
                    });
                break;
            //点赞数
            case "likes":
                $resData =GoodsCollect::alias("gc")
                    ->join("goods_answer a", "gc.collect_id=a.id", "left")
                    ->join("goods_question q", "q.id=a.question_id", "left")
                    ->field("q.id question_id,a.id answer_id,q.goods_id,q.goods_name,q.goods_thumb,q.content question_content,
                    a.content answer_content,count(a.user_id) answer_number,a.user_id answer_uid,gc.id,activity_id")
                    ->where(["gc.user_id"=>$user['id'],"gc.type"=>2])
                    ->where(['q.status'=>1,'a.status'=>1])
                    ->group("q.id")
                    ->order("gc.create_time DESC")
                    ->paginate()
                    ->each(function ($item) {
                        $item['goods_thumb'] = get_file_url($item['goods_thumb']);
                        $item['user_nickname'] = User::where("id", $item['answer_uid'])->value("user_nickname");
                        return $item;
                    });
                break;
        }
        $qAndA = [];
        $qAndA['title_num'] = self::getTitleNum($user);
        $qAndA['list'] = $resData;
        return ApiReturn::r(1, $qAndA, lang('获取成功'));
    }

    /**
     * 获取标题对应数量
     * @param $user
     * @return array
     * @author zhogus
     * @since 2020年12月30日17:08:05
     */
    protected function getTitleNum($user)
    {
        //提问数
        $resData = [];
        $resData["question_num"] = GoodsQuestion::alias("q")
            ->join("goods_answer a", "q.id=a.question_id", "left")
            ->where("q.user_id", $user['id'])
            ->where(['q.status'=>1,'a.status'=>1])
            ->group("q.id")
            ->order("q.create_time")
            ->count("q.id");
        //回答数
        $resData['answer_num'] =GoodsQuestion::alias("q")
            ->join("goods_answer a", "q.id=a.question_id", "left")
            ->where("a.user_id", $user['id'])
            ->where(['q.status'=>1,'a.status'=>1])
            ->group("q.id")
            ->order("a.create_time")
            ->count("q.id");
        //关注数
        $resData['follow_num'] =GoodsCollect::alias("gc")
            ->join("goods_question q", "q.id = gc.collect_id", "left")
            ->join("goods_answer a", "a.question_id = q.id", "left")
            ->where(["gc.user_id"=>$user['id'],"gc.type"=>1])
            ->where(['q.status'=>1,'a.status'=>1])
            ->group("q.id")
            ->order("q.create_time")
            ->count("gc.id");
        //点赞数
        $resData['likes_num'] =GoodsCollect::alias("gc")
            ->join("goods_answer a", "gc.collect_id=a.id", "left")
            ->join("goods_question q", "q.id=a.question_id", "left")
            ->where(["gc.user_id"=>$user['id'],"gc.type"=>2])
            ->where(['q.status'=>1,'a.status'=>1])
            ->field("q.id question_id,a.id answer_id,q.goods_id,q.goods_name,q.goods_thumb,q.content question_content,
            a.content answer_content,count(a.user_id) answer_number,a.user_id answer_uid,gc.id,activity_id")
            ->group("q.id")
            ->count("gc.id");
        return $resData;
    }

    /**
     * 删除问题/回答
     * @param $data
     * @param $user
     * @return \think\response\Json
     * @author zhougs
     * @since 2020年12月30日18:35:48
     */
    public function deleteQandA($data, $user)
    {
        if (empty($data)) {
            return ApiReturn::r(0, [], lang('参数错误'));
        }
        Db::startTrans();
        try {
            //删除问题及所有回复
            if ($data['type'] == 1) {
                $qres = GoodsQuestion::where("id", $data['delete_id'])->delete();
                if (!$qres) {
                    exception(lang('删除问题失败'));
                }
                $goodsAnswer = GoodsAnswer::where("question_id", $data['delete_id'])->select();
                foreach ($goodsAnswer as $good) {
                    $res = GoodsCollect::where('collect_id', $good['id'])->delete();
                    if ($res===false) {
                        exception(lang('删除问题点赞失败'));
                    }
                }
                $res = GoodsAnswer::where("question_id", $data['delete_id'])->delete();
                if ($res===false) {
                    exception(lang('删除问题相关回答失败'));
                }
            }
            //删除回复
            if ($data['type'] == 2) {
                $goodsAnswer = GoodsAnswer::where(['question_id'=>$data['delete_id'],'user_id'=>$user['id']])->select();
                foreach ($goodsAnswer as $good) {
                    $res = GoodsCollect::where('collect_id', $good['id'])->delete();
                    if ($res===false) {
                        exception(lang('删除问题点赞失败'));
                    }
                }
                $res = GoodsAnswer::where(['question_id'=>$data['delete_id'],'user_id'=>$user['id']])->delete();
                if (!$res) {
                    exception(lang('删除回答失败'));
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        return ApiReturn::r(1, [], lang('删除成功'));
    }
}
