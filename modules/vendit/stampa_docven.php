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
if (!ini_get('safe_mode')) { //se me lo posso permettere...
    ini_set('memory_limit', '128M');
    gaz_set_time_limit(0);
}

function get_template_lang($clfoco) {
    global $gTables;

    $lang = false;
	$rs_customer_language = gaz_dbi_dyn_query("sef",
	$gTables['clfoco']." LEFT JOIN ".$gTables['anagra']." ON ".$gTables['clfoco'].".id_anagra = ".$gTables['anagra'].".id
	LEFT JOIN ".$gTables['languages']." ON ".$gTables['anagra'].".id_language = ".$gTables['languages'].".lang_id",$gTables['clfoco'].".codice = ".$clfoco);
    if ($rs_customer_language->num_rows > 0) {
        $customer_language = gaz_dbi_fetch_array($rs_customer_language)['sef'];
		if (!empty($customer_language)) {
			switch ($customer_language) {
				case 'en':
					$lang = 'english';
					break;
				case 'es':
					$lang = 'espanol';
					break;
			}
		}
	}
    return $lang;
}

require("../../library/include/document.php");
// recupero i dati
if (isset($_GET['id_tes'])) {   //se viene richiesta la stampa di un solo documento attraverso il suo id_tes
    $id_testata = intval($_GET['id_tes']);
    $testata = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $id_testata);

    // Controllo esistenza documento
    if ( ! $testata ) {
//        alert("Nessun documento da stampare");
        header("Location: report_docven.php");
        exit;
    }

    if (!empty($_GET['template'])) {
        $template = substr($_GET['template'], 0, 25);
    } elseif (!empty($testata['template'])) {
        $template = $testata['template'];
    } else {
        $template = 'FatturaImmediata';
    }

    $lang = get_template_lang($testata['clfoco']);
    if (isset($_GET['dest'])) { // se l'utente vuole inviare una mail
      if ($_GET['dest'] == 'E'){
        createDocument($testata, $template, $gTables, 'rigdoc', 'E', $lang, false);
      }else{
        $email=filter_var($_GET['dest'], FILTER_VALIDATE_EMAIL);
        $r=gaz_dbi_put_row($gTables['tesdoc'], 'id_tes', $testata['id_tes'], 'email',$email);
        createDocument($testata, $template, $gTables, 'rigdoc', $email, $lang, false);
      }
    } else {
        createDocument($testata, $template, $gTables, 'rigdoc', false, $lang);
    }
} elseif (isset($_GET['td']) and $_GET['td'] == 2) {  //se viene richiesta la stampa di fattura/e differita/e appartenenti ad un periodo
    if (!isset($_GET['pi'])) {
        header("Location: report_docven.php");
        exit;
    }
    if (!isset($_GET['pf'])) {
        $_GET['pf'] = intval($_GET['pi']);
    }
    if (!isset($_GET['ni'])) {
        $_GET['ni'] = 1;
    }
    if (!isset($_GET['nf'])) {
        $_GET['nf'] = 999999999;
    }
    if (!isset($_GET['di'])) {
        $_GET['di'] = 20050101;
    }
    if (!isset($_GET['df'])) {
        $_GET['df'] = 20991231;
    }
    if (!isset($_GET['cl']) or ( empty($_GET['cl']))) {
        $cliente = '';
    } else {
        $cliente = ' AND clfoco = ' . intval($_GET['cl']);
    }
    if (!isset($_GET['ag']) or ( empty($_GET['ag']))) {   // selezione agente
        $agente = '';
    } else {
        $agente = ' AND B.id_agente = ' . intval($_GET['ag']);
    }
    $invioPerEmail = 0;
    if (!isset($_GET['ts']) || empty($_GET['ts']) || $_GET['ts']==3 ) { // se non ho scelto di inviare tramite e-mail
        $fattEmail = '';
    } else {
        $invioPerEmail = ($_GET['ts'] == 1 ? 0 : 1);
        $tipoInvio = $_GET['ts'];
        switch ($tipoInvio) {
            case 1:
                $fattEmail = " AND C.fatt_email = 0";
                break;
            default:
                $fattEmail = " AND (C.fatt_email = 1 or C.fatt_email = 3)";
        }
    }
    //recupero i documenti da stampare
    $where = "tipdoc = 'FAD' AND seziva = "
            . intval($_GET['si'])
            . " AND datfat BETWEEN '"
            . substr($_GET['di'], 0, 10)
            . "' AND '"
            . substr($_GET['df'], 0, 10)
            . "' AND numfat BETWEEN "
            . intval($_GET['ni'])
            . " AND "
            . intval($_GET['nf'])
            . " AND protoc BETWEEN "
            . intval($_GET['pi'])
            . " AND "
            . intval($_GET['pf'])
            . $cliente
            . $agente
            . $fattEmail;
    ;
    //recupero i documenti da stampare
    $from = $gTables['tesdoc'] . " A left join " . $gTables['clfoco'] . " B on A.clfoco=B.codice " .
            "left join " . $gTables['anagra'] . " C on B.id_anagra=C.id ";
    $orderby = "datfat ASC, protoc ASC, id_tes ASC";
    $clientiRS = gaz_dbi_dyn_query("distinct(A.clfoco) as clfoco", $from, $where);
    $numRecord = $clientiRS->num_rows;

    if ($numRecord > 0) {
        if ($invioPerEmail || isset($_GET['dest'])) {
            $arrayClienti = gaz_dbi_fetch_all($clientiRS);
            foreach ($arrayClienti as $cliente) {
                $clfoco = $cliente['clfoco'];
                $testate = gaz_dbi_dyn_query("A.*", $from, $where . " and A.clfoco=$clfoco", $orderby);
                $lang = get_template_lang($clfoco);
                createInvoiceFromDDT($testate, $gTables, $_GET['dest'], $lang);
                if ($_GET['dest'] !== 'E'){// se ho inviato ad indirizzo diverso da quello di default
                  $testate = gaz_dbi_dyn_query("A.*", $from, $where . " and A.clfoco=$clfoco", $orderby);
                  while ($tesdoc = gaz_dbi_fetch_array($testate)) {// memorizzo in ogni documento l'indirizzo email a cui ho inviato
                    $email=filter_var($_GET['dest'], FILTER_VALIDATE_EMAIL);
                    $r=gaz_dbi_put_row($gTables['tesdoc'], 'id_tes', $tesdoc['id_tes'], 'email',$email);
                  }
                }die;
            }
        } else {
            $testate = gaz_dbi_dyn_query("A.*", $from, $where, $orderby);
            $arrayClienti = gaz_dbi_fetch_array($clientiRS);
            $lang = get_template_lang($arrayClienti['clfoco']);
            createInvoiceFromDDT($testate, $gTables, false, $lang);
        }
    } else {
        alert("Nessun documento da stampare");
        tornaPaginaPrecedente();
    }
} else { // in tutti gli altri casi
    if (!isset($_GET['pi']) or ! isset($_GET['td'])) {
        header("Location: report_docven.php");
        exit;
    }
    if (!isset($_GET['pf'])) {
        $_GET['pf'] = intval($_GET['pi']);
    }
    $date_name = 'datfat';
    $num_name = 'numfat';
    $template = 'FatturaSemplice';
    $orderby = 'datfat ASC, protoc ASC, id_tes ASC';
    switch ($_GET['td']) {
        case 1:  //ddt
            $date_name = 'datemi';
            $num_name = 'numdoc';
            $_GET['pi'] = 0;
            $_GET['pf'] = 999999999;
            $where = "(tipdoc like 'DD%' OR tipdoc = 'FAD') ";
            $template = 'DDT';
            $orderby = 'datemi ASC, numdoc ASC, id_tes ASC';
            break;
        case 2:  //fattura differita
            $where = "tipdoc = 'FAD'";
            break;
        case 3:  //fattura immediata accompagnatoria
            $where = "tipdoc = 'FAI' AND template = 'FatturaImmediata'";
            $template = 'FatturaImmediata';
            break;
        case 4: //fattura immediata semplice
            $where = "tipdoc = 'FAI' AND template <> 'FatturaImmediata'";
            break;
        case 5: //nota di credito
            $where = "tipdoc = 'FNC'";
            break;
        case 6: //nota di debito
            $where = "tipdoc = 'FND'";
            break;
        case 7: //nota di debito
            $where = "tipdoc = 'VRI'";
            $template = 'Received';
            break;
        case 8: //cmr
            $where = "tipdoc = 'CMR'";
            $template = "CMR";
            break;
        case 10: //corrispettivi
            $date_name = 'datemi';
            $num_name = 'numdoc';
            $_GET['pi'] = 0;
            $_GET['pf'] = 999999999;
            $where = "tipdoc = 'VCO'";
            $template = 'Scontrino';
            break;
    }
    if (!isset($_GET['ni'])) {
        $_GET['ni'] = 1;
    }
    if (!isset($_GET['nf'])) {
        $_GET['nf'] = 999999999;
    }
    if (!isset($_GET['di'])) {
        $_GET['di'] = 20050101;
    }
    if (!isset($_GET['df'])) {
        $_GET['df'] = 20991231;
    }
    if (!isset($_GET['cl']) or ( empty($_GET['cl']))) {
        $cliente = '';
    } else {
        $cliente = ' AND clfoco = ' . intval($_GET['cl']);
    }

    if (!isset($_GET['ag']) or ( empty($_GET['ag']))) {   // selezione agente
        $agente = '';
    } else {
        $agente = ' AND B.id_agente = ' . intval($_GET['ag']);
    }
    $invioPerEmail = 0;
    if (!isset($_GET['ts']) || empty($_GET['ts']) || $_GET['ts']==3 ) { // se non ho scelto di inviare tramite e-mail
        $fattEmail = '';
    } else {
        $invioPerEmail = ($_GET['ts'] == 1 ? 0 : 1);
        $tipoInvio = $_GET['ts'];
        switch ($tipoInvio) {
            case 1:
                $fattEmail = " AND C.fatt_email = 0";
                break;
            default:
                $fattEmail = " AND (C.fatt_email = 1 or C.fatt_email = 3)";
        }
    }
    //recupero i documenti da stampare
    $where = $where
            . " AND seziva = "
            . intval($_GET['si'])
            . " AND $date_name BETWEEN '" . substr($_GET['di'], 0, 10) . "' AND '" . substr($_GET['df'], 0, 10)
            . "' AND $num_name BETWEEN " . intval($_GET['ni']) . " AND " . intval($_GET['nf'])
            . " AND protoc BETWEEN " . intval($_GET['pi']) . " AND " . intval($_GET['pf'])
            . $cliente . $agente . $fattEmail;
    $from = $gTables['tesdoc'] . " A left join " . $gTables['clfoco'] . " B on A.clfoco=B.codice
                                     left join " . $gTables['anagra'] . " C on B.id_anagra=C.id  ";
    //recupero i documenti da stampare
    $testate = gaz_dbi_dyn_query("A.*", $from, $where, $orderby);
    if ($testate->num_rows > 0) {
      if ($invioPerEmail) {
        foreach ($testate as $doc) {
          $testata = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $doc['id_tes']);
          $lang = get_template_lang($testata['clfoco']);
          createDocument($testata, $template, $gTables, 'rigdoc', 'E', $lang, false);
        }
      } else {
        $dest = ($_GET['ts']==3?'Z':false); // controllo se ho richiesto di zippare in un pacchetto di singoli file
        createMultiDocument($testate, $template, $gTables, $dest);
      }
    } else {
        alert("Nessun documento da stampare");
        tornaPaginaPrecedente();
    }
}
?>
