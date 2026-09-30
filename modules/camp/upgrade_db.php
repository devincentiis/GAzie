<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  Vacation Rental è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
	  --------------------------------------------------------------------------
	  Questo programma e` free software;   e` lecito redistribuirlo  e/o
	  modificarlo secondo i  termini della Licenza Pubblica Generica GNU
	  come e` pubblicata dalla Free Software Foundation; o la versione 2
	  della licenza o (a propria scelta) una versione successiva.

	  Questo programma  e` distribuito nella speranza  che sia utile, ma
	  SENZA   ALCUNA GARANZIA; senza  neppure  la  garanzia implicita di
	  NEGOZIABILITA` o di  APPLICABILITA` PER UN  PARTICOLARE SCOPO.  Si
	  veda la Licenza Pubblica Generica GNU per avere maggiori dettagli.

	  Ognuno dovrebbe avere   ricevuto una copia  della Licenza Pubblica
	  Generica GNU insieme a   questo programma; in caso  contrario,  si
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
//upgrade database per il modulo CAMP - registro di campagna
$dbname = constant("Database");
global $table_prefix;

// da qui in poi iserire le query che saranno eseguite su ogni azienda con il modulo attivo

/*  >>> esempio di come vanno impostate le query il numero [147] rappresenta la versione dell'update di GAzie
$upgrade_db[147][]="ALTER TABLE ".$table_prefix."_XXXrental_discounts ADD `test2` TINYINT(2) NOT NULL DEFAULT '0' COMMENT 'test update 2';";
*/

$upgrade_db[148][]="ALTER TABLE ".$table_prefix."_camp_fitofarmaci ADD INDEX(`PRODOTTO`);";
$upgrade_db[155][]="ALTER TABLE `".$table_prefix."_camp_uso_fitofarmaci` CHANGE `dose` `dose` DECIMAL(8,3) NOT NULL COMMENT 'unità di misura / ha'; ";
$upgrade_db[155][]="ALTER TABLE `".$table_prefix."_camp_uso_fitofarmaci` ADD `dose_hl` DECIMAL(8,3) NOT NULL COMMENT 'unità di misura / hl' AFTER `dose`; ";
$upgrade_db[160][]="ALTER TABLE `".$table_prefix."_XXXcamp_mov_sian` ADD `tesdoc` INT(11) NULL DEFAULT NULL COMMENT 'Riferimento al documento giustificativo quando si tratta di un movimento di magazzino creato manualmente' AFTER `id_movmag`";
?>
