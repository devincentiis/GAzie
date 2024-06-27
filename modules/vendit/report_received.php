<?php
/*
 --------------------------------------------------------------------------
                            GAzie - Gestione Azienda
    Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
         (https://www.devincentiis.it)
           <https://gazie.sourceforge.net>
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
$admin_aziend=checkAdmin();

if (isset($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
} else {
   $auxil = 1;
}

$where = " tipdoc = 'VRI' AND seziva = '$auxil'";
$all = $where;
$doc ='';

if (isset($_GET['numdoc'])) {
   if ($_GET['numdoc'] > 0) {
      $doc = intval($_GET['numdoc']);
      $auxil = $_GET['auxil']."&numdoc=".$doc;
      $where = " tipdoc = 'VRI' AND seziva = '$auxil' AND numdoc = '$doc'";
      $passo = 1;
   }
}

gaz_flt_var_assign('id_tes','i');
gaz_flt_var_assign('datemi','d');
gaz_flt_var_assign('numdoc','i');
gaz_flt_var_assign('clfoco','v' );

if (isset($_GET['all'])) {
	$_GET['id_tes']="";
	$_GET['datemi']="";
	$_GET['numdoc']="";
	$_GET['clfoco']="";
	$where=$all;
	$auxil = $_GET['auxil']."&all=yes";
	$passo = 100000;
}

require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("datemi"));
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
						data: {'type':'docven',id_tes:id},
						type: 'POST',
						url: '../vendit/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_received.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<form method="GET">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>ricevuta:</b></p>
        <p>Numero ID:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Data:</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
<div align="center" class="FacetFormHeaderFont"> Ricevute della sezione
<select name="auxil" class="FacetSelect" onchange="this.form.submit()">
<?php
for ($sez = 1; $sez <= 9; $sez++) {
     $selected="";
     if(substr($auxil,0,1) == $sez)
        $selected = " selected ";
     echo "<option value=\"".$sez."\"".$selected.">".$sez."</option>";
}
?>
</select>
</div>
<?php
if (!isset($_GET['field']) or ($_GET['field'] == 2) or(empty($_GET['field'])))
        $orderby = "datfat DESC, numfat DESC";
$recordnav = new recordnav($gTables['tesdoc'], $where, $limit, $passo);
$recordnav -> output();
?>
<div class="box-primary table-responsive">
<table class="Tlarge table table-striped table-bordered">
<tr>
<td colspan="1" class="FacetFieldCaptionTD">
<?php gaz_flt_disp_int ( "numdoc", "Numero Ricevuta" ); ?>
<!--<input type="text" placeholder="Cerca Numero" class="input-xs form-control" name="numdoc" value="<?php if ($doc > 0) print $doc; ?>" maxlength="6" tabindex="1" class="FacetInput">-->
</td>
<td colspan="1" class="FacetFieldCaptionTD">
<?php gaz_flt_disp_select ( "datemi", "YEAR(datemi) as datemi", $gTables["tesdoc"], $all, $orderby); ?>
</td>
<td class="FacetFieldCaptionTD">
	<?php gaz_flt_disp_select ( "clfoco", $gTables['anagra'].".ragso1,".$gTables["tesdoc"].".clfoco", $gTables['tesdoc']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesdoc'].".clfoco = ".$gTables['clfoco'].".codice LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id", $all, $orderby, "ragso1"); ?>
</td>
<td class="FacetFieldCaptionTD">
&nbsp;
</td>
<td class="FacetFieldCaptionTD">
<input type="submit" class="btn btn-sm btn-default" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value=1;">
</td>
<td class="FacetFieldCaptionTD">
<input type="submit" class="btn btn-sm btn-default" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value=1;">
</td>
</tr>
<tr>
<?php
// creo l'array (header => campi) per l'ordinamento dei record
$headers_tesdoc = array  (
              "Numero" => "numfat",
              "Data" => "datfat",
              "Cliente" => "ragso1",
              "Telefono" => "Importo",
              "Stampa" => "",
              "Status" => ""
              );
$linkHeaders = new linkHeaders($headers_tesdoc);
$linkHeaders -> output();
?>
</tr>
<?php
$rs_last_received = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $where,"datfat DESC, numfat DESC",0,1);
$last_received = gaz_dbi_fetch_array($rs_last_received);
if ($last_received)
    $last_n = $last_received['numdoc'];
else
    $last_n = 1;
//recupero le testate in base alle scelte impostate
$result = gaz_dbi_dyn_query($gTables['tesdoc'].".*,".$gTables['anagra'].".ragso1,".$gTables['anagra'].".telefo", $gTables['tesdoc']."
                            LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesdoc'].".clfoco = ".$gTables['clfoco'].".codice
                            LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra", $where, "id_tes desc",$limit, $passo);
while ($row = gaz_dbi_fetch_array($result)) {
    echo "<tr>";
    echo "<td class=\"FacetDataTD\"><a class=\"btn btn-xs btn-edit\" href=\"admin_docven.php?Update&id_tes=".$row["id_tes"]."\">".$row['tipdoc'].$row["numdoc"]."</a> &nbsp;</td>";
    echo "<td class=\"FacetDataTD\">".$row["datfat"]." &nbsp;</td>";
    echo "<td class=\"FacetDataTD\"><a title=\"Dettagli cliente\" href=\"report_client.php?nome=".$row["ragso1"]."\">".$row["ragso1"]."&nbsp;</a></td>";
    echo "<td class=\"FacetDataTD\">".$row["telefo"]." &nbsp;</td>";
    echo "<td class=\"FacetDataTD\" align=\"center\">
			<a href=\"stampa_docven.php?id_tes=".$row["id_tes"]."&template=Received\" title=\"Stampa\" class=\"btn btn-xs btn-default\">
				<i class=\"glyphicon glyphicon-print\"></i>
				</a>
		  </td>";
    if ($last_n == $row["numfat"] && $row["id_con"] == 0){
       echo "<td class=\"FacetDataTD\" align=\"center\">";
			?>
			<a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il documento" ref="<?php echo $row['id_tes'];?>" datemi="<?php echo $row['datemi']; ?>">
				<i class="glyphicon glyphicon-trash"></i>
			</a>
			</td>
			<?php
    } else {
        echo "<td class=\"FacetDataTD\">";
		if ($row["id_con"] > 0) {
			echo " <a class=\"btn btn-xs btn-default btn-default\" style=\"font-size:10px;\" title=\"Modifica il movimento contabile generato da questo documento\" href=\"../contab/admin_movcon.php?id_tes=" . $row["id_con"] . "&Update\">Cont." . $row["id_con"] . "</a> ";
		} else {
			echo " <a class=\"btn btn-xs btn-default btn-cont\" href=\"accounting_documents.php?type=VRI&vat_section=" . $seziva . "&last=" . $row["protoc"] . "\"><i class=\"glyphicon glyphicon-euro\"></i>&nbsp;Contabilizza</a>";
		}
        echo "</td>";
    }
    echo "</tr>\n";
}
?>
<th colspan="11" class="FacetFieldCaptionTD">
</form>
</table>
</div>
<?php
require("../../library/include/footer.php");
?>
