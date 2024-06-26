UPDATE `gaz_config` SET `cvalue` = '67' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
CREATE TABLE `gaz_XXXconfig` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `description` VARCHAR(100) DEFAULT '',
  `var` VARCHAR(100) NOT NULL DEFAULT '',
  `val` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `gaz_XXXconfig` ( `id`,`description`,`var`,`val`) VALUES (1, 'FTP server name for upload catalogue', 'server', 'localhost'),(2, 'Username for access to FTP server', 'user', 'user'),(3, 'Password for access to FTP server', 'pass', 'password'),(4, 'Joomla root directory', 'path', 'joomla\/');
-- STOP_WHILE( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)
INSERT INTO `gaz_menu_module` (`id`, `id_module`, `link`, `icon`, `class`, `translate_key`, `accesskey`, `weight`) VALUES ('54', '8', 'gaziecart_update.php', '', '', '5', '', '5');