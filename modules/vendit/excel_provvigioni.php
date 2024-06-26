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

function getNewAgente($id) {
   global $gTables;
   $agente = gaz_dbi_get_row($gTables['agenti'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['agenti'] . ".id_fornitore = " . $gTables['clfoco'] . ".codice
                                                  LEFT JOIN " . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', $gTables['agenti'] . '.id_agente', $id);
   return $agente;
}

require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
require("lang." . $admin_aziend['lang'] . ".php");
require("../../config/templates/report_template.php");

/*
print_r($_GET);
die();
*/

if (!isset($_GET['datini']) or ! isset($_GET['datfin']) or ! isset($_GET['id_agente'])) {
   header("Location: select_provvigioni.php");
   exit;
}

if ($_GET['id_agente'] > 0) {
   $sql_agente = 'tesdoc.id_agente = ' . intval($_GET['id_agente']) . ' AND ';
} else {
   $sql_agente = 'tesdoc.id_agente > 0 AND';
}

$dataini = substr($_GET['datini'], 0, 4) . '-' . substr($_GET['datini'], 4, 2) . '-' . substr($_GET['datini'], 6, 2);
$datafin = substr($_GET['datfin'], 0, 4) . '-' . substr($_GET['datfin'], 4, 2) . '-' . substr($_GET['datfin'], 6, 2);
$where = $sql_agente . " tipdoc LIKE 'F__' AND tiprig = 0 AND datfat BETWEEN " . intval($_GET['datini']) . " AND " . intval($_GET['datfin']);
$what = "tesdoc.id_agente, " .
        "tesdoc.id_tes, " .
        "tesdoc.datfat, " .
        "tesdoc.datemi, " .
        "tesdoc.clfoco, " .
        "tesdoc.tipdoc, " .
        "tesdoc.protoc, " .
        "tesdoc.numdoc, " .
        "tesdoc.numfat, " .
        "tesdoc.seziva, " .
        "tesdoc.sconto AS scochi, " .
        "anagra.ragso1, " .
        "anagra.ragso2, " .
        "anagra.citspe, " .
        "anagra.prospe, " .
        "rigdoc.id_tes, " .
        "SUM(rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100)*(1-tesdoc.sconto/100)) as totaleFattura, " .
        "SUM(rigdoc.quanti*rigdoc.prelis*(1-rigdoc.sconto/100)*(1-tesdoc.sconto/100)*rigdoc.provvigione/100) as totaleProvvigione," .
        "AVG(rigdoc.provvigione) as provvigione";
$table = $gTables['rigdoc'] . " rigdoc "
        . "LEFT JOIN " . $gTables['tesdoc'] . " tesdoc ON tesdoc.id_tes = rigdoc.id_tes "
        . "LEFT JOIN " . $gTables['clfoco'] . " clfoco ON tesdoc.clfoco = clfoco.codice "
        . "LEFT JOIN " . $gTables['anagra'] . " anagra ON anagra.id = clfoco.id_anagra ";
$groupBy = " numfat ";
$result = gaz_dbi_dyn_query($what, $table, $where, "id_agente, datfat , clfoco, protoc, id_rig", 0, 20000, $groupBy);

$filename="provvigioni.xls";

header ("Content-Type: application/vnd.ms-excel");
header ("Content-Disposition: inline; filename=$filename");

echo '<table border=\"1\">';
echo '<tr><td><strong>LA CORTE DEL CHIANTI</strong></td>';
echo '<td colspan="4" align="right>Provvigioni dal '.substr($_GET['datini'], 6, 2).'/'.substr($_GET['datini'], 4, 2).'/'.substr($_GET['datini'], 0, 4).' al '.substr($_GET['datfin'], 6, 2).'/'.substr($_GET['datfin'], 4, 2).'/'.substr($_GET['datfin'], 0, 4).'</td></tr>';

$intestazione='<tr><td>Documento</td><td>Cliente</td><td>Importo</td><td>% Prov.</td><td>Provvigioni</td></tr>';

$ctrlAgente = 0;
$ctrlDoc = 0;
while ($row = gaz_dbi_fetch_array($result)) {
   if ($ctrlAgente != $row['id_agente']) {
      if ($ctrlAgente > 0) {
		 echo '<tr><td colspan="2" align="right">Totali</td>';
		 echo '<td>'.gaz_format_number($tot_fatt).'</td>';
		 echo '<td></td>';
		 echo '<td>'.gaz_format_number($tot_prov).'</td></tr>';
		 echo "\n";
      }
      $agente = getNewAgente($row['id_agente']);
      echo '<tr></tr><tr><td colspan="5">Agente: '.$agente['ragso1'].' '.$agente['ragso2'].'</td></tr>';
	  echo "\n";
	  echo $intestazione;
	  echo "\n";
	  $tot_prov = 0.00;
	  $tot_fatt = 0.00;
   }
   if ($row['tipdoc'] == 'FNC') {   // nota di credito
      $row['totaleFattura'] = -$row['totaleFattura'];
      $row['totaleProvvigione'] = -$row['totaleProvvigione'];
   }
   $row_importo = $row['totaleFattura'];
   $tot_fatt += $row_importo;
   $row_provvig = $row['totaleProvvigione'];
   $tot_prov += $row_provvig;
   if ($ctrlDoc != $row['id_tes']) {

      $tmpDescr = $strScript['admin_docven.php']['doc_name'][$row['tipdoc']];

      echo '<tr><td>Fattura n.' . $row['numfat'] . '/' . $row['seziva'] . ' del ' . gaz_format_date($row['datfat']) . '</td><td>' . $row['ragso1'] . ' ' . $row['ragso2'].'</td>';
	  echo '<td>'.gaz_format_number($row_importo).'</td>';
	  echo '<td>'.gaz_format_number($row['provvigione']).'</td>';
	  echo '<td>'.gaz_format_number($row_provvig).'</td></tr>';
	  echo "\n";
	  $ctrlDoc = $row['id_tes'];
   }
	$ctrlAgente = $row['id_agente'];
}
echo '<tr><td colspan="2" align="right">Totali</td>';
echo '<td>'.gaz_format_number($tot_fatt).'</td>';
echo '<td></td>';
echo '<td>'.gaz_format_number($tot_prov).'</td></tr>';
echo "\n";
echo '</table>';
?>