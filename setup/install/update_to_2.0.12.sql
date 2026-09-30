UPDATE `gaz_config` SET `cvalue` = '11' WHERE `id` =2;
ALTER TABLE `gaz_admin` ADD `lang` VARCHAR( 15 ) NOT NULL AFTER `image` ,
ADD `style` VARCHAR( 15 ) NOT NULL AFTER `lang` ;
