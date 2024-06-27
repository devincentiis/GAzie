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
$admin_aziend = checkAdmin(8);
$msg = array('err' => array(), 'war' => array());
$paymov = new Schedule;

if (!isset($_POST['hidden_req'])) { //al primo accesso allo script per update
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
    $form['search_partner'] = '';
    $form['descri_partner'] = '';
    $form['id_partner'] = 0;
    if (isset($_GET['id_partner'])){
        $form['id_partner'] = intval($_GET['id_partner']);
        $partner = gaz_dbi_get_row($gTables['clfoco'], 'codice',  $form['id_partner']);
        $form['search_partner'] = $partner['descri'];
        $form['descri_partner'] = $partner['descri'];
    }
} else { // accessi successivi
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = $_POST['hidden_req'];
    $form['id_partner'] = intval($_POST['id_partner']);
    $form['search_partner'] = '';
    if ($form['id_partner']>0){
       $partner = gaz_dbi_get_row($gTables['clfoco']." LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id", 'codice',  $form['id_partner']);
       $form['search_partner'] = $partner['ragso1'];
    }
    // Se viene inviata la richiesta di cambio produzione
    if ($_POST['hidden_req'] == 'change_partner') {
        $form['id_partner'] = 0;
        $form['search_partner'] = '';
        $form['descri_partner'] = '';
        $form['hidden_req'] = '';
    }

    if (count($msg['err']) <= 0) { // non ci sono errori, posso procedere
    }

}

function random_color_part() {
    return str_pad( dechex( rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
}

function random_color() {
    return '#'.random_color_part() . random_color_part() . random_color_part();
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/autocomplete','html-svg-connect/jquery.html-svg-connect'));
?>
<script>
$( function() {
    $( "#search_partner" ).autocomplete({
        source: "search.php?opt=partner",
        minLength: 3,
        html: true, // optional (jquery.ui.autocomplete.html.js required)

        // optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
        select: function(event, ui) {
            $("#id_partner").val(ui.item.value);
            $(this).closest("form").submit();
        }
    });

	$("#dialog_paymov").dialog({ autoOpen: false });
	$('.dialog_paymov').click(function() {
		var fragment = '';
		var id_partner = $(this).attr('id_partner');
		var val_amount = $(this).attr('val_amount');
		$("span#idsaldo").html(val_amount);
		$.post("operat.php",
		  {type:'propose_schedule', ref:id_partner, val:val_amount},
		  function (data) {
			var ctrl=0;
			$.each(data, function (i, v) {
				if (ctrl!=v['id_tesdoc_ref']){
					fragment +='<div class="bg-info col-xs-12">'+v['descridoc']+'</div>';
				}
				var exp = new Date(v['expiry']);
				fragment +='<div class="col-xs-8 text-right">Scadenza: '+exp.toLocaleDateString('IT-it')+'</div><div class="col-xs-4 text-right">€ '+v['amount'].toFixed(2)+'</div>';
				ctrl=v['id_tesdoc_ref'];
			});
			$("#proposeTable").append(fragment);
		  }, "json"
        );
		$( "#dialog_paymov" ).dialog({
			minHeight: 1,
			minWidth: 600,
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
				delete:{
					text:'Allinea ad € '+val_amount,
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {type:'align_schedule', ref:id_partner, val:val_amount},
						type: 'POST',
						url: './operat.php',
						success: function(output){
							window.location.replace("./reconstruction_schedule.php?id_partner="+id_partner);
						}
					});
				}},
				"Annulla": function() {
					$(this).dialog("destroy");
					$("#proposeTable").html('');
				}
			}
		});
		$("#dialog_paymov" ).dialog( "open" );
	});

});
</script>
<form method="POST" name="form">
	<div style="display:none" id="dialog_paymov" title="Allineamento scadenzario">
	<h4 class="text-center">Proposta di riallinaemento</h4>
        <p class="ui-state-highlight">Questa è una proposta di riallineamento automatico al saldo contabile, <b> solo se il VALORE di € <span id="idsaldo"></span> è GIUSTO </b> puoi confermare ed eliminare tutto lo scadenzario precedente per ricrearne uno come quello della tabella sottostante</p>
		<table id="proposeTable"></table>
	</div>
<input type="hidden" name="ritorno" value="<?php echo $form['ritorno']; ?>">
<input type="hidden" name="hidden_req" value="<?php echo $form['hidden_req']; ?>">
<?php
$gForm = new informForm();

if (count($msg['err']) > 0) { // ho un errore
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
}
if (count($msg['war']) > 0) { // ho un alert
    $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
}
?>
<div class="h3 text-center"><?php echo ucfirst($script_transl['title']); ?></div>
<div class="panel panel-default div-bordered">
  <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="id_partner" class="col-sm-4 control-label text-right"><?php echo $script_transl['id_partner']; ?></label>
    <?php
    $gForm->selectPartner($form['search_partner'], $form['id_partner'], $admin_aziend['mascli']);
    ?>
                </div>
            </div>
        </div><!-- chiude row  -->
<?php
$date='2060-12-31'; // praticamente ricostruisco sempre tutto
// prendo l'array con i righi  ed il saldo contabile per confrontarlo con quello dello scedenziario
$allrows = $paymov->getPartnerAccountingBalance($form['id_partner'], $date, true);
$paymov->getPartnerStatus($form['id_partner'], $date,'DESC');
krsort($allrows['rows']);
$sc=reset($allrows);
$ss=reset($paymov->docData);
$saldocontabile=(substr($form['id_partner'],0,3)==$admin_aziend['mascli'])?$sc:-$sc;
$saldoscadenzario=$ss['saldo'];
if ($form['id_partner'] > 100000000) { // partner selezionato
    // Qui eseguo il controllo di congruità tra il saldo derivante dai movimenti di scadenzario (paymov) e quello contabile, se coincidono non faccio alcuna proposta, altrimenti mi comporto in maniera diversa a secondo che sia maggiore l'uno o l'altro. Se maggiore il saldo contabile propongo l'apertura di una o più partite, se maggiore quello dello scadenzario propongo l'eliminazione di una o più scadenze. Usando il pulsante "Automatico" il riallineamento avverrà in automatico. In ogni caso quello che fa fede è il SALDO CONTABILE! Si prende per giusto sempre e comunque quello, se così non fosse bisognerà modificare prima le registrazioni contabili in quanto dati fiscalmente rilevanti (libro giornale).
    $diff_saldi=$saldocontabile-$saldoscadenzario;
    $dida='<p class="bg-warning">ATTENZIONE! Il riallineamento dello scadenzario verrà eseguito partendo dal valore del saldo contabile di  <b> € '.gaz_format_number($saldocontabile).'  RITENUTO GIUSTO </b> in quanto ha valenza fiscale ( libro giornale ). Esso modificherà i soli dati presenti sui movimenti dello scadenzario (tabella di sinistra) lasciando aperte le ultime partite in ordine di scadenze decrescenti. Se il saldo contabile non è giusto astenersi dall\'uso di questo automatismo ed agire manualmente sui singoli movimenti di prima nota e correggere il saldo contabile del partner commerciale.</p>';
    if ($diff_saldi>=0.01){
        $btn_diff='<div class="btn btn-danger col-xs-12 dialog_paymov" val_amount="'.$saldocontabile.'" id_partner="'.$form['id_partner'].'">Differenza saldi € '. gaz_format_number(abs($diff_saldi)).', clicca per riallineare al saldo contabile di <b>€'. gaz_format_number($saldocontabile).'</b></div>'.$dida;
    } elseif ($diff_saldi <= -0.01){
        $btn_diff='<div class="btn btn-danger col-xs-12 dialog_paymov" val_amount="'.$saldocontabile.'" id_partner="'.$form['id_partner'].'">Differenza saldi € '. gaz_format_number(abs($diff_saldi)).', clicca per riallineare al saldo contabile di <b>€ '. gaz_format_number($saldocontabile).'</b></div>'.$dida;
    } else {
        $btn_diff='<div class="btn btn-success col-xs-12">***** SALDI COINCIDENTI *****</div>';
    }
    echo '<tr><td colspan=6 class="text-center">'.$btn_diff.'</td></tr>';

    ?>
<div class="col-xs-6">
<h3 class="sub-header">Movimenti dello scadenzario</h3>
    <div class="table-responsive">
        <table class="table">
             <thead>
                <tr>
                  <th class="col-xs-3 text-center">Documento</th>
                  <th class="col-xs-3 text-center">Scadenza</th>
                  <th class="col-xs-3 text-right">Importo</th>
                  <th class="col-xs-3 text-right">Progressivo</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
<?php
    $first_row_pm=true;
    $svg_conn=[];
    foreach ($paymov->PartnerStatus as $k => $v) {
        if ($first_row_pm) {
            $progressivo= $paymov->docData[$k]['saldo'];
            $first_row_pm=false;
            echo '<tr><td colspan=4 class="text-right">Saldo: <b>'.gaz_format_number($progressivo).'</b></td></tr>';
        }
        $amount = 0.00;
        $svg_conn['open'][]=array('stroke'=>random_color(),'id_tes'=>$paymov->docData[$k]['id_tes']);
        echo '<tr>';
        echo '<td class="FacetDataTD" colspan=4><a class="btn btn-xs btn-default" title="Modifica il movimento contabile '.$paymov->docData[$k]['id_tes'].' e/o lo scadenzario" href="../contab/admin_movcon.php?Update&id_tes='. $paymov->docData[$k]['id_tes'] . '"><i class="glyphicon glyphicon-edit"></i>' .$paymov->docData[$k]['descri'] . ' n.' . $paymov->docData[$k]['numdoc'] . ' del ' . gaz_format_date($paymov->docData[$k]['datdoc']) . '</a> ID partita'.$k.'</td><td id="pm'.$paymov->docData[$k]['id_tes'].'" title="pm'.$paymov->docData[$k]['id_tes'].'"></td></tr>';
        $row_cl=0;
        foreach ($v as $ki => $vi) {
            $class_paymov = 'FacetDataTDevidenziaCL';
            $v_op = '';
            $cl_exp = '';
            if ($vi['op_val'] >= 0.01) {
                $v_op = gaz_format_number($vi['op_val']);
                $progressivo -= $vi['op_val'];
            }
            $v_cl = '';
            if ($vi['cl_val'] >= 0.01) {
                $v_cl = gaz_format_number($vi['cl_val']);
                $cl_exp = gaz_format_date($vi['cl_exp']);
                $progressivo += $vi['cl_val'];
            }
            $expo = '';
            if ($vi['expo_day'] >= 1) {
                $expo = $vi['expo_day'];
                if ($vi['cl_val'] == $vi['op_val']) {
                    $vi['status'] = 2; // la partita è chiusa ma è esposta a rischio insolvenza
                    $class_paymov = 'FacetDataTDevidenziaOK';
                }
            } else {
                if ($vi['cl_val'] == $vi['op_val']) { // chiusa e non esposta
                    $cl_exp = '';
                    $class_paymov = 'FacetDataTD';
                } elseif ($vi['status'] == 3) { // SCADUTA
                    $cl_exp = '';
                    $class_paymov = 'FacetDataTDevidenziaKO';
                } elseif ($vi['status'] == 9) { // PAGAMENTO ANTICIPATO
                    $class_paymov = 'FacetDataTDevidenziaBL';
                    $vi['expiry'] = $vi['cl_exp'];
                }
            }
            echo '<tr class="' . $class_paymov . '">';
            echo '<td class="text-right"> Scadenza del </td><td>' . gaz_format_date($vi['expiry']) . "</td>";
            echo '<td class="text-right">'. gaz_format_number($vi['op_val']).'</td>';
            $first_cl=true;
            foreach ($vi['cl_rig_data'] as $vj) {
                $row_cl++;
                if($first_cl){
                    echo '<td id="'.$paymov->docData[$k]['id_tes'].'_'.$row_cl.'" class="text-right"><a class="btn btn-xs btn-success"  href="../contab/admin_movcon.php?id_tes=' . $vj['id_tes'] . '&Update" title="' . $script_transl['update'] . ': ' . $vj['descri'] . '"><i class="glyphicon glyphicon-edit"></i>'. substr($vj['descri'],0,15) . ' €'. gaz_format_number($vi['cl_val']).'</a></td></tr>';
                    $first_cl=false;
                } else {
                    echo '<tr class="' . $class_paymov . '"><td colspan=4 id="'.$paymov->docData[$k]['id_tes'].'_'.$row_cl.'" class="text-right"><a class="btn btn-xs btn-success"  href="../contab/admin_movcon.php?id_tes=' . $vj['id_tes'] . '&Update" title="' . $script_transl['update'] . ': ' . $vj['descri'] . '"><i class="glyphicon glyphicon-edit"></i>'. $vj['descri'] . '</a></td></tr>';
                }
                $svg_conn['close'][]=array('stroke'=>random_color(),'id_tes'=>$vj['id_tes'],'row_cl'=>$paymov->docData[$k]['id_tes'].'_'.$row_cl);
            }
            if ($vi['status'] <> 1 || $vi['status'] < 9) { // accumulo solo se non è chiusa
                $amount += round($vi['op_val'] - $vi['cl_val'], 2);
            }
        }
        if (!isset($_POST['paymov'])) {
            $form['paymov'][$k][$ki]['amount'] = $amount;
            $form['paymov'][$k][$ki]['id_tesdoc_ref'] = $k;
        }
        $open = 'cl';
        if ($amount >= 0.01) {
            // attributo opcl per js come aperto
            $open = 'op';
        }
        echo '<tr><td colspan=3 class="text-right"><b>saldo partita: € ' . gaz_format_number($form['paymov'][$k][$ki]['amount']) . '</b></td><td class="text-right"><b>'.(($progressivo>=0.01)?gaz_format_number($progressivo):"")."</b></td></tr>\n";
    }
?>
            </tbody>
        </table>
    </div>
</div>
<div class="col-xs-1" id="svgContainer">
</div>
<div class="col-xs-5">
<h3 class="sub-header">Partitario dei movimenti contabili</h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                  <th></th>
                  <th class="col-xs-2">Data</th>
                  <th class="col-xs-3">Descrizione</th>
                  <th class="col-xs-2 text-right">Avere</th>
                  <th class="col-xs-2 text-right">Dare</th>
                  <th class="col-xs-3 text-right">Progressivo</th>
                </tr>
            </thead>
            <tbody>
<?php
foreach($allrows['rows'] as $k=>$r) {
        $progressivo= (substr($form['id_partner'],0,3)==$admin_aziend['mascli'])?$r['progressivo']:-$r['progressivo'];
?>
<tr ><td id="mc<?php echo $r['id_tes']; ?>"></td>
    <td>
    <?php echo '<a class="btn btn-xs btn-default"  href="../contab/admin_movcon.php?id_tes=' . $r['id_tes'] . '&Update" title="Modifica il movimento contabile ' . $r['id_tes'] . '"><i class="glyphicon glyphicon-edit">'.$r['id_tes'] .'</i><br>' . gaz_format_date($r['datreg'])  . "</a>\n";?>
    </td>
    <td><small><?php echo $r['descri'];?></small></td>
    <?php
    if ($r['darave']=='D') {
        echo '<td></td><td class="text-right">'.gaz_format_number($r['import']).'</td>';
    } else {
        echo '<td class="text-right">'.gaz_format_number($r['import']).'</td><td></td>';
    }
    ?>
    <td class="text-right"><?php echo gaz_format_number($progressivo);?></td>
</tr>
<?php
}
?>
            <tbody>
        </table>
    </div>
</div>


<?php
echo '<script type="text/javascript">
    $(function() {
      $("#svgContainer").HTMLSVGconnect({
        strokeWidth: 2,
        paths: [';
	$offset=32;
    foreach($svg_conn['open'] as $vo){
        echo ' { start: "#pm'.$vo['id_tes'].'", end: "#mc'.$vo['id_tes'].'", stroke: "'.$vo['stroke'].'", orientation: "vertical", offset: '.$offset.' },';
        $offset += 2;
    }
    foreach($svg_conn['close'] as $vc){
        echo ' { start: "#'.$vc['row_cl'].'", end: "#mc'.$vc['id_tes'].'", stroke: "'.$vc['stroke'].'", orientation: "vertical", offset: '.$offset.' },';
        $offset += 2;
    }
echo    '] });
    });
</script>';
}

?>
</div>
<?php
if ($form['id_partner'] > 100000000 && abs($diff_saldi)==0.00 && $saldocontabile==0.00 && $first_row_pm==false && count($allrows['rows'])>=1) {
?>
<div class="col-xs-1 col-lg-3"></div>
  <div class="btn btn-danger col-xs-10 col-lg-6 dialog_paymov" val_amount="0.00" id_partner="<?php echo $form['id_partner']; ?>"><b> ATTENZIONE!!!<br/>I saldi sono coincidenti e a ZERO ma ci sono delle partite aperte con chiusure che probabilmente non sono state riferite correttamente, se il saldo è realmente zero allora si consiglia di eliminare tutto lo scadenzario (a partite aperte)</b></div>
<div class="col-xs-1 col-lg-3"></div>

<?php
}
?>

</div>
</form>
<?php
require("../../library/include/footer.php");
?>
