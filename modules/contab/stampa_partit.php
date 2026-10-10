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
require("../../library/include/electronic_invoice.inc.php");
$cleanFAE = new cleaningElectronicInvoice;

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}
if (!isset($_GET['codice']) ||
    !isset($_GET['regini']) ||
    !isset($_GET['regfin']) ) {
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
}
if (!isset($_GET['codfin'])) {
	$_GET['codfin'] = $_GET['codice'];
}

require("../../config/templates/report_template.php");
$gioini = substr($_GET['regini'],0,2);
$mesini = substr($_GET['regini'],2,2);
$annini = substr($_GET['regini'],4,4);
$utsini= mktime(0,0,0,$mesini,$gioini,$annini);
$giofin = substr($_GET['regfin'],0,2);
$mesfin = substr($_GET['regfin'],2,2);
$annfin = substr($_GET['regfin'],4,4);
$utsfin= mktime(0,0,0,$mesfin,$giofin,$annfin);
$dataini = date("Ymd",$utsini);
$datafin = date("Ymd",$utsfin);
$descrDataini = date("d-m-Y",$utsini);
$descrDatafin = date("d-m-Y",$utsfin);
$luogo_data=$admin_aziend['citspe'].", lì ";
$gazTimeFormatter->setPattern('dd MMMM yyyy');
if (isset($_GET['ds'])) {
  $giosta = substr($_GET['ds'],0,2);
  $messta = substr($_GET['ds'],2,2);
  $annsta = substr($_GET['ds'],4,4);
  $utssta= mktime(0,0,0,$messta,$giosta,$annsta);
  $utsstaobj = new DateTime('@'.$utssta);
  $gazTimeFormatter->setPattern('dd MMMM yyyy');
  $luogo_data .= ucwords($gazTimeFormatter->format($utsstaobj));
} else {
  $luogo_data .=ucwords($gazTimeFormatter->format(new DateTime()));
}
$where = (intval($_GET['idg']) >= 1 ? $gTables['clfoco'] . ".id_customer_group = ".intval($_GET['idg'])." AND " : '' ).
        " codcon BETWEEN ".intval($_GET['codice'])." AND ".intval($_GET['codfin'])." AND".
         " datreg BETWEEN '".$dataini."' AND '".$datafin."'";
$what = $gTables['rigmoc'].".*, ".$gTables['tesmov'].".id_tes, ".
        $gTables['tesmov'].".descri AS tesdes, ".$gTables['tesmov'].".caucon, ".$gTables['tesmov'].".datreg, ".$gTables['tesmov'].".seziva, ".
        $gTables['tesmov'].".datdoc, ".$gTables['tesmov'].".numdoc, ".$gTables['tesmov'].".protoc, ".
        $gTables['clfoco'].".codice, ".$gTables['clfoco'].".descri, t_part.descri AS partner ";
$table = $gTables['rigmoc']." LEFT JOIN ".$gTables['tesmov']." ON (".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes)
                              LEFT JOIN ".$gTables['clfoco']." ON (".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice)
                              LEFT JOIN ".$gTables['clfoco']." AS t_part ON (".$gTables['tesmov'].".clfoco = t_part.codice)";
$result = gaz_dbi_dyn_query ($what, $table,$where,"codcon ASC, datreg ASC, ".$gTables['tesmov'].".id_tes");
$item_head = array('top'=>array(array('lun' => 80,'nam'=>'Descrizione'),
                                array('lun' => 25,'nam'=>'Numero Conto')
                               )
                   );
$title = array('luogo_data'=>$luogo_data,
               'title'=>"PARTITARIO  dal ".$descrDataini." al ".$descrDatafin,
               'hile'=>array(   array('lun' => 18,'nam'=>'Data Reg.'),
                                array('lun' =>108,'nam'=>'Descrizione (Dati del documento)'),
                                array('lun' => 20,'nam'=>'Dare'),
                                array('lun' => 20,'nam'=>'Avere'),
                                array('lun' => 20,'nam'=>'SALDO')
                            )
              );
$aRiportare = array('top'=>array(array('lun' => 166,'nam'=>'da riporto : '),
                           array('lun' => 20,'nam'=>'')
                           ),
                    'bot'=>array(array('lun' => 166,'nam'=>'a riportare : '),
                           array('lun' => 20,'nam'=>'')
                           )
                    );

// INIZIO RICERCA APERTURA PRECENDENTE
$rs_last_opening = gaz_dbi_dyn_query("YEAR(datreg) AS anno, MONTH(datreg) AS mese, DAY(datreg) AS giorno", $gTables['tesmov'], "caucon = 'APE'", "datreg DESC", 0, 1);
$last_opening = gaz_dbi_fetch_array($rs_last_opening); // trovo la data dell'ultima apertura
if ($last_opening) {
	$last_opening_year = $last_opening['anno'];
	$last_opening_month = $last_opening['mese'];
	$last_opening_day = $last_opening['giorno'];
} else {
	$last_opening_year = '2004';
	$last_opening_month = '1';
	$last_opening_day = '27';
}
$date_last_opening = sprintf("%04d%02d%02d", $last_opening_year, $last_opening_month, $last_opening_day);
// FINE RICERCA APERTURA PRECENDENTE


$pdf = new Report_template('P','mm','A4',true,'UTF-8',false,true);
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(51);
$pdf->SetFooterMargin(22);
$config = new Config;
$ctrlConto = '';
$totdare = 0.00;
$totavere = 0.00;
$movSaldo = 0.00;
$rf=false;
$nr=0;
$pdf->SetFillColor(238,238,238);
while ($row = gaz_dbi_fetch_array($result)) {
  $nr++;
  $rf=$nr%2;
	$datadoc = substr($row['datdoc'],8,2).'-'.substr($row['datdoc'],5,2).'-'.substr($row['datdoc'],0,4);
	$datareg = substr($row['datreg'],8,2).'-'.substr($row['datreg'],5,2).'-'.substr($row['datreg'],0,4);
	$pdf->setRiporti($aRiportare);
	if ($ctrlConto != $row['codcon']) {
		if (!empty($ctrlConto)) {
			$pdf->Cell(126,4,'TOTALI DARE/AVERE PER IL PERIODO dal '.$descrDataini.' al '.$descrDatafin.' (saldo '.gaz_format_number($totdare-$totavere).') ',1,0,'R');
			$pdf->Cell(20,4,gaz_format_number($totdare),1,0,'R');
			$pdf->Cell(20,4,gaz_format_number($totavere),1,0,'R');
			$pdf->Cell(20,4,'',1,1,'C');
		}
		$totdare = 0.00;
		$totavere = 0.00;
		$movSaldo = 0.00;
		if (!empty($ctrlConto)) {
			$pdf->SetFont('helvetica','B',8);
			$pdf->Cell($aRiportare['top'][0]['lun'],4,'SALDO al '.$descrDatafin.' : ',1,0,'R');
			$pdf->Cell($aRiportare['top'][1]['lun'],4,$aRiportare['top'][1]['nam'],1,0,'R');
		}
		$pdf->SetFont('helvetica','',7);
		$aRiportare['top'][1]['nam'] = 0;
		$aRiportare['bot'][1]['nam'] = 0;
		$item_head['bot']= array(array('lun' => 80,'nam'=>$row['descri']),
			array('lun' => 25,'nam'=>$row['codcon'])
		);
		$pdf->setItemGroup($item_head);
		$pdf->setRiporti('');
		$pdf->AddPage('P',$config->getValue('page_format'));
		// INIZIO RICERCA SALDO PRECEDENTE
		$query = "SELECT SUM((CASE WHEN darave='D' THEN 1 ELSE -1 END)*import) AS saldo" .
			 " FROM " . $gTables['rigmoc'] . " LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes=" . $gTables['tesmov'] . ".id_tes" .
			 " WHERE codcon = " . $row['codcon'] . " AND datreg>='" . $date_last_opening . "' AND datreg<'" . $dataini . "'";
		$rs_extreme_accont = gaz_dbi_query($query);
		$extreme_account = gaz_dbi_fetch_array($rs_extreme_accont);
		if ($extreme_account) {
			$movSaldo = $extreme_account['saldo'];
		}
		// FINE RICERCA SALDO PRECEDENTE
		if ($movSaldo && abs($movSaldo)>=0.01) {
			$pdf->Cell(166,4,'SALDO PRECEDENTE',1,0,'R');
			$pdf->Cell(20,4,gaz_format_number($movSaldo),1,1,'R');
		}
	}
	if ($row['darave'] == 'D'){
		$totdare+= $row['import'];
		$movSaldo += $row['import'];
		$dare = gaz_format_number($row['import']);
		$avere = '';
	} else {
		$totavere+= $row['import'];
		$movSaldo -= $row['import'];
		$avere = gaz_format_number($row['import']);
		$dare = '';
	}
	$aRiportare['top'][1]['nam'] = gaz_format_number($movSaldo);
	$aRiportare['bot'][1]['nam'] = gaz_format_number($movSaldo);
	$pdf->Cell(18,4,$datareg,1,0,'C',$rf);
	if ((!empty($row['partner']) || !empty($row['numdoc'])) && $row['caucon'] != 'APE' && $row['caucon'] != 'CHI'){
		$pdf->SetFont('helvetica','',6);
		$row['tesdes'].=' ('.$row['partner'];
		if (!empty($row['numdoc'])){
			$row['tesdes'] .= ' n.'.$row['numdoc'].' del '.$datadoc;
			if ($row['protoc']>0) {
				$row['tesdes'] .= ' sez.'.$row['seziva'].' p.'.$row['protoc'];
			}
		}
		$row['tesdes'].=')';
	}
	$pdf->Cell(108,4,$row['tesdes'],'LTB',0,'L',$rf,'',1);
	$pdf->SetFont('helvetica','',7);
	$pdf->Cell(20,4,$dare,1,0,'R',$rf);
	$pdf->Cell(20,4,$avere,1,0,'R',$rf);
	$pdf->Cell(20,4,gaz_format_number($movSaldo),1,1,'R',$rf);
	$ctrlConto = $row['codcon'];
}

$pdf->Cell(126,4,'TOTALI DARE/AVERE PER IL PERIODO dal '.$descrDataini.' al '.$descrDatafin.' (saldo '.gaz_format_number($totdare-$totavere).') ',1,0,'R');
$pdf->Cell(20,4,gaz_format_number($totdare),1,0,'R');
$pdf->Cell(20,4,gaz_format_number($totavere),1,0,'R');
$pdf->Cell(20,4,'',1,1,'C');

$pdf->SetFont('helvetica','B',8);
$pdf->Cell($aRiportare['top'][0]['lun'],4,'SALDO al '.$descrDatafin.' : ',1,0,'R');
$pdf->Cell($aRiportare['top'][1]['lun'],4,$aRiportare['top'][1]['nam'],1,0,'R');
$pdf->setRiporti('');
$title = array('luogo_data'=>$luogo_data,
               'hile'=>[]
              );
$pdf->setVars($admin_aziend,$title);
$item_head['top']= [['lun' => 80,'nam'=>'Allegato riferito al partitario:'],['lun' => 25,'nam'=>'Conto']];
$pdf->setItemGroup($item_head);

$string_docattach = $_GET['docattach'];
$a_docattach = explode(',', $string_docattach);
$a_docattach = array_map('intval', $a_docattach);
foreach($a_docattach as $v){
  // riprendo la testata documento a partire dalla sua referenza di contabilizzazione
  $tesdoc = $v >= 1 ? gaz_dbi_get_row($gTables['tesdoc'],'id_con',$v): false;
  if ($tesdoc) {
    $xml = new DOMDocument();
    $xmlString = '';
    $fae_flux = gaz_dbi_get_row($gTables['fae_flux'],'id_tes_ref',$tesdoc['id_tes']);
    // riprendo eventuali altre testate (es. fattura differita con più di un ddt)
    $testate = gaz_dbi_dyn_query("*", $gTables['tesdoc']," tipdoc LIKE '" .$tesdoc['tipdoc']. "' AND seziva = " .$tesdoc['seziva']. " AND YEAR(datfat)=".substr($tesdoc['datfat'],0,4)." AND protoc = " .$tesdoc['protoc'],'datemi ASC, numdoc ASC, id_tes ASC');
    if (substr($tesdoc['tipdoc'],0,1) == 'F' ) { // FATTURE VENDITE
      if ($fae_flux && $fae_flux['flux_status']=='PI'){ // se è un file verso PA firmato lo riprendo dalla dir come tale
        $xmlString=file_get_contents(DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' . $tesdoc['filename_ret'], true);
      } else { // ... per gli altri prendo quelli che ho ricreati al volo da DB
        $xmlString=create_XML_invoice($testate,$gTables,'rigdoc',false,'Fattura');
      }
    } elseif (substr($tesdoc['tipdoc'],0,1) == 'A' ) { // FATTURE ACQUISTI
      // provengono da file con tracciato che potrebbe essere firmato per cui andrà ripulito
      $fattxml = DATA_DIR . 'files/' . $admin_aziend['codice'] . '/' .$tesdoc['id_tes'].'.inv';
      // Verifica se il file esiste ed è effettivamente un file
      if (!file_exists($fattxml) || !is_file($fattxml)) {
        continue;
      } else {
        $p7mContent = file_get_contents($fattxml);
        $p7mContent = $cleanFAE->recursiveDecodeContent($p7mContent,$fattxml);
        $cert = @tempnam(DATA_DIR . 'files/tmp/', 'pem');
        $retn = openssl_pkcs7_verify($fattxml, PKCS7_NOVERIFY, $cert);
        unlink($cert);
        if (!$retn) {
          echo "Error verifying PKCS#7 signature in {$fattxml}";
          return false;
        }

        $fatt = $cleanFAE->extractDER($fattxml);
        if (empty($fatt)) {
          $test = @base64_decode(file_get_contents($fattxml));
          // Salto lo header (INDISPENSABILE perché la regexp funzioni sempre)
          if (strpos($test, 'FatturaElettronicaSemplificata') !== FALSE) {
            if (preg_match('#(<[^>]*FatturaElettronicaSemplificata.*</[^>]*FatturaElettronicaSemplificata>)#', substr($test, 54), $gregs)) {
              $fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
            }
          } else {
            if (preg_match('#(<[^>]*FatturaElettronica.*</[^>]*FatturaElettronica>)#', substr($test, 54), $gregs)) {
              $fatt = '<'.'?'.'xml version="1.0"'.'?'.'>' . $gregs[1]; // RECUPERO INTESTAZIONE XML
            }
          }
        }

        if (!empty($fatt)) {
          $xmlString = $fatt;
        } else {
          $xmlString = $cleanFAE->removeSignature($p7mContent);
        }
      }
    }

    if (empty(trim($xmlString))) continue;

    $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOERROR);
    if (!$xml) continue;

    $cedente = $xml->FatturaElettronicaHeader->CedentePrestatore;
    $cessionario = $xml->FatturaElettronicaHeader->CessionarioCommittente;
    $datiGenerali = $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento;
    $beniServizi = $xml->FatturaElettronicaBody->DatiBeniServizi;
    $pagamenti = $xml->FatturaElettronicaBody->DatiPagamento;

    $denominazioneCedente = $cedente->DatiAnagrafici->Anagrafica->Denominazione ?? ($cedente->DatiAnagrafici->Anagrafica->Cognome . ' ' . $cedente->DatiAnagrafici->Anagrafica->Nome);
    $pIvaCedente = $cedente->DatiAnagrafici->IdFiscaleIVA->IdCodice;

    $indCedente = '';
    if (isset($cedente->Sede)) {
        $indCedente = "<br/><span style=\"color:#555555;\">" . htmlspecialchars($cedente->Sede->Indirizzo) . " " . htmlspecialchars($cedente->Sede->NumeroCivico ?? '') . ", " . htmlspecialchars($cedente->Sede->CAP) . " " . htmlspecialchars($cedente->Sede->Comune) . " (" . htmlspecialchars($cedente->Sede->Provincia) . ")</span>";
    }

    $denominazioneCessionario = $cessionario->DatiAnagrafici->Anagrafica->Denominazione ?? ($cessionario->DatiAnagrafici->Anagrafica->Cognome . ' ' . $cessionario->DatiAnagrafici->Anagrafica->Nome);
    $pIvaCessionario = $cessionario->DatiAnagrafici->IdFiscaleIVA->IdCodice;

    $indCessionario = '';
    if (isset($cessionario->Sede)) {
        $indCessionario = "<br/><span style=\"color:#555555;\">" . htmlspecialchars($cessionario->Sede->Indirizzo) . " " . htmlspecialchars($cessionario->Sede->NumeroCivico ?? '') . ", " . htmlspecialchars($cessionario->Sede->CAP) . " " . htmlspecialchars($cessionario->Sede->Comune) . " (" . htmlspecialchars($cessionario->Sede->Provincia) . ")</span>";
    }

    $htmlContent = '
    <table border="0" cellspacing="0" cellpadding="12" width="100%" style="border: 1px solid #888888;" bgcolor="#fbfdfe">
    <tr>
    <td>
    <table border="1" cellpadding="3" cellspacing="0" width="100%">
        <tr>
            <td width="50%" bgcolor="#fafafa">
                <strong>CEDENTE / PRESTATORE</strong><br/>
                ' . htmlspecialchars($denominazioneCedente) . '<br/>
                P.IVA / CF: ' . htmlspecialchars($pIvaCedente) . '
                ' . $indCedente . '
            </td>
            <td width="50%" bgcolor="#fafafa">
                <strong>CESSIONARIO / COMMITTENTE</strong><br/>
                ' . htmlspecialchars($denominazioneCessionario) . '<br/>
                P.IVA / CF: ' . htmlspecialchars($pIvaCessionario) . '
                ' . $indCessionario . '
            </td>
        </tr>
    </table>

    <h3 style="color:#003366; margin-top:10px;">Dati Documento</h3>
    <table border="1" cellpadding="3" cellspacing="0" width="100%">
        <tr bgcolor="#f2f2f2">
            <th width="25%"><strong>Tipo Documento</strong></th>
            <th width="25%"><strong>Numero</strong></th>
            <th width="25%"><strong>Data</strong></th>
            <th width="25%"><strong>Valuta</strong></th>
        </tr>
        <tr>
            <td>' . htmlspecialchars($datiGenerali->TipoDocumento) . '</td>
            <td>' . htmlspecialchars($datiGenerali->Numero) . '</td>
            <td>' . htmlspecialchars($datiGenerali->Data) . '</td>
            <td>' . htmlspecialchars($datiGenerali->Divisa) . '</td>
        </tr>
    </table>
    <p></p>
    <h3 style="color:#003366; margin-top:10px;">Dettaglio Linee Beni e Servizi</h3>
    <table border="1" cellpadding="3" cellspacing="0" width="100%">
        <thead>
            <tr bgcolor="#f2f2f2">
                <th width="6%"><strong>Num.</strong></th>
                <th width="44%"><strong>Descrizione</strong></th>
                <th width="8%" align="right"><strong>Qta</strong></th>
                <th width="12%" align="right"><strong>Prezzo Unit.</strong></th>
                <th width="6%" align="center"><strong>UM</strong></th>
                <th width="12%" align="right"><strong>Prezzo Tot.</strong></th>
                <th width="12%" align="center"><strong>Aliquota / Nat.</strong></th>
            </tr>
        </thead>
        <tbody>';
    foreach ($beniServizi->DettaglioLinee as $linea) {
        $qta = (string)$linea->Quantita;
        $um = (string)$linea->UnitaMisura;
        $prezzoUnit = (float)$linea->PrezzoUnitario;
        $prezzoTot = (float)$linea->PrezzoTotale;

        // CORREZIONE 1: Se la quantità è vuota o zero (righe di sole note), lasciamo lo spazio vuoto
        $mostraQta = ($qta != "" && (float)$qta > 0) ? number_format((float)$qta, 2, ',', '.') : '';
        $mostraUM = ($um != "") ? htmlspecialchars($um) : '';

        // CORREZIONE 2: Se il prezzo unitario o totale è zero ed è una riga descrittiva pura, puliamo l'output
        $mostraPrezzoUnit = ($prezzoUnit == 0 && $qta == "") ? '' : number_format($prezzoUnit, 2, ',', '.');
        $mostraPrezzoTot = ($prezzoTot == 0 && $qta == "") ? '0,00' : number_format($prezzoTot, 2, ',', '.');

        // CORREZIONE 3: Se manca la percentuale IVA, cerchiamo il codice Natura esenzione (es. N1 per i bolli)
        $iva = (string)$linea->AliquotaIVA;
        $natura = (string)$linea->Natura;
        $mostraIVA = ($iva != "") ? number_format((float)$iva, 2, ',', '.') . '%' : htmlspecialchars($natura);

        $htmlContent .= '
            <tr>
                <td width="6%" align="center">' . htmlspecialchars($linea->NumeroLinea) . '</td>
                <td width="44%">' . htmlspecialchars($linea->Descrizione) . '</td>
                <td width="8%" align="right">' . $mostraQta . '</td>
                <td width="12%" align="right">' . $mostraPrezzoUnit . '</td>
                <td width="6%" align="center">' . $mostraUM . '</td>
                <td width="12%" align="right"><strong>' . $mostraPrezzoTot . '</strong></td>
                <td width="12%" align="center">' . $mostraIVA . '</td>
            </tr>';
    }
    $totaleFatturaValore = number_format((float)$datiGenerali->ImportoTotaleDocumento, 2, ',', '.');
    $htmlContent .= '
            <tr bgcolor="#edf4fc">
                <td colspan="5" align="right"><strong>TOTALE FATTURA:</strong></td>
                <td colspan="2" align="right"><strong>€ ' . $totaleFatturaValore . '</strong></td>
            </tr>
        </tbody>
    </table>';
    $dettaglioPagamento = $pagamenti->DettaglioPagamento ?? null;
    if ($dettaglioPagamento) {
        $htmlContent .= '
    <p></p>
        <h3 style="color:#003366; margin-top:10px;">Dati di Pagamento</h3>
        <table border="1" cellpadding="3" cellspacing="0" width="100%">
            <thead>
                <tr bgcolor="#f2f2f2">
                    <th width="20%"><strong>Modalità Pagamento</strong></th>
                    <th width="40%"><strong>Dettagli</strong></th>
                    <th width="20%" align="center"><strong>Scadenza</strong></th>
                    <th width="20%" align="right"><strong>Importo</strong></th>
                </tr>
            </thead>
            <tbody>';
        foreach ($pagamenti->DettaglioPagamento as $detPag) {
            $impPag = number_format((float)$detPag->ImportoPagamento, 2, ',', '.');
            $htmlContent .= '
                <tr>
                    <td width="20%">' . htmlspecialchars($detPag->ModalitaPagamento) . '</td>
                    <td width="40%">' . htmlspecialchars($detPag->Beneficiario ?? '') . '</td>
                    <td width="20%" align="center">' . htmlspecialchars($detPag->DataScadenzaPagamento ?? '') . '</td>
                    <td width="20%" align="right">€ ' . $impPag . '</td>
                </tr>';
        }
        $htmlContent .= '
            </tbody>
        </table>';
    }
    $htmlContent .= '</td></tr></table>';
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 7);
    $pdf->writeHTML($htmlContent, true, false, true, false, '');
  }
}
// FINE STAMPA ALLEGATI
$pdf->Output();
?>
