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
$admin_aziend = checkAdmin(9);
$msg = array('err' => array(), 'war' => array());

if (isset($_GET['id_cash'])||isset($_POST['id_cash'])) {
    $toDo = 'update';
} else {
    $toDo = 'insert';
}

// le variabili sottostanti mi serviranno per rendere attivo il tab desiderato, in caso di un post derivante da un aggiunta righi oppure qualora si debba riferire ad un errore
$homeactive='';$repartiactive='';$tenderactive='';$utentiactive='';
if (isset($_POST['insreparto'])) {
    $repartiactive='active';
} elseif (isset($_POST['instender'])) {
    $tenderactive='active';
} elseif (isset($_POST['insutente'])) {
    $utentiactive='active';
} else {
  $homeactive='active';
}

if (isset($_POST['id_cash'])) {   //se non e' il primo accesso
  if ($_POST['id_cash']>0) {
      $toDo = 'update';
  } else {
      $toDo = 'insert';
  }
  $form = gaz_dbi_parse_post('cash_register');
  $form['reparti'] = array();
  $nreparto = 0;
  if (isset($_POST['reparti'])) {
    foreach ($_POST['reparti'] as $nreparto => $val) {
      $form['reparti'][$nreparto]['aliiva_codice'] = intval($val['aliiva_codice']);
      $form['reparti'][$nreparto]['reparto'] = substr($val['reparto'], 0, 8);
      $form['reparti'][$nreparto]['descrizione'] = substr($val['descrizione'], 0, 50);
      $nreparto++;
    }
  }
  if (isset($_POST['insreparto'])) {
      $form['reparti'][$nreparto]['aliiva_codice'] = 0;
      $form['reparti'][$nreparto]['reparto'] = '';
      $form['reparti'][$nreparto]['descrizione'] ='';
      $nreparto++;
  }
  $ntender = 0;
  if (isset($_POST['tenders'])) {
    foreach ($_POST['tenders'] as $ntender => $val) {
      $form['tenders'][$ntender]['pagame_codice'] = intval($val['pagame_codice']);
      $form['tenders'][$ntender]['tender'] = substr($val['tender'], 0, 8);
      $form['tenders'][$ntender]['descrizione'] = substr($val['descrizione'], 0, 50);
      $ntender++;
    }
  }
  if (isset($_POST['instender'])) {
      $form['tenders'][$ntender]['pagame_codice'] = 0;
      $form['tenders'][$ntender]['tender'] = '';
      $form['tenders'][$ntender]['descrizione'] ='';
      $ntender++;
  }
  // utenti abilitati
  $accutenti=[];
  foreach ($_POST['utenti'] as $k => $v) {
    $form['utenti'][$k]['nickname'] = $k;
    $form['utenti'][$k]['nome'] = substr($v['nome'], 0, 30);
    $form['utenti'][$k]['cognome'] = substr($v['cognome'], 0, 30);
    if (isset($v['abilit']) && $v['abilit'] == 'uon'){
        $form['utenti'][$k]['abilit'] = substr($v['abilit'], 0, 3);
        $accutenti[]=$k;
    } else {
        $form['utenti'][$k]['abilit'] = '';
    }
  }

  // Se viene inviata la richiesta eliminare un reparto
  if (isset($_POST['repartidel'])) {
      $delri = key($_POST['repartidel']);
      array_splice($form['reparti'], $delri, 1);
      $homeactive='';$repartiactive='active';$tenderactive='';$utentiactive='';
  }

  // Se viene inviata la richiesta eliminare un tender
  if (isset($_POST['tendersdel'])) {
      $delri = key($_POST['tendersdel']);
      array_splice($form['tenders'], $delri, 1);
      $homeactive='';$repartiactive='';$tenderactive='active';$utentiactive='';
  }

  if (isset($_POST['Submit'])) { // conferma tutto
    if ($toDo == 'update') {  // controlli in caso di modifica
    } else { // controlli inserimento
    }
    if (strlen(trim($form['descri']))< 3 ){
        $msg['err'][] = 'descri';
        $homeactive='active';
    }
    if (strlen(trim($form['driver']))< 5 ){
        $msg['err'][] = 'driver';
        $homeactive='active';
    }
    // controlli su reparti
    foreach ($form['reparti'] as $v) {
        if ($v['aliiva_codice'] < 1) {
            $msg['err'][] = 'repiva';
            $repartiactive='active';
        }
        if (strlen(trim($v['reparto']))< 1 ){
            $msg['err'][] = 'reprep';
            $repartiactive='active';
        }
        if (strlen(trim($v['descrizione']))< 3 ){
            $msg['err'][] = 'repdes';
            $repartiactive='active';
        }
    }
    // controlli su tender
    foreach ($form['tenders'] as $v) {
        if ($v['pagame_codice'] < 1) {
            $msg['err'][] = 'tenpag';
            $tenderactive='active';
        }
        if (strlen(trim($v['tender']))< 1 ){
            $msg['err'][] = 'tenten';
            $tenderactive='active';
        }
        if (strlen(trim($v['descrizione']))< 3 ){
            $msg['err'][] = 'tendes';
            $tenderactive='active';
        }
    }

    if (count($msg['err']) == 0) { // nessun errore
        $form['enabled_users']=json_encode($accutenti);
        if ($toDo == 'update') { // update
            gaz_dbi_table_update('cash_register', array('id_cash',$form['id_cash']), $form);
            // prima cancello le vecchie associazioni e poi le reinserisco da capo
            gaz_dbi_del_row($gTables['cash_register_reparto'], 'cash_register_id_cash', $form['id_cash']);
            foreach ($form['reparti'] as $k => $v) {
                $v['cash_register_id_cash']=$form['id_cash'];
                gaz_dbi_table_insert('cash_register_reparto', $v);
            }
            gaz_dbi_del_row($gTables['cash_register_tender'], 'cash_register_id_cash', $form['id_cash']);
            foreach ($form['tenders'] as $k => $v) {
                $v['cash_register_id_cash']=$form['id_cash'];
                gaz_dbi_table_insert('cash_register_tender', $v);
            }
        } else { // insert
            unset ($form['id_cash']);
            $newid = gaz_dbi_table_insert('cash_register', $form);
            foreach ($form['reparti'] as $k => $v) {
                $v['cash_register_id_cash']=$newid;
                gaz_dbi_table_insert('cash_register_reparto', $v);
            }
            foreach ($form['tenders'] as $k => $v) {
                $v['cash_register_id_cash']=$newid;
                gaz_dbi_table_insert('cash_register_tender', $v);
            }
        }
        header("Location: ../../modules/vendit/report_ecr.php");
        exit;
    }
  }
} elseif (!isset($_POST['id_cash']) && isset($_GET['id_cash'])) { //se e' il primo accesso per UPDATE
    $toDo = 'update';
    $form = gaz_dbi_get_row($gTables['cash_register'], 'id_cash', intval($_GET['id_cash']));

    // prendo tutti gli utenti dell'azienda
    $accutenti=json_decode($form['enabled_users']);
    $rsusr = gaz_dbi_dyn_query($gTables['admin_module'].".adminid AS nickname, ".$gTables['admin'].".user_firstname AS nome, ".$gTables['admin'].".user_lastname AS cognome", $gTables['admin_module'].' LEFT JOIN '.$gTables['admin']." ON ".$gTables['admin_module'].".adminid = ".$gTables['admin'].".user_name ", "moduleid = 2 AND ".$gTables['admin_module'].".company_id = " . $admin_aziend['company_id'], "adminid");
    while ($usr = gaz_dbi_fetch_array($rsusr)) {
        $form['utenti'][$usr['nickname']]=$usr;
        if (in_array($usr['nickname'],$accutenti)){
            $form['utenti'][$usr['nickname']]['abilit'] = 'uon';
        } else {
            $form['utenti'][$usr['nickname']]['abilit'] = '';
        }
    }

    // inizio reparti
    $nreparto = 0;
    $rs_row = gaz_dbi_dyn_query("*", $gTables['cash_register_reparto'], "cash_register_id_cash = " . $form['id_cash'], "reparto ASC");
    while ($row = gaz_dbi_fetch_array($rs_row)) {
        $form['reparti'][$nreparto] = $row;
        $nreparto++;
    }
    // fine reparti

	// inizio tender
    $ntender = 0;
    $rs_row = gaz_dbi_dyn_query("*", $gTables['cash_register_tender'], "cash_register_id_cash = " . $form['id_cash'] , "tender ASC");
    while ($row = gaz_dbi_fetch_array($rs_row)) {
        $form['tenders'][$ntender] = $row;
        $ntender++;
    }
    // fine tender

} else { //se e' il primo accesso per INSERT
    $toDo = 'insert';
    $form = gaz_dbi_fields('cash_register');
    $form['reparti']=[];
    $form['tenders']=[];
    // prendo tutti gli utenti dell'azienda
    $rsusr = gaz_dbi_dyn_query($gTables['admin_module'].".adminid AS nickname, ".$gTables['admin'].".user_firstname AS nome, ".$gTables['admin'].".user_lastname AS cognome", $gTables['admin_module'].' LEFT JOIN '.$gTables['admin']." ON ".$gTables['admin_module'].".adminid = ".$gTables['admin'].".user_name ", "moduleid = 2 AND ".$gTables['admin_module'].".company_id = " . $admin_aziend['company_id'], "adminid");
    while ($usr = gaz_dbi_fetch_array($rsusr)) {
        $form['utenti'][$usr['nickname']]=$usr;
    }
}

require("../../library/include/header.php");
$script_transl = HeadMain();
$gForm = new venditForm();
// ultimo utente
$last_urs = gaz_dbi_get_row ($gTables['admin'], 'user_name', $form['adminid'] );
$lu=$last_urs?$last_urs['user_name']:'Mai utilizzato';
?>
<form method="POST" name="formecr">
<?php
    echo '<input type="hidden" name="' . ucfirst($toDo) . '" value="" />';
    if (count($msg['err']) > 0) { // ho un errore
        $gForm->gazHeadMessage($msg['err'], $script_transl['err'], 'err');
    }
    if ($toDo == 'insert') {
        echo '<div class="text-center"><h3>' . $script_transl['ins_this'] . '</h3></div>';
    } else {
        echo '<div class="text-center"><h3>' . $script_transl['upd_this'] . ' ' . $form['id_cash'] . '</h3></div>';
    }
    ?>
        <div class="panel panel-default div-bordered">
            <div class="container-fluid">
            <ul class="nav nav-pills">
                <li class="<?php echo $homeactive; ?>"><a data-toggle="pill" href="#home">Dati principali</a></li>
                <li class="<?php echo $repartiactive; ?>"><a data-toggle="pill" href="#reparti">Reparti IVA</a></li>
                <li class="<?php echo $tenderactive; ?>"><a data-toggle="pill" href="#tenders">Tender</a></li>
                <li class="<?php echo $utentiactive; ?>"><a data-toggle="pill" href="#utenti">Utenti</a></li>
                <li style="float: right;"><?php echo '<input name="Submit" type="submit" class="btn btn-warning" value="' . ucfirst($script_transl[$toDo]) . '" />'; ?></li>
            </ul>


            <div class="tab-content">
              <div id="home" class="tab-pane fade in <?php echo $homeactive; ?>">
                 <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="id_cash" class="col-xs-4 control-label"><?php echo $script_transl['id_cash']; ?></label>
                            <?php
                            if ($form["id_cash"]<=0) { ?>
                                <div class="col-xs-8" > nuovo</div>
                            <?php
                            } else { ?>
                                <div class="col-xs-8" ><?php echo $form["id_cash"]; ?></div>
                            <?php
                            }
                            ?>
                            <input type="hidden" value="<?php echo $form["id_cash"]; ?>" name="id_cash" />
                            </div>
                    </div>
                </div><!-- chiude row  -->
                 <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="descri" class="col-xs-4 control-label"><?php echo $script_transl['descri']; ?></label>
                            <input class="col-xs-8" type="text" value="<?php echo $form["descri"]; ?>" name="descri" maxlength="32" minlength="1" placeholder=" compreso tra 3 e 32 caratteri"/>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                 <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="serial_port" class="col-xs-4 control-label"><?php echo $script_transl['serial_port']; ?></label>
                            <input class="col-xs-8" type="serial_port" value="<?php echo $form["serial_port"]; ?>" name="serial_port" maxlength="32"/>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                 <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="seziva" class="col-xs-4 control-label"><?php echo $script_transl['seziva']; ?></label>
                            <?php $gForm->selectNumber('seziva', $form['seziva'], 0, 1, 9, 'col-sm-1'); ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                 <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                        <label for="driver" class="col-xs-4 control-label"><?php echo $script_transl['driver']; ?></label>
                        <?php
                        echo '<select name="driver">';
                        $relativePath = '../../library/cash_register';
                        if ($handle = opendir($relativePath)) {
                            echo '<option value=""></option>';
                            while ($file = readdir($handle)) {
                                if (substr($file, 0, 1) == ".") continue;
                                $selected = "";
                                if ($form["driver"] == $file) {
                                    $selected = " selected ";
                                }
                                echo "<option value=\"" . $file . "\"" . $selected . ">" . $file . "</option>";
                            }
                            closedir($handle);
                        }
                        echo "</select>\n";
                        ?>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                 <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="path_data" class="col-xs-4 control-label"><?php echo $script_transl['path_data']; ?></label>
                            <input class="col-xs-8" type="text" value="<?php echo $form["path_data"]; ?>" name="path_data" maxlength="1000"/>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                 <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="lotteria_scontrini" class="col-xs-4 control-label"><?php echo $script_transl['lotteria_scontrini']; ?></label>
                            <input class="col-xs-8" type="lotteria_scontrini" value="<?php echo $form["lotteria_scontrini"]; ?>" name="lotteria_scontrini" maxlength="32"/>
                        </div>
                    </div>
                </div><!-- chiude row  -->
                 <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="adminid" class="col-xs-4 control-label"><?php echo $script_transl['adminid']; ?></label>
                            <div class="col-xs-8"><?php echo $lu; ?></div>
                        </div>
                    </div>
                </div><!-- chiude row  -->
              </div><!-- chiude tab-pane  -->

              <div id="reparti" class="tab-pane fade in <?php echo $repartiactive; ?>">

<?php
if (count($form['reparti']) > 0) {
?>
<div class="table-responsive">
<table class="Tlarge table table-bordered table-condensed table-striped">
<thead>
    <tr>
		<th class="FacetFieldCaptionTD"></th>
		<th class="FacetFieldCaptionTD">Aliquota IVA</th>
		<th class="FacetFieldCaptionTD">Reparto</th>
		<th class="FacetFieldCaptionTD">Descrizione</th>
		<th class="FacetFieldCaptionTD">Elimina</th>
	</tr>
</thead>
<tbody>
<?php
  foreach ($form['reparti'] as $k => $v) {
?>
<tr><td>
    <input type="hidden" name="<?php echo 'reparti[' . $k . '][id_cash_register_reparto]'; ?>" value="<?php echo $v['id_cash_register_reparto']; ?>"/><?php echo $k+1; ?>
    </td>
    <td>
    <?php
    $gForm->selectFromDB('aliiva', 'reparti[' . $k . '][aliiva_codice]', 'codice',  $v['aliiva_codice'], 'codice', true, ' - ', 'descri', '', 'col-sm-8', null, 'style="max-width: 350px;"');
    ?>
    </td>
    <td>
	<input class="col-xs-12" type="text" name="<?php echo 'reparti[' . $k . '][reparto]'; ?>" value="<?php echo $v['reparto']; ?>"  maxlength="8" minlength="1" placeholder=" compreso tra 1 e 8 caratteri" />
    </td>
    <td>
	<input class="col-xs-12" type="text" name="<?php echo 'reparti[' . $k . '][descrizione]'; ?>" value="<?php echo $v['descrizione']; ?>"  maxlength="50" minlength="3" placeholder="compreso tra 1 e 50 caratteri" />
    </td>
	<td class="text-center"><button type="submit" class="btn btn-danger btn-sm" name="<?php echo 'repartidel[' . $k . ']'; ?>" title="<?php echo $script_transl['delete'] . $script_transl['thisrow']; ?>"><i class="glyphicon glyphicon-trash"> </i> </button></td>
</tr>
<?php
  }
?>
</tbody>
</table>
</div>
<?php
}
?>
<div class="col-sm-1"></div><div class="col-sm-11"><input class="btn btn-success" type="submit" name="insreparto" value="Associa un nuovo reparto" /></div>
              </div><!-- chiude tab-pane  -->

              <div id="tenders" class="tab-pane fade in <?php echo $tenderactive; ?>">
<?php
if (count($form['tenders']) > 0) {
?>
<div class="table-responsive">
<table class="Tlarge table table-bordered table-condensed table-striped">
<thead>
    <tr>
		<th class="FacetFieldCaptionTD"></th>
		<th class="FacetFieldCaptionTD">Pagamento</th>
		<th class="FacetFieldCaptionTD">Tender</th>
		<th class="FacetFieldCaptionTD">Descrizione</th>
		<th class="FacetFieldCaptionTD">Elimina</th>
	</tr>
</thead>
<tbody>
<?php
  foreach ($form['tenders'] as $k => $v) {
?>
<tr><td>
    <input type="hidden" name="<?php echo 'tenders[' . $k . '][id_cash_register_tender]'; ?>" value="<?php echo $v['id_cash_register_tender']; ?>"/><?php echo $k+1; ?>
    </td>
    <td>
    <?php
    $gForm->selectFromDB('pagame', 'tenders[' . $k . '][pagame_codice]', 'codice',  $v['pagame_codice'], 'codice', true, ' - ', 'descri', '', 'col-sm-8', null, 'style="max-width: 350px;"');
    ?>
    </td>
    <td>
	<input class="col-xs-12" type="text" name="<?php echo 'tenders[' . $k . '][tender]'; ?>" value="<?php echo $v['tender']; ?>"  maxlength="8" minlength="1" placeholder=" compreso tra 1 e 8 caratteri" />
    </td>
    <td>
	<input class="col-xs-12" type="text" name="<?php echo 'tenders[' . $k . '][descrizione]'; ?>" value="<?php echo $v['descrizione']; ?>"  maxlength="50" minlength="3" placeholder="compreso tra 1 e 50 caratteri" />
    </td>
	<td class="text-center"><button type="submit" class="btn btn-danger btn-sm" name="<?php echo 'tendersdel[' . $k . ']'; ?>" title="<?php echo $script_transl['delete'] . $script_transl['thisrow']; ?>"><i class="glyphicon glyphicon-trash"> </i> </button></td>
</tr>
<?php
  }
?>
</tbody>
</table>
</div>
<?php
}

?>
<div class="col-sm-1"></div><div class="col-sm-11"><input class="btn btn-success" type="submit" name="instender" value="Associa un nuovo tender" /></div>

              </div><!-- chiude tab-pane  -->
              <div id="utenti" class="tab-pane fade in <?php echo $utentiactive; ?>">
<div class="h3">Abilitazione utenti</div>
<?php
foreach ($form['utenti'] as $k => $v) {
    if (isset($v['abilit']) && $v['abilit']=='uon'){
     $uon='checked';
     $uoff='';
    } else {
     $uon='';
     $uoff='checked';
    }
?>
<input type="hidden" name="<?php echo 'utenti[' . $v['nickname'] . '][nome]'; ?>" value="<?php echo $v['nome']; ?>"  >
<input type="hidden" name="<?php echo 'utenti[' . $v['nickname'] . '][cognome]'; ?>" value="<?php echo $v['cognome']; ?>"  >
                <div class="row">
                    <div class="col-sm-4" >
                            <label class="control-label"><?php echo $v['cognome'].' '.$v['nome'].' ('.$v['nickname'].')'; ?></label>
                    </div>
                    <div class="col-sm-8 radio" >
                            <label class="radio-inline"><input type="radio" name="<?php echo 'utenti[' . $v['nickname'] . '][abilit]'; ?>" value="uon" <?php echo $uon; ?> >Abilitato</label>
                            <label class="radio-inline"><input type="radio" name="<?php echo 'utenti[' . $v['nickname'] . '][abilit]'; ?>" value=""   <?php echo $uoff; ?>>Non Abilitato</label>
                    </div>
                </div><!-- chiude row  -->
<?php
}
?>
            </div><!-- chiude tab-pane  -->
        </div> <!-- chiude tab-content -->
     </div> <!-- chiude container -->
    </div><!-- chiude panel -->
</form>
<?php
require("../../library/include/footer.php");
?>
