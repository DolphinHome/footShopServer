<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace addons\BaiduAi\controller;

use app\common\controller\Common;
require_once(dirname(dirname(__FILE__))."/sdk/AipSpeech.php");

/**
 * 语音
 * @author 晓风<215628355@qq.com>
 * @package plugins\Sms\controller
 */
class Speech extends Common
{
	public $client;

	public function __construct(){
		$config = addons_config('BaiduAi');
		$this->client = new \AipSpeech($config['app_id'], $config['api_key'], $config['secret_key']);
	}

	public function setConnectionTimeoutInMillis($ms){
		$this->client->setConnectionTimeoutInMillis($ms);
	}

	public function setSocketTimeoutInMillis($ms){
		$this->client->setSocketTimeoutInMillis($ms);
	}

	public function __call($method, $args){
		$ret = call_user_func_array([$this->client, $method], $args);
		if(isset($ret['error_code']) && $ret['error_code'] != 0){
			$error_msg = $this->getErrorMsg($ret['error_code'])?:$ret['error_msg'];
			exception($error_msg);
		}else{
			return $ret;
		}
	}

	public function getErrorMsg($errorCode){
		$errors = [
			'500'  => '不支持的输入',
			'501'  => '输入参数不正确',
			'502'  => 'token验证失败',
			'503'  => '合成后端错误',
			'3300' => '输入参数不正确', // 请仔细核对文档及参照demo，核对输入参数
			'3301' => '音频质量过差', //请上传清晰的音频
			'3302' => '鉴权失败', // token字段校验失败。请使用正确的API_KEY 和 SECRET_KEY生成
			'3303' => '语音服务器后端问题', // 请将api返回结果反馈至论坛或者QQ群
			'3304' => '用户的请求QPS超限', // 请降低识别api请求频率 （qps以appId计算，移动端如果共用则累计）
			'3305' => '用户的日pv（日请求量）超限', // 请“申请提高配额”，如果暂未通过，请降低日请求量
			'3307' => '语音服务器后端识别出错问题', // 目前请确保16000的采样率音频时长低于30s，8000的采样率音频时长低于60s。如果仍有问题，请将api返回结果反馈至论坛或者QQ群
			'3308' => '音频过长', // 音频时长不超过60s，请将音频时长截取为60s以下
			'3309' => '音频数据问题', // 服务端无法将音频转为pcm格式，可能是长度问题，音频格式问题等。 请将输入的音频时长截取为60s以下，并核对下音频的编码，是否是8K或者16K， 16bits，单声道。
			'3310' => '输入的音频文件过大', // 语音文件共有3种输入方式： json 里的speech 参数（base64后）； 直接post 二进制数据，及callback参数里url。 分别对应三种情况：json超过10M；直接post的语音文件超过10M；callback里回调url的音频文件超过10M
			'3311' => '采样率rate参数不在选项里', // 目前rate参数仅提供8000,16000两种，填写4000即会有此错误
			'3312' => '音频格式format参数不在选项里', // 目前格式仅仅支持pcm，wav或amr，如填写mp3即会有此错误
		];
		return $errors[$errorCode]?: '';
	}
}
