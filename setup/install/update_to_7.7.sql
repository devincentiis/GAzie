UPDATE `gaz_config` SET `cvalue` = '104' WHERE `id` =2;
DELETE FROM `gaz_menu_module` WHERE  `link`='select_situazione_contabile.php';
DELETE FROM `gaz_menu_script` WHERE  `link`='select_situazione_contabile.php';
DELETE FROM `gaz_menu_script` WHERE  `link`='select_debiti_crediti.php';
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
DROP VIEW IF EXISTS `gaz_XXXmovimenti`;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)