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

$wards_table = "{$gTables['ward']}";

// campi ammissibili per la ricerca
$search_fields = [
    'id_ward' => "id_ward = %d",
    'wardname'=> "bename LIKE '%%%s%%'"
  ];

require("../../library/include/header.php");
$script_transl = HeadMain(0,'custom/autocomplete');

// creo l'array (header => campi) per l'ordinamento dei record in base al tipo di listato
$sortable_headers = [
  "" => "id_ward",
  "Nome/numero"=>"wardname",
  "Stanze/letti"=>"",
  $script_transl['delete'] => ""
];

$ts = new TableSorter(
  $wards_table,
  $passo,
  ['id_ward' => 'desc']
);
?>
<script>
$(function() {
  $( "#dialog" ).dialog({
    autoOpen: false
  });
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("id_ward"));
		$("p#iddescri").html($(this).attr("nome"));
		var id = $(this).attr('id_ward');
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
						data: {'type':'ward',ref:id},
						type: 'POST',
						url: './delete.php',
						success: function(output){
		          //alert(output);
							window.location.replace("./report_wards.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});


function printPdf(urlPrintDoc){
	$(function(){
    //alert(urlPrintDoc);
		$('#framePdf').attr('src',urlPrintDoc);
		$('#framePdf').css({'height': '100%'});
		$('.framePdf').css({'display': 'block','width': '90%', 'height': '80%', 'z-index':'2000'});
		$('#closePdf').on( "click", function() {
			$('.framePdf').css({'display': 'none'}); $('#framePdf').attr('src','../../library/images/wait_spinner.html');
		});
	});
};
</script>
<div class="text-center row">
  <div class="col-xs-12 col-md-3" style="padding-top: 25px;">
  <a class="btn btn-default" href="./admin_ward.php"> Nuovo reparto </a>
  </div>
  <div class="col-xs-12 col-md-6 text-center"><h1><?php echo $script_transl['title']; ?></h1></div>
  <div class="col-xs-12 col-md-3">
  </div>
</div>
<?php
$ts->output_navbar();
$gForm = new hospitalForm();
$con=false;
?>
<form method="GET" >
	<div class="framePdf panel panel-success" style="display: none; position: fixed; left: 5%; top: 10px">
		<div class="col-lg-12">
			<div class="col-xs-11"><h4><?php echo $script_transl['print'];; ?></h4></div>
			<div class="col-xs-1"><h4><button type="button" id="closePdf"><i class="glyphicon glyphicon-remove"></i></button></h4></div>
		</div>
		<iframe id="framePdf"  style="height: 100%; width: 100%" src="../../library/images/wait_spinner.html"></iframe>
	</div>
  <input type="hidden" name="info" value="none" />
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
        <p><b>Contratto:</b></p>
        <p>Numero:</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Cliente:</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
    <input type="hidden" name="auxil" value="">
    <div class="table-responsive">
    <table class="Tlarge table table-striped text-center">
        <tr>
            <?php $ts->output_headers(); ?>
        </tr>
        <?php
        //recupero le testate in base alle scelte impostate
        $result = gaz_dbi_dyn_query("*",
				    $wards_table,
				    $ts->where,
            $ts->orderby,
				    $ts->getOffset(),
            $ts->getLimit()
        );
        while ($r = gaz_dbi_fetch_array($result)) {
          echo "<tr class=\"FacetDataTD\">";
// colonna edit
          echo '<td class="text-center"><a class="btn btn-xs btn-edit" href="./admin_ward.php?id_ward='.$r['id_ward'].'" title="Modifica nome letto"><i class="glyphicon glyphicon-edit"></i></a>';
          echo "</td>";
// colonna reparto
          echo '<td>';
          echo '<a class="btn btn-sm btn-default" target="_blank" href="./admin_ward.php?wardname=' .$r['wardname']. '" title="Modifica nome reparto">' . $r['wardname'] . "</a>";
          echo '</td>';
// colonna stanze/letti
          echo '<td>';
          // riprendo il contenuto del reparto (stanze/letti)
          $ctrlroom = 0;
          $rs_sl = gaz_dbi_dyn_query("*",$gTables['bed']." LEFT JOIN ".$gTables['room']." ON ".$gTables['bed'].".id_room = ".$gTables['room'].".id_room", $gTables['room'].".id_ward = ".$r['id_ward'],$gTables['bed'].".id_room");
          while ($sl = gaz_dbi_fetch_array($rs_sl)) {
            echo ($ctrlroom<>$sl['id_room']?'<div class="col-xs-6 text-right">stanza: <a class="btn btn-xs btn-default" href="./admin_room.php?id_room='.$sl['id_room'].'">'.$sl['roomname'].'</a></div>':'<div class="col-xs-6 text-right"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </div>').'<div class="col-xs-6 text-left"> letto: <a class="btn btn-xs btn-default" href="./admin_bed.php?id_bed='.$sl['id_bed'].'">'.$sl['bedname'].'</a></div><br/>';
            $con=true;
            $ctrlroom =$sl['id_room'];
          }
          echo '</td>';
// colonna elimina
          echo '<td align="center"><a class="btn ';
          if ($con){
            echo 'btn-default" disabled title="Reparto non eliminabile perché ci sono stanze"';
          } else {
            echo 'btn-elimina dialog_delete" id_ward="'. $r['id_ward'].'" nome="'. $r['wardname'].'" ';
          }
          echo '> <i class="glyphicon glyphicon-trash"></i></a>';
          echo "</td>";

          echo "</tr>\n";
        }
        ?>
        <tr><th class="FacetFieldCaptionTD" colspan="10"></th></tr>
    </table>
    </div>
</form>
<?php
require("../../library/include/footer.php");
?>
