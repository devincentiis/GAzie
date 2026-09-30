UPDATE `gaz_config` SET `cvalue` = '71' WHERE `id` =2;
DELETE FROM `gaz_menu_script` WHERE `id` = 52;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXcaumag` DROP `upesis`;
ALTER TABLE `gaz_XXXartico` DROP `esiste`,  DROP `valore`;
INSERT IGNORE INTO `gaz_XXXcaumag` (`codice`, `descri`, `clifor`, `insdoc`, `operat`, `adminid`, `last_modified`) VALUES ('98', 'STORNO PER INVENTARIO', '0', '0', '0', '', CURRENT_TIMESTAMP);
UPDATE `gaz_XXXcaumag` SET `operat` = '1' WHERE `codice` =99;
-- STOP_WHILE( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)