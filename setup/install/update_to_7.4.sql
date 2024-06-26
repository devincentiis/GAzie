UPDATE `gaz_config` SET `cvalue` = '98' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXpagame` CHANGE `incaut` `incaut` CHAR(9) NOT NULL DEFAULT '';
ALTER TABLE `gaz_XXXpagame` ADD `pagaut` INT NULL DEFAULT 0 AFTER `incaut`;
UPDATE `gaz_XXXpagame` SET `pagaut`=(SELECT `cassa_` FROM `gaz_aziend` WHERE `codice`= CONVERT('XXX',UNSIGNED INTEGER) LIMIT 1) WHERE `incaut` = 'S';
UPDATE `gaz_XXXpagame` SET `incaut`=(SELECT `cassa_` FROM `gaz_aziend` WHERE `codice`= CONVERT('XXX',UNSIGNED INTEGER) LIMIT 1) WHERE `incaut` = 'S';
ALTER TABLE `gaz_XXXpagame` CHANGE `incaut` `incaut` INT NOT NULL DEFAULT 0;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
UPDATE `gaz_config` SET `cvalue` = '99' WHERE `id` =2;
ALTER TABLE `gaz_aziend` ADD COLUMN `desez4` VARCHAR(50) NOT NULL DEFAULT '' AFTER `desez3`, ADD COLUMN `desez5` VARCHAR(50) NOT NULL DEFAULT '' AFTER `desez4`, ADD COLUMN `desez6` VARCHAR(50) NOT NULL DEFAULT '' AFTER `desez5`, ADD COLUMN `desez7` VARCHAR(50) NOT NULL DEFAULT '' AFTER `desez6`, ADD COLUMN `desez8` VARCHAR(50) NOT NULL DEFAULT '' AFTER `desez7`, ADD COLUMN `desez9` VARCHAR(50) NOT NULL DEFAULT 'AUTOFATTURE - REVERSE CHARGE' AFTER `desez8`;
UPDATE `gaz_config` SET `cvalue` = '100' WHERE `id` =2;
ALTER TABLE `gaz_aziend` ADD COLUMN `reverse_charge_sez` INT NOT NULL DEFAULT '9' AFTER `desez9`;
ALTER TABLE `gaz_aziend` DROP COLUMN `upgrie`, DROP COLUMN `upggio`, DROP COLUMN `upginv`, DROP COLUMN `upgve1`, DROP COLUMN `upgve2`, DROP COLUMN `upgve3`, DROP COLUMN `upgac1`, DROP COLUMN `upgac2`, DROP COLUMN `upgac3`, DROP COLUMN `upgco1`, DROP COLUMN `upgco2`, DROP COLUMN `upgco3`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXrigmoi`	ADD COLUMN `reverse_charge_idtes` INT NOT NULL AFTER `tipiva`;
ALTER TABLE `gaz_XXXrigmoi`	ADD COLUMN `operation_type` VARCHAR(15) NOT NULL AFTER `reverse_charge_idtes`;
ALTER TABLE `gaz_XXXaliiva` ADD COLUMN `operation_type` CHAR(15) NOT NULL DEFAULT '' AFTER `tipiva`;
INSERT INTO `gaz_XXXaliiva` (`codice`, `tipiva`, `operation_type`, `aliquo`, `fae_natura`, `descri`, `status`, `annota`) SELECT MAX(`codice`)+1, 'I', 'SERVIZ', '22', 'N6', 'REVERSE CHARGE art.17c.6 IVA al 22%','','' FROM `gaz_XXXaliiva`;
ALTER TABLE `gaz_XXXclfoco`	ADD COLUMN `operation_type` VARCHAR(15) NOT NULL DEFAULT '' AFTER `ceeave`;
ALTER TABLE `gaz_XXXclfoco`	DROP COLUMN `op_type`;
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina riepilogativo IVA', 'upgrie', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Libro Giornale', 'upggio', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Libro Inventari', 'upginv', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture di Vendita della sezione IVA 1', 'upgve1', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture di Vendita della sezione IVA 2', 'upgve2', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture di Vendita della sezione IVA 3', 'upgve3', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture di Vendita della sezione IVA 4', 'upgve4', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture di Vendita della sezione IVA 5', 'upgve5', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture di Vendita della sezione IVA 6', 'upgve6', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture di Vendita della sezione IVA 7', 'upgve7', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture di Vendita della sezione IVA 8', 'upgve8', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture di Vendita della sezione IVA 9', 'upgve9', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture degli Acquisti della sezione IVA 1', 'upgac1', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture degli Acquisti della sezione IVA 2', 'upgac2', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture degli Acquisti della sezione IVA 3', 'upgac3', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture degli Acquisti della sezione IVA 4', 'upgac4', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture degli Acquisti della sezione IVA 5', 'upgac5', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture degli Acquisti della sezione IVA 6', 'upgac6', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture degli Acquisti della sezione IVA 7', 'upgac7', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture degli Acquisti della sezione IVA 8', 'upgac8', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro Fatture degli Acquisti della sezione IVA 9', 'upgac9', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro dei Corrispettivi della sezione IVA 1', 'upgco1', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro dei Corrispettivi della sezione IVA 2', 'upgco2', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro dei Corrispettivi della sezione IVA 3', 'upgco3', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro dei Corrispettivi della sezione IVA 4', 'upgco4', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro dei Corrispettivi della sezione IVA 5', 'upgco5', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro dei Corrispettivi della sezione IVA 6', 'upgco6', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro dei Corrispettivi della sezione IVA 7', 'upgco7', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro dei Corrispettivi della sezione IVA 8', 'upgco8', '0');
INSERT INTO `gaz_XXXcompany_data` (`description`, `var`, `data`) VALUES ('Ultima pagina Registro dei Corrispettivi della sezione IVA 9', 'upgco9', '0');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
UPDATE `gaz_config` SET `cvalue` = '101' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, 5, 'report_broven.php?auxil=VOG', '', '', 46, '', 4  FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, 5, 'admin_broven.php?Insert&tipdoc=VOG', '', '', 47, '', 5  FROM `gaz_menu_script`;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, 5, 'select_evaord_gio.php', '', '', 48, '', 6  FROM `gaz_menu_script`;
ALTER TABLE `gaz_destina` ADD COLUMN `fe_cod_ufficio` VARCHAR(7) NOT NULL DEFAULT '' AFTER `e_mail`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXtesbro` ADD `weekday_repeat` INT NOT NULL AFTER `datemi`;
ALTER TABLE `gaz_XXXtesdoc` ADD `weekday_repeat` INT NOT NULL AFTER `datemi`;
INSERT INTO gaz_XXXcaucon (codice, descri, operat) SELECT * FROM (SELECT 'APE', 'APERTURA CONTI', 0) AS tmp WHERE NOT EXISTS (SELECT codice FROM gaz_XXXcaucon WHERE codice = 'APE') LIMIT 1;
INSERT INTO gaz_XXXcaucon (codice, descri, operat) SELECT * FROM (SELECT 'CHI', 'CHIUSURA CONTI', 0) AS tmp WHERE NOT EXISTS (SELECT codice FROM gaz_XXXcaucon WHERE codice = 'CHI') LIMIT 1;
ALTER TABLE `gaz_XXXtesdoc`	ADD COLUMN `id_des_same_company` INT NOT NULL AFTER `id_des`;
ALTER TABLE `gaz_XXXtesbro`	ADD COLUMN `id_des_same_company` INT NOT NULL AFTER `id_des`;
ALTER TABLE `gaz_XXXartico` ADD `preve4` DECIMAL( 14, 5 ) NULL DEFAULT '0.00000' AFTER `preve3`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)