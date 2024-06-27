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
 

  Patch alla classe olivetti_ela, inviata da Francesco Pelassa e recepita sul CVS in data 18.03.2010

  Questa classe serve per la generazione delle stringhe da inviare attraverso la seriale RS232 ad un
  misuratore fiscale che usa il protocollo  Xon-Xoff, per la stampa dello scontrino fiscale attraverso
  il gestionale GAzie

  I ambiente Linux è indispensabile che il server web abbia i permessi per poter accedere alla porta seriale RS232 che
  normalmente è il file "/dev/ttyS0" equivalente alla "COM1" degli ambienti Windows; per fare questo si devono dare i seguenti comandi:

  sudo addgroup www-data dialout

  creare un file /etc/udev/rules.d/40-permissions_rs232.rules
  in ubuntu si fa così:

  sudo gedit /etc/udev/rules.d/40-permissions_rs232.rules

  mettendoci dentro la seguente riga:

  KERNEL=="ttyS[0-9]", GROUP="dialout", MODE="0777"

  poi si fa il restart di udev:

  sudo /etc/init.d/udev restart

  per maggiori info:
  https://ubuntuforums.org/showthread.php?t=782115
  e su:
  https://guide.debianizzati.org/index.php/Udev_e_Debian

 */

class xonxoff {

    function __construct() {
        // di default la seriale usata � la "/dev/ttyS0" equivalente a "COM1" su Windows
        $this->serial = '0';
        $this->_open = false;
    }

    public function set_serial($dev) {
        // cambio della seriale di default (ttyS0 o COM1)
        /*  il numero intero di seriale da passare è comunque quello dei sistemi Linux,
          su Windows automaticamente esso viene aumentato di 1; quindi
          per usare COM1 su Windows si deve comunque passare "0", in ogni caso
          su $dev si pu� passare al posto del numero anche una stringa corrispondente
          alla periferica realmente interessata es. "/dev/ttyS0" su Linux o "COM1" su
          Windows.
         */

        $this->serial = $dev;
    }

    public function open_ticket() {
        $this->_send('K');
        // nulla
    }

    public function set_cashier($user = '') {
        // imposto il nome del casiere
        //$this->_send('O1');
    }

    public function descri_ticket($descr = '') {
        // stampa rigo descrittivo
        $this->_send('"' . $descr . '"@');
    }

    public function lotteria_scontrini($codicelotteria,$cmdlotteria='L') {
        // stampa codice lotteria
        $this->_send('"' . $codicelotteria . '"'.$cmdlotteria);
    }

    public function close_ticket($d = '1') {
        $this->_close_port();
    }

    public function open_drawer($d = '1') {
        // apertura cassetto
        $this->_send('a');
        $this->_close_port();
    }

    public function row_ticket($amount, $descr = '', $vat = '', $row = '',$reparto='1R', $descriart=false) {
        // vendita articoli
        // il formato dell'importo deve essere senza punti e virgole e con 2 decimali
        $formato_ammount = number_format($amount, 2, '', '');
        $this->_send('"' . $descr . '"' . $formato_ammount . 'H'.$reparto);
    }

    public function pay_ticket($cash = '', $descr = '', $tender = '1T') {
        // pagamento
        $this->_send($tender);
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
        $this->_send('z1Fc');
        // Chiusura rapporto fiscale Z10
        $this->_send('');
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
                $_serial = '/dev/ttyS' . intval(substr($this->serial, 0, 1));
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
            exec('MODE ' . $_serial . ' BAUD=9600 DATA=8 PARITY=N STOP=1 XON=OFF');
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

        fwrite($this->_handle, $data);

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


