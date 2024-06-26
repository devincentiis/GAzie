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
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}

function getLastDoc($item_code) {
    global $gTables;
    $rs = false;
    $rs_last_doc = gaz_dbi_dyn_query("*", $gTables['files'], " item_ref ='" . $item_code . "'", 'id_doc DESC', 0, 1);
    $last_doc = gaz_dbi_fetch_array($rs_last_doc);
    // se e' il primo documento dell'anno, resetto il contatore
    if ($last_doc) {
        $rs = $last_doc;
    }
    return $rs;
}

if (isset($_POST['rowno'])) { //	Evitiamo errori se lo script viene chiamato direttamente
    require("../../library/include/datlib.inc.php");
	require ("../../modules/magazz/lib.function.php");
    $admin_aziend = checkAdmin();
    require("./lang." . $admin_aziend['lang'] . ".php");
    $script_transl = $strScript['camp_report_artico.php'];
    $no = intval($_POST['rowno']);
    $ob = filter_input(INPUT_POST, 'orderby');
    $so = filter_input(INPUT_POST, 'sort');
    $ca = filter_input(INPUT_POST, 'codart');
	$mt = filter_input(INPUT_POST, 'mostra');

    if (empty($ca)) {
        $where = '1';
    } else {
        $where = "codice = '" . $ca . "'";
        $no = '0';
    }
	if ($mt==0){
		$where=$where." AND mostra_qdc = '1' "; // Antonio Germani seleziona quali prodotti mostrare nell'elenco
	}
    $gForm = new magazzForm();
    $result = gaz_dbi_dyn_query('*', $gTables['artico'], $where, $ob . ' ' . $so, $no, PER_PAGE);
    while ($row = gaz_dbi_fetch_array($result)) {
		unset ($magval);unset($mv);
		$lastdoc = getLastDoc($row["codice"]);
		if ($row['good_or_service']!=1){
			$mv = $gForm->getStockValue(false, $row['codice']);
			$magval = array_pop($mv);
			$decimal_quantity=$admin_aziend['decimal_quantity'];

			if ( filter_var($magval, FILTER_VALIDATE_INT) === false ) {
			}else{// is an integer
			unset($magval);
			$magval['q_g']=0;
			}
			$$decimal_quantity=2;
			if (number_format($magval['q_g'],8)<0){
				$decimal_quantity=8;
			}

		} 	else {
			$magval['q_g'] = 0;
      $decimal_quantity=$admin_aziend['decimal_quantity'];
		}
		$class = 'default';
        if ($magval['q_g'] < 0) { // giacenza inferiore a 0
            $class = 'danger';
        } elseif ($magval['q_g'] > 0) { //
			if ($magval['q_g']<=$row['scorta']){
				$class = 'warning';
			}
        } else { // giacenza = 0
            $class = 'danger';
        }
        $iva = gaz_dbi_get_row($gTables['aliiva'], "codice", $row["aliiva"]);
        $ldoc = '';
        if ($lastdoc) {
            $ldoc = '<a href="../root/retrieve.php?id_doc=' . $lastdoc["id_doc"] . '">
		<i class="glyphicon glyphicon-file" title="Scheda di sicurezza (ultima inserita)"></i>
		</a>';
        }
        if ($row["good_or_service"] == 1) {
            $gooser_i = 'wrench';
        } else if ($row["good_or_service"] == 0) {
            $gooser_i = 'shopping-cart';
        } else if ($row["good_or_service"] == 2) {
            $gooser_i = 'tasks';
        }

        $com = '';
        if ($admin_aziend['conmag'] > 0 && $row["good_or_service"] <= 0) {
            $com = '<a class="btn btn-xs btn-default" href="../camp/camp_select_schart.php?di=0101' . date('Y') . '&df=' . date('dmY') . '&id=' . $row['codice'] . '" target="_blank">
		  <i class="glyphicon glyphicon-check"></i><i class="glyphicon glyphicon-print"></i>
		  </a>&nbsp;';
        }
		/*Antonio Germani prendo descrizione categoria merceologica */
		$catmer = gaz_dbi_get_row($gTables['catmer'], 'codice',$row['catmer']);
		$descatmer=(isset($catmer['descri']))?$catmer['descri']:'';
        ?>

        <tr>
            <td data-title="<?php echo $script_transl["codice"]; ?>">
                <a class="btn btn-xs btn-default" href="../camp/camp_admin_artico.php?Update&codice=<?php echo $row['codice']; ?>" ><i class="glyphicon glyphicon-edit"></i>&nbsp;<?php echo $row['codice']; ?></a>
            </td>
            <td data-title="<?php echo $script_transl["descri"]; ?>">
                <span class="gazie-tooltip" data-type="product-thumb" data-id="<?php echo $row["codice"]; ?>" data-label="<?php echo $row['annota']; ?>"><?php echo $row["descri"]; ?></span>
            </td>
			<td data-title="">
			<?php if ($row["classif_amb"]==0){?>
			<img src="../camp/media/classe_0.gif" alt="Non classificato" width="50 px">
			<?php echo "Nc"; }?>
			<?php if ($row["classif_amb"]==1){?>
			<img src="../camp/media/classe_1.gif" alt="Irritante" width="50 px">
			<?php echo "Xi"; }?>
			<?php if ($row["classif_amb"]==2){?>
			<img src="../camp/media/classe_2.gif" alt="Nocivo" width="50 px">
			<?php echo "Xn"; }?>
			<?php if ($row["classif_amb"]==3){?>
			<img src="../camp/media/classe_3.gif" alt="Tossico" width="50 px">
			<?php echo "T"; }?>
			<?php if ($row["classif_amb"]==4){?>
			<img src="../camp/media/classe_4.gif" alt="Molto tossico" width="50 px">
			<?php echo "T+"; }?>
			<?php if ($row["classif_amb"]==5){?>
			<img src="../camp/media/classe_5.gif" alt="Pericoloso ambiente" width="50 px">
			<?php echo "T+"; }?>
            </td>
            <td data-title="<?php echo $script_transl["good_or_service"]; ?>" class="text-center">
                <?php echo $ldoc; ?> &nbsp;   <i class="glyphicon glyphicon-<?php echo $gooser_i; ?>"></i>
            </td>
            <td data-title="<?php echo $script_transl["catmer"]; ?>" class="text-left">
                <?php echo $row["catmer"]," ",$descatmer; ?>
            </td>
            <td data-title="<?php echo $script_transl["unimis"]; ?>">
                <?php echo $row["unimis"]; ?>
            </td>

            <td data-title="<?php echo $script_transl["stock"]; ?>" title="Visualizza scheda prodotto" class="text-center <?php echo $class; ?>">
               <?php $print_magval=str_replace(",","",$magval['q_g']);echo gaz_format_quantity($print_magval,1,$decimal_quantity); echo '<p style="float:right;">'.$com.'</p></td><td title="Visualizza lotti">';

			   if (intval($row['lot_or_serial'])>0) {
			   ?>
			   <a  class="btn btn-info btn-md" href="javascript:;" onclick="window.open('<?php echo"../../modules/magazz/mostra_lotti.php?codice=".$row['codice'];?>', 'titolo', 'menubar=no, toolbar=no, width=800, height=400, left=80%, top=80%, resizable, status, scrollbars=1, location');">
						<span class="glyphicon glyphicon-tag"></span></a>
			   <?php } ?>
            </td>
            <td data-title="<?php echo $script_transl["clone"] . ' in ' . $row["codice"]; ?>_2" title="Copia" class="text-center">
                <a class="btn btn-xs btn-default" href="clone_artico.php?codice=<?php echo $row["codice"]; ?>">
                    <i class="glyphicon glyphicon-export"></i>
                </a>
            </td>
            <td class="text-center">
                <a class="btn btn-xs  btn-elimina dialog_delete" ref="<?php echo $row['codice'];?>" artico="<?php echo $row['descri']; ?>">
					<i class="glyphicon glyphicon-trash"></i>
				</a>
            </td>
        </tr>
        <?php
    }
	?>
	<script>
	$(function() {
		$("#dialog_delete").dialog({ autoOpen: false });
		$('.dialog_delete').click(function() {
			$("p#idcodice").html($(this).attr("ref"));
			$("p#iddescri").html($(this).attr("artico"));
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
							data: {'type':'artico',ref:id},
							type: 'POST',
							url: '../camp/delete.php',
							success: function(output){
								//alert(output);
								window.location.replace("./camp_report_artico.php");
							}
						});
					}}
				}
			});
			$("#dialog_delete" ).dialog( "open" );
		});
	});
	</script>
	<?php
    exit();
}
?>
