UPDATE `gaz_config` SET `cvalue` = '163' WHERE `id` =2;
ALTER TABLE `gaz_menu_module`	ADD COLUMN `trans_code` VARCHAR(6) NULL COMMENT 'Sequenza di caratteri da digitare per avvio rapido da tastiera' AFTER `accesskey`, ADD INDEX `trans_code` (`trans_code`);
ALTER TABLE `gaz_menu_script`	ADD COLUMN `trans_code` VARCHAR(6) NULL COMMENT 'Sequenza di caratteri da digitare per avvio rapido da tastiera' AFTER `accesskey`, ADD INDEX `trans_code` (`trans_code`);
UPDATE `gaz_menu_script` SET `trans_code`='MOVCON' WHERE `link`='admin_movcon.php?Insert';
UPDATE `gaz_menu_script` SET `trans_code`='MOVMAG' WHERE `link`='admin_movmag.php?Insert';
UPDATE `gaz_menu_script` SET `trans_code`='DDTVEN' WHERE `link`='admin_docven.php?Insert&tipdoc=DDT';
UPDATE `gaz_menu_script` SET `trans_code`='FATVEN' WHERE `link`='admin_docven.php?Insert&tipdoc=FAI';
UPDATE `gaz_menu_script` SET `trans_code`='ORDVEN' WHERE `link`='admin_broven.php?Insert&tipdoc=VOR';
UPDATE `gaz_menu_script` SET `trans_code`='PREVEN' WHERE `link`='admin_broven.php?Insert&tipdoc=VPR';
UPDATE `gaz_menu_script` SET `trans_code`='ORDACQ' WHERE `link`='admin_broacq.php?tipdoc=AOR';
UPDATE `gaz_menu_script` SET `trans_code`='PREACQ' WHERE `link`='admin_broacq.php?tipdoc=APR';
CREATE TABLE IF NOT EXISTS `gaz_api_token` ( `id` INT NOT NULL AUTO_INCREMENT, `adminid` VARCHAR(20) NOT NULL,`nome` VARCHAR(60) NOT NULL DEFAULT '', `token_hash` CHAR(64) NOT NULL, `created` DATETIME NOT NULL, `expires` DATETIME NOT NULL, `revoked` TINYINT NOT NULL DEFAULT 0, `last_used` DATETIME DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `token_hash` (`token_hash`));
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
SET @adminid_existsXXX = ( SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gaz_XXXrigbro' AND COLUMN_NAME = 'adminid');
SET @adminid_queryXXX = IF(@adminid_existsXXX = 0,  "ALTER TABLE `gaz_XXXrigbro` ADD COLUMN `adminid` VARCHAR(20) NOT NULL AFTER `status`","SELECT 1");
PREPARE stmtaXXX FROM @adminid_queryXXX;
EXECUTE stmtaXXX;
DEALLOCATE PREPARE stmtaXXX;
SET @last_modified_existsXXX = ( SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gaz_XXXrigbro' AND COLUMN_NAME = 'last_modified');
SET @last_modified_queryXXX = IF(@last_modified_existsXXX = 0,  "ALTER TABLE `gaz_XXXrigbro` ADD COLUMN `last_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `adminid`","SELECT 1");
PREPARE stmtbXXX FROM @last_modified_queryXXX;
EXECUTE stmtbXXX;
DEALLOCATE PREPARE stmtbXXX;
ALTER TABLE `gaz_XXXcontract` ADD COLUMN `covered_ym` VARCHAR(7) NOT NULL DEFAULT '' COMMENT 'Ultimo mese pagato (AAAA-MM), salvato sul contratto anche senza fatture/ricevute collegate' AFTER `note`;
ALTER TABLE `gaz_XXXcontract` ADD COLUMN `invoice_descri` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Descrizione del primo rigo in fattura (vuoto = testo standard)' AFTER `covered_ym`;
ALTER TABLE `gaz_XXXassist` ADD COLUMN `id_tesdoc` INT NOT NULL DEFAULT 0 COMMENT 'Documento di vendita generato dall\'intervento' AFTER `note`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
