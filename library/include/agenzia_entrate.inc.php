<?php
/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2023 Antonio De Vincentiis Montesilvano (PE)
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


 * ****************************************************************************************
  Questa classe genera il file da importare con l'apposito software messo a disposizione della
  Agenzie delle Entrate (Uniconline - File Internet) per effettuare la trasmissione per via
  telematica.
  Affinchè il tutto avvenga correttamente si devono passare alle funzioni specifiche sotto
  elencate  denominate "creaFileXXX" gli array di seguito specificati per singola  funzione.
 * **************************************************************************************** */
#[AllowDynamicProperties] 
class AgenziaEntrate {
    /*     * **** creaFileIVC - COMUNICAZIONE ANNUALE DATI IVA
      Una AVVERTENZA IMPORTANTE è quella di considerare che siccome questa funzione è predisposta
      solo per chi si invia "IN PROPRIO" le dichiarazioni viene impostato il relativo flag
      (campo 4 del record di testa "A") sempre a "01" e quindi il codice fiscale del fornitore
      deve coincidere con quello del contribuente, lo stesso vale per il campo 7 del Record "B"
      dove viene richiesto il codice fiscale del produttore del software.
      $A = array monodimensionale con i seguenti index:
      [0] = Codice fornitura 3 alfanumerico (IVC)
      [1] = Anno fornitura 2 numerico
      [2] = Codice Fiscale fornitore 16 alfanumerico
      $B = array monodimensionale con i seguenti index:
      [0] = Codice Fiscale del contribuente 16 alfanumerico
      [1] = Codice Fiscale o Partita IVA del produttore del software 16 alfanumerico
      [2] = Ragione Sociale del Contribuente 60 alfanumerico
      [3] = Cognome del Contribuente se persona fisica 24 alfanumerico
      [4] = Cognome del Contribuente se persona fisica 20 alfanumerico
      [5] = Anno d'Imposta 4 numerico
      [6] = Partita IVA del Contribuente 11 numerico
      [7] = Codice Attività 5 alfanumerico
      [8] = Flag Contabilità Separata 1 boleano
      [9] = Flag Societa appartenente ad un Gruppo IVA 1 boleano
      [10]= Flag Eventi Eccezionali 1 boleano
      [11]= Codice Fiscale Dichiarante (Responsabile della dichiarazione) 16 alfanumerico
      [12]= Codice carica del dichiarante se presente(0,1,2,5,6,7,8 o 9) 2 nuemrico
      [13]= Codice Fiscale società Dichiarante 11 numerico
      [14]= Totale operazioni attive al netto dell'IVA 11 numerico
      [15]= Operazioni non imponibili 11 numerico
      [16]= Operazioni esenti 11 numerico
      [17]= Cessioni intracomunitarie di beni 11 numerico
      [18]= Cessioni beni strumentali
      [19]= Totale operazioni passive al netto dell'IVA 11 numerico
      [20]= Acquisti non imponibili 11 numerico
      [21]= Acquisti esenti 11 numerico
      [22]= Acquisti intracomunitari si beni 11 numerico
      [23]= Acquisti beni strumentali
      [24]= Importazione oro e argento senza pagamento IVA Imponibile 11 numerico
      [25]= Importazione oro e argento senza pagamento IVA Imposta 11 numerico
      [26]= Importazione rottami Acquisti senza pagamento IVA Imponibile 11 numerico
      [27]= Importazione rottami Acquisti senza pagamento IVA Imposta 11 numerico
      [28]= IVA esigibile CD4 11 numerico
      [29]= IVA detratta  CD5 11 numerico
      [30]= IVA dovuta    CD6 11 numerico
      [31]= IVA a credito CD6 11 numerico
      $Z = numero di record "B", di default 1
     */

    function RecordA($A) { //record testa della fornitura comunicazione annuale dati IVA
        $this->CodiceFornitura = substr(str_pad($A[0], 3, ' '), 0, 3);
        /*
          // AGENZIA ENTRATE'S ERROR
          $this->AnnoFornitura = substr(str_pad($A[1],2,'0',STR_PAD_LEFT),0,2);
          // TEMP 2010
         */
        $this->AnnoFornitura = '10';
        $this->CFFornitore = substr(str_pad($A[2], 16, ' '), 0, 16);
        return "A" . str_repeat(" ", 14) . $this->CodiceFornitura . $this->AnnoFornitura . "01"
                . $this->CFFornitore . str_repeat(" ", 483) . str_repeat("0", 8) . str_repeat(" ", 1368)
                . "A\r\n";
    }

    function RecordB($B) { //record dati della comunicazione annuale IVA
        $this->CFContribuente = substr(str_pad($B[0], 16, ' '), 0, 16);
        $this->CFProduttore = substr(str_pad($B[1], 16, ' '), 0, 16);
        $this->Ragionesociale = substr(str_pad($B[2], 60, ' '), 0, 60);
        $this->Cognome = substr(str_pad($B[3], 24, ' '), 0, 24);
        $this->Nome = substr(str_pad($B[4], 20, ' '), 0, 20);
        $this->AnnoImposta = substr(str_pad($B[5], 4, '0', STR_PAD_LEFT), 0, 4);
        $this->PIContribuente = substr(str_pad($B[6], 11, '0', STR_PAD_LEFT), 0, 11);
        $this->CodiceAttivita = substr(str_pad($B[7], 6, ' '), 0, 6);
        if ($B[8] > 0) {
            $this->ContabSeparata = 1;
        } else {
            $this->ContabSeparata = 0;
        }
        if ($B[9] > 0) {
            $this->SocietaGruppo = 1;
        } else {
            $this->SocietaGruppo = 0;
        }
        if ($B[10] > 0) {
            $this->EventiEccezio = 1;
        } else {
            $this->EventiEccezio = 0;
        }
        $this->CFDichiarante = substr(str_pad($B[11], 16, ' '), 0, 16);
        $this->CodiceCarica = substr(str_pad($B[12], 2, '0', STR_PAD_LEFT), 0, 2);
        $this->CNSocDichiar = substr(str_pad($B[13], 11, '0', STR_PAD_LEFT), 0, 11);
        //   Dall'index 14 all'index 29 sono tutti da portare ad un valore numerico
        //   intero di lunghezza 11 caratteri (valori in unita' di euro)
        $this->Valori = "";
        for ($i = 14; $i < 32; $i++) {
            $this->Valori .= str_pad(intval($B[$i]), 11, ' ', STR_PAD_LEFT);
        }
        return "B" . $this->CFContribuente . '00000001' . str_repeat(" ", 48) . $this->CFProduttore . "0"
                . $this->Ragionesociale . $this->Cognome . $this->Nome . $this->AnnoImposta
                . $this->PIContribuente . $this->CodiceAttivita . $this->ContabSeparata
                . $this->SocietaGruppo . $this->EventiEccezio . $this->CFDichiarante
                . $this->CodiceCarica . $this->CNSocDichiar . $this->Valori . "0" . str_repeat(" ", 16) . str_repeat("0", 16) . str_repeat(" ", 1419) . "A\r\n";
    }

    function RecordZ($Z = 1) { //record coda della fornitura, in Z c'e' il numero di record "B"
        return "Z" . str_repeat(" ", 14) . str_pad(intval($Z), 9, '0', STR_PAD_LEFT) . str_repeat(" ", 1873) . "A\r\n";
    }

    function creaFileIVC($A, $B) {
        $accumulatore = $this->RecordA($A) . $this->RecordB($B) . $this->RecordZ();
        return $accumulatore;
    }

// --- FINE FUNZIONI COMUNICAZIONE ANNUALE DATI IVA (IVC)

    /*     * **** creaFileECF - ELENCO CLIENTI E FORNITORI
      $testa = array monodimensionale con i seguenti index:
      [codfis] = Codice Fiscale del contribuente 16 alfanumerico o 11 numerico
      [pariva] = Partita IVA del contribuente 11 numerico
      [nome] = Nome del Contribuente 26 alfanumerico
      [cognome] = Cognome del Contribuente 25 alfanumerico
      [sesso] = Sesso se Persona Fisica (d'ora in poi PF) 1 alfanumerico (MF)
      [datnas] = Data di nascita contribuente se PF formato GGMMAAAA
      [luonas] = Comune o stato estero di nascita contribuente se PF 40 alfanumerico
      [pronas] = Provincia o stato estero (EE) di nascita contribuente se PF 2 alfanumerico
      [ragsoc] = Ragione sociale contribuente se Persona Giuridica (d'ora in poi PG) 70 alfanumerico
      [segleg] = Comune della sede legale se PG 40 alfanumerico
      [proleg] = Provincia della sede legale se PG 2 alfanumerico
      [anno] = Anno fornitura 4 numerico
      $dati = array bidimensionale con i seguenti index posti anche in questo ordine:
      [tipo] = Tipo di Record 1 numerico (1=clienti 2=fornitori)
      [progressivo] = Numero Progressivo del cliente/fornitore 16 numerico
      [codfis] = Codice Fiscale del cliente/fornitore 16 alfanumerico o 11 numerico
      [pariva] = Partita IVA del cliente/fornitore 11 numerico
      [imponibile] = Imponibile
      [imposta] = Imposta applicata
      [nonimp] = Non imponibile
      [esente] = Esente
      [elementi] = Numero di elementi valorizzati nel record compreso il progressivo ed escluso esso stesso
      $totali = array bidimensionale (prima dimensione:index [1] =clienti, index[2] = fornitori)  la seconda dimensione deve avere i seguenti index:
      [numero] = Numero di clienti/fornitori 8 numerico
      [imponibile] = Imponibile
      [imposta] = Imposta applicata
      [esente] = Esente
      [nonimp] = Non imponibile
     */
    function Record09($T) { //record testa degli elenchi clienti e fornitori
        $this->CFContribuente = substr(str_pad($T['codfis'], 16, ' '), 0, 16);
        $this->PIContribuente = substr(str_pad($T['pariva'], 11, '0', STR_PAD_LEFT), 0, 11);
        if (isset($T['sesso'])) {
            $this->AltriDati = substr(str_pad($T['cognome'], 26, ' '), 0, 26) .
                    substr(str_pad($T['nome'], 25, ' '), 0, 25) .
                    substr($T['sesso'], 0, 1) .
                    substr(str_pad($T['datnas'], 8, ' '), 0, 8) .
                    substr(str_pad($T['luonas'], 40, ' '), 0, 40) .
                    substr(str_pad($T['pronas'], 2, ' '), 0, 2) . str_repeat(' ', 112);
        } else {
            $this->AltriDati = str_repeat(' ', 102) .
                    substr(str_pad($T['ragsoc'], 70, ' '), 0, 70) .
                    substr(str_pad($T['sedleg'], 40, ' '), 0, 40) .
                    substr(str_pad($T['proleg'], 2, ' '), 0, 2);
        }
        $this->Anno = substr(str_pad($T['anno'], 4, '0'), 0, 4);
        return "ECF0038" . $this->CFContribuente . $this->PIContribuente . $this->AltriDati .
                str_repeat(' ', 16) . $this->Anno . str_repeat('0', 8) . str_repeat(' ', 16) .
                str_repeat('0', 14) . str_repeat(' ', 1490) . "A\r\n";
    }

    function Record12($D) { // corpo e riepilogativo con i dati dei record dei singoli clienti/fornitori

        function CreaElemento($k, $v, $t) {
            $acc = '';
            if ($t == 1) { // clienti
                $tipo = 'CL';
                switch ($k) {
                    case 'progressivo':
                        $acc = $tipo . '001001' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                    case 'codfis':
                        $acc = $tipo . '002001' . substr(str_pad($v, 16, ' '), 0, 16);
                        break;
                    case 'pariva':
                        $acc = $tipo . '003001' . substr(str_pad($v, 16, ' '), 0, 16);
                        break;
                    case 'imponibile':
                        $acc = $tipo . '004001' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                    case 'imposta':
                        $acc = $tipo . '004002' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                    case 'nonimp':
                        $acc = $tipo . '005001' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                    case 'esente':
                        $acc = $tipo . '006001' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                }
            } else { //fornitori
                $tipo = 'FR';
                switch ($k) {
                    case 'progressivo':
                        $acc = $tipo . '001001' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                    case 'codfis':
                        $acc = $tipo . '002001' . substr(str_pad($v, 16, ' '), 0, 16);
                        break;
                    case 'pariva':
                        $acc = $tipo . '003001' . substr(str_pad($v, 16, ' '), 0, 16);
                        break;
                    case 'imponibile':
                        $acc = $tipo . '004001' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                    case 'imposta':
                        $acc = $tipo . '004002' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                    case 'nonimp':
                        $acc = $tipo . '006001' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                    case 'esente':
                        $acc = $tipo . '007001' . substr(str_pad($v, 16, ' ', STR_PAD_LEFT), 0, 16);
                        break;
                }
            }
            return $acc;
        }

        $n_elements = 0;
        $ctrl_tipo = 0;
        foreach ($D as $ElementsData) {
            if ($ctrl_tipo < $ElementsData['tipo'] and $ctrl_tipo != 0) { // non è lo stesso partner precedente e non è il primo
                $diff_to_end = (70 - $n_elements) * 24 + 116;
                $acc .= str_repeat(' ', $diff_to_end) . "A\r\n" . $ElementsData['tipo'];
                $n_elements = 0;
            } elseif ($ctrl_tipo == 0) {
                $acc = $ElementsData['tipo'];
            }
            foreach ($ElementsData as $key => $value) {
                $rs_elemento = CreaElemento($key, $value, $ElementsData['tipo']);
                if (!empty($rs_elemento)) { // se è un elemento valido
                    $acc .= $rs_elemento;
                    $n_elements++;
                }
                if ($n_elements == 70) {
                    // salta sulla riga successiva
                    $acc .= str_repeat(' ', 116) . "A\r\n" . $ElementsData['tipo'];
                    $n_elements = 0;
                }
            }
            $ctrl_tipo = $ElementsData['tipo'];
        }
        $diff_to_end = (70 - $n_elements) * 24 + 116;
        $acc .= str_repeat(' ', $diff_to_end) . "A\r\n";
        return $acc;
    }

    function Record3($T) { // record di coda degli elenchi clienti e fornitori
        $acc = '3';
        if (isset($T[1]) and ! isset($T[2])) { // solo clienti
            $acc .= substr(str_pad($T[1]['numero'], 8, '0', STR_PAD_LEFT), 0, 8) . '00000000';
            // clienti
            $acc .= substr(str_pad($T[1]['imponibile'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[1]['imposta'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[1]['nonimp'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[1]['esente'], 20, ' ', STR_PAD_LEFT), 0, 20);
            for ($i = 0; $i < 18; $i++) {
                $acc .= str_repeat(' ', 19) . '0';
            }
        } elseif (!isset($T[1]) and isset($T[2])) { // solo fornitori
            $acc .= '00000000' . substr(str_pad($T[2]['numero'], 8, '0', STR_PAD_LEFT), 0, 8);
            // fornitori
            for ($i = 0; $i < 10; $i++) {
                $acc .= str_repeat(' ', 19) . '0';
            }
            $acc .= substr(str_pad($T[2]['imponibile'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[2]['imposta'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= str_repeat(' ', 19) . '0';
            $acc .= substr(str_pad($T[2]['nonimp'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[2]['esente'], 20, ' ', STR_PAD_LEFT), 0, 20);
            for ($i = 0; $i < 7; $i++) {
                $acc .= str_repeat(' ', 19) . '0';
            }
        } elseif (isset($T[1]) and isset($T[2])) { // clienti e fornitori
            $acc .= substr(str_pad($T[1]['numero'], 8, '0', STR_PAD_LEFT), 0, 8) . substr(str_pad($T[2]['numero'], 8, '0', STR_PAD_LEFT), 0, 8);
            // clienti
            $acc .= substr(str_pad($T[1]['imponibile'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[1]['imposta'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[1]['nonimp'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[1]['esente'], 20, ' ', STR_PAD_LEFT), 0, 20);
            // fornitori
            for ($i = 0; $i < 6; $i++) {
                $acc .= str_repeat(' ', 19) . '0';
            }
            $acc .= substr(str_pad($T[2]['imponibile'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[2]['imposta'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= str_repeat(' ', 19) . '0';
            $acc .= substr(str_pad($T[2]['nonimp'], 20, ' ', STR_PAD_LEFT), 0, 20);
            $acc .= substr(str_pad($T[2]['esente'], 20, ' ', STR_PAD_LEFT), 0, 20);
            for ($i = 0; $i < 7; $i++) {
                $acc .= str_repeat(' ', 19) . '0';
            }
        }
        $acc .= str_repeat(' ', 1340);
        return $acc . "A\r\n";
    }

    function creaFileECF($testa, $dati, $totali) {
        $accumulatore = '0' . $this->Record09($testa) . $this->Record12($dati) .
                $this->Record3($totali) . '9' . $this->Record09($testa);
        return $accumulatore;
    }

// --- FINE FUNZIONI ELENCO CLIENTI E FORNITORI (ECF)





    /*     * **** creaFileART21 - COMUNICAZIONE OPERAZIONI RILEVANTI AI FINI IVA ANTE 2012
      $testa = array monodimensionale con i seguenti index:
      [codfis] = Codice Fiscale del contribuente 16 alfanumerico o 11 numerico
      [pariva] = Partita IVA del contribuente 11 numerico
      [nome] = Nome del Contribuente 26 alfanumerico
      [cognome] = Cognome del Contribuente 25 alfanumerico
      [sesso] = Sesso se Persona Fisica (d'ora in poi PF) 1 alfanumerico (MF)
      [datnas] = Data di nascita contribuente se PF formato GGMMAAAA
      [luonas] = Comune o stato estero di nascita contribuente se PF 40 alfanumerico
      [pronas] = Provincia o stato estero (EE) di nascita contribuente se PF 2 alfanumerico
      [ragsoc] = Ragione sociale contribuente se Persona Giuridica (d'ora in poi PG) 70 alfanumerico
      [segleg] = Comune della sede legale se PG 40 alfanumerico
      [proleg] = Provincia della sede legale se PG 2 alfanumerico
      [anno] = Anno fornitura 4 numerico
      $dati = array bidimensionale con i seguenti index posti anche in questo ordine:
      [tipo] = Tipo di Record 1 numerico (1=SOGGETTI NON TITOLARI DI PARTITA IVA,2=SOGGETTI TITOLARI DI PARTITA IVA,3=SOGGETTI NON RESIDENTI)
      [codfis] = Codice Fiscale del cliente 16 alfanumerico
      [pariva] = Partita IVA del cliente/fornitore 11 numerico
      [imponibile] = Imponibile o corrispettivo in caso di [tipo] = 1
      [imposta] = Imposta applicata
      [tipoimponibile] = Tipologia Imponibile (1=Imponibile,2 = Non imponibile,3 = Esente,4 = Imponibile con IVA non esposta in fattura )
      [tipooperazione] = Tipologia dell'operazione (1 = Cessione di beni,2 = Prestazione di servizi,3 = Acquisto di beni,4 = Acquisto di servizi )
      [cognome] = Cognome in caso di [tipo] = 3
      [nome] = Nome in caso di [tipo] = 3
      [datnas] = Data di Nascita in caso di [tipo] = 3   (se non valorizzato discrimina viene considerata una persona non fisica)
      [luonas] = Comune o Stato estero di nascita in caso di [tipo] = 3
      [pronas] = Provincia di nascita in caso di [tipo] = 3 (in caso di Stato estero, indicare "EE")
      [stato] = Stato estero del domicilio fiscale in caso di [tipo] = 3 (Indicare uno dei codici, corrispondente allo Stato di residenza della controparte, di
      cui all'Elenco dei Paesi e Territori esteri contenuto nelle istruzioni per la compilazione
      del modello UNICO di dichiarazione dei redditi.)
      [indirizzo] = Indirizzo estero del domicilio fiscale in caso di [tipo] = 3  (se non valorizzato discrimina viene considerata una persona fisica)
      $totali = array bidimensionale (prima dimensione:index [1] =clienti, index[2] = fornitori)  la seconda dimensione deve avere i seguenti index:
      [numero] = Numero di clienti/fornitori 8 numerico
      [imponibile] = Imponibile
      [imposta] = Imposta applicata
      [esente] = Esente
      [nonimp] = Non imponibile
     */
    function Record_09($T) { // TRACCIATO RECORD DI TESTA - CODA
        $this->CFContribuente = substr(str_pad($T['codfis'], 16, ' ', STR_PAD_RIGHT), 0, 16);
        $this->PIContribuente = substr(str_pad($T['pariva'], 11, '0', STR_PAD_LEFT), 0, 11);
        if (isset($T['sesso'])) {  // PERSONA FISICA
            $this->AltriDati = str_repeat(' ', 102) .
                    substr(str_pad($T['cognome'], 24, ' '), 0, 24) .
                    substr(str_pad($T['nome'], 20, ' '), 0, 20) .
                    substr($T['sesso'], 0, 1) .
                    substr($T['datnas'], 8, 2) . substr($T['datnas'], 5, 2) . substr($T['datnas'], 0, 4) .
                    substr(str_pad($T['luonas'], 40, ' '), 0, 40) .
                    substr(str_pad($T['pronas'], 2, ' '), 0, 2);
        } else {    // PERSONA GIURIDICA
            $this->AltriDati = substr(str_pad($T['ragsoc'], 60, ' '), 0, 70) .
                    substr(str_pad($T['sedleg'], 40, ' '), 0, 40) .
                    substr(str_pad($T['proleg'], 2, ' '), 0, 2) .
                    str_repeat(' ', 95);
        }
        $this->Anno = substr(str_pad($T['anno'], 4, '0'), 0, 4);
        return "ART21470" . str_repeat(' ', 23) . $this->CFContribuente . $this->PIContribuente .
                $this->AltriDati .
                $this->Anno . '000010001' .
                str_repeat(' ', 1528) . "A\r\n";
    }

    function Record_12345($D) { // TRACCIATO RECORD DI DETTAGLIO OPERAZIONI 
        $acc = '';
        $ctrl_tipo = 0;
        foreach ($D as $ElementsData) {
            switch ($ElementsData['soggetto_type']) {
                case '1': // SOGGETTI RESIDENTI NON TITOLARI DI PARTITA IVA
                    if (strlen(trim($ElementsData['codfis'])) == 11) { // È una persona giuridica ( associazione )
                        $cf = substr(str_pad($ElementsData['codfis'], 16, ' ', STR_PAD_RIGHT), 0, 16);
                    } else { // È una persona fisica
                        $cf = substr(str_pad($ElementsData['codfis'], 16, ' ', STR_PAD_LEFT), 0, 16);
                    }
                    $acc .= '1' . $cf
                            . substr($ElementsData['datreg'], 8, 2) . substr($ElementsData['datreg'], 5, 2) . substr($ElementsData['datreg'], 0, 4)
                            . $ElementsData['n_rate']
                            . substr(str_pad(round($ElementsData['operazioni_imponibili'] + $ElementsData['imposte_addebitate'] + $ElementsData['operazioni_nonimp'] + $ElementsData['operazioni_esente']), 9, ' ', STR_PAD_LEFT), 0, 9)
                            . str_repeat(' ', 1762) . "A\r\n";
                    break;
                case '2': // SOGGETTI RESIDENTI TITOLARI DI PARTITA IVA
                    $acc .= '2' . substr(str_pad($ElementsData['pariva'], 11, '0', STR_PAD_LEFT), 0, 11)
                            . substr($ElementsData['datreg'], 8, 2) . substr($ElementsData['datreg'], 5, 2) . substr($ElementsData['datreg'], 0, 4)
                            . substr(str_pad(round($ElementsData['numdoc']), 15, ' '), 0, 15)
                            . $ElementsData['n_rate']
                            . substr(str_pad(round($ElementsData['operazioni_imponibili'] + $ElementsData['operazioni_nonimp'] + $ElementsData['operazioni_esente']), 9, '0', STR_PAD_LEFT), 0, 9)
                            . substr(str_pad(round($ElementsData['imposte_addebitate']), 9, '0', STR_PAD_LEFT), 0, 9);
                    if ($ElementsData['op_type'] > 2) { //acquisto
                        $acc .= '2';
                    } else { // vendita
                        $acc .= '1';
                    }
                    $acc .= str_repeat(' ', 1742) . "A\r\n";
                    break;
                case '3': // SOGGETTI NON RESIDENTI 
                    $acc .= '3';
                    if ($ElementsData['sexper'] == 'G') { //persona giuridica
                        $acc .= str_repeat(' ', 97)
                                . substr(str_pad($ElementsData['ragso1'] . ' ' . $ElementsData['ragso2'], 60, ' ', STR_PAD_RIGHT), 0, 60)
                                . substr(str_pad($ElementsData['citspe'], 40, ' ', STR_PAD_RIGHT), 0, 40)
                                . substr(str_pad($ElementsData['istat_country'], 3, ' ', STR_PAD_LEFT), 0, 3)
                                . substr(str_pad($ElementsData['indspe'], 40, ' ', STR_PAD_RIGHT), 0, 40);
                    } else { // persona fisica
                        $acc .= substr(str_pad($ElementsData['cognome'], 24, ' ', STR_PAD_RIGHT), 0, 24)
                                . substr(str_pad($ElementsData['nome'], 20, ' ', STR_PAD_RIGHT), 0, 20)
                                . substr($ElementsData['datnas'], 8, 2) . substr($ElementsData['datnas'], 5, 2) . substr($ElementsData['datnas'], 0, 4)
                                . substr(str_pad($ElementsData['luonas'], 40, ' ', STR_PAD_RIGHT), 0, 40)
                                . substr(str_pad($ElementsData['pronas'], 2, ' ', STR_PAD_RIGHT), 0, 2)
                                . substr(str_pad($ElementsData['istat_country'], 3, ' ', STR_PAD_LEFT), 0, 3)
                                . str_repeat(' ', 143);
                    }
                    $acc .= substr($ElementsData['datreg'], 8, 2) . substr($ElementsData['datreg'], 5, 2) . substr($ElementsData['datreg'], 0, 4)
                            . substr(str_pad(round($ElementsData['numdoc']), 15, ' '), 0, 15)
                            . $ElementsData['n_rate']
                            . substr(str_pad(round($ElementsData['operazioni_imponibili'] + $ElementsData['operazioni_nonimp'] + $ElementsData['operazioni_esente']), 9, ' ', STR_PAD_LEFT), 0, 9)
                            . substr(str_pad(round($ElementsData['imposte_addebitate']), 9, ' ', STR_PAD_LEFT), 0, 9);
                    if ($ElementsData['op_type'] > 2) { //acquisto
                        $acc .= '2';
                    } else { // vendita
                        $acc .= '1';
                    }
                    $acc .= str_repeat(' ', 1513) . "A\r\n";
                    break;
                case '4': // SOGGETTI RESIDENTI - NOTE DI VARIAZIONE
                    break;
                case '5': // SOGGETTI NON RESIDENTI - NOTE DI VARIAZIONE
                    break;
            }
        }
        return $acc;
    }

    function creaFileART21($testa, $dati) {
        $accumulatore = '0' . $this->Record_09($testa) .
                $this->Record_12345($dati) .
                '9' . $this->Record_09($testa);
        return $accumulatore;
    }

// --- FINE FUNZIONI COMUNICAZIONE OPERAZIONI RILEVANTI AI FINI IVA (ART21) ANTE 2012


    /* --- INIZIO creaFileART21_poli MODELLO COMUNICAZIONE POLIVALENTE DAL 2013

      $testa = array monodimensionale con i seguenti index:
      [codfis] = Codice Fiscale del contribuente 16 alfanumerico o 11 numerico
      [pariva] = Partita IVA del contribuente 11 numerico
      [nome] = Nome del Contribuente 26 alfanumerico
      [cognome] = Cognome del Contribuente 25 alfanumerico
      [sesso] = Sesso se Persona Fisica (d'ora in poi PF) 1 alfanumerico (MF)
      [datnas] = Data di nascita contribuente se PF formato GGMMAAAA
      [luonas] = Comune o stato estero di nascita contribuente se PF 40 alfanumerico
      [pronas] = Provincia o stato estero (EE) di nascita contribuente se PF 2 alfanumerico
      [ragsoc] = Ragione sociale contribuente se Persona Giuridica (d'ora in poi PG) 70 alfanumerico
      [segleg] = Comune della sede legale se PG 40 alfanumerico
      [proleg] = Provincia della sede legale se PG 2 alfanumerico
      [anno] = Anno fornitura 4 numerico
      [ateco]= codice attività azienda ATECO 2007
      [telefono]= telefono del contribuente
      [fax]= fax del contribuente
      [e_mail]= e-mail del contribuente
      $dati = array bidimensionale con i seguenti index posti anche in questo ordine:
      [quadro] = Quadro 2 alfanumerico (FE=FATTURE EMESSE,FR=FATTURE RICEVUTE,NE=NOTE EMESSE,NR=NOTE RICEVUTE,DF=SENZA FATTURA,FN=NON RESIDENTI)
      [tipo] = Tipo di Record 1 numerico (1=SOGGETTI NON TITOLARI DI PARTITA IVA,2=SOGGETTI TITOLARI DI PARTITA IVA,3=SOGGETTI NON RESIDENTI)
      [codfis] = Codice Fiscale del cliente 16 alfanumerico
      [pariva] = Partita IVA del cliente/fornitore 11 numerico
      [imponibile] = Imponibile o corrispettivo in caso di [tipo] = 1
      [imposta] = Imposta applicata
      [tipoimponibile] = Tipologia Imponibile (1=Imponibile,2 = Non imponibile,3 = Esente,4 = Imponibile con IVA non esposta in fattura )
      [tipooperazione] = Tipologia dell'operazione (1 = Cessione di beni,2 = Prestazione di servizi,3 = Acquisto di beni,4 = Acquisto di servizi )
      [cognome] = Cognome in caso di [tipo] = 3
      [nome] = Nome in caso di [tipo] = 3
      [datnas] = Data di Nascita in caso di [tipo] = 3   (se non valorizzato discrimina viene considerata una persona non fisica)
      [luonas] = Comune o Stato estero di nascita in caso di [tipo] = 3
      [pronas] = Provincia di nascita in caso di [tipo] = 3 (in caso di Stato estero, indicare "EE")
      [stato] = Stato estero del domicilio fiscale in caso di [tipo] = 3 (Indicare uno dei codici, corrispondente allo Stato di residenza della controparte, di
      cui all'Elenco dei Paesi e Territori esteri contenuto nelle istruzioni per la compilazione
      del modello UNICO di dichiarazione dei redditi.)
      [indirizzo] = Indirizzo estero del domicilio fiscale in caso di [tipo] = 3  (se non valorizzato discrimina viene considerata una persona fisica)
      $totali = array bidimensionale (prima dimensione:index [1] =clienti, index[2] = fornitori)  la seconda dimensione deve avere i seguenti index:
      [numero] = Numero di clienti/fornitori 8 numerico
      [imponibile] = Imponibile
      [imposta] = Imposta applicata
      [esente] = Esente
      [nonimp] = Non imponibile
     */
    function createElement($codice, $valore, $padType = STR_PAD_RIGHT) { // FUNZIONE PER CREARE GLI ELEMENTI CON I CAMPI CODICE-VALORE
        $acc = $codice . str_pad(substr($valore, 0, 16), 16, ' ', $padType);
        if (strlen($valore) > 16) {
            $next_str = substr($valore, 16);
            $val = str_split($next_str, 15);
            foreach ($val as $v) {
                $acc .= $codice . '+' . str_pad($v, 15, ' ', $padType);
            }
        }
        return $acc;
    }

    function Record_A() { // RECORD DATI INVIO
        if (!empty($this->Intermediario)) { // intermediario no-intermediario
            $tipofornitore = '10' . substr(str_pad($this->Intermediario, 16, ' ', STR_PAD_RIGHT), 0, 16);
        } else {
            $tipofornitore = '01' . $this->CFContribuente;
        }
        return "A" . str_repeat(' ', 14) . "NSP00" .
                $tipofornitore .
                str_repeat(' ', 483) .
                str_repeat('0', 8) . /* qui andrebbero messi i valori degli invii multipli,
                  che GAzie non gestisce sperando che non si superi il limite di record
                  in una piccola/media azienda (5Mb) */
                str_repeat(' ', 1368) . "A\r\n";
    }

    function Record_B() { // RECORD DATI IDENTIFICATIVI
        if (!empty($this->Intermediario)) { // intermediario no-intermediario
            $impegno_trasmissione = substr(str_pad($this->Intermediario, 16, ' ', STR_PAD_RIGHT), 0, 16) . '000002 ' . date("dmY");
        } else {
            $impegno_trasmissione = '                000000 00000000';
        }
        return "B" . $this->CFContribuente . '00000001' .
                str_repeat(' ', 48) . $this->SoftHouseId . '100' . str_repeat('0', 23) . '01' .
                // qui andranno messi i 12 flag dei quadri compilati
                '000' . // FA,SA,BL al momento non gestiti
                $this->FE . $this->FR . $this->NE . $this->NR . $this->DF . $this->FN . $this->SE .
                '01' . // TU NO, riepilogo (TA)sempre esistente
                $this->PIContribuente . $this->ATECO . $this->telefono . $this->fax . $this->e_mail .
                $this->AltriDati . $this->Anno .
                str_repeat(' ', 18) . str_repeat('0', 18) . str_repeat(' ', 45) . str_repeat('0', 8) . str_repeat(' ', 102) .
                $impegno_trasmissione . str_repeat(' ', 1296) .
                "A\r\n";
    }

    function Record_CD($T, $D) { // RECORD DATI BLACK LIST, OPERAZIONI ANALITICHE, OPERAZIONI AGGREGATE (NON UTILIZZATA)             //                              TIPO
        $this->Intermediario = $T['intermediario'];
        $this->CFContribuente = substr(str_pad($T['codfis'], 16, ' ', STR_PAD_RIGHT), 0, 16);
        $this->PIContribuente = substr(str_pad($T['pariva'], 11, '0', STR_PAD_LEFT), 0, 11);
        $this->SoftHouseId = str_pad($T['pariva'], 16, ' ', STR_PAD_RIGHT);
        $this->ATECO = substr(str_pad($T['ateco'], 6, '0', STR_PAD_LEFT), 0, 6);
        $this->telefono = substr(str_pad(filter_var($T['telefono'], FILTER_SANITIZE_NUMBER_INT), 12, ' ', STR_PAD_RIGHT), 0, 12);
        $this->fax = substr(str_pad(filter_var($T['fax'], FILTER_SANITIZE_NUMBER_INT), 12, ' ', STR_PAD_RIGHT), 0, 12);
        $this->e_mail = substr(str_pad(strtoupper($T['e_mail']), 50, ' ', STR_PAD_RIGHT), 0, 50);
        if (isset($T['sesso'])) {  // PERSONA FISICA
            $this->AltriDati = substr(str_pad($T['cognome'], 24, ' '), 0, 24) .
                    substr(str_pad($T['nome'], 20, ' '), 0, 20) .
                    substr($T['sesso'], 0, 1) .
                    substr($T['datnas'], 8, 2) . substr($T['datnas'], 5, 2) . substr($T['datnas'], 0, 4) .
                    substr(str_pad($T['luonas'], 40, ' '), 0, 40) .
                    substr(str_pad($T['pronas'], 2, ' '), 0, 2) .
                    str_repeat(' ', 60);
        } else {    // PERSONA GIURIDICA
            $this->AltriDati = str_repeat(' ', 45) . str_repeat('0', 8) . str_repeat(' ', 42) . substr(str_pad($T['ragsoc'], 60, ' '), 0, 60);
        }
        $this->Anno = substr(str_pad($T['anno'], 4, '0'), 0, 4);

        $this->FE = 0; //FATTURE EMESSE                D
        $this->FR = 0; //FATTURE RICEVUTE              D  
        $this->FR_riepil = 0; //FATTURE RIEPILOG. RICEVUTE    D  
        $this->NE = 0; //NOTE EMESSE                   D
        $this->NR = 0; //NOTE RICEVUTE                 D
        $this->DF = 0; //OPERAZIONI SENZA FATTURA      D
        $this->FN = 0; //OPERAZIONI NO RESIDENTI       D
        $this->SE = 0; //OPERAZIONI SAN MARINO         D
        $this->progr_D = 1;
        $this->progr_FE = 0;
        $this->progr_FR = 0;
        $this->progr_FR_riepil = 0;
        $this->progr_NE = 0;
        $this->progr_NR = 0;
        $this->progr_DF = 0;
        $this->progr_FN = 0;
        $this->progr_SE = 0;
        $this->accu_D = '';
        $this->D_elements = 0;
        $pad = 1897;
        foreach ($D as $ElementsData) {
            if ($this->D_elements > 40) {
                // l'elemento D potrebbe non avere lo spazio per contenere
                // tutti gli elementi del prossimo movimento allora aggiungo un record
                // e azzero tutti i contatori dei quadri
                $pad = $this->progr_D * 1900 - 3;
                $fe = 0;
                $fr = 0;
                $ne = 0;
                $nr = 0;
                $df = 0;
                $fn = 0;
                $se = 0;
                $this->D_elements = 0;
                $this->progr_D++;
                $this->accu_D = str_pad($this->accu_D, $pad, ' ', STR_PAD_RIGHT) . "A\r\n" .
                        'D' . $this->CFContribuente . str_pad($this->progr_D, 8, '0', STR_PAD_LEFT) . str_repeat(' ', 48) . $this->SoftHouseId;
                $pad = $this->progr_D * 1900 - 3;
            } elseif (empty($this->accu_D)) {
                // inizializzo il primo 
                $fe = 0;
                $fr = 0;
                $ne = 0;
                $nr = 0;
                $df = 0;
                $fn = 0;
                $se = 0;
                $this->accu_D = 'D' . $this->CFContribuente . str_pad($this->progr_D, 8, '0', STR_PAD_LEFT) . str_repeat(' ', 48) . $this->SoftHouseId;
            }
            // adesso compilo i quadri
            switch ($ElementsData['quadro']) {
                case 'FE':  //FATTURE EMESSE                D
                    $fe++;
                    $this->D_elements += 5;
                    if ($fe == 6) { // forzo il prossimo ciclo al passaggio ad un nuovo modulo aumentando il valore di D_elements
                        $this->D_elements = 50;
                    }
                    $this->FE = 1;
                    $this->progr_FE++;
                    $cod_ini = 'FE' . str_pad($fe, 3, '0', STR_PAD_LEFT);
                    if ($ElementsData['pariva'] == 0) { // fattura emessa ad una associazione no profit
                        $this->accu_D .= $this->createElement($cod_ini . '002', $ElementsData['codfis']);
                    } else {
                        $this->accu_D .= $this->createElement($cod_ini . '001', $ElementsData['pariva']);
                    }
                    if ($ElementsData['pariva'] == $this->PIContribuente || $ElementsData['codfis'] == $this->CFContribuente) { // se è una autofattura almeno il CF o la PI coincidono
                        $this->accu_D .= $this->createElement($cod_ini . '006', '1', STR_PAD_LEFT);
                    }
                    $this->accu_D .= $this->createElement($cod_ini . '007', substr($ElementsData['datdoc'], 8, 2) . substr($ElementsData['datdoc'], 5, 2) . substr($ElementsData['datdoc'], 0, 4), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '009', $ElementsData['numdoc'] . '/' . $ElementsData['seziva']) .
                            $this->createElement($cod_ini . '010', round($ElementsData['operazioni_imponibili'] + $ElementsData['operazioni_nonimp'] + $ElementsData['operazioni_esente']), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '011', round($ElementsData['imposte_addebitate']), STR_PAD_LEFT)
                    ;
                    break;
                case 'FR':  //FATTURE RICEVUTE              D
                    $fr++;
                    $this->D_elements += 5;
                    if ($fr == 6) { // forzo il prossimo ciclo al passaggio ad un nuovo modulo aumentando il valore di D_elements
                        $this->D_elements = 50;
                    }
                    $cod_ini = 'FR' . str_pad($fr, 3, '0', STR_PAD_LEFT);
                    if ($ElementsData['riepil'] == 1) { // documento  riepilogativo es.scheda carburante
                        $this->FR_riepil = 1;
                        $this->progr_FR_riepil++;
                        ;
                        $this->accu_D .= $this->createElement($cod_ini . '002', '1', STR_PAD_LEFT);
                    } else {
                        $this->FR = 1;
                        $this->progr_FR++;
                        $this->accu_D .= $this->createElement($cod_ini . '001', $ElementsData['pariva']);
                    }
                    $this->accu_D .= $this->createElement($cod_ini . '003', substr($ElementsData['datdoc'], 8, 2) . substr($ElementsData['datdoc'], 5, 2) . substr($ElementsData['datdoc'], 0, 4), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '004', substr($ElementsData['datreg'], 8, 2) . substr($ElementsData['datreg'], 5, 2) . substr($ElementsData['datreg'], 0, 4), STR_PAD_LEFT);
                    if ($ElementsData['reverse_charge_idtes'] >= 1) { // c'è il reverse charge
                        $this->accu_D .= $this->createElement($cod_ini . '006', '1', STR_PAD_LEFT);
                    }
                    if ($ElementsData['pariva'] == $this->PIContribuente || $ElementsData['codfis'] == $this->CFContribuente) { // se è una autofattura almeno il CF o la PI coincidono
                        $this->accu_D .= $this->createElement($cod_ini . '007', '1', STR_PAD_LEFT);
                    }
                    $this->accu_D .= $this->createElement($cod_ini . '008', round($ElementsData['operazioni_imponibili'] + $ElementsData['operazioni_nonimp'] + $ElementsData['operazioni_esente']), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '009', round($ElementsData['imposte_addebitate']), STR_PAD_LEFT);
                    break;
                case 'NE':  //NOTE EMESSE
                    $ne++;
                    $this->NE = 1;
                    $this->D_elements += 5;
                    if ($ne == 10) { // forzo il prossimo ciclo al passaggio ad un nuovo modulo aumentando il valore di D_elements
                        $this->D_elements = 50;
                    }
                    $this->progr_NE++;
                    $cod_ini = 'NE' . str_pad($ne, 3, '0', STR_PAD_LEFT);
                    if ($ElementsData['pariva'] == 0) { // NOTA DI VARIAZIONE emessa ad un privato (senza partita IVA)
                        $this->accu_D .= $this->createElement($cod_ini . '002', $ElementsData['codfis']);
                    } else {
                        $this->accu_D .= $this->createElement($cod_ini . '001', $ElementsData['pariva']);
                    }
                    $this->accu_D .= $this->createElement($cod_ini . '003', substr($ElementsData['datdoc'], 8, 2) . substr($ElementsData['datdoc'], 5, 2) . substr($ElementsData['datdoc'], 0, 4), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '005', $ElementsData['numdoc'] . '/' . $ElementsData['seziva']) .
                            $this->createElement($cod_ini . '006', round($ElementsData['operazioni_imponibili'] + $ElementsData['operazioni_nonimp'] + $ElementsData['operazioni_esente']), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '007', round($ElementsData['imposte_addebitate']), STR_PAD_LEFT)
                    ;
                    break;
                case 'NR':  //NOTE RICEVUTE
                    $nr++;
                    $this->NR = 1;
                    $this->D_elements += 5;
                    if ($nr == 10) { // forzo il prossimo ciclo al passaggio ad un nuovo modulo aumentando il valore di D_elements
                        $this->D_elements = 50;
                    }
                    $this->progr_NR++;
                    ;
                    $cod_ini = 'NR' . str_pad($nr, 3, '0', STR_PAD_LEFT);
                    $this->accu_D .= $this->createElement($cod_ini . '001', $ElementsData['pariva']) .
                            $this->createElement($cod_ini . '002', substr($ElementsData['datdoc'], 8, 2) . substr($ElementsData['datdoc'], 5, 2) . substr($ElementsData['datdoc'], 0, 4), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '003', substr($ElementsData['datreg'], 8, 2) . substr($ElementsData['datreg'], 5, 2) . substr($ElementsData['datreg'], 0, 4), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '004', round($ElementsData['operazioni_imponibili'] + $ElementsData['operazioni_nonimp'] + $ElementsData['operazioni_esente']), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '005', round($ElementsData['imposte_addebitate']), STR_PAD_LEFT)
                    ;
                    break;
                case 'DF':  //OPERAZIONI SENZA FATTURA      D
                    $df++;
                    $this->DF = 1;
                    $this->D_elements += 3;
                    if ($df == 10) { // forzo il prossimo ciclo al passaggio ad un nuovo modulo aumentando il valore di D_elements
                        $this->D_elements = 50;
                    }
                    $this->progr_DF++;
                    ;
                    $cod_ini = 'DF' . str_pad($df, 3, '0', STR_PAD_LEFT);
                    $this->accu_D .= $this->createElement($cod_ini . '001', $ElementsData['codfis']) .
                            $this->createElement($cod_ini . '002', substr($ElementsData['datreg'], 8, 2) . substr($ElementsData['datreg'], 5, 2) . substr($ElementsData['datreg'], 0, 4), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '003', round($ElementsData['operazioni_imponibili'] + $ElementsData['operazioni_nonimp'] + $ElementsData['operazioni_esente'] + $ElementsData['imposte_addebitate']), STR_PAD_LEFT)
                    ;
                    break;
                case 'FN':  //OPERAZIONI NO RESIDENTI       D
                    $this->FN = 1;
                    break;
                case 'SE':  //OPERAZIONI SAN MARINO         D
                    $se++;
                    $this->SE = 1;
                    $this->D_elements += 10;
                    if ($se == 10) { // forzo il prossimo ciclo al passaggio ad un nuovo modulo aumentando il valore di D_elements
                        $this->D_elements = 50;
                    }
                    $this->progr_SE++;
                    $cod_ini = 'SE' . str_pad($se, 3, '0', STR_PAD_LEFT);
                    $this->accu_D .= $this->createElement($cod_ini . '007', $ElementsData['ragso1'] . ' ' . $ElementsData['ragso2']) .
                            $this->createElement($cod_ini . '008', $ElementsData['citspe']) .
                            $this->createElement($cod_ini . '009', str_pad($ElementsData['cod_agenzia_entrate'], 3, "0", STR_PAD_LEFT), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '010', $ElementsData['indspe']) .
                            $this->createElement($cod_ini . '011', $ElementsData['codfis']) .
                            $this->createElement($cod_ini . '012', substr($ElementsData['datdoc'], 8, 2) . substr($ElementsData['datdoc'], 5, 2) . substr($ElementsData['datdoc'], 0, 4), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '013', substr($ElementsData['datreg'], 8, 2) . substr($ElementsData['datreg'], 5, 2) . substr($ElementsData['datreg'], 0, 4), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '014', $ElementsData['numdoc']) .
                            $this->createElement($cod_ini . '015', round($ElementsData['operazioni_imponibili'] + $ElementsData['operazioni_nonimp'] + $ElementsData['operazioni_esente']), STR_PAD_LEFT) .
                            $this->createElement($cod_ini . '016', round($ElementsData['imposte_addebitate']), STR_PAD_LEFT)

                    ;
                    break;
            }
        }
        return str_pad($this->accu_D, $pad, ' ', STR_PAD_RIGHT) . "A\r\n";
    }

    function Record_E() { // RECORD DATI RIEPILOGATIVI
        $acc = "E" . $this->CFContribuente . '00000001' . str_repeat(' ', 48) . $this->SoftHouseId;
        if ($this->FE > 0) {
            $acc .= 'TA004001' . str_pad($this->progr_FE, 16, ' ', STR_PAD_LEFT);
        }
        if ($this->FR > 0) {
            $acc .= 'TA005001' . str_pad($this->progr_FR, 16, ' ', STR_PAD_LEFT);
        }
        if ($this->FR_riepil > 0) {
            $acc .= 'TA005002' . str_pad($this->progr_FR_riepil, 16, ' ', STR_PAD_LEFT);
        }
        if ($this->NE > 0) {
            $acc .= 'TA006001' . str_pad($this->progr_NE, 16, ' ', STR_PAD_LEFT);
        }
        if ($this->NR > 0) {
            $acc .= 'TA007001' . str_pad($this->progr_NR, 16, ' ', STR_PAD_LEFT);
        }
        if ($this->DF > 0) {
            $acc .= 'TA008001' . str_pad($this->progr_DF, 16, ' ', STR_PAD_LEFT);
        }
        if ($this->SE > 0) {
            $acc .= 'TA010001' . str_pad($this->progr_SE, 16, ' ', STR_PAD_LEFT);
        }
        return str_pad($acc, 1897, ' ', STR_PAD_RIGHT) . "A\r\n";
    }

    function Record_Z() { // RECORD DI CODA
        return "Z" . str_repeat(' ', 14) . '000000001' . str_repeat('0', 9) .
                str_pad($this->progr_D, 9, '0', STR_PAD_LEFT) .
                '000000001' . str_repeat(' ', 1846) . "A\r\n";
    }

    function creaFileART21_poli($testa, $dati) {
        $acc = $this->Record_CD($testa, $dati);
        return $this->Record_A() .
                $this->Record_B() .
                $acc .
                $this->Record_E() .
                $this->Record_Z();
    }

// --- FINE FUNZIONE CREA FILE COMUNICAZIONE POLIVALENTE
}

// --- INIZIO FUNZIONE PER LA CREAZIONE DELLA COMUNICAZIONE DELLE LIQUIDAZIONI PERIODICHE
function creaFileIVP($aziend, $data) {
    $domDoc = new DOMDocument;
	$domDoc->preserveWhiteSpace = false;
	$domDoc->formatOutput = true;
	//ricavo l'anno di imposta (già dal 2018 lo hanno cambiato)
	$y = substr($data['y'],-2);
    $domDoc->load("../../library/include/template_IVP".$y.".xml");
    $xpath = new DOMXPath($domDoc);
    $res = $xpath->query('//*[@identificativo = ""]');
    $res->item(0)->setAttribute('identificativo', $data['trimestre_liquidabile']); // non so se si può utilizzare questo metodo per valorizzare l'identificativo
    $results = $xpath->query("//iv:Fornitura/iv:Comunicazione/iv:Frontespizio/iv:CodiceFiscale")->item(0);
    $attrVal = $domDoc->createTextNode($aziend['codfis']);
    $results->appendChild($attrVal);
    $results = $xpath->query("//iv:Fornitura/iv:Comunicazione/iv:Frontespizio/iv:AnnoImposta")->item(0);
    $attrVal = $domDoc->createTextNode($data['y']);
    $results->appendChild($attrVal);
    $results = $xpath->query("//iv:Fornitura/iv:Comunicazione/iv:Frontespizio/iv:PartitaIVA")->item(0);
    $attrVal = $domDoc->createTextNode($aziend['pariva']);
    $results->appendChild($attrVal);
    $results = $xpath->query("//iv:Fornitura/iv:Comunicazione/iv:Frontespizio/iv:IdentificativoProdSoftware")->item(0);
    $attrVal = $domDoc->createTextNode($aziend['pariva']);
    $results->appendChild($attrVal);
    $results = $xpath->query("//iv:Fornitura/iv:Comunicazione/iv:DatiContabili")->item(0);
	$NumeroModulo=1;
    foreach ($data['mods'] as $k => $v) {
        $el = $domDoc->createElement("iv:Modulo", "");
        // nella comunicazione IVA del 2018 hanno aggiunto questo elemento
		if ($y >= 18) {
            $el1 = $domDoc->createElement("iv:NumeroModulo", $NumeroModulo);
            $el->appendChild($el1);
        }
        if ($aziend['ivam_t'] == 'M') { // mensile
            $el1 = $domDoc->createElement("iv:Mese", intval($k));
        } else { // trimestrale
            $el1 = $domDoc->createElement("iv:Trimestre", intval($k));
        }
        $el->appendChild($el1);
        if ($v['vp2'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:TotaleOperazioniAttive", number_format($v['vp2'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if ($v['vp3'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:TotaleOperazioniPassive", number_format($v['vp3'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if ($v['vp4'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:IvaEsigibile", number_format($v['vp4'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if ($v['vp5'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:IvaDetratta", number_format($v['vp5'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if (($v['vp4'] - $v['vp5']) >= 0.01) { // debito
            $el1 = $domDoc->createElement("iv:IvaDovuta", number_format(round($v['vp4'] - $v['vp5'], 2), 2, ',', ''));
        } elseif (($v['vp4'] - $v['vp5']) <= -0.01) { // credito
            $el1 = $domDoc->createElement("iv:IvaCredito", number_format(round($v['vp5'] - $v['vp4'], 2), 2, ',', ''));
        } else {
            $el1 = $domDoc->createElement("iv:IvaDovuta", number_format(0.00, 2, ',', ''));
        }
        $el->appendChild($el1);
        if ($v['vp7'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:DebitoPrecedente", number_format($v['vp7'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if ($v['vp8'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:CreditoPeriodoPrecedente", number_format($v['vp8'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if ($v['vp9'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:CreditoAnnoPrecedente", number_format($v['vp9'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if ($v['vp10'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:VersamentiAutoUE", number_format($v['vp10'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if ($v['vp11'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:CreditiImposta", number_format($v['vp11'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if ($v['vp12'] >= 0.01) {
            $el1 = $domDoc->createElement("iv:InteressiDovuti", number_format($v['vp12'], 2, ',', ''));
            $el->appendChild($el1);
        }
        if ($v['vp13'] >= 0.01) {
			if ($y >= 18) {
				$el1 = $domDoc->createElement("iv:Metodo", intval($v['vp13m']));
            }
			$el->appendChild($el1);
            $el1 = $domDoc->createElement("iv:Acconto", number_format($v['vp13'], 2, ',', ''));
            $el->appendChild($el1);
        }
        $ImportoDaVersare = round($v['vp4'] - $v['vp5'] + $v['vp7'] - $v['vp8'] - $v['vp9'] - $v['vp10'] - $v['vp11'] + $v['vp12'] - $v['vp13'], 2);
        if ($ImportoDaVersare >= 0.00) { // versamento debito
            $el1 = $domDoc->createElement("iv:ImportoDaVersare", number_format(round($ImportoDaVersare, 2), 2, ',', ''));
        } else { // da riportare a credito
            $el1 = $domDoc->createElement("iv:ImportoACredito", number_format(-round($ImportoDaVersare, 2), 2, ',', ''));
        }
        $el->appendChild($el1);
        $results->appendChild($el);
		$NumeroModulo++;
    }
    $filename = DATA_DIR . 'files/' . $aziend['codice'] . '/' . $aziend['country'] . $aziend['codfis'] . "_LI_" . $data['trimestre_liquidabile'] . ".xml";
    // salvo il file sul server
    $domDoc->save($filename);
}

// --- FINE FUNZIONE PER LA CREAZIONE DELLA COMUNICAZIONE DELLE LIQUIDAZIONI PERIODICHE
// --- INIZIO FUNZIONE PER LA CREAZIONE DELLA COMUNICAZIONE DEI DATI DELLE FATTURE (SPESOMETRO)

function creaHeadFile() {
    
}

function creaFileDAT20($aziend, $data, $periodo) {

    $formatta = function($stringa, $maxlen = 0) {
        $stringa = preg_replace('/[^A-Z0-9 _",\-\'\.]/', '', strtoupper($stringa));
        return $maxlen ? substr($stringa, 0, $maxlen) : $stringa; 
    };

    $denomina = function($azienda, $maxlen = 0) use ($formatta) {
        $ragso12 = $azienda['ragso1'] . ' ' . $azienda['ragso2'];
        $ragso12 = str_replace('&', 'E', $ragso12);
        return $formatta($ragso12, $maxlen);
    };

    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = true;
    $ctrl_partner = 0;
    $ctrl_block_file = FALSE;
    $block_name = array();
    $partner_number = 0; // conto il numeno dei Cessionari e/o Cedenti che non devono superare 1000 
    $file_group_number = 0; // numero di file per ogni blocco
    foreach ($data as $k => $v) {
        // controllo se ho un cambio partner, nel caso mia sia avvicinato al limite di 1000 genero un altro file
        if ($ctrl_partner <> $v['clfoco'] && $partner_number >= 998) {
            $file_group_number++;
        }
        if ($v['regiva'] < 6 && $v['tipo_documento'] != 'TD99') {
            $type = 'DTE';
            // ---------- FATTURE EMESSE --------------
            if ($ctrl_block_file != $type . $file_group_number) { // ho un nuovo file perché è cambiato il tipo oppure ho superato il li limite 1000
				$partner_number = 0;
                if ($ctrl_block_file) { // non è il primo quindi LO scrivo su un nuovo file
                    $file = $aziend['country'] . $aziend['codfis'] . "_DF_" . end($block_name) . substr($periodo, 0, 2) . substr($periodo, -1) . ".xml";
                    $fileurl = DATA_DIR . 'files/' . $aziend['codice'] . '/' . $file;
                    // salvo il file sul server
                    $doc->save($fileurl);
                }
                // stabilisco il nuovo nome
                $block_name[] = 'E' . $file_group_number;
                $doc->load("../../library/include/template_DAT20.xml");
                $xpath = new DOMXPath($doc);
                $res = $xpath->query("//ns2:DatiFattura/DatiFatturaHeader/ProgressivoInvio")->item(0);
                $root = $doc->createTextNode($periodo);
                $res->appendChild($root);
                $res = $xpath->query("//ns2:DatiFattura")->item(0);
                $root = $doc->createElement('DTE');
                $res->appendChild($root);
                $res = $xpath->query("//ns2:DatiFattura/DTE")->item(0);
                // 2.1 - Blocco contenente le informazioni relative al cedente/prestatore (azienda)
                $el_2_1 = $doc->createElement("CedentePrestatoreDTE", "");
                $el_2_1_1 = $doc->createElement("IdentificativiFiscali", "");
                $el_2_1_1_1 = $doc->createElement("IdFiscaleIVA", "");
                $el_2_1_1_1_1 = $doc->createElement("IdPaese", $aziend['country']);
                $el_2_1_1_1->appendChild($el_2_1_1_1_1);
                $el_2_1_1_1_2 = $doc->createElement("IdCodice", $aziend['pariva']);
                $el_2_1_1_1->appendChild($el_2_1_1_1_2);
                $el_2_1_1->appendChild($el_2_1_1_1);
                $el_2_1_1_2 = $doc->createElement("CodiceFiscale", strtoupper($aziend['codfis']));
                $el_2_1_1->appendChild($el_2_1_1_2);
                $el_2_1->appendChild($el_2_1_1);
                $el_2_1_2 = $doc->createElement("AltriDatiIdentificativi", "");
                // la denominazione se persona giuridica, nome e cognome se persona giuridica
                if ($aziend['sexper'] == 'G') {
                    $el_2_1_2_1 = $doc->createElement("Denominazione", $denomina($aziend));
                    $el_2_1_2->appendChild($el_2_1_2_1);
                } else {
                    $el_2_1_2_2 = $doc->createElement("Nome", strtoupper($aziend['legrap_pf_nome']));
                    $el_2_1_2->appendChild($el_2_1_2_2);
                    $el_2_1_2_3 = $doc->createElement("Cognome", strtoupper($aziend['legrap_pf_cognome']));
                    $el_2_1_2->appendChild($el_2_1_2_3);
                }
                $el_2_1_2_4 = $doc->createElement("Sede", '');
                $el_2_1_2_4_1 = $doc->createElement("Indirizzo", $formatta($aziend['indspe']));
                $el_2_1_2_4->appendChild($el_2_1_2_4_1);
                $el_2_1_2_4_3 = $doc->createElement("CAP", $aziend['capspe']);
                $el_2_1_2_4->appendChild($el_2_1_2_4_3);
                $el_2_1_2_4_4 = $doc->createElement("Comune", $formatta($aziend['citspe']));
                $el_2_1_2_4->appendChild($el_2_1_2_4_4);
                $el_2_1_2_4_5 = $doc->createElement("Provincia", strtoupper($aziend['prospe']));
                $el_2_1_2_4->appendChild($el_2_1_2_4_5);
                $el_2_1_2_4_6 = $doc->createElement("Nazione", $aziend['country']);
                $el_2_1_2_4->appendChild($el_2_1_2_4_6);
                $el_2_1_2->appendChild($el_2_1_2_4);
                $el_2_1->appendChild($el_2_1_2);
                $res->appendChild($el_2_1);
            }
            if ($ctrl_partner <> $v['clfoco']) {
                $partner_number++;
                // 2.2 - Blocco contenente le informazioni relative al cessionario/committente (cliente) e ai dati fattura a lui riferiti (reiterabile 1000 volte)
                $el_2_2 = $doc->createElement("CessionarioCommittenteDTE", "");
                $el_2_2_1 = $doc->createElement("IdentificativiFiscali", "");
                if (strlen($v['pariva']) > 5) { // il cliente ha una Partita IVA (metto 5 per evitare problemi con gli stranieri)
                    $el_2_2_1_1 = $doc->createElement("IdFiscaleIVA", "");
                    $el_2_2_1_1_1 = $doc->createElement("IdPaese", $v['country']);
                    $el_2_2_1_1->appendChild($el_2_2_1_1_1);
                    $el_2_2_1_1_2 = $doc->createElement("IdCodice", $v['pariva']);
                    $el_2_2_1_1->appendChild($el_2_2_1_1_2);
                    $el_2_2_1->appendChild($el_2_2_1_1);
                } elseif ($v['country'] != 'IT') { // è uno straniero e non è stata indicata la partita iva, provo con il codice fiscale
                    $el_2_2_1_1 = $doc->createElement("IdFiscaleIVA", "");
                    $el_2_2_1_1_1 = $doc->createElement("IdPaese", $v['country']);
                    $el_2_2_1_1->appendChild($el_2_2_1_1_1);
                    $el_2_2_1_1_2 = $doc->createElement("IdCodice", $v['codfis']);
                    $el_2_2_1_1->appendChild($el_2_2_1_1_2);
                    $el_2_2_1->appendChild($el_2_2_1_1);
                }
                if (preg_match('/[A-Z0-9]{11,16}/', strtoupper(trim($v['codfis']))) && $v['country'] == 'IT') {
                    $el_2_2_1_2 = $doc->createElement("CodiceFiscale", strtoupper($v['codfis']));
                    $el_2_2_1->appendChild($el_2_2_1_2);
                }
                $el_2_2->appendChild($el_2_2_1);
                $el_2_2_2 = $doc->createElement("AltriDatiIdentificativi", "");
                // la denominazione se persona giuridica, nome e cognome se persona giuridica
                if ($v['sexper'] == 'G') {
                    $el_2_2_2_1 = $doc->createElement("Denominazione", $denomina($v, 80));
                    $el_2_2_2->appendChild($el_2_2_2_1);
                } else {
                    $el_2_2_2_2 = $doc->createElement("Nome", $formatta($v['legrap_pf_nome'], 60));
                    $el_2_2_2->appendChild($el_2_2_2_2);
                    $el_2_2_2_3 = $doc->createElement("Cognome", $formatta($v['legrap_pf_cognome'], 60));
                    $el_2_2_2->appendChild($el_2_2_2_3);
                }
                $el_2_2_2_4 = $doc->createElement("Sede", '');
                $el_2_2_2_4_1 = $doc->createElement("Indirizzo", $formatta($v['indspe'], 60));
                $el_2_2_2_4->appendChild($el_2_2_2_4_1);
                if (is_numeric($v['capspe']) && strlen($v['capspe']) <= 5) { // per evitare l'errore (loro) il cap lo scrivo solo se è italiano
                    $el_2_2_2_4_3 = $doc->createElement("CAP", str_pad($v['capspe'], 5, "0", STR_PAD_LEFT));
                    $el_2_2_2_4->appendChild($el_2_2_2_4_3);
                }
                $el_2_2_2_4_4 = $doc->createElement("Comune", strtoupper($v['citspe']));
                $el_2_2_2_4->appendChild($el_2_2_2_4_4);
                if ($v['country'] == 'IT') {
                    $el_2_2_2_4_5 = $doc->createElement("Provincia", strtoupper($v['prospe']));
                    $el_2_2_2_4->appendChild($el_2_2_2_4_5);
                }
                $el_2_2_2_4_6 = $doc->createElement("Nazione", $v['country']);
                $el_2_2_2_4->appendChild($el_2_2_2_4_6);
                $el_2_2_2->appendChild($el_2_2_2_4);
                $el_2_2->appendChild($el_2_2_2);
            }
            // 2.2.3 - Blocco dati fatture (reiterabile 1000 volte)
            $el_2_2_3 = $doc->createElement("DatiFatturaBodyDTE", "");
            $el_2_2_3_1 = $doc->createElement("DatiGenerali", "");
            $el_2_2_3_1_1 = $doc->createElement("TipoDocumento", $v['tipo_documento']);
            $el_2_2_3_1->appendChild($el_2_2_3_1_1);
            $el_2_2_3_1_2 = $doc->createElement("Data", $v['datdoc']);
            $el_2_2_3_1->appendChild($el_2_2_3_1_2);
            $el_2_2_3_1_3 = $doc->createElement("Numero", $v['numdoc']);
            $el_2_2_3_1->appendChild($el_2_2_3_1_3);
            $el_2_2_3->appendChild($el_2_2_3_1);
            foreach ($v['riepilogo'] as $kr => $vr) {
                $el_2_2_3_2 = $doc->createElement("DatiRiepilogo", "");
                $el_2_2_3_2_1 = $doc->createElement("ImponibileImporto", number_format($vr['imponibile'], 2, '.', ''));
                $el_2_2_3_2->appendChild($el_2_2_3_2_1);
                $el_2_2_3_2_2 = $doc->createElement("DatiIVA", '');
                $el_2_2_3_2_2_1 = $doc->createElement("Imposta", number_format($vr['imposta'], 2, '.', ''));
                $el_2_2_3_2_2->appendChild($el_2_2_3_2_2_1);
                $el_2_2_3_2_2_2 = $doc->createElement("Aliquota", number_format($vr['aliquota'], 2, '.', ''));
                $el_2_2_3_2_2->appendChild($el_2_2_3_2_2_2);
                $el_2_2_3_2->appendChild($el_2_2_3_2_2);
                if (!empty($vr['natura'])) {
                    $el_2_2_3_2_3 = $doc->createElement("Natura", $vr['natura']);
                    $el_2_2_3_2->appendChild($el_2_2_3_2_3);
                }
                $el_2_2_3_2_6 = $doc->createElement("EsigibilitaIVA", $vr['esigibilita']);
                $el_2_2_3_2->appendChild($el_2_2_3_2_6);
                $el_2_2_3->appendChild($el_2_2_3_2);
                $el_2_2->appendChild($el_2_2_3);
            }
            $res->appendChild($el_2_2);
        } elseif ($v['regiva'] == 6 && $v['tipo_documento'] != 'TD99') {
            $type = 'DTR';
            // ---------- FATTURE RICEVUTE --------------
            if ($ctrl_block_file != $type . $file_group_number) { // ho un nuovo file perché è cambiato il tipo oppure ho superato il li limite 1000
				$partner_number = 0;
                if ($ctrl_block_file) { // non è il primo quindi LO scrivo su un nuovo file
                    $file = $aziend['country'] . $aziend['codfis'] . "_DF_" . end($block_name) . substr($periodo, 0, 2) . substr($periodo, -1) . ".xml";
                    $fileurl = DATA_DIR . 'files/' . $aziend['codice'] . '/' . $file;
                    // salvo il file sul server
                    $doc->save($fileurl);
                }
                // stabilisco il nuovo nome
                $block_name[] = 'R' . $file_group_number;
                $doc->load("../../library/include/template_DAT20.xml");
                $xpath = new DOMXPath($doc);
                $res = $xpath->query("//ns2:DatiFattura/DatiFatturaHeader/ProgressivoInvio")->item(0);
                $root = $doc->createTextNode($periodo);
                $res->appendChild($root);
                $res = $xpath->query("//ns2:DatiFattura")->item(0);
                $root = $doc->createElement('DTR');
                $res->appendChild($root);
                $res = $xpath->query("//ns2:DatiFattura/DTR")->item(0);
                // 3.1 - Blocco contenente le informazioni relative al cedente/prestatore (azienda)
                $el_3_1 = $doc->createElement("CessionarioCommittenteDTR", "");
                $el_3_1_1 = $doc->createElement("IdentificativiFiscali", "");
                $el_3_1_1_1 = $doc->createElement("IdFiscaleIVA", "");
                $el_3_1_1_1_1 = $doc->createElement("IdPaese", $aziend['country']);
                $el_3_1_1_1->appendChild($el_3_1_1_1_1);
                $el_3_1_1_1_2 = $doc->createElement("IdCodice", $aziend['pariva']);
                $el_3_1_1_1->appendChild($el_3_1_1_1_2);
                $el_3_1_1->appendChild($el_3_1_1_1);
                $el_3_1_1_2 = $doc->createElement("CodiceFiscale", strtoupper($aziend['codfis']));
                $el_3_1_1->appendChild($el_3_1_1_2);
                $el_3_1->appendChild($el_3_1_1);
                $el_3_1_2 = $doc->createElement("AltriDatiIdentificativi", "");
                // la denominazione se persona giuridica, nome e cognome se persona giuridica
                if ($aziend['sexper'] == 'G') {
                    $el_3_1_2_1 = $doc->createElement("Denominazione", $denomina($aziend));
                    $el_3_1_2->appendChild($el_3_1_2_1);
                } else {
                    $el_3_1_2_2 = $doc->createElement("Nome", strtoupper($aziend['legrap_pf_nome']));
                    $el_3_1_2->appendChild($el_3_1_2_2);
                    $el_3_1_2_3 = $doc->createElement("Cognome", strtoupper($aziend['legrap_pf_cognome']));
                    $el_3_1_2->appendChild($el_3_1_2_3);
                }
                $el_3_1_2_4 = $doc->createElement("Sede", '');
                $el_3_1_2_4_1 = $doc->createElement("Indirizzo", strtoupper($aziend['indspe']));
                $el_3_1_2_4->appendChild($el_3_1_2_4_1);
                $el_3_1_2_4_3 = $doc->createElement("CAP", $aziend['capspe']);
                $el_3_1_2_4->appendChild($el_3_1_2_4_3);
                $el_3_1_2_4_4 = $doc->createElement("Comune", strtoupper($aziend['citspe']));
                $el_3_1_2_4->appendChild($el_3_1_2_4_4);
                $el_3_1_2_4_5 = $doc->createElement("Provincia", strtoupper($aziend['prospe']));
                $el_3_1_2_4->appendChild($el_3_1_2_4_5);
                $el_3_1_2_4_6 = $doc->createElement("Nazione", $aziend['country']);
                $el_3_1_2_4->appendChild($el_3_1_2_4_6);
                $el_3_1_2->appendChild($el_3_1_2_4);
                $el_3_1->appendChild($el_3_1_2);
                $res->appendChild($el_3_1);
            }
            if ($ctrl_partner <> $v['clfoco']) {
                $partner_number++;
                // 3.2 - Blocco contenente le informazioni relative al cessionario/committente (fornitore) e ai dati fattura a lui riferiti (reiterabile 1000 volte)
                $el_3_2 = $doc->createElement("CedentePrestatoreDTR", "");
                $el_3_2_1 = $doc->createElement("IdentificativiFiscali", "");
                if (strlen($v['pariva']) > 5) { // il cliente ha una Partita IVA (metto 5 per evitare problemi con gli stranieri)
                    $el_3_2_1_1 = $doc->createElement("IdFiscaleIVA", "");
                    $el_3_2_1_1_1 = $doc->createElement("IdPaese", $v['country']);
                    $el_3_2_1_1->appendChild($el_3_2_1_1_1);
                    $el_3_2_1_1_2 = $doc->createElement("IdCodice", ($v['pariva'] == '99999999999' ? "OO" : $v['pariva']));
                    $el_3_2_1_1->appendChild($el_3_2_1_1_2);
                    $el_3_2_1->appendChild($el_3_2_1_1);
                } elseif ($v['country'] != 'IT') { // è uno straniero e non è stata indicata la partita iva, provo con il codice fiscale
                    $el_3_2_1_1 = $doc->createElement("IdFiscaleIVA", "");
                    $el_3_2_1_1_1 = $doc->createElement("IdPaese", $v['country']);
                    $el_3_2_1_1->appendChild($el_3_2_1_1_1);
                    $el_3_2_1_1_2 = $doc->createElement("IdCodice", strtoupper(trim($v['codfis'])));
                    $el_3_2_1_1->appendChild($el_3_2_1_1_2);
                    $el_3_2_1->appendChild($el_3_2_1_1);
                }
                if (preg_match('/[A-Z0-9]{11,16}/', strtoupper(trim($v['codfis']))) && $v['country'] == 'IT') {
                    $el_3_2_1_2 = $doc->createElement("CodiceFiscale", strtoupper($v['codfis']));
                    $el_3_2_1->appendChild($el_3_2_1_2);
                }
                $el_3_2->appendChild($el_3_2_1);
                $el_3_2_2 = $doc->createElement("AltriDatiIdentificativi", "");
                // la denominazione se persona giuridica, nome e cognome se persona giuridica
                if ($v['sexper'] == 'G') {
                    $el_3_2_2_1 = $doc->createElement("Denominazione", $denomina($v, 80));
                    $el_3_2_2->appendChild($el_3_2_2_1);
                } else {
                    $el_3_2_2_2 = $doc->createElement("Nome", $formatta($v['legrap_pf_nome'], 60));
                    $el_3_2_2->appendChild($el_3_2_2_2);
                    $el_3_2_2_3 = $doc->createElement("Cognome", $formatta($v['legrap_pf_cognome'], 60));
                    $el_3_2_2->appendChild($el_3_2_2_3);
                }
                $el_3_2_2_4 = $doc->createElement("Sede", '');
                $el_3_2_2_4_1 = $doc->createElement("Indirizzo", $formatta($v['indspe'], 60));
                $el_3_2_2_4->appendChild($el_3_2_2_4_1);
                if (is_numeric($v['capspe']) && strlen($v['capspe']) <= 5) { // per evitare l'errore (loro) il cap lo scrivo solo se è italiano
                    $el_3_2_2_4_3 = $doc->createElement("CAP", str_pad($v['capspe'], 5, "0", STR_PAD_LEFT));
                    $el_3_2_2_4->appendChild($el_3_2_2_4_3);
                }
                $el_3_2_2_4_4 = $doc->createElement("Comune", strtoupper($v['citspe']));
                $el_3_2_2_4->appendChild($el_3_2_2_4_4);
                if ($v['country'] == 'IT') {
                    $el_3_2_2_4_5 = $doc->createElement("Provincia", strtoupper($v['prospe']));
                    $el_3_2_2_4->appendChild($el_3_2_2_4_5);
                }
                $el_3_2_2_4_6 = $doc->createElement("Nazione", $v['country']);
                $el_3_2_2_4->appendChild($el_3_2_2_4_6);
                $el_3_2_2->appendChild($el_3_2_2_4);
                $el_3_2->appendChild($el_3_2_2);
            }
            // 3.2.3 - Blocco dati fatture (reiterabile 1000 volte)
            $el_3_2_3 = $doc->createElement("DatiFatturaBodyDTR", "");
            $el_3_2_3_1 = $doc->createElement("DatiGenerali", "");
            $el_3_2_3_1_1 = $doc->createElement("TipoDocumento", $v['tipo_documento']);
            $el_3_2_3_1->appendChild($el_3_2_3_1_1);
            $el_3_2_3_1_2 = $doc->createElement("Data", $v['datdoc']);
            $el_3_2_3_1->appendChild($el_3_2_3_1_2);
            $el_3_2_3_1_3 = $doc->createElement("Numero", $v['numdoc']);
            $el_3_2_3_1->appendChild($el_3_2_3_1_3);
            $el_3_2_3_1_4 = $doc->createElement("DataRegistrazione", $v['datreg']);
            $el_3_2_3_1->appendChild($el_3_2_3_1_4);
            $el_3_2_3->appendChild($el_3_2_3_1);
            foreach ($v['riepilogo'] as $kr => $vr) {
                $el_3_2_3_2 = $doc->createElement("DatiRiepilogo", "");
                $el_3_2_3_2_1 = $doc->createElement("ImponibileImporto", number_format($vr['imponibile'], 2, '.', ''));
                $el_3_2_3_2->appendChild($el_3_2_3_2_1);
                $el_3_2_3_2_2 = $doc->createElement("DatiIVA", '');
                $el_3_2_3_2_2_1 = $doc->createElement("Imposta", number_format($vr['imposta'], 2, '.', ''));
                $el_3_2_3_2_2->appendChild($el_3_2_3_2_2_1);
                $el_3_2_3_2_2_2 = $doc->createElement("Aliquota", number_format($vr['aliquota'], 2, '.', ''));
                $el_3_2_3_2_2->appendChild($el_3_2_3_2_2_2);
                $el_3_2_3_2->appendChild($el_3_2_3_2_2);
                if (!empty($vr['natura'])) {
                    $el_3_2_3_2_3 = $doc->createElement("Natura", $vr['natura']);
                    $el_3_2_3_2->appendChild($el_3_2_3_2_3);
                }
                if ($vr['detraibile'] === 0.00) { // in caso di IVA indetraibile la indico sull'apposito elemento
                    $el_3_2_3_2_4 = $doc->createElement("Detraibile", '0.00');
                    $el_3_2_3_2->appendChild($el_3_2_3_2_4);
                }
                $el_3_2_3_2_6 = $doc->createElement("EsigibilitaIVA", $vr['esigibilita']);
                $el_3_2_3_2->appendChild($el_3_2_3_2_6);
                $el_3_2_3->appendChild($el_3_2_3_2);
                $el_3_2->appendChild($el_3_2_3);
            }
            $res->appendChild($el_3_2);
        }
        $ctrl_partner = $v['clfoco'];
        $ctrl_block_file = $type . $file_group_number;
    }
    // sono uscito dal ciclo allora scrivo l'ultimo file sul fs
    $file = $aziend['country'] . $aziend['codfis'] . "_DF_" . end($block_name) . substr($periodo, 0, 2) . substr($periodo, -1) . ".xml";
    $fileurl = DATA_DIR . 'files/' . $aziend['codice'] . '/' . $file;
    // salvo il file sul server
    $doc->save($fileurl);

    // infine creo lo zip partendo dall'array con i riferimenti ai files
    $baseurl = DATA_DIR . 'files/' . $aziend['codice'] . '/' . $aziend['country'] . $aziend['codfis'] . "_DF_";
    $zipname = $baseurl . 'Z' . $periodo . ".zip";
    $zip = new ZipArchive();
    if ($zip->open($zipname, ZIPARCHIVE::CREATE) !== TRUE) {
        exit("non sono riuscito creare il file ZIP");
    } else {
        foreach ($block_name as $vbn) {
            $zip->addFile($baseurl . $vbn . substr($periodo, 0, 2) . substr($periodo, -1) . ".xml", basename($baseurl . $vbn . substr($periodo, 0, 2) . substr($periodo, -1) . ".xml"));
			$acc['files'][] = $aziend['country'] . $aziend['codfis'] . "_DF_".$vbn . substr($periodo, 0, 2) . substr($periodo, -1) . ".xml";
			}
        $zip->close();
        $acc['ZIP'] = $aziend['country'] . $aziend['codfis'] . '_DF_Z' . $periodo . ".zip";
    }
    return $acc;
}
// --- FINE FUNZIONI PER LA CREAZIONE DELLA COMUNICAZIONE DEI DATI DELLE FATTURE (SPESOMETRO)


// --- INIZIO CREAZIONE DELL'XML PER LA COMUNICAZIONE DELLE CHIUSURE GIORNALIERE DEL REGISTRATORE TELEMATICO
function creaFileCOR10($aziend,$data,$progressivo) {
	$progressivo=str_pad($progressivo, 4, "0", STR_PAD_LEFT);
    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = true;
    $doc->load("../../library/include/template_DAT20.xml"); // PRENDO LO STESSO TEMPLATE DELLA COMUNICAZIONE DATI FATTURE (SPESOMETRO) E NON COR10
    $xpath = new DOMXPath($doc);
    $res = $xpath->query("//ns2:DatiFattura/DatiFatturaHeader/ProgressivoInvio")->item(0);
    $root = $doc->createTextNode('C'.$progressivo);
    $res->appendChild($root);
    $res = $xpath->query("//ns2:DatiFattura")->item(0);
    $root = $doc->createElement('DTE');
    $res->appendChild($root);
    $res = $xpath->query("//ns2:DatiFattura/DTE")->item(0);
    // 2.1 - Blocco contenente le informazioni relative al cedente/prestatore (azienda)
    $el_2_1 = $doc->createElement("CedentePrestatoreDTE", "");
    $el_2_1_1 = $doc->createElement("IdentificativiFiscali", "");
    $el_2_1_1_1 = $doc->createElement("IdFiscaleIVA", "");
    $el_2_1_1_1_1 = $doc->createElement("IdPaese", $aziend['country']);
    $el_2_1_1_1->appendChild($el_2_1_1_1_1);
    $el_2_1_1_1_2 = $doc->createElement("IdCodice", $aziend['pariva']);
    $el_2_1_1_1->appendChild($el_2_1_1_1_2);
    $el_2_1_1->appendChild($el_2_1_1_1);
    $el_2_1_1_2 = $doc->createElement("CodiceFiscale", strtoupper($aziend['codfis']));
    $el_2_1_1->appendChild($el_2_1_1_2);
    $el_2_1->appendChild($el_2_1_1);
    $res->appendChild($el_2_1);
    $el_2_2 = $doc->createElement("CessionarioCommittenteDTE", "");
    $el_2_2_1 = $doc->createElement("IdentificativiFiscali", "");
    $el_2_2_1_1 = $doc->createElement("IdFiscaleIVA", "");
    $el_2_2_1_1_1 = $doc->createElement("IdPaese", 'XX');
    $el_2_2_1_1->appendChild($el_2_2_1_1_1);
    $el_2_2_1_1_2 = $doc->createElement("IdCodice", 'COR10');
    $el_2_2_1_1->appendChild($el_2_2_1_1_2);
    $el_2_2_1->appendChild($el_2_2_1_1);
    $el_2_2->appendChild($el_2_2_1);
    foreach ($data as $k => $v) {
        // 2.2.3 - Blocco dati fatture (reiterabile 1000 volte)
        $el_2_2_3 = $doc->createElement("DatiFatturaBodyDTE", "");
        $el_2_2_3_1 = $doc->createElement("DatiGenerali", "");
        $el_2_2_3_1_1 = $doc->createElement("TipoDocumento", 'TD12');
        $el_2_2_3_1->appendChild($el_2_2_3_1_1);
        $el_2_2_3_1_2 = $doc->createElement("Data", $k);
        $el_2_2_3_1->appendChild($el_2_2_3_1_2);
        $el_2_2_3_1_3 = $doc->createElement("Numero", '1');
        $el_2_2_3_1->appendChild($el_2_2_3_1_3);
        $el_2_2_3->appendChild($el_2_2_3_1);
        foreach ($v as $kr => $vr) {
			if ($kr=='tot_imponibile_giorno')continue;
            $el_2_2_3_2 = $doc->createElement("DatiRiepilogo", "");
            $el_2_2_3_2_1 = $doc->createElement("ImponibileImporto", number_format($vr['imponi'], 2, '.', ''));
            $el_2_2_3_2->appendChild($el_2_2_3_2_1);
            $el_2_2_3_2_2 = $doc->createElement("DatiIVA", '');
            $el_2_2_3_2_2_1 = $doc->createElement("Imposta", number_format($vr['impost'], 2, '.', ''));
            $el_2_2_3_2_2->appendChild($el_2_2_3_2_2_1);
            $el_2_2_3_2_2_2 = $doc->createElement("Aliquota", number_format($vr['periva'], 2, '.', ''));
            $el_2_2_3_2_2->appendChild($el_2_2_3_2_2_2);
            if (!empty($vr['fae_natura'])) {
                $el_2_2_3_2_2_3 = $doc->createElement("Natura", $vr['fae_natura']);
                $el_2_2_3_2_2->appendChild($el_2_2_3_2_2_3);
            }
            $el_2_2_3_2->appendChild($el_2_2_3_2_2);
			$el_2_2_3->appendChild($el_2_2_3_2);
        }
		$el_2_2->appendChild($el_2_2_3);
	}
    $res->appendChild($el_2_2);
    // sono uscito dal ciclo allora scrivo il file sul fs
    $file = $aziend['country'] . $aziend['codfis'] . "_DF_C" . $progressivo . ".xml";
    $fileurl = DATA_DIR . 'files/' . $aziend['codice'] . '/' . $file;
    // salvo il file sul server
    $doc->save($fileurl);
}
// --- FINE CREAZIONE DELL'XML PER LA COMUNICAZIONE DELLE CHIUSURE GIORNALIERE DEL REGISTRATORE TELEMATICO
?>