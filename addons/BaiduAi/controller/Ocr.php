<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace plugins\BaiduAi\controller;

use app\common\controller\Common;
require_once(dirname(dirname(__FILE__))."/sdk/AipOcr.php");

/**
 * 图像识别控制器
 * @author 晓风<215628355@qq.com>
 * @package plugins\Sms\controller
 */
class Ocr extends Common
{
	public $client;

	public function __construct(){
		$config = addons_config('BaiduAi');
		$this->client = new \AipOcr($config['app_id'], $config['api_key'], $config['secret_key']);
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
			$error_msg = ($this->getErrorMsg($ret['error_code'])?:"") . ";" . $ret['error_msg'];
			throw new \Exception($error_msg ,$ret['error_code']);
		}else{
			return $ret;
		}
	}

	public function getErrorMsg($errorCode){
		$errors = [
			'4'      => '集群超限额',
			'14'     => 'IAM鉴权失败，建议用户参照文档自查生成sign的方式是否正确，或换用控制台中ak sk的方式调用',
			'17'     => '每天流量超限额',
			'18'     => 'QPS超限额',
			'19'     => '请求总量超限额',
			'100'    => '无效参数',
			'110'    => 'Access Token失效',
			'111'    => 'Access token过期',
			'282000' => '服务器内部错误，如果您使用的是高精度接口，报这个错误码的原因可能是您上传的图片中文字过多，识别超时导致的，建议您对图片进行切割后再识别，其他情况请再次请求， 如果持续出现此类错误，请通过QQ群（631977213）或工单联系技术支持团队',
			'216100' => '请求中包含非法参数，请检查后重新尝试',
			'216101' => '缺少必须的参数，请检查参数是否有遗漏',
			'216102' => '请求了不支持的服务，请检查调用的url',
			'216103' => '请求中某些参数过长，请检查后重新尝试',
			'216110' => 'appid不存在，请重新核对信息是否为后台应用列表中的appid',
			'216200' => '图片为空，请检查后重新尝试',
			'216201' => '上传的图片格式错误，现阶段我们支持的图片格式为：PNG、JPG、JPEG、BMP，请进行转码或更换图片',
			'216202' => '上传的图片大小错误，现阶段我们支持的图片大小为：base64编码后小于4M，分辨率不高于4096*4096，请重新上传图片',
			'216630' => '识别错误，请再次请求，如果持续出现此类错误，请通过QQ群（631977213）或工单联系技术支持团队。',
			'216631' => '识别银行卡错误，出现此问题的原因一般为：您上传的图片非银行卡正面，上传了异形卡的图片或上传的银行卡正品图片不完整',
			'216633' => '识别身份证错误，出现此问题的原因一般为：您上传了非身份证图片或您上传的身份证图片不完整',
			'216634' => '检测错误，请再次请求，如果持续出现此类错误，请通过QQ群（631977213）或工单联系技术支持团队。',
			'282003' => '请求参数缺失',
			'282005' => '处理批量任务时发生部分或全部错误，请根据具体错误码排查',
			'282006' => '批量任务处理数量超出限制，请将任务数量减少到10或10以下',
			'282110' => 'URL参数不存在，请核对URL后再次提交',
			'282111' => 'URL格式非法，请检查url格式是否符合相应接口的入参要求',
			'282112' => 'url下载超时，请检查url对应的图床/图片无法下载或链路状况不好，您可以重新尝试一下，如果多次尝试后仍不行，建议更换图片地址',
			'282113' => 'URL返回无效参数',
			'282114' => 'URL长度超过1024字节或为0',
			'282808' => 'request id xxxxx 不存在',
			'282809' => '返回结果请求错误（不属于excel或json）',
			'282810' => '图像识别错误'
		];
		return $errors[$errorCode]?: '';
	}
}
