<?php
namespace Util;

/**
 * 工具类
 */
class Tools
{
    // 定义错误返回值
    public static $returnCode = [
        // 内部接口统一返回状态码
        '1'  => ['code'=>'1',  'msg'=>'成功'],
        '0'  => ['code'=>'0',  'msg'=>'失败'],
        '-1' => ['code'=>'-1', 'msg'=>'无客服在线'],
        '-2' => ['code'=>'-2', 'msg'=>'没有需要分配客服的会员'],
        '-3' => ['code'=>'-3', 'msg'=>'未设置客服最大服务客户人数'],
        '-4' => ['code'=>'-4', 'msg'=>'当前服务人数较多，请耐心等待'],
        '-5' => ['code'=>'-5', 'msg'=>'接口服务不存在!'],
        '-6' => ['code'=>'-6', 'msg'=>'服务请求参数不能为空!！'],
        '-7'  => ['code'=>'-7',  'msg'=>'您已掉线，请重新登录！'],
        '-8'  => ['code'=>'-8',  'msg'=>'获取用户信息失败，请联系管理员'],
        '-9'  => ['code'=>'-9',  'msg'=>'token验证非法'],
        
    ];
    
    /**
     * 返回业务码
     * @param $code int 业务码
     * @param $res Arr 返回数组信息
     * @param $msg string 返回提示语
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月5日11:09:24
     * @return Arr
     */
    public static function returnErrorCode($code=1, $res=[], $msg='')
    {
        $returnCodeArr = self::$returnCode[$code];
        if(!empty($res)){
            $returnCodeArr['data'] = $res;
        }
        if(!empty($msg)){
            $returnCodeArr['msg'] = $msg;
        }
        
        return $returnCodeArr;
    }

    /**
     * 格式化输出调试日志
     * @param $message string 日志输出说明
     * @param $arr Arr 输出内容
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2020年12月30日12:59:00
     * @return String
     */
    public static function printWriteLog($message='', $arr=[])
    {
        echo PHP_EOL . "------------------------------------{$message}开始----------------------------------------" . PHP_EOL . PHP_EOL;
        print_r($arr);
        echo PHP_EOL . "------------------------------------{$message}结束----------------------------------------" . PHP_EOL . PHP_EOL;
    }

    /**
     * 时间格式化
     * @param $time uintime uni时间戳
     * @author zenghu [ 1427305236@qq.com ]
     * @since 2021年1月14日14:57:59
     * @return String
     */
    public static function dateHandel($time)
    {
        if(!$time){ return false; }

        $fdate = '';
        $d = time() - intval($time);
        $ld = $time - mktime(0, 0, 0, 0, 0, date('Y')); // 得出年
        $md = $time - mktime(0, 0, 0, date('m'), 0, date('Y')); // 得出月
        $byd = $time - mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')); // 前天
        $yd = $time - mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')); // 昨天
        $dd = $time - mktime(0, 0, 0, date('m'), date('d'), date('Y')); // 今天
        $td = $time - mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')); // 明天
        $atd = $time - mktime(0, 0, 0, date('m'), date('d') + 2, date('Y')); // 后天
        if($d == 0){
            $fdate = '刚刚';
        }else{
            switch($d){
                case $d < $atd:
                    $fdate = date('Y年m月d日', $time);
                    break;
                case $d < $td:
                    $fdate = '后天' . date('H:i', $time);
                    break;
                case $d < 0:
                    $fdate = '明天' . date('H:i', $time);
                    break;
                case $d < 60:
                    $fdate = $d . '秒前';
                    break;
                case $d < 3600:
                    $fdate = floor($d / 60) . '分钟前';
                    break;
                case $d < $dd:
                    $fdate = floor($d / 3600) . '小时前';
                    break;
                case $d < $yd:
                    $fdate = '昨天' . date('H:i', $time);
                    break;
                case $d < $byd:
                    $fdate = '前天' . date('H:i', $time);
                    break;
                case $d < $md:
                    $fdate = date('m月d日 H:i', $time);
                    break;
                case $d < $ld:
                    $fdate = date('m月d日', $time);
                    break;
                default:
                    $fdate = date('Y年m月d日', $time);
                    break;
            }
        }

        return $fdate;
    }
    
}
