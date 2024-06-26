UPDATE `gaz_config` SET `cvalue` = '156' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
UPDATE `gaz_XXXcompany_config` SET `description`='Periodicità in minuti dei controlli presenza alerts su menù (min.15)' WHERE  `var`='menu_alerts_check';
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
