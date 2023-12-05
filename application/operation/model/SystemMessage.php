<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\model;

use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use think\Model as ThinkModel;
use think\Db;

/**
 * 站内信
 * Class SystemMessage
 * @package app\user\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/9 16:24
 */
class SystemMessage extends ThinkModel
{

    //设置表名
    protected $table = '__OPERATION_SYSTEM_MESSAGE__';
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public static $msgtype = [
        1 => '平台公告',
//        2 => '评价消息',
        3 => '订单信息',
//        4 => '其他消息',
//        5 => "客服信息"
    ];
    //

    /**
     * 发送一个站内信
     * @return boolean|string
     * @author 晓风<215628355@qq.com>
     */
    public function sendMsg($message, $to_user_id = null)
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
        $this->error = $res['result'] ;
        return false;
    }


    /**
     * 获取我的系统消息
     * @param int $user_id 会员ID
     * @return object
     * @author 晓风<215628355@qq.com>
     */
    public static function getList($user_id, $msgtype = 0)
    {
        $map = [];
        if ($msgtype > 0) {
            $map['operation_system_message.msg_type'] = $msgtype;
        }
        return self::view("operation_system_message", true)
            ->view("operation_system_message_read", "aid as readid", 'operation_system_message_read.sys_msg_id = operation_system_message.id and operation_system_message_read.user_id=:user_id', "left")
            ->bind(['user_id' => $user_id])
            ->where(function ($query) use ($user_id) {
                $query->where("operation_system_message.to_user_id", $user_id)
                    ->whereOr("operation_system_message.to_user_id", 0)
                    ->whereOr("operation_system_message.to_user_id", "")
                    ->whereOr("operation_system_message.to_user_id", null);
            })
            ->where($map)
            ->where("operation_system_message_read.aid IS NULL OR operation_system_message_read.status = 1")
            ->order("operation_system_message.create_time desc")
            ->paginate()->each(function ($item) use ($user_id) {
                if (!$item['readid']) {
                    //更正消息已读user_id
                    SystemMessageRead::setread($user_id, $item['id']);
                }
                $item['is_read'] = $item['readid'] ? 1 : 0;
                $item['thumb'] = get_file_url($item['thumb']);
                $item['thumb'] = get_file_url($item['thumb']);
//                if($item['msg_type'] == 3){
//                    $item['create_times'] = str_replace('-','/',substr($item['create_time'],2,14));
//                }else{
                    $item['create_times'] = substr($item['create_time'],0,16);
//                }
                $item['from'] = '来自：系统消息';
            });

    }

    /**
     * 获取我的系统消息
     * @param int $user_id 会员ID
     * @return object
     * @author 晓风<215628355@qq.com>
     */
    public static function getMessage($user_id, $msgtype = 0)
    {
        if ($msgtype == 1) {
            $category_id = 31;
            return Article::getMessage($category_id, $user_id);
        } elseif ($msgtype == 5) {
            return ServiceChat::getMessage($user_id);
        } else {
            $map = [
                ['to_user_id', '=', $user_id],
            ];
            if ($msgtype > 0) {
                $map[] = ['msg_type', '=', $msgtype];
            }
            //查询未读消息
            $list = Db::name("operation_system_message")
                ->where('is_read',0)
                ->where($map)
                ->order("create_time desc")
                ->select();
            $res = false;
            if (count($list) > 0) {
                $res = $list[0];
                $res["num"] = count($list);
            }
            return $res;
        }

    }
    /**
     * 获取我的系统消息
     * @param int $user_id 会员ID
     * @return object
     * @author 晓风<215628355@qq.com>
     */
    public static function getNew($user_id, $msgtype = 0)
    {
        $map = [];
        if ($msgtype > 0) {
            $map['operation_system_message.msg_type'] = $msgtype;
        }
        return self::view("operation_system_message", true)
            ->view("operation_system_message_read", "aid as readid", 'operation_system_message_read.sys_msg_id = operation_system_message.id and operation_system_message_read.user_id=:user_id', "left")
            ->bind(['user_id' => $user_id])
            ->where(function ($query) use ($user_id) {
                $query->where("operation_system_message.to_user_id", $user_id)
                    ->whereOr("operation_system_message.to_user_id", 0)
                    ->whereOr("operation_system_message.to_user_id", "")
                    ->whereOr("operation_system_message.to_user_id", null);
            })
            ->where($map)
            ->where("operation_system_message_read.aid IS NULL OR operation_system_message_read.status = 1")
            ->order("operation_system_message.create_time desc")
            ->find();

    }

    /*
* 发送消息
* @param  $to_user_id 指定接收人ID
* @param  $title 消息标题
* @param  $content 消息内容
* @param  $type 消息类型1单推2多推3全部推
* @param  $msg_type 消息分类
* @param  $template_type 消息类型1通知透2打开链接功能3透传功能
*/
    public static function send_msg($to_user_id, $title, $content, $type, $msg_type, $template_type, $thumb = 0, $link = '')
    {
        $message = (new SystemMessage())->insert([
            'to_user_id' => $to_user_id,
            'title' => $title,
            'content' => $content,
            'is_read' => 0,
            'create_time' => time(),
            'type' => $type,
            'msg_type' => $msg_type,
            'template_type' => $template_type,
            'thumb' => $thumb,
            'link' => $link
        ]);
        if ($message) {
            return true;
        } else {
            return false;
        }
    }
}
