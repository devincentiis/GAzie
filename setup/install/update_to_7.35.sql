UPDATE `gaz_config` SET `cvalue` = '130' WHERE `id` =2; 
UPDATE `gaz_module` SET `class`='fa fa-home' WHERE `name`='root';
UPDATE `gaz_module` SET `class`='fas fa-cash-register' WHERE `name`='vendit';
UPDATE `gaz_module` SET `class`='fa fa-shopping-cart' WHERE `name`='acquis';
UPDATE `gaz_module` SET `class`='fas fa-calculator' WHERE `name`='contab';
UPDATE `gaz_module` SET `class`='fas fa-dolly-flatbed' WHERE `name`='magazz';
UPDATE `gaz_module` SET `class`='fas fa-balance-scale' WHERE `name`='finann';
UPDATE `gaz_module` SET `class`='fas fa-cogs' WHERE `name`='config';
UPDATE `gaz_module` SET `class`='fa fa-info-circle' WHERE `name`='inform';
UPDATE `gaz_module` SET `class`='fas fa-user-friends' WHERE `name`='humres';
UPDATE `gaz_module` SET `class`='fa fa-support' WHERE `name`='suppor';
UPDATE `gaz_module` SET `class`='fas fa-industry' WHERE `name`='orderman';
UPDATE `gaz_module` SET `class`='fas fa-wikipedia-w' WHERE `name`='wiki';
UPDATE `gaz_module` SET `class`='fas fa-seedling' WHERE `name`='camp';
UPDATE `gaz_module` SET `class`='fas fa-book-reader' WHERE `name`='school';
UPDATE `gaz_module` SET `class`='fas fa-chart-line' WHERE `name`='stats';
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
UPDATE `gaz_XXXmovmag` SET `tipdoc`='MAG' WHERE `tipdoc` = '';
ALTER TABLE `gaz_XXXstaff_skills` CHANGE COLUMN `skill_value` `skill_data` VARCHAR(100) NULL DEFAULT NULL AFTER `variable_name`, ADD COLUMN `skill_description` VARCHAR(100) NULL DEFAULT NULL AFTER `skill_data`, ADD COLUMN `skill_cost` DECIMAL(8,2) NULL DEFAULT NULL AFTER `skill_description`;
ALTER TABLE `gaz_XXXstaff_worked_hours`	ADD COLUMN `id_tes` INT NULL COMMENT 'può essere usato per link con tesbro al fine di aver un documento/resoconto del lavoro eseguito' AFTER `id_orderman`;
CREATE TABLE IF NOT EXISTS `gaz_XXXexpdoc` ( `id_exp` INT NOT NULL AUTO_INCREMENT, `id_tes` INT NOT NULL DEFAULT 0, `CondizioniPagamento` VARCHAR(4) NOT NULL DEFAULT '0', `ModalitaPagamento` VARCHAR(4) NOT NULL DEFAULT '', `DataRiferimentoTerminiPagamento` DATE DEFAULT NULL, `GiorniTerminiPagamento` INT NOT NULL DEFAULT 0, `DataScadenzaPagamento` DATE DEFAULT NULL, `ImportoPagamento` decimal(10,2) NOT NULL DEFAULT 0.00, PRIMARY KEY (`id_exp`) USING BTREE,  KEY `DataScadenzaPagamento` (`DataScadenzaPagamento`), KEY `id_tes` (`id_tes`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Tabella contenente gli stessi dati degli elementi  <DatiPagamento>della fattura elettronica ed i riferimenti alla tabella gaz_NNNtesdoc. In fase di contabilizzazione delle fatture d''acquisto si avranno i dati necessari per aprire le partite  (gaz_NNNpaymov) con le scadenze  giuste. Solo se l''XML della fattura acquisita non contiene questo dettaglio allora per generarle verrà usata la modalità archiviata in anagrafica fornitore. ';
ALTER TABLE `gaz_XXXcamp_mov_sian` ADD COLUMN `status` VARCHAR(45) NOT NULL COMMENT '0=inviare - nome del file=inviato' AFTER `stabil_dep`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )