<?php

/**
* Questa classe genera il file MAV standard ABI-CBI
*
* @author Alessandro Pasotti - ItOpen.it
* @license GPL
*
* passando alla funzione "andata" i due array di seguito specificati:

$intestazione (per tutto il flusso):
============================================================
sia_mittente                obbligatorio
abi_assuntrice              obbligatorio
data_creazione              obbligatorio (GGMMAA)
nome_supporto               opzionale (max 20 caratteri)
cab_assuntrice              obbligatorio
conto                       obbligatorio
sia_ordinante               opzionale (normalmente = sia_mittente)
tipo_codice                 opzionale (1 = utenza, 2 = matricola, 3 = cf, 4 = cod. cliente, 5 = cod. forn., 6 = portaf. comm., 9 = altri)
codice_cliente              opzionale (max 16 caratteri)
ragione_soc1_creditore      obbligatorio (max 24 caratteri)
ragione_soc2_creditore      opzionale (max 24 caratteri)
indirizzo_creditore         opzionale (max 24 caratteri)
cap_citta_prov_creditore    opzionale (max 24 caratteri)


$records (per il singolo MAV):
===========================================================
scadenza                    obbligatorio (GGMMAA)
importo                     obbligatorio (in centesimi di euro)
nome_debitore               obbligatorio (max 60 caratteri)
codice_fiscale_debitore     opzionale
indirizzo_debitore          obbligatorio (max 30 caratteri)
cap_debitore                obbligatorio
comune_provincia_debitore   obbligatorio (max 25 caratteri)
descrizione_domiciliataria  opzionale (max 50 caratteri)
descrizione_debito          obbligatorio (max 80 caratteri)
numero_disposizione obbligatorio (max 10 caratteri)
tipo_bollettino             facoltativo, default "P" (blank (secondo accordi), "P" = postale, "B" = bancario)


*/
class MavAbiCbi {
    var $progressivo;
    var $sia_mittente;
    var $abi_assuntrice;
    var $data_creazione;
    var $nome_supporto;
    var $totale;
    var $creditore;

    /**
    * Costruttore
    *
    * @param boolean $debug
    */
    function __construct($debug = false){
        $this->debug = $debug;
    }

    /**
    * Record di testa
    *
    * @param string $sia_mittente codice SIA azienda mittente
    * @param string $abi_assuntrice codice ABI banca assuntrice
    * @param date $data_creazione in formato GGMMAA
    * @param string $nome_supporto (20 caratteri, libero), opzionale
    */
    function RecordIM($sia_mittente, $abi_assuntrice, $data_creazione, $nome_supporto = '') { //record di testa
        $this->sia_mittente =  str_pad(substr($sia_mittente,0,5),5,'0',STR_PAD_LEFT); // codice_sia 5 car
        $this->abi_assuntrice = str_pad(substr($abi_assuntrice,0,5),5,'0',STR_PAD_LEFT);
        $this->data_creazione = str_pad(substr($data_creazione,0,6),6,'0');
        $this->nome_supporto =  str_pad(substr(iconv('UTF-8', 'ASCII//TRANSLIT', $nome_supporto), 0, 20),20,'*',STR_PAD_RIGHT);
        return " IM"
        . $this->sia_mittente
        . $this->abi_assuntrice
        . $this->data_creazione
        . $this->nome_supporto
        . str_repeat(" ",74)
        . 'E'
        . str_repeat(" ",6);
    }


    /**
    * Record di coda
    */
    function RecordEF($richiamo) {
        return " EF"
        . $this->sia_mittente
        . $this->abi_assuntrice
        . $this->data_creazione
        . $this->nome_supporto
        . str_repeat(" ",6)
        . str_pad($this->progressivo,7,'0',STR_PAD_LEFT)
        . ($richiamo ? str_repeat("0",15) : str_pad($this->totale,15,'0',STR_PAD_LEFT))
        . ($richiamo ? str_pad($this->totale,15,'0',STR_PAD_LEFT) : str_repeat("0",15))
        . str_pad($this->progressivo*7+2,7,'0',STR_PAD_LEFT) // 7 è il numero record ?
        . str_repeat(" ",24)
        . 'E'
        . str_repeat(" ",6);
    }


    /**
    * Importo e scadenza
    *
    * @param boolean $richiamo se vero, mav richiamo
    * @param date $data_scadenza in formato GGMMAA
    * @param decimal $importo (in centesimi di euro)
    * @param string $cab_assuntrice
    * @param string $conto
    * @param string $sia_ordinante (normalmente = sia_mittente), opzionale
    * @param integer $tipo_codice (1 = utenza, 2 = matricola, 3 = cf, 4 = cod. cliente, 5 = cod. forn., 6 = portaf. comm., 9 = altri), opzionale
    * @param string $codice_cliente, opzionale
    */
    function Record14($richiamo, $data_scadenza, $importo, $cab_assuntrice, $conto, $sia_ordinante = '', $tipo_codice = ' ', $codice_cliente = '' ){
        $importo = preg_replace('/[^[0-9]/', '', $importo);
        $this->totale += $importo;
        return " 14"
        . str_pad($this->progressivo,7,'0',STR_PAD_LEFT)
        . str_repeat(" ",12)
        . $data_scadenza // GGMMAA
        . ($richiamo ? "07100" : "07000")
        . str_pad($importo,13,'0',STR_PAD_LEFT)
        . ($richiamo ? "+" : "-")
        // Assuntrice
        . str_pad($this->abi_assuntrice,5,'0',STR_PAD_LEFT)
        . str_pad($cab_assuntrice,5,'0',STR_PAD_LEFT)
        . str_pad($conto, 12)
        . str_repeat('-', 22) // filler -

        // Creditrice
        . str_pad($sia_ordinante ,5,'0',STR_PAD_LEFT)
        . str_pad(substr($tipo_codice, 0, 1), 1) // 1 car
        . str_pad($codice_cliente,16)
        . str_repeat(" ",6)
        . 'E'; // codice_divisa fisso euro
    }

    /**
    * Descrizione del creditore
    *
    * @param string $ragione_soc1_creditore (24 caratteri)
    * @param string $ragione_soc2_creditore (24 caratteri), opzionale
    * @param string $indirizzo_creditore (24 caratteri), opzionale
    * @param string $cap_citta_prov_creditore (24 caratteri), opzionale
    */
    function Record20($ragione_soc1_creditore, $ragione_soc2_creditore = '', $indirizzo_creditore = '', $cap_citta_prov_creditore = '') {
        $this->creditore =  str_pad(iconv('UTF-8', 'ASCII//TRANSLIT', $ragione_soc1_creditore),24) ;
        return " 20"
        . str_pad($this->progressivo,7,'0',STR_PAD_LEFT)
        . substr($this->creditore,0,24)
        . substr(str_pad(iconv('UTF-8', 'ASCII//TRANSLIT', $ragione_soc2_creditore),24),0,24) // Non usato
        . substr(str_pad(iconv('UTF-8', 'ASCII//TRANSLIT', $indirizzo_creditore),24),0,24)
        . substr(str_pad(iconv('UTF-8', 'ASCII//TRANSLIT', $cap_citta_prov_creditore),24),0,24)
        . str_repeat(" ",14);
    }

    /**
    * Descrizione del debitore (nome e CF)
    *
    * @param string $nome_debitore (60 caratteri)
    * @param string $codice_fiscale_debitore (30 caratteri), opzionale
    */
    function Record30($nome_debitore, $codice_fiscale_debitore = '') {
        return " 30"
        . str_pad($this->progressivo,7,'0',STR_PAD_LEFT)
        . substr(str_pad(iconv('UTF-8', 'ASCII//TRANSLIT', $nome_debitore),60),0,60)
        . str_pad($codice_fiscale_debitore,16,' ')
        . str_repeat(" ",34);
    }

    /**
    * Descrizione del debitore (indirizzo)
    *
    * @param string $indirizzo_debitore
    * @param string $cap_debitore
    * @param string $comune_provincia_debitore
    * @param string $descrizione_domiciliataria, opzionale
    */
    function Record40($indirizzo_debitore, $cap_debitore, $comune_provincia_debitore, $descrizione_domiciliataria=""){
        return " 40"
        . str_pad($this->progressivo,7,'0',STR_PAD_LEFT)
        . substr(str_pad(iconv('UTF-8', 'ASCII//TRANSLIT', $indirizzo_debitore),30),0,30)
        . str_pad($cap_debitore,5)
        . substr(str_pad(iconv('UTF-8', 'ASCII//TRANSLIT', $comune_provincia_debitore),25),0,25)
        . substr(str_pad(iconv('UTF-8', 'ASCII//TRANSLIT', $descrizione_domiciliataria),50),0,50);
    }

    /**
    * Causale del debito e CF creditore
    *
    * @param string $descrizione_debito
    */
    function Record50($descrizione_debito){
        return " 50"
        . str_pad($this->progressivo,7,'0',STR_PAD_LEFT)
        . substr(str_pad(iconv('UTF-8', 'ASCII//TRANSLIT', $descrizione_debito),80), 0, 80)
        . str_repeat(" ",30);
    }

    /**
    * Numero della disposizione di pagamento
    *
    * @param string $numero_disposizione
    */
    function Record51($numero_disposizione){
        return " 51"
        . str_pad($this->progressivo,7,'0',STR_PAD_LEFT)
        . str_pad($numero_disposizione,10,'0',STR_PAD_LEFT)
        . str_repeat(' ', 100);
        //. substr($this->creditore,0,20)
        //. str_repeat(" ",80);
    }

    /**
    * Tipo bollettino di pagamento
    *
    * @param char $tipo_bollettino può essere blank (secondo accordi), P = postale, B = bancario
    */
    function Record70($tipo_bollettino = 'P')   {
        return " 70"
        . str_pad($this->progressivo,7,'0',STR_PAD_LEFT)
        . str_repeat(" ",110);
    }

    /**
    * Helper per invio e richiamo
    *
    * @param array $intestazione
    * @param array $records
    */
    function _helper($richiamo, &$intestazione, &$records){
        $accumulatore = array();
        $this->progressivo = 0;
        $this->totale = 0;
        $accumulatore['RecordIM'] = $this->RecordIM($intestazione['sia_mittente'], $intestazione['abi_assuntrice'], $intestazione['data_creazione'], $intestazione['nome_supporto']);
        foreach ($records as $value) { //estraggo le ricevute dall'array
            $this->progressivo ++;
            $accumulatore['Record14_'. $this->progressivo] = $this->Record14($richiamo, $value['scadenza'], $value['importo'], $intestazione['cab_assuntrice'], $intestazione['conto'], $intestazione['sia_ordinante'], $value['tipo_codice'], $value['codice_cliente']);
            $accumulatore['Record20_'. $this->progressivo] = $this->Record20($intestazione['ragione_soc1_creditore'], $intestazione['ragione_soc2_creditore'], $intestazione['indirizzo_creditore'], $intestazione['cap_citta_prov_creditore']);
            $accumulatore['Record30_'. $this->progressivo] = $this->Record30($value['nome_debitore'], $value['codice_fiscale_debitore']);
            $accumulatore['Record40_'. $this->progressivo] = $this->Record40($value['indirizzo_debitore'], $value['cap_debitore'], $value['comune_provincia_debitore'], $value['descrizione_domiciliataria']);
            $accumulatore['Record50_'. $this->progressivo] = $this->Record50($value['descrizione_debito']);
            $accumulatore['Record51_'. $this->progressivo] = $this->Record51($value['numero_disposizione']);
            $accumulatore['Record70_'. $this->progressivo] = $this->Record70($value['tipo_bollettino']);
        }
        $accumulatore['RecordEF'] = $this->RecordEF($richiamo);
        if($this->debug){
            foreach($accumulatore as $k => $v){
                echo "\n$k\n";
                echo str_repeat('123456789 ', 12)."\n";
                echo "$v\n";
                echo strlen($v) . "\n";
            }
        }
        return $accumulatore;
    }


    /**
    * Crea i MAV in andata
    *
    * @param array $intestazione
    * @param array $records
    */
    function invio(&$intestazione, &$records)   {
        return $this->_helper(false, $intestazione, $records);
    }



    /**
    * Crea i MAV di richiamo
    */
    function richiamo(&$intestazione, &$records) {
        return $this->_helper(true, $intestazione, $records);
    }

    /**
    * Analizza i flussi di ritorno
    *
    * Estrae i record da un flusso MAV e li inserisce in un array multidimensionale,
    * indicizzato per tipo record
    *
    * @return array
    */
    function ritorno($text){
        // Elimina caratteri a capo
        $text = preg_replace('|[\x0a\x0d]|', '', $text);
        // Spezza in blocchi da 120 caratteri
        $a = str_split ($text, 120);
        $records = array();
        foreach($a as $r){
            $tipo_record = $this->_estrai_spezzone($r, 2, 3);
            switch($tipo_record) {
                case 'IM':
                    $records['IM']['abi_assuntrice']    = $this->_estrai_spezzone($r, 4, 8 ); // invertito
                    $records['IM']['sia_mittente']      = $this->_estrai_spezzone($r, 9, 13); // invertito
                    $records['IM']['data_creazione']    = $this->_estrai_spezzone($r, 14, 19);
                    $records['IM']['nome_supporto']     = $this->_estrai_spezzone($r, 20, 39);
                    $records['IM']['codice_divisa']     = $this->_estrai_spezzone($r, 114, 114);
                break;
                case 'EF':
                    $records['EF']['abi_assuntrice']    = $this->_estrai_spezzone($r, 4, 8 ); // invertito
                    $records['EF']['sia_mittente']      = $this->_estrai_spezzone($r, 9, 13); // invertito
                    $records['EF']['data_creazione']    = $this->_estrai_spezzone($r, 14, 19);
                    $records['EF']['nome_supporto']     = $this->_estrai_spezzone($r, 20, 39);
                    $records['EF']['numero_disposizioni']= $this->_estrai_spezzone($r, 46, 52);
                    $records['EF']['importi_negativi']  = (int)$this->_estrai_spezzone($r, 53, 67);
                    $records['EF']['importi_positivi']  = (int)$this->_estrai_spezzone($r, 68, 82);
                    $records['EF']['numero_record']     = $this->_estrai_spezzone($r, 83, 89);
                    $records['EF']['codice_divisa']     = $this->_estrai_spezzone($r, 114, 114);
                break;
                case '14':
                    $progressivo = $this->_estrai_spezzone($r, 4, 10 );
                    $records["$progressivo"]['data_scadenza']   = $this->_estrai_spezzone($r, 23, 28);
                    $records["$progressivo"]['causale']         = $this->_estrai_spezzone($r, 29, 33);
                    switch($records["$progressivo"]['causale']) {
                        case '07006' : $causale_desc = 'disposizione resa al carico'; break;
                        case '07008' : $causale_desc = 'disposizione richiamata'; break;
                        case '07000' : $causale_desc = 'disposizione pagata'; break;
                        case '07010' : $causale_desc = 'radiata'; break;
                        case '07011' : $causale_desc = 'disposizione pagata successivamente alla radiazione'; break;
                        default : $causale_desc = '!!! causale sconosciuta !!!';
                    }
                    $records["$progressivo"]['causale_desc']    = $causale_desc;
                    $records["$progressivo"]['importo']         = (int)$this->_estrai_spezzone($r, 34, 46);
                    $records["$progressivo"]['segno']           = $this->_estrai_spezzone($r, 47, 47);
                    $records["$progressivo"]['abi_esattrice']   = $this->_estrai_spezzone($r, 48, 52);
                    $records["$progressivo"]['cab_esattrice']   = $this->_estrai_spezzone($r, 53, 57);
                    $records["$progressivo"]['abi_assuntrice']  = $this->_estrai_spezzone($r, 70, 74);
                    $records["$progressivo"]['cab_assuntrice']  = $this->_estrai_spezzone($r, 75, 79);
                    $records["$progressivo"]['conto']           = $this->_estrai_spezzone($r, 80, 91);
                    $records["$progressivo"]['sia_ordinante']   = $this->_estrai_spezzone($r, 92, 96);
                    $records["$progressivo"]['tipo_codice']     = $this->_estrai_spezzone($r, 97, 97);
                    $records["$progressivo"]['codice_cliente']  = $this->_estrai_spezzone($r, 98, 113);
                    $records["$progressivo"]['codice_divisa']   = $this->_estrai_spezzone($r, 120, 120);
                break;
                case '20': // descr creditore
                    $progressivo = $this->_estrai_spezzone($r, 4, 10 );
                break;
                case '30': // descr debitore
                    $progressivo = $this->_estrai_spezzone($r, 4, 10 );
                    $records["$progressivo"]['ragione_soc1_creditore']  = $this->_estrai_spezzone($r, 11, 40);
                    $records["$progressivo"]['ragione_soc2_creditore']  = $this->_estrai_spezzone($r, 41, 70);
                    $records["$progressivo"]['codice_fiscale_debitore'] = $this->_estrai_spezzone($r, 71, 86);
                break;
                case '40': // indirizzo debitore
                    $progressivo = $this->_estrai_spezzone($r, 4, 10 );
                break;
                case '50': // descr debito
                    $progressivo = $this->_estrai_spezzone($r, 4, 10 );
                    $records["$progressivo"]['descrizione_debito']  = $this->_estrai_spezzone($r, 11, 80);
                break;
                case '51': // estermi disposizione
                    $progressivo = $this->_estrai_spezzone($r, 4, 10 );
                    $records["$progressivo"]['numero_disposizione'] = $this->_estrai_spezzone($r, 11, 20);
                    $records["$progressivo"]['codice_identificativo_univoco'] =  $this->_estrai_spezzone($r, 75, 86);
                    $records["$progressivo"]['spese'] =  $this->_estrai_spezzone($r, 87, 91);
                    $records["$progressivo"]['valuta_addebito'] =  $this->_estrai_spezzone($r, 92, 97);
                    $records["$progressivo"]['data_effettiva_pagamento'] =  $this->_estrai_spezzone($r, 110, 115);
                break;
                case '70': // tipo bollettino
                    $progressivo = $this->_estrai_spezzone($r, 4, 10 );
                    // Salta promemoria contabile
                    if(isset($records["$progressivo"]['tipo_bollettino'])){
                        break;
                    }
                    $records["$progressivo"]['tipo_bollettino'] = $this->_estrai_spezzone($r, 94, 94);
                break;
                // Promemoria contabile
                case '10': // tipo bollettino
                    $progressivo = $this->_estrai_spezzone($r, 4, 10 );
                break;

                default:
                    throw new Exception ('Tipo record sconosciuto : ' . $tipo_record . "\n" . $r);
            }
        }
        return $records;
    }

    function _estrai_spezzone($text, $start, $end){
        return trim(substr($text, $start - 1, $end - $start + 1));
    }

// funzione per GAzie
    function creaFile($intestazione,$records)
             {
             $accumulatore = $this->RecordIM($intestazione['sia_mittente'], $intestazione['abi_assuntrice'], $intestazione['data_creazione'], $intestazione['nome_supporto']);
             foreach ($records as $value) { //estraggo i record dall'array
                     $this->progressivo ++;
                     $accumulatore .= $this->Record14(false, $value['scadenza'], $value['importo'], $intestazione['cab_assuntrice'], $intestazione['conto'], $intestazione['sia_ordinante'], $value['tipo_codice'], $value['codice_cliente']);
                     $accumulatore .= $this->Record20($intestazione['ragione_soc1_creditore'], $intestazione['ragione_soc2_creditore'], $intestazione['indirizzo_creditore'], $intestazione['cap_citta_prov_creditore']);
                     $accumulatore .= $this->Record30($value['nome_debitore'], $value['codice_fiscale_debitore']);
                     $accumulatore .= $this->Record40($value['indirizzo_debitore'], $value['cap_debitore'], $value['comune_provincia_debitore'], $value['descrizione_domiciliataria']);
                     $accumulatore .= $this->Record50($value['descrizione_debito']);
                     $accumulatore .= $this->Record51($value['numero_disposizione']);
                     $accumulatore .= $this->Record70($value['tipo_bollettino']);
             }
             $accumulatore .= $this->RecordEF(false);
             return $accumulatore;
             }
// fine funzione per GAzie

}

?>