UPDATE `gaz_config` SET `cvalue` = '26' WHERE `id` =2;
UPDATE `gaz_menu_script` SET `link` = 'select_docforprint.php' WHERE `id` = 5 LIMIT 1 ;
UPDATE `gaz_config` SET `cvalue` = '27' WHERE `id` =2;
ALTER TABLE `gaz_tesdoc` CHANGE `numfat` `numfat` VARCHAR( 20 ) NOT NULL DEFAULT '0';
ALTER TABLE `gaz_tesdoc` CHANGE `codage` `id_agente` INT NOT NULL;  
ALTER TABLE `gaz_tesdoc` ADD `net_weight` DECIMAL( 12, 2 ) NOT NULL AFTER `sconto` ,
ADD `gross_weight` DECIMAL( 12, 2 ) NOT NULL AFTER `net_weight` ;
ALTER TABLE `gaz_rigdoc` CHANGE `quanti` `quanti` DECIMAL( 8, 2 ) NULL DEFAULT '0.0';
ALTER TABLE `gaz_rigdoc` ADD `provvigione` DECIMAL( 4, 2 ) NOT NULL AFTER `codric` ;
ALTER TABLE `gaz_tesbro` CHANGE `numfat` `numfat` VARCHAR( 20 ) NOT NULL DEFAULT '0';
ALTER TABLE `gaz_tesbro` CHANGE `codage` `id_agente` INT NOT NULL;  
ALTER TABLE `gaz_tesbro` ADD `net_weight` DECIMAL( 12, 2 ) NOT NULL AFTER `sconto` ,
ADD `gross_weight` DECIMAL( 12, 2 ) NOT NULL AFTER `net_weight` ;
ALTER TABLE `gaz_rigbro` CHANGE `quanti` `quanti` DECIMAL( 8, 2 ) NULL DEFAULT '0.0';
ALTER TABLE `gaz_rigbro` ADD `provvigione` DECIMAL( 4, 2 ) NOT NULL AFTER `codric` ; 
ALTER TABLE `gaz_clfoco` CHANGE `ccboni` `iban` VARCHAR( 32 ) NOT NULL DEFAULT '0';
ALTER TABLE `gaz_clfoco` CHANGE `pariva` `pariva` VARCHAR( 12 ) NOT NULL DEFAULT '0';
ALTER TABLE `gaz_clfoco` ADD `country` VARCHAR( 2 ) NOT NULL AFTER `prospe` ;
ALTER TABLE `gaz_clfoco` ADD `latitude` DECIMAL( 8, 5 ) NOT NULL AFTER `country` ;
ALTER TABLE `gaz_clfoco` ADD `longitude` DECIMAL( 8, 5 ) NOT NULL AFTER `latitude` ;
ALTER TABLE `gaz_clfoco` ADD `print_map` TINYINT NOT NULL AFTER `longitude` ;
ALTER TABLE `gaz_artico` ADD `id_cost` INT NOT NULL AFTER `codcon` ;
ALTER TABLE `gaz_aziend` ADD `cost_tra` INT NOT NULL AFTER `impacq` ,
ADD `cost_imb` INT NOT NULL AFTER `cost_tra` ,
ADD `cost_var` INT NOT NULL AFTER `cost_imb` ,
ADD `latitude` DECIMAL( 8, 5 ) NOT NULL AFTER `cost_var` ,
ADD `longitude` DECIMAL( 8, 5 ) NOT NULL AFTER `latitude` ;
CREATE TABLE `gaz_country` (
  `iso` CHAR(2) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `printable_name` VARCHAR(80) NOT NULL,
  `iso3` CHAR(3) DEFAULT NULL,
  `numcode` smallINT DEFAULT NULL,
  `bank_code_lenght` TINYINT NOT NULL,
  `bank_code_fix` TINYINT NOT NULL,
  `bank_code_alpha` TINYINT NOT NULL,
  `account_number_lenght` TINYINT NOT NULL,
  `account_number_fix` TINYINT NOT NULL,
  `account_number_alpha` TINYINT NOT NULL,
  PRIMARY KEY  (`iso`)
);
INSERT INTO `gaz_country` (`iso`, `name`, `printable_name`, `iso3`, `numcode`, `bank_code_lenght`, `bank_code_fix`, `bank_code_alpha`, `account_number_lenght`, `account_number_fix`, `account_number_alpha`) VALUES 
('AD', 'ANDORRA', 'Andorra', 'AND', 20, 8, 1, 0, 12, 1, 1),
('AT', 'AUSTRIA', 'Austria', 'AUT', 40, 5, 1, 0, 11, 0, 0),
('BE', 'BELGIUM', 'Belgium', 'BEL', 56, 3, 1, 0, 9, 1, 0),
('CH', 'SWITZERLAND', 'Switzerland', 'CHE', 756, 5, 0, 0, 12, 0, 1),
('DE', 'GERMANY', 'Germany', 'DEU', 276, 8, 1, 0, 10, 0, 0),
('DK', 'DENMARK', 'Denmark', 'DNK', 208, 4, 0, 0, 10, 0, 0),
('ES', 'SPAIN', 'Spain', 'ESP', 724, 10, 1, 0, 10, 1, 0),
('FI', 'FINLAND', 'Finland', 'FIN', 246, 6, 1, 0, 8, 0, 0),
('FO', 'FAROE ISLANDS', 'Faroe Islands', 'FRO', 234, 4, 0, 0, 10, 0, 0),
('FR', 'FRANCE', 'France', 'FRA', 250, 10, 1, 0, 13, 1, 1),
('GB', 'UNITED KINGDOM', 'United Kingdom', 'GBR', 826, 10, 1, 1, 8, 0, 0),
('GL', 'GREENLAND', 'Greenland', 'GRL', 304, 4, 0, 0, 10, 0, 0),
('GR', 'GREECE', 'Greece', 'GRC', 300, 7, 1, 0, 16, 0, 1),
('HU', 'HUNGARY', 'Hungary', 'HUN', 348, 0, 1, 1, 24, 0, 1),
('IE', 'IRELAND', 'Ireland', 'IRL', 372, 10, 1, 1, 8, 1, 0),
('IS', 'ICELAND', 'Iceland', 'ISL', 352, 4, 1, 0, 18, 1, 0),
('IT', 'ITALIA', 'Italia', 'ITA', 380, 11, 1, 1, 12, 1, 1),
('LI', 'LIECHTENSTEIN', 'Liechtenstein', 'LIE', 438, 5, 0, 0, 12, 0, 1),
('LU', 'LUXEMBOURG', 'Luxembourg', 'LUX', 442, 3, 1, 0, 13, 1, 1),
('MC', 'MONACO', 'Monaco', 'MCO', 492, 10, 1, 0, 13, 1, 1),
('NL', 'NETHERLANDS', 'Netherlands', 'NLD', 528, 4, 1, 1, 10, 0, 0),
('NO', 'NORWAY', 'Norway', 'NOR', 578, 4, 1, 0, 7, 1, 0),
('PL', 'POLAND', 'Poland', 'POL', 616, 8, 1, 0, 16, 0, 1),
('PT', 'PORTUGAL', 'Portugal', 'PRT', 620, 8, 1, 0, 13, 1, 0),
('SE', 'SWEDEN', 'Sweden', 'SWE', 752, 3, 1, 0, 17, 0, 0),
('SM', 'SAN MARINO', 'San Marino', 'SMR', 674, 11, 1, 1, 12, 1, 1);
CREATE TABLE `gaz_agenti` (
`id_agente` INT NOT NULL ,
`id_fornitore` INT NOT NULL
) ;
CREATE TABLE `gaz_provvigioni` (
`id_agente` INT NOT NULL ,
`id_provvigione` INT NOT NULL ,
`cod_articolo` VARCHAR( 15 ) NOT NULL ,
`cod_catmer` INT NOT NULL ,
`percentuale` DECIMAL( 4, 2 ) NOT NULL
) ;
UPDATE `gaz_config` SET `cvalue` = '28' WHERE `id` =2;
ALTER TABLE `gaz_pagame` ADD `id_bank` INT NOT NULL AFTER `tiprat` ;
UPDATE `gaz_menu_script` SET `link` = 'admin_pagame.php?Insert' WHERE `id` = 43 LIMIT 1 ;
UPDATE `gaz_config` SET `cvalue` = '29' WHERE `id` =2;
ALTER TABLE `gaz_country` ADD `bank_code_pos` TINYINT NOT NULL AFTER `numcode` ;
ALTER TABLE `gaz_country` ADD `account_number_pos` TINYINT NOT NULL AFTER `bank_code_alpha` ;
UPDATE `gaz_country` SET `bank_code_pos` = '6', `bank_code_lenght` = '10', `bank_code_alpha` = '0',`account_number_pos` = '16',`account_number_fix` = '0' WHERE `iso` = 'IT' LIMIT 1 ;
ALTER TABLE `gaz_country` ADD `VAT_number_lenght` TINYINT NOT NULL ,
ADD `VAT_number_alpha` TINYINT NOT NULL ;
ALTER TABLE `gaz_tesdoc` ADD `units` TINYINT NOT NULL AFTER `gross_weight` ,
ADD `volume` DECIMAL( 8, 2 ) NOT NULL AFTER `units` ;
ALTER TABLE `gaz_tesbro` ADD `units` TINYINT NOT NULL AFTER `gross_weight` ,
ADD `volume` DECIMAL( 8, 2 ) NOT NULL AFTER `units` ;
ALTER TABLE `gaz_rigdoc` CHANGE `prelis` `prelis` DECIMAL( 14, 5 ) NULL DEFAULT '0.000' ;
ALTER TABLE `gaz_rigbro` CHANGE `prelis` `prelis` DECIMAL( 14, 5 ) NULL DEFAULT '0.000' ;
UPDATE `gaz_config` SET `cvalue` = '30' WHERE `id` =2;
INSERT INTO `gaz_menu_module` (`id` ,`id_module` ,`link` ,`icon` ,`class` ,`translate_key` ,`accesskey` ,`weight`) VALUES ( '51', '2', 'report_agenti.php', '', '', '10', '', '8' );
INSERT INTO `gaz_menu_script` (`id` ,`id_menu` ,`link` ,`icon` ,`class` ,`translate_key` ,`accesskey` ,`weight`) VALUES ( '50', '51', 'admin_agenti.php?Insert', '', '', '1', '', '1' );
ALTER TABLE `gaz_agenti` ADD `base_percent` DECIMAL( 4, 2 ) NOT NULL ;
ALTER TABLE `gaz_provvigioni` DROP `id_provvigione`;
ALTER TABLE `gaz_provvigioni` ADD `id_provvigione` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;
ALTER TABLE `gaz_agenti` ADD PRIMARY KEY ( `id_agente` )  ; 
UPDATE `gaz_config` SET `cvalue` = '31' WHERE `id` =2;
ALTER TABLE `gaz_clfoco` ADD `id_agente` INT NOT NULL AFTER `print_map` ;
ALTER TABLE `gaz_artico` ADD `volume_specifico` DECIMAL( 12, 3 ) NOT NULL AFTER `peso_specifico` ,
ADD `pack_units` TINYINT NOT NULL AFTER `volume_specifico` ;
UPDATE `gaz_artico` SET `peso_specifico` = '1' WHERE `unimis` LIKE 'kg%' OR `unimis` LIKE 'gr%'; 
UPDATE `gaz_artico` SET `volume_specifico` = '1' WHERE `unimis` LIKE 'l%'; 
UPDATE `gaz_artico` SET `pack_units` = '1' WHERE `unimis` LIKE 'pz%'; 
ALTER TABLE `gaz_imball` ADD `weight` DECIMAL( 8, 2 ) NOT NULL AFTER `descri` ;
UPDATE `gaz_menu_script` SET `link` = 'admin_imball.php?Insert' WHERE `id` = 47 LIMIT 1 ;
UPDATE `gaz_config` SET `cvalue` = '32' WHERE `id` =2;
ALTER TABLE `gaz_agenti` ADD `tipo_contratto` TINYINT NOT NULL AFTER `base_percent` ,
ADD `adminid` VARCHAR( 20 ) NOT NULL AFTER `tipo_contratto` ,
ADD `last_modified` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL AFTER `adminid` ;
ALTER TABLE `gaz_aziend` ADD `decimal_price` INT NOT NULL AFTER `regime` ;
UPDATE `gaz_aziend` SET `decimal_price` = '3' WHERE `codice` = 1 LIMIT 1 ;
ALTER TABLE `gaz_artico` CHANGE `preacq` `preacq` DECIMAL( 14, 5 ) NULL DEFAULT '0.000',
CHANGE `preve1` `preve1` DECIMAL( 14, 5 ) NULL DEFAULT '0.000',
CHANGE `preve2` `preve2` DECIMAL( 14, 5 ) NULL DEFAULT '0.000',
CHANGE `preve3` `preve3` DECIMAL( 14, 5 ) NULL DEFAULT '0.000';
ALTER TABLE `gaz_movmag` CHANGE `prezzo` `prezzo` DECIMAL( 14, 5 ) NULL DEFAULT '0.000';  