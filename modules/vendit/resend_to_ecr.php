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
    scriva   alla   Free  Software Foundation,  Inc.,   59
    Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
 --------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");
$admin_aziend=checkAdmin();
$msg = '';

$gForm = new venditForm();
if (!isset($_POST['ritorno'])) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
    $form['ritorno'] = $_POST['ritorno'];
}

if (!isset($_GET['id_tes']) || isset($_POST['return'])) {
    header("Location: ".$form['ritorno']);
    exit;
}

if (isset($_GET['id_tes'])) {
    $tesdoc = gaz_dbi_get_row($gTables['tesdoc'],"id_tes",intval($_GET['id_tes']));
    // recupero i righi
    $rs_rows = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = ".intval($_GET['id_tes']),"id_rig");
    $tot = 0;
    $next_row = 0;
    while ($v = gaz_dbi_fetch_array($rs_rows)) {
            // calcolo importo totale (iva inclusa) del rigo e creazione castelletto IVA
            if ($v['tiprig'] <= 1) {    //ma solo se del tipo normale o forfait
               if ($v['tiprig'] == 0) { // tipo normale
                   $tot_row = CalcolaImportoRigo($v['quanti'], $v['prelis'],array($v['sconto'],$tesdoc['sconto'],-$v['pervat']));
               } else {                 // tipo forfait
                   $tot_row = CalcolaImportoRigo(1,$v['prelis'],-$v['pervat']);
               }
               if (!isset($castel[$v['codvat']])) {
                  $castel[$v['codvat']]=0.00;
               }
               $castel[$v['codvat']]+=$tot_row;
               // calcolo il totale del rigo stornato dell'iva
               $imprig=round($tot_row/(1+$v['pervat']/100),2);
               $tot+=$tot_row;
            }
            // fine calcolo importo rigo, totale e castelletto IVA
    }
} else {

}

$ecr=$gForm->getECRdata($tesdoc['id_contract']);

if (isset($_POST['ins'])) {
            $tesdoc = gaz_dbi_get_row($gTables['tesdoc'],"id_tes",intval($_GET['id_tes']));
            $ecr = gaz_dbi_get_row($gTables['cash_register'], 'id_cash', $tesdoc['id_contract']);
            $classname=substr($ecr['driver'],0,-4);
            // recupero i righi
            $rs_rows = gaz_dbi_dyn_query("*", $gTables['rigdoc'], "id_tes = ".intval($_GET['id_tes']),"id_rig");
            // INIZIO l'invio dello scontrino alla stampante fiscale dell'utente
            require("../../library/cash_register/".$ecr['driver']);
            $ticket_printer = new $classname;
            $ticket_printer->set_serial($ecr['serial_port']);
            $ticket_printer->open_ticket();
            $tot=0;
            while ($v = gaz_dbi_fetch_array($rs_rows)) {
                if ($v['tiprig'] <= 1) {    // se del tipo normale o forfait
                    if ($v['tiprig'] == 0) { // tipo normale
                       $tot_row = CalcolaImportoRigo($v['quanti'], $v['prelis'],array($v['sconto'],$tesdoc['sconto'],-$v['pervat']));
                    } else {                 // tipo forfait
                       $tot_row = CalcolaImportoRigo(1,$v['prelis'],-$v['pervat']);
                       $v['quanti']=1;
                       $v['codart']=$v['descri'];
                       $v['descri']=false;
                    }
                    $descricalc=floatval($v['quanti']).'x'.round($tot_row/$v['quanti'],$admin_aziend['decimal_price']);
                    $reparto = gaz_dbi_get_row($gTables['cash_register_reparto'], 'cash_register_id_cash', $tesdoc['id_contract'], " AND aliiva_codice = ".$v['codvat']);
                    $rep=($reparto)?$reparto['reparto']:'1R';
                    $ticket_printer->row_ticket($tot_row,$descricalc,$v['codvat'],$v['codart'],$rep, $v['descri']);
                    $tot+=$tot_row;
                } elseif ($v['tiprig'] == 5) {    // se lotteria scontrini
                    $cmdlotteria=(strlen(trim($ecr['codicelotteria']))>=1)?trim($ecr['codicelotteria']):'L';
                    $ticket_printer->lotteria_scontrini(strtoupper($v['descri']),$cmdlotteria);
                } else {                    // se descrittivo
                    $desc_arr=str_split(trim($v['descri']),24);
                    foreach ($desc_arr as $d_v) {
                             $ticket_printer->descri_ticket($d_v);
                    }
                }
            }
            if (!empty($tesdoc['spediz'])) { // � stata impostata la stampa del codice fiscale
               $ticket_printer->descri_ticket('CF= '.$tesdoc['spediz']);
            }
            $tender = gaz_dbi_get_row($gTables['cash_register_tender'], 'cash_register_id_cash', $tesdoc['id_contract'], " AND pagame_codice = ".$tesdoc['pagame']);
            $tender=($tender)?$tender['tender']:'1T';
            $ticket_printer->pay_ticket('','',$tender);
            $ticket_printer->close_ticket();
            // FINE invio
            header("Location: ".$form['ritorno']);
            exit;
}
require("../../library/include/header.php");
$script_transl = HeadMain(0);
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['head'].$tesdoc['numdoc'].'('.gaz_format_date($tesdoc['datemi']).')'.$script_transl['on'].'<font class="FacetDataTD">'.$ecr['descri']."</font></div>\n";
echo "<form method=\"POST\" name=\"ecr\">\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\" />\n";
echo "<table class=\"Tsmall\" align=\"center\">\n";
echo "\t<tr><td colspan=\"2\" class=\"FacetDataTDred\">".$script_transl['message']."</td></tr>\n";
echo "<tr><td colspan=\"2\" align=\"center\">".$script_transl['total'].' '.$admin_aziend['html_symbol'].' '.gaz_format_number($tot)."</td></tr>\n";
echo "<tr><td align=\"left\"> <input type=\"submit\" name=\"return\" value=\"".$script_transl['return']."\" /></td><td align=\"right\"><input  style=\"color:red;\" type=\"submit\" name=\"ins\" value=\"".$script_transl['submit']."\" /></td></tr>\n";
echo "</table>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>