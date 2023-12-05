<?php
// +----------------------------------------------------------------------
// |ZBPHP[基于ThinkPHP5.1开发]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2019 http://www.benbenwangluo.com
// +----------------------------------------------------------------------
// | Author jxy [ 415782189@qq.com ]
// +----------------------------------------------------------------------
// | 中犇软件 技术六部 出品
// +----------------------------------------------------------------------
namespace addons\ExpressBird\controller;
use app\common\controller\Common;
use Think\Db;

class Api extends Common{
    
    private $environment=['appId'=>'','appKey'=>'',];
    private $is_online;
    
    public function initialize(){
        $config = addons_config('ExpressBird');
        $this->environment['appId'] = $config['AppID'];
        $this->environment['appKey'] = $config['AppSecret'];
        $this->is_online = $config['is_online'];
    }
    
    /**
     * 物流查询 
     * $OrderCode //平台订单号
     * $ShipperCode //物流公司code
     * $LogisticCode //物流单号
     **/
    public function getOrderTracesByJson($OrderCode,$ShipperCode,$LogisticCode){
    	$requestData= "{'OrderCode':'".$OrderCode."','ShipperCode':'".$ShipperCode."','LogisticCode':'".$LogisticCode."'}";
    	$datas = array(
            'EBusinessID' => $this->environment['appId'],
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $this->environment['appKey']);
        $reqURL = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
    	$result=$this->sendPost($reqURL, $datas);	
    	return $result;
    }
    
    /**
     * 物流信息订阅 
     * $OrderCode //平台订单号
     * $ShipperCode //物流公司code
     * $LogisticCode //物流单号
     * $Sender.Name//发件人姓名
     * $Sender.Mobile//发件人姓名
     * $Sender.ProvinceName//省
     * $Sender.CityName//市
     * $Sender.ExpAreaName//区
     * $Sender.Address //街道
     * $Receiver.Name //收件人姓名
     * $Receiver.Mobile //收件人电话
     * $Receiver.ProvinceName //省
     * $Receiver.CityName //市
     * $Receiver.ExpAreaName //区
     * $Receiver.Address //街道
     **/
    public function orderTracesSubByJson($OrderCode,$ShipperCode,$LogisticCode,$Sender,$Receiver){
        $requestData="{'OrderCode': '".$OrderCode."',".
            "'ShipperCode':'".$ShipperCode."',".
            "'LogisticCode':'".$LogisticCode."',".
            "'Sender':{".
            "'Name':'".$Sender['Name']."','Mobile':'".$Sender['Mobile']."','ProvinceName':'".$Sender['ProvinceName']."','CityName':'".$Sender['CityName']."','ExpAreaName':'".$Sender['ExpAreaName']."','Address':'".$Sender['Address']."'},".
            "'Receiver':{".
            "'Name':'".$Receiver['Name']."','Mobile':'".$Receiver['Mobile']."','ProvinceName':'".$Receiver['ProvinceName']."','CityName':'".$Receiver['CityName']."','ExpAreaName':'".$Receiver['ExpAreaName']."','Address':'".$Receiver['Address']."'},".
            "}";
        $datas = array(
            'EBusinessID' => $this->environment['appId'],
            'RequestType' => '1008',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $this->environment['appKey']);
        if($this->is_online){
            $reqURL = 'http://api.kdniao.com/api/dist';
        }else{
            $reqURL = 'http://testapi.kdniao.com:8081/api/dist';
        }
        $result = $this->sendPost($reqURL, $datas);
        return $result;
    }
    
    /*
     * 获取物流公司信息
     * */
    public function getCompanyCode(){
        $data = Db::name('goods_express_company')->select();
        return $data;
    }
    
    /**
     * Json方式 提交在线下单
     * $eorder["ShipperCode"] //物流公司
     * $eorder["OrderCode"] //平台订单号
     * $eorder["PayType"] //邮费支付方式:1现付,2到付,3月结,4第三方支付
     * $sender["Name"]  //发件人姓名
     * $sender["Mobile"]  //发件人电话
     * $sender["ProvinceName"]  //发件人所在省
     * $sender["CityName"]  //发件人所在市
     * $sender["ExpAreaName"]  //发件人所在区
     * $sender["Address"]  //发件人地址
     * $receiver["Name"]   //收件人
     * $receiver["Mobile"] //收件人电话
     * $receiver["ProvinceName"] //收件人省
     * $receiver["CityName"] //收件人市
     * $receiver["ExpAreaName"] //收件人区
     * $receiver["Address"] //收件人地址
     */
    public function submitOOrder($eorder,$sender,$receiver){
        $eorder["ExpType"] = 1;
        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $commodityOne = [];
        $commodityOne["GoodsName"] = "其他";
        $commodity = [];
        $commodity[] = $commodityOne;
        $eorder["Commodity"] = $commodity;
        $requestData=json_encode($eorder, JSON_UNESCAPED_UNICODE);
        $datas = array(
            'EBusinessID' => $this->environment['appId'],
            'RequestType' => '1001',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $this->environment['appKey']);
        if($this->is_online){
            $reqURL = 'http://api.kdniao.com/api/eorderservice';
        }else{
            $reqURL = 'http://testapi.kdniao.com:8081/api/oorderservice';
        }
        $result = $this->sendPost($reqURL, $datas);
        return $result;
    }
    
    /**
     * 发送请求
     * */
    private function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port'])){
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);  
        return $gets;
    }
    
    /**
     * 签名加密
     * */
    private function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
    

}