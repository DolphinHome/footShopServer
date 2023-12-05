<?php
/**
 * Notes:
 * User: chenchen
 * Date: 2021/7/6
 * Time: 11:54
 * @return
 */

namespace app\operation\admin;

use app\admin\admin\Base;
use app\goods\model\Goods as GoodsModel;
use service\ApiReturn;
use service\Format;
use think\paginator\driver\Bootstrap;
use app\operation\model\LiveMedia;

class LiveGoods extends Base
{


    /**
     * Notes: 小程序直播商品列表
     * User: chenchen
     * Date: 2021/7/6
     * Time: 17:03
     * @return mixed
     */
    public function index()
    {
        $request = $this->request->param();
        $limit = $request["list_rows"] ?? 15;
        $offset = $request["page"] ?? 1;
        $status = $request["status"] ?? 0;
        $arr = [
            "offset" => $offset,
            "limit" => $limit,
            "status" => $status
        ];
        $list = addons_action('WeChat/MiniPay/get_goods_approved', [$arr]);
        $data_list = $list['goods'];
        $fields = [
            ['goodsId', lang('商品ID')],
            ['coverImgUrl', lang('商品图片'), 'picture'],
            ['name', lang('商品名称')],
            ['price', lang('商品价格')],
            ['right_button', lang('操作'), 'btn', '', '', 'text-center']
        ];

        $showdata = array_slice($data_list, ($offset - 1) * $limit, $limit, true);
        $p = Bootstrap::make($showdata, $limit, $offset, $list['total'], false, [
            'var_page' => 'page',
            'path' => url('admin/live_goods/index'),//这里根据需要修改url
            'query' => [],
            'fragment' => '',
        ]);

        $p->appends($_GET);
        $pages = $p->render();
        $this->assign('plist', $p);
        $this->assign('pages', $p->render());
        $tab_list = [
            'wait' => ['title' => lang('未审核'), 'url' => url('index', ['status' => 0])],
            'check' => ['title' => lang('审核中'), 'url' => url('index', ['status' => 1])],
            'pass' => ['title' => lang('审核通过'), 'url' => url('index', ['status' => 2])],
            'refuse' => ['title' => lang('审核驳回'), 'url' => url('index', ['status' => 3])],
        ];
        if ($status == 0) {
            $module = 'wait';
        } elseif ($status == 1) {
            $module = 'check';
        } elseif ($status == 2) {
            $module = 'pass';
        } else {
            $module = 'refuse';
        }
        $top_button = [];
        $right_button = [
//            ['ident' => 'edit', 'title' => '编辑', 'href' => ['edit', ['goods_id' => '__goodsId__']], 'icon' => 'fa fa-pencil pr5', 'class' => 'btn btn-xs mr5 btn-default layeredit'],
            ['ident' => 'delete', 'title' => '删除', 'href' => ['delete', ['goods_id' => '__goodsId__']], 'icon' => 'fa fa-times pr5', 'class' => 'btn btn-xs mr5 btn-default  ajax-get confirm'],
        ];
        if ($status == 0) {
            $top_button = [
                ['ident' => 'add', 'title' => '新增', 'href' => 'add', 'icon' => 'fa fa-plus pr5', 'class' => 'btn btn-sm mr5 btn-primary layeredit']
            ];
            $right_button[] = ['ident' => 'disable', 'title' => '撤回', 'href' => ['reset_audit', ['goods_id' => '__goodsId__', 'audit_id' => '__auditId__']], 'icon' => 'fa fa-pencil pr5', 'class' => 'btn btn-xs mr5 btn-default ajax-get confirm'];
        }
        return Format::ins()//实例化
        ->hideCheckbox()
            ->setTabNav($tab_list, $module)
            ->addColumns($fields)//设置字段
            ->setTopButtons($top_button)
            ->setRightButtons($right_button)
            ->setData($showdata)//设置数据
            ->setPages($pages)
            ->fetch();//显示
    }

    /**
     * Notes:
     * User: chenchen
     * Date: 2021/7/6
     * Time: 17:49
     * @return mixed
     * @throws \Exception
     */
    public function add()
    {

        // 保存文档数据
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $data["goodsInfo"] = [
                "coverImgUrl" => $param["media_id"] ?? '',
                "name" => $param["name"] ?? '',
                "priceType" => 1,
                "price" => $param["price"] ?? '',
                "url" => $param["url"]
            ];
            $res = addons_action('WeChat/MiniPay/add_goods', [$data]);
            if ($res["errcode"] == 0) {
                $this->success(lang('新增成功'), 'index');
            } else {
                $this->error(lang('新增失败'));
            }
        }
        return $this->fetch();
    }

    /**
     * Notes: 获取素材
     * User: chenchen
     * Date: 2021/7/8
     * Time: 14:11
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_media()
    {
        $map = [
            ["is_temp", "=", 0]
        ];
        $map1 = [
            ["is_temp", "=", 1],
            ["create_time", ">=", (time() - 24 * 60 * 60 * 3)]
        ];
        $list = LiveMedia::where(function ($query) use ($map, $map1) {
            $query->whereOr([$map, $map1]);
        })
            ->field("name,media_id,upload_id as url")
            ->select();
        if (count($list) > 0) {
            $list = $list->each(function ($v) {
                $v["url"] = get_file_url($v["url"]);
                return $v;
            })->toArray();
        } else {
            $list = [];
        }
        return ApiReturn::r(1, $list, 'ok');

    }

    /**
     * Notes: 获取小程序商品链接
     * User: chenchen
     * Date: 2021/7/8
     * Time: 15:00
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function get_link()
    {
        $where = [['is_delete', '=', 0], ['is_sale', '=', 1], ['status', '=', 1]];
        $list = GoodsModel::where($where)
            ->field("name,id")
            ->paginate()->each(function ($v) {
                $v['name'] = mb_substr($v['name'], 0, 30);
                $v['url'] = 'pages/goods/goodsdetail/goods-detail/index?goods_id=' . $v['id'] . '&sku_id=0';
                return $v;
            });
        return ApiReturn::r(1, $list, 'ok');
    }


    /**
     * Notes: 撤回直播商品的提审申请
     * User: chenchen
     * Date: 2021/7/8
     * Time: 15:41
     */
    public function reset_audit()
    {
        $param = $this->request->param();
        $auditId = $param["audit_id"] ?? 0;
        $goodsId = $param["goods_id"] ?? 0;
        if (!$auditId || !$goodsId) {
            $this->error(lang("参数错误"));
        }
        $data = [
            "auditId" => $auditId,
            "goodsId" => $goodsId
        ];
        $res = addons_action('WeChat/MiniPay/goods_reset_audit', [$data]);
        if ($res["errcode"] == 0) {
            $this->success(lang('撤销成功'), 'index');
        } else {
            $this->error(lang('撤销失败'));
        }
    }


    /**
     * Notes: 已撤回提审的商品再次发起提审申请
     * User: chenchen
     * Date: 2021/7/8
     * Time: 16:53
     */
    public function audit()
    {
        $goodsId = $this->request->param("goods_id", 0);
        $data = [
            "goodsId" => $goodsId
        ];
        $res = addons_action('WeChat/MiniPay/goods_audit', [$data]);
        if ($res["errcode"] == 0) {
            $this->success(lang('操作成功'), 'index');
        } else {
            $this->error(lang('操作失败'));
        }
    }

    /**
     * Notes: 删除直播商品
     * User: chenchen
     * Date: 2021/7/8
     * Time: 17:10
     */
    public function delete()
    {
        $goodsId = $this->request->param("goods_id", 0);
        $data = [
            "goodsId" => $goodsId
        ];
        $res = addons_action('WeChat/MiniPay/del_goods', [$data]);
        if ($res["errcode"] == 0) {
            $this->success(lang('操作成功'), 'index');
        } else {
            $this->error(lang('操作失败'));
        }
    }


}