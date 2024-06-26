UPDATE `gaz_config` SET `cvalue` = '119' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id FROM `gaz_menu_module` WHERE `link`='select_regiva.php'), 'select_regcor.php', '', '', 13, '', 16  FROM `gaz_menu_script`;
UPDATE `gaz_menu_script` SET `weight`=`weight`*3 WHERE  `id_menu`= (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='report_client.php');
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='report_client.php'), 'print_anagrafe.php?clifor=C&order=ZONE', '', '', 55, '', 30  FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXcompany_config` CHANGE `description` `description` VARCHAR(512);
INSERT INTO `gaz_XXXcompany_config` (`description`, `var`, `val`) VALUES ('Foglio di stile xsl per la visualizzazione della fattura elettronica (nella directory /library/include)', 'fae_style', 'fatturaordinaria_v1.2.1');
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
