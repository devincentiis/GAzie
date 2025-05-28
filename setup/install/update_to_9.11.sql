UPDATE `gaz_config` SET `cvalue` = '160' WHERE `id` =2;
INSERT INTO `gaz_currencies` (`curr_name`, `symbol`, `html_symbol`, `decimal_place`, `decimal_symbol`, `thousands_symbol`) VALUES ('US dollar', '$', '&#0036', '2', '.', '');
UPDATE `gaz_country` SET `istat_area`=11 WHERE  `iso`='HR';
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXbody_text`	ADD COLUMN `custom_field` TEXT NULL COMMENT 'Riferimenti generici utilizzabili sui moduli. Normalmente in formato json: {"nome_modulo":{"nome_variabile":{"valore_variabile": {}}}}' AFTER `descri`;
ALTER TABLE `gaz_XXXrigdoc`	ADD COLUMN `custom_field` TEXT NULL COMMENT 'Riferimenti generici utilizzabili sui moduli. Normalmente in formato json: {"nome_modulo":{"nome_variabile":{"valore_variabile": {}}}}' AFTER `id_orderman`;
ALTER TABLE `gaz_XXXrigbro`	ADD COLUMN `custom_field` TEXT NULL COMMENT 'Riferimenti generici utilizzabili sui moduli. Normalmente in formato json: {"nome_modulo":{"nome_variabile":{"valore_variabile": {}}}}' AFTER `id_rigmoc`;
INSERT INTO `gaz_XXXcaumag` (`codice`, `descri`, `type_cau`, `clifor`, `insdoc`, `operat`) VALUES (92, 'SCARICO PER CALO PONDERALE', 9, 0, 1, -1);
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
