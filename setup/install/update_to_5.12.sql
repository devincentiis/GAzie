UPDATE `gaz_config` SET `cvalue` = '72' WHERE `id` =2;
INSERT IGNORE INTO `gaz_menu_script` (`id`, `id_menu`, `link`, `icon`, `class`, `translate_key`, `accesskey`, `weight`) VALUES ('67', '6', 'select_filemav.php', '', '', '29', '', '7');
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
CREATE TABLE `gaz_XXXpaymov` ( `id` INT NOT NULL AUTO_INCREMENT, `id_paymovcon` INT NOT NULL, `id_docmovcon` INT NOT NULL, `amount` decimal(11,2) NOT NULL, `expiry` DATE NOT NULL, PRIMARY KEY (`id`) ) ENGINE=MyISAM;
-- STOP_WHILE( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query seguenti su tutte le aziende dell'installazione)