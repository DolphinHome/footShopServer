DROP TABLE IF EXISTS `lb_goods`;
CREATE TABLE `lb_goods` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品id',
  `name` varchar(120) NOT NULL DEFAULT '' COMMENT '商品名称',
  `cid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `ecid` int(11) DEFAULT '0' COMMENT '扩展分类id',
  `sn` varchar(60) NOT NULL DEFAULT '' COMMENT '商品编号',
  `adslogan` varchar(255) NOT NULL DEFAULT '' COMMENT '广告语',
  `click` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击数',
  `brand_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '品牌id',
  `stock` smallint(5) unsigned NOT NULL DEFAULT '10' COMMENT '库存数量',
  `comment_count` smallint(5) DEFAULT '0' COMMENT '商品评论数',
  `market_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场价',
  `shop_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本店价',
  `member_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '会员价',
  `cost_price` decimal(10,2) DEFAULT '0.00' COMMENT '商品成本价',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '商品关键词',
  `thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '商品上传原始图',
  `images` varchar(255) NOT NULL DEFAULT '' COMMENT '图册集合，用英文逗号分割',
  `collect_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '收藏量',
  `is_sale` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否上架',
  `is_shipping` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否包邮0否1是',
  `sort` smallint(4) unsigned NOT NULL DEFAULT '50' COMMENT '商品排序',
  `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐',
  `is_boutique` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否精品',
  `is_new` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否新品',
  `is_hot` tinyint(1) DEFAULT '0' COMMENT '是否热卖',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  `goods_type` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '商品所属类型id，取值表goods_type的cat_id',
  `give_integral` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '购买商品赠送积分',
  `suppliers_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '供货商ID',
  `sales_sum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品销量',
  `sales_num_new` int(11) DEFAULT '0' COMMENT '销量',
  `commission` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '佣金用于分销分成',
  `freight_template_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '运费模板ID',
  `is_spec` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启规格和属性',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态1正常2禁用',
  `freight_price` decimal(10,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '固定运费',
  `video` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '视频封面',
  `is_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除0否1是',
  `spectypeid` int(10) NOT NULL DEFAULT '0' COMMENT '规格属性表id',
  `weight` int(20) DEFAULT '0' COMMENT '商品重量',
  `amount_condition` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '满足多少金额包邮条件',
  `share_award_money` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '分销奖励余额',
  `discounts` decimal(10,2) unsigned DEFAULT '0.00' COMMENT 'APP购置回馈额度',
  `give_vip_time` tinyint(10) unsigned DEFAULT '0' COMMENT '赠送VIP时长,单位为月数',
  `empirical` int(1) unsigned DEFAULT '0' COMMENT '成长值',
  `sender_id` int(20) unsigned DEFAULT '0' COMMENT '供应商ID,关联lb_goods_express_sender表主键',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sn_repeat` (`sn`) USING BTREE,
  KEY `brand_id` (`brand_id`) USING BTREE,
  KEY `sort_order` (`sort`) USING BTREE,
  KEY `cat_id` (`cid`,`ecid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='商品主表';

DROP TABLE IF EXISTS `lb_goods_activity`;
CREATE TABLE `lb_goods_activity` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` int(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `name` varchar(256) NOT NULL COMMENT '分类名称',
  `slogan` varchar(255) DEFAULT NULL COMMENT '标语',
  `icon` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类图标',
  `cid` varchar(256) NOT NULL DEFAULT '0' COMMENT '关联商品分类',
  `type` tinyint(1) DEFAULT '1' COMMENT '活动类型(1:秒杀活动)',
  `show_position` varchar(255) DEFAULT NULL,
  `sdate` int(11) unsigned DEFAULT '0' COMMENT '开始日期',
  `edate` int(11) unsigned DEFAULT '0' COMMENT '结束日期',
  `presell_stime` int(11) DEFAULT '0' COMMENT '预售付尾款开始时间',
  `presell_etime` int(11) DEFAULT '0' COMMENT '预售付尾款结束时间',
  `background` int(11) unsigned DEFAULT '0' COMMENT '背景图',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 COMMENT='活动分类';

DROP TABLE IF EXISTS `lb_goods_activity_details`;
CREATE TABLE `lb_goods_activity_details` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `stock` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '库存数量',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `name` varchar(255) DEFAULT NULL COMMENT '商品名称',
  `sku_id` int(11) NOT NULL DEFAULT '0' COMMENT '规格ID',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '允许的购买初始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '允许的购买结束时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0:禁用;1:启用',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `activity_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联goods_activity主键',
  `activity_price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '活动价格',
  `join_number` int(10) unsigned DEFAULT '0' COMMENT '参团人数',
  `unlimited` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '限制时间0不限制全天有效1限制s时间段有效',
  `limit` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '限买件数',
  `member_activity_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '会员活动价',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=300 DEFAULT CHARSET=utf8 COMMENT='活动商品表';

DROP TABLE IF EXISTS `lb_goods_body`;
CREATE TABLE `lb_goods_body` (
  `goods_id` int(11) unsigned NOT NULL COMMENT '商品id',
  `description` text NOT NULL COMMENT '商品简介',
  `body` text NOT NULL COMMENT '商品详情',
  `mbody` text COMMENT '手机端商品详情',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`goods_id`),
  KEY `goods_id` (`goods_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品详情';

DROP TABLE IF EXISTS `lb_goods_brand`;
CREATE TABLE `lb_goods_brand` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` int(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `name` varchar(256) NOT NULL DEFAULT '' COMMENT '品牌名称',
  `url` varchar(256) NOT NULL DEFAULT '' COMMENT '网址',
  `logo` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'LOGO',
  `cid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '绑定分类',
  `description` varchar(256) NOT NULL DEFAULT '' COMMENT '简介',
  `sort` int(11) unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='品牌表';

DROP TABLE IF EXISTS `lb_goods_category`;
CREATE TABLE `lb_goods_category` (
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
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8 COMMENT='商品分类';

DROP TABLE IF EXISTS `lb_goods_sku`;
CREATE TABLE `lb_goods_sku` (
  `sku_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '规格商品id',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id',
  `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '规格键名',
  `specs` varchar(1000) NOT NULL DEFAULT '' COMMENT '规格内容组合',
  `key_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '规格键名中文',
  `shop_price` decimal(10,2) unsigned NOT NULL COMMENT '价格',
  `market_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场价',
  `member_price` decimal(10,2) unsigned NOT NULL COMMENT '会员专享价格',
  `cost_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本价',
  `stock` int(11) unsigned NOT NULL DEFAULT '10' COMMENT '库存数量',
  `sku_sn` varchar(255) NOT NULL DEFAULT '' COMMENT 'sku货号',
  `spec_img` varchar(255) NOT NULL DEFAULT '0' COMMENT '规格商品主图',
  `prom_id` int(10) NOT NULL DEFAULT '0' COMMENT '活动id',
  `prom_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '参加活动类型',
  `commission` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '佣金用于分销分成',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sales_num` int(11) NOT NULL DEFAULT '0' COMMENT '销量',
  `sku_weight` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品重量',
  PRIMARY KEY (`sku_id`),
  UNIQUE KEY `key` (`key`,`goods_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=325 DEFAULT CHARSET=utf8 COMMENT='商品子表，存储SKU库存价格等';

DROP TABLE IF EXISTS `lb_goods_type`;
CREATE TABLE `lb_goods_type` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id自增',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '类型名称',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `cid` int(20) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 COMMENT='商品类型';

DROP TABLE IF EXISTS `lb_goods_type_attr`;
CREATE TABLE `lb_goods_type_attr` (
  `goods_attr_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品属性id自增',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `attr_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '属性id',
  `value` text NOT NULL COMMENT '属性值',
  `price` varchar(255) NOT NULL DEFAULT '' COMMENT '属性价格',
  PRIMARY KEY (`goods_attr_id`),
  KEY `goods_id` (`goods_id`) USING BTREE,
  KEY `attr_id` (`attr_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8 COMMENT='商品-类型属性子表';

DROP TABLE IF EXISTS `lb_goods_type_attribute`;
CREATE TABLE `lb_goods_type_attribute` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '属性id',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '属性名称',
  `typeid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '属性分类id',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否显示0不显示1显示',
  `value` varchar(255) NOT NULL COMMENT '可选值列表',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '50' COMMENT '属性排序',
  PRIMARY KEY (`id`),
  KEY `cat_id` (`typeid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COMMENT='商品类型属性名';

DROP TABLE IF EXISTS `lb_goods_type_spec`;
CREATE TABLE `lb_goods_type_spec` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '规格表',
  `typeid` int(11) NOT NULL DEFAULT '0' COMMENT '规格类型',
  `name` varchar(55) NOT NULL DEFAULT '' COMMENT '规格名称',
  `sort` int(11) unsigned NOT NULL DEFAULT '50' COMMENT '排序',
  `is_upload_image` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否可上传规格图.0否，1是',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8 COMMENT='商品规格表';

DROP TABLE IF EXISTS `lb_goods_type_spec_image`;
CREATE TABLE `lb_goods_type_spec_image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `spec_image_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '规格项id',
  `thumb` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品规格图片',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `specimg` (`goods_id`,`spec_image_id`) USING BTREE COMMENT '每个商品的每个规格只能有一张图片'
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=latin1 COMMENT='商品规格图片表';

DROP TABLE IF EXISTS `lb_goods_type_spec_item`;
CREATE TABLE `lb_goods_type_spec_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '规格项id',
  `specid` int(11) DEFAULT NULL COMMENT '规格id',
  `item` varchar(54) DEFAULT NULL COMMENT '规格项',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8 COMMENT='商品类型规格值表';

DROP TABLE IF EXISTS `lb_goods_comment`;
CREATE TABLE `lb_goods_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '评论id',
  `order_sn` varchar(255) NOT NULL DEFAULT '' COMMENT '订单号',
  `goods_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id值',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id值',
  `star` varchar(10) NOT NULL DEFAULT '' COMMENT '评论星级',
  `content` tinytext NOT NULL COMMENT '评价内容',
  `thumb` varchar(50) NOT NULL DEFAULT '' COMMENT '评价图片',
  `create_time` int(10) NOT NULL COMMENT '评价时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0=已删除,1=正常',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '1匿名0显示',
  `sku_id` varchar(255) NOT NULL COMMENT '商品规格',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='评价表';

DROP TABLE IF EXISTS `lb_goods_cart`;
CREATE TABLE `lb_goods_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '购物车id',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '买家id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `goods_name` varchar(200) NOT NULL COMMENT '商品名称',
  `sku_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品的skuid',
  `sku_name` varchar(200) NOT NULL DEFAULT '' COMMENT '商品的sku名称',
  `shop_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `num` smallint(5) NOT NULL DEFAULT '1' COMMENT '购买商品数量',
  `goods_thumb` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品图片',
  `is_choose` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否选择',
  PRIMARY KEY (`id`),
  KEY `member_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=1170 COMMENT='购物车表';

DROP TABLE IF EXISTS `lb_goods_label`;
CREATE TABLE `lb_goods_label` (
  `label_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) DEFAULT NULL COMMENT '商品ID',
  `name` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`label_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8 COMMENT='商品的自定义小标签';

DROP TABLE IF EXISTS `lb_goods_express_sender`;
CREATE TABLE `lb_goods_express_sender` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL COMMENT '发件人',
  `phone` varchar(100) DEFAULT NULL COMMENT '电话',
  `province` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `area` varchar(50) DEFAULT NULL,
  `address` varchar(50) DEFAULT NULL,
  `pay_type` tinyint(1) unsigned DEFAULT '1' COMMENT '1:现付;2:到付;3:月结;4:第三方支付',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名',
  `password` varchar(50) DEFAULT NULL COMMENT '密码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `lb_goods_express_company`;
CREATE TABLE `lb_goods_express_company` (
  `aid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'aid',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` int(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `name` varchar(256) NOT NULL DEFAULT '' COMMENT '公司名称',
  `express_no` varchar(256) NOT NULL DEFAULT '' COMMENT '公司编号',
  `tel` varchar(256) NOT NULL DEFAULT '' COMMENT '联系电话',
  `logo` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司LOGO',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_default` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '是否设置默认',
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='快递公司';

DROP TABLE IF EXISTS `lb_goods_freight`;
CREATE TABLE `lb_goods_freight` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '模板id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '模板名称',
  `method` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '计费方式(1按重量 2按件数)',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序方式(数字越小越靠前)',
  `shop_id` int(11) NOT NULL DEFAULT '0' COMMENT '店铺ID',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '物流公司ID',
  `is_default` int(11) NOT NULL DEFAULT '0' COMMENT '是否是默认模板',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `lb_goods_freight_rule`;
CREATE TABLE `lb_goods_freight_rule` (
  `rule_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '规则id',
  `freight_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '配送模板id',
  `region` text NOT NULL COMMENT '可配送区域(城市id集)',
  `first` double unsigned NOT NULL DEFAULT '0' COMMENT '首件(个)/首重(Kg)',
  `first_fee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '运费(元)',
  `additional` double unsigned NOT NULL DEFAULT '0' COMMENT '续件/续重',
  `additional_fee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '续费(元)',
  `create_time` int(11) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `lb_order`;
CREATE TABLE `lb_order` (
  `aid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'aid',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '状态;-1:取消;0:待付款;1:已付款;2:已发货;3:已完成;4:已评价',
  `order_sn` varchar(256) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员id',
  `order_type` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单类型(3:普通商品4：积分商品5:拼团订单6:秒杀订单7:预售订单9:折扣订单11:会员限购订单)',
  `cost_price_total` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单成本价格',
  `order_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `payable_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '应付金额',
  `real_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `real_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pay_status` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态',
  `pay_type` varchar(256) NOT NULL DEFAULT '' COMMENT '支付渠道',
  `coupon_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠券ID',
  `coupon_money` decimal(10,2) DEFAULT NULL COMMENT '优惠券金额',
  `product_id` int(11) unsigned DEFAULT '0' COMMENT '充值规则id',
  `transaction_id` varchar(256) DEFAULT '' COMMENT '第三方支付订单id',
  `pay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `return_id` int(11) DEFAULT '0' COMMENT '取消订单原因',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `sender_id` int(11) DEFAULT '0' COMMENT '发货人ID',
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=340 DEFAULT CHARSET=utf8 COMMENT='订单总表';

DROP TABLE IF EXISTS `lb_order_relation`;
CREATE TABLE `lb_order_relation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `book_order_sn` varchar(200) NOT NULL COMMENT '定金订单号',
  `final_order_sn` varchar(200) NOT NULL COMMENT '尾款订单号',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='多张订单关联表';

DROP TABLE IF EXISTS `lb_order_action`;
CREATE TABLE `lb_order_action` (
  `action_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '动作id',
  `order_sn` int(11) NOT NULL COMMENT '订单id',
  `action` varchar(255) NOT NULL DEFAULT '' COMMENT '动作内容',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人id',
  `order_status` int(11) NOT NULL COMMENT '订单状态',
  `order_status_text` varchar(255) NOT NULL DEFAULT '' COMMENT '订单状态名称',
  `create_time` int(11) DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`action_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=1706 COMMENT='订单操作表';

DROP TABLE IF EXISTS `lb_order_goods_info`;
CREATE TABLE `lb_order_goods_info` (
  `order_sn` varchar(255) NOT NULL DEFAULT '' COMMENT '订单编号',
  `address_id` int(11) NOT NULL DEFAULT '0' COMMENT '地址id',
  `receiver_mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '收货人的手机号码',
  `receiver_address` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人详细地址',
  `receiver_name` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人姓名',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '买家对订单的备注',
  `consign_time_adjust` int(11) NOT NULL DEFAULT '0' COMMENT '卖家延迟发货时间',
  `shipping_company_id` int(11) NOT NULL DEFAULT '0' COMMENT '配送物流公司ID',
  `consign_time` int(11) DEFAULT '0' COMMENT '卖家发货时间',
  `sign_time` int(11) DEFAULT '0' COMMENT '买家签收时间',
  `finish_time` int(11) DEFAULT '0' COMMENT '订单完成时间',
  `express_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '运费',
  `province` varchar(50) DEFAULT '' COMMENT '省',
  `city` varchar(50) DEFAULT '' COMMENT '市',
  `district` varchar(50) DEFAULT '' COMMENT '区',
  PRIMARY KEY (`order_sn`),
  KEY `order_sn` (`order_sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商城订单扩展表';

DROP TABLE IF EXISTS `lb_order_goods_list`;
CREATE TABLE `lb_order_goods_list` (
  `order_sn` varchar(100) NOT NULL COMMENT '订单ID',
  `goods_id` int(11) NOT NULL COMMENT '商品ID',
  `goods_name` varchar(100) NOT NULL COMMENT '商品名称',
  `sku_id` int(11) DEFAULT NULL COMMENT 'skuID',
  `sku_name` varchar(50) DEFAULT NULL COMMENT 'sku名称',
  `shop_price` decimal(19,2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '购买数量',
  `goods_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品总价',
  `goods_thumb` int(11) NOT NULL DEFAULT '0' COMMENT '商品图片',
  `order_status` int(11) NOT NULL DEFAULT '0' COMMENT '订单状态:-1:已取消;0:未付款;1:已付款;4:售后中',
  `shipping_status` int(11) NOT NULL DEFAULT '0' COMMENT '物流状态',
  `refund_type` int(11) NOT NULL DEFAULT '1' COMMENT '退款方式',
  `refund_require_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `refund_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '退款原因',
  `refund_shipping_code` varchar(255) NOT NULL DEFAULT '' COMMENT '退款物流单号',
  `refund_shipping_company` varchar(255) NOT NULL DEFAULT '0' COMMENT '退款物流公司名称',
  `refund_real_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际退款金额',
  `refund_status` int(1) NOT NULL DEFAULT '0' COMMENT '退款状态0:未退;1:已退',
  `refund_time` int(11) DEFAULT '0' COMMENT '退款时间',
  `refund_balance_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单退款余额',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `is_evaluate` smallint(6) NOT NULL DEFAULT '0' COMMENT '是否评价 0为未评价 1为已评价 2为已追评',
  `activity_id` int(11) unsigned DEFAULT '0' COMMENT '活动ID',
  `is_aftersale` int(11) DEFAULT '0' COMMENT '是否申请过售后  0否 1是',
  KEY `UK_order_goods_id` (`goods_id`) USING BTREE,
  KEY `UK_order_goods_order_sn` (`order_sn`) USING BTREE,
  KEY `UK_order_goods_sku_id` (`sku_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=289 COMMENT='订单商品列表';

DROP TABLE IF EXISTS `lb_order_goods_express`;
CREATE TABLE `lb_order_goods_express` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(255) NOT NULL COMMENT '订单id',
  `order_goods_id_array` varchar(255) NOT NULL COMMENT '订单项商品组合列表',
  `express_name` varchar(50) NOT NULL DEFAULT '' COMMENT '包裹名称  （包裹- 1 包裹 - 2）',
  `shipping_type` tinyint(4) NOT NULL COMMENT '发货方式1 需要物流 0无需物流',
  `express_company_id` int(11) NOT NULL COMMENT '快递公司id',
  `express_company` varchar(255) NOT NULL DEFAULT '' COMMENT '物流公司名称',
  `express_no` varchar(50) NOT NULL COMMENT '运单编号',
  `uid` int(11) NOT NULL COMMENT '用户id',
  `memo` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `shipping_time` int(11) DEFAULT '0' COMMENT '发货时间',
  `receive_time` int(11) DEFAULT '0' COMMENT '收货时间',
  PRIMARY KEY (`id`),
  KEY `UK_ns_order_goods_express_order_goods_id_array` (`order_goods_id_array`),
  KEY `UK_ns_order_goods_express_order_id` (`order_sn`),
  KEY `UK_ns_order_goods_express_uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=606 COMMENT='商品订单物流信息表（多次发货）';

DROP TABLE IF EXISTS `lb_order_refund`;
CREATE TABLE `lb_order_refund` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(255) NOT NULL,
  `goods_id` int(11) unsigned DEFAULT '0' COMMENT '商品ID',
  `sku_id` int(11) unsigned DEFAULT '0' COMMENT '库存ID',
  `num` int(11) unsigned DEFAULT '1' COMMENT '数量',
  `goods_money` decimal(20,2) unsigned DEFAULT '0.00' COMMENT '商品总金额',
  `user_id` int(11) DEFAULT NULL,
  `refund_type` varchar(255) DEFAULT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `refund_content` varchar(255) DEFAULT NULL,
  `refund_picture` varchar(255) DEFAULT NULL,
  `refund_money` decimal(10,2) DEFAULT '0.00' COMMENT '实际退款金额',
  `refund_time` int(10) DEFAULT NULL,
  `create_time` int(10) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '售后申请状态0:申请中;-2:用户取消;-1:驳回;1:同意;2:确认收回;3:确认打款',
  `express_no` varchar(20) DEFAULT NULL,
  `express_company_id` int(11) unsigned DEFAULT '0' COMMENT '关联goods_express_company表',
  `server_no` varchar(50) DEFAULT NULL,
  `refund_id` varchar(255) DEFAULT NULL COMMENT '三方退款单号',
  `refund_status` tinyint(1) unsigned DEFAULT '0' COMMENT '0:未退款;1:已退款',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COMMENT='订单退款表';

DROP TABLE IF EXISTS `lb_order_remind`;
CREATE TABLE `lb_order_remind` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(255) NOT NULL COMMENT '订单编号',
  `user_id` int(10) NOT NULL COMMENT '用户id',
  `admin_id` int(10) DEFAULT NULL COMMENT '操作者id',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态: 0未处理，1已处理',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `lb_refund_reason`;
CREATE TABLE `lb_refund_reason` (
  `aid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'aid',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` int(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `reason` varchar(256) NOT NULL DEFAULT '' COMMENT '原因',
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='退换货原因';

INSERT INTO `lb_admin_api_list` (`apiName`, `hash`, `module`, `checkSign`, `needLogin`, `status`, `method`, `info`, `isTest`, `returnStr`, `create_time`, `group`, `readme`, `mock`)
VALUES
	('goods/get_category_list', '5da6e49d7373a', 'goods', 0, 0, 1, 2, '获取商品分类（不含商品）', 1, '获取所有分类：\r\n{\r\n	\"code\": \"1\",\r\n	\"msg\": \"请求成功\",\r\n	\"data\": [\r\n		{\r\n			\"id\": 45,\r\n			\"pid\": 0,\r\n			\"name\": \"水果/蔬菜/零食\",\r\n			\"thumb\": null,\r\n			\"child\": [\r\n				{\r\n					\"id\": 46,\r\n					\"pid\": 45,\r\n					\"name\": \"休闲零食\",\r\n					\"thumb\": null\r\n				},\r\n				{\r\n					\"id\": 47,\r\n					\"pid\": 45,\r\n					\"name\": \"新鲜蔬菜\",\r\n					\"thumb\": null\r\n				},\r\n				{\r\n					\"id\": 48,\r\n					\"pid\": 45,\r\n					\"name\": \"新鲜水果\",\r\n					\"thumb\": null\r\n				}\r\n			]\r\n		},\r\n		{\r\n			\"id\": 49,\r\n			\"pid\": 0,\r\n			\"name\": \"手机/运营商/数码\",\r\n			\"thumb\": null,\r\n			\"child\": [\r\n				{\r\n					\"id\": 42,\r\n					\"pid\": 49,\r\n					\"name\": \"手机\",\r\n					\"thumb\": null\r\n				}\r\n			]\r\n		},\r\n		{\r\n			\"id\": 50,\r\n			\"pid\": 0,\r\n			\"name\": \"美妆/护肤/个护清洁\",\r\n			\"thumb\": null,\r\n			\"child\": [\r\n				{\r\n					\"id\": 51,\r\n					\"pid\": 50,\r\n					\"name\": \"身体护理\",\r\n					\"thumb\": null,\r\n					\"child\": [\r\n						{\r\n							\"id\": 52,\r\n							\"pid\": 51,\r\n							\"name\": \"护手霜\",\r\n							\"thumb\": null\r\n						},\r\n						{\r\n							\"id\": 53,\r\n							\"pid\": 51,\r\n							\"name\": \"沐浴露\",\r\n							\"thumb\": null\r\n						}\r\n					]\r\n				},\r\n				{\r\n					\"id\": 54,\r\n					\"pid\": 50,\r\n					\"name\": \"洗发护发\",\r\n					\"thumb\": null,\r\n					\"child\": [\r\n						{\r\n							\"id\": 55,\r\n							\"pid\": 54,\r\n							\"name\": \"染发膏\",\r\n							\"thumb\": null\r\n						},\r\n						{\r\n							\"id\": 56,\r\n							\"pid\": 54,\r\n							\"name\": \"洗发水\",\r\n							\"thumb\": null\r\n						}\r\n					]\r\n				}\r\n			]\r\n		}\r\n	],\r\n	\"time\": 1571711876,\r\n	\"user\": \"\"\r\n}\r\n获取指定分类的下级分类\r\n{\r\n	\"code\": \"1\",\r\n	\"msg\": \"请求成功\",\r\n	\"data\": [\r\n		{\r\n			\"id\": 51,\r\n			\"pid\": 50,\r\n			\"name\": \"身体护理\",\r\n			\"thumb\": null,\r\n			\"child\": [\r\n				{\r\n					\"id\": 52,\r\n					\"pid\": 51,\r\n					\"name\": \"护手霜\",\r\n					\"thumb\": null\r\n				},\r\n				{\r\n					\"id\": 53,\r\n					\"pid\": 51,\r\n					\"name\": \"沐浴露\",\r\n					\"thumb\": null\r\n				}\r\n			]\r\n		},\r\n		{\r\n			\"id\": 54,\r\n			\"pid\": 50,\r\n			\"name\": \"洗发护发\",\r\n			\"thumb\": null,\r\n			\"child\": [\r\n				{\r\n					\"id\": 55,\r\n					\"pid\": 54,\r\n					\"name\": \"染发膏\",\r\n					\"thumb\": null\r\n				},\r\n				{\r\n					\"id\": 56,\r\n					\"pid\": 54,\r\n					\"name\": \"洗发水\",\r\n					\"thumb\": null\r\n				}\r\n			]\r\n		}\r\n	],\r\n	\"time\": 1571789739,\r\n	\"user\": \"\"\r\n}', 1571218607, 0, '1、传递pid数代表只获取此pid分类的下级分类列表。2、child是下级分类数组', 0),
	('goods/get_goods_detail', '5da6e7013ccbf', 'goods', 0, 0, 1, 2, '获取商品详情', 1, '{\r\n	\"code\": \"1\",\r\n	\"msg\": \"请求成功\",\r\n	\"data\": {\r\n		\"id\": 22,\r\n		\"cid\": 66,\r\n		\"name\": \"荣耀v20手机 幻夜黑 8+128G 全网通碎屏险套装\",\r\n		\"sales_sum\": 0,\r\n		\"spectypeid\": 11,\r\n		\"click\": 111,\r\n		\"is_recommend\": 1,\r\n		\"is_new\": 1,\r\n		\"is_hot\": 1,\r\n		\"adslogan\": \"荣耀v20手机 幻夜黑 8+128G 全网通碎屏险套装\",\r\n		\"shop_price\": \"0.01\",\r\n		\"market_price\": \"2399.00\",\r\n		\"images\": [\r\n			\"http://lbphp.lwwan.com/uploads/images/20191205/d40edb05a0eef8f03c3d0b11730d0370.png\",\r\n			\"http://lbphp.lwwan.com/uploads/images/20191205/672b665d71de35e8693413092c81e2e3.png\"\r\n		],\r\n		\"thumb\": \"http://lbphp.lwwan.com/uploads/images/20191205/d40edb05a0eef8f03c3d0b11730d0370.png\",\r\n		\"body\": \"  \r\n\r\n\r\n\r\n\r\n\r\n\r\n  \",\r\n		\"description\": \"  荣耀v20手机 幻夜黑 8+128G 全网通碎屏险套装  \",\r\n		\"stock\": 786,\r\n		\"is_spec\": 1,\r\n		\"is_shipping\": 1,\r\n		\"freight_price\": \"0.0\",\r\n		\"spec_list\": [\r\n			{\r\n				\"id\": 37,\r\n				\"name\": \"颜色\",\r\n				\"is_upload_image\": 1,\r\n				\"spec_value\": [\r\n					{\r\n						\"id\": 111,\r\n						\"item\": \"幻影红\",\r\n						\"thumb\": \"http://lbphp.lwwan.com/uploads/images/20191205/84528d8f35e40ab77dd81f061266fc43.png\"\r\n					},\r\n					{\r\n						\"id\": 112,\r\n						\"item\": \"幻影黑\",\r\n						\"thumb\": \"http://lbphp.lwwan.com/uploads/images/20191205/a79ea339e391d91af1cba3f0e3bb12e0.png\"\r\n					}\r\n				]\r\n			},\r\n			{\r\n				\"id\": 38,\r\n				\"name\": \"版本\",\r\n				\"is_upload_image\": 0,\r\n				\"spec_value\": [\r\n					{\r\n						\"id\": 116,\r\n						\"item\": \"全网通8G+256G\"\r\n					}\r\n				]\r\n			},\r\n			{\r\n				\"id\": 50,\r\n				\"name\": \"套装\",\r\n				\"is_upload_image\": 0,\r\n				\"spec_value\": [\r\n					{\r\n						\"id\": 132,\r\n						\"item\": \"套装1\"\r\n					},\r\n					{\r\n						\"id\": 133,\r\n						\"item\": \"套装2\"\r\n					}\r\n				]\r\n			}\r\n		],\r\n		\"active_stock\": 40,\r\n		\"sku_list\": {\r\n			\"111_116_132\": {\r\n				\"sku_id\": 410,\r\n				\"key\": \"111_116_132\",\r\n				\"key_name\": \"幻影红-全网通8G+256G-套装1\",\r\n				\"shop_price\": \"0.02\",\r\n				\"market_price\": \"2599.00\",\r\n				\"stock\": 97\r\n			},\r\n			\"111_116_133\": {\r\n				\"sku_id\": 411,\r\n				\"key\": \"111_116_133\",\r\n				\"key_name\": \"幻影红-全网通8G+256G-套装2\",\r\n				\"shop_price\": \"0.02\",\r\n				\"market_price\": \"2599.00\",\r\n				\"stock\": 97\r\n			},\r\n			\"112_116_132\": {\r\n				\"sku_id\": 414,\r\n				\"key\": \"112_116_132\",\r\n				\"key_name\": \"幻影黑-全网通8G+256G-套装1\",\r\n				\"shop_price\": \"0.02\",\r\n				\"market_price\": \"2599.00\",\r\n				\"stock\": 97\r\n			},\r\n			\"112_116_133\": {\r\n				\"sku_id\": 415,\r\n				\"key\": \"112_116_133\",\r\n				\"key_name\": \"幻影黑-全网通8G+256G-套装2\",\r\n				\"shop_price\": \"0.02\",\r\n				\"market_price\": \"2599.00\",\r\n				\"stock\": 97\r\n			}\r\n		},\r\n		\"activity_spec_list\": [\r\n			{\r\n				\"id\": 37,\r\n				\"name\": \"颜色\",\r\n				\"is_upload_image\": 1,\r\n				\"spec_value\": [\r\n					{\r\n						\"id\": 111,\r\n						\"item\": \"幻影红\",\r\n						\"thumb\": \"http://lbphp.lwwan.com/uploads/images/20191205/84528d8f35e40ab77dd81f061266fc43.png\"\r\n					},\r\n					{\r\n						\"id\": 112,\r\n						\"item\": \"幻影黑\",\r\n						\"thumb\": \"http://lbphp.lwwan.com/uploads/images/20191205/a79ea339e391d91af1cba3f0e3bb12e0.png\"\r\n					}\r\n				]\r\n			},\r\n			{\r\n				\"id\": 38,\r\n				\"name\": \"版本\",\r\n				\"is_upload_image\": 0,\r\n				\"spec_value\": [\r\n					{\r\n						\"id\": 116,\r\n						\"item\": \"全网通8G+256G\"\r\n					}\r\n				]\r\n			},\r\n			{\r\n				\"id\": 50,\r\n				\"name\": \"套装\",\r\n				\"is_upload_image\": 0,\r\n				\"spec_value\": [\r\n					{\r\n						\"id\": 132,\r\n						\"item\": \"套装1\"\r\n					},\r\n					{\r\n						\"id\": 133,\r\n						\"item\": \"套装2\"\r\n					}\r\n				]\r\n			}\r\n		],\r\n		\"activity_sku_list\": {\r\n			\"111_116_132\": {\r\n				\"sku_id\": 410,\r\n				\"key\": \"111_116_132\",\r\n				\"key_name\": \"幻影红-全网通8G+256G-套装1\",\r\n				\"shop_price\": \"0.02\",\r\n				\"market_price\": \"2599.00\",\r\n				\"stock\": 97\r\n			},\r\n			\"111_116_133\": {\r\n				\"sku_id\": 411,\r\n				\"key\": \"111_116_133\",\r\n				\"key_name\": \"幻影红-全网通8G+256G-套装2\",\r\n				\"shop_price\": \"0.02\",\r\n				\"market_price\": \"2599.00\",\r\n				\"stock\": 97\r\n			},\r\n			\"112_116_132\": {\r\n				\"sku_id\": 414,\r\n				\"key\": \"112_116_132\",\r\n				\"key_name\": \"幻影黑-全网通8G+256G-套装1\",\r\n				\"shop_price\": \"0.02\",\r\n				\"market_price\": \"2599.00\",\r\n				\"stock\": 97\r\n			},\r\n			\"112_116_133\": {\r\n				\"sku_id\": 415,\r\n				\"key\": \"112_116_133\",\r\n				\"key_name\": \"幻影黑-全网通8G+256G-套装2\",\r\n				\"shop_price\": \"0.02\",\r\n				\"market_price\": \"2599.00\",\r\n				\"stock\": 97\r\n			}\r\n		},\r\n		\"attr_value\": [\r\n			{\r\n				\"id\": 10,\r\n				\"name\": \"后摄主摄像素\",\r\n				\"attr_value\": \"4800万像素\"\r\n			},\r\n			{\r\n				\"id\": 11,\r\n				\"name\": \"摄像头数量\",\r\n				\"attr_value\": \"后置四摄\"\r\n			},\r\n			{\r\n				\"id\": 15,\r\n				\"name\": \"前摄主摄像素\",\r\n				\"attr_value\": \"3200万像素\"\r\n			}\r\n		],\r\n		\"activity_type\": 2,\r\n		\"comment\": {\r\n			\"total\": 0,\r\n			\"per_page\": 15,\r\n			\"current_page\": 1,\r\n			\"last_page\": 0,\r\n			\"data\": []\r\n		},\r\n		\"is_collect\": 0\r\n	},\r\n	\"time\": 1575702478,\r\n	\"user\": {\r\n		\"id\": 41,\r\n		\"user_nickname\": \"囍小雨\",\r\n		\"head_img\": \"http://lbphp.lwwan.com/uploads/images/20191205/4216078f5180ddac321311a3dbf16727.jpg\",\r\n		\"sex\": 1,\r\n		\"user_type\": 0,\r\n		\"user_level\": 0,\r\n		\"status\": 1\r\n	}\r\n}', 1571219215, 0, '', 0),
	('goods/get_goods_list', '5db113922d297', 'goods', 0, 0, 1, 2, '获取指定栏目的商品列表', 1, '{\r\n	\"code\": \"1\",\r\n	\"msg\": \"请求成功\",\r\n	\"data\": {\r\n		\"total\": 2,\r\n		\"per_page\": \"16\",\r\n		\"current_page\": 1,\r\n		\"last_page\": 1,\r\n		\"data\": [\r\n			{\r\n				\"id\": 12,\r\n				\"name\": \"有机西红柿新鲜蔬菜宝宝辅食\",\r\n				\"thumb\": \"http://127.0.0.1:103/uploads/images/20191024/7475c11058276c2e22df6e4fa264ecbc.jpg\",\r\n				\"sales_sum\": 0,\r\n				\"shop_price\": \"0.01\",\r\n				\"market_price\": \"25.00\",\r\n				\"is_shipping\": 1\r\n			},\r\n			{\r\n				\"id\": 13,\r\n				\"name\": \"【甘福园】新鲜圣女果5斤 千禧樱桃小番茄水果农家西红柿蔬菜包邮\",\r\n				\"thumb\": \"http://127.0.0.1:103/uploads/images/20191024/a88cb3acea0cf791f7b0eeacf7b6e85d.jpg\",\r\n				\"sales_sum\": 0,\r\n				\"shop_price\": \"0.03\",\r\n				\"market_price\": \"26.80\",\r\n				\"is_shipping\": 1\r\n			}\r\n		]\r\n	},\r\n	\"time\": 1571888383,\r\n	\"user\": \"\"\r\n}', 1571885992, 0, '', 0),
	('goods/get_one_order_info', '5db1769fb26fd', 'goods', 0, 1, 1, 1, '直接购买获取创建订单的相关信息', 1, '{\r\n	\"code\": \"1\",\r\n	\"msg\": \"请求成功\",\r\n	\"data\": {\r\n		\"goods\": {\r\n			\"id\": 11,\r\n			\"name\": \"荣耀20 李现同款 4800万超广角AI四摄 3200W美颜自拍\",\r\n			\"shop_price\": \"0.03\",\r\n			\"market_price\": \"2.00\",\r\n			\"thumb\": \"http://127.0.0.1:103/uploads/images/20191018/4302b17c29656af1d175329bff4f7b32.jpg\",\r\n			\"stock\": 10,\r\n			\"is_sale\": 1,\r\n			\"status\": 1\r\n		},\r\n		\"address\": {\r\n			\"address_id\": 1,\r\n			\"name\": \"刘星辰\",\r\n			\"mobile\": \"13603712782\",\r\n			\"address\": \"华城国际中心1905\",\r\n			\"is_default\": \"1\",\r\n			\"province\": \"河南\",\r\n			\"city\": \"郑州\",\r\n			\"district\": \"二七区\",\r\n			\"postal_code\": 450000\r\n		}\r\n	}\r\n}', 1571911359, 0, '', 0),
	('goods/get_cart_order_info', '5db1800146ded', 'goods', 0, 1, 1, 1, '购物车购买获取创建订单的相关信息', 1, '{\r\n	\"code\": \"1\",\r\n	\"msg\": \"请求成功\",\r\n	\"data\": {\r\n		\"tip\": 0,\r\n		\"msg\": \"\",\r\n		\"goods\": [\r\n			{\r\n				\"id\": 11,\r\n				\"sku_id\": 377,\r\n				\"name\": \"荣耀20 李现同款 4800万超广角AI四摄 3200W美颜自拍\",\r\n				\"shop_price\": \"0.03\",\r\n				\"key_name\": \"幻影蓝-全网通8G+128G-套装1\",\r\n				\"market_price\": \"2999.00\",\r\n				\"thumb\": \"http://127.0.0.1:103/uploads/images/20191018/4302b17c29656af1d175329bff4f7b32.jpg\",\r\n				\"stock\": 10,\r\n				\"is_sale\": 1,\r\n				\"status\": 1,\r\n				\"skustatus\": 1\r\n			},\r\n			{\r\n				\"id\": 11,\r\n				\"name\": \"荣耀20 李现同款 4800万超广角AI四摄 3200W美颜自拍\",\r\n				\"shop_price\": \"0.03\",\r\n				\"market_price\": \"2.00\",\r\n				\"thumb\": \"http://127.0.0.1:103/uploads/images/20191018/4302b17c29656af1d175329bff4f7b32.jpg\",\r\n				\"stock\": 10,\r\n				\"is_sale\": 1,\r\n				\"status\": 1\r\n			}\r\n		],\r\n		\"address\": {\r\n			\"address_id\": 1,\r\n			\"name\": \"刘星辰\",\r\n			\"mobile\": \"13603712782\",\r\n			\"address\": \"华城国际中心1905\",\r\n			\"is_default\": \"1\",\r\n			\"province\": \"河南\",\r\n			\"city\": \"郑州\",\r\n			\"district\": \"二七区\",\r\n			\"postal_code\": 450000\r\n		}\r\n	}\r\n}', 1571913748, 0, '', 0),
	('cart/add_cart', '5dd4e8de8c6c9', 'goods', 0, 1, 1, 1, '添加购物车商品', 1, '', 1574234402, 14, '购物车商品添加或数量减少', 0),
	('cart/remove_goods', '5dd4e9d8e946a', 'goods', 0, 1, 1, 1, '删除购物车商品', 1, '', 1574234626, 14, '删除购物车商品', 0),
	('cart/get_list', '5dd4fc3b61d33', 'goods', 0, 1, 1, 1, '获取购物车列表', 1, '', 1574239314, 14, '', 0),
	('order/get_list', '5dd519b898b4a', 'goods', 0, 1, 1, 1, '商品订单', 1, '', 1574246865, 13, 'type取值： unpay-未支付；unship-未发货；unreceive-未收货；finish-已完成；refund-退货中', 0),
	('order/remind_order', '5dd631e6d9ea8', 'goods', 0, 1, 1, 1, '订单提醒发货', 1, '', 1574318607, 13, '', 0),
	('order/receive_order', '5dd63212b1c1b', 'goods', 0, 1, 1, 1, '订单确认收货', 1, '', 1574318641, 13, '', 0),
	('order/comment', '5dd6325e6face', 'goods', 0, 1, 1, 1, '订单评价', 1, '', 1574318727, 13, '', 0),
	('order/refund_apply', '5dd632ac90043', 'goods', 0, 1, 1, 1, '订单退货申请', 1, '', 1574318844, 13, '', 0),
	('order/remove_order', '5dd6330bae65c', 'goods', 0, 1, 1, 1, '订单删除', 1, '', 1574318888, 13, '', 0),
	('cart/make_order', '5dd72d2af1d0c', 'goods', 0, 1, 1, 1, '购物车提交订单', 1, '', 1574382935, 14, '通过提交选中的cart_id', 0),
	('order/express', '5dd890cc4f3c6', 'goods', 0, 1, 1, 1, '订单物流', 1, '', 1574473960, 13, '', 0),
	('index/index', '5ddb3ddf4ac39', 'goods', 0, 0, 0, 1, '商城首页获取轮播图，banner图等', 1, '', 1574649372, 15, '', 0),
	('index/getMsg', '5ddb3e1ff2a8d', 'goods', 0, 1, 1, 1, '首页获取站内信数量', 1, '', 1574649405, 15, '', 0),
	('index/menu', '5ddb3e40e6e9a', 'goods', 0, 0, 1, 1, '首页获取功能菜单', 1, '', 1574649438, 15, '', 0),
	('index/goods_block', '5ddb3e6412063', 'goods', 0, 0, 1, 1, '首页获取商品模块', 1, '', 1574649464, 15, '', 0),
	('index/search', '5ddb3e8980e61', 'goods', 0, 0, 1, 1, '首页搜索商品', 1, '', 1574649505, 15, '', 0),
	('cart/set_goods', '5ddb9be016ddc', 'goods', 0, 1, 1, 1, '购物车商品数量操作', 1, '', 1574673428, 14, '商品数量必须大于0', 0),
	('order/cancel_order', '5ddcb385e1ccd', 'goods', 0, 1, 1, 1, '取消订单', 1, '', 1574745005, 13, '', 0),
	('activity/index', '5de23bd0c283c', 'goods', 0, 0, 1, 1, '活动商品列表', 1, '', 1575107574, 15, '', 0),
	('activity/lists', '5dea05416cfe9', 'goods', 0, 0, 1, 1, '活动信息列表', 1, '', 1575617934, 0, '', 0),
	('activity/myGroup', '5deb070b1a169', 'goods', 0, 1, 1, 1, '我的拼团记录', 1, '', 1575683939, 0, '', 0),
	('goods/commentList', '5def10d71d037', 'goods', 0, 0, 1, 1, '商品评论列表', 1, '', 1575948559, 0, '', 0),
	('order/refund_list', '5f72db26e6259', 'goods', 0, 1, 1, 2, '售后列表', 1, '', 1601363070, 13, '', 0),
	('order/refund_cancel', '5ec7395785d31', 'goods', 0, 0, 1, 1, '取消退单售后', 1, '', 1601367578, 13, '', 0),
	('order/refund_detaile', '5ec4ea8cd41bd', 'goods', 0, 0, 1, 1, '售后详情', 1, '', 1601368987, 13, '', 0),
	('order/refund_sender', '5ec7931fc9cf9', 'goods', 0, 0, 1, 1, '获取供应商的退货信息', 1, '', 1601372863, 13, '', 0),
	('order/refund_express', '5ec6539b3aa45', 'goods', 0, 1, 1, 1, '售后物流信息保存', 1, '', 1601374696, 13, '', 0),
	('goods/getMiniQrcode', '5f4373c548e0e', 'goods', 0, 0, 1, 1, '获取临时二维码', 1, '', 1601374815, 13, '', 0);


	INSERT INTO `lb_admin_api_fields` (`fieldName`, `hash`, `dataType`, `default`, `isMust`, `range`, `info`, `type`, `showName`, `pid`, `sort`, `mock`)
VALUES
	('max_level', '5da6e49d7373a', 1, '3', 0, '', 'max_level【返回的最大层级。类型：int(11)】默认3层，建议1-3层', 0, 'max_level', 0, 100, ''),
	('pid', '5da6e49d7373a', 1, '0', 0, '', 'pid【上级分类id。类型：smallint(5) unsigned】传递此参数代表只获取此分类的下级分类列表', 0, 'pid', 0, 100, ''),
	('cid', '5db113922d297', 1, '0', 0, '', 'cid【分类id。类型：int(11) unsigned】不传递则获取全部商品', 0, 'cid', 0, 100, ''),
	('sort', '5db113922d297', 1, '1', 0, '', 'sort【排序类型。类型：int(11)】1综合排序，2销量，3价格', 0, 'sort', 0, 100, ''),
	('pagesize', '5db113922d297', 1, '16', 0, '', 'pagesize【每页数量。类型：int(11) unsigned】', 0, 'pagesize', 0, 100, ''),
	('page', '5db113922d297', 1, '1', 0, '', 'page【页码。类型：int(11) unsigned】', 0, 'page', 0, 100, ''),
	('order', '5db113922d297', 2, 'asc', 0, '', 'order【排序类型。类型：varchar(4)】升序 asc 或者 倒序 desc 默认升序', 0, 'order', 0, 100, '这里是返回的文字字符串'),
	('keyword', '5db113922d297', 2, '', 0, '', '关键词', 0, 'keyword', 0, 100, '这里是返回的文字字符串'),
	('user_id', '5da6e7013ccbf', 1, '', 0, '', 'user_id【会员id。类型：int(11)】', 0, 'user_id', 0, 100, ''),
	('activity_id', '5da6e7013ccbf', 1, '0', 1, '', 'activity_id【活动ID。类型：int(11)】', 0, 'activity_id', 0, 100, ''),
	('goods_id', '5da6e7013ccbf', 1, '', 1, '', '商品id', 0, 'goods_id', 0, 100, ''),
	('is_cache', '5da6e7013ccbf', 1, '0', 0, '', 'is_cache【是否更新缓存。类型：int(11) unsigned】0否1是，传递1代表获取最新的商品信息，不使用缓存里的商品信息', 0, 'is_cache', 0, 100, ''),
	('goods_id', '5def10d71d037', 1, '', 1, '', 'goods_id【商品id。类型：int(11)】', 0, 'goods_id', 0, 100, ''),
	('page', '5def10d71d037', 1, '1', 0, '', 'page【页码】', 0, 'page', 0, 100, ''),
	('size', '5def10d71d037', 1, '1', 0, '', 'size【每页条数】', 0, 'size', 0, 100, ''),
	('cart_id', '5ddb9be016ddc', 1, '', 1, '', 'cart_id【购物车id。类型：int(11)】', 0, 'cart_id', 0, 100, ''),
	('num', '5ddb9be016ddc', 1, '', 1, '', 'num【购买商品数量。类型：smallint(5)】', 0, 'num', 0, 100, ''),
	('sku_id', '5ddb9be016ddc', 1, '', 1, '', 'sku_id【规格商品id。类型：int(10) unsigned】', 0, 'sku_id', 0, 100, ''),
	('cart_ids', '5dd4e9d8e946a', 2, '', 1, '', 'cart_ids【购物车id,以逗号连接】', 0, 'cart_ids', 0, 100, '这里是返回的文字字符串'),
	('goods_id', '5da6e7013ccbf', 1, '', 1, '', '商品id', 0, 'goods_id', 0, 100, ''),
	('pid', '5da6e49d7373a', 1, '0', 0, '', 'pid【上级分类id。类型：smallint(5) unsigned】传递此参数代表只获取此分类的下级分类列表', 0, 'pid', 0, 100, ''),
	('id', '5da6e49d7373a', 1, '', 1, '', 'id【商品分类id。类型：smallint(5) unsigned】', 1, 'id', 0, 100, ''),
	('name', '5da6e49d7373a', 2, '', 1, '', 'name【商品分类名称。类型：varchar(90)】', 1, 'name', 0, 100, ''),
	('pid', '5da6e49d7373a', 1, '', 1, '', 'pid【父id。类型：smallint(5) unsigned】', 1, 'pid', 0, 100, ''),
	('thumb', '5da6e49d7373a', 2, '', 1, '', 'thumb【分类图片。类型：varchar(512)】', 1, 'thumb', 0, 100, ''),
	('child', '5da6e49d7373a', 8, '', 1, '', 'child【商品下级分类。】', 1, 'child', 0, 100, ''),
	('is_cache', '5da6e7013ccbf', 1, '0', 0, '', 'is_cache【是否更新缓存。类型：int(11) unsigned】0否1是，传递1代表获取最新的商品信息，不使用缓存里的商品信息', 0, 'is_cache', 0, 100, ''),
	('id', '5da6e7013ccbf', 1, '', 1, '', 'id【商品id。类型：mediumint(8) unsigned】', 1, 'id', 0, 100, ''),
	('name', '5da6e7013ccbf', 2, '', 1, '', 'name【商品名称。类型：varchar(120)】', 1, 'name', 0, 100, ''),
	('cid', '5da6e7013ccbf', 1, '', 1, '', 'cid【分类id。类型：int(11) unsigned】', 1, 'cid', 0, 100, ''),
	('adslogan', '5da6e7013ccbf', 2, '', 1, '', 'adslogan【广告语。类型：varchar(255)】', 1, 'adslogan', 0, 100, ''),
	('is_new', '5da6e7013ccbf', 1, '', 1, '', 'is_new【是否新品。类型：tinyint(1) unsigned】', 1, 'is_new', 0, 100, ''),
	('brand', '5da6e7013ccbf', 2, '', 1, '', 'brand【品牌】', 1, 'brand', 0, 100, ''),
	('stock', '5da6e7013ccbf', 1, '', 1, '', 'stock【总库存数量。类型：smallint(5) unsigned】', 1, 'stock', 0, 100, ''),
	('comment_count', '5da6e7013ccbf', 1, '', 1, '', 'comment_count【商品评论数。类型：smallint(5)】', 1, 'comment_count', 0, 100, ''),
	('market_price', '5da6e7013ccbf', 5, '', 1, '', 'market_price【市场价，划线价。类型：decimal(10,2) unsigned】', 1, 'market_price', 0, 100, ''),
	('shop_price', '5da6e7013ccbf', 5, '', 1, '', 'shop_price【本店价。类型：decimal(10,2) unsigned】', 1, 'shop_price', 0, 100, ''),
	('keywords', '5da6e7013ccbf', 2, '', 1, '', 'keywords【商品关键词。类型：varchar(255)】', 1, 'keywords', 0, 100, ''),
	('thumb', '5da6e7013ccbf', 2, '', 1, '', 'thumb【商品上传原始图。类型：varchar(255)】', 1, 'thumb', 0, 100, ''),
	('images', '5da6e7013ccbf', 8, '', 1, '', 'images【图册集合，用英文逗号分割。类型：varchar(255)】', 1, 'images', 0, 100, ''),
	('collect_count', '5da6e7013ccbf', 1, '', 1, '', 'collect_count【收藏量。类型：int(11) unsigned】', 1, 'collect_count', 0, 100, ''),
	('is_sale', '5da6e7013ccbf', 1, '', 1, '', 'is_sale【是否上架。类型：tinyint(1) unsigned】', 1, 'is_sale', 0, 100, ''),
	('sales_sum', '5da6e7013ccbf', 1, '', 1, '', 'sales_sum【商品销量。类型：int(11) unsigned】', 1, 'sales_sum', 0, 100, ''),
	('is_shipping', '5da6e7013ccbf', 1, '', 1, '', 'is_shipping【是否包邮0否1是。类型：tinyint(1) unsigned】', 1, 'is_shipping', 0, 100, ''),
	('is_recommend', '5da6e7013ccbf', 1, '', 1, '', 'is_recommend【是否推荐。类型：tinyint(1) unsigned】', 1, 'is_recommend', 0, 100, ''),
	('is_hot', '5da6e7013ccbf', 1, '', 1, '', 'is_hot【是否热卖。类型：tinyint(1)】', 1, 'is_hot', 0, 100, ''),
	('description', '5da6e7013ccbf', 2, '', 1, '', 'description【商品简介。类型：varchar(255)】', 1, 'description', 0, 100, ''),
	('body', '5da6e7013ccbf', 2, '', 1, '', 'body【商品详情。类型：text】', 1, 'body', 0, 100, ''),
	('spec_list', '5da6e7013ccbf', 8, '', 1, '', 'spec_list【规格列表。类型：object】', 1, 'spec_list', 0, 100, ''),
	('spec_list.id', '5da6e7013ccbf', 1, '', 1, '', 'id【规格id。类型：int(11)】', 1, 'spec_list.id', 0, 100, ''),
	('spec_list.name', '5da6e7013ccbf', 2, '', 1, '', 'name【规格名称。类型：varchar(55)】', 1, 'spec_list.name', 0, 100, ''),
	('spec_list.is_upload_image', '5da6e7013ccbf', 1, '', 1, '', 'is_upload_image【是否可上传规格图.0否，1是。类型：tinyint(1) unsigned】', 1, 'spec_list.is_upload_image', 0, 100, ''),
	('spec_list.spec_value', '5da6e7013ccbf', 8, '', 1, '', 'spec_value【规格值列表。类型：object】', 1, 'spec_list.spec_value', 0, 100, ''),
	('spec_list.spec_value.id', '5da6e7013ccbf', 1, '', 1, '', 'id【规格项id。类型：int(11)】', 1, 'spec_list.spec_value.id', 0, 100, ''),
	('spec_list.spec_value.item', '5da6e7013ccbf', 2, '', 1, '', 'item【规格项。类型：varchar(54)】', 1, 'spec_list.spec_value.item', 0, 100, ''),
	('spec_list.spec_value.thumb', '5da6e7013ccbf', 2, '', 1, '', 'thumb【商品规格图片。类型：int(11) unsigned】', 1, 'spec_list.spec_value.thumb', 0, 100, ''),
	('sku_list', '5da6e7013ccbf', 8, '', 1, '', 'sku_list【SKU子商品列表。类型：object】', 1, 'sku_list', 0, 100, ''),
	('sku_list.sku_id', '5da6e7013ccbf', 1, '', 1, '', 'sku_id【规格商品id。类型：bigint(20) unsigned】', 1, 'sku_list.sku_id', 0, 100, ''),
	('sku_list.key', '5da6e7013ccbf', 2, '', 1, '', 'key【规格键名。类型：varchar(255)】', 1, 'sku_list.key', 0, 100, ''),
	('sku_list.key_name', '5da6e7013ccbf', 2, '', 1, '', 'key_name【规格键名中文。类型：varchar(255)】', 1, 'sku_list.key_name', 0, 100, ''),
	('sku_list.shop_price', '5da6e7013ccbf', 5, '', 1, '', 'shop_price【价格。类型：decimal(10,2) unsigned】', 1, 'sku_list.shop_price', 0, 100, ''),
	('sku_list.market_price', '5da6e7013ccbf', 5, '', 1, '', 'market_price【市场价，划线价。类型：decimal(10,2) unsigned】', 1, 'sku_list.market_price', 0, 100, ''),
	('stock', '5da6e7013ccbf', 1, '', 1, '', 'stock【库存数量。类型：int(11) unsigned】', 1, 'stock', 0, 100, ''),
	('is_spec', '5da6e7013ccbf', 1, '', 1, '', 'is_spec【是否开启规格和属性。类型：tinyint(1) unsigned】', 1, 'is_spec', 0, 100, ''),
	('comment', '5da6e7013ccbf', 8, '', 1, '', 'comment【评价。类型：object】', 1, 'comment', 0, 100, ''),
	('comment.total', '5da6e7013ccbf', 1, '', 1, '', 'total【评价总条数。类型：int(11)】', 1, 'comment.total', 0, 100, ''),
	('comment.per_page', '5da6e7013ccbf', 1, '', 1, '', 'pre_page【每页数量。类型：int(11)】', 1, 'comment.per_page', 0, 100, ''),
	('comment.current_page', '5da6e7013ccbf', 1, '', 1, '', 'current_page【当前页码。类型：int(11)】', 1, 'comment.current_page', 0, 100, ''),
	('comment.last_page', '5da6e7013ccbf', 1, '', 1, '', 'last_page【最后一页页码。类型：int(11)】', 1, 'comment.last_page', 0, 100, ''),
	('comment.data', '5da6e7013ccbf', 1, '', 1, '', 'pre_page【每页数量。类型：varchar(32)】', 1, 'comment.per_page', 0, 100, ''),
	('comment.data.id', '5da6e7013ccbf', 1, '', 1, '', 'id【评论id。类型：int(11)】', 1, 'comment.data.id', 0, 100, ''),
	('comment.data.thumb', '5da6e7013ccbf', 2, '', 1, '', 'thumb【评价图片。类型：varchar(50)】', 1, 'comment.data.thumb', 0, 100, ''),
	('comment.data.star', '5da6e7013ccbf', 1, '', 1, '', 'star【评论星级。类型：varchar(10)】', 1, 'comment.data.star', 0, 100, ''),
	('comment.data.create_time', '5da6e7013ccbf', 2, '', 1, '', 'create_time【评价时间。类型：int(10)】此时间为个性化时间', 1, 'comment.data.create_time', 0, 100, ''),
	('comment.data.content', '5da6e7013ccbf', 2, '', 1, '', 'content【评价内容。类型：tinytext】', 1, 'comment.data.content', 0, 100, ''),
	('comment.data.sku_id', '5da6e7013ccbf', 1, '', 1, '', 'sku_id【商品skuid。类型：varchar(255)】', 1, 'comment.data.sku_id', 0, 100, ''),
	('comment.data.user_nickname', '5da6e7013ccbf', 2, '', 1, '', 'user_nickname【会员昵称。类型：varchar(256)】', 1, 'comment.data.user_nickname', 0, 100, ''),
	('comment.data.head_img', '5da6e7013ccbf', 2, '', 1, '', 'head_img【头像。类型：varchar(256)】', 1, 'comment.data.head_img', 0, 100, ''),
	('max_level', '5da6e49d7373a', 1, '3', 0, '', 'max_level【返回的最大层级。类型：int(11)】默认3层，建议1-3层', 0, 'max_level', 0, 100, ''),
	('total', '5db113922d297', 1, '', 1, '', '总条数', 1, 'total', 0, 0, ''),
	('per_page', '5db113922d297', 1, '', 1, '', '每页条数', 1, 'per_page', 0, 0, ''),
	('current_page', '5db113922d297', 1, '', 1, '', '当前页码', 1, 'current_page', 0, 0, ''),
	('last_page', '5db113922d297', 1, '', 1, '', '最后页码', 1, 'last_page', 0, 0, ''),
	('data', '5db113922d297', 1, '', 1, '', '数据列表', 1, 'data', 0, 1, ''),
	('data.id', '5db113922d297', 2, '', 1, '', 'id【商品id。类型：mediumint(8) unsigned】', 1, 'data.id', 0, 100, ''),
	('data.name', '5db113922d297', 2, '', 1, '', 'name【商品名称。类型：varchar(120)】', 1, 'data.name', 0, 100, ''),
	('data.thumb', '5db113922d297', 2, '', 1, '', 'thumb【商品上传原始图。类型：varchar(255)】', 1, 'data.thumb', 0, 100, ''),
	('data.sales_sum', '5db113922d297', 1, '', 1, '', 'sales_sum【商品销量。类型：int(11) unsigned】', 1, 'data.sales_sum', 0, 100, ''),
	('data.market_price', '5db113922d297', 5, '', 1, '', 'market_price【市场价,划线价。类型：decimal(10,2) unsigned】', 1, 'data.market_price', 0, 100, ''),
	('shop_price', '5db113922d297', 5, '', 1, '', 'shop_price【本店价。类型：decimal(10,2) unsigned】', 1, 'shop_price', 0, 100, ''),
	('data.is_shipping', '5db113922d297', 1, '', 1, '', 'is_shipping【是否包邮0否1是。类型：tinyint(1) unsigned】', 1, 'data.is_shipping', 0, 100, ''),
	('goods_id', '5db1769fb26fd', 1, '', 1, '', 'goods_id【商品id。类型：int(11) unsigned】', 0, 'goods_id', 0, 100, ''),
	('sku_id', '5db1769fb26fd', 1, '', 0, '', 'sku_id【规格商品id。类型：int(10) unsigned】', 0, 'sku_id', 0, 100, ''),
	('number', '5db1769fb26fd', 1, '', 1, '', 'number【购买数量。类型：int(11)】', 0, 'number', 0, 100, ''),
	('goods', '5db1769fb26fd', 8, '', 1, '', 'goods【商品信息。类型：object】', 1, 'goods', 0, 1, ''),
	('address', '5db1769fb26fd', 8, '', 1, '', 'address【收货地址。类型：varchar(32)】', 1, 'address', 0, 101, ''),
	('goods.id', '5db1769fb26fd', 1, '', 1, '', 'id【商品id。类型：mediumint(8) unsigned】', 1, 'goods.id', 0, 2, ''),
	('goods.name', '5db1769fb26fd', 1, '', 1, '', 'name【商品名称。类型：varchar(120)】', 1, 'name', 0, 100, ''),
	('goods.shop_price', '5db1769fb26fd', 5, '', 1, '', 'shop_price【本店价。类型：decimal(10,2) unsigned】', 1, 'shop_price', 0, 100, ''),
	('goods.market_price', '5db1769fb26fd', 5, '', 1, '', 'market_price【市场价。类型：decimal(10,2) unsigned】', 1, 'market_price', 0, 100, ''),
	('goods.stock', '5db1769fb26fd', 1, '', 1, '', 'stock【库存数量。类型：smallint(5) unsigned】', 1, 'stock', 0, 100, ''),
	('goods.is_sale', '5db1769fb26fd', 1, '', 1, '', 'is_sale【是否上架。类型：tinyint(1) unsigned】', 1, 'is_sale', 0, 100, ''),
	('goods.status', '5db1769fb26fd', 1, '', 1, '', 'status【状态1正常2禁用。类型：tinyint(1) unsigned】', 1, 'status', 0, 100, ''),
	('goods.thumb', '5db1769fb26fd', 2, '', 1, '', 'thumb【商品上传原始图。类型：varchar(255)】', 1, 'thumb', 0, 100, ''),
	('address.address_id', '5db1769fb26fd', 1, '', 1, '', '地址id', 1, 'address_id', 0, 102, ''),
	('address.name', '5db1769fb26fd', 2, '', 1, '', '姓名', 1, '', 0, 103, ''),
	('address.mobile', '5db1769fb26fd', 7, '', 1, '', '手机号', 1, 'mobile', 0, 104, ''),
	('address.address', '5db1769fb26fd', 2, '', 1, '', '详细地址', 1, '', 0, 105, ''),
	('address.is_default', '5db1769fb26fd', 2, '', 1, '', '是否默认地址', 1, '', 0, 106, ''),
	('goods', '5db1800146ded', 8, '', 1, '', 'goods【商品信息。类型：object】', 1, 'goods', 0, 1, ''),
	('address', '5db1800146ded', 8, '', 1, '', 'address【收货地址。类型：varchar(32)】', 1, 'address', 0, 101, ''),
	('goods.id', '5db1800146ded', 1, '', 1, '', 'id【商品id。类型：mediumint(8) unsigned】', 1, 'goods.id', 0, 2, ''),
	('goods.name', '5db1800146ded', 1, '', 1, '', 'name【商品名称。类型：varchar(120)】', 1, 'name', 0, 100, ''),
	('goods.shop_price', '5db1800146ded', 5, '', 1, '', 'shop_price【本店价。类型：decimal(10,2) unsigned】', 1, 'shop_price', 0, 100, ''),
	('goods.market_price', '5db1800146ded', 5, '', 1, '', 'market_price【市场价。类型：decimal(10,2) unsigned】', 1, 'market_price', 0, 100, ''),
	('goods.stock', '5db1800146ded', 1, '', 1, '', 'stock【库存数量。类型：smallint(5) unsigned】', 1, 'stock', 0, 100, ''),
	('goods.is_sale', '5db1800146ded', 1, '', 1, '', 'is_sale【是否上架。类型：tinyint(1) unsigned】', 1, 'is_sale', 0, 100, ''),
	('goods.status', '5db1800146ded', 1, '', 1, '', 'status【状态1正常2禁用。类型：tinyint(1) unsigned】', 1, 'status', 0, 100, ''),
	('goods.thumb', '5db1800146ded', 2, '', 1, '', 'thumb【商品上传原始图。类型：varchar(255)】', 1, 'thumb', 0, 100, ''),
	('address.address_id', '5db1800146ded', 1, '', 1, '', '地址id', 1, 'address_id', 0, 102, ''),
	('address.name', '5db1800146ded', 2, '', 1, '', '姓名', 1, '', 0, 103, ''),
	('address.mobile', '5db1800146ded', 7, '', 1, '', '手机号', 1, 'mobile', 0, 104, ''),
	('address.address', '5db1800146ded', 2, '', 1, '', '详细地址', 1, '', 0, 105, ''),
	('address.is_default', '5db1800146ded', 2, '', 1, '', '是否默认地址', 1, '', 0, 106, ''),
	('goods.sku_id', '5db1769fb26fd', 1, '', 0, '', 'sku_id【规格商品id。类型：int(10) unsigned】', 1, 'goods.sku_id', 0, 100, ''),
	('goods.sku_id', '5db1800146ded', 1, '', 0, '', 'sku_id【规格商品id。类型：int(10) unsigned】', 1, 'goods.sku_id', 0, 100, ''),
	('user_id', '5da6e7013ccbf', 1, '', 0, '因为此用户不验证登录状态，所有传递user_id用来查询会员收藏商品没有', 'user_id【会员id。类型：int(11)】', 0, 'user_id', 0, 100, ''),
	('is_collect', '5da6e7013ccbf', 1, '', 0, '', 'is_collect【是否关注。类型：int(11) unsigned】如果要判断是否关注，请在请求参数中传入user_id', 1, 'is_collect', 0, 100, ''),
	('coupon_id', '5db1769fb26fd', 1, '', 0, '', 'coupon_id【优惠券id。类型：int(10) unsigned】', 0, 'coupon_id', 0, 100, ''),
	('address_id', '5db1769fb26fd', 1, '', 1, '', 'address_id【文档id。类型：int(11) unsigned】', 0, 'address_id', 0, 100, ''),
	('sku_id', '5dd4e8de8c6c9', 1, '', 0, '', 'sku_id【商品的skuid。类型：int(11) unsigned】', 0, 'sku_id', 0, 100, ''),
	('cart_ids', '5dd4e9d8e946a', 2, '', 1, '', 'cart_ids【购物车id,以逗号连接】', 0, 'cart_ids', 0, 100, ''),
	('goods_id', '5dd4e8de8c6c9', 1, '', 1, '', 'goods_id【商品id。类型：int(11) unsigned】', 0, 'goods_id', 0, 100, ''),
	('type', '5dd519b898b4a', 2, 'all', 1, '', 'type 【unpay:待付款 ,unship:已付款,unreceive:待发货,finish:已收货,evaluate:已评价,cancel:已取消;】', 0, 'type', 0, 100, ''),
	('order_sn', '5dd631e6d9ea8', 2, '', 1, '', 'order_sn【订单编号。类型：varchar(255)】', 0, 'order_sn', 0, 100, ''),
	('order_sn', '5dd63212b1c1b', 2, '', 1, '', '', 0, 'order_sn', 0, 100, ''),
	('cart_ids', '5dd72d2af1d0c', 2, '', 1, '', 'cart_ids【购物车数据ID】', 0, 'cart_ids', 0, 100, ''),
	('order_sn', '5dd6325e6face', 2, '', 1, '', 'order_sn【订单号。类型：varchar(255)】', 0, 'order_sn', 0, 100, ''),
	('user_id', '5dd6325e6face', 1, '', 1, '', 'user_id【用户id值。类型：int(11)】', 0, 'user_id', 0, 100, ''),
	('star', '5dd6325e6face', 1, '', 1, '', 'star【评论星级。类型：varchar(10)】', 0, 'star', 0, 100, ''),
	('content', '5dd6325e6face', 2, '', 1, '', 'content【评价内容。类型：tinytext】', 0, 'content', 0, 100, ''),
	('thumb', '5dd6325e6face', 2, '', 0, '', 'thumb【评价图片。类型：varchar(50)】', 0, 'thumb', 0, 100, ''),
	('type', '5dd6325e6face', 1, '', 1, '', 'type【1匿名0显示。类型：int(11)】', 0, 'type', 0, 100, ''),
	('order_sn', '5dd890cc4f3c6', 2, '', 1, '', 'order_sn【订单编号。类型：varchar(255)】', 0, 'order_sn', 0, 100, ''),
	('order_sn', '5dd6330bae65c', 2, '', 1, '', 'order_sn【订单号。类型：varchar(256)】', 0, 'order_sn', 0, 100, ''),
	('order_sn', '5dd632ac90043', 2, '', 1, '', 'order_sn【订单号。类型：varchar(100)】', 0, 'order_sn', 0, 100, ''),
	('refund_type', '5dd632ac90043', 1, '', 1, '', 'refund_type【退款方式。类型：int(11)】 1 退款 2 退货退款', 0, 'refund_type', 0, 100, ''),
	('refund_reason', '5dd632ac90043', 2, '', 1, '', 'refund_reason【退款原因。类型：varchar(255)】', 0, 'refund_reason', 0, 100, ''),
	('refund_picture', '5dd632ac90043', 2, '', 0, '', 'refund_picture【退货的拍照图片URL地址。类型：varchar(255)】', 0, 'refund_picture', 0, 100, ''),
	('type', '5ddb3e6412063', 1, '0', 1, '', '[0=>is_recommed, 1=>is_new, 2=>is_hot]', 0, 'type', 0, 100, ''),
	('size', '5ddb3e6412063', 1, '10', 1, '', '返回条数', 0, 'size', 0, 100, ''),
	('keyword', '5ddb3e8980e61', 2, '', 1, '', '商品名称', 0, 'keyword', 0, 100, ''),
	('cart_id', '5ddb9be016ddc', 1, '', 1, '', 'cart_id【购物车id。类型：int(11)】', 0, 'cart_id', 0, 100, ''),
	('num', '5ddb9be016ddc', 1, '', 1, '', 'num【购买商品数量。类型：smallint(5)】', 0, 'num', 0, 100, ''),
	('order_sn', '5ddcb385e1ccd', 2, '', 1, '', '', 0, 'order_sn', 0, 100, ''),
	('start_time', '5de23bd0c283c', 1, '0', 1, '', 'start_time【秒杀活动开始时间,24小时制.例如:6(表示第六个小时)】', 0, 'start_time', 0, 100, ''),
	('end_time', '5de23bd0c283c', 1, '0', 1, '', 'end_time 【秒杀活动结束时间,24小时制,例如21(表示下午九点)】', 0, 'end_time', 0, 100, ''),
	('page', '5de23bd0c283c', 1, '1', 0, '', 'page 【当前页码】', 0, 'page', 0, 100, ''),
	('size', '5de23bd0c283c', 1, '1', 0, '', 'size【每页条数】', 0, 'size', 0, 100, ''),
	('activity_id', '5da6e7013ccbf', 1, '0', 1, '', 'activity_id【活动ID。类型：int(11)】', 0, 'activity_id', 0, 100, ''),
	('activity_id', '5db1769fb26fd', 1, '0', 0, '', 'activity_id【活动ID。类型：int(11)】', 0, 'activity_id', 0, 100, ''),
	('list', '5de23bd0c283c', 9, '', 0, '', 'list【活动列表数据】', 1, 'list', 0, 100, ''),
	('list--activity_type', '5de23bd0c283c', 1, '1', 1, '', 'activity_type 【活动类型,1为秒杀活动】', 1, 'list--activity_type', 0, 100, ''),
	('list--stock', '5de23bd0c283c', 1, '', 1, '', 'stock【库存数量。类型：int(10) unsigned】', 1, 'list--stock', 0, 100, ''),
	('list--goods_id', '5de23bd0c283c', 1, '', 1, '', 'goods_id【商品ID。类型：int(11)】', 1, 'list--goods_id', 0, 100, ''),
	('list--start_time', '5de23bd0c283c', 1, '', 1, '', 'start_time【允许的购买初始时间。类型：int(11) unsigned】', 1, 'list--start_time', 0, 100, ''),
	('list--end_time', '5de23bd0c283c', 1, '', 1, '', 'end_time【允许的购买结束时间。类型：int(11)】', 1, 'list--end_time', 0, 100, ''),
	('list--type', '5de23bd0c283c', 1, '', 1, '', 'type【活动类型(1:秒杀活动)。类型：tinyint(1)】', 1, 'list--type', 0, 100, ''),
	('list--activity_price', '5de23bd0c283c', 1, '', 1, '', 'activity_price【活动价格。类型：decimal(20,2) unsigned】', 1, 'list--activity_price', 0, 100, ''),
	('list--activity_id', '5de23bd0c283c', 1, '', 1, '', 'activity_id【活动ID。类型：int(11)】', 1, 'list--activity_id', 0, 100, ''),
	('list--thumb', '5de23bd0c283c', 12, '', 1, '', 'thumb【商品上传原始图。类型：varchar(255)】', 1, 'list--thumb', 0, 100, ''),
	('list--sales_num', '5de23bd0c283c', 1, '', 1, '', 'sales_num【销量。类型：int(11)】', 1, 'list--sales_num', 0, 100, ''),
	('type', '5de23bd0c283c', 1, '1', 1, '', 'type【活动类型(1:秒杀活动;2:拼团活动)。类型：tinyint(1)】', 0, 'type', 0, 100, ''),
	('page', '5dd519b898b4a', 1, '1', 1, '', 'page【当期页码】', 0, 'page', 0, 100, ''),
	('size', '5dd519b898b4a', 1, '1', 1, '', 'size【每页条数】', 0, 'size', 0, 100, ''),
	('sku_id', '5ddb9be016ddc', 1, '', 1, '', 'sku_id【规格商品id。类型：int(10) unsigned】', 0, 'sku_id', 0, 100, ''),
	('type', '5dea05416cfe9', 1, '1', 1, '', 'type【活动类型(1:秒杀活动;2:拼团)。类型：tinyint(1)】', 0, 'type', 0, 100, ''),
	('status', '5deb070b1a169', 2, 'all', 0, '', 'status【all:所有;going:进行中;full:完成】', 0, 'status', 0, 100, ''),
	('goods_id', '5def10d71d037', 1, '', 1, '', 'goods_id【商品id。类型：int(11)】', 0, 'goods_id', 0, 100, ''),
	('page', '5def10d71d037', 1, '1', 0, '', 'page【页码】', 0, 'page', 0, 100, ''),
	('size', '5def10d71d037', 1, '1', 0, '', 'size【每页条数】', 0, 'size', 0, 100, ''),
	('page', '5deb070b1a169', 1, '1', 0, '', 'page【页码】', 0, 'page', 0, 100, ''),
	('size', '5deb070b1a169', 1, '1', 0, '', 'size【每页条数】', 0, 'size', 0, 100, ''),
	('coin_id', '5db1800146ded', 1, '', 0, '', 'coin_id 【牛币券ID】', 0, 'coin_id', 0, 100, ''),
	('cart_ids', '5db1800146ded', 2, '', 1, '', 'cart_ids 【购物车ID 1,2,3,4,5】', 0, 'cart_ids', 0, 100, '这里是返回的文字字符串'),
	('use_not_coupon', '5db1800146ded', 1, '', 0, '', 'use_not_coupon 【不使用优惠券1;使用优惠券:0】', 0, 'use_not_coupon', 0, 100, ''),
	('coupon_id', '5db1800146ded', 1, '', 0, '', 'coupon_id【优惠券ID】', 0, 'coupon_id', 0, 100, ''),
	('refund_content', '5dd632ac90043', 2, '', 0, '', '', 0, 'refund_content', 0, 100, '这里是返回的文字字符串'),
	('page', '5f72db26e6259', 1, '', 0, '', '', 0, 'page', 0, 100, ''),
	('goods_id', '5dd632ac90043', 1, '', 1, '', '', 0, 'goods_id', 0, 100, ''),
	('sku_id', '5dd632ac90043', 1, '', 0, '', '', 0, 'sku_id', 0, 100, ''),
	('goods_money', '5dd632ac90043', 5, '', 1, '', '', 0, 'goods_money', 0, 100, '11.11'),
	('num', '5dd632ac90043', 1, '', 1, '', '', 0, 'num', 0, 100, ''),
	('type', '5f72db26e6259', 2, '', 1, '', 'type 【apply:申请中;deal：已处理;】', 0, 'type', 0, 100, '这里是返回的文字字符串'),
	('use_not_coupon', '5db1769fb26fd', 1, '0', 0, '', 'use_not_coupon【1:不使用优惠券;0:使用优惠券】', 0, 'use_not_coupon', 0, 100, ''),
	('id', '5ec7395785d31', 1, '', 1, '', 'id 【售后退单ID】', 0, 'id', 0, 100, ''),
	('id', '5ec4ea8cd41bd', 1, '', 1, '', 'id 【售后退单ID】', 0, 'id', 0, 100, ''),
	('page', '5ddb3e6412063', 1, '1', 0, '', '页数', 0, 'page', 0, 100, ''),
	('id', '5ec7931fc9cf9', 1, '', 1, '', 'id 【退货单ID】', 0, 'id', 0, 100, ''),
	('express_company_id', '5ec6539b3aa45', 1, '', 1, '', 'express_company_id 【物流公司ID】', 0, 'express_company_id', 0, 100, ''),
	('express_no', '5ec6539b3aa45', 2, '', 1, '', 'express_no 【物流单号】', 0, 'express_no', 0, 100, '这里是返回的文字字符串'),
	('id', '5ec6539b3aa45', 1, '', 1, '', 'id【售后申请单ID】', 0, 'id', 0, 100, ''),
	('scene', '5f4373c548e0e', 2, '', 1, '', 'scene场景', 0, 'scene', 0, 100, '这里是返回的文字字符串'),
	('page', '5f4373c548e0e', 2, '', 0, '', 'page跳转的地址，默认是主页', 0, 'page', 0, 100, '这里是返回的文字字符串'),
	('width', '5f4373c548e0e', 1, '430', 0, '', 'width图片宽度', 0, 'width', 0, 100, ''),
	('num', '5dd4e8de8c6c9', 1, '1', 1, '', '商品数量', 0, 'num', 0, 100, '');

