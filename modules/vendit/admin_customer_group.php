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
$msg = "";


if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (!isset($_POST['ritorno'])) {
    $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}

if ((isset($_GET['Update']) and  !isset($_GET['id'])) or isset($_POST['Return'])) {
    header("Location: ".$_POST['ritorno']);
    exit;
}

if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso
  $form=gaz_dbi_parse_post('customer_group');
	$form['large_descri']=$_POST['large_descri'];
    // Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
       if (! empty($_FILES['userfile']['name'])) {
          if (!( $_FILES['userfile']['type'] == "image/png" ||
               $_FILES['userfile']['type'] == "image/x-png" ||
               $_FILES['userfile']['type'] == "image/jpeg" ||
               $_FILES['userfile']['type'] == "image/jpg" ||
               $_FILES['userfile']['type'] == "image/gif" ||
               $_FILES['userfile']['type'] == "image/x-gif"))
              $msg .= 'err3+';
              // controllo che il file non sia pi&ugrave; grande di 64Kb
          if ( $_FILES['userfile']['size'] > 63999)
              $msg .= 'err4+';
       }

       if ($toDo == 'insert') { // e' un inserimento, controllo se il id esiste
          $rs_ctrl = gaz_dbi_get_row($gTables['customer_group'],'id',$form['id']);
          if ($rs_ctrl){
             $msg .= "err1+";
          }
       }
       if (empty($form['descri'])){  //descrizione vuota
             $msg .= "err2+";
       }
      if ($msg == "") { // nessun errore
          // preparo la stringa dell'immagine
          if ($_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
             $form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
          } else {   // altrimenti riprendo la vecchia
             $oldimage = gaz_dbi_get_row($gTables['customer_group'],'id',$form['id']);
             $form['image'] = (isset($oldimage))?$oldimage['image']:'';
          }
          if ($toDo == 'update') { // e' una modifica
            gaz_dbi_table_update('customer_group',['id',$form["id"]],$form);
          } else { // e' un'inserimento
            gaz_dbi_table_insert('customer_group',$form);
          }
        header("Location: ".$_POST['ritorno']);
        exit;
      }
  }
} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $customer_group = gaz_dbi_get_row($gTables['customer_group'],"id",intval($_GET['id']));
    $form['ritorno'] = $_POST['ritorno'];
    $form['id'] = $customer_group['id'];
    $form['descri'] = $customer_group['descri'];
    $form['large_descri'] = $customer_group['large_descri'];
    $form['ref_ecommerce_customer_group'] = $customer_group['ref_ecommerce_customer_group'];
    $form['annota'] = $customer_group['annota'];
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $rs_ultimo_id = gaz_dbi_dyn_query("*", $gTables['customer_group'], 1 ,'id desc',0,1);
    $ultimo_id = gaz_dbi_fetch_array($rs_ultimo_id);
    $form['id'] = $ultimo_id['id']+1;
    $form['descri'] = '';
    $form['large_descri'] = '';
    $form['ref_ecommerce_customer_group'] = '';
    $form['ricarico'] = 0;
    $form['top'] = 0;
    $form['annota'] = '';
}
require("../../library/include/header.php");
$script_transl = HeadMain();
if ($toDo == 'update') {
   $title = ucwords($script_transl[$toDo].$script_transl[0])." n.".$form['id'];
} else {
   $title = ucwords($script_transl[$toDo].$script_transl[0]);
}
$gForm = new venditForm();
echo "<form method=\"POST\" enctype=\"multipart/form-data\">\n";
echo "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">\n";
echo "<input type=\"hidden\" value=\"".$_POST['ritorno']."\" name=\"ritorno\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">$title</div>";
echo "<table class=\"Tmiddle table\">\n";
if (!empty($msg)) {
    $message = "";
    $rsmsg = array_slice( explode('+',chop($msg)),0,-1);
    foreach ($rsmsg as $value){
            $message .= $script_transl['error']."! -> ";
            $rsval = explode('-',chop($value));
            foreach ($rsval as $valmsg){
                    $message .= $script_transl[$valmsg]." ";
            }
            $message .= "<br />";
    }
    echo '<tr><td colspan="5" class="FacetDataTDred">'.$message."</td></tr>\n";
}
if ($toDo == 'update') {
   echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[id]</td><td class=\"FacetDataTD\"><input type=\"hidden\" name=\"id\" value=\"".$form['id']."\" />".$form['id']."</td></tr>\n";
} else {
   echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[id]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"id\" value=\"".$form['id']."\" maxlength=\"3\"  /></td></tr>\n";
}
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[descri]*</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"descri\" value=\"".$form['descri']."\" maxlength=\"50\"  /></td></tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\"><img src=\"../root/view.php?table=customer_group&value=".$form['id']."&field=id\" width=\"100\"></td>";
echo "<td class=\"FacetDataTD\" align=\"center\">$script_transl[image]<br><input name=\"userfile\" type=\"file\" class=\"FacetDataTD\"></td>";
echo "</tr>\n";
?>
<tr>
	<td class="FacetFieldCaptionTD">
        <label for="large_descri" class="col-sm-4 control-label"><?php echo $script_transl['large_descri']; ?></label>
	</td>
    <td class="FacetFieldCaptionTD">
        <textarea id="large_descri" name="large_descri" class="mceClass"><?php echo $form['large_descri']; ?></textarea>
    </td>
</tr>
<?php
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[ref_ecommerce_customer_group]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"ref_ecommerce_customer_group\" value=\"".$form['ref_ecommerce_customer_group']."\" maxlength=\"4\"  /></td></tr>\n";
echo "<tr>\n";
echo "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[annota]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"annota\" value=\"".$form['annota']."\" maxlength=\"50\"  />\n";
echo "</select></td></tr><tr>";
echo "\t<td class=\"FacetFooterTD\">".$script_transl['sqn']."</td>";
echo "\t </td>\n";
echo '<td colspan=2 class="FacetFooterTD text-center">';
echo '<input name="ins" class="btn btn-warning" type="submit" value="'.ucfirst($script_transl[$toDo]).'">';
echo "</td></tr></table>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>
