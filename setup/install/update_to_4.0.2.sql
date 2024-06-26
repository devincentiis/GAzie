UPDATE `gaz_config` SET `cvalue` = '44' WHERE `id` =2;
INSERT INTO `gaz_menu_script` (`id` ,`id_menu` ,`link` ,`icon` ,`class` ,`translate_key` ,`accesskey` ,`weight`)
VALUES ('53', '23', 'inventory_stock.php', '', '', '0', '3', '3');
UPDATE `gaz_config` SET `cvalue` = '45' WHERE `id` =2;
INSERT INTO `gaz_caumag` (`codice` ,`descri` ,`clifor` ,`insdoc` ,`operat` ,`upesis` ,`adminid` ,`last_modified`)
VALUES (99, 'INVENTARIO DI MAGAZZINO', 0, 0, 0, 1, '', NOW( ));
