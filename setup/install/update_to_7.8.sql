UPDATE `gaz_config` SET `cvalue` = '105' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXartico` ADD `last_used` DATE NULL AFTER `clfoco`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
UPDATE `gaz_config` SET `cvalue` = '106' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXtesmov`	COMMENT='Testate dei movimenti contabili';
ALTER TABLE `gaz_XXXrigmoc`	COMMENT='Righi dei movimenti contabili le cui testate sono contenute in gaz_NNNtesmov';
ALTER TABLE `gaz_XXXrigmoi`	COMMENT='Righi dei movimenti IVA le cui testate sono contenute in gaz_NNNtesmov';
ALTER TABLE `gaz_XXXtesbro`	COMMENT='Testate documenti che non hanno valenza fiscale. Ordini, preventivi, commesse, ecc';
ALTER TABLE `gaz_XXXrigbro`	COMMENT='Righi dei documenti le cui testate sono contenute in gaz_NNNtesbro';
ALTER TABLE `gaz_XXXtesdoc`	COMMENT='Testate documenti fiscalmente validi. Fatture, Note credito, , Documenti di Trasporto, ecc';
ALTER TABLE `gaz_XXXrigdoc`	COMMENT='Righi dei documenti le cui testate sono contenute in gaz_NNNtesdoc';
ALTER TABLE `gaz_XXXtesbro`	ADD COLUMN `id_orderman` INT NOT NULL AFTER `id_con`;
CREATE TABLE `gaz_XXXorderman` ( `id` INT NOT NULL AUTO_INCREMENT, `order_type` VARCHAR(3) NULL DEFAULT NULL, `description` TEXT NOT NULL, `add_info` VARCHAR(80) NULL DEFAULT NULL, `id_tesbro` INT NOT NULL, `adminid` VARCHAR(20) NOT NULL DEFAULT '',	`last_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) COLLATE='utf8_general_ci' ENGINE=MyISAM;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)