UPDATE `gaz_config` SET `cvalue` = '68' WHERE `id` =2;
INSERT IGNORE INTO `gaz_module` (`id`, `name`, `link`, `icon`, `class`, `access`, `weight`) VALUES ('9', 'gazpme', 'docume_gazpme.php', 'gazpme.png', '', '0', '9');
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXartico` ADD `web_mu` VARCHAR( 15 ) NOT NULL AFTER `preve3` ,
ADD `web_price` decimal( 14, 5 ) NOT NULL AFTER `web_mu` ,
ADD `web_public` TINYINT NOT NULL AFTER `web_price`;
UPDATE `gaz_XXXartico` SET `web_price` = preve1;
UPDATE `gaz_XXXartico` SET `web_mu` = unimis;
UPDATE `gaz_XXXartico` SET `web_public` = 1;
-- STOP_WHILE( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)
