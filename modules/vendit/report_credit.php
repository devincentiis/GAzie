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

$message = "";
if (isset($_GET['stampa']) and $message == "") {
        //Mando in stampa i movimenti contabili generati
        $locazione = "Location: stampa_liscre.php?annini=".$annini."&mesini=".$mesini."&giornini=".$giornini."&annfin=".$annfin."&mesfin=".$mesfin."&giornfin=".$giornfin;
        header($locazione);
        exit;
}
if (isset($_GET['partaperte']) and $message == "") {
        //Mando in stampa il riepilogo dei movimenti contabili non saldati elencati per data
        $locazione = "Location: print_schedule.php?orderby=2&annini=".$annini."&mesini=".$mesini."&giornini=".$giornini."&annfin=".$annfin."&mesfin=".$mesfin."&giornfin=".$giornfin;
        header($locazione);
        exit;
}
if (isset($_GET['Return'])) {
        header("Location:docume_vendit.php");
        exit;
}

$sqlquery= "SELECT COUNT(DISTINCT ".$gTables['rigmoc'].".id_tes) as nummov,codcon, ragso1, e_mail, telefo,".$gTables['clfoco'].".codice, sum(import*(darave='D')) as dare,sum(import*(darave='A')) as avere, sum(import*(darave='D') - import*(darave='A')) as saldo, darave FROM ".$gTables['rigmoc']." LEFT JOIN ".$gTables['tesmov']." ON ".$gTables['rigmoc'].".id_tes = ".$gTables['tesmov'].".id_tes LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['rigmoc'].".codcon = ".$gTables['clfoco'].".codice LEFT JOIN ".$gTables['anagra']." ON ".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra WHERE datreg between ".$annini.$mesini.$giornini." and ".$annfin.$mesfin.$giornfin." and codcon like '".$admin_aziend['mascli']."%' and caucon <> 'CHI' and caucon <> 'APE' or (caucon = 'APE' and codcon like '".$admin_aziend['mascli']."%' and datreg like '".$annini."%') GROUP BY codcon ORDER BY ragso1, darave";
$rs_castel = gaz_dbi_query($sqlquery);
require("../../library/include/header.php");
$script_transl=HeadMain(0,array('custom/modal_form'));

?><script>

$(function() {
    $( "#dialog" ).dialog({ autoOpen: false });
    $('.dialog_mail').click(function() {
      $.fx.speeds._default = 500;
      targetUrl = $(this).attr("url");
      //alert (targetUrl);
      $("p#mail_adrs").html($(this).attr("mail"));
      $("p#mail_attc").html($(this).attr("namedoc"));
      $( "#dialog" ).dialog({
         modal: "true",
         show: "blind",
         hide: "explode",
            buttons: {
                         " <?php echo $script_transl['submit']; ?> ": function() {
                            window.location.href = targetUrl;
                         },
                         " <?php echo $script_transl['cancel']; ?> ": function() {
                           $(this).dialog("close");
                         }
                     }
            });
      $("#dialog" ).dialog( "open" );
    });
});
</script>
<div style="display:none" id="dialog" title="<?php echo $script_transl['mail_alert0']; ?>">
      <p id="mail_alert1"><?php echo $script_transl['mail_alert1']; ?></p>
      <p class="ui-state-highlight" id="mail_adrs"></p>
      <p id="mail_alert2"><?php echo $script_transl['mail_alert2']; ?></p>
      <p class="ui-state-highlight" id="mail_attc"></p>
</div>
<form method="GET">
<div align="center" class="FacetFormHeaderFont">Crediti verso Clienti</div>
<table class="FacetFormTABLE" align="center">
<?php
if (! $message == "") {
    echo "<tr><td colspan=\"2\" class=\"FacetDataTDred\">".$message."</td></tr>";
}
?>
<tr>
<td class="FacetFieldCaptionTD">Data inizio &nbsp;</td>
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
<td class="FacetFieldCaptionTD">Data fine &nbsp;</td>
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
echo "<input type=\"submit\" name=\"stampa\" value=\"".$script_transl['print']." !\">&nbsp;";
echo "<br /><br />";
echo "<input type=\"submit\" name=\"partaperte\" value=\" ! PARTITE APERTE !\"><br /><br />";
?>
</td>
</tr>
</table>
</form>
<br />
<div class="box-primary table-responsive">
<table class="Tlarge table table-striped table-bordered table-condensed">
<?php
// creo l'array (header => campi) per l'ordinamento dei record
$headers_tesmov = array  (
          "Codice" => "",
          "Cliente" => "",
          "Telefono" => "",
          "Movimenti" => "",
          "Dare" => "",
          "Avere" => "",
          "Saldo" => "",
          "Riscuoti" => "" ,
          "Estr.Conto" => "",
          "Partite aperte" => ""
);
$linkHeaders = new linkHeaders($headers_tesmov);
$linkHeaders -> output();
?>
</tr>
<?php
$tot=0;
while ($r = gaz_dbi_fetch_array($rs_castel)) {
      if (abs($r['saldo']) >= 0.001) {
         echo "<tr class=\"FacetDataTD\">";
         echo "<td>".$r['codcon']."&nbsp;</td>";
         echo "<td><a title=\"Dettagli cliente\" href=\"report_client.php?nome=".$r["ragso1"]."\">".$r["ragso1"]."</a> &nbsp;</td>";
         echo "<td>".$r['telefo']." &nbsp;</td>";
         echo "<td align=\"center\">".$r['nummov']." &nbsp;</td>";
         echo "<td align=\"right\">".gaz_format_number($r['dare'])." &nbsp;</td>";
         echo "<td align=\"right\">".gaz_format_number($r['avere'])." &nbsp;</td>";
         echo "<td align=\"right\">".gaz_format_number($r['saldo'])." &nbsp;</td>";
         echo "<td align=\"center\"><a class=\"btn btn-xs btn-default btn-pagamento\" title=\"Effettuato un pagamento da ".$r["ragso1"]."\"  href=\"customer_payment.php?partner=".$r['codcon']."\"><i class=\"glyphicon glyphicon-euro\"></i></a></td>";
         echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" title=\"Stampa l'Estratto Conto di {$r['ragso1']}\" href=\"stampa_estcon.php?codice=".$r['codcon']."&annini=".$annini."&mesini=".$mesini."&giornini=".$giornini."&annfin=".$annfin."&mesfin=".$mesfin."&giornfin=".$giornfin."\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\"></i></a>";
		 if (!empty($r["e_mail"])) {
		 	echo '<a class="btn btn-xs btn-default dialog_mail" url="stampa_estcon.php?codice='.$r["codcon"].'&annini='.$annini.'&mesini='.$mesini.'&giornini='.$giornini.'&annfin='.$annfin.'&mesfin='.$mesfin.'&giornfin='.$giornfin.'&dest=E" href="#" title="mailto: '.$r["e_mail"].'"
		 	mail="'.$r["e_mail"].'" namedoc="Estratto conto al '.$giornfin.'-'.$mesfin.'-'.$annfin.'"><i class="glyphicon glyphicon-envelope"></i></a>';
		 } else {
			echo '<a title="Non hai memorizzato l\'email per questo cliente, inseriscila ora" href="admin_client.php?codice='.substr($r["codice"],3).'&Update"><i class="glyphicon glyphicon-edit"></i></a>';
		 }
		 echo "</td>";
         echo "<td align=\"center\"><a class=\"btn btn-xs btn-default\" title=\"Stampa le Partite Aperte di {$r['ragso1']}\" href=\"print_schedule.php?clfoco=".$r['codcon']."&orderby=2\" target=\"_blank\"><i class=\"glyphicon glyphicon-print\"></i></a>";
		 if (!empty($r["e_mail"])) {
		 	echo '<a class="btn btn-xs btn-default dialog_mail" url="print_schedule.php?clfoco='.$r['codcon'].'&annfin='.$annfin.'&mesfin='.$mesfin.'&giornfin='.$giornfin.'&orderby=2&dest=E" href="#" title="mailto: '.$r["e_mail"].'"
		 	mail="'.$r["e_mail"].'" namedoc="Partite aperte al '.$giornfin.'-'.$mesfin.'-'.$annfin.'"><i class="glyphicon glyphicon-envelope"></i></a>';
		 } else {
			echo '<a title="Non hai memorizzato l\'email per questo cliente, inseriscila ora" href="admin_client.php?codice='.substr($r["codice"],3).'&Update"><i class="glyphicon glyphicon-edit"></i></a>';
		 }
		 echo "</tr>";
         $tot += $r['saldo'];
      }
}
echo "<tr><td colspan=\"6\"></td><td class='FacetDataTD' style='border: 2px solid #666; text-align: center;'>".gaz_format_number($tot)."</td><td></td><td></td><td></td><td></td></tr>\n";
?>
</table>
</div>
<?php
require("../../library/include/footer.php");
?>
