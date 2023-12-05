<?php
namespace Util;

use Util\Tools;

/**
 * 接口提供类
 */
class ApiList
{
    // 定义表前缀
    public static $prefix = 'lb_';

    // 服务接口定义及必须参数判断定义
    public static $serviceList = [
        'getFriendsList' => [], // 获取好友列表
        'getChatUsersList' => [], // 获取聊天用户列表
        'getMeassageChatList' => ['friend_id'], // 获取聊天消息列表
        'updateReadMessage' => ['friend_id','ids'], // 消息已读
        'getCustomerServiceUserList' => [], // 获取客服服务用户列表
        'getCustomerServiceInfo' => ['token','signature'], // 未登录情况下获取客户信息
    ];

    /**
     * 获取好友列表
     * @param $db object mysql服务对象
     * @param $userId INT 用户ID
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月11日16:39:29
     * @return Arr
     */
    public static function getFriendsList($db=[], $userId=0)
    {
        // TO DO LIST
    }

    /**
     * 获取聊天用户列表
     * @param $db object mysql服务对象
     * @param $userId INT 用户ID
     * @param $requests.page int 分页页码
     * @param $requests.size int 每页展示条数
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月15日10:49:58
     * @return Arr
     */
    public static function getChatUsersList($db=[], $userId=0, $requests=[])
    {
        // 模拟参数
        // $requests = array(
        //  'page' => '1',
        //  'size' => '10',
        // );

        // 获取参数值
        $page = empty($requests['page']) ? 1 : $requests['page'];
        $size = empty($requests['size']) ? 10 : $requests['size'];
        $limit = (($page - 1) * $size) . ',' . $size;

        // 查询聊天用户列表
        $chatUsersList = self::listHandel($db, "
            SELECT 
                kf_id friend_id,
                kf_name user_name,
                kf_avatar user_avatar,
                create_time
            FROM `lb_operation_service_log` 
            WHERE `type` = 2 AND `user_id` = {$userId}
        ", " LIMIT {$limit}");
        unset($page, $size, $limit);
        if (empty($chatUsersList[1])) {
            return ['list'=>[], 'total'=>0];
        }
    
        // 处理用户列表
        foreach ($chatUsersList[1] as $key=>$val) {
            // 获取最后一条消息内容和沟通时间
            $lastMessage = self::getUserChatMessage($db, $userId, $val['friend_id']);
            $chatUsersList[1][$key]['message_content'] = !isset($lastMessage['content']) ? '' : $lastMessage['content'];
            $chatUsersList[1][$key]['message_type'] = $lastMessage['message_type'];
            
            // 格式化时间戳
            $time = !isset($lastMessage['create_time']) ? $val['create_time'] : $lastMessage['create_time'];
            $chatUsersList[1][$key]['message_time'] = Tools::dateHandel($time);
            $chatUsersList[1][$key]['create_time'] = $time;
            
            // 获取未读消息数据
            $unreadMessageCount = self::getFriendUnreadMessageCount($db, $userId, $val['friend_id']);
            $chatUsersList[1][$key]['unread_message'] = $unreadMessageCount['unreadMessageCount'];
            $chatUsersList[1][$key]['unread_message_ids'] = $unreadMessageCount['unreadMessageIds'];
        }
        // 聊天用户列表数据排序
        $dateTime = array_column($chatUsersList[1], 'create_time');
        array_multisort($dateTime, SORT_DESC, $chatUsersList[1]);
        
        return ['list'=>$chatUsersList[1], 'total'=>$chatUsersList[0]];
    }

    /**
     * 获取聊天消息列表
     * @param $db object mysql服务对象
     * @param $userId INT 用户ID
     * @param $requests.friend_id int 好友ID
     * @param $requests.business_type int 业务类型(1 客服场景 2用户聊天场景)
     * @param $requests.page int 分页页码
     * @param $requests.size int 每页展示条数
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月11日16:39:29
     * @return Arr
     */
    public static function getMeassageChatList($db=[], $userId=0, $requests=[])
    {
        // 模拟参数
        // $requests = array(
        // 	'friend_id' => '1',
        // 	'business_type' => '1',
        // 	'page' => '1',
        // 	'size' => '10',
        // );

        // 获取参数值
        $type = empty($requests['business_type']) ? 2 : $requests['business_type'];
        $page = empty($requests['page']) ? 1 : $requests['page'];
        $size = empty($requests['size']) ? 10 : $requests['size'];
        $limit = (($page - 1) * $size) . ',' . $size;

        // 查询聊天消息
        $meassageChatList = self::listHandel($db, "
            SELECT
                aid,create_time,from_id,from_name,from_avatar,content,is_read,message_type type
            FROM `lb_operation_service_chat`
            WHERE `type` = {$type} AND `status` = 1
            AND (
                (`from_id` = '{$userId}' AND `to_id` = {$requests['friend_id']}) OR 
                (`from_id` = {$requests['friend_id']} AND `to_id` = '{$userId}')
            )
            ORDER BY create_time DESC
		", " LIMIT {$limit}");
        if (empty($meassageChatList[1])) {
            return ['list'=>[], 'total'=>0];
        }
        // // 处理聊天数据数组 后边需要的时候可以开启
        // foreach($meassageChatList as $key=>$val){
        //     // 格式化时间输出
        //     $meassageChatList[1][$key]['create_time'] = Tools::dateHandel($val['create_time']);
        // }

        // 聊天数据排序
        $dateFormatSort = array_column($meassageChatList[1], 'aid');
        array_multisort($dateFormatSort, SORT_ASC, $meassageChatList[1]);

        return ['list'=>$meassageChatList[1], 'total'=>$meassageChatList[0]];
    }

    /**
     * 消息已读
     * @param $db object mysql服务对象
     * @param $userId INT 用户ID
     * @param $requests.ids int 消息ID(支持字符戳批量更新)
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月12日09:25:38
     * @return Arr
     */
    public static function updateReadMessage($db=[], $userId=0, $requests=[])
    {
        $re = $db->query("
            UPDATE 
                lb_operation_service_chat
            SET is_read = 1, update_time = UNIX_TIMESTAMP()
            WHERE aid IN ({$requests['ids']})
        ");
        if (false === $re) {
            return ['result'=>false];
        }

        // TO DO LIST 通知好友消息已读 $requests['friend_id']

        return ['result'=>true];
    }
    
    /**
     * 获取客服服务用户列表
     * @param $db object mysql服务对象
     * @param $userId INT 客服ID
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月28日15:00:17
     * @return Arr
     */
    public static function getCustomerServiceUserList($db=[], $userId=0, $requests=[])
    {
        // 模拟参数
        // $requests = array(
        //  'page' => '1',
        //  'size' => '10',
        // );
        
        // 获取参数值
        $page = empty($requests['page']) ? 1 : $requests['page'];
        $size = empty($requests['size']) ? 10 : $requests['size'];
        $limit = (($page - 1) * $size) . ',' . $size;
        
        $userId = ltrim($userId, 'KF');

        // 查询聊天用户列表
        $chatUsersList = self::listHandel($db, "
            SELECT 
                user_id id,
                user_name name,
                user_avatar avatar,
                user_ip ip,
                create_time
            FROM `lb_operation_service_log` 
            WHERE `type` = 1 AND `kf_id` = {$userId}
            GROUP BY id
        ", " LIMIT {$limit}");
        unset($page, $size, $limit);
        if (empty($chatUsersList[1])) {
            return ['list'=>[], 'total'=>0];
        }
    
        // 处理用户列表
        foreach ($chatUsersList[1] as $key=>$val) {
            // 获取最后一条消息内容和沟通时间
            $lastMessage = self::getUserChatMessage($db, 'KF'.$userId, $val['id'], 1);
            $chatUsersList[1][$key]['message_content'] = !isset($lastMessage['content']) ? '' : $lastMessage['content'];
            $chatUsersList[1][$key]['message_type'] = $lastMessage['message_type'];
            
            // 格式化时间戳
            $time = !isset($lastMessage['create_time']) ? $val['create_time'] : $lastMessage['create_time'];
            $chatUsersList[1][$key]['message_time'] = Tools::dateHandel($time);
            $chatUsersList[1][$key]['create_time'] = $time;
            
            // 获取未读消息数据
            $unreadMessageCount = self::getFriendUnreadMessageCount($db, 'KF'.$userId, $val['id']);
            $chatUsersList[1][$key]['unread_message'] = $unreadMessageCount['unreadMessageCount'];
            $chatUsersList[1][$key]['unread_message_ids'] = $unreadMessageCount['unreadMessageIds'];
        }
        // 聊天用户列表数据排序
        $dateTime = array_column($chatUsersList[1], 'create_time');
        array_multisort($dateTime, SORT_DESC, $chatUsersList[1]);
        
        return ['list'=>$chatUsersList[1], 'total'=>$chatUsersList[0]];
    }

    /**
     * 列表数据查询带分页处理
     * @param $sql sting 待处理SQL
     * @param $limit sting 查询限制条件
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月11日18:02:39
     * @return Arr
     */
    private static function listHandel($db=[], $sql='', $limit='')
    {
        // 判断是否需要分页查询
        if (!empty($limit)) {
            $count = count($db->query($sql));

            $sql .= $limit;
            $data = $db->query($sql);

            return [$count, $data];
        }
        
        return $db->query($sql);
    }

    /**
     * 服务信息入库
     * @param $serviceLog.user_id int 客户ID
     * @param $serviceLog.client_id string 客户客户端ID
     * @param $serviceLog.user_name string 客户名称
     * @param $serviceLog.user_ip string 客户IP
     * @param $serviceLog.user_avatar url 客户头像
     * @param $serviceLog.kf_id int 客服ID
     * @param $serviceLog.group_id int 所属分组ID
     * @param $serviceLog.partner_id int 所属商户ID
     * @param $serviceLog.end_time int 服务结束时间
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月4日18:27:54
     * @return booler
     */
    public static function serviceLog($db=[], $serviceLog=[])
    {
        $serviceLog['create_time'] = time();
        
        return $db->insert(self::$prefix . 'operation_service_log')->cols($serviceLog)->query();
    }

    /**
     * 聊天信息入库
     * @param $messageInfo.from_id int 发送人ID
     * @param $messageInfo.from_name string 发送人名称
     * @param $messageInfo.from_avatar url 发送人头像
     * @param $messageInfo.to_id int 送达人ID
     * @param $messageInfo.to_name string 送达人名称
     * @param $messageInfo.content string 发送内容
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月4日17:48:17
     * @return booler
     */
    public static function messageChatInsert($db=[], $messageInfo=[])
    {
        $messageInfo['create_time'] = time();

        return $db->insert(self::$prefix.'operation_service_chat')->cols($messageInfo)->query();
    }
    
    /**
     * 获取商家配置的消息
     * @param $type int 消息类型(1 开场白消息，3 离线自动回复消息)
     * @param $partnerId int 商家ID
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2020年12月30日14:28:00
     * @return Arr
     */
    public static function getMessageList($db=[], $type=1, $partnerId=0)
    {
        if (!is_numeric($partnerId)) {
            $partnerId = 0;
        }
        return $db->row("select `answer` from `" . self::$prefix . "operation_service_reply` where `type` = {$type} AND `status` = 1 AND partner_id = {$partnerId}");
    }
    
    /**
     * 获取clientId的信息
     * @param $clientId INT 客户或客服clientId
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月4日10:59:00
     * @return Arr
     */
    public static function getClientInfo($db=[], $clientId=0)
    {
        return $db->row("SELECT `user_id`,`group_id`,`partner_id` FROM `" . self::$prefix . "operation_service_log` WHERE `client_id`= '{$clientId}'");
    }
    
    /**
     * 获取客服发送的离线消息列表
     * @param $limit INT 读取离线消息条数
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月6日11:03:10
     * @return Arr
     */
    public static function getOffLineMessageCustomerServiceToUserList($db=[], $limit=50)
    {
        return $db->query("SELECT * FROM `" . self::$prefix . "operation_service_offline_chat` WHERE `type` = 2 LIMIT {$limit}");
    }
    
    /**
     * 获取离线消息用户列表
     * @param $limit INT 读取离线消息条数
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月4日11:47:47
     * @return Arr
     */
    public static function getOffLineMessageList($db=[], $limit=50)
    {
        $res = $db->select('*')
            ->from(self::$prefix . 'operation_service_offline_chat')
            ->where('`type` = 1')
            ->groupBy(array('from_id'))
            ->orderByASC(array('id'))
            ->limit($limit)
            ->query();
        return $res;
    }

    /**
     * 获取指定用户的离线消息列表
     */
    public static function getOffLineMessageListByFromId($db=[], $from_id=0)
    {
        $res = [];
        if ($from_id) {
            $where = '`type` = 1 AND from_id='. $from_id;
            $res = $db->select('*')
            ->from(self::$prefix . 'operation_service_offline_chat')
            ->where($where)
            ->orderByASC(array('id'))
            ->query();
        }
        return $res;
    }
    
    /**
     * 获取离线消息列表
     * @param $userId INT 用户ID
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月6日10:16:46
     * @return Arr
     */
    public static function getUserOffLineMessageList($db=[], $userId=0)
    {
        return $db->select('*')
            ->from(self::$prefix . 'operation_service_offline_chat')
            ->where("from_id = {$userId}")
            ->query();
    }

    /**
     * 检测用户是否注册过
     * @param $userId INT 用户ID
     * @param $friendId INT 好友ID
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月14日11:02:31
     * @return Arr
     */
    public static function getUserServiceRegisterInfo($db=[], $userId=0, $friendId=0)
    {
        return $db->select('aid')
            ->from(self::$prefix . 'operation_service_log')
            ->where("(`user_id` = {$userId} AND `kf_id` = {$friendId}) AND `type` = 2")
            ->row();
    }

    /**
     * 获取用户最后一条聊天数据
     * @param $userId INT 用户ID
     * @param $friendId INT 好友ID
     * @param $type INT 聊天数据类型（1客服客户聊天 2客户和客户聊天）
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月14日11:02:31
     * @return Arr
     */
    private static function getUserChatMessage($db=[], $userId=0, $friendId=0, $type=2)
    {
        return $db->select('content, create_time, message_type')
            ->from(self::$prefix . 'operation_service_chat')
            ->where("
                `type` = {$type} AND (
                    (`from_id` = '{$userId}' AND `to_id` = {$friendId}) OR 
                    (`from_id` = {$friendId} AND `to_id` = '{$userId}')
                )
            ")
            ->orderByDESC(array('create_time'))
            ->row();
    }

    /**
     * 获取未读消息数
     * @param $userId INT 用户ID
     * @param $friendId INT 好友ID
     * @param $type INT 聊天数据类型（1客服客户聊天 2客户和客户聊天）
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月14日16:43:26
     * @return Arr
     */
    private static function getFriendUnreadMessageCount($db=[], $userId=0, $friendId=0, $type=2)
    {
        return $db->select("
                IFNULL(COUNT('aid'), 0) unreadMessageCount,
                IFNULL(GROUP_CONCAT(aid), '') unreadMessageIds
            ")
            ->from(self::$prefix . 'operation_service_chat')
            ->where("`type` = {$type} AND `is_read` = 0 AND (`from_id` = {$friendId} AND `to_id` = '{$userId}')")
            ->row();
    }
    
    /**
     * 用户根据凭证登录
     * @param $requestsParams.signature string 登录凭证
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月28日18:04:32
     * @return Arr
     */
    public static function getCustomerServiceInfo($db=[], $requestsParams=[])
    {
        $signature = $requestsParams['signature'];
        return $db->select("`id`, `nickname`, `avatar`, `partner_id`, `group`, `service_number`, `status`")
            ->from(self::$prefix . 'operation_service')
            ->where("`signature` = '{$signature}'")
            ->row();
    }

    /**
     * 获取客服头像,没有客服头像取站点logo
     */
    public static function getAvatar($db, $partnerId=0, $kf_id=0)
    {
        if (is_numeric($partnerId)) {
            $partnerId = 0;
        }
        $whereId = '';
        if ($kf_id) {
            $whereId = ' AND id='.$kf_id;
        }

        $imgdomain = 'https://zbphp.zhongbenzx.com';
        $res = $db->select("`id`, `nickname`, `avatar`, `partner_id`, `group`, `service_number`, `status`")
            ->from(self::$prefix . 'operation_service')
            ->where("`partner_id` = '{$partnerId}' AND status=1 {$whereId}")
            ->row();
        //调试SQL语句
        //Tools::printWriteLog('头像查询',  $db->lastSQL());
        if ($res['avatar']) {
            $id = $res['avatar'];
        } else {
            $logo = $db->select("`value`")
            ->from(self::$prefix . 'admin_config')
            ->where(" `name` = 'web_site_logo' ")
            ->row();
            $id = $logo['value'];
        }
        
        $upload = $db->select("`path`")
        ->from(self::$prefix . 'upload')
        ->where("`id` = {$id}")
        ->row();
        //Tools::printWriteLog('头像查询', $upload);
        if (stripos($upload['path'], 'http') !== false) {
            return $upload['path'];
        } else {
            return  $imgdomain.$upload['path'];
        }
    }

    /**
     * 获取商家的客服分组列表
     */
    public static function getGroupByPartnerId($partnerId=0)
    {
        $res = $data = [];
        $res = $db->select("`aid`")
        ->from(self::$prefix . 'operation_service_group')
        ->where("`partner_id` = '{$partnerId}' AND status=1")
        ->row();
        if (count($res)) {
            foreach($res as $v){
                $data[] = $v['aid'];
            }
        }
        return $data;
    }
}
