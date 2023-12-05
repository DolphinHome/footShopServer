<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\goods\model\Goods as GoodsModel;
use app\goods\model\ActivityDetails as ActivityDetailsModel;
use app\user\model\Task as TaskModel;
use service\ApiReturn;
use service\Str;
use Think\Db;
use service\SphinxClient;
use WeChat\Exceptions\InvalidResponseException;
use app\api\controller\v1\Upload;

/**
 * APP首页数据展示接口
 * @package app\api\controller\v1
 */
class Index extends Base
{
    // 定义搜索的数组
    private $notInIds = [];
    private $serchInfo = [];

    /**
     * 商城首页获取轮播图，banner图等
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/22 8:58
     */
    public function index($data)
    {
        $ads = \think\Db::name('operation_ads_type')->alias('at')
            ->field('at.name as type_name,u.path as thumb,a.href,a.width,a.height')
            ->join('operation_ads a', 'at.id=a.typeid')
            ->leftJoin('__UPLOAD__ u', 'u.id=a.thumb')
            ->where('at.status', 1)
            ->where('a.status', 1)
            ->select();
        $res = [];
        foreach ($ads as &$vo) {
            $res[$vo['type_name']][] = $vo;
        }
        unset($vo);
        foreach ($res as $k=>$v) {
            if ($k == lang('首页顶部轮播')) {
                foreach ($v as $key=>$val) {
                    $res[$k][$key]['rgb'] = $this->img($val['thumb']);
                }
            }
        }
        $arr = Db::query("
            SELECT 
                * 
            FROM lb_goods_category 
            WHERE lb_goods_category.pid IN (
                SELECT 
                    id 
                FROM lb_goods_category 
                -- WHERE lb_goods_category.pid = 7
            ) 
            ORDER BY RAND() 
            LIMIT 6
        ");
        $res['category'] = $arr;
        $res = unserialize(str_replace(array('NAN;','INF;'), '0;', serialize($res)));
        return ApiReturn::r(1, $res, lang('图片信息'));
    }

    public function img($img)
    {
        $rTotal = 0;
        $gTotal = 0;
        $bTotal = 0;
        $total = 0;
        $i = imagecreatefrompng($img);
        if (!$i) {
            $i = imagecreatefromjpeg($img);
        }
        for ($x=0;$x<imagesx($i);$x++) {
            for ($y=0;$y<imagesy($i);$y++) {
                $rgb = imagecolorat($i, $x, $y);
                $r  = ($rgb  >>  16) &  0xFF ;
                $g  = ($rgb  >>  8) &  0xFF ;
                $b  =  $rgb  &  0xFF ;
                $rTotal += $r;
                $gTotal += $g;
                $bTotal += $b;
                $total++;
            }
        }
        $rAverage = round($rTotal/$total);
        $gAverage = round($gTotal/$total);
        $bAverage = round($bTotal/$total);
        return [$rAverage,$gAverage,$bAverage];
    }


    /**
     * 首页获取站内信未读条数
     * @param array $data
     * @param array $user
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/22 8:58
     */
    public function getMsg($data, $user)
    {
        $msg_num = \think\Db::name('operation_system_message')->where('to_user_id', $user['id'])->where('is_read', 0)->count('id');
        return ApiReturn::r(1, $msg_num, lang('站内信条数'));
    }

    /*
     * 判断是否是新用户
     * */
    public function isNew($data, $user)
    {
        $orders = Db::name("order")->where(['user_id'=>$user['id'],'pay_status'=>1])->select();
        $goods = [];
        if (!count($orders)) {
            $sql = "SELECT g.shop_price,g.thumb,a.type,a.name 
as type_name,d.stock,d.goods_id,d.name,d.sku_id,d.activity_id,d.activity_price,d.limit,g.market_price
FROM lb_goods_activity 
AS a 
LEFT JOIN lb_goods_activity_details 
AS d ON a.id=d.activity_id 
LEFT JOIN lb_goods AS g ON d.goods_id=g.id WHERE d.status=1 AND a.type=7";
            $goods = Db::query($sql);
        }
        /*        else{
                    $sql = "SELECT g.shop_price,u.thumb,a.type,a.name as type_name,d.stock,d.goods_id,d.name,d.sku_id,d.activity_id,d.activity_price,d.limit FROM lb_goods_activity AS a LEFT JOIN lb_goods_activity_details AS d ON a.id=d.activity_id LEFT JOIN lb_goods AS g ON d.goods_id=g.id LEFT JOIN lb_upload u ON u.id=g.thumb WHERE d.status=1 AND a.type=7 AND a.name=lang('老用户福利')";
                }*/
        if (count($goods) > 0) {
            foreach ($goods as $k => $v) {
                if ($v['sku'] > 0) {
                    $shop_price = DB::name("goods_sku")->field("shop_price")->where(['sku_id' => $v['sku']])->find();
                    $goods[$k]['shop_price'] = $shop_price['shop_price'];
                }
                $goods[$k]['thumb'] = get_file_url($v['thumb']);
            }
        }
        return ApiReturn::r(1, $goods, lang('成功'));
    }


    /**
     * 获取功能菜单---九宫格
     * @param array $data
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/22 8:58
     */
    public function menu($data)
    {
        $menu = \think\Db::name('operation_nav_type')->alias('nt')
            ->field('nt.name as type_name,n.name,n.thumb,n.href')
            ->join('operation_nav n', 'nt.id=n.typeid')
            ->where('nt.status', 1)
            ->where('n.status', 1)
            ->order('n.sort desc')
            ->select();
        $res = [];
        foreach ($menu as &$vo) {
            $vo['thumb'] = get_thumb($vo['thumb']) ;
            $res[$vo['type_name']][] = $vo;
        }
        unset($vo);
        return ApiReturn::r(1, $res, '');
    }

    /**
     * 获取商品的活动版块
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @param $type int 商品版块 [0=>is_recommed, 1=>is_new, 2=>is_hot]
     * @param $size int 返回数量
     */
    public function goods_block($data)
    {
        if ($data['goods_id']) {
            $goods_id_list=explode(',', $data['goods_id']);
            $map[] = ['g.id','in',$goods_id_list];
        } else {
            $typeWhere = [['g.is_recommend','=',1], ['g.is_new','=',1], ['g.is_hot','=',1],['g.is_boutique','=',1],['g.is_sale','=',1]];
            $type = $data['type'] ?: 0;
            if ($type==3) {
                $order = ['weight'=> 'desc'];
            }
            $map[] = $typeWhere[$type];
        }
        if ($data['cid']) {
            $cat=Db::name('goods_category')->where(['pid'=>$data['cid']])->column('id');
            $cat=Db::name('goods_category')->where([['pid','in',$cat]])->column('id');
            $map[]=['g.cid','in',$cat];
        }
        if ($data['key_word']){
             $map[]=['g.name','like','%'.$data['key_word'].'%'];
        }
        if ($data['goods_rem_type']){
             $map[]=['g.goods_rem_type','=',$data['goods_rem_type']];
        }
        $where = '';
        if($data['lable_id']){
            $search_arr = explode(',',$data['lable_id']);

            foreach($search_arr as $value){
                if(empty($where)){
                    $where .= " find_in_set($value,lable_id) ";
                }else{
                    $where .= "or find_in_set($value,lable_id) ";
                }

            }
        }
        // dump($map);die;
        $total = GoodsModel::alias("g")->where($map)->where([['g.is_delete', '=', 0],['g.is_sale','=',1], ['g.status', '=', 1]])->count();
        $goods = GoodsModel::goods_list($map, $order, $data['size'], $data['page'],$where);

        foreach ($goods as $k => $v) {
            $sku = Db::name('goods_sku')->order('member_price asc, sku_id asc')->where([['goods_id', '=', $v['id']],['status', '=',1],['stock', '<>',0]])->find();
            $goods[$k]['sku_id'] = $sku['sku_id'] ? $sku['sku_id'] : 0;
            $goods[$k]['shop_price'] = $sku['shop_price'] ? $sku['shop_price'] : $v['shop_price'];
        }
        //$goods = ActivityDetailsModel::findActivityGoodsByList($goods,$goods_id_list);
        return ApiReturn::r(1, ['data'=>$goods,'current_page'=>$data['page'],'last_page'=>ceil($total/$data['size'])], lang('商品版块'));
    }

    /**
     * 推荐系统-获取推荐列表
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @param user_id int 用户ID
     */
    public function recommend_list($data)
    {
        $recommend = addons_action('Recommend/Api/getRecommend', [$data['user_id']]);
        if ($recommend) {
            return ApiReturn::r(1, $recommend, lang('请求成功'));
        } else {
            return ApiReturn::r(0, $recommend, lang('请求失败'));
        }
    }

    /**
     * 权益商品
     * @author jxy [ 415782189@qq.com ]
     * @param $size int 返回数量
     */
    public function goods_send_vip($data)
    {
        $goods = GoodsModel::alias('g')
        ->field("g.id,g.name,u.path as thumb,g.sales_sum,g.shop_price,g.member_price,g.market_price,g.is_shipping,g.is_spec,g.discounts,g.share_award_money,g.empirical,u.id as thumb_id")
        ->where([['g.give_vip_time','gt',0],['g.is_delete','=',0],['g.status','=',1],['g.is_sale','=',1]])
        ->join("upload u", "u.id=g.thumb", "left")
        ->select();
        if ($goods) {
            foreach ($goods as &$v) {
                $v['thumb']=get_file_url($v['thumb_id']);
            }
        }
        $goods = ActivityDetailsModel::findActivityGoodsByList($goods);
        return ApiReturn::r(1, $goods, lang('权益商品'));
    }

    /**
     * 获取任务相关数据
     * @author jxy [ 415782189@qq.com ]
     */
    public function task($data, $user)
    {
        $task = TaskModel::name('user_task')->select();
        foreach ($task as $k=>$v) {
            switch ($v['sign']) {
                case 'bindWx':
                    $is_used=Db::name('user_task_log')->where(['tid'=>$v['id'],'uid'=>$user['id']])->find();
                    break;
                case 'firstSign':
                    $is_used=Db::name('user_signin')->where(['user_id'=>$user['id']])->find();
                    break;
                case 'firstOrder':
                case 'browseGoods':
                case 'shareGoods':
                    $is_used=Db::name('user_task_log')->where(['tid'=>$v['id'],'uid'=>$user['id']])->whereTime('create_time', 'today')->find();
                    break;

            }
            $task[$k]['done']=$is_used?1:0;
        }
        return ApiReturn::r(1, $task, lang('任务信息'));
    }

    /**
    * 小程序直播间信息
    * @author jxy [ 415782189@qq.com ]
    */
    public function getliveinfo($data)
    {
        $arr=["start"=>$data['start'],"limit"=>$data['limit']];
        $res=addons_action('WeChat/MiniPay/getliveinfo', [$arr]);
        if (!$res['errcode']) {
            return ApiReturn::r(1, $res, lang('请求成功'));
        } else {
            return ApiReturn::r(0, [], $res['errmsg']);
        }
    }

    /**
     * 小程序直播间信息
     * @author jxy [ 415782189@qq.com ]
     */
    public function createlive($data)
    {
        try {
            $arr=$data;
            $res=addons_action('WeChat/MiniPay/createlive', [$arr]);
        } catch (InvalidResponseException $e) {
            return ApiReturn::r(0, $e->raw, lang('请求失败'));
        }
        if (!$res['errcode']) {
            return ApiReturn::r(1, $res, lang('请求成功'));
        } else {
            return ApiReturn::r(0, $res, lang('请求失败'));
        }
    }

    /**
     * 获取微信AccessToken
     * @author jxy [ 415782189@qq.com ]
     */
    public function getWxAccessToken()
    {
        $res=addons_action('WeChat/MiniPay/getWxAccessToken', []);
        return ApiReturn::r(1, $res, lang('请求成功'));
    }

    /**
     * 物流公司
     * @author jxy [ 415782189@qq.com ]
     */
    public function get_express_company($data)
    {
        $list=Db::name('goods_express_company')->select();
        return ApiReturn::r(1, ['list'=>$list], lang('请求成功'));
    }

    /**
     *预约取件
     **/
    public function book_packet($data, $user)
    {
        $order_refund=Db::name('order_refund')->get($data['id']);
        $goods = Db::name('goods')->get($order_refund['goods_id']);
        $of = Db::name('goods_express_sender')->get($goods['sender_id']);
        $express_company = Db::name('goods_express_company')->get($data['express_company_id']);
        $eorder["ShipperCode"]=$express_company["express_no"];//物流公司
        $eorder["OrderCode"]=$order_refund['server_no'];
        $eorder["PayType"]=1;
        $sender["Name"]=$data['Name'];  //发件人姓名
        $sender["Mobile"]=$data['Mobile'];  //发件人电话
        $sender["ProvinceName"]=$data['ProvinceName']; //发件人所在省
        $sender["CityName"]=$data['CityName'];  //发件人所在市
        $sender["ExpAreaName"]=$data['ExpAreaName'];  //发件人所在区
        $sender["Address"]=$data['Address'];
        $receiver["Name"]=$of['name'];   //收件人
        $receiver["Mobile"]=$of['phone']; //收件人电话
        $receiver["ProvinceName"]=$of['province']; //收件人省
        $receiver["CityName"]=$of['city']; //收件人市
        $receiver["ExpAreaName"]=$of['area']; //收件人区
        $receiver["Address"]=$of['address']; //收件人地址
        if ($config['is_online']) {
            $result=addons_action('ExpressBird/Api/submitOOrder', [$eorder,$sender,$receiver]);
            $result=json_decode($result, true);
        } else {
            $result['ResultCode']=100;
            $result['Order']['LogisticCode']=rand(1000, 9999).date('Ymd');
        }
        if ($result['ResultCode']==100) {
            Db::name('order_refund')->where(['id'=>$order_refund['id']])->update([
                'express_no'=>$result['Order']['LogisticCode'],
                'express_company_id'=>$express_company['id']
            ]);
            return ApiReturn::r(1, ['info'=>$result], lang('预约成功，请等待来电'));
        } else {
            return ApiReturn::r(0, ['info'=>$result], lang('预约失败'));
        }
    }

    /**
     *关键词搜索提示
     **/
    public function search($data)
    {
        $keyword = trim($data['keyword']) ? trim($data['keyword']) : ''; // 去除字符串空格

        // 拼接查询的条件
        $whereStr = 'g.name LIKE \'%' . preg_replace('# #', '', $keyword) . '%\'';
        $whereStrArr[] = preg_replace('# #', '', $keyword);
        $keywords = preg_split("/[\s,'!,@,#,$,%,^,&,*,(,),_,+,~,`,,,.,\/']+/", $keyword); // 处理特殊字符
        if (count($keywords) > 1) {
            $whereStr .= ' OR ';
            foreach ($keywords as $val) {
                $whereStr .= 'g.name LIKE \'%' . trim($val) . '%\' OR ';
                $whereStrArr[] = trim($val);
            }
            $whereStr = rtrim($whereStr, 'OR ');
        }
        $where[]=['g.status','=',1];
        $where[]=['g.is_sale','=',1];
        $where[]=['g.is_delete','=',0];
        /*if($keyword){
            $cl = new SphinxClient();
            $cl->SetServer('47.92.235.222', 9312);
            $cl->SetConnectTimeout(3);
            $cl->SetArrayResult( true );
            $cl->SetMatchMode(SPH_MATCH_ANY);
            $res = $cl->Query($keyword,"goods");
            foreach($res['matches'] as $v){
                $searchId[]=$v['id'];
            }
            $where[] = ['id', 'in', $searchId];
        }*/

        // 查询商品信息
        $goods_list = Db::name('goods g')
            ->field('g.*,IFNULL(ga.id, 0) activity_id,IFNULL(ga.type, 0) activity_type')
            ->where($where)
            ->where($whereStr)
            ->join('goods_activity_details gad', 'g.id = gad.goods_id AND gad.status = 1', 'LEFT')
            ->join('goods_activity ga', 'ga.id = gad.activity_id', 'LEFT')
            ->group('g.id')
            ->select();
        // 根据关键字重新排列数组顺序
        for ($i=0; $i < count($whereStrArr); $i++) {
            foreach ($goods_list as $k=>$v) {
                if (!in_array($v['id'], $this->notInIds)) {
                    if (strstr($v['name'], $whereStrArr[$i])) {
                        array_push($this->notInIds, $v['id']);
                        // 查询商品sku信息
                        if ($v['is_spec']) {
                            $goods_list[$k]['sku_id']=Db::name('goods_sku')->where(['status'=>1,'goods_id'=>$v['id']])->value('sku_id');
                        } else {
                            $goods_list[$k]['sku_id']=0;
                        }
                        $this->serchInfo[] = $goods_list[$k];
                    }
                }
            }
        }

        return ApiReturn::r(1, ['list'=>$this->serchInfo], lang('请求成功'));
    }

    public function createQrcode($data, $user)
    {
        $user_info = Db::name('user_info')->where(['user_id'=>$user['id']])->find();
        if ($user_info['xcx_qrcode']) {
            return ApiReturn::r(1, ['img'=>$user_info['xcx_qrcode']], lang('请求成功'));
        }
        $stream = addons_action('WeChat/MiniPay/createDefault', [$data['path'].'?code='.$user_info['invite_code']]);
        try {
            $_st=json_decode($stream, true);
            if (is_null($_st)) {
                $data['pictureStream'] = $stream;
                $res = Upload::pictureStream($data, $user);
                if ($res) {
                    Db::name('user_info')->where(['user_id'=>$user['id']])->update([
                        'xcx_qrcode'=>$res,
                    ]);
                    return ApiReturn::r(1, ['img'=>$res], lang('请求成功'));
                } else {
                    return ApiReturn::r(0, ['img'=>''], lang('图片创建失败'));
                }
            }
            return ApiReturn::r(0, ['img'=>'','wx_res'=>$_st], $_st['errmsg']);
        } catch (\InvalidArgumentException $e) {
            $data['pictureStream'] = $stream;
            $res = Upload::pictureStream($data, $user);
            if ($res) {
                return ApiReturn::r(1, ['img'=>$res], lang('请求成功'));
            } else {
                return ApiReturn::r(0, ['img'=>''], lang('图片创建失败'));
            }
        }
    }

    /**
     *获取APP的logo
     **/
    public function getLogo()
    {
        return ApiReturn::r(1, ['logo'=>get_file_url(config('app_logo'))], lang('请求成功'));
    }
    /*
     * 获取app下载信息
     *
     */
    public function appInfo()
    {
        $res=[];
        $admin_version_log=Db::name("admin_version_log")
            ->where([
                'status'=>1
            ])
            ->field("url")
            ->order("create_time desc")
            ->find();
        if ($admin_version_log) {
            $res['down_url']=$admin_version_log['url'];
        }
        $res['host']=config('web_site_domain').'/api/v1/';

        return ApiReturn::r(1, $res, 'ok');
    }


    public function getNearyShop($data = []){
        $lon = $data['lon'];
        $lat = $data['lat'];
        $res = Db::name('nearby_shop')->field('*')->field("get_juli(lon,lat,$lon,$lat) as juli")->where('is_delete',0)->order('juli asc')->paginate()->each(function($query){
            if($query['juli'] >= 1000){
                $query['juli'] = number_format($query['juli']/1000,1,'.','') .'KM';
            }else{
                $query['juli'] = $query['juli'] .'M';
            }
            $query['thumb'] = get_file_url($query['thumb']);
            return $query;
        });
        return ApiReturn::r(1,$res,'获取成功');
    }



    /**
     * Notes: 地址联动
     * User: chenchen
     * Date: 2021/7/30
     * Time: 11:31
     * @param array $data
     * @param array $user
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function address($data = [], $user = [])
    {
        $pid = $data['pid']??0;
        $res = get_level_data('china_area', $pid,'pid');
        return ApiReturn::r(1, $res, 'ok');
    }

    /**
     * 获取跳转小程序的参数
     * @param array $data
     * @return \think\response\Json
     */
    public function getTicket($data = []){
//        $web_url = $data['url'];
        $web_url = 'https://powerful.jishu11.com/index/index/getInformation?data=scanId%3AG6BE93400KCM-20220317-131309%3Bh5%3Ahttps%3A%2F%2Ffeet-model-pangu.oss-cn-beijing.aliyuncs.com%2Fjx%2FG6BE93400KCM_EJ97O54ENQHBQTIVIFJK.jason%3FExpires%3D4769558000%26OSSAccessKeyId%3DLTAI4Fubdv3hGo4pBQ49h7YF%26Signature%3Dr3KqzJDMl8ILTEPXhzp9tBTj9qc%253D&online=1';
        //随机字符串
        $rand_string = Str::randString(16,'');
        $rand_string = strtoupper($rand_string);
        //获取门票
        $access_token = $this->getAccessToken();
//        $access_token_arr = $access_token_object->getData();
//        $access_token = $access_token_arr['data'];
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$access_token&type=jsapi";
        $ticket_string = file_get_contents($url);
        $ticket_arr = json_decode($ticket_string,true);
        $return_data = [];
        $timestamp = time();
        $change_string = 'jsapi_ticket='.$ticket_arr['ticket'].'&noncestr='.$rand_string.'&timestamp='.$timestamp.'&url='.$web_url;
        $signature = sha1($change_string);
        $return_data['timestamp'] = $timestamp;
        $return_data['nonceStr'] = $rand_string;
        $return_data['signature'] = $signature;
        $return_data['jsapi_ticket'] = $ticket_arr['ticket'];
        $return_data['url'] = $web_url;
        return ApiReturn::r(1,$return_data,'获取成功');
    }

    public function getAccessToken(){
        $wechat = addons_config('Wechat');
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wechat['mini_appid'].'&secret='.$wechat['mini_appsecret'];
        $res = json_decode(file_get_contents($url),true);
        return $res['access_token'];
    }

}
