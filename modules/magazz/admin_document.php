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
$msg = '';

if (isset($_POST['Update']) || isset($_GET['Update'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
    $form=gaz_dbi_parse_post('files');
    $form['ritorno'] = $_POST['ritorno'];
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
				$msg .= "0+";
			}
			// controllo che il file non sia piu' grande di 10Mb
			if ( $_FILES['userfile']['size'] > 10485760 ){
				$msg .= "1+";
			} elseif($_FILES['userfile']['size'] == 0)  {
				$msg .= "2+";
			}
		} else {
           $msg .= "3+";
		}
		if (empty($msg)) { // nessun errore
			// controllo che ci sia la cartella doc
			$docfolder = DATA_DIR.'files/' . $admin_aziend['codice'] . '/doc/';
			if (!file_exists($docfolder)) {// se non c'è la creo
				mkdir($docfolder, 0777);
			}
          // aggiorno il solo db
          if ($toDo == 'insert') {
            $form['table_name_ref']= 'artico';
            gaz_dbi_table_insert('files',$form);
            //recupero l'id assegnato dall'inserimento
            $form['id_doc']= gaz_dbi_last_id();
          } elseif ($toDo == 'update') {
            gaz_dbi_table_update('files',array('id_doc',$form['id_doc']),$form);
          }
          // aggiorno il filesystem solo se è stato selezionato un nuovo file
          if ($_FILES['userfile']['error']==0) {
            move_uploaded_file($_FILES["userfile"]["tmp_name"], DATA_DIR . "files/" .$admin_aziend['company_id']."/doc/". $form['id_doc'] . "." . $form['extension']);
          }
          header("Location: ".$form['ritorno']);
          exit;
       }
    } elseif (isset($_POST['Return'])) { // torno indietro
          header("Location: ".$form['ritorno']);
          exit;
    } elseif (isset($_POST['Delete'])) {
		gaz_dbi_del_row($gTables['files'], 'id_doc',$form['id_doc']);
		header("Location: ".$form['ritorno']);
        exit;
	}
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['files'], 'id_doc',intval($_GET['id_doc']));
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    if (empty($form)) { // scappo!
       header("Location: ".$form['ritorno']);
       exit;
    }
} else { //se e' il primo accesso per INSERT
    $form=gaz_dbi_fields('files');
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $artico = gaz_dbi_get_row($gTables['artico'], 'codice',substr($_GET['item_ref'],0,32));
    if (!empty($artico)) { //l'articolo è stato trovato
       $form['item_ref']= $artico['codice'];
    } else { // scappo!
       header("Location: ".$form['ritorno']);
       exit;
    }
}

require("../../library/include/header.php");
$script_transl = HeadMain();
require("./lang.".$admin_aziend['lang'].".php");
$script_transl += $strScript["browse_document.php"];
$gForm = new magazzForm();
echo "<form method=\"POST\" name=\"form\" enctype=\"multipart/form-data\">\n";
if ($toDo == 'insert') {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['ins_this']."</div>\n";
   $form['id_doc']='';
   echo "<input type=\"hidden\" name=\"item_ref\" value=\"".$form['item_ref']."\">\n";
} else {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['upd_this']."</div>\n";
   echo "<input type=\"hidden\" name=\"id_doc\" value=\"".$form['id_doc']."\">\n";
}
echo "<input type=\"hidden\" name=\"ritorno\" value=\"".$form['ritorno']."\">\n";
echo "<input type=\"hidden\" name=\"extension\" value=\"".$form['extension']."\">\n";
echo "<input type=\"hidden\" name=\"item_ref\" value=\"".$form['item_ref']."\">\n";
echo "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="3" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">ID</td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">".$form['id_doc']."</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">File : </td>\n";
echo "\t<td class=\"FacetDataTD\">
			<a class=\"btn btn-xs btn-default\" href=\"../root/retrieve.php?id_doc=".$form["id_doc"]."\" title=\"".$script_transl['view']."!\">
				<i class=\"glyphicon glyphicon-eye-open\"></i>&nbsp;".DATA_DIR."files/".$admin_aziend['company_id']."/doc/".$form['id_doc'].".".$form['extension']."
			</a>
		</td>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" align=\"right\">".$script_transl['update']." :  <input name=\"userfile\" type=\"file\"> </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['item']."</td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">".$form['item_ref']."</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['note']."</td>\n";
echo "\t<td colspan=\"2\" class=\"FacetDataTD\">
      <input type=\"text\" name=\"title\" value=\"".$form['title']."\" maxlength=\"50\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['sqn']."</td>";
echo "\t </td>\n";
echo "\t<td  class=\"FacetDataTD\">\n";
echo '<input name="Return" type="submit" value="'.$script_transl['return'].'">';
echo '<input name="Delete" type="submit" value="Cancella !">';
echo "\t </td>\n";
echo "\t<td  class=\"FacetDataTD\" align=\"right\">\n";
echo '<input name="Submit" type="submit" value="'.ucfirst($script_transl[$toDo]).'">';
echo "\t </td>\n";
echo "</tr>\n";
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
