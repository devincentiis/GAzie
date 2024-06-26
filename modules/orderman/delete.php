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
// prevent direct access
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
    $user_error = 'Access denied - not an AJAX request...';
    trigger_error($user_error, E_USER_ERROR);
}
if (isset($_POST['type'])&&isset($_POST['ref'])) {
	require("../../library/include/datlib.inc.php");
	require("../../modules/magazz/lib.function.php");
	$upd_mm = new magazzForm;
	$admin_aziend = checkAdmin();
	switch ($_POST['type']) {
    case "orderman":
			$i=intval($_POST['ref']);
			$id_tesbro=intval($_POST['ref2']);
			$res = gaz_dbi_get_row($gTables['tesbro'],"id_tes",$id_tesbro); // prendo il rigo di tesbro interessato

			// prendo tutti i movimenti di magazzino a cui fa riferimento la produzione
			$what=$gTables['movmag'].".id_mov, ".$gTables['movmag'].".id_lotmag, ".$gTables['movmag'].".operat ";
			$table=$gTables['movmag'];$idord=$i;
			$where="id_orderman = $idord";
			$resmov=gaz_dbi_dyn_query ($what,$table,$where);
			while ($r = gaz_dbi_fetch_array($resmov)) {
				$upd_mm->uploadMag('DEL', 'PRO', '', '', '', '', '', '', '', '', '', '', $r['id_mov']);//cancello i movimenti di magazzino corrispondenti
				gaz_dbi_del_row($gTables['camp_mov_sian'], "id_movmag", $r['id_mov']);// cancello i relativi movimenti SIAN
        if (intval($r['operat'])==1 && intval($r['id_lotmag'])>0){// se aveva creato un lotto lo cancello
          gaz_dbi_del_row($gTables['lotmag'], "id", $r['id_lotmag']);
        }
			}
      if (isset($res)){// se il tipo di produzione prevede un ordine
        if (intval($res['clfoco'])==0) { // se NON è un ordine cliente esistente e quindi fu generato automaticamente da orderman
          gaz_dbi_del_row($gTables['tesbro'], "id_tes", $id_tesbro); // cancello tesbro
          gaz_dbi_del_row($gTables['rigbro'], "id_tes", $id_tesbro); // cancello rigbro
        } else { // se invece è un ordine cliente devo lasciarlo e solo sganciarlo da orderman
          gaz_dbi_query ("UPDATE " . $gTables['tesbro'] . " SET id_orderman = '' WHERE id_tes ='".$id_tesbro."'") ; // sgancio tesbro da orderman
        }
      }
      gaz_dbi_del_row($gTables['orderman'], "id", $i); // cancello orderman/produzione
			// in ogni caso riporto l'auto_increment all'ultimo valore disponibile
			$query="SELECT max(id)+1 AS li FROM ".$gTables['orderman'];
			$last_autincr=gaz_dbi_query($query);
			$li=gaz_dbi_fetch_array($last_autincr);
			$li=(isset($li['id']))?($li['id']+1):1;
			$query="ALTER TABLE ".$gTables['orderman']." AUTO_INCREMENT=".$li;
			gaz_dbi_query($query); // riporto l'auto_increment al primo disponibile per non avere vuoti di numerazione
		break;
		case "luoghi":
			$i=intval($_POST['ref']);
			gaz_dbi_del_row($gTables['campi'], "codice", $i);
		break;
		case "set_new_stato_lavorazione":
			$i=intval($_POST['ref']); // id_orderman
			$s=intval($_POST['new_status']); // id_orderman
      gaz_dbi_put_row($gTables['orderman'], 'id', $i, 'stato_lavorazione', $s);
		break;
	}
}
?>
