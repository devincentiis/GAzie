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

$anagrafica = new Anagrafica();

if (!empty($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
	$where = "vat_section='$auxil'";
} else {
   $auxil = 1;
	$where = "vat_section='$auxil'";
}
if (!empty($_GET['all'])) {
	$where = "vat_section='$auxil'";
   $auxil = $_GET['auxil']."&all=yes";
   $passo = 100000;
	$protocollo = '';
}
// filtro stato: di default solo i contratti attivi (quelli conclusi senza rinnovo tacito non si mostrano); "tutti" li mostra tutti
$stato = (isset($_GET['stato']) && $_GET['stato'] == 'tutti') ? 'tutti' : 'attivi';
if ($stato == 'attivi') {
	// attivi = non ancora conclusi, oppure conclusi ma con periodi non fatturati (ultimo mese pagato prima della fine)
	$where .= " AND (NOT (months_duration - PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'), EXTRACT(YEAR_MONTH FROM start_date)) <= 0 AND tacit_renewal = 0)"
	       . " OR (covered_ym <> '' AND covered_ym < DATE_FORMAT(DATE_ADD(start_date, INTERVAL months_duration MONTH) - INTERVAL 1 DAY, '%Y-%m')))";
}

// filtri delle colonne (riga dei filtri): ID, data, numero, cliente, canone, ultimo mese pagato
$f_id_txt = isset($_GET['id_contract']) ? trim($_GET['id_contract']) : '';
if (ctype_digit($f_id_txt) && intval($f_id_txt) > 0) $where .= " AND id_contract = " . intval($f_id_txt);
$doc_number = isset($_GET['doc_number']) ? trim($_GET['doc_number']) : '';
if (ctype_digit($doc_number) && intval($doc_number) > 0) $where .= " AND doc_number = " . intval($doc_number);
$f_anno = isset($_GET['anno_contr']) ? intval($_GET['anno_contr']) : 0;
if ($f_anno > 0) {
    $where .= " AND YEAR(conclusion_date) = " . $f_anno;
}
$f_mese = isset($_GET['mese_contr']) ? intval($_GET['mese_contr']) : 0;
if ($f_mese >= 1 && $f_mese <= 12) {
    $where .= " AND MONTH(conclusion_date) = " . $f_mese;
}
$f_cli = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
if ($f_cli !== '') {
    $cl = gaz_dbi_real_escape_string(str_replace(array('*', '?'), array('%', '_'), $f_cli));
    $where .= " AND id_customer IN (SELECT " . $gTables['clfoco'] . ".codice FROM " . $gTables['clfoco'] . " LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id WHERE " . $gTables['anagra'] . ".ragso1 LIKE '%" . $cl . "%' OR " . $gTables['anagra'] . ".ragso2 LIKE '%" . $cl . "%')";
}
$f_can = isset($_GET['canone']) ? trim($_GET['canone']) : '';
if ($f_can !== '' && is_numeric(str_replace(',', '.', $f_can))) {
    $where .= " AND current_fee = " . floatval(str_replace(',', '.', $f_can));
}
$f_cop = isset($_GET['covered_ym']) ? trim($_GET['covered_ym']) : '';
if (preg_match('/^(\d{4})-(\d{1,2})$/', $f_cop, $m)) {
    $where .= " AND covered_ym = '" . sprintf('%04d-%02d', $m[1], $m[2]) . "'";
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new GAzieForm();
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("cliente"));
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
							data: {'type':'contract',ref:id},
							type: 'POST',
							url: '../vendit/delete.php',
							success: function(output){
		                    //alert(output);
							window.location.replace("./report_contract.php");
						}
						});
					}}
				}
			});
		$("#dialog_delete" ).dialog( "open" );
	});
   $( "#dialog" ).dialog({
      autoOpen: false
   });
});
function confirMail(link){
   tes_id = link.id.replace("doc", "");
   $.fx.speeds._default = 500;
   targetUrl = $("#doc"+tes_id).attr("url");
   $("p#mail_adrs").html($("#doc"+tes_id).attr("mail"));
   $("p#mail_attc").html($("#doc"+tes_id).attr("namedoc"));
   $( "#dialog" ).dialog({
         modal: "true",
      show: "blind",
      hide: "explode",
         buttons: {
                      "<?php echo $script_transl['submit']; ?>": function() {
                         window.location.href = targetUrl;
                      },
                      "<?php echo $script_transl['cancel']; ?>": function() {
                        $(this).dialog("close");
                      }
                  }
         });
   $("#dialog" ).dialog( "open" );
}
function printPdf(urlPrintDoc){
	$(function(){
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
        $("#framePdf").focus();
    });
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
		});
	});
};
</script>
<?php
echo "<form method=\"GET\" name=\"report\">\n";
?>
<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 10px">
	<div class="col-lg-12">
		<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
		<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
	</div>
	<iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
</div>
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
	<p><b>contratto</b></p>
	<p>Codice:</p>
	<p class="ui-state-highlight" id="idcodice"></p>
	<p>Cliente:</p>
	<p class="ui-state-highlight" id="iddescri"></p>
</div>
<?php
echo "<input type=\"hidden\" name=\"hidden_req\">\n";
?>
    <div style="display:none" id="dialog" title="<?php echo $script_transl['mail_alert0']; ?>">
        <p id="mail_alert1"><?php echo $script_transl['mail_alert1']; ?></p>
        <p class="ui-state-highlight" id="mail_adrs"></p>
        <p id="mail_alert2"><?php echo $script_transl['mail_alert2']; ?></p>
        <p class="ui-state-highlight" id="mail_attc"></p>
    </div>
<?php
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'].$script_transl['vat_section'];
$gForm->selectNumber('auxil',$auxil,0,1,9,'FacetSelect','auxil');
echo "</div>\n";
// ordinamento di default: ultimo mese pagato dal più vecchio al più nuovo; con il click su una colonna si ordina per quella
$campi_ordinabili = array('id_customer', 'conclusion_date', 'doc_number', 'current_fee', 'periodicity', 'covered_ym');
if (isset($_GET['field']) && in_array($_GET['field'], $campi_ordinabili)) {
	$orderby = $_GET['field']." ASC, conclusion_date DESC, doc_number DESC";
} else {
	$orderby = "covered_ym ASC, conclusion_date DESC, doc_number DESC";
}
$recordnav = new recordnav($gTables['contract'], $where, $limit, $passo);
$recordnav -> output();
gaz_flt_var_assign('id_customer', 'i', $gTables['contract']);
?>
<?php
// evidenzia i contratti: rosso = periodo già scaduto e non fatturato, giallo = da fatturare nei prossimi 30 giorni
// (il prossimo periodo da fatturare inizia il primo del mese dopo l'ultimo mese pagato, o alla data di inizio se non c'è ancora nulla)
function contractRowClass($row) {
    $oggi = date('Y-m-d');
    $fine = (intval($row['months_duration']) > 0) ? date('Y-m-d', strtotime($row['start_date'] . ' +' . intval($row['months_duration']) . ' months -1 day')) : null;
    if (!empty($row['covered_ym'])) {
        $prossimo = date('Y-m-d', strtotime($row['covered_ym'] . '-01 +1 month'));
    } else {
        $prossimo = $row['start_date'];
    }
    // contratto concluso senza rinnovo tacito: evidenziato solo se ci sono periodi non ancora fatturati
    if ($fine !== null && $fine < $oggi && empty($row['tacit_renewal']) && $prossimo > $fine) return '';
    if ($prossimo < $oggi) return 'ctr-scaduto';
    if ($prossimo <= date('Y-m-d', strtotime('+15 days'))) return 'ctr-prossimo';
    return '';
}
?>
<div class="box-primary table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed gz-contratti">
<tr>
<td class="FacetFieldCaptionTD"><input type="text" name="id_contract" value="<?php echo htmlspecialchars($f_id_txt); ?>" class="form-control input-sm" style="width:60px"></td>
<td class="FacetFieldCaptionTD gz-flt-data">
	<?php
	$where_dat = $gTables['contract'] . ".vat_section='" . intval($auxil) . "'";
		echo '<select class="form-control input-sm" name="mese_contr" onchange="this.form.submit()" title="mese di stipula">';
	echo '<option value="0">Tutti i mesi</option>';
	$mesi_brevi = array('Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic');
	for ($mm = 1; $mm <= 12; $mm++) {
		$nome = $mesi_brevi[$mm - 1];
		echo '<option value="' . $mm . '"' . ($f_mese == $mm ? ' selected' : '') . '>' . $nome . '</option>';
	}
	echo '</select>';
gaz_flt_disp_select("anno_contr", "YEAR(conclusion_date) AS anno_contr", $gTables['contract'], $where_dat, "anno_contr ASC", "");
	?>
</td>
<td class="FacetFieldCaptionTD"><input type="text" name="doc_number" value="<?php echo htmlspecialchars($doc_number); ?>" maxlength="6" class="form-control input-sm" style="width:70px"></td>
<td class="FacetFieldCaptionTD"><input type="text" name="cliente" value="<?php echo htmlspecialchars($f_cli); ?>" placeholder="ragione sociale" class="form-control input-sm" style="width:100%"></td>
<td class="FacetFieldCaptionTD">
<select class="form-control input-sm" name="stato" onchange="this.form.submit()" title="stato dei contratti" style="width:140px">
<option value="attivi"<?php echo ($stato == 'attivi' ? ' selected' : ''); ?>>Contratti attivi</option>
<option value="tutti"<?php echo ($stato == 'tutti' ? ' selected' : ''); ?>>Tutti i contratti</option>
</select>
</td>
<td class="FacetFieldCaptionTD">
	<?php
	$where_cov = $gTables['contract'] . ".vat_section='" . intval($auxil) . "' AND covered_ym <> ''";
	$cov_opt = array();
	$gazTimeFormatter->setPattern('MMMM yyyy');
	$rs_cov = gaz_dbi_dyn_query("DISTINCT covered_ym", $gTables['contract'], $where_cov, "covered_ym ASC");
	while ($rc = gaz_dbi_fetch_array($rs_cov)) {
		$cov_opt[$rc['covered_ym']] = ucfirst($gazTimeFormatter->format(new DateTime($rc['covered_ym'] . '-01')));
	}
	gaz_flt_disp_select("covered_ym", "covered_ym", $gTables['contract'], $where_cov, "covered_ym ASC", $cov_opt);
	?>
</td>
<td class="FacetFieldCaptionTD"></td>
<td class="FacetFieldCaptionTD"></td>
<td class="FacetFieldCaptionTD"></td>
<td class="FacetFieldCaptionTD" align="center">
<input type="submit" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value=1;">
<input type="submit" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value=1;">
</td>
</tr>
<tr>
<?php
// creo l'array (header => campi) per l'ordinamento dei record
$headers_tesdoc = array  (
            $script_transl['id'] => "id_customer",
            $script_transl['date'] => "conclusion_date",
            $script_transl['number'] => "doc_number",
            $script_transl['customer'] => "id_customer",
            $script_transl['current_fee'] => "current_fee",
            "Ultimo mese pagato" => "covered_ym",
            $script_transl['periodicity'] => "periodicity",
            $script_transl['print'] => "",
            "Mail" => "",
            $script_transl['delete'] => ""
            );
$linkHeaders = new linkHeaders($headers_tesdoc);
$linkHeaders -> output();
?>
</tr>
<?php
//recupero le testate in base alle scelte impostate
$result = gaz_dbi_dyn_query('*',$gTables['contract'], $where, $orderby,$limit, $passo);
while ($row = gaz_dbi_fetch_array($result)) {
        $cliente = $anagrafica->getPartner($row['id_customer']);
        print "<tr class=\"" . contractRowClass($row) . "\">";
        print "<td class=\"FacetDataTD\" align=\"center\"><a class=\"btn btn-xs btn-edit\" href=\"admin_contract.php?Update&id_contract=".$row['id_contract']."\"><i class=\"glyphicon glyphicon-edit\"></i>&nbsp;".$row["id_contract"]."</a></td>";
        print "<td class=\"FacetDataTD\" align=\"center\">".gaz_format_date($row['conclusion_date'])."</td>";
        print "<td class=\"FacetDataTD\" align=\"center\">".$row['doc_number']." &nbsp;</td>";
        print "<td class=\"FacetDataTD\" align=\"center\"><a href=\"report_client.php?nome=".$cliente['ragso1']."\">".$cliente['ragso1']."</a>";
		if (!empty($row['note'])) {
			print "&nbsp;<span title=\"" . $row['note'] . "\"><i class=\"glyphicon glyphicon-map-marker\"></i></span>";
		}
        print "</td>";
        // canone e articoli ricorrenti (il canone è ricorrente): valori su una riga con il totale
        $rs_ric = gaz_dbi_dyn_query("SUM(quanti * price) AS tot_ric", $gTables['contract_row'], "id_contract = ".intval($row['id_contract'])." AND status <> 'unatantum' AND status <> 'fatturata'");
        $ric = gaz_dbi_fetch_array($rs_ric);
        $tot_ric = $ric ? floatval($ric['tot_ric']) : 0;
        $canone_txt = number_format($row['current_fee'],2,',','.');
        if ($tot_ric > 0) {
            $canone_txt .= " + ".number_format($tot_ric,2,',','.')." = <b>".number_format($row['current_fee'] + $tot_ric,2,',','.')."</b>";
        }
        print "<td class=\"FacetDataTD\" align=\"center\" style=\"white-space:nowrap\">".$canone_txt." &nbsp;</td>";
        // ultimo mese pagato (AAAA-MM) mostrato come "mese anno"
        $covered_txt = '-';
        if (!empty($row['covered_ym'])) {
            $gazTimeFormatter->setPattern('MMMM yyyy');
            $covered_txt = ucfirst($gazTimeFormatter->format(new DateTime($row['covered_ym'].'-01')));
        }
        print "<td class=\"FacetDataTD\" align=\"center\">".$covered_txt."</td>";
        print "<td class=\"FacetDataTD\" align=\"center\">".$script_transl['periodicity_value'][$row['periodicity']]." &nbsp;</td>";
		echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" onclick=\"printPdf('print_contract.php?id_contract=".$row['id_contract']."')\"><i class=\"glyphicon glyphicon-print\" title=\"Stampa documento PDF\"></i></a>";
		// Colonna "Mail"
		print "<td align=\"center\">";
		if (!empty($cliente['e_mail'])) { // ho una mail sul cliente
			print '<a class="btn btn-xs btn-default btn-email" onclick="confirMail(this);return false;" id="doc' . $row['id_contract'] . '" url="print_contract.php?id_contract=' . $row['id_contract'] . '&dest=E" href="#" title="mailto: ' . $cliente['e_mail'] . '"
	mail="' . $cliente['e_mail'] . '" namedoc="Contratto n.' . $row['doc_number'] . ' del ' . gaz_format_date($row['conclusion_date']) . '"><i class="glyphicon glyphicon-envelope"></i></a>';
		} else { // non ho mail
			print '<a title="Non hai memorizzato l\'email per questo cliente, inseriscila ora" href="admin_client.php?codice=' . substr($row['id_customer'], 3) . '&Update"><i class="glyphicon glyphicon-edit"></i></a>';
		}
		print "</td>";
        print "<td class=\"FacetDataTD\" align=\"center\">";
		?>
		<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $row['id_contract'];?>" cliente="<?php echo $cliente['ragso1']; ?>">
			<i class="glyphicon glyphicon-trash"></i>
		</a>
		<?php
        print "</td></tr>\n";
}
?>
</form>
</table>
</div>
<?php
require("../../library/include/footer.php");
?>
