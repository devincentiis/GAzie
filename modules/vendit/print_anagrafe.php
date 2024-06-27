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

$admin_aziend = checkAdmin();

$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data = $admin_aziend['citspe'] . ", lì " . ucwords($gazTimeFormatter->format(new DateTime()));

$form['id_agente'] = (isset($_GET['id_agente']) ? intval($_GET['id_agente']) : '');
$form['clifor'] = (isset($_GET['clifor']) ? substr($_GET['clifor'],-1) : '');
$orderby="ragioneSociale";
if (empty($form['id_agente']) && empty($form['clifor'])) { // mancano i dati per la selezione
   alert("Niente da stampare");
   tornaPaginaPrecedente();
} elseif (!empty($form['id_agente'])) {  // vogliamo la stampa dei clienti di un agente
   $where = "id_agente=" . $form['id_agente'] . " AND clfoco.codice BETWEEN " . $admin_aziend['mascli'] . "000001 AND " . $admin_aziend['mascli'] . "999999";
   $titolo = "CLIENTI DELL'AGENTE: " . queryNomeAgente($form['id_agente'], $gTables);
} elseif (isset($_GET['order'])&&$_GET['order']=='ZONE') {  // vogliamo la stampa dei clienti in ordine di zona
   $mastro = ($form['clifor'] == 'C' ? $admin_aziend['mascli'] : $admin_aziend['masfor']);
   $where = "clfoco.codice BETWEEN " . $mastro . "000001 AND " . $mastro . "999999";
   $titolo = "CLIENTI IN ORDINE DI ZONA ";
   $orderby="regions.id, provinces.id";
} else {   // vogliamo la stampa dell'anagrafica
   $mastro = ($form['clifor'] == 'C' ? $admin_aziend['mascli'] : $admin_aziend['masfor']);
   $where = "clfoco.codice BETWEEN " . $mastro . "000001 AND " . $mastro . "999999";
   $titolo = ($form['clifor'] == 'C' ? 'Elenco Clienti' : 'Elenco Fornitori');
}

require("../../config/templates/report_template.php");
$title = array('luogo_data'=>$luogo_data,
               'title'=>$titolo,
    'hile' => array(/* array('lun' => 45, 'nam' => 'Cliente'), */
        array('lun' => 60, 'nam' => 'Ragione Sociale'),
        array('lun' => 70, 'nam' => 'Indirizzo / Email'),
        array('lun' => 65, 'nam' => 'Telefono / Pagamento')
				)
              );
//$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(39);
$pdf->SetFooterMargin(18);
$config = new Config;
$pdf->AddPage();
$pdf->SetFont('helvetica','',9);

$rs = gaz_dbi_dyn_query("*, CONCAT(ragso1,SPACE(1),ragso2) AS ragioneSociale, CONCAT (indspe, SPACE(1), citspe, ' (',prospe,')') AS sede, CONCAT(telefo, SPACE(1), cell, SPACE(1), fax) AS telefono, e_mail, pariva, codfis, pagame.descri AS payment", $gTables['clfoco'] . " clfoco
LEFT JOIN " . $gTables['pagame'] . " pagame ON clfoco.codpag = pagame.codice
LEFT JOIN " . $gTables['anagra'] . " anagra ON anagra.id = clfoco.id_anagra
LEFT JOIN " . $gTables['provinces'] . " provinces ON anagra.prospe = provinces.abbreviation
LEFT JOIN " . $gTables['regions'] . " regions ON provinces.id_region = regions.id", $where, $orderby);

$pdf->SetFillColor(240, 240, 240);
while ($partner = gaz_dbi_fetch_array($rs)) {
   $pdf->Cell(60, 4, $partner['ragioneSociale'], 'LT', 0, 'L', true, '', 1);
   $pdf->Cell(70, 4, $partner['sede'], 'T', 0, 'R', true, '', 1);
   $pdf->Cell(65, 4, $partner["telefono"],'TR', 1, 'C', true, '', 1);
   $pdf->SetFont('helvetica','',7);
   $pdf->Cell(60, 4, $partner['sedleg'], 'LB', 0, 'L', false, '', 1);
   $pdf->SetFont('helvetica','',9);
   $pdf->Cell(70, 4, $partner["e_mail"], 'B', 0, 'L', false, '', 1);
   $pdf->Cell(65, 4, 'Pagam: '.$partner['payment'], 'BR', 1, 'R', false, '', 1);
}

$pdf->Output();

function queryNomeAgente($id_agente, $gTables) {
   $retVal = "";
   $rs = gaz_dbi_dyn_query("anagra.ragso1,anagra.ragso2", $gTables['agenti'] . " agenti LEFT JOIN " . $gTables['clfoco'] . " clfoco on agenti.id_fornitore = clfoco.codice "
           . "LEFT JOIN " . $gTables['anagra'] . ' anagra ON clfoco.id_anagra = anagra.id', "agenti.id_agente=$id_agente");
//   $anagrafiche = array();
   if ($r = gaz_dbi_fetch_array($rs)) {
      $retVal = $r["ragso1"] . " " . $r["ragso2"];
   }
   return $retVal;
}

?>
