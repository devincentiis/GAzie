UPDATE `gaz_config` SET `cvalue` = '84' WHERE `id` =2;
INSERT INTO `gaz_config` (`description`, `variable`, `cvalue`) VALUES ('Intermediary company', 'intermediary', 0 );
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)