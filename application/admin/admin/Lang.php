<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\admin\admin;

use service\Format;
use service\ApiReturn;
use app\admin\model\Lang as LangModel;
use app\admin\model\LangDictClient;

class Lang extends Base
{
    /**
     * 客户端语言包列表
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-26 08:42:22
     */
    public function index()
    {
        // 数据列表
        $map = [];
        $langTypeArr = LangModel::langTypeArr();
        if (input('param.lang_type')) {
            $map[] = ['lang_type', 'eq', input('param.lang_type')];
        }
    
        $data_list = LangModel::where($map)
            ->order('id desc')
            ->paginate()
            ->each(function ($item) {
                $item['upload_id'] = get_file_url($item['upload_id']);
                //$item['lang_type'] = strtolower($item['lang_type']);
                $item['lang_type_name'] = LangModel::langTypeArr()[$item['lang_type']];
                return $item;
            });
     
      
        $fields = [
            ['id', 'ID'],
            ['lang_type', lang('包语言类型')],
            ['lang_type_name', lang('语言类型说明')],
            ['upload_id', lang('包地址')],
            ['right_button', lang('操作'), 'btn']
        ];
      
        array_unshift($langTypeArr, lang('全部类型'));
        //halt($langTypeArr);
        $searchFields = [
            ['lang_type', lang('包语言类型'), 'select', '', $langTypeArr],
        ];
        
        return Format::ins()//实例化
            ->setPageTitle(lang('语言包列表'))
            ->hideCheckbox()
            ->setTopButton(['title' => lang('新增'), 'href' => ['add', ['group' => $group]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary'])
            ->setRightButton(['title' => lang('查看'), 'href'=>['/admin/lang_dict_client/index',['id'=>'__id__']], 'icon' => 'fa fa-list pr5', 'class' => 'btn btn-xs mr5 btn-default '])
            ->setRightButton(['title' => lang('下载'), 'href'=>['download',['id'=>'__id__']], 'icon' => 'fa fa-download pr5', 'class' => 'btn btn-xs mr5 btn-default '])
            ->setRightButton(['title' => lang('删除'), 'href'=>['delete',['id'=>'__id__']], 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-xs mr5 btn-default '])
            ->setRightButton(['ident'=> 'disable', 'title'=>lang('禁用'),'href'=>['setstatus',['type'=>'disable','ids'=>'__id__']],'icon'=>'fa fa-ban pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->setTopSearch($searchFields)
            ->addColumns($fields)//设置字段
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }


    /**
     * 新增
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-26 08:42:42
     */
    public function add($group = '')
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $exist = LangModel::where(['lang_type'=> $data['lang_type']])->find();
            if ($exist) {
                $this->error(lang('该内容已存在'));
            }
            if ($insertId = (new LangModel)->insertGetId($data)) {
                // 记录行为
                $details = lang('新增语言包');
                action_log('admin_lang_add', 'admin_lang', $insertId, UID, $details);

                //读取json内容，插入到translate_client表。支持编辑
                $this->insertDictClient($insertId);

                $this->success(lang('新增成功'), url('/admin/lang/index'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        
        $fields = [
            ['type' => 'file', 'name' => 'upload_id', 'title' => lang('上传包'), 'ext'=>'json'],
            ['type' => 'select', 'name' => 'lang_type', 'title' => lang('语言类型'), 'extra'=>LangModel::langTypeArr()],
            ['type' => 'radio', 'name' => 'status', 'title' => lang('状态'), '', 'extra' => [lang('禁用'), lang('启用')], 'value' => 1]
        ];
        $this->assign('page_title', lang('新增语言包'));
        $this->assign('form_items', $fields);
        return $this->fetch('public/add');
    }

    /**
     * 下载
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-26 08:41:47
     */
    public function download()
    {
        $id = input('param.id');
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $item = LangModel::where(['id' => $id])->find();
        $item_path = '.'.model('admin/upload')->getCacheFile($item['upload_id'])['path'];
        $file_name = basename($item_path);
        $file = fopen($item_path, "r");
        //直接下载
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: ".filesize($item_path));
        header("Content-Disposition: attachment; filename=" . $file_name);
        echo fread($file, filesize($item_path));
        fclose($item_path);
        exit;
    }

    

    /**
     * 删除
     * @param string $ids
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/12 11:43
     */
    public function delete()
    {
        $id = input('param.id');
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        LangModel::where(['id' => $id])->delete();
        LangDictClient::where(['lang_id' => $id])->delete();
        $this->success(lang('删除成功'));
    }


    /**
     * 将json格式语言包插入数据表
     * @param {*} $lang_id
     * @return {*}
     * @Author: wangph
     * @Date: 2021-05-04 09:40:33
     */
    public function insertDictClient($lang_id)
    {
        $item = LangModel::where(['id' => $lang_id])->find();
        $item_path = model('admin/upload')->getCacheFile($item['upload_id'])['path'];
        $file = file_get_contents('.'.$item_path);

        if (strlen($file)<1) {
            return;
        }
        $json = json_decode($file, true);
        if (empty($json)) {
            return;
        }

        foreach ($json as $word=>$trans) {
            $data[] = [
                'lang_id'=> $lang_id,
                'word' => $word,
                'trans' => $trans
            ];
        }
        LangDictClient::insertAll($data);
    }
}
