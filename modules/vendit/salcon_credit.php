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

require("../../library/include/calsca.inc.php");
$admin_aziend=checkAdmin();
$message="";
$year = date("Y");
$month = date("m");
$day = date("d");

$banche = $admin_aziend['masban'];
$masban = $banche*1000000;
$casse = substr($admin_aziend['cassa_'],0,3);
$mascas = $casse*1000000;

if (!isset($_POST['ritorno'])) {
        $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

if (!isset($_POST['righi'])) {
        $_POST['righi'] = array();
}

if (!isset($_GET['codice'])) {
    // il codice contiene il numero di conto del cliente in caso di INSERT e l'id_tes di gaz_tesbro in caso di UPDATE
    // ma se non e' stato inviato torna indietro
    header("Location: ".$_POST['ritorno']);
    exit;
}

if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso
   if ($toDo == 'insert') {
      $anagrafica = new Anagrafica();
      $conto = $anagrafica->getPartner($_GET['codice']);
      if ($_POST['stampa'] == "on") {
         $_POST['stampa'] = " checked";
      } else {
         $_POST['stampa'] = "";
      }
   } else {
     $testata = gaz_dbi_get_row($gTables['tesbro'],"id_tes",$_GET['codice']);
     $anagrafica = new Anagrafica();
     $conto = $anagrafica->getPartner($testata['clfoco']);
     $_POST['stampa'] = " disabled";
   }
   $utsemi= mktime(0,0,0,$_POST['mesemi'],$_POST['gioemi'],$_POST['annemi']);
   $pagame = gaz_dbi_get_row($gTables['pagame'],"codice",$conto['codpag']);

} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
   $testata = gaz_dbi_get_row($gTables['tesbro'],"id_tes",$_GET['codice']);
   $anagrafica = new Anagrafica();
   $conto = $anagrafica->getPartner($testata['clfoco']);
   $pagame = gaz_dbi_get_row($gTables['pagame'],"codice",$conto['codpag']);
   $_POST['num_rigo'] = 0;
   $_POST['stampa'] = " disabled";
   //dati righi
   $rs_rig = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = '{$testata['id_tes']}'","id_tes desc");
   // ...e della testata
   $_POST['tipdoc'] = $testata['tipdoc'];
   $_POST['numdoc'] = $testata['numdoc'];
   $_POST['gioemi'] = substr($testata['datemi'],8,2);
   $_POST['mesemi'] = substr($testata['datemi'],5,2);
   $_POST['annemi'] = substr($testata['datemi'],0,4);
   $utsemi= mktime(0,0,0,$_POST['mesemi'],$_POST['gioemi'],$_POST['annemi']);
   $_POST['numfat'] = $testata['numfat']; //impropriamente usato per il numero di conto d'accredito
   while ($rigo = gaz_dbi_fetch_array($rs_rig))
       {
       $_POST['righi'][$_POST['num_rigo']]['prelis'] = $rigo['prelis'];
       $_POST['righi'][$_POST['num_rigo']]['descri'] = $rigo['descri'];
       $_POST['righi'][$_POST['num_rigo']]['numdoc'] = '';
       $_POST['righi'][$_POST['num_rigo']]['des_con'] = '';
       $_POST['righi'][$_POST['num_rigo']]['id_rig'] = $rigo['id_rig'];
       $_POST['num_rigo']++;
       }

} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
   $anagrafica = new Anagrafica();
   $conto = $anagrafica->getPartner($_GET['codice']);
   $_POST['num_rigo'] = 0;
   $_POST['stampa'] = " checked";
   $_POST['gioemi'] = $day;
   $_POST['mesemi'] = $month;
   $_POST['annemi'] = $year;
   $_POST['righi'] = array();
   $_POST['tipdoc'] = 'VPA';
   $_POST['numfat'] = ""; //impropriamente usato per il numero di conto d'accredito
   $_POST['id_con'] = "";
   //recupero tutti i movimenti contabili del conto insieme alle relative testate per creare l'array dei crediti
   $utsemi= mktime(0,0,0,$_POST['mesemi'],$_POST['gioemi'],$_POST['annemi']);
   $pagame = gaz_dbi_get_row($gTables['pagame'],"codice",$conto['codpag']);
   $result = mergeTable($gTables['rigmoc'],"*",$gTables['tesmov'],"*","id_tes","codcon = {$conto['codice']} and caucon <> 'CHI' and caucon <> 'APE' or (caucon = 'APE' and codcon = {$conto['codice']} and datreg IN (SELECT min(datreg) FROM ".$gTables['tesmov'].")) ORDER BY datreg asc");
   $_POST['righi'] = createArrayCrediti($result,$pagame,$utsemi);
   $_POST['num_rigo'] = $_POST['righi']['numrighi'] ;
   $_POST['righi'] = array_splice($_POST['righi'],0,$_POST['num_rigo']);
   //azzero gli array eliminati
   $_POST['delrig'] = array();
}

if ($toDo == 'update') {
    $titolo = "Riscossione Credito da ".$conto['ragso1']." ".$conto['ragso2']."(modifica)";
} else {
    $titolo = "Riscossione Credito da ".$conto['ragso1']." ".$conto['ragso2']."(inserimento)";
}

if (!isset($_POST['delrig']))
    $_POST['delrig'] = array();
if (!isset($testata['id_con']))
    $testata['id_con'] = 0;

if (isset($_POST['ins'])) {
        //controllo le date
        if (!checkdate( $_POST['mesemi'], $_POST['gioemi'], $_POST['annemi']))
           $message .= "La data di emissione ".$_POST['gioemi']."-".$_POST['mesemi']."-".$_POST['annemi']." non &egrave; corretta! <br>";
        //altri controlli
        if ($_POST['numfat'] < 100000000)
            $message .= "Inserire il conto dove dev'essere accreditato il pagamento! <br>";
        $importo=0.00;
        foreach ($_POST['righi'] as $v) {
            $importo += preg_replace("/\,/",'.', $v['prelis']);
        }
        if ($importo <= 0) {
            $message .= "L'importo totale dev'essere maggiore di zero! <br>";
        }
        if ($message == "") {
               //formatto le date
               $dataemi = $_POST['annemi']."-".$_POST['mesemi']."-".$_POST['gioemi'];
               //inserisco la testata
               $descmov='';
               $ctrl_td='';
               for ($i = 0; $i < $_POST['num_rigo']; $i++) {  // preparo la descrizione
                  if ($ctrl_td != $_POST['righi'][$i]['des_con']) {
                      $descmov.= $_POST['righi'][$i]['des_con'].' ';
                  }
                  $descmov.= $_POST['righi'][$i]['numdoc'];
                  if ($i<($_POST['num_rigo']-1)) {
                      $descmov.=',';
                  }
                  $ctrl_td=$_POST['righi'][$i]['des_con'];
               }
               $descmov = "RISCOSSO ".$descmov;
               $rs_ultimo_documento = gaz_dbi_dyn_query("*", $gTables['tesbro'], "YEAR(datemi) = ".$_POST['annemi']." and tipdoc = '{$_POST['tipdoc']}'","numdoc desc",0,1);
               $ultimo_documento = gaz_dbi_fetch_array($rs_ultimo_documento);
               // ricavo il progressivo annuo, ma se e' il primo documento dell'anno, resetto il contatore
               if ($ultimo_documento) {
                  $testata['numdoc'] = $ultimo_documento['numdoc'] + 1;
               } else {
                  $testata['numdoc'] = 1;
               }
               $newValue=array('caucon'=>substr($_POST['tipdoc'],0,3),
                           'descri'=>$descmov,
                           'datreg'=>$dataemi,
                           'clfoco'=>intval($_GET['codice'])
                           );
               tesmovInsert($newValue);
               //recupero l'id assegnato dall'inserimento
               $ultimo_id = gaz_dbi_last_id();
               //inserisco i righi contabili
               rigmocInsert(array('id_tes'=>$ultimo_id,'darave'=>'D','codcon'=>intval($_POST['numfat']),'import'=> number_format($importo,2, '.', '')));
               rigmocInsert(array('id_tes'=>$ultimo_id,'darave'=>'A','codcon'=>intval($_GET['codice']),'import'=> number_format($importo,2, '.', '')));
               //inserisco la testata del brogliaccio
               $_POST['numdoc'] = $testata['numdoc'];
               $_POST['status'] = 'GENERATO';
               $_POST['datemi'] = $dataemi;
               $_POST['datfat'] = $dataemi;
               $_POST['clfoco'] = $_GET['codice'];
               $_POST['pagame'] = $conto['codpag'];
               $_POST['portos'] = number_format($importo,2, '.', '');  //impropriamente utilizzato per l'importo
               $_POST['id_con'] = $ultimo_id;
               tesbroInsert($_POST);
               $ultimo_id = gaz_dbi_last_id();
               //inserisco i righi
               for ($i = 0; $i < $_POST['num_rigo']; $i++) {
                        $_POST['righi'][$i]['id_tes'] = $ultimo_id;
                        rigbroInsert($_POST['righi'][$i]);
               }
               if ($_POST['stampa'] == " checked") {
                  $_SESSION['print_request']=$ultimo_id;
                  header("Location: invsta_salcon.php");
                  exit;
               } else  {
                  header("Location:report_salcon.php");
                  exit;
               }
    }
}

if (isset($_POST['upd'])) {
        //controllo le date
        if (!checkdate( $_POST['mesemi'], $_POST['gioemi'], $_POST['annemi']))
           $message .= "La data di emissione ".$_POST['gioemi']."-".$_POST['mesemi']."-".$_POST['annemi']." non &egrave; corretta! <br>";
        //altri controlli
        if ($_POST['numfat'] < 100000000)
            $message .= "Inserire il conto dove dev'essere accreditato il pagamento! <br>";
        $importo=0.00;
        foreach ($_POST['righi'] as $v) {
            $importo += preg_replace("/\,/",'.', $v['prelis']);
        }
        if ($importo <= 0) {
            $message .= "L'importo totale dev'essere maggiore di zero! <br>";
        }
        if ($message == "") {
               //formatto le date
               $dataemi = $_POST['annemi']."-".$_POST['mesemi']."-".$_POST['gioemi'];
               //inserisco la testata
               switch($_POST['tipdoc']) //previsto per implementare il trattamento di diversi tipi di riscossioni
                     {
                     case "VPA":
                     $descmov = "RISCOSSO DA CLIENTE";
                     break;
                     default:
                     $descmov = "RISCOSSO DA CLIENTE ";
                     break;
                     }
               $newValue=array('caucon'=>$testata['tipdoc'],
                           'descri'=>$descmov,
                           'datreg'=>$dataemi,
                           'numdoc'=>$testata['numdoc'],
                           'datdoc'=>$dataemi,
                           'clfoco'=>$conto['codice'],
                           );
               tesmovUpdate(array('id_tes',$testata['id_con']),$newValue);
               //recupero l'id assegnato ai righi
               $rs_righi_contabili = gaz_dbi_dyn_query("id_rig", $gTables['rigmoc'], "id_tes = ".$testata['id_con'],"id_rig asc");
               $cont_rigmoc[0] = $_POST['numfat'];
               $cont_rigmoc[1] = $testata['clfoco'];
               $daav_rigmoc[0] = 'D';
               $daav_rigmoc[1] = 'A';
               $index=0;
               while ($righi_contabili = gaz_dbi_fetch_array($rs_righi_contabili)) {
                     //modifico i righi contabili
                     gaz_dbi_table_update('rigmoc',array('id_rig',$righi_contabili['id_rig']),array('id_tes'=>$testata['id_con'],'darave'=>$daav_rigmoc[$index],'codcon'=>$cont_rigmoc[$index],'import'=>number_format($importo,2, '.', '')));
                     $index++;
               }
               //modifico la testata del brogliaccio
               $_POST['numdoc'] = $testata['numdoc'];
               $_POST['status'] = 'MODIFICATO';
               $_POST['datemi'] = $dataemi;
               $_POST['datfat'] = $dataemi;
               $_POST['clfoco'] = $conto['codice'];
               $_POST['pagame'] = $conto['codpag'];
               $_POST['portos'] = number_format($importo,2, '.', '');  //impropriamente utilizzato per l'importo
               $_POST['id_con'] = $testata['id_con'];
               $codice = array('id_tes',$testata['id_tes']);
               tesbroUpdate($codice,$_POST);
               //prima elimino dal db i righi eliminati
               if (isset($_POST['delrig']))
                  {
                  foreach ($_POST['delrig'] as $k => $v) {
                          if ($v == "ELIMINA") gaz_dbi_del_row($gTables['rigbro'], "id_rig", $k);
                   }
               }
               //modifico o inserisco i righi
               for ($i = 0; $i < $_POST['num_rigo']; $i++) {
                   if ($_POST['righi'][$i]['id_rig'] == 'NUOVO') {
                        $_POST['righi'][$i]['id_tes'] = $_GET['codice'];
                        rigbroInsert($_POST['righi'][$i]);
                   } else {
                        $_POST['righi'][$i]['id_tes'] = $_GET['codice'];
                        $codice = array('id_rig',$_POST['righi'][$i]['id_rig']);
                        rigbroUpdate($codice,$_POST['righi'][$i]);
                   }
               }
               header("Location: ".$_POST['ritorno']);
               exit;
    }
}

if (isset($_POST['Return'])) {
        header("Location: ".$_POST['ritorno']);
        exit;
}
// Quando viene inviata la richiesta di aggiungere un rigo
/** ENRICO FEDELE */
/* Con button non funziona _x */
//if (isset($_POST['add_x'])) {
/** ENRICO FEDELE */
if (isset($_POST['add'])) {
    $rigo = $_POST['num_rigo'];
    $_POST['righi'][$rigo]['prelis'] = 0.00;
    $_POST['righi'][$rigo]['descri'] = "";
    $_POST['righi'][$rigo]['id_rig'] = "NUOVO";
    $_POST['num_rigo']++;
}
// Quando viene inviata la richiesta di eliminazione di un rigo
if (isset($_POST['del'])) {
    $delri= key($_POST['del']);
    $chiaveid=$_POST['righi'][$delri]['id_rig'];
    if ($chiaveid != 'NUOVO') {
       $_POST['delrig'][$chiaveid] = "ELIMINA";
    }
    array_splice($_POST['righi'],$delri,1);
    $_POST['num_rigo']--;
}
require("../../library/include/header.php");
$script_transl=HeadMain();
print "<form method=\"POST\">\n";
print "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">\n";
print "<input type=\"hidden\" name=\"ritorno\" value=\"".$_POST['ritorno']."\">\n";
print "<input type=\"hidden\" name=\"tipdoc\" value=\"".$_POST['tipdoc']."\">\n";
print "<div align=\"center\" class=\"FacetFormHeaderFont\">".$titolo."</div>\n";
print "<table border=\"0\" class=\"FacetFormTABLE\" align=\"center\" width=\"50%\">\n";
if ($message != "") {
    print "<tr><td colspan=\"2\"  class=\"FacetDataTDred\">".$message."</td></tr>\n";
}
print "<tr> <td class=\"FacetFieldCaptionTD\">Data </td>";
print "<td class=\"FacetFieldCaptionTD\">";
print "\t <select name=\"gioemi\" class=\"FacetSelect\" >\n";
for( $counter = 1; $counter <= 31; $counter++ ) {
    $selected = "";
    if($counter ==  $_POST['gioemi'])
            $selected = "selected";
    print "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
print "\t </select>\n";
print "\t <select name=\"mesemi\" class=\"FacetSelect\" >\n";
$gazTimeFormatter->setPattern('MMMM');
for( $counter = 1; $counter <= 12; $counter++ ) {
    $selected = "";
    if($counter == $_POST['mesemi'])  $selected = "selected";
    $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
    print "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
print "\t </select>\n";
print "\t <select name=\"annemi\" class=\"FacetSelect\" >\n";
for( $counter = 2002; $counter <= 2030; $counter++ ) {
    $selected = "";
    if($counter == $_POST['annemi']) $selected = "selected";
    print "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
print "\t </select></td></tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\"> Conto per l'incasso </td>\n ";
print "<td class=\"FacetFieldCaptionTD\">";
print "\t <select name=\"numfat\" class=\"FacetSelect\">\n"; //impropriamente usato per il numero di conto d'accredito
$result = gaz_dbi_dyn_query ('*', $gTables['clfoco'], "(codice LIKE '$casse%' AND codice > '$mascas') or (codice LIKE '$banche%' AND codice > '$masban')", "codice ASC");//recupero i c/c
print "\t\t <option value=\"000000000\" $selected >000000000 - Conto non selezionato!</option>\n";
while ($a_row = gaz_dbi_fetch_array($result)) {
    $selected = "";
    if($a_row["codice"] == $_POST['numfat']) {
       $selected = "selected";
    }
    print "\t\t <option value=\"".$a_row["codice"]."\" $selected >".$a_row["codice"]." - ".$a_row["descri"]."</option>\n";
}
print "\t </select>\n";
print "</td></tr><tr>";
print "<td class=\"FacetFieldCaptionTD\">Stampa ricevuta di pagamento &nbsp;</td>";
print "<td class=\"FacetDataTD\"><input type=\"checkbox\" title=\"Per stampare la ricevuta seleziona questa checkbox\" name=\"stampa\" ".$_POST['stampa']." \"></td>\n";
print "</tr></table>";

//apro l'array contenente i righi eliminati per fare il POST
foreach ($_POST['delrig'] as $k => $v)
    {
    print "<input type=\"hidden\" value=\"{$v}\" name=\"delrig[{$k}]\">\n";
    }
print "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">\n";
/*print "<tr><td colspan=\"3\" align=\"right\">Aggiungi un rigo --&raquo; <input type=\"image\" name=\"add\" src=\"../../library/images/vbut.gif\" title=\"Aggiunta rigo! \"></td></tr>";*/

/** ENRICO FEDELE */
/* glyph-icon */
echo '  <tr>
		  <td colspan="3" align="right">Aggiungi un rigo --&raquo;
			<button type="submit" class="btn btn-default btn-sm" name="add" title="Aggiunta rigo!"><i class="glyphicon glyphicon-ok"></i></button>
		</td>
	   </tr>';
	   /** ENRICO FEDELE */

if ($_POST['num_rigo'] > 0) {
  print "<tr><th class=\"FacetFieldCaptionTD\">Descrizione</th><th class=\"FacetFieldCaptionTD\">Importo</th><th class=\"FacetFieldCaptionTD\">Selez.</th></tr>\n";
}
print "<input type=\"hidden\" value=\"{$_POST['num_rigo']}\" name=\"num_rigo\">\n";
$totale=0.00;
foreach ($_POST['righi'] as $k => $v) {
    print "<input type=\"hidden\" name=\"righi[{$k}][des_con]\" value=\"{$v['des_con']}\">\n";
    print "<input type=\"hidden\" name=\"righi[{$k}][numdoc]\" value=\"{$v['numdoc']}\">\n";
    $totale+=$v['prelis'];
    $importo_rigo=number_format($v['prelis'],2, '.', '');
    print "<tr><td><input type=\"text\" name=\"righi[{$k}][descri]\" value=\"{$v['descri']}\" maxlength=\"50\" ></td>\n";
    print "<td align=\"right\"><input align=\"right\" type=\"text\" name=\"righi[{$k}][prelis]\" value=\"".preg_replace("/\,/",'.', $importo_rigo)."\" maxlength=\"11\" ></td>\n";


   /** ENRICO FEDELE */
   /* glyph icon */
   echo '  <td align="right">
		     <button type="submit" class="btn btn-default btn-sm" name="del['.$k.']" title="Elimina rigo!"><i class="glyphicon glyphicon-trash"></i></button>
		   </td>
	     </tr>';
   /** ENRICO FEDELE */

	print "<input type=\"hidden\" name=\"righi[{$k}][id_rig]\" value=\"{$v['id_rig']}\">\n";
}
if($_POST['num_rigo'] > 0) {
    print "<tr><td></td><td align=\"right\"class=\"FacetAltDataTD\">Totale € ".number_format($totale,2, '.', '')."&nbsp;</td><td align=\"right\">\n";
    if ($toDo == 'update') {
        echo '<input title="Modifica il movimento contabile e la ricevuta di pagamento" type="submit" value="Modifica" accesskey="i" name="upd">';
    } else {
        echo '<input title="Inserisci il movimento contabile ed eventualmente proponi la stampa della ricevuta di pagamento" type="submit" value="Inserisci" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();">';
    }
    print "</td></tr>\n";
}
//recupero tutti i movimenti contabili del conto insieme alle relative testate...
$result = mergeTable($gTables['rigmoc'],"*",$gTables['tesmov'],"*","id_tes","codcon = ".$conto['codice']." ORDER BY datreg asc");
$nummov = gaz_dbi_num_rows($result);
if ($nummov > 0) {
    print "</table><br \><table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\"><tr><td colspan=\"6\">Questi sono i movimenti contabili relativi al cliente ".$conto['ragso1']." ".$conto['ragso2'].":<td></tr>\n";
    print "<tr><th class=\"FacetFieldCaptionTD\">Mov.</th><th class=\"FacetFieldCaptionTD\">Descrizione</th><th class=\"FacetFieldCaptionTD\">N.Doc.</th><th class=\"FacetFieldCaptionTD\">Data Doc.</th><th class=\"FacetFieldCaptionTD\">Importo</th><th class=\"FacetFieldCaptionTD\">D/A</th></tr>\n";
    while ($movimenti = gaz_dbi_fetch_array($result)) {
        $cl="FacetDataTD";
        if ($movimenti["id_tes"] == $testata["id_con"]) {
        $cl="FacetDataTDred";
        }
        print "<tr><td class=\"$cl\">n.<a href=\"../contab/admin_movcon.php?Update&id_tes=".$movimenti["id_tes"]."\" title=\"Modifica il movimento\">".$movimenti["id_tes"]."</a> del ".$movimenti["datreg"]." &nbsp;</td>\n";
        print "<td class=\"$cl\">".$movimenti["descri"]." &nbsp;</td>";
        print "<td align=\"center\" class=\"$cl\">".$movimenti["numdoc"]." &nbsp;</td>";
        print "<td align=\"center\" class=\"$cl\">".$movimenti["datdoc"]." &nbsp;</td>";
        print "<td align=\"right\" class=\"$cl\">".number_format($movimenti["import"],2, '.', '')." &nbsp;</td>";
        print "<td align=\"center\" class=\"$cl\">".$movimenti["darave"]." &nbsp;</td></tr>\n";
    }
} else {
    print "<tr><td colspan=\"6\" class=\"FacetDataTDred\">Non ci sono movimenti contabili relativi al cliente ".$conto['ragso1']." ".$conto['ragso2']." !<td></tr>\n";
}
print "</table></form>";
?>
<?php
require("../../library/include/footer.php");
?>
