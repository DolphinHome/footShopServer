<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\AdsType as AdsTypeModel;
use service\ApiReturn;
use service\Format;
use think\Db;

/**
 * 广告分类控制器
 * @package app\operation\admin
 */
class AdsType extends Base
{


    /**
     * 广告列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('update_time desc');
        // 数据列表
        $data_list = AdsTypeModel::where($map)->order($order)->paginate();
        $fields = [
            ['id', 'ID'],
            ['name', lang('广告位名称'), 'callback', function ($value, $data) {
                return "<a href=" . url('edit', ['id' => $data['id'], 'layer' => 1]) . " class='layeredit'>{$value}</a>";
            }, '__data__'],
            ['width', lang('图片宽度'), '', '', '', 'text-center'],
            ['height', lang('图片高度'), '', '', '', 'text-center'],
            ['create_time', lang('创建时间'), '', '', '', 'text-center'],
            ['status', lang('状态'), 'status', '', [lang('禁用'), lang('正常')], 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];


        array_push($this->right_button, [
            'ident' => 'statistics',//按钮标识
            'title' => ' 统计', //标题
            'href' => ['statistics', ['typeid' => '__id__']],//链接
            'icon' => 'fa fa-industry',
            'class' => 'btn btn-xs mr5 btn-default'
        ]);
        $this->assign('tablefields', json_encode($fields, JSON_UNESCAPED_UNICODE));
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setPrimaryKey('id')
            ->setTopButtons($this->top_button)
            ->setRightButtons($this->right_button)
            ->replaceRightButton(['id' => ['lt', 7]], '', 'delete')  //前6条广告位数据不能删除
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }


    public function setStatus($type = '')
    {
        $ids   = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids   = (array)$ids;
        $field = input('param.field', 'status');
        if (empty($type)) {
            $type = input('param.action');
        }

        empty($ids) && $this->error(lang('缺少主键'));

        $Model = $this->getModel();

        $protect_table = [
            '__ADMIN__',
            '__ADMIN_ROLE__',
            '__ADMIN_MODULE__',
            config('database.prefix').'admin',
            config('database.prefix').'admin_role',
            config('database.prefix').'admin_module',
        ];

        // 禁止操作核心表的主要数据
        if (in_array($Model->getTable(), $protect_table) && in_array('1', $ids)) {
            $this->error(lang('禁止操作'));
        }

        // 主键名称
        $pk = $Model->getPk();
        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = $Model->where($pk, 'IN', $ids)->setField($field, 0);
                Db::name('operation_ads')->where('typeid', 'IN', $ids)->setField('status', 0);
                break;
            case 'enable': // 启用
                $result = $Model->where($pk, 'IN', $ids)->setField($field, 1);
                Db::name('operation_ads')->where('typeid', 'IN', $ids)->setField('status', 1);
                break;
            case 'delete': // 删除
                $field = $Model->getTableFields($Model->getTable());
                if (in_array('is_del', $field)) {
                    $result = $Model->where($pk, 'IN', $ids)->setField('is_del', 1);
                } else {
                    $result = $Model->where($pk, 'IN', $ids)->delete();
                }

                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        $table = strtolower(str_replace('__', '', $Model->getTable()));

        if (false !== $result) {
            // \Cache::clear();

            // 记录行为
            action_log($table.'_'.$type, $table, $ids, UID, 'ID：'.implode('、', $ids));
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }

    /**
     * 新增
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['name'] = $this->trimAll($data['name']);
            if ($data['name'] == '') {
                $this->error(lang('广告位名称不能为空'));
            }
            if (mb_strlen($data['name'], 'utf-8') >= 20) {
                $this->error(lang('广告位名称不能超过20个字'));
            };
            // 验证
            $result = $this->validate($data, 'AdsType');
            if (true !== $result) {
                $this->error($result);
            }

            if ($res = AdsTypeModel::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('operation_nav_type_add', 'operation_ads_type', $res->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('广告位名称')],
            ['type' => 'text', 'name' => 'width', 'title' => lang('图片宽度')],
            ['type' => 'text', 'name' => 'height', 'title' => lang('图片高度')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'tips' => '', 'extra' => [lang('否'), lang('是')], 'value' => 1]
        ];

        $this->assign('page_title', lang('新增广告位'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 广告分类id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = AdsTypeModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['name'] = $this->trimAll($data['name']);
            if ($data['name'] == '') {
                $this->error(lang('广告位名称不能为空'));
            }
            if (mb_strlen($data['name'], 'utf-8') >= 20) {
                $this->error(lang('广告位名称不能超过20个字'));
            };

            // 验证
            $result = $this->validate($data, 'AdsType');
            if (true !== $result) {
                $this->error($result);
            }

            if (AdsTypeModel::update($data)) {
                // 记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('operation_ads_type_edit', 'operation_ads_type', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'name', 'title' => lang('广告位名称')],
            ['type' => 'text', 'name' => 'width', 'title' => lang('图片宽度')],
            ['type' => 'text', 'name' => 'height', 'title' => lang('图片高度')],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('立即启用'), 'tips' => '', 'extra' => [lang('否'), lang('是')], 'value' => 1]
        ];
        $this->assign('page_title', lang('编辑广告位'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /**
     * Notes:广告位点击量统计
     * User: php迷途小书童
     * Date: 2020/8/24
     * Time: 19:41
     * @param int $adsid
     * @return mixed
     */
    public function statistics($typeid = 0)
    {
        $get = $this->request->get();
        $typeid = $get['typeid'] ?: $typeid;
        $page_traffic_time = $get['page_traffic_time'] ?? '-7';
        $page_traffic_input = $get['page_traffic_input'] ?? date('Y-m-d', strtotime('-7 days')) . '~' . $now;

        ############页面流量统计 begin #########################
        $pv = [];
        $uv_date = '';
        for ($i = abs($page_traffic_time); $i >= 0; $i--) {
            $date_search = date('Y-m-d', strtotime('-' . $i . 'days'));
            $day = date('m-d', strtotime('-' . $i . 'days'));
            $pvnum = Db::name('operation_ads_pv_data')
                ->where('date', $date_search)
                ->where('typeid', $typeid)
                ->sum('typepv');
            array_push($pv, $pvnum);
            $uv_date .= "'{$day}',";
        }

        $page_traffic_data['pv'] = implode(',', $pv);
        $page_traffic_data['date'] = rtrim($uv_date, ',');
        $this->assign('typeid', $typeid);
        $this->assign('page_traffic_data', $page_traffic_data);
        $this->assign('page_traffic_input', $page_traffic_input);
        ############页面流量统计 end #########################

        ############浏览占比 begin #########################

        $browse_input = $get['browse_input'] ?? date('Y-m-d', strtotime('-7 days')) . '~' . $now;
        $this->assign('browse_input', $browse_input);
        $this->assign('page_traffic_time', $page_traffic_time);

        $ios = Db::name('operation_ads_pv_data')
            ->where('way', 1)
            ->whereTime('date', $browse_input)
            ->sum('typepv');

        $android = Db::name('operation_ads_pv_data')
            ->where('way', 2)
            ->whereTime('date', $browse_input)
            ->sum('typepv');
        $mini = Db::name('operation_ads_pv_data')
            ->where('way', 3)
            ->whereTime('date', $browse_input)
            ->sum('typepv');

        $pc = Db::name('operation_ads_pv_data')
            ->where('way', 4)
            ->whereTime('date', $browse_input)
            ->sum('typepv');

        $browse['ios'] = $ios;
        $browse['android'] = $android;
        $browse['mini'] = $mini;
        $browse['pc'] = $pc;

        $this->assign('browse', $browse);
        ############浏览占比 end ###########################

        return $this->fetch('ads/ads_type_statistics');
    }

    /**
     * Notes:
     * User: php迷途小书童
     * Date: 2020/8/24
     * Time: 19:54
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function leaderboard()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 数据列表
        $data_list = Db::name('operation_ads_pv_data')
            ->alias('a')
            ->join('operation_ads_type b', 'b.id = a.typeid')
            ->field('a.sex,a.way,a.typeid,a.area,sum(a.typepv) as pv_total')
            ->field('b.name,b.create_time')
            ->group('typeid')
            ->order('pv_total', 'desc')
            ->select();

        $list_type = \app\operation\model\AdsType::column('id,name');

        $fields = [
            ['typeid', 'ID'],
            ['typeid', lang('广告位名称'), 'status', '', $list_type],
            ['sex', lang('性别'), 'callback', function ($sex) {
                switch ($sex) {
                    case 1:
                        $sex = lang('男');
                        break;
                    case 2:
                        $sex = lang('女');
                        break;
                    default:
                        $sex = lang('未知');
                        break;
                }
                return $sex;
            }, '', 'text-center'],
            ['way', lang('来源'), 'callback', function ($way) {
                switch ($way) {
                    case 1:
                        $way = 'ios';
                        break;
                    case 2:
                        $way = 'android';
                        break;
                    case 3:
                        $way = 'mini';
                        break;
                    case 4:
                        $way = 'pc';
                        break;
                    default:
                        $way = lang('未知');
                        break;
                }
                return $way;
            }, '', 'text-center'],
            ['area', lang('所在地区')],
            ['pv_total', 'PV'],
            ['create_time', lang('创建时间'), 'callback', function ($create_time) {
                return date('Y-m-d H:i:s', $create_time);
            }, '', 'text-center'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];

        array_push($this->right_button, [
            'ident' => 'statistics',//按钮标识
            'title' => ' 统计', //标题
            'href' => ['statistics', ['typeid' => '__typeid__']],//链接
            'icon' => 'fa fa-industry',
            'class' => 'btn btn-xs mr5 btn-default'
        ]);

        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setTopButton(['title' => lang('广告位管理'), 'href' => ['operation/AdsType/index'], 'class' => 'btn btn-sm mr5 btn-success '])
            ->setRightButtons($this->right_button, ['edit', 'enable', 'disable', 'delete'])
            ->setData($data_list)//设置数据
            ->hideCheckbox()
            ->fetch();//显示
    }

    /*
     * 获取广告位宽高
     *
     */
    public function getSize()
    {
        $id = request()->param('id', 0);
        $res = Db::name('operation_ads_type')->where(['id' => $id])->field('width,height')->find();
        //如果没查到，默认400宽高
        if (empty($res)) {
            $res['width'] = 400;
            $res['height'] = 400;
        }
        return ApiReturn::r(1, $res, 'ok');
    }
}
