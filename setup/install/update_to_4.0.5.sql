UPDATE `gaz_config` SET `cvalue` = '47' WHERE `id` =2;
CREATE TABLE `gaz_cash_register` (
  `id_cash` TINYINT NOT NULL,
  `seziva` INT NOT NULL,
  `adminid` VARCHAR(20) NOT NULL,
  `enterpriseid` INT NOT NULL,
  `serial_port` VARCHAR(32) NOT NULL,
  `driver` VARCHAR(32) NOT NULL,
  `descri` VARCHAR(32) NOT NULL
) ENGINE=MyISAM;
INSERT INTO `gaz_cash_register` (`id_cash`, `seziva`, `adminid`, `enterpriseid`, `serial_port`, `driver`, `descri`) VALUES (1, 1, 'amministratore', 1, '0', 'olivetti_ela', 'LM78613134 Underwood Nettuna 500');
UPDATE `gaz_config` SET `cvalue` = '48' WHERE `id` =2;
ALTER TABLE `gaz_admin` ADD `last_ip` VARCHAR( 15 ) NOT NULL AFTER `Access`;
UPDATE `gaz_config` SET `cvalue` = '49' WHERE `id` =2;
ALTER TABLE `gaz_tesdoc` CHANGE `units` `units` INT NOT NULL;
ALTER TABLE `gaz_tesbro` CHANGE `units` `units` INT NOT NULL;
UPDATE `gaz_config` SET `cvalue` = '50' WHERE `id` =2;
ALTER TABLE `gaz_tesdoc` ADD `vat_susp` TINYINT NOT NULL AFTER `sconto` ,
ADD `stamp` DECIMAL( 6, 2 ) NOT NULL AFTER `vat_susp` ; 
ALTER TABLE `gaz_tesbro` ADD `vat_susp` TINYINT NOT NULL AFTER `sconto` ,
ADD `stamp` DECIMAL( 6, 2 ) NOT NULL AFTER `vat_susp` ; 
ALTER TABLE `gaz_aziend` ADD `vat_susp` TINYINT NOT NULL AFTER `regime` ;
ALTER TABLE `gaz_aziend` ADD `round_bol` TINYINT NOT NULL AFTER `ivabol` ;
ALTER TABLE `gaz_aziend` ADD `iva_susp` INT NOT NULL AFTER `ivaven` ;
UPDATE `gaz_config` SET `cvalue` = '51' WHERE `id` =2;
ALTER TABLE `gaz_tesmov` ADD `id_doc` INT NOT NULL AFTER `seziva` ;
INSERT INTO `gaz_menu_script` (`id` ,`id_menu` ,`link` ,`icon` ,`class` ,`translate_key` ,`accesskey` ,`weight`) VALUES ( '61', '1', 'close_ecr.php', '', '', '26', '', '4');