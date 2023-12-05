<?php
namespace app\api\controller\v1;

use app\api\controller\Base;
use think\Db;
use service\ApiReturn;

/**
 * 动态接口
 * @package app\api\controller\v1
 */
class Circle extends Base
{
    //我的发布
    public function myCircle($data=[], $user=[])
    {
        $page=$this->request->request('page', 1);
        $pageSize=$this->request->request('pageSize', 6);
        $user_id=$user['id'];
        $list=Db::name('circle')
            ->field('id,content,image,createtime')
            ->where('user_id', $user_id)
            ->order('id desc')
            ->paginate($pageSize, false, [])
            ->each(function ($item, $key) {
                if (mb_strlen($item['content'])>30) {
                    $content=mb_substr($item['content'], 0, 30);
                    $item['content']=$content.'...';
                }
                $item['image'] = get_files_url($item['image']);
                $item['createtime']=date('Y-m-d H:i', $item['createtime']);
                return $item;
            });
        if (!$list) {
            return ApiReturn::r(0, [], lang('暂无数据'));
        }
        return ApiReturn::r(1, $list, lang('请求成功'));
    }
    //全部列表
    public function listCircle($data=[], $user=[])
    {
        $pageSize=$this->request->request('pageSize', 6);
        $list=Db::name('circle')
            ->field('id,user_id,content,image,createtime,comments,likes')
            ->order('id desc')
            ->paginate($pageSize, false, [])
            ->each(function ($item, $key) use ($user) {
                $a0=Db::name('user')->field('head_img,user_nickname')->where('id', $item['user_id'])->find();
                $item['head_img']=get_file_url($a0['head_img']);
                $item['user_nickname']=$a0['user_nickname'];
                // if(mb_strlen($item['content'])>30){
                //     $content=mb_substr($item['content'],0,30);
                //     $item['content']=$content.'...';
                // }
                $item['image'] = get_files_url($item['image']);
                $item['createtime']=date('Y-m-d H:i', $item['createtime']);
                $item['is_follow']=0;
                $item['is_like']=0;
                //如果登录 是否点赞
                if (isset($user['id']) && $user['id']) {
                    $map['circle_id']=$item['id'];
                    $map['user_id']=$user['id'];
                    $a=Db::name('circle_like')->field('id,is_like')->where($map)->find();
                    if ($a['is_like']==1) {
                        $item['is_like']=1;
                    } else {
                        $item['is_like']=0;
                    }
                    //是否关注
                    $map1['user_id']=$item['user_id'];
                    $map1['fans_id']=$user['id'];
                    $a=Db::name('user_follow')->field('id')->where($map1)->find();
                    if ($a) {
                        $item['is_follow']=1;
                    } else {
                        $item['is_follow']=0;
                    }
                }
                //评论
                $ha=Db::name('circle_comment')
                    ->alias('a')
                    ->join('lb_user b', 'a.user_id=b.id')
                    ->field('a.id,a.user_id,a.content,a.createtime,a.touid,b.user_nickname,b.head_img')
                    ->where('a.circle_id', $item['id'])
                    //->order('a.id desc')
                    ->select();
                $item['comment']=[];
                if ($ha) {
                    foreach ($ha as $k=>$v) {
                        $ha[$k]['reply_nickname']='';
                        $ha[$k]['reply_img']=get_file_url(0);
                        $ha[$k]['user_nickname']=$v['user_nickname'];
                        $ha[$k]['createtime']=date('Y-m-d H:i', $v['createtime']);
                        $ha[$k]['content']=$v['content'];
                        if ($v['touid']) {
                            $aa=Db::name('user')->field('user_nickname,head_img')->where('id', $v['touid'])->find();
                            $ha[$k]['reply_nickname']=$aa['user_nickname'];
                            $ha[$k]['reply_img']=get_file_url($aa['head_img']);
                        }
                        unset($ha[$k]['touid']);
                    }

                    // if(mb_strlen($ha['content'])>30){
                    //     $haa['content']=mb_substr($ha['content'],0,30).'...';
                    // }
                    $item['comment']=$ha;
                }
                return $item;
            });
        if (!$list) {
            return ApiReturn::r(0, [], lang('暂无数据'));
        }
        return ApiReturn::r(1, $list, lang('请求成功'));
    }
    //所有评论
    public function listComment($data=[], $user=[])
    {
        $id=$data['id'];
        $a=Db::name('circle')->field('id')->where('id', $id)->find();
        if (!$a) {
            return ApiReturn::r(0, [], lang('动态不存在'));
        }
        $pageSize=$this->request->request('pageSize', 6);
        $list=Db::name('circle_comment')
            ->alias('a')
            ->join('lb_user b', 'a.user_id=b.id')
            ->field('a.id,a.content,a.createtime,b.user_nickname,b.head_img')
            ->where('a.circle_id', $id)
            //->order('a.id desc')
            ->paginate($pageSize, false, [])
            ->each(function ($item, $key) {
                $item['createtime']=date('Y-m-d H:i', $item['createtime']);
                $item['head_img']=get_file_url($item['head_img']);
                return $item;
            });
        if (!$list) {
            return ApiReturn::r(0, [], lang('暂无数据'));
        }
        return ApiReturn::r(1, $list, lang('请求成功'));
    }
    //添加
    public function addCircle($data=[], $user=[])
    {
        $user_id=$user['id'];
        $content=$data['content'];
        $image=$this->request->request('image', '');
        $image=trim($image, ',');
        //敏感词检测
        $flag = addons_action('DfaFilter/DfaFilter/check', [trim($data['content'])]);
        if ($flag) {
            return ApiReturn::r(0, [], lang('包含违规词汇'));
        }
        //防止重复提交
        $a=Db::name('circle')->field('id,createtime')->where('user_id', $user_id)->order('id desc')->find();
        if ($a && time()-$a['createtime']<3) {
            return ApiReturn::r(0, [], lang('发布太频繁'));
        }
        //新增
        $data=[
            'user_id'=>$user_id,
            'content'=>$content,
            'image'=>$image,
            'createtime'=>time(),
            'updatetime'=>time()
        ];
        $res=Db::name('circle')->insert($data);
        if (!$res) {
            return ApiReturn::r(0, [], lang('发布失败'));
        }
        return ApiReturn::r(1, [], lang('发布成功'));
    }

    //删除
    public function delCircle($data=[], $user=[])
    {
        $id=$data['id'];
        $user_id=$user['id'];
        $map['id']=$id;
        $map['user_id']=$user_id;
        $a=Db::name('circle')->field('id')->where($map)->find();
        if (!$a) {
            return ApiReturn::r(0, [], lang('动态不存在'));
        }
        Db::startTrans();
        try {
            $res=Db::name('circle')->where('id', $id)->delete();
            Db::name('circle_comment')->where('circle_id', $id)->delete();
            Db::name('circle_like')->where('circle_id', $id)->delete();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }

        if (!$res) {
            return ApiReturn::r(0, [], lang('删除失败'));
        }
        return ApiReturn::r(1, [], lang('删除成功'));
    }
    //添加评论
    public function addComment($data=[], $user=[])
    {
        $user_id=$user['id'];
        $id=$data['id'];
        $content=$data['content'];
        $touid=0;
        $pid=isset($data['pid']) ? $data['pid'] : 0;
        //敏感词检测
        $flag = addons_action('DfaFilter/DfaFilter/check', [trim($data['content'])]);
        if ($flag) {
            return ApiReturn::r(0, [], lang('包含违规词汇'));
        }
        //防止重复提交
        $map['user_id']=$user_id;
        $map['circle_id']=$id;
        $a=Db::name('circle_comment')->field('id,createtime')->where($map)->order('id desc')->find();
        if ($a && time()-$a['createtime']<3) {
            return ApiReturn::r(0, [], lang('发布太频繁'));
        }
        //判断动态是否存在
        $a1=Db::name('circle')->field('id')->where('id', $id)->find();
        if (!$a1) {
            return ApiReturn::r(0, [], lang('动态不存在'));
        }
        if ($pid) {
            $a2=Db::name('circle_comment')->field('id,user_id')->where('id', $pid)->find();
            if (!$a2) {
                return ApiReturn::r(0, [], lang('评论不存在'));
            } else {
                $touid=$a2['user_id'];
            }
        }
        //新增
        Db::startTrans();
        try {
            $a0=[
                'circle_id'=>$id,
                'user_id'=>$user_id,
                'content'=>$content,
                'createtime'=>time(),
                'updatetime'=>time(),
                'touid'=>$touid,
                'pid'=>$pid
            ];
            Db::name('circle_comment')->insert($a0);
            Db::name('circle')->where('id', $id)->setInc('comments');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::r(0, [], lang('发布失败'));
        }
        return ApiReturn::r(1, [], lang('发布成功'));
    }
    //点赞
    public function addlike($data=[], $user=[])
    {
        $id=$data['id'];
        $user_id=$user['id'];
        $a=Db::name('circle')->field('id')->where('id', $id)->find();
        if (!$a) {
            return ApiReturn::r(0, [], lang('动态不存在'));
        }
        $map['circle_id']=$id;
        $map['user_id']=$user_id;
        $a0=Db::name('circle_like')->field('id,is_like')->where($map)->find();
        Db::startTrans();
        try {
            if ($a0['is_like']==1) {
                $ha=[
                    'is_like'=>2,
                    'createtime'=>time(),
                    'updatetime'=>time()
                ];
                Db::name('circle_like')->where('id', $a0['id'])->update($ha);
                Db::name('circle')->where('id', $id)->setDec('likes');
            }
            if (!$a0) {
                $ha=[
                    'circle_id'=>$id,
                    'user_id'=>$user_id,
                    'is_like'=>1,
                    'createtime'=>time(),
                    'updatetime'=>time()
                ];
                Db::name('circle_like')->insert($ha);
                Db::name('circle')->where('id', $id)->setInc('likes');
            }
            if ($a0['is_like']==2) {
                $ha=[
                    'is_like'=>1,
                    'createtime'=>time(),
                    'updatetime'=>time()
                ];
                Db::name('circle_like')->where('id', $a0['id'])->update($ha);
                Db::name('circle')->where('id', $id)->setInc('likes');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::commit();
            return ApiReturn::r(0, [], lang('失败'));
        }
        return ApiReturn::r(1, [], lang('成功'));
    }
    //举报
    public function addReport($data=[], $user=[])
    {
        $id=$data['id'];
        $a=Db::name('circle')->field('id,is_report')->where('id', $id)->find();
        if (!$a) {
            return ApiReturn::r(0, [], lang('动态不存在'));
        }
        if ($a['is_report']==1) {
            return ApiReturn::r(1, [], lang('成功'));
        }
        $res=Db::name('circle')->where('id', $id)->update(['is_report'=>1]);
        if (!$res) {
            return ApiReturn::r(0, [], lang('失败'));
        }
        return ApiReturn::r(1, [], lang('成功'));
    }
}
