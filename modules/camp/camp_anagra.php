<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
	  (https://www.devincentiis.it)
	  <https://gazie.sourceforge.net>
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
 // >> Gestione File upload anagrafica clienti/fornitori SIAN <<

require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin();
$msg='';

if (!isset($_POST['ritorno'])) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
} else {
    $form['ritorno'] = $_POST['ritorno'];
}

if (isset($_POST['return'])) {
    header("Location: ".$form['ritorno']);
    exit;
}
if (!isset($_POST['hidden_req'])) { //al primo accesso allo script
    $form['hidden_req'] = '';
} else { // accessi successivi
    $form['hidden_req']=htmlentities($_POST['hidden_req']);
}

// inizio controlli

// fine controlli

// Antonio Germani - prendo tutti i clienti e fornitori che hanno un codice SIAN nella loro anagrafica
$cf=array();
$where="id_SIAN > 0";
        $what=$gTables['clfoco'].".status_SIAN, ".
              $gTables['anagra'].".ragso1, ".$gTables['anagra'].".id_SIAN, ".
			  $gTables['anagra'].".indspe, ".$gTables['anagra'].".citspe, ".
			  $gTables['anagra'].".prospe ";
        $table=$gTables['anagra']." LEFT JOIN ".$gTables['clfoco']." ON (".$gTables['anagra'].".id = ".$gTables['clfoco'].".id_anagra)";
        $rs=gaz_dbi_dyn_query ($what,$table,$where, 'id ASC');
        while ($r = gaz_dbi_fetch_array($rs)) {
            $cf[] = $r;
        }

// controllo se sono state spuntate delle righe e, se sì, ci creo un array $rows
$n=0;
$rows=array();
foreach ($cf as $row){
	if (isset ($_POST['trasmettere'.$n])){
		$rows[]=$row['id_SIAN'];
	}
	$n++;
}

if (isset($_POST['create']) && $msg=='') { // se non ci sono errori
    // creazione file anagrafica SIAN
	if (isset($rows[0])){
		header("Location: create_anagrasian.php?".http_build_query($rows));
		exit;
	}
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>

<form method="POST" name="select">
<input type="hidden" value="<?php echo $form['hidden_req']; ?>" name="hidden_req"/>
<input type="hidden" value="<?php echo $form['ritorno']; ?>" name="ritorno"/>
<div class="panel panel-default gaz-table-form col-sm-12">
    <div class="container-fluid">
		<div class="row">
			<div class="col-sm-12" align="center"><b>File upload dell'anagrafica fornitori e clienti SIAN</b>
				<p align="justify">
				Il sistema del SIAN non permette di effettuare l'aggiornamento dell'anagrafica fornitori tramite il file di upload.
				Pertanto, per la modifica di quelli già inseriti nel portale dell'olio SIAN, è necessario avvalersi delle funzioni online del portale stesso.
				Il sistema SIAN, se rileva che il soggetto è già presente, lo scarta mentre acquisisce gli eventuali restanti record.
			</p></div>
		</div>
		<div class="bg-info">
			<div class="row">
			<div class="col-sm-1 active bg-warning">
				<?php echo "<b>Codice"; ?>
				</div>
				<div class="col-sm-3 bg-success">
				<?php echo "Ragione sociale"; ?>
				</div>
				<div class="col-sm-3 bg-warning">
				<?php echo "Indirizzo"; ?>
				</div>
				<div class="col-sm-3 bg-success">
				<?php echo "Località"; ?>
				</div>
				<div class="col-sm-1 bg-warning">
				<?php echo "Provincia"; ?>
				</div>
				<div class="col-sm-1 bg-success">
				<?php echo "Seleziona</b>"; ?>
				</div>
			</div>
		</div>
		<?php
		$n=0;
		foreach ($cf as $row){
			?>
			<div class="row">
				<div class="col-sm-1 bg-warning">
				<?php echo $row['id_SIAN']; ?>
				</div>
				<div class="col-sm-3 bg-success">
				<?php echo $row['ragso1']; ?>
				</div>
				<div class="col-sm-3 bg-warning">
				<?php echo $row['indspe']; ?>
				</div>
				<div class="col-sm-3 bg-success">
				<?php echo $row['citspe']; ?>
				</div>
				<div class="col-sm-1 bg-warning">
				<?php echo $row['prospe']; ?>
				</div>
				<div class="col-sm-1 bg-success">
				<?php if ($row['status_SIAN']>0){ ?>
					<span class="glyphicon glyphicon-ban-circle text-danger" title="Già trasmesso"></span>
				<?php } else { ?>
					<input type="checkbox" name="trasmettere<?php echo $n;?>" value="trasmettere"/>
				<?php } ?>
				</div>
			</div>
			<?php
			$n++;
		}?>
		<div class="row">
			<div class="col-sm-6 bg-success">
				<button type="submit" name="return" value="<?php echo $script_transl['return']; ?>"><?php echo $script_transl['return']; ?>
				<span class="glyphicon glyphicon-step-backward text-success" title="Clicca per gestione anagrafica SIAN"></span>
				</button>
			</div>
			<div class="col-sm-6 bg-success">
				<button type="submit" name="create" value="CREA file">CREA file upload SIAN
				<span class="glyphicon glyphicon-play-circle text-success" title="Clicca per creare il file anagrafica SIAN"></span>
				</button>
			</div>
		</div>
	</div>
</div>
    <?php
require("../../library/include/footer.php");
?>
