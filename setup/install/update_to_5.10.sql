UPDATE `gaz_config` SET `cvalue` = '70' WHERE `id` =2;
ALTER TABLE `gaz_aziend` ADD `web_url` VARCHAR( 255 ) NOT NULL AFTER `e_mail`;
CREATE TABLE IF NOT EXISTS `gaz_municipalities` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_province` INT NOT NULL,
  `name` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `postal_code` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `dialing_code` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `stat_code` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `code_register` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `web_url` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `email` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;
CREATE TABLE IF NOT EXISTS `gaz_provinces` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_region` INT NOT NULL,
  `name` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `stat_code` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `abbreviation` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `web_url` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `email` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;
CREATE TABLE IF NOT EXISTS `gaz_regions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `iso_country` VARCHAR(2) CHARACTER SET utf8 NOT NULL,
  `name` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `capital` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `zone` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `stat_code` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `abbreviation` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `web_url` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `email` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0; 
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXartico` ADD `web_url` VARCHAR( 255 ) NOT NULL AFTER `web_public`; 
ALTER TABLE `gaz_XXXcatmer` ADD `web_url` VARCHAR( 255 ) NOT NULL AFTER `image`;
-- STOP_WHILE( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)