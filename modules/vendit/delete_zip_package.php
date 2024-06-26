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
$admin_aziend = checkAdmin();
$paymov = new Schedule;
$anagrafica = new Anagrafica();

if (isset($_GET['fn'])) {
	$fn=substr($_GET['fn'],0,37);
} else {
    header("Location: " . $_SERVER['HTTP_REFERER']);
}


if (!isset($_POST['ritorno'])) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if (isset($_POST['Delete'])) {
	// elimino il file sul filesystem
	$file_url = DATA_DIR."files/".$admin_aziend['codice']."/".$fn;
	@unlink($file_url);
	// elimino i righi dalla tabella dei flussi
    gaz_dbi_del_row($gTables['fae_flux'], 'filename_zip_package', $fn);
	// ristabilisco la possibilità i ricreare il pacchetto dalle fatture
    gaz_dbi_put_query($gTables['tesdoc'], "fattura_elettronica_zip_package = '" . $fn."'", "fattura_elettronica_zip_package", "");

    header("Location: " . $_POST['ritorno']);
    exit;
}

if (isset($_POST['Return'])) {
    header("Location: " . $_POST['ritorno']);
    exit;
}


require("../../library/include/header.php");
$script_transl = HeadMain();
// Controllo se l'operazione è pericolosa
$rs_danger = gaz_dbi_dyn_query ("MAX((flux_status='@' OR flux_status='@@' OR flux_status='IN')*1) AS danger",$gTables['fae_flux'], "filename_zip_package='".$fn."'");
$danger= gaz_dbi_fetch_row($rs_danger)[0];
?>
<form method="POST">
    <input type="hidden" name="ritorno" value="<?php print $form['ritorno']; ?>">
    <div class="text-center bg-danger">
        <p>
            <b>
			<?php
			if ($danger==0){
                 echo $script_transl['warning'] . '!!! ' . $script_transl['title'].$fn; 
			} else {
                 echo $script_transl['danger'];
			} ?>
            </b> 
        </p>
    </div>
    <div class="panel panel-warning">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 bg-info">
<?php echo $script_transl['head']; ?></div>                
                </div>
            </div><!-- chiude row  -->
<?php
// attingo i dati dal db
$orderby = $gTables['fae_flux'].'.filename_zip_package DESC, '.$gTables['fae_flux'].'.filename_ori DESC,'. $gTables['fae_flux'].'.progr_ret'   ;
$result = gaz_dbi_dyn_query ($gTables['fae_flux'].".*,".$gTables['tesdoc'].".tipdoc,".$gTables['tesdoc'].".datfat,".$gTables['tesdoc'].".protoc,".$gTables['tesdoc'].".seziva,".$gTables['tesdoc'].".numfat,".$gTables['clfoco'].".codice,".$gTables['clfoco'].".descri", $gTables['fae_flux'].' LEFT JOIN '.$gTables['tesdoc'].' ON '.$gTables['fae_flux'].'.id_tes_ref = '.$gTables['tesdoc'].'.id_tes LEFT JOIN '.$gTables['clfoco'].' ON '.$gTables['tesdoc'].'.clfoco = '.$gTables['clfoco'].'.codice',$gTables['fae_flux'].".filename_zip_package='".$fn."'" , $orderby);

while ($r = gaz_dbi_fetch_array($result)) {
?>
                    <div class="row">
                        <div class="col-sm-6 col-md-2 col-lg-2">
							<?php echo $r['filename_ori']; ?>                
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-4">
						<?php echo $script_transl['doc_type_value'][$r['tipdoc']].' n.'.$r['numfat'].'/'.$r['seziva'].'</td><td> prot.'.$r['protoc']; ?>                        
						</div>                    
                        <div class="col-sm-6 col-md-1 col-lg-1">
						<?php echo gaz_format_date($r['datfat']); ?>
						</div>
                        <div class="col-sm-6 col-md-5 col-lg-5">
						<?php echo $r['descri']; ?>
                        </div>
                    </div> <!-- chiude row  -->
<?php
}
?>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="col-sm-3 text-danger text-right"></label>
                        <input type="submit" accesskey="d" name="Delete" class="col-sm-6 bg-danger" value="<?php 
						if ($danger==0){
							echo $script_transl['delete']; 
						} else {
							echo $script_transl['danger_confirm'];
			} ?>
" >                
                    </div>
                </div>
            </div><!-- chiude row  -->
        </div><!-- chiude container  -->
    </div><!-- chiude panel  -->
</form>
<?php
require("../../library/include/footer.php");
?>
