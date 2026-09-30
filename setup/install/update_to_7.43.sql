UPDATE `gaz_config` SET `cvalue` = '138' WHERE `id` =2;
ALTER TABLE `gaz_menu_script`	ADD INDEX (`id_menu`);
ALTER TABLE `gaz_menu_script`	ADD INDEX (`link`);
ALTER TABLE `gaz_menu_module`	ADD INDEX (`id_module`), ADD INDEX (`link`);
ALTER TABLE `gaz_module` ADD INDEX (`name`),	ADD INDEX (`link`);
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXrigdoc`	CHANGE COLUMN `prelis` `prelis` DECIMAL(17,8) NULL DEFAULT '0' AFTER `quanti`;
DROP TABLE IF EXISTS `gaz_XXXfornitore_magazzino`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )