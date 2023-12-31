<?php
/**
 * run with command
 * php start.php start
 */
error_reporting(0);
ini_set('display_errors', 'on');
use Workerman\Worker;

if(strpos(strtolower(PHP_OS), 'win') === 0)
{
    exit("start.php not support windows, please use start_for_win.bat\n");
}

// 检查扩展
if(!extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

if(!extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

// 标记是全局启动
define('GLOBAL_START', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/workerman/globaldata/src/Server.php';
require_once __DIR__ . '/vendor/workerman/globaldata/src/Client.php';
require_once __DIR__ . '/vendor/Util/Tools.php';
require_once __DIR__ . '/vendor/Util/ApiList.php';

// 加载所有Applications/*/start.php，以便启动所有服务
foreach(glob(__DIR__.'/Applications/*/start*.php') as $start_file)
{
    require_once $start_file;
}

// 全局共享组件
$worker = new GlobalData\Server('127.0.0.1', 32320);

// 运行所有服务
Worker::runAll();
