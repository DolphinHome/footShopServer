<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\common\model;

use think\Model;

/**
 * 用于保存各个API的字段规则
 * @package app\common\model
 */
class Apilist extends Model
{
    protected $table = '__ADMIN_API_LIST__';

    protected $autoWriteTimestamp = true;

    //只读字段,一旦写入，就无法更改。
    protected $readonly = ['hash'];

    //关联模型
    public function api_fields()
    {
        return $this->hasOne('ApiFields', 'hash', 'hash');
    }

    public function getMethodTurnAttr($value, $data)
    {	//请求方式 method 字段 [获取器]
        $turnArr = [0=>lang('不限'), 1=>'POST',2=>'GET'];
        return $turnArr[$data['method']];
    }

    public function getAccessTokenTurnAttr($value, $data)
    {	//是否需要认证AccessToken accessToken 字段 [获取器]
        $turnArr = [0=>lang('不验证').'Token', 1=>lang('验证').'Token'];
        return $turnArr[$data['accessToken']];
    }

    public function getNeedLoginTurnAttr($value, $data)
    {	//是否需要认证用户token needLogin 字段 [获取器]
        $turnArr = [0=>lang('不验证登录'), 1=>lang('验证登录')];
        return $turnArr[$data['needLogin']];
    }

    public function getIsTestTurnAttr($value, $data)
    {	//是否是测试模式 isTest 字段 [获取器]
        $turnArr = [-1=>'MOCK'.lang('数据'),0=>lang('测试模式'), 1=>lang('生产模式')];
        return $turnArr[$data['isTest']];
    }
    public function getStatusTurnAttr($value, $data)
    {	//接口状态 Status 字段 [获取器]
        $turnArr = [0=>lang('接口被禁用'), 1=>lang('正常访问')];
        return $turnArr[$data['status']];
    }
  
    /**
     * 获取缓存APIINFO
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @param string $hash 接口HASH
     * @return array|null
     */
    public static function getCacheInfo($hash)
    {
        static $info = null;
        $apiInfo = cache('apiInfo_' . $hash);
        if (empty($apiInfo)  && $info === null) {
            $apiInfo = self::get(['hash' => $hash, 'status' => 1]);
            cache('apiInfo_' . $hash, $apiInfo, 7200); //接口信息
            $info = 1;
        }
        return   $apiInfo;
    }
}
