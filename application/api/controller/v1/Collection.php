<?php
/**
 * Notes:
 * User: lv
 * Date: 2020/8/26
 * Time: 18:44
 * @return
 */

namespace app\api\controller\v1;

use app\api\controller\Base;
use app\collection\model\CollectionCancel;
use app\collection\model\Collection as CollectionModel;
use app\collection\model\FollowCancel;
use app\collection\model\Follow as FollowModel;
use service\ApiReturn;
use think\Db;
use think\Exception;

class Collection extends Base
{
    public function index()
    {
    }

    /**
     * Notes:获取我的收藏
     * User: lv
     * Date: 2020/8/26
     * Time: 18:56
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     * @throws \think\exception\DbException
     */
    public function get_collection_list($data = [], $user = [])
    {
        $result = CollectionModel::get_user_collection($user['id'], $data['type']);

        if ($result) {
            return ApiReturn::r(1, $result, lang('请求成功'));
        } else {
            return ApiReturn::r(1, [], lang('暂无数据'));
        }
    }

    /**
     * Notes:添加/取消收藏
     * User: lv
     * Date: 2020/8/27
     * Time: 9:46
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws Exception
     */
    public function set_collection($data = [], $user = [])
    {
        $result = CollectionModel::isCollection($user['id'], $data['type'], $data['collect_id']);
        $data['user_id'] = $user['id'];
        // 启动事务
        Db::startTrans();
        try {
            if ($result) {
                //取消收藏
                if ($result['is_cancel'] == 1) {
                    //查看是否取消过
                    $res = CollectionCancel::is_cancel($user['id'], $data['type'], $data['collect_id']);
                    if ($res) {
                        $ret = CollectionCancel::where(['user_id'=>$user['id'], 'collect_id'=>$data['collect_id'],'type'=>$data['type']])->update(['update_time'=>time()]);
                        if (!$ret) {
                            exception(lang('更新失败'));
                        }
                    } else {
                        //首次添加
                        $ret = CollectionCancel::create($data);
                        if (!$ret) {
                            exception(lang('取消收藏失败'));
                        }
                    }
                    $res = CollectionModel::where(['user_id'=>$user['id'], 'collect_id'=>$data['collect_id'],'type'=>$data['type']])->update(['is_cancel'=>0]);
                    if (!$res) {
                        exception(lang('更新收藏失败'));
                    }
                } else {
                    //更新收藏表
                    $res = CollectionModel::where(['user_id'=>$user['id'], 'collect_id'=>$data['collect_id'],'type'=>$data['type']])->update(['is_cancel'=>1]);
                    if (!$res) {
                        exception(lang('更新收藏失败'));
                    }
                }
            } else {
                //首次添加收藏
                $ret = CollectionModel::create($data);
                if (!$ret) {
                    exception(lang('添加收藏失败'));
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        $is_cancel = CollectionModel::where(['user_id'=>$user['id'], 'collect_id'=>$data['collect_id'],'type'=>$data['type']])->value('is_cancel');
        return ApiReturn::r(1, ['is_cancel'=>$is_cancel], lang('提交成功'));
    }


    /**
     * Notes:关注&取消关注
     * User: lv
     * Date: 2020/8/27
     * Time: 11:38
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     */
    public function set_follow($data = [], $user = [])
    {
        $fans_id = $data['fans_id'];//关注id（我的粉丝）
        $user_id = $user['id']; //我的id
        $data['user_id'] = $user['id'];
        if ($user_id == $fans_id) {
            return ApiReturn::r(0, '', lang('你不能自己关注自己'));
        }

        $result = \app\collection\model\Follow::isFollow($user_id, $fans_id);
        // 启动事务
        Db::startTrans();
        try {
            if ($result) {
                //取消收藏
                if ($result['is_follow'] == 1) {
                    //查看是否取消过
                    $res = FollowCancel::is_can_follow($user_id, $fans_id);
                    if ($res) {
                        $ret = FollowCancel::where(['user_id'=>$user_id, 'fans_id'=>$fans_id])->update(['update_time'=>time()]);
                        if (!$ret) {
                            exception(lang('更新失败'));
                        }
                    } else {
                        //首次添加
                        $ret = FollowCancel::create($data);
                        if (!$ret) {
                            exception(lang('取消收藏失败'));
                        }
                    }
                    $res = FollowModel::where(['user_id'=>$user_id, 'fans_id'=>$fans_id])->update(['is_follow'=>0]);
                    if (!$res) {
                        exception(lang('更新收藏失败'));
                    }
                } else {
                    //更新收藏表
                    $res = FollowModel::where(['user_id'=>$user_id, 'fans_id'=>$fans_id])->update(['is_follow'=>1]);
                    if (!$res) {
                        exception(lang('更新收藏失败'));
                    }
                }
            } else {
                //首次添加收藏
                $ret = FollowModel::create($data);
                if (!$ret) {
                    exception(lang('添加收藏失败'));
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ApiReturn::r(0, [], $e->getMessage());
        }
        $is_follow = FollowModel::where(['user_id'=>$user['id'], 'fans_id'=>$fans_id])->value('is_follow');
        return ApiReturn::r(1, ['is_follow'=>$is_follow], lang('提交成功'));
    }


    /**
     * Notes:我的关注
     * User: lv
     * Date: 2020/8/27
     * Time: 15:01
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     * @throws \think\exception\DbException
     */
    public function follow_attention($data = [], $user = [])
    {
        //我的关注
        $result = FollowModel::get_user_fans($user['id']);
        if ($result) {
            return ApiReturn::r(1, $result, lang('请求成功'));
        }
        return ApiReturn::r(1, [], lang('没有更多数据了'));
    }
}
