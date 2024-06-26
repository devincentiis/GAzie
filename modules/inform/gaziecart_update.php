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
$admin_aziend=checkAdmin(9);
function getConfig($var)
{
    global $table_prefix;
    $query = "SELECT * FROM `".$gTables['company_config']."` WHERE var = '$var'";
    $result = gaz_dbi_query ($query);
    $data = gaz_dbi_fetch_array($result);
    return $data;
}

function updateGAzieCart($server,$user,$pass,$data,$filename)
{
    gaz_set_time_limit (30); // azzero il tempo altrimenti vado in fatal_error
    $fn_exp = explode("/", $filename);
    $filename=array_pop($fn_exp);
    // set up a connection or die
    $conn_id = @ftp_connect($server);
    if (!$conn_id){
        return '0+'; // torno l'errore di server
    }
    // faccio il login
    if (!@ftp_login($conn_id, $user, $pass)) {
            ftp_close($conn_id);
            return '1+'; // torno l'errore di login
    }
    //turn passive mode on
    ftp_pasv($conn_id, true);
    foreach( $fn_exp as $dir){
        // faccio i cambi di direttorio
        if (!@ftp_chdir($conn_id, $dir)) {
            ftp_close($conn_id);
            return '2+'; // torno l'errore di direttorio inesistente
        }
    }
    // scrivo il file temporaneamente sul filesystem del server
    // quindi questo file deve poter essere scritto da Apache, quindi i permessi devono essere giusti
    $fp=fopen('gaziecart.tmp','w+');
    fwrite($fp,$data);
    fclose($fp);
    // elimino il file sul sito per poterlo riscrivere
    $fp=fopen('gaziecart.tmp','r');
    @ftp_delete($conn_id, $filename);
    // faccio l'upload del nuovo file
    if (!@ftp_fput($conn_id, $filename,$fp, FTP_BINARY)) {
            fclose($fp);
            ftp_close($conn_id);
            return '3+'; // torno l'errore di file
    }
    fclose($fp);
    // close the connection
    ftp_close($conn_id);
    return false;
}

if (isset($_POST['ritorno'])) {   //se non e' il primo accesso
    $form['ritorno'] = $_POST['ritorno'];
    $form['server'] = addslashes(substr($_POST['server'],0,100));
    $form['user'] = addslashes(substr($_POST['user'],0,100));
    $form['pass'] = addslashes(substr($_POST['pass'],0,100));
    $form['path'] = addslashes(substr($_POST['path'],0,100));
    $form['listin'] = substr($_POST['listin'],0,3);
    if (isset($_POST['Return'])) { // torno indietro
          header("Location: ".$form['ritorno']);
          exit;
    }
} else { //se e' il primo accesso
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $cfg = gaz_dbi_get_row($gTables['company_config'],'var','server');
    $form['server'] = $cfg['val'];
    $cfg = gaz_dbi_get_row($gTables['company_config'],'var','user');
    $form['user'] = $cfg['val'];
    $cfg = gaz_dbi_get_row($gTables['company_config'],'var','pass');
    $form['pass'] = $cfg['val'];
    $cfg = gaz_dbi_get_row($gTables['company_config'],'var','path');
    $form['path'] = $cfg['val'];
    $form['listin'] = 'web';
}

require("../../library/include/header.php");
$script_transl = HeadMain();
echo "<form method=\"POST\" autocomplete=\"off\">";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"".$form['ritorno']."\">\n";
$gForm = new GAzieForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title']."</div>\n";
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['server']." * </td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\"><input type=\"text\" name=\"server\" value=\"".$form['server']."\" align=\"right\" maxlength=\"100\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['user']." * </td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\"><input type=\"text\" name=\"user\" value=\"".$form['user']."\" align=\"right\" maxlength=\"100\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['pass']." * </td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\"><input type=\"password\" name=\"pass\" value=\"".$form['pass']."\" align=\"right\" maxlength=\"20\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['path']." </td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\"><input type=\"text\" name=\"path\" value=\"".$form['path']."\" align=\"right\" maxlength=\"40\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['listin']."</td><td class=\"FacetDataTD\" colspan=\"2\">\n";
$gForm->variousSelect('listin',$script_transl['listin_value'],$form['listin'],'FacetSelect',false);
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['sqn']."</td>";
echo "\t </td>\n";
echo "\t<td  class=\"FacetDataTD\">\n";
echo '<input name="Return" type="submit" value="'.$script_transl['return'].'">';
echo "\t </td>\n";
echo "\t<td  class=\"FacetDataTD\" align=\"right\">\n";
echo '<input name="Submit" type="submit" value="'.ucfirst($script_transl['submit']).'">';
echo "\t </td>\n";
echo "</tr></table>\n";
if (isset($_POST['Submit'])) { // conferma
   $attempts=4;
   echo "<table class=\"Tmiddle table-striped\">\n";
   //preparazione dati XML
   if ($form['listin'] =='web'){
       $lst = 'web_price';
   } else {
       $lst = 'preve'.$form['listin'];
   }
   $xml='<?xml version="1.0" encoding="utf-8" ?>'."\n<gazie>\n";
   $rs = gaz_dbi_query ('SELECT art.codice AS cod,art.descri AS des,image,web_mu,web_multiplier,web_url,web_public,catmer,'.$lst.' AS prezzo,art.annota AS note,aliquo FROM '.$gTables['artico'].
                        ' AS art LEFT JOIN '.$gTables['aliiva'].' AS vat ON art.aliiva=vat.codice ORDER BY catmer, art.codice');
   while ($item = gaz_dbi_fetch_array($rs)) :
         if ($item['web_public']>0) {
            $xml .= "\t<artico>\n\t\t<codice>".preg_replace('/\//','_',$item['cod']).
                    "</codice>\n\t\t<descri>".$item['des'].
                    "</descri>\n\t\t<unimis>".$item['web_mu'].
                    "</unimis>\n\t\t<catmer>".$item['catmer'].
                    "</catmer>\n\t\t<prezzoweb>".number_format($item['prezzo']*$item['web_multiplier'],$admin_aziend['decimal_price'],'.','').
                    "</prezzoweb>\n\t\t<taxrate>".$item['aliquo'].
                    "</taxrate>\n\t\t<url>".htmlspecialchars($item['web_url']).
                    "</url>\n\t\t<annota>".$item['note'].
                    "</annota>\n\t</artico>\n";
            if (!empty($item['image'])) {
               for ($i = 1; $i <= $attempts; $i++) {  // cicli per i tentativi di upload ftp
                  $msg = updateGAzieCart($form['server'],$form['user'],$form['pass'],$item['image'],$form['path'].'components/com_gaziecart/images/artico/'.preg_replace('/\//','_',$item['cod']).'.png');
                  if (!$msg){ //  andato a buon fine
                      $i=9;
                      echo '<tr><td class="FacetDataTD">File:  artico/'.$item['cod'].".png uploaded!</td></tr>\n";
                  } else {
                      echo '<tr><td class="FacetDataTDred">Attempts n.'.$i." artico/".$item['cod'].".png failed: ".$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
                  }
               }
            }
         }
   endwhile;
   $rs = gaz_dbi_query ('SELECT codice,descri,image,annota FROM '.$gTables['catmer'].' ORDER BY codice');
   while ($item = gaz_dbi_fetch_array($rs)) :
         $xml .= "\t<catmer>\n\t\t<codice>".$item['codice']."</codice>\n\t\t<descri>".$item['descri']."</descri>\n\t\t<annota>".$item['annota']."</annota>\n\t</catmer>\n";
         if (!empty($item['image'])) {
             for ($i = 1; $i <= $attempts; $i++) {  // cicli per i tentativi di upload ftp
                 $msg = updateGAzieCart($form['server'],$form['user'],$form['pass'],$item['image'],$form['path'].'components/com_gaziecart/images/catmer/'.$item['codice'].'.png');
                 if (!$msg){ //  andato a buon fine
                      $i=9;
                      echo "<tr><td class=\"FacetDataTD\">File:  catmer/".$item['codice'].".png uploaded!</td></tr>\n";
                 } else {
                      echo '<tr><td class="FacetDataTDred">Attempts n.'.$i." catmer/".$item['codice'].".png failed: ".$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
                 }
             }
         }
   endwhile;
   $xml.="</gazie>";
             for ($i = 1; $i <= $attempts; $i++) {  // cicli per i tentativi di upload ftp
                 $msg = updateGAzieCart($form['server'],$form['user'],$form['pass'],$xml,$form['path'].'components/com_gaziecart/gaziecart.xml');
                 if (!$msg){ //  andato a buon fine
                      $i=9;
                      echo '<tr><td class="FacetDataTD">File: gaziecart.xml uploaded!</td></tr>';
                 } else {
                      echo '<tr><td class="FacetDataTDred">Attempts n.'.$i." gaziecart.xml failed: ".$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
                 }
             }
   echo "</tr></table>\n";
   if (!$msg) {
       gaz_dbi_put_row($gTables['company_config'],'var','server','val',$form['server']);
       gaz_dbi_put_row($gTables['company_config'],'var','user','val',$form['user']);
       gaz_dbi_put_row($gTables['company_config'],'var','pass','val',$form['pass']);
       gaz_dbi_put_row($gTables['company_config'],'var','path','val',$form['path']);
   }
}
?>
</form>
<?php
require("../../library/include/footer.php");
?>


