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
require('../../library/include/datlib.inc.php');
$admin_aziend = checkAdmin();

$title = '';
require('lang.'.$admin_aziend['lang'].'.php');
if ( !isset($_GET['id'])) {
    header('Location: report_assist.php');
    exit;
}
require('../../config/templates/report_template.php');

if ( isset($_GET['id']) ){
   $sql = $gTables['assist'].'.id = '.intval($_GET['id']).' ';
} else {
   $sql = $gTables['assist'].'.id > 0 ';
}
$where = $sql;

//$what = $gTables['assist'].".* ";
/*$table = $gTables['assist']." LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['assist'].".clfoco = ".$gTables['clfoco'].".codice
                              LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra ";*/

$result = gaz_dbi_dyn_query($gTables['assist'].".*,
		".$gTables['anagra'].".ragso1, ".$gTables['anagra'].".telefo, ".$gTables['anagra'].".cell, ".$gTables['anagra'].".fax, ".$gTables['clfoco'].".codice ",  $gTables['assist'].
		" LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['assist'].".clfoco = ".$gTables['clfoco'].".codice". 
		" LEFT JOIN ".$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id',
		$where, "id", $limit, $passo);

$pdf = new Report_template();
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(32);
$pdf->SetFooterMargin(20);
$config = new Config;

$row = gaz_dbi_fetch_array($result);

$html = '';
if ( $row['stato']=='aperto') {
$pdf->AddPage('P',$config->getValue('page_format'));
$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
$html .= "<span style=\"font-family: arial,helvetica,sans-serif; font-size:12px;\">";
$html .= "Nome Tecnico : <b>".$row["tecnico"]."</b><br/>";
$html .= "Cliente : <b>". $row["codice"] ." - ". $row["ragso1"]."</b><br>";
if ( $row["telefo"] ) $html .= "Telefono : <b>".$row["telefo"]."</b><br>";
if ( $row["telefo"] ) $html .= "Cellulare : <b>".$row["cell"]."</b><br>";
if ( $row["telefo"] ) $html .= "Fax : <b>".$row["fax"]."</b><br>";
$html .= "</span>";

$html .= "
	<p>
					<span style=\"font-family: arial,helvetica,sans-serif; font-size:12px;\">Il cliente consegna al centro assistenza il seguente materiale :<br />
					<strong>".$row["oggetto"]."</strong><br />
					<br />
					dichiarando i seguenti difetti, malfunzionamento o lavori da effettuare :<br />
					<strong>".$row["descrizione"]."</strong><br /><br />
					<br />";
if ( $row["info_agg"] ) {
                    $html .= "dichiarando o consegnando anche :<br />
                    <strong>".$row["info_agg"]."</strong><br />";
}
$html .= "</span>
    </p>
				<p style=\"text-align: justify;\">
					<span style=\"font-size:12px;\"><span style=\"font-family: arial,helvetica,sans-serif;\"><strong>Condizioni e termini per la presa in carico e ritiro del prodotto :</strong></span></span><br />
					&nbsp;</p>
				<ol>
					<li style=\"text-align: justify;\">
						<span style=\"font-size:12px;\"><span style=\"font-family: arial,helvetica,sans-serif;\">L&#39;intervento se in garanzia, copre esclusivamente i difetti di conformit&agrave; del prodotto acquistato presso il laboratorio, ai sensi della legge. Non sono coperti da garanziai prodotti che presentino chiari segni di manomissione o guasti causati da un&#39;uso improprio del prodotto o da agenti esterni non riconducibili a vizi e/o difetti di fabbricazione. In tal caso il laboratorio non sar&agrave;, pertanto, tenuto ad effettuare gratuitamente le riparazioni necessarie, ma potr&agrave; effettuarle, su richiesta del cliente a pagamento e secondo il preventivo che verr&agrave; fornito.</span></span><br />
						&nbsp;</li>
					<li style=\"text-align: justify;\">
						<span style=\"font-size:12px;\"><span style=\"font-family: arial,helvetica,sans-serif;\">Il cliente dichiara di essere a conoscenza che l&#39;intervento per la riparazione pu&ograve; comportare l&#39;eventuale perdita totale o parziale di programmi e dati in qualunque modo contenuti o registrati nel prodotto consegnato per la riparazione. Il laboratorio non si assume responsabili&agrave; alcuna riguardo a tale perdita, pertanto &egrave; esclusiva cura del cliente assicurarsi di aver effettuato le copie di sicurezza dei dati. A tale proposito si consiglia di richiedere al laboratorio, che provveder&agrave; a titolo oneroso, per l&#39;effettuazione dei backup di tutti i dati. In ogni caso il cliente &egrave; unico ed esclusivo responsabile di dati, informazioni e programmi contenuti o registrati in qualunque modo nel prodotto consegnato al laboratorio con particolare riferimento alla liceit&ugrave; e legittima titolarit&agrave; degli stessi.</span></span><br />
						&nbsp;</li>
					<li style=\"text-align: justify;\">
						<span style=\"font-size:12px;\"><span style=\"font-family: arial,helvetica,sans-serif;\">Salvo diversi accordi scritti, il cliente &egrave; tenuto a ritirare il prodotto recandosi presso il punto vendita secondo ti tempi indicati dal laboratorio medesimo. Nel caso in cui il cliente non ritiri il prodotto nel termine di 30gg. dalla data di riparazione, il cliente si impegna sin d&#39;ora a corrispondere al laboratorio una somma pari a 5,00 &euro; a titolo di deposito per ogni giorno di permanenza del prodotto presso il laboratorio.</span></span><br />
					</li>
				</ol><table><tr><td align=\"center\">Firma cliente</td><td align=\"center\">Firma tecnico</td></tr></table>
			";
} else {
    $intervento = str_pad($row["id"], 6, '0', STR_PAD_LEFT);
    $pdf->AddPage('P',$config->getValue('page_format'));
    $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
    $html .= "<span style=\"font-family: arial,helvetica,sans-serif; font-size:12px;\">";
    $html .= <<<END
<body style="width: 790px;">
<div style="text-align: center;">
Resoconto di intervento / codice <span style="font-weight: bold;">#$row[tipo]$intervento</span> / cliente <span style="font-weight: bold;">@$row[ragso1]</span><br>
</div>
<br>
<table style="text-align: left; width: 540px;" border="1" cellpadding="5" cellspacing="0">
<tbody>
<tr>
<td style="vertical-align: top; width: 95%;"><small>Oggetto dell'intervento</small><br>
<div style="text-align: right;">$row[oggetto]<br>
</div>
</td>
</tr>
<tr>
<td style="vertical-align: top;"><small>Descrizione del problema</small><br>
<div style="text-align: right;">$row[descrizione]<br>
</div>
</td>
</tr>
<tr>
<td style="vertical-align: top;"><small>Tecnico</small>
<div style="text-align: right;">$row[tecnico] </div>
</td>
</tr>
</tbody>
</table>
<br>
<br>
<table style="text-align: left; width: 540px; height: 180px;" border="1" cellpadding="5" cellspacing="0">
<tbody>
<tr>
<td style="vertical-align: top;"><small>Dettaglio attività svolte</small><br>
<div style="text-align: justify;">$row[soluzione]<br>
</div>
</td>
</tr>
</tbody>
</table>
<br>
<br>
<table class="MsoNormalTable" style="border: medium none ; border-collapse: collapse;" border="1" cellpadding="0" cellspacing="0">
<tbody>
<tr style="height: 78.8pt;">
<td style="border: 1pt solid windowtext; padding: 0cm 3.5pt; width: 282.5pt; height: 78.8pt;">
<table class="MsoNormalTable" style="border: medium none ; border-collapse: collapse;" border="1" cellpadding="0" cellspacing="0">
<tbody>
<tr style="page-break-inside: avoid;">
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 54.95pt;" valign="top" width="73">
<small><small> </small></small>
<h2><small><small><span style="font-weight: normal;">Codice</span><o:p></o:p></small></small></h2>
<small><small> </small></small>
</td>
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 164.85pt;" valign="top" width="220">
<small><small> </small></small>
<h2><small><small><span style="font-weight: normal;">Materiale sostituito</span><o:p></o:p></small></small></h2>
<small><small> </small></small>
</td>
<td
style="border-style: none none solid; border-color: -moz-use-text-color -moz-use-text-color windowtext; border-width: medium medium 1pt; padding: 1.4pt 3.5pt; width: 54.95pt;"
valign="top" width="73"><small><small> </small></small>
<h2><small><small><span style="font-weight: normal;">Q.ta'</span><o:p></o:p></small></small></h2>
<small><small> </small></small></td>
</tr>
<tr style="page-break-inside: avoid;">
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 54.95pt;" valign="top" width="73">
<br>
</td>
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 164.85pt;" valign="top" width="220">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;"><br>
<o:p></o:p></span></p>
</td>
<td style="border-style: none none solid; border-color: -moz-use-text-color -moz-use-text-color windowtext; border-width: medium medium 1pt; padding: 1.4pt 3.5pt; width: 54.95pt; vertical-align: middle;">
<br>
</td>
</tr>
<tr style="page-break-inside: avoid;">
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 54.95pt;" valign="top" width="73">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;" lang="DE"><o:p>&nbsp;</o:p></span></p>
</td>
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 164.85pt;" valign="top" width="220">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;" lang="DE"><o:p>&nbsp;</o:p></span></p>
</td>
<td style="border-style: none none solid; border-color: -moz-use-text-color -moz-use-text-color windowtext; border-width: medium medium 1pt; padding: 1.4pt 3.5pt; width: 54.95pt;" valign="top" width="73">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;" lang="DE"><o:p>&nbsp;</o:p></span></p>
</td>
</tr>
<tr style="page-break-inside: avoid;">
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 54.95pt;" valign="top" width="73">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;" lang="DE"><o:p>&nbsp;</o:p></span></p>
</td>
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 164.85pt;" valign="top" width="220">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;" lang="DE"><o:p>&nbsp;</o:p></span></p>
</td>
<td style="border-style: none none solid; border-color: -moz-use-text-color -moz-use-text-color windowtext; border-width: medium medium 1pt; padding: 1.4pt 3.5pt; width: 54.95pt;" valign="top" width="73">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;" lang="DE"><o:p>&nbsp;</o:p></span></p>
</td>
</tr>
<tr style="page-break-inside: avoid;">
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 54.95pt;" valign="top" width="73">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;" lang="DE"><o:p>&nbsp;</o:p></span></p>
</td>
<td style="border-style: none solid solid none; border-color: -moz-use-text-color windowtext windowtext -moz-use-text-color; border-width: medium 1pt 1pt medium; padding: 1.4pt 3.5pt; width: 164.85pt;" valign="top" width="220">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;" lang="DE"><o:p>&nbsp;</o:p></span></p>
</td>
<td style="border-style: none none solid; border-color: -moz-use-text-color -moz-use-text-color windowtext; border-width: medium medium 1pt; padding: 1.4pt 3.5pt; width: 54.95pt;" valign="top" width="73">
<p class="MsoNormal"><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;" lang="DE"><o:p>&nbsp;</o:p></span></p>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
<tr style="page-break-inside: avoid;">
<td>
<h1><small><small><small><span style="font-weight: normal;">Il cliente constata la ricezione dei servizi sopra indicati</span></small></small></small><o:p></o:p></h1>
<p class="MsoNormal"><b><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;"><o:p>&nbsp;</o:p></span></b></p>
<p class="MsoNormal"><b><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;"><o:p>&nbsp;</o:p></span></b></p>
<p class="MsoNormal"><b><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;"><o:p>&nbsp;</o:p></span></b></p>
<p class="MsoNormal"><b><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;"><o:p>&nbsp;</o:p></span></b></p>
<p class="MsoNormal">
<b><span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;">Timbro e firma </span></b>
<span style="font-size: 10pt; font-family: &quot;Arial&quot;,sans-serif;">___________________________________</span>
<span style="font-size: 4pt; font-family: &quot;Arial&quot;,sans-serif;"><o:p></o:p></span>
</p>
</td>
</tr>
</tbody>
</table>
</body>
END;
    $html .= "</span>";
}

$pdf->writeHTMLCell(0, 20, '', '', $html, 0, 1, 0, true, '', true);
$pdf->Output();
?>

<!--
Array ( [0] => 4 [id] => 4 
        [1] => 1 [idinstallazione] => 1 
        [2] => ASS [tipo] => ASS 
        [3] => 4 [codice] => 103000012 
        [4] => amministratore [utente] => amministratore 
        [5] => 2017-04-16 [data] => 2017-04-16 
        [6] => Andrea [tecnico] => Andrea 
        [7] => rimozione cpu [oggetto] => rimozione cpu 
        [8] => ciccop [descrizione] => ciccop 
        [9] => [soluzione] => 
        [10] => 08:00 [ora_inizio] => 08:00 
        [11] => 12:30 [ora_fine] => 12:30 
        [12] => [info_agg] => 
        [13] => 103000012 [clfoco] => 103000012 
        [14] => 4.50 [ore] => 4.50 
        [15] => [codart] => 
        [16] => [prezzo] => 
        [17] => [codeart] => 
        [18] => 0 [ripetizione] => 0 
        [19] => [ogni] => 
        [20] => effettuato [stato] => effettuato 
        [21] => [note] => 
        [22] => Delta [ragso1] => Delta 
        [23] => [telefo] => 
        [24] => [cell] => 
        [25] => [fax] => 
        [26] => 103000012 )
-->