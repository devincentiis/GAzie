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
 */
/*
 --------------------------------------------------------------------------
    Traduzione Tedesca, Da Sangregorio Antonino.
 --------------------------------------------------------------------------
*/

$strScript = array ("select_comiva.php" =>
                   array(  "J�hrlichen Mitteilung der MwSt.-Daten (Datei IVC)",
                           "ALLGEMEINE DATEN",
                           "Abgabenordnung",
                           "Firmenname",
                           "Familienname",
                           "Name",
                           "Fiscal Year",
                           "Steuernummer",
                           "USt-Id-Nr.",
                           "Getrennte Rechnungslegung",
                           "Mitteilung von einer Firma die Zugeh�rigkeit zu einer Gruppe MwSt.",
                           "Besondere Vorkommnisse",
                           "ANMELDER [ausf�llen, wenn abweichend von den Steuerpflichtigen]",
                           "Tax-Code des Unternehmens declanrant",
                           "Tax code",
                           "Termin-Code",
                           "Gesetzlichen, vertraglichen, de facto Agenten oder gesch�ftsf�hrendes Mitglied",
                           "Agent of a minor",
                           "Receiver (receivership)",
                           "Manager (judicial custody)",
                           "Fiscal agent of a non-resident person",
                           "The heir",
                           "Liquidator (voluntary winding-up)",
                           "A person assigned to extraordinary operations",
                           "INFORMATION RELETING TO TRANSACTIONS CARRIED OUT",
                           "Activity code",
                           "ASSET TRANCACTION",
                           "Total of the asset transaction [net of VAT]",
                           "of which: non-taxable transactions",
                           "exempt transactions",
                           "intra-community sale of goods",
                           'attben'=>"sales of equipment",
                           "LIABILITY TRANCACTION",
                           "Total liability transaction [net of VAT]",
                           "of which: non-taxable purchases",
                           "exempt purchases",
                           "intra-community purchases of goods",
                           'pasben'=>"purchases of equipment",
                           "IMPORTATION WITHOUT PAYING VAT ON ENTRY INTO CUSTOMS - INDUSTRIAL GOLD AND PURE SILVER",
                           "IMPORTATION WITHOUT PAYING VAT ON ENTRY INTO CUSTOMS - SCRAP AND OTHER RECYCLED MATERIAL",
                           "Taxable",
                           "Tax",
                           "CALCULATION OF OUTPUT OR INMPUT TAX",
                           "VAT ",
                           "Input tax",
                           "deducted",
                           "Output tax",
                           "or input tax"),
                   "select_chiape.php" =>
                   array(  'title'=>'Closing and Opening Accounts',
                           'errors'=>array('La data  non &egrave; corretta!',
                                           'La data di chisura non pu&ograve; essere successiva alla data di apertura!',
                                           "It has been already made one closing during the selected period!",
                                           "Lack of balance in debit/credit of general ledger check"
                                          ),
                           'date_closing'=>'Closing entry date',
                           'date_opening'=>'Opening entry date',
                           'closing_balance'=>"Balance of Closing",
                           'economic_result'=>"Income",
                           'operating_profit'=>"Periodic Profit",
                           'operating_losses'=>"Periodic Loss",
                           'opening_balance'=>"Balance of Opening",
                           'closing'=>" CLOSING ",
                           'opening'=>" OPENING ",
                           'economic'=>"ECONOMIC",
                           'code'=>"CODE",
                           'descr'=>"DESCRIPTION",
                           'exit'=>"DEBT",
                           'entry'=>"CREDIT",
                           'of'=>" OF ",
                           'sheet'=>"SHEET",
                           'assets'=>"ASSETS",
                           'liabilities'=>"LIABILITIES",
                           'costs'=>"COSTS",
                           'revenues'=>"REVENUES",
                           'acc_o'=>'ACCONTS OPENING',
                           'acc_c'=>'ACCONTS CLOSING'
                           ),
                   "select_bilanc.php" =>
                   array(  "Balance - Inventory book",
                           "Date Beginning Period",
                           "Date Fine Period",
                           "Definitive print",
                           "Number of first page",
                           "Description",
                           " BALANCE ",
                           " FROM THE ",
                           " TO THE ",
                           " PATRIMONIAL SITUATION ",
                           " Profit ",
                           " Loss ",
                           "ASSET",
                           "LIABILITY",
                           "COSTS",
                           "REVENUES",
                           "TOTAL ",
                           " PROFIT & LOSS ",
                           " successive to ",
                           "The present balance is in compliance with the journal entry writings.",
                           "The choice of the press after all modernizes to the field �last page of the Book Inventories� in the archives company",
                           "Number of first page to print (default: that one write on the archives company + 1)",
                           "Account",
                           "Page ",
                           "Balance",
                           "Signature",
                           " to carry forward : ",
                           " amount brought forward :",
                           "Clienti/Fornitori",
                           "cf_value"=>array(1=>"Completi",2=>"Solo totali",3=>"Dettaglio in calce")
                           ),
                    "select_elencf.php" =>
                   array(  "Elenco clienti e fornitori",
                           "Soggetti in elenco",
                           "Clienti",
                           "Fornitori",
                           "Elenco",
                           "Anno",
                           "Non Impon. art.8 c.2",
                           "Codice fiscale uguale a 0",
                           "Codice fiscale sbagliato per una persona fisica",
                           "Non ha il Codice Fiscale",
                           "Codice Fiscale o indicazione persona giuridica (G) errati",
                           "Codice Fiscale o sesso (M) errati",
                           "Codice Fiscale o sesso (F) errati",
                           "Il Codice Fiscale &egrave; formalmente errato",
                           "La Partita IVA &egrave; formalmente errata",
                           "Non ha la Partita IVA",
                           "Sede legale non corretta, il formato giusto dev'essere come questo esempio: Piazza del Quirinale,41 00187 ROMA (RM)",
                           "Aliquota IVA imponibile con imposta uguale a 0",
                           "Aliquota IVA che non prevede una imposta e che invece &egrave; diversa da 0",
                           "Non si pu&ograve; generare il File Internet perch&egrave; sono stati rilevati errori da correggere (vedi in seguito)",
                           "CORREGGI !",
                           "Non sono stati trovati movimenti IVA da riportare in elenco!",
                           "Totali",
                           "Ci sono degli errori nei dati di configurazione dell'azienda!",
                           "Codice Fiscale",
                           "Partita IVA",
                           "Cognome",
                           "Nome",
                           "Sesso",
                           "Data di Nascita",
                           "Comune di Nascita",
                           "Provincia di Nascita",
                           "Denominazione",
                           "Comune",
                           "Provincia"
                           ),
        "select_comopril.php" =>
                   array( 'title'=>"Report of significant VAT transactions (ART.21)",
                          'limit'=>"Minimum limit",
                          'year'=>"Reference year",
                          'op_date'=>"Operation date",
                          'ragso1'=>"Surname / Company Name 1",
                          'ragso2'=>"Name / Company Name 2",
                          'soggetto'=>"Surname Name / Company Name ",
                          'sourcedoc'=>"Source document",
                          'sex'=>"Sex / Legal Person",
                          'sedleg'=>"Registered Office",
                          'proleg'=>"Province",
                          'datnas'=>'Date of birth',
                          'luonas'=>'Birthplace',
                          'pronas'=>'Province of birth',
                          'soggetto_type'=>"Subject Type",
                          'soggetto_type_value'=>array(1=>'Person without VAT number',2=>'Company with VAT number',3=>'Non-resident',4=>'VAT Update-Resident',5=>'VAT-Update-Non-resident'),
                          'imptype'=>"Tipologia imponibile",
                          'imptype_value'=>array(1=>'Taxable',2=>'NO Taxable',3=>'Free',4=>'Taxable with VAT unexposed'),
                          'amount'=>"Amount of consideration",
                          'tax'=>"Tax",
                          'errors'=>array( "CORREGGI !",
                                           "Codice fiscale uguale a 0",
                                           "Codice fiscale sbagliato per una persona fisica",
                                           "Non ha il Codice Fiscale",
                                           "Codice Fiscale o indicazione persona giuridica (G) errati",
                                           "Codice Fiscale o sesso (M) errati",
                                           "Codice Fiscale o sesso (F) errati",
                                           "Il Codice Fiscale &egrave; formalmente errato",
                                           "Non ha la Partita IVA o essa &egrave; formalmente errata",
                                           "Persona Fisica straniera senza dati di nascita ",
                                           "Sede legale non corretta, il formato giusto dev'essere come questo esempio: Piazza del Quirinale,41 00187 ROMA (RM)",
                                           "Aliquota IVA imponibile con imposta uguale a 0",
                                           "Aliquota IVA che non prevede una imposta e che invece &egrave; diversa da 0",
                                           "Non si pu&ograve; generare il File Internet perch&egrave; sono stati rilevati errori da correggere (vedi in seguito)",
                                           "Non sono stati trovati movimenti IVA da riportare in elenco!",
                                           "Ci sono degli errori nei dati di configurazione dell'azienda!"),
                           'total'=>"Total",
                           'codfis'=>"Tax-Code",
                           'pariva'=>"VAT number",
                        ),
                  "error_protoc.php" =>
                   array(  'title'=>'Numberig check of VAT register',
                           'year'=>' of the year ',
                           'header'=>array('ID'=>'','Date '=>'','Section'=>'','Register'=>'','Protocol'=>'','Causal'=>'','Description'=>''
                                          ),
                           'pre_dd'=>' of ',
                           'expect'=>' expected '
                           )
                          );
?>