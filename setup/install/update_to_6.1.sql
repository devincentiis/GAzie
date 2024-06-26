UPDATE `gaz_config` SET `cvalue` = '88' WHERE `id` =2;
ALTER TABLE `gaz_aziend` ADD `split_payment` INT NOT NULL AFTER `ivaera`;
UPDATE `gaz_aziend` SET `split_payment` = '588000020' WHERE 1;
UPDATE `gaz_aziend` SET `template` = '' WHERE 1; 
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
INSERT INTO `gaz_XXXaliiva` SELECT MAX(codice)+1, 'T', 22, '', 'IVA 22% SPLIT PAYMENT PA', '', '', '', '' FROM `gaz_XXXaliiva`;
INSERT INTO `gaz_XXXclfoco` (`codice`, `id_anagra`, `descri`, `print_map`, `id_agente`, `banapp`, `portos`, `spediz`, `imball`, `listin`, `destin`, `id_des`, `iban`, `sia_code`, `maxrat`, `ragdoc`, `addbol`, `speban`, `spefat`, `stapre`, `daulfa`, `daulbo`, `codpag`, `sconto`, `aliiva`, `ritenuta`, `op_type`, `allegato`, `cosric`, `ceedar`, `ceeave`, `status`, `annota`, `adminid`, `last_modified`) VALUES ('588000020', '0', 'IVA Split Payment PA', '0', '0', '0', '0', '0', '0', '0', '', '0', '', '', '0.00', '', '', '', '', '', '0000-00-00', '0000-00-00', '0', '0.00', '0', '0.0', '0', '0', '0', '', '', '', '', 'amministratore', '2008-09-28 12:58:53');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)