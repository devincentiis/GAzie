# GAzie - Gestione Aziendale

[![Download GAzie - Gestione Aziendale](https://a.fsdn.com/con/app/sf-download-button)](https://sourceforge.net/projects/gazie/files/latest/download)
## Sommario
- [GAzie - Gestione Aziendale](#gazie---gestione-aziendale)
    * [Che cos'è GAzie](#che-cos-gazie)
    * [TL;DR](#tldr)
        - [Sei un webmaster?](#sei-un-webmaster)
        - [Sei uno sviluppatore?](#sei-uno-sviluppatore)
        + [Requisiti di Sistema](#requisiti-di-sistema)
        + [Installazione Veloce](#installazione-veloce)
    * [A chi è rivolto](#a-chi--rivolto)
    * [Che cos'è in grado di fare](#che-cos-in-grado-di-fare)
        + [Ciclo attivo](#ciclo-attivo)
        + [Ciclo Passivo](#ciclo-passivo)
        + [Magazzino e Produzione](#magazzino-e-produzione)
    * [Funzionalità ulteriori - Moduli Specifici](#funzionalit-ulteriori---moduli-specifici)
    * [Documentazione e Supporto](#documentazione-e-supporto)
        + [Utente Finale](#utente-finale--webmaster)
        + [Sviluppatori](#sviluppatori)
        + [Licenza](#licenza)
        + [To Do](#to-do)


## Che cos'è GAzie
GAzie è un software gestionale (ERP) multiazienda in grado di gestire tanti aspetti dell'azienda, dalla gestione ordini, alla produzione, al magazzino a lotti, la contabilità, il registratore di cassa, le fatture elettroniche attive e passive, il quaderno di campagna, il registro SIAN, la Sincronizzazione con e-commerce.
Un gestionale completo per le PMI, scritto in PHP e base di dati database MySQL/MariaDB.

## TL;DR
### Sei un webmaster?

Scarica l'ultima versione di GAzie [qui](https://sourceforge.net/projects/gazie/files/gazie/8.00/gazie8.00.zip/download)

### Sei uno sviluppatore?
Se vuoi contribuire allo sviluppo di GAzie, ti chiediamo lavorare con SVN (devi avere un account su SourceForge.net):

`svn checkout --username=[tuo username su SF] svn+ssh://[tuo username su SF]@svn.code.sf.net/p/gazie/code/ gazie-code`

A Digiuno di SVN? leggi questa guida: [https://www.html.it/guide/guida-subversion/](https://www.html.it/guide/guida-subversion/)

Se invece vuoi semplicemente fare un fork e apportare in privato le tue modifiche
puoi clonare il repository direttamente da SVN:

`svn checkout svn://svn.code.sf.net/p/gazie/code/ gazie-code`


### Requisiti di Sistema

* Webserver Apache 2.4 o superiore, IIS su windows
* Versione PHP >= 7.4 compilata DSO, non c'è ancora il supporto per PHP-FPM
* Estensioni PHP richieste: MySQLi, php-intl, php-xml, php-gd, php-xsl, php-calendar
* Database MariaDB o MySQL (consigliato MariaDB 10.x.x o sup.)



## A chi è rivolto
GAzie è la soluzione ideale per piccole aziende che operano nel commercio, nell'industria, nei servizi e nell'agricoltura

## Che cos'è in grado di fare
### Ciclo attivo
* Gestione clienti
* Preventivi
* Ordini
* Fatture immediate, differite, note di credito
* Generazione file xml per fattura elettronica

### Ciclo Passivo
* Gestione fornitori
* Preventivi, Ordini, DDT, Resi, Fatture fornitori
* Acquisizione Fatture elettroniche passive
* Contabilizzazione delle fatture in Prima Nota
* Gestione dei Cespiti
* Registro IVA e Libro Giornale
* Riconciliazione E/C in contabilità

### Magazzino e Produzione
* Gestione del Magazzino a lotti con Magazzini multipli
* Produzione di base

## Funzionalità ulteriori - Moduli Specifici
* Quaderno di campagna per le aziende agricole
* Gestione del SIAN per olivicoltori e confezionatori
* Registratore di cassa
* Sincronizzazione con i principali sistemi di E-commerce
* Gestione Prenotazioni case vacanze, B&B, agriturismi, hotel

## Documentazione e Supporto
### Utente Finale / Webmaster

Per l'installazione seguire [la guida sopra](#tldr) oppure dare una letta al file [INSTALL.html](doc/INSTALL.html)
È disponibile un Help in linea all'interno del programma e il supporto della community a questo [link](https://sourceforge.net/projects/gazie/support)

### Sviluppatori
Maggiori dettagli nella cartella `dev` e nel [wiki del progetto su SF](https://sourceforge.net/p/gazie/wiki) (di prossimo aggiornamento, vedi [to do](#to-do))

### Licenze
Trovi una copia della licenza dentro la cartella `doc`, oppure nel file [LICENSE](./LICENSE.md)
GAzie fa uso di librerie gestite tramite [Composer](https://getcomposer.org/) per ognuna di esse c'è una licenza specifica che troverai nelle rispettive sottocartelle di `vendor`.
Altre librerie sono contenute in `library`.

### To Do

- [x] Riscrivere il README più leggibile
- [ ] migliorare e aggiornare manuale installazione
- [ ] Creare guida sviluppatori più dettagliata su wiki di SF


