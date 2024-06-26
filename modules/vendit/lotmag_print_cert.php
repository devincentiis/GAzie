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
require("../../library/include/document.php");

class LotMagData extends DocContabVars {

    function __construct() {
        $this->id_movmag = 0;
    }

    function setMovMag($id) { // in caso di stampa di un certificato specifico scelgo l'id del movimento di magazzino
        $this->id_movmag = $id;
    }

    function getLots() {
        $where = '';
        if ($this->id_movmag > 0) {
            $where .=' AND mm.id_mov = ' . $this->id_movmag;
        }
        $from = $this->gTables[$this->tableName] . ' AS rs
            LEFT JOIN ' . $this->gTables['movmag'] . ' AS mm ON rs.id_mag=mm.id_mov
            LEFT JOIN ' . $this->gTables['lotmag'] . ' AS lm ON mm.id_lotmag=lm.id
            LEFT JOIN ' . $this->gTables['rigdoc'] . ' AS rd ON lm.id_rigdoc=rd.id_rig
            LEFT JOIN ' . $this->gTables['tesdoc'] . ' AS td ON rd.id_tes=td.id_tes';
        $rs_rig = gaz_dbi_dyn_query('rs.*,mm.id_lotmag,lm.*,td.clfoco as supplier ', $from, "rs.id_tes = " . $this->testat . $where, "rs.id_tes DESC, rs.id_rig");
        $results = array();
        while ($rigo = gaz_dbi_fetch_array($rs_rig)) {
            if ($rigo['tiprig'] == 0 && $rigo['id_mag'] > 0) {
                // ritrovo il file relativo al lotto e lo aggiungo alla matrice
                $rigo['file']= $this->azienda['codice'].'/';
                $rigo['ext'] = '';
                $dh = opendir(DATA_DIR.'files/' . $this->azienda['codice']);
                while (false !== ($filename = readdir($dh))) {
                    $fd = pathinfo($filename);
                    if ($fd['filename'] == 'lotmag_' . $rigo['id']) {
                        $rigo['file'] .= $filename;
                        $rigo['ext'] = $fd['extension'];
                    }
                }
                $results[] = $rigo;
            }
        }
        return $results;
    }

}

function createCertificate($testata, $gTables, $id_movmag = 0, $dest = false) {
    $config = new Config;
    $configTemplate = new configTemplate;
    require_once ("../../config/templates" . ($configTemplate->template ? '.' . $configTemplate->template : '') . '/certificate.php');
    $pdf = new Certificate();
    $docVars = new LotMagData();
    $docVars->setData($gTables, $testata, $testata['id_tes'], 'rigdoc');
    $docVars->setMovMag($id_movmag);
    $pdf->setVars($docVars, 'Certificate');
    $pdf->setTesDoc();
    $pdf->setCreator('GAzie - ' . $docVars->intesta1);
    $pdf->setAuthor($docVars->user['user_lastname'] . ' ' . $docVars->user['user_firstname']);
    $pdf->setTitle('Certificates');
    $pdf->setTopMargin(79);
    $pdf->setHeaderMargin(5);
    $pdf->Open();
    $pdf->pageHeader();
    $pdf->compose();
    $doc_name = preg_replace("/[^a-zA-Z0-9]+/", "_", $docVars->intesta1 . '_' . $pdf->tipdoc) . '.pdf';
    if ($dest && $dest == 'E') { // è stata richiesta una e-mail
        $dest = 'S';     // Genero l'output pdf come stringa binaria
        // Costruisco oggetto con tutti i dati del file pdf da allegare
        $content = new StdClass;
        $content->name = $doc_name;
        $content->string = $pdf->Output($doc_name, $dest);
        $content->encoding = "base64";
        $content->mimeType = "application/pdf";
        $gMail = new GAzieMail();
        $gMail->sendMail($docVars->azienda, $docVars->user, $content, $docVars->client);
    } elseif ($dest && $dest == 'X') { // è stata richiesta una stringa da allegare
        $dest = 'S';     // Genero l'output pdf come stringa binaria
        // Costruisco oggetto con tutti i dati del file pdf
        $content->descri = $doc_name;
        $content->string = $pdf->Output($content->descri, $dest);
        $content->mimeType = "PDF";
        return ($content);
    } else { // va all'interno del browser
        $pdf->Output($doc_name);
    }
}

// recupero i dati
if (isset($_GET['id_movmag'])) {   //se viene richiesta la stampa di un solo documento attraverso il suo id_movmag
    $movmag = gaz_dbi_get_row($gTables['movmag'], 'id_mov', intval($_GET['id_movmag']));
    $rigdoc = gaz_dbi_get_row($gTables['rigdoc'], 'id_rig', $movmag['id_rif']);
    $tesdoc = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $rigdoc['id_tes']);
    createCertificate($tesdoc, $gTables, $movmag['id_mov'], false);
} else { // in tutti gli altri casi devo passare l'id della testata del documento
    $tesdoc = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', intval($_GET['id_tesdoc']));
    createCertificate($tesdoc, $gTables, 0, false);
}
?>
