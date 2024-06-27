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

$titolo = "Bilancio IV direttiva CEE";
$oggi = date("Y-m-d");
$orainizio = date("Y-m-d H:i:s");
$message = "";
$errore ="";

$nromani = array(0=>"",1=>"I",2=>"II",3=>"III",4=>"IV",5=>"V",6=>"VI",7=>"VII",8=>"VIII",9=>"IX",10=>"X",11=>"XI",12=>"XII",13=>"XIII",14=>"XIV",15=>"XV",16=>"XVI",17=>"XVII",18=>"XVIII",19=>"XIX",20=>"XX");
$attdesc = array('A'=>array("Titolo"=>") CREDITI VERSO SOCI:"),'B'=>array("Titolo"=>") IMMOBILIZZAZIONI:",1=>" - Immobilizzazioni immateriali: ",2=>" - Immobilizzazioni materiali:",3=>" - Immobilizzazioni finanziarie: "),'C'=>array("Titolo"=>") ATTIVO CIRCOLANTE:",1=>" - Rimanenze: ",2=>" - Crediti: ",3=>" - Attivit&agrave; finanziarie: ",4=>" - Disponibilit&agrave; liquide: "),'D'=>array("Titolo"=>") RATEI E RISCONTI:"));
$pasdesc = array('A'=>array("Titolo"=>") PATRIMONIO NETTO:",1=>" - Capitale:",2=>" - Riserva da sovrapprezzo delle azioni:",3=>" - Riserva di rivalutazione:",4=>" - Riserva legale:",5=>" - Riserva per azioni proprie in portafoglio:",6=>" - Riserve statutarie:",7=>" - Altre riserve distintamente indicate:",8=>" - Utili (perdite) portati a nuovo:",9=>" - Utile (perdita) dell'esercizio:"),'B'=>array("Titolo"=>") FONDI RISCHI E ONERI:"),'C'=>array("Titolo"=>") TRATTAMENTO DI FINE RAPPORTO DI LAVORO SUBORDINATO:"),'D'=>array("Titolo"=>") DEBITI:"),'E'=>array("Titolo"=>") RATEI E RISCONTI:"));
$ecodesc = array('A'=>array("Titolo"=>") Valore della produzione:"),'B'=>array("Titolo"=>") Costi della produzione:"),'C'=>array("Titolo"=>") Proventi e oneri finanziari:"),'D'=>array("Titolo"=>") Rettifiche di valore di attivit finanziarie:"),'E'=>array("Titolo"=>") Proventi e oneri straordinari:"),'_'=>array("Titolo"=>") Risultato prima delle imposte:"));

//
// L'array $bin[] serve ad accumulare i valori calcolati, per poi poter generare
// una riclassificazione e gli indici relativi. L'array è associativo e la
// la chiave di accesso viene generata usando la variabile $code.
//
$bil = array();
$code = "";
$anno;
$query;
$result;
$extra;
$extcon_sum = 0;
//
// Inizializza a zero l'array associativo $bil[], per evitare che
// venga richiesto poi l'uso di elementi non dichiarati.
//
$bil["aA"] = 0;
$bil["aB"] = 0;
$bil["aB01"] = 0;
$bil["aB02"] = 0;
$bil["aB03"] = 0;
$bil["aB011"] = 0;
$bil["aB012"] = 0;
$bil["aB013"] = 0;
$bil["aB014"] = 0;
$bil["aB015"] = 0;
$bil["aB016"] = 0;
$bil["aB017"] = 0;
$bil["aB021"] = 0;
$bil["aB022"] = 0;
$bil["aB023"] = 0;
$bil["aB024"] = 0;
$bil["aB025"] = 0;
$bil["aB031a"] = 0;
$bil["aB031b"] = 0;
$bil["aB031c"] = 0;
$bil["aB031d"] = 0;
$bil["aB032a"] = 0;
$bil["aB032b"] = 0;
$bil["aB032c"] = 0;
$bil["aB032d"] = 0;
$bil["aB033"] = 0;
$bil["aB034"] = 0;
$bil["aC"] = 0;
$bil["aC01"] = 0;
$bil["aC02"] = 0;
$bil["aC03"] = 0;
$bil["aC04"] = 0;
$bil["aC011"] = 0;
$bil["aC012"] = 0;
$bil["aC013"] = 0;
$bil["aC014"] = 0;
$bil["aC015"] = 0;
$bil["aC021"] = 0;
$bil["aC022"] = 0;
$bil["aC023"] = 0;
$bil["aC024"] = 0;
$bil["aC024b"] = 0;
$bil["aC024c"] = 0;
$bil["aC025"] = 0;
$bil["aC03"] = 0;
$bil["aC031"] = 0;
$bil["aC032"] = 0;
$bil["aC033"] = 0;
$bil["aC034"] = 0;
$bil["aC035"] = 0;
$bil["aC036"] = 0;
$bil["aC041"] = 0;
$bil["aC042"] = 0;
$bil["aC043"] = 0;
$bil["aD"] = 0;
$bil["aD001"] = 0;
$bil["aD002"] = 0;
$bil["aD003"] = 0;
$bil["pA01"] = 0;
$bil["pA02"] = 0;
$bil["pA03"] = 0;
$bil["pA04"] = 0;
$bil["pA05"] = 0;
$bil["pA06"] = 0;
$bil["pA07"] = 0;
$bil["pA08"] = 0;
$bil["pA09"] = 0;
$bil["pB"] = 0;
$bil["pB001"] = 0;
$bil["pB002"] = 0;
$bil["pB003"] = 0;
$bil["pC"] = 0;
$bil["pD001"] = 0;
$bil["pD002"] = 0;
$bil["pD003"] = 0;
$bil["pD004"] = 0;
$bil["pD005"] = 0;
$bil["pD006"] = 0;
$bil["pD007"] = 0;
$bil["pD008"] = 0;
$bil["pD009"] = 0;
$bil["pD001"] = 0;
$bil["pD0011"] = 0;
$bil["pD0012"] = 0;
$bil["pD0013"] = 0;
$bil["pD0014"] = 0;
$bil["pE"] = 0;
$bil["pE001"] = 0;
$bil["pE002"] = 0;
$bil["pE003"] = 0;
$bil["eA"] = 0;
$bil["eA001"] = 0;
$bil["eA002"] = 0;
$bil["eA003"] = 0;
$bil["eA004"] = 0;
$bil["eA005"] = 0;
$bil["eB006"] = 0;
$bil["eB007"] = 0;
$bil["eB008"] = 0;
$bil["eB009a"] = 0;
$bil["eB009b"] = 0;
$bil["eB009c"] = 0;
$bil["eB009d"] = 0;
$bil["eB009e"] = 0;
$bil["eB0010a"] = 0;
$bil["eB0010b"] = 0;
$bil["eB0010c"] = 0;
$bil["eB0010d"] = 0;
$bil["eB0011"] = 0;
$bil["eB0012"] = 0;
$bil["eB0013"] = 0;
$bil["eB0014"] = 0;
$bil["eC"] = 0;
$bil["eC0015"] = 0;
$bil["eC0016a"] = 0;
$bil["eC0016b"] = 0;
$bil["eC0016c"] = 0;
$bil["eC0016d"] = 0;
$bil["eC0017"] = 0;
$bil["eD"] = 0;
$bil["eD0018a"] = 0;
$bil["eD0018b"] = 0;
$bil["eD0018c"] = 0;
$bil["eD0019a"] = 0;
$bil["eD0019b"] = 0;
$bil["eD0019c"] = 0;
$bil["eE0019"] = 0;
$bil["eD002"] = 0;
$bil["eD0021"] = 0;
$bil["e_0022"] = 0;
//
// Carica i dati del bilancio IV direttiva CEE
// Legge le linee del file
//
$data = array();
$descon = array();
$noclass = 'non riclassificato';
$lines=file('IVdirCEE.bil');
foreach($lines as $line) {
        $nuova = explode(';',$line,2);
        $descon[trim($nuova[0])] = $nuova[1];
        $data[] = trim($nuova[0]);
}
$data = array_slice($data,1);
if (!isset($_GET['gioini']))
    $_GET['gioini'] = "1";
if (!isset($_GET['mesini']))
    $_GET['mesini'] = "1";
if (!isset($_GET['annini']))
    $_GET['annini'] =   date("Y")-1;
if (!isset($_GET['giofin']))
    $_GET['giofin'] =  "31";
if (!isset($_GET['mesfin']))
    $_GET['mesfin'] =  "12";
if (!isset($_GET['annfin']))
    $_GET['annfin'] =  date("Y")-1;

//controllo i campi
if (!checkdate( $_GET['mesini'], $_GET['gioini'], $_GET['annini'])) {
    $message .= "La data ".$_GET['gioini']."-".$_GET['mesini']."-".$_GET['annini']." non &egrave; corretta!<br>";
}
if (!checkdate( $_GET['mesfin'], $_GET['giofin'], $_GET['annfin'])) {
    $message .= "La data ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']." non &egrave; corretta!<br>";
}
$utsini= mktime(0,0,0,$_GET['mesini'],$_GET['gioini'],$_GET['annini']);
$utsdop= mktime(0,0,0,$_GET['mesini'],$_GET['gioini']-1,$_GET['annini']+1);
$utsfin= mktime(0,0,0,$_GET['mesfin'],$_GET['giofin'],$_GET['annfin']);
$datainizio = date("Ymd",$utsini);
$datadopo = date("Ymd",$utsdop);
$datafine = date("Ymd",$utsfin);
//
if ($utsini >= $utsfin) {
    $message .="La data di inizio periodo dev'essere precedente alla data di fine periodo !<br>";
}
if (isset($_GET['stampa'])) {
    $locazione = "Location: stampa_bilcee.php?&bilini=".$datainizio."&bilfin=".$datafine;
    header($locazione);
    exit;
}
if (isset($_GET['Return'])) {
    header("Location:docume_finean.php");
    exit;
}
require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<form method="GET">
<div align="center" class="FacetFormHeaderFont">Bilancio IV direttiva CEE</div>
<table border="0" cellpadding="3" cellspacing="1" class="FacetFormTABLE" align="center">
<tr>
<td colspan="2" class="FacetDataTD"  style="color: red;">
<?php
if (! $message == "") {
    print "$message";
}
?>
</td>
</tr>
<tr><td class="FacetFieldCaptionTD">Data Inizio Periodo &nbsp;</td>
<td class="FacetDataTD" colspan=3>
<?php
// select del giorno
print "\t <select name=\"gioini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 31; $counter++ ) {
    $selected = "";
    if($counter ==  $_GET['gioini'])
       $selected = "selected";
    print "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
print "\t </select>\n";
// select del mese
echo "\t <select name=\"mesini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
$gazTimeFormatter->setPattern('MMMM');
for( $counter = 1; $counter <= 12; $counter++ ) {
  $selected = "";
  if($counter == $_GET['mesini']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
// select del anno
            print "\t <select name=\"annini\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
            for( $counter = 2003; $counter <= 2030; $counter++ )
                {
                $selected = "";
                if($counter == $_GET['annini'])
                        $selected = "selected";
                print "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
                }
            print "\t </select>\n";
         ?>
    </td>
  </tr>
  <tr>
    <td class="FacetFieldCaptionTD">Data Fine Periodo &nbsp;</td>
    <td class="FacetDataTD"  colspan=3 >
         <?php
            // select del giorno
            print "\t <select name=\"giofin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
            for( $counter = 1; $counter <= 31; $counter++ )
                {
                $selected = "";
                if($counter ==  $_GET['giofin'])
                        $selected = "selected";
                print "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
                }
            print "\t </select>\n";
            // select del mese
            echo "\t <select name=\"mesfin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
            for( $counter = 1; $counter <= 12; $counter++ )
                {
                $selected = "";
                if($counter == $_GET['mesfin']) $selected = "selected";
                $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
                echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
                }
            echo "\t </select>\n";
            // select del anno
            print "\t <select name=\"annfin\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
            for( $counter = 2003; $counter <= 2030; $counter++ )
                {
                $selected = "";
                if($counter == $_GET['annfin'])
                        $selected = "selected";
               print "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
                }
            print "\t </select>\n";
         ?>
    </td>
  </tr>
<?php
if ($message == "")
  {
    echo "<tr><td class=\"bg-info text-center\" colspan=2><input type=\"submit\" class=\"btn btn-info\" name=\"visualizza\" value=\"Visualizza l'anteprima\"></td></tr>";
  }
echo "</table>";
//
// Link all'altro file (provvisorio).
//
//echo "<p align=\"right\"><big>Compila la tabella dei dati <a href=\"extcon.php\"><big>extracontabili</big></a></strong></big></p>";
//
if (isset($_GET['visualizza']) and $message == "")
  {
    $where = "datreg between '$datainizio' and '$datafine' and caucon <> 'CHI' and caucon <> 'APE' or (caucon = 'APE' and datreg between '$datainizio' and '$datadopo') group by codcon ";
    $orderby = " codcon ";
    $rs_castel = gaz_dbi_dyn_query("codcon, ragso1,".$gTables['clfoco'].".descri AS descri, SUM(import*(darave='D')-import*(darave='A')) AS saldo, ceedar, ceeave", $gTables['rigmoc']."
                                   LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes
                                   LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice
                                   LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra", $where, $orderby);
    $ctrlnum = gaz_dbi_num_rows($rs_castel);
    if ($ctrlnum > 0)
      {
        //procedura per la creazione dell'array dei conti per la riclassificazione...
        while($castel = gaz_dbi_fetch_array($rs_castel))
          {
            if ($castel["saldo"] > 0) // se l'eccedenza è in dare
              {
                if (! in_array(trim($castel['ceedar']),$data)) // se non e' riclassificato
                  {
                    // vedo se c'è la riclassificazione sul mastro
                    $mastro = gaz_dbi_get_row($gTables['clfoco'],"codice",substr($castel['codcon'],0,3)."000000");
                    $castel['ceedar']=trim($mastro['ceedar']);
                    if (! in_array($castel['ceedar'],$data)) // se non e' riclassificato neanche il mastro
                      {
                        $errore .= 'Il conto '.$castel["codcon"]." ".$castel["descri"].' non &egrave; stato riclassificato per l\'eccedenza in dare! <br>' ;
                        $castel['ceedar']=trim($castel['codcon']);
                      }
                  }
                $conti[$castel['codcon']] = array($castel["saldo"],$castel["descri"],$castel["ceedar"]);
              }
            if ($castel["saldo"] < 0) //se l'eccedenza è in avere
              {
                if(! in_array(trim($castel['ceeave']),$data))
                  {
                    // vedo se c'è la riclassificazione sul mastro
                    $mastro = gaz_dbi_get_row($gTables['clfoco'],"codice",substr($castel['codcon'],0,3)."000000");
                    $castel['ceeave']=trim($mastro['ceeave']);
                    if (! in_array(trim($castel['ceeave']),$data)) //se non e' riclassificato neanche il mastro
                      {
                        $errore .= 'Il conto '.$castel["codcon"]." ".$castel["descri"].' non &egrave; stato riclassificato per l\'eccedenza in avere! <br>' ;
                        $castel['ceeave']=trim($castel['codcon']);
                      }
                  }
                $conti[$castel['codcon']] = array($castel["saldo"],$castel["descri"],$castel["ceeave"]);
              }
          }
        $contiassoc = array();
        foreach ($conti as $value)
          {
            if (! array_key_exists($value[2],$contiassoc))
               $contiassoc[$value[2]] = $value[0];
            else
               $contiassoc[$value[2]] += $value[0];
          }
        ksort($contiassoc);
        //array conti creato chiave con codice e valore con saldo totale!
        // calcolo l'utile o la perdita (conto economico) e ricreo gli array attivita,passivita,economico.
        $economico = array();
        $attivo = array();
        $passivo = array();
        $risulta = array();
        foreach ($contiassoc as $key => $value)
          {
            $ctrlett = substr($key,1,1);
            $ctrlrom = substr($key,2,2);
            $ctrltipcon = substr($key,0,1);
            switch($ctrltipcon)
              {
                case 'E':
                case 4:
                case 3:
                if (trim($ctrlett) == '')
                  {
                    $ctrlett='_';
                  }
                $economico = $economico + array($key=>$value);
                $risulta[$ctrlett][$ctrlrom][$key] = -$value;
                break;
                case 'A':
                case 1:
                $attivo[$ctrlett][$ctrlrom][$key] = $value;
                break;
                case 'P':
                case 2:
                $passivo[$ctrlett][$ctrlrom][$key] = -$value;
                break;
              }
          }
        //
        // aggiungo l'utile(perdita) sul relativo conto e riclassifico
        //
        $passivo['A']['09']['PA09000'] = -array_sum($economico);
        ksort($passivo);
        ksort($risulta);

        $totrom =0.00;
        $totlet =0.00;
        $totale =0.00;
        echo "<div><center><b>ANTEPRIMA BILANCIO IV direttiva CEE AL ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</b></center></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        echo "<tr><td colspan=\"4\">Questo bilancio, riclassificato secondo la IV direttiva CEE, &egrave; stato generato leggendo i movimenti compresi nel periodo selezionato escludendo tutti quelli di apertura e chiusura, unica eccezione &egrave; l'apertura effettuata entro l'anno successivo alla data impostata come inizio periodo.</td></tr>\n";

        if ($errore != "" )
          {
            echo "<tr><td colspan=\"4\" style=\"color: red;\">Sono stati riscontrati i seguenti errori che non ne giustificano la stampa ma la sola visualizzazione: <br></td></tr>\n";
            echo "<tr><td colspan=\"4\" style=\"color: red;\">".$errore."</td></tr>\n";
          }
        else
          {
            echo "<tr><td colspan=\"4\" class=\"FacetFooterTD text-center\"><input type=\"submit\" class=\"btn btn-warning\" name=\"stampa\" value=\"STAMPA IL BILANCIO CEE !\"></td></tr>\n";
          }
        echo "<tr><td colspan=\"4\"><hr></td></tr>\n";
        echo "<tr><td><hr></td><td align=\"center\" class=\"FacetFormHeaderFont\">SITUAZIONE PATRIMONIALE AL ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</td><td colspan=\"2\"><hr></td></tr>\n";
        echo "<tr><td align=\"left\"  class=\"FacetFormHeaderFont\" style=\"color: blue;\">ATTIVO</td><td colspan=\"3\"></td></tr>\n";
        foreach ($attivo as $keylet => $vallet)
          {
            if (! key_exists($keylet,$attdesc))
              {
                $keylet = strtoupper($noclass);
                $attdesc[$keylet]['Titolo']= '';
                $attdesc[$keylet][0]= ucfirst($noclass);
              }
            echo "<tr><td align=\"left\" class=\"FacetFormHeaderFont\">".$keylet.$attdesc[$keylet]['Titolo']." </td><td colspan=\"3\"></td></tr>\n";
            foreach ($vallet as $keyrom => $valrom)
              {
                if (! key_exists($keyrom,$attdesc))
                  {
                    $attdesc[$keylet][intval($keyrom)]= '';
                  }
                echo "<tr><td align=\"left\">".$nromani[intval($keyrom)].$attdesc[$keylet][intval($keyrom)]." </td><td colspan=\"3\"></td></tr>\n";
                foreach ($valrom as $key => $value)
                  {
                    $conto = substr($key,4,3);
                    if ($conto == 0)
                      {
                        $conto = "";
                      }
                    else
                      {
                        $conto=intval($conto);
                      }
                    $totrom +=$value;
                    $totlet +=$value;
                    $totale +=$value;
                    if($key < 100000000)  //controllo per i conti non classificati
                      {
                        if($value > 0)
                          {
                            $stampaval = number_format($value,2,'.','');
                          }
                        else
                          {
                            $stampaval = "(".number_format(-$value,2,'.','').")";
                          }
                        echo "<tr><td align=\"right\">".$conto.substr($key,7,1).") </td><td>".$descon[$key]."</td><td>Euro</td><td align=\"right\">".$stampaval."</td></tr>\n";
                        //
                        // Salva dentro $bil[];
                        //
                        $code = "a".$keylet.$keyrom.$conto.substr($key,7,1);
                        $bil[$code] = round ($value);
                      }
                    else
                      {
                        if($value > 0)
                          {
                            $stampaval = number_format($value,2,'.','');
                          }
                        else
                          {
                            $stampaval = "(".number_format(-$value,2,'.','').")";
                          }
                        $descricon=gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
                        echo "<tr><td></td><td style=\"color: red;\">\"".$key." - ".($descricon?$descricon["descri"]:'')."\" non riclassificato</td><td>Euro</td><td align=\"right\">".$stampaval."</td></tr>\n";
                      }
                  }
                if($totrom > 0)
                  {
                    $stampaval = number_format($totrom,2,'.','');
                  }
                else
                  {
                    $stampaval = "(".number_format(-$totrom,2,'.','').")";
                  }
                echo "<tr><td></td><td align=\"right\"> Totale ".$nromani[intval($keyrom)]."</td><td>Euro</td><td align=\"right\"> ".$stampaval."</td></tr>\n";
                //
                // Salva dentro $bil[];
                //
                $code = "a".$keylet.$keyrom;
                $bil[$code] = round ($totrom);
                //
                $totrom=0.00;
              }
            if($totlet > 0)
              {
                $stampaval = number_format($totlet,2,'.','');
              }
            else
              {
                $stampaval = "(".number_format(-$totlet,2,'.','').")";
              }
            echo "<tr><td></td><td align=\"right\" class=\"FacetFormHeaderFont\"> Totale ".$keylet." </td><td>Euro</td><td align=\"right\" class=\"FacetFormHeaderFont\"> ".$stampaval."</td></tr>\n";
            //
            // Salva dentro $bil[];
            //
            $code = "a".$keylet;
            $bil[$code] = round ($totlet);
            //
            $totlet=0.00;
          }
        echo "<tr><td align=\"center\" colspan=\"2\"></td><td colspan=\"2\"><hr></td></tr>";
        echo "<tr><td align=\"right\" colspan=\"2\" class=\"FacetFormHeaderFont\" style=\"color: blue;\"> TOTALE DELL'ATTIVO </td><td>Euro</td><td align=\"right\" class=\"FacetFormHeaderFont\" style=\"color: blue;\">".number_format($totale,2,'.','')."</td></tr>";
        $totale=0.00;
        echo "<tr><td colspan=\"4\"><hr></td></tr>\n";
        echo "<tr><td align=\"left\"  class=\"FacetFormHeaderFont\" style=\"color: brown;\">PASSIVO</td><td colspan=\"3\"></td></tr>";
        foreach ($passivo as $keylet => $vallet)
          {
            if (! key_exists($keylet,$pasdesc))
              {
                $keylet = strtoupper($noclass);
                $pasdesc[$keylet]['Titolo']= '';
                $pasdesc[$keylet][0]= ucfirst($noclass);
              }
            echo "<tr><td align=\"left\" class=\"FacetFormHeaderFont\">".$keylet.$pasdesc[$keylet]['Titolo']." </td><td colspan=\"3\"></td></tr>\n";
            foreach ($vallet as $keyrom => $valrom)
              {
                if (! key_exists($keyrom,$pasdesc))
                  {
                    $pasdesc[$keylet][intval($keyrom)]= '';
                  }
                if ($keyrom != 0)
                  {
                    echo "<tr><td align=\"left\">".$nromani[intval($keyrom)].$pasdesc[$keylet][intval($keyrom)]." </td><td colspan=\"3\"></td></tr>\n";
                  }
                foreach ($valrom as $key => $value)
                  {
                    $conto = substr($key,4,3);
                    if ($conto == 0)
                      {
                        $conto = "";
                      }
                    else
                      {
                        $conto=intval($conto);
                      }
                    $totrom +=$value;
                    $totlet +=$value;
                    $totale +=$value;
                    if($value > 0)
                      {
                        $stampaval = number_format($value,2,'.','');
                      }
                    else
                      {
                        $stampaval = "(".number_format(-$value,2,'.','').")";
                      }
                    if($key < 100000000)  //controllo per i conti non classificati
                      {
                        echo "<tr><td align=\"right\">".$conto.substr($key,7,1).") </td><td>".$descon[$key]."</td><td>Euro</td><td align=\"right\">".$stampaval."</td></tr>\n";
                        //
                        // Salva dentro $bil[];
                        //
                        $code = "p".$keylet.$keyrom.$conto.substr($key,7,1);
                        $bil[$code] = round ($value);
                      }
                    else
                      {
                        $descricon=gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
                        echo "<tr><td></td><td style=\"color: red;\">\"".$key." - ".($descricon?$descricon["descri"]:'')."\" non riclassificato</td><td>Euro</td><td align=\"right\">".$stampaval."</td></tr>\n";
                      }
                  }
                if($totrom > 0)
                  {
                    $stampaval = number_format($totrom,2,'.','');
                  }
                else
                  {
                    $stampaval = "(".number_format(-$totrom,2,'.','').")";
                  }
                echo "<tr><td></td><td align=\"right\"> Totale ".$nromani[intval($keyrom)]."</td><td>Euro</td><td align=\"right\"> ".$stampaval."</td></tr>\n";
                //
                // Salva dentro $bil[];
                //
                $code = "p".$keylet.$keyrom;
                $bil[$code] = round ($totrom);
                $totrom=0.00;
              }
            if($totlet > 0)
              {
                $stampaval = number_format($totlet,2,'.','');
              }
            else
              {
                $stampaval = "(".number_format(-$totlet,2,'.','').")";
              }
            echo "<tr><td></td><td align=\"right\" class=\"FacetFormHeaderFont\"> Totale ".$keylet." </td><td>Euro</td><td align=\"right\" class=\"FacetFormHeaderFont\"> ".$stampaval."</td></tr>\n";
            //
            // Salva dentro $bil[];
            //
            $code = "p".$keylet;
            $bil[$code] = round ($totlet);
            $totlet=0.00;
          }
        echo "<tr><td align=\"center\" colspan=\"2\"></td><td colspan=\"2\"><hr></td></tr>";
        echo "<tr><td align=\"right\" colspan=\"2\" class=\"FacetFormHeaderFont\" style=\"color: brown;\"> TOTALE DEL PASSIVO </td><td>Euro</td><td align=\"right\" class=\"FacetFormHeaderFont\" style=\"color: brown;\">".number_format($totale,2,'.','')."</td></tr>\n";
        $totale=0.00;
        echo "<tr><td colspan=\"4\"><hr></td></tr>\n";
        echo "<tr><td><hr></td><td align=\"center\" class=\"FacetFormHeaderFont\">CONTO ECONOMICO DAL ".$_GET['gioini']."-".$_GET['mesini']."-".$_GET['annini']." AL ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</td><td colspan=\"2\"><hr></td></tr>";
        echo "<tr><td align=\"left\"  class=\"FacetFormHeaderFont\" style=\"color: orange;\">CONTO ECONOMICO</td><td colspan=\"3\"></td></tr>";
        foreach ($risulta as $keylet => $vallet)
          {
            if (! key_exists($keylet,$ecodesc))
              {
                $keylet = strtoupper($noclass);
                $ecodesc[$keylet]['Titolo']= '';
                $ecodesc[$keylet][0]= ucfirst($noclass);
              }
            echo "<tr><td align=\"left\" class=\"FacetFormHeaderFont\">".$keylet.$ecodesc[$keylet]['Titolo']." </td><td colspan=\"3\"></td></tr>\n";
            foreach ($vallet as $keyrom => $valrom)
              {
                foreach ($valrom as $key => $value)
                  {
                    $conto = substr($key,4,3);
                    if ($conto == 0)
                      {
                        $conto = "";
                      }
                    else
                      {
                        $conto=intval($conto);
                      }
                    $totrom +=$value;
                    $totlet +=$value;
                    $totale +=$value;
                    if($value > 0)
                      {
                        $stampaval = number_format($value,2,'.','');
                      }
                    else
                      {
                        $stampaval = "(".number_format(-$value,2,'.','').")";
                      }
                    if($key < 100000000) //controllo per i conti non classificati
                      {
                        echo "<tr><td align=\"right\">".$conto.substr($key,7,1).") </td><td>".((!empty($descon[$key])) ? $descon[$key] : $key)."</td><td>Euro</td><td align=\"right\">".$stampaval."</td></tr>\n";
                        //
                        // Salva dentro $bil[];
                        //
                        $code = "e".$keylet.$keyrom.$conto.substr($key,7,1);
                        $bil[$code] = round ($value);
                      }
                    else
                      {
                        $descricon=gaz_dbi_get_row($gTables['clfoco'],"codice",$key);
                        echo "<tr><td></td><td style=\"color: red;\">\"".$key." - ".($descricon?$descricon["descri"]:'')."\" non riclassificato</td><td>Euro</td><td align=\"right\">".$stampaval."</td></tr>\n";
                      }
                  }
                if($totrom > 0)
                    $stampaval = number_format($totrom,2,'.','');
                else
                    $stampaval = "(".number_format(-$totrom,2,'.','').")";
                echo "<tr><td></td><td align=\"right\"> Totale ".$nromani[intval($keyrom)]."</td><td>Euro</td><td align=\"right\"> ".$stampaval."</td></tr>\n";
                //
                // Salva dentro $bil[];
                //
                $code = "e".$keylet.$keyrom;
                $bil[$code] = round ($totrom);
                $totrom=0.00;
              }
            if($totlet > 0)
                $stampaval = number_format($totlet,2,'.','');
            else
                $stampaval = "(".number_format(-$totlet,2,'.','').")";
            echo "<tr><td></td><td align=\"right\" class=\"FacetFormHeaderFont\"> Totale ".$keylet." </td><td>Euro</td><td align=\"right\" class=\"FacetFormHeaderFont\"> ".$stampaval."</td></tr>\n";
            //
            // Salva dentro $bil[];
            //
            $code = "e".$keylet;
            $bil[$code] = round ($totlet);
            $totlet=0.00;
          }
        if($totale > 0)
            $stampaval = number_format($totale,2,'.','');
        else
            $stampaval = "(".number_format(-$totale,2,'.','').")";
        echo "<tr><td align=\"center\" colspan=\"2\"></td><td colspan=\"2\"><hr></td></tr>";
        echo "<tr><td align=\"right\" colspan=\"2\" class=\"FacetFormHeaderFont\" style=\"color: orange;\"> UTILE(PERDITA) DI ESERCIZIO </td><td>Euro</td><td align=\"right\" class=\"FacetFormHeaderFont\" style=\"color: orange;\">".$stampaval."</td></tr>\n";
        $totale=0.00;
      }
    echo "</table>\n";
    //
    // Determina alcune voci di bilancio di cui mancano i totali.
    //
    $bil["aB031"]  = $bil["aB031a"]  + $bil["aB031b"]  + $bil["aB031c"]  + $bil["aB031d"];
    $bil["aB032"]  = $bil["aB032a"]  + $bil["aB032b"]  + $bil["aB032c"]  + $bil["aB032d"];
    $bil["eB009"]  = $bil["eB009a"]  + $bil["eB009b"]  + $bil["eB009c"]  + $bil["eB009d"] + $bil["eB009e"];
    $bil["eB0010"] = $bil["eB0010a"] + $bil["eB0010b"] + $bil["eB0010c"] + $bil["eB0010d"];
    $bil["eC0016"] = $bil["eC0016a"] + $bil["eC0016b"] + $bil["eC0016c"] + $bil["eC0016d"];
    $bil["eD0018"] = $bil["eD0018a"] + $bil["eD0018b"] + $bil["eD0018c"];
    $bil["eD0019"] = $bil["eD0019a"] + $bil["eD0019b"] + $bil["eD0019c"];
    //
    // Preparazione di valori nulli per i dati provenienti dalla tabella extcon.
    //
    $bil["eB7__ind"]  = 0;
    $bil["eB7__amm"]  = 0;
    $bil["eB7__com"]  = 0;
    $bil["eB8__ind"]  = 0;
    $bil["eB8__amm"]  = 0;
    $bil["eB8__com"]  = 0;
    $bil["eB9__ind"]  = 0;
    $bil["eB9__amm"]  = 0;
    $bil["eB9__com"]  = 0;
    $bil["eB10__ind"] = 0;
    $bil["eB10__amm"] = 0;
    $bil["eB10__com"] = 0;
    $bil["eB12__ind"] = 0;
    $bil["eB12__amm"] = 0;
    $bil["eB12__com"] = 0;
    $bil["eB13__ind"] = 0;
    $bil["eB13__amm"] = 0;
    $bil["eB13__com"] = 0;
    $bil["eB14__ind"] = 0;
    $bil["eB14__amm"] = 0;
    $bil["eB14__com"] = 0;
    $bil["pD__breve"] = 0;
    $bil["pD__medio"] = 0;
    $bil["pD__lungo"] = 0;
    $bil["num_dip"]   = 0;
    $bil["Cv"]        = 0;
    //
    // Lettura della tabella extcon.
    //
    // Attenzione: deve trattarsi di un anno intero, e non di più,
    // altrimenti non si possono trattare queste informazioni
    //
    if ($_GET["gioini"] == 1 && $_GET["mesini"] == 1 && $_GET["annini"] == $_GET["annfin"] && $_GET["mesfin"] == 12 && $_GET["giofin"] == 31)
      {
        //
        // Ok.
        //
        $anno = $_GET["annini"];
      }
    else
      {
        $anno = 0;
      }
    //
    // Se l'anno è valido, procede.
    //
    if ($anno > 0)
      {
        $query  = "SELECT * FROM " . $gTables['extcon'] . " WHERE year = \"".$anno."\"";
        $result = gaz_dbi_query ($query);
        $nrows  = gaz_dbi_num_rows ($result);
        //
        // Se l'anno non c'è, aggiunge una riga vuota e la rilegge.
        //
        if ($nrows == 0)
          {
            $query  = "INSERT INTO " . $gTables['extcon'] . " (`year`) VALUES (".$anno.")";
            $result = gaz_dbi_query ($query);
            $query  = "SELECT * FROM " . $gTables['extcon'] . " WHERE year = \"".$anno."\"";
            $result = gaz_dbi_query ($query);
          }
        $extra = gaz_dbi_fetch_array ($result);
        //
        // Sistema i valori nell'array $bil[], adattandoli in proporzione.
        //
        $extcon_sum = $extra['cos_serv_ind'] + $extra['cos_serv_amm'] + $extra['cos_serv_com'];
        if ($extcon_sum != 0)
          {
            $bil["eB7__ind"] = round (($extra['cos_serv_ind'] / $extcon_sum) * -$bil["eB007"]);
            $bil["eB7__amm"] = round (($extra['cos_serv_amm'] / $extcon_sum) * -$bil["eB007"]);
            $bil["eB7__com"] = round (($extra['cos_serv_com'] / $extcon_sum) * -$bil["eB007"]);
          }
        //
        $extcon_sum = $extra['cos_godb_ind'] + $extra['cos_godb_amm'] + $extra['cos_godb_com'];
        if ($extcon_sum != 0)
          {
            $bil["eB8__ind"] = round (($extra['cos_godb_ind'] / $extcon_sum) * -$bil["eB008"]);
            $bil["eB8__amm"] = round (($extra['cos_godb_amm'] / $extcon_sum) * -$bil["eB008"]);
            $bil["eB8__com"] = round (($extra['cos_godb_com'] / $extcon_sum) * -$bil["eB008"]);
          }
        //
        $extcon_sum = $extra['cos_pers_ind'] + $extra['cos_pers_amm'] + $extra['cos_pers_com'];
        if ($extcon_sum != 0)
          {
            $bil["eB9__ind"] = round (($extra['cos_pers_ind'] / $extcon_sum) * -$bil["eB009"]);
            $bil["eB9__amm"] = round (($extra['cos_pers_amm'] / $extcon_sum) * -$bil["eB009"]);
            $bil["eB9__com"] = round (($extra['cos_pers_com'] / $extcon_sum) * -$bil["eB009"]);
          }
        //
        $extcon_sum = $extra['cos_amms_ind'] + $extra['cos_amms_amm'] + $extra['cos_amms_com'];
        if ($extcon_sum != 0)
          {
            $bil["eB10__ind"] = round (($extra['cos_amms_ind'] / $extcon_sum) * -$bil["eB0010"]);
            $bil["eB10__amm"] = round (($extra['cos_amms_amm'] / $extcon_sum) * -$bil["eB0010"]);
            $bil["eB10__com"] = round (($extra['cos_amms_com'] / $extcon_sum) * -$bil["eB0010"]);
          }
        //
        $extcon_sum = $extra['cos_accr_ind'] + $extra['cos_accr_amm'] + $extra['cos_accr_com'];
        if ($extcon_sum != 0)
          {
            $bil["eB12__ind"] = round (($extra['cos_accr_ind'] / $extcon_sum) * -$bil["eB0012"]);
            $bil["eB12__amm"] = round (($extra['cos_accr_amm'] / $extcon_sum) * -$bil["eB0012"]);
            $bil["eB12__com"] = round (($extra['cos_accr_com'] / $extcon_sum) * -$bil["eB0012"]);
          }
        //
        $extcon_sum = $extra['cos_acca_ind'] + $extra['cos_acca_amm'] + $extra['cos_acca_com'];
        if ($extcon_sum != 0)
          {
            $bil["eB13__ind"] = round (($extra['cos_acca_ind'] / $extcon_sum) * -$bil["eB0013"]);
            $bil["eB13__amm"] = round (($extra['cos_acca_amm'] / $extcon_sum) * -$bil["eB0013"]);
            $bil["eB13__com"] = round (($extra['cos_acca_com'] / $extcon_sum) * -$bil["eB0013"]);
          }
        //
        $extcon_sum = $extra['cos_divg_ind'] + $extra['cos_divg_amm'] + $extra['cos_divg_com'];
        if ($extcon_sum != 0)
          {
            $bil["eB14__ind"] = round (($extra['cos_divg_ind'] / $extcon_sum) * -$bil["eB0014"]);
            $bil["eB14__amm"] = round (($extra['cos_divg_amm'] / $extcon_sum) * -$bil["eB0014"]);
            $bil["eB14__com"] = round (($extra['cos_divg_com'] / $extcon_sum) * -$bil["eB0014"]);
          }
        //
        $extcon_sum = $extra['deb_breve'] + $extra['deb_medio'] + $extra['deb_lungo'];
        if ($extcon_sum != 0)
          {
            $bil["pD__breve"] = round (($extra['deb_breve'] / $extcon_sum) * $bil["pD"]);
            $bil["pD__medio"] = round (($extra['deb_medio'] / $extcon_sum) * $bil["pD"]);
            $bil["pD__lungo"] = round (($extra['deb_lungo'] / $extcon_sum) * $bil["pD"]);
          }
        //
        $bil["num_dip"] = $extra['num_dip'];
      }
    //
    // Riclassificazione al valore aggiunto.
    //
    if ($errore == "" && $ctrlnum > 0)
      {
        echo "<div><center><b>RICLASSIFICAZIONE AL VALORE AGGIUNTO AL ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        $bil["Rv"] = $bil["eA001"];
        echo "<tr><td align=\"center\">c.e. A1</td><td align=\"center\"> </td>";
        echo "<th align=\"left\">Ricavi netti di vendita</th>";
        echo "<th align=\"right\">".$bil["Rv"]."</th><td align=\"center\">Rv</td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. A4</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">costi patrimonializzati per lavori interni</td>";
        echo "<td align=\"right\">".$bil["eA004"]."</td><td align=\"center\">  </td></tr>\n";
        //
        // Note di Paolo Del Romano
        //
        // 1) se indichiamo con i simboli "RI_m" e "RF_m"   le rimanenze iniziali e finali  di materie prime, materie di consumo e merci
        // 2) indichiamo poi con i simboli "RI_p" e RF_p"   le rimanenze iniziali e finali  di materie prime, materie di consumo e merci
        // 3) la variazione delle scorte è gestita nelle configurazioni a "valore aggiunto " e "conto econonomico civilistico" nel seguente modo:
        // 3.1) nel "Valore della Produzione" si calcola facendo "RF_p" -  "RI_p"
        // 3.2) nel "Costo della Produzione" si calcola facendo "RI_m" - "RF_m"
        // 4) la variazione delle scorte è gestita nelle configurazioni a "costo del venduto" invece  nel seguente modo:
        // 4.1) le rimanenze di materie e di prodotti vengono trattati allo stesso modo e quindi la formula è: (RI_m +RI_p) - (RF_m + RF_p)
        //
        // Tutto questo casino puo' essere spiegato:
        // con un percorso logico-matematico ===>se io faccio "ValoreDellaProduzione" MENO "CostoDellaProduzione" e separo le rimanenze dei prodotti (che metto nel primo aggregato) dalle rimanenze di materie prime (che metto nel secondo aggregato) è naturale che io debba invertire il segno per le rimanenze di materie da quelle di prodotti
        // con un ragionamento economico ====>se durante l'anno:
        // mi si incrementano le scorte di prodotti è naturale che tale incremento debba concorrere positivamente a formare il valore della produzione (RF&minus;RI)
        // mi si incrementano le scorte di materie prime è naturale che io debba rettificare il costo della produzione (RI&minus;RF)
        //
        echo "<tr><td align=\"center\">c.e. A2+A3</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">variazione delle rimanenze di prodotti finiti, semilavorati, prodotti in lavorazione, lavorazioni in corso su ordinazioni (RF&minus;RI)</td>";
        echo "<td align=\"right\">".($bil["eA002"]+$bil["eA003"])."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. A5</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">altri ricavi e proventi di gestione</td>";
        echo "<td align=\"right\">".$bil["eA005"]."</td><td align=\"center\">  </td></tr>\n";
        //
        $bil["Vp"] = $bil["eA"];
        echo "<tr><td align=\"center\">c.e. A</td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Valore della produzione</th>";
        echo "<th align=\"right\">".$bil["Vp"]."</th><td align=\"center\">Vp</td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B6</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">costi netti per l'acquisto di materie prime, sussidiarie e merci</td>";
        echo "<td align=\"right\">".-$bil["eB006"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B11</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">variazione delle rimanenze di materie prime, sussidiarie, di consumo e merci (RI&minus;RF)</td>";
        echo "<td align=\"right\">".-$bil["eB0011"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B7+B8</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">costi per servizi e per godimento beni di terzi</td>";
        echo "<td align=\"right\">".-($bil["eB007"]+$bil["eB008"])."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B7+B8</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">altri costi diversi di gestione</td>";
        echo "<td align=\"right\">".-$bil["eB0014"]."</td><td align=\"center\">  </td></tr>\n";
        //
        $bil["Va"] = $bil["Vp"]-(-($bil["eB006"]+$bil["eB0011"]+$bil["eB007"]+$bil["eB008"]+$bil["eB0014"]));
        echo "<tr><td align=\"center\"> </td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Valore aggiunto</th>";
        echo "<th align=\"right\">".$bil["Va"]."</th><td align=\"center\">Va</td></tr>\n";
        //
        $bil["Cl"] = -$bil["eB009"];
        echo "<tr><td align=\"center\">c.e. B9</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">costi del personale</td>";
        echo "<td align=\"right\">".$bil["Cl"]."</td><td align=\"center\">Cl</td></tr>\n";
        //
        $bil["Mol"] = $bil["Va"]-$bil["Cl"];
        echo "<tr><td align=\"center\"> </td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Margine operativo lordo (EBITDA)</th>";
        echo "<th align=\"right\">".$bil["Mol"]."</th><td align=\"center\">Mol</td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B10a+B10b+B10c</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">ammortamenti</td>";
        echo "<td align=\"right\">".-($bil["eB0010a"]+$bil["eB0010b"]+$bil["eB0010c"])."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B10d</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">svalutazione crediti</td>";
        echo "<td align=\"right\">".-$bil["eB0010d"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B12+B13</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">accantonamenti a fondo rischi e oneri</td>";
        echo "<td align=\"right\">".-($bil["eB0012"]+$bil["eB0013"])."</td><td align=\"center\">  </td></tr>\n";
        //
        $bil["Ro"] = $bil["Mol"]-(-($bil["eB0010a"]+$bil["eB0010b"]+$bil["eB0010c"]+$bil["eB0010d"]+$bil["eB0012"]+$bil["eB0013"]));
        echo "<tr><td align=\"center\"> </td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Reddito operativo (EBIT)</td>";
        echo "<th align=\"right\">".$bil["Ro"]."</th><td align=\"center\">Ro</td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. C</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">risultato della gestione finanziaria</td>";
        echo "<td align=\"right\">".$bil["eC"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. D</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">risultato della gestione accessoria</td>";
        echo "<td align=\"right\">".$bil["eD"]."</td><td align=\"center\">  </td></tr>\n";
        //
        $bil["Rego"] = $bil["Ro"]+($bil["eC"]+$bil["eD"]);
        echo "<tr><td align=\"center\"> </td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Risultato economico della gestione ordinaria</td>";
        echo "<th align=\"right\">".$bil["Rego"]."</td><td align=\"center\"> </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. E19</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">risultato della gestione straordinaria</td>";
        echo "<td align=\"right\">".$bil["eE0019"]."</td><td align=\"center\">  </td></tr>\n";
        //
        $bil["Rl"] = $bil["Rego"]+$bil["eE0019"];
        echo "<tr><td align=\"center\"> </td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Risultato economico al lordo delle imposte</th>";
        echo "<th align=\"right\">".$bil["Rl"]."</th><td align=\"center\">Rl</td></tr>\n";
        //
        $bil["Tx"] = -$bil["e_0022"];
        echo "<tr><td align=\"center\">c.e. 22</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">imposte d'esercizio</td>";
        echo "<td align=\"right\">".$bil["Tx"]."</td><td align=\"center\">Tx</td></tr>\n";
        //
        $bil["Re"] = $bil["Rl"]-$bil["Tx"];
        echo "<tr><td align=\"center\"> </td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Utile o perdita d'esercizio</th>";
        echo "<th align=\"right\">".$bil["Re"]."</th><td align=\"center\">Re</td></tr>\n";
        //
        echo "</table>\n";
      }
    //
    // Informazioni extracontabili.
    //
    if ($errore == "" && $ctrlnum > 0 && $anno !=0)
      {
        echo "<div><center><b>INFORMAZIONI EXTRACONTABILI DELL'ANNO $anno</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        echo "<tr>";
        echo "<th align=\"center\"></th>";
        echo "<th align=\"center\"></td>";
        echo "<th align=\"center\">quota costi industriali</td>";
        echo "<th align=\"center\">quota costi amministrativi</td>";
        echo "<th align=\"center\">quota costi commerciali</td>";
        echo "<th align=\"center\">valore complessivo di bilancio</td><td>&nbsp;</td>";
        echo "</tr>";
        if ($bil["eB7__ind"] || $bil["eB7__amm"] || $bil["eB7__com"])
          {
            echo "<tr>";
            echo "<td align=\"center\">c.e. B7</td>";
            echo "<td align=\"center\">costi per servizi</td>";
            echo "<td align=\"right\">".$bil["eB7__ind"]."</td>";
            echo "<td align=\"right\">".$bil["eB7__amm"]."</td>";
            echo "<td align=\"right\">".$bil["eB7__com"]."</td>";
            echo "<td align=\"right\">".-$bil["eB007"]."</td><td>&nbsp;</td>";
            echo "</tr>";
          }
        if ($bil["eB8__ind"] || $bil["eB8__amm"] || $bil["eB8__com"])
          {
            echo "<tr>";
            echo "<td align=\"center\">c.e. B8</td>";
            echo "<td align=\"center\">costi per godimento di beni di terzi</td>";
            echo "<td align=\"right\">".$bil["eB8__ind"]."</td>";
            echo "<td align=\"right\">".$bil["eB8__amm"]."</td>";
            echo "<td align=\"right\">".$bil["eB8__com"]."</td>";
            echo "<td align=\"right\">".-$bil["eB008"]."</td><td>&nbsp;</td>";
            echo "</tr>";
          }
        if ($bil["eB9__ind"] || $bil["eB9__amm"] || $bil["eB9__com"])
          {
            echo "<tr>";
            echo "<td align=\"center\">c.e. B9</td>";
            echo "<td align=\"center\">costi per il personale</td>";
            echo "<td align=\"right\">".$bil["eB9__ind"]."</td>";
            echo "<td align=\"right\">".$bil["eB9__amm"]."</td>";
            echo "<td align=\"right\">".$bil["eB9__com"]."</td>";
            echo "<td align=\"right\">".-$bil["eB009"]."</td><td>&nbsp;</td>";
            echo "</tr>";
          }
        if ($bil["eB10__ind"] || $bil["eB10__amm"] || $bil["eB10__com"])
          {
            echo "<tr>";
            echo "<td align=\"center\">c.e. B10</td>";
            echo "<td align=\"center\">ammortamenti e svalutazioni</td>";
            echo "<td align=\"right\">".$bil["eB10__ind"]."</td>";
            echo "<td align=\"right\">".$bil["eB10__amm"]."</td>";
            echo "<td align=\"right\">".$bil["eB10__com"]."</td>";
            echo "<td align=\"right\">".-$bil["eB0010"]."</td><td>&nbsp;</td>";
            echo "</tr>";
          }
        if ($bil["eB12__ind"] || $bil["eB12__amm"] || $bil["eB12__com"])
          {
            echo "<tr>";
            echo "<td align=\"center\">c.e. B12</td>";
            echo "<td align=\"center\">accantonamenti per rischi</td>";
            echo "<td align=\"right\">".$bil["eB12__ind"]."</td>";
            echo "<td align=\"right\">".$bil["eB12__amm"]."</td>";
            echo "<td align=\"right\">".$bil["eB12__com"]."</td>";
            echo "<td align=\"right\">".-$bil["eB0012"]."</td><td>&nbsp;</td>";
            echo "</tr>";
          }
        if ($bil["eB13__ind"] || $bil["eB13__amm"] || $bil["eB13__com"])
          {
            echo "<tr>";
            echo "<td align=\"center\">c.e. B13</td>";
            echo "<td align=\"center\">altri accantonamenti</td>";
            echo "<td align=\"right\">".$bil["eB13__ind"]."</td>";
            echo "<td align=\"right\">".$bil["eB13__amm"]."</td>";
            echo "<td align=\"right\">".$bil["eB13__com"]."</td>";
            echo "<td align=\"right\">".-$bil["eB0013"]."</td><td>&nbsp;</td>";
            echo "</tr>";
          }
        if ($bil["eB14__ind"] || $bil["eB14__amm"] || $bil["eB14__com"])
          {
            echo "<tr>";
            echo "<td align=\"center\">c.e. B14</td>";
            echo "<td align=\"center\">oneri diversi di gestione</td>";
            echo "<td align=\"right\">".$bil["eB14__ind"]."</td>";
            echo "<td align=\"right\">".$bil["eB14__amm"]."</td>";
            echo "<td align=\"right\">".$bil["eB14__com"]."</td>";
            echo "<td align=\"right\">".-$bil["eB0014"]."</td><td>&nbsp;</td>";
            echo "</tr>";
          }
        //
        echo "<tr>";
        echo "<td colspan=\"6\">&nbsp;</td>";
        echo "<tr>\n";
        //
        $bil["Cind"] = $bil["eB7__ind"]+$bil["eB9__ind"]+$bil["eB10__ind"]+$bil["eB14__ind"];
        $bil["Camm"] = $bil["eB7__amm"]+$bil["eB9__amm"]+$bil["eB10__amm"]+$bil["eB14__amm"];
        $bil["Ccom"] = $bil["eB7__com"]+$bil["eB9__com"]+$bil["eB10__com"]+$bil["eB14__com"];
        echo "<tr>";
        echo "<th align=\"center\"></td>";
        echo "<th align=\"center\">totali</th>";
        echo "<th align=\"right\">".$bil["Cind"]."</th>";
        echo "<th align=\"right\">".$bil["Camm"]."</th>";
        echo "<th align=\"right\">".$bil["Ccom"]."</th>";
        echo "<th align=\"right\">".-($bil["eB007"]+$bil["eB009"]+$bil["eB0010"]+$bil["eB0014"])."</th><td>&nbsp;</td>";
        echo "</tr>";
        //
        echo "<tr>";
        echo "<td colspan=\"6\"><hr></td>";
        echo "<tr>\n";
        //
        echo "<tr>";
        echo "<th align=\"center\"></th>";
        echo "<th align=\"center\"></td>";
        echo "<th align=\"center\">a breve termine</td>";
        echo "<th align=\"center\">a medio termine</td>";
        echo "<th align=\"center\">a lungo termine</td>";
        echo "<th align=\"center\">valore complessivo di bilancio</td><td>&nbsp;</td>";
        echo "</tr>";
        if ($bil["pD__breve"] || $bil["pD__medio"] || $bil["pD__lungo"])
          {
            echo "<tr>";
            echo "<td align=\"center\">passivo D</td>";
            echo "<td align=\"center\">debiti</td>";
            echo "<td align=\"right\">".$bil["pD__breve"]."</td>";
            echo "<td align=\"right\">".$bil["pD__medio"]."</td>";
            echo "<td align=\"right\">".$bil["pD__lungo"]."</td>";
            echo "<td align=\"right\">".$bil["pD"]."</td><td>&nbsp;</td>";
            echo "</tr>";
          }
        //
        echo "<tr>";
        echo "<td colspan=\"6\"><hr></td>";
        echo "<tr>\n";
        //
        if ($bil["num_dip"])
          {
            echo "<tr>";
            echo "<th align=\"right\" colspan=\"5\">numero dipendenti</th>";
            echo "<th align=\"right\">".$bil["num_dip"]."</th><td>Ndip</td>";
            echo "</tr>";
          }
        //
        echo "</table>";
      }
    //
    // Costo del venduto.
    //
    if ($errore == "" && $ctrlnum > 0 && $anno !=0)
      {
        echo "<div><center><b>COSTO DEL VENDUTO DELL'ANNO $anno</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        echo "<tr><td align=\"center\">c.e. B6</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">acquisti di materie prime, sussidiarie, di consumo e merci</td>";
        echo "<td align=\"right\">".-$bil["eB006"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B7 (solo costi industriali)</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">spese per prestazioni di servizi</td>";
        echo "<td align=\"right\">".$bil["eB7__ind"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B8 (solo costi industriali)</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">spese per godimento di beni di terzi</td>";
        echo "<td align=\"right\">".$bil["eB8__ind"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B9 (solo costi industriali)</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">costi del personale</td>";
        echo "<td align=\"right\">".$bil["eB9__ind"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B10 (solo costi industriali)</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">ammortamenti e svalutazioni</td>";
        echo "<td align=\"right\">".$bil["eB10__ind"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B14 (solo costi industriali)</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">oneri diversi di gestione</td>";
        echo "<td align=\"right\">".$bil["eB14__ind"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. B11</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">variazione rimanenze materie prime, sussidiarie, di consumo e merci (RF&minus;RI)</td>";
        echo "<td align=\"right\">".$bil["eB0011"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. A2</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">variazione rimanenze prodotti in lavorazione, semilavorati e finiti (RF&minus;RI)</td>";
        echo "<td align=\"right\">".$bil["eA002"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. A3</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">variazione dei lavori in corso su ordinazione (RF&minus;RI)</td>";
        echo "<td align=\"right\">".$bil["eA003"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. A4</td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">costi patrimonializzati per lavori interni</td>";
        echo "<td align=\"right\">".$bil["eA004"]."</td><td align=\"center\">  </td></tr>\n";
        //
        $bil["Cv"] = (-$bil["eB006"])
                     +$bil["eB7__ind"]
                     +$bil["eB8__ind"]
                     +$bil["eB9__ind"]
                     +$bil["eB10__ind"]
                     +$bil["eB14__ind"]
                     -$bil["eB0011"]
                     -$bil["eA002"]
                     -$bil["eA003"]
                     -$bil["eA004"];
        //
        echo "<tr><td align=\"center\"> </td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Costo del venduto</th>";
        echo "<th align=\"right\">".$bil["Cv"]."</th><td align=\"center\">Cv</td></tr>\n";
        //
        echo "</table>\n";
      }
    //
    // Ricavi e costo del venduto.
    //
    if ($errore == "" && $ctrlnum > 0 && $anno !=0)
      {
        echo "<div><center><b>RICLASSIFICAZIONE A RICAVI E COSTO DEL VENDUTO</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        echo "<tr><td align=\"center\">c.e. A1</td><td align=\"center\">+</td>";
        echo "<th align=\"left\">Ricavi netti di vendita</th>";
        echo "<th align=\"right\">".$bil["Rv"]."</th><td align=\"center\">Rv</td></tr>\n";
        //
        echo "<tr><td align=\"center\"></td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">costo del venduto</td>";
        echo "<td align=\"right\">".$bil["Cv"]."</td><td align=\"center\">Cv</td></tr>\n";
        //
        $bil["Mli"] = $bil["Rv"]-$bil["Cv"];
        echo "<tr><td align=\"center\"></td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Margine lordo industriale</th>";
        echo "<th align=\"right\">".$bil["Mli"]."</th><td align=\"center\">Mli</td></tr>\n";
        //
        echo "<tr><td align=\"center\"></td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">costi commerciali</td>";
        echo "<td align=\"right\">".$bil["Ccom"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\"></td><td align=\"center\">&minus;</td>";
        echo "<td align=\"left\">costi amministrativi</td>";
        echo "<td align=\"right\">".$bil["Camm"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. A5</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">altri ricavi e proventi di gestione</td>";
        echo "<td align=\"right\">".$bil["eA005"]."</td><td align=\"center\">  </td></tr>\n";
        //
        echo "<tr><td align=\"center\"> </td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Reddito operativo (EBIT)</th>";
        echo "<th align=\"right\">".($bil["Mli"]-$bil["Ccom"]-$bil["Camm"]+$bil["eA005"])."</th><td align=\"center\">Ro</td></tr>\n";
        //
        echo "</table>\n";
      }
    //
    // Dati per indici.
    //
    if ($errore == "" && $ctrlnum > 0)
      {
        echo "<div><center><b>DATI PER GLI INDICI AL ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        $bil["Rm"] = $bil["aC01"];
        echo "<tr><td align=\"center\">attivo CI</td>";
        echo "<td align=\"left\">Rimanenze</td>";
        echo "<td align=\"right\">".$bil["Rm"]."</td><td align=\"center\">Rm</td></tr>\n";
        //
        $bil["Df"] = $bil["aC02"];
        echo "<tr><td align=\"center\">attivo CII</td>";
        echo "<td align=\"left\">Disponibilità finanziarie</td>";
        echo "<td align=\"right\">".$bil["Df"]."</td><td align=\"center\">Df</td></tr>\n";
        //
        $bil["Dl"] = $bil["aC04"];
        echo "<tr><td align=\"center\">attivo CIV</td>";
        echo "<td align=\"left\">Disponibilità liquide</td>";
        echo "<td align=\"right\">".$bil["Dl"]."</td><td align=\"center\">Dl</td></tr>\n";
        //
        $bil["Aci"] = $bil["aC"];
        echo "<tr><td align=\"center\">attivo C</td>";
        echo "<td align=\"left\">Attivo circolante</td>";
        echo "<td align=\"right\">".$bil["Aci"]."</td><td align=\"center\">Ac</td></tr>\n";
        //
        $bil["Im"] = $bil["aB"];
        echo "<tr><td align=\"center\">attivo B</td>";
        echo "<td align=\"left\">Immobilizzazioni</td>";
        echo "<td align=\"right\">".$bil["Im"]."</td><td align=\"center\">Im</td></tr>\n";
        //
        $bil["Ti"] = $bil["aA"]+$bil["aB"]+$bil["aC"]+$bil["aD"];
        echo "<tr><td align=\"center\">attivo A+B+C+D</td>";
        echo "<td align=\"left\">Totale impieghi</td>";
        echo "<td align=\"right\">".$bil["Ti"]."</td><td align=\"center\">Ti</td></tr>\n";
        //
        $bil["Rv"] = $bil["eA001"];
        echo "<tr><td align=\"center\">c.e. A1</td>";
        echo "<td align=\"left\">Ricavi di vendita</td>";
        echo "<td align=\"right\">".$bil["Rv"]."</td><td align=\"center\">Rv</td></tr>\n";
        //
        //$bil["Cl"] = -$bil["eB009"];
        echo "<tr><td align=\"center\">c.e. B9</td>";
        echo "<td align=\"left\">Costi del lavoro</td>";
        echo "<td align=\"right\">".$bil["Cl"]."</td><td align=\"center\">Cl</td></tr>\n";
        //
        $bil["Am"] = -($bil["eB0010a"]+$bil["eB0010b"]+$bil["eB0010c"]);
        echo "<tr><td align=\"center\">c.e. B10a+B10b+B10c</td>";
        echo "<td align=\"left\">Ammortamenti</td>";
        echo "<td align=\"right\">".$bil["Am"]."</td><td align=\"center\">Am</td></tr>\n";
        //
        $bil["Cd"] = $bil["pD"];
        echo "<tr><td align=\"center\">passivo D</td>";
        echo "<td align=\"left\">Capitale di debito (totale dei debiti a breve, a media e a lunga scadenza)</td>";
        echo "<td align=\"right\">".$bil["Cd"]."</td><td align=\"center\">Cd</td></tr>\n";
        //
        $bil["Cp"] = ($bil["pA"]-$bil["pA08"]-$bil["pA09"]);
        echo "<tr><td align=\"center\">passivo A-AVIII-AIX</td>";
        echo "<td align=\"left\">Capitale proprio</td>";
        echo "<td align=\"right\">".$bil["Cp"]."</td><td align=\"center\">Cp</td></tr>\n";
        //
        echo "<tr><td align=\"center\">c.e. 26</td>";
        echo "<td align=\"left\">Risultato economico d'esercizio</td>";
        echo "<td align=\"right\">".$bil["Re"]."</td><td align=\"center\">Re</td></tr>\n";
        //
        $bil["Tf"] = ($bil["pA"]+$bil["pB"]+$bil["pC"]+$bil["pD"]);
        echo "<tr><td align=\"center\">passivo A+B+C+D</td>";
        echo "<td align=\"left\">Totale fonti</td>";
        echo "<td align=\"right\">".$bil["Tf"]."</td><td align=\"center\">Tf</td></tr>\n";
        //
        $bil["Of"] = -($bil["eC0017"]);
        echo "<tr><td align=\"center\">c.e. C17</td>";
        echo "<td align=\"left\">Oneri finanziari</td>";
        echo "<td align=\"right\">".$bil["Of"]."</td><td align=\"center\">Of</td></tr>\n";
        //
        if ($bil["Cv"])
          {
            echo "<tr><td align=\"center\"> </td>";
            echo "<td align=\"left\">Costo del venduto</td>";
            echo "<td align=\"right\">".$bil["Cv"]."</td><td align=\"center\">Cv</td></tr>\n";
          }
        //
        echo "<tr><td align=\"center\"> </td>";
        echo "<td align=\"left\">Valore aggiunto</td>";
        echo "<td align=\"right\">".$bil["Va"]."</td><td align=\"center\">Va</td></tr>\n";
        //
        echo "<tr><td align=\"center\"> </td>";
        echo "<td align=\"left\">Reddito operativo (EBIT)</td>";
        echo "<td align=\"right\">".$bil["Ro"]."</td><td align=\"center\">Ro</td></tr>\n";
        //
        echo "</table>\n";
      }
    //
    // Analisi per redditività.
    //
    if ($errore == "" && $ctrlnum > 0)
      {
        echo "<div><center><b>ANALISI PER REDDITIVITÀ AL ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        $bil["ROE"] = ($bil["Cp"] == 0 ? 0 : $bil["Re"]/$bil["Cp"]);
        $bil["ROE"] = round ($bil["ROE"], 4);
        echo "<tr><td align=\"center\">ROE (return on equity)</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>utile netto d'esercizio<p><hr><p>capitale netto</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Re<p><hr><p>Cp</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Re"]."<p><hr><p>".$bil["Cp"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["ROE"]."</td>\n";
        //
        $bil["ROI"] = ($bil["Ti"] == 0 ? 0 : $bil["Ro"]/$bil["Ti"]);
        $bil["ROI"] = round ($bil["ROI"], 4);
        echo "<tr><td align=\"center\">ROI (return on investments)</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>reddito operativo<p><hr><p>totale impieghi</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Ro<p><hr><p>Ti</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Ro"]."<p><hr><p>".$bil["Ti"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["ROI"]."</td>\n";
        //
        $bil["ROD"] = ($bil["Cd"] == 0 ? 0 : $bil["Of"]/$bil["Cd"]);
        $bil["ROD"] = round ($bil["ROD"], 4);
        echo "<tr><td align=\"center\">ROD (return on debts)</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>oneri finanziari totali<p><hr><p>capitale di debito</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Of<p><hr><p>Cd</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Of"]."<p><hr><p>".$bil["Cd"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["ROD"]."</td>\n";
        //
        $bil["ROS"] = ($bil["Rv"] == 0 ? 0 : $bil["Ro"]/$bil["Rv"]);
        $bil["ROS"] = round ($bil["ROS"], 4);
        echo "<tr><td align=\"center\">ROS (return on sales)</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>reddito operativo<p><hr><p>ricavi di vendita</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Ro<p><hr><p>Rv</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Ro"]."<p><hr><p>".$bil["Rv"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["ROS"]."</td>\n";
        //
        $bil["RotImp"] = ($bil["Ti"] == 0 ? 0 : $bil["Rv"]/$bil["Ti"]);
        $bil["RotImp"] = round ($bil["RotImp"], 4);
        echo "<tr><td align=\"center\">rotazione degli impieghi</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>ricavi di vendita<p><hr><p>totale impieghi</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Rv<p><hr><p>Ti</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Rv"]."<p><hr><p>".$bil["Ti"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["RotImp"]."</td>\n";
        //
        $bil["Leverage"] = ($bil["Cp"] == 0 ? 0 : $bil["Ti"]/$bil["Cp"]);
        $bil["Leverage"] = round ($bil["Leverage"], 4);
        echo "<tr><td align=\"center\">Leverage</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>totale impieghi<p><hr><p>capitale proprio</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Ti<p><hr><p>Cp</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Ti"]."<p><hr><p>".$bil["Cp"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Leverage"]."</td>\n";
        //
        $bil["InGeNoCa"] = ($bil["Ro"] == 0 ? 0 : $bil["Re"]/$bil["Ro"]);
        $bil["InGeNoCa"] = round ($bil["InGeNoCa"], 4);
        echo "<tr><td align=\"center\">indice della gestione non caratteristica</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>utile netto d'esercizio<p><hr><p>reddito operativo</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Re<p><hr><p>Ro</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Re"]."<p><hr><p>".$bil["Ro"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["InGeNoCa"]."</td>\n";
        //
        echo "</table>\n";
      }
    //
    // Analisi per redditività.
    //
    if ($errore == "" && $ctrlnum > 0)
      {
        echo "<div><center><b>ANALISI PER PRODUTTIVITÀ AL ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        $bil["Va/Ti"] = ($bil["Ti"] == 0 ? 0 : $bil["Va"]/$bil["Ti"]);
        $bil["Va/Ti"] = round ($bil["Va/Ti"], 4);
        echo "<tr><td align=\"center\">indice di produttività del capitale investito</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>valore aggiunto<p><hr><p>totale impieghi</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Va<p><hr><p>Ti</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Va"]."<p><hr><p>".$bil["Ti"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Va/Ti"]."</td>\n";
        //
        $bil["Cl/Rv"] = ($bil["Rv"] == 0 ? 0 : $bil["Cl"]/$bil["Rv"]);
        $bil["Cl/Rv"] = round ($bil["Cl/Rv"], 4);
        echo "<tr><td align=\"center\">incidenza del fattore lavoro</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>costo del personale<p><hr><p>ricavi netti di vendita</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Cl<p><hr><p>Rv</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Cl"]."<p><hr><p>".$bil["Rv"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Cl/Rv"]."</td>\n";
        //
        if ($bil["num_dip"])
          {
            echo "<tr><td align=\"center\">rendimento del fattore umano</td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>valore aggiunto<p><hr><p>numero di dipendenti</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>Va<p><hr><p>Ndip</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>".$bil["Va"]."<p><hr><p>".$bil["num_dip"]."</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"right\">".round($bil["Va"]/$bil["num_dip"], 4)."</td>\n";
            //
            echo "<tr><td align=\"center\">costo medio per dipendente</td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>costi del personale<p><hr><p>numero di dipendenti</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>Cl<p><hr><p>Ndip</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>".$bil["Cl"]."<p><hr><p>".$bil["num_dip"]."</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"right\">".round($bil["Cl"]/$bil["num_dip"], 4)."</td>\n";
            //
            echo "<tr><td align=\"center\">fatturato medio per dipendente</td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>ricavi di vendita<p><hr><p>numero di dipendenti</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>Rv<p><hr><p>Ndip</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>".$bil["Rv"]."<p><hr><p>".$bil["num_dip"]."</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"right\">".round($bil["Rv"]/$bil["num_dip"], 4)."</td>\n";
          }
        //
        echo "</table>\n";
      }
    //
    // Analisi patrimoniale.
    //
    if ($errore == "" && $ctrlnum > 0)
      {
        echo "<div><center><b>ANALISI PATRIMONIALE AL ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        $bil["Im/Ti"] = ($bil["Ti"] == 0 ? 0 : $bil["Im"]/$bil["Ti"]);
        $bil["Im/Ti"] = round ($bil["Im/Ti"], 4);
        echo "<tr><td align=\"center\">rigidità degli impieghi</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>immobilizzazioni<p><hr><p>totale impieghi</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Im<p><hr><p>Ti</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Im"]."<p><hr><p>".$bil["Ti"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Im/Ti"]."</td>\n";
        //
        $bil["Aci/Ti"] = ($bil["Ti"] == 0 ? 0 : $bil["Aci"]/$bil["Ti"]);
        $bil["Aci/Ti"] = round ($bil["Aci/Ti"], 4);
        echo "<tr><td align=\"center\">elasticità degli impieghi</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>attivo circolante<p><hr><p>totale impieghi</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Ac<p><hr><p>Ti</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Aci"]."<p><hr><p>".$bil["Ti"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Aci/Ti"]."</td>\n";
        //
        $bil["Aci/Im"] = ($bil["Im"] == 0 ? 0 : $bil["Aci"]/$bil["Im"]);
        $bil["Aci/Im"] = round ($bil["Aci/Im"], 4);
        echo "<tr><td align=\"center\">indice di elasticità</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>attivo circolante<p><hr><p>immobilizzazioni</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Ac<p><hr><p>Im</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Aci"]."<p><hr><p>".$bil["Im"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Aci/Im"]."</td>\n";
        //
        $bil["Cp/Ti"] = ($bil["Ti"] == 0 ? 0 : $bil["Cp"]/$bil["Ti"]);
        $bil["Cp/Ti"] = round ($bil["Cp/Ti"], 4);
        echo "<tr><td align=\"center\">incidenza del capitale proprio (autonomia finanziaria)</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>capitale proprio<p><hr><p>totale impieghi</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Cp<p><hr><p>Ti</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Cp"]."<p><hr><p>".$bil["Ti"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Cp/Ti"]."</td>\n";
        //
        $bil["Cp/Cd"] = ($bil["Cd"] == 0 ? 0 : $bil["Cp"]/$bil["Cd"]);
        $bil["Cp/Cd"] = round ($bil["Cp/Cd"], 4);
        echo "<tr><td align=\"center\">grado di capitalizzazione</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>capitale proprio<p><hr><p>capitale di debito complessivo</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Cp<p><hr><p>Cd</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Cp"]."<p><hr><p>".$bil["Cd"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Cp/Cd"]."</td>\n";
        //
        echo "</table>\n";
      }
    //
    // Analisi finanziaria.
    //
    if ($errore == "" && $ctrlnum > 0)
      {
        echo "<div><center><b>ANALISI FINANZIARIA AL ".$_GET['giofin']."-".$_GET['mesfin']."-".$_GET['annfin']."</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        $bil["Cp/Im"] = ($bil["Im"] == 0 ? 0 : $bil["Cp"]/$bil["Im"]);
        $bil["Cp/Im"] = round ($bil["Cp/Im"], 4);
        echo "<tr><td align=\"center\">indice di autocopertura delle immobilizzazioni</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>capitale proprio<p><hr><p>immobilizzazioni</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Cp<p><hr><p>Im</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Cp"]."<p><hr><p>".$bil["Im"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Cp/Im"]."</td>\n";
        //
        $bil["Rv/Aci"] = ($bil["Aci"] == 0 ? 0 : $bil["Rv"]/$bil["Aci"]);
        $bil["Rv/Aci"] = round ($bil["Rv/Aci"], 4);
        echo "<tr><td align=\"center\">indice di rotazione dell'attivo circolante</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>ricavi di vendita<p><hr><p>attivo circolante</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Rv<p><hr><p>Ac</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Rv"]."<p><hr><p>".$bil["Aci"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Rv/Aci"]."</td>\n";
        //
        if ($bil["Cv"])
          {
            $bil["Cv/Rm"] = ($bil["Rm"] == 0 ? 0 : $bil["Cv"]/$bil["Rm"]);
            $bil["Cv/Rm"] = round ($bil["Cv/Rm"], 4);
            echo "<tr><td align=\"center\">indice di rotazione delle rimanenze</td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>costo del venduto<p><hr><p>rimanenze</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>Cv<p><hr><p>Rm</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>".$bil["Cv"]."<p><hr><p>".$bil["Rm"]."</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"right\">".$bil["Cv/Rm"]."</td>\n";
          }
        //
        $bil["Rv/Rm"] = ($bil["Rm"] == 0 ? 0 : $bil["Rv"]/$bil["Rm"]);
        $bil["Rv/Rm"] = round ($bil["Rv/Rm"], 4);
        echo "<tr><td align=\"center\">indice di rotazione delle scorte al valore di vendita</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>ricavi di vendita<p><hr><p>rimanenze</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Rv<p><hr><p>Rm</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Rv"]."<p><hr><p>".$bil["Rm"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Rv/Rm"]."</td>\n";
        //
        echo "</table>\n";
      }
    //
    // Stato patrimoniale rielaborato secondo criteri finanziari.
    //
    if ($errore == "" && $ctrlnum > 0 && $anno !=0)
      {
        echo "<div><center><b>STATO PATRIMONIALE RIELABORATO SECONDO CRITERI FINANZIARI DELL'ANNO $anno</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        echo "<tr><td align=\"center\" colspan=\"5\"><big>impieghi</impieghi></td></tr>\n";
        //
        echo "<tr><td align=\"center\" colspan=\"5\"><hr></td></tr>\n";
        //
        echo "<tr><td align=\"center\">attivo CIV</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">disponibilità liquide</td>";
        echo "<td align=\"right\">".$bil["Dl"]."</td><td align=\"center\">Dl</td></tr>\n";
        //
        echo "<tr><td align=\"center\">attivo A+CII+CIII+D</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">disponibilità finanziarie</td>";
        echo "<td align=\"right\">".$bil["Df"]."</td><td align=\"center\">Df</td></tr>\n";
        //
        echo "<tr><td align=\"center\">attivo CI</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">rimanenze</td>";
        echo "<td align=\"right\">".$bil["Rm"]."</td><td align=\"center\">Rm</td></tr>\n";
        //
        $bil["Aco"] = $bil["Dl"] + $bil["Df"] + $bil["Rm"];
        echo "<tr><td align=\"center\"></td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Attivo corrente (attivo circolante)</th>";
        echo "<th align=\"right\">".$bil["Aco"]."</th><td align=\"center\">Ac</td></tr>\n";
        //
        echo "<tr><td align=\"center\" colspan=\"5\">&nbsp;</td></tr>\n";
        //
        echo "<tr><td align=\"center\">BI</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">immobilizzazioni immateriali</td>";
        echo "<td align=\"right\">".$bil["aB01"]."</td><td align=\"center\"></td></tr>\n";
        //
        echo "<tr><td align=\"center\">BII</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">immobilizzazioni materiali</td>";
        echo "<td align=\"right\">".$bil["aB02"]."</td><td align=\"center\"></td></tr>\n";
        //
        echo "<tr><td align=\"center\">BIII</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">immobilizzazioni finanziarie</td>";
        echo "<td align=\"right\">".$bil["aB03"]."</td><td align=\"center\"></td></tr>\n";
        //
        //$bil["Ai"] = $bil["aB01"] + $bil["aB02"] + $bil["aB03"];
        echo "<tr><td align=\"center\"></td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Attivo immobilizzato</th>";
        echo "<th align=\"right\">".$bil["Im"]."</th><td align=\"center\">Im</td></tr>\n";
        //
        echo "<tr><td align=\"center\" colspan=\"5\">&nbsp;</td></tr>\n";
        //
        echo "<tr><td align=\"center\"></td><td align=\"center\"></td>";
        echo "<th align=\"left\">TOTALE IMPIEGHI</th>";
        echo "<th align=\"right\">".($bil["Aci"]+$bil["Im"])."</td><td align=\"center\"></td></tr>\n";
        //
        echo "<tr><td align=\"center\" colspan=\"5\"><hr></td></tr>\n";
        //
        //
        //
        echo "<tr><td align=\"center\" colspan=\"5\"><big>fonti</impieghi></td></tr>\n";
        //
        echo "<tr><td align=\"center\" colspan=\"5\"><hr></td></tr>\n";
        //
        $bil["Db"] = $bil["pB002"] + $bil["pB003"] + $bil["pD__breve"] + $bil["pE"];
        echo "<tr><td align=\"center\">passivo B2+B3+D+E<br>(D solo a breve)</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">debiti a breve scadenza</td>";
        echo "<td align=\"right\">".$bil["Db"]."</td><td align=\"center\">Db</td></tr>\n";
        //
        $bil["Dc"] = $bil["pB001"] + $bil["pC"] + $bil["pD__medio"] + $bil["pD__lungo"];
        echo "<tr><td align=\"center\">passivo B1+C+D<br>(D a medio e lungo)</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">debiti a media e lunga scadenza</td>";
        echo "<td align=\"right\">".$bil["Dc"]."</td><td align=\"center\">Dc</td></tr>\n";
        //
        $bil["Cd"] = $bil["Dc"] + $bil["Db"];
        echo "<tr><td align=\"center\"></td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Capitale di debito complessivo</th>";
        echo "<th align=\"right\">".$bil["Cd"]."</th><td align=\"center\">Cd</td></tr>\n";
        //
        echo "<tr><td align=\"center\" colspan=\"5\">&nbsp;</td></tr>\n";
        //
        $bil["Cp"] = $bil["pA"] - $bil["pA08"] - $bil["pA09"];
        echo "<tr><td align=\"center\">passivo A-AVIII-AIX</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">capitale proprio</td>";
        echo "<td align=\"right\">".$bil["Cp"]."</td><td align=\"center\">Cp</td></tr>\n";
        //
        $bil["Re"] = $bil["pA08"] + $bil["pA09"];
        echo "<tr><td align=\"center\">passivo AVIII+AIX</td><td align=\"center\">+</td>";
        echo "<td align=\"left\">utile o perdita d'esercizio</td>";
        echo "<td align=\"right\">".$bil["Re"]."</td><td align=\"center\">Re</td></tr>\n";
        //
        $bil["Pn"] = $bil["Cp"] + $bil["Re"];
        echo "<tr><td align=\"center\"></td><td align=\"center\">=</td>";
        echo "<th align=\"left\">Patrimonio netto</th>";
        echo "<th align=\"right\">".$bil["Pn"]."</th><td align=\"center\">Pn</td></tr>\n";
        //
        echo "<tr><td align=\"center\" colspan=\"5\">&nbsp;</td></tr>\n";
        //
        echo "<tr><td align=\"center\"></td><td align=\"center\">=</td>";
        echo "<th align=\"left\">TOTALE FONTI</th>";
        echo "<th align=\"right\">".($bil["Cd"]+$bil["Pn"])."</th><td align=\"center\"></td></tr>\n";
        //
        echo "</table>\n";
      }
    //
    // Analisi per redditività.
    //
    if ($errore == "" && $ctrlnum > 0 && $anno !=0)
      {
        echo "<div><center><b>ANALISI FINANZIARIA</b></CENTER></div>\n";
        echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">";
        //
        if ($bil["Im"] != 0)
          {
            echo "<tr><td align=\"center\">indice di autocopertura delle immobilizzazioni</td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>patrimonio netto<p><hr><p>attivo immobilizzato</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>Pn<p><hr><p>Im</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>".$bil["Pn"]."<p><hr><p>".$bil["Im"]."</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"right\">".round ($bil["Pn"]/$bil["Im"], 4)."</td>\n";
            //
            echo "<tr><td align=\"center\">indice di copertura globale delle immobilizzazioni</td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>(patrimonio netto + debiti a media e lunga scadenza)<p><hr><p>attivo immobilizzato</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>(Pn+Dc)<p><hr><p>Im</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>(".$bil["Pn"]."+".$bil["Dc"].")<p><hr><p>".$bil["Im"]."</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"right\">".round (($bil["Pn"]+$bil["Dc"])/$bil["Im"], 4)."</td>\n";
          }
        //
        if ($bil["Db"] != 0)
          {
            echo "<tr><td align=\"center\">indice di disponibilità</td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>attivo circolante<p><hr><p>debiti a breve</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>Ac<p><hr><p>Db</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>".$bil["Aci"]."<p><hr><p>".$bil["Db"]."</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"right\">".round ($bil["Aci"]/$bil["Db"], 4)."</td>\n";
          }
        //
        if ($bil["Db"] != 0)
          {
            echo "<tr><td align=\"center\">indice di liquidità secondaria</td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>(disponibilità liquide + disponibilità finanziarie)<p><hr><p>debiti a breve</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>(Dl+Df)<p><hr><p>Db</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>(".$bil["Dl"]."+".$bil["Df"].")<p><hr><p>".$bil["Db"]."</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"right\">".round (($bil["Dl"]+$bil["Df"])/$bil["Db"], 4)."</td>\n";
          }
        //
        if ($bil["Db"] != 0)
          {
            echo "<tr><td align=\"center\">indice di liquidità primaria</td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>disponibilità liquide<p><hr><p>debiti a breve</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>Dl<p><hr><p>Db</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"center\"><p>".$bil["Dl"]."<p><hr><p>".$bil["Db"]."</p></td>";
            echo "<td align=\"center\">=</td>";
            echo "<td align=\"right\">".round ($bil["Dl"]/$bil["Db"], 4)."</td>\n";
          }
        //
        $bil["Rv/Aci"] = ($bil["Aci"] == 0 ? 0 : $bil["Rv"]/$bil["Aci"]);
        $bil["Rv/Aci"] = round ($bil["Rv/Aci"], 4);
        echo "<tr><td align=\"center\">indice di rotazione dell'attivo circolante</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>ricavi delle vendite<p><hr><p>attivo circolante</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Rv<p><hr><p>Ac</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Rv"]."<p><hr><p>".$bil["Aci"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".$bil["Rv/Aci"]."</td>\n";
        //
        echo "<tr><td align=\"center\">margine di struttura primario</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>patrimonio netto &minus; attivo immobilizzato</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Pn&minus;Im</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Pn"]." &minus; ".$bil["Im"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".($bil["Pn"]-$bil["Im"])."      </td>\n";
        //
        echo "<tr><td align=\"center\">margine di struttura secondario</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>(patrimonio netto + debiti a media e lunga scadenza) &minus; attivo immobilizzato</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>(Pn+Dc)&minus;Im</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>(".$bil["Pn"]."+".$bil["Dc"].") &minus; ".$bil["Im"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".(($bil["Pn"]+$bil["Dc"])-$bil["Im"])."      </td>\n";
        //
        echo "<tr><td align=\"center\">patrimonio circolante netto</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Attivo circolante &minus; debiti a breve</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>Ac&minus;Db</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>".$bil["Aci"]."&minus;".$bil["Db"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".($bil["Aci"]-$bil["Db"])."      </td>\n";
        //
        echo "<tr><td align=\"center\">margine di tesoreria</td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>(disponibilità liquide + disponibilità finanziarie) &minus; debiti a breve</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>(Dl+Df) &minus; Db</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"center\"><p>(".$bil["Dl"]."+".$bil["Df"].") &minus; ".$bil["Db"]."</p></td>";
        echo "<td align=\"center\">=</td>";
        echo "<td align=\"right\">".(($bil["Dl"]+$bil["Df"])-$bil["Db"])."      </td>\n";
        //
        echo "</table>\n";
      }
  }
?>
</form>
<?php
require("../../library/include/footer.php");
?>
