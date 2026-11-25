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
  Ricerca separata di clienti e fornitori nei documenti: nelle vendite la
  ricerca del partner mostra solo i clienti, negli acquisti solo i fornitori
  (sia nella tendina che premendo Invio). Evita di scegliere per errore un
  fornitore in una fattura e di creare cosi' un cliente doppione.
  Si attiva in Configurazione avanzata azienda con ricerca_partner_separata = 1.
*/

// true se l'azienda ha attivato la ricerca separata
function ricerca_partner_separata() {
  global $gTables;
  static $attivo = null;
  if ($attivo === null) {
    $r = gaz_dbi_get_row($gTables['company_config'], 'var', 'ricerca_partner_separata');
    $attivo = ($r && trim($r['val']) == '1');
  }
  return $attivo;
}

/* valore per l'ultimo parametro di selectPartner::selectDocPartner():
   $tipo = 'c' (solo clienti) o 'f' (solo fornitori) se la funzione e' attiva,
   '' (ricerca su tutte le anagrafiche, come prima) se e' spenta */
function ricerca_partner_mastro($tipo) {
  return ricerca_partner_separata() ? $tipo : '';
}
