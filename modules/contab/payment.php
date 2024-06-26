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
$admin_aziend = checkAdmin();
$msg = '';
$anagrafica = new Anagrafica();

function controllo($dataRegistrazione, $datdoc, $target_account) {
   $retval = true;
   if (empty($target_account)) {
      alert("Errore: conto non selezionato");
      return false;
   }
   if ($dataRegistrazione < $datdoc) {
      alert("Errore: data di registrazione antecedente alla data di apertura della partita");
      return false;
   }
   return $retval;
}

function salvaMovimento($descrizione, $importo, $target_account, $dataRegistrazione, $numpar, $partner, $datdoc, $clfr) {
   $valore = array(
       "caucon" => "",
       "descri" => $descrizione,
       "datreg" => $dataRegistrazione,
       "seziva" => "1", // eventualmente da modificare
       "id_doc" => 0,
       "protoc" => 0,
       "numdoc" => $numpar,
       "datdoc" => $datdoc,
       "clfoco" => $partner,
       "regiva" => "",
       "operat" => 0,
       "libgio" => ""
   );
   $idInserito = gaz_dbi_table_insert("tesmov", $valore);
   if ($clfr == "C") {
      $contoDare = $target_account;
      $contoAvere = $partner;
   } else {
      $contoAvere = $target_account;
      $contoDare = $partner;
   }

   $valore = array(
       "id_rig" => null,
       "id_tes" => $idInserito,
       "darave" => "D",
       "codcon" => $contoDare,
       "import" => $importo);
   $codiceOp1 = gaz_dbi_table_insert("rigmoc", $valore);
   $valore = array(
       "id_rig" => null,
       "id_tes" => $idInserito,
       "darave" => "A",
       "codcon" => $contoAvere,
       "import" => $importo);
   $codiceOp2 = gaz_dbi_table_insert("rigmoc", $valore);
   $valore = array(
       "id" => null,
       "id_tesdoc_ref" => $numpar,
       "id_rigmoc_pay" => ($clfr != "C" ? $codiceOp1 : 0),
       "id_rigmoc_doc" => ($clfr != "C" ? 0 : $codiceOp2),
       "amount" => $importo,
       "expiry" => $dataRegistrazione);
   gaz_dbi_table_insert("paymov", $valore);
}


function creaListaConti() {
  global $admin_aziend, $gTables;
  $conto = "<select name=\"target_account\" tabindex=\"4\"   class=\"FacetSelect\">\n"; //impropriamente usato per il numero di conto d'accredito
  $masban = $admin_aziend['masban'] * 1000000;
  $casse = substr($admin_aziend['cassa_'], 0, 3);
  $mascas = $casse * 1000000;
  $res = gaz_dbi_dyn_query('*', $gTables['clfoco'], "(codice LIKE '$casse%' AND codice > '$mascas') or (codice LIKE '" . $admin_aziend['masban'] . "%' AND codice > '$masban')", "codice ASC"); //recupero i c/c
  $conto = $conto . "\t\t <option value=\"0\">--------------------------</option>\n";
  while ($a = gaz_dbi_fetch_array($res)) {
   $sel = "";
   $conto = $conto . "\t\t <option value=\"" . $a["codice"] . "\" $sel >" . $a["codice"] . " - " . $a["descri"] . "</option>\n";
  }
  $conto = $conto . "\t </select>";
  return $conto;
}


if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
  $form['hidden_req'] = '';
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $form['date_reg_D'] = date("d");
  $form['date_reg_M'] = date("m");
  $form['date_reg_Y'] = date("Y");
  $form['importo'] = abs($_GET['importo']);
  $form['numpar'] = intval($_GET['numpar']);
  $form['partner'] = intval($_GET['partner']);
  $form['datdoc'] = substr($_GET['datdoc'],0,10);
  $form['numdoc'] = substr($_GET['numdoc'],0,15);
  $form['descrizione'] = ($admin_aziend['mascli'] == substr($_GET['partner'],0,3))? "INCASSO FT." : "PAGAMENTO FT.";
  $form['descrizione'] .= $form['numdoc'].' DEL '.$form['datdoc'];
  $form['target_account'] = 0;
} else { // accessi successivi
  $form['hidden_req'] = htmlentities($_POST['hidden_req']);
  $form['ritorno'] = $_POST['ritorno'];
  $form['descrizione'] = $_POST['descrizione'];
  $form['date_reg_D'] = intval($_POST['date_reg_D']);
  $form['date_reg_M'] = intval($_POST['date_reg_M']);
  $form['date_reg_Y'] = intval($_POST['date_reg_Y']);
  $form['importo'] = floatval($_POST['importo']);
  $form['numpar'] = intval($_POST['numpar']);
  $form['partner'] = intval($_POST['partner']);
  $form['datdoc'] = substr($_POST['datdoc'],0,10);
  $form['numdoc'] = substr($_POST['numdoc'],0,15);
  $form['target_account'] = intval($_POST['target_account']);
  if (isset($_POST['salva'])) {
    $clfr = ($admin_aziend['mascli'] == substr($form['partner'],0,3))? "C" : "F";
    $dd = gaz_format_date($form['datdoc'], true);
    $date_doc = new DateTime($dd);
    $dr = $form['date_reg_Y'].'-'.$form['date_reg_M'].'-'.$form['date_reg_D'];
    $date_reg = new DateTime($dr);
    if ($date_reg < $date_doc){
      $msg .= "Errore: data di registrazione antecedente alla data di apertura della partita! <br/>";
    }
    if (empty($form['target_account'])) {
      $msg .= "Errore: conto non selezionato! <br/>";
    }
    if (empty($msg)) {
      salvaMovimento($form['descrizione'], $form['importo'], $form['target_account'], $dr, $form['numpar'], $form['partner'], $dd, $clfr);
      header("Location: ".$form['ritorno']."?ok_payment");
      exit;
    }
  }
  if (isset($_POST['annulla'])) {
    header("Location: ".$form['ritorno']."?ok_payment");
    exit;
  }
}

$gForm = new GAzieForm();
require("../../library/include/header.php");
$script_transl = HeadMain(0, array('calendarpopup/CalendarPopup'));
echo "<script type=\"text/javascript\">
var cal = new CalendarPopup();
var calName = '';
function setMultipleValues(y,m,d) {
     document.getElementById(calName+'_Y').value=y;
     document.getElementById(calName+'_M').selectedIndex=m*1-1;
     document.getElementById(calName+'_D').selectedIndex=d*1-1;
}
function setDate(name) {
  calName = name.toString();
  var year = document.getElementById(calName+'_Y').value.toString();
  var month = document.getElementById(calName+'_M').value.toString();
  var day = document.getElementById(calName+'_D').value.toString();
  var mdy = month+'/'+day+'/'+year;
  cal.setReturnFunction('setMultipleValues');
  cal.showCalendar('anchor', mdy);
}
</script>
";
$partner = $anagrafica->getPartner($form['partner']);
?>

<div class="FacetFormHeaderFont" align="center">Registrazione pagamento relativo a partita <?php echo $form['numpar'].' di '.$partner['ragso1'].' '.$partner['ragso2']; ?></div>

<form action="payment.php" method="POST">
    <input type="hidden" name="ritorno" value="<?php echo $form['ritorno']; ?>">
    <input type="hidden" name="hidden_req" value="<?php echo $form['hidden_req']; ?>">
    <input type="hidden" name="numpar" value="<?php echo $form['numpar']; ?>">
    <input type="hidden" name="partner" value="<?php echo $form['partner']; ?>">
    <input type="hidden" name="numdoc" value="<?php echo $form['numdoc']; ?>">
    <input type="hidden" name="datdoc" value="<?php echo $form['datdoc']; ?>">
<table class="Tmiddle">
  <tbody>
<?php
if (!empty($msg)) {
    echo '<tr><td colspan="3" class="FacetDataTDred">'.$msg."</td></tr>\n";
}
?>
  <tr>
      <td class="FacetFieldCaptionTD">Data di registrazione</td>
      <td colspan="5" class="FacetDataTD">
          <?php $gForm->CalendarPopup('date_reg', $form['date_reg_D'], $form['date_reg_M'], $form['date_reg_Y'], 'FacetSelect', 1); ?>
      </td>
  </tr>
  <tr>
      <td class="FacetFieldCaptionTD">Descrizione</td>
      <td class="FacetDataTD" colspan="2">
          <input id="descrizione" name="descrizione" value="<?php echo $form['descrizione']; ?>" maxlength="100" required="true" type="text">
      </td>
  </tr>
  <tr>
      <td class="FacetFieldCaptionTD">Importo</td>
      <td colspan="2" class="FacetDataTD">
          <input id="prezzoUnitario" name="importo" value="<?php echo $form['importo']; ?>" maxlength="10" required="true" step="any" type="number">
      </td>
  </tr>
  <tr>
      <td class="FacetFieldCaptionTD">Conto</td>
      <td class="FacetDataTD" colspan="2">
          <?php echo creaListaConti(); ?>
      </td>
  </tr>
  <tr class="FacetFieldCaptionTD">
      <td class="text-center"><input type="button" name="annulla" value="<?php echo $script_transl['return']; ?>"></td>
      <td class="text-center">
           <input type="submit" name="salva" value="<?php echo $script_transl['submit']; ?>">
      </td>
  </tr>
  </tbody>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
