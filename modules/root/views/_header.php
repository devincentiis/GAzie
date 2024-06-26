<!DOCTYPE html>
<html lang="en">
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="robots" content="noindex">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="Antonio De Vincentiis https://www.devincentiis.it">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<?php
// utiLizzo l'ultimo thema scelto dall'ultimo utente con i massimi diritti
$exist_ac=gaz_dbi_query("SHOW TABLES LIKE '" . DB_TABLE_PREFIX ."_admin_config'");
if (gaz_dbi_num_rows($exist_ac) >= 1){
    $rsu = gaz_dbi_dyn_query('var_value', $gTables['admin'].' LEFT JOIN '. $gTables['admin_config'].' ON '. $gTables['admin'].'.user_name='. $gTables['admin_config'].'.adminid', $gTables['admin_config'].".var_name='theme'",  $gTables['admin'].'.Abilit DESC,'.$gTables['admin'].'.datacc DESC');
    $u = gaz_dbi_fetch_array($rsu);
} else {
    $u['var_value']='lte';
}
// CONTROLLO QUANTE AZIENDE HA L'INSTALLAZIONE
$rs_az = gaz_dbi_dyn_query('*', $gTables['aziend'], '1', 'codice DESC');
$az = gaz_dbi_fetch_array($rs_az);
if (gaz_dbi_num_rows($rs_az) > 1 || !file_exists(DATA_DIR.'files/'.$az['codice'].'/favicon.ico')) { // ho più aziende gestite oppure non ne ho alcuna sulla prima: devo usare una icona generica derivante dal tema dell'ultimo utente amministratore che è entrato
?>
  <meta name="apple-mobile-web-app-title" content="GAzie - Gestione AZIEndale">
  <link rel="shortcut icon" href="../../library/images/favicon.ico">
  <link rel="apple-touch-icon"  href="../../library/images/apple-icon-114x114-precomposed.png">
  <link rel="apple-touch-startup-image"  href="../../library/images/apple-icon-114x114-precomposed.png">
  <title>Login</title>
<?php
  $sfondo=base64_encode(file_get_contents('../../library/images/sfondo.png'));
} else { // ho una sola azienda, prendo la sua
?>
  <meta name="apple-mobile-web-app-title" content="<?php echo $az['ragso1'];?>">
<?php
  $ico=base64_encode(file_get_contents(DATA_DIR.'files/'.$az['codice'].'/favicon.ico'));
  $ico114=base64_encode(file_get_contents(DATA_DIR.'files/'.$az['codice'].'/logo_114x114.png'));
  $sfondo=base64_encode(file_get_contents(DATA_DIR.'files/'.$az['codice'].'/sfondo.png'));
?>
  <link rel="icon" href="data:image/x-icon;base64,<?php echo $ico?>"  type="image/x-icon" />
  <link rel="icon" sizes="114x114" href="data:image/x-icon;base64,<?php echo $ico114?>"  type="image/x-icon" />
  <link rel="apple-touch-icon" href="data:image/x-icon;base64,<?php echo $ico114?>"  type="image/x-icon">
  <link rel="apple-touch-startup-image" href="data:image/x-icon;base64,<?php echo $ico114?>"  type="image/x-icon">
  <link rel="apple-touch-icon-precomposed" sizes="114x114" href="data:image/x-icon;base64,<?php echo $ico114?>"  type="image/x-icon" />
<?php
}
?>
  <link rel="stylesheet" href="../../js/bootstrap/3.4.1/css/bootstrap.min.css" >
  <link rel="stylesheet" type="text/css" href="../..<?php echo $u['var_value'];?>/scheletons/default.css">
  <link rel="stylesheet" type="text/css" href="../..<?php echo $u['var_value'];?>/skins/default.css">
</head>
<body style="background-image: url('data:image/x-icon;base64,<?php echo $sfondo; ?>')">

