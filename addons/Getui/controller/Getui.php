<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace addons\Getui\controller;

require_once(dirname(dirname(__FILE__)) . '/sdk/IGt.Push.php');
require_once(dirname(dirname(__FILE__)) . '/sdk/igetui/IGt.AppMessage.php');
require_once(dirname(dirname(__FILE__)) . '/sdk/igetui/IGt.TagMessage.php');
require_once(dirname(dirname(__FILE__)) . '/sdk/igetui/IGt.APNPayload.php');
require_once(dirname(dirname(__FILE__)) . '/sdk/igetui/template/IGt.BaseTemplate.php');
require_once(dirname(dirname(__FILE__)) . '/sdk/IGt.Batch.php');
require_once(dirname(dirname(__FILE__)) . '/sdk/igetui/utils/AppConditions.php');
require_once(dirname(dirname(__FILE__)) . '/sdk/igetui/template/notify/IGt.Notify.php');
require_once(dirname(dirname(__FILE__)) . '/sdk/igetui/IGt.MultiMedia.php');
require_once(dirname(dirname(__FILE__)) . '/sdk/payload/VOIPPayload.php');

/**
 * 新的个推控制器,服务端推送接口，支持三个接口推送
 * 移除其他模板，统一使用透传模板
 * 1.PushMessageToSingle接口：支持对单个用户进行推送
 * 2.PushMessageToList接口：支持对多个用户进行推送，建议为50个用户
 * 3.pushMessageToApp接口：对单个应用下的所有用户进行推送，可根据省份，标签，机型过滤推送
 * @package addons\Getui\controller
 * @author 晓风<215628355@qq.com>
 */
class Getui
{
    // AppID
    protected $AppID;
    // AppKey
    protected $AppKey;
    // AppSecret
    protected $AppSecret;
    // masterSecret
    protected $MasterSecret;
    // 产品域名
    protected $domain = "http://sdk.open.api.igexin.com/apiex.htm";
   

    public function __construct()
    {      
        // 插件配置参数
        $config = addons_config('Getui');
        $this->AppID = $config['AppID'];
        $this->AppKey = $config['AppKey'];
        $this->AppSecret = $config['AppSecret'];
        $this->MasterSecret = $config['MasterSecret'];      
    }

    /**
     * 单推
     * 晓风<215628355@qq.com>]
     * @param string $title     消息标题
     * @param string $content   消息内容
     * @param string $client_id 客户端设备ID
     * @param string $custom    透传内容
     * @param string $logo      android的LOGO设置
     * @param string $logoUrl   android的LOGO链接设置
     * @param string $sound     IOS音乐设置
     * @param int    $badge     IOS角标设置（即未读消息数量）
     * @return pushMessageToSingle
     */
    public function pushMessageToSingle($title , $content , $client_id , $custom = "" , $logo = "", $logoUrl = "",$sound = "default",$badge = -1)
    {
        $igt = new \IGeTui($this->domain, $this->AppKey, $this->MasterSecret);
        
        //创建模板
        $template = $this->NotificationTemplate($title,$content,$custom,$logo,$logoUrl,$sound,$badge);

        //个推信息体
        $message = new \IGtSingleMessage();
        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        $message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
     
        //接收方
        $target = new \IGtTarget();
        $target->set_appId($this->AppID);
        $target->set_clientId($client_id);
        //执行推送
        return $igt->pushMessageToSingle($message, $target);     
    }


    /**
     * 多推
     * 晓风<215628355@qq.com>]
    * @param string $title     消息标题
     * @param string $content   消息内容
     * @param array   $client_id 客户端设备ID
     * @param string $custom    透传内容
     * @param string $logo      android的LOGO设置
     * @param string $logoUrl   android的LOGO链接设置
     * @param string $sound     IOS音乐设置
     * @param int    $badge     IOS角标设置（即未读消息数量）
     * @return pushMessageToSingle
     */
    function pushMessageToList($title , $content , $client_id , $custom = "" , $logo = "", $logoUrl = "",$sound = "default",$badge = -1)
    {
        putenv("gexin_pushList_needDetails=true");
        putenv("gexin_pushList_needAsync=true");

        $igt = new \IGeTui($this->domain, $this->AppKey, $this->MasterSecret);

        $template = $this->NotificationTemplate($title,$content,$custom,$logo,$logoUrl,$sound,$badge);
        
        //个推信息体
        $message = new \IGtListMessage();
        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        $message->set_PushNetWorkType(0);	//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        $contentId = $igt->getContentId($message);	//根据TaskId设置组名，支持下划线，中文，英文，数字
        
        //接收方
        foreach($client_id as $k=>$v){
            $target[$k] = new \IGtTarget();
            $target[$k]->set_appId($this->AppID);
            $target[$k]->set_clientId($v);
        }
        //执行推送
        return $igt->pushMessageToList($contentId, $target);      
    }
   /**
     * 群推
     * 晓风<215628355@qq.com>]
     * @param string $title     消息标题
     * @param string $content   消息内容
     * @param string $custom    透传内容
     * @param string $logo      android的LOGO设置
     * @param string $logoUrl   android的LOGO链接设置
     * @param string $sound     IOS音乐设置
     * @param int    $badge     IOS角标设置（即未读消息数量）
     * @return pushMessageToSingle
     */
    public function pushMessageToApp($title , $content , $custom = "" , $logo = "", $logoUrl = "",$sound = "default",$badge = -1)
    {
        $igt = new \IGeTui($this->domain, $this->AppKey, $this->MasterSecret);
        
        $template = $this->NotificationTemplate($title,$content,$custom,$logo,$logoUrl,$sound,$badge);
        //基于应用消息体
        $message = new \IGtAppMessage();
        $message->set_isOffline(true);
        $message->set_offlineExpireTime(3600 * 1000 * 2);//离线时间单位为毫秒，例，两个小时离线为3600*1000*2        
        $message->set_data($template); 
        $appIdList = array($this->AppID);
        $message->set_appIdList($appIdList);
        $rep = $igt->pushMessageToApp($message);
        return $rep;
    }
      /**
     * 创建一个通用模板
     * @param string $title     消息标题
     * @param string $content   消息内容
     * @param string $custom    透传内容
     * @param string $logo      android的LOGO设置
     * @param string $logoUrl   android的LOGO链接设置
     * @param string $sound     IOS音乐设置
     * @param int    $badge     IOS角标设置（即未读消息数量）
     * @return IGtTransmissionTemplate
     */
    private function NotificationTemplate($title,$content,$custom = "",$logo = "",$logoUrl = "",$sound = "default",$badge = -1){
        $template = new \IGtNotificationTemplate();
        $template->set_appId($this->AppID);//应用appid
        $template->set_appkey($this->AppKey);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($custom);//透传内容
        
        $template->set_title($title);      //通知栏标题
        $template->set_text($content);     //通知栏内容
        $template->set_logo($logo);        //通知栏logo
        $template->set_logoURL($logoUrl);   //通知栏logo链接
        //设置响铃 震动效果
        $template->set_isRing(true);                   //是否响铃
        $template->set_isVibrate(true);                //是否震动
        $template->set_isClearable(true);              //通知栏是否可清除
        
        //IOS推送设置
        //ANS普通消息
        $alertmsg = new \DictionaryAlertMsg();//  必须有。    声明DictionaryAlertMsg的对象alertmsg
        $alertmsg->body=$content;//  必须有。   为body赋值
        $alertmsg->title= $title;//  必须有。   为title赋值

        $apn = new \IGtAPNPayload();// 必须有。   声明IGtAPNPayload的对象apn
        $apn->alertMsg=$alertmsg;// 必须有alertmsg，且alertmsg中一定有title以及和body，因为这就是客户端在通知栏/横幅看到的标题和内容。
        $apn->contentAvailable=0;// 必须为0 
        $apn->sound = $sound;// 铃声
        $apn->badge = $badge;// 角标，可有可无
        $apn->add_customMsg("payload",$custom ?: "payload");
        $apn->add_customMsg("custom",$custom);//这就是IOS透传消息    
        $template->set_apnInfo($apn);
        
        return $template;
    }
    /**
     * 创建一个安卓模板
     * @param string $title     消息标题
     * @param string $content   消息内容
     * @param string $custom    透传内容
     * @param string $logo      android的LOGO设置
     * @param string $logoUrl   android的LOGO链接设置   
     * @return IGtTransmissionTemplate
     */
    private function NotificationTemplateAndroid($title,$content,$custom = "",$logo = "",$logoUrl = ""){
        $template = new \IGtNotificationTemplate();
        $template->set_appId($this->AppID);//应用appid
        $template->set_appkey($this->AppKey);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($custom);//透传内容
        
        $template->set_title($title);      //通知栏标题
        $template->set_text($content);     //通知栏内容
        $template->set_logo($logo);        //通知栏logo
        $template->set_logoURL($logoUrl);   //通知栏logo链接
        //设置响铃 震动效果
        $template->set_isRing(true);                   //是否响铃
        $template->set_isVibrate(true);                //是否震动
        $template->set_isClearable(true);              //通知栏是否可清除
        
        return $template;
    }
     /**
     * 创建一个IOS模板
     * @param string $title     消息标题
     * @param string $content   消息内容
     * @param string $custom    透传内容
     * @param string $sound     IOS音乐设置
     * @param int    $badge     IOS角标设置（即未读消息数量）
     * @return IGtTransmissionTemplate
     */
    private function NotificationTemplateIos($title,$content,$custom = "",$sound = "default",$badge = -1){
        $template =  new IGtTransmissionTemplate();
        $template->set_appId($this->AppID);//应用appid
        $template->set_appkey($this->AppKey);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($custom);//透传内容
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息        
      
        $alertmsg = new \DictionaryAlertMsg();//  必须有。    声明DictionaryAlertMsg的对象alertmsg
        $alertmsg->body=$content;//  必须有。   为body赋值
        $alertmsg->title= $title;//  必须有。   为title赋值

        $apn = new \IGtAPNPayload();// 必须有。   声明IGtAPNPayload的对象apn
        $apn->alertMsg=$alertmsg;// 必须有alertmsg，且alertmsg中一定有title以及和body，因为这就是客户端在通知栏/横幅看到的标题和内容。
        $apn->contentAvailable=0;// 必须为0 
        $apn->sound = $sound;// 铃声
        $apn->badge = $badge;// 角标，可有可无
        //$apn->add_customMsg("payload","payload");
        $apn->add_customMsg("payload",$custom ?: "payload");
        $apn->add_customMsg("custom",$custom);//这就是IOS透传消息    
        $template->set_apnInfo($apn);
        return $template;
    }
}