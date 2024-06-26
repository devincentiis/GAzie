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

// questa funzione  serve solo per mantenere la compatibilit� con le versioni precedenti NON USARE
// sui nuovi script usare direttamente la funzione CalcExpiry contenuta nella classe Expiry in expiry_calc.php
//
require_once("../../library/include/expiry_calc.php");

function CalcolaScadenze($totpag, $giorno, $mese, $anno, $tipdec, $giodec, $numrat, $tiprat, $mesesc, $giosuc) {
   $numrat=max($numrat,1); // non posso avere meno di 1 rata
   $ex = new Expiry;
   $rs_array = $ex->CalcExpiry($totpag, $anno . '-' . $mese . '-' . $giorno, $tipdec, $giodec, $numrat, $tiprat, $mesesc, $giosuc);
   $acc = array();
   foreach ($rs_array as $k => $v) {
      $k--;
      $acc['import'][$k] = $v['amount'];
      $acc['giorno'][$k] = substr($v['date'], 8, 2);
      $acc['mese'][$k] = substr($v['date'], 5, 2);
      $acc['anno'][$k] = substr($v['date'], 0, 4);
   }
   return $acc;
}

// fine vecchia funzione DEPRECABILE


function createArrayCrediti($result, $pagame, $utsval) {
   // la variabile $result deve contenere tutti i movimenti tranne quelli di chiusura
   // scopo di questa funzione e' quella di creare l'array dei CREDITI vantati verso il cliente in base
   // alla condizione di pagamento passata tramite la seconda variabile.

   global $gTables;

   $epsilon = 0.000001;    // Massima differenza tra 2 float
   // http://www.php.net/manual/en/language.types.float.php
   // http://stackoverflow.com/questions/3148937/compare-floats-in-php

   $parzi = 0.00;
   $progr = 0.00;
   $righiCrediti = array();
   $rigo = 0;
   $ctrlapertura = ""; //per prendere in considerazione solo il primo movimento d'apertura
   while ($movimenti = gaz_dbi_fetch_array($result)) {
      if ($movimenti["caucon"] != 'APE' or empty($ctrlapertura)) { //questo per eliminare tutti i movimenti di apertura successivi il primo
         if ($movimenti["darave"] == 'A') {
            if ($progr >= 0) {
               $progr += $movimenti["import"];
            }
            if ($progr < 0 and ( $progr > -$movimenti["import"] or abs($progr + $movimenti['import']) < $epsilon)) {
               $progr += $movimenti["import"];
               //questo per eliminare tutti gli array
               $righiCrediti = array();
               $rigo = 0;
            }
            if ($progr < 0 and $progr < -$movimenti["import"] and abs($progr + $movimenti['import']) >= $epsilon) {
               $parzi = $movimenti["import"];
               $progr += $movimenti["import"];
               foreach ($righiCrediti as $key => $value) {
                  if ($parzi >= $value['prelis']) {
                     array_shift($righiCrediti);
                     $rigo--;
                     $parzi -= $value['prelis'];
                  } elseif ($parzi > 0) {
                     $righiCrediti[0]['prelis'] = $value['prelis'] - $parzi; //questo elimina il resto dell'elemento
                     $parzi = 0.00;
                  }
               }
            }
         } else {

            $tesdoc = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $movimenti['id_doc']);
            if ($tesdoc['pagame'] != $pagame['codice']) {
               $pagame_tesdoc = gaz_dbi_get_row($gTables['pagame'], "codice", $tesdoc['pagame']);
               if ($pagame_tesdoc) {
                  $pagame = $pagame_tesdoc;
               }
            }

            if ($progr <= 0) {
               $giodoc = substr($movimenti['datdoc'], 8, 2);
               $mesdoc = substr($movimenti['datdoc'], 5, 2);
               $anndoc = substr($movimenti['datdoc'], 0, 4);
               $utsdoc = mktime(0, 0, 0, $mesdoc, $giodoc, $anndoc);
               $ratpag = CalcolaScadenze($movimenti['import'], $giodoc, $mesdoc, $anndoc, $pagame['tipdec'], $pagame['giodec'], $pagame['numrat'], $pagame['tiprat'], $pagame['mesesc'], $pagame['giosuc']);
               foreach ($ratpag['import'] as $key => $value) {
                  $utssca = mktime(0, 0, 0, $ratpag['mese'][$key], $ratpag['giorno'][$key], $ratpag['anno'][$key]);
                  if ($utssca <= $utsval) {
                     $righiCrediti[$rigo]['id_rig'] = $movimenti['id_rig'];
                     $righiCrediti[$rigo]['numdoc'] = $movimenti['numdoc'];
                     $righiCrediti[$rigo]['prelis'] = $value - $parzi;
                     switch ($movimenti['operat']) {
                        case 1:
                        case 2:
                           $righiCrediti[$rigo]['descri'] = substr($movimenti['descri'], 0, 18) . " N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                           break;
                        default:
                           $righiCrediti[$rigo]['descri'] = $movimenti['descri'];
                           break;
                     }
                     switch ($movimenti['caucon']) {
                        case "FAD":
                        case "FAI":
                        case "FAA":
                        case "FAF":
                        case "FAP":
                           $righiCrediti[$rigo]['des_con'] = "FT";
                           break;
                        case 'FND':
                           $righiCrediti[$rigo]['des_con'] = "ND";
                           break;
                        case 'FNC':
                           $righiCrediti[$rigo]['des_con'] = "NC";
                           break;
                        default:
                           $righiCrediti[$rigo]['des_con'] = "DOC";
                           break;
                     }
                     $rigo++;
                     $progr -= $value;
                  } else {
                     $progr -= $value;
                  }
               }
            }
            if ($progr > 0 and $progr < $movimenti['import'] and abs($progr - $movimenti['import']) >= $epsilon) {
               $giodoc = substr($movimenti['datdoc'], 8, 2);
               $mesdoc = substr($movimenti['datdoc'], 5, 2);
               $anndoc = substr($movimenti['datdoc'], 0, 4);
               $utsdoc = mktime(0, 0, 0, $mesdoc, $giodoc, $anndoc);
               $ratpag = CalcolaScadenze($movimenti['import'], $giodoc, $mesdoc, $anndoc, $pagame['tipdec'], $pagame['giodec'], $pagame['numrat'], $pagame['tiprat'], $pagame['mesesc'], $pagame['giosuc']);
               $parzi = $progr;
               foreach ($ratpag['import'] as $key => $value) {
                  $utssca = mktime(0, 0, 0, $ratpag['mese'][$key], $ratpag['giorno'][$key], $ratpag['anno'][$key]);
                  if ($utssca <= $utsval) {
                     if ($progr < $value) {
                        $righiCrediti[$rigo]['id_rig'] = $movimenti['id_rig'];
                        $righiCrediti[$rigo]['prelis'] = $value - $parzi;
                        $righiCrediti[$rigo]['numdoc'] = $movimenti['numdoc'];
                        switch ($movimenti['operat']) {
                           case 1:
                           case 2:
                              $righiCrediti[$rigo]['descri'] = substr($movimenti['descri'], 0, 18) . " N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                              break;
                           default:
                              $righiCrediti[$rigo]['descri'] = $movimenti['descri'];
                              break;
                        }
                        switch ($movimenti['caucon']) {
                            case "FAD":
                            case "FAI":
                            case "FAA":
                            case "FAF":
                            case "FAP":
                              $righiCrediti[$rigo]['des_con'] = "FT";
                              break;
                           case 'FND':
                              $righiCrediti[$rigo]['des_con'] = "ND";
                              break;
                           case 'FNC':
                              $righiCrediti[$rigo]['des_con'] = "NC";
                              break;
                           default:
                              $righiCrediti[$rigo]['des_con'] = "DOC";
                              break;
                        }
                        $rigo++;
                        $parzi = 0.00;
                        $progr -= $value;
                     } else {
                        $progr -= $value;
                        $parzi -= $value;
                     }
                  } else {
                     $parzi = 0.00;
                     $progr -= $value;
                  }
               }
            }
            if ($progr > 0 and ( $progr > $movimenti['import'] or abs($progr - $movimenti['import']) < $epsilon)) {
               $progr -= $movimenti["import"];
            }
         }
      }
      if ($movimenti["caucon"] == 'APE') {
         $ctrlapertura = $movimenti["caucon"];
      }
   } //fine while
   $righiCrediti['numrighi'] = $rigo;
   return $righiCrediti;
}

function createArrayDebiti($result, $pagame, $utsval) {
   // scopo di questa funzione e' quella di creare l'array dei DEBITI verso il fornitore in base
   // alla condizione di pagamento passata tramite la seconda variabile.

   global $gTables;

   $parzi = 0.00;
   $progr = 0.00;
   $righiDebiti = array();
   $rigo = 0;
   while ($movimenti = gaz_dbi_fetch_array($result)) {
      if ($movimenti["darave"] == 'D') {
         if ($progr >= 0) {
            $progr += $movimenti["import"];
         }
         if ($progr < 0 and $progr >= -$movimenti["import"]) {
            $progr += $movimenti["import"];
            //questo per eliminare tutti gli array
            $righiDebiti = array();
            $rigo = 0;
         }
         if ($progr < 0 and $progr < -$movimenti["import"]) {
            $parzi = $movimenti["import"];
            $progr += $movimenti["import"];
            foreach ($righiDebiti as $key => $value) {
               if ($parzi >= $value['prelis']) {
                  array_shift($righiDebiti);
                  $rigo--;
                  $parzi -= $value['prelis'];
               } elseif ($parzi > 0) {
                  $righiDebiti[0]['prelis'] = $value['prelis'] - $parzi; //questo elimina il resto dell'elemento
                  $parzi = 0.00;
               }
            }
         }
      } else {

         $tesdoc = gaz_dbi_get_row($gTables['tesdoc'], "id_tes", $movimenti['id_doc']);
         if ($tesdoc['pagame'] != $pagame['codice']) {
            $pagame_tesdoc = gaz_dbi_get_row($gTables['pagame'], "codice", $tesdoc['pagame']);
            if ($pagame_tesdoc) {
               $pagame = $pagame_tesdoc;
            }
         }

         if ($progr <= 0) {
            $giodoc = substr($movimenti['datdoc'], 8, 2);
            $mesdoc = substr($movimenti['datdoc'], 5, 2);
            $anndoc = substr($movimenti['datdoc'], 0, 4);
            $utsdoc = mktime(0, 0, 0, $mesdoc, $giodoc, $anndoc);
            $ratpag = CalcolaScadenze($movimenti['import'], $giodoc, $mesdoc, $anndoc, $pagame['tipdec'], $pagame['giodec'], $pagame['numrat'], $pagame['tiprat'], $pagame['mesesc'], $pagame['giosuc']);
            if (count($ratpag) > 0) {
               foreach ($ratpag['import'] as $key => $value) {
                  $utssca = mktime(0, 0, 0, $ratpag['mese'][$key], $ratpag['giorno'][$key], $ratpag['anno'][$key]);
                  if ($utssca <= $utsval) {
                     $righiDebiti[$rigo]['prelis'] = $value - $parzi;
                     $righiDebiti[$rigo]['id_rig'] = 0;
                     switch ($movimenti['caucon']) {
                        case "AFA":
                        case "AFT":
                           $righiDebiti[$rigo]['descri'] = "FATTURA N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                           $righiDebiti[$rigo]['des_con'] = "FT N." . $movimenti['numdoc'];
                           break;
                        case 'AFD':
                           $righiDebiti[$rigo]['descri'] = "NOTA DEBITO N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                           $righiDebiti[$rigo]['des_con'] = "ND N." . $movimenti['numdoc'];
                           break;
                        case 'AFC':
                           $righiDebiti[$rigo]['descri'] = "NOTA CREDITO N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                           $righiDebiti[$rigo]['des_con'] = "NC N." . $movimenti['numdoc'];
                           break;
                        default:
                           $righiDebiti[$rigo]['descri'] = "DOCUMENTO N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                           $righiDebiti[$rigo]['des_con'] = "DOC N." . $movimenti['numdoc'];
                           break;
                     }
                     $rigo++;
                     $progr -= $value;
                  } else {
                     $progr -= $value;
                  }
               }
            }
         }
         if ($progr > 0 and $progr < $movimenti['import']) {
            $giodoc = substr($movimenti['datdoc'], 8, 2);
            $mesdoc = substr($movimenti['datdoc'], 5, 2);
            $anndoc = substr($movimenti['datdoc'], 0, 4);
            $utsdoc = mktime(0, 0, 0, $mesdoc, $giodoc, $anndoc);
            $ratpag = CalcolaScadenze($movimenti['import'], $giodoc, $mesdoc, $anndoc, $pagame['tipdec'], $pagame['giodec'], $pagame['numrat'], $pagame['tiprat'], $pagame['mesesc'], $pagame['giosuc']);
            $parzi = $progr;
            foreach ($ratpag['import'] as $key => $value) {
               $utssca = mktime(0, 0, 0, $ratpag['mese'][$key], $ratpag['giorno'][$key], $ratpag['anno'][$key]);
               if ($utssca <= $utsval) {
                  if ($progr < $value) {
                     $righiDebiti[$rigo]['prelis'] = $value - $parzi;
                     $righiDebiti[$rigo]['id_rig'] = 0;
                     switch ($movimenti['caucon']) {
                        case "AFA":
                        case "AFT":
                           $righiDebiti[$rigo]['descri'] = "FATTURA N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                           $righiDebiti[$rigo]['des_con'] = "FT N." . $movimenti['numdoc'];
                           break;
                        case 'AFD':
                           $righiDebiti[$rigo]['descri'] = "NOTA DEBITO N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                           $righiDebiti[$rigo]['des_con'] = "ND N." . $movimenti['numdoc'];
                           break;
                        case 'AFC':
                           $righiDebiti[$rigo]['descri'] = "NOTA CREDITO N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                           $righiDebiti[$rigo]['des_con'] = "NC N." . $movimenti['numdoc'];
                           break;
                        default:
                           $righiDebiti[$rigo]['descri'] = "DOCUMENTO N." . $movimenti['numdoc'] . " DEL " . $giodoc . "-" . $mesdoc . "-" . $anndoc;
                           $righiDebiti[$rigo]['des_con'] = "DOC N." . $movimenti['numdoc'];
                           break;
                     }
                     $rigo++;
                     $parzi = 0.00;
                     $progr -= $value;
                  } else {
                     $progr -= $value;
                     $parzi -= $value;
                  }
               } else {
                  $parzi = 0.00;
                  $progr -= $value;
               }
            }
         }
         if ($progr > 0 and $progr >= $movimenti['import']) {
            $progr -= $movimenti["import"];
         }
      }
   } //fine while
   $righiDebiti['numrighi'] = $rigo;
   return $righiDebiti;
}

?>
