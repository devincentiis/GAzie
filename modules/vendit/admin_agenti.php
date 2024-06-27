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
    scriva   alla   Free  Software Foundation,  Inc.,   59
    Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
 --------------------------------------------------------------------------
*/
require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();
$msg = "";

$mastrofornitori = $admin_aziend['masfor']."000000";
$inifornitori=$admin_aziend['masfor'].'000001';
$finfornitori=$admin_aziend['masfor'].'999999';

if (!isset($_POST['ritorno'])) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
    $form['ritorno'] = $_POST['ritorno'];
}
if (isset($_GET['Update']) and  !isset($_GET['id_agente'])) {
    header("Location: ".$form['ritorno']);
    exit;
}

if ((isset($_POST['Update'])) or (isset($_GET['Update']))) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

if ((isset($_POST['Insert'])) or (isset($_POST['Update']))) {   //se non e' il primo accesso
  //qui si dovrebbe fare un parsing di quanto arriva dal browser... o altro;-)
  $form['id_agente'] = intval($_POST['id_agente']);
  $form['clfoco'] = substr($_POST['clfoco'],0,12);
  $form['adminid'] = substr($_POST['adminid'],0,20);
  $form['id_agente_coord'] = intval($_POST['id_agente_coord']);
  $form['coord_percent'] = floatval(preg_replace("/\,/",'.',$_POST['coord_percent']));
  $form['hidden_req'] = substr($_POST['hidden_req'],0,20);;
  $form['base_percent'] = floatval(preg_replace("/\,/",'.',$_POST['base_percent']));
  $anagrafica = new Anagrafica();
  $fornitore = $anagrafica->getPartner($form['clfoco']);
  // inizio rigo di input
  $form['in_cod_articolo'] = substr($_POST['in_cod_articolo'],0,15);
  $form['in_cod_catmer'] = intval($_POST['in_cod_catmer']);
  $form['in_percentuale'] = floatval(preg_replace("/\,/",'.',$_POST['in_percentuale']));
  $form['in_status'] = $_POST['in_status'];
  $form['cosear'] = $_POST['cosear'];
  foreach ($_POST['search'] as $k => $v) {
      $form['search'][$k] = $v;
  }
  // fine rigo input
  $form['righi'] = array();
  $next_row = 0;
  $admuser = gaz_dbi_get_row($gTables['admin'],'user_name',$form['adminid']," AND Abilit >= 8");
  if (!empty($admuser)) { // l'agente non può essere amministratore
    $msg .= "21+";
  }
  if (isset($_POST['righi'])) {
     foreach ($_POST['righi'] as $next_row => $value) {
          // inizio impedimento della duplicazione dei codici
          if ( (!empty($value['cod_articolo']) && $value['cod_articolo'] == $form['in_cod_articolo'] ) ||
               (!empty($value['cod_catmer']) && $value['cod_catmer'] == $form['in_cod_catmer'] ) ) { //codice esistente
                 $msg = "7-8-11+";
                 //unset($_POST['in_submit_x']);
			   unset($_POST['in_submit']);
          }
          // fine controllo impedimento inserimento codici esistenti
          $form['righi'][$next_row]['id_provvigione'] = intval($value['id_provvigione']);
          $form['righi'][$next_row]['cod_articolo'] = substr($value['cod_articolo'],0,15);
          $form['righi'][$next_row]['cod_catmer'] = intval($value['cod_catmer']);
          $form['righi'][$next_row]['percentuale'] = floatval(preg_replace("/\,/",'.',$value['percentuale']));
          $form['righi'][$next_row]['status'] = substr($value['status'],0,10);
          if (isset($_POST['upd_row'])) {
             $key_up = key($_POST['upd_row']);
             if ($key_up == $next_row) {
                $form['in_cod_articolo'] = $form['righi'][$key_up]['cod_articolo'];
                $form['in_cod_catmer'] = $form['righi'][$key_up]['cod_catmer'];
                $form['in_percentuale'] = $form['righi'][$key_up]['percentuale'];
                $form['in_status'] = "UPDROW".$next_row;
                $form['cosear'] = $form['in_cod_articolo'];
                array_splice($form['righi'],$key_up,1);
                $next_row--;
             }
          }
          $next_row++;
     }
  }
  if (isset($_POST['in_submit'])) {
   if ((!empty($form['in_cod_articolo']) || $form['in_cod_catmer'] > 0) && $form['in_percentuale'] >= 0) {
    if (substr($form['in_status'],0,6) == "UPDROW"){ //se � un rigo da modificare
         $old_key = intval(substr($form['in_status'],6));
         $form['righi'][$old_key]['id_provvigione'] = $form['id_provvigione'];
         $form['righi'][$old_key]['cod_articolo'] = $form['in_cod_articolo'];
         $form['righi'][$old_key]['cod_catmer'] = $form['in_cod_catmer'];
         $form['righi'][$old_key]['percentuale'] = $form['in_percentuale'];
         $form['righi'][$old_key]['status'] = "UPDATE";
         ksort($form['righi']);
    } else { //se � un rigo da inserire
         $form['righi'][$next_row]['id_provvigione'] = 0;
         $form['righi'][$next_row]['cod_articolo'] = $form['in_cod_articolo'];
         $form['righi'][$next_row]['cod_catmer'] = $form['in_cod_catmer'];
         $form['righi'][$next_row]['percentuale'] = $form['in_percentuale'];
         $form['righi'][$next_row]['status'] = "INSERT";
    }
    // reinizializzo rigo di input tranne che per il tipo rigo e aliquota iva
    $form['in_cod_articolo'] = '';
    $form['in_cod_catmer'] = 0;
    $form['in_percentuale'] = 0;
    $form['in_status'] = "INSERT";
    // fine reinizializzo rigo input
    $form['cosear'] = '';
    $next_row++;
  } else {  // dati insufficenti per aggiungere un rigo
       $msg .= "12+";
  }
  }
  // Se viene inviata la richiesta di conferma totale ...
  if (isset($_POST['ins'])) {
    if (preg_match("/^id_([0-9]+)$/", $form['clfoco'], $match)) {
      $new_clfoco = $anagrafica->getPartnerData($match[1], 1);
      $form['clfoco'] = $anagrafica->anagra_to_clfoco($new_clfoco, $admin_aziend['masfor']);
    }
    $form['id_fornitore']=$form['clfoco'];
    if ($form['clfoco'] < $inifornitori || $form['clfoco'] > $finfornitori) {
       $msg .= "14+";
    }
    if ($form['id_agente'] <= 0) {
       $msg .= "15+";
    }
    $fornitore_exist = gaz_dbi_get_row($gTables['agenti'],'id_fornitore',$form['clfoco']);
    if (!empty($fornitore_exist) && $fornitore_exist['id_agente'] != $form['id_agente']) { // il fornitore � gi� un agente (ma non ha lo stesso id)
          $msg .= "16+";
    }
    if ($toDo == 'insert') {
       $agente_exist = gaz_dbi_get_row($gTables['agenti'],'id_agente',$form['id_agente']);
       if (!empty($agente_exist)) { // esiste un agente con lo stesso codice
          $msg .= "17+";
       }
    }
    if ($msg == "") {// nessun errore

      if ($toDo == 'update') { // e' una modifica
         $old_rows = gaz_dbi_dyn_query("*", $gTables['provvigioni'], "id_agente = ".$form['id_agente'],"id_provvigione asc");
         $i=0;
         $count = count($form['righi'])-1;
         while ($val_old_row = gaz_dbi_fetch_array($old_rows)) {
            if ($i <= $count) { //se il vecchio rigo e' ancora presente nel nuovo lo modifico
               $form['righi'][$i]['id_agente'] = $form['id_agente'];
               provvigioniUpdate(array('id_provvigione',$val_old_row['id_provvigione']),$form['righi'][$i]);
            } else { //altrimenti lo elimino
               gaz_dbi_del_row($gTables['provvigioni'], 'id_provvigione', $val_old_row['id_provvigione']);
            }
            $i++;
         }
         //qualora i nuovi righi fossero di pi� dei vecchi inserisco l'eccedenza
         for ($i = $i; $i <= $count; $i++) {
             $form['righi'][$i]['id_agente'] = $form['id_agente'];

             provvigioniInsert($form['righi'][$i]);
         }
         //modifico la testata con i nuovi dati...
         agentiUpdate(array('id_agente',$form['id_agente']),$form);
         header("Location: ".$form['ritorno']);
         exit;
      } else { // e' un'inserimento
         agentiInsert(array('id_agente',$form['id_agente']),$form);
         foreach ($form['righi'] as $i => $value) {
            $form['righi'][$i]['id_agente'] = $form['id_agente'];
            provvigioniInsert($form['righi'][$i]);
         }
         header("Location: ".$form['ritorno']);
         exit;
      }
    }
  }

  // Se viene inviata la richiesta elimina il rigo corrispondente
  if (isset($_POST['del'])) {
    $delri= key($_POST['del']);
    array_splice($form['righi'],$delri,1);
    $next_row--;
  }

} elseif ((!isset($_POST['Update'])) and (isset($_GET['Update']))) { //se e' il primo accesso per UPDATE
    $form['id_agente'] = intval($_GET['id_agente']);
    $agenti = gaz_dbi_get_row($gTables['agenti'],'id_agente',$form['id_agente']);
    $anagrafica = new Anagrafica();
    $fornitore = $anagrafica->getPartner($agenti['id_fornitore']);
    $rs_rig = gaz_dbi_dyn_query("*", $gTables['provvigioni'], "id_agente = ".$form['id_agente'],"id_provvigione ASC");
    // inizio rigo di input
    $form['in_cod_articolo'] = '';
    $form['in_cod_catmer'] = 0;
    $form['in_percentuale'] = 0;
    $form['in_status'] = "INSERT";
    $form['cosear']='';
    // fine rigo input
    $form['righi'] = array();
    // ...e della testata
    $form['id_agente'] = $agenti['id_agente'];
    $form['seach_clfoco'] = substr($fornitore['ragso1'],0,10);
    $form['search']['clfoco'] = '';
    $form['clfoco'] = $agenti['id_fornitore'];
    $form['adminid'] = $agenti['adminid'];
    $form['base_percent'] = $agenti['base_percent'];
    $form['id_agente_coord'] = $agenti['id_agente_coord'];
    $form['coord_percent'] = $agenti['coord_percent'];
    $form['hidden_req'] ='';
    $next_row = 0;
    while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
      $form['righi'][$next_row]['id_provvigione'] = $rigo['id_provvigione'];
      $form['righi'][$next_row]['cod_articolo'] = $rigo['cod_articolo'];
      $form['righi'][$next_row]['cod_catmer'] = $rigo['cod_catmer'];
      $form['righi'][$next_row]['percentuale'] = $rigo['percentuale'];
      $form['righi'][$next_row]['status'] = "UPDATE";
      $next_row++;
    }
} elseif (!isset($_POST['Insert'])) { //se e' il primo accesso per INSERT
    $form['righi'] = array();
    $next_row = 0;
    // inizio rigo di input
    $form['in_cod_articolo'] = 0;
    $form['in_cod_catmer'] = 0;
    $form['in_percentuale'] = 0;
    $form['in_status'] = "INSERT";
    // fine rigo input
    $form['cosear'] = '';
    $form['search']['clfoco'] = '';
    $rs_ultimo_agente = gaz_dbi_dyn_query("id_agente", $gTables['agenti'], 1,"id_agente DESC",0,1);
    $ultimo_agente = gaz_dbi_fetch_array($rs_ultimo_agente);
    $form['id_agente'] = $ultimo_agente?$ultimo_agente['id_agente']+1:1;
    $form['clfoco'] = '';
    $form['adminid'] = '';
    $form['base_percent'] = 0;
    $form['id_agente_coord'] = '';
    $form['coord_percent'] = '';
    $form['seach_clfoco'] = '';
    $form['hidden_req'] ='';
}

require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/autocomplete'));
?>
<script>
$(function(){
  $('select[name="id_agente_coord"]').bind('change', function () {
    var ida = parseInt($(this).val());
    if ( ida >= 1){
      $('#display_coord_percent').show();
    } else{
      $('#display_coord_percent').hide();
    }
  });
  $('select[name="id_agente_coord"]').trigger('change');
});
</script>
<?php
echo "<form method=\"POST\">\n";
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".ucfirst($script_transl[$toDo].$script_transl[1])."</div> ";
echo "<input type=\"hidden\" name=\"".ucfirst($toDo)."\" value=\"\">\n";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"".$form['ritorno']."\">\n";
echo '<input type="hidden" value="'.$form['hidden_req'].'" name="hidden_req" />';
echo "<table class=\"Tsmall\" align=\"center\">\n";
if (!empty($msg)) {
    echo "<tr><td colspan=\"2\" class=\"FacetDataTDred\">";
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
    echo $message."</td>\n";
}
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">$script_transl[2] : </td><td class=\"FacetDataTD\">\n";
if ($toDo == 'update') {
  echo "\t<input type=\"hidden\" name=\"id_agente\" value=\"".$form['id_agente']."\" /><div class=\"FacetDataTD\">".$form['id_agente']."<div>\n";
} else {
  echo "\t<input type=\"text\" name=\"id_agente\" value=\"".$form['id_agente']."\" maxlength=\"3\"  class=\"FacetInput\" />\n";
}
echo "</td></tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">$script_transl[3] : </td><td class=\"FacetDataTD\">\n";
$select_fornitore = new selectPartner('clfoco');
$select_fornitore->selectDocPartner('clfoco', $form['clfoco'], $form['search']['clfoco'], 'clfoco', $script_transl['search_partner'], $admin_aziend['masfor']);
echo "</td></tr>\n";
$sql = gaz_dbi_dyn_query ("*", $gTables['admin']." LEFT JOIN ".$gTables['anagra']." ON (".$gTables['admin'].".id_anagra = ".$gTables['anagra'].".id) ", $gTables['admin'].".Abilit < 8" );
$accopt='<option value="no_user"> non è un utente</option>';
$sel=false;
while ($row = $sql->fetch_assoc()){
	$selected = "";
	if ($row['user_name'] == $form['adminid']) {
		$selected = "selected";
		$sel=true;
	}
	$accopt .= '<option '.$selected.' value="'.$row['user_name'].'">' . $row['ragso1'] .' '.$row['ragso2']. '</option>';
}
echo '<tr><td class="text-right" >È anche l\'utente:</td><td><select name="adminid" onchange="this.form.submit()">';
echo $accopt;
echo '</select></td></tr>';
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">$script_transl[6] : </td><td class=\"FacetDataTD\">\n";
echo "<input type=\"text\" name=\"base_percent\" value=\"".$form['base_percent']."\" maxlength=\"5\"  class=\"FacetInput\">";
echo "</td></tr>\n";
?>
<tr>
  <td colspan=2 class="text-center bg-info"><b><?php echo $script_transl['agente_coord']; ?></b></td>
</tr>
<tr>
  <td colspan=2 class="text-center bg-info">
  <?php
  $select_agente = new selectAgente("id_agente_coord");
  $select_agente->addSelected($form["id_agente_coord"]);
  $select_agente->output();
  ?>
  </td>
</tr>
<tr id="display_coord_percent">
  <td class="FacetFieldCaptionTD"><?php echo $script_transl['coord_percent']; ?></td>
  <td class="FacetDataTD"><input type="text" name="coord_percent" value="<?php echo $form['coord_percent']; ?>" class="FacetInput"></td>
</tr>
<?php
echo "</table>\n";
echo "<div class=\"FacetFormHeaderFont\" align=\"center\">$script_transl[10] $script_transl[7] / $script_transl[8]</div>\n";
// inizio rigo inserimento
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">\n";
echo "<input type=\"hidden\" value=\"".$form['in_status']."\" name=\"in_status\" />\n";
echo "<tr><td class=\"FacetColumnTD\">$script_transl[7] :\n";
$select_catmer = new selectcatmer('in_cod_catmer');
$select_catmer -> addSelected($form['in_cod_catmer']);
$select_catmer -> output();
echo "</td><td class=\"FacetColumnTD\">$script_transl[8] :\n";
$select_artico = new selectartico('in_cod_articolo');
$select_artico -> addSelected($form['in_cod_articolo']);
$select_artico -> output($form['cosear'],'C');
echo "</td><td class=\"FacetColumnTD\">$script_transl[9] : <input type=\"text\" value=\"".$form['in_percentuale']."\" maxlength=\"5\"  name=\"in_percentuale\">\n";
echo '  </td>
		<td class="FacetColumnTD" align="right">
			<button type="submit" class="btn btn-default btn-sm" name="in_submit" title="'.$script_transl['submit'].$script_transl['thisrow'].'" tabindex="6"><i class="glyphicon glyphicon-ok"></i></button>
		</td>
	  </tr>';
echo "</td></tr>\n";

// fine rigo inserimento
echo "<tr><td colspan=\"5\"><hr></td></tr>\n";
// inizio righi già inseriti
foreach ($form['righi'] as $key => $value) {
        echo "<input type=\"hidden\" value=\"".$value['status']."\" name=\"righi[$key][status]\">\n";
        echo "<input type=\"hidden\" value=\"".$value['id_provvigione']."\" name=\"righi[$key][id_provvigione]\">\n";
        echo "<tr>\n";
        if  ($value['cod_catmer']>0){
            $catmer = gaz_dbi_get_row($gTables['catmer'],'codice',$value['cod_catmer']);
            echo '<td>Categoria:</td><td>
						<input type="hidden" value="'.$value['cod_catmer'].'" name="righi['.$key.'][cod_catmer]">
						<input type="hidden" value="'.$value['cod_articolo'].'" name="righi['.$key.'][cod_articolo]" />
						<button name="upd_row['.$key.']" class="btn btn-xs btn-success btn-block" type="submit">
							<i class="glyphicon glyphicon-refresh"></i>&nbsp;'.$catmer['descri'].'
						</button>
					  </td>
					  ';
        } else {
            $artico = gaz_dbi_get_row($gTables['artico'],'codice',$value['cod_articolo']);
            echo '<td>Articolo:</td>
            <td>
						<input type="hidden" value="'.$value['cod_articolo'].'" name="righi['.$key.'][cod_articolo]">
						<input type="hidden" value="" name="righi['.$key.'][cod_catmer]" />
						<button name="upd_row['.$key.']" class="btn btn-xs btn-success btn-block" type="submit">
							<i class="glyphicon glyphicon-refresh"></i>&nbsp;'.$value['cod_articolo']." - ".$artico["descri"].'
						</button>
					  </td>';   //FP: da formattare meglio
        }
        echo "<td><input type=\"text\" name=\"righi[$key][percentuale]\" value=\"".$value['percentuale']."\" maxlength=\"5\"  class=\"FacetInput\"></td>\n";
		echo '  <td align="right">
				  <button type="submit" class="btn btn-default btn-sm" name="del['.$key.']" title="'.$script_transl['delete'].$script_transl['thisrow'].'!"><i class="glyphicon glyphicon-trash"></i></button>
				</td>
			  </tr>';

}
// fine righi inseriti
if ($toDo == 'update') {
   echo '<td class="FacetFieldCaptionTD" colspan="5" align="right"><input type="submit" accesskey="m" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="Modifica"></td></tr>';
} else {
   echo '<td class="FacetFieldCaptionTD" colspan="5" align="right"><input type="submit" accesskey="i" name="ins" id="preventDuplicate" onClick="chkSubmit();" value="Inserisci"></td></tr>';
}
echo "</table>";
?>
</form>
<?php
require("../../library/include/footer.php");
?>

