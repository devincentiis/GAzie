UPDATE `gaz_config` SET `cvalue` = '82' WHERE `id` =2;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXclfoco` ADD `sia_code` VARCHAR( 5 ) NOT NULL AFTER `iban` ;
ALTER TABLE `gaz_XXXeffett` ADD `protoc` INT NOT NULL AFTER `seziva`; 
TRUNCATE TABLE `gaz_XXXpaymov`; 
ALTER TABLE `gaz_XXXpaymov` CHANGE `id_tesdoc_ref` `id_tesdoc_ref` VARCHAR( 15 ) NOT NULL;
ALTER TABLE `gaz_XXXcaucon` ADD `pay_schedule` INT NOT NULL AFTER `operat`;
UPDATE `gaz_XXXcaucon` SET `pay_schedule` = 1 WHERE (`codice` = 'FAP' OR `codice` = 'FND' OR `codice` = 'AFC' OR `codice` = 'FNC' OR `codice` = 'FDR' OR `codice` = 'FDE' OR `codice` = 'FAI' OR `codice` = 'FAD' OR `codice` = 'AFA');
INSERT INTO `gaz_XXXcaucon` (`codice`, `descri`, `insdoc`, `regiva`, `operat`, `pay_schedule`, `contr1`, `tipim1`, `daav_1`, `contr2`, `tipim2`, `daav_2`, `contr3`, `tipim3`, `daav_3`, `contr4`, `tipim4`, `daav_4`, `contr5`, `tipim5`, `daav_5`, `contr6`, `tipim6`, `daav_6`, `adminid`, `last_modified`) VALUES
('RIB', 'EMESSA RICEVUTA BANCARIA', 1, 0, 0, 2, 103000000, 'A', 'A', 597000021, 'A', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 'amministratore', '0000-00-00 00:00:00'),
('TRA', 'EMESSA CAMBIALE TRATTA', 1, 0, 0, 2, 103000000, 'A', 'A', 597000021, 'A', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 'amministratore', '0000-00-00 00:00:00'),
('MAV', 'EMESSO MAV', 1, 0, 0, 2, 103000000, 'A', 'A', 597000021, 'A', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 'amministratore', '0000-00-00 00:00:00'),
('RIM', 'PAGAMENTO DA CLIENTE IN CONTANTI', 0, 0, 0, 2, 103000000, 'A', 'A', 108000030, 'A', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 'amministratore', '0000-00-00 00:00:00'),
('BON', 'RICEVUTO BONIFICO DA CLIENTE', 0, 0, 0, 2, 103000000, 'A', 'A', 597000021, 'A', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 'amministratore', '0000-00-00 00:00:00'),
('PFC', 'PAGATO FORNITORE IN CONTANTI', 0, 0, 0, 2, 212000000, 'A', 'D', 108000030, 'A', 'A', 0, '', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 'amministratore', '0000-00-00 00:00:00'),
('PFB', 'PAGATO FORNITORE CON BONIFICO', 0, 0, 0, 2, 212000000, 'A', 'D', 597000021, 'A', 'A', 0, '', 'D', 0, '', 'D', 0, '', 'D', 0, '', 'D', 'amministratore', '2013-04-04 09:30:13');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)