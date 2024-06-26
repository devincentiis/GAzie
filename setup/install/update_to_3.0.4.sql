UPDATE `gaz_config` SET `cvalue` = '23' WHERE `id` =2;
ALTER TABLE `gaz_clfoco` ADD `allegato` BOOL NOT NULL AFTER `aliiva`;
UPDATE `gaz_clfoco` SET `allegato` = 1 WHERE 1;
ALTER TABLE `gaz_aziend` ADD `interessi` DECIMAL( 3, 1 ) NOT NULL AFTER `ivam_t`;
UPDATE `gaz_aziend` SET `interessi` = 1 WHERE 1;