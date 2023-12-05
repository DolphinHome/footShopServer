<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\api\controller;

use app\common\model\Api;
use app\common\model\Apilist;
use think\Controller;
use service\ApiReturn;
use service\Tree;

// API基础控制器

class Base extends Controller
{
    // 返回字段名称 数组
    public $fname;

    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin:*');
        $hash = input('hash');
        $data = \app\common\model\ApiFields::getCacheFields($hash, 1);
        $this->fname = [];
        foreach ($data as $v) {
            $this->fname[$v['fieldName']] = [
                'isMust' => $v['isMust'],
                'default' => $v['default']
            ];
        }
    }

    /**
     * API入口，修改验证规则机制
     * @param string $hash
     * @return action
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function iniApi($version, $hash)
    {
        $ret = Api::init($hash);

        // 0为正常输出
        if ($ret == 0) {
            ApiReturn::$user = Api::$user;

            $api_type = Apilist::where(['hash' => $hash, 'status' => 1])->value("type");
            if ($api_type == 1) {
                //模块接口
                return model_action(Api::$apiInfo['apiName'], [Api::$param, Api::$user], 'controller\\' . $version);

            } elseif ($api_type == 2) {
                //插件接口

            } else {
                //普通接口
                return action(Api::$apiInfo['apiName'], [Api::$param, Api::$user], 'controller\\' . $version);
            }
        }
        // 66为mock数据模式
        if ($ret == 66) {
            $data = \app\common\model\ApiFields::getCacheFields($hash, 1)->toArray();
            $data1 = Tree::config(['title' => 'fieldName'])->toLayer($data);
            $fname = [];
            //最多支持3层递归
            foreach ($data1 as $k => $v) {
                if ($v['child']) {
                    foreach ($v['child'] as $vv) {
                        if ($vv['child']) {
                            foreach ($vv['child'] as $vvv) {
                                $fname[$v['fieldName']][$vv['fieldName']][$vvv['fieldName']] = $vvv['mock'];
                            }
                        } else {
                            $fname[$v['fieldName']][$vv['fieldName']] = $vv['mock'];
                        }
                    }
                } else {
                    $fname[$v['fieldName']] = $v['mock'];
                }
            }

            ApiReturn::$user = [
                "id" => 1,
                "user_nickname" => lang('似水星辰'),
                "head_img" => "http://127.0.0.1:201/uploads/images/20200110/53e55d734e897b1ea07d5f582d248613.jpg",
                "sex" => 0,
                "user_type" => 1,
                "user_level" => 0,
                "status" => 1
            ];

            $apiInfo = Apilist::getCacheInfo($hash);
            if ($apiInfo['mock'] == 0) {
                return ApiReturn::r('1', $fname, lang('请求成功'));
            }

            $datas = [
                "total" => 2,
                "per_page" => 15,
                "current_page" => 1,
                "last_page" => 1,
                'data' => [$fname, $fname]
            ];
            return ApiReturn::r('1', $datas, lang('请求成功'));

        }

        return $ret;

    }

    /**
     * 转发直接访问地址
     * @param $version
     * @param $controller
     * @param $function
     * @author 似水星辰
     * @date 2021/6/15 下午8:14
     */
    public function iniPage($version, $controller, $function){
        $ret = Api::initpage();
        // 0为正常输出
        if ($ret == 0) {
            ApiReturn::$user = Api::$user;
            return action($controller.'/'.$function, [Api::$param, Api::$user], 'controller\\' . $version);
        }
    }

    /**
     * 数据过滤转换
     * 对FNAME进行了重写，所有字段若未定义按照默认值填入
     * @param $data
     * @param null $whiteList
     * @return array
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function filter($data, $whiteList = null)
    {
        $whiteList = $whiteList === null ? $this->fname : $whiteList;
        $newData = array();
        foreach ($whiteList as $key => $val) {
            if (is_array($val)) {
                //若不是非必填且该字段未定义，则忽略
                if (!$val['isMust'] && !isset($data[$key])) {
                    continue;
                }
                $newData[$key] = $data[$key] ?? $val['default'];//检查字段，若不存在则写入默认值
            } else {
                $newData[$val] = $data[$val] ?? '';
            }
        }
        return $newData;
    }

}
