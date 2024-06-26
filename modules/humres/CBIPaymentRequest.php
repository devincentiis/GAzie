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
$admin_aziend=checkAdmin();
require("../../library/include/CbiSepa.inc.php");
$CBIBdyPaymentRequest = new CbiSepa();
$id_tes=intval($_GET['id_tes']);
// riprendo sia la testata del movimento contabile che le partite contenute nel pagamento
$result = gaz_dbi_dyn_query($gTables['tesmov'].'.*, '.$gTables['rigmoc'].'.import, '.$gTables['anagra'].'.ragso1, '.$gTables['anagra'].'.ragso2, '.$gTables['clfoco'].'.iban', $gTables['rigmoc'].' LEFT JOIN '.$gTables['tesmov'].' ON '.$gTables['rigmoc'].'.id_tes = '.$gTables['tesmov'].'.id_tes LEFT JOIN '.$gTables['clfoco'].' ON '.$gTables['rigmoc'].'.codcon = '.$gTables['clfoco'].'.codice LEFT JOIN '.$gTables['anagra'].' ON '.$gTables['clfoco'].'.id_anagra = '.$gTables['anagra'].'.id', $gTables['tesmov'].'.id_tes = '.$id_tes." AND codcon BETWEEN ".$admin_aziend['mas_staff']."000000 AND ".$admin_aziend['mas_staff'].'999999','id_rig');
$d=[];
while($r=gaz_dbi_fetch_array($result)){
	$d[]=array('InstdAmt'=>$r['import'],'Nm'=>trim($r['ragso1'].' '.$r['ragso2']),'IBAN'=>$r['iban'],'Ustrd'=>htmlentities($r['descri'].' del '.gaz_format_date($r['datreg']),ENT_XML1));
}
// riprendo la contropartita della partita dove è indicata la banca (darave='A')
$result = gaz_dbi_dyn_query($gTables['clfoco'].'.codice', $gTables['tesmov'].' LEFT JOIN '.$gTables['rigmoc'].' ON '.$gTables['tesmov'].'.id_tes = '.$gTables['rigmoc'].'.id_tes LEFT JOIN '.$gTables['clfoco'].' ON '.$gTables['rigmoc'].'.codcon = '.$gTables['clfoco'].'.codice LEFT JOIN '.$gTables['banapp'].' ON '.$gTables['clfoco'].'.banapp = '.$gTables['banapp'].'.codice ', $gTables['tesmov'].'.id_tes = '.intval($_GET['id_tes']).' AND '.$gTables['rigmoc'].".darave = 'A'" ,$gTables['tesmov'].'.id_tes',0,1);
$b=gaz_dbi_fetch_array($result);
$h=array('bank'=>$b['codice'],'CtgyPurpCd'=>'SALA');
$CBIBdyPaymentRequest->create_XML_CBIPaymentRequest($gTables,$h,$d);
?>