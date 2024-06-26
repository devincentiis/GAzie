UPDATE `gaz_config` SET `cvalue` = '53' WHERE `id` =2;
ALTER TABLE `gaz_aziend` ADD `c_ritenute` INT NOT NULL AFTER `cocamb` ,
ADD `ritenuta` DECIMAL( 3, 1 ) NOT NULL AFTER `c_ritenute`;
UPDATE `gaz_aziend` SET `c_ritenute` = '106000003' WHERE 1;
ALTER TABLE `gaz_rigdoc` ADD `ritenuta` DECIMAL( 3, 1 ) NOT NULL AFTER `provvigione` ;
ALTER TABLE `gaz_rigbro` ADD `ritenuta` DECIMAL( 3, 1 ) NOT NULL AFTER `provvigione` ;
ALTER TABLE `gaz_clfoco` ADD `ritenuta` DECIMAL( 3, 1 ) NOT NULL AFTER `aliiva` ;
INSERT INTO `gaz_menu_script` (`id` ,`id_menu` ,`link` ,`icon` ,`class` ,`translate_key` ,`accesskey` ,`weight`) VALUES ( '62', '2', 'admin_docven.php?Insert&tipdoc=FAP', '', '', '27', '', '7');
INSERT INTO `gaz_caucon` (`codice` ,`descri` ,`insdoc` ,`regiva` ,`operat` ,`contr1` ,`tipim1` ,`tipiv1` ,`daav_1` ,`contr2` ,`tipim2` ,`tipiv2` ,`daav_2` ,`contr3` ,`tipim3` ,`tipiv3` ,`daav_3` ,`contr4` ,`tipim4` ,`tipiv4` ,`daav_4` ,`contr5` ,`tipim5` ,`tipiv5` ,`daav_5` ,`contr6` ,`tipim6` ,`tipiv6` ,`daav_6` ,`adminid` ,`last_modified`) VALUES ('FAP', 'PARCELLA', 1, 2, 1, 103000000, 'A', '', 'D', 420000001, 'B', '', 'A', 215000001, 'C', '', 'A', 0, '', '', '', 0, '', '', '', 0, '', '', '', '', '');