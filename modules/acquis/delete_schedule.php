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

if (isset($_GET['partner'])) {
    $paymov->setPartnerTarget(intval($_GET['partner']));
    $paymov->getPartnerStatus(0);
} elseif (isset($_GET['id_tesdoc_ref'])) {
    $paymov->setIdTesdocRef(substr($_GET['id_tesdoc_ref'], 0, 15));
    $paymov->getPartnerStatus(0);
} elseif (isset($_GET['all'])) {
    $paymov->getPartnerStatus($admin_aziend['masfor']);
} else {
    header("Location: " . $_SERVER['HTTP_REFERER']);
}

$paymov->PartnerStatus;
$partner = $anagrafica->getPartner($paymov->target);

if (!isset($_POST['ritorno'])) {
    $form['ritorno'] = $_SERVER['HTTP_REFERER'];
}
if (isset($_POST['Delete'])) {
    foreach ($paymov->PartnerStatus as $k => $v) {
        foreach ($v as $ki => $vi) {
            $ctrl_close_paymov = false;
            if ($vi['expo_day'] <= 0 && round($vi['cl_val'],2) == round($vi['op_val'],2)) {
                $ctrl_close_paymov = true;
            }
        }
        if ($ctrl_close_paymov) { // ma solo le chiuse 
            gaz_dbi_del_row($gTables['paymov'], 'id_tesdoc_ref', $k);
        }
    }
    header("Location: " . $_POST['ritorno']);
    exit;
}

if (isset($_POST['Return'])) {
    header("Location: " . $_POST['ritorno']);
    exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain('delete_schedule');
?>
<form method="POST">
    <input type="hidden" name="ritorno" value="<?php print $form['ritorno']; ?>">
    <div class="text-center bg-danger">
        <p>
            <b>
                <?php echo $script_transl['warning'] . '!!! ' . $script_transl['title']; ?>
            </b> 
        </p>
    </div>
    <div class="panel panel-warning">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 bg-info">
                    <div class="form-group">
                        <label class="col-sm-6 text-default"><?php echo $script_transl['ragsoc']; ?></label>
                        <div class="col-sm-6"><?php echo $partner['descri']; ?></div>                
                    </div>
                </div>
            </div><!-- chiude row  -->
            <?php
            foreach ($paymov->PartnerStatus as $k => $v) {
                foreach ($v as $ki => $vi) {
                    $ctrl_close_paymov = false;
                    if ($vi['expo_day'] <= 0 && round($vi['cl_val'],2) == round($vi['op_val'],2)) {
                        $ctrl_close_paymov = true;
                    }
                }
                if ($ctrl_close_paymov) { // ma solo le chiuse 
                    ?>
                    <div class="row">
                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label for="id_tesdoc_ref" class="col-sm-4 control-label"><?php echo $script_transl['id_tesdoc_ref']; ?></label>
                                <div class="col-sm-8"><?php echo $k; ?></div>                
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label for="descri" class="col-sm-4 control-label"><?php echo $script_transl['descri']; ?></label>
                                <div class="col-sm-8"><?php echo $paymov->docData[$k]['descri'] . ' ' . $paymov->docData[$k]['numdoc'] . '/' . $paymov->docData[$k]['seziva']; ?></div>                
                            </div>
                        </div>                    
                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label for="ragsoc" class="col-sm-4 control-label">del</label>
                                <div class="col-sm-8"><?php echo ' del ' . gaz_format_date($paymov->docData[$k]['datdoc']); ?></div>                
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label for="id_tesdoc_ref" class="col-sm-4 control-label"><?php echo $script_transl['amount']; ?></label>
                                <div class="col-sm-8"><?php echo $paymov->docData[$k]['amount']; ?></div>                
                            </div>
                        </div>
                    </div> <!-- chiude row  -->
                    <?php
                }
            }
            ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="col-sm-3 text-danger text-right"></label>
                        <input type="submit" accesskey="d" name="Delete" class="col-sm-6 bg-danger" value="<?php echo $script_transl['delete']; ?>" >                
                    </div>
                </div>
            </div><!-- chiude row  -->
        </div><!-- chiude container  -->
    </div><!-- chiude panel  -->
</form>
<?php
require("../../library/include/footer.php");
?>
