UPDATE `gaz_config` SET `cvalue` = '116' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
UPDATE `gaz_XXXcompany_config` SET `description`='Usa art. composti per seconda unità di misura (0=No, 1=Si)' WHERE  `var`='show_artico_composit';
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`) VALUES ('Eventuale collegamento Sezione IVA - Regime Fiscale es.1=RF04;2=RF12', 'sezione_regime_fiscale');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
