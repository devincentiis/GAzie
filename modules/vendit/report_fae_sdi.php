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
require('../../library/include/datlib.inc.php');
$admin_aziend = checkAdmin();
$cemail = gaz_dbi_get_row($gTables['company_config'], 'var', 'cemail');
$dest_fae_zip_package = gaz_dbi_get_row($gTables['company_config'], 'var', 'dest_fae_zip_package');
$send_fae_zip_package = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_zip_package');
$pecsdi_sdi_email = gaz_dbi_get_row($gTables['company_config'], 'var', 'pecsdi_sdi_email');

if (!isset($_POST['ritorno'])) {
	$form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
	$form['ritorno'] = $_POST['ritorno'];
}

$ricerca_fe = '';
$senza_esito = 0;
$mostra_intesta = 1;
$mostra_intesta_riga = 1;

@$id_record = $_GET['id_record'];

if (isset($_POST['Submit_file'])) { // conferma invio upload file
	if (!empty($_FILES)) {
		foreach($_FILES as $key => $file) {
			$exp_key = explode('_', $key);
			if ($exp_key[0] == 'p7mfile' && !empty($file['name'])) {
				$p7mfile = $file;
				$id_record = $exp_key[1];
				break;
			}
		}
		if (!empty($p7mfile) && !($p7mfile['type'] == "application/pkcs7-mime" || $p7mfile['type'] == "application/pkcs7" || $p7mfile['type'] == "text/xml")) {
			$msg_err = 'Formato del file ' . print_r($p7mfile, true) . ' non valido';
		} else {
			if (move_uploaded_file($p7mfile['tmp_name'], DATA_DIR.'files/' . $admin_aziend['codice'] . '/' . $p7mfile['name'])) { // nessun errore
				$msg_err = 'Caricamento del file riuscito!';
			} else { // no upload
				$msg_err = 'Caricamento del file non riuscito';
			}
		}
	}
}

if (isset($_GET['all'])) {
	$where = '';
	$status = '';
	$form['ritorno'] = '';
	$mostra_intesta = 1;
	$mostra_intesta_riga = 1;
} elseif (!empty($id_record)) {
	//da migliorare l'interazione
	if (!empty($send_fae_zip_package['val']) && !empty($_GET['id_tes_ref']) && !empty($_GET['file_name'])) {
		require('../../library/include/electronic_invoice.inc.php');
		require('../../library/' . $send_fae_zip_package['val'] . '/SendFaE.php');
		$testata = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "id_tes = " . $_GET['id_tes_ref']);
		$file_path = DATA_DIR.'files/' . $admin_aziend['codice'] . '/';
		$file_url = $file_path . $_GET['file_name'];
		$file_content = create_XML_invoice($testata, $gTables, 'rigdoc', false, 'from_string.xml');
		file_put_contents($file_url, $file_content);
		$IdentificativoSdI = SendFatturaElettronica($file_url);
		if (!empty($IdentificativoSdI)) {
			if (is_array($IdentificativoSdI)) {
				gaz_dbi_put_row($gTables['fae_flux'], "id", $id_record, "flux_status", "@");
				gaz_dbi_put_query($gTables['fae_flux'], "id = " . $id_record, "id_SDI", $IdentificativoSdI[0]);
				header('Location: report_fae_sdi.php?post_xml_result=OK');
			} else {
				echo '<p>' . print_r($IdentificativoSdI, true) . '</p>';
			}
		}
	} else if (!empty($p7mfile)) {
		gaz_dbi_put_row($gTables['fae_flux'], "id", $id_record, "filename_ori", $p7mfile['name']);
		require('../../library/include/electronic_invoice.inc.php');
		require('../../library/' . $send_fae_zip_package['val'] . '/SendFaE.php');
		$file_path = DATA_DIR.'files/' . $admin_aziend['codice'] . '/';
		$file_url = $file_path . $p7mfile['name'];
		gaz_dbi_put_row($gTables['fae_flux'], "id", $id_record, "flux_status", "#");
		$IdentificativoSdI = SendFatturaElettronica($file_url);
		if (!empty($IdentificativoSdI)) {
			if (is_array($IdentificativoSdI)) {
				gaz_dbi_put_row($gTables['fae_flux'], "id", $id_record, "flux_status", "@");
				gaz_dbi_put_query($gTables['fae_flux'], "id = " . $id_record, "id_SDI", $IdentificativoSdI[0]);
				header('Location: report_fae_sdi.php?post_xml_result=OK');
			} else {
				echo '<p>' . print_r($IdentificativoSdI, true) . '</p>';
			}
		}
	} else {
		gaz_dbi_put_row($gTables['fae_flux'], "id", $id_record, "flux_status", "@");
	}
	$status = '';
} else {

	if (isset($_GET['ricerca_fe'])) {
		$passo = 1000000;
		$ricerca_fe = $_GET['ricerca_fe'];
		$status = '';
		$where = " filename_ori LIKE '%" . $ricerca_fe . "%' OR numfat LIKE '" . $ricerca_fe . "' OR id_SDI LIKE '" . $ricerca_fe . "'";
		$mostra_intesta = 1;
	}

	if (empty($ricerca_fe)) {
		$status = '';
		if (!empty($_GET['id_tes'])) {
			$where = " id_tes_ref = " . $_GET['id_tes'] . "";
			$mostra_intesta = 1;
		}

		if (isset($_GET['status'])) {
			$passo = 1000000;
			$status = $_GET['status'];

			if ($status == 'NO') {
				//$status = '@';
				$where = " flux_status != 'RC' AND flux_status != 'MC' AND flux_status != 'DT' AND flux_status != 'NS' AND flux_status != 'NE' AND flux_status != 'NEEC01' AND flux_status != 'NEEC02'";
				//$senza_esito = 1;
				$mostra_intesta = 1;
				$mostra_intesta_riga = 0;
			} elseif ($status == 'NEEC01') {
				$where = " flux_status LIKE 'NEEC01'";
				//$senza_esito = 1;
				$mostra_intesta = 1;
				$mostra_intesta_riga = 0;
			} elseif ($status == 'NEEC02') {
				$where = " flux_status LIKE 'NEEC02'";
				//$senza_esito = 1;
				$mostra_intesta = 1;
				$mostra_intesta_riga = 0;
			} elseif (strpos($status, 'NE') !== FALSE) {
				$where = " flux_status LIKE 'NE%'";
				//$senza_esito = 1;
				$mostra_intesta = 1;
				$mostra_intesta_riga = 0;
			} elseif ($status == '@@' || $status == 'IN') {
				$where = " (flux_status LIKE '@@' OR flux_status LIKE 'IN') AND filename_ret <> ''";
				//$senza_esito = 1;
				$mostra_intesta = 1;
				$mostra_intesta_riga = 0;
			} elseif ($status == '#' || $status == 'DI') {
				$where = " (flux_status LIKE '#' OR flux_status LIKE 'DI')";
				//$senza_esito = 1;
				$mostra_intesta = 1;
				$mostra_intesta_riga = 0;
			} elseif ($status == '##' || $status == 'PA') {
				$where = " (flux_status LIKE '#' OR flux_status LIKE 'PA')";
				//$senza_esito = 1;
				$mostra_intesta = 1;
				$mostra_intesta_riga = 0;
			} else {
				$where = " flux_status LIKE '" . $status . "'";
				$mostra_intesta = 1;
				$mostra_intesta_riga = 0;
			}
		}
	}

	if (isset($_GET['post_xml_result'])) {
		//TO-DO: POPUP DI ESITO INOLTRO XML A SISTEMA ESTERNO
	}

}


require("../../library/include/header.php");
$script_transl=HeadMain(0,array('calendarpopup/CalendarPopup',
                                  'custom/modal_form',
                                  'custom/varie'));
?>
<script>
$(function() {
   $( "#dialogMail" ).dialog({
      autoOpen: false
   });

   $( "#dialogSend" ).dialog({
      autoOpen: false
   });
});

function confirMail(link) {
   na_fi = link.id.replace("fn", "");
   $.fx.speeds._default = 500;
   targetUrl = $("#fn"+na_fi).attr("url");
   //alert (na_fi);
   $("p#mail_adrs").html($("#fn"+na_fi).attr("mail"));
   $("p#mail_attc").html($("#fn"+na_fi).attr("namedoc"));
   $( "#dialogMail" ).dialog({
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
   $("#dialogMail" ).dialog( "open" );
}

function confirSend(link) {
   na_fi = link.id.replace("zn", "");
   $.fx.speeds._default = 500;
   targetUrl = $("#zn"+na_fi).attr("url");
   $("p#send_lbry").html($("#zn"+na_fi).attr("library"));
   $("p#send_attc").html($("#zn"+na_fi).attr("namedoc"));
   $( "#dialogSend" ).dialog({
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
   $("#dialogSend" ).dialog( "open" );
}
</script>
<?php
$gForm = new GAzieForm();
echo '<form method="GET">';
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title'];
echo "</div>\n";
if (strlen($cemail['val'])>5 || strlen($dest_fae_zip_package['val'])>5 || ( $pecsdi_sdi_email && strlen($pecsdi_sdi_email['val'])>5) ) {
	$yes_mail = ' enabled ';
}
if (isset($send_fae_zip_package['val']) && strlen($send_fae_zip_package['val'])>5) {
	$yes_send = ' enabled ';
}
if (empty($yes_mail) && empty($yes_send)) {
	$yes_mail = ' disabled ';
	$yes_send = ' disabled ';
	echo "<p class=\"bg-danger text-center\">La configurazione avanzata azienda non ha alcun indirizzo email per il servizio di invio fatture elettroniche</p>";
	echo "<p class=\"bg-danger text-center\">La configurazione avanzata azienda non ha alcuna libreria di terze parti per il servizio di inoltro fatture elettroniche</p>";
} else {
	$yes_mail = (!empty($yes_mail) && $yes_mail == ' enabled ') ? '' : ' disabled ';
	$yes_send = (!empty($yes_send) && $yes_send == ' enabled ') ? '' : ' disabled ';
	echo '<p align="center"><a href="check_fae_sdi.php">' . $script_transl['checkfae'] . '</a></p>';
}

if (!empty($msg_err)) {
	echo "<p class=\"bg-danger text-center\">" . $msg_err . "</p>";
}

$recordnav = new recordnav($gTables['fae_flux'].' LEFT JOIN '.$gTables['tesdoc'].' ON '.$gTables['fae_flux'].'.id_tes_ref = '.$gTables['tesdoc'].'.id_tes', $where, $limit, $passo);
$recordnav->output();
?>

<br />
<div class="box-primary table-responsive">
<table id ="tableId" name="tableId" class="Tlarge table table-striped table-bordered table-condensed">
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">

    <div style="display:none" id="dialogMail" title="<?php echo $script_transl['mail_alert0']; ?>">
        <p id="mail_alert1"><?php echo $script_transl['mail_alert1']; ?></p>
        <p class="ui-state-highlight" id="mail_adrs"></p>
        <p id="mail_alert2"><?php echo $script_transl['mail_alert2']; ?></p>
        <p class="ui-state-highlight" id="mail_attc"></p>
    </div>

    <div style="display:none" id="dialogSend" title="<?php echo $script_transl['send_alert0']; ?>">
        <p id="send_alert1"><?php echo $script_transl['send_alert1']; ?></p>
        <p class="ui-state-highlight" id="send_lbry"></p>
        <p id="send_alert2"><?php echo $script_transl['send_alert2']; ?></p>
        <p class="ui-state-highlight" id="send_attc"></p>
    </div>

<tr style="margin-bottom: 20px !important;">
<td class="FacetFieldCaptionTD">
<input type="text" name="ricerca_fe" id="ricerca_fe" value="<?php echo $ricerca_fe ?>" maxlength="30" tabindex="1" class="FacetInput">
</td>
<td class="FacetFieldCaptionTD" colspan="2">


<select name="status">
	<option value=""></option>
	<option value="##" <?php if($status=="##"||$status=="PA") echo "selected";?> >## - Non ancora firmata</option>
	<option value="#" <?php if($status =="#"||$status=="DI") echo "selected";?> ># - Non ancora inviata</option>
	<option value="@" <?php if($status =="@"||$status=="IN") echo "selected";?> >@ - Inviata</option>
	<option value="@@" <?php if($status =="@@") echo "selected";?> >@@- Inviata sistema esterno</option>
	<option value="NS" <?php if($status =="NS") echo "selected";?> >NS - Notifica scarto</option>
	<option value="MC" <?php if($status =="MC") echo "selected";?> >MC - Mancata consegna</option>
	<option value="RC" <?php if($status =="RC") echo "selected";?> >RC - Ricevuta consegna</option>
	<option value="DT" <?php if($status =="DT") echo "selected";?> >DT - Decorrenza termini</option>
	<option value="NE" <?php if($status =="NE") echo "selected";?> >NE - Notifica esito</option>
	<option value="NEEC01" <?php if($status =="NEEC01") echo "selected";?> >NEEC01 - Accettata</option>
	<option value="NEEC02" <?php if($status =="NEEC02") echo "selected";?> >NEEC02 - Rifiutata</option>
	<option value="NO" <?php if($status =="NO") echo "selected";?> >NO - Senza esiti oltre RC</option>
</select>
</td>
<td class="FacetFieldCaptionTD">
<input type="submit" name="search" colspan="11" value="Cerca" tabindex="1" />
</td>
<td colspan="1" class="FacetFieldCaptionTD">
<input type="submit" name="all" value="Mostra tutti" />
</td>
</tr>
</form>
<?php
$headers = array  (''=>'',
                   $script_transl['id']=>'id',
                   $script_transl['filename_ori']=>'',
                   $script_transl['numfat']=>'',
                   $script_transl['codice']=>'',
                   $script_transl['ragso1']=>'',
                   $script_transl['exec_date']=>'',
                   $script_transl['received_date']=>'',
                   $script_transl['delivery_date']=>'',
                   $script_transl['filename_son']=>'',
                   $script_transl['id_SDI']=>'',
                   $script_transl['filename_ret']=>'',
                   $script_transl['mail_id']=>'',
                   $script_transl['flux_status']=>'',
                   $script_transl['progr_ret']=>'',
                   $script_transl['flux_descri']=>''
            );
$linkHeaders = new linkHeaders($headers);

if ( $mostra_intesta == 1 and $mostra_intesta_riga == 0 ) {
    $linkHeaders -> output();
}

//$orderby = $gTables['fae_flux'].'.filename_zip_package DESC, '.$gTables['fae_flux'].'.filename_ori DESC,'. $gTables['fae_flux'].'.progr_ret';
$orderby = $gTables['fae_flux'] . '.id DESC';

$result = gaz_dbi_dyn_query ($gTables['fae_flux'].".*,".$gTables['tesdoc'].".tipdoc,".$gTables['tesdoc'].".datfat,".$gTables['tesdoc'].".protoc,".$gTables['tesdoc'].".seziva,".$gTables['tesdoc'].".numfat,".$gTables['clfoco'].".codice,".$gTables['clfoco'].".descri,".$gTables['anagra'].".fe_cod_univoco", $gTables['fae_flux'].' LEFT JOIN '.$gTables['tesdoc'].' ON '.$gTables['fae_flux'].'.id_tes_ref = '.$gTables['tesdoc'].'.id_tes LEFT JOIN '.$gTables['clfoco'].' ON '.$gTables['tesdoc'].'.clfoco = '.$gTables['clfoco'].'.codice LEFT JOIN '.$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id', $where, $orderby, $limit, $passo);

$ctrl_zip = 'START_CHECK_VALUE';
while ($r = gaz_dbi_fetch_array($result)) {

	if (strlen($r['filename_zip_package']) > 16) {// uso un report diverso in caso di impacchettamento in files zip
		if ($ctrl_zip!=$r['filename_zip_package']) {
			echo '<tr><td class="bg-info" colspan="11">Il file pacchetto di fatture <span class="bg-warning">'.$r['filename_zip_package'].'</span> è stato generato per contenere le seguenti fatture elettroniche:</td>';
			echo '<td colspan="2" align="center"><a '.$yes_mail.'class="btn btn-xs btn-info btn-email" onclick="confirMail(this);return false;" id="fn' . substr($r["filename_zip_package"],0,-4) . '" url="send_fae_package.php?fn='.$r['filename_zip_package'].'" href="#" title="Mailto: ' . $dest_fae_zip_package['val'] . '"
				mail="' . $dest_fae_zip_package['val'] . '" namedoc="'.$r["filename_zip_package"].'">Invia <i class="glyphicon glyphicon-envelope"></i></a>';
			if ($r['id_SDI'] == 0 && $r['flux_status'] != "@" && $r['flux_status'] != "@@") {
				echo '<td align="center"><a '.$yes_send.'class="btn btn-xs btn-info btn-email" onclick="confirSend(this);return false;" id="zn' . substr($r["filename_zip_package"],0,-4) . '" url="electronic_invoice.php?zn='.$r['filename_zip_package'].'&sdiflux=' . $send_fae_zip_package['val'] . '&invia" href="#" title="POST call: ' . $send_fae_zip_package['val'] . ' library"
					library="' . $send_fae_zip_package['val'] . '" namedoc="'.$r["filename_zip_package"].'">Invia con<br><b>'.$send_fae_zip_package['val'].'</b><i class="glyphicon glyphicon-upload"></i></a>';
			} else {
				echo '<td></td>';
			}
			echo '<td align="center"><a class="btn btn-xs btn-success" title="Download del pacchetto di fatture elettroniche" href="download_zip_package.php?fn='.$r['filename_zip_package'].'">Download <i class="glyphicon glyphicon-download"></i></a></td>';
			if ($r['id_SDI'] == 0) {
				if ($ctrl_zip == 'START_CHECK_VALUE') {
					$class='btn btn-xs  btn-elimina';
					$title='Cancella il pacchetto di fatture elettroniche';
					if ($r['flux_status'] != "@@" && $r['flux_status'] != "@"){ // l'ultimo zip può essere eliminato solo se non è stato inviato
            echo '<td colspan="2"><a class="'.$class.'" title="'.$title.'" href="delete_zip_package.php?fn='.$r['filename_zip_package'].'">'.$script_transl['delete'].'<i class="glyphicon glyphicon-trash"></i></a></td>';
					}
				} else {
					echo '<td colspan="2"></td>';
				}
			} else {
				echo '<td colspan="2"></td>';
			}
			echo '</tr>';
		   $linkHeaders -> output();
		}
	} else if ($ctrl_zip!=$r['filename_zip_package']) {
		echo '<tr><td class="bg-info" colspan="16">Fatture elettroniche senza pacchetto:</td></tr>';
		$linkHeaders -> output();
	}

	$ctrl_zip = $r['filename_zip_package'];

	if ($senza_esito == 1) {
		$where1 = " filename_ori = '" . $r['filename_ori'] . ".p7m' and flux_status <> 'RC' ";
		$risultati = gaz_dbi_dyn_query ("*", $gTables['fae_flux'], $where1, $orderby, $limit, $passo);
		$rr = gaz_dbi_fetch_array($risultati);

		if ($rr == false) {
			//echo "<tr><td>-------- FALSO " . $where1 . "</td></tr>";
		} else {
			//echo "<tr><td>-------- VERO "  . $where1 . " " . $rr['filename_ori'] . "</td></tr>";
			continue;
		}
	}

    $class = '';
    $class1 = '';
    $class2 = '';
    if ($r['flux_status'] == 'RC') {
        $class = 'FacetDataTD';
		if (strlen($r['fe_cod_univoco']) == 6) {
			$class2 = 'FacetDataTDevidenziaOK';
		}
    } elseif ($r['flux_status'] == 'NS') {
        $class = 'FacetDataTD';
        $class2 = 'FacetDataTDevidenziaKO';
    } elseif ($r['flux_status'] == 'DT') {
        $class = 'FacetDataTDred';
    } elseif ($r['flux_status'] == 'MC') {
        $class = 'FacetDataTD';
        $class2 = 'FacetDataTDred';
    } elseif ($r['flux_status'] == '@' || $r['flux_status'] == '@@' || $r['flux_status'] == 'IN') {
        $class = 'FacetDataTD';
        $class1 = '';
    } elseif ($r['flux_status'] == '##' || $r['flux_status'] == '#' || $r['flux_status'] == 'DI') {
        $class = 'FacetDataTD';
        $class1 = '';
    }

    if ($r['progr_ret'] == '000' && $mostra_intesta_riga == 1) {
        $class = 'FacetDataTD';
        $class1 = '';
    } elseif ($r['progr_ret'] == '000' && $mostra_intesta_riga == 0) {
        $class = 'FacetDataTD';
        $class1 = '';
    }

    if ($r['flux_status'] == 'NE' || $r['flux_status'] == 'NEEC01') {
        //Fattura accettata
        $class = 'FacetDataTD';
        $class2 = 'FacetDataTDevidenziaCL';
    } else if (strlen($r['flux_status']) > 2 && strpos($r['flux_status'], 'NE') !== FALSE) {
        //Fattura rifiutata
        $class = 'FacetDataTD';
        $class2 = 'FacetDataTDevidenziaBL';
    }

    echo "<tr class=\"$class1 $class2\">";
    echo "<td>&nbsp;</td>";
    echo "<td class=\"$class\" align=\"center\">".$r['id']."</td>";
    echo "<td class=\"$class paper\" align=\"left\">".$r['filename_ori']."</td>";
    echo "<td class=\"$class\" align=\"center\">".$r['numfat']."</td>";
    echo "<td class=\"$class\" align=\"center\">".$r['codice']."</td>";
    echo "<td class=\"$class\" align=\"center\">".$r['descri']."</td>";
    echo "<td style=\"white-space:nowrap;\" class=\"$class\" align=\"center\">".gaz_format_date($r['exec_date'])."</td>";
    echo "<td style=\"white-space:nowrap;\" class=\"$class\" align=\"center\">".gaz_format_date($r['received_date'])."</td>";
    echo "<td style=\"white-space:nowrap;\" class=\"$class\" align=\"center\">".gaz_format_date($r['delivery_date'])."</td>";
    echo "<td class=\"$class\" align=\"center\">".$r['filename_son']."</td>";
    echo "<td class=\"$class\" align=\"center\">".$r['id_SDI']."</td>";
    echo "<td class=\"$class\" align=\"center\">".$r['filename_ret']."</td>";
    echo "<td class=\"$class\" align=\"center\">".$r['mail_id']."</td>";

    //aggiungere una icona invece del cancelletto
    if ($r['flux_status'] == "##") {
        echo "<td class=\"$class  $class2\" align=\"center\" title=\"".$script_transl['flux_status_value'][$r['flux_status']]."\">". "<form method=\"POST\"  enctype=\"multipart/form-data\"><input type=\"file\" accept=\".xml,.p7m\" name=\"p7mfile_".$r['id']."\" />" . "<input name=\"Submit_file\" type=\"submit\" class=\"btn btn-warning\" value=\"Carica fattura firmata\" /></form>" . "</td>";
    } elseif ($r['flux_status'] == "#" || $r['flux_status'] == "DI") {
        $modulo_fae_report="report_fae_sdi.php?id_record=".$r['id']."&amp;id_tes_ref=".$r['id_tes_ref']."&amp;file_name=".$r['filename_ori'];
        echo "<td class=\"$class  $class2\" align=\"center\" title=\"".$script_transl['flux_status_value'][$r['flux_status']]."\">". "<a href=\"".$modulo_fae_report."\">#</a>" . "</td>";
    } elseif ($r['flux_status'] == "@") {
        echo "<td class=\"$class  $class2\" align=\"center\" target=\"_blank\" title=\"".$script_transl['flux_status_value'][$r['flux_status']]."\">". "<a href=\"#\">@</a>" . "</td>";
    } elseif ($r['flux_status'] == "@@") {
        echo "<td class=\"$class  $class2\" align=\"center\" target=\"_blank\">". "<a href=\"#\">@@</a>" . "</td>";
    } else {
        echo "<td class=\"$class  $class2\" align=\"center\" title=\"".$script_transl['flux_status_value'][$r['flux_status']]."\">".$r['flux_status']."</td>";
    }
    echo "<td class=\"$class\" align=\"center\">".$r['progr_ret']."</td>";

    if (strlen($r['flux_descri']) < 5) {
        echo "<td class=\"$class\" >".$r['flux_descri']."</td>";
    } else {
        echo "<td class=\"$class\" ></td>";
        echo "</tr>";
        echo "<tr><td colspan =\"5\"><td colspan =\"10\" class=\"$class\" style=\"text-align:left;\" >".$r['flux_descri']."</td>";
        echo "</tr><tr><td colspan=\"15\">&nbsp;</td></tr>";
    }
    echo "</tr>";
}

echo "</table>\n";
echo "</div>";
echo "</form>\n";

?>
<?php
require("../../library/include/footer.php");
?>

