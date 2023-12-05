/*
Navicat MySQL Data Transfer

Source Server         : locahost
Source Server Version : 50553
Source Host           : 127.0.0.1:3306
Source Database       : zbshop

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2019-12-19 18:19:47
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for lb_goods_integral
-- ----------------------------
DROP TABLE IF EXISTS `lb_goods_integral`;
CREATE TABLE `lb_goods_integral` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品id',
  `name` varchar(120) NOT NULL DEFAULT '' COMMENT '商品名称',
  `cid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `stock` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT '库存数量',
  `integral` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '积分',
  `thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '商品上传原始图',
  `images` varchar(255) NOT NULL DEFAULT '' COMMENT '图册集合，用英文逗号分割',
  `sort` smallint(4) unsigned NOT NULL DEFAULT '50' COMMENT '商品排序',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态1正常2禁用',
  `description` varchar(255) DEFAULT NULL,
  `body` text,
  PRIMARY KEY (`id`),
  KEY `sort_order` (`sort`) USING BTREE,
  KEY `cat_id` (`cid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 COMMENT='商品主表';

-- ----------------------------
-- Table structure for lb_goods_integral_category
-- ----------------------------
DROP TABLE IF EXISTS `lb_goods_integral_category`;
CREATE TABLE `lb_goods_integral_category` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品分类id',
  `name` varchar(90) NOT NULL DEFAULT '' COMMENT '商品分类名称',
  `mobile_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '手机端显示的商品分类名',
  `pid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `thumb` varchar(512) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '分类图片',
  `is_hot` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐为热门分类',
  `typeid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '绑定的规格分类',
  `sort` int(10) NOT NULL DEFAULT '99' COMMENT '排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8 COMMENT='商品分类';

-- ----------------------------
-- Table structure for lb_order_integral_list
-- ----------------------------
DROP TABLE IF EXISTS `lb_order_integral_list`;
CREATE TABLE `lb_order_integral_list` (
  `order_sn` varchar(100) NOT NULL COMMENT '订单ID',
  `goods_id` int(11) NOT NULL COMMENT '商品ID',
  `goods_name` varchar(100) NOT NULL COMMENT '商品名称',
  `goods_integral` decimal(19,2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '购买数量',
  `total_integral` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品总价',
  `goods_thumb` int(11) NOT NULL DEFAULT '0' COMMENT '商品图片',
  KEY `UK_order_goods_id` (`goods_id`) USING BTREE,
  KEY `UK_order_goods_order_sn` (`order_sn`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=289 COMMENT='订单商品列表';


INSERT INTO `lb_admin_api_list` (`apiName`, `hash`, `module`, `checkSign`, `needLogin`, `status`, `method`, `info`, `isTest`, `returnStr`, `create_time`, `group`, `readme`)
VALUES
	('integral/index', '5df9cd20a86ca', 'integral', 0, 0, 1, 1, '积分商品列表', 1, '', 1576652129, 0, '积分商品列表'),
	('integral/detail', '5df9d0783a4d0', 'integral', 0, 0, 1, 1, '积分商品详情', 1, '', 1576653120, 0, ''),
	('integral/get_order_info', '5df9dbfdc5252', 'integral', 0, 1, 1, 1, '获取积分商品订单信息', 1, '', 1576655937, 0, ''),
	('integral/category', '5dfad40a36179', 'integral', 0, 0, 1, 1, '积分商品分类', 1, '', 1576719498, 0, ''),
	('integral/log', '5dfb3918154c0', 'integral', 0, 1, 1, 1, '积分已兑换商品列表', 1, '', 1576745442, 0, ''),
	('integral/rule', '5dfb4da24ce6a', 'integral', 0, 0, 1, 1, '积分规则说明', 1, '', 1576750525, 0, '');

INSERT INTO `lb_admin_api_fields` (`fieldName`, `hash`, `dataType`, `default`, `isMust`, `range`, `info`, `type`, `showName`, `pid`, `sort`)
VALUES
	('page', '5df9cd20a86ca', 1, '1', 0, '', 'page 【页码】', 0, 'page', 0, 100),
	('size', '5df9cd20a86ca', 1, '1', 0, '', 'size【每页条数】', 0, 'size', 0, 100),
	('cid', '5df9cd20a86ca', 1, '', 0, '', 'cid【分类id。类型：int(11) unsigned】', 0, 'cid', 0, 100),
	('user_id', '5df9cd20a86ca', 1, '', 0, '', '当前登录用户的id', 0, 'user_id', 0, 100),
	('goods_id', '5df9d0783a4d0', 1, '', 1, '', 'goods_id【商品ID】', 0, 'goods_id', 0, 100),
	('goods_id', '5df9dbfdc5252', 1, '', 1, '', 'goods_id【商品ID】', 0, 'goods_id', 0, 100),
	('number', '5df9dbfdc5252', 1, '1', 1, '', 'number【获取数量】', 0, 'number', 0, 100),
	('address_id', '5df9dbfdc5252', 1, '', 1, '', 'address_id【地址ID】', 0, 'address_id', 0, 100),
	('pid', '5dfad40a36179', 1, '0', 0, '', 'pid【上级分类id。类型：smallint(5) unsigned】传递此参数代表只获取此分类的下级分类列表', 0, 'pid', 0, 100),
	('max_level', '5dfad40a36179', 1, '3', 0, '', 'max_level【返回的最大层级。类型：int(11)】默认3层，建议1-3层', 0, 'max_level', 0, 100),
	('page', '5dfb3918154c0', 1, '1', 0, '', 'page【页码】', 0, 'page', 0, 100),
	('size', '5dfb3918154c0', 1, '1', 0, '', 'size【每页条数】', 0, 'size', 0, 100);
