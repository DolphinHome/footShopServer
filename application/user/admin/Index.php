<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\user\admin;

use app\common\model\Api;
use app\admin\admin\Base;
use app\common\model\Order;
use app\goods\model\Cart;
use app\operation\model\UserAddress;
use app\user\model\MoneyLog;
use app\user\model\User as UserModel;
use service\ApiReturn;
use service\Format;
use think\Cache;
use think\Db;
use think\facade\Cookie;

/**
 * 会员主表控制器
 * @package app\User\admin
 */
class Index extends Base
{
    /**
     * 会员主表列表
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = request()->param();
        $name = isset($map['name']) ? $map['name'] : '';
        $user_type = isset($map['user_type']) ? $map['user_type'] : 'all';
        $sex = isset($map['sex']) ? $map['sex'] : 'all';
        $mobile = isset($map['mobile']) ? $map['mobile'] : '';
        $create_time = isset($map['create_time']) ? $map['create_time'] : 0;
        $by = isset($map['by']) ? $map['by'] : '';
        $orders = isset($map['order']) ? $map['order'] : '';
        $where = ' is_delete = 0 ';
        if ($name) {
            $where .= " and (user_name like '%{$name}%' or user_nickname like '%{$name}%') ";
        }
        if ($mobile) {
            $where .= " and mobile like '%{$mobile}%' ";
        }
        if ($user_type != 'all') {
            $where .= " and user_type ={$user_type} ";
        }
        if ($create_time) {
            $create_time = explode(' - ', $create_time);
            $start_time = strtotime($create_time[0].' 00:00:00');
            $end_time = strtotime($create_time[1].' 23:59:59');
            $where .= " and create_time >= $start_time and create_time <= $end_time  ";
        }


        if ($sex != 'all') {
            $where .= " and sex ={$sex} ";
        }
        $order = ['id' => 'desc'];
        if ($by && $orders) {
            $order = [$orders => $by];
        }
        $search_fields = [
            ['name', lang('昵称'), 'text'],
            ['mobile', lang('手机号'), 'text'],
            //会员类型0普通会员1白银会员2黄金会员
//            ['user_type', lang('会员类型'), 'select', '', ['all' => lang('全部'), '0' => lang('普通会员'), '1' => lang('白银会员'), '2' => lang('黄金会员')]],
            ['sex', lang('性别'), 'select', '', ['all' => lang('全部'), '0' => lang('保密'), '1' => lang('男'), '2' => lang('女')]],
            ['create_time', lang('注册时间'), 'daterange'],
        ];
        // 数据列表
        $data_list = UserModel::where($where)->order($order)->paginate();
        foreach ($data_list as $k => &$v) {
            if (!$v['head_img']) {
                $data_list[$k]['head_img'] = Db::name("admin_config")->where('name', 'web_site_logo')->value('value');
            }
            $v['order_num'] = Order::where([
                'user_id' => $v['id']
            ])->count();
            $order_time = Order::where([
                'user_id' => $v['id']
            ])->max('create_time');
            $v['order_time'] = $order_time ? date('Y-m-d H:i:s', $order_time) : '';
        }
        //导出excel
        if (isset($map['is_import'])) {
            if (isset($map['ids']) && !empty($map['ids'])) {
                $where .= " and id in ({$map['ids']}) ";
            }
            $list = UserModel::where($where)->order($order)->select();
            $excelData = $_excelData = [];
            foreach ($list as $v) {
                $excelData[] = [
                    'user_nickname' => $v['user_nickname'],
                    'user_type' => $this->getMember($v['user_type']),
                    'sex' => $this->getUserSex($v['sex']),
                    'mobile' => $v['mobile'],
                    'user_money' => $v['user_money'],
                    'score' => $v['score'],
                    'total_consumption_money' => $v['total_consumption_money'],
                    'count_score' => $v['count_score'],
                    'create_time' => $v['create_time']

                ];
            }
            $_excelData[0]['list'] = $excelData;
            $xlsName = '会员信息-' . date("Y-m-d H:i:s", time());
            $xlsCell = [
                ['user_nickname', lang('昵称')],
                ['user_type', lang('会员类型')],
                ['sex', lang('性别')],
                ['mobile', lang('手机号')],
                ['user_money', lang('会员余额')],
                ['score', '会员积分	'],
                ['total_consumption_money', lang('累计消费金额')],
                ['count_score', lang('累计获取积分')],
                ['create_time', lang('注册时间')]
            ];
            $excelData = array_values($_excelData);
            $this->exportExcel($xlsName, $xlsCell, $excelData);
        }


        $fields = [
            ['id', 'ID'],
            ['head_img', lang('头像'), 'picture'],
//            ['user_name', lang('真实姓名')],
            ['user_nickname', lang('昵称'), 'callback', function ($value, $data) {
                return
                    "<a  href=" . url('info', ['ids' => $data['id'], 'layer' => 1])
                    . " class='mr5 comment' data-toggle='dialog-right'>{$value}</a>";
            }, '__data__'],
//            ['user_type', lang('会员类型'), 'status', '', [lang('普通会员'), lang('白银会员'), lang('黄金会员')]],
            ['sex', lang('性别'), 'callback', function ($value) {
                return $this->getUserSex($value);
            }],
            ['mobile', lang('手机号')],
//            ['user_money', lang('会员余额'), 'text.edit', '', '', '', 'user'],
//            ['score', lang('会员积分'), 'text.edit', '', '', '', 'user'],

//            ['user_level', lang('会员等级')],
            ['total_consumption_money', lang('累计消费金额')],
//            ['count_score', lang('累计获取积分')],
//            ['freeze_money', lang('冻结金额')],
            ['order_num', lang('下单次数')],
            ['order_time', lang('最后一次下单时间')],
//            ['id', lang('优惠券数量'), 'callback', function ($value) {
//                return $this->coupon_num($value);
//            }],
            ['status', lang('状态'), 'status'],
            ['create_time', lang('注册时间')],
            ['right_button', lang('操作'), 'btn']
        ];
        //是否显示导出excel按钮 1显示
        $this->assign('excel_show', 1);
        if (count($data_list) <= 0) {
            $this->bottom_button_select = [];
        }
        return Format::ins()//实例化
        ->setOrder('user_money,score,total_consumption_money,count_score')
            ->addColumns($fields)//设置字段
            ->setTopSearch($search_fields)
            ->setTopButtons($this->top_button)
            //->bottom_button_select($this->bottom_button_select)
            ->setRightButtons($this->right_button, ['delete'])
//            ->setRightButtons([['ident' => 'user_delete', 'title' => lang('删除'), 'href' => ['user_delete', ["ids" => '__id__']],
//                'icon' => 'fa fa-times pr5', 'class' => 'btn btn-xs mr5 btn-default  ajax-get confirm']])
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /*
     * 分销列表
     *
     */
    public function distribution()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = $this->getMap();
        $name = isset($map['name']) ? $map['name'] : '';
        $mobile = isset($map['mobile']) ? $map['mobile'] : '';
        $create_time = isset($map['create_time']) ? $map['create_time'] : 0;
        $where = ' is_delete = 0 ';
        if ($name) {
            $where .= " and (user_name like '%{$name}%' or user_nickname like '%{$name}%') ";
        }
        if ($mobile) {
            $where .= " and mobile like '%{$mobile}%' ";
        }
        if ($create_time) {
            $create_time = explode(' - ', $create_time);
            $start_time = strtotime($create_time[0].' 00:00:00');
            $end_time = strtotime($create_time[1].' 23:59:59');
            $where .= " and create_time >= $start_time and create_time <= $end_time  ";
        }

        $search_fields = [
            ['name', lang('昵称'), 'text'],
            ['mobile', lang('手机号'), 'text'],
            ['create_time', lang('注册时间'), 'daterange'],
        ];
        // 数据列表
        $user_id = Db::name("distribution")->where([])->column("user_id");
        $pid = Db::name("distribution")->where([])->column("pid");
        $user_id_arr = array_merge($user_id, $pid);
        if (!$user_id_arr) {
            $user_id_arr = [0];
        }
        $user_id_str = implode(',', $user_id_arr);
        $where .= " and id in ({$user_id_str}) ";
        $data_list = Db::name("user")
            ->field("id,user_nickname,mobile,commission,create_time")
            ->where($where)
            ->order("id desc")
            ->paginate()
            ->each(function ($v) {
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                return $v;
            });

        $fields = [
            ['id', 'ID'],
            ['user_nickname', lang('昵称'), 'callback', function ($value, $data) {
                return
                    "<a  href=" . url('dis_info', ['id' => $data['id'], 'layer' => 1])
                    . " class='mr5 comment' data-toggle='dialog-right'>{$value}</a>";
            }, '__data__'],
            ['mobile', lang('手机号')],
            ['commission', lang('账户佣金')],
            ['id', lang('直接推广人数'), 'callback', function ($value, $data) {
                return
                    "<a  href=" . url('dis_first', ['ids' => $data['id'], 'layer' => 1])
                    . " class='mr5 comment' data-toggle='dialog-right'>{$this->first_num($value)}</a>";
            }, '__data__'],
            ['create_time', lang('间接推广人数'), 'callback', function ($value, $data) {
                return
                    "<a  href=" . url('dis_second', ['ids' => $data['id'], 'layer' => 1])
                    . " class='mr5 comment' data-toggle='dialog-right'>{$this->second_num($data['id'])}</a>";
            }, '__data__'],
            ['create_time', lang('注册时间')],
            ['right_button', lang('操作'), 'btn']
        ];
        $right_button = [['data-toggle' => 'dialog-right', 'title' => lang('查看'), 'href' => ['dis_info', ['id' => '__id__', 'layer' => 1]], 'icon' => 'fa fa-eye pr5', 'class' => 'btn btn-xs mr5 btn-default ']];
        return Format::ins()//实例化
            ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopSearch($search_fields)
            ->setRightButtons($right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示
    }

    /*
     * 获取直接推广人数
     *
     */
    public function first_num($user_id)
    {
        //直推人数
        return Db::name("distribution")->where([
            'pid' => $user_id
        ])->count();
    }

    /*
     * 间接推广人数
     *
     */
    public function second_num($user_id)
    {
        //间推人数
        $id_arr = Db::name("distribution")->where([
            'pid' => $user_id
        ])->column("user_id");
        if (!$id_arr) {
            $id_arr = [0];
        }
        return Db::name("distribution")->where([
            ['pid', 'in', $id_arr]
        ])->count();
    }

    /*
     * 分销详情
     *
     */
    public function dis_info()
    {
        //分销会员基本信息
        $id = request()->param('id', 0);
        $info = UserModel::where([
            'id' => $id
        ])->find();

        //直推人id
        $first_push = Db::name("distribution")->where([
            'pid' => $id
        ])->column("user_id");


        //间推人id
        $id_arr = Db::name("distribution")->where([
            'pid' => $id
        ])->column("user_id");
        $second_push = Db::name("distribution")->where([
            ['pid', 'in', $id_arr]
        ])->column("user_id");


        //返佣记录
        $list = Db::name("distribution_commission")
            ->where([
                'user_id' => $id
            ])
            ->field("sum(money) as money,order_sn,user_id,create_user_id,create_time")
            ->group("order_sn")
            ->paginate()
            ->each(function ($v) use ($first_push, $second_push) {
                $type = '';
                if (in_array($v['create_user_id'], $first_push)) {
                    $type = lang('直推会员');
                }
                if (in_array($v['create_user_id'], $second_push)) {
                    $type = lang('间推会员');
                }
                $v['type'] = $type;
                $v['user_name'] = Db::name("user")->where([
                    'id' => $v['create_user_id']
                ])->value("user_nickname");
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                return $v;
            });
        $pages = $list->render();

        $this->assign("info", $info);
        $this->assign("list", $list);
        $this->assign("pages", $pages);

        return $this->fetch();
    }

    /*
     * 直接推广人
     *
     */
    public function dis_first()
    {
        $id = request()->param("ids", 0);
        // 数据列表
        $list = Db::name("distribution")
            ->alias("d")
            ->field("u.id,u.user_nickname,u.mobile,u.commission,d.create_time")
            ->where([
                'd.pid' => $id
            ])
            ->join("user u", "d.user_id=u.id", "left")
            ->order("d.id desc")
            ->paginate()
            ->each(function ($v) {
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                return $v;
            });
        $pages = $list->render();
        $this->assign("list", $list);
        $this->assign("pages", $pages);
        return $this->fetch();
    }

    /*
     * 间接推广人
     *
     */
    public function dis_second()
    {
        $id = request()->param("ids", 0);
        $id_arr = Db::name("distribution")->where([
            'pid' => $id
        ])->column("user_id");
        if (!$id_arr) {
            $id_arr = [0];
        }
        // 数据列表
        $list = Db::name("distribution")
            ->alias("d")
            ->field("u.id,u.user_nickname,u.mobile,u.commission,d.create_time")
            ->where([
                ['d.pid', 'in', $id_arr]
            ])
            ->join("user u", "d.user_id=u.id", "left")
            ->order("d.id desc")
            ->paginate()
            ->each(function ($v) {
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                return $v;
            });
        $pages = $list->render();
        $this->assign("list", $list);
        $this->assign("pages", $pages);
        return $this->fetch();
    }

    /*
     * 获取优惠券数量
     *
     */
    public function coupon_num($user_id)
    {
        return Db::name("operation_coupon_record")->where([
            ['user_id', '=', $user_id],
            ['status', '=', 1],
            ['start_time', '<=', time()],
            ['end_time', '>=', time()]
        ])->count();
    }

    public function user_delete($ids)
    {
        Db::startTrans();
        try {
            Db::name("user")->where(['id' => $ids])->setField('is_delete', 1);
            Db::name("user_info")->where(['user_id' => $ids])->delete();    //用户信息做物理删除
            //清除缓存
            Api::clearUserCache($ids);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error(lang('删除失败'));
        }
        $this->success(lang('删除成功'));
    }

    public function setStatus($type = '')
    {
        $data = input("param.");
        $ids = $data['ids'];
        $type = $data['type']??$data['action'];
        $ids = (array)$ids;

        empty($ids) && $this->error(lang('缺少主键'));
        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = UserModel::where('id', 'IN', $ids)->setField('status', 0);
                Api::clearUserCache($ids[0]);
                action_log('user_disable', 'user', 0, UID, lang('批量禁用用户') . 'ID:' . $ids);
                break;
            case 'enable': // 启用
                $result = UserModel::where('id', 'IN', $ids)->setField('status', 1);
                Api::clearUserCache($ids[0]);
                action_log('user_enable', 'user', 0, UID, lang('批量启用用户') . 'ID:' . $ids);
                break;
            case 'delete': // 删除
                $result = UserModel::where('id', 'IN', $ids)->setField('is_delete', 1);
                action_log('user_delete', 'user', 0, UID, lang('批量删除商品') . 'ID:' . $ids);
                break;
            default:
                $this->error(lang('非法操作'));
                break;
        }

        if (false !== $result) {
            // \Cache::clear();
            // 记录行为
            action_log('admin_user_' . $type, 'user', $ids, UID, 'ID：' . implode('、', $ids));
            return ApiReturn::r(1, [], lang('操作成功'));
        } else {
            return ApiReturn::r(0, [], lang('操作失败'));
        }
    }


    /*
     * 会员购物车
     *
     */
    public function cart($data)
    {
        $list_rows = $data['list_rows'];
        $list = Cart::where(['user_id' => $data['user_id']])->order('create_time DESC')
            ->paginate($list_rows, false, ['query' => request()->param()]);
        $pages = $list->render();
        $result = [
            'list' => $list,
            'pages' => $pages,
        ];
        return $result;
    }

    /*
     * 会员订单
     *
     */
    public function order($data)
    {
        $list_rows = $data['list_rows'];
        $list = Order::where(['user_id' => $data['user_id']])
            ->order('create_time DESC')
            ->paginate($list_rows, false, ['query' => request()->param()])
            ->each(function (&$v) {
                $v['status'] = Order::$order_status[$v['status']];
                return $v;
            });
        $pages = $list->render();
        $result = [
            'list' => $list,
            'pages' => $pages,
        ];
        return $result;
    }

    /*
     *
     *会员地址
     */
    public function address($data)
    {
        $list_rows = $data['list_rows'];
        $list = UserAddress::where(['user_id' => $data['user_id']])
            ->paginate($list_rows, false, ['query' => request()->param()]);
        $pages = $list->render();
        $result = [
            'list' => $list,
            'pages' => $pages,
        ];
        return $result;
    }

    /*
     *
     * 会员充值
     *
     */
    public function recharge($data)
    {
        $list_rows = $data['list_rows'];
        $list = MoneyLog::where([
            'user_id' => $data['user_id'],
            'change_type' => 1
        ])
            ->paginate($list_rows, false, ['query' => request()->param()]);
        $pages = $list->render();
        $result = [
            'list' => $list,
            'pages' => $pages,
        ];
        return $result;
    }

    /*
     *
     * 提现
     *
     */
    public function withdrawal($data)
    {
        $list_rows = $data['list_rows'];
        $list = MoneyLog::where([
            'user_id' => $data['user_id'],
            'change_type' => 4
        ])
            ->paginate($list_rows, false, ['query' => request()->param()]);
        $pages = $list->render();
        $result = [
            'list' => $list,
            'pages' => $pages,
        ];
        return $result;
    }

    /*
     * 积分
     *
     */
    public function integral($data)
    {
        $list_rows = $data['list_rows'];
        $list = Db::name("user_score_log")->where([
            'user_id' => $data['user_id'],
        ])
            ->paginate($list_rows, false, ['query' => request()->param()]);
        $pages = $list->render();
        $result = [
            'list' => $list,
            'pages' => $pages,
        ];
        return $result;
    }

    /*
     * 浏览记录
     *
     */
    public function visit($data)
    {
        $list_rows = $data['list_rows'];
        $list = Db::name("user_collection")->where([
            'user_id' => $data['user_id'],
            'type' => 3
        ])->order("create_time DESC")->paginate($list_rows, false, ['query' => request()->param()]);
        $pages = $list->render();
        $result = [
            'list' => $list,
            'pages' => $pages,
        ];
        return $result;
    }

    /*
     *
     * 收藏记录
     *
     */
    public function collection($data)
    {
        $list_rows = $data['list_rows'];
        $list = Db::name("user_collection")->where([
            'user_id' => $data['user_id'],
            'type' => 1
        ])->order("create_time DESC")->paginate($list_rows, false, ['query' => request()->param()]);
        $pages = $list->render();
        $result = [
            'list' => $list,
            'pages' => $pages,
        ];
        return $result;
    }


    /*
     * 会员详情
     */
    public function info()
    {
        $ids = input('param.ids');
        $page = input('param.page');
        $list_rows = input('param.list_rows');
        $info = UserModel::where(['id' => $ids])->find();
        if (!$info) {
            $this->error(lang('信息不存在'));
        }
        $info['head_img'] = get_file_url($info['head_img']);
        if (strpos($info['head_img'], 'images/none.png') !== false) {
            $info['head_img'] = config('web_site_domain') . '/static/admin/images/benben.png';
        }

        $info['user_type'] = $this->getMember($info['user_type']);
        $info['sex'] = $this->getUserSex($info['sex']);
        $info['coupon_num'] = $this->coupon_num($ids);
        $this->assign('info', $info);

        $where = [
            'user_id' => $ids,
            'page' => $page,
            'list_rows' => $list_rows
        ];


        //会员购物车
        $cart = $this->cart($where);
        foreach ($cart['list'] as &$v) {
            $v['goods_thumb'] = get_file_url($v['goods_thumb']);
        }
        $this->assign('cart', $cart['list']);
        $this->assign('cart_pages', $cart['pages']);

        //会员地址
        $address = $this->address($where);
        $this->assign('address', $address['list']);
        $this->assign('address_pages', $address['pages']);

        //会员订单
        $order = $this->order($where);
        $this->assign('order', $order['list']);
        $this->assign('order_pages', $order['pages']);

        //会员充值
        $order_recharge = $this->recharge($where);
        $this->assign('recharge', $order_recharge['list']);
        $this->assign('recharge_pages', $order_recharge['pages']);

        //会员提现
        $withdrawal = $this->withdrawal($where);
        $this->assign('withdrawal', $withdrawal['list']);
        $this->assign('withdrawal_pages', $withdrawal['pages']);

        //会员积分
        $integral = $this->integral($where);
        $this->assign('integral', $integral['list']);
        $this->assign('integral_pages', $integral['pages']);

        //浏览记录
        $visit = $this->visit($where);
        $this->assign('visit', $visit['list']);
        $this->assign('visit_pages', $visit['pages']);

        //收藏记录
        $collection = $this->collection($where);
        $this->assign('collection', $collection['list']);
        $this->assign('collection_pages', $collection['pages']);


        return $this->fetch();
    }

    public function getMember($user_type)
    {
        $data = ['0' => lang('普通会员'), '1' => lang('白银会员'), '2' => lang('黄金会员')];
        return $data[$user_type];
    }

    public function getUserSex($sex)
    {
        $data = ['0' => lang('保密'), '1' => lang('男'), '2' => lang('女')];
        return $data[$sex];
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
            $birthday = strtotime($data['birthday']);
            if ($birthday > time()) {
                exception(lang('生日不能超过当前时间'));
            }
            $data['birthday'] = $birthday;

            // 验证
            $result = $this->validate($data, 'User');
            if (true !== $result) {
                $this->error($result);
            }
            // 启动事务
            Db::startTrans();
            try {
                //验证手机号是否已经存在
                $find = UserModel::where(['mobile' => $data['mobile']])->find();
                if ($find) {
                    exception(lang('手机号已存在'));
                }
                $result = UserModel::create($data);
                $id = $result->id;
                if (!$id) {
                    exception(lang('新增会员失败'));
                }
                // 新增会员附加信息
                $userinfo = Db::name('user_info')->insert(['user_id' => $id, 'invite_code' => 'IC00' . $id]);
                if (!$userinfo) {
                    exception(lang('新增会员附加信息失败'));
                }
                //记录行为
                unset($data['__token__']);
                $details = json_encode($data, JSON_UNESCAPED_UNICODE);
                action_log('user_index_add', 'user', $id, UID, $details);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success(lang('新增成功'), cookie('__forward__'));
        }

        $fields = [
            ['type' => 'text', 'name' => 'mobile', 'title' => lang('手机号')],
            ['type' => 'password', 'name' => 'password', 'title' => lang('密码')],
            ['type' => 'text', 'name' => 'user_nickname', 'title' => lang('昵称')],
            ['type' => 'text', 'name' => 'user_name', 'title' => lang('姓名')],
            ['type' => 'image', 'name' => 'head_img', 'title' => lang('头像')],
            ['type' => 'date', 'name' => 'birthday', 'title' => lang('生日'), 'tips' => '', 'attr' => ''],
            ['type' => 'radio', 'name' => 'sex', 'title' => lang('性别'), 'tips' => '', 'attr' => '', 'extra' => [lang('未知'), lang('男'), lang('女')], 'value' => '0'],
            ['type' => 'selects', 'name' => 'user_email', 'title' => '角色', 'tips' => '', 'extra' => ['超级管理员','普通管理员','员工'] , 'value' => '0'],


        ];
        $this->assign('page_title', lang('新增会员'));
        $this->assign('form_items', $fields);
        return $this->fetch('admin@public/add');
    }

    /**
     * 编辑
     * @param null $id 会员主表id
     * @author 似水星辰 [2630481389@qq.com]
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) {
            $this->error(lang('缺少参数'));
        }

        $info = UserModel::get($id);
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['birthday'] = strtotime($data['birthday']);
            // 如果没有填写密码，则不更新密码
            if ($data['password'] == '') {
                unset($data['password']);
            }

            // 验证
            $result = $this->validate($data, 'User');
            if (true !== $result) {
                $this->error($result);
            }
            $UserModel = new UserModel();
            if ($UserModel->allowField(['user_nickname', 'password', 'head_img'])->update($data)) {
                // 记录行为
                unset($data['__token__']);
                $details = arrayRecursiveDiff($data, $info);
                action_log('user_index_edit', 'user', $id, UID, $details);
                $this->success(lang('编辑成功'), cookie('__forward__'));
            } else {
                $this->error(lang('编辑失败'));
            }
        }

       
        $fields = [
            ['type' => 'hidden', 'name' => 'id'],
            ['type' => 'text', 'name' => 'mobile', 'title' => lang('手机号'), 'tips' => '', 'attr' => ''],
            ['type' => 'password', 'name' => 'password', 'title' => lang('重置密码'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'user_nickname', 'title' => lang('昵称'), 'tips' => '', 'attr' => ''],
            ['type' => 'text', 'name' => 'user_name', 'title' => lang('姓名'), 'tips' => '', 'attr' => ''],
            ['type' => 'image', 'name' => 'head_img', 'title' => lang('头像'), 'tips' => '', 'attr' => ''],
            ['type' => 'date', 'name' => 'birthday', 'title' => lang('生日'), 'tips' => '', 'attr' => ''],
            ['type' => 'radio', 'name' => 'sex', 'title' => lang('性别'), 'tips' => '', 'attr' => '', 'extra' => [lang('未知'), lang('男'), lang('女')], 'value' => '0'],
            ['type' => 'text', 'name' => 'user_email', 'title' => lang('邮箱')],
            ['type' => 'text', 'name' => 'user_vip', 'title' => lang('会员卡等级')],
            ['type' => 'text', 'name' => 'user_vip_start_time', 'title' => lang('会员卡开始时间')],
            ['type' => 'text', 'name' => 'user_vip_last_time', 'title' => lang('会员卡结束时间')],



            //['type' => 'number', 'name' => 'user_level', 'title' => lang('会员等级'), 'tips' => '', 'attr' => '', 'value' => '0'],
            //['type' => 'number', 'name' => 'user_type', 'title' => lang('会员类型'), 'tips' => '1注册会员', 'attr' => '', 'value' => '1']
        ];
        $this->assign('page_title', lang('编辑会员信息'));
        $this->assign('form_items', $this->setData($fields, $info));
        return $this->fetch('admin@public/edit');
    }

    /*
     * 语言切换
     *
     */
    public function lang()
    {
        $language = request()->param("language");
        Cookie::set("language", $language, 24 * 60 * 60);
    }

    /**
     * 用户健康报告
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function userHealthArchives(){

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = request()->param();
        $name = isset($map['name']) ? $map['name'] : '';
        $where[] = ['uh.is_delete','=',0];
        if ($name) {
            $where[] = ['u.user_nickname','like',"%$name%"];
        }

        $search_fields = [
            ['name', lang('名称'), 'text']
        ];
        // 数据列表
        $data_list =  Db::name('user_health_archives')->alias('uh')->join('user u','uh.user_id=u.id')->where($where)->field("uh.*,u.user_nickname,u.head_img")->paginate();
        foreach ($data_list as $k => &$v) {
            if (!$v['head_img']) {
                $data_list[$k]['head_img'] = Db::name("admin_config")->where('name', 'web_site_logo')->value('value');
            }

        }

        $fields = [
            ['id', 'ID'],
            ['head_img', lang('头像'), 'picture'],
            ['user_nickname', lang('昵称')],
            ['foot_type', lang('脚型')],
            ['fit_shoes', lang('适合鞋型')],
            ['foot_type_influence', lang('脚型影响'),'text.tip'],
            ['fit_type_characteristic', lang('脚型特点'),'text.tip'],
            ['foot_type_introduce', lang('脚型介绍'),'text.tip'],
            ['foot_length_left', lang('左脚长度')],
            ['foot_length_right', lang('右脚长度')],
            ['foot_width_left', lang('左脚宽度')],
            ['foot_width_right', lang('右脚宽度')],
            ['focus_left_sole_img', lang('左脚脚掌图'), 'picture'],
            ['focus_left_heal_img', lang('左脚脚跟图'), 'picture'],
            ['focus_rightt_heal_img', lang('右脚脚跟图'), 'picture'],
            ['foot_type_analysis', lang('足型报告'),'text.tip'],
            ['thumb_left', lang('左脚拇指参数')],
            ['thumb_right', lang('右脚拇指参数')],
            ['thumb_testing', lang('拇指检测数说明'),'text.tip'],
            ['thumb_influence', lang('拇指脚型影响'),'text.tip'],
            ['thumb_motion', lang('拇指建议运动'),'text.tip'],
            ['heel_left', lang('左后跟参数')],
            ['heel_right', lang('右后跟参数')],
            ['heel_testing', lang('后跟检测定义'),'text.tip'],
            ['heel_influence', lang('后脚跟影响'),'text.tip'],
            ['heel_motion', lang('后跟建议运动'),'text.tip'],
            ['arch_foot_left', lang('左脚足弓参数')],
            ['arch_foot_right', lang('右脚足弓参数')],
            ['archl_testing', lang('足弓检测定义'),'text.tip'],
            ['archl_influence', lang('足弓影响'),'text.tip'],
            ['arch_motion', lang('足弓建议运动'),'text.tip'],
            ['right_button', lang('操作'), 'btn']
        ];
        $right_button = [
            ['ident'=> 'delete', 'title'=>'删除','href'=>['health_delete',['ids'=>'__id__']],'icon'=>'fa fa-times pr5','class'=>'btn btn-xs mr5 btn-default  ajax-get confirm'],
        ];
        return Format::ins()//实例化
        ->addColumns($fields)//设置字段
        ->setTopSearch($search_fields)
            ->setRightButtons($right_button)
            ->setData($data_list)//设置数据
            ->fetch();//显示



    }
}
