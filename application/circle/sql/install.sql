DROP TABLE IF EXISTS `lb_circle`;
CREATE TABLE `lb_circle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '内容',
  `comments` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论次数',
  `likes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `is_report` tinyint(1) NOT NULL DEFAULT '0' COMMENT '举报 1是',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='动态表';

DROP TABLE IF EXISTS `lb_circle_comment`;
CREATE TABLE `lb_circle_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `circle_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '动态id',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `content` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') CHARACTER SET utf8 NOT NULL DEFAULT 'normal' COMMENT '状态',
  `touid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  PRIMARY KEY (`id`),
  KEY `circle_id` (`circle_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8  COMMENT='动态评论表';

DROP TABLE IF EXISTS `lb_circle_like`;
CREATE TABLE `lb_circle_like` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `circle_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '动态id',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `is_like` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1点赞 2取消',
  `createtime` int(10) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `circle_id` (`circle_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='动态点赞表';

INSERT INTO `lb_admin_api_list` (`apiName`, `hash`, `module`, `checkSign`, `needLogin`, `status`, `method`, `info`, `isTest`, `returnStr`, `create_time`, `group`, `readme`, `mock`)
VALUES
	('circle/addReport', '5f2a85dc67aba', 'circle', 0, 1, 1, 1, '动态--举报', 1, '', 1596622502, 0, '', 0),
	('circle/listComment', '5f2a29b2020eb', 'circle', 0, 0, 1, 2, '动态--评论列表', 1, '{\r\n    \"code\": \"1\",\r\n    \"msg\": \"请求成功\",\r\n    \"data\": {\r\n        \"total\": 1,\r\n        \"per_page\": \"1\",\r\n        \"current_page\": 1,\r\n        \"last_page\": 1,\r\n        \"data\": [\r\n            {\r\n                \"id\": 1,//评论id\r\n                \"content\": \"哈哈\",//内容\r\n                \"createtime\": \"2020-08-05 11:18\",//时间\r\n                \"user_nickname\": \"哈哈\",//昵称\r\n                \"head_img\": \"http://chaoy.com/uploads/images/20200805/c77caf625e38fdf9427b110bfa315a7d.jpg\"//头像\r\n            }\r\n        ]\r\n    },\r\n    \"time\": 1596599347,\r\n    \"user\": \"\"\r\n}', 1596598736, 0, '', 0),
	('circle/addLike', '5f2a2629daae3', 'circle', 0, 1, 1, 1, '动态--点赞/取消点赞', 1, '', 1596597941, 0, '', 0),
	('circle/addComment', '5f2a21192964a', 'circle', 0, 1, 1, 1, '动态--发布评论', 1, '', 1596596850, 0, '', 0),
	('circle/listCircle', '5f2a1acb59a8a', 'circle', 0, 0, 1, 2, '动态--全部数据', 1, '{\r\n    \"code\": \"1\",\r\n    \"msg\": \"请求成功\",\r\n    \"data\": {\r\n        \"total\": 5,\r\n        \"per_page\": \"6\",\r\n        \"current_page\": 1,\r\n        \"last_page\": 1,\r\n        \"data\": [\r\n            {\r\n                \"id\": 6,//动态id\r\n                \"user_id\": 1,//用户id\r\n                \"content\": \"哈哈\",//内容\r\n                \"image\": [//图片\r\n                    \"http://chaoyy.com/uploads/images/chaoyang/20200806/feb0fb6cea10f875101f113e44dc2113.png\"\r\n                ],\r\n                \"createtime\": \"2020-08-17 18:16\",//发布时间\r\n                \"comments\": 4,//评论数\r\n                \"likes\": 0,//点赞数\r\n                \"head_img\": \"http://chaoyy.com/uploads/images/chaoyang/20200817/55a43dbd9b8c5f77c72ca635bbab4238.jpg\",//发布者头像\r\n                \"user_nickname\": \"haha\",//发布者昵称\r\n                \"is_follow\": 0,//1关注\r\n                \"is_like\": 0,//1点赞\r\n                \"comment\": [\r\n                    {\r\n                        \"id\": 7,//评论id\r\n                        \"content\": \"内容\",//内容\r\n                        \"createtime\": \"2020-08-17 18:17\",\r\n                        \"user_nickname\": \"haha\",//用户昵称(第一个)\r\n                        \"reply_nickname\": \"\"//用户昵称(第二个)\r\n                    }\r\n                ]\r\n            }\r\n        ]\r\n    },\r\n    \"time\": 1599211993,\r\n    \"user\": {\r\n        \"id\": 1,\r\n        \"user_nickname\": \"haha\",\r\n        \"head_img\": \"http://chaoyy.com/uploads/images/chaoyang/20200817/55a43dbd9b8c5f77c72ca635bbab4238.jpg\",\r\n        \"sex\": 1,\r\n        \"user_type\": 0,\r\n        \"user_level\": 0,\r\n        \"status\": 1\r\n    }\r\n}', 1596594941, 0, '', 0),
	('circle/delCircle', '5f2a1920bbb4a', 'circle', 0, 1, 1, 1, '动态--删除', 1, '', 1596594511, 0, '', 0),
	('circle/myCircle', '5f2a150562ce3', 'circle', 0, 1, 1, 2, '动态--我的发布', 1, '{\r\n    \"code\": \"1\",\r\n    \"msg\": \"请求成功\",\r\n    \"data\": {\r\n        \"total\": 2,\r\n        \"per_page\": \"2\",\r\n        \"current_page\": 1,\r\n        \"last_page\": 1,\r\n        \"data\": [\r\n            {\r\n                \"id\": 2,//id\r\n                \"content\": \"哈哈\",//内容\r\n                \"image\": [],//图片\r\n                \"createtime\": \"2020-08-05 10:09\"//时间\r\n            },\r\n            {\r\n                \"id\": 1,\r\n                \"content\": \"哈哈\",\r\n                \"image\": [\r\n                    \"http://chaoy.com/uploads/images/20200805/c77caf625e38fdf9427b110bfa315a7d.jpg\",\r\n                    \"http://chaoy.com/uploads/images/20200805/442fbf8dd00dc952a93490a9097d4e77.jpg\"\r\n                ],\r\n                \"createtime\": \"2020-08-05 10:08\"\r\n            }\r\n        ]\r\n    },\r\n    \"time\": 1596594220,\r\n    \"user\": {\r\n        \"id\": 1,\r\n        \"user_nickname\": \"用户94059\",\r\n        \"head_img\": \"http://chaoy.com/static/admin/images/none.png\",\r\n        \"sex\": 0,\r\n        \"user_type\": 0,\r\n        \"user_level\": 0,\r\n        \"status\": 1\r\n    }\r\n}', 1596593468, 0, '', 0),
	('circle/addCircle', '5f293ca82eaad', 'circle', 0, 1, 1, 1, '动态--发布', 1, '', 1596538056, 0, '', 0);


INSERT INTO `lb_admin_api_fields` (`fieldName`, `hash`, `dataType`, `default`, `isMust`, `range`, `info`, `type`, `showName`, `pid`, `sort`, `mock`)
VALUES
	('content', '5f293ca82eaad', 2, '', 1, '', '内容', 0, 'content', 0, 100, ''),
	('image', '5f293ca82eaad', 2, '', 0, '', '图片id 多个用,隔开', 0, 'image', 0, 100, ''),
	('pageSize', '5f2a150562ce3', 1, '', 0, '', '每页条数', 0, 'pageSize', 0, 100, ''),
	('id', '5f2a1920bbb4a', 1, '', 1, '', 'id', 0, 'id', 0, 100, ''),
	('pageSize', '5f2a1acb59a8a', 1, '', 0, '', '每页条数', 0, 'pageSize', 0, 100, ''),
	('content', '5f2a21192964a', 2, '', 1, '', '内容', 0, 'content', 0, 100, ''),
	('id', '5f2a21192964a', 1, '', 1, '', '动态id', 0, 'id', 0, 100, ''),
	('pid', '5f2a21192964a', 1, '0', 1, '', '评论id', 0, 'pid', 0, 100, ''),
	('id', '5f2a2629daae3', 1, '', 1, '', '动态id', 0, 'id', 0, 100, ''),
	('id', '5f2a29b2020eb', 1, '', 1, '', '动态id', 0, 'id', 0, 100, ''),
	('pageSize', '5f2a29b2020eb', 1, '', 0, '', '每页条数', 0, 'pageSize', 0, 100, ''),
	('id', '5f2a85dc67aba', 1, '', 1, '', '动态id', 0, 'id', 0, 100, '');


