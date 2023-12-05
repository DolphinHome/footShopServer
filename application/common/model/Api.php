<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\common\model;

use Firebase\JWT\JWT;
use service\ApiReturn;
use think\Validate;
use think\facade\Request;
use app\user\model\User;

/**
 * Api请求统一调度
 * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
 * @after 晓风<215628355@qq.com> 2020年10月14日09:14:13
 */
class Api
{
    //接口信息
    public static $apiInfo = null;
    //接口参数
    public static $param = null;
    //用户信息
    public static $user = [];

    /**
     * 头部 公共参数
     * @param array $header 头部参数数组
     * @param string $alg 声明签名算法为SHA256
     * @return string $typ 声明类型为jwt
     */
    private static $header = [
        'alg' => 'HS256', //生成signature的算法 //声明签名算法为SHA256
        'typ' => 'JWT'  //声明类型为jwt
    ];

    // 初始化处理
    public static function init($hash)
    {
        $apiInfo = Apilist::getCacheInfo($hash);

        if (empty($apiInfo)) {
            return ApiReturn::r('-1'); //hash参数无效
        }

        self::$apiInfo = $apiInfo;
        if ($apiInfo['status'] < 1) {
            return ApiReturn::r('-230', '', lang('接口已禁用')); // 参数错误
        }

        $header = Request::header();
        $data = self::getFormData($apiInfo["method"]);
        $check = self::checkData($hash, $data);
        if ($check) {
            return $check;
        }

        // MOCK数据模式下开始模拟数据
        if ($apiInfo['isTest'] == -1) {
            return 66;
        }
        //验证签名
        if ($apiInfo['checkSign'] && $apiInfo['isTest']) {
            $ret = self::checkSign($header, $data);
            if ($ret) {
                return $ret;
            }
        }
        //验证TOKEN
        if ($apiInfo['needLogin'] && !$header['user-token']) {
            return ApiReturn::r('-201', [], lang('您还没有登录，请先登录'));
        }
       
        //有TOKEN就获取user
        if ($apiInfo['needLogin'] && $header['user-token']) {
            $ret = self::checkUserToken($header['user-token']);
            if ($ret) {
                return $ret;
            }
        }
        return 0;
    }

    /**
     * 直接访问地址初始化
     * @return int|json
     * @author 似水星辰
     */
    public static function initpage()
    {
        $data = self::getFormData();
        self::$param = $data;
        $header = Request::header();
        //有TOKEN就获取user
        if ($header['user-token']) {
            $ret = self::checkUserToken($header['user-token']);
            if ($ret) {
                return $ret;
            }
        }
        return 0;
    }

    /**
     * 验签SIGN
     * @param $header 用户提交的header
     * @param $formdata 用户提交的表单数据
     * @return int|json
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public static function checkSign($header, $formdata)
    {
        $sign['appid'] = $header['appid'] ?? null;
        $sign['signaturenonce'] = $header['signaturenonce'] ?? null;
        $sign['timestamp'] = $header['timestamp'] ?? null;
        if (!$sign['appid']) {
            return ApiReturn::r('-101', '', lang('缺少') . 'AppId' . lang('参数'));
        }
        if (!$sign['signaturenonce']) {
            return ApiReturn::r('-102', '', lang('缺少') . 'SignatureNonce' . lang('参数'));
        }
        if (!$header['signature']) {
            return ApiReturn::r('-103', '', lang('缺少') . 'Signature' . lang('参数'));
        }
        if (!$sign['timestamp']) {
            return ApiReturn::r('-104', '', lang('缺少') . 'Timestamp' . lang('参数'));
        }

        $time = time();
        $max = $sign['timestamp'] - 60;
        if ($max > $time) {
            return ApiReturn::r('-105', '', lang('请求时间不正确'));
        }

        $min = $sign['timestamp'] + 60;
        if ($min < $time) {
            return ApiReturn::r('-106', '', lang('请求时间已过期'));
        }

        $app = Apiapp::getCache($sign['appid']);
        if (!$app || !$app['app_secret']) {
            return ApiReturn::r('-107', '', 'APPID' . lang('未授权'));
        }
        $sign["appsecret"] = $app['app_secret'];
        $sign = array_merge($sign, $formdata);
        ksort($sign);
        $string = [];
        foreach ($sign as $key => $val) {
            $string[] = $key . '=' . $val;
        }
        $signdata = implode("&", $string);

        $checkSign = sha1($signdata);

        if ($checkSign === $header['signature']) {
            //验证通过返回0即可,我偷下懒
            return 0;
        }

        unset($sign['appsecret']);
        return ApiReturn::r('-108', $sign, lang('签名不正确'));
    }


    /**
     * 获得用户提交的参数
     * @param int $method
     * @return type
     * @author 晓风<215628355@qq.com>
     */
    protected static function getFormData($method = 0)
    {
        $request = Request::instance();
        if ($method == 1) { // post获取数据
            return $request->post();
        }
        if ($method == 2) { // get获取数据
            return $request->get();
        }
        $_method = strtoupper(request()->method()); // 用当前的请求方式 获取数据
        switch ($_method) {
            case 'GET':
                return $request->get();
            case 'POST':
                return $request->post();
            case 'DELETE':
                return $request->delete();
            case 'PUT':
                return $request->put();
            default :
                break;
        }
        return [];
    }

    /**
     * 验证参数并存储data
     * @param string $hash
     * @param array $data
     * @return int
     */
    protected static function checkData($hash, $data)
    {
        $rule = ApiFields::getCacheFields($hash, 0); //获取数据库的 请求字段
        //如果是请求参数，需要去除pid等于0的，并且请求参数如果是数组或对象形式，也要转为字符串去处理
        foreach ($rule as $key => $value) {
            if($value['pid'] != 0){
                unset($rule[$key]);
            }
            if($value['type'] == 0){
                if($value['dataType'] == 8 || $value['dataType'] == 9){
                    $value['dataType'] = 2;
                }
            }
        }
        $newRule = ApiFields::cacheBuildValidateRule($rule);
        if ($newRule) {
            $validate = new Validate($newRule);
            if (!$validate->check($data)) {  //验证
                return ApiReturn::r('-900', [], $validate->getError()); // 参数错误
            }
        }
        $newData = [];
        foreach ($rule as $item) {
            if ($data[$item['fieldName']] == '') {
                if ($item['default'] != '') {
                    $newData[$item['fieldName']] = $item['default'];
                }
            } else {
                $newData[$item['fieldName']] = $data[$item['fieldName']];
            }
        }
        self::$param = $newData;
        return 0;
    }

    /**
     * 获取会员信息
     * @param int $id
     * @param string $cache
     * @return mixed
     * @author 晓风<215628355@qq.com>
     */
    public static function getUserInfo($id, $cache = 3600)
    {
        return User::alias('u')
            ->join('user_info i', 'u.id=i.user_id', 'left')
            ->where('u.id', $id)
            ->cache('userinfo_' . $id, $cache)
            ->field('i.*')
            ->field('u.password,u.wechat_id,i.user_id,i.update_time', true)
            ->find();
    }

    /**
     * 清除会员缓存
     * @param int $id
     * @author 晓风<215628355@qq.com>
     */
    public static function clearUserCache($id)
    {
        cache('userinfo_' . $id, null);
    }


    /**
     * 验证User-token 并存入user
     * @param string $userToken
     * @return type
     * @author 晓风<215628355@qq.com>
     */
    protected static function checkUserToken(string $userToken)
    {
        $token = cache('user_token_' . $userToken);
        if (!$token) {
            return ApiReturn::r('-201', [], lang('您还没有登录，请先登录'));
        }
        $ret = self::checkToken($token);
        $param = $ret['params'];
        if ($param->id) {
            $userinfo = self::getUserInfo($param->id);
            if (!$userinfo) {
                return ApiReturn::r('-203', [], lang('登录信息失效，请重新登录'));
            }
            if ($userinfo["status"] != 1) {
                return ApiReturn::r('-203', [], lang('你的账户已禁用'));
            }
            if ($userinfo["is_delete"] == 1) {
                return ApiReturn::r('-203', [], lang('你的账户已被删除'));
            }
            $userinfo['head_img'] = get_file_url($userinfo['head_img']);
            self::$user = $userinfo;
        } else {
            $res = json_decode($ret, true);
            switch ($res['code']) {
                case 101:
                    return ApiReturn::r('-202', [], lang('签名不正确，请重新弄登录'));
                case 102:
                    return ApiReturn::r('-202', [], lang('签名未到开始使用时间'));
                case 103:
                    return ApiReturn::r('-203', [], lang('登录信息失效，请重新登录'));
                default:
                    return ApiReturn::r('-202', [], lang('未知错误，请联系管理员'));
            }
        }
        return 0;
    }

    /**
     * 对外方法，获得user里的参数
     * @param string $field
     * @return array|string
     * @author 晓风<215628355@qq.com>
     */
    public static function getUser($field = "")
    {
        return $field ? (self::$user[$field] ?? null) : self::$user;
    }


    /**
     * 创建 token
     * @param array $data 必填 自定义参数数组
     * @param integer $exp_time 必填 token过期时间 单位:秒 例子：7200=2小时
     * @param string $scopes 选填 token标识，请求接口的token
     * @return string
     */
    public static function createToken($data = "", $exp_time = 0, $scopes = "")
    {

        //JWT标准规定的声明，但不是必须填写的；
        //iss: jwt签发者
        //sub: jwt所面向的用户
        //aud: 接收jwt的一方
        //exp: jwt的过期时间，过期时间必须要大于签发时间
        //nbf: 定义在什么时间之前，某个时间点后才能访问
        //iat: jwt的签发时间
        //jti: jwt的唯一身份标识，主要用来作为一次性token。
        //公用信息
        try {
            $key = config('zbphp.user_token_key');
            $time = time(); //当前时间
            if ($scopes) {
                $token['scopes'] = $scopes; //token标识，请求接口的token
            }
            if (!$exp_time) {
                $exp_time = 604800;//默认=7天过期,暂时没启用refresh刷新token
            }

            if ($data) {
                $token['data'] = $data; //自定义参数
            }
            $token = [
                'iss' => 'ZBPHP', //签发者 可选
                'iat' => $time, //签发时间
                'nbf' => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                'scopes' => $scopes, //token标识，请求接口的token
                'exp' => $time + $exp_time, //token过期时间,这里设置2个小时
                'params' => $data
            ];

            $json = JWT::encode($token, $key);
            //Header("HTTP/1.1 201 Created");
            //return json_encode($json); //返回给客户端token信息
            return $json; //返回给客户端token信息

        } catch (\Firebase\JWT\ExpiredException $e) {  //签名不正确
            $returndata['code'] = "104";//101=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return json_encode($returndata); //返回信息
        } catch (Exception $e) {  //其他错误
            $returndata['code'] = "199";//199=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return json_encode($returndata); //返回信息
        }
    }

    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     * @param string $jwt 需要验证的token
     * @return string $msg 返回消息
     */
    public static function checkToken($jwt)
    {
        $key = config('zbphp.user_token_key');

        try {
            JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($jwt, $key, ['HS256']); //HS256方式，这里要和签发的时候对应
            $arr = (array)$decoded;

            return $arr; //返回信息

        } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
            //echo "2,";
            //echo $e->getMessage();
            $returndata['code'] = "101";//101=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return json_encode($returndata); //返回信息
        } catch (\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
            //echo "3,";
            //echo $e->getMessage();
            $returndata['code'] = "102";//102=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return json_encode($returndata); //返回信息
        } catch (\Firebase\JWT\ExpiredException $e) {  // token过期
            //echo "4,";
            //echo $e->getMessage();
            $returndata['code'] = "103";//103=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return json_encode($returndata); //返回信息
        } catch (\Exception $e) {  //其他错误
            //echo "5,";
            //echo $e->getMessage();
            $returndata['code'] = "199";//199=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return json_encode($returndata); //返回信息
        }
        //Firebase定义了多个 throw new，我们可以捕获多个catch来定义问题，catch加入自己的业务，比如token过期可以用当前Token刷新一个新Token
    }

    /**
     * 根据TOKEN获取用户信息
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2020/9/16 20:59
     */
    public static function get_user_info()
    {

        $header = Request::header();
        $token = cache('user_token_' . $header['user-token']);
        $ret = self::checkToken($token);
        $param = $ret['params'];
        if ($param->id) {
            $userinfo = User::alias('u')->join('user_info i', 'u.id=i.user_id', 'left')
                ->where('u.id', $param->id)->cache('userinfo_' . $param->id, '3600')
                ->field('i.*')->field('u.password,u.wechat_id,i.user_id,i.update_time', true)->find();
            $userinfo['head_img'] = get_file_url($userinfo['head_img']);
            return $userinfo;
        } else {
            return $ret;
        }
    }
}
