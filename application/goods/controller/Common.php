<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\common\controller;

use think\Controller;

/**
 * 项目公共控制器
 * @package app\common\controller
 */
class Common extends Controller
{
    /**
     * 顶部按钮组
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public $top_button = [
        ['ident'=> 'add', 'title'=>'新增', 'href'=>'add', 'icon'=>'fa fa-plus pr5','class'=>'btn btn-sm mr5 btn-primary '],
        ['ident'=> 'enable', 'title'=>'批量启用','href'=>['setstatus',['type'=>'enable']],'icon'=>'fa fa-check-circle pr5','class'=>'btn btn-sm mr5 btn-default  ajax-post confirm','extra'=>'target-form="ids"'],
        ['ident'=> 'disable', 'title'=>'批量禁用','href'=>['setstatus',['type'=>'disable']],'icon'=>'fa fa-ban pr5','class'=>'btn btn-sm mr5 btn-default  ajax-post confirm','extra'=>'target-form="ids"'],
        ['ident'=> 'delete', 'title'=>'批量删除','href'=>'delete','icon'=>'fa fa-times pr5','class'=>'btn btn-sm mr5 btn-default  ajax-post confirm','extra'=>'target-form="ids"'],
    ];

    /**
     * 右侧按钮组
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public $right_button = [
        ['ident'=> 'edit', 'title'=>'编辑','href'=>'edit','icon'=>'fa fa-pencil pr5','class'=>'btn btn-xs mr5 btn-default '],
        ['ident'=> 'disable', 'title'=>'禁用','href'=>['setstatus',['type'=>'disable','ids'=>'__id__']],'icon'=>'fa fa-ban pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'],
        ['ident'=> 'delete', 'title'=>'删除','href'=>['delete',['ids'=>'__id__']],'icon'=>'fa fa-times pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'],
    ];

    /**
     * 初始化
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    protected function initialize()
    {
        $domain = $_SERVER['SERVER_NAME'];
        $ignore_domain = ['127.0.0.1', 'localhost'];
        //先过虑域名，默认设置2个本地过虑
        if(!in_array($domain, $ignore_domain)){
            $module = request()->module();
            $ignore = ['api', 'index'];
            //过虑模块，这些模块不校验
            $c = cache('system_check_auth');
            if (!in_array($module, $ignore) && (!$c || (time() - $c) > 259200)) {
                // 检查是否授权
                $content = \service\File::read_file('./../data/install.lock');
                if (!$content) {
                    $this->error(lang('授权码无效'));
                } else {
                    $verifyurl = 'http://mk.zhongbenruanjian.com/api/v2/5e031f1d5228a';
                    $da = ["domain_url" => $domain, "password" => config('license_code'), "verify" => $content];
                    $result = curl_post($verifyurl, $da);
                    $result1 = json_decode($result, true);
                    if ($result1['code'] == 0) {
                        $this->error(lang('请获取授权后再使用'));
                    }
                    cache('system_check_auth', time());
                }
            }
        }
        // 模块后台公共模板
        $this->assign('admin_layout', APP_PATH . 'admin/view/layout.html');
		// 输出弹出层参数
        $this->assign('layer', $this->request->param('layer'));
    }

	/**
     * 获取当前操作模型
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return object|\think\db\Query
     */
    final protected function getModel()
    {
        $controller  = parse_name($this->request->controller());

        // 使用模型
        try {
            $Model = \App::model($controller);
        } catch (\Exception $e) {
            $this->error(lang('找不到模型：').$controller);
        }

        return $Model;
    }

	/**
     * 设置状态
     * 禁用、启用、删除都是调用这个内部方法
     * @param string $type 操作类型：enable,disable,delete
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     */
    public function setStatus($type = '')
    {
        $ids   = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids   = (array)$ids;
        $field = input('param.field', 'status');

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
                $result = $Model->where($pk,'IN',$ids)->setField($field, 0);
                break;
            case 'enable': // 启用
                $result = $Model->where($pk,'IN',$ids)->setField($field, 1);
                break;
            case 'delete': // 删除
                $result = $Model->where($pk,'IN',$ids)->delete();
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        $table = strtolower(str_replace('__','',$Model->getTable()));

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
     * 删除记录
     * @return void
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function delete(){
		$ids   = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids   = (array)$ids;
		if($ids){
			$this->setStatus('delete');
		}

		$this->error(lang('缺少参数'));
	}

    /**
     * 设置表单字段数据
     * @param array $fields 字段名集合
     * @param array $info 对应字段值集合
     * @return array
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function setData($fields = [], $info =[]){
        if(is_array($fields)){
            foreach($fields as &$v){
                if($v['type'] != 'sort'){
                    if($v['type'] == 'password'){
                        $v['value'] = '';
                    }else if($v['type'] == 'attr'){
                        $v['value'] = htmlspecialchars_decode($info[$v['name']]);
                    }else{
                        //if($info[$v['name']]){
                            $v['value'] = $info[$v['name']];
                        //}
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * 获取筛选条件
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @alter 小乌 <82950492@qq.com>
     * @return array
     */
    final protected function getMap()
    {


        $search_field     = input('param.search_field/s', '');
        $keyword          = input('param.keyword/s', '');
        if($search_field && $keyword ==''){
            $this->error(lang('关键词不能为空'));
        }
        // 搜索框搜索
        if ($search_field != '' && $keyword !== '') {
            $map[] = [$search_field, 'like', "%$keyword%"];
        }
        unset($map['search_field'],$map['keyword']);
        if(!$map){
            $map = input('param.');
            foreach($map as $k=>$v){
                if($map[$k] == '' || $map['page']){
                    unset($map[$k]);
                }
            }
        }
        return $map;
    }

    /**
     * 获取字段排序
     * @param string $extra_order 额外的排序字段
     * @param bool $before 额外排序字段是否前置
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return string
     */
    final protected function getOrder($extra_order = '', $before = false)
    {
        $order = input('param.order/s', '');
        $by    = input('param.by/s', '');
        if ($order == '' || $by == '') {
            return $extra_order;
        }
        if ($extra_order == '') {
            return $order. ' '. $by;
        }
        if ($before) {
            return $extra_order. ',' .$order. ' '. $by;
        } else {
            return $order. ' '. $by . ',' . $extra_order;
        }
    }
}