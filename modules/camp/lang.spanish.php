<?php
/*
	  --------------------------------------------------------------------------
	  GAzie - Gestione Azienda
	  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
	  (http://www.devincentiis.it)
	  <http://gazie.sourceforge.net>
	  --------------------------------------------------------------------------
	  REGISTRO DI CAMPAGNA è un modulo creato per GAzie da Antonio Germani, Massignano AP 
	  Copyright (C) 2018-2021 - Antonio Germani, Massignano (AP)
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
/*
  -- TRANSLATED BY : Dante Becerra Lagos (softenglish@gmail.com)
 */

$strScript = array("browse_document.php" =>
    array('title' => "Lista de documentos / certificados",
        'errors' => array('El formato de archivo no fue aceptada!',
            'El archivo es muy grande!',
            'El archivo está vacío!',
            'No existe el fichero se ha seleccionado'),
        'ins_this' => "Insertar un documento y / o Certificado",
        'upd_this' => "Editar el documento y / o Certificado",
        'item' => "El producto de referencia",
        'table_name_ref' => "Tabla de referencia",
        'note' => "Leyenda / Notas",
        'ext' => "Extensión",
        'select' => "Sel.",
        'code' => "Codigo"),
    "report_artico.php" =>
    array('title' => 'Goods and service report',
    ),
    "report_statis.php" =>
    array("estatistico ",
        "ventas",
        "compras",
        "a&ntilde;o",
        " ordenar por ",
        " desde: ",
        " hasta: ",
        "Ultima compra ",
        "Ultimas ventas ",
        " del a&ntilde;o ",
        " Item ",
        " Cantidad ",
        " Cantidad - ",
        "Fuera de bodega"),
    "report_movmag.php" =>
    array("movimientos de bodega",
        "codigo",
        "Insertar ",
        "Reporte de ",
        "Fecha Reg. ",
        "Item",
        "Cantidad ",
        "Cantidad",
        "Documento",
        " de ",
        "Crear movimientos desde documentos"),
    "admin_movmag.php" =>
    array("movimientos de bodega ",
        "Fecha de entrada ",
        "Causal ",
        "Cliente",
        "Proveedor",
        "C",
        "P",
        "Item",
        "Fecha de Documento",
        "Descripcion de Documento",
        "Reduccion al cerrar",
        "Unidad de medida ",
        "Cantidad ",
        "Precio ",
        "Reduccion en linea",
        "La fecha del documento no es correcta",
        "La fecha de registro no es correcta",
        "La fecha del documento es sucesiva a la fecha del registro",
        "No se ha seleccionado item!",
        "La cantidad no puede ser igual a cero!",
        'operat' => 'Operacion',
        'operat_value' => array(-1 => "Descarga", 0 => "No", 1 => "Subida"),
        'partner' => 'Cliente/Proveedor',
        'del_this' => 'Borrar el movimientos de bodega',
        'amount' => " Cantidad -  ",
    ),
    "report_caumag.php" =>
    array("Causales de bodega",
        "Reporte de "
    ),
    "admin_catmer.php" =>
    array("categoria mercancia ",
        "Numero ",
        "Descripcion ",
        "Image (jpg,png,gif) max 10kb: ",
        "% de recarga ",
        "Anotaciones ",
        "codigo ya existe!",
        "la descripcion esta vacia!",
        "El archivo de imagen debe estar en PNG",
        "La imagen no debe ser mayor de 10 KB",
        'web_url' => 'Web url<br />(es: http://site.com/group.html)'
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
    array("Causal Bodega ",
        "Codigo ",
        "Descripcion ",
        "Datos del Documento",
        "Operacion ",
        "Consistencia de Actualizacion ",
        "No",
        "Si",
        "Descarga",
        "No",
        "Subida",
        "Cliente/Proveedor",
        "Cliente",
        "Ambos",
        "Proveedor",
        "Codigo ya existe!",
        "la descripcion esta vacia!",
        "el codigo debe ser menor a 99"
    ),
    "genera_movmag.php" =>
    array("Generar los movimientos de existencias de los documentos",
        "Fecha de Inicio ",
        "Fecha de Termino",
        "Empresa sin obligaci&oacute;n de almac&eacute;n fiscal!",
        " sucesivo a el ",
        "  las l&iacute;neas se transfieren a trav&eacute;s del almacen:",
        " No hay renglones de transferir en almac&eacute;n!"),
    "select_giomag.php" =>
    array(0 => 'Impresion de diario de almac&eacute;n',
        'title' => 'Selecci&oacute;n para el visualizar y/o imprimir del diario de almac&eacute;n',
        'errors' => array('La fecha no es correcta!',
            'La fecha de comienzo de los movimientos contables a imprimir no puede ser sucesiva a la fecha del ultimo!',
            'La fecha de impresion non puede ser anterior a quella del ultimo movimiento!'
        ),
        'date' => 'Fecha de impresion ',
        'date_ini' => 'Fecha de inicio registro  ',
        'date_fin' => 'Fecha de termino registro ',
        'header' => array('Fecha' => '', 'Causal' => '', 'Documento Descripcion ' => '',
            'Precio' => '', 'Valor' => '', 'UM' => '', 'Cantidad' => ''
        )
    ),
    "recalc_exist_value.php" =>
    array("Revalorizacion  articulos existentes de movimientos de almacen",
        "A&ntilde;o de referencia",
        "Metodo de revalorizacion, seleccionado en configuraci&oacute;n empresa",
        "Han sido movidos al siguiente",
        "articulos durante el ",
        "Movimientos",
        "Codigo",
        "Descripcion",
        "Existencia",
        "UM acq.",
        "Valor precedente",
        "Valor revalorizado",
        "NO REVALORIZADO ver nota ",
        "(1)porque hay algunas compras en los a&ntilde;os posteriores al",
        "(2) porque no hay movimientos de adquisici&oacute;n en el ",
        "No hay ning&uacute;n art&iacute;culo movido!"),
    "inventory_stock.php" =>
    array('title' => "Inventario de Existencias",
        'del' => "de",
        'catmer' => "Categoria mercaderia ",
        'select' => "Sel.",
        'code' => "Codigo",
        'descri' => "Descripcion de Item",
        'mu' => "Unidad",
        'load' => "Carga",
        'unload' => "Descarga",
        'value' => "Valor existencia",
        'v_a' => "Current Valor",
        'v_r' => "Real valor",
        'g_a' => "Current stock",
        'g_r' => "Real stock",
        'g_v' => "Stock value",
        'noitem' => "No articulos seleccionados en el intervalo",
        'errors' => array(" Real stock no puede ser negativo",
            " Real valor no puede ser negativo o cero",
            " Usted est&aacute; tratando de hacer un inventario con current stock cero y cero real stock "),
        'preview_title' => 'Confirmaci&oacute;n de las decisiones tomadas se registrar&aacute;n los siguientes movimientos:'
    ),
    "select_schart.php" =>
    array(0 => 'Impresion ficheros de almac&eacute;n',
        'title' => 'Seleccion para ver y/o imprimir  las tarjetas de almac&eacute;n',
        'mesg' => array('La busqueda no dio resultados!',
            'Inserte al menos 1 caracter!',
            'Cambiando articulo'
        ),
        'errors' => array('La fecha  non es correcta!',
            'La fecha de comienzo de los movimientos contables de imprimir no puede ser sucesivo a la fecha del &uacute;ltimo!',
            'La fecha de impresion no puede ser anterior a la del ultimo movimiento!',
            'El art&iacute;culo inicial no puede tener un c&oacute;digo posterior al del final!',
            'La categor&iacute;a de mercaderia inicial no puede tener un c&oacute;digo sucesivo al del final!'
        ),
        'date' => 'Fecha de impresion ',
        'cm_ini' => 'Categor&iacute;a de Mercancia Inicio ',
        'art_ini' => 'Articulo de inicio ',
        'date_ini' => 'Fecha registro inicio  ',
        'cm_fin' => 'Categoria de Mercancia Termino ',
        'art_fin' => 'Articulo Final ',
        'date_fin' => 'Fecha registro termino ',
        'header' => array('Fecha' => '', 'Causal' => '', 'Documento<br \>descripcion' => '',
            'Precio' => '', 'UM' => '', 'Cantidad' => '',
            '<br \>carga' => '', '<br \>descarga' => '',
            'Cantidad<br \>existencias' => '', 'Existencias<br />valor' => ''
        ),
        'tot' => 'Consistencia'
    ),
    "stampa_schart.php" =>
    array(0 => 'TARJETA DE EXISTENCIAS dal ', 1 => ' al ',
        'bot' => 'a riportare : ',
        'top' => 'da riporto :  ',
        'item_head' => array('Codigo', 'Cat.Merc', 'Descripcion', 'UM', 'Min.Exist.'),
        'header' => array('Fecha', 'Causal', 'Documento descripcion',
            'Precio', 'UM', 'Cantidad',
            ' carga', ' descarga',
            'Cantid. exist.', 'Val. exist.'
        ),
        'tot' => 'Consistencia de '
    ),
    "select_deplia.php" =>
    array('title' => 'Seleccione para impresion del catalogo',
        'mesg' => array('La busqueda no dio resultados!',
            'Inserte al menos 1 caracter!',
            'Cambiando item'
        ),
        'errors' => array('La fecha  no es correcta!',
            'El primer art&iacute;culo no puede tener un c&oacute;digo siguiente al del final!',
            'La categoria de mercaderia inicial no puede tener un codigo posterior al del final!'
        ),
        'date' => 'Fecha de Impresion',
        'cm_ini' => 'Categoria de mercaderia inicio ',
        'art_ini' => 'Articulo inicio ',
        'cm_fin' => 'Categoria de mercaderia final ',
        'art_fin' => 'Articulo final ',
        'barcode' => 'Imprimir',
        'barcode_value' => array(0 => 'Imagenes', 1 => 'Codigos de barras'),
        'listino' => 'Lista',
        'listino_value' => array(0 => 'de compra', 1 => ' de venta 1', 2 => ' de venta 2', 3 => ' de venta 3', 4 => ' de venta 4', 'web' => 'de venta en l&iacute;nea')
    ),
    "select_listin.php" =>
    array('title' => 'seleccion para la impresion de las listas',
        'mesg' => array('La busqueda no dio resultados!',
            'Inserte al menos 1 caracter!',
            'Cambiando item'
        ),
        'errors' => array('La fecha  no es correcta!',
            'El primer art&iacute;culo no puede tener un c&oacute;digo siguiente al del final!',
            'La categoria de mercaderia inicial no puede tener un codigo posterior al del final!'
        ),
        'date' => 'Fecha de Impresion ',
        'cm_ini' => 'Categoria de mercaderia inicio',
        'art_ini' => 'Articulo inicio ',
        'cm_fin' => 'Categoria de mercaderia final ',
        'art_fin' => 'Articulo final ',
        'listino' => 'Lista',
        'listino_value' => array(0 => 'de compra', 1 => ' de venta 1', 2 => ' de venta 2', 3 => ' de venta 3', 4 => ' de venta 4', 'web' => 'de venta en l&iacute;nea')
    ),
    "update_prezzi.php" =>
    array('title' => 'Actualizacion de precios de itemes',
        'mesg' => array('La busqueda no dio resultados!',
            'Inserte al menos 1 caracter!',
            'Cambiando item'
        ),
        'errors' => array('Valor "0" inaceptable en este modo de edici&oacute;n!',
            'El primer art&iacute;culo no puede tener un c&oacute;digo siguiente al del final!',
            'La categoria de mercaderia inicial no puede tener un codigo posterior al del final!'
        ),
        'cm_ini' => 'Categoria de mercaderia inicio ',
        'art_ini' => 'Articulo inicio ',
        'cm_fin' => 'Categoria de mercaderia final ',
        'art_fin' => 'Articulo final ',
        'lis_obj' => 'Lista objeto de la modificacion',
        'lis_bas' => 'Lista base de calculo',
        'listino_value' => array(0 => 'de compra', 1 => ' de venta 1', 2 => ' de venta  2', 3 => ' de venta  3', 4 => ' de venta 4', 'web' => 'de venta en l&iacute;nea'),
        'mode' => 'El modo de modificacion',
        'mode_value' => array('A' => 'Sustitucion', 'B' => 'Suma en porcentaje', 'C' => 'Suma valor',
            'D' => 'Multiplicacion para valor', 'E' => 'Division por valor', 'F' => 'Ajuste a cero y suma percentual'),
        'valore' => 'Porcentaje / Valor',
        'round_mode' => 'Redondeo matem&aacute;tico a',
        'round_mode_value' => array('1 ', '10 centesimo', '1 centesimo', '1 milesima', '0,1 milesima', '0,01 milesima'),
        'weight_valadd' => 'Delivery add ex. €/kg',
        'header' => array('Cat. Mercaderia' => '', 'Codigo' => '', 'Descripcion' => '', 'U.M.' => '',
            'Precio anterior' => '', 'Weight add' => '', 'Precio nuevo' => ''
        )
    ),
    "update_vatrate.php" =>
    array('title' => 'Cambio de la tasa del IVA de los Articulos',
        'mesg' => array('La búsqueda no dio resultados!',
            'Indicar al menos 1 carácter!',
            'Actualizar artículo'
        ),
        'errors' => array('Errore nullo',
            'El punto inicial puede no tener un código que sigue al final del código!',
            'El grupo de artículos iniciales no puede tener un código después de la última!'
        ),
        'cm_ini' => 'Categoria de mercaderia inicio ',
        'art_ini' => 'Articulo inicio ',
        'cm_fin' => 'Categoria de mercaderia final ',
        'art_fin' => 'Articulo final ',
        'rate_obj' => 'Tipo de IVA a cambio',
        'rate_new' => 'Nueva tasa del IVA',
        'header' => array('Categoria de mercaderia' => '', 'Codigo' => '', 'Descripción' => '', 'U.M.' => '',
            'Tasa del IVA de edad' => '', 'Nueva tasa del IVA' => ''
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
            'lotmag'=>'Per avere la tracciabilità per lotti è necessario attivare la contabilità di magazzino in configurazione azienda'
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
        'preacq' => 'Purchase price',
        'preve1' => 'Selling price of a list 1',
        'preve2' => 'Selling price of a list 2',
        'preve3' => 'Selling price of a list 3',
        'preve4' => 'Selling price of a list 4',
        'preve1_sc' => 'Prezzo scontato 1',
        'preve2_sc' => 'Prezzo scontato 2',
        'preve3_sc' => 'Prezzo scontato 3',
        'preve4_sc' => 'Prezzo scontato 4',
        'sconto' => 'Discount',
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
        'peso_specifico' => 'Specific Gravity / Multiplier',
        'volume_specifico' => 'Specific volume',
        'pack_units' => 'Pieces in packaging',
        'codcon' => 'Account of income from sales',
        'id_cost' => 'Account of cost on purchases',
        'annota' => 'Note (also published on the website)',
        'document' => 'documentos y/o certificaciones',
        'web_mu' => 'Measurement Units on the website',
        'web_price' => 'Selling price on the website',
        'web_multiplier' => 'Web precio multiplicador',
        'web_public' => 'Public website',
        'web_public_value' => array(0 => 'No', 1 => 'Yes'),
        'depli_public' => 'Public on depliant',
        'depli_public_value' => array(0 => 'No', 1 => 'Yes'),
        'web_url' => 'Web url<br />(es: http://site.com/item.html)',
        'modal_ok_insert' => 'Product inserted successfully, click on the X on the upper right corner to exit or...',
        'iterate_invitation' => 'INSERT ANOTHER ITEM IN STOCK!',
        'browse_for_file' => 'Seleccionar un archivo',
        'id_anagra' => 'Fornitore',
        'mesg' => array('La ricerca non ha dato risultati!',
            'Inserire almeno 1 carattere!',
            'Cambia fornitore'
        )
    )
);
?>