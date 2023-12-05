<?php


namespace app\api\controller;

use app\operation\model\SystemMessage as SystemMessageModel;
use think\Controller;
use think\Exception;
use think\Facade\Config;
use app\goods\model\Goods as GoodsModel;
use app\common\model\Order as OrderModel;
use app\goods\admin\Order;
use think\Db;
use think\Log;

/**
 * 消息通知类
 * Class Notify
 * @package app\api\controller
 */
class Notify extends Controller
{
    // 私有化表名
    private $queue;

    // 自动实例化表
    function __construct()
    {
        $this->queue = \think\Db::name('queue');
    }

    /**
     * 实时消息异步通知类
     * @author zenghu<1427305236@qq.com>
     * @since 2020年8月1日17:57:40
     * @return \think\response\Json
     */
    public function inicrontab()
    {
        // 发送秒杀通知
        $this->iniSecondKill();
        // 拼团机器人
        $this->groupRobot();
    }

    /**
     * 发送秒杀通知
     * @author zenghu<1427305236@qq.com>
     * @since 2020年8月1日17:57:40
     * @return \think\response\Json
     */
    private function iniSecondKill()
    {
        // 读取需要跑批的数据 每次读取100条
        $data = date('H:i');
        $queueDate = $this->queue->where("q_implement_time <= '{$data}' AND q_type IN (1,2)")->limit(100)->select();
        if (empty($queueDate)) {
            \think\facade\Log::instance()->write("api/Notify/iniSecondKill数据为空不用执行" . Db::getlastsql(), "error");
            return '数据为空不用执行';
            die;
        }
        // 处理需要跑批的数据
        foreach ($queueDate as $val) {
            $this->queue->where(['q_id' => $val['q_id']])->delete();
            $extent = json_decode($val['q_extent'], true);
            $this->sendNotifySecondKill(['userId' => $val['q_user_id'], 'goodsId' => $val['q_goods_id'], 'pageUrl' => $extent['page_url'], 'type' => $val['q_type']]);


        }
    }

    /**
     * 发送秒杀通知
     * @param $requests .userId int 用户ID [必须]
     * @param $requests .goodsId int 秒杀活动商品ID [必须]
     * @param $requests .pageUrl string 秒杀活动跳转的路径 [非必须]
     * @author zenghu<1427305236@qq.com>
     * @since 2020年8月1日17:57:40
     * @return Arr
     */
    private function sendNotifySecondKill($requests = [])
    {
        // 检测参数值
        if (empty($requests['userId']) || empty($requests['goodsId'])) {
            \think\facade\Log::instance()->write("api/Notify/sendNotifySecondKill PARAMS userId OR goodsId NOT NULL", "error");
            return ['status' => '5000', 'msg' => 'PARAMS userId OR goodsId NOT NULL'];
        }

        // 查询用户OPENID
        $openid = Db::name('user_info')->where(['user_id' => $requests['userId']])->value('xcx_openid');

        // 获取跳转小程序类型 开发版或正式版
        $miniprogramState = (config('app_type') == 'release') ? 'formal' : 'trial';

        // 跳转路径
        $pageUrl = empty($requests['pageUrl']) ? '' : $requests['pageUrl'];

        // 查询商品秒杀活动信息
        $goodsInfo = GoodsModel::alias('g')
            ->field('ga.name ganame,ga.sdate,gad.name gadname')
            ->where(['g.status' => 1, 'ga.type' => 1, 'ga.status' => 1, 'gad.status' => 1, 'g.id' => $requests['goodsId']])
            ->join('goods_activity_details gad', 'g.id = gad.goods_id', 'LEFT')
            ->join('goods_activity ga', 'ga.id = gad.activity_id', 'LEFT')
            ->find();
        // || empty($goodsInfo['sdate'])
        if (empty($goodsInfo) || empty($goodsInfo['ganame']) || empty($goodsInfo['gadname'])) {
            \think\facade\Log::instance()->write("api/Notify/sendNotifySecondKill查询商品秒杀活动信息为空:" . Db::getlastsql(), "error");
            return ['status' => '5000', 'msg' => 'SEND ERROR,THIS GOODS NOT ACTIVION'];
        }

        // 处理特殊字符
        $gadname = mb_substr($this->replaceSpecialChar($goodsInfo['gadname']), 0, 19, 'utf-8');
        $ganame = mb_substr($this->replaceSpecialChar($goodsInfo['ganame']), 0, 19, 'utf-8');


        // 发送订阅消息通知
        try {

            if ($requests['type'] == 2) { // UniPush 推送
                $data = [
                    'to_user_id' => $requests['userId'],
                    'title' => $gadname,
                    'content' => '活动即将开始,请前往查看',
                    'custom' => [
                        'name' => $goodsInfo['ganame'],
                        'goods_id' => $requests['goodsId'],
                    ],
                ];
                $msg = new SystemMessageModel();
                $msg->insert($data);
                $msg->sendMsg($msg);
            } elseif ($requests['type'] == 1) {
                $data = [
                    'touser' => $openid, // 接收者openid
                    'template_id' => 'x3ljpUAnxYgi-u6iQcaRuP-Z3fVoJ5o22KTDqiNhPMk', // 所需下发的订阅模板id
                    'page' => $pageUrl, // 点击模板卡片后的跳转页面,不填无跳转
                    'data' => [
                        'thing6' => ['value' => $gadname], // 活动内容
                        'thing11' => ['value' => '活动即将开始,请前往查看'], // 温馨提示
                        'thing12' => ['value' => $ganame], // 活动名称
                        'date7' => ['value' => date('Y-m-d H:00:00', strtotime("+1 hour"))], // 开始时间 提示时间前置了3分钟
                    ],
                    'miniprogram_state' => $miniprogramState, // 获取跳转小程序类型 开发版或正式版
                ];
                addons_action('WeChat', 'MiniPay', 'subscribe', [$data]);
            } else {
                throw new Exception("传输类型错误！");
            }
            return ['status' => '2000', 'msg' => 'SEND SUCCESS'];
        } catch (\Exception $e) {
            \think\facade\Log::instance()->write("api/Notify/sendNotifySecondKill发送秒杀通知:" . $e->getMessage(), "error");
            return ['status' => '5000', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 过滤特殊字符戳
     * @param $strParam string 含有特殊字符的戳
     * @author zenghu<1427305236@qq.com>
     * @since 2020年8月27日14:10:56
     * @return string 不含特殊字符
     */
    private function replaceSpecialChar($strParam)
    {
        $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";

        return preg_replace($regex, "", $strParam);
    }

    public function sendMsg(SystemMessage $message, $to_user_id = null)
    {

        $extra = $message["custom"];
        if (is_array($json = json_decode($message["custom"], true))) {
            $extra = $json;
        }


        $title = $message["title"];
        $content = $message["content"];
        $custom = json_encode([
            "id" => $message["id"],
            "action" => $message["action"],
            "msg_type" => $message["msg_type"],
            "extra" => $extra
        ]);
        $logo = "";
        $logoUrl = "";
        $sound = "default";
        $badge = -1;

        if (null == $to_user_id) {
            $to_user_id = $message['to_user_id'];
        }
        $to_user_id = explode(",", $to_user_id);
        if (count($to_user_id) <= 1) {
            $to_user_id = $to_user_id[0];
        }
        $type = is_array($to_user_id) ? 2 : ($to_user_id ? 1 : 3);

        switch ($type) {
            case 1:
                //单推
                $client_id = \think\Db::name('user')->where('id', $to_user_id)->value('client_id');
                if (!$client_id) {
                    $this->error = '获取指定用户的client_id失败，无法发送';
                    return false;
                }

                $res = addons_action('Getui/Getui/pushMessageToSingle', [$title, $content, $client_id, $custom, $logo, $logoUrl, $sound, $badge]);
                break;
            case 2:
                $client_id = \think\Db::name('user')->where('id', 'in', $to_user_id)->column('client_id');
                if (!$client_id) {
                    $this->error = '获取指定用户的client_id失败，无法发送';
                    return false;
                }
                //多推
                $res = addons_action('Getui/Getui/pushMessageToList', [$title, $content, $client_id, $custom, $logo, $logoUrl, $sound, $badge]);
                break;
            case 3:
                //群推
                $res = addons_action('Getui/Getui/pushMessageToApp', [$title, $content, $custom, $logo, $logoUrl, $sound, $badge]);
                break;
        }

        if ($res['result'] == 'ok') {
            return true;
        }
        $this->error = $res['result'];
        return false;
    }

    public function groupRobot()
    {
        $activityGroupUserList = Db::name('goods_activity_group_user')
            ->alias('gcgu')
            ->leftJoin('goods_activity_group gcg', 'gcg.id = gcgu.group_id')
            ->leftJoin('goods_activity ga', 'gcg.activity_id = ga.id')
            ->where('gcgu.status', '=', 1)
            ->where('gcg.is_full', '=', 0)
            ->where('ga.status', '=', 1)
            ->where('ga.type', '=', 2)
            ->where('ga.edate', '>', strtotime(date('Y-m-d')))
            ->select('ga.edate, gcg.id');
        foreach ($activityGroupUserList as $item) {
            if (time() - $item['edate'] < 60) {
                (new Order())->add_robot_to_group($item['id']);
            }
        }
    }
}
