<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\model;

use think\Db;
use think\Model as ThinkModel;

/**
 * 文档模型
 * Class Document
 * @package app\operation\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/4 11:47
 */
class Article extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__OPERATION_ARTICLE__';
    //附表
    const EXTRA_TABLE = "operation_article_body";

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取文档列表
     * @param array $map 筛选条件
     * @param array $order 排序
     * @return mixed
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function getList($map = [], $order = [], $user = [])
    {
        /*        $data_list = self::view('operation_article oa', 'id,category_id,title,img_url,synopsis,click_count,fabulous,is_recommend,create_time as add_time,update_time,status,sort')
                    ->view("operation_article_column oac", 'name,cat_img', 'oac.id=oa.category_id', 'left')
                    ->view("operation_article_comment oacm", 'count(id) as article_comment_num', 'oacm.article_id=oa.id' ,'left')*/
        $data_list = self::alias('oa')->join("operation_article_column oac", "oac.id=oa.category_id", "left")
            ->join("operation_article_comment oacm ", "oa.id = oacm.article_id", 'left')
            ->field("oa.id,oa.category_id,oa.title,oa.img_url,oa.synopsis,oa.click_count,oa.fabulous,oa.is_recommend,oa.create_time as add_time,oa.update_time,oa.status,oa.sort,oac.name,oac.cat_img,count(oacm.id) as article_comment_num")
            ->where($map)
            ->group("oa.id")
            ->order($order)
            ->paginate()->each(function ($item) use ($user) {
                $item['is_like'] = Db::name("user_article_like")->where(["user_id" => $user['id'], "article_id" => $item['id']])->value('id') ? 1 : 0;
                $item['user_like_num'] = Db::name("user_article_like")->where(["article_id" => $item['id']])->count();
                $item['collect'] = Db::name("user_collection")->where(['collect_id' => $item['id'], 'type' => 2])->count();
                $item['comment'] = Db::name("operation_article_comment")->where(['article_id' => $item['id']])->count();
                $item['like'] = Db::name("operation_article_comment")->where(['article_id' => $item['id']])->count();
                $item['title'] = str2sub($item['title'], 22);
                $item['synopsis'] = str2sub($item['synopsis'], 58);
                $item['img_url'] = get_files_url($item['img_url']);
                $item['cat_img'] = get_file_url($item['cat_img']);
                $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
                return $item;
            });
        return $data_list;
    }

    /*
     * 获取文章举报列表
     *
     *
     */
    public static function getReportList($map = [], $order = [], $is_page = true)
    {
        $res = self::view("operation_article_report", true)
            ->view("operation_article", 'title,id as article_id ', 'operation_article_report.be_report_article_id=operation_article.id', 'left')
            ->view("user", 'user_name', 'operation_article_report.report_user_id=user.id', 'left')
            ->where($map)
            ->where(['operation_article_report.is_del' => 0])
            ->order('operation_article_report.id desc');
        if ($is_page) {
            return $res->paginate();
        } else {
            return $res->select();
        }
    }

    /**
     * 获取单篇文档
     * @param string $id 文档id
     * @param array $map 查询条件
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function getOne($id = '', $map = [])
    {
        $data = self::view('operation_article', 'id,category_id,title,img_url,synopsis,click_count,fabulous,is_recommend,create_time as add_time,update_time,status,sort');
        if (self::EXTRA_TABLE != '') {
            $data = $data->view(self::EXTRA_TABLE, true, 'operation_article.id=' . self::EXTRA_TABLE . '.aid', 'left');
        }
        return $data->view("operation_article_column", 'name', 'operation_article_column.id=operation_article.category_id', 'left')
            ->where('operation_article.id', $id)
            ->where($map)
            ->find();
    }

    /**
     * 新增或更新文档
     * @return bool
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function saveData()
    {
        $data = request()->post();

        $data['user_id'] = UID;

        self::startTrans();
        try {
            if ($data['id']) {
                $ret = $this->where("id", $data['id'])->update($data);
                if (false === $ret) {
                    exception(lang('编辑主表失败'));
                }

                $ret_body = Db::name(self::EXTRA_TABLE)->where("aid", $data['id'])->update(['body' => $data['body']]);

                if (false === $ret_body) {
                    exception(lang('编辑附加表失败'));
                }
            } else {
                if (!$data['title']) {
                    exception(lang('标题不能为空'));
                }
                $ret = $this->create($data);
                if (false === $ret) {
                    exception(lang('新增主表记录失败'));
                }
                $ret_body = Db::name(self::EXTRA_TABLE)->insert(['aid' => $ret->id, 'body' => $data['body']]);
                if (false === $ret_body) {
                    exception(lang('新增附加表记录失败'));
                }

                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('operation_article_add', 'operation_article', $ret->id, UID, $details);
            }

            // 提交事务
            self::commit();
        } catch (\Exception $e) {
            // 回滚事务
            self::rollback();
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 删除
     * @param type $ids
     * @return boolean
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function delIds($ids)
    {
        self::where("id", "in", $ids)->delete();
        Db::name(self::EXTRA_TABLE)->where("aid", "in", $ids)->delete();
        return true;
    }

    public static function reportArr($id)
    {
        $reportArr = array('5' => lang('色情暴力'), '6' => lang('政治相关'), '7' => lang('虚假广告'));
        return $reportArr[$id];
    }

    //文章是否推荐
    public static function recommend($id)
    {
        $recommend = array('0' => lang('否'), '1' => lang('是'));
        return $recommend[$id];
    }

    /**
     * 获取最新未读平台公告
     * @param $category_id int 文章分类id
     *
     */
    public static function getMessage($category_id, $user_id)
    {
        $res = false;
        //查询未读平台公告
        $list = Db::name("operation_article")
            ->where([
                ["id", "not in", function ($query) use ($user_id) {
                    $query->name("operation_article_read")->where('user_id', $user_id)->field("article_id");
                }]
            ])
            ->where([
                ["category_id", "=", $category_id],
                ["status", "=", 1]
            ])
            ->field("title,create_time,synopsis as content")
            ->order("create_time desc")
            ->select();
        if (count($list) > 0) {
            $res = $list[0];
            $res["num"] = count($list);
        }
        return $res;

    }

    /**
     * 获取平台消息列表
     * @param $category_id int 文章分类id
     */
    public static function getNoticeList($category_id)
    {
        $list = Article::where([
            "category_id" => $category_id,
            "status" => 1
        ])
            ->field("id,title,create_time,synopsis,img_url")
            ->order("create_time desc")
            ->paginate()
            ->each(function ($v) {
                $img_url = explode(",", $v["img_url"]);
                $v["img_url"] = get_file_url($img_url[0]);
                return $v;
            });
        return $list;

    }
}