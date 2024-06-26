UPDATE `gaz_config` SET `cvalue` = '06' WHERE `id` =2;
ALTER TABLE `gaz_clfoco` ADD `cosric` INT NOT NULL AFTER `aliiva`;
UPDATE `gaz_config` SET `cvalue` = '07' WHERE `id` =2;
ALTER TABLE `gaz_rigbro` ADD `id_doc` INT NOT NULL AFTER `codric` ;
UPDATE `gaz_config` SET `cvalue` = '08' WHERE `id` =2;
UPDATE `gaz_tesdoc` SET numdoc=numfat WHERE tipdoc = 'FAI' or tipdoc = 'FNC' or tipdoc = 'FND';
UPDATE `gaz_config` SET `cvalue` = '09' WHERE `id` =2;
INSERT INTO `gaz_config` VALUES (5, 'Check Update', 'http://www.openmind-solutions.it/gazie/file_ver', '0', '5', 0, '0000-00-00 00:00:00');
INSERT INTO `gaz_config` VALUES (6, 'Update_files_address', 'update_URI_files', 'http://sourceforge.net/projects/gazie', '6', 0, '0000-00-00 00:00:00');