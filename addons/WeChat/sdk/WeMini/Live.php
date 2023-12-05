<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2018 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

namespace WeMini;

use service\HttpService;
use WeChat\Contracts\BasicWeChat;

/**
 * 小程序直播
 * Class Soter
 * @package WeMini
 */
class Live extends BasicWeChat
{
    /**
     * 获取直播间列表
     * @param array $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getliveinfo($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/getliveinfo?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取直播间列表
     * @param array $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createlive($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/room/create?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * 删除直播间
     * @param array $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function deleteroom($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/room/deleteroom?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * 编辑直播间
     * @param array $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function editroom($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/room/editroom?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * 往指定直播间导入已入库的商品
     */
    public function room_add_goods($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/room/addgoods?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * 直播间导入的商品上下架
     */
    public function room_goods_onsale($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/onsale?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * 删除直播间商品
     */
    public function room_goods_delete($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/deleteInRoom?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * 推送直播间商品
     */
    public function room_goods_push($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/push?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * 获取直播间成员
     */
    public function role_list($data)
    {
        
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/role/getrolelist?access_token=ACCESS_TOKEN&role='.$data['role'].'&offset='.$data['offset'].'&limit='.$data['limit'].'&keyword='.$data['keyword'];
       
        return $this->callGetApi($url, $data, true);
    }


    

    public function getWxAccessToken()
    {
        return $this->getAccessToken();
    }

    /**
     * Notes: 添加直播间商品并提交审核
     * User: chenchen
     * Date: 2021/7/6
     * Time: 11:00
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function add_goods($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/add?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * Notes: 撤回直播商品的提审申请
     * User: chenchen
     * Date: 2021/7/6
     * Time: 11:12
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function goods_reset_audit($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/resetaudit?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * Notes: 已撤回提审的商品再次发起提审申请
     * User: chenchen
     * Date: 2021/7/6
     * Time: 11:18
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function goods_audit($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/audit?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * Notes: 删除直播间商品
     * User: chenchen
     * Date: 2021/7/6
     * Time: 11:20
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function del_goods($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/delete?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }


    /**
     * Notes: 更新直播间商品
     * User: chenchen
     * Date: 2021/7/6
     * Time: 11:22
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function update_goods($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/update?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * Notes: 获取直播间商品的信息与审核状态
     * User: chenchen
     * Date: 2021/7/6
     * Time: 11:28
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function get_goods_warehouse($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/getgoodswarehouse?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true, ["headers" => ["Content-type: application/json;charset='utf-8'", "Accept: application/json", "Cache-Control: no-cache", "Pragma: no-cache"]]);
    }

    /**
     * Notes: 获取直播间商品列表
     * User: chenchen
     * Date: 2021/7/6
     * Time: 11:31
     * @param $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function get_goods_approved($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/getapproved?access_token=ACCESS_TOKEN' . '&offset=' . $data['offset'] . '&limit=' . $data['limit'] . '&status=' . $data['status'];
        return $this->callGetApi($url);
    }




}