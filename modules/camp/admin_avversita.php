<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
// ANTONIO GERMANI   >>> aggiungi o modifcica avversità  <<<

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

if ((isset($_GET['Update']) and  !isset($_GET['id_avv'])) or isset($_POST['Return'])) {
    header("Location: ".$_POST['ritorno']);
    exit;
}

if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso
    $form=gaz_dbi_parse_post('camp_avversita');
    // Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {

       if ($toDo == 'insert') { // controllo se il codice esiste se e' un inserimento
          $rs_ctrl = gaz_dbi_get_row($gTables['camp_avversita'],'id_avv',$form['id_avv']);
          if ($rs_ctrl){
             $msg .= "4+";
          }
       }
       if (empty($form['nome_avv'])){  //descrizione vuota
             $msg .= "3+";
       }
       if ($msg == "") {// nessun errore

          if ($toDo == 'update') { // e' una modifica
		  $query="UPDATE " . $gTables['camp_avversita'] . " SET nome_avv = '"  .$form['nome_avv']. "' WHERE id_avv ='". $form["id_avv"] ."'";
			gaz_dbi_query ($query) ;

          } else { // e' un'inserimento
            gaz_dbi_table_insert('camp_avversita',$form);
          }
          header("Location: ".$_POST['ritorno']);
          exit;
       }
  }
} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per update
    $camp_avversita = gaz_dbi_get_row($gTables['camp_avversita'],"id_avv",$_GET['id_avv']);
    $form['ritorno'] = $_POST['ritorno'];
    $form['id_avv'] = $camp_avversita['id_avv'];
    $form['nome_avv'] = $camp_avversita['nome_avv'];

} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
    $rs_ultimo_id_avv = gaz_dbi_dyn_query("*", $gTables['camp_avversita'], 1 ,'id_avv desc',0,1);
    $ultimo_id_avv = gaz_dbi_fetch_array($rs_ultimo_id_avv);
    $form['id_avv'] = $ultimo_id_avv['id_avv']+1;
    $form['nome_avv'] = "";

}
require("../../library/include/header.php");
$script_transl = HeadMain();
if ($toDo == "update") {
   $title = ucwords($script_transl[$toDo].$script_transl[0])." n.".$form['id_avv'];
} else {
   $title = ucwords($script_transl[$toDo].$script_transl[0]);
}
print "<form method=\"POST\" enctype=\"multipart/form-data\">\n";
print "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">\n";
print "<input type=\"hidden\" value=\"".$_POST['ritorno']."\" name=\"ritorno\">\n";
print "<div align=\"center\" class=\"FacetFormHeaderFont\">$title</div>";
print "<table class=\"Tmiddle table-striped\" align=\"center\">\n";
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
   print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[1]</td><td class=\"FacetDataTD\"><input type=\"hidden\" name=\"id_avv\" value=\"".$form['id_avv']."\" />".$form['id_avv']."</td></tr>\n";
} else {
   print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[1]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"id_avv\" value=\"".$form['id_avv']."\" maxlength=\"3\"  /></td></tr>\n";
}
print "<tr><td class=\"FacetFieldCaptionTD\">$script_transl[2]</td><td class=\"FacetDataTD\"><input type=\"text\" name=\"nome_avv\" value=\"".$form['nome_avv']."\" maxlength=\"50\"  /></td></tr>\n";

print "<tr>";
if ($toDo !== 'update') {
print "<td class=\"FacetFieldCaptionTD\"><input type=\"reset\" name=\"Cancel\" value=\"".$script_transl['cancel']."\">\n</td>";
}
print "<td class=\"FacetDataTD\" align=\"right\">\n";
print "<input type=\"submit\" name=\"Return\" value=\"".$script_transl['return']."\">\n";
if ($toDo == 'update') {
   print '<input type="submit" accesskey="m" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="'.ucfirst($script_transl['update']).'!"></td></tr><tr></tr>';
} else {
   print '<input type="submit" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="'.ucfirst($script_transl['insert']).'!"></td></tr><tr></tr>';
}
print "</td></tr></table>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>
