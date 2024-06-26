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

require("../../library/include/datlib.inc.php");

$admin_aziend=checkAdmin();

if (!ini_get('safe_mode')){ //se me lo posso permettere...
    ini_set('memory_limit','128M');
    gaz_set_time_limit (0);
}

$luogo_data=$admin_aziend['citspe'].", lì ";
$now = new DateTime();

require("../../config/templates/report_template_qc.php");
$title = array('luogo_data'=>$luogo_data,
               'title'=>"SITUAZIONE CAMPI DI COLTIVAZIONE al ".$now->format('d-m-Y'),
               'hile'=>array(array('lun' => 15,'nam'=>'N. campo'),
							array('lun' => 50,'nam'=>'Descrizione campo'),
                             array('lun' => 28,'nam'=>'Dimensione in ha'),
                             array('lun' => 45,'nam'=>'Coltura in atto'),
							 array('lun' => 30,'nam'=>'note'),
                             array('lun' => 80,'nam'=>'Immagine')
                            )
              );

// Antonio Germani carico le tabelle campi e camp_colture
$where="";
$what=$gTables['campi'].".*, ".$gTables['camp_colture'].".* ";
$table=$gTables['campi']." LEFT JOIN ".$gTables['camp_colture']." ON (".$gTables['campi'].".id_colture = ".$gTables['camp_colture'].".id_colt)";
$res=gaz_dbi_dyn_query ($what,$table,$where, 'codice ASC');

// avvio la creazione del PDF
$pdf = new Report_template('L','mm','A4',true,'UTF-8',false,true);
$pdf->setVars($admin_aziend,$title);
$pdf->SetTopMargin(42);
$pdf->SetFooterMargin(20);
$config = new Config;
$pdf->AddPage('L',$config->getValue('page_format'));
$pdf->SetFont('helvetica','',7);
$pdf->setJPEGQuality(15);
$n="";
if (gaz_dbi_num_rows($res) > 0) {
	while ($b_row = $res->fetch_assoc()) {
		if ($n>0){// evita la pagina bianca alla fine del ciclo while
			$pdf->AddPage(); // manda alla pagina successiva
		}$n=1;
		$pdf->Cell(15,3,$b_row['codice'],1);
		$pdf->Cell(50,3,$b_row['descri'],1);
		$pdf->Cell(28,3,str_replace('.', ',',$b_row["ricarico"]),1);
		$pdf->Cell(45,3,substr($b_row["id_colture"]." - ".$b_row['nome_colt'],0,50),1);
		$pdf->Cell(30,3,substr($b_row["annota"],0,50),1);
		if (strlen($b_row['image'])>0){
			$pdf->Image('@'.$b_row['image'], $x='', $y='', $w=80, $h=0, $type='', $link='', $align='', $resize=true, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false);
		}
	}
}
$pdf->Output();
?>
