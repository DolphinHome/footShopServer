<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


/**
 * 插件配置信息
 */
return [
   	['type' => 'text', 'name' => 'ak', 'title' => 'AccessKeyId', 'tips' => ''],
	['type' => 'text', 'name' => 'sk',  'title' => 'AccessKeySecret', 'tips' => ''],
	['type' => 'text', 'name' => 'bucket', 'title' => 'Bucket', 'tips' => '上传的空间名'],
	['type' => 'text', 'name' => 'endpoint', 'title' => 'Endpoint', 'tips' => '如：oss-cn-beijing.aliyuncs.com'],
	['type' => 'text', 'name' => 'domain', 'title' => '绑定域名', 'tips' => '如：http://foodstyle.zzebz.com'],	
    ['type' => 'text', 'name' => 'style', 'title' => '缩略图样式', 'tips' => '如：image/resize,m_lfit,w_100,limit_0/auto-orient,1/quality,q_90'],  
];
