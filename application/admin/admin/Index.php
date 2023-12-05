<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\admin\admin;

use app\admin\model\Login;
use think\Db;
use app\admin\model\Process as ProcessModel;

class Index extends Base
{
    /**
     * 后台首页
     * @return mixed
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index()
    {
        $this->redirect(url('statistics/index/index'));
        return $this->fetch();
    }

	/**
     * 清空系统缓存
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function Clear_Cache()
    {
        $this->clearCache();
        $this->success(lang('清空缓存成功'));
    }

    /**
     * 个人设置
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function Setting(){
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['nickname'] == '' && $this->error(lang('昵称不能为空'));
            $data['id'] = UID;

            // 如果没有填写密码，则不更新密码
            if ($data['password'] == '') {
                unset($data['password']);
            }else{
                // 验证
                $result = $this->validate($data, 'Admin');
                if (true !== $result) $this->error($result);
            }

            $UserModel = new Login();
            if ($user = $UserModel->allowField(['nickname', 'email', 'password', 'mobile', 'avatar'])->update($data)) {
                $info = $UserModel->where('id', UID)->field('password', true)->find();
                //刷新信息
                $UserModel->autoLogin($info);
                $this->success(lang('编辑成功'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        // 获取数据
        $info = Login::where('id', UID)->field('password', true)->find();
        $fields =[
            ['type'=>'static', 'name'=>'username', 'title'=>lang('用户名'), 'tips'=>lang('不可更改')],
            ['type'=>'text', 'name'=>'nickname', 'title'=>lang('昵称'), 'tips'=>lang('可以是中文')],
            ['type'=>'text', 'name'=>'email', 'title'=>lang('邮箱')],
            ['type'=>'password', 'name'=>'password', 'title'=>lang('密码'), 'tips'=>'至少8个字符，包含大写字母，小写字母，数字和特殊字符($@$!%*?&#),如不修改请保持为空'],
            ['type'=>'text', 'name'=>'mobile', 'title'=>lang('手机号'),'attr'=>'data-rule="mobile" data-rule-mobile: "[/^1[3-9]\d{9}$/, "请填写正确的手机号"]"'],
            ['type'=>'image', 'name'=>'avatar', 'title'=>lang('头像')]
        ];

        $this->assign('page_title',lang('编辑配置'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('public/edit');
    }

    /**
     * 获取联动数据
     * @param string $token token
     * @param int $pid 父级ID
     * @param string $pidkey 父级id字段名
     * @author 似水星辰 [2630481389@qq.com]
     * @return \think\response\Json
     */
    public function getLevelData($token = '', $pid = 0, $pidkey = 'pid')
    {
        if ($token == '') {
            return json(['code' => 0, 'msg' => '缺少Token']);
        }

        $token_data = session($token);
        $table      = $token_data['table'];
        $option     = $token_data['option'];
        $key        = $token_data['key'];

        $data_list = Db::name($table)->where($pidkey, $pid)->column($option, $key);

        if ($data_list === false) {
            return json(['code' => 0, 'msg' => lang('查询失败')]);
        }

        if ($data_list) {
            $result = [
                'code' => 1,
                'msg'  => lang('请求成功'),
                'list' => format_linkage($data_list)
            ];
            return json($result);
        } else {
            return json(['code' => 0, 'msg' => lang('查询不到数据')]);
        }
    }

	/**
     * 添加快捷菜单
     * @param $menuid
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/20 18:36
     */
    public function quick_menu($menuid = 0){
        if($menuid == 0){
            $this->error(lang('菜单id错误'));
        }

        $res = Db::name('admin_quick_menu')->where('menu_id', $menuid)->count('aid');
        if($res){
            $this->error(lang('菜单已存在，请勿重复添加'));
        }else{
            $res1 = Db::name('admin_quick_menu')->insert(['menu_id'=>$menuid, 'create_time'=> time()]);
            if($res1){
               $this->success(lang('添加快捷菜单成功'));
            }
        }
    }

    /**
     * 删除快捷菜单
     * @param int $menuid
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/2/20 19:01
     */
    public function quick_menu_del($menuid = 0){
        if($menuid == 0){
            $this->error(lang('菜单id错误'));
        }

        $res = Db::name('admin_quick_menu')->where('menu_id', $menuid)->delete();
        if($res){
            $this->success(lang('快捷菜单已删除'));
        }
    }

    /**
     * @Notes:业务流程
     * @Interface operationflow
     * @author: yuzubo
     * @Time: 2020/11/24
     */
    public function operationflow(){
        
        $list = ProcessModel::get_process_list();
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * @Notes:
     * @Interface guidance
     * @author: yuzubo
     * @Time: 2020/11/24
     */
    public function guidance(){
        return $this->fetch();
    }

}
