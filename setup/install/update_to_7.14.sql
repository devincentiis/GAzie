UPDATE `gaz_config` SET `cvalue` = '114' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
UPDATE `gaz_XXXtesdoc` SET `fattura_elettronica_zip_package`='FAE_ZIP_NOGENERATED' WHERE YEAR(`datemi`)< 2018 AND `tipdoc` LIKE 'VCO' AND `numfat` > 0;
INSERT INTO `gaz_XXXcompany_config` (`description`,`var`,`val`) VALUES ('Eventuale ultimo rigo descrittivo su ddt (multiriga)','descriptive_last_ddt','');
INSERT INTO `gaz_XXXcompany_config` (`description`,`var`,`val`) VALUES ('Nome della libreria di terze parti da usare per la eventuale trasmissione delle fatture elettroniche','send_fae_zip_package','');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)