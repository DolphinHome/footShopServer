# 使用说明

## 插件作用
封装百度ai接口的调用,统一返回结果

## 接口的额度

|API|状态|请求地址|调用量限制|QPS限制|
| --- | --- | --- | --- | --- |
|通用文字识别|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/general_basic |500次/天免费|不保证并发|
|通用文字识别（含位置信息版）|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/general |500次/天免费|不保证并发|
|通用文字识别（含生僻字版）|待开通付费|https://aip.baidubce.com/rest/2.0/ocr/v1/general_enhanced |--|--|
|通用文字识别（高精度版）|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/accurate_basic |50次/天免费|不保证并发|
|通用文字识别（高精度含位置版）|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/accurate |50次/天免费|不保证并发|
|网络图片文字识别|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/webimage |500次/天免费|不保证并发|
|身份证识别|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/idcard |500次/天免费|不保证并发|
|银行卡识别|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/bankcard |500次/天免费|不保证并发|
|驾驶证识别|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/driving_license |200次/天免费|不保证并发|
|行驶证识别|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/vehicle_license |200次/天免费|不保证并发|
|营业执照识别|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/business_license |200次/天免费|不保证并发|
|车牌识别|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/license_plate |200次/天免费|不保证并发|
|表格文字识别-提交请求|免费使用|https://aip.baidubce.com/rest/2.0/solution/v1/form_ocr/request |50次/天免费|不保证并发|
|表格文字识别-获取结果|免费使用|https://aip.baidubce.com/rest/2.0/solution/v1/form_ocr/get_request_result |无限制|不保证并发|
|通用票据识别|免费使用|https://aip.baidubce.com/rest/2.0/ocr/v1/receipt |200次/天免费|不保证并发|
|自定义模版文字识别|免费使用|https://aip.baidubce.com/rest/2.0/solution/v1/iocr/recognise |500次/天免费|不保证并发|

## 接口使用

插件设置

[插件配置](http://ww4.sinaimg.cn/large/0060lm7Tly1fpemg01tszj316o0bh0tf.jpg)

参考 http://ai.baidu.com/docs

addons_action('BaiduAi', '插件控制器如Ocr', '方法idcard', [数组参数]);

如：
~~~
$img = file_get_contents('http://weiwoju.oss-cn-hangzhou.aliyuncs.com/uploads/2017-09-18/59bfa7a3b01c9.jpg');
$ret = action_action('BaiduAi', 'Ocr', 'idcard', [$img, 'front']);
dump($ret);
~~~

严谨的加try catch

## 注意事项
服务器将项目目录里plugins/sdk/lib写入权限加上


## 反馈
http://ai.baidu.com/docs# 底部的提交反馈

在百度云控制台内[提交工单](http://ticket.bce.baidu.com/#/ticket/create)，咨询问题类型请选择人工智能服务；

加入开发者QQ群：313787791

## 错误码
控制器里已经对错误码进行了转换


## 维护
晓风：215628355