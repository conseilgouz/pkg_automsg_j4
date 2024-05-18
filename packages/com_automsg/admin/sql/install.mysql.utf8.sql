CREATE TABLE IF NOT EXISTS `#__automsg` (
`id` integer NOT NULL AUTO_INCREMENT,
`state` integer NOT NULL default 0,
`article_id` integer NOT NULL ,
`created` datetime NOT NULL DEFAULT '1980-01-01 00:00:00',
`modified` datetime NULL DEFAULT NULL,
`sent` datetime NULL DEFAULT NULL,
`cr` varchar(50) NOT NULL default '',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Auto Message Table';

CREATE TABLE IF NOT EXISTS `#__automsg_config` (
`id` integer NOT NULL AUTO_INCREMENT,
`state` integer NOT NULL default 0,
`usergroups` varchar(200) NOT NULL default '',
`categories` varchar(200) NOT NULL default '',
`msgcreator` tinyint NOT NULL default 0,
`msgauto` tinyint NOT NULL default 0,
`async` tinyint NOT NULL default 0,
`limit` tinyint NOT NULL default 0,
`maillimit` integer NOT NULL default 0,
`maildelay` integer NOT NULL default 1,
`report` tinyint NOT NULL default 0,
`log` tinyint NOT NULL default 0,
`created` datetime NOT NULL DEFAULT '1980-01-01 00:00:00',
`modified` datetime NULL DEFAULT NULL,
`save_execution_rules` text ,
`save_cron_rules` text ,
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='AutoMsg Config Table';

CREATE TABLE IF NOT EXISTS `#__automsg_errors` (
`id` integer NOT NULL AUTO_INCREMENT,
`state` integer NOT NULL default 0,
`userid` integer NOT NULL,
`articleids` text,
`error` text,  
`timestamp` datetime NOT NULL DEFAULT '1980-01-01 00:00:00',
`modified` datetime NULL DEFAULT NULL,
`retry`integer not null default 0,
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='AutoMsg Error Table';

CREATE TABLE IF NOT EXISTS `#__automsg_waiting` (
`id` integer NOT NULL AUTO_INCREMENT,
`state` integer NOT NULL default 0,
`userid` integer NOT NULL,
`articleids` text,
`timestamp` datetime NOT NULL DEFAULT '1980-01-01 00:00:00',
`modified` datetime NULL DEFAULT NULL,
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='AutoMsg Error Table';


INSERT INTO `#__automsg_config` (`id`,`state`,`usergroups`,`categories`) VALUES (1,0,'8','');