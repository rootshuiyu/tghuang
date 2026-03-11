-- 咪咕系统业务表创建脚本
-- 数据库: xndata, 前缀: hub_

CREATE TABLE IF NOT EXISTS `hub_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '渠道编码',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '渠道名称',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图标',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '模块标识',
  `switch` tinyint(1) NOT NULL DEFAULT 1 COMMENT '开关 0=下线 1=上线',
  `active_state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '激活状态',
  `pay_type` varchar(100) NOT NULL DEFAULT '' COMMENT '支付类型',
  `pay_tpl` varchar(50) NOT NULL DEFAULT 'payment' COMMENT '支付模板',
  `pool` text COMMENT '账号池配置',
  `account_name` varchar(255) DEFAULT NULL,
  `account_ck` varchar(255) DEFAULT NULL,
  `account_value1` varchar(255) DEFAULT NULL,
  `account_value2` varchar(255) DEFAULT NULL,
  `account_value3` varchar(255) DEFAULT NULL,
  `account_value4` varchar(255) DEFAULT NULL,
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏渠道';

CREATE TABLE IF NOT EXISTS `hub_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '所属用户',
  `access_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '所属渠道',
  `mid` varchar(50) NOT NULL DEFAULT '' COMMENT '账号编码',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '账号名称',
  `remarks` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `ck` text COMMENT 'Cookie',
  `ck_pool` tinyint(1) NOT NULL DEFAULT 0 COMMENT '池类型',
  `switch` tinyint(1) NOT NULL DEFAULT 1 COMMENT '开关',
  `in_fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计入账',
  `in_number` int(10) NOT NULL DEFAULT 0 COMMENT '入账笔数',
  `limit_fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '限额',
  `limit_min_fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '最小限额',
  `limit_max_fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '最大限额',
  `limit_day_in_number` int(10) NOT NULL DEFAULT 0 COMMENT '日限入账笔数',
  `limit_day_pull_number` int(10) NOT NULL DEFAULT 0 COMMENT '日限拉取笔数',
  `limit_day_number` int(10) NOT NULL DEFAULT 0 COMMENT '日限单数',
  `match_number` int(10) NOT NULL DEFAULT 0 COMMENT '匹配次数',
  `active_time` int(10) unsigned DEFAULT NULL COMMENT '活跃时间',
  `account_state` tinyint(1) NOT NULL DEFAULT 0 COMMENT '账号状态',
  `lock_time` int(10) NOT NULL DEFAULT 0 COMMENT '锁定时间',
  `runinfo` varchar(255) NOT NULL DEFAULT '' COMMENT '运行信息',
  `sup_info` varchar(255) DEFAULT '' COMMENT '上游信息',
  `config` text COMMENT 'JSON配置',
  `value1` text,
  `value2` text,
  `value3` text,
  `value4` text,
  `value5` text,
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `access_id` (`access_id`),
  KEY `user_id` (`user_id`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏账号';

CREATE TABLE IF NOT EXISTS `hub_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `orderid` varchar(64) NOT NULL DEFAULT '' COMMENT '订单号',
  `syorder` varchar(64) NOT NULL DEFAULT '' COMMENT '系统单号',
  `suporder` varchar(64) NOT NULL DEFAULT '' COMMENT '上游单号',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `access_id` int(10) unsigned NOT NULL DEFAULT 0,
  `account_id` int(10) unsigned NOT NULL DEFAULT 0,
  `fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '金额',
  `status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态 0=下单中 1=下单失败 2=待支付 3=超时 10=成功',
  `paytime` int(10) unsigned DEFAULT NULL COMMENT '支付时间',
  `exptime` int(10) unsigned DEFAULT NULL COMMENT '过期时间',
  `notify_info` text COMMENT '回调信息JSON',
  `notify_url` varchar(500) DEFAULT '' COMMENT '回调地址',
  `return_url` varchar(500) DEFAULT '' COMMENT '返回地址',
  `pay_url` varchar(500) DEFAULT '' COMMENT '支付链接',
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderid` (`orderid`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `access_id` (`access_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付订单';

CREATE TABLE IF NOT EXISTS `hub_fund` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `value` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT '金额',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '说明',
  `action` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=支出 1=收入',
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=系统 1=手动',
  `order_id` varchar(64) DEFAULT '' COMMENT '关联订单',
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='资金流水/抽佣';

CREATE TABLE IF NOT EXISTS `hub_sup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `pid` varchar(50) NOT NULL DEFAULT '' COMMENT '商户号',
  `key` varchar(100) NOT NULL DEFAULT '' COMMENT '密钥',
  `des` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分后台';

CREATE TABLE IF NOT EXISTS `hub_huborder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `orderid` varchar(64) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '金额',
  `status` tinyint(3) NOT NULL DEFAULT 0 COMMENT '状态',
  `moudel` varchar(50) NOT NULL DEFAULT '' COMMENT '模块',
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='汇总订单';

CREATE TABLE IF NOT EXISTS `hub_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `access_id` int(10) unsigned NOT NULL DEFAULT 0,
  `sha1` varchar(64) NOT NULL DEFAULT '',
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sha1` (`sha1`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付链接映射';

CREATE TABLE IF NOT EXISTS `hub_account_count` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(10) unsigned NOT NULL DEFAULT 0,
  `sha1` varchar(64) NOT NULL DEFAULT '',
  `date` varchar(20) NOT NULL DEFAULT '',
  `in_number` int(10) NOT NULL DEFAULT 0,
  `pull_number` int(10) NOT NULL DEFAULT 0,
  `fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sha1` (`sha1`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账号统计';

CREATE TABLE IF NOT EXISTS `hub_summary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` varchar(20) NOT NULL DEFAULT '',
  `access_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `total_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_count` int(10) NOT NULL DEFAULT 0,
  `success_count` int(10) NOT NULL DEFAULT 0,
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='汇总统计';
