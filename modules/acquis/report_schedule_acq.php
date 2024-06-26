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
require("../../library/include/header.php");
$script_transl = HeadMain();

$search_fields = [
    'sea_tesdoc_ref' => "{$gTables['paymov']}.id_tesdoc_ref LIKE '%%%s%%'",
	'sea_descri' => "{$gTables['tesmov']}.descri LIKE '%%%s%%'",
    'sea_expiry' => "{$gTables['paymov']}.expiry  LIKE '%%%s%%'",
    'codmin' => "codcon >= ". $admin_aziend['masfor']."000000 + GREATEST(%d, 1)",
    'codmax' => "codcon <= ". $admin_aziend['masfor']."000000 + LEAST(%d, 999999)",
	
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
            "Identificativo partita" => 'id_tesdoc_ref',
            "Apertura partita (fattura)"=>'id_rigmoc_doc',
            "Chiusura partita (pagamento)"=>'id_rigmoc_pay',
            "Importo"=>'',
            'Scadenza' => 'expiry'
);

$tablejoin = $gTables['paymov']." LEFT JOIN ".$gTables['rigmoc']." ON (".$gTables['paymov'].".id_rigmoc_pay + ".$gTables['paymov'].".id_rigmoc_doc ) = ".$gTables['rigmoc'].".id_rig LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes";

$ts = new TableSorter(
    $tablejoin, 
    $passo, 
    ['id'=>'desc'],
    ['codmin' => 1, 'codmax' => 999999]
	);
?>
<script>
$(function() {
	$( "#suggest_tesdoc_ref" ).autocomplete({
		source: "./search.php?opt=suggest_tesdoc_ref",
		minLength: 4,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#suggest_tesdoc_ref").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
	$( "#suggest_sea_descri" ).autocomplete({
		source: "./search.php?opt=suggest_sea_descri",
		minLength: 4,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#suggest_sea_descri").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
	$( "#suggest_sea_expiry" ).autocomplete({
		source: "./search.php?opt=suggest_sea_expiry",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#suggest_sea_expiry").val(ui.item.value);
			$(this).closest("form").submit();
		}
	});
});
</script>

<div class="text-center"><h3><?php echo $script_transl['title'];?></h3></div>
<?php
$ts->output_navbar();
?>
<form method="GET" class="clean_get">
	<div class="table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
	<tr>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="sea_tesdoc_ref" placeholder="riferimento partita" id="suggest_tesdoc_ref" class="input-sm form-control" value="<?php echo (isset($sea_tesdoc_ref))? htmlentities($sea_tesdoc_ref, ENT_QUOTES) : ""; ?>" maxlength="15">
		</td>
		<td class="FacetFieldCaptionTD" colspan="2">
			<input type="text" name="sea_descri" placeholder="descrizione" id="suggest_sea_descri" class="input-sm form-control" value="<?php echo (isset($sea_descri))? $sea_descri : ""; ?>" maxlength="15">
        </td>
		<td class="FacetFieldCaptionTD text-center">
			<a class="btn btn-sm btn-default" href="?">Reset</a>
			<?php  $ts->output_order_form(); ?>
        </td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="sea_expiry" placeholder="scadenza" id="suggest_sea_expiry" class="input-sm form-control" value="<?php echo (isset($sea_expiry))? htmlentities($sea_expiry, ENT_QUOTES) : ""; ?>" maxlength="15">
        </td>
	</tr>

<?php
$result = gaz_dbi_dyn_query('*', $tablejoin , $ts->where, $ts->orderby,  $ts->getOffset(), $ts->getLimit());
echo '<tr>';
$ts->output_headers();
echo '</tr>';
while ($r = gaz_dbi_fetch_array($result)) {
    // faccio una subquery che sembra più veloce di LEFT JOIN per ricavare l'id_tes
    echo "<tr>";
    echo "<td>" . $r["id_tesdoc_ref"] . "</td>";
    if ($r["id_rigmoc_doc"] > 0) {
        echo "<td><a class=\"btn btn-xs btn-default btn-warning\"  style=\"font-size:10px;\" href=\"../contab/admin_movcon.php?id_tes=" . $r["id_tes"] . "&Update\">" . $r["id_tes"] . "</a>&nbsp; ".$r["descri"]." &nbsp;</td>";
    } else {
        echo "<td></td>";
    }
    if ($r["id_rigmoc_pay"] > 0) {
        echo "<td><a class=\"btn btn-xs btn-default btn-success\"  style=\"font-size:10px;\" href=\"../contab/admin_movcon.php?id_tes=" . $r["id_tes"] . "&Update\">" . $r["id_tes"] . "</a>&nbsp; ".$r["descri"]." &nbsp;</td>";
    } else {
        echo "<td></td>";
    }
    echo "<td align=\"right\">" . $r["amount"] . " &nbsp;</td>";
    echo "<td align=\"center\">" . $r["expiry"] . " &nbsp;</td>";
    echo "</tr>";
}
?>
</table></div>
<?php
if ( isset($_GET['xml']) ){
  $ref='';
  if  ( isset($_GET['id_rig']) && $_GET['id_rig'] > 0 ){
	$ref='?id_rig='.intval($_GET['id_rig']); 
  } elseif  ( isset($_GET['id_tes']) && $_GET['id_tes'] > 0 ){
	$ref='?id_tes='.intval($_GET['id_tes']); 
  }
  echo '<script>
	$( window ).load(function() {
		window.location.href = "CBIPaymentRequest.php'.$ref.'"
	});
	</script>';
}
?>

<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
