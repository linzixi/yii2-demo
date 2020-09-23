ALTER TABLE scheme_briefdoc_tpl
ADD COLUMN   `open_red_header` tinyint(1) DEFAULT '0' COMMENT '是否开启红头文件 1 开启 0 关闭',
 ADD COLUMN  `red_title` varchar(255) DEFAULT '' COMMENT '红头标题',
 ADD COLUMN  `red_number` varchar(120) DEFAULT '' COMMENT '红头编号';

 ALTER TABLE company_authority
 ADD COLUMN `resource_num` int(11)  DEFAULT 0 COMMENT '资源数 账号/年';