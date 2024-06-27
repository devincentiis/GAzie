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

class campForm extends GAzieForm {

	// Antonio Germani - Come select selectFromDB ma con in più preleva $key4 da $table2, dove $key3 è uguale a $key2, e lo visualizza nella scelta del select. Cioè nelle scelte del select ci sarà $key e $key4
	function selectFrom2DB($table,$table2,$key3,$key4, $name, $key, $val, $order = false, $empty = false, $bridge = '', $key2 = '', $val_hiddenReq = '', $class = 'FacetSelect', $addOption = null, $style = '', $where = false, $echo=false, $disabled="") {
        global $gTables;
		$acc='';
        $refresh = '';

        if (!$order) {
            $order = $key;
        }

        $query = 'SELECT * FROM `' . $gTables[$table] . '` ';
        if ($where) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY `' . $order . '`';
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
        }
        $acc .= "\t <select $disabled id=\"$name\" name=\"$name\" class=\"$class\" $refresh $style>\n";
        if ($empty) {
            $acc .= "\t\t <option value=\"\"></option>\n";
        }

        $result = gaz_dbi_query($query);
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            if ($r[$key] == $val) {
                $selected = "selected";
            }

			$r2 = gaz_dbi_get_row($gTables[$table2], $key3, $r[$key2]);

            $acc .= "\t\t <option value=\"" . $r[$key] . "\" $selected >";
            if (empty($key2)) {
                $acc .= substr($r[$key], 0, 43) . "</option>\n";
            } else {
                $acc .= substr($r[$key], 0, 28) . $bridge . substr($r2[$key4], 0, 35) . "</option>\n";
            }
        }
        if ($addOption) {
            $acc .= "\t\t <option value=\"" . $addOption['value'] . "\"";
            if ($addOption['value'] == $val) {
                $acc .= " selected ";
            }
            $acc .= ">" . $addOption['descri'] . "</option>\n";
        }
        $acc .= "\t </select>\n";
		if ($echo){
			return $acc;
		} else {
			echo $acc;
		}
    }

}

class silos {

	function getCont($codsil,$codart="", $excluded_movmag = 0){// restituisce la quantità di olio di un recipiente
		global $gTables,$admin_aziend;
		$content=0;
		$orderby=2;
		$limit=0;
		$passo=2000000;
		$where="recip_stocc = '".$codsil."'";

		if (is_array($excluded_movmag)){
		  $add_excl="";
		  foreach($excluded_movmag as $each){
			$add_excl.= " AND ".$gTables['movmag'].".id_mov <> ".intval($each);
		  }
		  $where=$where.$add_excl;
		}elseif($excluded_movmag <> 0){
			$where=$where." AND ".$gTables['movmag'].".id_mov <> ".$excluded_movmag;
		}

		if (strlen($codart)>0){
		  $where=$where." AND artico = '". $codart ."'";
		}
			$what=	$gTables['movmag'].".operat, ".$gTables['movmag'].".quanti, ".$gTables['movmag'].".id_orderman, ".
					$gTables['camp_mov_sian'].".*, ".$gTables['camp_artico'].".confezione ";
			$groupby= "";
			$table=$gTables['camp_mov_sian']." LEFT JOIN ".$gTables['movmag']." ON ".$gTables['camp_mov_sian'].".id_movmag = ".$gTables['movmag'].".id_mov
      LEFT JOIN ".$gTables['camp_artico']." ON ".$gTables['camp_artico'].".codice = ".$gTables['movmag'].".artico
			";
			$ressilos=gaz_dbi_dyn_query ($what,$table,$where,$orderby,$limit,$passo,$groupby);
			while ($r = gaz_dbi_fetch_array($ressilos)) {
				if ($r['confezione']==0){
					$content=$content+($r['quanti']*$r['operat']);
				}
			}
			$content=number_format ($content,6);

			return $content ;
	}

	function getMovContainer($codsil,$codart="", $excluded_movmag = 0){// restituisce i movimenti di olio e lotti di un contenitore olio
		global $gTables,$admin_aziend;
		$content=[];
		$q=0;
		$where="recip_stocc = '".$codsil."'";

		if (is_array($excluded_movmag)){
		  $add_excl="";
		  foreach($excluded_movmag as $each){
			$add_excl.= " AND ".$gTables['movmag'].".id_mov <> ".intval($each);
		  }
		  $where=$where.$add_excl;
		}elseif($excluded_movmag <> 0){
			$where=$where." AND ".$gTables['movmag'].".id_mov <> ".$excluded_movmag;
		}

		if (strlen($codart)>0){
		  $where=$where." AND artico = '". $codart ."'";
		}
			$what=	$gTables['movmag'].".operat, ".$gTables['movmag'].".id_mov, ".$gTables['movmag'].".id_lotmag, ".$gTables['movmag'].".quanti, ".$gTables['movmag'].".id_orderman, ".$gTables['camp_mov_sian'].".*, ".$gTables['camp_artico'].".confezione, ".$gTables['artico'].".codice, ".$gTables['artico'].".descri, ".$gTables['artico'].".unimis";
			$table=$gTables['camp_mov_sian']." LEFT JOIN ".$gTables['movmag']." ON ".$gTables['camp_mov_sian'].".id_movmag = ".$gTables['movmag'].".id_mov
											LEFT JOIN ".$gTables['camp_artico']." ON ".$gTables['movmag'].".artico = ".$gTables['camp_artico'].".codice
											LEFT JOIN ".$gTables['artico']." ON ".$gTables['movmag'].".artico = ".$gTables['artico'].".codice";
			$ressilos=gaz_dbi_dyn_query ($what,$table,$where,$gTables['movmag'].'.datreg');
			while ($r = gaz_dbi_fetch_array($ressilos)) {
				if ($r['confezione']==0 && $r['id_mov']>=1 ){
          $val=$r['quanti']*$r['operat'];
					$q = number_format($q + $val,6);
          $idlot=(intval($r['id_lotmag'])>0)?"-id lotto: ".$r['id_lotmag']:'';
					$content[]=['val'=>$r['quanti']*$r['operat'],'id'=>$r['id_mov'],'cod'=>$r['codice'],'des'=>$r['descri'],'um'=>$r['unimis'],'pro'=>$q,'id_lot'=>$idlot];
				}
			}
			return $content ;
	}

	function getLotRecip($codsil,$codart=""){// funzione per trovare l'ID dell'ultimo lotto inserito nel recipiente di stoccaggio
		$id_lotma=false;
		global $gTables,$admin_aziend;
		$sil = new lotmag();
		$what=$gTables['movmag'].".id_lotmag, ".$gTables['movmag'].".id_mov, ".$gTables['movmag'].".artico ";
		$table=$gTables['movmag']." LEFT JOIN ".$gTables['camp_mov_sian']." ON ".$gTables['movmag'].".id_mov = ".$gTables['camp_mov_sian'].".id_movmag";
		$where="recip_stocc = '".$codsil."'";
		if (strlen($codart)>0){
			$where = $where." AND artico = '".$codart."'";
		}
		$orderby="id_mov DESC";
		$groupby= "";
		$passo=2000000;
		$limit=0;
		$lastmovmag=gaz_dbi_dyn_query ($what,$table,$where,$orderby,$limit,$passo,$groupby);

		while ($r = gaz_dbi_fetch_array($lastmovmag)) {
			$id_lotma = $r['id_lotmag'];
			$cont= $sil -> dispLotID ($r['artico'], $r['id_lotmag']);
			if ($cont>0){
				break;
			}
		}
		$identif=gaz_dbi_get_row($gTables['lotmag'], "id", $id_lotma);
		$identifier=(isset($identif))?$identif['identifier']:'';
		return array($id_lotma,$identifier) ;
	}

  function getSilosArtico($codsil, $excluded_movmag=0){// restituisce i codici articoli presenti nel silos
    global $gTables;
    $latestEmpty= $this -> getLatestEmptySil($codsil, $excluded_movmag);
    //echo "<pre>latest:",print_r($latestEmpty);
    $date=(isset($latestEmpty['datdoc']))?$latestEmpty['datdoc']:'';
    $id_mov=(isset($latestEmpty['id_mov']))?$latestEmpty['id_mov']:'';
    $select=$gTables['movmag'].".artico";
    $table=$gTables['movmag']."
    LEFT JOIN ".$gTables['camp_mov_sian']." ON ".$gTables['movmag'].".id_mov = ".$gTables['camp_mov_sian'].".id_movmag LEFT JOIN ".$gTables['camp_artico']." ON ".$gTables['camp_artico'].".codice = artico";
    $where= $gTables['camp_mov_sian'].".recip_stocc = '".$codsil."' AND ".$gTables['camp_artico'].".confezione = 0";
    if (strlen($date)>0){
      $where = $where." AND (datdoc > '".$date."' OR(datdoc = '".$date."' AND id_mov > ".$id_mov."))";
    }
    $resmovs=gaz_dbi_dyn_query ($select,$table,$where);
    $result = array();
    foreach($resmovs as $res){
      array_push($result,$res['artico']);
    }
    return array_unique($result);
  }

	function selectSilos($name, $key, $val, $order = false, $empty = false, $key2 = '', $val_hiddenReq = '', $class = 'FacetSelect', $addOption = null, $style = '', $where = false, $echo=false, $codart="", $excluded_movmag=0) {
        global $gTables;
        $campsilos = new silos();
        $acc='';
        $refresh = '';
        if (!$order) {
            $order = $key;
        }
        $query = 'SELECT * FROM `' . $gTables['camp_recip_stocc'] . '` ';
        if ($where) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY `' . $order . '`';
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
        }
        $acc .= "\t <select id=\"$name\" name=\"$name\" class=\"$class\" $refresh $style>\n";
        if ($empty) {
            $acc .= "\t\t <option value=\"\"></option>\n";
        }
        $result = gaz_dbi_query($query);
        while ($r = gaz_dbi_fetch_array($result)) {
            $ok="";
            if (strlen($codart)>0){// se è stato inviato un codice articolo, controllo che sia presente nel silos
              // vedo la data dell'ultimo svuotamento totale e il relativo idmovmag
              $resmovs=$this -> getSilosArtico($r['cod_silos'], $excluded_movmag);
              foreach ($resmovs as $res) {
                if ($res==$codart){ // se è presente l'articolo nel silos do l'ok
                  $ok="ok";break;
                }
              }
            }
            if (($ok=="ok" && strlen($codart)>0) || $codart==""){// se è presente lo visualizzo nella select
              $lot = $campsilos->getLotRecip($r[$key],$codart);
              $cont = $campsilos->getCont($r[$key]);
              $selected = '';$addlot="";
              if ($r[$key] == $val) {
                  $selected = "selected";
              }
              if(strlen($lot[1])>0){
                $addlot="-Lotto: " . $lot[1];
              }
              $acc .= "\t\t <option value=\"" . $r[$key] . "\" $selected >";
              if (empty($key2)) {
                  $acc .= substr($r[$key], 0, 43) . "</option>\n";
              } else {
                  $acc .= substr($r[$key], 0, 28) . "-" . substr($r['nome'], 0, 20) .  $addlot . "-Cont.Kg: ". $cont ."</option>\n";
              }
            }
        }
        if ($addOption) {
            $acc .= "\t\t <option value=\"" . $addOption['value'] . "\"";
            if ($addOption['value'] == $val) {
                $acc .= " selected ";
            }
            $acc .= ">" . $addOption['descri'] . "</option>\n";
        }
        $acc .= "\t </select>\n";
		if ($echo){
			return $acc;
		} else {
			echo $acc;
		}
    }

	function getLatestEmptySil($codsil, $excluded_movmag=0){// funzione per trovare la data più recente dell'ultimo svuotamento totale del silos/recipiente di stoccaggio
	// se trovato il punto zero, restituisce un array: datdoc (la data dello zero) id_mov (id magazzino del movimento zero) RunningTotal (valore numerico zero)
		global $gTables,$admin_aziend;
    $where="";
    if (is_array($excluded_movmag)){
		  $add_excl="";
		  foreach($excluded_movmag as $each){
			$add_excl.= " AND ".$gTables['movmag'].".id_mov <> ".intval($each);
		  }
		  $where .= $add_excl;
		}elseif($excluded_movmag <> 0){
			$where .= " AND ".$gTables['movmag'].".id_mov <> ".$excluded_movmag;
		}

		$query ="
		SELECT datdoc, id_mov, quanti, operat
		FROM ".$gTables['movmag']."
		LEFT JOIN ".$gTables['camp_mov_sian']." ON ".$gTables['camp_mov_sian'].".id_movmag = id_mov
		LEFT JOIN ".$gTables['camp_artico']." ON ".$gTables['camp_artico'].".codice = artico
		WHERE ".$gTables['camp_mov_sian'].".recip_stocc = '".$codsil."' AND ".$gTables['camp_artico'].".confezione = 0 ".$where."	ORDER BY datdoc ASC, id_mov ASC
		";

		$res = gaz_dbi_query($query);
		$sum=0;$zeroday=array();
		foreach ($res as $r){
			$sum = number_format($sum,8) + ($r['quanti']*$r['operat']);
			if ($sum == 0){
				$zeroday['id_mov']=$r['id_mov'];
				$zeroday['datdoc']=$r['datdoc'];
			}
		}
		return $zeroday;
	}

	function getContentSil($codsil,$date="",$id_mov=0,$excluded_movmag = 0){// funzione per trovare il contenuto in lotti e varietà dalla data dell'ultimo svuotamento totale di un silos (id_mov è l'ultimo id da escludere nella stessa data)

		if ($date==""){
			$latestEmpty= $this -> getLatestEmptySil($codsil,$excluded_movmag);
			$date=(isset($latestEmpty['datdoc']))?$latestEmpty['datdoc']:'';
			$id_mov=(isset($latestEmpty['id_mov']))?$latestEmpty['id_mov']:'';
		}

		global $gTables,$admin_aziend;
		$sil = new lotmag();
		$select=$gTables['movmag'].".id_lotmag, ".$gTables['artico'].".quality, ".$gTables['movmag'].".artico, ".$gTables['movmag'].".id_mov, ".$gTables['movmag'].".datdoc, ".$gTables['movmag'].".quanti, ".$gTables['movmag'].".operat";
		$table=$gTables['movmag']."
		LEFT JOIN ".$gTables['camp_mov_sian']." ON ".$gTables['movmag'].".id_mov = ".$gTables['camp_mov_sian'].".id_movmag
    LEFT JOIN ".$gTables['artico']." ON ".$gTables['movmag'].".artico = ".$gTables['artico'].".codice
    LEFT JOIN ".$gTables['camp_artico']." ON ".$gTables['camp_artico'].".codice = ".$gTables['artico'].".codice
		";
		$where= $gTables['camp_mov_sian'].".recip_stocc = '".$codsil."' AND ".$gTables['camp_artico'].".confezione = 0";
		if (is_array($excluded_movmag)){
		  $add_excl="";
		  foreach($excluded_movmag as $each){
			$add_excl.= " AND ".$gTables['movmag'].".id_mov <> ".intval($each);
		  }
		  $where=$where.$add_excl;
		}elseif($excluded_movmag <> 0){
			$where=$where." AND ".$gTables['movmag'].".id_mov <> ".$excluded_movmag;
		}

		if (strlen($date)>0){
			$where = $where." AND (datdoc > '".$date."' OR(datdoc = '".$date."' AND id_mov > ".$id_mov."))";
		}
		$orderby="datdoc DESC, id_mov DESC";
		$groupby= "";
		$passo=2000000;
		$limit=0;
		$resmovs=gaz_dbi_dyn_query ($select,$table,$where,$orderby,$limit,$passo,$groupby);// ho trovato tutti i movimenti interessati
		$count=array();
		$var_dichiarabili="";
		$key="id_lotti"; // chiave per il raggruppamento per lotto
		$key2="varieta"; // chiave per il raggruppamento per varietà
		$count[$key]['totale']=0;$count[$key2]['totale']=0; // azzero i totali
		foreach ($resmovs as $res) { // procedo al raggruppamento e conteggio
			//echo "<pre>",print_r($res);
			if( !isset($count[$key][$res['id_lotmag']]) ){ // se la chiave lotto ancora non c'è nell'array
				// Aggiungo la chiave con il rispettivo valore iniziale
				$count[$key][$res['id_lotmag']] = number_format(($res['quanti']*$res['operat']),8);
			} else {
				// Altrimenti, aggiorno il valore della chiave
				$count[$key][$res['id_lotmag']] += number_format(($res['quanti']*$res['operat']),8);
			}

			if( !isset($count[$key2][$res['quality']]) ){ // se la chiave varietà ancora non c'è nell'array
				// Aggiungo la chiave con il rispettivo valore iniziale
				if (strlen($res['quality'])<3){// basta una sola partita senza varietà per bloccare la classificazione varietale del silos
					$var_dichiarabili="NO";// varietà non dichiarabili in quanto è presente una partita anonima
				}
				$count[$key2][$res['quality']] = number_format(($res['quanti']*$res['operat']),8);
			} else {
				// Altrimenti, aggiorno il valore della chiave
				$count[$key2][$res['quality']]+= number_format(($res['quanti']*$res['operat']),8);
			}
		}
		$count[$key]['totale']= number_format (array_sum($count[$key]),8); // il totale dei lotti

		$count[$key2]['totale']= number_format (array_sum($count[$key2]),8); // il totale delle varietà

		// i valori zero o, peggio, negativi sono da escludere
		$count[$key] = array_filter($count[$key],function($var){return($var >= 0.00000001);});
		$count[$key2] = array_filter($count[$key2],function($var){return($var > 0.00000001);});
		if ($var_dichiarabili=="NO"){// se le varietà non sono dichiarabili per contaminazione con partita anonima
			$totale= isset($count[$key2]['totale'])?$count[$key2]['totale']:0; // memorizzo il totale delle varietà
			$count[$key2]=[];//azzero l'array delle varietà
			$count[$key2]['totale']=$totale;// reimposto solo la quantità totale nell'array
		}

		arsort($count[$key2]);

		//restituisce array['lotti](totale=>qta, idlotto=>qta, id lotto=>qta, etc) e array['varieta'](totale=>qta, varieta=>qta, varieta=>qta, etc) Le varietà sono elencate in ordine descrescente in base al valore della quantità.
		return $count;
	}
}

// converte da ore decimali a hh:mm:ss - Es. da 5.75 a 05:45:00
function convertTime($h_dec){
    // start by converting to seconds
    $seconds = ($h_dec * 3600);
    // we're given hours, so let's get those the easy way
    $hours = floor($h_dec);
    // since we've "calculated" hours, let's remove them from the seconds variable
    $seconds -= $hours * 3600;
    // calculate minutes left
    $minutes = floor($seconds / 60);
    // remove those from seconds as well
    $seconds -= $minutes * 60;
	if (ceil($seconds) == 60){
		$minutes++;
		$seconds=0;
	}
    // return the time formatted HH:MM:SS
    //return lz($hours).":".lz($minutes).":".lz($seconds);
	// return the time formatted HH:MM
    return lz($hours).":".lz($minutes);
}
// lz = leading zero
function lz($num)
{
    return (strlen($num) < 2) ? "0{$num}" : $num;
}
// FALSE converte da hh:mm:ss a secondi - Es. da 05:45:00 a 20700. Oppure TRUE da hh:mm:ss in ore decimali - Es. da 05:45:00 a 5.75
function convertHours($time,$dec = FALSE){
	if ($dec == 0){
		$dec=1;
	} else {
		$dec=3600;
	}
	$parsed = date_parse($time);
	$seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
	return $seconds/$dec;
}
function ContTratt($artico,$idProd=""){// restituisce il numero di trattamenti
		global $gTables,$admin_aziend;
		$year = date("Y");
		$where=" WHERE artico = '".$artico."' AND tipdoc ='CAM' AND operat = '-1'";
		if ($idProd>0){
			$where .=" AND id_orderman = ". $idProd;
		} else {
			$where .=" AND SUBSTRING_INDEX(datdoc, '-', 1) = ". $year;
		}
		$query = 'SELECT * FROM `' . $gTables['movmag'] . '` '. $where;
		//echo $query;
		$res=gaz_dbi_query ($query);
		return $res->num_rows;
}
?>
