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
    'sea_id_tes' => $gTables['tesmov'].".id_tes LIKE '%%%s%%'",
	'sea_descri' => $gTables['tesmov'].".descri LIKE '%%%s%%'",
    'sea_datreg' => $gTables['tesmov'].".datreg  LIKE '%%%s%%'",
    'codmin' => "clfoco >= ".$admin_aziend['masban']."000000 + GREATEST(%d, 1)",
    'codmax' => "clfoco <= ".$admin_aziend['masban']."000000 + LEAST(%d, 999999)",
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array  (
            "N. Movimento" => 'id_tes',
            "Descrizione"=>'descri',
            "Data richiesta"=>'datreg',
            "Importo"=>''
);

$tablejoin = $gTables['tesmov']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['tesmov'].".clfoco = ".$gTables['clfoco'].".codice LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id";

$ts = new TableSorter(
    $tablejoin,
    $passo,
    ['datreg'=>'desc'],
    ['codmin' => 1, 'codmax' => 999999, 'caucon' => 'BBH']
	);
?>
<script>
$(function() {
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
	$( "#suggest_sea_datreg" ).autocomplete({
		source: "./search.php?opt=suggest_sea_datreg",
		minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
      	// optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
		select: function(event, ui) {
			$("#suggest_sea_datreg").val(ui.item.value);
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
		</td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="sea_descri" placeholder="Descrizione" id="suggest_sea_descri" class="input-sm form-control" value="<?php echo (isset($sea_descri))? $sea_descri : ""; ?>" maxlength="15">
        </td>
		<td class="FacetFieldCaptionTD">
			<input type="text" name="sea_datreg" placeholder="Data" id="suggest_sea_datreg" class="input-sm form-control" value="<?php echo (isset($sea_datreg))? htmlentities($sea_datreg, ENT_QUOTES) : ""; ?>" maxlength="15">
        </td>
		<td class="FacetFieldCaptionTD text-center">
			<a class="btn btn-sm btn-default" href="?">Reset</a>
			<?php  $ts->output_order_form(); ?>
        </td>
	</tr>

<?php
$result = gaz_dbi_dyn_query( $gTables['tesmov'].".*", $tablejoin , $ts->where, $ts->orderby,  $ts->getOffset(), $ts->getLimit());
echo '<tr>';
$ts->output_headers();
echo '</tr>';
while ($r = gaz_dbi_fetch_array($result)) {
	// riprendo le contropartite
	$rrc = gaz_dbi_dyn_query('*',$gTables['rigmoc']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id", $gTables['rigmoc'].".id_tes = ".$r['id_tes']." AND darave = 'D'",'id_rig');
	$import=0.00;
	while($rc= gaz_dbi_fetch_array($rrc)){
	  $import += $rc['import'];
	}
    echo "<tr>";
    echo '<td><a class="btn btn-xs btn-default" href="pay_salary.php?id_tes='. $r["id_tes"] .'"><i class="glyphicon glyphicon-edit"> </i> ' . $r["id_tes"] . "</a></td>";
    echo "<td>" .$r["descri"]."  </td>";
    echo "<td align=\"center\">" . gaz_format_date($r["datreg"]) . "  </td>";
    echo "<td align=\"right\">" . gaz_format_number($import). "  </td>";
    echo "</tr>";
}
print_r($r);
?>
</table></div>
<?php
if (isset($_GET['xml'])){
  echo "<script>
	$(window).on('load',(function() {
		window.location.href = 'CBIPaymentRequest.php?id_tes=".intval($_GET['id_tes'])."';
	}));
	</script>";
}
?>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
