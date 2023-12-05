<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\common\controller\Common;
use app\admin\model\Login as LoginModel;
use app\admin\model\Role as RoleModel;
use app\admin\model\Menu as MenuModel;
use app\admin\model\Process as ProcessModel;

/**
 * 业务流程 
 * @package app\admin\login
 */
class Process extends Base
{

	public function index(){

		$list = ProcessModel::get_process_list();
		$this->assign('list', $list);
		return $this->fetch();
	}


	public function add(){
		if ($this->request->isPost()) {
			$data = $this->request->post();
			$saveDate['status'] = $data['status'];
			$saveDate['name'] = $data['name'];
			$saveDate['type'] = $data['type'];
			$saveDate['url'] = $data['thumb'];
			$saveDate['synopsis'] = json_encode(explode(',',trim($data['synopsis'],',')));
			$saveDate['detail'] =  explode(';', trim($data['detail'],';'));

			foreach ($saveDate['detail'] as $key => $value) {
				$detail = explode(',', $value);
				$array['name'] = $detail[0];
				array_shift($detail);
				$array['detail'] = $detail;
				$retun[] = $array;
			}

			$saveDate['detail'] = json_encode($retun);
			$info = ProcessModel::where('name',$saveDate['name'])->find();
			if(!$info){
				$res = ProcessModel::add_process($saveDate);
				if($res){
					echo json_encode(array('code'=>1,'msg'=>lang('成功'),'url'=>'index'));
				}else{
					echo json_encode(array('code'=>0,'msg'=>lang('失败')));
				}
			}else{
				echo json_encode(array('code'=>0,'msg'=>lang('已有分类名称')));
			}
			

		}else{
			return $this->fetch();
		}
		
	}

	public function delete(){
		$data = $this->request->get();
		$info = ProcessModel::where('id',$data['id'])->delete();
		if($info){
			$this->redirect('index');
		}else{
			$this->redirect('index');
		}
	}
}