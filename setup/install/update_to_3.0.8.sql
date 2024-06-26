UPDATE `gaz_config` SET `cvalue` = '33' WHERE `id` =2;
ALTER TABLE `gaz_letter` CHANGE `data` `write_date` DATE NOT NULL;  
ALTER TABLE `gaz_letter` ADD `revision` CHAR(3) NOT NULL AFTER `numero` ;
ALTER TABLE `gaz_letter` CHANGE `oggetto` `oggetto` VARCHAR( 128 ) NOT NULL;
ALTER TABLE `gaz_letter` ADD `note` VARCHAR(64) NOT NULL AFTER `signature` ;
ALTER TABLE `gaz_letter` ADD `status` VARCHAR(16) NOT NULL AFTER `note` ;
