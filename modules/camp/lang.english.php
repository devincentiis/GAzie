<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP
	  Copyright (C) 2018-2023 - Antonio Germani, Massignano (AP)
	  https://www.lacasettabio.it
	  https://www.programmisitiweb.lacasettabio.it
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
	  scriva   alla   Free  Software Foundation,  Inc.,   59
	  Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
	  --------------------------------------------------------------------------
	  # free to use, Author name and references must be left untouched  #
	  --------------------------------------------------------------------------
*/

$strScript = array("browse_document.php" =>
    array('title' => "List of Documents / Certificates",
        'errors' => array('The file format was not accepted!',
            'The file is too big!',
            'The file is empty!',
            'No file selected'),
        'ins_this' => "Insert a document and / or Certificate",
        'upd_this' => "Edit Document and / or Certificate",
        'item' => "Item reference",
        'table_name_ref' => "Reference Table",
        'note' => "Caption / Notes ",
        'ext' => "Extension",
        'select' => "Sel.",
        'code' => "Code"),
    "report_artico.php" =>
    array('title' => 'Goods and service report',
    ),
    "report_statis.php" =>
    array("statistic ",
        "sales",
        "purchases",
        "year",
        " order by ",
        " from: ",
        " to: ",
        "Last purchase ",
        "Last sales ",
        " of the year ",
        " Item ",
        " Quantity ",
        " Amount - ",
        "Out of warehouse"),
    "report_movmag.php" =>
    array("warehouse movements ",
        "code",
        "Insert ",
        "Report of ",
        "Reg. date",
        "Item",
        "Quantity",
        "Amount",
        "Document",
        " of ",
        "Create movements from documents"),
    "admin_movmag.php" =>
    array("warehouse movement ",
        "Entry date ",
        "Causal ",
        "customer",
        "Supplier",
        "C",
        "S",
        "Item",
        "Document date ",
        "Doc. description ",
        "Reduction in closing",
        "Measure unit ",
        "Quantity ",
        "Price ",
        "Reduction in line",
        "The document date is not corrected!",
        "The record date is not corrected!",
        "The date of the document is successive to that one of record date",
        "It has not been selected the item!",
        "The quantity cannot be equal to zero!",
        'operat' => 'Operator',
        'operat_value' => array(-1 => "Unloading", 0 => "Nop", 1 => "Loading"),
        'partner' => 'Partner',
        'del_this' => 'Delete stock movement',
        'amount' => " Amount - ",
    ),
    "report_caumag.php" =>
    array("warehouse causals ",
        "Report of "
    ),
    "admin_catmer.php" =>
    array("categoria merceologica ",
        "Numero ",
        "Descrizione ",
        "Image (jpg,png,gif) max 10kb: ",
        "% di ricarico ",
        "Annotazioni ",
        "codice gi&agrave; esistente!",
        "la descrizione &egrave; vuota!",
        "Il file immagine dev'essere nel formato PNG",
        "L'immagine non dev'essere pi&ugrave; grande di 10 kb",
        'web_url' => 'Web url<br />(ex: http://site.com/group.html)'
    ),
    "admin_ragstat.php" =>
    array("statistic groups ",
        "Numero ",
        "Descrizione ",
        "Immagine (jpg,png,gif) max 10kb: ",
        "% di ricarico ",
        "Annotazioni ",
        "codice gi&agrave; esistente!",
        "la descrizione &egrave; vuota!",
        "Il file immagine dev'essere nel formato PNG",
        "L'immagine non dev'essere pi&ugrave; grande di 10 kb",
        'web_url' => 'Web url<br />(es: http://site.com/group.html)'
    ),
    "admin_caumag.php" =>
    array("warehouse causal ",
        "Code ",
        "Description ",
        "Document data ",
        "Operation ",
        "Update consistency ",
        "No",
        "Yes",
        "Download",
        "Nop",
        "Upload",
        "customer/Supplier",
        "customer",
        "Both",
        "Supplier",
        "codice gi&agrave; esistente!",
        "la descrizione &egrave; vuota!",
        "il codice dev'essere un numero minore di 99"
    ),
    "genera_movmag.php" =>
    array("Genera movimenti di magazzino da documenti",
        "Data inizio ",
        "Data fine",
        "Azienda senza obbligo di magazzino fiscale!",
        " successiva alla ",
        "  righi sono da traferire in magazzino:",
        " Non ci sono righi da trasferire in magazzino!"),
    "select_giomag.php" =>
    array(0 => 'Stampa giornale di magazzino',
        'title' => 'Selezione per la visualizzazione e/o la stampa del giornale di magazzino',
        'errors' => array('La data  non &egrave; corretta!',
            'La data di inizio dei movimenti contabili da stampare non pu&ograve; essere successiva alla data dell\'ultimo !',
            'La data di stampa non pu&ograve; essere precedente a quella dell\'ultimo movimento!'
        ),
        'date' => 'Data di stampa ',
        'date_ini' => 'Data registrazione inizio  ',
        'date_fin' => 'Data registrazione fine ',
        'header' => array('Date' => '', 'Causal' => '', 'Document description' => '',
            'Price' => '', 'Amount' => '', 'MU' => '', 'Quantity' => ''
        )
    ),
    "recalc_exist_value.php" =>
    array("Rivalutazione esistenza articoli da movimenti di magazzino",
        "Anno di riferimento",
        "Metodo di rivalutazione, scelto in configurazione azienda",
        "Sono stati movimentati i seguenti",
        "articoli durante il ",
        "Movimenti",
        "Codice",
        "Descrizione",
        "Esistenza",
        "UM acq.",
        "Valore precedente",
        "Valore rivalutato",
        "NON RIVALUTATO vedi nota ",
        "(1) perch&egrave; ci sono degli acquisti negli anni successivi al ",
        "(2) perch&egrave; non ci sono movimenti di acquisto nel ",
        "Non ci sono articoli movimentati!"),
    "inventory_stock.php" =>
    array('title' => "Inventory exist stock",
        'del' => "of",
        'catmer' => "Item Group ",
        'select' => "Sel.",
        'code' => "Code",
        'descri' => "Item description",
        'mu' => "M.U.",
        'load' => "Load",
        'unload' => "Unload",
        'value' => "New stock value",
        'v_a' => "Current Value",
        'v_r' => "Real value",
        'g_a' => "Current stock",
        'g_r' => "Real stock",
        'g_v' => "Stock value",
        'noitem' => "No items found in this category",
        'errors' => array(" The real stock can not be negative",
            " The real value can not be negative or zero",
            " You are trying to make an inventory with zero real stock and zero current stock "),
        'preview_title' => 'Confirming the choices made will be recorded the following movements of stock:'
    ),
    "select_schart.php" =>
    array(0 => 'Stampa schedari di magazzino',
        'title' => 'Selection for view and/or print the warehouse item reports',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Cambia articolo'
        ),
        'errors' => array('La data  non &egrave; corretta!',
            'La data di inizio dei movimenti contabili da stampare non pu&ograve; essere successiva alla data dell\'ultimo !',
            'La data di stampa non pu&ograve; essere precedente a quella dell\'ultimo movimento!',
            'L\'articolo iniziale non pu&ograve; avere un codice successivo a quello finale!',
            'La categoria merceologica iniziale non pu&ograve; avere un codice successivo a quello finale!'
        ),
        'date' => 'Data di stampa ',
        'cm_ini' => 'Categoria merceologica inizio ',
        'art_ini' => 'Articolo inizio ',
        'date_ini' => 'Data registrazione inizio  ',
        'cm_fin' => 'Categoria merceologica fine ',
        'art_fin' => 'Articolo fine ',
        'date_fin' => 'Data registrazione fine ',
        'header' => array('Date' => '', 'Causal' => '', 'Document<br \>description' => '',
            'Price' => '', 'UM' => '', 'Quantity' => '',
            '<br \>loading' => '', '<br \>unloadig' => '',
            'Storage<br \>quantity' => '', 'Storage<br \>value' => ''
        ),
        'tot' => 'Consistenza'
    ),
    "stampa_schart.php" =>
    array(0 => 'VALUATION STOCK REPORT from ', 1 => ' to ',
        'bot' => 'to carry : ',
        'top' => 'from carry :  ',
        'item_head' => array('Code', 'Group', 'Description', 'MU', 'Min.Stock'),
        'header' => array('Date', 'Causal', 'Document description',
            'Price', 'MU', 'Quantity',
            ' load', ' unload',
            'Q.ty stock', 'Val. stock'
        ),
        'tot' => 'Consistency at '
    ),
    "select_deplia.php" =>
    array('title' => 'Selezione per la stampa del catalogo',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Change item'
        ),
        'errors' => array('La data  non &egrave; corretta!',
            'L\'articolo iniziale non pu&ograve; avere un codice successivo a quello finale!',
            'La categoria merceologica iniziale non pu&ograve; avere un codice successivo a quello finale!'
        ),
        'date' => 'Print date ',
        'cm_ini' => 'Categoria merceologica inizio ',
        'art_ini' => 'Articolo inizio ',
        'cm_fin' => 'Categoria merceologica fine ',
        'art_fin' => 'Articolo fine ',
        'barcode' => 'Stampa',
        'barcode_value' => array(0 => 'Immagini', 1 => 'Codici a Barre'),
        'listino' => 'Price list',
        'listino_value' => array(1 => ' of sales 1', 2 => ' of sales 2', 3 => ' of sales 3', 4 => ' of sales 4', 'web' => 'of web sales')
    ),
    "select_listin.php" =>
    array('title' => 'Selezione per la stampa dei listini',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Change item'
        ),
        'errors' => array('La data  non &egrave; corretta!',
            'L\'articolo iniziale non pu&ograve; avere un codice successivo a quello finale!',
            'La categoria merceologica iniziale non pu&ograve; avere un codice successivo a quello finale!'
        ),
        'date' => 'Data di stampa ',
        'cm_ini' => 'Categoria merceologica inizio ',
        'art_ini' => 'Articolo inizio ',
        'cm_fin' => 'Categoria merceologica fine ',
        'art_fin' => 'Articolo fine ',
        'listino' => 'Price list ',
        'listino_value' => array(0 => 'of purchase', 1 => ' of sales 1', 2 => ' of sales 2', 3 => ' of sales 3', 4 => ' of sales 4', 'web' => 'of web sales')
    ),
    "update_prezzi.php" =>
    array('title' => 'Update item prices',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Change item'
        ),
        'errors' => array('Valore "0" inaccettabile in questa modalit&agrave; di Modifica',
            'L\'articolo iniziale non pu&ograve; avere un codice successivo a quello finale!',
            'La categoria merceologica iniziale non pu&ograve; avere un codice successivo a quello finale!'
        ),
        'cm_ini' => 'Categoria merceologica inizio ',
        'art_ini' => 'Articolo inizio ',
        'cm_fin' => 'Categoria merceologica fine ',
        'art_fin' => 'Articolo fine ',
        'lis_obj' => 'Object change price list',
        'lis_bas' => 'Base price list',
        'listino_value' => array(0 => 'of purchase', 1 => ' of sales 1', 2 => ' of sales 2', 3 => ' of sales 3', 4 => ' of sales 4', 'web' => 'of web sales'),
        'mode' => 'Modalit&agrave; di modifica',
        'mode_value' => array('A' => 'Sostituzione', 'B' => 'Somma in percentuale', 'C' => 'Somma valore',
            'D' => 'Moltiplicazione per valore', 'E' => 'Divisione per valore', 'F' => 'Azzeramento e somma percentuale'),
        'valore' => 'Percentuale/valore',
        'round_mode' => 'Arrotondamento matematico a',
        'round_mode_value' => array('1 ', '10 cents', '1 cent', '1 mils', '0,1 mils', '0,01 mils'),
        'weight_valadd' => 'Delivery add ex. �/kg',
        'header' => array('Cat.Merceologica' => '', 'Codice' => '', 'Descrizione' => '', 'U.M.' => '',
            'Old price' => '', 'Weight add' => '', 'New price' => ''
        )
    ),
    "update_vatrate.php" =>
    array('title' => 'Changing the VAT rate of Items',
        'mesg' => array('The search gave no results!',
            'Enter at least 1 character!',
            'Change item'
        ),
        'errors' => array('Errore nullo',
            'The initial item may not have a code following the final code!',
            'The initial item group can not have a code following the final!'
        ),
        'cm_ini' => 'Initial item group ',
        'art_ini' => 'Initial item ',
        'cm_fin' => 'Final item group ',
        'art_fin' => 'Final item ',
        'rate_obj' => 'VAT rate to change',
        'rate_new' => 'New VAT rate',
        'header' => array('Item group' => '', 'Code' => '', 'Description' => '', 'M.U.' => '',
            'Old VAT rate' => '', 'New VAT rate' => ''
        )
    ),
    "admin_artico.php" =>
    array('title' => 'Management of products',
        'ins_this' => 'Add product',
        'upd_this' => 'Update product',
        'err' => array(
            'codice'=>'The product code already exists',
            'movmag'=>'You are trying to change the code to a product associated with the movement of stock',
            'filmim'=>'The file must be in PNG, JPG, GIF',
            'filsiz'=>'The image of the product should not be larger than 10 kb',
            'valcod'=>'Enter a valid code',
            'descri'=>'Enter a description',
            'unimis'=>'Insert the unit sales',
            'aliiva'=>'Enter the VAT rate',
            'lotmag'=>'Per avere la tracciabilit� per lotti � necessario attivare la contabilit� di magazzino in configurazione azienda'
        ),
        'codice' => "Code",
        'descri' => "Description",
        'good_or_service' => "Tipologia di articolo",
        'good_or_service_value' => array(0 => 'Good', 1 => 'Service'),
        'body_text' => "Descrizione amplia su rigo aggiuntivo",
        'lot_or_serial' => 'Lot or serial number',
        'lot_or_serial_value' => array(0 => 'No', 1 => 'Lot', 2 => 'Serial'),
        'barcode' => "Barcode EAN13",
        'image' => "Image (jpg,png,gif) max 10kb",
        'unimis' => "Measurement Unit for sales",
        'catmer' => "Product Category",
        'ragstat' => "Statistic group",
        'sconto' => 'Discount',
        'preacq' => 'Purchase price',
        'preve1' => 'Selling price of a list 1',
        'preve2' => 'Selling price of a list 2',
        'preve3' => 'Selling price of a list 3',
        'preve4' => 'Selling price of a list 4',
        'preve4_sc' => 'Prezzo scontato 4',
        'preve1_sc' => 'Prezzo scontato 1',
        'preve2_sc' => 'Prezzo scontato 2',
        'preve3_sc' => 'Prezzo scontato 3',
        'aliiva' => 'VAT rate',
        'retention_tax' => 'Apply retention tax',
        'retention_tax_value' => array(0 => 'No', 1 => 'Yes'),
        'payroll_tax' => 'Generate Payroll tax row',
        'payroll_tax_value' => array(0 => 'No', 1 => 'Yes'),
        'esiste' => 'Actual existence',
        'valore' => 'Value of the existing',
        'last_cost' => 'Cost of the last purchase',
        'scorta' => 'Minimum stock',
        'riordino' => 'Purchase lot',
        'uniacq' => 'Measurement Unit of purchases',
		'classif_amb' => 'Classificazione ambientale',
		'classif_amb_value' => array(0=>'non classificato',1=>'Irritante',2=>'nocivo',3=>'Tossico',4=>'Molto tossico',5=>'Pericoloso ambiente'),
        'peso_specifico' => 'Specific Gravity / Multiplier',
        'volume_specifico' => 'Specific volume',
        'pack_units' => 'Pieces in packaging',
        'codcon' => 'Account of income from sales',
        'id_cost' => 'Account of cost on purchases',
        'annota' => 'Note (also published on the website)',
        'document' => 'Documents and / or certification',
        'web_mu' => 'Measurement Units on the website',
        'web_price' => 'Selling price on the website',
        'web_multiplier' => 'Web price multiplier',
        'web_public' => 'Public website',
        'depli_public' => 'Public on depliant',
        'depli_public_value' => array(0 => 'No', 1 => 'Yes'),
        'web_public_value' => array(0 => 'No', 1 => 'Yes'),
        'web_url' => 'Web url<br />(ex: http://site.com/item.html)',
        'modal_ok_insert' => 'Product inserted successfully, click on the X on the upper right corner to exit or...',
        'iterate_invitation' => 'INSERT ANOTHER ITEM IN STOCK!',
        'browse_for_file' => 'Browse',
        'id_anagra' => 'Supplier',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Cambia fornitore'
        )
    )
);
?>
