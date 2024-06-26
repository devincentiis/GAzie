UPDATE `gaz_config` SET `cvalue` = '76' WHERE `id` =2;
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, '23', 'update_vatrate.php', '', '', '11', '', '6'  FROM `gaz_menu_script`;
