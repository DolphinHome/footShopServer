<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace addons\BaiduAi\controller;

use app\common\controller\Common;
require_once(dirname(dirname(__FILE__))."/sdk/AipFace.php");

/**
 * 图像识别控制器
 * @author 晓风<215628355@qq.com>
 * @package plugins\Sms\controller
 */
class Face extends Common
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
			$error_msg = $this->getErrorMsg($ret['error_code'])?:$ret['error_msg'];
			exception($error_msg);
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
			'216015' => '模块关闭',
			'216100' => '请求中包含非法参数，请检查后重新尝试',
			'216101' => '缺少必须的参数，请检查参数是否有遗漏',
			'216102' => '请求了不支持的服务，请检查调用的url',
			'216103' => '请求中某些参数过长，请检查后重新尝试',
			'216110' => 'appid不存在，请重新核对信息是否为后台应用列表中的appid',
			'216111' => 'userid信息非法，请检查对应的参数',
			'216200' => '图片为空或者base64解码错误',
			'216201' => '上传的图片格式错误，现阶段我们支持的图片格式为：PNG、JPG、JPEG、BMP，请进行转码或更换图片',
			'216202' => '上传的图片大小错误，现阶段我们支持的图片大小为：base64编码后小于4M，分辨率不高于4096*4096，请重新上传图片',
			'216300' => '数据库异常，少量发生时重试即可',
			'216400' => '后端识别服务异常，可以根据具体msg查看错误原因',
			'216401' => '内部错误',
			'216402' => '未找到人脸，请检查图片是否含有人脸',
			'216500' => '未知错误',
			'216611' => '用户不存在，请确认该用户是否注册或注册已经生效(需要已经注册超过5s）',
			'216613' => '删除用户图片记录失败，重试即可',
			'216614' => '两两比对中图片数少于2张，无法比较',
			'216615' => '服务处理该图片失败，发生后重试即可',
			'216616' => '图片已存在',
			'216617' => '新增用户图片失败',
			'216618' => '组内用户为空，确认该group是否存在或已经生效(需要已经注册超过5s)',
			'216631' => '本次请求添加的用户数量超限',
		];
		return $errors[$errorCode]?: '';
	}
}
