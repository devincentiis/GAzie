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
$libFunc = new magazzForm();
if ($admin_aziend['decimal_quantity'] > 4) {
   $admin_aziend['decimal_quantity'] = 4;
}

if (!isset($_GET['li']) or ! isset($_GET['ci']) or ! isset($_GET['cf']) or ! isset($_GET['ai']) or ! isset($_GET['af'])) {
   header("Location: select_listin.php");
   exit;
}

if (empty($_GET['af'])) {
   $_GET['af'] = 'zzzzzzzzzzzzzzz';
}
$gazTimeFormatter->setPattern('dd MMMM yyyy');
$luogo_data = $admin_aziend['citspe'] . ", lì ";
if (isset($_GET['ds'])) {
  $giosta = substr($_GET['ds'], 0, 2);
  $messta = substr($_GET['ds'], 2, 2);
  $annsta = substr($_GET['ds'], 4, 4);
  $utssta = mktime(0, 0, 0, $messta, $giosta, $annsta);
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime($annsta.'-'.$messta.'-'.$giosta)));
} else {
  $luogo_data .= ucwords($gazTimeFormatter->format(new DateTime()));
}

require("../../config/templates/report_template.php");
$what = $gTables['catmer'] . ".codice AS codcat , " . $gTables['catmer'] . ".descri AS descat , " .
        $gTables['artico'] . ".codice AS codart," . $gTables['artico'] . ".descri AS desart," . $gTables['artico'] . ".* , " .
        $gTables['aliiva'] . ".codice AS codiva, " . $gTables['aliiva'] . ".aliquo ";
$table = $gTables['artico'] . " LEFT JOIN " . $gTables['catmer'] . " ON (" . $gTables['artico'] . ".catmer = " . $gTables['catmer'] . ".codice)
         LEFT JOIN " . $gTables['aliiva'] . " ON (" . $gTables['artico'] . ".aliiva = " . $gTables['aliiva'] . ".codice)";
$where = "catmer BETWEEN '" . intval($_GET['ci']) .
        "' AND '" . intval($_GET['cf']) .
        "' AND " . $gTables['artico'] . ".codice BETWEEN '" . substr($_GET['ai'], 0, 15) .
        "' AND '" . substr($_GET['af'], 0, 15) . "' AND id_assets = 0 AND movimentabile <> 'N'";
/** inizio modifica FP 28/11/2015
 * filtro per fornitore ed ordinamento
 */
$titoloAddizionale = "";
if (isset($_GET['fo']) && !empty($_GET['fo'])) {
   $where = $where . " and " . $gTables['artico'] . ".clfoco='" . $_GET['fo'] . "'";
   $titoloAddizionale = " - Fornitore: "  . $_GET['fn'] ;
}
$order = "";
if (isset($_GET['o1'])) {
   $order = selezionaOrdine($_GET['o1'], $order);
}
if (isset($_GET['o2'])) {
   $order = selezionaOrdine($_GET['o2'], $order);
}
if (isset($_GET['o3'])) {
   $order = selezionaOrdine($_GET['o3'], $order);
}
if (empty($order)) {
   $order = "catmer ASC," . $gTables['artico'] . ".codice ASC";
}

/** fine modifica FP */
$result = gaz_dbi_dyn_query($what, $table, $where, $order);
if (isset($admin_aziend['lang'])){
  $price_list_names = gaz_dbi_dyn_query('*', $gTables['company_data'], "ref = '" . $admin_aziend['lang'] . "_artico_pricelist'", "id_ref ASC");
  if ($price_list_names->num_rows == 6){
    $script_transl['listino_value']=array();
    while ($list_name = gaz_dbi_fetch_array($price_list_names)){
      $script_transl['listino_value'][]=$list_name["data"];
    }
  }
}
switch ($_GET['li']) {
   case '0':
      $descrlis = (isset($script_transl['listino_value'][0]))?$script_transl['listino_value'][0]:'Listino d\'acquisto';
      break;
   case '1':
      $descrlis = (isset($script_transl['listino_value'][1]))?$script_transl['listino_value'][1]:'Listino di vendita n.1';
      break;
   case '2':
      $descrlis = (isset($script_transl['listino_value'][2]))?$script_transl['listino_value'][2]:'Listino di vendita n.2';
      break;
   case '3':
      $descrlis = (isset($script_transl['listino_value'][3]))?$script_transl['listino_value'][3]:'Listino di vendita n.3';
      break;
   case '4':
      $descrlis = (isset($script_transl['listino_value'][4]))?$script_transl['listino_value'][4]:'Listino di vendita n.4';
      break;
   case '5':
      $descrlis = (isset($script_transl['listino_value'][5]))?$script_transl['listino_value'][5]:'Listino di vendita online';
      break;
   default:
      $descrlis = 'Listino';
}
switch ($_GET['ts']) {
    case '0': // ESPANSA (vecchio layout di stampa)
	   $title = array('luogo_data' => $luogo_data,
		   'title' => $descrlis,
		   'hile' => array(
			   array('lun' => 190, 'nam' => 'Codice'),
			   array('lun' => 15, 'nam' => 'U.M.'),
			   array('lun' => 25, 'nam' => 'Prezzo'),
			   array('lun' => 25, 'nam' => 'Esistenza'),
			   array('lun' => 15, 'nam' => '% I.V.A.')
		   )
	   );
	   /** ENRICO FEDELE */
	   $gForm = new magazzForm();
	   $pdf = new Report_template();
	   $pdf->setVars($admin_aziend, $title, 'L');
	   $pdf->setAuthor($admin_aziend['ragso1'] . ' ' . $_SESSION["user_name"]);
	//$pdf->setTitle($title['title']);
	   $pdf->SetTopMargin(35);
	   $pdf->setFooterMargin(10);
	   $pdf->AddPage('L');
	   $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
	   $ctrlcatmer = 0;

	   /** ENRICO FEDELE */
	   /* Inizializzo le variabili e setto la dimensione del font a 9 */
	   $color = 0;
	   $color1 = array(255, 255, 255);
	   $color2 = array(240, 240, 240);
	   $pdf->SetFont('helvetica', '', 9);
	   /** ENRICO FEDELE */
	   while ($row = gaz_dbi_fetch_array($result)) {
		  $mv = $gForm->getStockValue(false, $row['codice']);
		  $magval = array_pop($mv);
      $magval=(is_numeric($magval))?['q_g'=>0,'v_g'=>0]:$magval;
		  $pdf->SetFont('helvetica', '', 10);
		  switch ($_GET['li']) {
			 case '0':
				$price = $row['preacq'];
				break;
			 case '1':
				$price = $row['preve1'];
				break;
			 case '2':
				$price = $row['preve2'];
				break;
			 case '3':
				$price = $row['preve3'];
				break;
			 case '4':
				$price = $row['preve4'];
				break;
			 case 'web':
				$price = $row['web_price'] * $row['web_multiplier'];
				$row['unimis'] = $row['web_mu'];
				break;
			 default:
				$price = $row['preve1'];
		  }
		  /** ENRICO FEDELE */
		  /* Modifico il layout della tabella, grassetto corsivo per categoria merceologica e codice articolo */
		  $pdf->SetFont('helvetica', 'BI', 9);
		  if ($row["catmer"] <> $ctrlcatmer) {
			 gaz_set_time_limit(30);
			 $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
			 /* Riga della categoria merceologica impostata a tutta larghezza */
			 $pdf->Cell(190, 4, 'Categoria Merceologica n.' . $row['codcat'] . ' = ' . $row['descat'], 1, 0, 'L', 1);
			 $pdf->Cell(15, 4, 'U.M.', 1, 0, 'C', true);
			 $pdf->Cell(25, 4, 'Prezzo', 1, 0, 'C', true);
			 $pdf->Cell(25, 4, 'Esistenza', 1, 0, 'C', true);
			 $pdf->Cell(15, 4, '% I.V.A.', 1, 1, 'C', true);

		  }
		  /* Alterno il colore delle righe per maggiore leggibilità */
		  $color == $color1 ? $color = $color2 : $color = $color1;
		  $pdf->SetFillColor($color[0], $color[1], $color[2]);

		  /* Celle con riempimento */
		  $pdf->Cell(190, 4, $row['codart'], 1, 0, 'C', true);
		  /* Reimposto il font per proseguire la stampa senza grassetto/italico */
		  $pdf->SetFont('helvetica', '', 9);
		  $pdf->Cell(15, 4, $row['unimis'], 1, 0, 'C', true);
		  $pdf->Cell(25, 4, number_format($price, $admin_aziend['decimal_price'], ',', '.'), 1, 0, 'R', true);
		  $pdf->Cell(25, 4, number_format($magval['q_g'], $admin_aziend['decimal_quantity'], ',', '.'), 1, 0, 'R', true);
		  $pdf->Cell(15, 4, $row['aliquo'], 1, 1, 'C', true); /* A capo dopo questa cella */
		  /* Descrizione articolo a capo per evitare testo sovrapposto con descrizioni lunghe */
		  $pdf->Cell(20, 4, 'Descrizione', 1, 0, 'L', true);
		  $pdf->Cell(250, 4, $row['desart'], 1, 1, 'L', true); /* A capo dopo questa cella */
		  /* Annotazioni a capo per evitare testo sovrapposto con descrizioni lunghe */
		  if (strlen($row['annota'])>0){ // Antonio Germani se le annotazioni non ci sono evito di stampare inutilmente la riga
			$pdf->Cell(20, 4, 'Annotazioni', 1, 0, 'L', true);
			$pdf->Cell(250, 4, $row['annota'], 1, 1, 'L', true); /* A capo dopo questa cella */
		  }
		  /** ENRICO FEDELE */
		  $ctrlcatmer = $row["catmer"];
	   }
	break;
    case '1': // layout di stampa di FP
	   $title = array('luogo_data' => $luogo_data,
		   'title' => $descrlis . $titoloAddizionale,
		   'hile' => array(
			   array('lun' => 30, 'nam' => 'Codice'),
			   array('lun' => 100, 'nam' => 'Descrizione'),
			   array('lun' => 10, 'nam' => 'U.M.'),
			   array('lun' => 20, 'nam' => 'Prezzo'),
			   array('lun' => 20, 'nam' => 'Sconto'),
			   array('lun' => 20, 'nam' => 'Prezzo finito'),
			   array('lun' => 20, 'nam' => 'IVA comp.'),
			   array('lun' => 50, 'nam' => 'Categoria'),
		   )
	   );
	   $gForm = new magazzForm();
	   $pdf = new Report_template();
	   $pdf->setVars($admin_aziend, $title, 'L');
	   $pdf->setAuthor($admin_aziend['ragso1'] . ' ' . $_SESSION["user_name"]);
	   $pdf->setFooterMargin(10);
	   $pdf->setTopMargin(40);
	   $pdf->AddPage('L');
	   $pdf->SetFillColor(hexdec(substr($admin_aziend['colore'], 0, 2)), hexdec(substr($admin_aziend['colore'], 2, 2)), hexdec(substr($admin_aziend['colore'], 4, 2)));
	//   $ctrlcatmer = 0;

	   /* Inizializzo le variabili e setto la dimensione del font a 6 */
	   $color = 0;
	   $color1 = array(255, 255, 255);
	   $color2 = array(240, 240, 240);
	   $pdf->SetFont('helvetica', '', 10);
	   /** ENRICO FEDELE */
	   while ($row = gaz_dbi_fetch_array($result)) {
		  switch ($_GET['li']) {
			 case '0':
        $lastbuys= $libFunc->getLastBuys($row['codice'],false);
        $klb=key($lastbuys);
        // per gli acquisti mi baso sul prezzo dell'ultimo acquisto, se non c'è prendo dall'anagrafica
				$price = $klb?$lastbuys[$klb]['prezzo']:$row['preacq'];
				$row['unimis'] = $klb?$lastbuys[$klb]['unimis']:$row['uniacq'];
				$row['sconto'] = $klb?$lastbuys[$klb]['scorig']:0;
				break;
			 case '1':
				$price = $row['preve1'];
				break;
			 case '2':
				$price = $row['preve2'];
				break;
			 case '3':
				$price = $row['preve3'];
				break;
			 case '4':
				$price = $row['preve4'];
				break;
			 case 'web':
				$price = $row['web_price'] * $row['web_multiplier'];
				$row['unimis'] = $row['web_mu'];
				break;
			 default:
				$price = $row['preve1'];
		  }
		  /* Alterno il colore delle righe per maggiore leggibilità */
		  $color == $color1 ? $color = $color2 : $color = $color1;
		  $pdf->SetFillColor($color[0], $color[1], $color[2]);

		  /* Celle con riempimento */
		  $pdf->Cell(30, 4, $row['codart'], 1, 0, 'L', true, '', 1);
		  $pdf->Cell(100, 4, $row['desart'], 1, 0, 'L', true, '', 1);
		  $pdf->Cell(10, 4, $row['unimis'], 1, 0, 'L', true);
		  $pdf->Cell(20, 4, number_format($price, $admin_aziend['decimal_price'], ',', '.'), 1, 0, 'R', true);
		  $sconto=$row['sconto'];
		  $pdf->Cell(20, 4, number_format($sconto, 2, ',', '.'), 1, 0, 'R', true);
	//      $pdf->Cell(20, 4, $row['sconto'], 1, 0, 'C', true);
		  $prezzoScontato=$price*(1-$sconto/100);
		  $pdf->Cell(20, 4, number_format($prezzoScontato, $admin_aziend['decimal_price'], ',', '.'), 1, 0, 'R', true);
		  $aliquotaIva=$row['aliquo'];
		  $importo=$prezzoScontato*(1+$aliquotaIva/100);
		  $pdf->Cell(20, 4, number_format($importo, $admin_aziend['decimal_price'], ',', '.'), 1, 0, 'R', true);
		  $pdf->Cell(50, 4, $row['descat'], 1, 1, 'C', true, '', 1);
	   }
	break;
    case '2': // Stampa verticale (con confezioni)
		$title=array('luogo_data'=>$luogo_data,
					   'title'=>$descrlis,
					   'hile'=>array(array('lun' => 30,'nam'=>'Codice'),
									 array('lun' => 85,'nam'=>'Descrizione'),
									 array('lun' => 15,'nam'=>'U.M.'),
									 array('lun' => 25,'nam'=>'Prezzo'),
									 array('lun' => 25,'nam'=>'Pz.Confezione')
									 )
					);
		$primapag=1;
		$gForm = new magazzForm();
		$pdf = new Report_template();
		$pdf->setVars($admin_aziend,$title);
		$pdf->setAuthor($admin_aziend['ragso1'].' '.$_SESSION['user_name']);
		$pdf->setTitle($title['title']);
		$pdf->SetTopMargin(39);
		$pdf->setFooterMargin(10);
		$pdf->setLeftMargin(10);
		$pdf->AddPage();
		$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
		$ctrlcatmer=0;
		while ($row = gaz_dbi_fetch_array($result)) {
			   $pdf->SetFont('helvetica','',10);
			   switch($_GET['li']) {
				case '0':
				$price = $row['preacq'];
				break;
				case '1':
				$price = $row['preve1'];
				break;
				case '2':
				$price = $row['preve2'];
				break;
				case '3':
				$price = $row['preve3'];
				break;
        case '4':
				$price = $row['preve4'];
				break;
				case 'web':
				$price = $row['web_price']*$row['web_multiplier'];
				$row['unimis'] = $row['web_mu'];
				break;
        default:
        $price = $row['preve1'];
			  }
			  if ($row["catmer"] <> $ctrlcatmer) {
				gaz_set_time_limit (30);
				$pdf->Cell(180,3,'',0,1);
			    $pdf->SetFont('helvetica','B',11);
				$pdf->Cell(180,5,$row['descat'],0,1);
			    $pdf->SetFont('helvetica','',10);
				if ($primapag) {
					$primapag=0;
				} else {
				    $pdf->SetFont('helvetica','',9);
					$pdf->Cell(30,4,'Codice',1,0,'C',1);
					$pdf->Cell(85,4,'Descrizione',1,0,'C',1);
					$pdf->Cell(15,4,'U.M.',1,0,'C',1);
					$pdf->Cell(25,4,'Prezzo',1,0,'C',1);
					$pdf->Cell(25,4,'Pz.Confezione',1,1,'C',1);
					$pdf->SetFont('helvetica','',10);
				}
			  }
			    $pdf->SetFont('helvetica','',10);
			  $pdf->Cell(30,5,$row['codart'],1,0,'L',0,'',1);
			  $pdf->Cell(85,5,$row['desart'],1,0,'L',0,'',1);
			  $pdf->Cell(15,5,$row['unimis'],1,0,'C',0,'',1);
			  $pdf->Cell(25,5,number_format($price,$admin_aziend['decimal_price'],',','.'),1,0,'R',0,'',1);
			  if ($row['pack_units']>0) {
				  $pdf->Cell(25,5,$row['pack_units'],1,1,'C',0,'',1);
			  } else {
				  $pdf->Cell(25,5,'1',1,1,'C',0,'',1);
			  }
			  $ctrlcatmer=$row["catmer"];
		}
	break;
  case '3': // Stampa verticale (con confezioni e iva compresa)
		$title=array('luogo_data'=>$luogo_data,
					   'title'=>$descrlis." IVA compresa",
					   'hile'=>array(array('lun' => 30,'nam'=>'Codice'),
									 array('lun' => 85,'nam'=>'Descrizione'),
									 array('lun' => 15,'nam'=>'U.M.'),
									 array('lun' => 25,'nam'=>'Prezzo'),
									 array('lun' => 25,'nam'=>'Pz.Confezione')
									 )
					);
		$primapag=1;
		$gForm = new magazzForm();
		$pdf = new Report_template();
		$pdf->setVars($admin_aziend,$title);
		$pdf->setAuthor($admin_aziend['ragso1'].' '.$_SESSION['user_name']);
		$pdf->setTitle($title['title']);
		$pdf->SetTopMargin(39);
		$pdf->setFooterMargin(10);
		$pdf->setLeftMargin(10);
		$pdf->AddPage();
		$pdf->SetFillColor(hexdec(substr($admin_aziend['colore'],0,2)),hexdec(substr($admin_aziend['colore'],2,2)),hexdec(substr($admin_aziend['colore'],4,2)));
		$ctrlcatmer=0;
		while ($row = gaz_dbi_fetch_array($result)) {
			   $pdf->SetFont('helvetica','',10);
			   switch($_GET['li']) {
				case '0':
				$price = $row['preacq'];
				break;
				case '1':
				$price = $row['preve1'];
				break;
				case '2':
				$price = $row['preve2'];
				break;
				case '3':
				$price = $row['preve3'];
				break;
        case '4':
				$price = $row['preve4'];
				break;
				case 'web':
				$price = $row['web_price']*$row['web_multiplier'];
				$row['unimis'] = $row['web_mu'];
				break;
        default:
        $price = $row['preve1'];
			  }
			  if ($row["catmer"] <> $ctrlcatmer) {
				gaz_set_time_limit (30);
				$pdf->Cell(180,3,'',0,1);
			    $pdf->SetFont('helvetica','B',11);
				$pdf->Cell(180,5,$row['descat'],0,1);
			    $pdf->SetFont('helvetica','',10);
				if ($primapag) {
					$primapag=0;
				} else {
				    $pdf->SetFont('helvetica','',9);
					$pdf->Cell(30,4,'Codice',1,0,'C',1);
					$pdf->Cell(85,4,'Descrizione',1,0,'C',1);
					$pdf->Cell(15,4,'U.M.',1,0,'C',1);
					$pdf->Cell(25,4,'Prezzo',1,0,'C',1);
					$pdf->Cell(25,4,'Pz.Confezione',1,1,'C',1);
					$pdf->SetFont('helvetica','',10);
				}
			  }
			    $pdf->SetFont('helvetica','',10);
			  $pdf->Cell(30,5,$row['codart'],1,0,'L',0,'',1);
			  $pdf->Cell(85,5,$row['desart'],1,0,'L',0,'',1);
			  $pdf->Cell(15,5,$row['unimis'],1,0,'C',0,'',1);
        $price=$price*(1-$row['sconto']/100);
        $price=$price*(1+$row['aliquo']/100);
			  $pdf->Cell(25,5,number_format($price,$admin_aziend['decimal_price'],',','.'),1,0,'R',0,'',1);
			  if ($row['pack_units']>0) {
				  $pdf->Cell(25,5,$row['pack_units'],1,1,'C',0,'',1);
			  } else {
				  $pdf->Cell(25,5,'1',1,1,'C',0,'',1);
			  }
			  $ctrlcatmer=$row["catmer"];
		}
	break;
}
$pdf->Output();

/** inizio modifica FP 28/11/2015
 * ordinamento
 */
function selezionaOrdine($sceltaOrdine, $order) {
   global $gTables;

   $daAggiungere = "";
   switch ($sceltaOrdine) {
      case 1:  // codice
         $daAggiungere = $gTables['artico'] . ".codice ASC";
         break;
      case 2:  // descrizione
         $daAggiungere = $gTables['artico'] . ".descri ASC";
         break;
      case 3:  // categoria
         $daAggiungere = "catmer ASC";
         break;
      default:
         break;
   }
   if (!empty($daAggiungere)) {
      $daAggiungere = (empty($order) ? "" : ",") . $daAggiungere;
   }
   return $order . $daAggiungere;
}

/** fine modifica FP */
?>
