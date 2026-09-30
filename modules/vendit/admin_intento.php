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
$msg=['err'=>[],'war'=>[]];

if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
    $form=gaz_dbi_parse_post('files');
    $form['protocintento']=preg_replace("([^0-9-])", "",substr($_POST['protocintento'],0,24));
    $form['dataintento']=preg_replace("([^0-9/-])", "", $_POST['dataintento']);
    $form['importointento']=floatval($_POST['importointento']);
    $form['cliente']=$_POST['cliente'];
    if (isset($_POST['Submit'])) { // conferma tutto
      if ($_FILES['userfile']['error']==0) { // se è stato selezionato un nuovo file
        preg_match("/\.([^\.]+)$/", $_FILES['userfile']['name'], $matches);
        $form['title']=$_FILES["userfile"]["name"]; // modifico pure il titolo
        $form['extension']=$matches[1];
        //print $_FILES['userfile']['type'];
        if ( $_FILES['userfile']['type'] == "image/png" ||
          $_FILES['userfile']['type'] == "image/x-png" ||
          $_FILES['userfile']['type'] == "application/pdf" ||
          $_FILES['userfile']['type'] == "image/pjpeg" ||
          $_FILES['userfile']['type'] == "image/jpeg" ||
          $_FILES['userfile']['type'] == "text/richtext" ||
          $_FILES['userfile']['type'] == "text/plain" ||
          $_FILES['userfile']['type'] == "application/vnd.oasis.opendocument.text" ||
          $_FILES['userfile']['type'] == "application/msword" ||
          $_FILES['userfile']['type'] == "image/tiff" ||
          $_FILES['userfile']['type'] == "application/doc" ||
          $_FILES['userfile']['type'] == "application/rtf" || (
          substr($_FILES['userfile']['type'],0,11) == "application" && ($form['extension']=='odt' ||
                                                                             $form['extension']=='doc' ||
                                                                             $form['extension']=='docx'||
                                                                             $form['extension']=='pdf'))) {
             // vado avanti...
        } else {
          $msg['err'][]='ext';
        }
        // controllo che il file non sia piu' grande di 10Mb
        if ( $_FILES['userfile']['size'] > 10485760 ){
          $msg['err'][]='big';
        } elseif($_FILES['userfile']['size'] == 0)  {
          $msg['err'][]='empty';
        }
      } elseif($toDo=='insert') {
              $msg['err'][]='sel';
      }
      $dataintento = gaz_format_date($form['dataintento'], true);
      if (!gaz_format_date($form['dataintento'],'chk')||(strtotime($dataintento) > strtotime('now'))){
          $msg['err'][]='dataintento';
      }
      if (!preg_match('/^\d{17}-\d{6}$/',$form['protocintento'])) {
        $msg['err'][]='protocintento';
      }
      if ($form['importointento'] < 100) {
        $msg['err'][]='importointento';
      }
      if (count($msg['err'])<1) { // nessun errore
        // controllo che ci sia la cartella doc
        $docfolder = DATA_DIR.'files/' . $admin_aziend['codice'] . '/doc/';
        if (!file_exists($docfolder)) {// se non c'è la creo
          mkdir($docfolder, 0666);
        }
        // aggiorno il solo db
        $form['custom_field']=json_encode(['vendit'=>array('dataintento'=>$dataintento,'protocintento'=>$form['protocintento'],'importointento'=>$form['importointento'])]);
        if ($toDo == 'insert') {
          $form['table_name_ref']= 'clfoco';
          $form['item_ref']= 'intento';
          $form['id_doc']=gaz_dbi_table_insert('files',$form);
        } elseif ($toDo == 'update') {
          gaz_dbi_table_update('files',array('id_doc',$form['id_doc']),$form);
        }
        $clfoco = gaz_dbi_get_row($gTables['clfoco'], 'codice',$form['id_ref']);
        // aggiorno il filesystem solo se è stato selezionato un nuovo file
        if ($_FILES['userfile']['error']==0) {
          move_uploaded_file($_FILES["userfile"]["tmp_name"], DATA_DIR . "files/" .$admin_aziend['company_id']."/doc/". $form['id_doc'] . "." . $form['extension']);
        }
        header("Location: admin_client.php?codice=".substr($form['id_ref'],-6)."&Update&tab=commer");
        exit;
      }
    } elseif (isset($_POST['Return'])) { // torno indietro
        header("Location: admin_client.php?codice=".substr($form['id_ref'],-6)."&Update&tab=commer");
      exit;
    } elseif (isset($_POST['Delete'])) {
      gaz_dbi_del_row($gTables['files'], 'id_doc',$form['id_doc']);
        header("Location: admin_client.php?codice=".substr($form['id_ref'],-6)."&Update&tab=commer");
      exit;
	}
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['files'], 'id_doc',intval($_GET['id_doc']));
    $clfoco = gaz_dbi_get_row($gTables['clfoco'], 'codice',$form['id_ref']);
    $form['cliente']=$clfoco['descri'];
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $form['dataintento']='';
    $form['protocintento']='';
    $form['importointento']='';
	if ($data=json_decode($form['custom_field'],true)){// se c'è un json nel custom_field
		if (is_array($data['vendit']) && strlen($data['vendit']['dataintento'])>0){ // se è riferito al modulo vendit e contiene la data di accettazione della dichiarazione
            $form['dataintento']=gaz_format_date($data['vendit']['dataintento']);
            $form['protocintento']=$data['vendit']['protocintento'];
            $form['importointento']=$data['vendit']['importointento'];
        }
	}
    if (empty($form)) { // scappo!
       header("Location: ".$form['ritorno']);
       exit;
    }
} else { //se e' il primo accesso per INSERT
    $form=gaz_dbi_fields('files');
    $form['dataintento']='';
    $form['protocintento']='';
    $form['importointento']=0.00;
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $clfoco = gaz_dbi_get_row($gTables['clfoco'], 'codice',intval($admin_aziend['mascli'] * 1000000 + $_GET['id_ref']));
    $form['cliente']=$clfoco['descri'];
    if (!empty($clfoco)) { //l'articolo è stato trovato
       $form['id_ref']= $clfoco['codice'];
    } else { // scappo!
       header("Location: ".$form['ritorno']);
       exit;
    }
}

require("../../library/include/header.php");
$script_transl = HeadMain('','','browse_intento');
$gForm = new venditForm();
?>
<script type="text/javascript">
$(function () {
    $("#dataintento").datepicker({
        showButtonPanel: true,
        showOtherMonths: true,
        selectOtherMonths: true,
        beforeShow: function (input, inst) {
            var rect = input.getBoundingClientRect();
            setTimeout(function () {
                inst.dpDiv.css({ top: rect.top + 40, left: rect.left + 0 });
            }, 0);
        }
    });
});
</script>

<?php
if (count($msg['err']) > 0) { // ho un errore
    echo '<div class="text-center"><div><b>';
    $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    echo "</b></div></div>\n";
}

echo "<form method=\"POST\" name=\"form\" enctype=\"multipart/form-data\">\n";
if ($toDo == 'insert') {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['ins_this']."</div>\n";
   $form['id_doc']='';
   echo "<input type=\"hidden\" name=\"id_ref\" value=\"".$form['id_ref']."\">\n";
} else {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['upd_this']."</div>\n";
   echo "<input type=\"hidden\" name=\"id_doc\" value=\"".$form['id_doc']."\">\n";
}
echo "<input type=\"hidden\" name=\"cliente\" value=\"".$form['cliente']."\">\n";
echo "<input type=\"hidden\" name=\"extension\" value=\"".$form['extension']."\">\n";
echo "<input type=\"hidden\" name=\"id_ref\" value=\"".$form['id_ref']."\">\n";
echo "<input type=\"hidden\" name=\"title\" value=\"".$form['title']."\">\n";
echo "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">";
echo '<div align="center"><table class="table-striped table-bordered table-condensed">';
echo "<tr>\n";
echo "\t<td>ID</td>\n";
echo "\t<td colspan=\"2\">".$form['id_doc']."</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td>File : </td>\n";
echo "\t<td>
			<a class=\"btn btn-xs btn-default\" href=\"../root/retrieve.php?id_doc=".$form["id_doc"]."\" title=\"".$script_transl['view']."!\">
				<i class=\"glyphicon glyphicon-eye-open\"></i>&nbsp;".DATA_DIR."files/".$admin_aziend['company_id']."/doc/".$form['id_doc'].".".$form['extension']."
			</a>
		</td>\n";
echo '<td><div>'.$script_transl['update'].' :</div><div><input name="userfile" type="file"></div> </td>';
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td>".$script_transl['item']."</td>\n";
echo "\t<td colspan=\"2\"><b>".$form['cliente']."</b></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td>".$script_transl['title']."</td>\n";
echo "\t<td colspan=\"2\">".$form['title']."</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td>".$script_transl['dataintento']."</td>\n";
echo "\t<td colspan=\"2\">
      <input type=\"text\" id=\"dataintento\" name=\"dataintento\" value=\"".$form['dataintento']."\" maxlength=\"10\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<tr>\n";
echo "\t<td>".$script_transl['protocintento']."</td>\n";
echo "\t<td colspan=\"2\">
      <input class=\"col-xs-12\" type=\"text\" name=\"protocintento\" maxlength=\"24\"  value=\"".$form['protocintento']."\" placeholder=\"12345678901234567-123456\" /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td>".$script_transl['importointento']."</td>\n";
echo "\t<td colspan=\"2\">
      <input class=\"col-xs-12\" type=\"text\" name=\"importointento\" maxlength=\"24\"  value=\"".$form['importointento']."\" placeholder=\"plafond massimo\" /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td>".$script_transl['sqn']."</td>";
echo "\t </td>\n";
echo "\t<td >\n";
echo '<input name="Return" type="submit" value="'.$script_transl['return'].'">';
echo '<input name="Delete" type="submit" value="Cancella !">';
echo "\t </td>\n";
echo "\t<td  align=\"right\">\n";
echo '<input name="Submit" type="submit" value="'.ucfirst($script_transl[$toDo]).'">';
echo "\t </td>\n";
echo "</tr>\n";
?>
</table>
</form>
</div>
<?php
require("../../library/include/footer.php");
?>
