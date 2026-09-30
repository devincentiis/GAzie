UPDATE `gaz_config` SET `cvalue` = '144' WHERE `id` =2;
UPDATE `gaz_menu_script` SET `link`='admin_warehouse.php?Insert' WHERE  `link`='admin_wharehouse.php?Insert';
UPDATE `gaz_menu_module` SET `link`='report_warehouse.php' WHERE `link`='report_wharehouse.php';
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXcamp_mov_sian` CHANGE `varieta` `varieta` VARCHAR(250) NOT NULL COMMENT 'Campo descrittivo della varietà da utilizzare per il campo note nel registro telematico oli SIAN';
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) SELECT
'Usa art. composti per seconda unità di misura (0=No, 1=Si)', 'show_artico_composit', '0' FROM DUAL
WHERE NOT EXISTS (SELECT `var` FROM `gaz_XXXcompany_config` WHERE `var` = 'show_artico_composit' LIMIT 1);
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) VALUES ('Permetti inserimento di clienti senza dati fiscali', 'consenti_nofisc', '0');
ALTER TABLE `gaz_XXXclfoco`	ADD COLUMN `visannota` CHAR(1) NOT NULL DEFAULT 'N' AFTER `annota`;
UPDATE `gaz_XXXmovmag` SET `id_wharehouse` = 0 WHERE `id_wharehouse` IS NULL;
RENAME TABLE `gaz_XXXwharehouse` TO `gaz_XXXwarehouse`;
ALTER TABLE `gaz_XXXmovmag`	CHANGE COLUMN `id_wharehouse` `id_warehouse` INT NULL COMMENT 'Ref. alla tabella gaz_NNNwarehouse' AFTER `artico`,	DROP INDEX `id_wharehouse`,	ADD INDEX (`id_warehouse`) USING BTREE;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
