<?php
/*
 * @Descripttion:
 * @Version: 1.0
 * @Author: wangph
 * @Date: 2021-03-31 09:46:02
 * @LastEditors: wangph
 * @LastEditTime: 2021-04-29 11:13:03
 */

// +----------------------------------------------------------------------
// | 辉腾模块化框架
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 郑州辉腾科技有限公司
// +----------------------------------------------------------------------

namespace app\member\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\member\model\CashAccount as CashAccountModel;

/**
 * 提现账户管理
 * @author 晓风<215628355@qq.com>
 * @package app\cms\admin
 */
class CashAccount extends Admin
{

    /**
     * 首页
     * @author 晓风<215628355@qq.com>
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 查询
        $map = $this->getMap();        // 排序
        $order = $this->getOrder('sort asc,aid DESC');

        $dataList = CashAccountModel::where($map)->order("aid desc")->paginate();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
                ->setSearch(['user_id' => lang('会员ID')]) // 设置搜索框
                ->addColumns([// 批量添加数据列
                        ['aid', 'ID'],
                        ['user_id', lang('会员ID')],
                        ['true_name', lang('收款人姓名')],
                        ['account_type', lang('账户类型'),'status','',['--',lang('微信'),lang('支付宝'),lang('银行卡')]],
                        ['account_id', lang('账户')],
                        ['is_default', lang('是否默认'), 'status','',[lang('否'),lang('是')]],
                        ['create_time', lang('创建时间'),'datetime'],
                        ['right_button', lang('操作'), 'btn']
                ])
                ->setPrimaryKey('aid')
                ->addTopButtons('delete') // 添加顶部按钮
                ->addRightButtons('delete') // 添加右侧按钮
                ->setRowList($dataList) // 设置表格数据
                ->fetch(); // 渲染模板
    }
    public function add()
    {
    }
    public function edit($id = 0)
    {
    }
}
