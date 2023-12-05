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
use app\admin\model\LangDictServer as LangDictServerModel;
use app\admin\model\LangType as LangTypeModel;

class LangDictServer extends Base
{
    /**
     * 查看服务端语言包内容列表
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-26 08:42:22
     */
    public function index()
    {
        $word = trim(input('param.word'));
        // 数据列表
        if ($word) {
            $map[] = ["cn|tw|en|ja|ko", "like", '%'.$word.'%'];
        }
    
        $data_list = LangDictServerModel::where($map)
            ->order('id desc')
            ->paginate();
    
        //获取启用的语言包类型数组
        $langTypeAble = LangTypeModel::langTypeAble();
        $fields = [
            ['id', 'ID']
        ];
        //获取显示的列
        foreach ($langTypeAble as $l) {
            array_push($fields, [$l['type'], lang($l['intro'])]);
        }
        array_push($fields, ['right_button', lang('操作'), 'btn']);
        
        $searchFields = [
            ['word', lang('关键词'), 'text'],
        ];

        return Format::ins()//实例化
            ->setPageTitle(lang('语言列表'))
            ->hideCheckbox()
            ->setTopButton(['title' => lang('新增'), 'href' => ['add', ['id'=>$lang_id]], 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary layeradd'])
            ->setRightButton(['title' => lang('编辑'), 'href'=>['edit',['id'=>'__id__']], 'icon' => 'fa fa-pencil pr5', 'class' => 'btn btn-xs mr5 btn-default layeredit'])
            ->setRightButton(['title' => lang('删除'), 'href'=>['delete',['ids'=>'__id__']], 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-xs mr5 btn-default '])
            ->setTopSearch($searchFields)
            ->addColumns($fields)//设置字段
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 新增词典
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-26 08:42:42
     */
    public function add($id = '')
    {
        //获取启用的语言包类型数组
        $langTypeAble = LangTypeModel::langTypeAble();
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (empty($data['cn'])) {
                $this->error(lang('中文不能为空'));
            }
            $exist = LangDictServerModel::where(['cn'=> $data['cn']])->find();
            if ($exist) {
                $this->error(lang('该内容已存在'));
            }
            
            if ($insertId = (new LangDictServerModel)->insertGetId($data)) {
                // 记录行为
                $details = lang('新增语言字典');
                action_log('admin_lang_dict_server_add', 'admin_lang_dict_server', $insertId, UID, $details);
                //获取新增的语言类型字段
                $diff_field = [];
                foreach($langTypeAble as $v){
                    if (!empty($data[$v['type']])) {
                        $diff_field[] = $v['type'];
                    }
                }
                //更新php语言包
                $this->updateLangArray($diff_field);

                $this->success(lang('新增成功'));
            } else {
                $this->error(lang('新增失败'));
            }
        }
        
       
        //获取显示的列
        $fields = [];
        foreach ($langTypeAble as $l) {
            array_push($fields, ['type' => 'textarea', 'name' => $l['type'], 'title'=>lang($l['intro'])]);
        }

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
        $info   = LangDictServerModel::get(['id' => $id])->toArray();
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $diff_field = [];
            //找出更新的不同字段
            $diff_field = $this->diffCompare($data, $info);
            $res = LangDictServerModel::where(['id'=>$id])->update($data);
            if ($res !== false) {
                $this->updateLangArray($diff_field);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }
    
     
        $info['cn'] = htmlentities($info['cn']);
        $info['tw'] = htmlentities($info['tw']);
        
        //获取启用的语言包类型数组
        $langTypeAble = LangTypeModel::langTypeAble();
        $fields = [];
        //获取显示的列
        foreach ($langTypeAble as $l) {
            array_push($fields, ['type' => 'textarea', 'name' => $l['type'], 'title'=>lang($l['intro'])]);
        }
        $this->assign('page_title', lang('编辑'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }
    
    /**
     * 字典更新后lang语音包的文件更新
     * 语言包路径 application\lang\zh-cn.php
     * @return {*}
     * @Author: wangph
     * @Date: 2021-05-04 11:33:33
     */
    public function updateLangArray($fields = [])
    {
        if (empty($fields)) {
            return;
        }
        $files = $dict = [];
        //获取启用的语言类型
        $langTypeAble = LangTypeModel::langTypeAble();
        //获取语言包列表
        $dict = LangDictServerModel::order('id asc')->select();
        //获取对应的应用目录下加载语言包的文件完整路径，下面执行写入内容
        foreach ($langTypeAble as $l) {
            $langtype = $l['type'];
            $langpath = APP_PATH.'lang/'.$l['type_region'].'.php';
            //fields不为空，只更新修改对应的语言包
            if (in_array($langtype, $fields)) {
                file_put_contents($langpath, $this->createLangLine($dict, $langtype));
            }
        }
    }



    /**
     * 生成语言包的数组格式
     * @param {*} $dict
     * @param {*} $lang_type
     * @return {*}
     * @Author: wangph
     * @Date: 2021-05-05 09:55:32
     */
    public function createLangLine($dict, $lang_type)
    {
       
        $line = '';
        //语言包文件头部
        $file_head = <<<EOF
<?php
return [

EOF;
        //语言包文件尾部
        $file_end = '];';
        //遍历字典
        foreach ($dict as $v) {
            if (!empty($v[$lang_type])) {
                if(strpos($v[$lang_type], '"') !== false){
                    $v[$lang_type] =  str_replace("\"",'\"',$v[$lang_type]);
                }
                $line .=  '    \''.$v['cn'].'\''.' => "'.$v[$lang_type].'",'."\r";
            }
        }
        return  $file_head.$line.$file_end;
    }




    /**
     * 获取编辑时和原数据不同的字段
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-05-04 18:05:24
     */
    public function diffCompare($newdata, $olddata)
    {
        $diff = [];
        foreach ($olddata as $k=>$v) {
            if (isset($newdata[$k])) {
                if ($newdata[$k] != $v) {
                    $diff[] = $k;
                }
            }
        }
        return $diff;
    }
}
