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

$admin_aziend=checkAdmin();
require("../../library/include/document.php");

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

if (isset($_GET['id_tes'])){   //se viene richiesta la stampa di un solo documento attraverso il suo id_tes
	$id_testata = intval($_GET['id_tes']);
	$testata = gaz_dbi_get_row($gTables['tesdoc'], 'id_tes', $id_testata);
	if (!empty($_GET['template'])){
	  $template = substr($_GET['template'],0,25);
	} elseif(!empty($testata['template']))  {
	  $template = $testata['template'];
	} else {
	  $template = 'FatturaAcquisto';
	}
	if (($testata['ddt_type']<>"T" && $testata['ddt_type']<>"L") || $template=="DDT"){
		if (isset($_GET['dest']) && $_GET['dest'] == 'E') { // se l'utente vuole inviare una mail
			createDocument($testata, $template, $gTables, 'rigdoc', 'E');
		} else {
			createDocument($testata, $template, $gTables);
		}
	} else {
		$lang = "";
		$testate= gaz_dbi_dyn_query("*", $gTables['tesdoc'], "YEAR(datreg) = '".substr($testata['datreg'],0,4)."' AND (ddt_type = 'T' OR ddt_type = 'L') AND protoc = '{$testata['protoc']}'","id_tes ASC");

		// createDocument($testata, $template, $gTables);
		createInvoiceACQFromDDT($testate, $gTables, false, $lang);
	}


}elseif (isset($_GET['td']) and ($_GET['td'] <= 3)) {  //se viene richiesta la stampa di fattura/e differita/e appartenenti ad un periodo
   if (!isset($_GET['pi'])) {
     ?>
     <script>
        alert("Non posso stampare, manca PI");
        tornaPaginaPrecedente();
        </script>
        <?php
        header("Location: report_docacq.php");
        exit;
    }
    if (!empty($_GET['template'])){
      $template = substr($_GET['template'],0,25);
    } elseif(!empty($testata['template']))  {
      $template = $testata['template'];
    } else {
      $template = 'FatturaAcquisto';
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
	 switch ($_GET['td']) {  
		case 0:  //fattura acquisto
			$td  = 'AF_';
			break;
		case 1:  //fattura acquisto
			$td  = 'AFA';
			break;
		case 2:  //fattura immediata differita con ddt
			$td = 'AFT';
			break;
		case 3: //fattura 
			$td = 'AFC';
			break;      
   }
    $data_inizio = substr($_GET['di'], 0, 4) . '-' . substr($_GET['di'], 4, 2) . '-' . substr($_GET['di'], 6, 2);
$data_fine   = substr($_GET['df'], 0, 4)   . '-' . substr($_GET['df'], 4, 2)   . '-' . substr($_GET['df'], 6, 2);

    $where = "tipdoc LIKE '". $td ."' AND seziva = "
            . intval($_GET['si'])
            . " AND datfat BETWEEN '"
            . $data_inizio
            . "' AND '"
            . $data_fine           
            . "' AND protoc BETWEEN "
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
            createMultiDocument($testate, $template, $gTables);
        }
    } else {
        ?>
        <script>
        alert("Nessun documento da stampare");
        tornaPaginaPrecedente();
        </script>
        <?php
    }
}else { // in tutti gli altri casi
    echo "NB: da sviluppare se serve.";
	die;
}
?>
