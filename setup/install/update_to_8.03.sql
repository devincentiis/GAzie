UPDATE `gaz_config` SET `cvalue` = '147' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='report_docacq.php'), 'fae_acq_packaging.php', '', '', 22, '', 25  FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXcampi`	COMMENT='Tabella contenente i campi ovvero i luoghi di produzione.', ADD COLUMN `used_from_modules` VARCHAR(127) NULL COMMENT 'Se FALSE o NULL appare nella select di tutti i moduli altrimenti solo in quello/i del/i modulo/i indicati qui. Usare la virgola per separare i nomi dei moduli quando più di uno.' COLLATE 'utf8_general_ci' AFTER `codice_prodotto_usato`;
ALTER TABLE `gaz_XXXrigdoc`	ADD INDEX (`codice_fornitore`);
ALTER TABLE `gaz_XXXtesbro` ADD `custom_field` TEXT NULL DEFAULT NULL COMMENT 'Riferimenti generici utilizzabili sui moduli. Normalmente in formato json: {"nome_modulo":{"nome_variabile":{"valore_variabile": {}}}}' AFTER `status`;
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) SELECT 'Impacchetta le fatture elettroniche degli scontrini non anonimi (0=No, 1=Si)', 'fae_ticket_pack', '1' FROM DUAL WHERE NOT EXISTS (SELECT `var` FROM `gaz_XXXcompany_config` WHERE `var` = 'fae_ticket_pack' LIMIT 1);
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
