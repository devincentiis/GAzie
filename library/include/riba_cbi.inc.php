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

 * ****************************************************************************************
  Questa classe genera il file RiBa standard ABI-CBI passando alla funzione "creaFile" i due array di seguito specificati:
  $intestazione = array monodimensionale con i seguenti index:
  [0] = abi_assuntrice variabile lunghezza 5 numerico
  [1] = cab_assuntrice variabile lunghezza 5 numerico
  [2] = conto variabile lunghezza 12 alfanumerico
  [3] = data_creazione variabile lunghezza 6 numerico formato GGMAA
  [4] = nome_supporto variabile lunghezza 20 alfanumerico
  [5] = codice_divisa variabile lunghezza 1 alfanumerico opzionale default "E"
  [6] = ragione_soc1_creditore variabile lunghezza 24 alfanumerico
  [7] = ragione_soc2_creditore variabile lunghezza 24 alfanumerico
  [8] = indirizzo_creditore variabile lunghezza 24 alfanumerico
  [9] = cap_citta_prov_creditore variabile lunghezza 24 alfanumerico
  [10] = codice_fiscale_creditore variabile lunghezza 16 alfanumerico opzionale default ""
  [11] = codice SIA 5 caratteri alfanumerici
  [12] = carry  booleano true per aggiungere i caratteri di fine rigo chr(13) e chr(10)
  $ricevute_bancarie = array bidimensionale con i seguenti index:
  [0] = numero ricevuta lunghezza 10 numerico
  [1] = scadenza lunghezza 6 numerico
  [2] = importo in centesimi di euro lunghezza 13 numerico
  [3] = nome debitore lunghezza 60 alfanumerico
  [4] = codice fiscale/partita iva debitore lunghezza 16 alfanumerico
  [5] = indirizzo debitore lunghezza 30 alfanumerico
  [6] = cap debitore lunghezza 5 numerico
  [7] = comune debitore lunghezza 25 alfanumerico
  [8] = abi banca domiciliataria lunghezza 5 numerico
  [9] = cab banca domiciliataria lunghezza 5 numerico
  [10] = descrizione banca domiciliataria lunghezza 50 alfanumerico
  [11] = codice cliente attribuito dal creditore lunghezza 16 numerico
  [12] = descrizione del debito lunghezza 40 alfanumerico (CIG CUP)
  [13] = provincia debitore lunghezza 2 alfanumerico
  [14] = descrizione del debito lunghezza 40 alfanumerico (Numero e data riferimento della fattura che ha generato l'effetto)
 */

class RibaAbiCbi {

    var $progressivo = 0;
    var $assuntrice;
    var $data;
    var $valuta;
    var $supporto;
    var $totale;
    var $creditore;

    function RecordIB($abi_assuntrice, $data_creazione, $nome_supporto, $codice_divisa, $sia_code, $cab_assuntrice) { //record di testa
        $this->assuntrice = str_pad($abi_assuntrice, 5, '0', STR_PAD_LEFT);
        $this->cab_ass = str_pad($cab_assuntrice, 5, '0', STR_PAD_LEFT);
        $this->data = str_pad($data_creazione, 6, '0');
        $this->valuta = substr($codice_divisa, 0, 1);
        $this->supporto = str_pad($nome_supporto, 20, '*', STR_PAD_LEFT);
        $this->sia_code = str_pad($sia_code, 5, '0', STR_PAD_LEFT);
        return " IB" . $this->sia_code . $this->assuntrice . $this->data . $this->supporto . str_repeat(" ", 65) . "1$" . $this->assuntrice . str_repeat(" ", 2) . $this->valuta . str_repeat(" ", 6);
    }

    function Record14($scadenza, $importo, $abi_assuntrice, $conto, $abi_domiciliataria, $cab_domiciliataria, $codice_cliente) {
        $this->totale += $importo;
        return " 14" . str_pad($this->progressivo, 7, '0', STR_PAD_LEFT) . str_repeat(" ", 12) . $scadenza . "30000" . str_pad($importo, 13, '0', STR_PAD_LEFT) . "-" . str_pad($abi_assuntrice, 5, '0', STR_PAD_LEFT) . $this->cab_ass . str_pad($conto, 12) . str_pad($abi_domiciliataria, 5, '0', STR_PAD_LEFT) . str_pad($cab_domiciliataria, 5, '0', STR_PAD_LEFT) . str_repeat(" ", 12) . $this->sia_code . "4" . str_pad($codice_cliente, 16) . str_repeat(" ", 6) . $this->valuta;
    }

    function Record20($ragione_soc1_creditore, $ragione_soc2_creditore, $indirizzo_creditore, $cap_citta_prov_creditore) {
        $this->creditore = str_pad($ragione_soc1_creditore, 24);
        return " 20" . str_pad($this->progressivo, 7, '0', STR_PAD_LEFT) . substr($this->creditore, 0, 24) . substr(str_pad($ragione_soc2_creditore, 24), 0, 24) . substr(str_pad($indirizzo_creditore, 24), 0, 24) . substr(str_pad($cap_citta_prov_creditore, 24), 0, 24) . str_repeat(" ", 14);
    }

    function Record30($nome_debitore, $codice_fiscale_debitore) {
        return " 30" . str_pad($this->progressivo, 7, '0', STR_PAD_LEFT) . substr(str_pad($nome_debitore, 60), 0, 60) . str_pad($codice_fiscale_debitore, 16, ' ') . str_repeat(" ", 34);
    }

    function Record40($indirizzo_debitore, $cap_debitore, $comune_debitore, $descrizione_domiciliataria = "", $provincia_debitore='') {
        return " 40" . str_pad($this->progressivo, 7, '0', STR_PAD_LEFT) . substr(str_pad($indirizzo_debitore, 30), 0, 30) . str_pad(intval($cap_debitore), 5, '0', STR_PAD_LEFT) . substr(str_pad($comune_debitore, 22), 0, 22) . " " . substr(str_pad($provincia_debitore, 2), 0, 2) . substr(str_pad($descrizione_domiciliataria, 50), 0, 50);
    }

    function Record50($descrizione_debito, $codice_fiscale_creditore) {
        return " 50" . str_pad($this->progressivo, 7, '0', STR_PAD_LEFT) . substr(str_pad($descrizione_debito, 80), 0, 80) . str_repeat(" ", 10) . str_pad($codice_fiscale_creditore, 16, ' ') . str_repeat(" ", 4);
    }

    function Record51($numero_ricevuta_creditore) {
        return " 51" . str_pad($this->progressivo, 7, '0', STR_PAD_LEFT) . str_pad($numero_ricevuta_creditore, 10, '0', STR_PAD_LEFT) . substr($this->creditore, 0, 20) . str_repeat(" ", 80);
    }

    function Record70() {
        return " 70" . str_pad($this->progressivo, 7, '0', STR_PAD_LEFT) . str_repeat(" ", 110);
    }

    function RecordEF() { //record di coda
        return " EF" . $this->sia_code . $this->assuntrice . $this->data . $this->supporto . str_repeat(" ", 6) . str_pad($this->progressivo, 7, '0', STR_PAD_LEFT) . str_pad($this->totale, 15, '0', STR_PAD_LEFT) . str_repeat("0", 15) . str_pad($this->progressivo * 7 + 2, 7, '0', STR_PAD_LEFT) . str_repeat(" ", 24) . $this->valuta . str_repeat(" ", 6);
    }

    function creaFile($intestazione, $ricevute_bancarie) {
        $eol = '';
        if (isset($intestazione[12])) {
            $eol = chr(13) . chr(10);
        }
        $accumulatore = $this->RecordIB($intestazione[0], $intestazione[3], $intestazione[4], $intestazione[5], $intestazione[11], $intestazione[1]) . $eol;
        foreach ($ricevute_bancarie as $value) { //estraggo le ricevute dall'array
            $this->progressivo ++;
            $accumulatore .= $this->Record14($value[1], $value[2], $intestazione[0], $intestazione[2], $value[8], $value[9], $value[11]) . $eol;
            $accumulatore .= $this->Record20($intestazione[6], $intestazione[7], $intestazione[8], $intestazione[9]) . $eol;
            $accumulatore .= $this->Record30($value[3], $value[4]) . $eol;
            $accumulatore .= $this->Record40($value[5], $value[6], $value[7], $value[10], $value[13]) . $eol;
            $accumulatore .= $this->Record50($value[12] . ' ' . $value[14], $intestazione[10]) . $eol;
            $accumulatore .= $this->Record51($value[0]) . $eol;
            $accumulatore .= $this->Record70() . $eol;
        }
        $accumulatore .= $this->RecordEF() . $eol;
        return $accumulatore;
    }

}

?>