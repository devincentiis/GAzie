UPDATE `gaz_config` SET `cvalue` = '95' WHERE `id` =2;
ALTER TABLE `gaz_aziend` ADD COLUMN `lost_cost_assets` INT NOT NULL AFTER `mas_cost_assets`;
ALTER TABLE `gaz_aziend` ADD COLUMN `min_rate_deprec` DECIMAL(4,1) NOT NULL DEFAULT '0.0' AFTER `lost_cost_assets`;
ALTER TABLE `gaz_aziend` ADD COLUMN `super_amm_account` INT NOT NULL AFTER `lost_cost_assets`;
ALTER TABLE `gaz_aziend` ADD COLUMN `super_amm_rate` DECIMAL(4,1) NOT NULL DEFAULT '40.0' AFTER `super_amm_account`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='report_assets.php'), 'depreciation_assets.php', '', '', 7, '', 7  FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='select_situazione_contabile.php'), 'select_debiti_crediti.php', '', '', 7, '', 2  FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='../magazz/report_statis.php'),  'select_esportazione_articoli_venduti.php', '', '', 40, '', 3  FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXassets` ADD COLUMN `lost_cost_value` DECIMAL(12,2) NOT NULL DEFAULT '0.00' AFTER `no_deduct_cost_rate`;
ALTER TABLE `gaz_XXXassets` ADD COLUMN `super_ammort` DECIMAL(12,2) NOT NULL DEFAULT '0.00' AFTER `lost_cost_value`;
CREATE OR REPLACE VIEW gaz_XXXmovimenti AS SELECT * FROM gaz_XXXpaymov paymov JOIN gaz_XXXrigmoc rigmoc ON (paymov.id_rigmoc_pay = rigmoc.id_rig) union all select * from gaz_XXXpaymov paymov JOIN gaz_XXXrigmoc rigmoc ON (paymov.id_rigmoc_doc = rigmoc.id_rig );
ALTER TABLE `gaz_XXXclfoco` ADD `sel4esp_art` BOOLEAN DEFAULT false AFTER `id_assets`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
