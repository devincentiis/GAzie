<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
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
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
$msg = "";

require("../../library/include/header.php");
$script_transl = HeadMain(0,array(/*'tiny_mce/tiny_mce',*/
                                  /*'boxover/boxover'*/));

if (isset($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
} else {
   $auxil = "";
   $where = "item_ref LIKE '".addslashes($auxil)."%' ";
}

if (isset($_GET['all'])) {
   $auxil = "&all=yes";
   $where = "1";
   $passo = 100000;
} else {
   if (isset($_GET['auxil'])) {
      $where = "item_ref LIKE '".addslashes($auxil)."%'";
   }
}

if (!isset($_GET['flag_order'])) {
   $orderby = " id_doc DESC";
}

if ($auxil == "&all=yes"){
   $auxil='';
}

/** ENRICO FEDELE */
/* pulizia del codice, eliminato boxover, aggiunte classi bootstrap alla tabella, convertite immagini in glyphicons */
print '<div align="center" class="FacetFormHeaderFont">'.$script_transl['title'].'</div>
	   <form method="GET">
	   <table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
	   	<thead>
			<tr>
				<th></th>
				<th class="FacetFieldCaptionTD">
					'.$script_transl['item'].':&nbsp;<input type="text" name="auxil" value="'.$auxil.'" maxlength="6" tabindex="1" class="FacetInput">
				</th>
				<th>
					<input type="submit" name="search" value="'.$script_transl['search'].'" tabindex="1" onClick="javascript:document.report.all.value=1;">
				</th>
				<th>
					<input type="submit" name="all" value="'.$script_transl['vall'].'" onClick="javascript:document.report.all.value=1;">
				</th>
			</tr>
		</thead>
		<tbody>';
$result = gaz_dbi_dyn_query ('*',$gTables['files']." LEFT JOIN ".$gTables['artico']." ON ".$gTables['files'].".item_ref = ".$gTables['artico'].".codice", $where, $orderby, $limit, $passo);
// creo l'array (header => campi) per l'ordinamento dei record
$headers_mov = array("ID" => "id_doc",
					 $script_transl['item'] => "item_ref",
					 $script_transl['table_name_ref'] => "table_name_ref",
					 $script_transl['note'] => "title",
					 $script_transl['ext'] => "extension",
					 'Download' => "",
					 $script_transl['delete'] => "");
$linkHeaders = new linkHeaders($headers_mov);
$recordnav   = new recordnav($gTables['files'], $where, $limit, $passo);

$linkHeaders->output();
$recordnav->output();

while ($a_row = gaz_dbi_fetch_array($result)) {
    /*if(!isset($_GET['all']) and !empty($a_row["image"])){
         $boxover = "title=\"cssbody=[FacetInput] cssheader=[FacetButton] header=[".$a_row['annota']."] body=[<center><img src='../root/view.php?table=artico&value=".$a_row['item_ref']."'>] fade=[on] fadespeed=[0.03] \"";
    } else {
         $boxover = "title=\"cssbody=[FacetInput] cssheader=[FacetButton] header=[".$a_row['annota']."]  fade=[on] fadespeed=[0.03] \"";
    }*/
    /*print "<td class=\"FacetDataTD\" align=\"center\" $boxover >".$a_row["item_ref"]."</td>\n";*/
	// class="gazie-tooltip" data-type="product-thumb" data-id="'.$value['codart'].'" data-title="'.$descrizione.'" type="text" name="rows['.$key.'][descri]" value="'.$descrizione.'" maxlength="50"
    echo '<tr>
			<td class="FacetDataTD" align="right">
				<a href="admin_document.php?id_doc='.$a_row["id_doc"].'&Update" title="'.ucfirst($script_transl['update']).'">'.$a_row["id_doc"].'</a>
			</td>
			<td class="FacetDataTD" align="center">
				<span class="gazie-tooltip" data-type="product-thumb" data-id="'.$a_row['item_ref'].'" data-title="'.$a_row['annota'].'">'.$a_row["item_ref"].'</span>
			</td>
			<td class="FacetDataTD" align="center">'.$a_row["table_name_ref"].'</td>
			<td class="FacetDataTD" align="center">'.$a_row["title"].'</td>
			<td class="FacetDataTD" align="center">'.$a_row["extension"].'</td>
			<td class="FacetDataTD" align="center">
				<a class="btn btn-xs btn-default" href="../root/retrieve.php?id_doc='.$a_row["id_doc"].'" title="'.$script_transl['view'].'">
					<i class="glyphicon glyphicon-eye-open"></i>
				</a>
			</td>
			<td class="FacetDataTD" align="center">
				<a href="delete_document.php?id_doc='.$a_row["id_doc"].'" title="'.$script_transl['delete'].'!">
					<i class="glyphicon glyphicon-trash"></i>
				</a>
			</td>
		  </tr>';
}
?>
            </tbody>
        </table>
    <?php
require("../../library/include/footer.php");
?>
