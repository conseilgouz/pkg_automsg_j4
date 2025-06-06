DROP TABLE IF EXISTS `#__automsg`;
DROP TABLE IF EXISTS `#__automsg_errors`;
DROP TABLE IF EXISTS `#__automsg_waiting`;
DROP TABLE IF EXISTS `#__automsg_public`;
DROP TABLE IF EXISTS `#__automsg_config`;
DELETE FROM `#__mail_templates` WHERE `extension` = 'com_automsg'
