<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
  (http://www.devincentiis.it)
  <http://gazie.sourceforge.net>
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
  scriva   alla   Free  Software Foundation, 51 Franklin Street,
  Fifth Floor Boston, MA 02110-1335 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();

$titolo = 'Indirizzi di destinazione';

//$mascli = $admin_aziend['mascli'] . "000000";
//$clienti = $admin_aziend['mascli'];
require("../../library/include/header.php");
$script_transl = HeadMain();
$where = "1";
$dbAnagrafe = $gTables["anagra"];
$dbDestinazioni = $gTables["destina"];


if (isset($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
} else {
   $auxil = "";
}

if (isset($_GET['auxil1'])) {
   $auxil1 = $_GET['auxil1'];
} else {
   $auxil1 = "";
}

if (isset($_GET['all'])) {
   $auxil = "&all=yes";
   $passo = 100000;
} else {
   if (isset($_GET['auxil']) and $auxil1 == "") {
      $where .= " AND $dbAnagrafe.ragso1 LIKE '" . addslashes($auxil) . "%'";
   } elseif (isset($_GET['auxil1'])) {
      $codicetemp = intval($auxil1);
      $where .= " AND CONVERT(gaz_destina.codice,char) LIKE '" . $codicetemp . "%'";
   }
}

if (!isset($_GET['field'])) {
   $orderby =  "$dbAnagrafe.ragso1, $dbAnagrafe.ragso2, $dbDestinazioni.unita_locale1, $dbDestinazioni.unita_locale2";
}

if (isset($_GET['ricerca_completa'])) {
   $ricerca_testo = $_GET['ricerca_completa'];
   $where .= " and ( unita_locale1 like '%" . $ricerca_testo . "%' ";
   $where .= " or unita_locale2 like '%" . $ricerca_testo . "%' ";
   $where .= " or pariva like '%" . $ricerca_testo . "%' ";
   $where .= " or pariva like '%" . $ricerca_testo . "%' ";
   $where .= " or codfis like '%" . $ricerca_testo . "%' ";
   $where .= " or citspe like '%" . $ricerca_testo . "%' )";
}
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("anagrafe"));
		var id = $(this).attr('ref');
		$( "#dialog_delete" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
   			close: {
					text:'Non eliminare',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("close");
          }
        },
				delete:{
					text:'Elimina',
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'destinazioni',ref:id},
						type: 'POST',
						url: '../vendit/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_destinazioni.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div align="center" class="FacetFormHeaderFont">Indirizzi di Destinazione</div>
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>Destinazione:</b></p>
        <p>codice:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Anagrafica:</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
    <div class="box-primary table-responsive">
    <table class="Tlarge table table-striped table-bordered table-condensed">
        <tr>
            <th class="FacetFieldCaptionTD">
                <input placeholder="Cerca" class="input-xs form-control" type="text" name="auxil1" value="<?php echo $auxil1 ?>" maxlength="6" tabindex="1" class="FacetInput">
            </th>
            <th class="FacetFieldCaptionTD">
                <input placeholder="Cerca Ragione Sociale" class="input-xs form-control" type="text" name="auxil" value="<?php if ($auxil != "&all=yes") echo $auxil; ?>" maxlength="6" tabindex="1" class="FacetInput">
            </th>
            <th colspan="2" class="FacetFieldCaptionTD">
            </th>
            <th class="FacetFieldCaptionTD">
                <input type="submit" class="btn btn-xs btn-default" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value = 1;">
            </th>
            <th class="FacetFieldCaptionTD">
                <input type="submit" class="btn btn-xs btn-default" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value = 1;">
            </th>
        </tr>
        <?php
        $result = gaz_dbi_dyn_query("$dbDestinazioni.codice as codice, "
                . "concat($dbAnagrafe.ragso1,space(1),$dbAnagrafe.ragso2) as anagrafe,"
                . "concat($dbDestinazioni.unita_locale1,space(1),$dbDestinazioni.unita_locale2) as destinazione,"
                . "$dbDestinazioni.indspe, $dbDestinazioni.capspe, $dbDestinazioni.citspe, "
                . "$dbDestinazioni.prospe, $dbDestinazioni.country, $dbDestinazioni.telefo, $dbDestinazioni.e_mail", "$dbDestinazioni LEFT JOIN $dbAnagrafe ON $dbDestinazioni.id_anagra = $dbAnagrafe.id", $where, $orderby, $limit, $passo);
// creo l'array (header => campi) per l'ordinamento dei record
        $headers_ = array(
            "Codice" => "codice",
            "Anagrafe" => "anagrafe",
            "Destinazione" => "destinazione",
//            "Indirizzo" => "indspe",
            "Citt&agrave;" => "citspe",
            "Telefono" => "telefo",
//            "Email" => "e_mail",
            "Cancella" => ""
        );
        $linkHeaders = new linkHeaders($headers_);
        $linkHeaders->output();
        $recordnav = new recordnav($gTables['destina'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['destina'] . '.id_anagra = ' . $gTables['anagra'] . '.id', $where, $limit, $passo);
        $recordnav->output();
        ?>
        </tr>
        <?php
        while ($a_row = gaz_dbi_fetch_array($result)) {
           echo "<tr>";
           // Colonna codice cliente
           $codiceRiga=$a_row["codice"];
           echo "<td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs btn-default\" href=\"admin_destinazioni.php?codice=$codiceRiga&Update\">"
                   . "<i class=\"glyphicon glyphicon-edit\"></i>&nbsp;$codiceRiga</a> &nbsp</td>";
           // Colonna anagrafe
           $anagrafeRiga=$a_row["anagrafe"];
           $destinazioneRiga=$a_row["destinazione"];
           echo "<td class=\"FacetDataTD\"> $anagrafeRiga &nbsp;</td>";
           // Colonna destinazione
           echo "<td class=\"FacetDataTD\"> $destinazioneRiga &nbsp;</td>";
           // colonna indirizzo
           $google_string = str_replace(" ", "+", $a_row["indspe"]) . "," . str_replace(" ", "+", $a_row["capspe"]) . "," . str_replace(" ", "+", $a_row["citspe"]) . "," . str_replace(" ", "+", $a_row["prospe"]);
           echo "<td class=\"FacetDataTD\" title=\"" . $a_row["capspe"] . " " . $a_row["indspe"] . "\">";
           echo "<a class=\"btn btn-xs btn-default\" target=\"_blank\" href=\"https://www.google.it/maps/place/" . $google_string . "\">" . $a_row["citspe"] . " (" . $a_row["prospe"] . ")&nbsp;<i class=\"glyphicon glyphicon-map-marker\"></i></a>";
           echo "</td>";
           // composizione telefono
           $title = "";
           $telefono = "";
           if (!empty($a_row["telefo"])) {
              $telefono = $a_row["telefo"];
              if (!empty($a_row["cell"])) {
                 $title .= "cell:" . $a_row["cell"];
              }
              if (!empty($a_row["fax"])) {
                 $title .= " fax:" . $a_row["fax"];
              }
           } elseif (!empty($a_row["cell"])) {
              $telefono = $a_row["cell"];
              if (!empty($a_row["fax"])) {
                 $title .= " fax:" . $a_row["fax"];
              }
           } elseif (!empty($a_row["fax"])) {
              $telefono = "fax:" . $a_row["fax"];
           } else {
              $telefono = "_";
              $title = " nessun contatto telefonico memorizzato ";
           }
           // colonna telefono
           echo "<td class=\"FacetDataTD\" title=\"$title\" align=\"center\">" . gaz_html_call_tel($telefono) . " &nbsp;</td>";
           echo "<td class=\"FacetDataTD\" align=\"center\">";
		   ?>
		   <a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $a_row['codice'];?>" anagrafe="<?php echo $a_row['anagrafe']; ?>">
				<i class="glyphicon glyphicon-trash"></i>
			</a>
			<?php
           echo "</td></tr>\n";
        }
        ?>
</form>
</table>
    </div>
<?php
require("../../library/include/footer.php");
?>
