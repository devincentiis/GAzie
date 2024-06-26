UPDATE `gaz_config` SET `cvalue` = '69' WHERE `id` =2;
ALTER TABLE `gaz_country` ADD `black_list` INT NOT NULL; 
UPDATE `gaz_country` SET `black_list` = 5 WHERE `iso`='AD' OR `iso`='LI' OR `iso`='MC' OR `iso`='SM';
UPDATE `gaz_country` SET `black_list` = 2 WHERE `iso`='AT' OR `iso`='CH' OR `iso`='BE' OR `iso`='LU';
ALTER TABLE `gaz_menu_script` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT;
INSERT INTO `gaz_menu_script` (`id_menu` ,`link` ,`icon` ,`class` ,`translate_key` ,`accesskey` ,`weight`) VALUES ('18', 'stampa_piacon.php', '', '', '5', '', '5');
INSERT INTO `gaz_menu_script` (`id_menu` ,`link` ,`icon` ,`class` ,`translate_key` ,`accesskey` ,`weight`) VALUES ('5', 'import_gaziecart.php', '', '', '28', '', '3');
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
ALTER TABLE `gaz_XXXartico` ADD `web_multiplier` DECIMAL( 6, 3 ) NOT NULL AFTER `web_price`;
UPDATE `gaz_XXXartico` SET `web_multiplier` = 1;
ALTER TABLE `gaz_XXXclfoco` CHANGE `annota` `annota` VARCHAR( 3000 ) NOT NULL;
INSERT INTO `gaz_XXXconfig` ( `description`,`var`,`val`) VALUES ( 'Order Mail address', 'order_mail', 'order@mydomain.com'),('Server address', 'order_server', 'in.mydomain.com'),('Password for access', 'order_pass', 'password');
ALTER TABLE `gaz_XXXtesbro` CHANGE `numdoc` `numdoc` BIGINT NOT NULL;
-- STOP_WHILE( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)