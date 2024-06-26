UPDATE `gaz_config` SET `cvalue` = '145' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) SELECT
'Invia i pdf dei reports in finestra modale (0=No, 1=Si)', 'pdf_reports_send_to_modal', '1' FROM DUAL
WHERE NOT EXISTS (SELECT `var` FROM `gaz_XXXcompany_config` WHERE `var` = 'pdf_reports_send_to_modal' LIMIT 1);
INSERT INTO `gaz_XXXcaumag` (`codice`, `descri`, `type_cau`, `clifor`, `insdoc`, `operat`) SELECT
84, 'CARICO PER TRASFERIMENTO DA ALTRO MAGAZZINO', 9, 0, 0, 1 FROM DUAL
WHERE NOT EXISTS (SELECT `codice` FROM `gaz_XXXcaumag` WHERE `codice` = 84 LIMIT 1);
INSERT INTO `gaz_XXXcaumag` (`codice`, `descri`, `type_cau`, `clifor`, `insdoc`, `operat`) SELECT
83, 'SCARICO PER TRASFERIMENTO VERSO ALTRO MAGAZZINO', 9, 0, 0, -1 FROM DUAL
WHERE NOT EXISTS (SELECT `codice` FROM `gaz_XXXcaumag` WHERE `codice` = 83 LIMIT 1);
UPDATE `gaz_XXXartico` SET `good_or_service`= 0 WHERE `good_or_service` IS NULL;
ALTER TABLE `gaz_XXXartico` CHANGE COLUMN `good_or_service` `good_or_service` INT NOT NULL AFTER `movimentabile`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
