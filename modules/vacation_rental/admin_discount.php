<?php
/*
   --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP)
  (http://www.programmisitiweb.lacasettabio.it)

  --------------------------------------------------------------------------
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2022 - Antonio De Vincentiis Montesilvano (PE)
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
require("../../modules/magazz/lib.function.php");
$admin_aziend = checkAdmin();
$msg = "";
$gForm = new magazzForm();

if ((isset($_POST['Update'])) or ( isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

if ((isset($_POST['Insert'])) or ( isset($_POST['Update']))) {   //se non e' il primo accesso
	if (isset($_POST['Return'])) {
		header("Location: " . $_POST['ritorno']);
		exit;
	}
  $form = $_POST;
    // Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
        if ($toDo == 'insert' && strlen($form['discount_voucher_code'])>0) { // e' un inserimento, controllo se il codice buono sconto esiste già
            $rs_ctrl = gaz_dbi_get_row($gTables['rental_discounts'], "discount_voucher_code", $form['discount_voucher_code']);
            if ($rs_ctrl) {
                $msg .= "buono_esiste+";
            }
        }
        if (empty($form['description'])) {  //descrizione vuota
            $msg .= "manca_descri+";
        }
        if ($form['value'] < 1) {  //valore vuoto
            $msg .= "valore_vuoto+";
        }
        //print_r($form);die;
        if ($msg == "") {// nessun errore
            if ($toDo == 'update') { // e' una modifica
              $where = array("0" => "id", "1" => $form['id']);
              $what = $form;
              gaz_dbi_table_update("rental_discounts",$where, $what);
            } else { // e' un'inserimento
                if ($form['valid_from']==NULL){
                  $form['valid_from']="0000-00-00";
                }
                if ($form['valid_to']==NULL){
                  $form['valid_to']="0000-00-00";
                }
                $form['STATUS']="CREATED";
                gaz_dbi_table_insert('rental_discounts', $form);
            }
            header("Location: report_discount.php");
            exit;
        }
    }
} elseif ((!isset($_POST['Update'])) and ( isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $disc = gaz_dbi_get_row($gTables['rental_discounts'], "id", intval($_GET['id']));
    $form=$disc;
    $form['ritorno'] = $_POST['ritorno'];

} elseif (!isset($_POST['Insert']) && isset($_GET['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['id']=0;
    $form['title']="";
    $form['description']="";
    $form['accommodation_code']="";
    $form['facility_id']="";
    $form['valid_from']="0000-00-00";
    $form['valid_to']="0000-00-00";
    $form['value']="";
    $form['discount_voucher_code']="";
    $form['is_percent']="";
    $form['min_stay']=0;
    $form['last_min']=0;
    $form['priority']=0;
    $form['stop_further_processing']=0;
    $form['id_anagra']=0;
    $form['reusable']=0;
    $form['level_points']=0;
}
require("../../library/include/header.php");
$script_transl = HeadMain(5);
if ($toDo == 'update') {
    $title = ucwords($script_transl[$toDo] . $script_transl['sconto']) . " n." . $form['id'];
} else {
    $title = ucwords($script_transl[$toDo] . $script_transl['sconto']);
}
?>
<form method="POST">
  <input type="hidden" name="<?php echo ucfirst($toDo); ?>" value="">
  <input type="hidden" value="<?php echo $_POST['ritorno']; ?>" name="ritorno">
  <div class="text-center"><h3><?php echo $title; ?></h3></div>
  <table border="0" cellpadding="3" cellspacing="1" class="FacetFormTABLE" align="center">
    <?php
    if (!empty($msg)) {
        $message = "";
        $rsmsg = array_slice(explode('+', chop($msg)), 0, -1);
        foreach ($rsmsg as $value) {
            $message .= $script_transl['error'] . "! -> ";
            $rsval = explode('-', chop($value));
            foreach ($rsval as $valmsg) {
                $message .= $script_transl[$valmsg] . " ";
            }
            $message .= "<br />";
        }
        echo '<tr><td colspan="5" class="FacetDataTDred">' . $message . "</td></tr>\n";
    }
    if ($toDo == 'update') {
        print "<tr><td class=\"FacetFieldCaptionTD\">ID</td><td class=\"FacetDataTD\"><input type=\"hidden\" name=\"id\" value=\"" . $form['id'] . "\" />" . $form['id'] . "</td></tr>\n";
    }
    ?>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['discount_voucher_code']; ?>
      </td>
      <td class="FacetDataTD">
        <input type="text" name="discount_voucher_code" value="<?php echo $form['discount_voucher_code'];?>" maxlength="50" placeholder="Se è un buono sconto, scrivere il codice altrimenti lasciare bianco"/>
      </td>
    </tr>
     <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['title_dis']; ?>
      </td>
      <td class="FacetDataTD">
        <input type="text" name="title" value="<?php echo $form['title'];?>" maxlength="50"/>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['descri']; ?>
      </td>
      <td class="FacetDataTD">
        <input type="text" name="description" value="<?php echo $form['description'];?>" maxlength="50"/>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['facility_id']; ?>
      </td>
      <td class="FacetDataTD">
        <?php $gForm->selectFromDB('artico_group', 'facility_id', 'id_artico_group', $form['facility_id'], false, true, ' - ', 'descri', '', 'col-sm-8', null, 'style="max-width: 250px;"','custom_field REGEXP \'vacation_rental\'');?>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['accommodation_code']; ?>
      </td>
      <td class="FacetDataTD">
        <?php $gForm->selectFromDB('artico', 'accommodation_code', 'codice', $form['accommodation_code'], false, true, ' - ', 'descri', '', 'col-sm-8', null, 'style="max-width: 250px;"','custom_field REGEXP \'accommodation_type\'');?>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['last_min']; ?>
      </td>
      <td class="FacetDataTD">
        <input type="number" name="last_min" value="<?php echo $form['last_min'];?>" maxlength="10"/>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['valid_from']; ?>
      </td>
      <td class="FacetDataTD">
        <input type="date" name="valid_from" value="<?php echo $form['valid_from'];?>" maxlength="50"/>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['valid_to']; ?>
      </td>
      <td class="FacetDataTD">
        <input type="date" name="valid_to" value="<?php echo $form['valid_to'];?>" maxlength="50"/>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['value']; ?>
      </td>
      <td class="FacetDataTD">
        <input type="text" name="value" value="<?php echo $form['value'];?>" maxlength="50"/>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['is_percent']; ?>
      </td>
      <td class="FacetDataTD">
        <?php $gForm->variousSelect('is_percent', $script_transl['is_percent_value'], $form['is_percent'], "col-sm-8", true, '', false, 'style="max-width: 100px;"');?>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['min_stay']; ?>
      </td>
      <td class="FacetDataTD">
        <input type="number" name="min_stay" value="<?php echo $form['min_stay'];?>" maxlength="10"/>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['priority']; ?>
      </td>
      <td class="FacetDataTD">
        <input type="text" name="priority" value="<?php echo $form['priority'];?>" maxlength="50"/>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['stop_further_processing']; ?>
      </td>
      <td class="FacetDataTD">
        <?php $gForm->variousSelect('stop_further_processing', $script_transl['is_percent_value'], $form['stop_further_processing'], "col-sm-8", true, '', false, 'style="max-width: 100px;"');?>
      </td>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['reusable']; ?>
      </td>
      <td class="FacetDataTD">
      <input type="text" name="reusable" value="<?php echo $form['reusable'];?>" maxlength="3"/>
      </td>
      </td>
    </tr>
    <tr>
      <td class="FacetFieldCaptionTD"><?php echo $script_transl['level_points']; ?>
      </td>
      <td class="FacetDataTD">
      <input type="number" name="level_points" value="<?php echo $form['level_points'];?>" max="3"/>
      </td>
      </td>
    </tr>

    <tr>
      <td class="FacetFieldCaptionTD"><input type="reset" name="Cancel" value="<?php echo $script_transl['cancel'];?>">
      </td>
      <td class="FacetDataTD" align="right">
        <input type="submit" name="Return" value="<?php echo $script_transl['return'];?>">
        <?php
        if ($toDo == 'update') {
            print '<input type="submit" accesskey="m" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="' . ucfirst($script_transl['update']) . '!"></td></tr><tr></tr>';
        } else {
            print '<input type="submit" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="' . ucfirst($script_transl['insert']) . '!"></td></tr><tr></tr>';
        }
        ?>
      </td>
    </tr>
  </table>
</form>
<?php
require("../../library/include/footer.php");
?>
