<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\admin;

use app\admin\admin\Base;
use app\operation\model\Suggestions as SuggestionsModel;
use app\operation\model\SuggestionsType;
use service\Format;

/**
 * 投诉建议控制器
 * @package app\Suggestions\admin
 */
class Suggestions extends Base
{
    /**
     * 投诉建议列表
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        if (isset($map['type']) && $map['type'] == -1) {
            unset($map['type']);
        }
        if (isset($map['is_replay']) && $map['is_replay'] == -1) {
            unset($map['is_replay']);
        }
//        echo '<pre>';
//        print_r($map);die;
        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = SuggestionsModel::where($map)->order($order)->paginate();
        foreach ($data_list as &$v) {
            $v['thumb'] = get_file_url($v['thumb']);
        }
        $type = SuggestionsType::where('status', 1)->column('id,title');
        $fields = [
            ['id', 'ID'],
            ['type', lang('投诉建议类型名称'), 'status', '', $type],
            ['thumb', lang('图片'), 'pictures'],
//            ['contact', lang('手机号')],
//            ['qq_contact', 'QQ号'],
//            ['email_contact', lang('邮箱')],
            ['body', lang('内容'), 'text.tip'],
            ['is_replay', lang('已回复'), [lang('否'), lang('是')]],
            ['replay', lang('回复内容')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        $type[-1] = lang('全部');
        ksort($type);
        $search_fields = [
            ['type', lang('投诉建议类型'), 'select', '', $type],
            ['contact', lang('手机号'), 'text'],
            ['is_replay', lang('是否回复'), 'select', '', ['-1' => lang('全部'), '0' => lang('未回复'), '1' => lang('已回复')]],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopSearch($search_fields)
            ->setTopButtons($this->top_button, ['add', 'disable', 'enable'])
            ->setRightButtons($this->right_button, ['edit', 'disable'])
            ->setRightButton([
                'ident' => 'replay',
                'title' => lang('回复'),
                'href' => ['replay', ['id' => '__id__', 'layer' => 1, 'reload' => 1]],
                'icon' => 'fa fa-check-circle pr5',
                'class' => 'btn btn-xs mr5 btn-default layeredit',
                'layer' => 1,
            ])
            ->replaceRightButton(['is_replay' => 1], '', 'replay')
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    public function userTextDecode($str)
    {
        $text = json_encode($str);
        $text = preg_replace_callback('/\\\\\\\\/i', function ($str) {
            return '\\';
        }, $text);
        return json_decode($text);
    }


    //回复
    public function replay($id = 0)
    {
        if (!$id) {
            $this->error(lang('缺少参数'));
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证

            if (!$data['replay']) {
                $this->error(lang('请输入回复内容'));
            }
            $data['is_replay'] = 1;
            $res = SuggestionsModel::update($data);

            if (false === $res) {
                $this->error(lang('编辑失败'));
            }
            $this->success(lang('编辑成功'), cookie('__forward__'));
        }

        $info = SuggestionsModel::get($id);
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'textarea', 'name' => 'replay', 'title' => lang('回复内容'), 'tips' => '', 'attr' => '', 'value' => ''],
        ];
        $this->assign('page_title', lang('提交回复内容'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }

    /**
     * 投诉建议类型
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function type()
    {
//        return $this->fetch();
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $data_list = SuggestionsType::where($map)->order($order)->paginate();
        $fields = [
            ['id', 'ID'],
            ['title', lang('名称')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button, ['disable', 'enable', 'delete'])
            ->setRightButtons($this->right_button, ['disable', 'delete'])
            ->setRightButton(['ident' => 'enable', 'title' => lang('启用'), 'href' => ['type_status', ['ids' => '__id__', 'status' => 1]], 'icon' => 'fa fa-check-circle pr5', 'class' => 'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->setRightButton(['ident' => 'disable', 'title' => lang('禁用'), 'href' => ['type_status', ['ids' => '__id__', 'status' => 0]], 'icon' => 'fa fa-ban pr5', 'class' => 'btn btn-xs mr5 btn-default   ajax-get confirm'])
            ->setRightButton(['ident' => 'delete', 'title' => lang('删除'), 'href' => ['type_status', ['ids' => '__id__', 'status' => 3]], 'icon' => 'fa fa-close pr5', 'class' => 'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->replaceRightButton(['status' => 0], '', 'disable')
            ->replaceRightButton(['status' => 1], '', 'enable')
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Suggestions.typeadd');
            if (true !== $result) {
                $this->error($result);
            }

            if ($ret = SuggestionsType::create($data)) {
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('operation_suggestions_add', 'operation', $ret->id, UID, $details);
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields = [
            ['type' => 'text', 'name' => 'title', 'title' => lang('名称'), 'tips' => '', 'attr' => '', 'value' => ''],

        ];
        $this->assign('page_title', lang('新增投诉建议类型'));
        $this->assign('form_items', $fields);
        return $this->fetch('../../admin/view/public/add');
    }

    /**
     * 编辑
     * @param null $id 投诉建议id
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = SuggestionsType::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Suggestions.typeadd');
            if (true !== $result) {
                $this->error($result);
            }

            if (SuggestionsType::update($data)) {
                //记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('operation_suggestions_edit', 'operation', $id, UID, $details);

                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'title', 'title' => lang('名称'), 'tips' => '', 'attr' => '', 'value' => ''],
        ];
        $this->assign('page_title', lang('编辑投诉建议类型'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }

    /**
     * 禁用/启用/删除  投诉建议类型
     * @return mixed
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function type_status($ids, $status)
    {
        switch ($status) {
            case 0: // 禁用
                $result = SuggestionsType::where('id', 'IN', $ids)->setField('status', 0);
                break;
            case 1: // 启用
                $result = SuggestionsType::where('id', 'IN', $ids)->setField('status', 1);
                break;
            case 3: // 启用
                $result = SuggestionsType::where('id', 'IN', $ids)->delete();
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }
        if ($result) {
            $this->success(lang('操作成功'));
        }
    }
}
