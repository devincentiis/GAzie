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
require("../../library/include/datlib.inc.php");

require("../../library/include/calsca.inc.php");
$admin_aziend=checkAdmin();
$message="";
$anagrafica = new Anagrafica();
$descridoc="";
$masban = $admin_aziend['masban']."000000";
$banche = $admin_aziend['masban'];
if (!isset($_POST['ritorno']))
        $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
if (!isset($_GET['id_tes']))
    {
    header("Location: ".$_POST['ritorno']);
    exit;
    }
if (!isset($_POST['delrig']))
    $_POST['delrig'] = array();
$tesbro = gaz_dbi_get_row($gTables['tesbro'],"id_tes",$_GET['id_tes']);
$conto = $anagrafica->getPartner($tesbro['clfoco']);
$pagame = gaz_dbi_get_row($gTables['pagame'],"codice",$conto['codpag']);
$year = date("Y");
$month = date("m");
$day = date("d");
if (!isset($_POST['rigbon']))
    {
    //recupero i dati
    $rs_rig = gaz_dbi_dyn_query("*", $gTables['rigbro'], "id_tes = '{$_GET['id_tes']}'","id_tes desc");
    //dati righi
    $_POST['rigbon'] = 0;
    // ...e della testata
    $_POST['conven'] = "";
    $_POST['tipdoc'] = $tesbro['tipdoc'];
    $_POST['numdoc'] = $tesbro['numdoc'];
    $_POST['clfoco'] = $tesbro['clfoco'];
    $_POST['gioemi'] = substr($tesbro['datemi'],8,2);
    $_POST['mesemi'] = substr($tesbro['datemi'],5,2);
    $_POST['annemi'] = substr($tesbro['datemi'],0,4);
    $_POST['gioval'] = substr($tesbro['datfat'],8,2);
    $_POST['mesval'] = substr($tesbro['datfat'],5,2);
    $_POST['annval'] = substr($tesbro['datfat'],0,4);
    $_POST['banapp'] = $tesbro['banapp'];
    $_POST['spediz'] = $tesbro['spediz'];
    $_POST['numfat'] = $tesbro['numfat'];
    $_POST['id_con'] = $tesbro['id_con'];
    while ($rigo = gaz_dbi_fetch_array($rs_rig))
        {
        $_POST['righi'][$_POST['rigbon']]['prelis'] = $rigo['prelis'];
        $_POST['righi'][$_POST['rigbon']]['descri'] = $rigo['descri'];
        $_POST['righi'][$_POST['rigbon']]['id_rig'] = $rigo['id_rig'];
        $_POST['rigbon']++;
        }
    }
$utsemi= mktime(0,0,0,$_POST['mesemi'],$_POST['gioemi'],$_POST['annemi']);
$utsval= mktime(0,0,0,$_POST['mesval'],$_POST['gioval'],$_POST['annval']);
if ($_POST['tipdoc'] == 'AOB') $descridoc = 'Ordine di Bonifico';
elseif ($_POST['tipdoc'] == 'AOA') $descridoc = 'Ordine di Addebito';
$titolo = $descridoc." verso ".$conto['ragso1']." ".$conto['ragso2'];
if (isset($_POST['update']))
       {
        //controllo le date
        if (!checkdate( $_POST['mesemi'], $_POST['gioemi'], $_POST['annemi']))
           $message .= "La data di emissione ".$_POST['gioemi']."-".$_POST['mesemi']."-".$_POST['annemi']." non &egrave; corretta! <br>\n";
        if (!checkdate( $_POST['mesval'], $_POST['gioval'], $_POST['annval']))
           $message .= "La data della valuta ".$_POST['gioval']."-".$_POST['mesval']."-".$_POST['annval']." non &egrave; corretta! <br>\n";
        if ($utsval < $utsemi)
            $message .= "La data di emissione non dev'essere successiva a quella della valuta ! <br>\n";
        //altri controlli
        if (empty($_POST['spediz']))
            $message .= "Inserire il numero di c/c bancario presso il quale eseguire l'accredito! <br>\n";
        if ($_POST['banapp'] == 0)
            $message .= "Inserire la banca presso la quale eseguire l'accredito! <br>\n";
        if ($_POST['numfat'] < 100000000)
            $message .= "Inserire il conto corrente dove eseguire l'addebito! <br>\n";
        $importo=0.00;
        foreach ($_POST['righi'] as $value)
            {
            $importo+= preg_replace("/\,/",'.', $value['prelis']);
            }
        if ($importo <= 0)
            $message .= "L'importo totale dev'essere maggiore di zero! <br>\n";
        if ($message == "") // nessun errore
               {
               //formatto le date
               $dataemi = $_POST['annemi']."-".$_POST['mesemi']."-".$_POST['gioemi'];
               $dataval = $_POST['annval']."-".$_POST['mesval']."-".$_POST['gioval'];
        //modifico la testata con i nuovi dati...
        $_POST['status'] = 'MODIFICATO';
        $_POST['datemi'] = $dataemi;
        $_POST['datfat'] = $dataval;
        $_POST['pagame'] = $conto['codpag'];
        $_POST['portos'] = number_format($importo,2, '.', '');  //impropriamente utilizzato per l'importo
        $codice = array('id_tes',$_GET['id_tes']);
        tesbroUpdate($codice,$_POST);
        //prima elimino dal db i righi eliminati
        if (isset($_POST['delrig']))
           {
           foreach ($_POST['delrig'] as $key => $value)
                   {
                   if ($value == "ELIMINA") gaz_dbi_del_row($gTables['rigbro'], "id_rig", $key);
                   }
           }

        //a secondo che il rigo sia nuovo o esistente inserisco oppure modifico i dati
        for ($i = 0; $i < $_POST['rigbon']; $i++)
            {
                if ($_POST['righi'][$i]['id_rig'] == 'NUOVO') {
                        $_POST['righi'][$i]['id_tes'] = $_GET['id_tes'];
                        rigbroInsert($_POST['righi'][$i]);
                } else {
                        $_POST['righi'][$i]['id_tes'] = $_GET['id_tes'];
                        $codice = array('id_rig',$_POST['righi'][$i]['id_rig']);
                        rigbroUpdate($codice,$_POST['righi'][$i]);
                }
            }
            header("Location: ".$_POST['ritorno']);
            exit;
            }
}
if (isset($_POST['Return']))
        {
        header("Location: ".$_POST['ritorno']);
        exit;
        }
    // Quando viene inviata la richiesta di aggiungere un rigo
// Quando viene inviata la richiesta di aggiungere un rigo
/** ENRICO FEDELE */
/* Con button non funziona _x */
//if (isset($_POST['add_x'])) {
/** ENRICO FEDELE */
if(isset($_POST['add'])) {
    $rigo = $_POST['rigbon'];
    $_POST['righi'][$rigo]['prelis'] = 0.00;
    $_POST['righi'][$rigo]['descri'] = "";
    $_POST['righi'][$rigo]['id_rig'] = "NUOVO";
    $_POST['rigbon']++;
    }
    // Quando viene inviata la richiesta di eliminazione di un rigo
    if (isset($_POST['del']))
    {
    $delri= key($_POST['del']);
    $chiaveid=$_POST['righi'][$delri]['id_rig'];
    $_POST['delrig'][$chiaveid] = "ELIMINA";
    array_splice($_POST['righi'],$delri,1);
    $_POST['rigbon']--;
    }
require("../../library/include/header.php");
$script_transl=HeadMain();
print "<form method=\"POST\">\n";
print "<input type=\"hidden\" name=\"ritorno\" value=\"".$_POST['ritorno']."\">";
print "<input type=\"hidden\" name=\"tipdoc\" value=\"".$_POST['tipdoc']."\">";
print "<input type=\"hidden\" name=\"clfoco\" value=\"".$_POST['clfoco']."\">";
print "<input type=\"hidden\" name=\"numdoc\" value=\"".$_POST['numdoc']."\">";
print "<input type=\"hidden\" name=\"id_con\" value=\"".$_POST['id_con']."\">";
print "<div align=\"center\" class=\"FacetFormHeaderFont\">".$descridoc." n.". $_POST['numdoc']." verso ".$conto['ragso1']." ".$conto['ragso2']."</div>\n";
print "<table border=\"0\" class=\"FacetFormTABLE\" align=\"center\" width=\"50%\">\n";
if ($message == "")
    print "<tr><td colspan=\"2\" class=\"FacetSelect\" style=\"color: blue;\">Questa procedura modifica l'".$descridoc." ma non apporta cambiamenti al movimento contabile generato in fase di inserimento, per questo vi potete servire della lista in calce.</td></tr>\n";
else
    print "<tr><td colspan=\"2\" class=\"FacetSelect\" style=\"color: red;\">".$message."</td></tr>\n";
print "<tr> <td class=\"FacetFieldCaptionTD\">In data </td>\n";
print "<td class=\"FacetFieldCaptionTD\">\n";
           // select del giorno emissione
            print "\t <select name=\"gioemi\" class=\"FacetSelect\" >\n";
            for( $counter = 1; $counter <= 31; $counter++ )
                {
                $selected = "";
                if($counter ==  $_POST['gioemi'])
                        $selected = "selected";
                print "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
                }
            print "\t </select>\n";
            // select del mese
            print "\t <select name=\"mesemi\" class=\"FacetSelect\" >\n";
            $gazTimeFormatter->setPattern('MMMM');
            for( $counter = 1; $counter <= 12; $counter++ ) {
              $selected = "";
              if($counter == $_POST['mesemi']) $selected = "selected";
              $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
              print "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
            }
            print "\t </select>\n";
            // select del anno
            print "\t <select name=\"annemi\" class=\"FacetSelect\" >\n";
            for( $counter = 2002; $counter <= 2030; $counter++ )
                {
                $selected = "";
                if($counter == $_POST['annemi'])
                        $selected = "selected";
                print "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
                }
            print "\t </select></td></tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\">accreditate su </td> ";
print "<td class=\"FacetFieldCaptionTD\">\n ";
           $select_banapp = new selectbanapp("banapp");
           $select_banapp -> addSelected($_POST["banapp"]);
           $select_banapp -> output();
print "</td></tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\"> C/C num. </td>\n";
print "<td class=\"FacetFieldCaptionTD\"><input title=\"Numero di c/c del beneficiario\" type=\"text\" name=\"spediz\" value=\"{$_POST["spediz"]}\" maxlength=\"20\"  class=\"FacetInput\"></td></tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\">con valuta </td>\n ";
print "<td class=\"FacetFieldCaptionTD\">\n";
           // select del giorno valuta
            print "\t <select name=\"gioval\" class=\"FacetSelect\" >\n";
            for( $counter = 1; $counter <= 31; $counter++ )
                {
                $selected = "";
                if($counter ==  $_POST['gioval'])
                        $selected = "selected";
                print "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
                }
            print "\t </select>\n";
            // select del mese
            print "\t <select name=\"mesval\" class=\"FacetSelect\" >\n";
            for( $counter = 1; $counter <= 12; $counter++ )
                {
                $selected = "";
                if($counter == $_POST['mesval']) $selected = "selected";
                $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
                print "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
                }
            print "\t </select>\n";
            // select del anno
            print "\t <select name=\"annval\" class=\"FacetSelect\">\n";
            for( $counter = 2002; $counter <= 2030; $counter++ )
                {
                $selected = "";
                if($counter == $_POST['annval'])
                        $selected = "selected";
                print "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
                }
            print "\t </select></td></tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\"> da addebitare sul c/c </td>\n ";
print "<td class=\"FacetFieldCaptionTD\">\n";
print "\t <select name=\"numfat\" class=\"FacetSelect\">\n";
$selected = "selected";
$result = gaz_dbi_dyn_query ('*', $gTables['clfoco'].' LEFT JOIN '. $gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '. $gTables['anagra'].'.id', "codice like '$banche%' and codice > '$masban' and banapp > 0", "codice asc");//recupero i c/c
while ($a_row = gaz_dbi_fetch_array($result))
            {
            $selected = "";
            if($a_row["codice"] == $_POST['numfat'])
                $selected = "selected";
            print "\t\t <option value=\"".$a_row["codice"]."\" $selected >".$a_row["ragso1"]." ".$a_row["citspe"]." n.".$a_row["iban"]."</option>\n";
            }
print "\t </select>\n";
print "</td></tr></table>\n";
print "<div class=\"table-responsive\"> <table class=\"Tlarge table table-striped table-bordered table-condensed\">\n";
//apro l'array contenente i righi eliminati per fare il POST
foreach ($_POST['delrig'] as $key => $value)
    {
    print "<input type=\"hidden\" value=\"{$value}\" name=\"delrig[{$key}]\">\n";
    }
	/*
print "<tr><td colspan=\"3\" align=\"right\">Aggiungi un rigo --&raquo; <input type=\"image\" name=\"add\" src=\"../../library/images/vbut.gif\" title=\"Aggiunta rigo! \"></td></tr>\n";*/

/** ENRICO FEDELE */
/* glyph-icon */
echo '  <tr>
		  <td colspan="3" align="right">Aggiungi un rigo --&raquo;
			<button type="submit" class="btn btn-default btn-sm" name="add" title="Aggiunta rigo!"><i class="glyphicon glyphicon-ok"></i></button>
		</td>
	   </tr>';
	   /** ENRICO FEDELE */
if ($_POST['rigbon'] > 0)
  print "<tr><th class=\"FacetFieldCaptionTD\">Descrizione</th><th class=\"FacetFieldCaptionTD\">Importo</th><th class=\"FacetFieldCaptionTD\">Selez.</th></tr>\n";
  print "<input type=\"hidden\" value=\"{$_POST['rigbon']}\" name=\"rigbon\">\n";
  $totale=0.00;
  foreach ($_POST['righi'] as $key => $value)
       {
        $totale+=$value['prelis'];
        $importo=number_format($value['prelis'],2, '.', '');
        print "<tr><td><input type=\"text\" name=\"righi[{$key}][descri]\" value=\"{$value['descri']}\" maxlength=\"50\" ></td>\n";
        print "<td align=\"right\"><input align=\"right\" type=\"text\" name=\"righi[{$key}][prelis]\" value=\"{$value['prelis']}\" maxlength=\"11\" ></td>\n";

		//print "<td align=\"right\"><input type=\"image\" name=\"del[{$key}]\" src=\"../../library/images/xbut.gif\" title=\"Elimina rigo!\"></td></tr>\n";

		/** ENRICO FEDELE */
		/* glyph icon */
		echo '  <td align="right">
				  <button type="submit" class="btn btn-default btn-sm" name="del['.$key.']" title="Elimina rigo!"><i class="glyphicon glyphicon-trash"></i></button>
				</td>
			  </tr>';
		/** ENRICO FEDELE */

		print "<input type=\"hidden\" name=\"righi[{$key}][id_rig]\" value=\"{$value['id_rig']}\">\n";
       }
if($_POST['rigbon'] > 0)
        print "<tr><td></td><td align=\"right\"class=\"FacetAltDataTD\">Totale € ".number_format($totale,2, '.', '')."&nbsp;</td><td align=\"right\"><input type=\"submit\" title=\"Modifica l'$descridoc e proponi la stampa\" value=\"Modifica\" accesskey=\"i\" name=\"update\" ></td></tr>\n";
//recupero tutti i movimenti contabili del conto insieme alle relative testate...
$result = mergeTable($gTables['rigmoc'],"*",$gTables['tesmov'],"*","id_tes","codcon = {$conto['codice']} and caucon <> 'CHI' and caucon <> 'APE' ORDER BY datreg asc");
$nummov = gaz_dbi_num_rows($result);
if ($nummov > 0)
    {
    print "</table><br><table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\"><tr><td colspan=\"6\">Questi sono i movimenti contabili, epurati degli eventuali movimenti di apertura e chiusura, relativi al fornitore ".$conto['ragso1']." ".$conto['ragso2'].":<td></tr>\n";
    print "<tr><th class=\"FacetFieldCaptionTD\">Mov.</th><th class=\"FacetFieldCaptionTD\">Descrizione</th><th class=\"FacetFieldCaptionTD\">N.Doc.</th><th class=\"FacetFieldCaptionTD\">Data Doc.</th><th class=\"FacetFieldCaptionTD\">Importo</th><th class=\"FacetFieldCaptionTD\">D/A</th></tr>\n";
    while ($movimenti = gaz_dbi_fetch_array($result))
        {
        print "<tr><td class=\"FacetDataTD\">n.<a href=\"../contab/admin_movcon.php?Update&id_tes=".$movimenti["id_tes"]."\" title=\"Modifica il movimento\">".$movimenti["id_tes"]."</a> del ".$movimenti["datreg"]." &nbsp;</td>\n";
        print "<td class=\"FacetDataTD\">".$movimenti["descri"]." &nbsp;</td>\n";
        print "<td align=\"center\" class=\"FacetDataTD\">".$movimenti["numdoc"]." &nbsp;</td>\n";
        print "<td align=\"center\" class=\"FacetDataTD\">".$movimenti["datdoc"]." &nbsp;</td>\n";
        print "<td align=\"right\" class=\"FacetDataTD\">".number_format($movimenti["import"],2, '.', '')." &nbsp;</td>\n";
        print "<td align=\"center\" class=\"FacetDataTD\">".$movimenti["darave"]." &nbsp;</td></tr>\n";
        }
    }
    else
    print "<tr><td colspan=\"6\" class=\"FacetDataTDred\">Non ci sono movimenti contabili relativi al fornitore ".$conto['ragso1']." ".$conto['ragso2']." !<td></tr>\n";
print "</table></div></form>\n";
?>
<?php
require("../../library/include/footer.php");
?>
