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
$msg = '';
if (isset($_POST['Insert']) || isset($_POST['Update'])) {   //se non e' il primo accesso
    $_POST['totfat']=preg_replace("/\,/",'.',$_POST['totfat']);
    $_POST['impeff']=preg_replace("/\,/",'.',$_POST['impeff']);
    $form=gaz_dbi_parse_post('effett');
    $form['ritorno'] = $_POST['ritorno'];
    $form['hidden_req'] = $_POST['hidden_req'];
    $form['clfoco']=substr($_POST['clfoco'],0,15);
    $form['banacc']=intval($_POST['banacc']);
    $form['date_emi_D']=intval($_POST['date_emi_D']);
    $form['date_emi_M']=intval($_POST['date_emi_M']);
    $form['date_emi_Y']=intval($_POST['date_emi_Y']);
    $form['date_doc_D']=intval($_POST['date_doc_D']);
    $form['date_doc_M']=intval($_POST['date_doc_M']);
    $form['date_doc_Y']=intval($_POST['date_doc_Y']);
    $form['date_exp_D']=intval($_POST['date_exp_D']);
    $form['date_exp_M']=intval($_POST['date_exp_M']);
    $form['date_exp_Y']=intval($_POST['date_exp_Y']);
    foreach($_POST['search'] as $k=>$v){
       $form['search'][$k]=$v;
    }

    $toDo = 'update';
    if (isset($_POST['Insert'])){
       $toDo = 'insert';
    }
    if (isset($_POST['Submit'])) { // conferma tutto
       // inizio controllo campi
       if (!checkdate( $form['date_emi_M'], $form['date_emi_D'], $form['date_emi_Y']) ||
           !checkdate( $form['date_exp_M'], $form['date_exp_D'], $form['date_exp_Y']) ||
           !checkdate( $form['date_doc_M'], $form['date_doc_D'], $form['date_doc_Y'])) {
           $msg .='0+';
       }
       $utsemi= mktime(0,0,0,$form['date_emi_M'],$form['date_emi_D'],$form['date_emi_Y']);
       $utsdoc= mktime(0,0,0,$form['date_doc_M'],$form['date_doc_D'],$form['date_doc_Y']);
       $utsexp= mktime(0,0,0,$form['date_exp_M'],$form['date_exp_D'],$form['date_exp_Y']);
       if ($utsdoc > $utsemi) {
          $msg .='3+';
       }
       if ($utsemi > $utsexp) {
          $msg .='4+';
       }
       if ($form['progre']<1 && $toDo=='update') {
          $msg .='5+';
       }
       if (empty($form['clfoco'])) {
          $msg .='6+';
       }

       if ($form['impeff']<0.01) {
          $msg .='7+';
       }
       if ($form['banapp']<1) {
          $msg .='8+';
       }
       if ($form['pagame']<1) {
          $msg .='9+';
       }
       //  --- fine controlli ----

       if (empty($msg)) { // nessun errore
          $tipeff=gaz_dbi_get_row($gTables['pagame'],'codice',$form['pagame']);
          $form['tipeff']=$tipeff['tippag'];
          $form['datemi']=sprintf("%04d-%02d-%02d",$form['date_emi_Y'],$form['date_emi_M'],$form['date_emi_D']);
          $form['datfat']=sprintf("%04d-%02d-%02d",$form['date_doc_Y'],$form['date_doc_M'],$form['date_doc_D']);
          $form['scaden']=sprintf("%04d-%02d-%02d",$form['date_exp_Y'],$form['date_exp_M'],$form['date_exp_D']);
          $anagrafica = new Anagrafica();
          if (preg_match("/^id_([0-9]+)$/",$form['clfoco'],$match)) {
             $new_clfoco = $anagrafica->getPartnerData($match[1],1);
             $form['clfoco']=$anagrafica->anagra_to_clfoco($new_clfoco,$admin_aziend['mascli'],$form['pagame']);
          }
          if ($toDo == 'insert') {
              // ricavo il progressivo annuo, ma se e' il primo effetto dell'anno, resetto il contatore
              $rs_last_effett = gaz_dbi_dyn_query("progre", $gTables['effett'], "YEAR(datemi) = ".$form['date_emi_Y'] ,"progre DESC",0,1);
              $last_progre = gaz_dbi_fetch_array($rs_last_effett);
              if ($last_progre) {
                 $form['progre'] = $last_progre['progre'] + 1;
              } else{
                 $form['progre'] = 1;
              }
              gaz_dbi_table_insert('effett',$form);
          } else {
              gaz_dbi_table_update('effett',array('id_tes',intval($form['id_tes'])),$form);
          }
          header("Location: report_effett.php");
          exit;
       }
    } elseif (isset($_POST['Return'])) { // torno indietro
          header("Location: ".$form['ritorno']);
          exit;
    }

    // Se viene inviata la richiesta di conferma cliente
    if ($_POST['hidden_req']=='clfoco') {
        $anagrafica = new Anagrafica();
        if (preg_match("/^id_([0-9]+)$/",$form['clfoco'],$match)) {
           $cliente = $anagrafica->getPartnerData($match[1],1);
        } else {
           $cliente = $anagrafica->getPartner($form['clfoco']);
        }
        $form['pagame']=$cliente['codpag'];
        $form['banapp']=$cliente['banapp'];
        $form['hidden_req'] = '';
    }

} elseif (!isset($_POST['Update']) && isset($_GET['Update'])) { //se e' il primo accesso per UPDATE
    $form=gaz_dbi_get_row($gTables['effett'],'id_tes',intval($_GET['id_tes']));
    $form['date_emi_D'] = substr($form['datemi'],8,2);
    $form['date_emi_M'] = substr($form['datemi'],5,2);
    $form['date_emi_Y'] = substr($form['datemi'],0,4);
    $form['date_doc_D'] = substr($form['datfat'],8,2);
    $form['date_doc_M'] = substr($form['datfat'],5,2);
    $form['date_doc_Y'] = substr($form['datfat'],0,4);
    $form['date_exp_D'] = substr($form['scaden'],8,2);
    $form['date_exp_M'] = substr($form['scaden'],5,2);
    $form['date_exp_Y'] = substr($form['scaden'],0,4);
    $toDo = 'update';
    $form['search']['clfoco']='';
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $anagrafica = new Anagrafica();
    $last=$anagrafica->queryPartners('*',"codice BETWEEN ".$admin_aziend['mascli']."000000 AND ".$admin_aziend['mascli']."999999" ,"codice DESC",0,1);
    $form=gaz_dbi_fields('effett');
    $toDo = 'insert';
    $form['date_emi_D']=date("d");
    $form['date_emi_M']=date("m");
    $form['date_emi_Y']=date("Y");
    $form['date_doc_D']=date("d");
    $form['date_doc_M']=date("m");
    $form['date_doc_Y']=date("Y");
    $form['date_exp_D']=date("d");
    $form['date_exp_M']=date("m");
    $form['date_exp_Y']=date("Y");
    $form['search']['clfoco']='';
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $form['hidden_req'] = '';
}
require("../../library/include/header.php");
$script_transl = HeadMain(0,array('calendarpopup/CalendarPopup',
                                  'custom/autocomplete'),'select_effett');
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
echo "<form method=\"POST\" name=\"select\">\n";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"".$form['ritorno']."\">\n";
echo "<input type=\"hidden\" name=\"banacc\" value=\"".$form['banacc']."\">\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">";
$gForm = new venditForm();
$select_customer = new selectPartner('clfoco');

if ($toDo == 'insert') {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['ins_this'];
   echo "<input type=\"hidden\" value=\"".$form['progre']."\" name=\"progre\" />\n";
   echo "<input type=\"hidden\" value=\"\" name=\"id_tes\" />\n";
} else {
   echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['upd_this'];
   echo "<input type=\"hidden\" value=\"".$form['id_tes']."\" name=\"id_tes\" />\n";
}
$select_customer->selectDocPartner('clfoco',$form['clfoco'],$form['search']['clfoco'],'clfoco',$script_transl['mesg'],$admin_aziend['mascli']);
echo "</div>\n" ;
echo '<div align="center"><table class="table-striped table-bordered table-condensed">';
if (!empty($msg)) {
    echo '<tr><td colspan="3" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td></tr>\n";
}
if ($toDo == 'update') {
   echo "<tr>\n";
   echo "\t<td > ID </td>\n";
   echo "\t<td colspan=\"2\" > ".$form['id_tes']." </td>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "\t<td >".$script_transl['progre']."* </td>\n";
   echo "\t<td colspan=\"2\" >
        <input type=\"text\" name=\"progre\" value=\"".$form['progre']."\" align=\"right\" maxlength=\"9\"  /></td>\n";
   echo "</tr>\n";
}
echo "<tr>\n";
echo "\t<td >".$script_transl['type'].' - '.$script_transl['pagame']." * </td><td colspan=\"2\" >\n";
echo "<select name=\"pagame\" class=\"FacetSelect\"  onchange=\"this.form.submit()\">\n";
      $rs_pagame = gaz_dbi_dyn_query ('*', $gTables['pagame'], "tippag = 'B' OR tippag = 'I' OR tippag = 'T' OR tippag = 'V'",'tippag');
      echo "\t\t <option value=\"0\"></option>\n";
      while ($r = gaz_dbi_fetch_array($rs_pagame)) {
            $selected = "";
            if($r["codice"] == $form['pagame'])
                $selected = "selected";
            echo "\t\t <option value=\"".$r["codice"]."\" $selected >".$script_transl['type_pay'][$r["tippag"]]." - ".$r["descri"]."</option>\n";
            }
echo "</select>\n";
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td >".$script_transl['date_emi']."</td><td colspan=\"2\" >\n";
$gForm->CalendarPopup('date_emi',$form['date_emi_D'],$form['date_emi_M'],$form['date_emi_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td >".$script_transl['numfat']." </td>\n";
echo "\t<td colspan=\"2\" >
      <input type=\"text\" name=\"numfat\" value=\"".$form['numfat']."\" style=\"text-align:right;\" maxlength=\"9\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td >".$script_transl['vat_section']."</td><td >\n";
$gForm->selectNumber('seziva',$form['seziva'],0,1,9);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td >".$script_transl['date_doc']."</td><td colspan=\"2\" >\n";
$gForm->CalendarPopup('date_doc',$form['date_doc_D'],$form['date_doc_M'],$form['date_doc_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td >".$script_transl['totfat']." </td>\n";
echo "\t<td colspan=\"2\" >
      <input type=\"text\" name=\"totfat\" value=\"".$form['totfat']."\" style=\"text-align:right;\" maxlength=\"12\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td >".$script_transl['salacc']."</td><td colspan=\"2\" >\n";
$gForm->variousSelect('salacc',$script_transl['salacc_value'],$form['salacc']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td >".$script_transl['impeff']." * </td>\n";
echo "\t<td colspan=\"2\" >
      <input type=\"text\" name=\"impeff\" value=\"".$form['impeff']."\" style=\"text-align:right;\" maxlength=\"12\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td >".$script_transl['date_exp']."</td><td colspan=\"2\" >\n";
$gForm->CalendarPopup('date_exp',$form['date_exp_D'],$form['date_exp_M'],$form['date_exp_Y'],'FacetSelect',1);
echo "</tr>\n";
echo "<tr>\n";
echo "<td >".$script_transl['banapp']." * </td><td colspan=\"2\"  colspan=\"2\">\n";
$select_banapp = new selectbanapp("banapp");
$select_banapp->addSelected($form["banapp"]);
$select_banapp->output();
echo "</td>\n";
echo "<tr>\n";
echo "<td >IBAN</td>";
echo "\t<td colspan=\"2\" >
      <input type=\"text\" name=\"iban\" value=\"".$form['iban']."\"  maxlength=\"23\"  /></td>\n";
echo "</tr>\n";
echo "</td>\n";
echo "</tr>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td >".$script_transl['banacc']."</td><td colspan=\"2\" >\n";
$banacc = gaz_dbi_get_row($gTables['clfoco'], "codice", $form['banacc']);
echo $banacc?$banacc['descri']:'da distintare';
echo "</td>\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td >".$script_transl['cigcup']." </td>\n";
echo "\t<td colspan=\"2\" >
      <input type=\"text\" name=\"cigcup\" value=\"".$form['cigcup']."\"  maxlength=\"40\"   /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFooterTD\" >".$script_transl['sqn']."</td>";
echo "\t </td>\n";
echo "\t<td align=\"center\" class=\"FacetFooterTD\">\n";
echo '<input name="Submit" class="btn btn-warning" type="submit" value="'.ucfirst($script_transl[$toDo]).'">';
echo "\t </td>\n";
echo "</tr>\n";
?>
</table></div>
</form>
<?php
require("../../library/include/footer.php");
?>
