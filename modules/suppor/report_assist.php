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
require_once('../../library/include/datlib.inc.php');
$admin_aziend = checkAdmin();

$titolo = 'Assistenza Clienti';
$totale_ore = 0;
$stati = array();

$orderby = "stato ASC, data DESC";


if ( isset($_GET['chstato'] ) ) {
	$rows = array ('avvisare', 'bloccato', 'aperto', 'effettuato', 'fatturato');
	$found = false;
	for ($t=0; $t<count($rows); $t++ ) {
		if ( $found == true ) {
			$stato = $rows[$t];
			$found = false;
		}
		if ( $rows[$t]==$_GET['prev'] && $t<count($rows)-1 ) $found=true;
		elseif ( $rows[$t]==$_GET['prev'] && $t==count($rows)-1 ) {
			$stato = $rows[0];
		}
	}
	if (!empty($stato)) {
		gaz_dbi_table_update("assist", array("id", $_GET['chstato']), array("stato" => $stato));
	}
	if (empty($_GET['popup'])) {
		//header('Location: '.$form['ritorno']);
	} else {
		echo "<script>window.opener.location.reload(false);window.close();</script>";
	}
} else {
	if ( !isset($_GET['stato']) ) $_GET['stato'] = 'nochiusi';
}


if ( !isset( $_GET['idinstallazione']) ) { //se non viene visualizzato all'interno dello script installazioni
	require_once('../../library/include/header.php');
	$script_transl = HeadMain();
}


if ( !isset( $_GET['clfoco'] )) $_GET['clfoco'] = 'All';
if ( !isset( $_GET['oggetto'] )) $_GET['oggetto'] = '';

$where = "tipo='ASS' AND " . $gTables['anagra'] . ".ragso1 LIKE '%%'";
$all = $where;

if ( !empty($_GET['stato']) && $_GET['stato']=='nochiusi' ) {
	$where .= " AND ".$gTables['assist'].".stato!='chiuso' AND ".$gTables['assist'].".stato!='fatturato'";
}

if ( isset( $_GET['idinstallazione']) ) {
	$where .= " AND idinstallazione=".$_GET['idinstallazione'];
}

if ( isset($_GET['flt_passo']) ) {
	$passo = $_GET['flt_passo'];
} else {
	$passo = 50;
}

if ( !isset($_GET['tecnico']) ) $_GET['tecnico'] = 'All';

if ( !isset( $_GET['idinstallazione']) ) {
	gaz_flt_var_assign('codice', 'i', $gTables['assist']);
	gaz_flt_var_assign('data', 'd', $gTables['assist']);
	gaz_flt_var_assign('citspe', 'v');
	gaz_flt_var_assign('clfoco', 'v', $gTables['assist']);
	gaz_flt_var_assign('telefo', 'v');
	gaz_flt_var_assign('oggetto', 'v');
	gaz_flt_var_assign('descrizione', 'v');
	gaz_flt_var_assign('tecnico', 'v');
	gaz_flt_var_assign('stato', 'v', $gTables['assist']);
}

$result = gaz_dbi_dyn_query($gTables['assist'].".*,
		".$gTables['anagra'].".citspe, ".$gTables['anagra'].".ragso1, ".$gTables['anagra'].".telefo ", $gTables['assist'].
		" LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['assist'].".clfoco=".$gTables['clfoco'].".codice". 
		" LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra=".$gTables['anagra'].".id",
		$where, $orderby, $limit, $passo);

if (!isset( $_GET['idinstallazione']) || (isset($result) && gaz_dbi_num_rows($result)>0)) {
?>
<div align="center" class="FacetFormHeaderFont">Assistenze</div>
	<?php
	if ( !isset( $_GET['idinstallazione']) ) 
	{
		?>
		<form method="GET">
		<?php
	} else {
		?>
		<center><b>Programmazione Interventi</b></center>
		<?php
	}

	$recordnav = new recordnav($gTables['assist'].
		" LEFT JOIN ".$gTables['clfoco']." ON ".$gTables['assist'].".clfoco=".$gTables['clfoco'].".codice". 
		" LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra=".$gTables['anagra'].".id",
	$where, $limit, $passo);
	$recordnav -> output();
	?>
	
	<div class="box-primary table-responsive">
	<table class="Tlarge table table-striped table-bordered table-condensed">
		<?php
		if ( !isset( $_GET['idinstallazione']) ) {
		?>
		<tr>
			<td class="FacetFieldCaptionTD">
				<?php gaz_flt_disp_int("codice", "Numero"); ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php gaz_flt_disp_select("data", "YEAR(data) as data", $gTables["assist"], "9999", $orderby); ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php gaz_flt_disp_int("citspe", "Zona"); ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php 
					gaz_flt_disp_select("clfoco", $gTables['anagra'] . ".ragso1," . $gTables['assist'] . ".clfoco", $gTables['assist'] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['assist'] . ".clfoco=" . $gTables['clfoco'] . ".codice LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra=" . $gTables['anagra'] . ".id", $all." AND stato<>'chiuso' ", "ragso1", "ragso1"); 
					//gaz_flt_disp_int("", "Cliente"); 
				?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php gaz_flt_disp_int("telefo", "Telefono"); ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php gaz_flt_disp_int("oggetto", "Oggetto"); ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php gaz_flt_disp_int("descrizione", "Descrizione"); ?>
			</td>
			<td class="FacetFieldCaptionTD" colspan="2">
				<?php gaz_flt_disp_select("tecnico", "tecnico", $gTables["assist"], "1=1", "tecnico"); ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<?php gaz_flt_disp_select("stato", "stato", $gTables["assist"], "tipo='ASS'", "stato"); ?>
			</td>
			<td class="FacetFieldCaptionTD">
				<a class="btn btn-sm btn-default" href="print_ticket_list.php?auxil=<?php echo $auxil; ?>&clfoco=<?php echo (!empty($_GET['clfoco'])) ? $_GET['clfoco'] : '' ; ?>&flt_stato=<?php echo (!empty($_GET['stato'])) ? $_GET['stato'] : '' ; ?>&oggetto=<?php echo $_GET['oggetto']; ?>&flt_passo=<?php echo $passo; ?>"><i class="glyphicon glyphicon-list"></i>&nbsp;Stampa Lista</a>
			</td>
			<td class="FacetFieldCaptionTD">
				<input type="submit" class="btn btn-sm btn-default" name="search" value="Cerca" tabindex="1" onClick="javascript:document.report.all.value = 1;">
			</td>
		</tr>
		<?php
		}
		?>

		<?php
		if ( isset( $_GET['idinstallazione']) ) {
		$headers_assist = array(
			"ID"=>"",
			"Data"=>"",
			"Cliente"=>"",
			"Oggetto"=>"",
			"Soluzione"=>"",
			""=>"",
			"Ore"=>"",
			"Tecnico"=>"",
			"Stato"=>"",
			"Operaz."=>""
		);
		} else {
		$headers_assist = array(
			"ID" 	=> "codice",
			"Data" 		=> "data",
			"Zona" 		=> "citspe",
			"Cliente" 	=> "cliente",
			"Telefono" 	=> "telefono",
			"Oggetto" 	=> "oggetto",
			"Descrizione" => "descrizione",
			"Ore"			=> "ore",
			"Tecnico"       => "tecnico",
			"Stato" 		=> "stato",	
			"Operaz." 	=> "",
			"Elimina" 	=> ""
		);
		}

$linkHeaders = new linkHeaders($headers_assist);
$linkHeaders -> output();


//if (!isset($_GET['field']) or ($_GET['field'] == 2) or (empty($_GET['field'])))
//	$orderby = $gTables['assist'].".codice DESC";

while ($a_row = gaz_dbi_fetch_array($result)) {
?>
	<tr>
		<td>
			<a class="btn btn-xs btn-default" href="admin_assist.php?codice=<?php echo $a_row['codice']; ?>&Update">
			<i class="glyphicon glyphicon-edit"></i><?php echo $a_row['codice']; ?></a>
		</td>
		<td>
			<?php echo gaz_format_date($a_row['data']); ?> 
		</td>
		<?php
			if ( !isset( $_GET['idinstallazione']) ) {
				echo "<td>" . $a_row['citspe'] . "</td>";
			}
		?>
		<td>
			<?php
				if ( !empty($a_row['idinstallazione']) && empty($_GET['idinstallazione']) ) {
			?>
			<a href="admin_install.php?idinstallazione=<?php echo $a_row['idinstallazione']; ?>&Update">
			<?php
				} else {
			?>
			<a href="../vendit/report_client.php?nome=<?php echo $a_row['ragso1']; ?>">
			<?php
				}
			?>
			<?php 
				if ( strlen($a_row['ragso1']) > 20 ) {
					echo substr($a_row['ragso1'],0,20).'...'; 
				} else {
					echo $a_row['ragso1']; 
				}
			?></a>
		</td>
		<?php
			if ( !isset( $_GET['idinstallazione']) ) {
				echo "<td>".$a_row['telefo']."</td>";
			}
		?>
		<td>
			<?php echo $a_row['oggetto']; ?>
		</td>
		<?php
			if ( !isset( $_GET['idinstallazione']) ) {
				echo "<td>". $a_row['descrizione']. "</td>";
			} else {
				echo "<td colspan='2'>". $a_row['soluzione']. "</td>";
			}
		?>
		<td>
			<?php echo $a_row['ore']; ?>
		</td>
		<td>
			<?php echo $a_row['tecnico']; ?>
		</td>
		<td>
			<?php
				$filtro = '';
				if ( isset($_GET['clfoco']) ) $filtro .= '&clfoco='.$_GET['clfoco'];
				if ( isset($_GET['codice']) ) $filtro .= '&codice='.$_GET['codice'];
				if ( isset($_GET['stato']) ) $filtro .= '&stato='.$_GET['stato'];
				if ( isset($_GET['data']) ) $filtro .= '&data='.$_GET['data'];

				switch ($a_row['stato']) {
					case 'avvisare':
						$class_label_stato = 'btn-avvisare';
						break;
					case 'bloccato':
						$class_label_stato = 'btn-bloccato';
						break;
					case 'effettuato':
						$class_label_stato = 'btn-effettuato';
						break;
					case 'aperto':
						if (empty($a_row['idinstallazione'])) {
							$class_label_stato = 'btn-aperto';
						} else {
							$class_label_stato = 'btn-apinstallazione';
						}
						break;
					default:
						$class_label_stato = 'btn-default';
						break;
				}
			?>
			<a href="report_assist.php?chstato=<?php echo $a_row['id']."&prev=".$a_row['stato'].$filtro; ?>" class="btn btn-xs <?php echo $class_label_stato; ?>" onclick="window.open('report_assist.php?popup=stato&chstato=<?php echo ($a_row['id']."&prev=".$a_row['stato']); ?>','nuovaFinestra','top=50,left=200,width=800,height=680,location=no,menubar=no,resizable=no,status=no,titlebar=no'); return false;" onkeypress="window.open('report_assist.php?popup=stato&chstato=<?php echo ($a_row['id']."&prev=".$a_row['stato']); ?>','nuovaFinestra','top=50,left=200,width=800,height=680,location=no,menubar=no,resizable=no,status=no,titlebar=no'); return false;" title="Aggiorna allo stato successivo">
				<?php echo $a_row['stato']; ?>
			</a>
		</td>
		<td>
			<a class="btn btn-xs btn-default" href="stampa_assist.php?id=<?php echo $a_row['id']; ?>&cod=<?php echo $a_row['codice']; ?>" target="_blank"><i class="glyphicon glyphicon-print"></i></a>
			&nbsp;<a class="btn btn-xs btn-default" href="evadi_assist.php?id=<?php echo $a_row['id']; ?>&cod=<?php echo $a_row['codice']; ?>" target="_blank"><i class="glyphicon glyphicon-briefcase"></i></a>	
		</td>
<?php
	if ( !isset( $_GET['idinstallazione']) ) {
		echo "<td>
			<a class=\"btn btn-xs  btn-elimina\" href=\"delete_assist.php?id=".$a_row['id']."\">
			<i class=\"glyphicon glyphicon-trash\"></i></a>
		</td>";
	}
?>
	</tr>
<?php 
	$totale_ore += $a_row['ore'];
} 

$passi = array(20, 50, 100, 10000);
?>
<tr>
	<td class="FacetFieldCaptionTD" colspan="8" align="right">Totale Ore : 
		<?php echo floatval($totale_ore); ?>
	</td>
	<td class="FacetFieldCaptionTD" colspan="4" align="right">Totale Euro : 
		<?php echo floatval($totale_ore * 42); ?>
	</td>
</tr>
</table>
</div>

<div class="FootElementi">
	Numero elementi : 
		<select name="flt_passo" onchange="this.form.submit()">		
		<?php
		foreach ( $passi as $val ) {
			if ( $val == $passo ) $selected = ' selected';
			else $selected = '';
			echo "<option value='".$val."'".$selected.">".$val."</option>";
		}
		?>
		</select>
	</div>

	<div>
		Tipologia degli interventi : <br>
		avvisare - <br>
		bloccato - <br>
		aperto - Il cliente ha chiamato e ha comunicato la problematica<br>
		effettuato - L'intervento è stato completato e si può fatturare<br>
		fatturato - L'intervento è stato fatturato, controllare e chiudere per nasconderlo dalla lista<br>
		chiuso - L'intervento non è più visibile ma ancora presente nel database<br>
		<br>
		nochiusi = mostra solo aperti, effettuati, avvisare o bloccato
	</div>
<?php
}

if ( !isset( $_GET['idinstallazione']) ) { //se non viene visualizzato all'interno dello script installazioni
	echo '</form>';
	require('../../library/include/footer.php');
}
?>
