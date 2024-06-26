UPDATE `gaz_config` SET `cvalue` = '120' WHERE `id` =2;
DELETE FROM `gaz_menu_script` WHERE `link`='select_regcor.php';
INSERT INTO `gaz_menu_script` SELECT MAX(id)+1, (SELECT MIN(id) FROM `gaz_menu_module` WHERE `link`='comunicazioni_doc.php'), 'comunicazione_dati_fatture.php?esterometro', '', '', 14, '', 17  FROM `gaz_menu_script`;
-- START_WHILE ( questo e' un tag che serve per istruire install.php ad INIZIARE ad eseguire le query seguenti su tutte le aziende dell'installazione)
UPDATE `gaz_XXXtesmov` SET `datliq`=`datreg` WHERE `caucon`='VCO' AND `datreg` >= '2019-01-01' AND `datliq` < '2000-01-01' ;
-- STOP_WHILE ( questo e' un tag che serve per istruire install.php a SMETTERE di eseguire le query su tutte le aziende dell'installazione)
