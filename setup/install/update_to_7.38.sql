UPDATE `gaz_config` SET `cvalue` = '133' WHERE `id` =2; 
DELETE FROM `gaz_menu_script` WHERE  `link`='report_acqddt.php';
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )