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
use app\admin\model\LangDictClient as LangDictClientModel;

class LangDictClient extends Base
{
    /**
     * 查看客户端json语言包内容列表
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-26 08:42:22
     */
    public function index()
    {
        $lang_id = input('param.id');
        $word = input('param.word');

        $langTypeArr = LangModel::langTypeArr();
        $item = LangModel::where(['id' => $lang_id])->find();
        //语言包的类型名
        $item_lang_type_name =  $langTypeArr[$item['lang_type']];
      
        // 数据列表
        $map[] = [
           [ 'lang_id', '=', $lang_id]
        ];
        if ($word) {
            $map[] = ["word","like",'%'.$word.'%'];
        }
    
        //分页展示
        $data_list = LangDictClientModel::where($map)
            ->order('id desc')
            ->paginate();
        
        $fields = [
            ['id', 'ID'],
            ['word', lang('原文')],
            ['trans', lang('翻译')],
            ['right_button', lang('操作'), 'btn']
        ];
    
        $searchFields = [
            ['word', lang('原文'), 'text'],
        ];

        return Format::ins()//实例化
            ->setPageTitle($item_lang_type_name . lang('语言列表'))
            ->hideCheckbox()
            ->setTopButton(['title' => lang('新增'), 'href' => ['add', ['id'=>$lang_id]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary layeradd'])
            ->setRightButton(['title' => lang('编辑'), 'href'=>['edit',['id'=>'__id__']], 'icon' => 'fa fa-pencil pr5', 'class' => 'btn btn-xs mr5 btn-default layeredit'])
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
    public function add($id = '')
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['lang_id'] = $id;
            $exist = LangDictClientModel::where(['word'=> $data['word']])->find();
            if ($exist) {
                $this->error(lang('该内容已存在'));
            }
            
            if ($insertId = (new LangDictClientModel)->insertGetId($data)) {
                // 记录行为
                $details = lang('新增语言字典');
                action_log('admin_lang_dict_client_add', 'admin_lang_dict_client', $insertId, UID, $details);

                //读取json内容，插入到translate_client表。支持编辑
                $this->updateLangJson($id);

                $this->success(lang('新增成功'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        
        $fields =[
            ['type' => 'text', 'name' => 'word','title'=>lang('原文')],
            ['type'=>'text', 'name'=>'trans', 'title'=>lang('翻译'), ],
        ];
        $this->assign('page_title', lang('新增语言字典'));
        $this->assign('form_items', $fields);
        return $this->fetch('public/add');
    }


    /**
     * 编辑单个词的翻译
     * @param {*} $id
     * @return {*}
     * @Author: wangph
     * @Date: 2021-05-04 10:43:22
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }
        $info = LangDictClientModel::get(['id' => $id]);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (empty($data['trans'])) {
                $this->error(lang('翻译不能为空'));
            }

            $res = LangDictClientModel::where(['id'=>$id])->update($data);
            if ($res !== false) {
                //更新对应的json
                $this->updateLangJson($info['lang_id']);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
    
        $info['word'] = htmlentities($info['word']);
        $info['trans'] = htmlentities($info['trans']);
        $fields = [
            ['type' => 'text', 'name' => 'word', 'title'=>lang('原文'), 'attr'=>'readonly'],
            ['type'=>'text', 'name'=>'trans', 'title'=>lang('翻译'), ],
        ];
        $this->assign('page_title', lang('编辑'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
    
    /**
     * 字典更新后json文件更新
     * @param {*} $lang_id
     * @return {*}
     * @Author: wangph
     * @Date: 2021-05-04 11:33:33
     */
    public function updateLangJson($lang_id)
    {
        if (empty($lang_id)) {
            return;
        }
        $item = LangModel::where(['id' => $lang_id])->find();
        $item_path = model('admin/upload')->getCacheFile($item['upload_id'])['path'];
        $dict = LangDictClientModel::where(['lang_id' => $lang_id])->order('id desc')->select();
        if (count($dict) < 1) {
            return;
        }
        $data = [];
        foreach ($dict as $k=>$v) {
            //json格式
            $data[$v['word']] =  $v['trans'];
        }
        $data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $file = file_put_contents('.'.$item_path, $data_json);
    }
}
