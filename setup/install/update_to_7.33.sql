UPDATE `gaz_config` SET `cvalue` = '128' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXassets`	ADD COLUMN `codice_artico` VARCHAR(15) NOT NULL COMMENT 'verrà valorizzato con un codice articolo se si vorranno gestire i beni strumentali, e sarà lo stesso valore codice in gaz_NNNartico ' AFTER `descri`, ADD INDEX `codice_artico` (`codice_artico`);
ALTER TABLE `gaz_XXXcontract` ADD COLUMN `id_assets` INT NOT NULL COMMENT 'Utilizzato in caso di contratto asssociato ad un bene strumentale che è soggetto a manutenzione periodica' AFTER `id_customer`, ADD INDEX `id_assets` (`id_assets`);
ALTER TABLE `gaz_XXXartico`	ADD COLUMN `id_assets` INT NOT NULL COMMENT 'Riferimento al bene strumentale ( id di gaz_NNNassets), quando maggiore di 0 è un bene strumentale e come tale in genere non in vendita ' AFTER `codice_fornitore`, ADD INDEX `id_assets` (`id_assets`);
ALTER TABLE `gaz_XXXassets`	ADD COLUMN `install_date` DATE NULL COMMENT 'Data di installazione del bene ammortizzabile' AFTER `codice_artico`;
INSERT INTO `gaz_XXXcaucon_rows` (`caucon_cod`, `clfoco_ref`, `type_imp`, `dare_avere`, `n_order`) VALUES ('AFT', '212000000', 'A', 'A', '3'),('AFT', '330000004', 'B', 'D', '1'),('AFT', '106000001', 'C', 'D', '2');
INSERT INTO `gaz_XXXcaucon` (`codice`, `descri`, `insdoc`, `regiva`, `operat`, `pay_schedule`, `adminid`) VALUES ('AFT', 'FATTURA DI ACQUISTO DIFFERITA', '1', '6', '1', '1', 'amministratore');
UPDATE `gaz_XXXcaucon` SET `descri`='FATTURA DI ACQUISTO IMMEDIATA' WHERE  `codice`='AFA';
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)