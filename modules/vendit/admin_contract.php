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

$anagrafica = new Anagrafica();

if (!isset($_POST['ritorno'])) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
    $form['ritorno'] = $_POST['ritorno'];
}

if ((isset($_GET['Update']) and  !isset($_GET['id_contract']))) {
    header("Location: ".$form['ritorno']);
    exit;
}


if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso
    //qui si deve fare un parsing di quanto arriva dal browser...
    $form['id_contract'] = intval($_POST['id_contract']);
    $cliente = $anagrafica->getPartner(intval($_POST['id_customer']));
    $form['hidden_req'] = $_POST['hidden_req'];
    foreach($_POST['search'] as $k=>$v){
      $form['search'][$k]=$v;
    }
    $form['doc_type'] = strtoupper(substr($_POST['doc_type'],0,3));
    $form['id_customer'] = substr($_POST['id_customer'],0,13);
    $form['vat_section'] = intval($_POST['vat_section']);
    $form['doc_number'] = intval($_POST['doc_number']);
    $form['conclusion_date_Y'] = intval($_POST['conclusion_date_Y']);
    $form['conclusion_date_M'] = intval($_POST['conclusion_date_M']);
    $form['conclusion_date_D'] = intval($_POST['conclusion_date_D']);
    $form['start_date_Y'] = intval($_POST['start_date_Y']);
    $form['start_date_M'] = intval($_POST['start_date_M']);
    $form['start_date_D'] = intval($_POST['start_date_D']);
    $form['months_duration'] = intval($_POST['months_duration']);
    $form['covered_month'] = intval($_POST['covered_month']);
    $form['covered_year'] = intval($_POST['covered_year']);
    $form['initial_fee'] = floatval(preg_replace("/\,/",'.',$_POST['initial_fee']));
    $form['periodic_reassessment'] = intval($_POST['periodic_reassessment']);
    $form['bank'] = intval($_POST['bank']);
    $form['payment_method'] = intval($_POST['payment_method']);
    $form['periodicity'] = intval($_POST['periodicity']);
    $form['tacit_renewal'] = intval($_POST['tacit_renewal']);
    $form['current_fee'] = floatval(preg_replace("/\,/",'.',$_POST['current_fee']));
    $form['cod_revenue'] = intval($_POST['cod_revenue']);
    $form['vat_code'] = intval($_POST['vat_code']);
    $form['id_body_text'] = intval($_POST['id_body_text']);
    $form['body_text'] = $_POST['body_text'];
    $form['last_reassessment_Y'] = intval($_POST['last_reassessment_Y']);
    $form['last_reassessment_M'] = intval($_POST['last_reassessment_M']);
    $form['last_reassessment_D'] = intval($_POST['last_reassessment_D']);
    $form['id_agente'] = intval($_POST['id_agente']);
    $form['provvigione'] = floatval(preg_replace("/\,/",'.',$_POST['provvigione']));
    $form['status'] = $_POST['status'];
    // inizio rigo di input
    $form['in_descri'] = $_POST['in_descri'];
    $form['in_unimis'] = $_POST['in_unimis'];
    $form['in_quanti'] = gaz_format_quantity($_POST['in_quanti'],0,$admin_aziend['decimal_quantity']);
    $form['in_price'] = $_POST['in_price'];
    $form['in_discount'] = $_POST['in_discount'];
    $form['in_vat_code'] = $_POST['in_vat_code'];
    $form['in_cod_revenue'] = $_POST['in_cod_revenue'];
    // fine rigo input
    $form['rows'] = array();
    $next_row = 0;
    if (isset($_POST['rows'])) {
       foreach ($_POST['rows'] as $next_row => $value) {
            $form['rows'][$next_row]['descri'] = substr($value['descri'],0,100);
            $form['rows'][$next_row]['unimis'] = substr($value['unimis'],0,3);
            $form['rows'][$next_row]['price'] = number_format(preg_replace("/\,/",'.',$value['price']),$admin_aziend['decimal_price'],'.','');
            $form['rows'][$next_row]['discount'] = floatval(preg_replace("/\,/",'.',$value['discount']));
            $form['rows'][$next_row]['quanti'] = gaz_format_quantity($value['quanti'],0,$admin_aziend['decimal_quantity']);
            $form['rows'][$next_row]['vat_code'] = intval($value['vat_code']);
            $form['rows'][$next_row]['cod_revenue'] = intval($value['cod_revenue']);
            $next_row++;
       }
    }
    // Se viene inviata la richiesta di conferma totale ...
    if (isset($_POST['ins'])) {
       $form['conclusion_date'] = $form['conclusion_date_Y']."-".$form['conclusion_date_M']."-".$form['conclusion_date_D'];
       $utsconcl = mktime(0,0,0,$form['conclusion_date_M'],$form['conclusion_date_D'],$form['conclusion_date_Y']);
       $form['start_date'] = $form['start_date_Y']."-".$form['start_date_M']."-".$form['start_date_D'];
       $utsstart = mktime(0,0,0,$form['start_date_M'],$form['start_date_D'],$form['start_date_Y']);
       $form['last_reassessment'] = $form['last_reassessment_Y']."-".$form['last_reassessment_M']."-".$form['last_reassessment_D'];
       $utsreass = mktime(0,0,0,$form['last_reassessment_M'],$form['last_reassessment_D'],$form['last_reassessment_Y']);
       if (!checkdate($form['conclusion_date_M'],$form['conclusion_date_D'],$form['conclusion_date_Y'])) {
          $msg .= "0+";
       }
       if (!checkdate($form['start_date_M'],$form['start_date_D'],$form['start_date_Y'])) {
          $msg .= "1+";
       }
       if (!checkdate($form['last_reassessment_M'],$form['last_reassessment_D'],$form['last_reassessment_Y'])) {
          $msg .= "2+";
       }
       if ($utsconcl>$utsstart) {
          $msg .= "3+";
       }
       if ($utsstart>$utsreass) {
          $msg .= "4+";
       }
       if (empty($form["id_customer"])) {
          $msg .= "5+";
       }
       if (empty ($form["payment_method"])) {
          $msg .= "6+";
       }
       if (empty ($form["body_text"])) {
          $msg .= "9+";
       }
       //if ($form["current_fee"] <= 0) { consento che il canone corrente sia a 0, l'eventuale testo del contratto andrà messo come descrittivo
       //   $msg .= "10+";
       //}
       //controllo che i rows non abbiano descrizioni e unita' di misura vuote in presenza di quantita diverse da 0
       foreach ($form['rows'] as $i => $value) {
            if (empty($value['descri']) && $value['quanti']>0) {
                $msg .= "7+";
            }
            if (empty($value['unimis']) && $value['quanti']>0) {
                $msg .= "8+";
            }
       }
       if ($msg == "") { // nessun errore
          if (preg_match("/^id_([0-9]+)$/",$form['id_customer'],$match)) {
             $new_clfoco = $anagrafica->getPartnerData($match[1],1);
             $form['id_customer']=$anagrafica->anagra_to_clfoco($new_clfoco,$admin_aziend['mascli'],$form['payment_method']);
          }
          if ($toDo == 'update') { // e' una modifica
             $old_rows = gaz_dbi_dyn_query("*", $gTables['contract_row'], "id_contract = ".$form['id_contract'],"id_contract");
             $i=0;
             $count = count($form['rows'])-1;
             while ($val_old_row = gaz_dbi_fetch_array($old_rows)) {
                   if ($i <= $count) { //se il vecchio rigo e' ancora presente nel nuovo lo modifico
                      $form['rows'][$i]['id_contract'] = $form['id_contract'];
                      contractRowUpdate($form['rows'][$i],array('id_row',$val_old_row['id_row']));
                   } else { //altrimenti lo elimino
                      gaz_dbi_del_row($gTables['contract_row'], 'id_row', $val_old_row['id_row']);
                   }
                   $i++;
             }
             //qualora i nuovi rows fossero di più dei vecchi inserisco l'eccedenza
             for ($i = $i; $i <= $count; $i++) {
                $form['rows'][$i]['id_contract'] = $form['id_contract'];
                contractRowUpdate($form['rows'][$i]);
             }
             bodytextUpdate(array('id_body',$form['id_body_text']),array('table_name_ref'=>'contract','id_ref'=>$form['id_contract'],'body_text'=>$form['body_text'],'lang_id'=>$admin_aziend['id_language']));
             $form['data_ordine']= $form['covered_year'].'-'.$form['covered_month'].'-01';
             contractUpdate($form, array('id_contract',$form['id_contract']),$gTables['tesdoc']);
             header("Location: ".$form['ritorno']);
             exit;
          } else { // e' un'inserimento
            $ultimo_id=contractUpdate($form);
            $ultimo_id_body=bodytextInsert(array('table_name_ref'=>'contract','id_ref'=>$ultimo_id,'body_text'=>$form['body_text'],'lang_id'=>$admin_aziend['id_language']));
            gaz_dbi_put_row($gTables['contract'], 'id_contract', $ultimo_id, 'id_body_text', $ultimo_id_body);
            //inserisco i rows
            foreach ($form['rows'] as $i=>$value) {
                  $value['id_contract'] = $ultimo_id;
                  contractRowUpdate($value);
            }
            $_SESSION['print_request']=$ultimo_id;
            header("Location: invsta_contract.php");
            exit;
          }
    }
  }
  // Se viene inviata la richiesta di conferma cliente
  if ($_POST['hidden_req']=='id_customer') {
    if (preg_match("/^id_([0-9]+)$/",$form['id_customer'],$match)) {
        $cliente = $anagrafica->getPartnerData($match[1],1);
    } else {
        $cliente = $anagrafica->getPartner($form['id_customer']);
    }
    $form['payment_method']=$cliente['codpag'];
    $form['bank']=$cliente['banapp'];
    $form['id_agente']=$cliente['id_agente'];
    $form['in_vat_code']=$cliente['aliiva'];
    $provvigione = new Agenti;
    $form['provvigione']=$provvigione->getPercent($form['id_agente']);
    $form['hidden_req']='';
  }

  // Se viene modificato l'agente ricarico la provvigione
  if ($_POST['hidden_req'] == 'AGENTE') {
     if ($form['id_agente'] > 0) {
         $provvigione = new Agenti;
         $form['provvigione']=$provvigione->getPercent($form['id_agente']);
    } else {
         $form['provvigione']=0.00;
    }
    $form['hidden_req']='';
  }

  // Se viene inviata la richiesta di conferma rigo
  if (isset($_POST['in_submit'])) {
    $form['rows'][$next_row]['descri'] = $form['in_descri'];
    $form['rows'][$next_row]['unimis'] = $form['in_unimis'];
    $form['rows'][$next_row]['price'] = number_format($form['in_price'],$admin_aziend['decimal_price'],'.','');
    $form['rows'][$next_row]['cod_revenue'] = $form['in_cod_revenue'];
    $form['rows'][$next_row]['quanti'] = $form['in_quanti'];
    $form['rows'][$next_row]['discount'] = $form['in_discount'];
    $form['rows'][$next_row]['vat_code'] =  $form['in_vat_code'];
    $form['rows'][$next_row]['cod_revenue'] = $form['in_cod_revenue'];
    // reinizializzo rigo di input tranne che tipo rigo, aliquota iva e conto ricavo
    $form['in_descri'] = "";
    $form['in_unimis'] = "";
    $form['in_price'] = 0;
    $form['in_discount'] = 0;
    $form['in_quanti'] = 0;
    // fine reinizializzo rigo input
    $next_row++;
  }

  // Se viene inviata la richiesta elimina il rigo corrispondente
  if (isset($_POST['del'])) {
    $delri= key($_POST['del']);
    array_splice($form['rows'],$delri,1);
    $next_row--;
  }

} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $contract = gaz_dbi_get_row($gTables['contract'],"id_contract",intval($_GET['id_contract']));
    $tesdoc = gaz_dbi_get_row($gTables['tesdoc'],"id_contract",intval($_GET['id_contract'])," ORDER BY data_ordine DESC");
    $cliente = $anagrafica->getPartner($contract['id_customer']);
    $form['hidden_req'] = '';
    $form['id_contract'] = $contract['id_contract'];
    $form['doc_type'] = $contract['doc_type'];
    $form['id_customer'] = $contract['id_customer'];
    $form['search']['id_customer']=substr($cliente['ragso1'],0,10);
    $form['vat_section'] = $contract['vat_section'];
    $form['doc_number'] = $contract['doc_number'];
    $form['conclusion_date_Y'] = substr($contract['conclusion_date'],0,4);
    $form['conclusion_date_M'] = substr($contract['conclusion_date'],5,2);
    $form['conclusion_date_D'] = substr($contract['conclusion_date'],8,2);
    $form['start_date_Y'] = substr($contract['start_date'],0,4);
    $form['start_date_M'] = substr($contract['start_date'],5,2);
    $form['start_date_D'] = substr($contract['start_date'],8,2);
    $form['months_duration'] = $contract['months_duration'];
    $form['covered_month'] = $tesdoc ? substr($tesdoc['data_ordine'],5,2) : '';
    $form['covered_year'] = $tesdoc ? substr($tesdoc['data_ordine'],0,4) : '';
    $form['initial_fee'] = $contract['initial_fee'];
    $form['periodic_reassessment'] = $contract['periodic_reassessment'];
    $form['bank'] = $contract['bank'];
    $form['payment_method'] = $contract['payment_method'];
    $form['tacit_renewal'] = $contract['tacit_renewal'];
    $form['current_fee'] = $contract['current_fee'];
    $form['vat_code'] = $contract['vat_code'];
    $form['cod_revenue'] = $contract['cod_revenue'];
    $form['id_body_text'] = $contract['id_body_text'];
    $bodytext = gaz_dbi_get_row($gTables['body_text'],"id_body",$contract['id_body_text']);
    $form['body_text'] = $bodytext['body_text'];
    $form['last_reassessment_Y'] = substr($contract['last_reassessment'],0,4);
    $form['last_reassessment_M'] = substr($contract['last_reassessment'],5,2);
    $form['last_reassessment_D'] = substr($contract['last_reassessment'],8,2);
    $form['periodicity'] = $contract['periodicity'];
    $form['provvigione'] = $contract['provvigione'];
    $form['id_agente'] = $contract['id_agente'];
    $form['status'] = $contract['status'];

    // inizio rigo di input
    $form['in_descri'] = "";
    $form['in_unimis'] = '';
    $form['in_quanti'] = 0;
    $form['in_price'] = 0;
    $form['in_discount'] = 0;
    $form['in_vat_code'] = $admin_aziend['preeminent_vat'];
    $form['in_cod_revenue'] = $admin_aziend['impven'];
    // fine rigo input

    $form['rows'] = array();
    $next_row = 0;
    $rs_row = gaz_dbi_dyn_query("*", $gTables['contract_row'], "id_contract = ".intval($_GET['id_contract']),"id_row ASC");
    while ($row = gaz_dbi_fetch_array($rs_row)) {
           $form['rows'][$next_row]['descri'] = $row['descri'];
           $form['rows'][$next_row]['unimis'] = $row['unimis'];
           $form['rows'][$next_row]['price'] = number_format($row['price'],$admin_aziend['decimal_price'],'.','');
           $form['rows'][$next_row]['discount'] = $row['discount'];
           $form['rows'][$next_row]['quanti'] = gaz_format_quantity($row['quanti'],0,$admin_aziend['decimal_quantity']);
           $form['rows'][$next_row]['vat_code'] = $row['vat_code'];
           $form['rows'][$next_row]['cod_revenue'] = $row['cod_revenue'];
           $next_row++;
    }
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['id_contract'] = '';
    $form['id_customer'] = '';
    if (empty($admin_aziend['pariva'])){
        $form['doc_type'] = 'VRI';
    } else {
        $form['doc_type'] = 'FAI';
    }
    $cliente['indspe'] = '';
    $form['search']['id_customer']='';
    if (!isset($_GET['vat_section'])) {
        $rs_last = gaz_dbi_dyn_query("vat_section,doc_type", $gTables['contract'], 1,"id_contract DESC",0,1);
        $last = gaz_dbi_fetch_array($rs_last);
                if ($last){
                   $form['vat_section'] = $last['vat_section'];
                } else {
                   $form['vat_section'] = 1;
                }
    } else {
        $form['vat_section'] = intval($_GET['vat_section']);
    }
    // trovo l'ultimo numero di contratto
    $rs_last = gaz_dbi_dyn_query("*", $gTables['contract'], "YEAR(conclusion_date)=".date("Y"),"doc_number DESC",0,1);
    $last = gaz_dbi_fetch_array($rs_last);
    $form['doc_number'] = ($last)?($last['doc_number']+1):1;
    $form['conclusion_date_Y'] = date("Y");
    $form['conclusion_date_M'] = date("m");
    $form['conclusion_date_D'] = date("d");
    $form['start_date'] = date("d-m-Y");
    $form['start_date_Y'] = date("Y");
    $form['start_date_M'] = date("m");
    $form['start_date_D'] = date("d");
    $form['months_duration'] = 48;
    $form['covered_month'] = '';
    $form['covered_year'] = '';
    $form['initial_fee'] = 0.00;
    $form['periodic_reassessment'] = 1;
    $form['payment_method'] = 0;
    $form['bank'] = 0;
    $form['periodicity'] = 0;
    $form['tacit_renewal'] = 1;
    $form['current_fee'] = 0.00;
    $form['cod_revenue'] = $admin_aziend['impven'];
    $form['id_body_text'] = 0;
    $form['vat_code'] = $admin_aziend['preeminent_vat'];
    $form['body_text'] = '';
    $form['last_reassessment'] = '';
    $form['last_reassessment_Y'] = date("Y");
    $form['last_reassessment_M'] = date("m");
    $form['last_reassessment_D'] = date("d");
    $form['id_agente'] = 0;
    $form['provvigione'] = 0.00;
    $form['status'] ='';
    $form['rows'] = [];
    $next_row = 0;
    $form['hidden_req'] = '';
    // inizio rigo di input
    $form['in_descri'] = "";
    $form['in_type_row'] = 0;
    $form['in_unimis'] = "";
    $form['in_price'] = 0;
    $form['in_discount'] = 0;
    $form['in_quanti'] = 0;
    $form['in_vat_code'] = $admin_aziend['preeminent_vat'];
    $form['in_cod_revenue'] = $admin_aziend['impven'];
    // fine rigo input
}

require("../../library/include/header.php");
$script_transl = HeadMain(0,array('calendarpopup/CalendarPopup','custom/autocomplete'));
$title = ucfirst($script_transl['ins_this']);
if ($toDo=='update'){
  $title = ucfirst($script_transl['upd_this']);
}
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
echo "<form method=\"POST\" name=\"contract\">\n";
$gForm = new GAzieForm();
echo "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">\n";
echo "<input type=\"hidden\" value=\"".$form['id_contract']."\" name=\"id_contract\">\n";
echo "<input type=\"hidden\" value=\"".$form['id_body_text']."\" name=\"id_body_text\">\n";
echo "<input type=\"hidden\" value=\"".$form['ritorno']."\" name=\"ritorno\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">$title ";
$select_cliente = new selectPartner("id_customer");
$select_cliente->selectDocPartner('id_customer',$form['id_customer'],$form['search']['id_customer'],'id_customer',$script_transl['mesg'],$admin_aziend['mascli']);
echo ' n.'.$form['doc_number']."</div>\n";
echo "<div class=\"table-responsive\"><table class=\"Tlarge table table-striped table-bordered table-condensed\">\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['vat_section']."</td><td class=\"FacetDataTD\">\n";
$gForm->selectNumber('vat_section',$form['vat_section'],0,1,9);
echo "\t </td>\n";
if (!empty($msg)) {
    echo '<td colspan="2" class="FacetDataTDred">'.$gForm->outputErrors($msg,$script_transl['errors'])."</td>\n";
} else {
    echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['address']."</td><td>".(($cliente)?$cliente['indspe']:'')."<br />";
    echo "</td>\n";
}
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['conclusion_date']."</td><td class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('conclusion_date',$form['conclusion_date_D'],$form['conclusion_date_M'],$form['conclusion_date_Y']);
echo "\t</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['doc_number']."</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"doc_number\" value=\"".$form['doc_number']."\" align=\"right\" maxlength=\"9\" /></td>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['current_fee']."</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"current_fee\" value=\"".$form['current_fee']."\" align=\"right\" maxlength=\"9\" tabindex=\"2\" /></td>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['start_date']."</td>\n";
echo "\t<td class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('start_date',$form['start_date_D'],$form['start_date_M'],$form['start_date_Y']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['initial_fee']."</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"initial_fee\" value=\"".$form['initial_fee']."\" align=\"right\" maxlength=\"9\" /></td>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['bank']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('banapp','bank','codice',$form['bank'],'codice',1,' ','descri');
echo "</td>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['payment_method']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('pagame','payment_method','codice',$form['payment_method'],'codice',1,' ','descri');
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['months_duration']."</td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"months_duration\" value=\"".$form['months_duration']."\" align=\"right\" maxlength=\"3\" />\n";
echo "\t </td>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['periodicity']."</td><td  class=\"FacetDataTD\">\n";
$gForm->variousSelect('periodicity',$script_transl['periodicity_value'],$form['periodicity']);
echo "\t </td>\n";

echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['doc_type']."</td><td  class=\"FacetDataTD\">\n";
$gForm->variousSelect('doc_type',$script_transl['doc_type_value'],$form['doc_type']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['tacit_renewal']."</td><td class=\"FacetDataTD\">\n";
$gForm->variousSelect('tacit_renewal',$script_transl['tacit_renewal_value'],$form['tacit_renewal'],'',false);
echo "\t </td>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['cod_revenue']."</td><td class=\"FacetDataTD\">\n";
$select_cod_revenue = new selectconven('cod_revenue');
$select_cod_revenue->addSelected($form['cod_revenue']);
$select_cod_revenue->output(substr($form['cod_revenue'],0,1));
echo "\t </td>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['last_reassessment']."</td><td class=\"FacetDataTD\">\n";
$gForm->CalendarPopup('last_reassessment',$form['last_reassessment_D'],$form['last_reassessment_M'],$form['last_reassessment_Y']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">\n".$script_transl['periodic_reassessment']."</td><td  class=\"FacetDataTD\">\n";
$gForm->variousSelect('periodic_reassessment',$script_transl['periodic_reassessment_value'],$form['periodic_reassessment'],'',false);
echo "\t </td>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['vat_code']."</td><td  class=\"FacetDataTD\">\n";
$gForm->selectFromDB('aliiva','vat_code','codice',$form['vat_code'],'codice',0,' - ','descri');
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">".$script_transl['id_agente']."</td><td  class=\"FacetDataTD\">\n";
$select_agente = new selectAgente("id_agente");
$select_agente->addSelected($form["id_agente"]);
$select_agente->output();
echo " ".$script_transl['provvigione']."\n";
echo "\t<input type=\"text\" name=\"provvigione\" value=\"".$form['provvigione']."\" align=\"right\" maxlength=\"5\" />\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\"></td><td  class=\"FacetDataTD\">";
echo "\t </td>\n";
echo "<td class=\"FacetFieldCaptionTD\"></td><td  class=\"FacetDataTD\">\n";
echo "</td>\n";
echo "<td class=\"FacetFieldCaptionTD\">Ultimo mese pagato</td><td  class=\"FacetDataTD\">\n";
$gazTimeFormatter->setPattern('MMMM');
echo "\t <select name=\"covered_month\" onchange=\"this.form.submit()\">\n";
echo "\t <option value=\"\"> - - - - - - </option>\n";
for ($counter = 1;$counter <= 12;$counter++) {
	$selected = "";
	if ($counter == $form['covered_month']) $selected = "selected";
  $nome_mese = $gazTimeFormatter->format(new DateTime("2000-".$counter."-01"));
	echo "\t <option value=\"$counter\"  $selected >$nome_mese</option>\n";
}
echo "\t </select>\n";
echo "\t <select name=\"covered_year\" onchange=\"this.form.submit()\">\n";
echo "\t <option value=\"\"> - - - - </option>\n";
$dmiddle=($form['covered_year']>=2000)?$form['covered_year']:date("Y");
for ($counter = $dmiddle - 10;$counter <= $dmiddle + 10;$counter++) {
	$selected = "";
	if ($counter == $form['covered_year']) $selected = "selected";
	echo "\t <option value=\"$counter\"  $selected >$counter</option>\n";
}
echo "\t </select>\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo '<td colspan=3 align="right"><b>'.$script_transl['body_text'].'</b></td><td colspan=3>';
echo $script_transl['status'].': ';
$gForm->variousSelect('status',$script_transl['status_value'],$form['status'],'',false);
echo '</td></tr>';
echo "\t<tr><td colspan=\"6\">\n";
echo "<textarea id=\"body_text\" name=\"body_text\" class=\"mceClass\" style=\"width:100%;height:400px;\" >".$form['body_text']."</textarea>\n";
echo "</td></tr>\n";
echo "</table></div>\n";
echo "<div class=\"table-responsive\"><table class=\"Tlarge table table-striped\">\n";
if ($next_row>0) {
  echo "<tr class=\"bg-info text-center\"><td colspan=\"8\"><b>".$script_transl['insrow']."</b></td></tr>\n";
  foreach ($form['rows'] as $k=>$val) {
    $nr=$k+1;
    $aliiva = gaz_dbi_get_row($gTables['aliiva'],'codice',$val['vat_code']);
    echo "<input type=\"hidden\" value=\"".$val['vat_code']."\" name=\"rows[$k][vat_code]\">\n";
    echo "<input type=\"hidden\" value=\"".$val['cod_revenue']."\" name=\"rows[$k][cod_revenue]\">\n";
    echo "<tr class=\"FacetFieldCaptionTD\">\n";
    echo '<td>'.$nr."</td><td colspan=2><input type=\"text\" name=\"rows[$k][descri]\" value=\"".$val['descri']."\" maxlength=\"100\" /><br/>
          ".$script_transl['cod_revenue'].": ".$val['cod_revenue']." - ".$aliiva['descri']."</td>\n";
    echo "<td><input type=\"text\" name=\"rows[$k][unimis]\" value=\"".$val['unimis']."\" maxlength=\"3\" /></td>\n";
    echo "<td><input type=\"text\" style=\"text-align:right\" name=\"rows[$k][quanti]\" value=\"".$val['quanti']."\" maxlength=\"11\" /></td>\n";
    echo "<td><input type=\"text\" style=\"text-align:right\" name=\"rows[$k][price]\" value=\"".$val['price']."\" maxlength=\"15\" /></td>\n";
    echo "<td><input type=\"text\" style=\"text-align:right\" name=\"rows[$k][discount]\" value=\"".$val['discount']."\" maxlength=\"4\" /></td>\n";
	  echo '  <td align="right">
				 <button type="submit" class="btn btn-default btn-sm" name="del['.$k.']" title="'.$script_transl['delete'].$script_transl['thisrow'].'"><i class="glyphicon glyphicon-trash"></i></button>
			   </td>
			 </tr>';
		echo "\t </tr>\n";
  }
}
echo "</table></div><br/>\n";
echo "<div class=\"table-responsive\"><table class=\"Tlarge table input-area\">\n";
echo "<tr>\n";
echo "\t<td colspan=\"8\" align=\"center\"><b>".$script_transl['rows_title']."</b></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<input type=\"hidden\" value=\"".$form['hidden_req']."\" name=\"hidden_req\" />\n";
echo "\t<tr align=\"center\">\n";
echo "\t<td colspan=\"3\">".$script_transl['descri']."</td>\n";
echo "\t<td>".$script_transl['unimis']."</td>\n";
echo "\t<td>".$script_transl['quanti']."</td>\n";
echo "\t<td>".$script_transl['price']."</td>\n";
echo "\t<td>".$script_transl['discount']."</td>\n";
echo "</tr>\n";
echo "<tr align=\"center\">\n";
echo "<td colspan=\"3\">\n";
echo "<input type=\"text\" value=\"".$form['in_descri']."\" maxlength=\"100\" name=\"in_descri\">\n";
echo "\t </td>\n";
echo "<td>\n";
echo "<input type=\"text\" value=\"".$form['in_unimis']."\" maxlength=\"3\" name=\"in_unimis\">\n";
echo "\t </td>\n";
echo "<td>\n";
echo "<input type=\"text\" style=\"text-align:right\" value=\"".$form['in_quanti']."\" maxlength=\"11\" name=\"in_quanti\">\n";
echo "\t </td>\n";
echo "<td>\n";
echo "<input type=\"text\" style=\"text-align:right\" value=\"".$form['in_price']."\" maxlength=\"15\" name=\"in_price\">\n";
echo "\t </td>\n";
echo "<td>\n";
echo "<input type=\"text\" style=\"text-align:right\" value=\"".$form['in_discount']."\" maxlength=\"4\" name=\"in_discount\">";
echo "\t </td>\n";
echo "<td align=\"right\">\n";
echo '&nbsp;<button type="submit" class="btn btn-success" name="in_submit"><i class="glyphicon glyphicon-ok"></i> '.$script_transl['insert'].$script_transl['thisrow'].'</button>';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "\t<tr>\n";
echo "<td colspan=\"7\">\n";
echo $script_transl['vat_code'].' :';
$gForm->selectFromDB('aliiva','in_vat_code','codice',$form['in_vat_code'],'codice',0,' - ','descri');
echo $script_transl['cod_revenue'].' :';
$select_cod_revenue = new selectconven("in_cod_revenue");
$select_cod_revenue -> addSelected($form['in_cod_revenue']);
$select_cod_revenue -> output(substr($form['in_cod_revenue'],0,1));
echo "\t </td>\n";
echo "\t </tr>\n";
echo "\t<tr class=\"FacetFooterTD\">\n";
echo '<td colspan=8 align="center"> <input type="submit" class="btn btn-warning" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="';
echo $script_transl['submit'];
echo '" tabindex="100" >';
echo "\t </td>\n";
echo "\t </tr>\n";
echo "</table></div>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>
