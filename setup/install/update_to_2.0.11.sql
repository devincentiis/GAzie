UPDATE `gaz_config` SET `cvalue` = '10' WHERE `id` =2;
ALTER TABLE `gaz_artico` ADD `image` BLOB NOT NULL AFTER `descri`;
ALTER TABLE `gaz_catmer` ADD `image` BLOB NOT NULL AFTER `descri`;
ALTER TABLE `gaz_admin` ADD `image` BLOB NOT NULL AFTER `Nome`;
ALTER TABLE `gaz_aziend` ADD `image` BLOB NOT NULL AFTER `ragso2`;
INSERT INTO `gaz_config` (`id`, `description`, `variable`, `cvalue`, `weight`, `show`, `last_modified`) VALUES ('7', 'Nome del foglio di stile', 'css', NULL, '7', '1', '0000-00-00 00:00:00');