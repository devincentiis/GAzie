<?php

/*
  --------------------------------------------------------------------------
  GAzie - Gestione Azienda
  Copyright (C) 2004-2024 - Antonio De Vincentiis Montesilvano (PE)
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
 */

/*
  -- TRANSLATED BY : Dante Becerra Lagos (softenglish@gmail.com)
 */

$transl['vendit'] = array('name' => "Ventas",
    'title' => "Administracion de Ventas",
    'm2' => array(1 => array("Administracion de Boletas Fiscales", "Boletas"),
        2 => array("Administracion de Facturacion ", "Facturacion"),
        3 => array("Administracion de Notas de Entrega", "Notas de Entrega"),
        4 => array("Administracion de Estimacion de Ventas", "Estimaciones"),
        5 => array("Pedidos de Venta", "Pedidos"),
        6 => array("Administraci&oacute;n de Cuentas", "Cuentas"),
        7 => array("Administracion de Clientes", "Clientes"),
        8 => array("Administracion de Agentes de Venta", "Agentes"),
        9 => array("Estadisticas de Venta", "Estadisticas"),
        10 => array("Contratos", "Contratos"),
        11 => array("Las partidas abiertas horario", "Partidas abiertas ")
    ),
    'm3' => array(1 => array("Emitir Recibos Fiscal", "Emitir Recibos"),
        2 => array("Emitir Factura de Venta", "Emitir Factura"),
        3 => array("Emitir Nota de Credito", "Emitir Nota de Credito"),
        4 => array("Emitir Nota de Deuda", "Emitir Nota de Deuda"),
        5 => array("Imprimir Documento Emitido", "Reimprimir Documentos"),
        6 => array("Crear Facturas Diferidas desde D.d.T.", "Facturas D.d.T."),
        7 => array("Facturas de Contabilidad Emitidas", "Facturas de Contabilidad"),
        8 => array("Emitir Notas de Entrega", "Emitir D.d.T."),
        9 => array("Emitir Estimaciones de Venta", "Estimaciones de Venta"),
        10 => array("Pedidos Recibidos de Cliente", "Pedidos de Cliente"),
        11 => array("Emitir Pedidos de clientes", "Emitir Pedidos"),
        12 => array("Emitir nueva cuenta de cambio", "Nueva cuenta de cambio"),
        13 => array("Crear cuenta desde Factura de Venta", "Crear cuenta desde Facturas"),
        14 => array("Imprimir cuenta creada", "Imprimir cuenta de cambio"),
        15 => array("Lista de Cuentas para banco", "Cuentas para reporte de cambio"),
        16 => array("Crear archivo RiBa estandar CBI", "Crear archivo RiBa"),
        17 => array("Cuentas de contabilidad", "Cuentas de contabilidad para cambio"),
        18 => array("Insertar nuevos clientes", "Nuevos clientes"),
        19 => array("Reporte de recibibles desde clientes ", "Reporte de recibibles"),
        20 => array("Insertar nuevo agente de ventas", "Nuevo agente"),
        21 => array("Reporte de boletas de pagos a clientes", "Boletas de pagos"),
        22 => array("Insertar nuevo contrato", "Nuevo Contrato"),
        23 => array("Crear recibido/factura desde contratos ", "Crear recibido/factura desde contratos"),
        24 => array("Contabilidad recibidos/factura emitidos", "Contabilidad recibidos/factura"),
        25 => array("Reporte de recibidos", "Recibidos"),
        26 => array("Cerrar caja registradora diaria", "Cerrar ECR diaria"),
        27 => array("Emitir nueva Cuenta", "Emitir Cuenta"),
        28 => array("Importaci�n desde el sitio web", "importaci&oacute;n desde el web"),
        29 => array("Crear archivo MAV estandar CBI", "Crear archivo MAV"),
        30 => array("Informe de la factura electr�nica", "informe de Factura Electr�nica"),
        31 => array("Reporte de partidas abiertas", "Reporte de partidas abiertas "),
        32 => array("Selezione e stampa stato clienti", "Stato delle scadenze"),
        33 => array("Gestione degli indirizzi di destinazione dei Clienti", "Destinazioni"),
        34 => array("Inserimento nuovo indirizzo di destinazione", "Nuova destinazione"),
        35 => array("Emetti Ricevuta Fiscale", "Emetti Ricevuta"),
        36 => array("Stampa lista documenti", "Lista documenti"),
        37 => array("Stampa lista fornitori", "Lista fornitori"),
        38 => array("Stampa analisi acquisti clienti", "Analisi acquisti clienti"),
        39 => array("Stampa analisi agenti", "Analisi agenti"),
        40 => array("Esportazione articoli venduti", "Esportazione articoli venduti"),
        41 => array("Gestione sconti clienti/articoli", "Sconti clienti/articoli"),
        42 => array("Gestione sconti clienti/raggruppamenti statistici", "Sconti clienti/raggruppamenti statistici"),
        43 => array("Stampa analisi fatturato clienti", "Analisi fatturato clienti"),
        44 => array("Stampa analisi fatturato cliente x fornitore", "Analisi fatturato cliente x fornitore"),
        45 => array("Vendita bene ammortizzabile (su libro cespiti)", "Alienazione bene ammortizzabile"),
        46 => array("Visualizza Ordini settimanali del giorno", "Ordini settimanali del giorno"),
        47 => array("Nuovo ordine settimanale del giorno", "Inserisci ordine settimanale del giorno"),
        48 => array("Evadi ordini settimanali del giorno", "Evadi ordini settimanali del giorno"),
        49 => array("Emetti scontrino prezzi IVA inclusa", "Scontrini prezzi IVA incl."),
        50 => array("Emetti CMR", "Emetti CMR"),
        51 => array("Lista CMR", "Lista CMR"),
        52 => array("Impacchetta fatture elettroniche", "Impacchetta fatture elettroniche"),
        53 => array("Genera fatture differite da CMR", "Fatturazione CMR"),
        54 => array("Importa XML fattura di vendita", "Importa XML fattura di vendita"),
        55 => array("Lista Clienti per zone", "Lista Clienti per zone"),
        56 => array("Lista dei Registratori Telematici", "Lista Registratori Telematici"),
        57 => array("Nuovo Registratore Telematico", "Nuovo Registratore Telematico"),
        58 => array("Genera file RID CBI-SEPA", "Distinta RID (xml)"),
        59 => array("Lista delle distinte generate", "Lista delle distinte"),
        60 => array("Stampa libera lista effetti", "Stampa lista effetti"),
        61 => array("Nuovo gruppo clienti", "Nuovo gruppo clienti"),
        62 => array("Lista gruppi clienti", "Lista gruppi clienti")
    )
);
?>
