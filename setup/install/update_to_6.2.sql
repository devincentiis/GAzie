UPDATE `gaz_config` SET `cvalue` = '89' WHERE `id` =2;
DELETE FROM `gaz_menu_module` WHERE `id_module` = (SELECT id FROM `gaz_module` WHERE `name`='gazpme' LIMIT 1);
DELETE FROM `gaz_menu_module` WHERE `id_module` = (SELECT id FROM `gaz_module` WHERE `name`='gazpma' LIMIT 1);
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXartico` ADD `lot_or_serial` INT NOT NULL AFTER `descri`;
ALTER TABLE `gaz_XXXmovmag` ADD `id_lotmag` INT NOT NULL AFTER `artico`;
CREATE TABLE IF NOT EXISTS `gaz_XXXlotmag` (  `id` INT NOT NULL AUTO_INCREMENT,  `id_purchase` INT NOT NULL, `lot_or_serial` VARCHAR(100) NOT NULL, `description` VARCHAR(100) NOT NULL,  `id_doc` INT NOT NULL,  `expiry` DATE NOT NULL,  PRIMARY KEY (`id`)) ENGINE=MyISAM AUTO_INCREMENT=1 ;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)