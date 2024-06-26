UPDATE `gaz_config` SET `cvalue` = '61' WHERE `id` =2;
ALTER TABLE `gaz_aziend` CHANGE `codice` `codice` INT NOT NULL; 
UPDATE `gaz_config` SET `cvalue` = '62' WHERE `id` =2;
INSERT INTO `gaz_menu_script` (`id`,`id_menu`,`link`,`icon`,`class` ,`translate_key`,`accesskey`,`weight`) VALUES ('63','37','report_aziend.php','','','10','','3');
INSERT INTO `gaz_menu_script` (`id`,`id_menu`,`link`,`icon`,`class` ,`translate_key`,`accesskey`,`weight`) VALUES ('64','37','create_new_enterprise.php','','','11','','6');