<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author 似水星辰 [2630481389@qq.com]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------

namespace app\goods\admin;

use app\admin\admin\Base;
use app\goods\model\Freight as FreightModel;
use app\common\model\Area;
use think\Db;
use service\Format;
use service\Tree;

/**
 * 运费模板控制器
 * @package app\ExpressCompany\admin
 */
class Freight extends Base
{
    /**
     * 运费模板列表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap(['company_id']);
        $company_id = $map['company_id'];
        if ($map['method'] == "all") {
            unset($map['method']);
        }
        // 数据列表
        $data_list = FreightModel::where($map)->order('sort asc')->paginate();
        $company = \app\goods\model\ExpressCompany::column('aid,name');
        $fields = [
            ['id', 'ID'],
            ['name', lang('模板名称')],
            ['method', lang('计费方式'), '', '', [1 => lang('按重量'), 2 => lang('按件数')]],
            ['company_id', lang('快递公司'), '', '', $company],
            ['sort', lang('排序'), 'text.edit', '', '', '', 'freight'],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
//        array_unshift($this->top_button,['ident'=> 'new-add', 'title'=>lang('新增'), 'href'=>'add?company_id='.$company_id, 'icon'=>'fa fa-plus pr5','class'=>'btn btn-sm mr5 btn-primary btn-flat']);
        $searchFields = [
            ['name', lang('模板名称'), 'text'],
            ['method', lang('计费方式'), 'select', '', ['all' => "全部", 1 => lang('按重量'), 2 => lang('按件数')]],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopSearch($searchFields)
            ->setTopButton(['ident' => 'new-add', 'title' => lang('新增'), 'href' => 'add?company_id=' . $company_id, 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary layeradd '])
            ->setRightButton(['title' => lang('编辑'), 'data-toggle' => 'dialog', 'href' => ['edit', ['id' => '__id__', 'layer' => 1, 'reload' => 0]], 'class' => 'btn btn-xs btn-default mr5 font12', 'data-width' => '1200', 'data-height' => '800'])
            ->setRightButtons($this->right_button_layer, ['edit'])
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function add($company_id = 0)
    {
        // 保存数据测试一下
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($data['company_id'] == '-1') {
                $this->error(lang('请选择快递公司'));
            }

            // 验证
            $result = $this->validate($data, 'Freight.add');
            if (true !== $result) {
                $this->error($result);
            }
            if (!isset($data['freight'])) {
                $this->error(lang('请选择地址'));
            }

            // 启动事务
            Db::startTrans();
            try {
                $res = FreightModel::create($data);
                $id = $res->id;
                if ($id) {
                    foreach ($data['freight']['region'] as $key => $val) {
                        $sql[$key]['freight_id'] = $id;
                        $sql[$key]['create_time'] = time();
                        $sql[$key]['region'] = $val;
                        $sql[$key]['first'] = $data['freight']['first'][$key];
                        $sql[$key]['first_fee'] = $data['freight']['first_fee'][$key];
                        $sql[$key]['additional'] = $data['freight']['additional'][$key];
                        $sql[$key]['additional_fee'] = $data['freight']['additional_fee'][$key];
                    }
                    $res1 = Db::name('goods_freight_rule')->insertAll($sql);
                    if (!$res1) {
                        exception(lang('新增运费失败'));
                    }
                } else {
                    exception(lang('新增运费模板失败'));
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            //记录行为
            unset($data['__token__']);
            $details = json_encode($data, JSON_UNESCAPED_UNICODE);
            action_log('goods_freight_add', 'goods_freight', $res->id, UID, $details);
            $this->success(lang('新增成功'), cookie('__forward__'));
        }
        $company = '';
        if (!$company_id) {
            $company = Db::name("goods_express_company")->where(['status' => 1])->field("aid,name")->select();
        }
        //获取地区缓存
        $area = Area::get_cache();
        $this->assign('area', json_encode($area));
        $this->assign('company_id', $company_id);
        $this->assign('company', $company);

        $this->assign('page_title', lang('新增运费模板'));
        return $this->fetch();
    }

    /**
     * 编辑
     * @param null $id 快递公司id
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = FreightModel::get($id, ['rule']);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['update_time'] = time();

            // 验证
            $result = $this->validate($data, 'Freight.edit');
            if (true !== $result) {
                $this->error($result);
            }
            if (!isset($data['freight'])) {
                $this->error(lang('请选择地址'));
            }

            // 启动事务
            Db::startTrans();
            try {
                //获取运费模板信息
                $freight = FreightModel::detail($data['id']);
                $res = $freight->update($data);
                if ($res) {
                    //先删除原有的，如果原来的有商品再用，则不让保存
                    $res1 = $freight->remove($data['id']);
                    if (!$res1) {
                        exception($freight->getError());
                    }
                    //保存新的
                    $res2 = $freight->post_update($data);
                    if (!$res2) {
                        exception(lang('编辑运费失败'));
                    }
                } else {
                    exception(lang('编辑运费模板失败'));
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            //记录行为
            unset($data['__token__']);
            $details = arrayRecursiveDiff($data, $info);
            action_log('goods_freight_edit', 'goods_freight', $id, UID, $details);
            $this->success(lang('编辑成功'), url('goods/freight/edit', ['id' => $id, 'layer' => 1, 'reload' => 1]));
        }
        //获取地区缓存
        $area = Area::get_cache();
        $this->assign('area', json_encode($area));
        $this->assign('info', $info);
        $this->assign('page_title', lang('编辑运费模板'));
        return $this->fetch();
    }

    /**
     * 查看快递编码
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/28 16:58
     */
    public function express_code()
    {
        return $this->fetch();
    }
}
