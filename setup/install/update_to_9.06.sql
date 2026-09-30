UPDATE `gaz_config` SET `cvalue` = '155' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='select_regiva.php'), 'protocol_renumbering.php', '', '', 16, '', 80  FROM `gaz_menu_script`;
UPDATE `gaz_config` SET `cvalue` = JSON_MERGE_PRESERVE(cvalue, JSON_OBJECT('RPL','vendit')) WHERE variable='report_movmag_ref_doc';
UPDATE `gaz_config` SET `cvalue` = JSON_MERGE_PRESERVE(cvalue, JSON_OBJECT('VOL','vendit')) WHERE variable='report_movmag_ref_doc';
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXclfoco`	ADD INDEX (`descri`);
ALTER TABLE `gaz_XXXrigbro`	CHANGE COLUMN `tiprig` `tiprig` INT NOT NULL AFTER `id_tes`;
ALTER TABLE `gaz_XXXrigbro`	CHANGE COLUMN `id_doc` `id_doc` INT NOT NULL COMMENT 'può essere usato come riferimento ad un figlio anche se in rigdoc (es. se questo è il rigo di un ordine evaso) o ad un genitore (es. se questo è un task di un diagramma di Gantt sarà l\'id_tes in tesbro)' AFTER `delivery_date`;
ALTER TABLE `gaz_XXXcamp_mov_sian` ADD INDEX (`recip_stocc`);
ALTER TABLE `gaz_XXXagenti`	ADD COLUMN `id_agente_coord` INT NOT NULL COMMENT 'per la gestione dei subagenti, questa è la referenza all\'agente coordinatore' AFTER `id_agente`,	ADD INDEX `id_agente_coord` (`id_agente_coord`);
ALTER TABLE `gaz_XXXagenti`	ADD COLUMN `coord_percent` DECIMAL(4,2) NOT NULL COMMENT 'rappresenta la percentuale che va all\'agente coordinatore' AFTER `base_percent`;
ALTER TABLE `gaz_XXXorderman`	ADD COLUMN `custom_field` TEXT NULL AFTER `tracking_no`;
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) VALUES ('Allega il PDF delle fatture a quella elettronica (0=SI, 1=NO)', 'attach_pdf_to_fae', '1');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
