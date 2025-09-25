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
require("../../modules/magazz/lib.function.php");
$admin_aziend = checkAdmin();
$gForm = new acquisForm();
$msg = array('err' => array(), 'war' => array());
$tipdoc_conv=array('TD01'=>'AFA','TD02'=>'AFA','TD03'=>'AFA','TD04'=>'AFC','TD05'=>'AFD','TD06'=>'AFA','TD08','TD16'=>'AFA','TD17'=>'AFA','TD18'=>'AFA','TD19'=>'AFA','TD24'=>'AFT','TD25'=>'AFT','TD26'=>'AFA','TD27'=>'AFA');

// ATTENZIONE TD01 deve indicizzare per AFT nel caso in cui ci sono DDT di riferimento all'interno del tracciato, quindi si dovrà gestire questa accezione. Comunque con la prossima versione della fattura elettronica (2.0) saranno da implementare anche altri tipi di doc

$magazz = new magazzForm;
$docOperat = $magazz->getOperators();
$toDo = 'upload';
$f_ex=false; // visualizza file

$send_fae_zip_package = gaz_dbi_get_row($gTables['company_config'], 'var', 'send_fae_zip_package');

function tryBase64Decode($s)
{
	// Check if there are valid base64 characters
	if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s)) {
		// Decode the string in strict mode and check the results
		try {
			$decoded = base64_decode($s, true);
			if ($decoded !== false) {
				// Encode the string again
				if(base64_encode($decoded) == $s) {
                	return $decoded;
                } else {
					error_log('Charset non gestito in tryBase64Decode ' . print_r($decoded, true), 0);
                	return $decoded;
                }
			}
		} catch (Exception $ex) {
			//$ex->getMessage();
		}
	}

	return $s;
}


function der2smime($file)
{
$to = <<<TXT
MIME-Version: 1.0
Content-Disposition: attachment; filename="smime.p7m"
Content-Type: application/x-pkcs7-mime; smime-type=signed-data; name="smime.p7m"
Content-Transfer-Encoding: base64
\n
TXT;
	$from = file_get_contents($file);
	$to.= chunk_split(base64_encode($from));
	return file_put_contents($file,$to);
}

function extractDER($file)
{
	$tmp = tempnam(DATA_DIR . 'files/tmp/', 'ricder');
	$txt = tempnam(DATA_DIR . 'files/tmp/', 'rictxt');
	$flags = PKCS7_BINARY|PKCS7_NOVERIFY|PKCS7_NOSIGS;
	openssl_pkcs7_verify($file, $flags, $tmp); // estrazione certificato
	@openssl_pkcs7_verify($file, $flags, '/dev/null', array(), $tmp, $txt); // estrazione contenuto - questo potrebbe fallire se il file non è ASN.1 clean
	unlink($tmp);
	$out = file_get_contents($txt);
	unlink($txt);
	return $out;
}

function removeSignature($s)
{
	$start_xml = strpos($s, '<?xml ');
	if ($start_xml !== FALSE) {
		$s = substr($s, $start_xml);
	} else {
		$start_xml = strpos($s, '<?xml-stylesheet ');
		if ($start_xml !== FALSE) {
			$s = substr($s, $start_xml);
		}
	}
	// elimino le sequenze di caratteri aggiunti dalla firma (ancora da testare approfonditamente)
	$s = preg_replace('/[\x{0004}]{1}[\x{0082}]{1}[\x{0001}-\x{001F}]{1}[\s\S]{1}/i', '', $s);
	$s = preg_replace('/[\x{0004}]{1}[\x{0082}]{1}[\s\S]{1}[\x{0000}]{1}/i', '', $s);
	$s = preg_replace('/[\x{0004}]{1}[\x{0081}]{1}[\s\S]{1}/i', '', $s);
	$s = preg_replace('/[\x{0004}]{1}[\s\S]{1}/i', '', $s);
	$s = preg_replace('/[\x{0003}]{1}[\s\S]{1}/i', '', $s);
	//$s = preg_replace('/[\x{0004}]{1}[A-Za-z]{1}/i', '', $s); // per eliminare tag finale
	preg_match_all('/<\/.+?>/', $s, $matches, PREG_OFFSET_CAPTURE);
	$lastMatch = end($matches[0]);
	// trovo l'ultimo carattere del tag di chiusura per eliminare la coda
	$f_end = $lastMatch[1]+strlen($lastMatch[0]);
	$s = substr($s, 0, $f_end);
	return $s;
}

function recoverCorruptedXML($s)
{
	libxml_use_internal_errors(true);
	$xml = @simplexml_load_string($s);
	$errors = libxml_get_errors();
	if (!empty($errors) && is_array($errors) && count($errors)>0) {
		$lines = explode("\n", $s);
		foreach ($errors as $error) {
			if (strpos($error->message, 'Opening and ending tag mismatch')!==false) {
				$tag   = trim(preg_replace('/Opening and ending tag mismatch: (.*) line.*/', '$1', $error->message));
				$line  = $error->line-1;
				$lines[$line] = substr($lines[$line], 0, strpos($lines[$line], '</')).'</'.$tag.'>';
			}
		}
		libxml_clear_errors();
		return implode("\n", $lines);
	} else {
		return $s;
	}
}

function getLastProtocol($type, $year, $sezione) {
	/* 	questa funzione trova l'ultimo numero di protocollo
	*	controllando sia l'archivio documenti che il registro IVA acquisti
	*/
	global $gTables;
    $rs_ultimo_tesdoc = gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datreg) = ".$year." AND tipdoc LIKE '" . substr($type, 0, 2) . "_' AND seziva = ".$sezione, "protoc DESC", 0, 1);
    $ultimo_tesdoc = gaz_dbi_fetch_array($rs_ultimo_tesdoc);
    $rs_ultimo_tesmov = gaz_dbi_dyn_query("*", $gTables['tesmov'], "YEAR(datreg) = ".$year." AND regiva = 6 AND seziva = ".$sezione, "protoc DESC", 0, 1);
    $ultimo_tesmov = gaz_dbi_fetch_array($rs_ultimo_tesmov);
    $lastProtocol = 0;
    $lastDatreg = date("Y-m-d");
    if ($ultimo_tesdoc) {
        $lastProtocol = $ultimo_tesdoc['protoc'];
        $lastDatreg = $ultimo_tesdoc['datreg'];
    }
    if ($ultimo_tesmov) {
        if ($ultimo_tesmov['protoc'] > $lastProtocol) {
            $lastProtocol = $ultimo_tesmov['protoc'];
            $lastDatreg = $ultimo_tesmov['datreg'];
        }
    }
    return array('last_protoc'=>$lastProtocol + 1,'last_datreg'=>$lastDatreg);
}

function encondeFornitorePrefix($clfoco,$b=36) {
    $num = intval(substr($clfoco,-6));
	/* con questa funzione ricavo un prefisso di codice articolo che dipende dal codice fornitore */
    $base = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $r = $num % $b;
    $res = $base[$r];
    $q = floor($num / $b);
    while ($q) {
        $r = $q % $b;
        $q = floor($q / $b);
        $res = $base[$r] . $res;
    }
    return $res;

}

function existDdT($numddt,$dataddt,$clfoco) {
	global $gTables;
	/* Questa funzione serve per controllare se è già stato registrato in magazzino il rigo dell'eventuale DdT contenuto nella
		fattura che stiamo acquisendo mi baso su fornitore, numero, data e, se lo passo, il codice articolo, quando passo $codart
		faccio una ricerca puntuale sull'articolo specifico
	*/
    $result=gaz_dbi_dyn_query("*", $gTables['tesdoc']. " LEFT JOIN " . $gTables['rigdoc'] . " ON " . $gTables['tesdoc'] . ".id_tes = " . $gTables['rigdoc'] . ".id_tes", "(tipdoc='ADT' OR tipdoc='RDL') AND clfoco = ".$clfoco." AND datemi='".$dataddt."' AND numdoc='".$numddt."'", "id_rig ASC");
    $acc=[];
    $l=1;
    while($r=gaz_dbi_fetch_array($result)){
      if ($l<=1 ) {
        $acc=$r;
      }
      $acc['rigdoc'][$l] = ['codart'=>$r['codart'],'quanti'=>$r['quanti'],'id_rig'=>$r['id_rig'],'id_mag'=>$r['id_mag'],'id_order'=>$r['id_order']];
      $acc['rig_codart'][$r['codart']] = ['ln'=>$l,'quanti'=>$r['quanti'],'id_rig'=>$r['id_rig'],'id_mag'=>$r['id_mag'],'id_order'=>$r['id_order']];
      $l++;
    }
    return $acc;
}


function concileDdT($name,$sel,$acc_DataDDT) {
  $nurow=explode('_',$name);
  $acc = '<select name="'.$name.'" id="'.$name.'" class="bg-warning text-danger" onchange="this.form.hidden_req.value=\'concileDdT\'; this.form.submit();">
          <option value="" > ------------------------------------- </option>';
  foreach ($acc_DataDDT as $val) {
      $selected = ($sel == $val['Numero']) ? ' selected ' : '';
      $selected_tolast = ($sel == $val['Numero'].'_tolast') ? ' selected ' : '';
      $acc .= '<option class="bg-default text-default" value="'.$val['Numero'].'" '.$selected.'>DdT n.'.$val['Numero'].' del '.gaz_format_date($val['Data']).' (solo su rigo '.($nurow[1]+1).') </option>
               <option class="bg-info text-default" value="'.$val['Numero'].'_tolast" '.$selected_tolast.'>DdT n.'.$val['Numero'].' del '.gaz_format_date($val['Data']).' (su rigo '.($nurow[1]+1).' e successivi) </option>';
  }
  $acc .= "</select>\n";
  return $acc;
}

$sync_mods=[];
$sync_mods=explode(",",$admin_aziend['gazSynchro']);

if (!isset($_POST['fattura_elettronica_original_name'])) { // primo accesso nessun upload
	$form['fattura_elettronica_original_name'] = '';
	$form['date_ini_D'] = '01';
	$form['date_ini_M'] = date('m', strtotime('last month'));
	$form['date_ini_Y'] = date('Y', strtotime('last month'));
	$form['date_fin_D'] = date('d');
	$form['date_fin_M'] = date('m');
	$form['date_fin_Y'] = date('Y');
	$form['curr_doc'] = 0;
	$form['id_doc'] = 0;
	$form['incrbenamm'] = 0;
	if (in_array($send_fae_zip_package['val'],$sync_mods)){
		$res_faesync=gaz_dbi_dyn_query("*", $gTables['files'], "item_ref='faesync' AND status = 0", "table_name_ref", 0);
	}
} else { // accessi successivi
	$form['fattura_elettronica_original_name'] = filter_var($_POST['fattura_elettronica_original_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$form['curr_doc'] = intval($_POST['curr_doc']);
	$form['id_doc'] = intval($_POST['id_doc']);
	$form['incrbenamm'] = intval($_POST['incrbenamm']);
	$form['date_ini_D'] = '01';
	$form['date_ini_M'] = date('m');
	$form['date_ini_Y'] = date('Y');
	$form['date_fin_D'] = date('d');
	$form['date_fin_M'] = date('m');
	$form['date_fin_Y'] = date('Y');

	if (!isset($_POST['datreg'])){
		$form['datreg'] = date("d/m/Y");
		$form['seziva'] = 1;
		// adesso metto uno ma dovrò proporre il magazzino di riferimento dell'utente
		$magmodule = gaz_dbi_get_row($gTables['module'], "name",'magazz');
		$magadmin_module = gaz_dbi_get_row($gTables['admin_module'], "moduleid",$magmodule['id']," AND adminid='{$admin_aziend['user_name']}' AND company_id=" . $admin_aziend['company_id']);
		$magcustom_field=isset($magadmin_module['custom_field'])?json_decode($magadmin_module['custom_field']):false;
		$form["in_id_warehouse"] = (isset($magcustom_field->user_id_warehouse))?$magcustom_field->user_id_warehouse:0;
	} else {
		$form['datreg'] = substr($_POST['datreg'],0,10);
		$form['seziva'] = intval($_POST['seziva']);
		$form['in_id_warehouse'] = intval($_POST['in_id_warehouse']);
	}

	if (isset($_POST['Submit_file']) || isset($_POST['fae_from_sync'])) { // conferma invio upload file
    if (isset($_POST['fae_from_sync']) && strtotime($_POST['table_name_ref'.intval($_POST['fae_from_sync'])])>0){// se è una data
      $form['datreg']=gaz_format_date($_POST['table_name_ref'.intval($_POST['fae_from_sync'])], false, false);
      $_POST['datreg']=$form['datreg'];
    }
    if (isset($_POST['fae_from_sync'])){
			$_POST['fae_original_name']=$_POST['fae_original_name'.intval($_POST['fae_from_sync'])];
      $form['id_doc'] = intval($_POST['fae_from_sync']);
		}

    if (!empty($_FILES['userfile']['name'])) {
      if ( $_FILES['userfile']['type'] == "application/x-zip-compressed") {
        $filepath= DATA_DIR . 'files/' . $admin_aziend['codice'] . '/tmp/'.$send_fae_zip_package['val'] .'_' . $_FILES['userfile']['name'];
        if (move_uploaded_file($_FILES['userfile']['tmp_name'],$filepath)) { // nessun errore
				} else { // no upload
					$msg['err'][] = 'no_upload';
				}
        $zip = new ZipArchive;
        if ($zip->open($filepath) === TRUE) {
          for($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $fileinfo = pathinfo($filename);
            $zip->extractTo(DATA_DIR .'files/' .$admin_aziend['codice'].'/tmp/',$filename);
   					$file_id=gaz_dbi_table_insert('files', ["table_name_ref"=>date('Y-m-d H:i:s'), "item_ref"=>"faesync","extension"=>$fileinfo['extension'],"title"=>$filename]);
            copy(DATA_DIR . 'files/' . $admin_aziend['codice'] . '/tmp/'.$filename, DATA_DIR . 'files/' . $admin_aziend['codice'] . '/doc/'.$file_id.'.'.$fileinfo['extension']);
          }
          $zip->close();
          header("Location: acquire_invoice.php");
          exit;
        } else {
          $msg['err'][] = 'filmim';
        }
			} else if (!( $_FILES['userfile']['type'] == "application/pkcs7-mime" || $_FILES['userfile']['type'] == "application/pkcs7" || $_FILES['userfile']['type'] == "text/xml")) {
				$msg['err'][] = 'filmim';
			} else {
        if (move_uploaded_file($_FILES['userfile']['tmp_name'], DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $_FILES['userfile']['name'])) { // nessun errore
					$form['fattura_elettronica_original_name'] = $_FILES['userfile']['name'];
				} else { // no upload
					$msg['err'][] = 'no_upload';
				}
			}
		} else if (!empty($_POST['selected_SdI'])) {
			require('../../library/' . $send_fae_zip_package['val'] . '/SendFaE.php');
			$FattF = DownloadFattF(array($admin_aziend['country'].$admin_aziend['codfis'] => array('id_SdI' => $_POST['selected_SdI'])));
			if (!empty($FattF) && is_array($FattF) && file_put_contents( DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . key($FattF), base64_decode($FattF[key($FattF)])) !== FALSE) { // nessun errore
				$form['fattura_elettronica_original_name'] = key($FattF);
			} else { // no upload
				$msg['err'][] = 'no_upload';
			}
		} else {
      // è una fattura elettronica fae proveniente da un sistema personalizzato di sincronizzazione es: pecSdI, pecARUBA, ecc
      // per consentire l'acquisizione di fatture di acquisto arrivate tramite un modulo scritto per questo scopo esso deve
      // scrivere il file nella directory files/1/doc con estensione originale e nome uguale al contenuto di "id_doc"
      // della tabella gaz_001files, tabella che dovrà contenere anche il nome originale del file in "title" e "item_ref" = 'faesync'
      // la referenza 'faesync', infatti viene utilizzata per essere richiamate al primo accesso per proporle all'utente
      // vedi $res_faesync qualche rigo sopra
			copy(DATA_DIR . 'files/' . $admin_aziend['codice'] . '/doc/'.$_POST['fae_from_sync'].".". pathinfo($_POST['fae_original_name'], PATHINFO_EXTENSION) , DATA_DIR . 'files/' . $admin_aziend['codice'] . '/'.$_POST['fae_original_name']);
			$form['fattura_elettronica_original_name']=$_POST['fae_original_name'];
			$_POST['Submit_file']="Acquisisci";
		}
	} else if (isset($_POST['Submit_form'])) { // ho  confermato l'inserimento

		$form['pagame'] = intval($_POST['pagame']);
		$form['incrbenamm'] = intval($_POST['incrbenamm']);
		$form['new_acconcile'] = intval($_POST['new_acconcile']);
        if ($form['pagame'] <= 0 ) {  // ma non ho selezionato il pagamento
			$msg['err'][] = 'no_pagame';
		}
		// faccio i controlli sui righi
		foreach($_POST as $kr=>$vr){
			if (substr($kr,0,7)=='codvat_' && $vr<=0 && $vr !='isdescri') {
				$msg['err'][] = 'no_codvat';
			}
			if (substr($kr,0,7)=='codric_' && $vr<=0 && $vr !='isdescri') {
				$msg['err'][] = 'no_codric';
			}
		}
	} else if (isset($_POST['IncreaseBenamm'])){ // ho chiesto l'incremento di valore di un bene ammortizzabile
		$form['incrbenamm'] = $admin_aziend['mas_fixed_assets'];
  } else if (isset($_POST['Download'])) { // faccio il download dell'allegato
		$name = filter_var($_POST['Download'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment;  filename="'.$name.'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize( DATA_DIR . 'files/tmp/' . $name ));
		readfile( DATA_DIR . 'files/tmp/' . $name );
		exit;
	} else if (isset($_POST['Submit_list'])) { // ho richiesto l'elenco delle fatture passive
		$form['date_ini_D'] = str_pad($_POST['date_ini_D'], 2, '0', STR_PAD_LEFT);
		$form['date_ini_M'] = str_pad($_POST['date_ini_M'], 2, '0', STR_PAD_LEFT);
		$form['date_ini_Y'] = $_POST['date_ini_Y'];
		$form['date_fin_D'] = str_pad($_POST['date_fin_D'], 2, '0', STR_PAD_LEFT);
		$form['date_fin_M'] = str_pad($_POST['date_fin_M'], 2, '0', STR_PAD_LEFT);
		$form['date_fin_Y'] = $_POST['date_fin_Y'];
		$FattF = array();
		$where1 = " tipdoc LIKE 'A%' AND fattura_elettronica_original_name!='' AND datreg BETWEEN '" . $form['date_ini_Y'] . '-' . $form['date_ini_M'] . '-' . $form['date_ini_D'] . "' AND '" . $form['date_fin_Y'] . '-' . $form['date_fin_M'] . '-' . $form['date_fin_D'] . "'";
		$risultati = gaz_dbi_dyn_query("*", $gTables['tesdoc'], $where1);
		if ($risultati) {
			while ($r = gaz_dbi_fetch_array($risultati)) {
				$FattF[] = $r['fattura_elettronica_original_name'];
			}
		}
		require('../../library/' . $send_fae_zip_package['val'] . '/SendFaE.php');
		$AltreFattF = ReceiveFattF(array($admin_aziend['country'].$admin_aziend['codfis'] => array('fattf' => $FattF, 'ini_date' => $form['date_ini_Y'] . '-' . $form['date_ini_M'] . '-' . $form['date_ini_D'], 'fin_date' => $form['date_fin_Y'] . '-' . $form['date_fin_M'] . '-' . $form['date_fin_D'])));
	}
	$tesdoc = gaz_dbi_get_row($gTables['tesdoc'], 'BINARY fattura_elettronica_original_name', $form["fattura_elettronica_original_name"]);
	if (!empty($form['fattura_elettronica_original_name'])) { // c'è anche sul database, è una modifica
    if ($tesdoc){
      $toDo = 'update';
      $form['datreg'] = gaz_format_date($tesdoc['datreg'], false, false);
      $form['seziva'] = $tesdoc['seziva'];
      $msg['war'][] = 'file_exists'; //potrebbe non essere un errore, per esempio quando si importa lo stesso file contenente più fatture
    }else{
      $toDo = 'insert';
    }

		// INIZIO acquisizione e pulizia file xml o p7m
		$file_name = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $form['fattura_elettronica_original_name'];
		if (!isset($_POST['datreg'])&& !$tesdoc){
			$form['datreg'] = date("d/m/Y",filemtime($file_name));
		}
		$p7mContent = @file_get_contents($file_name);
		$p7mContent = tryBase64Decode($p7mContent);

		$tmpfatt = tempnam(DATA_DIR . 'files/tmp/', 'ricfat');
		file_put_contents($tmpfatt, $p7mContent);

		if (FALSE !== der2smime($tmpfatt)) {
			$cert = tempnam(DATA_DIR . 'files/tmp/', 'ricpem');
			$retn = openssl_pkcs7_verify($tmpfatt, PKCS7_NOVERIFY, $cert);
			unlink($cert);
			if (!$retn) {
				//unlink($tmpfatt);
				//echo "Error verifying PKCS#7 signature in {$file_name}";
				//error_log('errore in Verifica firma PKCS#7', 0);
				//echo 'errore in Verifica firma PKCS#7';
				//return false;
			}

			$isFatturaElettronicaSemplificata = false;
			$fatt = extractDER($tmpfatt);
			if (empty($fatt)) {
				$test = @base64_decode(file_get_contents($tmpfatt));
				// Salto lo header (INDISPENSABILE perché la regexp funzioni sempre)
				if (strpos($test, 'FatturaElettronicaSemplificata') !== FALSE) {
					$isFatturaElettronicaSemplificata = true;
					if (preg_match('#(<[^>]*FatturaElettronicaSemplificata.*</[^>]*FatturaElettronicaSemplificata>)#', substr($test, 54), $gregs)) {
						$fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
					}
				} else {
					if (preg_match('#(<[^>]*FatturaElettronica.*</[^>]*FatturaElettronica>)#', substr($test, 54), $gregs)) {
						$fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
					}
				}
			} else {
				if (strpos($p7mContent, 'FatturaElettronicaSemplificata') !== FALSE) {
					$isFatturaElettronicaSemplificata = true;
				}
			}
		}
		unlink($tmpfatt);


		if (!empty($fatt)) {
			$invoiceContent = $fatt;
		} else {
			$invoiceContent = removeSignature($p7mContent);
		}


		$xml = new DOMDocument;
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;
		if (FALSE === @$xml->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()))) {
			// elimino le sequenze di caratteri non stampabili aggiunti dalla firma (da testare approfonditamente)
			$invoiceContent = preg_replace('/[[:^print:]]/', "", $invoiceContent);
			if (FALSE === @$xml->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()))) {
				$xml->loadXML(recoverCorruptedXML($invoiceContent));
			}
		}
		$xpath = new DOMXpath($xml);
		$f_ex=true;
	} else {
		$toDo = 'upload';
	}

	// definisco l'array dei righi
	$form['rows'] = [];

	$anagra_with_same_pi = false; // sarà true se è una anagrafica esistente ma non è un fornitore sul piano dei conti


	if ($f_ex) { // non ho errori di file,  faccio altri controlli sul contenuto del file

	if (empty($form['curr_doc'])) {
		$docs = $xml->getElementsByTagName('FatturaElettronicaBody');
		if ($docs->length < 1) { // se non esiste il nodo <FatturaElettronicaBody>
			$msg['err'][] = 'invalid_fae';
			$f_ex=false; // non è visualizzabile
		}
		if (count($docs) == 1) {
			$form['curr_doc'] = 1;
		}
	}

	if (!empty($form['curr_doc'])) {

		$ndoc = 0;
		$docs = $xml->getElementsByTagName('FatturaElettronicaBody');
		foreach ($docs as $doc) {
			$ndoc++;
			if ($ndoc == $form['curr_doc']) break;
		}

		// INIZIO CONTROLLI CORRETTEZZA FILE
		$val_err = libxml_get_errors(); // se l'xml è valido restituisce 1
		libxml_clear_errors();
		if (empty($val_err)){
			// INIZIO CONTROLLO NUMERO DATA, ovvero se nonostante il nome del file sia diverso il suo contenuto è già stato importato e già c'è uno con lo stesso tipo_documento-numero_documento-anno-fornitore
			$tipdoc=$tipdoc_conv[$xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/TipoDocumento")->item(0)->nodeValue];
      if (substr($tipdoc,0,2)=='XF') {
        // $msg['err'][] = 'reverse_charge'; // PROMEMORIA: commentato e cambiati TD16,TD17,TD18,TD19 di $tipdoc_conv DA XFA in AFA perché l'errore bloccava l'acquisizione (es. fatture Amazon)
      }
			$datdoc=$xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/Data")->item(0)->nodeValue;
			$numdoc=$xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0)->nodeValue;
			if ($isFatturaElettronicaSemplificata) {
				$codiva=$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
				if ($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/CodiceFiscale")->length>=1){
					$codfis=$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/CodiceFiscale")->item(0)->nodeValue;
				} else {
					$codfis=$codiva;
				}
			} else {
				$codiva=$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
				if ($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->length>=1){
					$codfis=$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue;
				} else {
					$codfis=$codiva;
				}
			}
			$nomefornitore=($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Denominazione")->length>=1)?$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Denominazione")->item(0)->nodeValue:$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Cognome")->item(0)->nodeValue.' '.$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Nome")->item(0)->nodeValue;
			$r_invoice=gaz_dbi_dyn_query("*", $gTables['tesdoc']. " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesdoc'] . ".clfoco = " . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id", "tipdoc LIKE '".substr($tipdoc,0,2)."_' AND (pariva = '".$codiva."' OR codfis = '".$codfis."') AND datfat='".$datdoc."' AND numfat='".addslashes($numdoc)."'", "id_tes", 0, 1);
			$exist_invoice=gaz_dbi_fetch_array($r_invoice);
      //var_dump($tipdoc,$codiva,$datdoc,$numdoc);
			if ($exist_invoice) { // esiste un file che pur avendo un nome diverso è già stato acquisito ed ha lo stesso numero e data
				$msg['err'][] = 'same_content';
				$f_ex=false; // non è visualizzabile
			}
			// FINE CONTROLLO NUMERO DATA
			if ($xml->getElementsByTagName("FatturaElettronicaHeader")->length < 1) { // non esiste il nodo <FatturaElettronicaHeader>
				$msg['err'][] = 'invalid_fae';
				$f_ex=false; // non è visualizzabile
			} else if ( ( !$isFatturaElettronicaSemplificata && @$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue <> $admin_aziend['pariva'] && @$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue <> $admin_aziend['codfis'] ) ||
						 ( $isFatturaElettronicaSemplificata && @$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/IdentificativiFiscali/IdFiscaleIVA/IdCodice")->item(0)->nodeValue <> $admin_aziend['pariva'] && @$xpath->query("//FatturaElettronicaHeader/CessionarioCommittente/IdentificativiFiscali/CodiceFiscale")->item(0)->nodeValue <> $admin_aziend['codfis'] ) ) { // ne partita IVA ne codice fiscale coincidono con quella della azienda che sta acquisendo la fattura
				$msg['err'][] = 'not_mine';
				$f_ex=false; // non la visualizzo perché non è una mia fattura
			} else {
				// controllo se ho il fornitore in archivio
				$form['partner_cost']=$admin_aziend['impacq'];
				$form['partner_vat']=$admin_aziend['preeminent_vat'];
				$form['pariva'] = $codiva;
				$form['codfis'] = $codfis;
				$anagrafica = new Anagrafica();
        $partner_with_same_pi = $anagrafica->queryPartners('*', "codice BETWEEN " . $admin_aziend['masfor'] . "000000 AND " . $admin_aziend['masfor'] . "999999 AND pariva = '" . $form['pariva'] . "'", "CASE WHEN codfis LIKE '" . $form['codfis'] . "' THEN 1 ELSE 0 END DESC, codice DESC");
        if ($partner_with_same_pi) { // ho già il fornitore sul piano dei conti
          $form['clfoco'] = $partner_with_same_pi[0]['codice'];
          if ($partner_with_same_pi[0]['cosric']>100000000) { // ho un costo legato al fornitore
            $form['partner_cost'] = $partner_with_same_pi[0]['cosric']; // costo legato al fornitore
          }
          $form['pagame'] = $partner_with_same_pi[0]['codpag']; // condizione di pagamento
          $form['new_acconcile']=0;
          if ( $partner_with_same_pi[0]['aliiva'] > 0 ){
            $form['partner_vat'] = $partner_with_same_pi[0]['aliiva'];
          }
        } else { // se non ho già un fornitore sul piano dei conti provo a vedere nelle anagrafiche
          $rs_anagra_with_same_pi = gaz_dbi_query_anagra(array("*"), $gTables['anagra'], array("pariva" => "='" . $form['pariva'] . "'"), array("pariva" => "DESC"), 0, 1);
          $anagra_with_same_pi = gaz_dbi_fetch_array($rs_anagra_with_same_pi);
          if ($anagra_with_same_pi) { // c'è già un'anagrafica con la stessa PI non serve reinserirlo ma dovrò metterlo sul piano dei conti
            $msg['war'][] = 'no_suppl';
          } else { // non c'è nemmeno nelle anagrafiche allora attingerò i dati da questa fattura
            $msg['war'][] = 'no_anagr';
          }
        }
			}

		} else {
			$msg['err'][] = 'invalid_xml';
			$f_ex=false; // non è visualizzabile
		}
		// FINE CONTROLLI SU FILE

		if ($f_ex) { // non ho errori  vincolanti sul file posso proporre la visualizzazione
			//	Prendo i valori delle ritenute d'acconto che purtroppo sul tracciato ufficiale non viene distinto a livello di linee pertanto devo ricavarmele //
			$tot_ritenute = ($doc->getElementsByTagName("ImportoRitenuta")->length >= 1 ? $doc->getElementsByTagName('ImportoRitenuta')->item(0)->nodeValue : 0 );
			$ali_ritenute = ($doc->getElementsByTagName("AliquotaRitenuta")->length >= 1 ? $doc->getElementsByTagName('AliquotaRitenuta')->item(0)->nodeValue : 0 );
			// mi calcolo le eventuali ritenute relative alle casse previdenziali da annotare sotto quando aggiungerò i righi tipo 4
			$ritenute_su_casse = 0;
			$DatiCassaPrevidenziale = $doc->getElementsByTagName('DatiCassaPrevidenziale');
			foreach ($DatiCassaPrevidenziale as $item) { // attraverso per trovare gli elementi cassa previdenziale
				if ($item->getElementsByTagName("Ritenuta")->length >= 1 && $item->getElementsByTagName('Ritenuta')->item(0)->nodeValue=='SI'){
					// su questo contributo cassa ho la ritenuta
					$ritenute_su_casse += round($item->getElementsByTagName('ImportoContributoCassa')->item(0)->nodeValue*$ali_ritenute/100,2);
				}
			}
			// calcolo il residuo ritenute che sono costretto a mettere sulla prima linea questa è sicuramente una carenza strutturale del tracciato che non fa alcun riferimento alle linee
			$res_ritenute=round($tot_ritenute-$ritenute_su_casse,2);
			// mi serve per tenere traccia della linea con l'importo più grosso in modo da poterci sommare gli eventuali errori di arrotondamento sul totale imponibile dovuto alla diversità del metodo di calcolo usato in gazie
			$max_val_linea=1;
			$tot_imponi=0.00;
			// INIZIO creazione array dei righi con la stessa nomenclatura usata sulla tabella rigdoc a causa della mancanza di rigore del tracciato ufficiale siamo costretti a crearci un castelletto conti e iva	al fine contabilizzare direttamente qui senza passare per la contabilizzazione di GAzie e tentare di creare dei	righi documenti la cui somma coincida con il totale imponibile riportato sul tracciato
      // prendo i valori dal documento corrente
      $curr_doc_cont = $xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]");
      $df = $xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/Data")->item(0)->nodeValue;
      // trovo l'ultima data di registrazione
      $lr = getLastProtocol('AF_',substr($df,0,4),1)['last_datreg'];
      if ($lr > $df && !isset($_POST['datreg'])) { // solo al primo accesso propongo l'ultima data di registrazione
        $form['datreg'] = gaz_format_date($lr, false, true);
      }
      $date_post = gaz_format_date($form['datreg'], true);
      if ($lr > $date_post) { // solo se scelgo una data inferiore all'ultima registrazione la forzo
        $form['datreg'] = gaz_format_date($lr, false, true);
      }
      // controllo se ho uno split payment
      $yes_split = false;
      if ($xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiBeniServizi/DatiRiepilogo/EsigibilitaIVA")->length >= 1) {
        $yes_split = $xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiBeniServizi/DatiRiepilogo/EsigibilitaIVA")->item(0)->nodeValue;
      }
      $cudo=$curr_doc_cont->item(0);
			$DettaglioLinee = $cudo->getElementsByTagName('DettaglioLinee');
			$nl=0;
			$nl_NumeroLinea = []; // matrice che conterrà i riferimenti tra $nl e il NumeroLinea, da utilizzare per assegnare Numero/DataDDT se presenti
      foreach ($DettaglioLinee as $item) {
				$nl++;
				// assegno i riferimenti tra $nl e il NumeroLinea
				// succede di tutto: se NumeroLinea è doppio lo dobbiamo controllare...
				$NumLin='GAZ'.$nl;
				if ($item->getElementsByTagName("NumeroLinea")->length >= 1){ // c'è un riferimento al numero
					$NumLin=$item->getElementsByTagName('NumeroLinea')->item(0)->nodeValue;
					if (array_key_exists($NumLin,$nl_NumeroLinea)){ // controllo, e se c'è un numero duplicato :( ne invento uno pur di non perdere il riferimento
						$NumLin .= '-'.$nl;
					}
				}
				$nl_NumeroLinea[$NumLin]=$nl;
				if ($item->getElementsByTagName("CodiceTipo")->length >= 1) {
					$form['rows'][$nl]['codice_fornitore'] = trim($item->getElementsByTagName('CodiceTipo')->item(0)->nodeValue).'_'.trim($item->getElementsByTagName('CodiceValore')->item(0)->nodeValue);
				} else {
					$form['rows'][$nl]['codice_fornitore'] = ($item->getElementsByTagName("CodiceArticolo")->length >= 1 ? $item->getElementsByTagName('CodiceArticolo')->item(0)->nodeValue : '' );
				}
				// Elimino spazi dal codice fornitore creato
				$form['rows'][$nl]['codice_fornitore'] = preg_replace("/\s+/","_",$form['rows'][$nl]['codice_fornitore']);
				// vedo se ho uno stesso codice_fornitore già acquisito in precedenti documenti tramite la funzione specifica, se si lo propongo
        $codart = $gForm->CodartFromCodiceFornitore($form['rows'][$nl]['codice_fornitore'],(isset($form['clfoco'])?$form['clfoco']:0));
        $codice_articolo = $codart ? $codart['codart'] : '';
				$artico = gaz_dbi_get_row($gTables['artico'], 'codice', $codice_articolo);
				$form['rows'][$nl]['codart'] = ($artico && !empty($form['rows'][$nl]['codice_fornitore']))?$artico['codice']:'';
				$form['rows'][$nl]['search_codart'] = ($artico && !empty($form['rows'][$nl]['codice_fornitore']))?$artico['descri']:'';
				$form['rows'][$nl]['descri'] = $item->getElementsByTagName('Descrizione')->item(0)->nodeValue;
				if ($item->getElementsByTagName("Quantita")->length >= 1) {
					$form['rows'][$nl]['quanti'] = $item->getElementsByTagName('Quantita')->item(0)->nodeValue;
					$form['rows'][$nl]['tiprig'] = 0;
				} else {
					$form['rows'][$nl]['quanti'] = '';
					$form['rows'][$nl]['tiprig'] = 1; // rigo forfait
				}
				$form['rows'][$nl]['unimis'] =  ($item->getElementsByTagName('UnitaMisura')->length >= 1 ? $item->getElementsByTagName('UnitaMisura')->item(0)->nodeValue :	'');
				$form['rows'][$nl]['prelis'] = $item->getElementsByTagName('PrezzoUnitario')->item(0)->nodeValue;

				// Antonio Germani prendo il tipo di cessione prestazione che mi servirà per le eccezioni delle anomalie
				$form['rows'][$nl]['tipocessprest'] = $item->getElementsByTagName('TipoCessionePrestazione')->length >= 1 ? $item->getElementsByTagName('TipoCessionePrestazione')->item(0)->nodeValue : '';

				// inizio applicazione sconto su rigo
				$form['rows'][$nl]['sconto'] = 0;
				$acc_sconti=array();
				if ($item->getElementsByTagName("ScontoMaggiorazione")->length >= 1) { // ho uno sconto/maggiorazione
					$acc_sconti=array();
					$sconti_forfait=array();
					$sconto_maggiorazione=$item->getElementsByTagName("ScontoMaggiorazione");
					foreach ($sconto_maggiorazione as $sconti) { // potrei avere più elementi 2.2.1.10 <ScontoMaggiorazione>
						if ($form['rows'][$nl]['prelis'] < 0.00000001 && $sconti->getElementsByTagName("Importo")->length >= 1) { // se trovo l'elemento 2.2.1.9 <PrezzoUnitario> a zero calcolo lo sconto a forfait
							$sconti_forfait[]=($sconti->getElementsByTagName('Tipo')->item(0)->nodeValue == 'SC' ? -$sconti->getElementsByTagName('Importo')->item(0)->nodeValue : $sconti->getElementsByTagName('Importo')->item(0)->nodeValue);
						} elseif ($sconti->getElementsByTagName("Importo")->length >= 1 && $item->getElementsByTagName('Importo')->item(0)->nodeValue >= 0.00001){
							// calcolo la percentuale di sconto partendo dall'importo del rigo e da quello dello sconto, il funzionamento di GAzie prevede la percentuale e non l'importo dello sconto
							$tot_rig= (!empty($form['rows'][$nl]['quanti']) && $form['rows'][$nl]['quanti']!=0) ? $form['rows'][$nl]['quanti']*$form['rows'][$nl]['prelis'] : $form['rows'][$nl]['prelis'];
							$acc_sconti[]=(!empty($form['rows'][$nl]['quanti']) && intval($form['rows'][$nl]['quanti'])>1) ? $form['rows'][$nl]['quanti']*$item->getElementsByTagName('Importo')->item(0)->nodeValue*100/$tot_rig : $item->getElementsByTagName('Importo')->item(0)->nodeValue*100/$tot_rig;
							//$form['rows'][$nl]['sconto']=$item->getElementsByTagName('Importo')->item(0)->nodeValue*100/$tot_rig;
						} elseif($sconti->getElementsByTagName("Percentuale")->length >= 1 && $sconti->getElementsByTagName('Percentuale')->item(0)->nodeValue>=0.00001){ // ho una percentuale accodo quella
							$acc_sconti[]=($sconti->getElementsByTagName('Tipo')->item(0)->nodeValue == 'SC' ? $sconti->getElementsByTagName('Percentuale')->item(0)->nodeValue : -$sconti->getElementsByTagName('Percentuale')->item(0)->nodeValue);
						}
					}
					if (count($sconti_forfait) > 0) {
						$sf=0;
						foreach($sconti_forfait as $scf){ // attraverso l'accumulatore di sconti forfait per ottenerne il totale
							$sf += $scf;
						}
						$form['rows'][$nl]['prelis'] = $sf;
					} else {
						$is=1;
						foreach($acc_sconti as $vsc){ // attraverso l'accumulatore di sconti per ottenerne uno solo
							$is *=(1-$vsc/100);
						}
						$form['rows'][$nl]['sconto'] = 100*(1-$is);
					}
				}
				$form['rows'][$nl]['pervat'] = $item->getElementsByTagName('AliquotaIVA')->item(0)->nodeValue;
				// se ho un residuo di ritenuta d'acconto valorizzo con l'aliquota di cui sopra
				$form['rows'][$nl]['ritenuta'] = 0;
				// calcolo l'importo del rigo
				if ($form['rows'][$nl]['tiprig']==0){
					$form['rows'][$nl]['amount']=CalcolaImportoRigo($form['rows'][$nl]['quanti'],$form['rows'][$nl]['prelis'],array($form['rows'][$nl]['sconto']));
				} else {
					$form['rows'][$nl]['amount']=CalcolaImportoRigo(1,$form['rows'][$nl]['prelis'],array($form['rows'][$nl]['sconto']));
				}

				// tengo traccia del NumeroLinea e se il rigo è descrittivo
				$form['rows'][$nl]['numrig'] = $item->getElementsByTagName('NumeroLinea')->item(0)->nodeValue;
				$form['rows'][$nl]['is_descri'] = ($form['rows'][$nl]['prelis']<0.00000001)?1:false;

				$tot_imponi += $form['rows'][$nl]['amount'];
				if (!empty($form['rows'][$nl]) && !empty($form['rows'][$max_val_linea]) && $form['rows'][$nl]['amount']>$form['rows'][$max_val_linea]['amount']){ // è una linea con valore più alto delle precedenti faccio il push
					$max_val_linea=$nl;
				}
				if (round($res_ritenute,2)>=0.01){
					$res_ritenute -= $form['rows'][$nl]['amount']*$ali_ritenute/100;
					if (round($res_ritenute,2) >= 0) { // setto l'aliquota ritenuta ma solo se c'è stata capienza
						$form['rows'][$nl]['ritenuta'] = $ali_ritenute;
					}
				}
				$post_nl = $nl-1;
				if (empty($_POST['Submit_file']) && !isset($_POST['Select_doc'])) { // l'upload del file è già avvenuto e sono nei refresh successivi quindi riprendo i valori scelti e postati dall'utente
					if (isset($_POST['resetDdT_'.$post_nl])){
            $_POST['numddt_'.$post_nl]='';
					}

					$form['codart_'.$post_nl] = preg_replace("/[^A-Za-z0-9_]i/", '',(isset($_POST['codart_'.$post_nl]))?substr($_POST['codart_'.$post_nl],0,15):'');
					if ($_POST['hidden_req']=='change_codart_'.$post_nl){
						$form['codart_'.$post_nl] ='';
					}
					$form['rows'][$nl]['codart']=$form['codart_'.$post_nl];
					$form['search_codart_'.$post_nl] = isset($_POST['search_codart_'.$post_nl])?substr($_POST['search_codart_'.$post_nl],0,35):'';
					$form['rows'][$nl]['search_codart']=$form['search_codart_'.$post_nl];
					$form['codric_'.$post_nl] = (isset($_POST['codric_'.$post_nl]))?intval($_POST['codric_'.$post_nl]):'';
          if (isset($_POST['IncreaseBenamm'])){ // ho chiesto l'incremento di valore di un bene ammortizzabile
            $form['codric_'.$post_nl] ='';
          }
					$form['warehouse_'.$post_nl] = (isset($_POST['warehouse_'.$post_nl]))?intval($_POST['warehouse_'.$post_nl]):0;
					$form['rows'][$nl]['warehouse']=$form['warehouse_'.$post_nl];
					$form['codvat_'.$post_nl] = (isset($_POST['codvat_'.$post_nl]))?intval($_POST['codvat_'.$post_nl]):'';
				} else {
					if (isset( $form['rows'][$nl]['codart'])){
						$form['codart_'.$post_nl] = $form['rows'][$nl]['codart'];
					} else {
						$form['rows'][$nl]['codart'] = '';
						$form['codart_'.$post_nl] ='';
					}
					if (isset( $form['rows'][$nl]['search_codart']) && strlen($form['rows'][$nl]['search_codart'])>0){
						$form['search_codart_'.$post_nl] = $form['rows'][$nl]['search_codart'];
					} else {
						$form['rows'][$nl]['search_codart'] = '';
						$form['search_codart_'.$post_nl] ='';
					}
					/* al primo accesso dopo l'upload del file propongo:
					   - la prima data di registrazione utile considerando quella di questa fattura e l'ultima registrazione
					   - i costi sulle linee (righe) in base al fornitore
					   - le aliquote IVA in base a quanto trovato sul database e sul riepilogo del tracciato
					*/
					$form['codric_'.$post_nl] = $form['partner_cost'];
					$form['warehouse_'.$post_nl] = $form['in_id_warehouse'];
					if (preg_match('/TRASP/i',strtoupper($form['rows'][$nl]['descri']))) { // se sulla descrizione ho un trasporto lo propongo come costo d'acquisto
						$form['codric_'.$post_nl] = $admin_aziend['cost_tra'];
					}
					$expect_vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $form['partner_vat']); // analizzo le possibilità
					@$Natura = $item->getElementsByTagName('Natura')->item(0)->nodeValue;
					if ($yes_split == 'S') {
						$rs_split_vat = gaz_dbi_dyn_query("*", $gTables['aliiva'], "aliquo=" . $form['rows'][$nl]['pervat'] . " AND tipiva='T'", "codice ASC", 0, 1);
						$split_vat = gaz_dbi_fetch_array($rs_split_vat);
						$form['codvat_'.$post_nl] = $split_vat['codice'];
					} elseif ( $partner_with_same_pi &&  $partner_with_same_pi[0]['aliiva'] >=1) { // di defautl utilizzo l'eventuale aliquota della anagrafica del fornitore
						$form['codvat_'.$post_nl] = $partner_with_same_pi[0]['aliiva'];
					} elseif ( empty($Natura) && $expect_vat['aliquo'] == $form['rows'][$nl]['pervat']) { // coincide con le aspettative
						$form['codvat_'.$post_nl] = $expect_vat['codice'];
					} else { // non è quella che mi aspettavo allora provo a trovarne una tra quelle con la stessa aliquota
						$filter_vat = "aliquo=" . $form['rows'][$nl]['pervat'];
            $orderby = 'codice ASC';
						if (!empty($Natura)) {
							$filter_vat.= " AND fae_natura='" . $Natura . "'";
              if (substr($Natura,0,2)=='N6') { // con il reverse charge (N6.X) propongo quella più adatta ma considero una aliquota con IVA
                $filter_vat = "fae_natura='" . $Natura . "' AND aliquo >= 0.1";
                $orderby = 'descri ASC'; // ci vorrebe un similar text con gli acquisti le aliquote
              }
            }
						$rs_last_codvat = gaz_dbi_dyn_query("*", $gTables['aliiva'], $filter_vat . " AND tipiva<>'T'", $orderby, 0, 1);
						$last_codvat = gaz_dbi_fetch_array($rs_last_codvat);
						if ($last_codvat) {
							$form['codvat_'.$post_nl] = $last_codvat['codice'];
						} else {
							$form['codvat_'.$post_nl] = 'non trovata';
						}
					}
					if (empty($form['codvat_'.$post_nl])) {
						if ( $expect_vat['aliquo'] == $form['rows'][$nl]['pervat']) { // coincide con le aspettative
							$form['codvat_'.$post_nl] = $expect_vat['codice'];
						}
					}
          $map_pervat[floatval($form['rows'][$nl]['pervat'])]=$form['codvat_'.$post_nl]; // mappo aliquote-codici aliquote, potrebbe servirmi per risolvere l'eventuale PORCATA degli arrotondamenti sul castelleto IVA ( tiprig=91 )

				}

			}
			//Se la fattura è derivante da un DdT aggiungo i relativi  elementi  all'array dei righi
			$anomalia="";
      $numddt="";
			$resetDdT=false;
			if ($doc->getElementsByTagName('DatiDDT')->length>=1) {
				// quando ci sono dei DdT capita che il rigo che precede sia la descrizione del seguente allora faccio un primo attraversamento dei riferimenti ai righi perchè capita che alcuni righi descrittivi che precedono siano comunque riferiti a ddt
				$DatiDDT=$doc->getElementsByTagName('DatiDDT');
				$ctrl_NumeroDDT='';
				$acc_DataDDT=[];
        $first=true;
				foreach ($DatiDDT as $valDatiDDT) { // attraverso DatiDDT
					$RiferimentoNumeroLinea=$valDatiDDT->getElementsByTagName('RiferimentoNumeroLinea');
					$numddt=preg_replace('/\D/', '',$valDatiDDT->getElementsByTagName('NumeroDDT')->item(0)->nodeValue);
					$dataddt=$valDatiDDT->getElementsByTagName('DataDDT')->item(0)->nodeValue;
          // faccio il push sull'accumulatore dei DataDDT con stesso numero-data
          $acc_DataDDT[$numddt]=['Numero'=>$numddt,'Data'=>$dataddt];
          if (isset($form['clfoco'])) {
            $existDdT=existDdT($numddt,$dataddt,$form['clfoco']);
          } else {
            $existDdT=false;
          }
          $acc_DataDDT[$numddt]=['Numero'=>$numddt,'Data'=>$dataddt,'Exist'=>$existDdT];
					foreach ($RiferimentoNumeroLinea as $valRiferimentoNumeroLinea) { // attraverso RiferimentoNumeroLinea
            if (isset($nl_NumeroLinea[$valRiferimentoNumeroLinea->nodeValue])){//se esiste la linea indicata dal 'RiferimentoNumeroLinea'
              $nl = $nl_NumeroLinea[$valRiferimentoNumeroLinea->nodeValue];
              if (isset($form['clfoco'])&&$existDdT){
                $form['rows'][$nl]['exist_ddt']=$existDdT;
              } else {
                $form['rows'][$nl]['exist_ddt']=false;
              }
              if (isset($_POST['resetDdT_'.($nl-1)])){
                $form['rows'][$nl]['NumeroDDT']=false;
                $form['rows'][$nl]['DataDDT']=false;
                $form['numddt_'.($nl-1)]='';
              } else {
                $form['rows'][$nl]['NumeroDDT']=$numddt;
                $form['rows'][$nl]['DataDDT']=$dataddt;
                $form['numddt_'.($nl-1)]=$numddt;
              }
              // è stato assegnato ad un DdT lo rimuovo dall'array $nl_NumeroLinea in modo da poter, eventualmente trattare questi successivamente
              unset($nl_NumeroLinea[$form['rows'][$nl]['numrig']]);
              $first=false;
              $ctrl_NumeroDDT=$numddt;
            }
					}
					$ctrl_NumeroDDT=$numddt;
					$ctrl_DataDDT=$dataddt;
				}
        $numddt_tolast=false;
        // riciclo i righi per assegnare le eventuali nuove scelte DdT dell'utente
				foreach($form['rows'] as $nl => $v) {
          if ( !isset($v['NumeroDDT']) ){
            $v['NumeroDDT']='';
          }
          if ($_POST['hidden_req']=='concileDdT') { // l'utente ha scelto di cambiare il DdT di riferimento
            if (substr($_POST['numddt_'.($nl-1)],-7) == '_tolast' ) {
              $numddt_tolast=substr($_POST['numddt_'.($nl-1)],0,-7);
            } else {
              $form['rows'][$nl]['NumeroDDT']=$_POST['numddt_'.($nl-1)];
              $form['rows'][$nl]['DataDDT']=$acc_DataDDT[$_POST['numddt_'.($nl-1)]]['Data'];
              $form['numddt_'.($nl-1)]=$_POST['numddt_'.($nl-1)];
            }
            if ($numddt_tolast){
              $form['rows'][$nl]['NumeroDDT']=$numddt_tolast;
              $form['rows'][$nl]['DataDDT']=$acc_DataDDT[$numddt_tolast]['Data'];
              $form['numddt_'.($nl-1)]=$numddt_tolast;
            }
          } else if ( !empty($_POST['numddt_'.($nl-1)]) && $_POST['numddt_'.($nl-1)] <> $v['NumeroDDT'] ) { // se provengo da un cambiamento dell'utente
                $form['rows'][$nl]['NumeroDDT']=$_POST['numddt_'.($nl-1)];
                $form['rows'][$nl]['DataDDT']=$acc_DataDDT[$_POST['numddt_'.($nl-1)]]['Data'];
                $form['numddt_'.($nl-1)]=$_POST['numddt_'.($nl-1)];
          }
        }
        $numddt_tolast=false;
        $prevdescri="";
				foreach($nl_NumeroLinea as $nl){ // in questo mi ritrovo i righi non assegnati ai ddt specifici (potrebbero essere anche tutti), alcune fatture malfatte non specificano i righi!
        // in $nl ho l'indice del rigo non assegnato ad alcun DdT
          if ( count($acc_DataDDT) >= 2 ){ // se la fattura contiene più DDT allora obbligo l'utente a riferirli bene
            // qui distinguo se sono al primo refresh dopo l'upload del file
            if ( empty($_POST['Submit_file']) && !isset($_POST['Select_doc']) && !isset($_POST['resetDdT']) ) { // l'upload del file è già avvenuto e sono nei refresh successivi quindi riprendo i valori scelti e postati  dall'utente a meno che sia stato chiesto un reset
              if ($_POST['hidden_req']=='concileDdT') { // l'utente ha scelto il DdT di riferimento mancante
                if (substr($_POST['numddt_'.($nl-1)],-7) == '_tolast' ){
                  $numddt_tolast=substr($_POST['numddt_'.($nl-1)],0,-7);
                }
                if ($numddt_tolast){
                  $_POST['numddt_'.($nl-1)]=$numddt_tolast;
                }
              }
              if (empty($_POST['numddt_'.($nl-1)])){
                $form['rows'][$nl]['NumeroDDT']=false;
                $form['rows'][$nl]['DataDDT']=false;
              } else {
                $form['rows'][$nl]['NumeroDDT']=$_POST['numddt_'.($nl-1)];
                $form['rows'][$nl]['DataDDT']=$acc_DataDDT[$_POST['numddt_'.($nl-1)]]['Data'];
              }
              $form['numddt_'.($nl-1)]=$_POST['numddt_'.($nl-1)];
            } else { // sono al primo ingresso dopo l'upload del file, chiedo l'intervento dell'utente sul rigo mettendolo a false
              $form['rows'][$nl]['NumeroDDT']=false;
              $form['rows'][$nl]['DataDDT']=false;
              $isdescri = (abs($form['rows'][$nl]['amount']) < 0.01) ? true : false;
              if($first) { // almeno il primo rigo si suppone faccia parte del primo DdT, per gli altri  è necessario l'intervento dell'utente
                $jumpddt = false;
                $firstDdT=array_keys($acc_DataDDT);
                $nddt = count($firstDdT)-1;
                $ddtpointer=0;
                $form['rows'][$nl]['NumeroDDT'] = false;
                $form['rows'][$nl]['DataDDT'] = false;
                $form['numddt_'.($nl-1)]=$acc_DataDDT[$firstDdT[0]]['Numero'];
              } else { // i righi successivi saranno del primo fino a quando non trovo uno decrittivo ovvero on un amount a zero
                if ($isdescri && $prevdescri=="") { // è descrittivo e il precedente non lo era
                  if (isset($jumpddt)){
                  }else{
                    $jumpddt = true; // e vengo da un non descrittivo allora salto
                  }
                } else { // è con importo modifico lo stato di jump per poter fare un nuovo salto
                  $jumpddt = false;
                }
                if ( $jumpddt && isset($ddtpointer) && $ddtpointer < $nddt ) {
                  $ddtpointer++;
                  $form['rows'][$nl]['NumeroDDT'] = false;
                  $form['rows'][$nl]['DataDDT'] = false;
                  $form['numddt_'.($nl-1)]=$acc_DataDDT[$firstDdT[$ddtpointer]]['Numero'];
                } else {
                  $form['numddt_'.($nl-1)] = isset($form['numddt_'.($nl-2)])?$form['numddt_'.($nl-2)]:false; // se c'è propongo il rigo che lo precede
                }
              }
              $prevdescri=$isdescri;
            }
          } else { // se la fattura contiene un solo DDT a riferimento allora tutti i righi saranno suoi anche se non riferiti bene, e pertanto non è anomalo
            $form['rows'][$nl]['NumeroDDT']=$numddt;
            $form['rows'][$nl]['DataDDT']=$dataddt;
            $form['numddt_'.($nl-1)]=$numddt;
          }
          $form['rows'][$nl]['exist_ddt']=isset($acc_DataDDT[$form['numddt_'.($nl-1)]]['Exist'])?$acc_DataDDT[$form['numddt_'.($nl-1)]]['Exist']:false;
          if (empty($anomalia) && !$form['rows'][$nl]['NumeroDDT']){
            $anomalia = 'Anomalia fattura con DdT con riferimenti sui righi mancanti, selezionare e accettare le scelte prima di cofermare';
          }
          $first=false;
          $resetDdT=true;
				}
        // ricontrollo per segnalare anomalia nel caso in cui non tutti i ddt siano stati utilizzati dai righi
        $ddtused=[];
        $ddt_accln=[];
        foreach ( $form['rows'] as $kr=>$vrow){
          if ($vrow['NumeroDDT']) {
            if (!isset($ddtused[$vrow['NumeroDDT']])) { // al primo rigo di questo DdT lo segno come usato e creo la matrice con tutti i righi
              $ddtused[$vrow['NumeroDDT']]= $vrow['DataDDT'];
              $ddt_accln= ['numdoc'=>$vrow['NumeroDDT'],'date'=>$vrow['DataDDT'],'codart'=>$vrow['codart'],'quanti'=>$vrow['quanti'],'rigddt'=>(isset($vrow['exist_ddt']['rig_codart'])?$vrow['exist_ddt']['rig_codart']:null)];
            }
            // provo ad attribuire il codice articolo di questo ad uno dei i righi presenti sull'accumulatore del DdT già inserito basandomi sulla quantità
            if ($vrow['codart']=='' && isset($ddt_accln['rigddt'])) { // non ho trovato il codice articolo tramite codice fornitore oppure il fornitore non li attribuisce univocamente, uso quello sul DdT
              foreach($ddt_accln['rigddt'] as $krddt => $vrddt) { // percorro la matrice con i righi del DdT fino a trovare quello con la stessa quantità
                if ($vrow['quanti']==$vrddt['quanti']){
                  $form['codart_'.($kr-1)]=$krddt;
                  $form['rows'][$kr]['codart']=$krddt;
                  unset($ddt_accln['rigddt'][$krddt]); // quello utilizzato lo tolgo dalla matrice
                }
              }
            }
          }
        }
        if ( empty($anomalia) && count($acc_DataDDT) > count($ddtused) && count($acc_DataDDT) <= count($form['rows']) ) {
          $anomalia = 'Anomalia dei '.count($acc_DataDDT).' DdT indicati sul tracciato ne sono stati utilizzati solo '.count($ddtused);
          $resetDdT=true;
        }
        // fine controllo
			}
			$linekeys=array_keys($form['rows']);
			$nl=end($linekeys); // trovo l'ultima linea, mi servirà per accodare CassaPrevidenziale, sconti, ecc
			// QUI TRATTERO' gli elementi <DatiCassaPrevidenziale> come righi accodandoli ad essi su rigdoc (tipdoc=4)
			foreach ($DatiCassaPrevidenziale as $item) { // attraverso per trovare gli elementi cassa previdenziale
				$nl++;
				$form['rows'][$nl]['codice_fornitore'] = $item->getElementsByTagName('TipoCassa')->item(0)->nodeValue;
				$form['rows'][$nl]['tiprig'] = 4;
				// carico anche la descrizione corrispondente dal file xml
				$xml_cassa_prev = simplexml_load_file('../../library/include/fae_tipo_cassa.xml');
				foreach ($xml_cassa_prev->record as $v) {
					$selected = '';
					if ($v->field[0] == $form['rows'][$nl]['codice_fornitore']) {
						$form['rows'][$nl]['descri']= 'Contributo '.strtolower($v->field[1]);
					}
				}
				$form['rows'][$nl]['unimis'] = '';
				$form['rows'][$nl]['quanti'] = '';
				$form['rows'][$nl]['sconto'] = 0;
				$form['rows'][$nl]['provvigione'] = $item->getElementsByTagName('AlCassa')->item(0)->nodeValue; // così come per le vendite uso il campo provvigioni per mettere l'aliquota della cassa previdenziale (evidenziato anche sui commenti del database)
				if ($item->getElementsByTagName('ImponibileCassa')->length>=1) {
					$form['rows'][$nl]['prelis'] = $item->getElementsByTagName('ImponibileCassa')->item(0)->nodeValue;
				} else { // non ho l'imponibile base di calcolo, allora lo ricavo dall'importo del contributo e dall'aliquota
					$form['rows'][$nl]['prelis'] = round($item->getElementsByTagName('ImportoContributoCassa')->item(0)->nodeValue*100/$form['rows'][$nl]['provvigione'],2);
				}
				$form['rows'][$nl]['amount'] = $form['rows'][$nl]['prelis'];
				$tot_imponi += round($form['rows'][$nl]['amount']*$form['rows'][$nl]['provvigione']/100,2);
				$form['rows'][$nl]['pervat'] = $item->getElementsByTagName('AliquotaIVA')->item(0)->nodeValue;
				$form['rows'][$nl]['ritenuta']='';
				if ($item->getElementsByTagName("Ritenuta")->length >= 1 && $item->getElementsByTagName('Ritenuta')->item(0)->nodeValue=='SI'){
					// su questo contributo cassa ho la ritenuta
					$form['rows'][$nl]['ritenuta']= $ali_ritenute;
				}
				$post_nl = $nl-1;
				if (empty($_POST['Submit_file'])) { // l'upload del file è già avvenuto e sono nei refresh successivi quindi riprendo i valori scelti e postati dall'utente
					$form['codart_'.$post_nl] = preg_replace("/[^A-Za-z0-9_]i/", '',substr($_POST['codart_'.$post_nl],0,15));
					$form['search_codart_'.$post_nl] = substr($_POST['search_codart_'.$post_nl],0,35);
					$form['codric_'.$post_nl] = intval($_POST['codric_'.$post_nl]);
					$form['warehouse_'.$post_nl] = intval($_POST['warehouse_'.$post_nl]);
					$form['codvat_'.$post_nl] = intval($_POST['codvat_'.$post_nl]);
				} else {
					if (isset( $form['rows'][$nl]['codart'])){
						$form['codart_'.$post_nl] = $form['rows'][$nl]['codart'];
					} else {
						$form['rows'][$nl]['codart'] = '';
						$form['codart_'.$post_nl] ='';
					}
					if (isset( $form['rows'][$nl]['search_codart']) && strlen($form['rows'][$nl]['search_codart'])>0){
						$form['search_codart_'.$post_nl] = $form['rows'][$nl]['search_codart'];
					} else {
						$form['rows'][$nl]['search_codart'] = '';
						$form['search_codart_'.$post_nl] ='';
					}

					// al primo accesso dopo l'upload del file propongo:
				  // - i costi sulle linee (righe) in base al fornitore
				  // - le aliquote IVA in base a quanto trovato sul database e sul riepilogo del tracciato
					$form['codric_'.$post_nl] = $form['partner_cost'];
					$expect_vat = gaz_dbi_get_row($gTables['aliiva'], 'codice', $form['partner_vat']);
					// analizzo le possibilità
					@$Natura = $item->getElementsByTagName('Natura')->item(0)->nodeValue;
					if ( empty($Natura) && $expect_vat['aliquo'] == $form['rows'][$nl]['pervat']) { // coincide con le aspettative
						$form['codvat_'.$post_nl] = $expect_vat['codice'];
					} else { // non è quella che mi aspettavo allora provo a trovarne una tra quelle con la stessa aliquota
						$filter_vat = "aliquo=" . $form['rows'][$nl]['pervat'];
						if (!empty($Natura)) {
							$filter_vat.= " AND fae_natura='" . $Natura . "'";
						}
						$rs_last_codvat = gaz_dbi_dyn_query("*", $gTables['aliiva'], $filter_vat . " AND tipiva<>'T'", "codice ASC", 0, 1);
						$last_codvat = gaz_dbi_fetch_array($rs_last_codvat);
						if ($last_codvat) {
							$form['codvat_'.$post_nl] = $last_codvat['codice'];
						} else {
							$form['codvat_'.$post_nl] = 'non trovata';
						}
					}
					if ( empty($form['codvat_'.$post_nl]) && $expect_vat['aliquo'] == $form['rows'][$nl]['pervat']) { // coincide con le aspettative
						$form['codvat_'.$post_nl] = $expect_vat['codice'];
					}
				}
			}
			//	Se presenti, trasformo gli sconti/maggiorazioni del campo 2.1.1.8 <ScontoMaggiorazione> in righe forfait
			if ($xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/ScontoMaggiorazione")->length >= 1) {
				$sconto_totale_incondizionato = [];
				$sconto_maggiorazione = $xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/ScontoMaggiorazione");
				foreach ($sconto_maggiorazione as $sconti) { // potrei avere più elementi 2.2.1.10 <ScontoMaggiorazione>
					$sconto_incondizionato = ($sconti->getElementsByTagName('Tipo')->item(0)->nodeValue == 'SC' ? -$sconti->getElementsByTagName('Importo')->item(0)->nodeValue : $sconti->getElementsByTagName('Importo')->item(0)->nodeValue);
          if ($sconti->getElementsByTagName('Percentuale')->length >= 1 && $sconti->getElementsByTagName('Percentuale')->item(0)->nodeValue>=0.00001) {
            $sconto_totale_incondizionato[] = $sconti->getElementsByTagName('Percentuale')->item(0)->nodeValue;
          } else {
            if (abs($sconto_incondizionato)>=0.00001) {
              $nl++;
              $form['rows'][$nl]['tiprig'] = 1;
              $form['rows'][$nl]['codice_fornitore'] = '';
              $form['rows'][$nl]['descri'] = 'Sconto';
              $form['rows'][$nl]['unimis'] = '';
              $form['rows'][$nl]['quanti'] = '';
              $form['rows'][$nl]['sconto'] = '';
              $form['rows'][$nl]['ritenuta'] = '';
              $form['rows'][$nl]['pervat'] = '';
              $form['codart_'.($nl-1)] = '';
              $form['codvat_'.($nl-1)] = '';
              $form['codric_'.($nl-1)] = '';
              $form['rows'][$nl]['prelis'] = $sconto_incondizionato;
              $form['rows'][$nl]['amount'] = $sconto_incondizionato;
            }
          }
				}
				if (count($sconto_totale_incondizionato) > 0) {
					$is=1;
					foreach($sconto_totale_incondizionato as $vsc){ // attraverso l'accumulatore di sconti per ottenerne uno solo
						$is *=(1-$vsc/100);
					}
					$sconto_totale_incondizionato = 100*(1-$is);
				}
			}
			$ImponibileImporto=0.00;
			$ImpostaDocumento=0.00;
			//Se la fattura è di tipo semplificata
			if ($isFatturaElettronicaSemplificata) {
				$DettaglioLineeSemplificate = $doc->getElementsByTagName('DatiBeniServizi');
				$nl = 0;
				foreach ($DettaglioLineeSemplificate as $item) {
					$nl++;
					$form['rows'][$nl]['tiprig'] = 1;
					$form['rows'][$nl]['codice_fornitore'] = '';
					$form['rows'][$nl]['descri'] = $item->getElementsByTagName('Descrizione')->item(0)->nodeValue;
					$form['rows'][$nl]['unimis'] = '';
					$form['rows'][$nl]['prelis'] = $item->getElementsByTagName('Importo')->item(0)->nodeValue;
					$form['rows'][$nl]['quanti'] = 1;
					$form['rows'][$nl]['amount'] = $form['rows'][$nl]['prelis'];
					$form['rows'][$nl]['sconto'] = '';
					$form['rows'][$nl]['ritenuta'] = '';
					if ($item->getElementsByTagName('Aliquota')->length > 0) {
						$form['rows'][$nl]['pervat'] = $item->getElementsByTagName('Aliquota')->item(0)->nodeValue;
                    } else {
						$form['rows'][$nl]['pervat'] = 0;
					}
					if ($item->getElementsByTagName('Imposta')->length > 0) {
						$ImpostaDocumento += $item->getElementsByTagName('Imposta')->item(0)->nodeValue;
                    }
					if ($item->getElementsByTagName('Natura')->length > 0) {
						$Natura = $item->getElementsByTagName('Natura')->item(0)->nodeValue;
						$rs_vat = gaz_dbi_dyn_query("codice", $gTables['aliiva'], "fae_natura='" . $Natura . "'", "codice DESC", 0, 1);
						$cod_vat = gaz_dbi_fetch_array($rs_vat)['codice'];
						$form['codvat_'.($nl-1)] = $cod_vat;
					}
				}
			} else { // non è una fattura semplificata
				$DatiRiepilogo = $xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiBeniServizi/DatiRiepilogo");
				$naturaN6 = false;
				foreach($DatiRiepilogo as $dr) {
					if ($dr->getElementsByTagName('Imposta')->length >= 1) {
						$ImpostaDocumento +=  (float)$dr->getElementsByTagName('Imposta')->item(0)->nodeValue;
					}
					$ImponibileImporto+= (float)$dr->getElementsByTagName('ImponibileImporto')->item(0)->nodeValue;
					if ($dr->getElementsByTagName('Natura')->length >= 1) { // se ho l'elemento Natura = 6.x dovrò ricercare l'aliquota per il reverse charge a tutto il documento ed attribuirla a tutti i righi del documento
						$Natura = $dr->getElementsByTagName('Natura')->item(0)->nodeValue;
						if ( substr($Natura,0,2) == 'N6' ) { // dovrò fare il reverse charge sostituisco con il codice iva relativo
							$naturaN6 = $Natura;
						}
					}
          // controllo se lo SdI ha consentito la PORCATA!
          $ctrlaliquo=(float)$dr->getElementsByTagName('AliquotaIVA')->item(0)->nodeValue;
          $ctrlimponi=(float)$dr->getElementsByTagName('ImponibileImporto')->item(0)->nodeValue;
          if ( $ctrlaliquo > 0 ) {
            $ctrliva=round($ctrlimponi*$ctrlaliquo/100,2);
            $diffiva=round((float)$dr->getElementsByTagName('Imposta')->item(0)->nodeValue-$ctrliva,2);
            if ( abs($diffiva) >= 0.01) { // PORCATA! L'IVA NON RISPETTA LE REGOLE, sono costretto ad inserire questo rigo fittizio per indicare l'anomalia ma soprattutto al fine di contabilizzare in accordo con la stessa (porcata)
              $nl++;
              $form['rows'][$nl]['tiprig'] = 91;
              $form['rows'][$nl]['codice_fornitore'] = '';
              $form['rows'][$nl]['descri'] = 'Storno IVA calcolata in modo errato';
              $form['rows'][$nl]['unimis'] = '';
              $form['rows'][$nl]['quanti'] = '';
              $form['rows'][$nl]['sconto'] = '';
              $form['rows'][$nl]['ritenuta'] = '';
              $form['rows'][$nl]['pervat'] = $ctrlaliquo;
              $form['codart_'.($nl-1)] = '';
              $form['codvat_'.($nl-1)] = (isset($map_pervat))?$map_pervat[floatval($ctrlaliquo)]:'';
              $form['codric_'.($nl-1)] = intval($form['codric_'.$post_nl]);
              $form['rows'][$nl]['prelis'] = $diffiva;
              $form['rows'][$nl]['amount'] = $diffiva;
            }
          }
          // fine controllo e "trattamento" PORCATA, se è stata fatta avrò dei righi tipo 91 che dovrò gestire in contabilizzazione per apportare le dovute modifiche ai valori di IVA, ma anche sulle stampe PDF
				}
				if (!isset($_POST['Submit_form']) && $naturaN6 ) { // al primo accesso se sopra ho trovato che è una natura da reverse charge
					$stdiva = gaz_dbi_get_row($gTables['aliiva'], 'codice', $admin_aziend['preeminent_vat'])['aliquo']; //la percentuale dell'aliquota standard (potrebbe cambiare negli anni)
					$rs_reverse = gaz_dbi_dyn_query("codice", $gTables['aliiva'], "aliquo=".$stdiva." AND fae_natura='" .$naturaN6."'", "codice DESC", 0, 1);
					$cod_reverse = gaz_dbi_fetch_array($rs_reverse)['codice'];
					// riattraverso i righi e ci metto il nuovo codice IVA
					foreach($form['rows'] as $kn => $vn) {
						$kp = $kn-1;
					}
				}
				$totdiff=abs($ImponibileImporto-$tot_imponi);
				// Infine aggiungo un eventuale differenza di centesimo di imponibile sul rigo di maggior valore, questo succede perché il tracciato non è rigoroso nei confronti dell'importo totale dell'elemento
				if ($totdiff>=0.01){ // qualora ci sia una differenza di almeno 1 cent la aggiunto (o lo sottraggo al rigo di maggior valore
					if ($form['rows'][$max_val_linea]['tiprig']==0){ //rigo normale con quantità variabile
						$form['rows'][$max_val_linea]['prelis']+= ($ImponibileImporto-$tot_imponi)/$form['rows'][$max_val_linea]['quanti'];
					} else {
						$form['rows'][$max_val_linea]['prelis']+= $ImponibileImporto-$tot_imponi;
					}
					$form['rows'][$max_val_linea]['amount'] += $ImponibileImporto-$tot_imponi;
				}
			}

      // qui eseguo un controllo per vedere se c'è l'elemento <Arrotondamento> dentro <DatiGeneraliDocumento> e se l'elemento <ImportoTotaleDocumento> non coincide con i righi procedo con l'aggiunta di un rigo fittizio in art.15 (natura esenzione N1)
      $ImportoTotaleDocumento=$xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/ImportoTotaleDocumento")->item(0)->nodeValue;
			if ($xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/Arrotondamento")->length >= 1) {
        $Arrotondamento=$xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/Arrotondamento")->item(0)->nodeValue;
        if (abs($ImportoTotaleDocumento-($ImponibileImporto + $ImpostaDocumento)) >= 0.01) { // ho una effettiva differenza tra i totali del castelletto IVA e il totale documennto allora aggiungo un rigo fuori campo IVA N1
  				$codvat_fc=gaz_dbi_get_row($gTables['aliiva'], "fae_natura", 'N1')['codice'];
          $nl++;
					$form['rows'][$nl]['tiprig'] = 1;
					$form['rows'][$nl]['codice_fornitore'] = '';
					$form['rows'][$nl]['descri'] = 'Arrotondamento';
					$form['rows'][$nl]['unimis'] = '';
					$form['rows'][$nl]['quanti'] = '';
					$form['rows'][$nl]['sconto'] = '';
					$form['rows'][$nl]['ritenuta'] = '';
					$form['rows'][$nl]['pervat'] = '';
					$form['codart_'.($nl-1)] = '';
					$form['codvat_'.($nl-1)] = $codvat_fc;
					$form['codric_'.($nl-1)] = $form['codric_'.($nl-2)]; // attribuisco il costo del rigo che lo precede
					$form['rows'][$nl]['prelis'] = $Arrotondamento;
					$form['rows'][$nl]['amount'] = $Arrotondamento;
        }
      }

			// ricavo l'allegato, e se presente metterò un bottone per permettere il download
      $yesatt = $doc->getElementsByTagName('NomeAttachment')->item(0);
      if ($yesatt){
        $yesatt=[];
        $allegati = $doc->getElementsByTagName('Allegati');
        foreach ($allegati as $allitem){
          $nomeatt = $allitem->getElementsByTagName('NomeAttachment')->item(0);
          $name_file = $nomeatt->textContent;
          $contentatt = $allitem->getElementsByTagName('Attachment')->item(0);
          $yesatt[]='<div class="text-bold">Download allegato: <a download='.$name_file.'" href="data:application/'.pathinfo($name_file,PATHINFO_EXTENSION).';base64,'.$contentatt->textContent.'">'.$name_file.'</a></div>';
        }
      }
			if (isset($_POST['Select_doc'])) { // vengo da una selezione di fattura corrente  contenuta in un xml multiplo
                // non modifico i valori derivanti da $form
			} else if (empty($_POST['Submit_file'])) { // l'upload del file è già avvenuto e sono nei refresh successivi quindi riprendo i valori scelti e postati dall'utente
				//$form['datreg'] = substr($_POST['datreg'],0,10);
				$form['pagame'] = intval($_POST['pagame']);
				$form['new_acconcile'] = intval($_POST['new_acconcile']);
				$form['seziva'] = intval($_POST['seziva']);
			}

			if (isset($_POST['Submit_form']) && count($msg['err'])==0) { // confermo le scelte sul form, inserisco i dati sul db ma solo se non ho errori
				if (!$anagra_with_same_pi && !$partner_with_same_pi) { // non ho nulla: devo inserire tutto (anagrafica e fornitore) basandomi sul pagamento e sui conti di costo scelti dall'utente
					$new_partner = array_merge(gaz_dbi_fields('clfoco'), gaz_dbi_fields('anagra'));
					$new_partner['codpag'] = $form['pagame'];
					$new_partner['sexper'] = 'G';
					// setto le colonne in base ai dati di questa fattura elettronica
					$new_partner['pariva'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice")->item(0)->nodeValue;
					if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0)) {
						$new_partner['codfis'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/CodiceFiscale")->item(0)->nodeValue;
						// ho un codice fiscale posso vedere se è una persona fisica e di quale sesso
						preg_match('/^[a-z]{6}[0-9]{2}[a-z]([0-9]{2})[a-z][0-9]{3}[a-z]$/i',trim($new_partner['codfis']),$match);
						if (count($match)>1){
							if ($match[1] > 40 ){  // è un codice fiscale femminile
								$new_partner['sexper'] = 'F';
							} else {
								$new_partner['sexper'] = 'M';
							}
						} else { // giuridica
							$new_partner['sexper'] = 'G';
						}
					}
					if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Nome")->item(0)) {
						$new_partner['legrap_pf_nome'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Nome")->item(0)->nodeValue;
						$new_partner['legrap_pf_cognome'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Cognome")->item(0)->nodeValue;
						$new_partner['descri'] = $new_partner['legrap_pf_cognome']. ' '.$new_partner['legrap_pf_nome'];
						if (strlen($new_partner['descri'])>50){
							$new_partner['ragso1'] = $new_partner['legrap_pf_cognome'];
							$new_partner['ragso2'] = $new_partner['legrap_pf_nome'];
						} else {
							$new_partner['ragso1'] = $new_partner['descri'];
						}
					}
					if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Denominazione")->item(0)) {
						$new_partner['descri'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/Anagrafica/Denominazione")->item(0)->nodeValue;
						if (strlen($new_partner['descri'])>50){
							$new_partner['ragso1'] = substr(str_replace(array("'",'"',"`"),"",$new_partner['descri']),0,50);
							$new_partner['ragso2'] = substr(str_replace(array("'",'"',"`"),"",$new_partner['descri']),50,100);
						} else {
							$new_partner['ragso1'] = str_replace(array("'",'"',"`"),"",$new_partner['descri']);
						}
					}
					$new_partner['indspe'] = ucwords(strtolower($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Indirizzo")->item(0)->nodeValue));
					if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/NumeroCivico")->item(0)){
						$new_partner['indspe'] .= ', '.$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/NumeroCivico")->item(0)->nodeValue;
					}
					$new_partner['capspe'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/CAP")->item(0)->nodeValue;
					$new_partner['citspe'] = strtoupper($xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Comune")->item(0)->nodeValue);
					if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Provincia")->item(0)){
						$new_partner['prospe'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Provincia")->item(0)->nodeValue;
					}
					$new_partner['country'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Sede/Nazione")->item(0)->nodeValue;
					$new_partner['counas'] = $new_partner['country'];
					$new_partner['id_currency'] =1;
					$new_partner['id_language'] =1;
					$new_partner['cosric']=intval($_POST['codric_0']);	 // prendo il primo valore di costo per valorizzare quello del fornitore
					if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Telefono")->item(0)) {
						$new_partner['telefo'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Telefono")->item(0)->nodeValue;
					}
					if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Fax")->item(0)) {
						$new_partner['fax'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Fax")->item(0)->nodeValue;
					}
					if (@$xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Email")->item(0)) {
						$new_partner['e_mail'] = $xpath->query("//FatturaElettronicaHeader/CedentePrestatore/Contatti/Email")->item(0)->nodeValue;
					}
					if (@$xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiPagamento/DettaglioPagamento/IBAN")->item(0)) {
						$new_partner['iban'] = $xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiPagamento/DettaglioPagamento/IBAN")->item(0)->nodeValue;
					}
					// trovo l'ultimo codice disponibile sul piano dei conti
					$rs_last_partner = gaz_dbi_dyn_query("*", $gTables['clfoco'], 'codice BETWEEN ' . $admin_aziend['masfor'] . '000001 AND ' . $admin_aziend['masfor'] . '999999', "codice DESC", 0, 1);
					$last_partner = gaz_dbi_fetch_array($rs_last_partner);
					if (!$last_partner) {
						$new_partner['codice']=$admin_aziend['masfor'].'000001';
					} else {
						$new_partner['codice'] =$last_partner['codice']+1;
					}
					// inserisco il partner
					$anagrafica->insertPartner($new_partner);
					$form['clfoco']=$new_partner['codice'];
				} else if ($anagra_with_same_pi) { // devo inserire il fornitore, ho già l'anagrafica
					$anagra_with_same_pi['id_anagra'] = $anagra_with_same_pi['id'];
					$anagra_with_same_pi['cosric'] = intval($_POST['codric_0']); // prendo il primo valore di costo per valorizzare quello del fornitore
					$form['clfoco'] = $anagrafica->anagra_to_clfoco($anagra_with_same_pi, $admin_aziend['masfor'], $form['pagame']);
				}
				$prefisso_codici_articoli_fornitore=encondeFornitorePrefix($form['clfoco']);// mi servirà eventualmente per attribuire ai nuovi articoli un pre-codice univoco e uguale per tutti gli articoli dello stesso fornitore
				$form['tipdoc'] = $tipdoc_conv[$xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/TipoDocumento")->item(0)->nodeValue];
				$form['protoc'] = getLastProtocol($form['tipdoc'],substr($form['datreg'],-4),$form['seziva'])['last_protoc'];
				$form['numfat'] = $xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0)->nodeValue;
				$form['numdoc'] = preg_replace ('/\D/', '', $form['numfat']);
				$form['datfat'] = $xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiGenerali/DatiGeneraliDocumento/Data")->item(0)->nodeValue;
				$form['datemi'] = $form['datfat'];
				$form['fattura_elettronica_original_content'] = mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings());//https://php.watch/versions/8.2/utf8_encode-utf8_decode-deprecated
				$form['datreg'] = gaz_format_date($form['datreg'], true);
				$form['caumag'] = $magazz->get_codice_caumag(1, 1, $docOperat[$form['tipdoc']]);
				if (!empty($sconto_totale_incondizionato)) {
					$form['sconto'] = $sconto_totale_incondizionato;
				}
				$form['template']="FatturaAcquisto";

				$accexpdoc = array();
				if ($doc->getElementsByTagName('DettaglioPagamento')->length>=1) {
					// se ho le date e gli importi delle scadenze creo un array da inserire sulla tabella gaz_NNNexpdoc al fine di poter aprire le partite in base a quanto riportato in fattura del fornitore e senza calcolarli dalla modalità di pagamento con si faceva sulle versioni <= 7.34
					$detpag=$doc->getElementsByTagName('DettaglioPagamento');
					foreach ($detpag as $vdp) { // attraverso
						if ($vdp->getElementsByTagName('DataScadenzaPagamento')->length>=1 && $vdp->getElementsByTagName('ImportoPagamento')->length>=1){
							$accexpdoc[] = array('ModalitaPagamento'=>$vdp->getElementsByTagName('ModalitaPagamento')->item(0)->nodeValue,'DataScadenzaPagamento'=>$vdp->getElementsByTagName('DataScadenzaPagamento')->item(0)->nodeValue,'ImportoPagamento'=>$vdp->getElementsByTagName('ImportoPagamento')->item(0)->nodeValue);
						}

					}
				}
				// Inizio scrittura DB
				if ($doc->getElementsByTagName('DatiDDT')->length<1 || $form['tipdoc']=="AFC"){
					// se non ci sono ddt vuol dire che è una fattura immediata AFA
					//oppure se è una nota credito AFC non devo considerare eventuali DDT a riferimento
					$ultimo_id=tesdocInsert($form); // Antonio Germani - creo fattura immediata senza ddt
                    $fn = DATA_DIR . 'files/' . $admin_aziend["codice"] . '/'.$ultimo_id.'.inv';
                    file_put_contents($fn,$form['fattura_elettronica_original_content']);
				}
				$ctrl_ddt='';
        $i=0;
        foreach ($form['rows'] as $row){ // aggiungo i mancanti prendendoli dal $_POST
          $j=$i+1;
          $form['rows'][$j]['codart'] = preg_replace("/[^A-Za-z0-9_]i/",'',$_POST['codart_'.$i]);
					$form['rows'][$j]['codric'] = intval($_POST['codric_'.$i]);
					$form['rows'][$j]['warehouse'] = intval($_POST['warehouse_'.$i]);
					$form['rows'][$j]['codvat'] = intval($_POST['codvat_'.$i]);
          $i++;
        }

        if (!empty($ctrl_NumeroDDT)){
          usort($form['rows'], function($a, $b) {
            return $a['NumeroDDT'] <=> $b['NumeroDDT'];
          });
        }
				$movmag_prev=array();
				foreach ($form['rows'] as $i => $v) { // inserisco i righi
          $form['rows'][$i]['status'] = ( substr($v['codric'],0,3) == $admin_aziend['mas_fixed_assets'] ) ? 'ASS10':'';
					if (abs($v['prelis'])<0.00000001) { // siccome il prezzo è a zero mi trovo di fronte ad un rigo di tipo descrittivo
						$form['rows'][$i]['tiprig']=2;
					}
					if ($form['tipdoc']=="AFC" && $ImportoTotaleDocumento <= -0.01 ) { // capita a volte che dei software malfatti sulle note credito indichino i valori in negativo... allora per renderli compatibili con la contabilizzazione di GAzie invertiamo il segno
							$form['rows'][$i]['prelis']=-$v['prelis'];
					}
					// questo mi servirà sotto se è stata richiesta la creazione di un articolo nuovo
					if (empty(trim($v['codice_fornitore']))) { // non ho il codice del fornitore me lo invento accodando al precedente prefisso dipendente dal codice del fornitore un hash a 8 caratteri della descrizione
						$new_codart=$prefisso_codici_articoli_fornitore.'_'.crc32($v['descri']);
					} else { // ho il codice articolo del fornitore sul tracciato ma potrei averlo cambiato
						$new_codart=$prefisso_codici_articoli_fornitore.'_'.substr($v['codice_fornitore'],-11);
					}
					$movmag_datreg=$form['datreg'];
					if (isset($v['exist_ddt']) && $form['tipdoc']!=="AFC") { // se ci sono DDT collegabili alla FAE e non è una nota credito AFC
						if ($ctrl_ddt!=$v['NumeroDDT']) {
							// Antonio Germani - controllo se esiste tesdoc di questo ddt usando la funzione existDdT
							$exist_tesdoc=existDdT($v['NumeroDDT'],$v['DataDDT'],$form['clfoco']);
							// registro il DdT in data di emissione, se già presente conservo quella dei movimenti di magazzino che andrò ad eliminare (vedi sotto)
							$movmag_datreg=$v['DataDDT'];
							if ($exist_tesdoc){// se esiste cancello tesdoc e ne cancello tutti i rigdoc e i relativi movmag, ma mi mantengo le date di registrazione
								$rs_righidel = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = '{$exist_tesdoc['id_tes']}'","id_tes desc");
								gaz_dbi_del_row($gTables['tesdoc'], "id_tes", $exist_tesdoc['id_tes']);
								while ($a_row = gaz_dbi_fetch_array($rs_righidel)) {
								  if ($a_row['id_mag']!=null && $a_row['id_mag']>=1) {
                    $movmag_rowprev = gaz_dbi_get_row($gTables['movmag'], "id_mov", $a_row['id_mag']);
                    $movmag_prev[] = $movmag_rowprev;// creo un array con tutti i vecchi righi di movmag cancellati servirà poi per riconnettere l' id del lotto qualora fosse già stato inserito nel precedente ddt. Servirà anche per riconnettere eventuali movimenti SIAN
                    $movmag_datreg = $movmag_rowprev['datreg'];
                    gaz_dbi_del_row($gTables['rigdoc'], "id_rig", $a_row['id_rig']);
                    gaz_dbi_del_row($gTables['movmag'], "id_mov", $a_row['id_mag']);
								  }
								}
							}
							// creo un nuovo tesdoc AFT
							if ($exist_tesdoc && $exist_tesdoc['tipdoc']=="RDL"){
								$ddt_type="L";
							} else {
								$ddt_type="T";
							}
							$form['tipdoc']="AFT";$form['ddt_type']=$ddt_type;$form['numdoc']=substr($v['NumeroDDT'],-9);$form['datemi']=$v['DataDDT'];
							$ultimo_id =tesdocInsert($form); // Antonio Germani - creo fattura differita
							$fn = DATA_DIR . 'files/' . $admin_aziend["codice"] . '/'.$ultimo_id.'.inv';
							file_put_contents($fn,$form['fattura_elettronica_original_content']);
						}
						$ctrl_ddt=$v['NumeroDDT'];

					}
          //echo"<pre>",print_r($movmag_prev);die;
					$form['rows'][$i]['id_tes'] = $ultimo_id;
					$aliiva=$form['rows'][$i]['codvat'];
          //if($naturaN6){
            $form['rows'][$i]['pervat']=gaz_dbi_get_row($gTables['aliiva'], 'codice', $aliiva)['aliquo'];
          //}
					$exist_new_codart=gaz_dbi_get_row($gTables['artico'], "codice", $new_codart);
					if ($exist_new_codart && substr($v['codart'],0,6)!='Insert') { // il codice esiste lo uso, ma prima controllo se l'ho volutamente cambiato sul form
						if( $exist_new_codart['codice'] != $form['rows'][$i]['codart'] ){ // ho scelto un codice diverso
							$other_artico=gaz_dbi_get_row($gTables['artico'], "codice", $form['rows'][$i]['codart']);
              if (isset($other_artico)){
                $form['rows'][$i]['good_or_service']=$other_artico['good_or_service'];
                //aggiorno l'articolo con questo codice fornitore
                gaz_dbi_put_row($gTables['artico'], 'codice', $other_artico['codice'], 'codice_fornitore', $v['codice_fornitore']);
              }
						} else {
							$form['rows'][$i]['codart']=$exist_new_codart['codice'];
							$form['rows'][$i]['good_or_service']=$exist_new_codart['good_or_service'];
						}
					} else { // il codice nuovo ricavato non esiste creo l'articolo basandomi sui dati in fattura
						if ($exist_new_codart) { // il fornitore ha la pessima abitudine di usare lo stesso codice articolo per diversi articoli me lo invento con un hash a 8 caratteri della descrizione nella speranza che almeno questa sia cambiata...
							$new_codart=$prefisso_codici_articoli_fornitore.'_'.crc32($v['descri'].$form['datreg'].$form['protoc']);
						}
						$v['catmer'] = 1; // di default utilizzo la prima categoria merceologica, sarebbe da farla selezionare all'operatore...
						$form['rows'][$i]['good_or_service']=0;
            // controllo se ho scelto di creare un nuovo articolo su diversi righi
						$exist_artico=gaz_dbi_get_row($gTables['artico'], "codice", $new_codart);
						if ( isset($v['codart']) && !$exist_artico ){
							switch ($v['codart']) {
								case 'Insert_New': // inserisco il nuovo articolo in gaz_XXXartico senza lotti o matricola
								$artico=array('codice'=>$new_codart,'descri'=>$v['descri'],'catmer'=>$v['catmer'],'codice_fornitore'=>$v['codice_fornitore'],'unimis'=>$v['unimis'],'web_mu'=>$v['unimis'],'uniacq'=>$v['unimis'],'aliiva'=>$aliiva);
								gaz_dbi_table_insert('artico', $artico);
								$form['rows'][$i]['codart'] = $new_codart;
								break;
								case 'Insert_W_lot': // inserisco il nuovo articolo in gaz_XXXartico con lotti
								$artico=array('codice'=>$new_codart,'descri'=>$v['descri'],'catmer'=>$v['catmer'],'codice_fornitore'=>$v['codice_fornitore'],'lot_or_serial'=>1,'unimis'=>$v['unimis'],'web_mu'=>$v['unimis'],'uniacq'=>$v['unimis'],'aliiva'=>$aliiva);
								gaz_dbi_table_insert('artico', $artico);
								$form['rows'][$i]['codart'] = $new_codart;
								break;
								case 'Insert_W_matr': //  inserisco il nuovo articolo in gaz_XXXartico con matricola
								$artico=array('codice'=>$new_codart,'descri'=>$v['descri'],'catmer'=>$v['catmer'],'codice_fornitore'=>$v['codice_fornitore'],'lot_or_serial'=>2,'unimis'=>$v['unimis'],'web_mu'=>$v['unimis'],'uniacq'=>$v['unimis'],'aliiva'=>$aliiva);
								gaz_dbi_table_insert('artico', $artico);
								$form['rows'][$i]['codart'] = $new_codart;
								break;
								default: //  negli altri casi controllo se devo inserire il riferimento ad una bolla
							}
						}
					}
					// alla fine se ho un codice articolo e il tipo rigo è normale aggiorno l'ultimo costo con il nuovo prezzo d'acquisto e con l'ultimo fornitore
					if (strlen($form['rows'][$i]['codart'])>2&&$form['rows'][$i]['tiprig']==0) {
						tableUpdate('artico',array('clfoco','preacq'),$form['rows'][$i]['codart'],array('preacq'=>CalcolaImportoRigo(1,$form['rows'][$i]['prelis'],array($form['rows'][$i]['sconto'])),'clfoco'=>$form['clfoco']));
					}

					// inserisco il rigo rigdoc
					$id_rif=rigdocInsert($form['rows'][$i]);
					if (isset($form['rows'][$i]['good_or_service']) && $form['rows'][$i]['good_or_service']==0 && strlen($form['rows'][$i]['codart'])>0 && $form['tipdoc']!=="AFC"){ // se l'articolo prevede di movimentare il magazzino e non è una nota credito
						// Antonio Germani - creo movimento di magazzino sempre perché, se c'erano, sono stati cancellati
						if (isset($v['NumeroDDT']) && $v['NumeroDDT']>0){ // se c'è un ddt
              $idlotmag='';$n=0;$rif_sian_movmag='';$break=0;

              foreach($movmag_prev as $key => $movmag_row){// controllo se un rigo con stesso codice articolo e la stessa relativa quantità erano nei movmag cancellati
                if ($movmag_row['artico'] == $form['rows'][$i]['codart'] && floatval($movmag_row['quanti']) == floatval($form['rows'][$i]['quanti'])){
                  $idlotmag=intval($movmag_row['id_lotmag']);//se era presente ne prendo l' id_lot
                  $art=gaz_dbi_get_row($gTables['artico'], "codice", $movmag_row['artico']);// prendo i dati di questo articolo
                  if (intval($art['SIAN'])>0){// se l'articolo movimenta il SIAN, allora devo riconnettere il movmag con camp_mov_sian
                    $rif_sian_movmag=intval($movmag_row['id_mov']);// quando avrò il nuovo id movamag aggiornero il camp_mov_sian
                  }
                  unset ($movmag_prev[$key]);// tolgo questo rigo dall'array dei previous in quanto già elaborato( per evitare di riaverlo dentro questo ciclo qualora ci fossero più righi di uno con stesso codart con stessa quanti
                  break;
                }
                $n++;
              }
							$rowmag=array("caumag"=>$form['caumag'],"type_mov"=>"0","operat"=>"1","datreg"=>$movmag_datreg,"tipdoc"=>"ADT",
							"desdoc"=>"D.d.t. di acquisto n.".$v['NumeroDDT']."/".$form['seziva']." prot. ".$form['protoc']."/".$form['seziva'],
							"datdoc"=>$form['datemi'],"clfoco"=>$form['clfoco'],"id_rif"=>$id_rif,"artico"=>$form['rows'][$i]['codart'],"id_warehouse"=>$form['rows'][$i]['warehouse'],"quanti"=>$form['rows'][$i]['quanti'],
							"prezzo"=>$form['rows'][$i]['prelis'],"scorig"=>$form['rows'][$i]['sconto'],'synccommerce_classname'=>$admin_aziend['synccommerce_classname'],'id_lotmag'=>$idlotmag);
						} else { // se non c'è DDT
							$rowmag=array("caumag"=>$form['caumag'],"type_mov"=>"0","operat"=>"1","datreg"=>$movmag_datreg,"tipdoc"=>"ADT",
							"desdoc"=>"Fattura di acquisto n.".$form['numfat']."/".$form['seziva']." prot. ".$form['protoc']."/".$form['seziva'],
							"datdoc"=>$form['datfat'],"clfoco"=>$form['clfoco'],"id_rif"=>$id_rif,"artico"=>$form['rows'][$i]['codart'],"id_warehouse"=>$form['rows'][$i]['warehouse'],"quanti"=>$form['rows'][$i]['quanti'],
							"prezzo"=>$form['rows'][$i]['prelis'],"scorig"=>$form['rows'][$i]['sconto'],'synccommerce_classname'=>$admin_aziend['synccommerce_classname']);
						}

						$id_mag=movmagInsert($rowmag);

						// aggiorno idmag nel rigdoc
						gaz_dbi_query("UPDATE " . $gTables['rigdoc'] . " SET id_mag = " . $id_mag . " WHERE `id_rig` = $id_rif ");
            if (isset($idlotmag) && intval($idlotmag)>0){// aggiorno lotmag
              gaz_dbi_query("UPDATE " . $gTables['lotmag'] . " SET id_movmag = " . intval($id_mag) . ", id_rigdoc = " . intval($id_rif) . " WHERE id = ".intval($idlotmag));

            }

            if (isset($rif_sian_movmag) && intval($rif_sian_movmag)>0){// aggiorno camp_mov_sian
              gaz_dbi_query("UPDATE " . $gTables['camp_mov_sian'] . " SET id_movmag = " . $id_mag . " WHERE `id_movmag` = $rif_sian_movmag ");
            }
					}

				}
				// se l'array delle scadenze ha dati li inserisco nell'apposita tabella facendo riferimento sempre all'ultimi id_tes inserito
				foreach ($accexpdoc as $ved) { // attraverso
					$ved['id_tes']=$ultimo_id;
					expdocInsert($ved);
				}

				if (in_array($send_fae_zip_package['val'],$sync_mods)){
					$where = [];
					$where[]="title";
					$where[]=$form['fattura_elettronica_original_name'];
					$set['status']=1;
					gaz_dbi_table_update("files", $where, $set);
					//Antonio Germani: caso di 2 o più aziende installate in GAzie con stessa partita IVA e stessa PEC per comunicare con ADE
					// dopo aver impostato lo status di acquisita su questa azienda, devo togliere eventuali fae.xml caricati  anche nelle altre aziende.
					// Quindi, vedo se ci sono altre aziende con stessa partita iva togliendo l'azienda attuale
					$codice_aziends = gaz_dbi_dyn_query("codice", $gTables['aziend'], "pariva = ".$admin_aziend['pariva']." AND codice <> ".$admin_aziend['codice'] , "codice ASC");
					foreach($codice_aziends as $codice){// le ciclo e cerco in tabella file per ciascuna delle altre aziende se c'è nella colonna 'title' la 'fattura_elettronica_original_name'
						unset($duplicated);
						$check_duplicated= gaz_dbi_dyn_query("id_doc","gaz_".sprintf('%03d',$codice['codice'])."files","title = '".$form['fattura_elettronica_original_name']."'");
						$duplicated=gaz_dbi_fetch_array($check_duplicated);
						if ($duplicated){// se c'è lo cancello
							gaz_dbi_del_row( "gaz_".sprintf('%03d',$codice['codice'])."files", "title", $form['fattura_elettronica_original_name']);
						}
					}

				}
        if (count($movmag_prev)>0){// se mi sono rimasti dei righi nei movimenti precedenti segnalo l'incoerenze fra DDT e FAE
          ?>
          <script>
          alert('ATTENZIONE: la FAE è stata acquisita ma sono state riscontrate incoerenze negli articoli presenti nel DDT con quelli della FAE. Tali incoerenze hanno creato ERRORI nella gestione dei lotti e dei movimenti SIAN, qualora presenti. Gli errori dovranno essere corretti manualmente da un esperto di GAzie');
          </script>
          <?php
        }
        header('Location: report_docacq.php?sezione='.$form['seziva']);
				exit;
			} else { // non ho confermato, sono alla prima entrata dopo l'upload del file
				if (!isset($form['pagame'])) {
					if ($xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiPagamento/DettaglioPagamento/ModalitaPagamento")->item(0)){
						$fae_mode = $xpath->query("//FatturaElettronicaBody[".$form['curr_doc']."]/DatiPagamento/DettaglioPagamento/ModalitaPagamento")->item(0)->nodeValue;
						$pagame = gaz_dbi_get_row($gTables['pagame'], "fae_mode", $fae_mode);
					}
					$form['pagame'] = (isset($pagame))?$pagame['codice']:0;
					$form['new_acconcile']=0;
				}
			}
		}
	}
	}
}

require('../../library/include/header.php');
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup'));
echo "<script type=\"text/javascript\">
var cal = new CalendarPopup();
var calName = '';
function setMultipleValues(y,m,d) {
     document.getElementById(calName+'_Y').value=y;
     document.getElementById(calName+'_M').selectedIndex=m*1-1;
     document.getElementById(calName+'_D').selectedIndex=d*1-1;
}
function setDate(name) {
  calName = name.toString();
  var year = document.getElementById(calName+'_Y').value.toString();
  var month = document.getElementById(calName+'_M').value.toString();
  var day = document.getElementById(calName+'_D').value.toString();
  var mdy = month+'/'+day+'/'+year;
  cal.setReturnFunction('setMultipleValues');
  cal.showCalendar('anchor', mdy);
}
</script>
";
?>
<script type="text/javascript">
$(function(){
    $("#datreg").datepicker({showButtonPanel: true, showOtherMonths: true, selectOtherMonths: true});
    $("#datreg,#new_acconcile").change(function () {
        this.form.submit();
    });
    $( ".search_artico" ).autocomplete({
        source: "search.php?opt=invoiceart",
        minLength: 2,
        html: true, // optional (jquery.ui.autocomplete.html.js required)
        // optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        },
        select: function(event, ui) {
			var vn = $(this).attr('artref');
			$('#'+vn).val(ui.item.value);
            this.form.submit();
        }
    });
});

function prevXML(urlPrintDoc){
	$(function(){
		$('#xmlpreview').attr('src',urlPrintDoc);
		$('#xmlpreview').css({'height': '100%'});
		$('.xmlpreview').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
    $("html, body").delay(100).animate({scrollTop: $('#xmlpreview').offset().top},200, function() {
        $("#xmlpreview").focus();
    });
		$('#closeXML').on( "click", function() {
			$('.xmlpreview').css({'display': 'none'});
		});
	});
};

</script>
<div align="center" ><b><?php echo $script_transl['title'];?></b></div>
<form method="POST" name="form" enctype="multipart/form-data" id="add-invoice">
	<div class="xmlpreview panel panel-success" style="display: none; position: absolute; left: 5%; top: 100px">
		<div class="col-lg-12">
			<div class="col-xs-11"><h4>Anteprima fattura</h4></div>
			<div class="col-xs-1"><h4><button type="button" id="closeXML"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="xmlpreview"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
	</div>
    <input type="hidden" name="fattura_elettronica_original_name" value="<?php echo $form['fattura_elettronica_original_name']; ?>">
    <input type="hidden" name="incrbenamm" value="<?php echo $form['incrbenamm']; ?>">
    <input type="hidden" name="curr_doc" value="<?php echo $form['curr_doc']; ?>">
    <input type="hidden" name="id_doc" value="<?php echo $form['id_doc']; ?>">
    <input type="hidden" name="hidden_req" id="hidden_req" value="">
<?php
// INIZIO form che permetterà all'utente di interagire per (es.) imputare i vari costi al piano dei conti (contabilità) ed anche le eventuali merci al magazzino
if (count($msg['err']) > 0) { // ho un errore
  $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
  // controllo se il file è stato acquisito tramite sync, per alcuni errori devo consentire di elimianare il file
  $typerr=['filmim','invalid_xml','invalid_fae','file_exists','not_mine','same_content','reverse_charge'];
  $isfilerr=false;
  foreach ($msg['err'] as $verr) {
    if (in_array($verr,$typerr)){
      $isfilerr=true;
    }
  }
  if (isset($_POST['fae_from_sync']) && $_POST['fae_from_sync'] >= 1 && $isfilerr) { // visualizzo il bottone per eliminare il file
    echo '<div class="row col-xs-12 text-center"><a href="delete_fae_from_sync.php?id_doc='.intval($_POST['fae_from_sync']).'&fn='.$_POST['fae_original_name'.intval($_POST['fae_from_sync'])].'" class="btn btn-sm btn-danger">ELIMINA IL FILE '.$_POST['fae_original_name'.intval($_POST['fae_from_sync'])].'</a></div>';
  }
}
if (count($msg['war']) > 0) { // ho un alert
    $gForm->gazHeadMessage($msg['war'], $script_transl['war'], 'war');
}

if ($toDo=='insert' || $toDo=='update' ) {
	if ($f_ex){
		if (empty($form['curr_doc']) && count($docs) > 1) {
	?>
			<div class="row">
					<div class="form-group">
						<label for="image" class="col-sm-4 control-label">Scegli la fattura da acquisire</label>
						<div class="col-sm-12">
							<br />

	<?php
				$ndoc = 0;
				echo "<table class=\"Tlarge table table-striped table-bordered table-condensed\">";
				echo '<tr><th>Seleziona</th><th>Tipo Doc.</th><th>Numero</th><th>Data</th></tr>';
				foreach ($docs as $doc) {
					$ndoc++;
					$tipdoc = $xpath->query("//FatturaElettronicaBody[".$ndoc."]/DatiGenerali/DatiGeneraliDocumento/TipoDocumento")->item(0)->nodeValue;
					$datdoc = $xpath->query("//FatturaElettronicaBody[".$ndoc."]/DatiGenerali/DatiGeneraliDocumento/Data")->item(0)->nodeValue;
					$numdoc = $xpath->query("//FatturaElettronicaBody[".$ndoc."]/DatiGenerali/DatiGeneraliDocumento/Numero")->item(0)->nodeValue;
					echo '<tr>';
					echo '<td align="center">' . $ndoc . ' <input type="radio" name="curr_doc" value="' . $ndoc . '" /></td>';
					echo '<td>' . $tipdoc . '</td>';
					echo '<td>' . gaz_format_date($datdoc, false) . '</td>';
					echo '<td>' . $numdoc . '</td>';
					echo '</tr>';
				}
				echo '</table><br />';
				echo '<div class="col-sm-12 text-right"><input name="Select_doc" type="submit" class="btn btn-warning" value="Seleziona documento" />';
	?>
						</div>
					</div>
				</div>
			</div><!-- chiude row  -->
	<?php
		} else {
	?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<div class="col-xs-12 text-center bg-info"><h3><?php echo $nomefornitore; ?></h3></div>
			<div class="col-xs-12 text-center bg-warning text-danger"><b><?php echo ($partner_with_same_pi && $partner_with_same_pi[0]['aliiva']>=1)?'AVVISO! L\'anagrafica del fornitore: <a target="_blank" href="admin_fornit.php?Update&codice='.intval(substr($partner_with_same_pi[0]['codice'],3,6)).'"> <b>'.$nomefornitore.'</b></a> ha forzato le aliquote IVA dei righi':''; ?></b></div>
			<div class="row">
				<div class="col-sm-12 col-md-12 col-lg-12">
        <?php
        echo $script_transl['head_text1']. '<b>'.$form['fattura_elettronica_original_name'] .'</b>';
        if ( $form['incrbenamm'] < 100 ) {
          echo $script_transl['head_text2'];
          if ($form['id_doc'] >= 1) { // visualizzo il bottone per offrire la possibilità di acquisire il file come bene ammortizzabile
            echo '<br/>Se questa fattura è relativa ad un <a href="admin_assets.php?id_doc='.$form['id_doc'].'" class="btn btn-xs btn-warning">NUOVO BENE AMMORTIZZABILE CLICCA QUI </a> per procedere all\'acquisizione fornendo altri dati. <br/>Se l\'acquisto va solo ad incrementare il valore di un <b>cespite già presente</b> <small><input name="IncreaseBenamm" type="submit" class="" value="CLICCA QUI PER SCEGLIERE QUALE." /></small>';
          }
        } else {
          echo '<br/><span class="bg-warning text-danger"><b>Hai scelto di acquisire la fattura come incremento di valore di un bene ammortizzabile, scegli il CONTO IMMOBILIZZAZIONE sotto oppure revoca la scelta:<a href="acquire_invoice.php" class="btn btn-xs btn-warning"> ANNULLA </a></b></span> ';
        }
        ?>
				</div>
			</div> <!-- chiude row  -->
		</div>
		<div class="panel-body">
			<div class="form-group">
				<div class="form-group col-md-4 col-lg-2 nopadding">
					 <label for="seziva" class="col-form-label"><?php echo $script_transl['seziva']; ?></label>
					 <div>
							<?php
							$gForm->selectNumber('seziva', $form['seziva'], 0, 1, 9, "col-xs-12", '', 'style="max-width: 100px;"');
							?>
					</div>
				</div>
				<div class="form-group col-md-4 col-lg-2 nopadding">
					<label for="in_id_warehouse" class="col-form-label">Magazzino</label>
					 <div>
	<?php
	$magazz->selectIdWarehouse('in_id_warehouse',$form["in_id_warehouse"],false,'col-xs-12 col-sm-6');
	?>
					</div>
				</div>
				<div class="form-group col-md-4 col-lg-2 nopadding">
					 <label for="datreg" class="col-form-label"><?php echo $script_transl['datreg']; ?></label>
					 <div>
						 <input type="text" id="datreg" name="datreg" value="<?php echo $form['datreg']; ?>">
					 </div>
				</div>
				<div class="form-group col-md-6 col-lg-3 nopadding">
					<label for="new_acconcile" class="col-form-label" >
          <?php
          if ( $form['incrbenamm'] >= 100 ) {
            echo '<span class="bg-warning text-danger">'.$script_transl['new_acconcile_incrbenamm'].'</span>';
          } else {
            echo $script_transl['new_acconcile'];
          }
          ?>
          </label>
					<div>
					<?php
					// new_acconcile lo riporto sempre a 0 dopo ogni post e solo quando viene cambiato cambieranno tutti i valori dei conti di costo di tutti i righi
          // se ho scelto di incrementare il valore di un bene ammortizzabile passo il mastro della configurazione azienda altrimenti tutti i conti di costo
          $acconcile = $form['incrbenamm'] >= 100 ? $form['incrbenamm'] : ['sub',3];
					$gForm->selectAccount('new_acconcile', 0, $acconcile,'', false, "col-xs-12 small",'style="max-width: 300px;"', false);
					?>
					</div>
				</div>
				<div class="form-group col-md-6 col-lg-3 nopadding">
					 <label for="pagame" class="col-form-label" ><?php echo $script_transl['pagame']; ?></label>
					 <div>
							<?php
							$select_pagame = new selectpagame("pagame");
							$select_pagame->addSelected($form["pagame"]);
							$select_pagame->output(false, "col-lg-12");
							?>
					</div>
				</div>
			</div> <!-- chiude row  -->
		</div>
	</div>
	<?php
			$rowshead=[];
			$ctrl_ddt='';
			$exist_movmag=false;
			$new_acconcile=$form['new_acconcile'];
      foreach ($form['rows'] as $k => $v) {
				$k--;
				if (isset($v['NumeroDDT'])) { // ho i riferimenti ai DdT
          if ( $v['NumeroDDT'] && empty($anomalia) ) { // ho i riferimenti ai DdT
            if ($ctrl_ddt!=$v['NumeroDDT']) { // salto DdT
              $exist_ddt='';
              if ($v['exist_ddt']){ // ho un ddt d'acquisto già inserito
                $exist_ddt='<span class="warning"> gi&agrave; inserito in data '.gaz_format_date($v['exist_ddt']['datreg']).' <a class="btn btn-xs btn-success" href="admin_docacq.php?id_tes='. $v['exist_ddt']['id_tes'] . '&Update"><i class="glyphicon glyphicon-edit"></i>&nbsp;'.$v['exist_ddt']['id_tes'].'</a></span>';
                $tipddt=$v['exist_ddt']['tipdoc'];
              } else {
                $tipddt="Ddt";
              }
              $ctrl_ddt=$v['NumeroDDT'];
              $rowshead[$k]='<td colspan=14><b>da DdT n.'.$v['NumeroDDT'].' del '.gaz_format_date($v['DataDDT']).' '.$exist_ddt.'</b></td>';
            }
            echo '<input type="hidden" name="'.'numddt_'.$k.'" value="'.(isset($form['numddt_'.$k])?$form['numddt_'.$k]:$ctrl_ddt).'" />';
          } else { // qui segnalo le anomalie e faccio le richieste di intervento dell'utente
            if ( $v['NumeroDDT']){
              if ($ctrl_ddt!=$v['NumeroDDT']) { // salto DdT
                $exist_ddt='';
                if ($v['exist_ddt']){ // ho un ddt d'acquisto già inserito
                  $exist_ddt='<span class="warning"> gi&agrave; inserito in data '.gaz_format_date($v['exist_ddt']['datreg']).' <a class="btn btn-xs btn-success" href="admin_docacq.php?id_tes='. $v['exist_ddt']['id_tes'] . '&Update"><i class="glyphicon glyphicon-edit"></i>&nbsp;'.$v['exist_ddt']['id_tes'].'</a></span>';
                  $tipddt=$v['exist_ddt']['tipdoc'];
                } else {
                  $tipddt="Ddt";
                }
                $ctrl_ddt=$v['NumeroDDT'];
                $rowshead[$k]='<td colspan=14><b>da DdT n.'.$v['NumeroDDT'].' del '.gaz_format_date($v['DataDDT']).' '.$exist_ddt.'</b></td>';
              }
              echo '<input type="hidden" name="'.'numddt_'.$k.'" value="'.(isset($form['numddt_'.$k])?$form['numddt_'.$k]:$ctrl_ddt).'" />';
            } else {
              $ctrl_ddt='';
              $rowshead[$k]='<td colspan=14 class="bg-danger text-danger">'.concileDdT('numddt_'.$k,$form['numddt_'.$k],$acc_DataDDT).' Sulla fattura manca il riferimento al DdT del rigo '.($k+1).'  accetta la proposta o cambia la selezione</td>';
            }
          }
        }
				if ($new_acconcile>100000000){
					$form['codric_'.$k]=$new_acconcile;
				}
				$codric_dropdown = $gForm->selectAccount('codric_'.$k, $form['codric_'.$k], array('sub',1,3), '', false, "col-sm-12 small",'style="max-width: 350px;"', false, true);
				$whareh_dropdown = $magazz->selectIdWarehouse('warehouse_'.$k,(isset($form['warehouse_'.$k]))?$form['warehouse_'.$k]:0,true,'col-xs-12',$form['codart_'.$k],$datdoc,($docOperat[$tipdoc]*-floatval($v['quanti'])));
				$codvat_dropdown = $gForm->selectFromDB('aliiva', 'codvat_'.$k, 'codice', $form['codvat_'.$k], 'aliquo', true, '-', 'descri', '', 'col-sm-12 small', null, 'style="max-width: 350px;"', false, true);
				$codart_select = $gForm->concileArtico('codart_'.$k,(isset($form['search_codart_'.$k]))?$form['search_codart_'.$k]:'',$form['codart_'.$k]);
				//forzo i valori diversi dalla descrizione a vuoti se è descrittivo
				if (abs($v['prelis'])<0.00000001){ // siccome il prezzo è a zero mi trovo di fronte ad un rigo di tipo descrittivo
					$v['codice_fornitore'] = '';
					$v['unimis'] = '';
					$v['quanti'] = '';
					$v['unimis'] = '';
					$v['prelis'] = '';
					$v['sconto'] = '';
					$v['amount'] = '';
					$v['ritenuta'] = '';
					$v['pervat'] = '';
					$codric_dropdown = '<input type="hidden" name="codric_'.$k.'" value="isdescri" />';
					$whareh_dropdown = '<input type="hidden" name="warehouse_'.$k.'" value="0" />';
					$codvat_dropdown = '<input type="hidden" name="codvat_'.$k.'" value="isdescri" />';
					$codart_select = '<input type="hidden" name="codart_'.$k.'" /><input type="hidden" name="search_codart_'.$k.'" />';
				} else {
					//$v['prelis']=gaz_format_number($v['prelis']);
					$v['amount']=gaz_format_number($v['amount']);
					$v['ritenuta']=floatval($v['ritenuta']);
					$v['pervat']=floatval($v['pervat']);
				}
				// creo l'array da passare alla funzione per la creazione della tabella responsive
				$resprow[$k] = array(
					array('head' => $script_transl["nrow"], 'class' => '',
						'value' => ($k+1)),
					array('head' => $script_transl["codart"], 'class' => '',
						'value' => $v['codice_fornitore']),
					array('head' => 'Articolo', 'class' => '',
						'value' => $codart_select),
					array('head' => 'Magazzino', 'class' => '',
						'value' => $whareh_dropdown),
					array('head' => $script_transl["descri"], 'class' => 'col-sm-12 col-md-3 col-lg-3',
						'value' => $v['descri']),
					array('head' => $script_transl["unimis"], 'class' => '',
						'value' => $v['unimis']),
					array('head' => $script_transl["quanti"], 'class' => 'text-right numeric',
						'value' => $v['quanti']),
					array('head' => $script_transl["prezzo"], 'class' => 'text-right numeric',
						'value' => $v['prelis']),
					array('head' => $script_transl["sconto"], 'class' => 'text-right numeric',
						'value' => $v['sconto']),
					array('head' => $script_transl["amount"], 'class' => 'text-right numeric',
						'value' => $v['amount'], 'type' => ''),
					array('head' => $script_transl["conto"], 'class' => 'text-center numeric',
						'value' => $codric_dropdown, 'type' => ''),
					array('head' => $script_transl["tax"], 'class' => 'text-center numeric',
						'value' => $codvat_dropdown, 'type' => ''),
					array('head' => '%', 'class' => 'text-center numeric',
						'value' => $v['pervat'], 'type' => ''),
					array('head' => 'Ritenuta', 'class' => 'text-center numeric',
						'value' => $v['ritenuta'], 'type' => '')
				);
        if ($resetDdT) {
          array_unshift($resprow[$k], ['head' =>'DdT', 'class' => '',	'value' => '<input type="submit" value="" name="resetDdT_'.$k.'" title="annulla la scelta del DdT di riferimento per questo rigo" class="bg-danger" style="background-image: url(../../library/images/link_break.png); background-size:100% 100%; width:100%; height:100%; " >']);
        }
			}
			$gForm->gazResponsiveTable($resprow, 'gaz-responsive-table', $rowshead);
	?>
  <div class="row">
      <div class="col-sm-2">
	<?php
if ($yesatt){
  foreach ($yesatt as $yav){
    echo $yav;
  }
}
	?>
			</div>
			<?php
      if ($resetDdT) {
        echo '<div class="col-sm-2"><input name="resetDdT" type="submit" class="btn btn-warning" value="Annulla scelte DdT"></div>';
        echo '<div class="col-sm-2 text-center"><input name="ychoice" type="submit" class="btn btn-success" value="Accetta"></div>';
      } else {
        echo '<div class="col-sm-2"></div>';
      }
			if (!empty($anomalia)) { // La FAE non ha i riferimenti linea nei ddt
				echo '<div class="col-sm-5 text-danger bg-warning text-right">'.$anomalia.'</div>';
			} else {
 				echo '<div class="col-sm-5 text-danger bg-warning text-right"></div>';

      }	?>

				<div class="col-sm-1">
					<input name="Submit_form" type="submit"
          <?php
					if ($anomalia!=""){ // La FAE non ha i riferimenti linea nei ddt
						echo ' disabled ';
					}
					?>
          class="btn btn-warning" value="<?php echo $script_transl['submit']; ?>" />
				</div>
			</div>
    </form>
	<br/>
	<?php
		}
	}
	if ($f_ex) {	// visualizzo la fattura elettronica in calce
		$fae_xsl_file = gaz_dbi_get_row($gTables['company_config'], 'var', 'fae_style');
		$xslDoc = new DOMDocument();
		$xslDoc->load('../../library/include/'.$fae_xsl_file['val'].'.xsl');
		$xslt = new XSLTProcessor();
		$xslt->importStylesheet($xslDoc);
		$iframe_src = str_replace('"', '&quot;', $xslt->transformToXML($xml));
?>
        <iframe style="border: none" width="99%" height="400px" sandbox="allow-same-origin"
                srcdoc="<?=$iframe_src?>"
                onload="this.style.height = this.contentDocument.firstChild.scrollHeight + 'px';
                        this.contentDocument.body.style.textAlign = 'center';">
        </iframe>
<?php
	}
} else { // all'inizio chiedo l'upload di un file xml o p7m
?>
<div class="panel panel-default gaz-table-form">
	<div class="container-fluid">

		<?php
		if (!isset($_POST['fattura_elettronica_original_name']) && in_array($send_fae_zip_package['val'],$sync_mods)){
			if ($res_faesync->num_rows >0){
				?>
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="image" class="col-sm-4 control-label">Queste fatture sono arrivate ma ancora da acquisire:</label>
							<div class="col-sm-8">
							<?php
              $first='class="btn btn-success" title="Acquisisci"';
							foreach($res_faesync as $faesync){
                $linkxml = "view_fae.php?id_tes=" . $faesync['id_doc'].'.'. $faesync['extension'].'&fromdoc';
								echo '<p>'.$faesync['table_name_ref']." ";
                if ($first) {
                  echo '<button type="submit" '. $first .' name="fae_from_sync" value="'. $faesync['id_doc'].  '">'.$faesync['title'].' </button>';
                	echo '<a class="btn btn-default btn-xml" target="_blank" title="Anteprima" onclick="prevXML(\''.$linkxml.'\')"> <i class="glyphicon glyphicon-eye-open" title="Visualizza"></i></a>';
                } else {
                  echo '<a class="btn btn-default btn-xml" target="_blank" title="Anteprima" onclick="prevXML(\''.$linkxml.'\')">'.$faesync['title'].' <i class="glyphicon glyphicon-eye-open" title="Visualizza"></i></a>';
                }
                ?>
                <input type="hidden" name="fae_original_name<?php echo $faesync['id_doc'];?>" value="<?php echo $faesync['title'];?>">
                <input type="hidden" name="table_name_ref<?php echo $faesync['id_doc'];?>" value="<?php echo substr($faesync['table_name_ref'],0,10);?>">
								</p>
                <?php
                $first=false;
							}
							?>
							</div>
						</div>
					</div>
				</div><!-- chiude row  -->
				<?php
			}
		}
		?>

       <div class="row">
           <div class="col-md-12">
               <div class="form-group">
                   <label for="image" class="col-sm-4 control-label">Seleziona il file xml, p7m <?php if (!empty($send_fae_zip_package['val'])) { echo 'o pacchetto ZIP'; } ?> </label>
                   <div class="col-sm-8">File: <input type="file" accept=".xml,.p7m,.zip" name="userfile" />
				   </div>
               </div>
           </div>
       </div><!-- chiude row  -->
<?php

if (!empty($send_fae_zip_package['val']) && $send_fae_zip_package['val']!='pec_SDI' && !in_array($send_fae_zip_package['val'],$sync_mods)) {
?>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label for="image" class="col-sm-4 control-label">o consulta il canale telematico</label>
					<div class="col-sm-8">
						<br />
						<div class="col-sm-6">dal
<?php
						$gForm->CalendarPopup('date_ini', $form['date_ini_D'], $form['date_ini_M'], $form['date_ini_Y'], 'FacetSelect', 1);
?>
						</div>
						<div class="col-sm-6">al
<?php
						$gForm->CalendarPopup('date_fin', $form['date_fin_D'], $form['date_fin_M'], $form['date_fin_Y'], 'FacetSelect', 1);
?>
						</div>
						<div class="col-sm-12 text-center"><input name="Submit_list" type="submit" class="btn btn-success" value="VISUALIZZA" />
						</div>
					</div>
				</div>
			</div>
		</div><!-- chiude row  -->
<?php
	if (!empty($AltreFattF)) {
?>
		<div class="row">
			<div class="col-md-12">
				<div class="form-group">
					<label for="image" class="col-sm-4 control-label">Scegli la fattura da acquisire</label>
					<div class="col-sm-12">
						<br />
<?php
		if (is_array($AltreFattF)) {
			echo "<table class=\"Tlarge table table-striped table-bordered table-condensed\">";
			echo '<tr><th>Seleziona</th><th>Id SdI</th><th>Ricezione</th><th>Fornitore</th><th>Numero</th><th>Data</th></tr>';
			foreach ($AltreFattF as $AltraFattF) {
				echo '<tr>';
				echo '<td align="center"><input type="radio" name="selected_SdI" value="' . $AltraFattF[0] . '" /></td>';
				echo '<td>' . implode('</td><td>', $AltraFattF) . '</td>';
				echo '</tr>';
			}
			echo '</table><br />';
		} else {
			echo '<p>' . print_r($AltreFattF, true) . '</p>';
		}
?>
					</div>
				</div>
			</div>
		</div><!-- chiude row  -->
<?php
	}
}
?>
		<div class="col-sm-12 text-right"><input name="Submit_file" type="submit" class="btn btn-warning" value="<?php echo $script_transl['btn_acquire']; ?>" />
		</div>
		<br /><br />
	</div> <!-- chiude container -->
</div><!-- chiude panel -->
<?php
}
require("../../library/include/footer.php");
?>

