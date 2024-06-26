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
$message = "";

$mastroclienti = $admin_aziend['mascli']."000000";
$iniclienti=$admin_aziend['mascli'].'000001';
$finclienti=$admin_aziend['mascli'].'999999';
$mastrofornitori = $admin_aziend['masfor']."000000";
$inifornitori=$admin_aziend['masfor'].'000001';
$finfornitori=$admin_aziend['masfor'].'999999';

if (!isset($_POST['gioemi'])) {
     $_POST['gioemi'] = date("d");
}
if (!isset($_POST['mesemi'])) {
     $_POST['mesemi'] = date("m");
}
if (!isset($_POST['annemi'])) {
     $_POST['annemi'] = date("Y");
}
if (!isset($_POST['descri'])) {
     $_POST['descri'] = "";
}
if (!isset($_POST['template'])) {
     $_POST['template'] = 'CartaIntestata';
}
if($_POST["template"] != 'CartaIntestata') {
   $_POST["descri"] = "";
}
if (!isset($_POST['cod_partner'])) {
         $_POST['cod_partner'] = 0;
}
if (!isset($_POST['cerca_partner'])) {
         $_POST['cerca_partner'] = "";
}
if (isset($_POST['newpartner'])) {
    $anagrafica = new Anagrafica();
    $partner = $anagrafica->getPartner($_POST['cod_partner']);
    $_POST['cerca__partner'] = substr($partner['ragso1'],0,4);
    $_POST['cod_partner'] = 0;
}

//controllo i campi
if (!checkdate( $_POST['mesemi'], $_POST['gioemi'], $_POST['annemi'])) {
    $message .= "La data ".$_POST['gioemi']."-".$_POST['mesemi']."-".$_POST['annemi']." non &egrave; corretta! <br>";
}

if (isset($_POST['stampa']) and $message == "") {
        if (isset($_POST['stadat'])) {
           $data = sprintf("%04d-%02d-%02d", $_POST['annemi'], $_POST['mesemi'], $_POST['gioemi']);
        } else {
           $data = '';
        }
        $_SESSION['print_request'] = array('data'=>$data,
                                           'clfoco'=>intval($_POST['cod_partner']),
                                           'template'=>$_POST['template'],
                                           'descrizione'=>$_POST['descri']);
        header("Location: invsta_intest.php");
        exit;
}

if (isset($_POST['Return'])) {
    header("Location: docume_inform.php");
    exit;
}

$titolo="Stampa Modulo vuoto - carta intestata ";
require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<form method="POST">
<div align="center" class="FacetFormHeaderFont"><?php echo $titolo; ?></div>
<table border="0" cellpadding="3" cellspacing="1" class="FacetFormTABLE" align="center">
<?php
if (! $message == "") {
    echo "<tr><td colspan=\"2\" class=\"FacetDataTDred\">$message</td></tr>\n";
}
?>
<tr>
<td class="FacetFieldCaptionTD">Modulo da stampare (template)&nbsp;</td>
<td class="FacetDataTD">
<select name="template" class="FacetSelect" onchange="this.form.submit()">
<?php
    $templates = array(
                       'Carta Intestata' => 'CartaIntestata',
                       'Fattura Accompagn.' => 'FatturaImmediata',
                       'Fattura Semplice' => 'FatturaSemplice',
                       'D.d.T.' => 'DDT'
                       );

foreach ($templates as $key=>$value) {
    $selected="";
    if($_POST["template"] == $value) {
        $selected = " selected ";
    }
    echo "<option value=\"".$value."\"".$selected."> ".$key." </option>\n";
}
?>
</select>
</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Descrizione/Num. del documento&nbsp;</td>
<td class="FacetDataTD"> <input title="Descrizione/numero del documento" type="text" name="descri" value=<?php echo "\"".$_POST["descri"]."\""; ?> maxlength="25" class="FacetInput">&nbsp;</td>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Stampa data&nbsp;</td>
<td class="FacetDataTD"><input title="Scegliere se stampare o meno la data inserita sotto" type="checkbox" name="stadat" value="1" ></td>
</tr>
<tr>
<td class="FacetFieldCaptionTD">Cliente/Fornitore</td>
<?php
echo "<td class=\"FacetColumnTD\">";
$messaggio = "";
$ric_mastro = substr($_POST['cod_partner'],0,3);
if ($_POST['cod_partner'] == 0) {
   $tabula =" tabindex=\"1\" ";
   if (strlen($_POST['cerca_partner']) >= 2) {
      $anagrafica = new Anagrafica();
      $partner = $anagrafica->queryPartners("*", "(codice between '$iniclienti' and '$finclienti' or codice between '$inifornitori' and '$finfornitori') and ragso1 like '{$_POST['cerca_partner']}%'", "ragso1 asc");
      if (sizeof($partner) > 0) {
         $tabula="";
         echo "\t<select name=\"cod_partner\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
         echo "<option value=\"000000000\"> ---------- </option>";
 		 foreach ($partner AS $key => $row) {
           $selected = "";
           if ($row["codice"] == $_POST['cod_partner']) {
               $selected = "selected";
           }
           echo "\t\t <option value=\"".$row["codice"]."\" $selected >".$row["ragso1"]."&nbsp;".$row["citspe"]."</option>\n";
         }
         echo "\t </select>\n";
      } else {
      $messaggio = "Non &egrave; stato trovato nulla";
      }
   } else {
      $messaggio = "Inserire min. 2 caratteri";
   }
   echo "\t<input type=\"text\" name=\"cerca_partner\" accesskey=\"e\" value=\"".$_POST['cerca_partner']."\" maxlength=\"15\"  class=\"FacetInput\">\n";
   echo $messaggio;
   //echo "\t <input type=\"image\" align=\"middle\" accesskey=\"c\" name=\"search\" src=\"../../library/images/cerbut.gif\"></td>\n";
   /** ENRICO FEDELE */
   /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
   echo '&nbsp;<button type="submit" class="btn btn-default btn-sm" name="search" accesskey="c"><i class="glyphicon glyphicon-search"></i></button></td>';
   /** ENRICO FEDELE */
} else {
   $anagrafica = new Anagrafica();
   $partner = $anagrafica->getPartner($_POST['cod_partner']);
   echo "<input type=\"submit\" value=\"".substr($partner['ragso1'],0,30)."\" name=\"newpartner\" title=\" Modifica \">\n";
   echo "\t<input type=\"hidden\" name=\"cod_partner\" value=\"".$_POST['cod_partner']."\">\n";
}
?>
</td></tr>
<tr>
<td class="FacetFieldCaptionTD">Data del documento &nbsp;</td>
<td class="FacetDataTD">
<?php
// select del giorno
echo "\t <select name=\"gioemi\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 1; $counter <= 31; $counter++ ) {
    $selected = "";
    if($counter ==  $_POST['gioemi']) {
        $selected = "selected";
    }
    echo "\t\t <option value=\"$counter\" $selected >$counter</option>\n";
}
echo "\t </select>\n";
// select del mese
echo "\t <select name=\"mesemi\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
$gazTimeFormatter->setPattern('MMMM');
for( $counter = 1; $counter <= 12; $counter++ ) {
  $selected = "";
  if($counter == $_POST['mesemi']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
  echo "\t\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
// select del anno
echo "\t <select name=\"annemi\" class=\"FacetSelect\" onchange=\"this.form.submit()\">\n";
for( $counter = 2002; $counter <= 2030; $counter++ ) {
    $selected = "";
    if($counter == $_POST['annemi'])
        $selected = "selected";
    echo "\t\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select>\n";
?>
</td>
<tr>
<tr class="FacetFooterTD">
<td>
</td>
<td align="center" ">
<?php
echo "<input type=\"submit\" class=\"btn btn-warning\" name=\"stampa\" value=\"Stampa\" >\n";
?>
</td>
</tr>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
