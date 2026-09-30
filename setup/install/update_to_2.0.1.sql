UPDATE `gaz_config` SET `cvalue` = '01' WHERE `id` =2;
UPDATE `gaz_aliiva` SET `aliquo` = '20.0',
`last_modified` = NOW( ) WHERE `codice` =1 LIMIT 1 ;
UPDATE `gaz_config` SET `cvalue` = '02' WHERE `id` =2;
