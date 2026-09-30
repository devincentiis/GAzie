UPDATE `gaz_config` SET `cvalue` = '54' WHERE `id` =2;
ALTER TABLE `gaz_vettor` ADD `ragione_sociale` VARCHAR( 100 ) NOT NULL AFTER `codice` , ADD `indirizzo` VARCHAR( 100 ) NOT NULL AFTER `ragione_sociale` , ADD `cap` VARCHAR( 5 ) NOT NULL AFTER `indirizzo` , ADD `citta` VARCHAR( 100 ) NOT NULL AFTER `cap` , ADD `provincia` VARCHAR( 100 ) NOT NULL AFTER `citta` , ADD `partita_iva` VARCHAR( 12 ) NOT NULL AFTER `provincia` , ADD `codice_fiscale` VARCHAR( 16 ) NOT NULL AFTER `partita_iva` , ADD `n_albo` VARCHAR( 50 ) NOT NULL AFTER `codice_fiscale`;
UPDATE `gaz_vettor` SET `ragione_sociale` = `descri` WHERE 1;
UPDATE `gaz_menu_script` SET `link` = 'admin_vettore.php?Insert' WHERE `link` ='insert_vettor.php' LIMIT 1 ;
