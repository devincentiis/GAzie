UPDATE `gaz_config` SET `cvalue` = '85' WHERE `id` =2;
ALTER TABLE `gaz_anagra` ADD `fe_cod_univoco` VARCHAR( 6 ) NOT NULL AFTER `pariva` ;
ALTER TABLE `gaz_aziend` ADD `fiscal_reg` VARCHAR( 4 ) NOT NULL AFTER `regime` ;
UPDATE `gaz_aziend` SET `fiscal_reg`= 'RF01' WHERE 1;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXrigdoc` CHANGE `tiprig` `tiprig` INT NOT NULL;
ALTER TABLE `gaz_XXXportos` ADD `incoterms` VARCHAR( 3 ) NOT NULL AFTER `descri`;
UPDATE `gaz_XXXportos` SET `incoterms`='EXW' WHERE 1;
ALTER TABLE `gaz_XXXpagame` ADD `fae_mode` VARCHAR( 4 ) NOT NULL AFTER `tiprat`;
UPDATE `gaz_XXXpagame` SET `fae_mode`='MP12' WHERE `tippag` = 'B';
UPDATE `gaz_XXXpagame` SET `fae_mode`='MP01' WHERE `tippag` = 'C';
ALTER TABLE `gaz_XXXaliiva` ADD `fae_natura` VARCHAR( 2 ) NOT NULL AFTER `aliquo` ;
UPDATE `gaz_XXXaliiva` SET `fae_natura`='N1' WHERE `tipiva` = 'C';
UPDATE `gaz_XXXaliiva` SET `fae_natura`='N2' WHERE `tipiva` = 'S';
UPDATE `gaz_XXXaliiva` SET `fae_natura`='N3' WHERE `tipiva` = 'N';
UPDATE `gaz_XXXaliiva` SET `fae_natura`='N4' WHERE `tipiva` = 'E';
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)