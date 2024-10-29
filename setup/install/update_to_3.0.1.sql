UPDATE `gaz_config` SET `cvalue` = '19' WHERE `id` =2;
UPDATE `gaz_config` SET `variable` = 'update_url' WHERE `id` =5;
UPDATE `gaz_config` SET `cvalue` = '20' WHERE `id` =2;
CREATE TABLE `gaz_letter` (
  `id_let` INT NOT NULL auto_increment,
  `data` DATE NOT NULL,
  `numero` VARCHAR(20) NOT NULL,
  `clfoco` INT NOT NULL,
  `tipo` CHAR(3) NOT NULL,
  `c_a` VARCHAR(60) NOT NULL,
  `oggetto` VARCHAR(60) NOT NULL,
  `corpo` TEXT NOT NULL,
  `signature` TINYINT NOT NULL,
  `adminid` VARCHAR(20) NOT NULL,
  `last_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id_let`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
UPDATE `gaz_config` SET `cvalue` = '21' WHERE `id` =2;
ALTER TABLE `gaz_catmer` ADD `ricarico` DECIMAL( 4, 1 ) NOT NULL AFTER `image` ;