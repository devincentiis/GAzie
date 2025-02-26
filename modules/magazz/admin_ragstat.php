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
$msg = "";
if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
  $toDo = 'update';
} else {
  $toDo = 'insert';
}
if (!isset($_POST['ritorno'])) {
  $_POST['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if ((isset($_GET['Update']) and  !isset($_GET['codice'])) or isset($_POST['Return'])) {
  header("Location: ".$_POST['ritorno']);
  exit;
}
if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso
    $form=gaz_dbi_parse_post('ragstat');
    // Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
       if (! empty($_FILES['userfile']['name'])) {
          if (!( $_FILES['userfile']['type'] == "image/png" ||
               $_FILES['userfile']['type'] == "image/x-png" ||
               $_FILES['userfile']['type'] == "image/jpeg" ||
               $_FILES['userfile']['type'] == "image/jpg" ||
               $_FILES['userfile']['type'] == "image/gif" ||
               $_FILES['userfile']['type'] == "image/x-gif"))
              $msg .= '8+';
              // controllo che il file non sia pi&ugrave; grande di 64Kb
          if ( $_FILES['userfile']['size'] > 63999)
              $msg .= '9+';
       }

       if ($toDo == 'insert') { // e' un inserimento, controllo se il codice esiste
          $rs_ctrl = gaz_dbi_get_row($gTables['ragstat'],'codice',$form['codice']);
          if ($rs_ctrl){
             $msg .= "6+";
          }
       }
       if (empty($form['descri'])){  //descrizione vuota
             $msg .= "7+";
       }
       if ($msg == "") {// nessun errore
          // preparo la stringa dell'immagine
          if ($_FILES['userfile']['size'] > 0) { //se c'e' una nuova immagine nel buffer
             $form['image'] = file_get_contents($_FILES['userfile']['tmp_name']);
          } else {   // altrimenti riprendo la vecchia
             $oldimage = gaz_dbi_get_row($gTables['ragstat'],'codice',$form['codice']);
             $form['image'] = $oldimage['image'];
          }
          if ($toDo == 'update') { // e' una modifica
            gaz_dbi_table_update('ragstat',$form["codice"],$form);
          } else { // e' un'inserimento
            gaz_dbi_table_insert('ragstat',$form);
          }
          header("Location: ".$_POST['ritorno']);
          exit;
       }
  }
} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $ragstat = gaz_dbi_get_row($gTables['ragstat'],"codice",$_GET['codice']);
    $form['ritorno'] = $_POST['ritorno'];
    $form['codice'] = $ragstat['codice'];
    $form['descri'] = $ragstat['descri'];
    $form['web_url'] = $ragstat['web_url'];
    $form['annota'] = $ragstat['annota'];
    $form['ricarico'] = $ragstat['ricarico'];
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $form['codice'] = '';
    $form['descri'] = '';
    $form['ricarico'] = 0;
    $form['web_url']='';
    $form['annota'] = '';
}
require("../../library/include/header.php");
$script_transl = HeadMain();
if ($toDo == 'update') {
   $title = ucwords($script_transl[$toDo].$script_transl[0])." n.".$form['codice'];
} else {
   $title = ucwords($script_transl[$toDo].$script_transl[0]);
}
print "<form method=\"POST\" enctype=\"multipart/form-data\">\n";
print "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">\n";
print "<input type=\"hidden\" value=\"".$_POST['ritorno']."\" name=\"ritorno\">\n";
print "<div align=\"center\" class=\"FacetFormHeaderFont\">$title</div>";
print '<table class="Tmiddle">';
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
   print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[1]</td><td class=\"FacetDataTD\"><input type=\"hidden\" name=\"codice\" value=\"".$form['codice']."\" />".$form['codice']."</td></tr>\n";
} else {
   print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[1]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"codice\" value=\"".$form['codice']."\" maxlength=\"3\"  /></td></tr>\n";
}
print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[2]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"descri\" value=\"".$form['descri']."\" maxlength=\"50\"  /></td></tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\"><img src=\"../root/view.php?table=ragstat&value=".$form['codice']."\" width=\"100\"></td>";
print "<td class=\"FacetDataTD\">$script_transl[3]<br><input name=\"userfile\" type=\"file\" class=\"FacetDataTD\"></td>";
print "</tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[4]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"ricarico\" value=\"".$form['ricarico']."\" maxlength=\"4\"  /></td></tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['web_url']." </td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"web_url\" value=\"".$form['web_url']."\" maxlength=\"255\"  /></td>\n";
echo "</tr>\n";
print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[5]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"annota\" value=\"".$form['annota']."\" maxlength=\"50\"  />\n";
print "</select></td></tr><trclass=\"FacetFooterTD\"><td>";
print "</td><td class=\"FacetDataTD\" align=\"center\">\n";
if ($toDo == 'update') {
   print '<input type="submit" class="btn btn-warning" accesskey="m" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="'.ucfirst($script_transl['update']).'"></td></tr><tr></tr>';
} else {
   print '<input type="submit" class="btn btn-warning" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="'.ucfirst($script_transl['insert']).'"></td></tr><tr></tr>';
}
print "</td></tr></table>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>
