<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\model;

use think\Model as ThinkModel;
/**
 * 站内信阅读状态
 * Class SystemMessageRead
 * @package app\user\model
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @since 2019/4/9 16:23
 */
class SystemMessageRead extends ThinkModel  {
    
    //设置表名
     protected $table = '__OPERATION_SYSTEM_MESSAGE_READ__';
    // 自动写入时间戳
     protected $autoWriteTimestamp = true;
     
     public static function setread($user_id,$sys_msg_id){  
         return self::create(['sys_msg_id'=>$sys_msg_id,'user_id'=>$user_id,'status'=>1]); 
     }
     
     public static function getread($user_id,$sys_msg_id){         
         return self::get(['sys_msg_id'=>$sys_msg_id,'user_id'=>$user_id]); 
     }
     
     public static function delread($aid){         
         return self::where("aid",$aid)->update(['status'=>2]); 
     }
     
     public static function delMsg($sys_msg_id){
        return self::where("sys_msg_id",$sys_msg_id)->delete(); 
     }
}
