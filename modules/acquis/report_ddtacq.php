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
require ("../../modules/vendit/lib.function.php");
$lm = new lotmag;
$admin_aziend = checkAdmin();
$pdf_to_modal = gaz_dbi_get_row($gTables['company_config'], 'var', 'pdf_reports_send_to_modal')['val'];
$tipdoc_filter = "('DDL', 'RDL', 'DDR','ADT', 'AFT')";

$partner_select = !gaz_dbi_get_row($gTables['company_config'], 'var', 'partner_select_mode')['val'];

$tesdoc = "(SELECT * FROM {$gTables['tesdoc']} WHERE tipdoc IN $tipdoc_filter) as dtesdoc";
$tesdoc_e_partners = "{$gTables['tesdoc']} " .
                     "INNER JOIN {$gTables['clfoco']}" .
                     " ON ({$gTables['tesdoc']}.clfoco = {$gTables['clfoco']}.codice " .
                     " AND tipdoc IN $tipdoc_filter) " .
                     "LEFT JOIN {$gTables['anagra']}" .
                     " ON {$gTables['clfoco']}.id_anagra = {$gTables['anagra']}.id";

// funzione di utilità generale, adatta a mysqli.inc.php
function cols_from($table_name, ...$col_names) {
  $full_names = array_map(function ($col_name) use ($table_name) { return "$table_name.$col_name"; }, $col_names);
  return implode(", ", $full_names);
}

// campi ammissibili per la ricerca
$search_fields = [
    'sezione' => "seziva = %d",
    'numdoc'  => "numdoc = %d",
    'tipo'    => "tipdoc LIKE '%s'",
    'numero'  => "numfat LIKE '%%%s%%'",
    'anno'    => "YEAR(datemi) = %d",
    'fornitore'=> $partner_select ? "clfoco = '%s'" : "ragso1 LIKE '%%%s%%'"
];

// creo l'array (header => campi) per l'ordinamento dei record
$sortable_headers = array(
    "ID" => "id_tes",
    "Tipo" => "tipdoc",
    "Numero" => "numdoc",
    "Data" => "datemi",
    "Fornitore" => "",
    "Status" => "",
    "Stampa" => "",
    "Cancella" => ""
);

require("../../library/include/header.php");
$script_transl = HeadMain();
$ts = new TableSorter(
    !$partner_select && isset($_GET["fornitore"]) ? $tesdoc_e_partners : $tesdoc,
    $passo,
    ['id_tes' => 'desc'],
    ['sezione'=>1]
);
?>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("catdes"));
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
						data: {'type':'docacq',id_tes:id},
						type: 'POST',
						url: '../acquis/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./report_ddtacq.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
function printPdf(urlPrintDoc){
	$(function(){
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $("html, body").delay(100).animate({scrollTop: $('#framePdf').offset().top},200, function() {
        $("#framePdf").focus();
    });
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'});
		});
	});
};
</script>
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>"  name="auxil" class="clean_get">
	<div class="framePdf panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
		<div class="col-lg-12">
    <div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
		<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="framePdf"  style="height: 100%; width: 100%" src=""></iframe>
	</div>
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
    <p><b>documento di trasporto:</b></p>
    <p>ID:</p>
    <p class="ui-state-highlight" id="idcodice"></p>
    <p>Fornitore</p>
    <p class="ui-state-highlight" id="iddescri"></p>
	</div>
    <div align="center" class="FacetFormHeaderFont">D.d.T. acquisti della sezione
      <select name="sezione" class="FacetSelect" onchange="this.form.submit()">
        <?php
        for ($i = 1; $i <= 9; $i++) {
          $selected = ($sezione == $i) ? "selected" : "";
          echo "<option value='$i' $selected > $i </option>\n";
        }
        ?>
      </select>
    </div>
	<?php
        $ts->output_navbar();
	?>
	<div class="table-responsive">
    <table class="Tlarge table table-striped table-bordered table-condensed">
        <tr>
          <td class="FacetFieldCaptionTD">
          </td>
          <td class="FacetFieldCaptionTD">
              <?php  gaz_flt_disp_select("tipo", "tipdoc as tipo", $tesdoc_e_partners, $ts->where, "tipdoc ASC"); ?>
          </td>
          <td class="FacetFieldCaptionTD">
                  <?php gaz_flt_disp_int("numdoc", "Numero"); ?>
          </td>
          <td  class="FacetFieldCaptionTD">
              <?php  gaz_flt_disp_select("anno", "YEAR(datemi) as anno", $tesdoc_e_partners, $ts->where, "anno DESC"); ?>
          </td>
          <td class="FacetFieldCaptionTD">
          <?php
          if ($partner_select) {
            gaz_flt_disp_select("fornitore", "clfoco AS fornitore, ragso1 as nome",
            $tesdoc_e_partners,
            $ts->where, "nome ASC", "nome");
          } else {
            gaz_flt_disp_int("fornitore", "Fornitore");
          }
          ?>
          </td>
          <td class="FacetFieldCaptionTD">
          </td>
          <td class="FacetFieldCaptionTD">
          </td>
          <td  class="FacetFieldCaptionTD">
            <input type="submit" class="btn btn-sm btn-default" name="search" value="<?php echo $script_transl['search'];?>" onClick="javascript:document.report.all.value=1;">
            <a class="btn btn-sm btn-default" href="?">Reset</a>
            <?php  $ts->output_order_form(); ?>
            </td>
        </tr>
        <tr>
          <?php
          // creo l'array (header => campi) per l'ordinamento dei record
          $headers_tesdoc = array(
              "ID" => "id_tes",
              "Tipo" => "tipdoc",
              "Numero" => "numdoc",
              "Data" => "datemi",
              "Fornitore (cod.)" => "clfoco",
              "Status" => "",
              "Stampa" => "",
              "Cancella" => ""
          );
          ?>
        </tr>
        <tr>
          <?php
          $ts->output_headers();
          ?>
        </tr>
        <?php
        $result = gaz_dbi_dyn_query(cols_from($gTables['tesdoc'],
						  "id_tes","tipdoc","ddt_type","seziva","datemi","numdoc","numfat","datfat","status") . ", " .
              cols_from($gTables['anagra'],
						  "fe_cod_univoco",
						  "pec_email",
						  "ragso1",
						  "ragso2",
						  "e_mail"),
              $tesdoc_e_partners,
              $ts->where,
              $ts->orderby,
              $ts->getOffset(),
              $ts->getLimit() );
        while ($r = gaz_dbi_fetch_array($result)) {
          // controllo ogni rigo se è ultimo movimento per quel tipdoc
          $ddtanomalo=($r['status']=='DdtAnomalo')?'<small class="text-warning" title="Il DdT è stato generato da una fattura elettronica con riferimenti ai righi errati o mancanti"> &nbsp; (<sup>*</sup>) &nbsp; </small>':'';
          $order='id_tes DESC';
          if  (substr($r['tipdoc'],0,2) == 'DD') {
            $where = "tipdoc LIKE 'DD_' AND seziva = ".$r['seziva']." AND numfat = 0" ;
            $order='numdoc DESC';
            $title="Modifica documento";
          } elseif  (substr($r['tipdoc'],0,2) == 'AF'){ // fattura o nota credito fornitore
            $where = "tipdoc LIKE 'AF_' AND seziva = ".$r['seziva']." AND YEAR(datreg) = '".substr($r['datfat'],0,4)."'";
            $order='protoc DESC';
            if ($r['ddt_type']=="T" OR $r['ddt_type']=="L"){
              //$update="disabled";
            }
            $title="Cancellare la fattura per modificare il DDT";
          } elseif  (substr($r['tipdoc'],0,2) == 'AD'){
            $where = "tipdoc LIKE 'AD_'";
            $order='id_tes DESC';
            $title="Modifica documento";
          } elseif  (substr($r['tipdoc'],0,2) == 'RD'){
            $where = "tipdoc LIKE 'RD_' AND seziva = ".$r['seziva'];
            $order='id_tes DESC';
            $title="Modifica documento";
          }
          $addtipdoc="DDT";
          $btncol='edit';
          switch ($r['tipdoc']) {
            case "RDL":
              $btncol='warning';
              $addtipdoc="RDL";
              break;
            case "DDL":
              $btncol='info';
              $addtipdoc="DDL";
              break;
            case "DDR":
              $btncol='danger';
              $addtipdoc="DDR";
              break;
          }
          if ($r['tipdoc']=="AFT" AND $r['ddt_type']=="T"){
            $addtip="ADT &#8594; ";
          } elseif ($r['tipdoc']=="AFT" AND $r['ddt_type']=="L"){
            $addtip="RDL &#8594; ";
          } else {
            $addtip="";
          }
          echo "<tr>";
          echo '<td class="text-center"><a class="btn btn-xs btn-'.$btncol.'" href="admin_docacq.php?id_tes=' . $r["id_tes"] . '&Update&'.$addtipdoc.'" title="'. $title .'" >  <i class="glyphicon glyphicon-edit"></i>&nbsp;' . $r["id_tes"] . '</a></td>';
          echo '<td class="text-center">' . $addtip.$r["tipdoc"] . " &nbsp;</td>";
          echo '<td class="text-center">'. $r["numdoc"] . ' '.$ddtanomalo.'</td>';
          echo '<td class="text-center">'. gaz_format_date($r["datemi"]). " &nbsp;</td>";
          echo "<td>" . $r["ragso1"] . "&nbsp;</td>";
          if (intval(preg_replace("/[^0-9]/","",$r['numfat']))>=1){
            echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" ".($pdf_to_modal==0?'href="stampa_docacq.php?id_tes=' . $r["id_tes"] .'&template=FatturaAcquisto" target="_blank"':"onclick=\"printPdf('stampa_docacq.php?id_tes=" . $r["id_tes"] ."')\"")."><i class=\"glyphicon glyphicon-print\" title=\"Stampa fattura n. " . $r["numfat"] . " PDF\"></i> fatt. n. " . $r["numfat"] . "</a></td>";
          } else {
            echo "<td>" . $r["status"] . " &nbsp;</td>";
          }
          echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" style=\"cursor:pointer;\" ".($pdf_to_modal==0?'href="stampa_docacq.php?id_tes=' . $r["id_tes"] .'&template=DDT" target="_blank"':"onclick=\"printPdf('stampa_docacq.php?id_tes=" . $r["id_tes"] ."&template=DDT')\"")."><i class=\"glyphicon glyphicon-print\" title=\"Stampa documento PDF\"></i></a></td>";
          echo '<td class="text-center">';
          $check_lot_exit = $lm -> check_lot_exit("",$r['id_tes']);// controllo se è già uscito qualche articolo lotto con lo stesso id lotto

          if (substr($r['tipdoc'], 0, 2)=="AF" ){
            ?>
            <button title="Questo Ddt &egrave; stato fatturato. Per eliminarlo devi prima eliminare la relativa fattura" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
            <?php
          } elseif($check_lot_exit===TRUE){
            ?>
            <button title="Non puoi eliminare questo DDT perché almeno uno dei suoi articoli ha un ID lotto che è già uscito dal magazzino" class="btn btn-xs   disabled"><i class="glyphicon glyphicon-trash"></i></button>
            <?php
          } else {
            ?>
            <a class="btn btn-xs  btn-elimina dialog_delete" title="Elimina questo D.d.T." ref="<?php echo $r['id_tes'];?>" catdes="<?php echo $r['ragso1']; ?>">
              <i class="glyphicon glyphicon-trash"></i>
            </a>
            <?php
          }
          echo "</td></tr>";
        }
        ?>
    </table>
    </div>
</form>

<script src="../../js/custom/fix_select.js" type="text/javascript"></script>
<script src="../../js/custom/clean_empty_form_fields.js" type="text/javascript"></script>
<?php
require("../../library/include/footer.php");
?>
