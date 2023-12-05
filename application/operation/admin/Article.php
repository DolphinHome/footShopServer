<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\Article as ArticleModel;
use app\operation\model\ArticleColumn;
use service\Format;
use think\Db;

/**
 * 文档控制器
 * Class Article
 * @package app\cms\admin
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/4 11:44
 */
class Article extends Base
{

    /**
     * 文档列表
     * @return mixed
     * @throws \think\exception\DbException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/4 11:46
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        $where = " oa.trash = 0 ";
        if (isset($map['title'])) {
            $where .= " and oa.title like '%{$map['title']}%' ";
        }
        $by = isset($map['by']) ? $map['by'] : '';
        $orders = isset($map['order']) ? $map['order'] : '';

        if (isset($map['category_id']) && $map['category_id'] != 0) {
            //搜索分类判断
            $children_id = Db::name("operation_article_column")->where(['pid' => $map['category_id']])->column('id');
            $children_str = 0;
            if ($children_id) {
                $children_str = implode(',', $children_id);
            }
            $where .= " and (oa.category_id = {$map['category_id']}
                        or oa.category_id in ({$children_str})
            ) ";
        }

        // 排序
        $click_count = '';
        if (isset($map['by']) && isset($map['order'])) {
            $click_count = 'oa.' . $map['order'] . ' ' . $map['by'] . ',';
        }
        $order = $this->getOrder($click_count . 'oa.sort asc, oa.id desc');
        // 数据列表
        $data_list = ArticleModel::getList($where, $order);
        $fields = [
            ['id', 'ID'],
            ['title', lang('标题'), 'callback', function ($value, $data) {
                return "<a href=" . url('edit', ['id' => $data['id'], 'layer' => 1]) . " class='layeredit'>{$value}</a>";
            }, '__data__'],
            ['name', lang('分类')],
//            ['comment', lang('评论'), 'callback', function ($value, $data) {
//                return "<a href=" . url('comment_list', ['article_id' => $data['id']]) . ">{$value}</a>";
//            }, '__data__'],
//            ['user_like_num', lang('点赞'), 'callback', function ($value, $data) {
//                return "<a href=" . url('like_list', ['article_id' => $data['id']]) . ">{$value}</a>";
//            }, '__data__'],
//            ['collect', lang('收藏'), 'callback', function ($value, $data) {
//                return "<a href=" . url('collect_list', ['article_id' => $data['id']]) . ">{$value}</a>";
//            }, '__data__'],
//            ['click_count', lang('点击量'), 'text.edit'],
            ['add_time', lang('发布时间')],
            ['sort', lang('排序'), 'text.edit'],
//            ['is_recommend', lang('是否推荐'), 'callback', function ($value) {
//                return ArticleModel::recommend($value);
//            }],
            ['status', lang('状态'), 'status'],
            ['right_button', lang('操作'), 'btn']
        ];
        $article_column_model = new ArticleColumn();
        $article_class = $article_column_model::getTreeList();
        if (isset($article_class[0])) {
            $article_class[0] = lang('全部');
        }
        foreach ($article_class as &$v) {
            $v = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", " ", ($v));
        }
        $search_fields = [
            ['title', lang('文章标题'), 'text'],
            ['category_id', lang('文章分类'), 'select', '', $article_class],
        ];
        if (count($data_list) <= 0) {
            $this->bottom_button_select = [];
        }
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->bottom_button_select($this->bottom_button_select)
            ->setTopSearch($search_fields)
            ->setOrder('click_count')
            ->setTopButtons($this->top_button)
            ->setTopButton(['title' => lang('批量推荐'), 'href' => ['recommend', ['group' => $group]], 'icon' => 'fa fa-check-circle pr5', 'class' => 'btn btn-sm mr5 btn-default  ajax-post confirm', 'target-form' => 'ids'])
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }


    /*
     * 文章评论列表
     *
     */
    public function comment_list()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 数据列表
        $data_list = Db::name('operation_article_comment')->where($map)->order('id desc')->paginate(15, false, [
            'query' => $this->request->param()
        ]);
        $fields = [
            ['id', 'ID'],
            ['article_id', lang('文章标题'), 'callback', function ($value) {
                return Db::name('operation_article')->where('id', $value)->value('title');
            }],
            ['user_id', lang('评论人'), 'callback', function ($value) {
                return Db::name('user')->where(['id' => $value])->value('user_name');
            }],
            ['content', lang('评论内容')],
            ['create_time', lang('评论时间'), 'callback', function ($value) {
                return date('Y-m-d H:i:s', $value);
            }],
            ['right_button', lang('操作'), 'btn']
        ];
        $this->right_button = [
            ['ident' => 'delReport', 'title' => lang('删除'), 'href' => ['del_comment', ['ids' => '__id__']], 'icon' => 'fa fa-times pr6', 'class' => 'btn btn-xs mr5 btn-default  ajax-get confirm'],
        ];
        unset($this->top_button[0], $this->top_button[1], $this->top_button[2]);
        $this->top_button[3]['href'] = 'del_comment';
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /*
     * 删除评论
     *
     */
    public function del_comment($ids = null)
    {
        if ($ids === null) {
            $this->error(lang('参数错误'));
        }
        $ret = Db::name('operation_article_comment')->where('id', 'in', $ids)->delete();
        if (false === $ret) {
            $this->error(lang('删除失败'));
        }
        return $this->success(lang('删除成功'));
    }

    /*
 * 文章点赞列表
 *
 */
    public function like_list()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 数据列表
        $data_list = Db::name('user_article_like')->where($map)->order('id desc')->paginate(15, false, [
            'query' => $this->request->param()
        ]);
        $fields = [
            ['id', 'ID'],
            ['article_id', lang('文章标题'), 'callback', function ($value) {
                return Db::name('operation_article')->where('id', $value)->value('title');
            }],
            ['user_id', lang('点赞人'), 'callback', function ($value) {
                return Db::name('user')->where(['id' => $value])->value('user_name');
            }],

            ['create_time', lang('点赞时间'), 'callback', function ($value) {
                return date('Y-m-d H:i:s', $value);
            }]
        ];

        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        //        ->setRightButtons($this->right_button)
        ->setData($data_list)//设置数据
        ->fetch();//显示
    }

    /*
 * 文章收藏列表
 *
 */
    public function collect_list()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        $map1[] = ['collect_id', '=', $map['article_id']];
        $map1[] = ['type', '=', 2];
        // 数据列表
        $data_list = Db::name('user_collection')->where($map1)->order('aid desc')->paginate(15, false, [
            'query' => $this->request->param()
        ]);
        $fields = [
            ['aid', 'ID'],
            ['collect_id', lang('文章标题'), 'callback', function ($value) {
                return Db::name('operation_article')->where('id', $value)->value('title');
            }],
            ['user_id', lang('收藏人'), 'callback', function ($value) {
                return Db::name('user')->where(['id' => $value])->value('user_name');
            }],
            ['create_time', lang('收藏时间'), 'callback', function ($value) {
                return date('Y-m-d H:i:s', $value);
            }]

        ];

        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setData($data_list)//设置数据
        ->fetch();//显示
    }

    //批量推荐文章
    public function recommend($ids)
    {
        if ($ids === null) {
            $this->error(lang('参数错误'));
        }
        $ret = Db::name('operation_article')->where('id', 'in', $ids)->setField('is_recommend', 1);
        if (false === $ret) {
            $this->error(lang('操作失败'));
        }
        return $this->success(lang('操作成功'));
    }

    /**
     * 添加文档
     * @param int $cid 栏目id
     * @param string $model 模型id
     * @return mixed
     * @throws \think\exception\PDOException
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function add($cid = 0)
    {
        // 保存文档数据
        if ($this->request->isAjax() || $this->request->isPost()) {
            $DocumentModel = new ArticleModel();
            if (false === $DocumentModel->saveData()) {
                $this->error($DocumentModel->getError());
            }
            $this->success(lang('新增成功'), 'index');
        }

        //$columns = ArticleColumn::getTreeList(0, false);
        $columns = ArticleColumn::where(["is_system" => 0])->column("id,name");
        //增加系统不可编辑分类
//        $columns[25]='常见问题';
//        $columns[26]='交易问题';
        $columns[31]='平台公告';
        $fields = [
            ['type' => 'hidden', 'name' => 'status', 'value' => 1],
            ['type' => 'hidden', 'name' => 'user_id', 'value' => UID],
            ['type' => 'select', 'name' => 'category_id', 'title' => lang('所属分类'), 'extra' => $columns],
            ['type' => 'text', 'name' => 'title', 'title' => lang('标题')],
            // ['type' => 'images', 'name' => 'img_url', 'title' => lang('缩略图')],
            ['type' => 'addimgcut', 'name' => 'img_url', 'title' => lang('略缩图')],
            //['type' => 'color', 'name' => 'colors', 'title' => lang('颜色')],
            //['type' => 'alivideo', 'name' => 'video_id', 'title' => lang('视频')],
            ['type' => 'textarea', 'name' => 'synopsis', 'title' => lang('文章简介')],
            ['type' => 'wangeditor', 'name' => 'body', 'title' => lang('详细内容')],
            ['type' => 'number', 'name' => 'sort', 'title' => lang('排序'), 'value' => 0],
//            ['type' => 'number', 'name' => 'click_count', 'title' => lang('点击数'), 'value' => 0],
//            ['type' => 'radio', 'name' => 'is_recommend', 'title' => lang('是否推荐'), 'extra' => [lang('否'), lang('是')], 'value' => 0],
        ];
        $this->assign('page_title', lang('新增文章'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑文档
     * @param null $id 文档id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('参数错误'));
        }
        // 获取数据
        $info = ArticleModel::getOne($id);
        // 保存文档数据
        if ($this->request->isPost()) {
            $data = request()->post();
            $DocumentModel = new ArticleModel();
            $result = $DocumentModel->saveData();
            if (false === $result) {
                $this->error($DocumentModel->getError());
            }

            // 记录行为
            unset($data['__token__']);
            $details = arrayRecursiveDiff($data, $info);
            action_log('operation_article_edit', 'operation_article', $id, UID, $details);
            $this->success(lang('编辑成功'), 'index');
        }

        $columns = ArticleColumn::where(["is_system" => 0])->column("id,name");
        //增加系统不可编辑分类
//        $columns[25]='常见问题';
//        $columns[26]='交易问题';
        $columns[31]='平台公告';
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'select', 'name' => 'category_id', 'title' => lang('所属分类'), 'extra' => $columns],
            ['type' => 'text', 'name' => 'title', 'title' => lang('标题')],
            // ['type' => 'images', 'name' => 'img_url', 'title' => lang('缩略图')],
            ['type' => 'editimgcut', 'name' => 'img_url', 'title' => lang('缩略图')],
            ['type' => 'textarea', 'name' => 'synopsis', 'title' => lang('文章简介')],
            ['type' => 'wangeditor', 'name' => 'body', 'title' => lang('详细内容')],
            ['type' => 'number', 'name' => 'sort', 'title' => lang('排序'), 'value' => 0],
//            ['type' => 'number', 'name' => 'click_count', 'title' => lang('阅读量'), 'value' => 0],
//            ['type' => 'radio', 'name' => 'is_recommend', 'title' => lang('是否推荐'), 'extra' => [lang('否'), lang('是')]],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('是否启用'), 'extra' => [lang('否'), lang('是')]],
        ];
        $this->assign('page_title', lang('编辑文章'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /**
     * 删除文档(不是彻底删除，而是移动到回收站)
     * @param null $ids 文档id
     * @param string $table 数据表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function delete($ids = null)
    {
        if ($ids === null) {
            $this->error(lang('参数错误'));
        }
        $ret = \app\operation\model\Article::where('id', 'in', $ids)->setField('trash', 1);
        // 移动文档到回收站
        if (false === $ret) {
            $this->error(lang('删除失败'));
        }
        return $this->success(lang('删除成功'));
    }

    public function reportlist()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
//        echo '<pre>';
//        print_r($map);die;
        if (isset($map['title'])) {
            $map[] = ['operation_article.title', 'like', '%' . $map['title'] . '%'];
            unset($map['title']);
        }
        if (isset($map['user_name'])) {
            $map[] = ['user.user_name', 'like', '%' . $map['user_name'] . '%'];
            unset($map['user_name']);
        }
        if (isset($map['report_type'])) {
            if ($map['report_type'] != -1) {
                $map[] = ['operation_article_report.report_type', '=', $map['report_type']];
            }
            unset($map['report_type']);
        }
        if (isset($map['status'])) {
            if ($map['status'] != -1) {
                $map[] = ['operation_article_report.status', '=', $map['status']];
            }
            unset($map['status']);
        }
        // 数据列表
        $data_list = ArticleModel::getReportList($map);

        $fields = [
            ['id', 'ID'],
            ['title', lang('文章标题'), 'callback', function ($value, $data) {
                return "<a ident='edit' title=lang('编辑') href='" . url('edit', ['id' => $data['article_id'], 'layer' => 1]) . "' class=' layeredit'></i>{$value}</a> ";
            }, '__data__'],
            ['user_name', lang('举报人')],
            ['report_type', lang('举报类型'), 'callback', function ($value) {
                return ArticleModel::reportArr($value);
            }],
            ['remark', lang('举报内容')],
            ['status', lang('状态'), 'status', '', [lang('未处理'), lang('已处理')]],
            ['right_button', lang('操作'), 'btn']
        ];
        $report1 = ['-1' => lang('全部')];
        $report2 = Db::name('operation_article_report_type')->where('status', 1)->column('id,name', 'id');
        $report_type = $report1 + $report2;
        $search_fields = [
            ['title', lang('文章标题'), 'text'],
            ['user_name', lang('举报人'), 'text'],
            ['report_type', lang('举报类型'), 'select', '', $report_type],
            ['status', lang('是否处理'), 'select', '', ['-1' => lang('全部'), '0' => lang('未处理'), '1' => lang('已处理')]],
        ];
        $this->right_button = [
            ['ident' => 'handleReport', 'title' => lang('标记已处理'), 'href' => ['handleReport', ['ids' => '__id__']], 'icon' => 'fa fa-ban pr5', 'class' => 'btn btn-xs mr5 btn-default  ajax-get confirm'],
            ['ident' => 'delReport', 'title' => lang('删除'), 'href' => ['delReport', ['ids' => '__id__']], 'icon' => 'fa fa-times pr6', 'class' => 'btn btn-xs mr5 btn-default  ajax-get confirm'],
        ];
        return Format::ins()//实例化
        ->hideCheckbox()
            ->setTopSearch($search_fields)
            ->addColumns($fields)//设置字段
            ->setRightButtons($this->right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    //删除文章举报信息
    public function delReport($ids)
    {
        if ($ids === null) {
            $this->error(lang('参数错误'));
        }
        $ret = Db::name('operation_article_report')->where('id', 'in', $ids)->setField('is_del', 1);
        if (false === $ret) {
            $this->error(lang('删除失败'));
        }
        return $this->success(lang('删除成功'));
    }

    //处理文章举报信息
    public function handleReport($ids)
    {
        if ($ids === null) {
            $this->error(lang('参数错误'));
        }
        $ret = Db::name('operation_article_report')->where('id', 'in', $ids)->setField('status', 1);
        if (false === $ret) {
            $this->error(lang('操作失败'));
        }
        return $this->success(lang('操作成功'));
    }
}
