UPDATE `gaz_config` SET `cvalue` = '153' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='report_artico.php'), 'change_codart.php', '', '', 16, '', 10  FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXrigbro`	CHANGE COLUMN `codice_fornitore` `codice_fornitore` VARCHAR(50) NOT NULL COMMENT 'Mappatura di codart con il codice usato dal fornitore in caso di acquisto, potrebbe essere usato anche per mappare i codici clienti ad es. nella acquisizione ordini da clienti' AFTER `codart`, ADD INDEX `codice_fornitore` (`codice_fornitore`);
ALTER TABLE `gaz_XXXtesbro`	ADD INDEX `tipdoc` (`tipdoc`);
ALTER TABLE `gaz_XXXorderman`	ADD INDEX `id_tesbro` (`id_tesbro`);
ALTER TABLE `gaz_XXXtesdoc` ADD COLUMN `custom_field` TEXT NULL COMMENT 'Usabile per contenere le scelte dell\'utente in ambito dello specifico modulo. Normalmente in formato json: {"nome_modulo":{"nome_variabile": {"valorei_variabile"}}' AFTER `status`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
