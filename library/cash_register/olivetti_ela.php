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
 

  Questa classe serve per la generazione delle stringhe da inviare attraverso la seriale RS232 ad un
  misuratore fiscale Olivetti, Underwood., ecc per la stampa dello scontrino fiscale attraverso il gestionale GAzie
  i test sono stati eseguiti solo su NETTUNA 500 che di default ha la porta n.2 già pronta per essere
  collegata al PC configurata a 9600 baud, 8 bit dati, No parità, 2 bit stop, No controllo flusso, ovvero (9600-8-N-2-N)

  I ambiente Linux è indispensabile che il server web abbia i permessi per poter accedere alla porta seriale RS232 che
  normalmente è il file "/dev/ttyUSB0" equivalente alla "COM1" degli ambienti Windows; per fare questo si devono dare i seguenti comandi:

  sudo addgroup www-data dialout

  creare un file /etc/udev/rules.d/40-permissions_rs232.rules
  in ubuntu si fa così:

  sudo gedit /etc/udev/rules.d/40-permissions_rs232.rules

  mettendoci dentro la seguente riga:

  KERNEL=="ttyUSB[0-9]", GROUP="dialout", MODE="0777"

  poi si fa il restart di udev:

  sudo /etc/init.d/udev restart

  per maggiori info:
  https://ubuntuforums.org/showthread.php?t=782115
  e su:
  https://guide.debianizzati.org/index.php/Udev_e_Debian

 */

class olivetti_ela {

    function __construct() {
        // di default la seriale usata è la "/dev/ttyUSB0" equivalente a "COM1" su Windows
        $this->serial = '0';
        $this->_open = false;
    }

    public function set_serial($dev) {
        // cambio della seriale di default (ttyUSB0 o COM1)

        /*  il numero intero di seriale da passare è comunque quello dei sistemi Linux,
          su Windows automaticamente esso viene aumentato di 1; quindi
          per usare COM1 su Windows si deve comunque passare "0", in ogni caso
          su $dev si pu� passare al posto del numero anche una stringa corrispondente
          alla periferica realmente interessata es. "/dev/ttyUSB0" su Linux o "COM1" su
          Windows.
         */

        $this->serial = $dev;
    }

    public function open_ticket() {
        // apertura scontrino fiscale
        $this->_send('$1322');
    }

    public function set_cashier($user = '') {
        // imposto il nome del casiere
        $this->_send('$1304' . $this->_tag_data($user) . '!2');
    }

    public function descri_ticket($descr = '') {
        // stampa rigo descrittivo
        $this->_send('#112' . $this->_tag_data($descr));
    }

    public function lotteria_scontrini($codicelotteria,$cmdlotteria='') {
        // stampa codice lotteria
        $this->_send('$'.$cmdlotteria. $this->_tag_data($codicelotteria) );
    }

    public function close_ticket($d = '1') {
        // chiusura scontrino fiscale, da chiamare sempre!
        $this->_send('$1323');
        $this->_send('#912!' . $d);
        $this->_close_port();
    }

    public function open_drawer($d = '1') {
        // apertura cassetto
        $this->_send('#912!' . $d);
        $this->_close_port();
    }

    public function row_ticket($amount, $descr = '', $vat = '', $row = '',$reparto='1R', $descriart=false) {
        // vendita articoli
        $this->_send('$1325' . $this->_tag_data($amount) . $this->_tag_data($descr) . $this->_tag_data($vat) . $this->_tag_data($row));
    }

    public function pay_ticket($cash = '', $descr = '', $tender = '') {
        // pagamento
        $this->_send('$1329' . $this->_tag_data($cash) . $this->_tag_data($descr));
    }

    public function simple_ticket($amount) {
        // Esempio di scontrino completo con pagamento contanti
        // senza descrizioni, una cosa veramente minimale
        $this->open_ticket();
        $this->row_ticket($amount);
        $this->pay_ticket();
        $this->close_ticket();
    }

    public function fiscal_report() {
        // Stampa rapporto fiscale Z10
        $this->_send('$1333');
        // Chiusura rapporto fiscale Z10
        $this->_send('$1334');
        $this->_close_port();
    }

    protected function _tag_data($data) {
        $x = 32 + strlen($data);
        return chr($x) . $data;
    }

    protected function _crc($data) {
        $x = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            $x+=ord($data[$i]);
        }
        return str_pad(strtoupper(DecHex($x)), 4, '0', STR_PAD_LEFT);
    }

    protected function _open_port() {
        // setting serial port rs232
        $sysname = substr(php_uname(), 0, 3);
        if ($sysname == "Lin") {
            if (is_numeric(substr($this->serial, 0, 1))) {
                $_serial = '/dev/ttyUSB' . intval(substr($this->serial, 0, 1));
            } else {
                $_serial = $this->serial;
            }
            exec('stty -F ' . $_serial . ' baud=9600 +cs8 -parenb +cstopb clocal -crtscts -ixon -ixoff');
        } elseif ($sysname == "Win") {
            if (is_numeric(substr($this->serial, 0, 1))) {
                $_serial = 'COM' . intval(substr($this->serial, 0, 1) + 1);
            } else {
                $_serial = $this->serial;
            }
            exec('MODE ' . $_serial . ' BAUD=9600 DATA=8 PARITY=N STOP=2 XON=OFF');
        } else {
            trigger_error("Il Sistema operativo non risulta essere windows o linux
                           ci sono problemi per settare la porta RS232", E_USER_ERROR);
            exit();
        }
        // end setting serial

        $this->_handle = fopen($_serial, "r+");
        $this->_open = true;
    }

    protected function _send($data) {
        if (!$this->_open) {
            $this->_open_port();
        }
        $formatted_data = chr(17) . chr(02) . $data . $this->_crc($data) . chr(03);
        fwrite($this->_handle, $formatted_data);
        /*
          Quello che faccio sotto � per aspettare che l'ECR "digerisca" la stringa inviata
          soprattutto in considerazione che non effettuo il controllo della risposta che
          invia l'ECR stesso alla rs232 del server.
          Purtroppo sui sistemi windows non funziona usleep() per cui devo usare sleep() che
          accetta solo valori interi, quindi minimo 1 secondo...
          penso che basterebbe anche solo 0.2 sec ovvero usleep(200000) ma funziona solo su linux
         */
        sleep(1);
    }

    protected function _close_port() {
        if ($this->_open) {
            fclose($this->_handle);
            $this->_open = false;
        }
    }

}

?>