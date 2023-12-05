<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */

//declare(ticks=1);
use \GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;
use Util\Tools;
use Util\ApiList;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 新建一个类的静态成员，用来保存数据库实例
     */
    public static $db = null;
    public static $prefix = 'lb_';
    public static $global = null;

    public static $maxNumber = 4; // 最大接待人数

    /**
     * 当businessWorker进程启动时触发。每个进程生命周期内都只会触发一次
     */
    public static function onWorkerStart($worker)
    {
        // 初始化mysql
        if (empty(self::$db)) {
            self::$db = new \Workerman\MySQL\Connection('127.0.0.1', '3306', 'zb_mkh', 'jzeTMcLYxY8yshRb', 'zb_mkh');
        }

        // 初始化global
        if (empty(self::$global)) {
            self::$global = new \GlobalData\Client('127.0.0.1:32320');
            // Tools::printWriteLog('实例化global后参数', self::$global);

            // 客服列表
            if (is_null(self::$global->kfList)) {
                self::$global->kfList = [];
            }
            // 会员列表[动态的，这里面只是目前未被分配的会员信息]
            if (is_null(self::$global->userList)) {
                self::$global->userList = [];
            }
            // 会员以 uid 为key的信息简表,只有在用户退出的时候，才去执行修改
            if (is_null(self::$global->uidSimpleList)) {
                self::$global->uidSimpleList = [];
            }

            // 当天的累积接入值
            $key = date('Ymd') . 'total_in';
            if (is_null(self::$global->$key)) {
                self::$global->$key = 0;
                $oldKey = date('Ymd', strtotime('-1 day')); // 删除前一天的统计值
                unset(self::$global->$oldKey);
                unset($oldKey, $key);
            }
            // 成功接入值
            $key = date('Ymd') . 'success_in';
            if (is_null(self::$global->$key)) {
                self::$global->$key = 0;
                $oldKey = date('Ymd', strtotime('-1 day')); // 删除前一天的统计值
                unset(self::$global->$oldKey);
                unset($oldKey, $key);
            }
        }

        // 定时统计数据， 一个worker实例有4个进程，只在id编号为0的进程上设置定时器，只在id编号为0的进程上设置定时器，其它1、2、3号进程不设置定时器
        if (0 === $worker->id) {
            // 1分钟统计一次实时数据
            // Timer::add(60 * 1, function(){
            //     self::writeLog(1);
            // });
            //40分钟写一次当前日期点数的log数据
            Timer::add(60 * 40, function(){
                self::writeLog(2);
            });
        }
    }

    /**
     * 当客户端连接上gateway进程时(TCP三次握手完毕时)触发的回调函数。
     * 如果业务不需此回调可以删除onConnect
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {

    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        $message = json_decode($message, true);
        if ($message['type'] != 'ping') {
            Tools::printWriteLog('初始化消息', $message);
        }
        

        // 根据消息类型处理不同的业务
        switch ($message['type']) {
            case 'init': // 客服初始化
                // 模拟参数
                // Array (
                //     [type] => init
                //     [partner_id] => 0
                //     [service_number] => 2
                //     [uid] => KF2 // 此处UID为平台标识+KF+ID 此处修改应注意聊天入库信息修改及服务注册信息修改
                //     [name] => zenghu
                //     [avatar] => http://zbphp.zhongbenzx.com/static/admin/images/none.png
                //     [group_id] => 1
                //     [platform] => 'bentengjia' // 不允许有‘_’符号的存在，例如：bentengjia_01,不允许
                // )

                // 初始化客服列表
                self::customerServiceInitialize($client_id, $message);
                //手动清理客服列表
                if (0) {
                    $old = self::$global->kfList;
                    $kfList = [];
                    // 刷新内存中客服的服务列表
                    while (!self::$global->cas('kfList', $old, $kfList)) {
                    };
                    unset($old, $kfList);
                }

                Tools::printWriteLog('初始化后客服列表', self::$global->kfList);

                // 客服ID绑定客服服务client_id
                Gateway::bindUid($client_id, $message['uid']);

                // 记录客服服务信息
                Gateway::setSession($client_id, ['uid' => $message['uid']]);
                // 拉取用户来服务 
                self::pullUserTask($message['partner_id'], $message['group_id']);
                break;

            case 'userInit'; // 客户初始化
                // 模拟参数
                // Array(
                //     [type] => userInit
                //     [partner_id] => 0
                //     [uid] => 6
                //     [name] => 孙悟空
                //     [avatar] => http://b2c.zhongbenzx.com/uploads/images/f7/4c76af601e630bb465de8957d368f5.png
                //     [group] => 1
                //     [platform] => 'bentengjia' // 不允许有‘_’符号的存在，例如：bentengjia_01,不允许
                // )

                // 初始化客户列表

                self::usersInitialize($client_id, $message);
//                Tools::printWriteLog('客户重新赋值初始化', self::$global->userList);

                // 增加服务接入值
                self::accessIncrement('total_in');

                // 客户ID绑定客户服务client_id
                Gateway::bindUid($client_id, $message['uid']);

                // 给客户分配客服服务
                $platformPartnerId = (isset($message['platform']) && !empty($message['platform'])) ? ($message['platform'] . '_' . $message['partner_id']) : $message['partner_id'];
                self::userOnlineTask($client_id, $message['group'], $platformPartnerId);

                break;

            case 'user'; // 用户初始化（服务于用户和用户聊天使用）
                // 服务注册模拟参数
                // $message = array(
                //     'type' => 'user',
                //     'uid' => '1',
                //     'uname' => 'emmjmmmm',
                //     'uavatar' => '********************',
                //     'to_uid' => '2',
                //     'to_uname' => 'zenghu',
                //     'to_uavatar' => 'http://wwww.33333.com/2333333333.png',
                // );

                // 绑定用户ID
                Gateway::bindUid($client_id, $message['uid']);
                Gateway::setSession($client_id, ['uid' => $message['uid']]);

                // 用户服务注册服务
                self::insertUserServiceRegister($client_id, $message);

                break;

            case 'chatMessage': // 聊天消息
            case 'link': // 发送商品链接
                if (!empty($message) && !empty($message['data']['to_id'])) {
                    $client = Gateway::getClientIdByUid($message['data']['to_id']);
                    // 调试模式--输出消息
                    Tools::printWriteLog('获取客服列表信息', self::$global->kfList);
                    Tools::printWriteLog('获取用户列表信息', self::$global->userList);
                    Tools::printWriteLog('获取绑定to_id的客服信息', $client);
                }
                // 获取聊天业务参数业务类型（1用户客服业务中 2用户对用户业务中）
                $businessType = (isset($message['data']['business_type']) && !empty($message['data']['business_type']) && in_array($message['data']['business_type'], [1, 2])) ? $message['data']['business_type'] : 1;

                // 用户对用户--检测聊天双方是否建立过链接
                if ($businessType == 2) {
                    self::insertUserServiceRegister('', [
                        'uid' => $message['data']['to_id'],
                        'uname' => $message['data']['to_name'],
                        'uavatar' => $message['data']['to_avatar'],
                        'to_uid' => $message['data']['from_id'],
                        'to_uname' => $message['data']['from_name'],
                        'to_uavatar' => $message['data']['from_avatar'],
                    ]);
                }
//                Tools::printWriteLog('sendMessageChat', $message);

                // 客服在线发送消息
                if (!empty($client)) {
                    $clientCount = count($client) - 1;
                    // 客服在线发送消息
                    self::sendMessageChat($client[$clientCount], [
                        'from_id' => $message['data']['from_id'],
                        'from_name' => $message['data']['from_name'],
                        'from_avatar' => $message['data']['from_avatar'],
                        'type' => $message['data']['type'],
                        'content' => $message['data']['content'],
                        'to_id' => $message['data']['to_id'],
                        'to_name' => $message['data']['to_name'],
                        'business_type' => $businessType,
//                        'timestamp' => $message['data']['timestamp'],
                        'partner_id' => $message['data']['partner_id'],

                    ]);
                } else {
                    if ($businessType == 1) { // 客服和用户聊天相关业务--离线
                        // 离线模式存储消息-待符合条件的客服上线第一时间转发消息给客服
                        self::$db->insert(self::$prefix . 'operation_service_offline_chat')->cols([
                            'from_id' => $message['data']['from_id'],
                            'type' => is_numeric($message['data']['from_id']) ? 1 : 2,
                            'message_content' => json_encode($message)
                        ])->query();
                    } else if ($businessType == 2) { // 用户对用户离线模式下发送消息
                        // 记录聊天记录
                        ApiList::messageChatInsert(self::$db, [
                            'from_id' => $message['data']['from_id'],
                            'from_name' => $message['data']['from_name'],
                            'from_avatar' => $message['data']['from_avatar'],
                            'to_id' => $message['data']['to_id'],
                            'to_name' => $message['data']['to_name'],
                            'content' => is_array($message['data']['content']) ? json_encode($message['data']['content']) : $message['data']['content'],
                            'type' => $message['data']['business_type'],
                            'message_type' => $message['data']['type'],
                        ]);
                    } else {
                        // TO DO LIST
                    }
                }
                break;

            case 'changeGroup': // 转接--暂未使用 2021年2月3日14:50:41
                // 通知客户端转接中
                $simpleList = self::$global->uidSimpleList;

                if (!isset($simpleList[$message['partner_id']][$message['uid']])) { // 客户已经退出
                    return;
                }

                $userClient = $simpleList[$message['partner_id']][$message['uid']]['0'];
                $userGroup = $simpleList[$message['partner_id']][$message['uid']]['1'];  // 会员原来的分组也是客服的分组

                $reLink = [
                    'message_type' => 'relinkMessage'
                ];
                Gateway::sendToClient($userClient, json_encode($reLink));
                unset($reLink);

                // 记录该客服与该会员的服务结束
                self::$db->query("update `" . self::$prefix . "operation_service_log` set `end_time` = " . time() . " where `client_id`= '" . $userClient . "'");

                // 从当前客服的服务表中删除这个会员
                $old = $kfList = self::$global->kfList;
                if (!isset($kfList[$message['partner_id']][$userGroup])) {
                    $waitMsg = '客服不在线,请稍后再咨询1。';
                    // 逐一通知
                    foreach (self::$global->userList[$message['partner_id']] as $vo) {
                        $waitMessage = [
                            'message_type' => 'wait',
                            'data' => [
                                'content' => $waitMsg,
                                'code' => 0,
                            ]
                        ];
                        Gateway::sendToClient($userClient, json_encode($waitMessage));
                        unset($waitMessage);
                    }
                    return;
                }
                $myList = $kfList[$message['partner_id']][$userGroup]; // 该客服分组数组
                foreach ($myList as $key => $vo) {
                    if (in_array($userClient, $vo['user_info'])) {
                        // 维护现在的该客服的服务信息
                        $kfList[$message['partner_id']][$userGroup][$key]['task'] -= 1; // 当前服务的人数 -1
                        foreach ($vo['user_info'] as $k => $v) {
                            if ($userClient == $v) {
                                unset($kfList[$message['partner_id']][$userGroup][$key]['user_info'][$k]);
                                break;
                            }
                        }
                        break;
                    }
                }
                while (!self::$global->cas('kfList', $old, $kfList)) {
                }; // 刷新内存中客服的服务列表
                unset($old, $kfList, $myList);

                // 将会员加入队列中
                $userList = self::$global->userList;
                do {
                    $NewUserList = $userList;
                    $NewUserList[$message['partner_id']][$message['uid']] = [
                        'id' => $message['uid'],
                        'name' => $message['name'],
                        'avatar' => $message['avatar'],
                        'ip' => $message['ip'],
                        'group' => $message['group'], // 指定要链接的分组
                        'client_id' => $userClient
                    ];

                } while (!self::$global->cas('userList', $userList, $NewUserList));
                unset($NewUserList, $userList);

                // 执行会员分配通知双方
                self::userOnlineTask($userClient, $message['group'], $message['partner_id']);
                unset($userClient, $userGroup);
                break;

            case 'closeUser': // 客户断线
                $userInfo = self::$global->uidSimpleList;
                if (isset($userInfo[$message['partner_id']][$message['uid']])) {
                    $waitMessage = [
                        'message_type' => 'wait',
                        'data' => [
                            'content' => '客服不在线,请稍后再咨询2。',
                            'code' => 0
                        ]
                    ];
                    Gateway::sendToClient($userInfo[$message['partner_id']][$message['uid']]['0'], json_encode($waitMessage));
                    unset($waitMessage);
                }
                unset($userInfo);
                break;

            case 'consultingService': // 服务咨询
                // 获取客户clientId
                $client = Gateway::getClientIdByUid($message['data']['from_id']);
                // 调试模式--输出消息
//                Tools::printWriteLog('获取客户信息', $client);

                // 客服在线发送消息
                if (!empty($client)) {
                    // 获取问题答案
                    $consultingServiceChat = [
                        'message_type' => 'consultingService',
                        'data' => [
                            'content' => htmlspecialchars($message['data']['answer']),
                        ]
                    ];
                    $clientCount = count($client) - 1;
                    Gateway::sendToClient($client[$clientCount], json_encode($consultingServiceChat));
                    unset($consultingServiceChat);
                }
                break;

            case 'serviceApi': // 拉取数据接口
                // 模拟参数
                // $message = [
                //     'type' => 'serviceApi',
                //     'serviceApiName' => 'getChatUsersList',
                //     'unLogin' => 'unLogin', // 未登录状态下接口请求标识（注意：仅在不需要登录状态下请求的接口才有此参数）
                //     'requestsParams' => [
                //         'page' => 1,
                //         'size' => 10,
                //     ],
                // ];
                $message = json_encode(self::sentry($client_id, $message));
                Tools::printWriteLog("serviceApi", $message);

                // 发送消息
                Gateway::sendToClient($client_id, $message);

                break;
        }
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     * tips: 当服务端主动退出的时候，会出现 exit status 9.原因是：服务端主动断开之后，连接的客户端会走这个方法，而短时间内进程
     * 需要处理这多的逻辑，又有cas操作，导致进程退出会超时，然后会被内核杀死，从而报出错误 9.实际对真正的业务没有任何的影响。
     */
    public static function onClose($client_id)
    {
        // 服务退出标识
        $isServiceUserOut = false;
        $partnerId = 0; // 定义商户ID
        //Tools::printWriteLog('客户或客服退出输出消息', $client_id);
        // 获取clientId的商户信息
        $clientInfo = ApiList::getClientInfo(self::$db, $client_id);
        if (!empty($clientInfo)) {
            $partnerId = $clientInfo['partner_id'];
        }
        // 调试模式--输出消息
        //Tools::printWriteLog('客户或客服退出输出消息', $clientInfo);

        // 将会员服务信息，从客服的服务列表中移除
        $old = $kfList = self::$global->kfList;
        // 调试模式--输出消息
        //Tools::printWriteLog('退出时客服列表', $kfList);

        // 客服退出
        if (!empty($kfList) && !empty($kfList[$partnerId])) {
            foreach ($kfList[$partnerId] as $k => $v) { // 商户
                foreach ($v as $key => $vo) { // 分组
                    if (in_array($client_id, $vo['user_info'])) {
                        $isServiceUserOut = true;

                        // 根据client id 去更新这个会员离线的一些信息
                        self::$db->query("update `" . self::$prefix . "operation_service_log` set `end_time` = " . time() . " where `client_id`= '" . $client_id . "'");

                        // 从会员的内存表中检索出该会员的信息，并更新内存
                        $oldSimple = $simpleList = self::$global->uidSimpleList;
                        $outUser = [];
                        foreach ($simpleList[$partnerId] as $u => $c) {
                            if ($c['0'] == $client_id) {
                                $outUser[] = [
                                    'id' => $u,
                                    'group_id' => $c['1']
                                ];
                                unset($simpleList[$partnerId][$u]);
                                break;
                            }
                        }
                        while (!self::$global->cas('uidSimpleList', $oldSimple, $simpleList)) {
                        };
                        unset($oldSimple, $simpleList);

                        // 通知 客服删除退出的用户
                        if (!empty($outUser)) {
                            $del_message = [
                                'message_type' => 'delUser',
                                'data' => [
                                    'id' => $clientInfo['user_id']
                                ]
                            ];
                            Gateway::sendToClient($vo['client_id'], json_encode($del_message));
                            unset($del_message);

                            // 尝试分配新会员进入服务
                            self::userOfflineTask($clientInfo['group_id'], $partnerId);
                        }
                        unset($outUser);

                        // 维护现在的该客服的服务信息
                        $kfList[$partnerId][$k][$key]['task'] -= 1; // 当前服务的人数 -1
                        foreach ($vo['user_info'] as $m => $l) {
                            if ($client_id == $l) {
                                unset($kfList[$partnerId][$k][$key]['user_info'][$m]);
                                break;
                            }
                        }

                        // 刷新内存中客服的服务列表
                        while (!self::$global->cas('kfList', $old, $kfList)) {
                        };
                        unset($old, $kfList);
                        break;
                    }
                }

                if ($isServiceUserOut) break;
            }
        }

        // 尝试从排队的用户中删除退出的客户端
        if (false == $isServiceUserOut) {
            $old = $userList = self::$global->userList;
            if (!empty($userList) && !empty($userList[$partnerId])) {
                foreach (self::$global->userList[$partnerId] as $key => $vo) {
                    if ($client_id == $vo['client_id']) {
                        $isServiceUserOut = true;
                        unset($userList[$partnerId][$key]);
                        break;
                    }
                }
            }
            while (!self::$global->cas('userList', $old, $userList)) {
            };

            // 从会员的内存表中检索出该会员的信息，并更新内存
            $oldSimple = $simpleList = self::$global->uidSimpleList;
            if (!empty($simpleList) && !empty($simpleList[$partnerId])) {
                foreach ($simpleList[$partnerId] as $u => $c) {
                    if ($c['0'] == $client_id) {
                        unset($simpleList[$partnerId][$u]);
                        break;
                    }
                }
            }
            while (!self::$global->cas('uidSimpleList', $oldSimple, $simpleList)) {
            };
            unset($oldSimple, $simpleList);
        }

        // 尝试是否是客服退出
        if (false == $isServiceUserOut) {
            $old = $kfList = self::$global->kfList;
            if (!empty($kfList) && !empty($kfList[$partnerId])) {
                foreach (self::$global->kfList[$partnerId] as $k => $v) {
                    foreach ($v as $key => $vo) {
                        // 客服服务列表中无数据，才去删除客服内存信息
                        if ($client_id == $vo['client_id'] && (0 == count($vo['user_info']))) {
                            unset($kfList[$partnerId][$k][$key]);
                            break;
                        }
                    }
                }
            }
            while (!self::$global->cas('kfList', $old, $kfList)) {
            };
        }

        Tools::printWriteLog('退出后客服列表', self::$global->kfList);
    }

    /**
     * 客服上线-初始化客服
     * @param $kfClientId string 客服服务客户端ID
     * @param $kfMessage arr 客服传输请求消息
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月5日11:45:00
     */
    private static function customerServiceInitialize($kfClientId = 0, $kfMessage = [])
    {
        // 获取客服列表
        $kfList = self::$global->kfList;
        Tools::printWriteLog('客服上线-初始化客服列表', $kfList);

        // 处理平台标识  需满足单机多项目服务多开的情况  2021年2月3日11:28:15
        $platformPartnerId = $kfMessage['partner_id'];
        if (isset($kfMessage['platform']) && !empty($kfMessage['platform'])) {
            $platformPartnerId = $kfMessage['platform'] . '_' . $kfMessage['partner_id'];
        }

        // 如果该客服未在内存中记录则记录
        if (!isset($kfList[$platformPartnerId][$kfMessage['group_id']]) || !array_key_exists($kfMessage['uid'], $kfList[$platformPartnerId][$kfMessage['group_id']])) {
            do {
                $newKfList = $kfList;
                $newKfList[$platformPartnerId][$kfMessage['group_id']][$kfMessage['uid']] = [
                    'id' => $kfMessage['uid'],
                    'name' => $kfMessage['name'],
                    'avatar' => $kfMessage['avatar'],
                    'service_number' => $kfMessage['service_number'],
                    'client_id' => $kfClientId,
                    'task' => 0,
                    'user_info' => []
                ];
            } while (!self::$global->cas('kfList', $kfList, $newKfList));
            unset($newKfList, $kfList);
        } else if (isset($kfList[$platformPartnerId][$kfMessage['group_id']][$kfMessage['uid']])) {
            do {
                $newKfList = $kfList;
                $newKfList[$platformPartnerId][$kfMessage['group_id']][$kfMessage['uid']]['client_id'] = $kfClientId;
                $newKfList[$platformPartnerId][$kfMessage['group_id']][$kfMessage['uid']]['service_number'] = $kfMessage['service_number'];
            } while (!self::$global->cas('kfList', $kfList, $newKfList));
            unset($newKfList, $kfList);
        }
    }

    /**
     * 客户上线-初始化客户
     * @param $khClientId string 客户服务客户端ID
     * @param $khMessage arr 客服传输请求消息
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月5日14:00:08
     */
    private static function usersInitialize($khClientId = 0, $khMessage = [])
    {
        // 获取客户列表
        $userList = self::$global->userList;
        Tools::printWriteLog('客户上线-初始化客户', $userList);

        // 处理平台标识  需满足单机多项目服务多开的情况  2021年2月3日13:42:58
        if (is_numeric($khMessage['partner_id'])) {
            $khMessage['partner_id'] = 0;
        }
        $platformUserId = $khMessage['partner_id'];
        if (isset($khMessage['platform']) && !empty($khMessage['platform'])) {
            $platformUserId = $khMessage['platform'] . '_' . $khMessage['partner_id'];
        }

        // 如果该顾客未在内存中记录则记录
//        Tools::printWriteLog('userList', $userList);

        if ($khMessage['uid'] && is_array($userList)) {
            if (!isset($userList[$platformUserId]) || !array_key_exists($khMessage['uid'], $userList[$platformUserId])) {
                do {
                    $NewUserList = $userList;
                    $NewUserList[$platformUserId][$khMessage['uid']] = [
                        'id' => $khMessage['uid'],
                        'name' => $khMessage['name'],
                        'avatar' => $khMessage['avatar'],
                        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
                        'group' => $khMessage['group'],
                        'client_id' => $khClientId
                    ];

                } while (!self::$global->cas('userList', $userList, $NewUserList));
                unset($NewUserList, $userList);

                // 维护 UID对应的client_id 数组
                do {
                    $old = $newList = self::$global->uidSimpleList;
                    $newList[$platformUserId][$khMessage['uid']] = [
                        $khClientId,
                        $khMessage['group'],
                        $khMessage['partner_id']
                    ];
                } while (!self::$global->cas('uidSimpleList', $old, $newList));
                unset($old, $newList);
            }
            Tools::printWriteLog('客户上线-初始化客户', self::$global->userList);
        }

    }

    /**
     * 增加服务接入值
     * @params $field string 键名
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月5日14:07:08
     */
    private static function accessIncrement($field = 'total_in')
    {
        $key = date('Ymd') . $field;
        self::$global->$key = 0;
        do {
            $oldKey = date('Ymd', strtotime('-1 day'));
            unset(self::$global->$oldKey, $oldKey);
        } while (!self::$global->increment($key));
        unset($key);
    }

    /**
     * 有人退出
     * @param $group
     * @param $partnerId 商户ID
     */
    private static function userOfflineTask($group = 0, $partnerId = 0)
    {
        // 重新分配客服和客户关系
        $res = self::assignmentTask($group, $partnerId);
        if (1 == $res['code']) {
            while (!self::$global->cas('kfList', self::$global->kfList, $res['data']['4'])) {
            }; // 更新客服数据
            while (!self::$global->cas('userList', self::$global->userList, $res['data']['5'])) {
            }; // 更新会员数据

            // 通知会员发送信息绑定客服的id
            $noticeUser = [
                'message_type' => 'connect',
                'data' => [
                    'kf_id' => $res['data']['0'],
                    'kf_name' => $res['data']['1']
                ]
            ];
            Gateway::sendToClient($res['data']['3']['client_id'], json_encode($noticeUser));
            unset($noticeUser);

            // 通知客服端绑定会员的信息
            $noticeKf = [
                'message_type' => 'connect',
                'data' => [
                    'user_info' => $res['data']['3']
                ]
            ];
            Gateway::sendToClient($res['data']['2'], json_encode($noticeKf));
            unset($noticeKf);

            // 逐一通知
            $number = 1;
            foreach (self::$global->userList[$partnerId] as $vo) {
                $waitMsg = '您前面还有 ' . $number . ' 位会员在等待。';
                $waitMessage = [
                    'message_type' => 'wait',
                    'data' => [
                        'content' => $waitMsg,
                        'code' => 1
                    ]
                ];
                Gateway::sendToClient($vo['client_id'], json_encode($waitMessage));
                $number++;
            }
            unset($waitMessage, $number);

            // 写入服务接入值
            self::accessIncrement('success_in');
        } else {
            switch ($res['code']) {
                case -1:
                    $waitMsg = '客服不在线,请稍后再咨询3。';
                    // 逐一通知
                    foreach (self::$global->userList[$partnerId] as $vo) {
                        $waitMessage = [
                            'message_type' => 'wait',
                            'data' => [
                                'content' => $waitMsg,
                                'code' => 0
                            ]
                        ];
                        Gateway::sendToClient($vo['client_id'], json_encode($waitMessage));
                    }
                    break;
                case -2:
                    break;
                case -3:
                    break;
                case -4:
                    // 逐一通知
                    $number = 1;
                    foreach (self::$global->userList[$partnerId] as $vo) {

                        $waitMsg = '您前面还有 ' . $number . ' 位会员在等待。';
                        $waitMessage = [
                            'message_type' => 'wait',
                            'data' => [
                                'content' => $waitMsg,
                                'code' => 1
                            ]
                        ];
                        Gateway::sendToClient($vo['client_id'], json_encode($waitMessage));
                        $number++;
                    }
                    break;
            }
            unset($waitMessage, $number);
        }
    }

    /**
     * 有人进入执行分配
     * @param $client_id
     * @param $group
     * @param $partnerId int 商户ID
     */
    private static function userOnlineTask($client_id = 0, $group = 0, $partnerId = 0, $userInfo = [])
    {
        $userClientId = (!empty($userInfo['client_id']) && isset($userInfo['client_id'])) ? $userInfo['client_id'] : '';
        $res = self::assignmentTask($group, $partnerId, $userClientId);
        Tools::printWriteLog('客服分配客户消息', $res);
        if (1 == $res['code']) {
            while (!self::$global->cas('kfList', self::$global->kfList, $res['data']['4'])) {
            }; // 更新客服数据
            while (!self::$global->cas('userList', self::$global->userList, $res['data']['5'])) {
            }; // 更新会员数据

            // 通知会员发送信息绑定客服的id
            $noticeUser = [
                'message_type' => 'connect',
                'data' => [
                    'kf_id' => $res['data']['0'],
                    'kf_name' => $res['data']['1']
                ]
            ];
            Gateway::sendToClient($client_id, json_encode($noticeUser));
            unset($noticeUser);

            // 转换partnerId
            $partnerId = is_numeric($partnerId) ? $partnerId : (substr($partnerId, strripos($partnerId, "_") + 1));

            // 获取开场白消息
            if (empty($userClientId)) {
                $sayHello = ApiList::getMessageList(self::$db, 1, $partnerId);
//                Tools::printWriteLog('开场白消息', $sayHello);
                if (!empty($sayHello)) {
                    $hello = [
                        'message_type' => 'helloMessage',
                        'data' => [
                            'name' => $res['data']['1'],
                            'avatar' => ApiList::getAvatar(self::$db, $partnerId, intval(ltrim($res['data']['0'], 'KF'))),
                            'id' => $res['data']['0'],
                            'time' => date('H:i'),
                            'content' => htmlspecialchars($sayHello['answer'])
                        ]
                    ];
                    Gateway::sendToClient($client_id, json_encode($hello));
                    unset($hello);
                }
                unset($sayHello);
            }

            // 通知客服端绑定会员的信息
            $noticeKf = [
                'message_type' => 'connect',
                'data' => [
                    'user_info' => empty($userInfo) ? $res['data']['3'] : $userInfo,
                ]
            ];
            Gateway::sendToClient($res['data']['2'], json_encode($noticeKf));
            unset($noticeKf);

            // 服务信息入库
            if (empty($userInfo)) {
                ApiList::serviceLog(self::$db, [
                    'user_id' => intval(ltrim($res['data']['3']['id'], 'PKF')),
                    'client_id' => $res['data']['3']['client_id'],
                    'user_name' => $res['data']['3']['name'],
                    'user_ip' => $res['data']['3']['ip'],
                    'user_avatar' => $res['data']['3']['avatar'],
                    'kf_id' => intval(ltrim($res['data']['0'], 'KF')),
                    'create_time' => time(),
                    'group_id' => $group,
                    'partner_id' => $partnerId,
                    'end_time' => 0,
                ]);
            } else {
                ApiList::serviceLog(self::$db, [
                    'user_id' => intval(ltrim($userInfo['id'], 'PKF')),
                    'client_id' => $userInfo['client_id'],
                    'user_name' => $userInfo['name'],
                    'user_ip' => $userInfo['ip'],
                    'user_avatar' => $userInfo['avatar'],
                    'kf_id' => intval(ltrim($res['data']['0'], 'KF')),
                    'group_id' => $group,
                    'partner_id' => $partnerId,
                    'end_time' => 0,
                ]);
            }

            // 写入服务接入值
            self::accessIncrement('success_in');

            return [$res['data']['0'], $res['data']['1'], $res['data']['2']];
        } else {
            $waitMsg = '';
            switch ($res['code']) {
                case -1:
                    // 获取离线回复
                    $sayDeline = ApiList::getMessageList(self::$db, 3, $partnerId);
//                    Tools::printWriteLog('离线消息', $sayDeline);
                    $waitMsg = empty($sayDeline['answer']) ? '客服不在线,请稍后再咨询。' : htmlspecialchars($sayDeline['answer']);
                    unset($sayDeline);
                    $code = 0;
                    break;
                case -2:
                    $waitMsg = '没有需要分配的用户';
                    $code = 1;
                    break;
                case -3:
                    break;
                case -4:
                    $number = count(self::$global->userList[$partnerId]);
                    $waitMsg = '您前面还有 ' . $number . ' 位会员在等待。';
                    $code = 1;
                    break;
            }

            $waitMessage = [
                // 'message_type' => 'wait',
                // 离线模式或者等待模式客户端不做断开处理 2020年12月30日17:21:32
                'message_type' => 'connect',
                'data' => [
                    'content' => $waitMsg,
                    'code' => $code,
                    'avatar' => ApiList::getAvatar(self::$db, $partnerId),
                ]
            ];
            
            if (empty($userClientId)) {
                Gateway::sendToClient($client_id, json_encode($waitMessage));
            }
            unset($waitMessage);

            return false;
        }
    }

    /**
     * 给客服分配会员【均分策略】
     * @param $group 分组ID
     * @param $partnerId 商户ID
     * @param $userClientId 用户服务客户端ID（固定用户客户端ID时分配机制）
     * zenghu UADATE 重写分配机制 2021年1月5日16:02:11
     */
    private static function assignmentTask($group = 0, $partnerId = 0, $userClientId = '')
    {
        // 获取客服列表
        $kfList = self::$global->kfList;
        //Tools::printWriteLog('建立聊天关系分配客服-在线客服列表', $kfList);
        //Tools::printWriteLog('partnerId', $partnerId);

        // 该商户该分组下没有客服上线
        if (empty($kfList) || empty($kfList[$partnerId][$group]) || !isset($kfList[$partnerId][$group])) {
            //查询出此商家下的所有分组
            $group_partner = ApiList::getGroupByPartnerId($partnerId);
            if (empty($group_partner)) {
                return Tools::returnErrorCode(-1);
            }
            $newGroup = 1;
            foreach($group_partner as $g) {
                //哪个分组下有上线的客服就用这个分配
                if (isset($kfList[$partnerId][$g]) && count($kfList[$partnerId][$g])) {
                    $newGroup = $g;
                    break;
                }
            }
            //新分组大于1，说明可以重新分配
            if ($newGroup > 1) {
                $group = $newGroup;
            } else {
                return Tools::returnErrorCode(-1);
            }
        }
        Tools::printWriteLog('分配分组group', $group);

        $userList = [];
        $user = [];
        if (empty($userClientId)) {
            // 获取客户列表
            $userList = self::$global->userList;
//            Tools::printWriteLog('建立聊天关系分配客服-在线客户列表', $userList);
            // 该商户下没有待分配的会员
            if (empty($userList) || empty($userList[$partnerId])) {
                return Tools::returnErrorCode(-2);
            }
            $user = array_shift($userList[$partnerId]);
            $userClientId = $user['client_id'];
        }

        // 获取该商户该分组下的客服列表
        $kf = $kfList[$partnerId][$group];
        $onekf = array_shift($kf);
        $kfId = $onekf['id']; // 客服ID
        $kfServiceNumber = $onekf['service_number']; // 客服最大服务人数

        // 判断客服是否超出服务上限人数
        if ($onekf['task'] >= $kfServiceNumber) {
            if (!empty($kf)) {
                foreach ($kf as $key => $vo) {
                    if ($vo['task'] < $vo['service_number']) {
                        $kfId = $key;
                        $kfServiceNumber = $vo['service_number'];
                        break;
                    }
                }
            }
        }

        // 需要排队了
        if ($kfList[$partnerId][$group][$kfId]['task'] == $kfServiceNumber) {
            return Tools::returnErrorCode(-4);
        }
        unset($kf, $onekf, $kfServiceNumber);

        // 给客户分配客服
        if (!in_array($userClientId, $kfList[$partnerId][$group][$kfId]['user_info'])) {
            $kfList[$partnerId][$group][$kfId]['task'] += 1;
            array_push($kfList[$partnerId][$group][$kfId]['user_info'], $userClientId);
        }

        Tools::printWriteLog('建立聊天关系分配客服-在线客服列表', $kfList);
        return Tools::returnErrorCode(1, [
            $kfList[$partnerId][$group][$kfId]['id'],
            $kfList[$partnerId][$group][$kfId]['name'],
            $kfList[$partnerId][$group][$kfId]['client_id'],
            $user,
            $kfList,
            $userList
        ]);
    }

    /**
     * 客服上线，对未分配的客户重新分配来服务
     */
    public static function pullUserTask($partnerId, $group)
    {
        // 转换partnerId
        $partnerId = is_numeric($partnerId) ? $partnerId : (substr($partnerId, strripos($partnerId, "_") + 1));
        $userList = self::$global->userList;
        // 该商户下没有待分配的会员
        if (empty($userList) || empty($userList[$partnerId])) {
            return Tools::returnErrorCode(-2);
        }

        //对未分配的客户进行分配
        foreach($userList[$partnerId] as $v) {
            $userClientId = $v['client_id'];
            // 重新分配客服和客户关系
            $res = self::assignmentTask($group, $partnerId, $userClientId);
            Tools::printWriteLog('返回分配信息列表', $res);
            /**
             * data=> 
             *   [0] => KF113    客服ID
              *  [1] => 华粉1号  客服名称
              *  [2] => 7f0000010fa300000002  客服client
             */
            if (1 == $res['code']) {
                while (!self::$global->cas('kfList', self::$global->kfList, $res['data']['4'])) {
                }; // 更新客服数据
                while (!self::$global->cas('userList', self::$global->userList, $res['data']['5'])) {
                }; // 更新会员数据
    
                // 通知会员发送信息绑定客服的id
                $noticeUser = [
                    'message_type' => 'connect',
                    'data' => [
                        'kf_id' => $res['data']['0'],
                        'kf_name' => $res['data']['1']
                    ]
                ];
                Gateway::sendToClient($userClientId, json_encode($noticeUser));
                unset($noticeUser);
    
                // 获取开场白消息
                $sayHello = ApiList::getMessageList(self::$db, 1, $partnerId);
                if (!empty($sayHello)) {
                    $hello = [
                        'message_type' => 'helloMessage',
                        'data' => [
                            'name' => $res['data']['1'],
                            'avatar' => ApiList::getAvatar(self::$db, $partnerId, intval(ltrim($res['data']['0'], 'KF'))),
                            'id' => $res['data']['0'],
                            'time' => date('H:i'),
                            'content' => htmlspecialchars($sayHello['answer'])
                        ]
                    ];
                    Gateway::sendToClient($userClientId, json_encode($hello));
                    unset($hello);
                }
                unset($sayHello);
                

                // 通知客服端绑定会员的信息
                $noticeKf = [
                    'message_type' => 'connect',
                    'data' => [
                        'user_info' => empty($userInfo) ? $res['data']['3'] : $userInfo,
                    ]
                ];
                Gateway::sendToClient($res['data']['2'], json_encode($noticeKf));
                unset($noticeKf);
                //一些重要的参数
                $user_id = intval(ltrim($v['id'], 'PKF'));
                $kf_id = intval(ltrim($res['data']['0'], 'KF'));
                $kf_client_id = $res['data']['2'];
                // 服务信息入库
                ApiList::serviceLog(self::$db, [
                    'user_id' => $user_id,
                    'client_id' => $v['client_id'],
                    'user_name' => $v['name'],
                    'user_ip' => $v['ip'],
                    'user_avatar' => $v['avatar'],
                    'kf_id' =>  $kf_id,
                    'group_id' => $group,
                    'partner_id' => $partnerId,
                    'end_time' => 0,
                ]);
                // 写入服务接入值
                self::accessIncrement('success_in');

                //客户离线消息发送给客服
                self::sendOfflineMessage($user_id, $res['data']['0'], $res['data']['1'], $res['data']['2']);

                return [$res['data']['0'], $res['data']['1'], $res['data']['2']];
            }
        }
    }

    /**
     * 将内存中的数据写入统计表
     * @param int $flag
     */
    private static function writeLog($flag = 1)
    {
        // 上午 8点 到 22 点开始统计
        if (date('H') < 8 || date('H') > 22) {
            return;
        }

        // 当前正在接入的人 和 在线客服数
        $kfList = self::$global->kfList;

        $nowTalking = 0;
        $onlineKf = 0;
        if (!empty($kfList)) {
            foreach ($kfList as $key => $vo) {
                $onlineKf += count($vo);
                foreach ($vo as $k => $v) {
                    $nowTalking += count($v['user_info']);
                }
            }
        }

        // 在队列中的用户
        $inQueue = count(self::$global->userList);

        $key = date('Ymd') . 'total_in';
        $key2 = date('Ymd') . 'success_in';
        $param = [
            'is_talking' => $nowTalking,
            'in_queue' => $inQueue,
            'online_kf' => $onlineKf,
            'success_in' => self::$global->$key2,
            'total_in' => self::$global->$key,
            'now_date' => date('Y-m-d')
        ];
        self::$db->update(self::$prefix . 'operation_service_now_data')->cols($param)->where('id=1')->query();

        if (2 == $flag) {
            $param = [
                'is_talking' => $nowTalking,
                'in_queue' => $inQueue,
                'online_kf' => $onlineKf,
                'success_in' => self::$global->$key2,
                'total_in' => self::$global->$key,
                'add_date' => date('Y-m-d'),
                'add_hour' => date('H'),
                'add_minute' => date('i'),
            ];
            self::$db->insert(self::$prefix . 'operation_service_data')->cols($param)->query();
        }
        unset($kfList, $nowTalking, $inQueue, $onlineKf, $key, $key2, $param);
    }

    /**
     * 离线消息推送
     * @return Arr
     * @since 2020年12月31日16:16:23
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function sendOfflineMessage($user_id, $kf_id, $kf_name, $kf_client_id)
    {
        // 获取离线消息列表
        $offlineMessage = ApiList::getOffLineMessageListByFromId(self::$db, $user_id);
        Tools::printWriteLog('离线消息-离线消息列表', $offlineMessage);
        if (!empty($offlineMessage)) {
            foreach ($offlineMessage as $val) {
                $message_content = json_decode($val['message_content'], true);
                
                //客服在线发送消息
                $insertChatId = self::sendMessageChat($kf_client_id, [
                    'from_id' => $user_id,
                    'from_name' => $message_content['data']['from_name'],
                    'from_avatar' =>  $message_content['data']['from_avatar'],
                    'type' => $message_content['data']['type'],
                    'content' => $message_content['data']['content'],
                    'to_id' => $kf_id,
                    'to_name' =>$kf_name,
                    'business_type' => 1,
                    'partner_id' => $message_content['data']['partner_id'],
                ]);
                
                if ($insertChatId) {
                    self::$db->query("DELETE FROM `" . self::$prefix . "operation_service_offline_chat` WHERE `id` = {$val['id']}");
                } else {
                    continue;
                }
            }
            unset($message_content);
        }
        unset($offlineMessage);
    }

    /**
     * 离线消息发送给在线的客服[同商户同分组]
     * @param $messageInfo Arr 消息数组
     * @return Arr
     * @since 2020年12月31日16:59:00
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function sendOfflineMessageToCustomerService($messageInfo = [])
    {
//        Tools::printWriteLog('离线消息-消息内容', $messageInfo);

        // 获取商户分组信息
        $partnerId = $messageInfo['partner_id'];
        $group = $messageInfo['group_id'];

        // 判断发送离线消息用户是否在线
        $sendOffLineMessageUser = Gateway::getClientIdByUid($messageInfo['from_id']);
//        Tools::printWriteLog('离线消息-客户是否在线', $sendOffLineMessageUser);

        // 发送离线消息用户在线
        if (!empty($sendOffLineMessageUser) && !empty($sendOffLineMessageUser[0])) {
            // 获取用户信息
            $userInfo = self::$global->userList[$partnerId][$messageInfo['from_id']];
            if (!empty($userInfo)) {
                $clientCount = count($sendOffLineMessageUser) - 1;
                $userInfo['client_id'] = $sendOffLineMessageUser[$clientCount];
                $result = self::userOnlineTask($sendOffLineMessageUser[$clientCount], $group, $partnerId, $userInfo);
                if ($result) {
                    // 获取该用户下离线消息列表
                    $userOffLineMessageList = ApiList::getUserOffLineMessageList(self::$db, $messageInfo['from_id']);
                    foreach ($userOffLineMessageList as $val) {
                        $message_content = json_decode($val['message_content'], true);
//                        Tools::printWriteLog('离线消息-客户发送客服link消息', $message_content);
                        // 客服在线发送消息
                        self::sendMessageChat($result[2], [
                            'from_id' => $userInfo['id'],
                            'from_name' => $userInfo['name'],
                            'from_avatar' => $userInfo['avatar'],
                            'type' => 'text',
                            'content' => $message_content['data']['content'],
                            'to_id' => $result[0],
                            'to_name' => $result[1],
                            'business_type' => 1
                        ]);
                    }
                    unset($userInfo, $message_content);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 客服离线消息发送给用户
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月6日11:01:50
     */
    private static function sendOfflineMessageCustomerServiceToUser()
    {
        // 获取客服发送的离线消息列表
        $offLineMessageList = ApiList::getOffLineMessageCustomerServiceToUserList(self::$db, 10);
        Tools::printWriteLog('客服离线消息', $offLineMessageList);
        if (!empty($offLineMessageList)) {
            foreach ($offLineMessageList as $val) {
                // 获取离线发送的消息
                $messageInfo = json_decode($val['message_content'], true);
                $messageInfo = $messageInfo['data'];
                $messageInfo['business_type'] = 1;

                // 判断发送离线消息用户是否在线
                $sendOffLineMessageUser = Gateway::getClientIdByUid($messageInfo['to_id']);
//                Tools::printWriteLog('离线消息-客户是否在线', $sendOffLineMessageUser);
                // 发送离线消息用户在线
                if (!empty($sendOffLineMessageUser) && !empty($sendOffLineMessageUser[0])) {
                    $clientCount = count($sendOffLineMessageUser) - 1;
                    // 客服在线发送消息
                    self::sendMessageChat($sendOffLineMessageUser[$clientCount], $messageInfo);

                    self::$db->query("DELETE FROM `" . self::$prefix . "operation_service_offline_chat` WHERE `id` = {$val['id']}");
                }
            }
            unset($messageInfo, $sendOffLineMessageUser);
        }
        unset($offLineMessageList);
    }

    /**
     * 发送聊天信息
     * @param $clientId 发送人客户端ID
     * @param $message .from_id int 发送人ID
     * @param $message .from_name string 发送人名称
     * @param $message .from_avatar url 发送人头像
     * @param $message .to_id int 送达人ID
     * @param $message .to_name string 送达人名称
     * @param $message .content string 发送内容
     * @return booler
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月4日17:48:17
     */
    private static function sendMessageChat($clientId = 0, $message = [])
    {
        // 记录聊天记录
        $messageChatId = ApiList::messageChatInsert(self::$db, [
            'from_id' => $message['from_id'],
            'from_name' => $message['from_name'],
            'from_avatar' => $message['from_avatar'],
            'to_id' => $message['to_id'],
            'to_name' => $message['to_name'],
            'content' => is_array($message['content']) ? json_encode($message['content']) : $message['content'],
            'type' => $message['business_type'],
            'message_type' => $message['type'],
            'partner_id' => $message['partner_id']
        ]);

        // 判断用户对用户聊天业务下通知发送消息的用户
        if ($message['business_type'] == 2 && isset($message['timestamp']) && !empty($message['timestamp'])) {
            // 给发送人返回发送消息的ID
            $fromIsOnline = Gateway::getClientIdByUid($message['from_id']);
            if (!empty($fromIsOnline) && !empty($fromIsOnline[0])) {
                $clientCount = count($fromIsOnline) - 1;
                Gateway::sendToClient($fromIsOnline[$clientCount], json_encode([
                    'message_type' => 'messageSuccess',
                    'data' => [
                        $message['timestamp'] => $messageChatId
                    ]
                ]));
            }
        }

        // 发送消息 自己给自己发送消息不再推送消息 htmlspecialchars()
        if ($message['from_id'] != $message['to_id']) {
            Gateway::sendToClient($clientId, json_encode([
                'message_type' => 'chatMessage',
                'data' => [
                    'name' => $message['from_name'],
                    'avatar' => $message['from_avatar'],
                    'id' => $message['from_id'],
                    'time' => date('H:i'),
                    'type' => $message['type'],
                    'content' => is_array($message['content']) ? json_encode($message['content']) : $message['content'],
                    'business_type' => $message['business_type'],
                    'aid' => $messageChatId
                ]
            ]));
        }
        return $messageChatId;
    }

    /**
     * 聊天用户注册功能
     * @param $message Arr 注册信息数据
     * @return booler
     * @since 2021年1月14日10:34:19
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function insertUserServiceRegister($client_id = '', $message = [])
    {
        // 检测用户是否注册过服务
        if (ApiList::getUserServiceRegisterInfo(self::$db, $message['uid'], $message['to_uid'])) {
            return true;
        }

        // 记录用户注册的服务
        ApiList::serviceLog(self::$db, [
            'user_id' => $message['uid'],
            'client_id' => $client_id,
            'user_name' => $message['uname'],
            'user_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            'user_avatar' => $message['uavatar'],
            'kf_id' => $message['to_uid'],
            'kf_name' => $message['to_uname'],
            'kf_avatar' => $message['to_uavatar'],
            'group_id' => 0,
            'partner_id' => 0,
            'type' => 2
        ]);

        return true;
    }

    /**
     * 消息已读通知好友已读消息
     * @param $params .friend_id int 好友ID
     * @param $params .ids string 已读消息IDS
     * @return mes
     * @since 2021年1月15日13:42:44
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function readNotify($params = [])
    {
        // 判断好友用户是否在线
        $friendIsOnline = Gateway::getClientIdByUid($params['friend_id']);
//        Tools::printWriteLog('好友用户是否在线', $friendIsOnline);
        // 发送离线消息用户在线
        if (!empty($friendIsOnline) && !empty($friendIsOnline[0])) {
            $clientCount = count($friendIsOnline) - 1;
            Gateway::sendToClient($friendIsOnline[$clientCount], json_encode([
                'message_type' => 'readMessage',
                'data' => [
                    'ids' => $params['ids']
                ]
            ]));
        }
    }

    /**
     * 获取最大的服务人数
     * @return int
     * 此函数作废 因客服管理平台可配置化客服最大服务客户人数 zenghu 2021年1月5日15:34:44
     */
    private static function getMaxServiceNum()
    {
        $maxNumber = self::$maxNumber;
        if (!empty($maxNumber)) {
            $maxNumber = 5;
        }

        return $maxNumber;
    }

    /**
     * 哨兵当值
     * @return Arr
     * @since 2021年1月11日16:13:28
     * @author zenghu [ 1427305236@qq.com ]
     */
    private static function sentry($client_id = '', $message = [])
    {
        // 无进城事由
        if (empty($message['serviceApiName'])) {
            return Tools::returnErrorCode('-6');
        }
        // 城无此人
        if (!in_array($message['serviceApiName'], array_keys(ApiList::$serviceList))) {
            return Tools::returnErrorCode('-5');
        }
        // 是否需要介绍信&&介绍信内容是否正确
        if (!empty(ApiList::$serviceList[$message['serviceApiName']])) {
            foreach (ApiList::$serviceList[$message['serviceApiName']] as $val) {
                if (!isset($message['requestsParams'][$val]) || empty($message['requestsParams'][$val])) {
                    return Tools::returnErrorCode('-6', [], "服务请求参数{$val}不能为空！");
                    break;
                }
            }
        }
        // TO DO LIST 接口限流 -- IP限制 2021年1月12日08:33:52

        // 服务分发请求
        $serviceApiName = $message['serviceApiName'];
        //Tools::printWriteLog($serviceApiName . '接口获取参数：', $message);

        // 判断是否需要登录请求接口
        if (!isset($message['unLogin'])) {
            // 判断用户是否在线
            $isOnlineUser = Gateway::isOnline($client_id);
            //Tools::printWriteLog('用户对用户-获取用户是否在线', $isOnlineUser);
            if (!$isOnlineUser) {
                return Tools::returnErrorCode('-7');
            }

            //获取用户ID 使用getUidByClientId 要求Gateway版本>=3.0.8，目前为3.0.19
            $userId = Gateway::getUidByClientId($client_id);
            if(!$userId){
                return Tools::returnErrorCode('-8');
            }

            // 采用session处理此方案
            $userInfo = Gateway::getSession($client_id);
            //Tools::printWriteLog('获取客服UID：', $userInfo);
            $userId = $userInfo['uid'];
            if (empty($userInfo) || empty($userId)) {
                return Tools::returnErrorCode('-8');
            }

            $res = ApiList::$serviceApiName(self::$db, $userId, $message['requestsParams']);

            // 判断消息已读接口调用--附加通知好友对方已读推送
            if ($serviceApiName == 'updateReadMessage') {
                self::readNotify($message['requestsParams']);
            }
        } else {
            if ($serviceApiName == 'userLogin') {
                $token = md5(date('Ymd') . 'zhongbenkeji');
                if ($message['requestsParams']['token'] != $token) {
                    return Tools::returnErrorCode('-9');
                }
            }
            $res = ApiList::$serviceApiName(self::$db, $message['requestsParams']);

            // 判断是否为获取客服接口
            if ($serviceApiName == 'userLogin') {
                if (empty($res)) {
                    return Tools::returnErrorCode('-1', [], '查询无此客服，请确认是否开通客服权限！');
                }
                if ($res['status'] == 0) {
                    return Tools::returnErrorCode('-2', [], '已暂停客服服务，请咨询管理员！');
                }
            }
        }

        // 统一服务返回
        $codeReturn = Tools::returnErrorCode('1', $res);
        $codeReturn['data']['type'] = 'serviceApi';
        $codeReturn['data']['serviceApiName'] = $serviceApiName;
        //Tools::printWriteLog($serviceApiName . '接口返回：', $codeReturn);

        return $codeReturn;
    }
}
