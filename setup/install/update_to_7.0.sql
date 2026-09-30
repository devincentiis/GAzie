UPDATE `gaz_config` SET `cvalue` = '94' WHERE `id` =2;
ALTER TABLE `gaz_aziend` ADD COLUMN `amm_min` VARCHAR(20) NOT NULL AFTER `fiscal_reg`;
ALTER TABLE `gaz_aziend` ADD COLUMN `mas_fixed_assets` INT NOT NULL AFTER `masban`;
ALTER TABLE `gaz_aziend` ADD COLUMN `mas_found_assets` INT NOT NULL AFTER `mas_fixed_assets`;
ALTER TABLE `gaz_aziend` ADD COLUMN `mas_cost_assets` INT NOT NULL AFTER `mas_found_assets`;
UPDATE `gaz_aziend` SET `amm_min` = '22IV';
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='report_docacq.php'), 'admin_assets.php?Insert', '', '', 16, '', 4  FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_module` SELECT MAX(id)+1, 6, 'report_assets.php', '', '', 6, '', 6  FROM `gaz_menu_module`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='report_assets.php'), 'assets_book.php', '', '', 6, '', 5  FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXassist` ADD `prezzo` VARCHAR(10) NULL AFTER `ore`;
ALTER TABLE `gaz_XXXclfoco` ADD COLUMN `id_assets` INT NOT NULL AFTER `ceeave`;
CREATE TABLE IF NOT EXISTS `gaz_XXXassets` ( `id` INT NOT NULL AUTO_INCREMENT, `id_tes` INT NOT NULL, `type_mov` INT NOT NULL,`descri` VARCHAR(100) NOT NULL, `unimis` VARCHAR(3) NOT NULL, `quantity` decimal(12,3) NOT NULL, `a_value` decimal(12,2) NOT NULL, `pagame` INT NOT NULL, `ss_amm_min` INT NOT NULL, `valamm` decimal(5,2) NOT NULL DEFAULT '0.00', `acc_fixed_assets` INT NOT NULL, `acc_found_assets` INT NOT NULL, `acc_cost_assets` INT NOT NULL, `id_no_deduct_vat` INT NOT NULL, `no_deduct_vat_rate` decimal(5,2) NOT NULL DEFAULT '0.00', `acc_no_detuct_cost` INT NOT NULL, `no_deduct_cost_rate` decimal(5,2) NOT NULL DEFAULT '0.00', PRIMARY KEY (`id`)) ENGINE=MyISAM;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
