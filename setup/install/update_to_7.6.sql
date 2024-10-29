UPDATE `gaz_config` SET `cvalue` = '103' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='select_liqiva.php'), 'comunicazione_dati_fatture.php', '', '', 10, '', 15  FROM `gaz_menu_script`;
UPDATE `gaz_menu_module` SET `link`='comunicazioni_doc.php' WHERE `link`='select_liqiva.php';
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='select_regiva.php'), 'select_liqiva.php', '', '', 11, '', 15  FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='comunicazioni_doc.php'), 'report_comunicazioni_dati_fatture.php', '', '', 12, '', 20  FROM `gaz_menu_script`;
ALTER TABLE `gaz_anagra` CHANGE COLUMN `legrap` `legrap_pf_nome` VARCHAR(60) NOT NULL DEFAULT '' AFTER `sedleg`;
ALTER TABLE `gaz_anagra` ADD COLUMN `legrap_pf_cognome` VARCHAR(60) NOT NULL DEFAULT '' AFTER `legrap_pf_nome`;
ALTER TABLE `gaz_aziend` CHANGE COLUMN `legrap` `legrap_pf_nome` VARCHAR(60) NOT NULL DEFAULT '' AFTER `sedleg`;
ALTER TABLE `gaz_aziend` ADD COLUMN `legrap_pf_cognome` VARCHAR(60) NOT NULL DEFAULT '' AFTER `legrap_pf_nome`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
CREATE TABLE IF NOT EXISTS `gaz_XXXcomunicazioni_dati_fatture` ( `id` INT NOT NULL AUTO_INCREMENT, `anno` INT DEFAULT NULL, `periodicita` VARCHAR(1) NOT NULL DEFAULT 'M', `trimestre_semestre` INT DEFAULT NULL, `nome_file_DTE` VARCHAR(100) DEFAULT '', `nome_file_DTR` VARCHAR(100) DEFAULT '', `nome_file_ZIP` VARCHAR(100) DEFAULT '', `IdFile` VARCHAR(18) DEFAULT '', `nome_file_ANN` VARCHAR(100) DEFAULT '', PRIMARY KEY (`id`)) ENGINE=MyISAM COMMENT='tabella contenente i dati delle Comunicazioni dati fatture (spesometro) secondo le specifiche tecniche dell''Agenzia delle Entrate';
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)