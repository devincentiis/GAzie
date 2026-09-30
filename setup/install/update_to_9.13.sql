UPDATE `gaz_config` SET `cvalue` = '162' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id_menu FROM `gaz_menu_script` WHERE `link`='select_evaord.php'), 'select_order_status.php', '', '', 63, '', 3 FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id_menu FROM `gaz_menu_script` WHERE `link`='fae_acq_packaging.php'), 'select_docacq_print.php', '', '', 23, '', 30 FROM `gaz_menu_script`;
ALTER TABLE `gaz_anagra` ADD INDEX (`ragso1`), ADD INDEX (`ragso2`);
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXstaff`	ADD INDEX (`employment_status`);
ALTER TABLE `gaz_XXXstaff`	CHANGE COLUMN `id_contract` `id_contract` VARCHAR(10) NOT NULL DEFAULT '' AFTER `id_clfoco`;
ALTER TABLE `gaz_XXXassets`	ADD COLUMN `conn_status`  VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'Stato della connessione' AFTER `linkmode`;
ALTER TABLE `gaz_XXXassets`	ADD COLUMN `last_conn` TIMESTAMP NULL COMMENT 'Ultima connessione rilevata' AFTER `conn_status`;
ALTER TABLE `gaz_XXXassets`	ADD COLUMN `custom_field` TEXT NULL COMMENT 'Riferimenti generici utilizzabili sui moduli. Normalmente in formato json: {"nome_modulo":{"nome_variabile":{"valore_variabile": {}}}}' AFTER `super_ammort`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
