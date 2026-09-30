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
$admin_aziend = checkAdmin();
if (!isset($_SESSION['id_patient'])) {
  header("Location: select_patient.php");
  exit;
} else {
  require_once("./lib.data.php");
  $patient=DecryptPersonalData($gTables['encrypted_personal_data'],'id_patient_bidx',intval($_SESSION['id_patient']))[0];
  preg_match_all('/(?<=\b)\w/iu',$patient['last_name'],$matches);
  $patient_redname=$patient['first_name'].' '.implode('.',$matches[0]).'.';
}
?>
<style type="text/css">

/*==============  image flip horizontal ====================*/

 /* The flip card container - set the width and height to whatever you want. We have added the border property to demonstrate that the flip itself goes out of the box on hover (remove perspective if you don't want the 3D effect */
.flip-image {
  margin: auto;
  width: 100%;
  height: 300px;
  // border: 1px solid #f1f1f1;
  // perspective: 1000px; /* Remove this if you don't want the 3D effect */
}

/* This container is needed to position the front and back side */
.flip-image-inner {
  position: relative;
  width: 100%;
  height: 100%;
  text-align: center;
  transition: transform 0.8s;
  transform-style: preserve-3d;
}

/* Do an horizontal flip when you move the mouse over the flip box container */
.flip-image:hover .flip-image-inner {
  transform: rotateY(180deg);
}

/* Position the front and back side */
.flip-image-front, .flip-image-back {
  position: absolute;
  width: 100%;
  height: 100%;
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
}

/* Style the front side (fallback if image is missing) */
.flip-image-front {
 // background-color: #fff;
  color: black;
}

/* Style the back side */
.flip-image-back {
	background-color: #eee;
	color: white;
	transform: rotateY(180deg);
}
.flip-image-back>a {
  color: black;
}
div .text-left.col-sm-5.menu-buttons div{
 padding: 22px;
}
div.menu-buttons div a {
  text-align: left;
}
a.btn.btn-md.btn-info.col-xs-12.text-bold {
  border-radius: 20px;
	background-color: #517993;
  border: 2px solid #517993;
}
a.btn.btn-info:hover {
	color: #f7f7bb;
}
</style>


<?php
require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<script>
$(function() {
  $( "#dialog" ).dialog({
    autoOpen: false
  });
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#iddescri").html($(this).attr("desdoc"));
		var id = $(this).attr('idtesref');
		$( "#dialog_delete" ).dialog({
			minHeight: 1,
			width: "auto",
			modal: "true",
			show: "blind",
			hide: "explode",
			buttons: {
   			close: {
					text:'Non eliminare',
					'class':'btn btn-default',
          click:function() {
            $(this).dialog("close");
          }
        },
				delete:{
					text:'Elimina',
					'class':'btn btn-danger',
					click:function (event, ui) {
					$.ajax({
						data: {'type':'admidimi',ref:id},
						type: 'POST',
						url: './delete.php',
						success: function(output){
		          //alert(id);
							window.location.replace("./patient_dashboard.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
</script>
<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
  <p id="iddescri"></p>
</div>

<div class="panel panel-info gaz-table-form text-center">
  <div class="container-fluid">
    <div class="col-sm-12 text-center bg-info text-bold"><h3>Paziente: <?php echo $patient_redname.' <small>('.$patient['patient_number'].')</small>' ?></h3></div>
    <div class="col-sm-1"><a class="btn btn-md btn-info" href="../hospital/admin_patient.php" title="Modifica anagrafica"><i class="fa fa-pencil-square-o"></i></a></div>
    <div class="flip-image col-sm-6">
      <div class="flip-image-inner">
        <div class="flip-image-front"><a href="admin_patient.php">
        <img class="img-circle dit-picture" src="tessera_sanitaria.php" alt="Logo" style="max-height: 300px; max-width: 100%;" border="0" ></a>
        </div>
        <div class="flip-image-back">
          <a href="admin_patient.php">
          <div style="cursor:pointer;">
          <img class="img-circle dit-picture" src="tessera_sanitaria.php?back" alt="Logo" style="max-height: 300px; max-width: 100%;" border="0" title="Modifica anagrafica" ></a>
          </div>
          </a>
        </div>
      </div>
    </div>
    <div class="text-left col-sm-5 menu-buttons">
      <div><a class="btn btn-md btn-info col-xs-12 text-bold" href="./dashboard_sez1.php"><i class="fa fa-circle"></i> Sezione 1 <span class="small"> (amministrativa)</span></a></div>
      <div><a class="btn btn-md btn-info col-xs-12 text-bold" href="./dashboard_sez2.php"><i class="fa fa-circle"></i> Sezione 2 <span class="small"> (diagnostica)</span></a></div>
      <div><a class="btn btn-md btn-info col-xs-12 text-bold" href="./dashboard_sez3.php"><i class="fa fa-circle"></i> Sezione 3 <span class="small"> (farmacologica)</span></a></div>
      <div><a class="btn btn-md btn-info col-xs-12 text-bold" href="./dashboard_sez4.php"><i class="fa fa-circle"></i> Sezione 4 <span class="small"> (chirurgica)</span></a></div>
      <div><a class="btn btn-md btn-info col-xs-12 text-bold" href="./dashboard_sez5.php"><i class="fa fa-circle"></i> Sezione 5 <span class="small"> (riabilitativa)</span></a></div>
      <div><a class="btn btn-md btn-info col-xs-12 text-bold" href="./dashboard_sez6.php"><i class="fa fa-circle"></i> Sezione 6 <span class="small"> (follow-up)</span></a></div>
    </div>
  </div>
</div>
<div class="panel panel-success gaz-table-form">
  <div class="container-fluid">
<?php
// riprendo la situazione attuale del paziente: ultima ammissione e/o dimissione ( tipdoc HAD o HDI )
  $digpass=rand(pow(10,3), pow(10,4)-1);
  $hadi=false;
  $rs_hadi = gaz_dbi_dyn_query("*",$gTables['tesbro'], "id_con = ".$_SESSION['id_patient']." AND (tipdoc='HAD' OR tipdoc='HDI')","datemi ASC, tipdoc ASC");
  while ($rhadi = gaz_dbi_fetch_array($rs_hadi)) {
    if ($hadi==false){
      echo '<div class="col-xs-12 text-center bg-info text-info"><b><div class="col-xs-6 text-center">AMMISSIONI</div><div class="col-xs-6 text-center">DIMISSIONI</div></b></div><table class="col-xs-12">';
    }
    if (isset($td) && $td==$rhadi['tipdoc']) {
      echo '<tr><td colspan=2 class="text-center bg-danger text-danger"><b>!!!  ERRORE SU DATE !!!</b></td></tr>';
    }
    if ($rhadi['tipdoc']=='HAD') { // AMMISSIONI
      // controllo se è eliminabile, ovvero nessun altro id_orderman della tabella tesbro è riferita a questa ammissione
      $raddel = gaz_dbi_get_row($gTables['tesbro'],'id_orderman',$rhadi['id_tes']," AND tipdoc LIKE 'H%' AND id_tes <> ".$rhadi['id_tes']);
      echo '<tr><td><a href=admin_admission.php?id_tes='.$rhadi['id_tes'].' class="btn btn-sm btn-info" title="Modifica l\'ammissione"> <i class="fa fa-file-text-o"></i> </a> &nbsp; Ammissione del <b>'.gaz_format_date($rhadi['datemi']).' </b><a class="btn btn-sm btn-default" href=print_admission.php?id='.$rhadi['id_tes'].'&dp='.$digpass.' target="_blank"><i class="fa fa-file-pdf-o"> '.$digpass.'</i></a> &nbsp;';
      if (!$raddel){
        echo '<a class="btn btn-sm btn-elimina dialog_delete" idtesref="'.$rhadi['id_tes'].'" desdoc="Ammissione del '.gaz_format_date($rhadi['datemi']).'" ><i class="glyphicon glyphicon-trash"> </i></a>';
      }
      echo ' &nbsp; <a class="btn btn-sm btn-default upload-signed" idtesref="'.$rhadi['id_tes'].'" title="Carica l\'Ammissione firmata"> <i class="fa fa-pencil-square-o"> </i>  </a></td><td> &nbsp; </td></tr>';
    } else { // DIMISSIONI
      // controllo se è eliminabile, una dimissione non è eliminabile se c'è una ammissione successiva per questo paziente (id_con)
      $rdidel = gaz_dbi_get_row($gTables['tesbro'],'id_con',$rhadi['id_con']," AND tipdoc LIKE 'HAD' AND datemi > '".$rhadi['datemi']."'");
      echo '<tr><td> &nbsp; </td><td class="text-center"><a href=admin_admission.php?id_tes='.$rhadi['id_tes'].' class="btn btn-sm btn-info"> <i class="fa fa-pencil-square-o"></i>  </a> &nbsp; Dimissione del <b>'.gaz_format_date($rhadi['datemi']).'</b> <a class="btn btn-sm btn-default" href="print_admission.php?id='.$rhadi['id_tes'].'&dp='.$digpass.'" target="_blank"><i class="fa fa-file-pdf-o"> '.$digpass.'</i>  </a>';
      if (!$rdidel) {
        echo '&nbsp; <a class="btn btn-sm btn-elimina dialog_delete" idtesref="'.$rhadi['id_tes'].'" desdoc="Dimissione del '.gaz_format_date($rhadi['datemi']).'"> <i class="glyphicon glyphicon-trash"> </i> </a>';
      }
      echo '&nbsp; <a class="btn btn-sm btn-default upload-signed" idtesref="'.$rhadi['id_tes'].'" title="Carica la Dimissione firmata"> <i class="fa fa-pencil-square-o"> </i>  </a> </td></tr>';
    }
    $hadi=true;
    $td=$rhadi['tipdoc'];
  }
  echo '</table>';
  if (!$hadi) {
    echo '<div class="col-xs-12 bg-danger text-danger"><b>La persona non risulta essere stata ospitata &raquo; <a class="btn btn-info" href="admin_admission.php"> Prima ammissione </a></b></div>';
  } elseif ($td =='HDI') { // l'ultimo documento è una dimissione
    echo '<div class="col-xs-12 bg-warning text-warning"><b>La persona risulta essere stata dimessa &raquo; <a class="btn btn-sm btn-info" href="admin_admission.php"> Nuova ammissione </a></b></div>';
  } else { // l'ultimo documento è una ammissione, consento la dimissione
    echo '<div class="col-xs-12 bg-warning text-warning text-center"><b>La persona risulta essere ospitata &raquo; <a class="btn btn-sm btn-info" href="admin_dimission.php"> Dimissione </a></b></div>';
  }
?>
  </div>
</div>
</div>
<?php
require("../../library/include/footer.php");
?>
