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
require("../../library/include/datlib.inc.php");
$admin_aziend = checkAdmin(7);
$msg = '';

function createNewTable($table, $new_id) {
  global $table_prefix;
  $results = gaz_dbi_query("SHOW CREATE TABLE " . $table);
  $row = gaz_dbi_fetch_array($results);
  $key = 'Create Table';
  if (array_key_exists('Create View', $row)) $key = 'Create View';
	// aggiung una query per l'azzeramento dell'eventuale auto_increment
	$prep_sql=$row[$key].";\n";
	if (preg_match("/AUTO_INCREMENT=/i", $prep_sql) && preg_match("/$table_prefix\_[0-9]{3}/", $prep_sql)) {
		$prep_sql."ALTER TABLE `".$table."` AUTO_INCREMENT=0;\n";
	}
  $retval = preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $new_id), $prep_sql);
  return $retval;
}

if (isset($_POST['ritorno'])) {   //se non e' il primo accesso
  $form['ritorno'] = $_POST['ritorno'];
  $form['codice'] = intval($_POST['codice']);
  $form['ref_co'] = intval($_POST['ref_co']);
  $form['clfoco'] = intval($_POST['clfoco']);
  $form['base_arch'] = intval($_POST['base_arch']);
  $form['artico_catmer'] = intval($_POST['artico_catmer']);
  if (isset($_POST['users'])) {
    $form['users'] = substr($_POST['users'], 0, 8);
    $where_user = "company_id = " . $form['ref_co'];
  } else {
    $form['users'] = '';
    $where_user = "company_id = " . $form['ref_co'] . " AND adminid = '" . $_SESSION["user_name"] . "'";
  }
  if (isset($_POST['Submit'])) { // conferma tutto
    //eseguo i controlli formali
    $code_exist = gaz_dbi_dyn_query('codice', $gTables['aziend'], "codice = " . $form['codice'], 'codice DESC', 0, 1);
    $code = gaz_dbi_fetch_array($code_exist);
    if ($code) {
        $msg .= "1+";
    }
    if ($form['codice'] <= 0 || $form['codice'] > 999) {
        $msg .= "0+";
    }
    if (empty($msg)) { // nessun errore
      // prendo i dati dell'azienda di riferimento
      $ref_company = gaz_dbi_get_row($gTables['aziend'], 'codice', $form['ref_co']);
      // richiamo le tabelle dall'azienda di riferimento richiesta
      $tables = gaz_dbi_query("SHOW FULL TABLES FROM $Database LIKE '" . $table_prefix . "\_" . sprintf('%03d', $form['ref_co']) . "%'");
      $dbViews = array();
      while ($r = gaz_dbi_fetch_array($tables)) {
        /*
          if is not a base table then queue to process it later
          because tables that the view depends on may not exist at this time
          !! hopefully no one will create views that depends on other views !!
        */
        if ($r[1] == 'VIEW') {
          $dbViews[] = $r[0];
          continue;
        }
        // CREO LA STRUTTURA DELLA TABELLA
        $sql = createNewTable($r[0], $form['codice']);
        gaz_dbi_query($sql);
        if (preg_match("/[a-zA-Z0-9]*.aliiva$/", $r[0]) ||
          preg_match("/[a-zA-Z0-9]*.caumag$/", $r[0]) ||
          preg_match("/[a-zA-Z0-9]*.caucon$/", $r[0]) ||
          preg_match("/[a-zA-Z0-9]*.caucon_rows$/", $r[0]) ||
          preg_match("/[a-zA-Z0-9]*.pagame$/", $r[0]) ||
          preg_match("/[a-zA-Z0-9]*.portos$/", $r[0]) ||
          preg_match("/[a-zA-Z0-9]*.spediz$/", $r[0])) { // queste tabelle le copio identiche anche con i dati provenienti dall'azienda di riferimento
          switch ($form['base_arch']) {
            case 0:  // SOLO STRUTTURA
            break;
            default: // POPOLO CON I DATI
              $sql = " INSERT INTO `" . preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $form['codice']), $r[0]) . "`  SELECT * FROM `" . $r[0] . "` ;";
              gaz_dbi_query($sql);
            break;
          }
        } elseif (preg_match("/[a-zA-Z0-9]*.company_config$/", $r[0])) { // questa tabella di configurazione azienda la popolo...
          $sql = " INSERT INTO `" . preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $form['codice']), $r[0]) . "`  SELECT * FROM `" . $r[0] . "` ;";
          gaz_dbi_query($sql);
          //  ma senza valori della colonna "val" tranne che per il foglio di stile della fattura elettronica e il testo sulla mail di invio documenti
          $sql = " UPDATE `" . preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $form['codice']), $r[0]) . "`  SET `val` ='' WHERE ( `var` <> 'fae_style' AND `var` <> 'company_email_text' );";
          gaz_dbi_query($sql);
        } elseif (preg_match("/[a-zA-Z0-9]*.company_data$/", $r[0])) { // questa tabella con altri dati aziendali la popolo ma senza valori della colonna "data"
          $sql = " INSERT INTO `" . preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $form['codice']), $r[0]) . "`  SELECT * FROM `" . $r[0] . "` ;";
          gaz_dbi_query($sql);
          // svuoto la colonna data
          $sql = " UPDATE `" . preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $form['codice']), $r[0]) . "`  SET data ='' ;\n\n";
          gaz_dbi_query($sql);
        } elseif (preg_match("/[a-zA-Z0-9]*.imball$/", $r[0]) ||
          preg_match("/[a-zA-Z0-9]*.vettor$/", $r[0])) { // per queste tabella mi baso sulla scelta dell'utente
          switch ($form['base_arch']) {
            case 2: // POPOLO CON I DATI
              $sql = " INSERT INTO `" . preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $form['codice']), $r[0]) . "`  SELECT * FROM `" . $r[0] . "` ;";
              gaz_dbi_query($sql);
            break;
            default: // SOLO STRUTTURA
            break;
          }
        } elseif (preg_match("/[a-zA-Z0-9]*.artico$/", $r[0]) ||
          preg_match("/[a-zA-Z0-9]*.catmer$/", $r[0])) {
          switch ($form['artico_catmer']) {
            case 1: // POPOLO CON GLI ARTICOLI DI MAGAZZINO
              $sql = " INSERT INTO `" . preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $form['codice']), $r[0]) . "` SELECT * FROM `" . $r[0] . "` ;";
              gaz_dbi_query($sql);
            break;
            default:  // SOLO STRUTTURA
            break;
          }
        } elseif (preg_match("/[a-zA-Z0-9]*.clfoco$/", $r[0])) { // per la tabelle del piano dei conti mi baso sulla scelta dell'utente
          switch ($form['clfoco']) {
            case 0: // SOLO STRUTTURA
            break;
            case 1: // POPOLO CON I DATI
              $sql = " INSERT INTO `" . preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $form['codice']), $r[0]) . "` SELECT * FROM `" . $r[0] . "`
                   WHERE (codice < " . ($ref_company['mascli'] * 1000000 + 1) . " OR codice > " . ($ref_company['mascli'] * 1000000 + 999999) . ") AND
                         (codice < " . ($ref_company['masfor'] * 1000000 + 1) . " OR codice > " . ($ref_company['masfor'] * 1000000 + 999999) . ") AND
                         (codice < " . ($ref_company['masban'] * 1000000 + 1) . " OR codice > " . ($ref_company['masban'] * 1000000 + 999999) . ");";
              gaz_dbi_query($sql);
              break;
            case 2: // POPOLO CON I DATI BANCHE, CLIENTI, FORNITORI
              $sql = " INSERT INTO  `" . preg_replace("/$table_prefix\_[0-9]{3}/", $table_prefix . sprintf('_%03d', $form['codice']), $r[0]) . "` SELECT * FROM `" . $r[0] . "` ;";
              gaz_dbi_query($sql);
            break;
          }
        }
      }
      // process any pending view queued to be created
      foreach($dbViews as $viewName){
        $view = createNewTable($viewName, $form['codice']);
        gaz_dbi_query($view);
      }
      // inserisco la nuova azienda nel suo archivio con una descrizione da modificare manualmente
      $upd = 'Modificare i dati di questa azienda';
      $new_company = $ref_company;
      $new_company['codice'] = $form['codice'];
      $new_company['ragso1'] = 'AZIENDA NUOVA N.' . $form['codice'];
      $new_company['ragso2'] = $upd;
      $new_company['image'] = '';
      $new_company['sedleg'] = '';
      $new_company['legrap_pf_nome'] = '';
      $new_company['luonas'] = '';
      $new_company['pronas'] = '';
      $new_company['indspe'] = $upd;
      $new_company['capspe'] = '';
      $new_company['citspe'] = $upd;
      $new_company['prospe'] = '';
      $new_company['telefo'] = '';
      $new_company['image']  = file_get_contents('../../library/images/company-logo.png');
      $new_company['fax'] = '';
      $new_company['e_mail'] = $upd;
      $new_company['codfis'] = '00000000000';
      $new_company['pariva'] = 0;
      gaz_dbi_table_insert('aziend', $new_company);
      // procedo all'abilitazione degli utenti in base alla scelta fatta dal'operatore
      $user_abilit = gaz_dbi_dyn_query('*', $gTables['admin_module'], $where_user, 'moduleid');
      while ($r = gaz_dbi_fetch_array($user_abilit)) {
        $r['company_id'] = $form['codice'];
        gaz_dbi_table_insert('admin_module', $r);
      }
      changeEnterprise($form['codice']);
      if (!file_exists( DATA_DIR . 'files/' . $form['codice'] )) {
        $dst = DATA_DIR . 'files/' . $form['codice'];
        mkdir($dst, 0740);
        $src = '../../library/images';
        $dir = opendir($src);
        while(false !== ( $file = readdir($dir)) ) {
          if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
              // recurse_copy($src . '/' . $file,$dst . '/' . $file);
            } else {
              copy($src . '/' . $file,$dst . '/' . $file);
            }
          }
        }
        closedir($dir);
        // creo la subdir per contenere le immagini
        $dirimg = DATA_DIR . 'files/' . $form['codice'] . '/images';
        mkdir($dirimg, 0755);
        // creo la subdir per contenere i documenti
        $dirdoc = DATA_DIR . 'files/' . $form['codice'] . '/doc';
        mkdir($dirdoc, 0755);
        // creo la subdir per contenere i file SIAN
        $dirsia = DATA_DIR . 'files/' . $form['codice'] . '/sian';
        mkdir($dirsia, 0755);
        // creo la subdir per contenere i file temporanei
        $dirdoc = DATA_DIR . 'files/' . $form['codice'] . '/tmp';
        mkdir($dirdoc, 0755);
      }
      header('Location: admin_aziend.php?Update&codice=' . $form['codice']);
      exit;
    }
  } elseif (isset($_POST['Return'])) { // torno indietro
    header('Location: ' . $form['ritorno']);
    exit;
  }
} else { //se e' il primo accesso
  $form['ritorno'] = $_SERVER['HTTP_REFERER'];
  $rs_last = gaz_dbi_dyn_query('codice', $gTables['aziend'], 1, 'codice DESC', 0, 1);
  $last = gaz_dbi_fetch_array($rs_last);
  $form['codice'] = $last['codice'] + 1;
  $form['ref_co'] = 0;
  $form['clfoco'] = 1;
  $form['base_arch'] = 1;
  $form['artico_catmer'] = 0;
  $form['users'] = true;
}

require("../../library/include/header.php");
$script_transl = HeadMain();
echo "<form method=\"POST\">";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"" . $form['ritorno'] . "\">\n";
$gForm = new GAzieForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">" . $script_transl['title'] . "</div>\n";
echo "<table class=\"Tmiddle table-striped\">\n";
if (!empty($msg)) {
    echo '<tr><td colspan="3" class="FacetDataTDred">' . $gForm->outputErrors($msg, $script_transl['errors']) . "</td></tr>\n";
}
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\">" . $script_transl['codice'] . "* </td>\n";
echo "\t<td class=\"FacetDataTD\" colspan=\"2\"><input type=\"text\" name=\"codice\" value=\"" . $form['codice'] . "\" align=\"right\" maxlength=\"3\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['ref_co'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->selectFromDB('aziend', 'ref_co', 'codice', $form['ref_co'], 'codice', 0, ' - ', 'ragso1');
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['clfoco'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->variousSelect('clfoco', $script_transl['clfoco_value'], $form['clfoco']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['base_arch'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->variousSelect('base_arch', $script_transl['base_arch_value'], $form['base_arch']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['artico_catmer'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->variousSelect('artico_catmer', $script_transl['artico_catmer_value'], $form['artico_catmer']);
echo "\t </td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class=\"FacetFieldCaptionTD\">" . $script_transl['users'] . "</td><td colspan=\"2\" class=\"FacetDataTD\">\n";
$gForm->selCheckbox('users', $form['users']);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFooterTD\">".$script_transl['sqn']."</td>";
echo "\t </td>\n";
echo '<td colspan=2 class="FacetFooterTD text-center">';
echo '<input name="Submit" class="btn btn-warning" type="submit" value="'.ucfirst($script_transl['submit']).'">';
echo "\t </td>\n";
echo "</tr>\n";
?>
</table>
</form>
<?php
require("../../library/include/footer.php");
?>
