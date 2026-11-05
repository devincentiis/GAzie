<?php
// Override delle variabili dei moduli per il tema adminlte_clean.
// Formato: $gzOverrides['nome_variabile'] = array('valore attuale' => 'valore nuovo');
// Le sostituzioni vengono applicate in HeadMain(), dopo che il modulo ha definito le sue variabili.
$gzOverrides = array(
  // intestazioni delle tabelle di magazz/report_artico.php (chiave = testo mostrato)
  'sortable_headers' => array(
    "Prezzo vend.<br/>listino 1" => "Prezzo L1",
    "Merce<br/>Servizio" => "Tipo",
    "Codice<br/>Fornitore" => "Cod.Fornit.",
    "Stampa<br/>Duplica" => "Dettagli",
    "Operazioni<br/>Stato" => "Dettagli",
  ),
);
