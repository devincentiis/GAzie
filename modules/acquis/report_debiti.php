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

$admin_aziend=checkAdmin();
if(!isset($_GET["annfin"])) {
    $giornfin = intval(date("d"));
    $mesfin = intval(date("m"));
    $annfin = intval(date("Y"));
} else {
    $giornfin = intval($_GET["giornfin"]);
    $mesfin = intval($_GET["mesfin"]);
    $annfin = intval($_GET["annfin"]);
}
if(!isset($_GET["annini"])) {
	// controllo l'ultima apertura conti disponibile
    $rs_ultima_apertura = gaz_dbi_dyn_query("*", $gTables['tesmov'], "caucon = 'APE'", "datreg DESC", 0, 1);
    $ultima_apertura = gaz_dbi_fetch_array($rs_ultima_apertura);
    if ($ultima_apertura){
		$giornini = substr($ultima_apertura['datreg'],8,2);
		$mesini = substr($ultima_apertura['datreg'],5,2);
		$annini = substr($ultima_apertura['datreg'],0,4);
	} else {
		// non avendo aperture trovo la prima registrazione
		$rs_prima_registrazione = gaz_dbi_dyn_query("*", $gTables['tesmov'], 1 , "datreg ASC", 0, 1);
		$prima_registrazione = gaz_dbi_fetch_array($rs_prima_registrazione);
		if ($prima_registrazione) {
			$giornini = substr($prima_registrazione['datreg'],8,2);
			$mesini = substr($prima_registrazione['datreg'],5,2);
			$annini = substr($prima_registrazione['datreg'],0,4);
		} else {
			$giornini = 1;
			$mesini = 1;
			$annini = date("Y");
		}
	}
} else {
    $giornini = intval($_GET["giornini"]);
    $mesini = intval($_GET["mesini"]);
    $annini = intval($_GET["annini"]);
}

$giornfin = str_pad($giornfin, 2, "0", STR_PAD_LEFT);
$mesfin = str_pad($mesfin, 2, "0", STR_PAD_LEFT);

$giornini = str_pad($giornini, 2, "0", STR_PAD_LEFT);
$mesini = str_pad($mesini, 2, "0", STR_PAD_LEFT);

if (isset($_GET['stampa']) and $message == "") {
    //Mando in stampa i movimenti contabili generati
    $locazione = "Location: stampa_lisdeb.php?annini=".$annini."&mesini=".$mesini."&giornini=".$giornini."&annfin=".$annfin."&mesfin=".$mesfin."&giornfin=".$giornfin;
    header($locazione);
    exit;
}
if (isset($_GET['Return'])) {
    header("Location:docume_acquis.php");
    exit;
}

$sqlquery= "SELECT COUNT(DISTINCT ".$gTables['rigmoc'].".id_tes) AS nummov,codcon, ragso1, telefo, SUM(import*(darave='D')) AS dare, SUM(import*(darave='A'))AS avere, SUM(import*(darave='D') - import*(darave='A')) AS saldo, darave
            FROM ".$gTables['rigmoc']." LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes
                                        LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice
                                        LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
                                        WHERE datreg between ".$annini.$mesini.$giornini." and ".$annfin.$mesfin.$giornfin." and codcon like '".$admin_aziend['masfor']."%' and caucon <> 'CHI' and caucon <> 'APE' or (caucon = 'APE' and codcon like '".$admin_aziend['masfor']."%' and datreg like '".$annini."%') GROUP BY codcon ORDER BY ragso1, darave";
$rs_castel = gaz_dbi_query($sqlquery);
require("../../library/include/header.php");
$script_transl=HeadMain();
?>
<form method="GET">
<div align="center" class="FacetFormHeaderFont"><?php echo $script_transl['title']; ?></div>
<table class="FacetFormTABLE" align="center">
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['start_date']; ?> &nbsp;</td>
<td align="center" nowrap class="FacetFooterTD">
	<!--// select del giorno-->
	<select name="giornini" class="FacetSelect" onchange="this.form.target='_self'; this.form.submit()">
<?php
for( $counter = 1; $counter <= 31; $counter++ ) {
    $selected = "";
    if($counter == $giornini)
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
?>
	</select> /
	<!--// select del mese-->
	<select name="mesini" class="FacetSelect" onchange="this.form.target='_self'; this.form.submit()">
<?php
for( $counter = 1; $counter <= 12; $counter++ ) {
    $selected = "";
    if($counter == $mesini)
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
?>
	</select> /
	<!--// select del anno-->
	<select name="annini" class="FacetSelect" onchange="this.form.target='_self'; this.form.submit()">
<?php
for( $counter = date("Y")-10 ; $counter <= date("Y")+2; $counter++ ) {
    $selected = "";
    if($counter == $annini)
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
?>
	</select>
</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"><?php echo $script_transl['end_date']; ?> &nbsp;</td>
<td align="center" nowrap class="FacetFooterTD">
	<!--// select del giorno-->
	<select name="giornfin" class="FacetSelect" onchange="this.form.target='_self'; this.form.submit()">
<?php
for( $counter = 1; $counter <= 31; $counter++ ) {
    $selected = "";
    if($counter == $giornfin)
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
?>
	</select> /
	<!--// select del mese-->
	<select name="mesfin" class="FacetSelect" onchange="this.form.target='_self'; this.form.submit()">
<?php
for( $counter = 1; $counter <= 12; $counter++ ) {
    $selected = "";
    if($counter == $mesfin)
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
?>
	</select> /
	<!--// select del anno-->
	<select name="annfin" class="FacetSelect" onchange="this.form.target='_self'; this.form.submit()">
<?php
for( $counter = date("Y")-10 ; $counter <= date("Y")+2; $counter++ ) {
    $selected = "";
    if($counter == $annfin)
            $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
?>
	</select>
</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD"></td>
<td colspan="3" align="right" nowrap class="FacetFooterTD">
<input type="submit" name="Return" value="Indietro">
<?php
echo "<input type=\"submit\" name=\"stampa\" value=\"".$script_transl['print']."!\" /> &nbsp;";
?>
</td>
</tr>
</table>
</form>
<div class="table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed">
<?php
$headers_tesmov = array  (
          $script_transl['codice'] => "",
          $script_transl['partner'] => "",
          $script_transl['telefo'] => "",
          $script_transl['mov'] => "",
          $script_transl['dare'] => "",
          $script_transl['avere'] => "",
          $script_transl['saldo'] => "",
          $script_transl['pay'] => "",
          $script_transl['statement'] => ""
);
$linkHeaders = new linkHeaders($headers_tesmov);
$linkHeaders -> output();
$tot=0;
while ($r = gaz_dbi_fetch_array($rs_castel)) {
      if ($r['saldo'] != 0) {
         echo "<tr class=\"FacetDataTD\">";
         echo "<td>".$r['codcon']."&nbsp;</td>";
         echo "<td>".$r['ragso1']." &nbsp;</td>";
         echo "<td>".$r['telefo']." &nbsp;</td>";
         echo "<td align=\"center\">".$r['nummov']." &nbsp;</td>";
         echo "<td align=\"right\">".gaz_format_number($r['dare'])." &nbsp;</td>";
         echo "<td align=\"right\">".gaz_format_number($r['avere'])." &nbsp;</td>";
         echo "<td align=\"right\">".gaz_format_number($r['saldo'])." &nbsp;</td>";
         echo "<td align=\"center\" title=\"".$script_transl['pay_title'].$r['ragso1']."\">
		 		<a class=\"btn btn-xs btn-default\" href=\"supplier_payment.php?partner=".$r["codcon"]."\">
					<i class=\"glyphicon glyphicon-piggy-bank\"></i>
				</a>
			</td>";
         
			/** ENRICO FEDELE */
		 echo "<td align=\"center\" title=\"".$script_transl['statement_title'].$r['ragso1']."\">
		 		<a class=\"btn btn-xs btn-default\" href=\"../contab/select_partit.php?id=".$r['codcon']."&yi=".$annini."&yf=".$annfin."\"  target=\"_blank\">
					<i class=\"glyphicon glyphicon-eye-open\"></i>
					<i class=\"glyphicon glyphicon-print\"></i>
				</a>
			  </td>";
         echo "</tr>\n";
         $tot += $r['saldo'];
      }
}
echo "<tr><td colspan=\"6\"></td><td class='FacetDataTD' style='border: 2px solid #666; text-align: center;'>".gaz_format_number($tot)."</td><td></td><td></td></tr>\n";
?>
</table></div>
<?php
require("../../library/include/footer.php");
?>