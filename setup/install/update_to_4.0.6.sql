UPDATE `gaz_config` SET `cvalue` = '52' WHERE `id` =2;
ALTER TABLE `gaz_tesdoc` CHANGE `pervat` `round_stamp` TINYINT NOT NULL DEFAULT '5'; 
ALTER TABLE `gaz_tesdoc`  DROP `ivaspe`;
ALTER TABLE `gaz_tesbro` CHANGE `pervat` `round_stamp` TINYINT NOT NULL DEFAULT '5'; 
ALTER TABLE `gaz_tesbro`  DROP `ivaspe`;
UPDATE gaz_tesdoc SET round_stamp='0';
UPDATE gaz_tesbro SET round_stamp='0';
UPDATE gaz_tesdoc SET round_stamp='5', stamp='1.2' WHERE gaz_tesdoc.pagame IN (SELECT codice FROM gaz_pagame WHERE tippag = 'T');
UPDATE gaz_tesbro SET round_stamp='5', stamp='1.2' WHERE gaz_tesbro.pagame IN (SELECT codice FROM gaz_pagame WHERE tippag = 'T');
ALTER TABLE `gaz_effett` ADD `id_doc` INT NOT NULL AFTER `banacc` ;
UPDATE `gaz_aziend` SET `round_bol` = '5' WHERE `codice` =1;
UPDATE gaz_tesdoc SET round_stamp='-1' WHERE tipdoc = 'VRI';