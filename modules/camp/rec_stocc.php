<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/
// >> Visualizza recipienti di stoccaggio <<

require("../../library/include/datlib.inc.php");
require ("../../modules/vendit/lib.function.php");
$gSil = new silos();
$admin_aziend=checkAdmin();
$titolo = 'Recipienti di stoccaggio e silos';

require("../../library/include/header.php");
$script_transl = HeadMain();

if (isset($_GET['auxil'])) {
   $auxil = $_GET['auxil'];
}
if (isset($_GET['all'])) {
   $auxil = "&all=yes";
   $where = "cod_silos like '%'";
   $passo = 100000;
} else {
   if (isset($_GET['auxil'])) {
      $where = "cod_silos like '".addslashes($_GET['auxil'])."%'";
   }
}

if (!isset($_GET['auxil'])) {
   $auxil = "";
   $where = "cod_silos like '".addslashes($auxil)."%'";
}

?>
<style>
	.bar {
		max-width:100%;
		width:283px;
		height: 28px;
		overflow: hidden;
		background: url(../../modules/camp/media/background_bar.jpg) no-repeat;
	}
</style>
<script>
$(function() {
	$("#dialog_delete").dialog({ autoOpen: false });
	$('.dialog_delete').click(function() {
		$("p#idcodice").html($(this).attr("ref"));
		$("p#iddescri").html($(this).attr("capacity"));
		var id = $(this).attr('ref');
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
						data: {'type':'recstocc',ref:id},
						type: 'POST',
						url: '../camp/delete.php',
						success: function(output){
		                    //alert(output);
							window.location.replace("./rec_stocc.php");
						}
					});
				}}
			}
		});
		$("#dialog_delete" ).dialog( "open" );
	});
});
function ContentSil(silos) {
	$("#idlotti").append("ID Lotti");
	$("#idvar").append("Varietà");
    $("#dialog_silos").attr("title","Contenuto attuale: "+silos);
	var jsondatastr = null;
	var n=0;
		$.ajax({ // chiedo il contenuto
				'async': false,
				url:"./ajax_request.php",
				type: "POST",
				dataType: 'text',
				data: {term: silos, opt: 'ContentSil' },
				success:function(jsonstr) {
					var jsondata = $.parseJSON(jsonstr);
					var type1 = JSON.stringify(jsondata['id_lotti']);
					var type2 = JSON.stringify(jsondata['varieta']);

					var obj = $.parseJSON(type2);
          var n = (Object.keys(obj).length);
          if (n>1){
            $.each(obj, function(i, value) {
              $(".list_variants").append("<tr><td> "+i+":  Kg."+value+"&nbsp;</td></tr>");
            n++;
            });
          }
					if (n<=1){
						$(".list_variants").append('<tr><td class="bg-danger">********* Non ci sono varietà o è un mix con oli senza varietà *********</td></tr>');
					}
					n=0;
					var obj = $.parseJSON(type1);
					$.each(obj, function(i, value) {
						$(".list_var").append("<tr><td> "+i+":  Kg."+value+"&nbsp;</td></tr>");
						n++;
					});

					if (n<=1){
						$(".list_var").append('<tr><td class="bg-danger">********* Non ci sono lotti *********</td></tr>');
					}
				}
			});
	$( function() {
        var dialog,
        dialog = $("#dialog_silos").dialog({
            modal: true,
            show: "blind",
            hide: "explode",
            width: "auto",
            buttons: {
                Chiudi: function() {
                    $(this).dialog('close');
                }
            },
            close: function(){
				$("p#idlotti").empty();
				$("p#idvar").empty();
				$("div.list_var tr").remove();
				$("div.list_variants tr").remove();
				$(this).dialog('destroy');
            }
        });
	});
};

function getMovContainer(cod_silos) {
	$("#idsilos").append("Contenitore: "+cod_silos);
  $("#dialog_movcontainer").attr("title","Movimenti del contenitore");
	$.post("ajax_request.php",
		{term: cod_silos, opt: 'movcontainer'},
		function (data) {
			var j=0;
				$.each(data, function(i, value) {
				j++;
        if (parseFloat(value.pro)>=0.000001){
          clpro = 'bg-success';
        } else {
          clpro = 'bg-danger';
          value.pro ='--- vuoto ---';
        }
				$("table.list_movcontainer").append("<tr><td> <button onclick='location.href=\"../magazz/admin_movmag.php?Update&id_mov="+value.id+"\"' target='_blank' type='button'>"+value.id+" </button></td><td class='bg-warning'>&nbsp;"+ value.cod + " </td><td>&nbsp; "+value.datdoc+":"+value.des+value.id_lot+"&nbsp; </td><td class='bg-info'> "+value.val+ "</td><td> &nbsp;" +value.um+ "&nbsp; </td><td class='text-right "+ clpro +"'> "+value.pro+" </td></tr>");
				});
				if (j==0){
					$(".list_movcontainer").append('<tr><td class="bg-danger">********* Contenitore non movimentato *********</td></tr>');
				}
		}, "json"
	);
	$( function() {
    var dialog,
    dialog = $("#dialog_movcontainer").dialog({
      position: { my: "center-200 top", at: "center top+80"},
      modal: true,
      show: "blind",
      hide: "explode",
      width: "auto",
      buttons: {
        Chiudi: function() {
          $(this).dialog('close');
        }
      },
      close: function(){
          $("p#idsilos").empty();
          $("table.list_movcontainer tr").remove();
          $(this).dialog('destroy');
      }
    });
	});

};
</script>
<style> .ui-dialog { z-index: 1050; } </style>
<div align="center" class="FacetFormHeaderFont">Recipienti di stoccaggio</div>
<?php
$recordnav = new recordnav($gTables['camp_recip_stocc'], $where, $limit, $passo);
$recordnav -> output();
?>
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<div style="display:none" id="dialog_delete" title="Conferma eliminazione">
		<p><b>recipiente di stoccaggio:</b></p>
		<p>Codice</p>
        <p class="ui-state-highlight" id="idcodice"></p>
        <p>Capacità</p>
        <p class="ui-state-highlight" id="iddescri"></p>
	</div>
	<div style="display:none; min-width:350px; " id="dialog_silos" title="">
		<p class="ui-state-highlight" id="idlotti"></p>
		<div class="list_var">
		</div>
		<p class="ui-state-highlight" id="idvar"></p>
		<div class="list_variants">
		</div>
	</div>
	<div style="display:none; min-width:350px; " id="dialog_movcontainer" title="">
		<p class="ui-state-highlight" id="idsilos"></p>
		<table class="list_movcontainer">
		</table>
	</div>
    <table class="Tlarge table table-striped table-bordered table-condensed table-responsive">
    	<thead>
            <tr>
                <td></td>
                <td class="FacetFieldCaptionTD">Codice recipiente o silos:
                    <input type="text" name="auxil" value="<?php if ($auxil != "&all=yes") echo $auxil; ?>" maxlength="6" tabindex="1" class="FacetInput" />
                </td>
                <td>
                    <input type="submit" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value=1;" />
                </td>
                <td>
                    <input type="submit" name="all" value="Mostra tutti" onClick="javascript:document.report.all.value=1;" />
                </td>
            </tr>
            <tr>
				<?php
					$result = gaz_dbi_dyn_query ('*', $gTables['camp_recip_stocc'], $where, $orderby, $limit, $passo);
					// creo l'array (header => campi) per l'ordinamento dei record
					$headers_silos = array("Codice SIAN del recipiente o silos"      => "cod_silos",
											"Capacità in Kg" => "capacita",
											"Stato" => "riempimento",
											"" => "",
											"Titolo di possesso" => "affitto",
											"Destinato a DOP o IGP" => "dop_igp",
											"Cancella"    => ""
											);
					$linkHeaders = new linkHeaders($headers_silos);
					$linkHeaders -> output();
				?>
        	</tr>
        </thead>

		<?php
		while ($a_row = gaz_dbi_fetch_array($result)) {
			$content= $gSil -> getCont($a_row['cod_silos']);
			unset ($lot);
			?>
			<tr class="FacetDataTD">
				<td>
					<a class="btn btn-xs btn-success btn-block" href="admin_rec_stocc.php?Update&codice=<?php echo $a_row["cod_silos"]; ?>">
					<i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo $a_row['cod_silos']," - ",$a_row['nome'];?>
					</a>
				</td>
				<td align="center"><?php echo gaz_format_quantity($a_row['capacita'], 1, 3);?></td>
				<td>
					<?php
					if ($content>0){
						$lot=	$gSil -> getLotRecip($a_row['cod_silos']);
						echo "Kg.",gaz_format_quantity($content,true)," l.",gaz_format_quantity($content/0.915,true)," Ultimo lotto: ",$lot[1];
						if ($content > $a_row['capacita']){
							echo " ERRORE!";
						}            
					}
					echo ' <a class="btn btn-xs btn-default dialog_content" title="Movimenti nel contenitore" onclick="getMovContainer(\''.$a_row['cod_silos'].'\')" > <i class="glyphicon glyphicon-list"></i></a>';
					?>
					<div class="bar">
						<img src="../../modules/camp/media/white_bar.jpg" alt="Barra silos" title="Contenuto silos" style="padding-left:<?php echo ((($content/$a_row['capacita'])*100)* 280 )/100;?>px;">
					</div>
				</td>
				<td>
					<a class="btn btn-xs btn-default dialog_content" title="Contenuto in lotti e varietà" onclick="ContentSil('<?php echo $a_row['cod_silos'];?>')" >
					<i class="glyphicon glyphicon-oil"></i>
					</a>
				</td>
				<td align="center">
					<?php
					if (intval($a_row['affitto'])==0){
						echo "Proprietà";
					} else{
						echo "Affitto";
					}
					?>
				</td>
				<td align="center">
				<?php
					if (intval($a_row['dop_igp'])==0){
						echo "NO";
					} else{
						echo "DOP IGP";
					}
					?>
				</td>
				<td align="center">
					<a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $a_row['cod_silos'];?>" capacity="<?php echo $a_row['capacita']; ?>">
						<i class="glyphicon glyphicon-trash"></i>
					</a>
				</td>
			</tr>
			<?php
		}
		?>


    </table>
</form>
<form method="post" action="admin_rec_stocc.php">
	<table>
		<tr class="FacetFieldCaptionTD">
			<td colspan="7" align="right">
				<input class="btn btn-info" type="submit" name="aggiungi" value="<?php echo "Inserisci nuovo contenitore o silos";?>">
			</td>
		</tr>
	</table>
</form>
<a href="https://programmisitiweb.lacasettabio.it/quaderno-di-campagna/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Registro di campagna è un modulo di Antonio Germani</a>
<?php
require("../../library/include/footer.php");
?>
