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
function getDashFiles($mod)
{
	$fileArr=[];
	foreach(glob('../'.$mod, GLOB_ONLYDIR) as $dir) {
	    if ($handle = opendir($dir)) {
			while ($file = readdir($handle)) {
				if(($file == ".")||($file == "..")||($file == "dash_order_update.php")) continue;
				if(!preg_match("/^dash_[A-Za-z0-9 _ .-]+\.php$/",$file)) continue; //filtro i nomi contenenti il suffisso dash e estensione .php
				$fileArr[] = str_replace('../', '', $dir).'/'.$file; // push sull'accumulatore con una stringa adatta alla colonna del DB
			}
		}
	}
	return $fileArr;
}

require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
require("../../library/include/header.php");
$script_transl = HeadMain(0, array('custom/bootstrap-switch'));

// eseguo l'aggiornamento del db se richiesto
if(isset($_POST['addrow'])&&!empty($_POST['addrow'])){ // aggiungo il widget
	$titolo=filter_var($_POST['title-'.$_POST['addrow']], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$file=filter_var($_POST['addrow'],FILTER_SANITIZE_FULL_SPECIAL_CHARS).".php";
	// controllo se il widget è sul db
	$widget_exist=gaz_dbi_get_row($gTables['breadcrumb'], "adminid ='".$admin_aziend['user_name']."' AND (codice_aziend = 0 OR codice_aziend = ".$admin_aziend['codice'].") AND file", $file);

  if($widget_exist){ // il widget esiste faccio l'upload
		gaz_dbi_put_row($gTables['breadcrumb'],'id_bread',$widget_exist['id_bread'],'exec_mode', 2);
		if (!empty($titolo)){ // ho modificato il titolo
			gaz_dbi_put_row($gTables['breadcrumb'],'id_bread',$widget_exist['id_bread'],'titolo', $titolo);
		}
    if ($widget_exist['codice_aziend']==0){// se il codice azienda è ancora 0 lo imposto
      gaz_dbi_put_row($gTables['breadcrumb'],'id_bread',$widget_exist['id_bread'],'codice_aziend', $admin_aziend['codice']);
    }
	}else{ // non esiste lo devo inserire
		gaz_dbi_query("INSERT INTO " . $gTables['breadcrumb'] . "(position_order,exec_mode,file,titolo,adminid,codice_aziend)  SELECT COALESCE(MAX(position_order),0)+1,'2','".$file."','".$titolo."','" . $admin_aziend['user_name'] . "', ". $admin_aziend['codice'] ." FROM " . $gTables['breadcrumb']." WHERE adminid = '".$admin_aziend['user_name']."'");
	}
}elseif(isset($_POST['delrow'])&&!empty($_POST['delrow'])){ // elimino il widget dall'utente facendo l'upload del rigo
	gaz_dbi_query("UPDATE ".$gTables['breadcrumb']." SET exec_mode = 9 WHERE file = '".$_POST['delrow'].".php' AND adminid = '".$admin_aziend['user_name']."' AND (codice_aziend = 0 OR codice_aziend = ".$admin_aziend['codice'].")");
}
?>
<script>
$(function () {
  $(".yn_toggle").bootstrapSwitch({
    on: 'YES',
    off: 'NO',
    onClass: 'success'}, true);
  $(".yn_toggle").change(function () {
    var str = $(this).attr('name');
    if($(this).is(":checked")){
      $('#delrow').disabled = true;
      $('#addrow').disabled = false;
      $('#addrow').val(str);
          } else if($(this).is(":not(:checked)")){
      $('#addrow').disabled = true;
      $('#delrow').disabled = false;
      $('#delrow').val(str);
    }
    $('form#widform').submit();
  })
});
</script>
<form id="widform" method='post' class="form-horizontal">
  <div class="panel panel-default gaz-table-form">
    <div class="container-fluid">
      <input type="hidden" id="delrow" name="delrow" />
      <input type="hidden" id="addrow" name="addrow" />
<?php
$active_modules=gaz_dbi_dyn_query("name",$gTables['admin_module']." adm LEFT JOIN ".$gTables['module']." mdl ON adm.moduleid=mdl.id", "company_id=".$admin_aziend['company_id']." AND adminid ='".$admin_aziend['user_name']."' AND adm.access >= 3");
$ctrl_name='';
while($mods=gaz_dbi_fetch_assoc($active_modules)) {
  foreach(getDashFiles($mods['name']) as $w){
    $v=substr($w,0,-4);
    // controllo se sulla tabella del database ho il relativo rigo ed è attivato (exec_mode=2)
    $widget_exist=gaz_dbi_get_row($gTables['breadcrumb'], "exec_mode=2 AND adminid ='".$admin_aziend['user_name']."' AND (codice_aziend = 0 OR codice_aziend = ".$admin_aziend['codice'].") AND file", $w);
    $cked='';
    if($widget_exist){
      $cked='checked';
    }else{
      $widget_exist['titolo']='';
    }
    if ($ctrl_name!=$mods['name']){
      require("../" . $mods['name'] . "/menu.".$admin_aziend['lang'].".php");
      echo '<div class="row text-center"> <h3><img src="../'.$mods['name'].'/'.$mods['name'].'.png" height=42 >'.$transl[$mods['name']]['name'].'</h3></div>';
    }
    echo '<div class="row">
        <div class="col-xs-7" title="'.$v.'"><img src="../'.$v.'.png" style="height:116px; width: auto; max-width: 100%;">
        </div>
        <div class="col-xs-3">
        <input type="text"  name="title-'.$v.'" value="'.$widget_exist['titolo'].'"/>
        </div>
        <div class="col-xs-2">
        <input type="checkbox" '.$cked.' class="yn_toggle" name="'.$v.'" data-on-text="YES" data-off-text="NO" />
        </div>
       </div><hr/>';
    $ctrl_name=$mods['name'];
  }
}
?>
    </div><!-- chiude container-fluid  -->
  </div><!-- chiude panel  -->
</form>
<?php
require("../../library/include/footer.php");
?>
