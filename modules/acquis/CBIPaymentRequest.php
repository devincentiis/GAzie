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
require("../../library/include/CbiSepa.inc.php");
$CBIBdyPaymentRequest = new CbiSepa();

if (isset($_GET['id_rig'])){ // ho id_rig del movimento contabile che ha generato la partita
	// riprendo sia la testata del movimento contabile che le partite contenute nel pagamento
    $result = gaz_dbi_dyn_query($gTables['tesmov'].'.*, '.$gTables['rigmoc'].'.import, '.$gTables['anagra'].'.ragso1, '.$gTables['anagra'].'.ragso2, '.$gTables['clfoco'].'.iban ', $gTables['paymov'].' LEFT JOIN '.$gTables['rigmoc'].' ON '.$gTables['paymov'].'.id_rigmoc_pay = '.$gTables['rigmoc'].'.id_rig LEFT JOIN '.$gTables['tesmov'].' ON '.$gTables['rigmoc'].'.id_tes = '.$gTables['tesmov'].'.id_tes LEFT JOIN '.$gTables['clfoco'].' ON '.$gTables['rigmoc'].'.codcon = '.$gTables['clfoco'].'.codice LEFT JOIN '.$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id', $gTables['paymov'].'.id_rigmoc_pay = '.intval($_GET['id_rig']),'expiry',0,1);
	$r=gaz_dbi_fetch_array($result);
	// riprendo la contropartita della partita dove è indicata la banca (darave='A')
    $result = gaz_dbi_dyn_query($gTables['clfoco'].'.codice', $gTables['tesmov'].' LEFT JOIN '.$gTables['rigmoc'].' ON '.$gTables['tesmov'].'.id_tes = '.$gTables['rigmoc'].'.id_tes LEFT JOIN '.$gTables['clfoco'].' ON '.$gTables['rigmoc'].'.codcon = '.$gTables['clfoco'].'.codice LEFT JOIN '.$gTables['banapp'].' ON '.$gTables['clfoco'].'.banapp = '.$gTables['banapp'].'.codice ', $gTables['tesmov'].'.id_tes = '.$r['id_tes'].' AND '.$gTables['rigmoc'].".darave = 'A'" ,$gTables['tesmov'].'.id_tes',0,1);
	$b=gaz_dbi_fetch_array($result);
	// adesso creo un array con i dati del beneficiario da passare alla funzione 
	$d[0]=array('InstdAmt'=>$r['import'],'Nm'=>trim($r['ragso1'].' '.$r['ragso2']),'IBAN'=>$r['iban'],'Ustrd'=>$r['descri']);
	$h=array('bank'=>$b['codice'],'CtgyPurpCd'=>'SUPP','FileName'=>'Bonifico'.preg_replace("/[^a-zA-Z0-9]/", "",$r['ragso1']).'_'.$r['id_tes']);
	$CBIBdyPaymentRequest->create_XML_CBIPaymentRequest($gTables,$h,$d);
} elseif (isset($_GET['id_tes'])){ // ho id_tes del movimento contabile che ha generato le chiusure di partite
	// riprendo sia la testata del movimento contabile che le partite contenute nel pagamento
    $result = gaz_dbi_dyn_query($gTables['tesmov'].'.*, '.$gTables['rigmoc'].'.import, '.$gTables['anagra'].'.ragso1, '.$gTables['anagra'].'.ragso2, '.$gTables['clfoco'].'.iban, '.$gTables['paymov'].'.id_tesdoc_ref ', $gTables['paymov'].' LEFT JOIN '.$gTables['rigmoc'].' ON '.$gTables['paymov'].'.id_rigmoc_pay = '.$gTables['rigmoc'].'.id_rig LEFT JOIN '.$gTables['tesmov'].' ON '.$gTables['rigmoc'].'.id_tes = '.$gTables['tesmov'].'.id_tes LEFT JOIN '.$gTables['clfoco'].' ON '.$gTables['rigmoc'].'.codcon = '.$gTables['clfoco'].'.codice LEFT JOIN '.$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id', $gTables['tesmov'].'.id_tes = '.intval($_GET['id_tes']),'expiry');
	$d=[];
	while($r=gaz_dbi_fetch_array($result)){
		// riprendo i dati del documento di origine della partita
		$rdoc = gaz_dbi_dyn_query('*', $gTables['paymov'].' LEFT JOIN '.$gTables['rigmoc'].' ON '.$gTables['paymov'].'.id_rigmoc_doc = '.$gTables['rigmoc'].'.id_rig LEFT JOIN '.$gTables['tesmov'].' ON '.$gTables['rigmoc'].'.id_tes = '.$gTables['tesmov'].'.id_tes', $gTables['paymov'].'.id_tesdoc_ref = '.$r['id_tesdoc_ref'].' AND '.$gTables['paymov'].'.id_rigmoc_doc > 0 ','id_tesdoc_ref',0,1);
		$doc=gaz_dbi_fetch_array($rdoc);
		$d[]=array('InstdAmt'=>$r['import'],'Nm'=>trim($r['ragso1'].' '.$r['ragso2']),'IBAN'=>$r['iban'],'Ustrd'=>'N.'.$doc['numdoc'].' del '.gaz_format_date($doc['datdoc']));
	}
	// riprendo la contropartita della partita dove è indicata la banca (darave='A')
	$result = gaz_dbi_dyn_query($gTables['clfoco'].'.codice', $gTables['tesmov'].' LEFT JOIN '.$gTables['rigmoc'].' ON '.$gTables['tesmov'].'.id_tes = '.$gTables['rigmoc'].'.id_tes LEFT JOIN '.$gTables['clfoco'].' ON '.$gTables['rigmoc'].'.codcon = '.$gTables['clfoco'].'.codice LEFT JOIN '.$gTables['banapp'].' ON '.$gTables['clfoco'].'.banapp = '.$gTables['banapp'].'.codice ', $gTables['tesmov'].'.id_tes = '.intval($_GET['id_tes']).' AND '.$gTables['rigmoc'].".darave = 'A'" ,$gTables['tesmov'].'.id_tes',0,1);
	$b=gaz_dbi_fetch_array($result);
	$h=array('bank'=>$b['codice'],'CtgyPurpCd'=>'SUPP');
	$CBIBdyPaymentRequest->create_XML_CBIPaymentRequest($gTables,$h,$d);
}

?>