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
// Antonio Germani - amministrazione immagini per e-commerce
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
    $row['title'] = [];
    $row['extension']=[];
    if (isset($_POST['Submit'])) { // conferma tutto
      if (count($_FILES)>0) { // se è stato selezionato almeno un nuovo file
        $n=0;
        foreach ($_FILES['userfile']['name'] as $filename){

        preg_match("/\.([^\.]+)$/", $filename, $matches);
        if ($_POST['title']==""){

        $row['title'][$n]=$filename; // modifico pure il titolo
        } else {
          $row['title'][$n]=$_POST['title'];
        }
        if (isset($matches[1])){

          $row['extension'][$n]=$matches[1];

          if ( $_FILES['userfile']['type'][$n] == "image/png" ||
            $_FILES['userfile']['type'][$n] == "image/x-png" ||
            $_FILES['userfile']['type'][$n] == "application/pdf" ||
            $_FILES['userfile']['type'][$n] == "image/pjpeg" ||
            $_FILES['userfile']['type'][$n] == "image/jpeg" ||
            $_FILES['userfile']['type'][$n] == "image/tiff" ||
            $_FILES['userfile']['type'][$n] == "application/doc" ||
            $_FILES['userfile']['type'][$n] == "application/rtf" || (
            substr($_FILES['userfile']['type'][$n],0,11) == "application" && ($row['extension'][$n]=='odt' ||
                                                                               $row['extension'][$n]=='doc' ||
                                                                               $row['extension'][$n]=='docx'||
                                                                               $row['extension'][$n]=='pdf'))) {
               // vado avanti...
          } else {
            $msg .= "0+";
          }
        }else{
          $msg .= "3+";
        }
        // controllo che il file non sia piu' grande di 10Mb
        if ( $_FILES['userfile']['size'][$n] > 10485760 ){
          $msg .= "1+";
        } elseif($_FILES['userfile']['size'][$n] == 0)  {
          $msg .= "2+";
        }
  $n++;
        }



      } else {
             $msg .= "3+";
      }
      if (empty($msg)) { // nessun errore
        if (count($_FILES)>0) { // se è stato selezionato almeno un file

          for ($n = 0; $n < count($row['title']); $n++){
              $form['title']=$row['title'][$n];
              $form['extension']=$row['extension'][$n];
              // aggiorno il db
              if ($toDo == 'insert') {
                $form['table_name_ref']= 'artico';
                gaz_dbi_table_insert('files',$form);
                //recupero l'id assegnato dall'inserimento
                $form['id_doc']= gaz_dbi_last_id();

              } elseif ($toDo == 'update') {

                gaz_dbi_table_update('files',array('id_doc',$form['id_doc']),$form);
              }
              // aggiorno il filesystem solo se è stato selezionato un nuovo file
              if ($_FILES['userfile']['error'][$n]==0) {

               if(move_uploaded_file($_FILES["userfile"]["tmp_name"][$n], DATA_DIR . "files/".$admin_aziend['company_id']."/images/". $form['id_doc'] . "." . $form['extension'])){

               }else{
                 echo "ERRORE dell'upload immagine hq: ",DATA_DIR . "files/".$admin_aziend['company_id']."/images/". $form['id_doc'] . "." . $form['extension'];die;
               }
              }
          }
        }

        header("Location: ".$form['ritorno']."&tab=magazz");
        exit;
      }
    } elseif (isset($_POST['Return'])) { // torno indietro
        header("Location: ".$form['ritorno']."&tab=magazz");
        exit;
    } elseif (isset($_POST['Delete'])) {
		gaz_dbi_del_row($gTables['files'], 'id_doc',$form['id_doc']);
		unlink (DATA_DIR."files/".$admin_aziend['company_id']."/images/". $form['id_doc'] . "." . $form['extension']);
		header("Location: ".$form['ritorno']."&tab=magazz");
        exit;
	}
} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form = gaz_dbi_get_row($gTables['files'], 'id_doc',intval($_GET['id_doc']));
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    if (empty($form)) { // scappo!
       header("Location: ".$form['ritorno']."&tab=magazz");
       exit;
    }
} else { //se e' il primo accesso per INSERT
    $form=gaz_dbi_fields('files');
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $artico = gaz_dbi_get_row($gTables['artico'], 'codice',substr($_GET['item_ref'],0,32));
    if (!empty($artico)) { //l'articolo è stato trovato
       $form['item_ref']= $artico['codice'];
    } else { // scappo!
       header("Location: ".$form['ritorno']."&tab=magazz");
       exit;
    }
}

require("../../library/include/header.php");
$script_transl = HeadMain();
require("./lang.".$admin_aziend['lang'].".php");
$script_transl += $strScript["browse_document.php"];
require("../../modules/magazz/lib.function.php");
$gForm = new magazzForm();
echo "<form method=\"POST\" name=\"form\" enctype=\"multipart/form-data\">\n";
if ($toDo == 'insert') {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['ins_this_img']."</div>\n";
   $form['id_doc']='';
   echo "<input type=\"hidden\" name=\"item_ref\" value=\"".$form['item_ref']."\">\n";
} else {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['upd_this_img']."</div>\n";
   echo "<input type=\"hidden\" name=\"id_doc\" value=\"".$form['id_doc']."\">\n";
}
echo "<input type=\"hidden\" name=\"ritorno\" value=\"".$form['ritorno']."\">\n";
echo "<input type=\"hidden\" name=\"extension\" value=\"".$form['extension']."\">\n";
echo "<input type=\"hidden\" name=\"item_ref\" value=\"".$form['item_ref']."\">\n";
echo "<input type=\"hidden\" name=\"id_ref\" value=\"1\">\n";
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
			<a class=\"btn btn-xs btn-default\" href=\"../root/retrieve.php?id_ref=".$form["id_doc"]."\" title=\"".$script_transl['view']."!\">
				<i class=\"glyphicon glyphicon-eye-open\"></i>&nbsp;".DATA_DIR."files/".$form['id_doc'].".".$form['extension']."
			</a>
		</td>\n";
if ($toDo == "insert"){
echo "\t<td class=\"FacetFieldCaptionTD\" align=\"right\">".$script_transl['update']." :  <input name=\"userfile[]\" type=\"file\" multiple> </td>\n";
}
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
echo "\t<td  class=\"FacetDataTD\">\n";
echo '<input name="Return" type="submit" value="'.$script_transl['return'].'">';
if ($toDo == "update"){
	?>
	<div class="col-sm-6">
		<!-- Trigger the modal with a button -->
		<button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#downloader">Cancella immagine</button>
		<!-- Modal content-->
		<div id="downloader" class="modal fade" role="dialog">
			<div class="modal-dialog modal-content">
				<div class="modal-header" align="left">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">ATTENZIONE !</h4>
				</div>
				<div class="modal-body">
					<p>Stai per eliminare definitivamente questa immagine. <br>Questa operazione &egrave irreversibile. <br>Sei sicuro di volerlo fare?</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Annulla</button>
					<input type="submit" class="btn btn-danger pull-right" name="Delete"  value="Sì, elimina!">
				</div>
			</div>
		</div>
	</div>
	<?php
}
echo "\t </td>\n";
if ($toDo == "insert"){
echo "\t<td  class=\"FacetDataTD\" align=\"right\">\n";
echo '<input name="Submit" type="submit" value="'.ucfirst($script_transl[$toDo]).'">';
echo "\t </td>\n";
}
echo "</tr>\n";
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
