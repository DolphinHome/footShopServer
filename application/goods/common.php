<?php
// +----------------------------------------------------------------------
// | LwwanPHP
// +----------------------------------------------------------------------
// | 版权所有 似水星辰 [ 2630481389@qq.com ]
// +----------------------------------------------------------------------
// | 星辰工作室 http://www.sitejs.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
/**
 * 日志记录
 * @param $logDescription 日志说明
 * @param $logContents 日志内容
 * @param $file 创建的目录文件
 * @author zenghu [1427305236@qq.com] 2020年8月12日10:17:32
 */
function writeLog($logDescription='',$logContents='',$file='error')
{
    // 文件目录
    $path = LOG_PATH . $file . '/' . date('Ym') . '/';
    if(!is_dir($path)) {
        mkdir($path, 0777,1);
    }

    // 文件内容
    $logContents = is_string($logContents) ? $logContents : json_encode($logContents);
    $time = date('Y-m-d H:i:s') . lang('操作');
    $content = $time . PHP_EOL . $logDescription . ':' . $logContents . PHP_EOL . PHP_EOL;

    file_put_contents($path. date('d') . '.log', $content, FILE_APPEND);
}