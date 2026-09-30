UPDATE `gaz_config` SET `cvalue` = '63' WHERE `id` =2;
ALTER TABLE `gaz_aziend` CHANGE `pariva` `pariva` VARCHAR( 12 ); 
ALTER TABLE `gaz_aziend` ADD `sales_return` INT NOT NULL AFTER `omaggi`;
ALTER TABLE `gaz_aziend` ADD `purchases_return` INT NOT NULL AFTER `cost_var`;
ALTER TABLE `gaz_anagra` CHANGE `capspe` `capspe` VARCHAR( 10 ) NOT NULL DEFAULT ''; 
ALTER TABLE `gaz_aziend` CHANGE `capspe` `capspe` VARCHAR( 10 ) NOT NULL DEFAULT '';
INSERT INTO `gaz_config` (`id`, `description`, `variable`, `cvalue`, `weight`, `show`, `last_modified`) VALUES ('9', 'Locale UTF8 set for Windows system', 'win_locale', 'ita', '0', '0', '0000-00-00 00:00:00'), ('10', 'Locale UTF8 set for Other system', 'lin_locale', 'it_IT.UTF-8', '0', '0', '0000-00-00 00:00:00'); 
UPDATE `gaz_config` SET `cvalue` = '64' WHERE `id` =2;
INSERT INTO `gaz_config` (`id`, `description`, `variable`, `cvalue`, `weight`, `show`, `last_modified`) VALUES ('11', 'Installation language', 'install_lang', 'italian', '0', '0', '0000-00-00 00:00:00');