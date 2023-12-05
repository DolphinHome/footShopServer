<?php


namespace app\goods\admin;


use app\admin\admin\Base;
use service\Format;
use think\Db;

class Lable extends Base
{
    /**
     * 会员标签列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder();
        // 数据列表
        $data_list = Db::name('goods_lable')->where($map)->order($order)->paginate();
        $fields =[
            ['id','ID'],
            ['lable_name',lang('标签名称')],
            ['create_time',lang('创建时间'),'callback',function($value){
                return date('Y-m-d H:i:s',$value);
            }],
            ['right_button', lang('操作'), 'btn','','','text-center']
        ];
        return Format::ins() //实例化
        ->addColumns($fields)//设置字段
        ->setTopButtons($this->top_button)
            ->setRightButton(['ident'=> 'edit', 'title'=>'编辑','href'=>'edit','icon'=>'fa fa-pencil pr5','class'=>'btn btn-xs mr5 btn-default '])
            ->setData($data_list)//设置数据
            ->fetch();//显示
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

            // 验证
//            $result = $this->validate($data, 'Label');
//            if (true !== $result) {
//                $this->error($result);
//            }

            if(empty($data['lable_name'])){
                $this->error('商品标签名称不能为空');
            }
            $data['create_time'] = time();
            $data['update_time'] = time();
            if ($page = Db::name('goods_lable')->insert($data)) {
                $this->success(lang('新增成功'), cookie('__forward__'));
            } else {
                $this->error(lang('新增失败'));
            }
        }

        $fields =[
            ['type'=>'text','name'=>'lable_name','title'=>lang('商品标签名称'),'tips'=>'','attr'=>'','value'=>''],
        ];
        $this->assign('page_title', lang('新增商品标签'));
        $this->assign('form_items', $fields);
        return $this->fetch('../../admin/view/public/add');
    }

    /**
     * 编辑
     * @param null $id 会员标签id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if(empty($data['lable_name'])){
                $this->error('商品标签名称不能为空');
            }
            // 验证
//            $result = $this->validate($data, 'Label');
//            if (true !== $result) {
//                $this->error($result);
//            }
            $data['update_time'] = time();
            if (Db::name('goods_lable')->where('id',$data['id'])->update($data)) {
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

        $info = Db::name('goods_lable')->where('id',$id)->find();
        $fields =[
            ['type' => 'hidden', 'name' => 'id'],
            ['type'=>'text','name'=>'lable_name','title'=>lang('商品标签名称'),'tips'=>'','attr'=>'','value'=>''],

        ];
        $this->assign('page_title', lang('编辑商品标签'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('../../admin/view/public/edit');
    }
}