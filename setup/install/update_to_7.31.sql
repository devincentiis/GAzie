UPDATE `gaz_config` SET `cvalue` = '126' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) VALUES ('Giorni di validità dei preventivi di vendita (DEFAULT)', 'day_of_validity', '30');
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) VALUES ('Tempi di consegna in giorni lavorativi (DEFAULT)', 'delivery_time', '15');
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) VALUES ('Valore di DEFAULT pee stampa totali sui preventivi (0=No, 1=Si)', 'print_total', '1');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)