<?php
namespace app\goods\admin;

use service\JsonService;
use service\HttpService;
use think\Controller;
use Think\Db;

/**
 * 产品管理
 * Class StoreProduct
 * @package app\admin\controller\store
 */
class Copytaobao extends Controller
{
    use \app\common\traits\controller\Upload;//继承中间件
    //错误信息
    public $errorInfo = true;
    //产品默认字段
    public $productInfo = [
        'cate_id' => '',
        'store_name' => '',
        'store_info' => '',
        'unit_name' => '件',
        'price' => 0,
        'keyword' => '',
        'ficti' => 0,
        'ot_price' => 0,
        'give_integral' => 0,
        'postage' => 0,
        'cost' => 0,
        'image' => '',
        'slider_image' => '',
        'add_time' => 0,
        'stock' => 0,
        'description' => '',
        'soure_link' => ''
    ];
    //抓取网站主域名
    protected $grabName = [
        'taobao',
        '1688',
        'tmall',
        'jd'
    ];
    //远程下载附件图片分类名称
    protected $AttachmentCategoryName = '远程下载';



    /*
     * 设置错误信息
     * @param string $msg 错误信息
     * */
    public function setErrorInfo($msg = '')
    {
        $this->errorInfo = $msg;
        return false;
    }

    /*
     * 设置字符串字符集
     * @param string $str 需要设置字符集的字符串
     * @return string
     * */
    public function Utf8String($str)
    {
        $encode = mb_detect_encoding($str, array("ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5'));
        if (strtoupper($encode) != 'UTF-8') {
            $str = mb_convert_encoding($str, 'utf-8', $encode);
        }
        return $str;
    }

    /**
     * 获取资源,并解析出对应的商品参数
     * @return json
     */
    public function get_request_contents()
    {
        $data = $this->request->post();
        $link = $data['curl'];
        $cookie = $data['cookie'];
        $this->cookie = $cookie;
        //供测试的电商地址
        //$link = [
            // 'https://item.taobao.com/item.htm?spm=a230r.1.14.174.2e04523clL72Gd&id=602017798613&ns=1&abbucket=14#detail',
            // //'https://detail.tmall.com/item.htm?spm=a220m.1000858.1000725.101.1ea62a68Aff5EE&id=646783526548&skuId=4835339795246&user_id=2231547606&cat_id=2&is_b=1&rn=6c390fe607790c7ba03f5a8ebef8375c',
            // 'https://item.taobao.com/item.htm?spm=a21bo.21814703.201876.1.5af911d9SES9bE&id=10710025777&scm=1007.34127.227518.0&pvid=c3350589-df31-46d5-bb7c-e2fbaf7f4fce',
            // 'https://item.jd.com/100015884722.html',
            // 'https://detail.1688.com/offer/621647013077.html?tracelog=p4p&spm=a26352.13672862.offerlist.1.18685e46Rf3bSP&clickid=b129fd2f12d94f168857c209ea6206b0&sessionid=905f409b7c260d9fb7ad534417dafeb6'
        //];
        
        $url = $this->checkurl($link);
        if ($url === false) {
            return JsonService::fail($this->errorInfo);
        }
        $this->errorInfo = true;

        try {
            //获取url信息
            $pathinfo = pathinfo($url);
            if (!isset($pathinfo['dirname'])) {
                return JsonService::fail('解析URL失败');
            }
            //提取域名
            $parse_url = parse_url($pathinfo['dirname']);
            if (!isset($parse_url['host'])) {
                return JsonService::fail('获取域名失败');
            }
            //获取第一次.出现的位置
            $strLeng = strpos($parse_url['host'], '.') + 1;
            //截取域名中的真实域名不带.com后的
            $funsuffix = substr($parse_url['host'], $strLeng, strrpos($parse_url['host'], '.') - $strLeng);
            if (!in_array($funsuffix, $this->grabName)) {
                return JsonService::fail('您输入的地址不在复制范围内！');
            }

            if ($funsuffix == 'taobao') {
                $header = [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
                    'Referer: https://s.taobao.com/',
                    'Host: item.taobao.com',
                    'Cookie: '.$cookie
                ];
            } elseif($funsuffix == 'tmall'){
                $header = [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
                    'Referer: https://list.tmall.com/',
                    'Host: detail.tmall.com',
                    'Cookie: '.$cookie
                ];
            }elseif($funsuffix == 'jd'){
                $header = [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
                    'Referer: https://search.jd.com/',
                    'Host: item.jd.com',
                    'Cookie: '.$cookie
                ];
            }elseif($funsuffix == '1688'){
                $header = [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
                    'Referer: https://s.1688.com/',
                    'Host: detail.1688.com',
                    'Cookie: '.$cookie
                ];
            }
            $html = $this->curlHttpsGet($url, $header, 30);
            
            if (!$html) {
                return JsonService::fail('商品HTML信息获取失败');
            }
            //除jd为uft-8外，淘宝系都是gbk
            if($funsuffix != 'jd'){
                $html = $this->Utf8String($html);
            }
           
            preg_match('/<title>([^<>]*)<\/title>/', $html, $title);
            //商品标题
            $this->productInfo['store_name'] = isset($title['1']) ? str_replace(['-淘宝网', '-tmall.com天猫', ' - 阿里巴巴', ' ', '-', '【图片价格品牌报价】京东', '京东', '【行情报价价格评测】'], '', trim($title['1'])) : '';
            $this->productInfo['store_info'] = $this->productInfo['store_name'];

            //设拼接设置产品函数
            $funName = "setProductInfo" . ucfirst($funsuffix);
            //执行方法
            if (method_exists($this, $funName)) {
                $this->$funName($html);
            } else {
                return JsonService::fail('设置产品函数不存在');
            }
            if (!$this->productInfo['slider_image']) {
                return JsonService::fail('未能获取到商品信息，请确保商品信息有效！');
            }
            return JsonService::successful($this->productInfo);
        } catch (\Exception $e) {
            return JsonService::fail('系统错误', ['line' => $e->getLine(), 'meass' => $e->getMessage()]);
        }
    }

    /*
     * 淘宝设置产品
     * @param string $html 网页内容
     * */
    public function setProductInfoTaobao($html)
    {
        //获取轮播图
        $images = $this->getTaobaoImg($html);
        $images = array_merge($images);
        $this->productInfo['slider_image'] = isset($images['gaoqing']) ? $images['gaoqing'] : (array)$images;
        $this->productInfo['slider_image'] = array_slice($this->productInfo['slider_image'], 0, 5);
        //获取产品详情请求链接
        $link = $this->getTaobaoDesc($html);
        //descnew.taobao.com
        if (stripos($link,'descnew.taobao.com')) {
            $header = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
                'Host: descnew.taobao.com',
                'Cookie: '.$this->cookie
            ];
            //获取请求内容
            $desc_json = HttpService::getRequest($link,[],$header);
        } else {
            //itemcdn.tmall.com开头的网站
            $desc_json = HttpService::getRequest($link,[],$header);
        }

        //转换字符集
        $desc_json = $this->Utf8String($desc_json);
        //截取掉多余字符
        $desc_json = str_replace('var desc=\'', '', $desc_json);
        $desc_json = str_replace(["\n", "\t", "\r"], '', $desc_json);
        $content = substr($desc_json, 0, -2);
        //$this->productInfo['description'] = $content;
        //获取详情图   
        $description_images = $this->decodedesc($content);
        $this->productInfo['description_images'] = is_array($description_images) ? $description_images : [];
        
        $this->productInfo['image'] = is_array($this->productInfo['slider_image']) && isset($this->productInfo['slider_image'][0]) ? $this->productInfo['slider_image'][0] : '';
        $this->productInfo['image_id'] =  $this->FiletoId($this->productInfo['image']);
        foreach ($this->productInfo['slider_image'] as $v) {
            $this->productInfo['slider_image_id'][] =  $this->FiletoId($v);
        }
    }
    


    /*
     * 天猫设置产品
     * @param string $html 网页内容
     * */
    public function setProductInfoTmall($html)
    {
        //获取轮播图
        $images = $this->getTianMaoImg($html);
        $images = array_merge($images);
        $this->productInfo['slider_image'] = $images;
        $this->productInfo['slider_image'] = array_slice($this->productInfo['slider_image'], 0, 5);
        $this->productInfo['image'] = is_array($this->productInfo['slider_image']) && isset($this->productInfo['slider_image'][0]) ? $this->productInfo['slider_image'][0] : '';
        //获取产品详情请求链接
        $link = $this->getTianMaoDesc($html);
        //获取请求内容
        $desc_json = HttpService::getRequest($link);
        //转换字符集
        $desc_json = $this->Utf8String($desc_json);
        //截取掉多余字符
        $desc_json = str_replace('var desc=\'', '', $desc_json);
        $desc_json = str_replace(["\n", "\t", "\r"], '', $desc_json);
        $content = substr($desc_json, 0, -2);
        //$this->productInfo['description'] = $content;
        //获取详情图
        $description_images = $this->decodedesc($content);
        $this->productInfo['description_images'] = is_array($description_images) ? $description_images : [];
        $this->productInfo['image_id'] =  $this->FiletoId($this->productInfo['image']);
        foreach ($this->productInfo['slider_image'] as $v) {
            $this->productInfo['slider_image_id'][] =  $this->FiletoId($v);
        }
    }

    /*
     * 1688设置产品
     * @param string $html 网页内容
     * */
    public function setProductInfo1688($html)
    {
        //获取轮播图
        $images = $this->get1688Img($html);
        if (isset($images['gaoqing'])) {
            $images['gaoqing'] = array_merge($images['gaoqing']);
            $this->productInfo['slider_image'] = $images['gaoqing'];
        } else {
            $this->productInfo['slider_image'] = $images;
        }
        $this->productInfo['slider_image'] = array_slice($this->productInfo['slider_image'], 0, 5);
        $this->productInfo['image'] = is_array($this->productInfo['slider_image']) && isset($this->productInfo['slider_image'][0]) ? $this->productInfo['slider_image'][0] : '';
        //获取产品详情请求链接
        $link = $this->get1688Desc($html);
        //获取请求内容
        $desc_json = HttpService::getRequest($link);
        //转换字符集
        $desc_json = $this->Utf8String($desc_json);
        //截取掉多余字符
        $desc_json = str_replace('var offer_details=', '', $desc_json);
        $desc_json = str_replace(["\n", "\t", "\r"], '', $desc_json);
        $desc_json = substr($desc_json, 0, -1);
        $descArray = json_decode($desc_json, true);
        if (!isset($descArray['content'])) {
            $descArray['content'] = '';
        }
        //$this->productInfo['description'] = $descArray['content'];
        //获取详情图
        $description_images = $this->decodedesc($descArray['content']);
        $this->productInfo['description_images'] = is_array($description_images) ? $description_images : [];
        $this->productInfo['image_id'] =  $this->FiletoId($this->productInfo['image']);
        foreach ($this->productInfo['slider_image'] as $v) {
            $this->productInfo['slider_image_id'][] =  $this->FiletoId($v);
        }
    }

    /*
     * JD设置产品
     * @param string $html 网页内容
     * */
    public function setProductInfoJd($html)
    {
        //获取产品详情请求链接
        $desc_url = $this->getJdDesc($html);
       
        //获取请求内容
        $desc_json = HttpService::getRequest($desc_url);
        //转换字符集
        //$desc_json = $this->Utf8String($desc_json);
        
        //截取掉多余字符
        // if (substr($desc_json, 0, 8) == 'showdesc') {
        //     $desc_json = str_replace('showdesc', '', $desc_json);
        // }
        // $desc_json = str_replace('data-lazyload=', 'src=', $desc_json);
        $descArray = json_decode($desc_json, true);
       
        if (!$descArray) {
            $descArray = ['content' => ''];
        }
       
        //获取轮播图
        $images = $this->getJdImg($html);
        $images = array_merge($images);
        $this->productInfo['slider_image'] = $images;
        $this->productInfo['image'] = is_array($this->productInfo['slider_image']) ? $this->productInfo['slider_image'][0] : '';
        //$this->productInfo['description'] = $descArray['content'];
        //获取详情图
        $description_images = $this->decodedesc($descArray['content']);
        $this->productInfo['description_images'] = is_array($description_images) ? $description_images : [];
        $this->productInfo['image_id'] =  $this->FiletoId($this->productInfo['image']);
        foreach ($this->productInfo['slider_image'] as $v) {
            $this->productInfo['slider_image_id'][] =  $this->FiletoId($v);
        }
    }


    /*
    * 检查淘宝，天猫，1688的商品链接
    * @return string
    */
    public function checkurl($link)
    {
        $link = strtolower($link);
        if (!$link) {
            return $this->setErrorInfo('请输入链接地址');
        }
        if (substr($link, 0, 4) != 'http') {
            return $this->setErrorInfo('链接地址必须以http开头');
        }
        return $link;
        $arrLine = explode('?', $link);
        if (!count($arrLine)) {
            return $this->setErrorInfo('链接地址有误(ERR:1001)');
        }
        if (!isset($arrLine[1])) {
            if (strpos($link, '1688') !== false && strpos($link, 'offer') !== false) {
                return trim($arrLine[0]);
            } elseif (strpos($link, 'item.jd') !== false) {
                return trim($arrLine[0]);
            } else {
                return $this->setErrorInfo('链接地址有误(ERR:1002)');
            }
        }
        if (strpos($link, '1688') !== false && strpos($link, 'offer') !== false) {
            return trim($arrLine[0]);
        }
        if (strpos($link, 'item.jd') !== false) {
            return trim($arrLine[0]);
        }
        $arrLineValue = explode('&', $arrLine[1]);
        if (!is_array($arrLineValue)) {
            return $this->setErrorInfo('链接地址有误(ERR:1003)');
        }
        if (!strpos(trim($arrLine[0]), 'item.htm')) {
            $this->setErrorInfo('链接地址有误(ERR:1004)');
        }
        //链接参数
        $lastStr = '';
        foreach ($arrLineValue as $k => $v) {
            if (substr(strtolower($v), 0, 3) == 'id=') {
                $lastStr = trim($v);
                break;
            }
        }
        if (!$lastStr) {
            return $this->setErrorInfo('链接地址有误(ERR:1005)');
        }
        return trim($arrLine[0]) . '?' . $lastStr;
    }


    private function FiletoId($f)
    {
        $img = pathinfo($f);
       
        $file_h = get_headers($f, true);
        $wh = getimagesize($f);
        $md5 = md5_file($f);
        $sha1 = sha1_file($f);

        $up_dir = './uploads/';//存放在当前目录的upload文件夹下
        $path = '/uploads/' . $img['basename'];
        $new_file = $up_dir . $img['basename'];
        
        if (stripos($f,'http') !==false) {
            file_put_contents($new_file, file_get_contents($f));
            //使用trait Upload 上传，本地和oss都支持
            $res = $this->uploadPath($new_file);
            if ($res) {
                $data = [
                    'id' => $res['id'],
                    'path' => $this->_getFileUrl($res['path']),
                ];
                    
            }
            //halt($res);
            return $res['id'];
        }
        

        $file_id=Db::name('upload')->where(['md5'=>$md5])->find();
        if ($file_id) {
            return $file_id['id'];
        }
        $sha1_by_file_id=Db::name('upload')->where(['sha1'=>$sha1])->find();
        if ($sha1_by_file_id) {
            return $sha1_by_file_id['id'];
        }
        $file_info = [
            'uid'    => session('admin_auth.uid') ? session('admin_auth.uid') : 0,
            'name'   => $f,
            'mime'   => $file_h['Content-Type'],
            'path'   => $f,
            'ext'    => $img['extension'],
            'size'   => $file_h['Content-Length'],
            'md5'    => $md5,
            'sha1'   => sha1_file($f),
            'thumb'  => $f,
            'module' => 'goods',
            'width'  => $wh[0],
            'height' => $wh[1],
            'create_time' => time(),
        ];
        return Db::name('upload')->insertGetId($file_info);
    }

    //提取商品描述中的所有图片
    public function decodedesc($desc = '')
    {
        $desc = trim($desc);
        if (!$desc) {
            return '';
        }
        preg_match_all('/<img[^>]*?src="([^"]*?)"[^>]*?>/i', $desc, $match);
        if(empty($match[1])) {
            //适配jd新图片规则
            preg_match_all('/<img[^>]*?data-lazyload="([^"]*?)"[^>]*?>/i', $desc, $match);
        }
      
        if (!isset($match[1]) || count($match[1]) <= 0) {
            preg_match_all('/:url(([^"]*?));/i', $desc, $match);
            if (!isset($match[1]) || count($match[1]) <= 0) {
                return $desc;
            }
        } else {
            preg_match_all('/:url(([^"]*?));/i', $desc, $newmatch);
            if (isset($newmatch[1]) && count($newmatch[1]) > 0) {
                $match[1] = array_merge($match[1], $newmatch[1]);
            }
        }
        $match[1] = array_unique($match[1]); //去掉重复
        foreach ($match[1] as $k => &$v) {
            $_tmp_img = str_replace([')', '(', ';'], '', $v);
            $_tmp_img = strpos($_tmp_img, 'http') ? $_tmp_img : 'http:' . $_tmp_img;
            if (strpos($v, '?')) {
                $_tarr = explode('?', $v);
                $_tmp_img = trim($_tarr[0]);
            }
            $_urls = str_replace(['\'', '"'], '', $_tmp_img);
            if ($this->_img_exists($_urls)) {
                $v = $_urls;
            }
        }

        if(count($match[1]) > 0) {
            $imglist = [];
            foreach($match[1] as $f) {
                $imglist[] = get_file_url($this->FiletoId($f));
            }
            return $imglist;
        }
        return $match[1];
    }

    //获取京东商品组图
    public function getJdImg($html = '')
    {
        //获取图片服务器网址
        preg_match('/<img(.*?)id="spec-img"(.*?)data-origin=\"(.*?)\"[^>]*>/', $html, $img);
        if (!isset($img[3])) {
            return '';
        }
        $info = parse_url(trim($img[3]));
        if (!$info['host']) {
            return '';
        }
        if (!$info['path']) {
            return '';
        }
        $_tmparr = explode('/', trim($info['path']));
        $url = 'http://' . $info['host'] . '/' . $_tmparr[1] . '/' . str_replace(['jfs', ' '], '', trim($_tmparr[2]));
        preg_match('/imageList:(.*?)"],/is', $html, $img);
        if (!isset($img[1])) {
            return '';
        }
        $_arr = explode(',', $img[1]);
        foreach ($_arr as $k => &$v) {
            $_str = $url . str_replace(['"', '[', ']', ' '], '', trim($v));
            if (strpos($_str, '?')) {
                $_tarr = explode('?', $_str);
                $_str = trim($_tarr[0]);
            }
            if ($this->_img_exists($_str)) {
                $v = $_str;
            } else {
                unset($_arr[$k]);
            }
        }
        return array_unique($_arr);
    }

    //获取京东商品描述
    public function getJdDesc($html = '')
    {
        preg_match('/,(.*?)desc:([^<>]*)\',/i', $html, $descarr);
        if (!isset($descarr[1]) && !isset($descarr[2])) {
            return '';
        }
        $tmpArr = explode(',', $descarr[2]);
        if (count($tmpArr) > 0) {
            $descarr[2] = trim($tmpArr[0]);
        }
        $replace_arr = ['\'', '\',', ' ', ',', '/*', '*/'];
        if (isset($descarr[2])) {
            $d_url = str_replace($replace_arr, '', $descarr[2]);
            return $this->formatDescUrl(strpos($d_url, 'http') ? $d_url : 'http:' . $d_url);
        }
        $d_url = str_replace($replace_arr, '', $descarr[1]);
        $d_url = $this->formatDescUrl($d_url);
        $d_url = rtrim(rtrim($d_url, "?"), "&");
        return substr($d_url, 0, 4) == 'http' ? $d_url : 'http:' . $d_url;
    }

    //处理下京东商品描述网址
    public function formatDescUrl($url = '')
    {
        if (!$url) {
            return '';
        }
        $url = substr($url, 0, 4) == 'http' ? $url : 'http:' . $url;
        if (!strpos($url, '&')) {
            $_arr = explode('?', $url);
            if (!is_array($_arr) || count($_arr) <= 0) {
                return $url;
            }
            return trim($_arr[0]);
        } else {
            $_arr = explode('&', $url);
        }
        if (!is_array($_arr) || count($_arr) <= 0) {
            return $url;
        }
        unset($_arr[count($_arr) - 1]);
        $new_url = '';
        foreach ($_arr as $k => $v) {
            $new_url .= $v . '&';
        }
        return !$new_url ? $url : $new_url;
    }

    //获取1688商品组图
    public function get1688Img($html = '')
    {
        preg_match('/<ul class=\"nav nav-tabs fd-clr\">(.*?)<\/ul>/is', $html, $img);
        if (!isset($img[0])) {
            return '';
        }
        preg_match_all('/preview":"(.*?)\"\}\'>/is', $img[0], $arrb);
        if (!isset($arrb[1]) || count($arrb[1]) <= 0) {
            return '';
        }
        $thumb = [];
        $gaoqing = [];
        $res = ['thumb' => '', 'gaoqing' => ''];  //缩略图片和高清图片
        foreach ($arrb[1] as $k => $v) {
            $_str = str_replace(['","original":"'], '*', $v);
            $_arr = explode('*', $_str);
            if (is_array($_arr) && isset($_arr[0]) && isset($_arr[1])) {
                if (strpos($_arr[0], '?')) {
                    $_tarr = explode('?', $_arr[0]);
                    $_arr[0] = trim($_tarr[0]);
                }
                if (strpos($_arr[1], '?')) {
                    $_tarr = explode('?', $_arr[1]);
                    $_arr[1] = trim($_tarr[0]);
                }
                if ($this->_img_exists($_arr[0])) {
                    $thumb[] = trim($_arr[0]);
                }
                if ($this->_img_exists($_arr[1])) {
                    $gaoqing[] = trim($_arr[1]);
                }
            }
        }
        $res = ['thumb' => array_unique($thumb), 'gaoqing' => array_unique($gaoqing)];  //缩略图片和高清图片
        return $res;
    }

    //获取1688商品描述
    public function get1688Desc($html = '')
    {
        preg_match('/data-tfs-url="([^<>]*)data-enable="true"/', $html, $descarr);
        if (!isset($descarr[1])) {
            return '';
        }
        return str_replace(['"', ' '], '', $descarr[1]);
    }

    //获取天猫商品组图
    public function getTianMaoImg($html = '')
    {
        $pic_size = '430';
        preg_match('/<img[^>]*id="J_ImgBooth"[^r]*rc=\"([^"]*)\"[^>]*>/', $html, $img);
        if (isset($img[1])) {
            $_arr = explode('x', $img[1]);
            $filename = $_arr[count($_arr) - 1];
            $pic_size = intval(substr($filename, 0, 3));
        }
        preg_match('|<ul id="J_UlThumb" class="tb-thumb tm-clear">(.*)</ul>|isU', $html, $match);
        preg_match_all('/<img src="(.*?)" \//', $match[1], $images);
        if (!isset($images[1])) {
            return '';
        }
        foreach ($images[1] as $k => &$v) {
            $tmp_v = trim($v);
            $_arr = explode('x', $tmp_v);
            $_fname = $_arr[count($_arr) - 1];
            $_size = intval(substr($_fname, 0, 3));
            if (strpos($tmp_v, '://')) {
                $_arr = explode(':', $tmp_v);
                $r_url = trim($_arr[1]);
            } else {
                $r_url = $tmp_v;
            }
            $str = str_replace($_size, $pic_size, $r_url);
            if (strpos($str, '?')) {
                $_tarr = explode('?', $str);
                $str = trim($_tarr[0]);
            }
            $_i_url = strpos($str, 'http') ? $str : 'http:' . $str;
            if ($this->_img_exists($_i_url)) {
                $v = $_i_url;
            } else {
                unset($images[1][$k]);
            }
        }
        return array_unique($images[1]);
    }

    //获取天猫商品描述
    public function getTianMaoDesc($html = '')
    {
        preg_match('/descUrl":"([^<>]*)","httpsDescUrl":"/', $html, $descarr);
        if (!isset($descarr[1])) {
            preg_match('/httpsDescUrl":"([^<>]*)","fetchDcUrl/', $html, $descarr);
            if (!isset($descarr[1])) {
                return '';
            }
        }
        return strpos($descarr[1], 'http') ? $descarr[1] : 'http:' . $descarr[1];
    }

    //获取淘宝商品组图
    public function getTaobaoImg($html = '')
    {
        preg_match('/auctionImages([^<>]*)"]/', $html, $imgarr);
        if (!isset($imgarr[1])) {
            return '';
        }
        $arr = explode(',', $imgarr[1]);
        foreach ($arr as $k => &$v) {
            $str = trim($v);
            $str = str_replace(['"', ' ', '', ':['], '', $str);
            if (strpos($str, '?')) {
                $_tarr = explode('?', $str);
                $str = trim($_tarr[0]);
            }
            $_i_url = strpos($str, 'http') ? $str : 'http:' . $str;
            if ($this->_img_exists($_i_url)) {
                $v = $_i_url;
            } else {
                unset($arr[$k]);
            }
        }
        return array_unique($arr);
    }

    //获取淘宝商品描述
    public function getTaobaoDesc($html = '')
    {
        preg_match('/descUrl([^<>]*)counterApi/', $html, $descarr);
        if (!isset($descarr[1])) {
            return '';
        }
        $arr = explode(':', $descarr[1]);
        $url = [];
        foreach ($arr as $k => $v) {
            if (strpos($v, '//')) {
                $str = str_replace(['\'', ',', ' ', '?//','//',':'], '', $v);
                $url[] = trim($str);
            }
        }
        if ($url) {
            return strpos($url[0], 'http://') ? $url[0] : 'https://' . $url[1];
        } else {
            return '';
        }
    }

    /**
     * GET 请求
     * @param string $url
     */
    public function curl_Get($url = '', $time_out = 25, $header)
    {
        if (!$url) {
            return '';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER,  $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
        $response = curl_exec($ch);
        if ($error = curl_error($ch)) {
            return false;
        }
        curl_close($ch);
        return $response;
        //return mb_convert_encoding($response, 'utf-8', 'GB2312');
    }


    public function curlHttpsGet($url, $header=array(), $timeout=30)
    {
        $curl = curl_init($url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
     
        $response = curl_exec($ch);
     
        if($error=curl_error($ch)){
            die($error);
        }
     
        curl_close($ch);
     
        return $response;

    }
    //检测远程文件是否存在
    public function _img_exists($url = '')
    {
        ini_set("max_execution_time", 0);
        $str = @file_get_contents($url, 0, null, 0, 1);
        if (strlen($str) <= 0) {
            return false;
        }
        if ($str) {
            return true;
        } else {
            return false;
        }
    }

    //获取即将要下载的图片扩展名
    public function getImageExtname($url = '', $ex = 'jpg')
    {
        $_empty = ['file_name' => '', 'ext_name' => $ex];
        if (!$url) {
            return $_empty;
        }
        if (strpos($url, '?')) {
            $_tarr = explode('?', $url);
            $url = trim($_tarr[0]);
        }
        $arr = explode('.', $url);
        if (!is_array($arr) || count($arr) <= 1) {
            return $_empty;
        }
        $ext_name = trim($arr[count($arr) - 1]);
        $ext_name = !$ext_name ? $ex : $ext_name;
        return ['file_name' => md5($url) . '.' . $ext_name, 'ext_name' => $ext_name];
    }

    /*
      $filepath = 绝对路径，末尾有斜杠 /
      $name = 图片文件名
      $maxwidth 定义生成图片的最大宽度（单位：像素）
      $maxheight 生成图片的最大高度（单位：像素）
      $filetype 最终生成的图片类型（.jpg/.png/.gif）
    */
    public function resizeImage($filepath = '', $name = '', $maxwidth = 0, $maxheight = 0)
    {
        $pic_file = $filepath . $name; //图片文件
        $img_info = getimagesize($pic_file); //索引 2 是图像类型的标记：1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，
        if ($img_info[2] == 1) {
            $im = imagecreatefromgif($pic_file); //打开图片
            $filetype = '.gif';
        } elseif ($img_info[2] == 2) {
            $im = imagecreatefromjpeg($pic_file); //打开图片
            $filetype = '.jpg';
        } elseif ($img_info[2] == 3) {
            $im = imagecreatefrompng($pic_file); //打开图片
            $filetype = '.png';
        } else {
            return ['path' => $filepath, 'file' => $name, 'mime' => ''];
        }
        $file_name = md5('_tmp_' . microtime() . '_' . rand(0, 10)) . $filetype;
        $pic_width = imagesx($im);
        $pic_height = imagesy($im);
        $resizewidth_tag = false;
        $resizeheight_tag = false;
        if (($maxwidth && $pic_width > $maxwidth) || ($maxheight && $pic_height > $maxheight)) {
            if ($maxwidth && $pic_width > $maxwidth) {
                $widthratio = $maxwidth / $pic_width;
                $resizewidth_tag = true;
            }
            if ($maxheight && $pic_height > $maxheight) {
                $heightratio = $maxheight / $pic_height;
                $resizeheight_tag = true;
            }
            if ($resizewidth_tag && $resizeheight_tag) {
                if ($widthratio < $heightratio) {
                    $ratio = $widthratio;
                } else {
                    $ratio = $heightratio;
                }
            }
            if ($resizewidth_tag && !$resizeheight_tag) {
                $ratio = $widthratio;
            }
            if ($resizeheight_tag && !$resizewidth_tag) {
                $ratio = $heightratio;
            }
            $newwidth = $pic_width * $ratio;
            $newheight = $pic_height * $ratio;
            if (function_exists("imagecopyresampled")) {
                $newim = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $pic_width, $pic_height);
            } else {
                $newim = imagecreate($newwidth, $newheight);
                imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $pic_width, $pic_height);
            }
            if ($filetype == '.png') {
                imagepng($newim, $filepath . $file_name);
            } elseif ($filetype == '.gif') {
                imagegif($newim, $filepath . $file_name);
            } else {
                imagejpeg($newim, $filepath . $file_name);
            }
            imagedestroy($newim);
        } else {
            if ($filetype == '.png') {
                imagepng($im, $filepath . $file_name);
            } elseif ($filetype == '.gif') {
                imagegif($im, $filepath . $file_name);
            } else {
                imagejpeg($im, $filepath . $file_name);
            }
            imagedestroy($im);
        }
        @unlink($pic_file);
        return ['path' => $filepath, 'file' => $file_name, 'mime' => $img_info['mime']];
    }
}
