<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\operation\controller;

use think\worker\Server;

/**
 * 客服聊天回调控制器
 * @package app\user\admin
 */
class Worker extends Server
{
    // protected $socket = 'Websocket://127.0.0.1:8282';
    protected $socket = 'Websocket://127.0.0.1:32321';
    protected $option = [
        'name' => 'socket',
        'count' => 4
    ];

    public function onMessage($connection, $data)
    {
        $connection->send(json_encode($data));
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {

    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {

    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {

    }
}