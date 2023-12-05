<?php
/*
 * 发件人
 * @Version: 1.0
 * @Author: 似水星辰 [ 2630481389@qq.com ]
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-28 14:37:33
 */
namespace app\goods\admin;

use app\admin\admin\Base;
use Think\Db;
use service\Format;

class Sender extends Base
{

    /**
     * 发件人列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @since 2019/4/4 11:46
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();
        $where = [];
        if (isset($map['name'])) {
            $where[] = ['name', 'like', '%' . $map['name'] . '%'];
        }
        if (isset($map['phone'])) {
            $where[] = ['phone', '=', $map['phone']];
        }
        if (isset($map['pay_type']) && $map['pay_type'] != 'all') {
            $where[] = ['pay_type', '=', $map['pay_type']];
        }

        // 排序
        $order = $this->getOrder('id desc');
        // 数据列表
        $pay_type = ['all' => lang('全部'), 1 => lang('现付'), 2 => lang('到付'), 3 => lang('月付')];
        $data_list = Db::name('goods_express_sender')->where($where)->order($order)->paginate();
        $fields = [
            ['id', 'ID'],
            ['name', lang('供应商名称')],
            ['phone', lang('电话')],
            ['province', lang('省'),],
            ['city', lang('市')],
            ['area', lang('区'),],
            ['address', lang('街道'),],
            ['pay_type', lang('支付方式'), 'callback', function ($v) {
                switch ($v) {
                    case 1:
                        $n = lang('现付');
                        break;
                    case 2:
                        $n = lang('到付');
                        break;
                    case 3:
                        $n = lang('月付');
                        break;
                }
                return $n;
            }],
            ['right_button', lang('操作'), 'btn']
        ];
        $searchFields = [
            ['name', lang('供应商名称'), 'text'],
            ['phone', lang('电话'), 'text'],
            ['pay_type', lang('支付方式'), 'select', '', $pay_type],
        ];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopSearch($searchFields)
            ->setTopButtons($this->top_button_layer)
            ->setRightButtons($this->right_button_layer)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /**
     * 添加发货人
     * @param int $cid 栏目id
     * @param string $model 模型id
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return mixed
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        // 保存文档数据
        if ($this->request->isAjax() || $this->request->isPost()) {
            $data = request()->post();
            $result = $this->validate($data, 'ExpressSender');
            if(true !== $result) $this->error($result);
            if(!$data['area'][3]){
                $this->error(lang('请选择地址'));
            }

            $area = Db::name('china_area')->get($data['area'][3]);
            $city = Db::name('china_area')->get($area['pid']);
            $province = Db::name('china_area')->get($city['pid']);
            $param['name'] = $data['name'];
            $param['phone'] = $data['phone'];
            $param['province'] = $province['name'];
            $param['city'] = $city['name'];
            $param['area'] = $area['name'];
            $param['address'] = $data['address'];
            $param['pay_type'] = $data['pay_type'];
            $res = Db::name('goods_express_sender')->insertGetId($param);
            if ($res) {
                //记录行为
                unset($param['__token__']);
                $details = json_encode($param, JSON_UNESCAPED_UNICODE);
                action_log('goods_sender_add', 'goods_express_sender', $res, UID, $details);
                $this->success(lang('新增成功'), 'index');
            } else {
                $this->error(lang('新增失败'));
            }
    
        }
        $fields = [
            ['type' => 'text', 'name' => 'name', 'title' => lang('供应商名称'),],
            ['type' => 'text', 'name' => 'phone', 'title' => lang('电话')],
            ['type' => 'linkages', 'name' => 'area', 'key' => 'id', 'option' => 'name', 'pid' => 'pid', 'title' => lang('区域'), 'level' => 3, 'table' => 'china_area'],
            ['type' => 'text', 'name' => 'address', 'title' => lang('街道'), 'value' => ''],
            ['type' => 'select', 'name' => 'pay_type', 'title' => lang('支付方式'), 'extra' => [1 => lang('现付'), 2 => lang('到付'), 3 => lang('月付')]],
        ];
        $this->assign('page_title', lang('添加供应商'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑文档
     * @param null $id 文档id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @author 似水星辰 [2630481389@qq.com]
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('参数错误'));
        }
        $sender = Db::name('goods_express_sender')->get($id);
        // 保存文档数据
        if ($this->request->isPost()) {
            $data = request()->post();
            if(!$data['area'][3]){
                $this->error(lang('请选择地址'));
            }
            $area = Db::name('china_area')->get($data['area'][3]);
            $city = Db::name('china_area')->get($area['pid']);
            $province = Db::name('china_area')->get($city['pid']);
            $param['name'] = $data['name'];
            $param['phone'] = $data['phone'];
            $param['province'] = $province['name'];
            $param['city'] = $city['name'];
            $param['area'] = $area['name'];
            $param['address'] = $data['address'];
            $param['pay_type'] = $data['pay_type'];
            $res = Db::name('goods_express_sender')->where(['id' => $data['id']])->update($param);
            if ($res !== false) {
                //记录行为
                unset($param['__token__']);
                $details = arrayRecursiveDiff($param, $sender);
                action_log('goods_sender_edit', 'goods_express_sender', $id, UID, $details);
                $this->success(lang('编辑成功'), 'index');
            } else {
                $this->error(lang('编辑失败'));
            }
        }
       
        $area = Db::name('china_area')->where(['name' => $sender['area']])->find();
        $fields = [
            ['type' => 'hidden', 'name' => 'id', 'value' => $sender['id']],
            ['type' => 'text', 'name' => 'name', 'title' => lang('供应商名称'), 'value' => $sender['name']],
            ['type' => 'text', 'name' => 'phone', 'title' => lang('电话'), 'value' => $sender['phone']],
            ['type' => 'linkages', 'value' => $area['id'], 'name' => 'area', 'key' => 'id', 'option' => 'name', 'pid' => 'pid', 'title' => lang('区域'), 'level' => 3, 'table' => 'china_area'],
            ['type' => 'text', 'name' => 'address', 'title' => lang('街道'), 'value' => $sender['address']],
            ['type' => 'select', 'name' => 'pay_type', 'title' => lang('支付方式'), 'extra' => [1 => lang('现付'), 2 => lang('到付'), 3 => lang('月付')], 'value' => $sender['pay_type']],
        ];
        $this->assign('page_title', lang('编辑供应商'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/edit');
    }

    public function setStatus($type = '')
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;

        empty($ids) && $this->error(lang('缺少主键'));

        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = Db::name('goods_express_sender')->where('id', 'IN', $ids)->setField('status', 0);
                break;
            case 'enable': // 启用
                $result = Db::name('goods_express_sender')->where('id', 'IN', $ids)->setField('status', 1);
                break;
            case 'delete': // 删除
                $result = Db::name('goods_express_sender')->where('id', 'IN', $ids)->delete();
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }
        if (false !== $result) {
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }
}
