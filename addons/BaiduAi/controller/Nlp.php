<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace addons\BaiduAi\controller;

use app\common\controller\Common;
require_once(dirname(dirname(__FILE__))."/sdk/AipNlp.php");

/**
 * 自然语言处理
 * @author 晓风<215628355@qq.com>
 * @package plugins\Sms\controller
 */
class Nlp extends Common
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
			'4'   => '集群超限额',
			'14'  => 'IAM鉴权失败，建议用户参照文档自查生成sign的方式是否正确，或换用控制台中ak sk的方式调用',
			'17'  => '每天流量超限额',
			'18'  => 'QPS超限额',
			'19'  => '请求总量超限额',
			'100' => '无效参数',
			'110' => 'Access Token失效',
			'111' => 'Access token过期',

			'282000' => '内部错误',
			'282002' => '编码错误，请使用GBK编码',
			'282004' => '请求中包含非法参数，请检查后重新尝试',

			'282130' => '当前查询无结果返回，出现此问题的原因一般为：参数配置存在问题，请检查后重新尝试',
			'282131' => '输入长度超限，请查看文档说明',
			'282133' => '接口参数缺失',
			'282300' => 'word不在算法词典中',
			'282301' => 'word_1提交的词汇暂未收录，无法比对相似度',
			'282302' => 'word_2提交的词汇暂未收录，无法比对相似度',
			'282303' => 'word_1和word_2暂未收录，无法比对相似度',
		];
		return $errors[$errorCode]?: '';
	}
}
