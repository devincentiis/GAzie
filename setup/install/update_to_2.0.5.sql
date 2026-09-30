UPDATE `gaz_config` SET `cvalue` = '04' WHERE `id` =2;
ALTER TABLE `gaz_aziend` ADD `regime` VARCHAR( 1 ) NOT NULL AFTER `codiva`;
ALTER TABLE `gaz_aziend` ADD `fatimm` VARCHAR( 1 ) NOT NULL AFTER `desez3` , ADD `colore` VARCHAR( 6 ) NOT NULL AFTER `fatimm`;