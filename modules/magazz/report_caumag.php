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
$admin_aziend=checkAdmin();
$msg = "";


if (isset($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
}
if (isset($_GET['all'])) {
   $auxil = "&all=yes";
   $where = "descri like '%'";
   $passo = 100000;
} else {
   if (isset($_GET['auxil'])) {
      $where = "descri like '".addslashes($_GET['auxil'])."%'";
   }
}

if (!isset($_GET['flag_order'])) {
   $orderby = " codice desc";
}

if (!isset($_GET['auxil'])) {
   $auxil = "";
   $where = "codice like '$auxil%'";
}
$where ="(type_cau = 0 OR type_cau = 9) AND ".$where;
require("../../library/include/header.php");
$script_transl = HeadMain();
require("./lang.".$admin_aziend['lang'].".php");
$script_transl += $strScript["admin_caumag.php"];
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("caudes"));
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
              data: {'type':'caumag',ref:id},
              type: 'POST',
              url: '../magazz/delete.php',
              success: function(output){
                          //alert(output);
                window.location.replace("./report_caumag.php");
              }
            });
          }
        }
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<?php
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl[1].$script_transl[0]."</div>\n";
echo "<form method=\"GET\">";
?>
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
  <p><b>causale di magazzino:</b></p>
	<p>Codice</p>
  <p class="ui-state-highlight" id="idcodice"></p>
  <p>Descrizione</p>
  <p class="ui-state-highlight" id="iddescri"></p>
</div>
<div class="table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed">
<?php
echo "<tr><td></td><td class=\"FacetFieldCaptionTD\">".$script_transl[2].":\n";
echo "<input type=\"text\" name=\"auxil\" value=\"";
if ($auxil != "&all=yes"){
    echo $auxil;
}
echo "\" maxlength=\"6\"  tabindex=\"1\" class=\"FacetInput\"></td>\n";
echo "<td><input type=\"submit\" name=\"search\" value=\"".$script_transl['search']."\" tabindex=\"1\" onClick=\"javascript:document.report.all.value=1;\"></td>\n";
echo "<td><input type=\"submit\" name=\"all\" value=\"".$script_transl['vall']."\" onClick=\"javascript:document.report.all.value=1;\"></td></tr>\n";
$result = gaz_dbi_dyn_query ("*",$gTables['caumag'], $where, $orderby, $limit, $passo);
// creo l'array (header => campi) per l'ordinamento dei record
$headers_mov = array  (
            $strScript["admin_caumag.php"][1] => "codice",
            $script_transl[2] => "descri",
            $script_transl[11] => "clifor",
            $script_transl[4] => "operat",
            $script_transl['delete'] => ""
            );
$linkHeaders = new linkHeaders($headers_mov);
$linkHeaders -> output();
$recordnav = new recordnav($gTables['caumag'], $where, $limit, $passo);
$recordnav -> output();
while ($a_row = gaz_dbi_fetch_array($result)) {
    echo "<tr class=\"FacetDataTD\">\n";
    echo '<td class="text-center"><a class="btn btn-xs btn-edit" href="admin_caumag.php?codice='.$a_row["codice"].'&Update" title="'.ucfirst($script_transl['update']).'"><i class="glyphicon glyphicon-edit"></i>&nbsp;'.$a_row["codice"]."</a> &nbsp</td>";
    echo "<td align=\"center\">".$a_row["descri"]." &nbsp;</td>";
    echo "<td align=\"center\">".$script_transl[$a_row['clifor']+13]."</td>";
    echo "<td align=\"center\">".$script_transl[$a_row['operat']+9]."</td>";
    echo "<td align=\"center\">";
	?>
	<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $a_row['codice'];?>" caudes="<?php echo $a_row['descri']; ?>">
		<i class="glyphicon glyphicon-trash"></i>
	</a>
	<?php
	echo"</td></tr>\n";
}
?>
</table>
</div>
<?php
require("../../library/include/footer.php");
?>
