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
if ($admin_aziend['Abilit'] >= 8) {
	if (isset($_POST['datref_cli'])){
		$form['datref_cli']=substr($_POST['datref_cli'],0,10);
	} else {
		$form['datref_cli']=date("d-m-Y");
	}
    ?>
    <!-- Scadenziari -->
	<div class="panel panel-info col-sm-12" >
          <div class="box-header company-color">
            <div class="box-title"><b><?php echo $script_transl['sca_scacli']; ?></b> -> data di riferimento:
			<?php echo '<input type="text" value="'.$form['datref_cli'].'" id="datref_cli" name="datref_cli" readonly>'; ?><small>(6 mesi prima e 6 dopo)</small>
			</div>
			<a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
          </div>
          <div class="box-body">
              <table id="clienti" class="table table-striped dataTable" role="grid" aria-describedby="clienti_info">
                  <thead>
                      <tr role="row">
                          <th><?php echo $script_transl['sca_cliente']; ?></th>
                          <th><?php echo $script_transl['sca_dare']; ?></th>
                          <th><?php echo $script_transl['sca_avere']; ?></th>
                          <th><?php echo $script_transl['sca_saldo']; ?></th>
                          <th><?php echo $script_transl['sca_scadenza']; ?></th>
                      </tr>
                  </thead>
                  <tbody>
                      <!-- Scadenzario clienti -->
                      <?php

	$rs_rate = gaz_dbi_dyn_query(
	$gTables["paymov"].'.*,'.$gTables["anagra"].'.ragso1,'.$gTables["rigmoc"].'.id_tes,'.$gTables["rigmoc"].'.codcon,'.$gTables["tesmov"].'.caucon,'.$gTables["tesmov"].'.numdoc,'.$gTables["tesmov"].'.datreg,'.$gTables["tesmov"].'.seziva,'.$gTables["tesmov"].'.datdoc',$gTables["paymov"]."
	LEFT JOIN ". $gTables["rigmoc"].' ON '.$gTables["paymov"].'.id_rigmoc_doc = '.$gTables["rigmoc"].".id_rig
	LEFT JOIN ". $gTables["tesmov"].' ON '.$gTables["rigmoc"].'.id_tes = '.$gTables["tesmov"].".id_tes
	LEFT JOIN ". $gTables["clfoco"].' ON '.$gTables["rigmoc"].'.codcon = '.$gTables["clfoco"].".codice
	LEFT JOIN ". $gTables['anagra'].' ON '.$gTables["clfoco"].'.id_anagra='.$gTables['anagra'] . ".id",
	"id_rigmoc_doc >0 AND expiry BETWEEN DATE_SUB('".gaz_format_date($form['datref_cli'],true)."',INTERVAL 6 MONTH) AND DATE_ADD('".gaz_format_date($form['datref_cli'],true)."',INTERVAL 6 MONTH) AND ".$gTables['rigmoc'] . ".codcon BETWEEN " . $admin_aziend['mascli'] . "000001 AND ".$admin_aziend['mascli']  . "999999",$gTables["paymov"].'.expiry ASC,'.$gTables["tesmov"].'.datdoc ASC,'.$gTables["tesmov"].'.seziva ASC, '.$gTables["tesmov"].'.protoc ASC');
	$paymov = new Schedule;
	//$paymov->setScheduledPartner($admin_aziend['mascli'],$form['datref_cli']);
	// impostazioni variabili
	$today = date("Y-m-d");
	$expiryFound = "";
	$id_tesdocrefFound = "";
	$diffDate = 99999999;
  $datetime2 = date_create(gaz_format_date($form['datref_cli'],true));
	$anagrafica = new Anagrafica();
	while($r=gaz_dbi_fetch_array($rs_rate)) {
		$paymov->setIdTesdocRef($r['id_tesdoc_ref']);
		$paymov->getExpiryStatus($r['expiry']);
		$v=$paymov->ExpiryStatus;
    $delbtn='';
    switch ($v['status']) {
      case 1: // pagata
        $lnk='title="Pagata"';
        $delbtn='<a class="btn btn-xs delete_customer_schedule" ref="'.$r['id_tesdoc_ref'].'" nome="'.$r['ragso1'].'" > <i class="glyphicon glyphicon-trash" title="Elimina partita chiusa, rimangono i movimenti contabili"> </i></a>';
      break;
      case 2: // esposta
        $lnk='href="../vendit/customer_payment.php?partner='.$r['codcon'].'" title="In scadenza"';
      break;
      case 3: // scaduta
        $lnk='href="../vendit/customer_payment.php?partner='.$r['codcon'].'" title="Scaduta, non pagata"';
      break;
      default: // non ancora scaduta
        $lnk='href="../vendit/customer_payment.php?partner='.$r['codcon'].'" title="Non scaduta"';
    }
    // controlli per calcolo data da visualizzare in prossimit? di oggi
    $datetime1 = date_create($v['expiry']);
		$diffDays = $datetime1->diff($datetime2);
		$nGiorni=$diffDays->format('%R%a');
    if(abs($nGiorni) <= $diffDate) {
      $expiryFound = $r['expiry'];
      $id_tesdocrefFound = $r['id_tesdoc_ref'];
      $diffDate = $nGiorni;
    }
		// costruzione chiave partita su cui posizionarsi
		$keyRowCli =  $r['expiry'].'_'.$r['id_tesdoc_ref'];
		// stampa colonne
		$desdoc=(strlen($r['numdoc'])>=1)?$r['numdoc'] .'/'.$r['seziva'] .' '.gaz_format_date($r['datdoc']):gaz_format_date($r['datreg']);
		echo "\n<tr class='text-left' role='row'>";
		echo '<td><small><a href="../contab/admin_movcon.php?id_tes='.$r['id_tes'].'&Update">'.$r['caucon'].' ' .$desdoc .' </a>'.$r['ragso1'] . "</small><span class='keyRow'>" . $keyRowCli . "</span></td>";
		echo "<td align='right'>". gaz_format_number($v['op_val']) . "</td>";
		echo "<td align='right'>" . gaz_format_number($v['cl_val']) . "</td>";
		echo "<td align='right'>" . gaz_format_number($v['op_val']-$v['cl_val'])."</td>";
		echo '<td align="center"><a class="btn btn-xs btn-'.$v['style'].'" '.$lnk.'><small>' . gaz_format_date($r['expiry']) . '</small></a>'.$delbtn.'</td>';
		echo "</tr>\n";
	}
	$keyRowFoundCli=$expiryFound.'_'.$id_tesdocrefFound;
    ?>
                  </tbody>
              </table>
			</div>
      </div>
	<div style="display:none" id="delete_customer_schedule" title="Conferma eliminazione">
        <p><b>Eliminazione scadenza:</b></p>
        <p>Partita ID:</p>
        <p class="ui-state-highlight" id="id_customer_schedule"></p>
        <p>Cliente:</p>
        <p class="ui-state-highlight" id="id_customer_descri"></p>
	</div>
    <script src="../../library/theme/lte/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="../../library/theme/lte/plugins/datatables/dataTables.bootstrap.min.js"></script>
    <script>
	$(function () {
		$("#clienti").DataTable({
            "oLanguage": {
                "sUrl": "../../library/theme/lte/plugins/datatables/Italian.json"
            },
            "lengthMenu": [[5, 10, 20, 50, -1], [5, 10, 20, 50, "Tutti"]],
            "iDisplayLength": 5,
			"responsive": true,
			"ordering": false,
            "stateSave": true

        });
		$('#datref_cli').each(function(){
			$(this).datepicker({ dateFormat: 'dd-mm-yy' });
		});
		$("#datref_cli").change(function () {
			this.form.submit();
		});
		$("#delete_customer_schedule").dialog({ autoOpen: false });
		$('.delete_customer_schedule').click(function() {
			$("p#id_customer_schedule").html($(this).attr("ref"));
			$("p#id_customer_descri").html($(this).attr("nome"));
			var id_tesdoc_ref = $(this).attr('ref');
			$( "#delete_customer_schedule" ).dialog({
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
							data: {'type':'customer_schedule',ref:id_tesdoc_ref},
							type: 'POST',
							url: '../vendit/delete.php',
							success: function(output){
			                    //alert(output);
								window.location.replace("./admin.php");
							}
						});
					}}
				}
			});
			$("#delete_customer_schedule" ).dialog( "open" );
		});

    });

  //*+ DC - 07/02/2018 - nuove funzioni per gestione posizionmento su scadenzari
  function gotoPage(id,num)
	{
		var table = $(id).DataTable();
		table.page( num ).draw( false );
	}

	function searchPageOnTable(id,keyRow,lenPage)
	{
		var table = $(id).DataTable();

		var plainArray = table
			.column(0)
			.data()
			.toArray();

		var i;

		for(i= 0 ; i < plainArray.length; i++)
		{
			if(plainArray[i].split('"keyRow">')[1].replace("</span>","") == keyRow)
				break;
		}

		return Math.floor(i / lenPage)
	}

	//add stylesheet css
	//$('document').ready(function() {
		$("head").append('<link rel="stylesheet" href="./admin.css">');
	//});

	$(window).on('load',(function(){
		// Scadenzario Clienti
		keyRowCli = "<?php echo $keyRowFoundCli ?>";

		if(keyRowCli != ""){
			setTimeout(function(){num = searchPageOnTable('#clienti',keyRowCli,$('#clienti').DataTable().page.len())
				gotoPage('#clienti',num);
				$("#clienti").css("max-height","none");
				$("#clienti").css("opacity","1");
				$(".wheel_load").css("display","none");
			},1000)
			}
			else
			{
				$("#clienti").css("max-height","none");
				$("#clienti").css("opacity","1");
				$(".wheel_load").css("display","none");
			}
		}));
    //*- DC - 07/02/2018 - nuove funzioni per gestione posizionmento su scadenzari
    </script>
    <?php
}
?>
