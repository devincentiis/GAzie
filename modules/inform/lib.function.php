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

class informForm extends GAzieForm {

	private $testata = [];
  private $TestataLettera;

	function __construct($testata='') {
		$this->TestataLettera = $testata;
	}

	//Funzione per elaborare gli schortcode
	function shortcode($testo){
		//Recupero i dati della lettera
		foreach ($this->TestataLettera as $key => $value) {
			if ($key == 'datemi'){
				$testo = str_replace('[' . $key .' dFY]', date('d F Y',strtotime($value)), $testo);
				$testo = str_replace('[' . $key .' dmT]', date('d/m/Y',strtotime($value)), $testo);
			}else{
				$testo = (is_string($value))?str_replace('[' . $key .']', $value , $testo):$testo;
			}
		}

		//Cerco se c'è da recuperare la stampa del preventivo
		$regex = '/\[preventivo\s(.*?)\]/i';
		preg_match_all($regex, $testo, $matches, PREG_SET_ORDER);
		// No matches, skip this
		if ($matches){
			foreach ($matches as $match)
			{
			$param = array();
			$MatchesListTemp = explode(' ', $match[1]);
			foreach ($MatchesListTemp as $match1)
			{
				$tmp = explode('=', $match1);
				$param[$tmp[0]] = str_replace("'", '',$tmp[1]);
			}
			$output = $this->righepreventivo($param);

			$testo = str_replace($match[0], $output, $testo);
			}
		}

		//Cerco se c'è da recuperare il totale del preventivo
		$regex = '/\[totalepreventivo\s(.*?)\]/i';
		preg_match_all($regex, $testo, $matches, PREG_SET_ORDER);
		// No matches, skip this
		if ($matches){
			foreach ($matches as $match)
			{
			$param = array();
			$MatchesListTemp = explode(' ', $match[1]);
			foreach ($MatchesListTemp as $match1)
			{
				$tmp = explode('=', $match1);
				$param[$tmp[0]] = str_replace("'", '',$tmp[1]);
			}
			$output = $this->totalepreventivo($param);

			$testo = str_replace($match[0], $output, $testo);
			}
		}

		return $testo;
	}

	//Funzione per la creazione della tabella del preventivo
	function righepreventivo($param){
		global $gTables, $admin_aziend;

		require("../../modules/vendit/lang." . $admin_aziend['lang'] . ".php");
		$script_transl = $strScript['admin_broven.php'];

		//Recupero le righe del
		$old_rows = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = " . $param['id'], "id_rig asc");

		//Colori Tabella
		if(isset($param['thbackgroundcolor'])){
			$thbackgroundcolor = $param['thbackgroundcolor'];
		}else{
			$thbackgroundcolor = "#f8f8f8";
		}

		if(isset($param['thcolor'])){
			$thcolor = $param['thcolor'];
		}else{
			$thcolor = "#000";
		}

		if(isset($param['trbordertot'])){
			$trbordertot = $param['trbordertot'];
		}else{
			$trbordertot = "#f2f2f2";
		}

		//Calcolo le larghezze e i numeri delle colonne
		if(strtolower($param['noparriga'])!= 'si'){
			//Mostro la colonna Prezzo e quantità
			$descrilen = 55 ;
			$totalelen = 15 ;
			$ncdescri = 2;
		}else{
			//Nascondo la colonna Prezzo e quantità
			$descrilen = 80 ;
			$totalelen = 20 ;
			$ncdescri = 1 ;
		}
		if(isset($param['checkbox']) && strtolower($param['checkbox'])== 'si'){
			$descrilen = $descrilen - 5;
		}

		//Disegno la tabella
		$output = '<table cellspacing="0" cellpadding="1" border="0">';
		$output .= '<tr style="background-color:' . $thbackgroundcolor . ';color:' . $thcolor .'">';

		//Intestazione della tabella
		$output .= '<th width="' . $descrilen . '%">' . $script_transl[21] . '</th>';

		if(strtolower($param['noparriga'])!= 'si'){
			$output .= '<th width="15%" align="right">' . $script_transl[23] . '</th>';
			$output .= '<th width="15%" align="right">' . $script_transl[16] . '</th>';
		}

		$output .= '<th width="' .$totalelen .'%" align="right">' . $script_transl[25] . '</th>';

		if(isset($param['checkbox']) && strtolower($param['checkbox'])== 'si'){
			$output .= '<th width="2%" align="right"></th>';
			$output .= '<th width="3%" align="right"></th>';
		}
		$output .= '</tr>';
		//disegno le righe della tabella del preventivo
		while ($val_old_row = gaz_dbi_fetch_array($old_rows)) {
			//Calcolo il prezzo e l'importo
			if(isset($param['calciva']) && strtolower($param['calciva']) == 'si'){
				$prezzo = round($val_old_row['prelis'] + ($val_old_row['prelis'] * $val_old_row['pervat'] / 100), 2);
			}else{
				$prezzo = round($val_old_row['prelis'], 2);
			}
			//Calcolo l'importo
			$importo = round($val_old_row['quanti'] * $prezzo, 2);

			//Disegno la riga
			$output .= '<tr>';
			$output .= '<td>' . $val_old_row['descri'] . '</td>';
			//Celle prezzo unitario e quantità
			if(strtolower($param['noparriga'])!= 'si'){
				$output .= '<td align="right">&euro; ' . $prezzo . '</td>';
				$output .= '<td align="right">' . $val_old_row['unimis'] . ' ' . round($val_old_row['quanti'], 2) . '</td>';
			}
			//Importo della riga
			$output .= '<td align="right">&euro; ' . $importo . '</td>';

			//Checkbox sulle righe
			if(isset($param['checkbox']) && strtolower($param['checkbox'])== 'si'){
				$output .= '<td></td>';
				$output .= '<td><div style="border:1px solid #a2a2a2;height:10px;"></div></td>';
			}
			$output .= '</tr>';
		}
		//disegno il totale
		if(isset($param['totale']) && strtolower($param['totale'])== 'si'){
			$output .= '<tr>';
				$output .= '<td colspan="' . $ncdescri . '"></td>';

				if(strtolower($param['noparriga'])!= 'si'){
					$output .= '<td align="right" style="border-top:1px solid ' . $trbordertot . ';">' . $script_transl[36] . '</td>';
					$output .= '<td align="right" style="border-top:1px solid ' . $trbordertot . ';">' .$this->totalepreventivo($param) .'</td>';
				}else{
					$output .= '<td align="right" style="border-top:1px solid #f2f2f2;">' . $script_transl[36] . ' ' .$this->totalepreventivo($param) .'</td>';
				}
				if(isset($param['checkbox']) && strtolower($param['checkbox'])== 'si'){
					$output .= '<th width="2%" align="right"></th>';
					$output .= '<th width="3%" align="right"></th>';
				}
			$output .= '</tr>';
		}

		$output .= '</table>';
		return $output ;
	}

	//Funzione per la creazione della tabella del preventivo
	function totalepreventivo($param){
		global $gTables;

		//Recupero le righe del
		$old_rows = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = " . $param['id'], "id_rig asc");
		$tot = 0;
		while ($val_old_row = gaz_dbi_fetch_array($old_rows)) {
			if(isset($param['calciva']) && strtolower($param['calciva']) == 'si'){
				$prezzo = round($val_old_row['prelis'] + ($val_old_row['prelis'] * $val_old_row['pervat'] / 100), 2);
			}else{
				$prezzo = round($val_old_row['prelis'], 2);
			}
			//Calcolo l'importo
			$importo = round($val_old_row['quanti'] * $prezzo, 2);

			$tot += $importo;
		}
		return '&euro; ' .$tot ;
	}

    function selectMunicipalities($cerca,$val) {
        global $gTables;
        if ($val >= 1) {
            $municipalities = gaz_dbi_get_row($gTables['municipalities'], 'id',  $val);
            echo '<input type="submit" tabindex="999" value="'.$municipalities['name'].'" name="change" onclick="this.form.hidden_req.value=\'change_municipalities\';" title="Cambia comune">';
            echo '<input type="hidden" name="search_municipalities" id="search_municipalities" value="' . $municipalities['name'] . '" />';
        } else {
            echo '<input type="text" name="search_municipalities" id="search_municipalities" placeholder=" cerca" tabindex="1" value="' . $cerca . '"  maxlength="16" />';
        }
        echo '<input type="hidden" id="id_municipalities" name="id_municipalities" value="'.$val.'">';
    }

    function selectPartner($cerca,$val,$mascli) {
        global $gTables;
        if ($val >= 1) {
            $partner = gaz_dbi_get_row($gTables['clfoco'], 'codice',  $val);
            $clifor=(substr($val,0,3)==$mascli)?'Cliente: ':'Fornitore: ';
            echo '<input type="submit" tabindex="999" value="'.$clifor.$partner['descri'].'" name="change" onclick="this.form.hidden_req.value=\'change_partner\';" title="Cambia cliente/fornitore">';
            echo '<input type="hidden" name="search_partner" id="search_partner" value="' . $cerca . '" />';
        } else {
            echo '<input type="text" name="search_partner" id="search_partner" placeholder=" cerca" tabindex="1" value="' . $cerca . '"  maxlength="16" />';
        }
        echo '<input type="hidden" id="id_partner" name="id_partner" value="'.$val.'">';
    }

    function amout_to_paymov($clfoco,$amount) {
        // serve per generare un array contenente le scadenze delle ultime fatture attive/passive fino a "coprire" l'importo ($amout) passato come referenza
        global $gTables;
    }

    function delete_all_partner_paymov($clfoco) {
        // serve per ELIMINARE TUTTE LE PARTITE le cui testate sono riferite al cliente/fornitore passato a riferimento
        global $gTables;
        $sql_del_paymov = "DELETE " . $gTables['paymov'] . " FROM " . $gTables['paymov'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON (" . $gTables['paymov'] . ".id_rigmoc_doc = " . $gTables['rigmoc'] . ".id_rig OR " . $gTables['paymov'] . ".id_rigmoc_pay = " . $gTables['rigmoc'] . ".id_rig) WHERE ".$gTables['rigmoc'] .".codcon = ".$clfoco;
        gaz_dbi_query($sql_del_paymov);
    }

    function get_openable_schedule($clfoco,$amount,$admin_aziend) {
        // passando il codice cliente/fornitore e l'importo da riaprire ritorna una array con tutti i dati e i riferimenti per riaprire/ricostruire lo scadenzario basandosi sugli ultimi documenti di vendita/acquisti, le loro condizioni di pagamento
        global $gTables;
        $partner = gaz_dbi_get_row($gTables['clfoco'], 'codice',  $clfoco);
        $p = gaz_dbi_get_row($gTables['pagame'], 'codice', $partner['codpag']);
        require("../../library/include/calsca.inc.php");
        $da=(substr($clfoco,0,3)==$admin_aziend['mascli'])?'D':'A';
        // riprendo tutti i movimenti di apertura (documenti) senza considerare le chiusure/aperture di fine anno
        $sqlquery = "SELECT  " . $gTables['rigmoc'] . ".id_rig AS id_rigmoc_doc, CONCAT(SUBSTR(" . $gTables['tesmov'] . ".datreg,1,4)," . $gTables['tesmov'] . ".regiva," . $gTables['tesmov'] . ".seziva, LPAD(" . $gTables['tesmov'] . ".protoc,9,'0')) AS id_tesdoc_ref ," . $gTables['pagame'] . ".*," . $gTables['tesmov'] . ".datdoc AS datfat," . $gTables['rigmoc'] . ".import, CONCAT(" . $gTables['tesmov'] . ".descri,' n.'," . $gTables['tesmov'] . ".numdoc,' del ', DATE_FORMAT(" . $gTables['tesmov'] . ".datdoc,'%d/%m/%Y')) AS descridoc
            FROM " . $gTables['rigmoc'] . "
            LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes
            LEFT JOIN ".$gTables['tesdoc']." ON ".$gTables['tesmov'].".id_doc = ".$gTables['tesdoc'].".id_tes
            LEFT JOIN ".$gTables['pagame']." ON ".$gTables['tesdoc'].".pagame = ".$gTables['pagame'].".codice
            WHERE codcon = ".$clfoco." AND darave = '".$da."' AND ".$gTables['tesmov'].".caucon <> 'CHI' AND ".$gTables['tesmov'].".caucon <> 'APE' ORDER BY ".$gTables['tesmov'].".datreg DESC";
        $rs = gaz_dbi_query($sqlquery);
        $acc=[];
        while ($r = gaz_dbi_fetch_array($rs)) {
            if (empty($r['codice'])){
                $rate = CalcolaScadenze($r['import'], substr($r['datfat'], 8, 2), substr($r['datfat'], 5, 2), substr($r['datfat'], 0, 4), $p['tipdec'], $p['giodec'], $p['numrat'], $p['tiprat'], $p['mesesc'], $p['giosuc']);
            } else {
                $rate = CalcolaScadenze($r['import'], substr($r['datfat'], 8, 2), substr($r['datfat'], 5, 2), substr($r['datfat'], 0, 4), $r['tipdec'], $r['giodec'], $r['numrat'], $r['tiprat'], $r['mesesc'], $r['giosuc']);
            }
            foreach($rate['import'] as $k=>$v){
                $acc[$rate['anno'][$k].$rate['mese'][$k].$rate['giorno'][$k].$r['id_tesdoc_ref']]=array('amount'=> $v,'expiry'=>$rate['anno'][$k].'-'.$rate['mese'][$k].'-'.$rate['giorno'][$k],'data'=>$r);
            }
        }
        krsort($acc); // ordino per datascadenza-riferimento descrescenti
        $rest=$amount;
        $accret=[];
		$n=0;
        foreach($acc as $v){ // ciclo fino a quando non ho esaurito tutto l'importo da attribuire
            if ($rest>=0.01){
                if ($rest>=$v['amount']){ // posso assegnare tutto il valore
                    $accret[$v['data']['id_tesdoc_ref'].gaz_format_date($v['expiry'],false,3)]=array('descridoc'=>$v['data']['descridoc'],'id_tesdoc_ref'=>$v['data']['id_tesdoc_ref'],'id_rigmoc_doc'=>$v['data']['id_rigmoc_doc'],'amount'=>round($v['amount'],2),'expiry'=>$v['expiry']);
                    $rest-=$v['amount'];
                } elseif ($rest<$v['amount']){ // posso assegnare tutto il valore
                    $accret[$v['data']['id_tesdoc_ref'].gaz_format_date($v['expiry'],false,3)]=array('descridoc'=>$v['data']['descridoc'],'id_tesdoc_ref'=>$v['data']['id_tesdoc_ref'],'id_rigmoc_doc'=>$v['data']['id_rigmoc_doc'],'amount'=>round($rest,2),'expiry'=>$v['expiry']);
                    $rest=0;
                }
            } else { break; }
			$n++;
        }
        krsort($accret); // ordino per datascadenza-riferimento descrescenti
        return $accret;
    }

  function removeSignature($string, $filename) {
    $string = substr($string, strpos($string, '<?xml '));
    preg_match_all('/<\/.+?>/', $string, $matches, PREG_OFFSET_CAPTURE);
    $lastMatch = end($matches[0]);
    // trovo l'ultimo carattere del tag di chiusura per eliminare la coda
    $f_end = $lastMatch[1]+strlen($lastMatch[0]);
    $string = substr($string, 0, $f_end);
    // elimino le sequenze di caratteri aggiunti dalla firma (ancora da testare approfonditamente)
    $string = preg_replace ('/[\x{0004}]{1}[\x{0082}]{1}[\x{0001}\x{0002}\x{0003}\x{0004}]{1}[\s\S]{1}/i', '', $string);
    $string = preg_replace ('/[\x{0004}]{1}[\x{0081}]{1}[\s\S]{1}/i', '', $string);
    $string = preg_replace ('/[\x{0004}]{1}[A-Za-z]{1}/i', '', $string); // per eliminare tag finale
    return $string;
  }

  public $doc;
  public $xpath;
  function getInvoiceContent($fattura_elettronica_original_name ) {
		$p7mContent = @file_get_contents($fattura_elettronica_original_name);
		$invoiceContent = $this->removeSignature($p7mContent,$fattura_elettronica_original_name);
		$this->doc = new DOMDocument;
		$this->doc->preserveWhiteSpace = false;
		$this->doc->formatOutput = true;
		$this->doc->loadXML(mb_convert_encoding($invoiceContent, 'UTF-8', mb_list_encodings()));
		$this->xpath = new DOMXpath($this->doc);
  }

}
class gazBackup extends MySQLDump {

  public function gazDataDir($zipname)
  {
    $rootPath = realpath(DATA_DIR.'files');
    $zip = new ZipArchive();
    $zip->open(DATA_DIR.'files/tmp/'.$zipname.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFile(DATA_DIR.'files/tmp/tmp-backup.sql',$zipname.'.sql');
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $name => $file) {
      if (!$file->isDir()) { // è un file
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);
        if (preg_match("/tmp|backups|htaccess/", $relativePath)==false) {
          $zip->addFile($filePath, $relativePath);
        }
      } else { // è una dir
        $end2 = substr($file,-2);
        if ($end2 == "/.") {
          $folder = substr($file, 0, -2);
          $zip->addEmptyDir($folder);
        }
      }
    }
    $zip->close();
	  unlink(DATA_DIR.'files/tmp/tmp-backup.sql');
  }
}
?>
