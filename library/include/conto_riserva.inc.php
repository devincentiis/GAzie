<?php
/*
 --------------------------------------------------------------------------
                            GAzie - Gestione Azienda
    Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
         (https://www.devincentiis.it)
           <https://gazie.sourceforge.net>
  Questo file: Copyright (C) 2026 Domenico Seccia - Assistenzacassa (https://assistenzacassa.it)
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
/*
  Conto di riserva in contabilizzazione dei documenti di vendita e acquisto:
  un rigo senza conto ricavi/costi valido usa il conto indicato
  nella configurazione azienda.
  Si attiva in Configurazione avanzata azienda con conto_ricavi_riserva = 1.
*/

// true se l'azienda ha attivato il conto di riserva
function conto_riserva_attivo() {
  global $gTables;
  static $attivo = null;
  if ($attivo === null) {
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', 'conto_ricavi_riserva');
    $attivo = ($r && trim($r['val']) == '1');
  }
  return $attivo;
}

// conto del rigo: se manca o non e' valido e l'interruttore e' attivo usa il conto dell'azienda
// solo righi normali (0) e forfait (1): cassa previdenziale (4) e cespiti (90) hanno conti propri
function conto_riserva_rigo($codric, $tiprig, $conto_azienda) {
  if (intval($codric) >= 100000000 || !in_array(intval($tiprig), [0, 1], true)) {
    return $codric;
  }
  if (!conto_riserva_attivo() || intval($conto_azienda) < 100000000) {
    return $codric; // interruttore spento o conto dell'azienda mancante: come prima
  }
  return $conto_azienda;
}
