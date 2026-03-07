UPDATE `gaz_config` SET `cvalue` = '162' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT id_menu FROM `gaz_menu_script` WHERE `link`='select_evaord.php'), 'select_order_status.php', '', '', 63, '', 3  FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)

-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione )
