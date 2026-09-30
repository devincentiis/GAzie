UPDATE `gaz_config` SET `cvalue` = '92' WHERE `id` =2;
ALTER TABLE `gaz_config` CHANGE COLUMN `last_modified` `last_modified` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER `show`;
INSERT INTO `gaz_config` (`description`, `variable`, `cvalue`) VALUES ('backup to keep', 'keep_backup', '200');
INSERT INTO `gaz_config` (`description`, `variable`, `cvalue`) VALUES ('leave free space in backup', 'freespace_backup', '10');
INSERT INTO `gaz_config` (`description`, `variable`, `cvalue`) VALUES ('backup files', 'file_backup', '0');
UPDATE `gaz_config` SET `last_modified` = CURRENT_TIMESTAMP WHERE 1;
ALTER TABLE `gaz_anagra` ADD `fatt_email` BOOLEAN NOT NULL DEFAULT FALSE AFTER `e_mail`;
INSERT INTO `gaz_menu_module` SELECT MAX(id)+1, 3, 'report_agenti_forn.php', '', '', 9, '', 9  FROM `gaz_menu_module`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT MAX(id) FROM `gaz_menu_module`), 'admin_agenti_forn.php?Insert', '', '', 14, '', 1  FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
CREATE TABLE gaz_XXXagenti_forn (`id_agente` INT NOT NULL,`id_fornitore` INT NOT NULL,`base_percent` decimal(4,2) NOT NULL,`tipo_contratto` TINYINT NOT NULL,`adminid` VARCHAR(20) NOT NULL,`last_modified` TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE CURRENT_TIMESTAMP) ENGINE=MyISAM;
UPDATE `gaz_XXXtesdoc` SET `ddt_type`= 'T' WHERE `ddt_type`='D';
ALTER TABLE gaz_XXXclfoco ADD INDEX `id_agente` (`id_agente`);
ALTER TABLE gaz_XXXrigmoc ADD INDEX `codcon` (`codcon`);
ALTER TABLE gaz_XXXpaymov ADD INDEX `id_rigmoc_pay` (`id_rigmoc_pay`);
ALTER TABLE gaz_XXXpaymov ADD INDEX `id_rigmoc_doc` (`id_rigmoc_doc`);
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
