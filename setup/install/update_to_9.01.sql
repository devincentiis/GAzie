UPDATE `gaz_config` SET `cvalue` = '151' WHERE `id` =2;
ALTER TABLE `gaz_anagraes` COLLATE='utf8mb4_0900_ai_ci';
ALTER TABLE `gaz_anagra` ADD COLUMN `fiscal_reg` VARCHAR(4) NULL AFTER `pariva`;
ALTER TABLE `gaz_admin_config` ADD COLUMN `company_id` INT NULL DEFAULT 0 AFTER `adminid`, ADD INDEX `company_id` (`company_id`);
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
UPDATE gaz_XXXtesdoc SET data_ordine = datemi WHERE id_contract >= 1 AND data_ordine < 2020-01-01;
ALTER TABLE `gaz_XXXfae_flux` CHANGE `filename_ori` `filename_ori` VARCHAR(30);
ALTER TABLE `gaz_XXXfae_flux`	CHANGE COLUMN `filename_ret` `filename_ret` VARCHAR(60) NULL AFTER `id_SDI`;
INSERT INTO `gaz_XXXcaumag` (`codice`, `descri`, `type_cau`, `clifor`, `insdoc`, `operat`) VALUES (97, 'STORNO PER INVENTARIO LOTTI', 9, 0, 0, 0);
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
