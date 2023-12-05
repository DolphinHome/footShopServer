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
use app\admin\model\LangType as LangTypeModel;

class LangType extends Base
{
    /**
     * 语言包类型列表
     * @param {*}
     * @return {*}
     * @Author: wangph
     * @Date: 2021-04-26 08:42:22
     */
    public function index()
    {
        // 数据列表
        $map = [];
        $langTypeArr = LangTypeModel::langTypeArr();
        if (input('param.type_region')) {
            $map[] = ['type_region', 'eq', input('param.type_region')];
        }
    
        $data_list = LangTypeModel::where($map)
            ->order('id desc')
            ->paginate();

        $fields = [
            ['id', 'ID'],
            ['type', lang('语言类型')],
            ['type_region', lang('语言类型-地区')],
            ['intro', lang('语言类型说明')],
            ['right_button', lang('操作'), 'btn']
        ];
      
        array_unshift($langTypeArr, lang('全部类型'));

        $searchFields = [
            ['type_region', lang('语言类型'), 'select', '', $langTypeArr],
        ];
        
        return Format::ins()//实例化
            ->setPageTitle(lang('语言包列表'))
            ->hideCheckbox()
            ->setRightButton(['ident'=> 'disable', 'title'=>lang('禁用'),'href'=>['setstatus',['type'=>'disable','ids'=>'__id__']],'icon'=>'fa fa-ban pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'])
            ->setTopSearch($searchFields)
            ->addColumns($fields)//设置字段
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

}
