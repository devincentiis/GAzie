UPDATE `gaz_config` SET `cvalue` = '65' WHERE `id` =2;
UPDATE `gaz_menu_script` SET `link` = 'admin_effett.php?Insert' WHERE `link` = 'insert_effett.php' LIMIT 1;
UPDATE `gaz_config` SET `cvalue` = '66' WHERE `id` =2;
ALTER TABLE `gaz_country` ADD `IBAN_prefix` VARCHAR( 2 ) NOT NULL AFTER `numcode`, ADD `IBAN_lenght` INT NOT NULL AFTER `IBAN_prefix`;
UPDATE `gaz_country` SET `IBAN_prefix` =  'IT', `IBAN_lenght` = '27' WHERE `iso` = 'IT' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'AD', `IBAN_lenght` = '24' WHERE `iso` = 'AD' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'AT', `IBAN_lenght` = '20' WHERE `iso` = 'AT' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'BE', `IBAN_lenght` = '16' WHERE `iso` = 'BE' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'CH', `IBAN_lenght` = '21' WHERE `iso` = 'CH' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'DE', `IBAN_lenght` = '22' WHERE `iso` = 'DE' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'DK', `IBAN_lenght` = '18' WHERE `iso` = 'DK' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'ES', `IBAN_lenght` = '24' WHERE `iso` = 'ES' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'FI', `IBAN_lenght` = '18' WHERE `iso` = 'FI' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'FO', `IBAN_lenght` = '18' WHERE `iso` = 'FO' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'FR', `IBAN_lenght` = '27' WHERE `iso` = 'FR' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'GB', `IBAN_lenght` = '22' WHERE `iso` = 'GB' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'GL', `IBAN_lenght` = '18' WHERE `iso` = 'GL' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'GR', `IBAN_lenght` = '27' WHERE `iso` = 'GR' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'HU', `IBAN_lenght` = '28' WHERE `iso` = 'HU' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'IE', `IBAN_lenght` = '22' WHERE `iso` = 'IE' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'IS', `IBAN_lenght` = '26' WHERE `iso` = 'IS' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'LI', `IBAN_lenght` = '21' WHERE `iso` = 'LI' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'LU', `IBAN_lenght` = '20' WHERE `iso` = 'LU' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'MC', `IBAN_lenght` = '27' WHERE `iso` = 'MC' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'NL', `IBAN_lenght` = '18' WHERE `iso` = 'NL' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'NO', `IBAN_lenght` = '15' WHERE `iso` = 'NO' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'PL', `IBAN_lenght` = '28' WHERE `iso` = 'PL' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'PT', `IBAN_lenght` = '25' WHERE `iso` = 'PT' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'SE', `IBAN_lenght` = '24' WHERE `iso` = 'SE' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'SI', `IBAN_lenght` = '19' WHERE `iso` = 'SI' LIMIT 1;
UPDATE `gaz_country` SET `IBAN_prefix` =  'SM', `IBAN_lenght` = '27' WHERE `iso` = 'SM' LIMIT 1;
