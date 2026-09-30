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
  scriva   alla   Free  Software Foundation,  Inc.,   59
  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
  --------------------------------------------------------------------------
 */
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();

function getErrors() {
    global $gTables, $admin_aziend;
    $e = [];
    $sql1 = "SELECT * FROM ".$gTables['paymov']." LEFT JOIN ".$gTables['rigmoc']." ON ".$gTables['paymov'].".id_rigmoc_pay=".$gTables['rigmoc'].".id_rig WHERE ".$gTables['paymov'].".id_rigmoc_pay>0 AND ".$gTables['rigmoc'].".id_rig IS NULL ORDER BY  ".$gTables['paymov'].".id;";
    $sql2 = "SELECT * FROM ".$gTables['paymov']." LEFT JOIN ".$gTables['rigmoc']." ON ".$gTables['paymov'].".id_rigmoc_doc=".$gTables['rigmoc'].".id_rig WHERE ".$gTables['paymov'].".id_rigmoc_doc>0 AND ".$gTables['rigmoc'].".id_rig IS NULL ORDER BY  ".$gTables['paymov'].".id;";
    $rs = gaz_dbi_query($sql1);
    while ($r = gaz_dbi_fetch_array($rs)) {
      $r['op_cl']='Chiusura (pagamento)';
      $e[]=$r;
    }
    $rs = gaz_dbi_query($sql2);
    while ($r = gaz_dbi_fetch_array($rs)) {
      $r['op_cl']='Apertura (Fattura)';
      $e[]=$r;
    }
    return $e;
}

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
    $form['hidden_req'] = htmlentities($_POST['hidden_req']);
    $form['ritorno'] = $_POST['ritorno'];
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
		$("p#iddescri").html($(this).attr("descri"));
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
						data: {'type':'paymov',id_paymov:id},
						type: 'POST',
						url: './delete.php',
						success: function(output){
							window.location.replace("./error_paymov.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>

<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
  <p><b>Movimento orfano:</b></p>
  <p>ID:</p>
  <p class="ui-state-highlight" id="idcodice"></p>
  <p>Riferimenti:</p>
  <p class="ui-state-highlight" id="iddescri"></p>
</div>

<form method="post">
<input type="hidden" value="<?php echo $form['hidden_req']; ?>" name="hidden_req" />
<input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno" />
<div class="text-center FacetFormHeaderFont">Controllo movimenti di scadenzario senza riferimento ad un movimento contabile (orfani)</div>
<?php
$m = getErrors();
if (sizeof($m) > 0) {
?>
<div class="text-center bg-danger text-danger"><b>Probabilmente i problemi sono stati causati in fase di registrazione/modifica/eliminazione dei seguenti movimenti contabili, si consiglia  la cancellazione tramite i bottoni sotto riportati e il successivo ricontrollo manuale dello scadenzario degli stessi clienti/fornitori</b></div>
<div class="table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed">
<?php
  foreach ($m AS $key => $mv) {
    //qui tento di riferire al documento che lo ha generato tramite id_tesdoc_ref
    $reflink='';
    $refdesc='Non riconducibile ad alcun documento';
    $regiva=intval(substr($mv['id_tesdoc_ref'],4,1));
    if ( $regiva > 0 ) { // solo se è riferito ad un protocollo di un registro IVA posso supporre (ma non è detto) che il problema sia stato causato da questo documento, se lo trovo lo visualizzo
      $year=intval(substr($mv['id_tesdoc_ref'],0,4));
      $protoc=intval(substr($mv['id_tesdoc_ref'],6,9));
      $seziva=intval(substr($mv['id_tesdoc_ref'],5,1));
      $tesmov = gaz_dbi_get_row($gTables['tesmov'], "seziva",$seziva," AND protoc=".$protoc." AND YEAR(datreg)=".$year." AND regiva=".$regiva);
      $clfoco = gaz_dbi_get_row($gTables['clfoco'], "codice",$tesmov['clfoco']);
      if ($tesmov){
        $reflink=$regiva>5?'../acquis/supplier_payment.php?partner='.$tesmov['clfoco']:'../vendit/customer_payment.php?partner='.$tesmov['clfoco'];
        $refdesc=$tesmov['descri'].' n.'.$tesmov['numdoc'].' registrato il '.gaz_format_date($tesmov['datreg']).' con protocollo n.'.$tesmov['protoc'].' sez.'.$tesmov['seziva'];
      }
    }
    echo '<tr>
    <td><a class="btn btn-info btn-xs" href="'. $reflink . '" >Controlla '.$clfoco['descri'].'</a></td>
    <td>'.$mv['op_cl'].'</td><td>'.$refdesc.'</td>
    <td class="text-right"> € '.gaz_format_number($mv['amount']).'</td>
    <td><a class="btn btn-xs  btn-elimina dialog_delete" title="Cancella il movimento" ref="'.$mv['id'].'" descri="'. $refdesc.'">
        <i class="glyphicon glyphicon-trash"></i>
        </a>
    </tr>';
  }
?>
</table>
</div>
<?php
} else {
?>
<div class="text-center bg-success text-success"><b>Non sono stati trovati movimenti orfani</b></div>
<?php
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>
