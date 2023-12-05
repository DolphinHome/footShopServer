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

namespace app\integral\admin;

use app\admin\admin\Base;
use app\common\model\Order as OrderModel;
use app\goods\model\OrderGoods;
use app\goods\model\OrderGoodsExpress;
use app\goods\model\OrderInfo;
use app\goods\model\OrderRefund as RefundModel;
use think\Db;
use service\Format;

/**
 * 订单控制器
 * @package app\Brand\admin
 */
class Order extends Base
{
    /**
     * 订单列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        // $map = $this->getMap();
        $order_sn=input('param.order_sn');
        $status=input('param.status', '');
        $create_time=input('param.create_time');
        $pay_status=input('param.pay_status');
        $map[]=['order_type','=',4];
        if ($order_sn) {
            $map[]=['order_sn','=',$order_sn];
        }
        if ($create_time) {
            $map[]=['create_time','>=',strtotime($create_time)];
            $map[]=['create_time','<',(strtotime($create_time.' 23:59:59'))];
        }
        if ($status!=='') {
            $map[]=['status','=',$status];
            $this->assign('status', $status);
        }
        // 排序
        $order = 'create_time desc';//$this->getOrder();
        // 数据列表
        $data_list = OrderModel::where($map)->order($order)->paginate()->each(function ($item) {
            $item['status_name'] = OrderModel::$order_status[$item['status']];
            return $item;
        });
        $tab[] = ['title'=>lang('未发货'),'url'=>url('integral/order/index', 'status=1'),'value'=>1];
        $tab[] = ['title'=>lang('已发货'),'url'=>url('integral/order/index', 'status=2'),'value'=>2];
        $this->assign('tab_list', $tab);
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());
        return $this->fetch();
    }

    /**
     * @param int $aid 订单id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/28 11:14
     */
    public function detail($order_sn = null)
    {
        $info = Db::name('order')->where(['order_sn'=>$order_sn])->find();
        $info['order_address'] = Db::name('order_goods_info')->where(['order_sn'=>$info['order_sn']])->find();
        $info['goods_integral'] = Db::name('order_integral_list')->where(['order_sn'=>$order_sn])->select();
        $info['order_express'] = Db::name('order_goods_express')->where(['order_sn'=>$order_sn])->find();
        $this->assign('order_info', $info);
        return $this->fetch();
    }

    /**
     * 订单提醒
     * @return mixed
     */
    public function remind()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map[] = [];
        // 排序
        $order = 'id desc';
        // 数据列表
        $data_list = \think\Db::name('order_remind')->alias('r')
            ->join('order o', 'r.order_sn=o.order_sn')
            ->field('r.id,r.order_sn,r.create_time,r.status,o.create_time as order_time,o.user_id')
            ->order($order)
            ->paginate();

        $buttons = [
            [
                'ident' => 'detail',//按钮标识
                'title' => lang('查看详情'), //标题
                'href' => ['detail', ['order_sn'=>'__order_sn__']],//链接
                'icon' => 'fa fa-eye pr5',//图标
                'class' => 'btn btn-xs mr5 btn-success btn-flat'//样式类
            ],[
                'ident' => 'read',//按钮标识
                'title' => lang('标记已读'), //标题
                'href' => ['remind_read', ['rid'=>'__id__']],//链接
                'icon' => 'fa fa-eye pr5',//图标
                'class' => 'btn btn-xs mr5 btn-warning btn-flat'//样式类
            ],[
                'ident' => 'del',//按钮标识
                'title' => lang('删除'), //标题
                'href' => ['remind_del', ['rid'=>'__id__']],//链接
                'icon' => 'fa fa-eye pr5',//图标
                'class' => 'btn btn-xs mr5 btn-danger btn-flat'//样式类
            ]
        ];
        $fields = [
            ['id', 'ID'],
            ['order_sn', lang('编号')],
            ['create_time', lang('提醒时间')],
            ['order_time', lang('下单时间')],
            ['user_id', lang('下单人'), 'callback', 'get_nickname'],
            ['status', lang('状态'), 'status', '', [lang('未读'),lang('已读')]],
            ['right_button', lang('操作'), 'btn']
        ];
        return Format::ins()
            ->addColumns($fields)
            ->setRightButtons($buttons)
            ->replaceRightButton(['status'=>1], '', 'read')
            ->setData($data_list)
            ->fetch();
    }

    /**
     * 提醒已读
     */
    public function remind_read($rid)
    {
        $rs = \think\Db::name('order_remind')->where('id', $rid)->setField('status', 1);
        if ($rs) {
            $this->success(lang('已读成功'));
        } else {
            $this->error(lang('修改失败'));
        }
    }

    /**
     * 提醒删除
     * @param $rid
     */
    public function remind_del($rid)
    {
        $rs = \think\Db::name('order_remind')->delete($rid);
        if ($rs) {
            $this->success(lang('删除成功'));
        } else {
            $this->error(lang('删除失败'));
        }
    }

    /**
     * 订单物流
     * @return mixed
     */
    public function express()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $order_sn=input('param.order_sn');
        $express_no=input('param.express_no');
        $shipping_type=input('param.shipping_type');
        $shipping_time=input('param.shipping_time');

        // 查询
        $map = [];
        if ($order_sn) {
            $map[]=['order_sn','=',$order_sn];
        }
        if ($express_no) {
            $map[]=['express_no','=',$express_no];
        }
        if ($shipping_time) {
            $map[]=['shipping_time','>=',strtotime($shipping_time)];
            $map[]=['shipping_time','<',(strtotime($shipping_time.' 23:59:59'))];
        }
        if ($shipping_type!=='' && !is_null($shipping_type)) {
            $map[]=['shipping_type','=',$shipping_type];
        }

        // 排序
        $order = 'id desc';
        // 数据列表
        $data_list = \think\Db::name('order_goods_express')->where($map)->order($order)->paginate(10);
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());
        return $this->fetch();
    }

    /**
     * 订单物流添加
     */
    public function express_add($order_sn)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Express');
            if (true !== $result) {
                $this->error($result);
            }
            $data['uid'] = UID;

            // 商品列表
            $goods = Db::name('order_integral_list')->where('order_sn', $order_sn)->select();
            $goods_array = [];
            foreach ($goods as $item) {
                $goods_array[] = $item['goods_id'];
            }
            $data['order_goods_id_array'] = implode(',', $goods_array);
            $data['shipping_time'] = time();
            Db::startTrans();
            try {
                OrderGoodsExpress::create($data);
                // 修改订单状态
                Db::name('order')->where('order_sn', $order_sn)->setField('status', 2);

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error(lang('添加失败'), cookie('__forward__'));
            }
            action_log('order_delivery', 'goods', 0, UID);
            $this->success(lang('添加成功'), cookie('__forward__'));
        }

        //快递公司
        $express_company = \app\goods\model\ExpressCompany::field('aid,name')->order('sort desc')->select();
        $express_company_data = [];
        foreach ($express_company as $item) {
            $express_company_data[$item['aid']] = $item['name'];
        }

        $fields = [
            ['type' => 'hidden', 'name' => 'order_sn', 'value'=> $order_sn],
            ['type' => 'text', 'name' => 'express_name', 'title' => lang('包裹名称'), 'tips' => '', 'attr' => ''],
            ['type' => 'radio', 'name' => 'shipping_type', 'title' => lang('发货方式'), 'tips' => '', 'attr' => '', 'extra'=>[lang('无需物流'),lang('需要物流')], 'value'=>1],
            ['type' => 'select', 'name' => 'express_company_id', 'title' => lang('快递公司'), 'tips' => '', 'extra' => $express_company_data],
            ['type' => 'hidden', 'name' => 'express_company', 'value'=> current($express_company_data)],
            ['type' => 'text', 'name' => 'express_no', 'title' => lang('快递单号'), 'tips' => '', 'attr' => ''],
            ['type' => 'datetime', 'name' => 'shipping_time', 'title' => lang('发货时间'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'memo', 'title' => lang('备注'), 'tips' => '', 'attr' => ''],
        ];
        $this->assign('page_title', lang('添加物流'));
        $this->assign('form_items', $fields);
        $this->assign('set_script', ['/static/goods/js/express.js','/static/plugins/layer/laydate/laydate.js']);
//        $this->assign('set_script', ['/static/plugins/layer/laydate/laydate.js']);
        return $this->fetch('admin@public/add');
    }


    public function express_del($eid)
    {
        $rs = \think\Db::name('order_goods_express')->delete($eid);
        if ($rs) {
            action_log('order_express_delete', 'goods', 0, UID);
            $this->success(lang('删除成功'), cookie('__forward__'));
        } else {
            $this->error(lang('删除失败'), cookie('__forward__'));
        }
    }

    /**
     * 订单售后
     * @return mixed
     */
    public function refund()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $order_sn=input('param.order_sn');
        $status=input('param.status', '');
        $refund_time=input('param.refund_time');
        if ($order_sn) {
            $map[]=['order_sn','=',$order_sn];
        }
        if ($refund_time) {
            $map[]=['refund_time','>=',strtotime($refund_time)];
            $map[]=['refund_time','<',(strtotime($refund_time.' 23:59:59'))];
        }
        if ($status!=='') {
            $map[]=['status','=',$status];
            $this->assign('status', $status);
        }
        // 查询
        $map = [];
        // 排序
        $order = '';
        // 数据列表
        $tab[] = ['title'=>lang('售后中'),'url'=>url('goods/order/refund', 'status=5'),'value'=>5];
        $tab[] = ['title'=>lang('售后完成'),'url'=>url('goods/order/refund', 'status=6'),'value'=>6];
        $this->assign('tab_list', $tab);
        $data_list = RefundModel::get_list($map, $order);
        $this->assign('list', $data_list);
        $this->assign('pages', $data_list->render());
        $this->assign('orderStatus', [0=>lang('驳回'),1=>lang('同意')]);
        return $this->fetch();
    }

    /**
     * 售后原因
     */
    public function refund_detail($rfid)
    {
        $detail = RefundModel::get($rfid);
        $this->assign('detail', $detail);
        $info = OrderInfo::get_order_detail($detail['order_sn']);
        $info['order_status'] = \app\common\model\Order::$order_status[$info['status']];
        $this->assign('order_info', $info);
        return $this->fetch();
    }

    /**
     * 退货确认
     */
    public function refund_sure($rfid)
    {
        $order_sn = RefundModel::where('id', $rfid)->value('order_sn');

        Db::startTrans();
        try {
            $order = Db::name('order')->where('order_sn', $order_sn)->where('status', 5)->lock(true)->find();
            if (!$order) {
                exception(lang('订单不可操作'));
            }
            Db::name('order')->where('order_sn', $order_sn)->setField('status', 6);
            Db::name('order_refund')->where('id', $rfid)->setField('status', 1);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(lang('操作失败'), cookie('__forward__'));
        }
        action_log('order_refund_pass', 'goods', 0, UID);
        $this->success(lang('操作成功'), cookie('__forward__'));
    }

    /**
     * 退货删除
     */
    public function refund_del($rfid)
    {
        $order_sn = RefundModel::where('id', $rfid)->value('order_sn');

        Db::startTrans();
        try {
            $order = Db::name('order')->where('order_sn', $order_sn)->where('status', 5)->lock(true)->find();
            if (!$order) {
                exception(lang('订单不可操作'));
            }
            Db::name('order')->where('order_sn', $order_sn)->setField('status', 6);
            Db::name('order_refund')->where('id', $rfid)->setField('status', -1);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(lang('操作失败'), cookie('__forward__'));
        }
        action_log('order_refund_refuse', 'goods', 0, UID);
        $this->success(lang('操作成功'), cookie('__forward__'));
    }
}
