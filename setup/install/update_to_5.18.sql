UPDATE `gaz_config` SET `cvalue` = '77' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
UPDATE `gaz_XXXtesmov` SET id_doc = ( SELECT `gaz_XXXtesdoc`.id_tes FROM `gaz_XXXtesdoc` WHERE `gaz_XXXtesmov`.id_tes = `gaz_XXXtesdoc`.id_con LIMIT 1) WHERE `id_doc`=0;
-- STOP_WHILE( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)