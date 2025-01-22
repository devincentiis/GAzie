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
require '../../vendor/autoload.php';
if (isset($_SERVER['SCRIPT_FILENAME']) && (str_replace('\\', '/', __FILE__) == $_SERVER['SCRIPT_FILENAME'])) {
    exit('Accesso diretto non consentito');
}

connectToDB();

session_cache_limiter('nocache');
$scriptname = basename($_SERVER['PHP_SELF']);
$direttorio = explode("/", dirname($_SERVER['PHP_SELF']));
$module = array_pop($direttorio);
$radixarr = array_diff($direttorio, array('modules', $module, ''));
$radix = implode('/', $radixarr);
if (strlen($radix) > 1) {// session.name cannot contain any of the following '=,;.[ \t\r\n\013\014'
    session_name(str_replace( array( '=',',',';','.','[','\t','\r','\n','\013','\014' ), '', implode($radixarr)));
} else {
    session_name(str_replace( array( '=',',',';','.','[','\t','\r','\n','\013','\014' ), '', _SESSION_NAME));
}
session_start();
session_gc();
$prev_script = '';
if (isset($_SERVER["HTTP_REFERER"])) {
    $prev = explode("?", basename($_SERVER["HTTP_REFERER"]));
    $prev_script = $prev[0];
}
$script_uri = basename($_SERVER['REQUEST_URI']);
$mod_uri = '/' . $module . '/' . $script_uri;

//sostituisci a partire da destra
function str_lreplace($search, $replace, $subject)
{
    $pos = strrpos($subject, $search);
    if($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}

//controlla se ci sono versioni customizzate
//checkCustoms($_SERVER['REQUEST_URI']);
function checkCustoms($posizione) {
    $intpos=0;
    $pos="";
    $found=false;
    $posizione = explode( '/',$posizione );
    foreach ( $posizione as $posizione_modulo ) {
        if ( $posizione_modulo == "modules" || $found) {
            $found=true;
            $intpos++;
            $pos .= $posizione_modulo.'/';
        }
    }
    $pos = rtrim($pos,"/");
    if ( strpos($pos, "?") ) {
        $pos = explode ("?", $pos);
        if ( is_array($pos) ) $pos = $pos[0];
    }
    $custom_str = str_lreplace( "/", "/custom_", $pos);
    if ( file_exists ("../../". $custom_str ) ) {
        header ( "Location: ../../".$custom_str );
        die;
    }
}
//controllo se esiste la cartella dell'azienda corrente
function controllaEsistenzaCartelle()
{
	global $admin_aziend;
	if ( file_exists ( '../../data/files/'.$admin_aziend['codice'] ) )
		return false;
	else
		return true;
}
//funzione che estrae i valori tra i tag html di una stringa
function getTextBetweenTags($tag, $html, $strict = 0) {
    $dom = new domDocument;
    if ($strict == 1) {
        $dom->loadXML($html);
    } else {
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        foreach (libxml_get_errors() as $error) {
            //echo $error->code." - Line: ".$error->line;
        }
    }
    $dom->preserveWhiteSpace = false;
    $content = $dom->getElementsByTagname($tag);
    $out = array();
    foreach ($content as $item) {
        $out[] = $item->nodeValue;
    }
    libxml_use_internal_errors(false);
    return $out;
}

function gaz_flt_var_assign($flt, $typ, $tab="") {
    global $where;
    $op = "";
    if (isset($_GET[$flt]) && $_GET[$flt] != 'All' && $_GET[$flt] != "") {
        if ( $tab!="" ) $tab .=".";
        if ($typ == "i") {
            $where .= " AND " . $tab.$flt . " = " . intval($_GET[$flt]) . " ";
        } else if ($typ == "v") {
            if ( $_GET[$flt]=="nochiusi") $op .= " !='chiuso'";
            else $op = " LIKE '%" . addslashes(substr($_GET[$flt], 0, 30)) . "%'";
            $where .= " AND " . $tab.$flt .$op ;
        } else if ($typ == "d") {
            $where .= " AND $tab$flt >= \"" . intval($_GET[$flt]) . "/01/01\" and $tab$flt <= \"" . intval($_GET[$flt]) . "/12/31\"";
        }
    }
}

// crea una select che permette di filtrare la colonna di una tabella
// $flt - colonna sulla quale eseguire il filtro
//
// $optval - valore opzionale se diverso dal valore del campo, pu� essere array (es: stato=0 diventa stato=aperto preso da var)
function gaz_flt_disp_select($flt, $fltdistinct, $tbl, $where, $orderby, $optval = "") {
    ?><select class="form-control input-sm" name="<?php echo $flt; ?>" onchange="this.form.submit()">
    <?php
    if (isset($_GET[$flt]))
        $fltget = $_GET[$flt];
    else
        $fltget = "";
    ?>
        <option value="All" <?php echo ($flt == "All") ? "selected" : ""; ?>>Tutti</option> <?php //echo $script_transl['tuttitipi'];             ?>

        <?php
        if ( $flt=="stato") {
            echo '<option value="nochiusi"';
            echo ($fltget == "nochiusi") ? "selected" : "";
            echo '>non chiusi</option>';
        }
        $res = gaz_dbi_dyn_query("distinct " . $fltdistinct, $tbl, $where, $orderby);
        while ($val = gaz_dbi_fetch_array($res)) {
            if ($fltget == $val[$flt])
                $selected = "selected";
            else
                $selected = "";

            if (is_array($optval)) {
                $testo = $optval[$val[$flt]];
            } else {
                $testo = ($optval != "") ? $val[$optval] : $val[$flt];
            }

            echo "<option value=\"" . $val[$flt] . "\" " . $selected . ">" . $testo . "</option>";
        }
        ?>
    </select><?php
}

function gaz_flt_disp_int($flt, $hint) {
    ?><input type="text" placeholder="<?php echo $hint; ?>" class="input-sm form-control" name="<?php echo $flt; ?>" value="<?php if (isset($_GET[$flt])) print $_GET[$flt]; ?>" size="5" class="FacetInput"><?php
}

function gaz_today() {
    $today = date("d/m/Y");
    $tmp = DateTime::createFromFormat('d/m/Y', $today);
    $today = $tmp->format('Y-m-d');
    return $today;
}

function gaz_time_from($time) {
    $time = time() - $time; // to get the time since that moment
    $time = ($time < 1) ? 1 : $time;
    $tokens = array(
        31536000 => 'anni',
        2592000 => 'mesi',
        604800 => 'settimane',
        86400 => 'giorni',
        3600 => 'ore',
        60 => 'minuti',
        1 => 'secondi'
    );
    foreach ($tokens as $unit => $text) {
        if ($time < $unit)
            continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . " fa"; //.(($numberOfUnits>1)?'i':'o');
    }
}

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

function gaz_format_number($number = 0) {
    global $gTables, $currency;
    if (!isset($currency)) {
        $currency = gaz_dbi_get_row($gTables['admin'] . ' LEFT JOIN ' . $gTables['aziend'] . ' ON ' . $gTables['admin'] . '.company_id = ' . $gTables['aziend'] . '.codice LEFT JOIN ' . $gTables['currencies'] . ' ON ' . $gTables['currencies'] . '.id = ' . $gTables['aziend'] . '.id_currency', "user_name", $_SESSION["user_name"]);
    }
    return number_format(floatval($number), $currency['decimal_place'], $currency['decimal_symbol'], $currency['thousands_symbol']);
}

function gaz_format_date($date, $from_form = false, $to_form = false) {
	if (intval($date)==0){
		return null;
	}
  if ($from_form) { // dal formato gg-mm-aaaa o gg/mm/aaaa (es. proveniente da form) a diversi
    $m = intval(substr($date, 3, 2));
    $d = intval(substr($date, 0, 2));
    $Y = intval(substr($date, 6, 4));
    $uts = mktime(0, 0, 0, $m, $d, $Y);
    if ($from_form === true) { // adatto al db
        return date("Y-m-d", $uts);
    } elseif ($from_form === 1) { // per i campi input dei form
        return date("d/m/Y", $uts);
    } elseif ($from_form === 2) { // restituisce l'mktime
        return $uts;
    } elseif ($from_form === 3) { // il valore numerico (confrontabile)
        return date("Ymd", $uts);
    } elseif ($from_form === 'chk') { // restituisce true o false se la data non � stata formattata bene
        return checkdate($m, $d, $Y);
    } else { // altri restituisco il timestamp
        return date("Ymd", $uts);
    }
  } else { // dal formato aaaa-mm-gg oppure aaaa/mm/gg (es. proveniente da db) a diversi
    $uts = mktime(0, 0, 0, intval(substr($date, 5, 2)), intval(substr($date, 8, 2)), intval(substr($date, 0, 4)));
    if ($to_form === false) { // adatto al db
        return date("d-m-Y", $uts);
    } elseif ($to_form === 2) { // restituisce l'mktime
        return $uts;
    } elseif ($to_form === 3) { // il valore numerico (confrontabile)
        return date("Ymd", $uts);
    } else { // adatto ai form input
        return date("d/m/Y", $uts);
    }
  }
}

function gaz_format_datetime($date) {
    $uts = mktime(substr($date, 11, 2), substr($date, 14, 2), substr($date, 17, 2), substr($date, 5, 2), substr($date, 8, 2), substr($date, 0, 4));
    return date("d-m-Y H:i:s", $uts);
}

function gaz_html_call_tel($tel_n) {
    if ($tel_n != "_") {
        preg_match_all("/([\d]+)/", $tel_n, $r);
        $ret = '<a href="tel:' . implode("", $r[0]) . '" >' . $tel_n . "</a>\n";
    } else {
        $ret = $tel_n;
    }
    return $ret;
}

function gaz_html_ae_checkiva($paese, $pariva) {
    $htmlpariva = '<a class="dialog_vies" target="_blank" country="' . $paese . '" ref="' . $pariva . '" style="cursor: pointer;" title="Controllo VIES">' . $paese . ' ' . $pariva . '</a>';
    return $htmlpariva;
}

function gaz_format_quantity($number, $comma = false, $decimal = false) {
    $number = sprintf("%.5f", preg_replace("/\,/", '.', $number)); //max 5 decimal
    if (!$decimal) { // decimal is not defined (deprecated in recursive call)
        global $gTables;
        $config = gaz_dbi_get_row($gTables['aziend'], 'codice', 1);
        $decimal = $config?$config['decimal_quantity']:9;
    }
    if ($decimal == 9) { //float
        if ($comma == true) {
            return preg_replace("/\./", ',', floatval($number));
        } else {
            return floatval($number);
        }
    } else { //decimal defined
        if ($comma == true) {
            return number_format($number, $decimal, ',', '.');
        } else {
            return number_format($number, $decimal, '.', '');
        }
    }
}

function gaz_set_time_limit($time) {
    global $disable_set_time_limit;
    if (!$disable_set_time_limit) {
        set_time_limit($time);
    }
}

function CalcolaImportoRigo($quantita, $prezzo, $sconto, $decimal = 2) {
  if (is_array($sconto)) {
    $res = 1;
    foreach ($sconto as $val) {
      if (!$val) $val=0.00;
      $res -= $res * $val / 100;
    }
    $res = 1 - $res;
  } else {
    if (!$sconto)	$sconto=0.00;
    $res = $sconto / 100;
  }
  $prezzo=(float)$prezzo;
  return round((float)$quantita * ($prezzo - $prezzo * (float)$res) , (int)$decimal);
}

//
// La funzione table_prefix_ok() serve a determinare se il prefisso
// delle tabelle e' valido, secondo lo schema di Gazie, oppure no.
// In pratica, si verifica che inizi con la stringa `gaz' e pu�
// continuare con lettere minuscole e cifre numeriche, fino
// a un massimo di ulteriori nove caratteri
//
function table_prefix_ok($table_prefix) {
  if (preg_match("/^[g][a][z][a-z0-9]{0,9}$/", $table_prefix) == 1) {
      return TRUE;
  } else {
      return FALSE;
  }
}

//
// La funzione table_prefix_get() serve a estrapolare il prefisso
// del nome di una tabella di Gazie, usando le stesse regole
// della funzione table_prefix_ok() per tale individuazione.
// Il riconoscimenti si basa soprattutto sul fatto che il prefisso
// dei nomi delle tabelle non possa contenere il trattino basso.
//
// ATTENZIONE: il funzionamento corretto di questa funzione
//             � ancora da verificare e viene aggiunta solo
//             come suggerimento, in abbinamento alla funzione
//             table_prefix_ok().
//
function table_prefix_get($table_name) {
  $matches;
  if (preg_match("/^([g][a][z][a-z0-9]{0,9})[_]/", $table_name, $matches) == 1) {
      return $matches[1];
  } else {
      return "";
  }
}

function tornaPaginaPrecedente() {
  echo "<script type='text/javascript'>javascript:history.go(-1);</script>";
}

function isDDT($tipdoc) {
  return (strpos($tipdoc,'DD')==0 || $tipdoc == "RDV");
}

function getRegimeFiscale($si){
	global $gTables;
	$res=false;
  $conf_rf=gaz_dbi_get_row($gTables['company_config'], 'var', 'sezione_regime_fiscale');
  $rrff=($conf_rf)?trim($conf_rf['val'].''):0;
	$rf=explode(';',$rrff);
	if (isset($rf[0])&&!empty($rf[0])){// ho almeno un altro regime
		foreach($rf as $v){
			$exrf=explode('=',$v);
			if (preg_match("/^([1-8]{1})$/", $exrf[0], $rgsez)&&preg_match("/^(RF[0-9]{2})$/", $exrf[1], $rgrf)){
				if ($rgsez[1]==$si) $res=$rgrf[1];
			}
		}
	}
	return $res;
}

class selectAgente extends SelectBox {

    private $tipo;

    public function __construct($name, $tipo = "C") {
        parent::__construct($name);
        $this->tipo = $tipo;
    }

    function output($class = '') {
        if ($this->tipo == "C") {
            $nomeTabella = 'agenti';
        } else {
            $nomeTabella = 'agenti_forn';
        }
        global $gTables;
        $query = "SELECT " . $gTables[$nomeTabella] . ".id_agente," . $gTables[$nomeTabella] . ".id_fornitore," . $gTables['anagra'] . ".ragso1," . $gTables['clfoco'] . ".codice
                  FROM " . $gTables[$nomeTabella] . " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables[$nomeTabella] . ".id_fornitore = " . $gTables['clfoco'] . ".codice
                  LEFT JOIN " . $gTables['anagra'] . " ON " . $gTables['clfoco'] . ".id_anagra = " . $gTables['anagra'] . ".id";
        SelectBox::_output($query, 'ragso1', True, '', '', "id_agente", '', $class);
    }

}

class Config {

  function getValue($variable) {
    global $gTables;
    $variable = filter_var(substr($variable, 0, 100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $cval = gaz_dbi_get_row($gTables['config'], 'variable', $variable);
    return $cval?$cval['cvalue']:false;
  }

  function setValue($variable, $value = array('description' => '', 'cvalue' => '', 'show' => 0)) {
      /* in $variabile va sempre il nome della variabile,
       * la tabella viene aggiornata ne caso in cui il nome variabile esiste mentre
       * viene inserita qualora non esista.
       * In caso di inserimento � necessario passare un array in $value mentre in caso di
       * aggiornamento � sufficiente un valore */
      global $gTables;
      $variable = filter_var(substr($variable, 0, 100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $result = gaz_dbi_dyn_query("*", $gTables['config'], "variable='" . $variable . "'");
      if (gaz_dbi_num_rows($result) >= 1) { // � un aggiornamento
          if (is_array($value)) {
              $row = gaz_dbi_fetch_array($result);
              $value['cvalue'] = filter_var(substr($value['cvalue'], 0, 100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
              $this->{$variable} = $value['cvalue'];
              $value['variable'] = $variable;
              ;
              gaz_dbi_table_update('config', array('id', $row['id']), $value);
          } else {
              $this->{$variable} = filter_var(substr($value, 0, 100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
              gaz_dbi_put_row($gTables['config'], 'variable', $variable, 'cvalue', $value['cvalue']);
          }
      } else { // � un inserimento
          gaz_dbi_table_insert('config', $value);
      }
  }

}

class UserConfig {
	public $body_send_doc_email = '';
	public $theme = '';
	public $LTE_Fixed = '';
	public $LTE_Boxed = '';
	public $LTE_Collapsed = '';
	public $LTE_Onhover = '';
	public $LTE_SidebarOpen = '';
	public $az_email = '';

  function __construct() {
    global $gTables;
    $results = gaz_dbi_query("SELECT var_name, var_value FROM " . $gTables['admin_config']." WHERE adminid='".$_SESSION['user_name']."'");
    while ($row = gaz_dbi_fetch_object($results)) {
      $this->{$row->var_name} = $row->var_value;
    }
  }

  function getValue($variable) {
      return $this->{$variable};
  }

  function setValue($variable, $value = ['var_descri' => '', 'var_value' => '']) {
    /* in $variabile va sempre il nome della variabile,
     * la tabella viene aggiornata ne caso in cui il nome variabile esiste mentre
     * viene inserita qualora non esista.
     * In caso di inserimento è necessario passare un array in $value mentre in caso di
     * aggiornamento è sufficiente un valore */
    global $gTables;
    $variable = filter_var(substr($variable, 0, 100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $result = gaz_dbi_dyn_query("*", $gTables['admin_config'], "var_name='" . $variable . "' AND adminid='".$_SESSION['user_name']."'");
    if (gaz_dbi_num_rows($result) >= 1) { // è un aggiornamento
      if (is_array($value)) {
        $row = gaz_dbi_fetch_array($result);
        $value['var_value'] = filter_var(substr($value['var_value'], 0, 100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $this->{$variable} = $value['var_value'];
        $value['var_name'] = $variable;
        gaz_dbi_table_update('admin_config', array('id', $row['id']), $value);
      } else {
        $this->{$variable} = filter_var(substr($value, 0, 100), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
     		gaz_dbi_query ("UPDATE ".$gTables['admin_config']." SET `var_value`='".$value['var_value']."' WHERE `var_name`='".$variable . "' AND adminid='".$_SESSION['user_name']."'");
      }
    } else { // è un inserimento
      gaz_dbi_table_insert('admin_config', $value);
    }
  }

  function setDefaultValue() {
    $this->setValue('LTE_Fixed', ["var_name" => "LTE_Fixed", "var_descri" => "Attiva lo stile fisso", "var_value" => "false"]);
    $this->setValue('LTE_Boxed', ["var_name" => "LTE_Boxed", "var_descri" => "Attiva lo stile boxed", "var_value" => "false"]);
    $this->setValue('LTE_Collapsed', ["var_name" => "LTE_Collapsed", "var_descri" => "Collassa il menu principale", "var_value" => "true"]);
    $this->setValue('LTE_Onhover', ["var_name" => "LTE_Onhover", "var_descri" => "Espandi automaticamente il menu", "var_value" => "false"]);
    $this->setValue('LTE_SidebarOpen', ["var_name" => "LTE_SidebarOpen", "var_descri" => "Mantieni la barra aperta", "var_value" => "false"]);
  }

}

// end Config

class configTemplate {
  public $template;
  function __construct() {
      global $gTables;
      $row = gaz_dbi_get_row($gTables['aziend'], 'codice', $_SESSION['company_id']);
      $this->template = $row['template'];
  }
  function setTemplateLang($lang) {
      $this->template .= ".".$lang;
  }
}

class Anagrafica {
	public $gTables = [];
	public $partnerTables = '';
	public $cache = [];
  public $codice;
    function __construct() {
        global $gTables;
        $this->gTables = $gTables;
        $this->partnerTables = $gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id';
        $this->cache = [];
    }

    function getPartner($idClfoco, $cache = false, $refresh = false) {
        if ($cache) {
             if (array_key_exists($idClfoco, $this->cache) && !$refresh) {
                 return $this->cache[$idClfoco];
             }
             $anagra = gaz_dbi_get_anagra($this->partnerTables, "codice", $idClfoco);
             $this->cache[$idClfoco] = $anagra;
             return $anagra;
        }
        return gaz_dbi_get_anagra($this->partnerTables, "codice", $idClfoco);
    }

    function getPartnerData($idAnagra, $acc = 1) {
        global $table_prefix;
        $rs_co = gaz_dbi_dyn_query('codice', $this->gTables['aziend'], 1);
        $partner_data = [];
        $partner = [];
        while ($co = gaz_dbi_fetch_array($rs_co)) {
            $rs_partner = gaz_dbi_query('SELECT * FROM ' . $table_prefix . sprintf('_%03d', $co['codice']) . 'clfoco WHERE ' .
                    ' codice BETWEEN ' . $acc . '00000001 AND ' . $acc . '99999999 AND id_anagra =' . $idAnagra . '  LIMIT 1');
            $r_p = gaz_dbi_fetch_array($rs_partner);
            if ($r_p) {
                $r_p['id_aziend'] = $co['codice'];
                $partner_data[] = $r_p;
            }
        }
        if (sizeof($partner_data) == 0) {  // se non ci sono tra i partner omogenei controllo su tutti
            $rs_co = gaz_dbi_dyn_query('codice', $this->gTables['aziend'], 1);
            while ($co = gaz_dbi_fetch_array($rs_co)) {
                $rs_partner = gaz_dbi_query('SELECT * FROM ' . $table_prefix . sprintf('_%03d', $co['codice']) . 'clfoco WHERE ' .
                        ' id_anagra =' . $idAnagra . '  LIMIT 1');
                $r_p = gaz_dbi_fetch_array($rs_partner);
                if ($r_p) {
                    $r_p['id_aziend'] = $co['codice'];
                    $partner_data[] = $r_p;
                }
            }
        }
        if (sizeof($partner_data) == 0) { // e' un'anagrafica isolata inserisco una tabella vuota
            $partner_data[0] = gaz_dbi_fields('clfoco');
            $partner_data[0]['last_modified'] = 'isolated';
            $partner_data[0]['id_anagra'] = $idAnagra;
        }
        foreach ($partner_data as $k => $row) {
            $partner[$row['last_modified']] = $row;
        }
        ksort($partner);
        $r_a = gaz_dbi_get_row($this->gTables['anagra'], 'id', $idAnagra);
        $data = array_merge(array_pop($partner), $r_a);
        unset($data['codice']);
        return $data;
    }

    function queryPartners($select, $where = 1, $orderby = 2, $limit = 0, $passo = 1900000) {
        $result = gaz_dbi_dyn_query($select, $this->partnerTables, $where, $orderby, $limit, $passo);
        $partners = array();
        while ($row = gaz_dbi_fetch_array($result)) {
            $partners[] = $row;
        }
        return $partners;
    }

    function queryPartnersAes($select, $where, $orderby, $limit = 0, $passo = 1900000) {
        $result = gaz_dbi_query_anagra($select, $this->partnerTables, $where, $orderby, $limit, $passo);
        $partners = array();
        while ($row = gaz_dbi_fetch_array($result)) {
            $partners[] = $row;
        }
        return $partners;
    }

    function updatePartners($codice, $newValue) {
        $newValue['descri'] = $newValue['ragso1'] . ((!empty($newValue['ragso2'])) ? ' ' . $newValue['ragso2'] : '');
        gaz_dbi_table_update('clfoco', $codice, $newValue);
        gaz_dbi_update_anagra(array('id', $newValue['id_anagra']), $newValue);
    }

    function anagra_to_clfoco($v, $m, $payment=1) {
        $last_partner = gaz_dbi_dyn_query("*", $this->gTables['clfoco'], 'codice BETWEEN ' . $m . '000001 AND ' . $m . '999999', "codice DESC", 0, 1);
        $last = gaz_dbi_fetch_array($last_partner);
        if ($last) {
            $v['codice'] = $last['codice'] + 1;
        } else {
            $v['codice'] = $m . '000001';
        }
        $v['descri'] = $v['ragso1'];
        $v['codpag'] = $payment;
		// inserisco i valori sono quelli statisticamente pi� utilizzati
        $v['speban'] = 'S';
        $v['addbol'] = 'S';
        $v['spefat'] = 'N';
        $v['stapre'] = 'S';
        if (isset($v['ragso2'])) {
            $v['descri'] .= $v['ragso2'];
        }
        gaz_dbi_table_insert('clfoco', $v);
        return $v['codice'];
    }

    function insertPartner($v) {
        $v['descri'] = $v['ragso1'];
        if (isset($v['ragso2'])) {
            $v['descri'] .= ' '.$v['ragso2'];
        }
        gaz_dbi_insert_anagra($v);
        $v['id_anagra'] = gaz_dbi_last_id();
        $this->codice=gaz_dbi_table_insert('clfoco', $v);
    }

    function deletePartner($idClfoco) {
        global $gTables;
        gaz_dbi_del_row($gTables['clfoco'], 'codice', $idClfoco);
    }

}

//===============================================================================
// classe generica per la generazione di select box
//================================================================================
class SelectBox {
  public $selected;
    var $name;

    // assegno subito il nome della select box
    function __construct($name) {
        $this->name = $name;
    }

    function setSelected($selected) {
        $this->selected = $selected;
    }

    function addSelected($selected) {
        $this->setSelected($selected);
    }

    function _output($query, $index1, $empty = false, $bridge = '', $index2 = '', $key = 'codice', $refresh = '', $class = false) {
        if (!empty($refresh)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$refresh'; this.form.submit();\"";
        }
        $cl = 'FacetSelect';
        if ($class) {
            $cl = $class;
        }
        echo "\t <select id=\"$this->name\" name=\"$this->name\" class=\"$cl\" $refresh >\n";
        if ($empty) {
            echo "\t\t <option value=\"\"></option>\n";
        }
        $result = gaz_dbi_query($query);
        while ($a_row = gaz_dbi_fetch_array($result)) {
          if (isset($a_row[$index1])){
            $selected = "";
            if ($a_row[$key] == $this->selected) {
                $selected = "selected";
            }
            echo "\t\t <option value=\"" . $a_row[$key] . "\" $selected >";
            if (empty($index2)) {
                if ( strpos( $query, 'banapp' )) echo sprintf("%'.05d\n", $a_row["codabi"])." ".sprintf("%'.05d\n", $a_row["codcab"])."  ";
                echo substr($a_row[$index1], 0, 43) . "</option>\n";
            } else {
                if ( strpos( $query, 'banapp' )) echo sprintf("%'.05d\n", $a_row["codabi"])." ".sprintf("%'.05d\n", $a_row["codcab"])."  ";
                echo substr($a_row[$index1], 0, 38) . $bridge . substr($a_row[$index2], 0, 35) . "</option>\n";
            }
          }
        }
        echo "\t </select>\n";
    }

}

// classe per la generazione di select box dei clienti e fornitori (partner commerciali)
class selectPartner extends SelectBox {
  public $gTables=[];
  public $name ='';
  public $what=[];
  public $selected ='';
    function __construct($name) {
        global $gTables;
        $this->gTables = $gTables;
        $this->name = $name;
		$this->what = array(
			"a.id" => "id",
			"pariva" => "pariva",
			"codfis" => "codfis",
			"a.citspe" => "citta",
			"ragso1" => "ragsoc",
			"(SELECT " . $this->gTables['clfoco'] . ".codice FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1)" => "codice",
			"(SELECT " . $this->gTables['clfoco'] . ".status FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1)" => "status",
			"0" => "codpart",
		);
    }

    function setWhat($m) {
		$this->what = array(
			"a.id" => "id",
			"pariva" => "pariva",
			"codfis" => "codfis",
			"a.citspe" => "citta",
			"ragso1" => "ragsoc",
			"(SELECT " . $this->gTables['clfoco'] . ".codice FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra AND " . $this->gTables['clfoco'] . ".codice BETWEEN " . $m . "000001 AND " . $m . "999999 LIMIT 1)" => "codpart",
			"(SELECT " . $this->gTables['clfoco'] . ".codice FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1)" => "codice",
			"(SELECT " . $this->gTables['clfoco'] . ".status FROM " . $this->gTables['clfoco'] . " WHERE a.id=" . $this->gTables['clfoco'] . ".id_anagra LIMIT 1)" => "status",
		);
    }

    function queryAnagra($where) {
        $rs = gaz_dbi_query_anagra($this->what, $this->gTables['anagra'] . ' AS a', $where, array("a.ragso1" => "ASC"));
        $anagrafiche = array();
        while ($r = gaz_dbi_fetch_array($rs)) {
            $anagrafiche[] = $r;
        }
        return $anagrafiche;
    }

    function queryNomeAgente($id_agente) {
        $retVal = "";
        $rs = gaz_dbi_dyn_query("b.descri as nomeAgente", $this->gTables['agenti'] . ' AS a join ' . $this->gTables['clfoco'] . " as b on a.id_fornitore=b.codice ", "a.id_agente=$id_agente");
//        $anagrafiche = array();
        if ($r = gaz_dbi_fetch_array($rs)) {
            $retVal = $r["nomeAgente"];
        }
        return $retVal;
    }

    function output($mastro, $cerca) {
        global $script_transl;
        $msg = "";
        $put_anagra = '';
        $tabula = " tabindex=\"1\" ";
        if (strlen($cerca) >= 2) {
            if (is_numeric($cerca)) {                       //ricerca per partita iva
                $partners = $this->queryAnagra(array("pariva" => '=' . intval($cerca)));
            } elseif (preg_match('/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]$/i', $cerca)) {   //ricerca per codice fiscale
                $partners = $this->queryAnagra(array("codfis" => " LIKE '%" . addslashes($cerca) . "%'"));
            } else {                                        //ricerca per ragione sociale
                $partners = $this->queryAnagra(array("a.ragso1" => " LIKE '" . addslashes($cerca) . "%'"));
            }
            $numclfoco = sizeof($partners);
            if ($numclfoco > 0) {
                $tabula = " ";
                echo "\t <select name=\"$this->name\" class=\"FacetSelect\">\n";
				foreach ($partners AS $key => $a_row) {
                    $selected = "";
                    $style = '';
                    if ($a_row["codice"] == $this->selected) {
                        $selected = "selected";
                        if ($a_row["codice"] < 1) {
                            $put_anagra = "\t<input type=\"hidden\" name=\"put_anagra\" value=\"" . $a_row['id'] . "\">\n";
                        }
                    }
                    if ($a_row["codice"] < 1) {
                        $style = 'style="background:#FF0000";';
                    }
                    echo "\t\t <option value=\"" . $a_row["codice"] . "\" $selected $style>" . $a_row["ragsoc"] . "&nbsp;" . $a_row["citta"] . "</option>\n";
                }
                echo "\t </select>\n";
            } else {
                $msg = $script_transl['notfound'] . "!\n";
                echo "\t<input type=\"hidden\" name=\"$this->name\" value=\"\">\n";
            }
        } else {
            $msg = $script_transl['minins'] . " 2 " . $script_transl['charat'] . "!\n";
            echo "\t<input type=\"hidden\" name=\"$this->name\" value=\"\">\n";
        }
        echo $put_anagra;
        echo "\t<input type=\"text\" name=\"ragso1\" " . $tabula . " accesskey=\"e\" value=\"" . $cerca . "\" maxlength=\"16\" size=\"10\" class=\"FacetInput\">\n";
        echo $msg;
        //echo "\t<input type=\"image\" align=\"middle\" accesskey=\"c\" " . $tabula . " name=\"clfoco\" src=\"../../library/images/cerbut.gif\" title=\"" . $script_transl['search'] . "\">\n";
        /** ENRICO FEDELE */
        /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
        echo '<button type="submit" class="btn btn-default btn-sm" accesskey="c" name="clfoco" ' . $tabula . ' title="' . $script_transl['search'] . '"><i class="glyphicon glyphicon-search"></i></button>';
        /** ENRICO FEDELE */
    }

  function selectDocPartner($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg='', $m = 0, $anonimo = -1, $tab = 1, $soloMastroSelezionato = false) {
    /* se passo $m=-1 ottengo tutti i partner nel piano dei conti indistintamente
      passare false su $tab se non si vuole la tabulazione
      $soloMastroSelezionato = true se si vogliono visualizzare solo i clienti (o i fornitori) in base a $m
     */
    global $gTables;
    $tab1 = '';
    $tab2 = '';
    $tab3 = '';
    if ($tab) {
      $tab1 = ' tabindex="' . $tab . '"';
      $tab2 = ' tabindex="' . ($tab + 1) . '"';
      $tab3 = ' tabindex="' . ($tab + 2) . '"';
    }
    if (preg_match("/^id_([0-9]+)$/", $val, $match)) { // e' stato selezionata la sola anagrafica
      $partner = gaz_dbi_get_row($gTables['anagra'], 'id', $match[1]);
      echo "\t<input type=\"submit\" value=\"→ \" name=\"fantoccio\" disabled>\n";
      echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
      echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
      echo "\t<input type=\"submit\" tabindex=\"999\" style=\"background:#FFBBBB\"; value=\"" . $partner['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
    } elseif ($val > 100000000) { //vengo da una modifica della precedente select case quindi non serve la ricerca
      $partner = gaz_dbi_get_row($gTables['clfoco'] . ' LEFT JOIN ' . $gTables['anagra'] . ' ON ' . $gTables['clfoco'] . '.id_anagra = ' . $gTables['anagra'] . '.id', "codice", $val);
      echo "\t<input type=\"submit\" value=\"→ \" name=\"fantoccio\" disabled>\n";
      echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
      echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
      echo "\t<input type=\"submit\" tabindex=\"999\" value=\"" . $partner['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
    } elseif ($val == $anonimo) { // e' un cliente anonimo
      echo "\t<input type=\"submit\" value=\"→ \" name=\"fantoccio\" disabled>\n";
      echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
      echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"\">\n";
      echo "\t<input type=\"submit\" tabindex=\"999\" value=\"" . $mesg[5] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
    } else {
      if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
        if ($m > 100) { //ho da ricercare nell'ambito di un mastro
          $this->setWhat($m);
        }
        if (is_numeric(trim($strSearch,'%'))) {     //ricerca per partita iva
          $partner = $this->queryAnagra(array("pariva" => " LIKE '" . $strSearch ."'"));
        } elseif (substr($strSearch, 0, 1) == '@') { //ricerca conoscendo il codice cliente
          $temp_agrafica = new Anagrafica();
          $codicetemp = intval($m * 1000000 + substr($strSearch, 1));
          $last = $temp_agrafica->getPartner($codicetemp);
          $codicecer = $last['id_anagra'];
          $partner = $this->queryAnagra(array("a.id" => "=" . intval($codicecer)));
          //echo "---".$m."-".$codicetemp."-".$codicecer; //debug
        } elseif (substr($strSearch, 0, 1) == '#') { //ricerca conoscendo il codice univoco ufficio
          $partner = $this->queryAnagra(array("a.fe_cod_univoco" => " LIKE '%" . addslashes(substr($strSearch, 1)) . "%'"));
        } elseif ( preg_match('/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]$/i', $strSearch)) {   //ricerca per codice fiscale
          $partner = $this->queryAnagra(array("a.codfis" => " LIKE '%" . addslashes($strSearch) . "%'"));
        } else {                                     //ricerca per ragione sociale
          $partner = $this->queryAnagra(array("a.ragso1" => " LIKE '" . addslashes($strSearch) . "%'"));
        }
        if (count($partner) > 1 || $_POST['hidden_req']=='change' ) {
          echo "\t<select name=\"$name\" $tab1 class=\"FacetSelect\" onchange=\"if(typeof(this.form.hidden_req)!=='undefined'){this.form.hidden_req.value='$name';} this.form.submit();\">\n";
          echo "<option value=\"0\"> ---------- </option>";
          if ($anonimo > 100) {
              echo "<option value=\"$anonimo\">" . $mesg[5] . "</option>";
          }
          preg_match("/^id_([0-9]+)$/", $val, $match);
          foreach ($partner as $r) {
              if (isset($r['codpart']) && $r['codpart'] > 0) {
                  $r['codice'] = $r['codpart'];
              }
              $style = '';
              $selected = '';
              $disabled = '';
              if ($r['status'] == 'HIDDEN') {
                  $disabled = ' disabled ';
              }
              if (isset($match[1]) && $match[1] == $r['id']) {
                  $selected = "selected";
              } elseif ($r['codice'] == $val && $val > 0) {
                  $selected = "selected";
              }
              if ($m < 0) { // vado cercando tutti i partner del piano dei conti
                  if ($r["codice"] < 1) {  // disabilito le anagrafiche presenti solo in altre aziende
                      $disabled = ' disabled ';
                      $style = 'style="background:#FF6666";';
                  }
              } elseif ($r["codice"] < 1) {
                  $style = 'style="background:#FF6666";';
                  $r['codice'] = 'id_' . $r['id'];
              } elseif (substr($r["codice"], 0, 3) != $m) {// non appartiene al mastro passato in $m
                  if ($soloMastroSelezionato) { // voglio solo le anagrafi di questo mastro
                      continue;   // salto questa riga
                  }
                  $style = 'style="background:#FFBBBB";';
                  $r['codice'] = 'id_' . $r['id'];
              }
              echo "\t\t <option $style value=\"" . $r['codice'] . "\" $selected $disabled>" . $r["ragsoc"] . " " . $r["citta"] . "</option>\n";
          }
          echo "\t </select>\n";
        } elseif(count($partner) == 1){
          $style='';
          if ($m < 0) { // vado cercando tutti i partner del piano dei conti
            if ($partner[0]["codpart"] < 1) {  // disabilito le anagrafiche presenti solo in altre aziende
            }
            $partner[0]['codpart']=$partner[0]['codice'];
            echo "\t<input type=\"submit\" id=\"onlyone_submit\" value=\"→ \" onclick=\"if(typeof(this.form.hidden_req)!=='undefined'){this.form.hidden_req.value='$name';} this.form.submit();\">\n";
          } elseif ($partner[0]["codpart"] < 1) {
            $partner[0]['codpart'] = 'id_' . $partner[0]['id'];
            $style = 'style="background:#FF6666";';
          } elseif (substr($partner[0]["codpart"], 0, 3) != $m) {// non appartiene al mastro passato in $m
            $partner[0]['codpart'] = 'id_' . $partner[0]['id'];
            $style = 'style="background:#FF6666";';
            echo "\t<input type=\"submit\" id=\"onlyone_submit\" value=\"→ \" onclick=\"if(typeof(this.form.hidden_req)!=='undefined'){this.form.hidden_req.value='$name';} this.form.submit();\">\n";
          } else {
            echo "\t<input type=\"submit\" id=\"onlyone_submit\" value=\"→ \" onclick=\"if(typeof(this.form.hidden_req)!=='undefined'){this.form.hidden_req.value='$name';} this.form.submit();\">\n";
          }
          $val=$partner[0]['codpart'];
          echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
          echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner[0]['ragsoc'], 0, 8) . "\">\n";
          echo "\t<input type=\"submit\" tabindex=\"999\" value=\"" . $partner[0]['ragsoc'] . "\" name=\"change\" ".$style." onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
        } else {
          $msg = $mesg[0];
          echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
        }

      } else {
        $msg = $mesg[1];
        echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
      }
      if( !strstr($val,'id') && $val<=100000000){
        echo "\t<input type=\"text\" $tab2 id=\"search_$name\" name=\"search[$name]\" value=\"" . $strSearch . "\" maxlength=\"16\" size=\"10\" class=\"FacetInput\">\n";
      }
      if (isset($msg)) {
          echo "<input type=\"text\" style=\"color: red; font-weight: bold;\" size=\"" . strlen($msg) . "\" disabled value=\"$msg\">\n";
      }
      if( !strstr($val,'id') && $val<=100000000){
        echo '<button type="submit" class="btn btn-default btn-sm" name="search_str" ' . $tab3 . '><i class="glyphicon glyphicon-search"> </i></button>';
      }
    }
  }

    function selectAnagra($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg='', $tab = false) {
        global $gTables;
        $tab1 = '';
        $tab2 = '';
        $tab3 = '';
        if ($tab) {
            $tab1 = ' tabindex="' . $tab . '"';
            $tab2 = ' tabindex="' . ($tab + 1) . '"';
            $tab3 = ' tabindex="' . ($tab + 2) . '"';
        }
        if ($val > 1) { //vengo da una modifica della precedente select case quindi non serve la ricerca
            $partner = gaz_dbi_get_row($gTables['anagra'], "id", $val);
            echo "\t<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$val\">\n";
            echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"" . substr($partner['ragso1'], 0, 8) . "\">\n";
            echo "\t<input type=\"submit\" tabindex=\"999\" value=\"" . $partner['ragso1'] . "\" name=\"change\" onclick=\"this.form.$name.value='0'; this.form.hidden_req.value='change';\" title=\"$mesg[2]\">\n";
        } else {
            if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
                if (is_numeric($strSearch)) {                       //ricerca per partita iva
                    $partner = $this->queryAnagra(array("pariva" => "=" . intval($strSearch)));
                } elseif (preg_match('/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]$/i', $strSearch)) {   //ricerca per codice fiscale
                    $partner = $this->queryAnagra(array("a.codfis" => " LIKE '%" . addslashes($strSearch) . "%'"));
                } else {                                            //ricerca per ragione sociale
                    $partner = $this->queryAnagra(array("a.ragso1" => " LIKE '" . addslashes($strSearch) . "%'"));
                }
                if (count($partner) > 0) {
                    echo "\t<select name=\"$name\" $tab1 class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\">\n";
                    echo "<option value=\"0\"> ---------- </option>";
                    foreach ($partner as $r) {
                        $style = '';
                        $selected = '';
                        if ($r['codice'] == $val && $val > 0) {
                            $selected = "selected";
                        }
                        echo "\t\t <option $style value=\"" . $r['id'] . "\" $selected >" . $r["ragsoc"] . " " . $r["citta"] . "</option>\n";
                    }
                    echo "\t </select>\n";
                } else {
                    $msg = $mesg[0];
                    echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
                }
            } else {
                $msg = $mesg[1];
                echo '<input type="hidden" name="'.$name.'"  id="'.$name.'" value="'.$val.'">';
            }
            echo '<input type="text" '.$tab2.'  name="search['.$name.']" id="search_'.$name.'" value="'. $strSearch . '" maxlength=16 size=10 class="FacetInput">';
            if (isset($msg)) {
                echo "<input type=\"text\" style=\"color: red; font-weight: bold;\" size=\"" . strlen($msg) . "\" disabled value=\"$msg\">";
            }
            echo '<button type="submit" class="btn btn-default btn-sm" name="search_str" ' . $tab3 . '><i class="glyphicon glyphicon-search"></i></button>';
        }
    }

    function selectDestin($codclfoco,$names,$vals,$strSearch='') {
      $ph=($vals['id_des_same_company'] > 1 || $vals['id_des'] > 1 ) ? 'vedi sotto' : 'idem';
      echo '<textarea rows=1 cols=30 name="destin" placeholder="'.$ph.'">'.$vals['destin'].'</textarea><br>';
      if ($codclfoco > 100000000) { // la destinazione solo se ho il cliente/fornitore
        $clfoco = gaz_dbi_get_row($this->gTables['clfoco'],"codice",$codclfoco);
        $anagra = gaz_dbi_get_row($this->gTables['anagra'],"id",$clfoco['id_anagra']);
        $rs_destina = gaz_dbi_dyn_query('*', $this->gTables['destina'],'id_anagra = '.$clfoco['id_anagra']);
        $first=1;
        while ($r = gaz_dbi_fetch_array($rs_destina)) {
          if($first==1){
            echo '<select name="'.$names['id_des_same_company'].'" onchange="this.form.hidden_req.value=\''.$names['id_des_same_company'].'\'; this.form.submit();">';
            echo '<option value="0"> ---------- </option>';
          }
          $selected = '';
          if ($r['codice'] == $vals['id_des_same_company'] && $vals['id_des_same_company'] > 0) {
            $selected = "selected";
          }
          echo '<option value="'.$r['codice'].'" '.$selected.' >'.$r["unita_locale1"].' '.$r["citspe"].'('.$r["prospe"].')</option>';
          $first=false;
        }
        if ($first===false) echo '</select><br/>';
        if ($vals['id_des'] > 1) { //vengo da una modifica della precedente select case quindi non serve la ricerca
          $destpartner = gaz_dbi_get_row($this->gTables['anagra'], "id", $vals['id_des']);
          if ($first==1) {
            echo '<input type="hidden" id="'.$names['id_des_same_company'].'" name="'.$names['id_des_same_company'].'" value="'.$vals['id_des_same_company'].'">';
          }
          echo '<input type="hidden" id="'.$names['id_des'].'" name="'.$names['id_des'].'" value="'.$vals['id_des'].'">';
          echo '<input type="hidden" name="search['.$names['id_des'].']" value="'. substr($destpartner['ragso1'],0,15) .'">';
          echo '<input type="submit" class="btn btn-success btn-xs" value="'.$destpartner['ragso1'].'" name="change_'.$names['id_des'].'" onclick="this.form.'.$names['id_des'].'.value=\'0\'; this.form.hidden_req.value=\'change_'.$names['id_des'].'\';" title="Cambia destinazione">';
        } else if ($vals['id_des_same_company'] >= 1 ) { // ho scelto una destinazione interna, non visualizzo l'esterna
          echo '<input type="hidden" id="'.$names['id_des'].'" name="'.$names['id_des'].'" value="'.$vals['id_des'].'">';
          echo '<input type="hidden" name="search['.$names['id_des'].']" value="'.$strSearch.'">';
        } else {
          if (strlen($strSearch) >= 2) { //sto ricercando un nuovo destinatario
            if (is_numeric($strSearch)) { //ricerca per partita iva
              $destpartner = $this->queryAnagra(array("pariva" => "=" . intval($strSearch)));
            } elseif (preg_match('/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]$/i', $strSearch)) {   //ricerca per codice fiscale
              $destpartner = $this->queryAnagra(array("a.codfis" => " LIKE '%" . addslashes($strSearch) . "%'"));
            } else {                                            //ricerca per ragione sociale
              $destpartner = $this->queryAnagra(array("a.ragso1" => " LIKE '" . addslashes($strSearch) . "%'"));
            }
            if (count($destpartner) > 0) {
              echo '<select name="'.$names['id_des'].'" onchange="this.form.hidden_req.value=\''.$names['id_des'].'\'; this.form.submit();">';
              echo '<option value="0"> ---------- </option>';
              foreach ($destpartner as $r) {
                $selected = '';
                if ($r['codice'] == $vals['id_des'] && $vals['id_des'] > 0) {
                  $selected = "selected";
                }
                echo '<option value="'.$r['id'].'" '.$selected.' >'.$r["ragsoc"].' '.$r["citta"].'</option>';
              }
              echo '</select>';
            } else {
              $msg = '$mesg[0]';
              echo '<input type="hidden" name="'.$names['id_des'].'" value="'.$vals['id_des'].'">';
            }
          } else {
            $msg = 'min. 2 caratteri';
            echo '<input type="hidden" name="'.$names['id_des'].'"  id="'.$names['id_des'].'" value="'.$vals['id_des'].'">';
          }
          echo '<input type="text" name="search['.$names['id_des'].']" id="search_'.$names['id_des'].'" value="'. $strSearch . '" maxlength=16>';
          if (isset($msg)) {
              echo ' <span style="color:red;"> '.$msg.' </span> ';
          }
          echo '<button type="submit" class="btn btn-default btn-xs" name="search_'.$names['id_des'].'"><i class="glyphicon glyphicon-search"></i></button>';
          if ($first==1) {
            echo '<input type="hidden" id="'.$names['id_des_same_company'].'" name="'.$names['id_des_same_company'].'" value="'.$vals['id_des_same_company'].'">';
          }
        }
      } else {
        echo '<input type="hidden" id="'.$names['id_des_same_company'].'" name="'.$names['id_des_same_company'].'" value="0">';
        echo '<input type="hidden" id="'.$names['id_des'].'" name="'.$names['id_des'].'" value="'.$vals['id_des'].'">';
        echo '<input type="hidden" name="search['.$names['id_des'].']" value="search['.$names['id_des'].']">';
      }
    }

    function queryClfoco($codiceAnagrafe, $mastro) {
        $retVal = 0;
        $codiceAnagrafe = addslashes($codiceAnagrafe);
//      $where = "id_anagra='$codiceAnagrafe' and codice like '$mastro%'";
        $where = "codice='$codiceAnagrafe'";
        $rs = gaz_dbi_dyn_query('codice', $this->gTables['clfoco'] . ' AS a', $where);
        if ($r = gaz_dbi_fetch_array($rs)) {
            $retVal = $r['codice'];
        }
        return $retVal;
    }

}

// Antonio Germani - classe per la generazione di select box ordini
class selectorder extends SelectBox
{
  public $selected;
  public $name;
    function output($cerca, $field = 'C', $class = 'FacetSelect', $sele = 1)
    {
        global $gTables, $script_transl, $script_transl;
        $msg = "";
        $tabula = ' tabindex="4" ';
        $opera = "%'";
        if (strlen($cerca) >= 1) {
            $opera = "'"; ////
            $field_sql = 'id_tes';
            if (substr($cerca, 0, 1) == "@") {
                $cerca = substr($cerca, 1);
            }
            // uso la variabile $field per aggiungere al $where un filtro sui articoli composti
            if ( $field!='C' ) {
                $opera .= $field;
            }

            $result = gaz_dbi_dyn_query("numdoc,id_tes,datemi,descri", $gTables['tesbro']. " LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['tesbro'] . ".clfoco = " . $gTables['clfoco'] . ".codice", $field_sql . " LIKE '" . addslashes($cerca) . $opera, "id_tes DESC");
            // nella tabella tesbro seleziona id_tes, numdoc e datemi dove numdoc è come $cerca. Ordina per numdoc
            $numclfoco = gaz_dbi_num_rows($result);
            if ($numclfoco > 0) {
				if ($sele) {
					$tabula = "";
					echo ' <select tabindex="4" name="' . $this->name . '" class="' . $class . '">';
					while ($z_row = gaz_dbi_fetch_array($result)) {
						$selected = "";
						if ($z_row["id_tes"] == $this->selected) {
							$selected = ' selected=""';
						}
						echo ' <option value="' . $z_row["id_tes"] . '"' . $selected . '>' . $z_row["numdoc"] . ' del ' . gaz_format_date( $z_row["datemi"]).' '.$z_row["descri"] . '</option>';
					}
					echo ' </select>';
				}
			} else {
                $msg = $script_transl['notfound'] . '!';
                echo '<input type="hidden" name="' . $this->name . '" value="" />';
            }
        } else {
            $msg = $script_transl['minins'] . ' 2 ' . $script_transl['charat'] . '!';
            echo '<input type="hidden" name="' . $this->name . '" value="" />';
        }

        echo '&nbsp;<input type="text" class="' . $class . '" name="coseor" id="search_order" value="' . $cerca . '" ' . $tabula . ' maxlength="16" />';
        if ($msg != "") {
            echo '&nbsp;<span class="bg-danger text-danger"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>' . $msg . '</span>';
        }
    }

}

class selectPosition extends SelectBox
{
  public $selected;
  public $name;

  function output($cerca, $field = 'C', $class = 'FacetSelect', $sele = 1, $name='cosepos') {
      global $gTables, $script_transl, $script_transl;
      $msg = "";
      $tabula = ' tabindex="4" ';
      $opera = "%'";
      if (strlen($cerca) >= 1) {
        $opera = "'"; ////
        $field_sql = 'id_position';
        if (substr($cerca, 0, 1) == "@") {
            $cerca = substr($cerca, 1);
        }
        // uso la variabile $field per aggiungere al $where un filtro sui articoli composti
        if ( $field!='C' ) {
            $opera .= $field;
        }
        $result = gaz_dbi_dyn_query("id_position,position,name,descri," . $gTables['artico_position'] . ".id_warehouse", $gTables['artico_position']. " LEFT JOIN " . $gTables['warehouse'] . " ON " . $gTables['artico_position'] . ".id_warehouse = " . $gTables['warehouse'] . ".id LEFT JOIN " . $gTables['shelves'] . " ON " . $gTables['artico_position'] . ".id_shelf = " . $gTables['shelves'] . ".id_shelf", $field_sql . " = " . $cerca, $gTables['artico_position'] . ".id_warehouse , ".$gTables['artico_position'] . ".id_shelf, id_position");
        $numresult = gaz_dbi_num_rows($result);
        if ($numresult > 0) {
          if ($sele) {
            $tabula = "";
            echo ' <select tabindex="4" name="' . $this->name . '" class="' . $class . '">';
            while ($posrow = gaz_dbi_fetch_array($result)) {
              $selected = "";
              if ($posrow["id_position"] == $this->selected) {
                $selected = ' selected=""';
              }
              echo ' <option value="' . $posrow["id_position"] . '"' . $selected . ' class="bg-info">' . $posrow["position"] . ' scaf:' .$posrow["descri"].' mag:'.($posrow["id_warehouse"]>0?$posrow["name"]:'SEDE') . '</option>';
            }
            echo ' </select>';
          }
        } else {
          $msg = $script_transl['notfound'] . '!';
          echo '<input type="hidden" name="' . $this->name . '" value="" />';
        }
      } else {
        $msg = $script_transl['minins'] . ' 2 ' . $script_transl['charat'] . '!';
        echo '<input type="hidden" name="' . $this->name . '" value="" />';
      }
      echo '&nbsp;<input type="text" class="' . $class . '" name="'.$name.'" id="search_position" value="' . $cerca . '" ' . $tabula . ' maxlength="32" placeholder="'.$msg.'" />';
    }

}

class selectproduction extends SelectBox {
  public $selected;
  public $name;
     function output($cerca, $without_closed = true, $class = 'FacetSelect',$sele=1, $msg = "") {
        global $gTables, $script_transl;
        $opera = "%'";
        if (strlen($cerca) >= 1) {
            $opera = "'"; ////
            $field_sql = 'description';
            if ($without_closed){
                $opera .= " AND stato_lavorazione < 9";
            }
            $result = gaz_dbi_dyn_query("id,description", $gTables['orderman'], $field_sql . " LIKE '" . addslashes($cerca) . $opera, "id DESC");
            $numclfoco = gaz_dbi_num_rows($result);
            if ($numclfoco > 0) {
				echo ' <select name="' . $this->name . '" class="' . $class . '">';
				while ($z_row = gaz_dbi_fetch_array($result)) {
					$selected = "";
					if ($z_row["id"] == $this->selected) {
						$selected = ' selected ';
					}
					echo ' <option value="' . $z_row["id"] . '"' . $selected .'>' . $z_row["id"] .' - '.$z_row["description"] . '</option>';
				}
                echo "<option value=\"0\"> ---------- </option>\n";
				echo ' </select>';
			} else {
                $msg = $script_transl['notfound'] . '!';
                echo '<input type="hidden" name="' . $this->name . '"  id="'. $this->name.'" />';
            }
        } else {
            $msg = $script_transl['minins'] . ' 2 ' . $script_transl['charat'] . '!';
            echo '<input type="hidden" name="' . $this->name . '"  id="'. $this->name.'" />';
        }
        echo '&nbsp;<input type="text" class="' . $class . '" name="coseprod" placeholder="'.$msg.'" id="search_production" value="' . $cerca . '" maxlength="16" />';
    }

}

class selectcontract extends SelectBox {
  public $selected;
  public $name;
  function output($cerca,$clfoco,$class = 'FacetSelect') {
    global $gTables, $script_transl;
    $msg = "";
    $opera = "%'";
    if (strlen($cerca) >= 1) {
      $resp = gaz_dbi_dyn_query("id_contract,conclusion_date,doc_number", $gTables['contract'], "id_customer=". $clfoco." AND (conclusion_date LIKE '" . addslashes($cerca) . "')", "id_contract DESC");
      $numclfoco = gaz_dbi_num_rows($resp);
      $resc = gaz_dbi_dyn_query("id_tes AS id_contract,datemi AS conclusion_date,numdoc AS doc_number", $gTables['tesbro'], "tipdoc='CON' AND clfoco=". $clfoco." AND datemi LIKE '" . addslashes($cerca) ."'", "id_tes DESC");
      $numclfoco += gaz_dbi_num_rows($resc);
      if ($numclfoco > 0) {
				echo ' <select name="' . $this->name . '" class="' . $class . '" >';
				while ($r = gaz_dbi_fetch_array($resp)) {
					$selected = "";
					if ($r["id_contract"] == $this->selected) {
						$selected = ' selected ';
					}
					echo ' <option value="' . $r["id_contract"] . '"' . $selected .'>N.' . $r["doc_number"] .' del '.gaz_format_date($r["conclusion_date"]). '</option>';
				}
				while ($r = gaz_dbi_fetch_array($resc)) {
					$selected = "";
					if ($r["id_contract"] == $this->selected) {
						$selected = ' selected ';
					}
					echo ' <option value="' . $r["id_contract"] . '"' . $selected .'>N.' . $r["doc_number"] .' del '.gaz_format_date($r["conclusion_date"]). '</option>';
				}
        echo "<option value=\"0\"> ---------- </option>\n";
				echo ' </select>';
			} else {
        $msg = $script_transl['notfound'] . '!';
        echo '<input type="hidden" name="' . $this->name . '" id="'. $this->name.'" />';
      }
    } else {
      $msg = $script_transl['minins'] . ' 2 ' . $script_transl['charat'] . '!';
      echo '<input type="hidden" name="' . $this->name . '" id="'. $this->name.'" />';
    }
    echo '&nbsp;<input type="text" class="' . $class . '" name="cosecont" placeholder="'.$msg.'" id="search_contract" value="' . $cerca . '" maxlength="16" />';
  }
}

// classe per la generazione di select box degli articoli
class selectartico extends SelectBox {
  public $selected;
  public $name;

    function output($cerca, $field = 'C', $class = 'FacetSelect',$sele=1) {
        global $gTables, $script_transl, $script_transl;
        $msg = "";
        $tabula = ' tabindex="4" ';
        $opera = "%'";
        if (strlen($cerca) >= 1) {
            $opera = "'"; ////
            $field_sql = 'codice';
            if (substr($cerca, 0, 1) == "@") {
                $cerca = substr($cerca, 1);
            }
            // uso la variabile $field per aggiungere al $where un filtro sui articoli composti
            if ( $field!='C' ) {
                $opera .= $field;
            }

            $result = gaz_dbi_dyn_query("codice,descri,barcode", $gTables['artico'], $field_sql . " LIKE '" . addslashes($cerca) . $opera, "descri DESC");
            $numclfoco = gaz_dbi_num_rows($result);
            if ($numclfoco > 0) {
				if ($sele) {
					$tabula = "";
					echo ' <select tabindex="4" name="' . $this->name . '" class="' . $class . '">';
					while ($a_row = gaz_dbi_fetch_array($result)) {
						$selected = "";
						if ($a_row["codice"] == $this->selected) {
							$selected = ' selected=""';
						}
						echo ' <option value="' . $a_row["codice"] . '"' . $selected . '>' . $a_row["codice"] . '-' . $a_row["descri"] . '</option>';
					}
					echo ' </select>';
				}
			} else {
                $msg = $script_transl['notfound'] . '!';
                echo '<input type="hidden" name="' . $this->name . '" value="" />';
            }
        } else {
            $msg = $script_transl['minins'] . ' 2 ' . $script_transl['charat'] . '!';
            echo '<input type="hidden" name="' . $this->name . '" value="" />';
        }
        echo '&nbsp;<input type="text" class="' . $class . '" name="cosear" id="search_cosear" placeholder="'.$msg.'" value="' . $cerca . '" ' . $tabula . ' maxlength="32" />';
    }

}

// classe per la generazione di select box dei conti ricavi di vendita-costi d'acquisto
class selectconven extends SelectBox {

    function output($mastri, $class = false, $empty = false, $refresh = '') {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['clfoco'] . "` WHERE codice LIKE '" . $mastri . "%' AND codice NOT LIKE '%000000' ORDER BY `codice` ASC";
        SelectBox::_output($query, 'codice', $empty, '-', 'descri', 'codice', $refresh, $class);
    }

}

// classe per la generazione di select box dei conti ricavi di vendita-costi d'acquisto
class selectbanacc extends SelectBox {

    function output($mastri) {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['clfoco'] . "` WHERE codice LIKE '" . $mastri . "%' AND codice > '" . $mastri . "000000' ORDER BY `codice` ASC";
        SelectBox::_output($query, 'codice', True, '-', 'ragso1');
    }

}

// classe per la generazione di select box banche d'appoggio
class selectbanapp extends SelectBox {

    function output($refresh = '', $class = false, $empty = true) {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['banapp'] . '` ORDER BY `codabi`,`codcab`,`descri` ASC';
        SelectBox::_output($query, 'descri', $empty, '', 'locali','codice', $refresh, $class);
    }

}

// classe per la generazione di select box dei pagamenti
class selectpagame extends SelectBox {

    function output($refresh = '', $class = false, $empty = true) {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['pagame'] . '` ORDER BY `descri`, `codice`';
        SelectBox::_output($query, 'descri', $empty, '', '', 'codice', $refresh, $class);
    }

}

// classe per la generazione di select box delle aliquote iva
class selectaliiva extends SelectBox {

    function output($class = false, $tipiva = false) {
        global $gTables;
        $where = '';
        if ($tipiva) {
            $where = " WHERE tipiva='" . $tipiva . "'";
        }
        $query = 'SELECT * FROM `' . $gTables['aliiva'] . '`' . $where . ' ORDER BY `codice`';
        SelectBox::_output($query, 'descri', True, '', '', 'codice', '', $class);
    }

}

// classe per la generazione di select box delle categorie merceologiche
class selectcatmer extends SelectBox {

    function output($refresh = '') {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['catmer'] . '` ORDER BY `codice`';
        SelectBox::_output($query, 'codice', True, '-', 'descri', 'codice', $refresh);
    }

}

// classe per la generazione di select box porto resa
class selectportos extends SelectBox {

    function output() {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['portos'] . '` ORDER BY `codice`';
        SelectBox::_output($query, 'codice', True, '-', 'descri');
    }

}

// classe per la generazione di select box delle spedizioni
class selectspediz extends SelectBox {

    function output() {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['spediz'] . '` ORDER BY `codice`';
        SelectBox::_output($query, 'codice', True, '-', 'descri');
    }

}

// classe per la generazione di select box imballi
class selectimball extends SelectBox {

    function output() {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables['imball'] . '` ORDER BY `codice`';
        SelectBox::_output($query, 'codice', True, '-', 'descri');
    }

}

// classe per la generazione di select box imballi, spedizioni, porto resa
class SelectValue extends SelectBox {

    function output($table, $fieldName) {
        global $gTables;
        $query = 'SELECT * FROM `' . $gTables[$table] . '` ORDER BY `codice`';
        $index1 = 'codice';
        $empty = True;
        $bridge = '&nbsp; ';
        $index2 = 'descri';
        echo "\t <select name=\"$this->name\" class=\"FacetSelect\" onChange=\"pulldown_menu('" . $this->name . "','" . $fieldName . "')\" style=\"width: 20px\">\n";
        if ($empty) {
            echo "\t\t <option value=\"\"></option>\n";
        }
        $result = gaz_dbi_query($query);
        while ($a_row = gaz_dbi_fetch_array($result)) {
            if ($index2 == '') {
                echo "\t\t <option value=\"\">" . $a_row[$index1] . "</option>\n";
            } else {
                echo "\t\t <option value=\"" . $a_row[$index2] . "\">&nbsp;" . $a_row[$index1] . $bridge . $a_row[$index2] . "</option>\n";
            }
        }
        echo "\t </select>\n";
    }

}

// classe per la generazione di select box vettori
class selectvettor extends SelectBox {
  public $selected;
    function output() {
        global $gTables;
        echo "\t <select name=\"$this->name\" class=\"FacetSelect\">\n";
        echo "\t\t <option value=\"\"></option>\n";
        $result = gaz_dbi_dyn_query("*", $gTables['vettor'], 1, "codice");
        while ($a_row = gaz_dbi_fetch_array($result)) {
            $selected = "";
            if ($a_row["codice"] == $this->selected) {
                $selected = "selected";
            }
            echo "\t\t <option value=\"" . $a_row["codice"] . "\" $selected >" . substr($a_row["ragione_sociale"], 0, 22) . "</option>\n";
        }
        echo "\t </select>\n";
    }

}

// classe per l'invio di documenti allegati ad una e-mail
class GAzieMail {

    function sendMail($admin_data, $user, $content, $receiver, $mail_message = '', $template=true) {
      // su $admin_data['other_email'] ci va un eventuale indirizzo mail diverso da quello in anagrafica
      global $gTables, $debug_active;

      // Antonio Germani prendo i dati IMAP utente, se ci sono
      $custom_field = gaz_dbi_get_row($gTables['anagra'], 'id', $user['id_anagra'])['custom_field'];
      $imap_usr='';
      if (isset($custom_field) && $data = json_decode($custom_field,true)){// se c'è un json e c'è una mail aziendale utente
        if (isset($data['config'][$admin_data['codice']]) && is_array($data['config'])){ // se c'è il modulo "config" e c'è l'azienda attuale posso procedere
          list($encrypted_data, $iv) = explode('::', base64_decode($data['config'][$admin_data['codice']]['imap_pwr']), 2);
          $imap_pwr=openssl_decrypt($encrypted_data, 'aes-128-cbc', $_SESSION['aes_key'], 0, $iv);
          $imap_usr=$data['config'][$admin_data['codice']]['imap_usr'];
          $imap_sent_folder=$data['config'][$admin_data['codice']]['imap_sent_folder'];
          $imap_server = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_server')['val'];
          $imap_port = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_port')['val'];
          $imap_secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'imap_secure')['val'];
        }
      }

      require_once "../../library/phpmailer/class.phpmailer.php";
      require_once "../../library/phpmailer/class.smtp.php";
      if (isset ($receiver['mod_fae']) && strpos($receiver['mod_fae'], 'pec')===0){// se c'è il modulo per invio fae che inizia il suo nome con 'pec' definisco il server smtp con la pec
        $config_port = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_port');
        $config_secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_secure');
        $config_user = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_usr');
        $rspsw=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'pec_smtp_psw'");
        $rpsw=gaz_dbi_fetch_row($rspsw);
        $config_pass = $rpsw?$rpsw[0]:'';
        //$config_pass = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_psw'); // GAzie <=9.03
        $config_host = gaz_dbi_get_row($gTables['company_config'], 'var', 'pec_smtp_server');
        $admin_data['other_email'] = $admin_data['pec'];
        $mailto = $receiver['e_mail']; //recipient-DESTINATARIO
      } else {// altrimenti prendo la configurazione smtp semplice
        $config_port = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_port');
        $config_secure = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_secure');
        $config_user = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_user');
        $rspsw=gaz_dbi_query("SELECT AES_DECRYPT(FROM_BASE64(val),'".$_SESSION['aes_key']."') FROM ".$gTables['company_config']." WHERE var = 'smtp_password'");
        $rpsw=gaz_dbi_fetch_row($rspsw);
        $config_pass = $rpsw?$rpsw[0]:'';
        //$config_pass = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_password');
        $config_host = gaz_dbi_get_row($gTables['company_config'], 'var', 'smtp_server');
        $mailto = $receiver['e_mail']; //recipient-DESTINATARIO
      }
      // definisco il server SMTP e il mittente
      $config_mailer = gaz_dbi_get_row($gTables['company_config'], 'var', 'mailer');
      $config_notif = gaz_dbi_get_row($gTables['company_config'], 'var', 'return_notification');
      $config_replyTo = gaz_dbi_get_row($gTables['company_config'], 'var', 'reply_to');
      // attingo il contenuto del corpo della email dall'apposito campo della tabella configurazione utente
      $user_text = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'body_send_doc_email', "AND adminid = '{$user['user_name']}'");
      // attingo indirizzo email specifico dalla tabella configurazione utente
      $az_email = gaz_dbi_get_row($gTables['admin_config'], 'var_name', 'az_email', "AND adminid = '". $user['user_name'] ."' AND company_id = ".$admin_data['codice']);
      $company_text = gaz_dbi_get_row($gTables['company_config'], 'var', 'company_email_text');
      $admin_data['web_url'] = trim($admin_data['web_url']);
      if (!isset($mailto) && !empty($admin_data['other_email']) && strlen($admin_data['other_email'])>=10){
        $mailto = $admin_data['other_email']; //recipient
      }
      $subject = "Invio " . str_lreplace('.pdf', '', (isset($admin_data['doc_name']))?$admin_data['doc_name']:'')." - ".$admin_data['ragso1'] . " " . $admin_data['ragso2'];//subject
      // aggiungo al corpo  dell'email
      $body_text = "<div><b>" . ((isset($admin_data['cliente1']))?$admin_data['cliente1']:'') . "</b></div>\n";
      $body_text .= "<div>" . ((isset($admin_data['doc_name']))?$admin_data['doc_name']:''). "</div>\n";
      $body_text .= "<div>" . ( !empty($mail_message) ? $mail_message : $company_text['val']) . "</div>\n";
      $body_text .= ( empty($admin_data['web_url']) ? "" : "<h4><span style=\"color: #000000;\">Web: <a href=\"" . $admin_data['web_url'] . "\">" . $admin_data['web_url'] . "</a></span></h4>" );
      $body_text .= "<h3><span style=\"color: #000000; background-color: #" . $admin_data['colore'] . ";\">" . $admin_data['ragso1'] . " " . $admin_data['ragso2'] . "</span></h3>";
      $body_text .= "<address><div style=\"color: #000000;\">" . $user['user_firstname'] . " " . $user['user_lastname'] . "</div>\n";
      $body_text .= "<div>" . $user_text['var_value'] . "</div></address>\n";
      $body_text .= "<hr /><small>" . EMAIL_FOOTER . " " . GAZIE_VERSION . "</small>\n";
      //
      // Inizializzo PHPMailer
      //
      $mail = new PHPMailer();
      $mail->Host = $config_host['val'];
      $mail->IsHTML();                                // Modalita' HTML
      $mail->CharSet = 'UTF-8';
      // Imposto il server SMTP
      if (!empty($config_port['val'])) {
          $mail->Port = $config_port['val'];             // Imposto la porta del servizio SMTP
      }
      switch ($config_mailer['val']) {
        case "smtp":
          // Invio tramite protocollo SMTP
          $mail->SMTPDebug = FALSE;                           // Attivo il debug
          $mail->IsSMTP();                                // Modalita' SMTP
          if (!empty($config_secure['val'])) {
            $mail->SMTPSecure = $config_secure['val']; // Invio tramite protocollo criptato
          } else {
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
          }
          $mail->SMTPAuth = (!empty($config_user['val']) && $config_mailer['val'] == 'smtp' ? TRUE : FALSE );
          if ($mail->SMTPAuth) {
            $mail->Username = $config_user['val'];     // Imposto username per autenticazione SMTP
            $mail->Password = $config_pass;     // Imposto password per autenticazione SMTP
          }
        break;
      }
      /* Imposto email a cui rispondere (se � stata impostata nella tabella gaz_xxxcompany_config`)
       * deve stare prima di $mail->SetFrom perch� altrimenti aggiunge il from al reply
       */
      if (isset($config_replyTo) && !empty($config_replyTo['val'])) {  // utilizzo l'indirizzo in company_config
          $mittente = $config_replyTo['val'];
      } elseif (strlen($user['user_email'])>=10)  { // utilizzo quella dell'utente
          $mittente = $user['user_email'];
      } else { // utilizzo quella dell'azienda, la stessa che appare sui documenti
          $mittente = $admin_data['e_mail'];
      }
      if (isset ($receiver['mod_fae']) && strpos($receiver['mod_fae'], 'pec')===0){// se c'è il modulo per invio fae che inizia il suo nome con 'pec' cambio il mittente come da impostazioni specifiche
        $mittente=$admin_data['pec'];
        $config_send_fae = gaz_dbi_get_row($gTables['company_config'], 'var', 'pecsdi_sdi_email')['val'];
        if (strlen($config_send_fae)>0){// se c'è un indirizzo per i pacchetti zip in configurazione azienda
          $mail->AddAddress($config_send_fae, $admin_data['ragso1'] . " " . $admin_data['ragso2']);// Aggiungo PEC SDI come Destinatario
        }
      }
      // Imposto eventuale richiesta di notifica
      if ($config_notif['val'] == 'yes') {
          $mail->AddCustomHeader($mail->HeaderLine("Disposition-notification-to", $mittente));
      }
      $mail->setLanguage(strtolower($admin_data['country']));
      // Imposto email del mittente
      $mail->SetFrom($mittente, $admin_data['ragso1'] . " " . $admin_data['ragso2']);
      // Imposto email del destinatario
      $mail->Hostname = $config_host;
      $mail->AddAddress($mailto);//Destinatario
      if (isset($az_email) && strlen($az_email['var_value'])>6 && $imap_usr==''){ // Antonio Germani: se c'è un indirizzo specifico utente/azienda, invio per cc a questo $az_email['var_value']
        $mail->AddCC($az_email['var_value'], $admin_data['ragso1'] . " " . $admin_data['ragso2']); // Aggiungo mittente come destinatario per conoscenza, per avere una copia
      }elseif (strlen($user['user_email'])>=10 && $imap_usr=='') { // altrimenti, quando l'utente che ha inviato la mail ha un suo indirizzo il cc avviene su di lui
        $usermail = $user['user_email'];
        $mail->AddCC($usermail, $admin_data['ragso1'] . " " . $admin_data['ragso2']); // Aggiungo mittente come destinatario per conoscenza, per avere una copia
      }

      // Imposto l'oggetto dell'email
      $mail->Subject = $subject;
      // Imposto il testo HTML dell'email
      $mail->MsgHTML($body_text);
      // Aggiungo la fattura in allegato
      if (!empty($content->urlfile)) { // se devo trasmettere un file allegato passo il suo url
        $mail->AddAttachment( $content->urlfile, $content->name );
      } else { // altrimenti metto il contenuto del pdf che presumibilmente mi arriva da document.php
        $mail->AddStringAttachment($content->string, $content->name, $content->encoding, $content->mimeType);
      }

      if ($template){// Creo una veste grafica
        $admin_aziend = checkAdmin();
        require('../../library/include/header.php');
        $script_transl = HeadMain();
      }

	// Invio...
	if ($debug_active) {
    ?>
        <center>
        <table class="center">
          <tr>
            <td><b>SIMULAZIONE INVIO DEBUG</b></td>
          </tr>
          <tr>
            <td>invio e-mail riuscito... <strong>OK</strong></td>
          </tr>
          <tr>
            <td>mail send has been successful... <strong>OK</strong></td>
          </tr>
                <!--<tr><td><button onclick="history.back()">Torna indietro</button></td></tr>-->
        </table>
        </center>
    <?php
		return true;
	}

	if ( $mail->Send() ) {
    if ($imap_usr!==''){// se ho un utente imap carico la mail nella sua posta inviata
      if($imap = @imap_open("{".$imap_server.":".$imap_port."/".$imap_secure."}".$imap_sent_folder, $imap_usr, $imap_pwr)){
        if ($append=@imap_append($imap, "{".$imap_server."}".$imap_sent_folder, $mail->getSentMIMEMessage(),"\\seen")){
                // inserimento avvenuto
        }else{
          $errors = @imap_errors();
          ?>
          <center>
            <table class="center">
              <tr>
                <td><b>carico mail inviata in 'posta inviata' NON riuscito</b></td>
              </tr>
              <tr>
                <td><?php echo implode ('; ', $errors ); ?></td>
              </tr>
            </table>
          </center>
          <?php
        }
      }else{
        $errors = @imap_errors();
          ?>
          <center>
            <table class="center">
              <tr>
                <td><b>carico mail inviata in 'posta inviata' NON riuscito</b></td>
              </tr>
              <tr>
                <td><?php echo implode ('; ', $errors ); ?></td>
              </tr>
            </table>
          </center>
          <?php
      }
    }
  ?>
		<center>
      <table class="center">
        <tr>
          <td><b>INVIO MAIL</b></td>
        </tr>
        <tr>
          <td>invio e-mail riuscito... <strong>OK</strong></td>
        </tr>
        <tr>
          <td>mail send has been successful... <strong>OK</strong></td>
        </tr>
        <!--<tr><td><button onclick="history.back()">Torna indietro</button></td></tr>-->
      </table>
    </center>
  <?php
    if ($template){
      require('../../library/include/footer.php');
    }
    return true;
  } else {
    ?>
		<center>
      <table class="center">
        <tr>
          <td><b>INVIO MAIL</b></td>
        </tr>
        <tr>
          <td>invio e-mail <strong style="color: #ff0000;">NON riuscito... ERROR!</strong></td>
        </tr>
        <tr>
          <td>mail send has<strong style="color: #ff0000;"> NOT been successful... ERROR!</strong>></td>
        </tr>
        <tr>
          <td>Errore: <?php echo $mail->ErrorInfo; ?></td>
        </tr>
        <!--<tr><td ><button onclick="history.back()">Torna indietro</button></td></tr>-->
      </table>
    </center>
    <?php
      if ($template){
        require('../../library/include/footer.php');
      }
      return false;
  }
  }

}

// classe per la generazione dinamica dei form di amministrazione
class GAzieForm {

    function outputErrors($idxMsg, $transl_errors) {
        /* In questa funzione si deve passare una striga dove il "+"
          serve a separare i diversi indici di errori e il "-" separa il riferimento
          all'errore es. "fa150-3+" dara' un risultato del genere:
          ERRORE! -> introdotto un valore negativo �fa150
         */
        global $script_transl;
        $message = '';
        if (!empty($idxMsg)) {
            $rsmsg = array_slice(explode('+', chop($idxMsg)), 0, -1);
            foreach ($rsmsg as $value) {
                $message .= $script_transl['error'] . "! -> ";
                $rsval = explode('-', chop($value));
                $k = array_pop($rsval);
                $message .= $transl_errors[$k] . ' ';
                foreach ($rsval as $valmsg) {
                    $message .= ' &raquo;' . $valmsg;
                }
                $message .= "<br />";
            }
        }
        return $message;
    }

    function Calendar($name, $day, $month, $year, $class = 'FacetSelect', $refresh = '') {
      global $gazTimeFormatter;
      $gazTimeFormatter->setPattern('MMMM');
      if (!empty($refresh)) {
          $refresh = "onchange=\"this.form.hidden_req.value='$refresh'; this.form.submit();\"";
      }

      echo "\t <select name=\"" . $name . "_D\" id=\"" . $name . "_D\" class=\"$class\" $refresh>\n";
      for ($i = 1; $i <= 31; $i++) {
          $selected = "";
          if ($i == $day) {
              $selected = "selected";
          }
          echo "\t\t <option value=\"$i\" $selected >$i</option>\n";
      }
      echo "\t </select>\n";
      echo "\t <select name=\"" . $name . "_M\" id=\"" . $name . "_M\" class=\"$class\" $refresh>\n";
      for ($i = 1; $i <= 12; $i++) {
          $selected = "";
          if ($i == $month) {
              $selected = "selected";
          }
          $month_name = ucwords($gazTimeFormatter->format(new DateTime("2000-".$i."-01")));
          echo "\t\t <option value=\"$i\"  $selected >$month_name</option>\n";
      }
      echo "\t </select>\n";
      echo "\t <select name=\"" . $name . "_Y\" id=\"" . $name . "_Y\" class=\"$class\" $refresh>\n";
      for ($i = $year - 10; $i <= $year + 10; $i++) {
          $selected = "";
          if ($i == $year) {
              $selected = "selected";
          }
          echo "\t\t <option value=\"$i\"  $selected >$i</option>\n";
      }
      echo "\t </select>\n";
    }

    function CalendarPopup($name, $day, $month, $year, $class = 'FacetSelect', $refresh = '') {
      global $script_transl,$gazTimeFormatter;
      $gazTimeFormatter->setPattern('MMMM');
      if (!empty($refresh)) {
          $refresh = ' onchange="this.form.hidden_req.value=\'' . $refresh . '\'; this.form.submit();"';
      }
      echo '<select name="' . $name . '_D" id="' . $name . '_D" class="' . $class . '"' . $refresh . '>';
      for ($i = 1; $i <= 31; $i++) {
          $selected = "";
          if ($i == $day) {
              $selected = ' selected=""';
          }
          echo '		<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
      }
      echo '	</select>
	  		<select name="' . $name . '_M" id="' . $name . '_M" class="' . $class . '"' . $refresh . '>';
      for ($i = 1; $i <= 12; $i++) {
          $selected = "";
          if ($i == $month) {
              $selected = ' selected=""';
          }
          $month_name = ucwords($gazTimeFormatter->format(new DateTime("2000-".$i."-01")));
          echo '		<option value="' . $i . '"' . $selected . '>' . $month_name . '</option>';
      }
      echo '</select>
	  	<input type="text" name="' . $name . '_Y" id="' . $name . '_Y" value="' . $year . '" class="' . $class . '"  size="4"' . $refresh . ' />
	  	<a class="btn btn-default btn-sm" href="#" onClick="setDate(\'' . $name . '\'); return false;" title="' . $script_transl['changedate'] . '" name="anchor" id="anchor">
			<i class="glyphicon glyphicon-calendar"></i>
			</a>';
    }

    function variousSelect($name, $transl, $sel, $class = 'FacetSelect', $bridge = true, $refresh = '', $maxlenght = false, $style = '',$empty=false, $echo=false) {
        $acc="";
		if (!empty($refresh)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$refresh'; this.form.submit();\"";
        }
        $acc .= "<select name=\"$name\" id=\"$name\" class=\"$class\" $refresh $style>\n";
        if ($empty) {
            $acc .= "\t\t <option value=\"$empty\"></option>\n";
        }

        foreach ($transl as $i => $val) {
            if ($maxlenght) {
                $val = substr($val, 0, $maxlenght);
            }
            $selected = '';
            if ($bridge) {
                $k = $i . ' -';
            } else {
                $k = '';
            }
            if ($sel == $i) {
                $selected = ' selected ';
            }
            $acc .= "<option value=\"$i\"$selected>$k $val</option>\n";
        }
        $acc .= "</select>\n";
		 if ($echo){
			return $acc;
		  } else {
			echo $acc;
		  }
    }

    function selCheckbox($name, $sel, $title = '', $refresh = '', $class = 'FacetSelect') {
        if (!empty($refresh)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$refresh'; this.form.submit();\"";
        }
        $selected = '';
        if ($sel == $name) {
            $selected = ' checked ';
        }
        echo "<input type=\"checkbox\" name=\"$name\" title=\"$title\" value=\"$name\" $selected $refresh>\n";
    }

    function selectNumber($name, $val, $msg = false, $min = 0, $max = 1, $class = 'FacetSelect', $val_hiddenReq = '', $style = '', $echo=false, $exclude="") {
      global $script_transl;
      $acc="";
      $refresh = '';
      if (!empty($val_hiddenReq)) {
        $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
      }
      $acc .="<select  name=\"$name\" id=\"$name\" class=\"$class\" $refresh $style>\n";
      for ($i = $min; $i <= $max; $i++) {
        if ($i==$exclude){
          continue;
        }
        $selected = '';
        $message = $i;
        if ($val == $i) {
            $selected = " selected ";
        }
        if ($msg && $i == 0) {
            $message = $script_transl['no'];
        }
        if ($msg && $i == 1) {
            $message = $script_transl['yes'];
        }
        $acc .= "<option value=\"$i\"$selected>$message</option>\n";
      }
      $acc .= "</select>\n";
      if ($echo){
        return $acc;
      } else {
        echo $acc;
      }
    }

    function selectFromDB($table, $name, $key, $val, $order = false, $empty = false, $bridge = '', $key2 = '', $val_hiddenReq = '', $class = 'FacetSelect', $addOption = null, $style = '', $where = false, $echo=false, $bridge2 = '', $key3 = '', $sort='') {
        global $gTables;
		$acc='';
        $refresh = '';
        if (!$order) {
            $order = $key;
        }
        $query = 'SELECT * FROM `' . $gTables[$table] . '` ';
        if ($where) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY `' . $order . '` '.strtoupper($sort);
        if (!empty($val_hiddenReq)) {
            $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
        }
        $acc .= "\t <select id=\"$name\" name=\"$name\" class=\"$class\" $refresh $style>\n";
        if ($empty) {
            $acc .= "\t\t <option value=\"\"></option>\n";
        }
        $result = gaz_dbi_query($query);
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            if ($r[$key] == $val) {
                $selected = "selected";
            }
            $acc .= "\t\t <option value=\"" . $r[$key] . "\" $selected >";
            if (empty($key2)) {
                $acc .= substr($r[$key], 0, 43);
            } else {
                $acc .= substr($r[$key], 0, 28) . $bridge . substr($r[$key2], 0, 40);
            }
            if (!empty($key3)) {
                $acc .= $bridge2 . substr($r[$key3], 0, 20);

            }
            $acc .= "</option>\n";
        }
        if ($addOption) {
            $acc .= "\t\t <option value=\"" . $addOption['value'] . "\"";
            if ($addOption['value'] == $val) {
                $acc .= " selected ";
            }
            $acc .= ">" . $addOption['descri'] . "</option>\n";
        }
        $acc .= "\t </select>\n";
		if ($echo){
			return $acc;
		} else {
			echo $acc;
		}
    }

    // funzione per la generazione di una select box da file XML
    function selectFromXML($nameFileXML, $name, $key, $val, $empty = false, $val_hiddenReq = '', $class = 'FacetSelect', $addOption = null, $style='', $echo=true) {
      $acc='';
      $refresh = '';
      if (file_exists($nameFileXML)) {
          $xml = simplexml_load_file($nameFileXML);
      } else {
          exit('Failed to open: ' . $nameFileXML);
      }
      if (!empty($val_hiddenReq)) {
          $refresh = "onchange=\"this.form.hidden_req.value='$val_hiddenReq'; this.form.submit();\"";
      }
      $acc .= "\t <select id=\"$name\" name=\"$name\" class=\"$class\" $refresh $style >\n";
      if ($empty) {
          $acc .= "\t\t <option value=\"\"></option>\n";
      }
      foreach ($xml->record as $v) {
          $selected = '';
          if ($v->field[0] == $val) {
              $selected = "selected";
          }
          $acc .= "\t\t <option value=\"" . $v->field[0] . "\" $selected >&nbsp;" . $v->field[0] . " - " . $v->field[1] . "</option>\n";
      }
      if ($addOption) {
          $acc .= "\t\t <option value=\"" . $addOption['value'] . "\"";
          if ($addOption['value'] == $val) {
              $acc .= " selected ";
          }
          $acc .= ">" . $addOption['descri'] . "</option>\n";
      }
      $acc .= "\t </select>\n";
      if ($echo){
        echo $acc;
      } else {
        return $acc;
      }
    }

    function selectAccount($name, $val, $type = 1, $val_hiddenReq = '', $tabidx = false, $class = 'FacetSelect', $opt = 'style="max-width: 550px;"', $mas_only = true, $echo=false) {
        global $gTables, $admin_aziend;
		$acc='';
        $bg_class = Array(1 => "gaz-attivo", 2 => "gaz-passivo", 3 => "gaz-costi", 4 => "gaz-ricavi", 5 => "gaz-transitori",
            6 => "gaz-transitori", 7 => "gaz-transitori", 8 => "gaz-transitori", 9 => "gaz-transitori");
        if (!empty($val_hiddenReq)) {
            $opt = " onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\"";
        }
        if ($tabidx) {
            $opt .= " tabindex=" . $tabidx;
        }
        if (is_array($type)) { /* per cercare tra i mastri l'array deve contenere tutti i
          i primi numeri che si vogliono ovvero: 1=attivo,2=passivo,3=ricavi,4=costi, ecc
          se si vuole cercare tra i sottoconti allora il primo elemento
          dell'array deve contenere il valore "SUB"
         */
            $where = '';
            $first = true;
            $sub = false;
            foreach ($type as $v) {
                if (strtoupper($v) == 'SUB') {
                    $sub = true;
                    continue;
                }
                $where .= ($first ? "" : " OR ");
                $first = false;
                if ($sub) {
                    $where .= "codice BETWEEN " . intval(substr($v, 0, 1)) . "00000001 AND " . intval(substr($v, 0, 1)) . "99999999 AND codice NOT LIKE '" . $admin_aziend['mascli'] . "%' AND codice NOT LIKE '" . $admin_aziend['masfor'] . "%' AND codice NOT LIKE '%000000'";
                } else {
                    $where .= "codice LIKE '" . intval(substr($v, 0, 1)) . "__000000'";
                }
            }
        } elseif ($type > 99) { // se passo il mastro
            $type = sprintf('%03d', substr($type, 0, 3));
            $where = "codice BETWEEN " . $type . "000001 AND " . $type . "999999 AND codice NOT LIKE '%000000'";
        } else {
            $where = "codice BETWEEN " . $type . "00000001 AND " . $type . "99999999 AND codice NOT LIKE '" . $admin_aziend['mascli'] . "%' AND codice NOT LIKE '" . $admin_aziend['masfor'] . "%' AND codice NOT LIKE '%000000'";
        }
        $acc .= "<select id=\"$name\" name=\"$name\" class=\"$class\" $opt>\n";
        $acc .= "\t<option value=\"0\"> ---------- </option>\n";
        $result = gaz_dbi_dyn_query("codice,descri", $gTables['clfoco'], $where, "codice ASC");
        while ($r = gaz_dbi_fetch_array($result)) {
            $selected = '';
            $v = $r["codice"];
            $c = intval($v / 100000000);
            $selected .= ' class="' . $bg_class[$c] . '" ';
            if ((intval($type) > 99 || (is_array($type) && count($type) == 1)) && $mas_only) {
                $v = intval(substr($r["codice"], 0, 3));
            }
            if ($val == $v) {
                $selected .= " selected ";
            }
            $acc .= "\t<option value=\"" . $v . "\"" . $selected . ">" . $r["codice"] . "-" . $r['descri'] . "</option>\n";
        }
        $acc .= "</select>\n";
		if ($echo){
			return $acc;
		} else {
			echo $acc;
		}
    }

    function selTypeRow($name, $val, $class = 'FacetDataTDsmall',$pers_type=false, $bridge = true) {
        global $script_transl;
		if ($pers_type){
			$tr=$pers_type;
		} else {
			$tr=$script_transl['typerow'];
		}
        $this->variousSelect($name, $tr, $val, $class, $bridge);
    }

    function selSearchItem($name, $val, $class = 'FacetDataTDsmall') {
        global $script_transl;
        $this->variousSelect($name, $script_transl['search_item'], $val, $class, true);
    }

    function selectLanguage($name,$val,$ret_type=false,$class='', $refresh=false) {
      global $gTables;
      $query = 'SELECT * FROM ' . $gTables['languages'].' WHERE 1 ORDER BY lang_id';
      $acc = '<select id="'.$name.'" name="'.$name.'" class="'.$class.'" '.($refresh?'onchange="this.form.hidden_req.value=\''.$refresh.'\'; this.form.submit();"':'').' >';
      $rs = gaz_dbi_query($query);
      while ($r = gaz_dbi_fetch_array($rs)) {
        $selected = '';
        if ($r['lang_id'] == intval($val)) {
          $selected = "selected";
        }
        $acc .= '<option value="'.$r['lang_id'] . '" '.$selected.' > '.base64_decode($r['emoji']).' '.$r['title'];
        $acc .= '</option>';
      }
      $acc .='</select>';
      if ($ret_type){
        return $acc;
      } else {
        echo $acc;
      }
    }

    function gazHeadMessage($message, $transl, $type = 'err') {
        if (!empty($message)) {
            $m = 'ERROR';
            $c = 'alert-danger';
            if ($type == 'war') {
                $m = 'ATTENTION';
                $c = 'alert-warning';
            }
            echo '<div class="container">
			<div class="row alert ' . $c . ' fade in" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Chiudi">
					<span aria-hidden="true">&times;</span>
				</button>
				';
            foreach ($message as $v) {
                echo '<span class="glyphicon glyphicon-alert" aria-hidden="true"></span> ' . $m . '!=> ' . $transl[$v] . "<br>\n";
            }
            echo "</div>
		</div>\n";
        }
        return '';
    }

    function gazResponsiveTable($rows,$id='gaz-responsive-table',$rowshead=[],$rowsfoot=[], $theadclass='bg-success',$thclass='') {
      // in $row ci devono essere i righi con un array così formattato:
      // $rows[row][col]=array('title'=>'nome_colonna','value'=>'valore','type'=>'es_input','class'=>'classe_bootstrap',table_id=>'gaz-resposive_table')
      // eventualmente si può valorizzare $rowshead e $rowsfoot per scrivere un rigo prima o dopo di quello di riferimento
      ?>
      <div class="col-xs-12" style="padding: 0; padding-right: 0" >
        <div id="<?php echo $id; ?>"  class="table-responsive" style="min-height: 80px;">
          <table class="col-xs-12 table-striped table-condensed cf">
            <thead class="cf">
              <tr class="<?php echo $theadclass;?>">
      <?php
      // attraverso il primo elemento dell'array allo scopo di scrivere il thead
			$fk=key($rows);
      foreach ($rows[$fk] as $v) {
        echo '<th class="'.$thclass.'">' . $v['head'] . "</th>";
      }
      ?>
              </tr>
            </thead>
            <tbody>
      <?php
      foreach ($rows as $k=>$col) {
        if (isset($rowshead[$k])){ // ho una intestazione per il rigo
          echo '<tr>'.$rowshead[$k].'</tr>';
				}
        echo '<tr>';
        foreach ($col as $v) {
          echo '<td data-title="' . $v['head'] . '" class="' . $v['class'] . '"';
          if (isset($v['td_content'])) { // se ho un tipo diverso dal semplice
            echo $v['td_content'];
          }
          echo '>' . $v['value'] . " </td>\n";
        }
        echo "</tr>\n";
        if (isset($rowsfoot[$k])){ // ho una intestazione per il rigo
          echo '<tr>'.$rowsfoot[$k].'</tr>';
				}
      }
      ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php
    }
}

/* SEZIONE PER L'ORDINAMENTO DEI RECORD IN OUTPUT
  SONO IMPOSTATE TUTE LE VARIABILI NECESSARIE ALLA FUNZIONE gaz_dbi_dyn_query
  imposto le variabili di sessione con i valori di default */
if (!isset($_GET['flag_order'])) {
    $flag_order = '';
    $flagorpost = '';
}
if (!isset($_GET['auxil'])) {
    $auxil = "1";
}
if (!isset($limit)) {
    $limit = "0";
}
if (!isset($passo)) {
    $passo = "20";
}
if (!isset($field)) {
    $field = "2";
}
//flag di ordinamento ascendente e discendente
if (isset($_GET['flag_order']) && ($_GET['flag_order'] == "DESC")) {
    $flag_order = "ASC";
    $flagorpost = "DESC";
} elseif (isset($_GET['flag_order']) && ($_GET['flag_order'] <> "DESC")) {
    $flag_order = "DESC";
    $flagorpost = "ASC";
}
// se $PHP_SELF e' compreso nel referer (ricaricamento dalla stessa pagina), conservo tutte le variabili di
// sessione, altrimenti resetto $session['field'], $session['limit'], $session['passo'], $session['where'] e session['order']
if (!isset($_SERVER["HTTP_REFERER"])) {
    $_SERVER["HTTP_REFERER"] = "";
}
// If you only want to determine if a particular needle  occurs within haystack, use the faster and less memory intensive function strpos() instead
//if (!strstr ($_SERVER["HTTP_REFERER"],$_SERVER['PHP_SELF'])) {
if (!strpos($_SERVER["HTTP_REFERER"], $_SERVER['PHP_SELF'])) {
    $field = "2";  // valore che indica alla gaz_dbi_dyn_query che orderby non va usato
    $flag_order = "DESC"; // per default i dati piu' recenti sono i primi
    $limit = "0";
    $passo = "20";
    $orderby = $field . " " . $flag_order;
    $auxil = "1";
    $where = '1';
}
// imposto il nuovo campo per l'ordinamento
if (isset($_GET['auxil'])) {
    $auxil = $_GET['auxil'];
}
if (isset($_GET['field'])) {
    $field = $_GET['field'];
}
$orderby = $field . ' ' . $flag_order;
if (isset($_GET['limit'])) {
    $limit = $_GET['limit'];
}
// statement where di default = 1
if (!isset($_GET['where'])) {
    $where = "1";
} else {
    $where = $_GET['where'];
}

// classe che visualizza i pulsanti per la navigazione dei record
// input= tabella, session[where], limit e passo.
// calcola i valori da impostare sulla variabile limit per scorrere i record
// visualizza il numero totale di record e i pulsanti
class recordnav {
  public $count;
    var $table;
    var $where;
    var $limit;
    var $passo;
    var $last;

    function __construct($table, $where, $limit, $passo) {
        global $limit, $passo;
        $this->table = $table;
        $this->where = $where;
        $this->limit = $limit;
        $this->passo = $passo;
        // faccio il conto totale dei record selezionati dalla query
        $this->count = gaz_dbi_record_count($table, $where);
        $this->last = $this->count - ($this->count % $this->passo);
        //return $last;
    }

    function output() {
        global $flagorpost;
        global $field;
        global $auxil, $script_transl;
        global $datfat;
        global $datemi;
        $first = 0;
        $next = $this->limit + $this->passo;
        $prev = $this->limit - $this->passo;
        // se e' arrivato a fondo scala imposto il fermo
        if ($prev <= 0) {
            $prev = 0;
        }
        if ($next >= $this->last) {
            $next = $this->last;
        }
        if (($this->count) <= $this->passo) {
            // non visualizzo la barra di navigazione dei record
            echo "<div align=\"center\"><font class=\"FacetFormDataFont\">Num. record = $this->count</font></div>";
        } else {
            echo "<div align=\"center\"><font class=\"FacetFormDataFont\">Num. record = $this->count</font></div>";
            echo "<div align=\"center\">";
            echo "| << <a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&auxil=" . $auxil . "&flag_order=" . $flagorpost . "&limit=0" . "&datfat=" . $datfat . "&datemi=" . $datemi . "\" >" . ucfirst($script_transl['first']) . "</a> ";
            echo "| < <a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&auxil=" . $auxil . "&flag_order=" . $flagorpost . "&limit=$prev" . "&datfat=" . $datfat . "&datemi=" . $datemi . "\">" . ucfirst($script_transl['prev']) . "</a> ";
            echo "| <a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&auxil=" . $auxil . "&flag_order=" . $flagorpost . "&limit=$next" . "&datfat=" . $datfat . "&datemi=" . $datemi . "\">" . ucfirst($script_transl['next']) . "</a> > ";
            echo "| <a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&auxil=" . $auxil . "&flag_order=" . $flagorpost . "&limit=$this->last" . "&datfat=" . $datfat . "&datemi=" . $datemi . "\">" . ucfirst($script_transl['last']) . "</a> >> |";
            echo "</div>";
        }
    }

}

// classe per la creazione di headers cliccabili per l'ordinamento dei record
// accetta come parametro un array associativo composto dalle label e relativi campi del db
class linkHeaders {
  public $align;
  public $style;

    var $headers = array(); // label e campi degli headers

    function __construct($headers) {
        $this->headers = $headers;
        $this->align = false;
        $this->style = false;
    }

    function setAlign($align) { // funzione per settare l'allineamento del testo passando un array
        $this->align = $align;
    }

    function setStyle($style) { // funzione per settare uno stile particolare passando un array
        $this->style = $style;
    }

    function output() {
        global $flag_order, $script_transl, $auxil, $headers;
        $k = 0; // � l'indice dell'array dei nomi di campo
        foreach ($this->headers as $header => $field) {
            $style = 'FacetFieldCaptionTD text-center';
            $align = '';
            if ($this->align) { // ho settato i nomi dei campi del db
                $align = ' style="text-align:' . $this->align[$k] . ';" ';
            }
            if ($this->style) { // ho settato degli stili diversi
                $style = $this->style[$k];
            }
            if ($field <> "") {
                echo "\t<th class=\"$style\" $align ><a href=\"" . $_SERVER['PHP_SELF'] . "?field=" . $field . "&flag_order=" . $flag_order . "&auxil=" . $auxil . "\" title=\"" . $script_transl['order'] . $header . "\">" . $header . "</a></th>\n\r";
            } else {
                echo "\t<th class=\"$style\" $align >" . $header . "</th>\n\r";
            }
            $k++;
        }
    }

}

/**
 * Svolge le funzioni delle classi recordnav e linkHeaders, nella prospettiva di sostituirle.
 *
 * Si appoggia a due variabili globali: $search_fields e $sortable_headers,
 * da definire nel modulo che vuole utilizzare la classe.
 *
 * Il parametro $table può essere un riferimento tabella semplice,  una JOIN, o anche
 * un'intera subquery (vedi /acquis/report_ddtacq.php e /acquis/report_distinte.php).
 */
class TableSorter {
    # database
    protected $table;      # usata internamente per contare i record totali
    protected $count;      # n. totale record
    public $group_by;      # se non vuota avrà forma: "x, y, z"
    public $where = "";    # costruita a partire dall'url corrente
    public $orderby = "";  # idem
    public $where_fix;     # condizioni fisse non dipendenti dall'url corrente

    # paginazione
    public $paginate = True;    # dividi i record in pagine?
    protected $passo;           # record per pagina
    protected $cur_page = 1;    # n. pagina corrente
    protected $pages = 1;       # n. pagine totali

    # ri/costruzione query dell'url              Esempi:
    protected $url_search_query = "";            # "articolo=123&movimento=4560"
    protected $url_order_query = "";             # "ord_artico=asc&ord_id_mov=desc"
    protected $url_order_query_parts = array();  # ["artico" => "asc", "id_mov" => "desc"]
    protected $url_page_query = "";              # "pag=3"
    const ord_prefix = "ord_";

    # header ordinabili
    protected $arrows = ["desc" => "&#9660;", "asc" => "&#9650;", null => ""];
    protected $align = false;                   # TODO
    protected $style = 'FacetFieldCaptionTD text-center';   # TODO

    # valori di default                Esempi:
    protected $default_search;         # ["caumag" => "1"]
    protected $default_order;          # analogo a $url_order_query_parts

    function __construct($table, $passo, $default_order, $default_search=[], $group_by=[], $where_fix='') {
      $this->passo = $passo;
      $this->group_by = join(", ", $group_by);
      $this->default_search = $default_search;
      $this->where_fix = $where_fix;
      $this->parse_search_request();
      $this->count = gaz_dbi_record_count($table, $this->where, $this->group_by);
      $this->set_pagination();
      $this->default_order = $default_order;
      $this->parse_order_request();
    }

    /**
     * Ritorna l'offset a partire dal quale estrarre i record (LIMIT).
     */
    public function getOffset() {
        if ($this->paginate)
            return ($this->cur_page - 1) * $this->passo;
        else return 0;
    }

    /**
     * Ritorna il numero di record da estrarre (LIMIT).
     */
    public function getLimit() {
        if ($this->paginate)
            return $this->passo;
        else return 100000;      # estrai tutti i record
    }

    /**
     * Compone frammenti di query ignorando quelli vuoti.
     */
    public static function join_queries(...$url_queries) {
        return implode("&", array_filter($url_queries));
    }

    /**
     * Elabora i parametri di ricerca contenuti nell'url della richiesta.
     *
     * Compone la parte WHERE della query db, e ricompone la parte di ricerca della url query
     * per utilizzarla nei link. Usa la variabile globale $search_fields, che a ogni nome di
     * parametro ricercabile deve associare un'espressione filtro da inserire nella WHERE.
     *
     */
    protected function parse_search_request() {
        global $search_fields;
        $url_search_query_parts = array();
        $where_parts = array();
        # tolgo i parametri vuoti (es. x in "?x=&y=3&z=0")
        $pruned_GET = array_filter($_GET, 'strlen');
        # i valori di default vengono sovrascritti se presenti anche nella richiesta
        $def_GET = array_merge($this->default_search, $pruned_GET);
        foreach ($search_fields as $field => $sql_expr) {
            if (isset($def_GET[$field]) && strlen($def_GET[$field])) {
                global $$field;  # settiamo una variabile globale chiamata come il parametro
                $$field = $def_GET[$field];
                if (isset($pruned_GET[$field]))  # escludiamo dall'url i valori default applicati
                  $url_search_query_parts[] = "$field=" . urlencode($$field);
                  $where_parts[] = sprintf($sql_expr, gaz_dbi_real_escape_string($$field), gaz_dbi_real_escape_string($$field));
                  $$field = htmlspecialchars($$field, ENT_QUOTES);
            }
        }
        if ($this->where_fix) $where_parts[] = $this->where_fix;
        $this->where = implode(" AND ", $where_parts);
        $this->url_search_query = implode("&", $url_search_query_parts);
    }

    /**
     * Imposta i dati di paginazione in base alle preferenze e al numero totale di record.
     */
    protected function set_pagination() {
        $this->pages = ceil($this->count / $this->passo) or 1;
        if (isset($_GET['pag'])) {
            if ($_GET['pag'] == "all") {
                $this->paginate = False;
                $this->url_page_query = "pag=all";
            } else {
                $this->cur_page = intval($_GET['pag']);
                $this->url_page_query = sprintf("pag=%d", $this->cur_page);
            }
        }
    }

    /**
     * Compone la parte di ordinamento di una url query.
     *
     * @param array $parts I parametri di ordinamento voluti; possono essere diversi da quelli attuali.
     */
    protected function make_url_order_query($parts) {
        $a = array();
        foreach ($parts as $field => $value) $a[] = self::ord_prefix . "$field=$value";
        return join("&", $a);
    }

    /**
     * Elabora i parametri di ordinamento contenuti nell'url della richiesta.
     *
     * I campi ammessi devono essere specificati nell'array globale $order_fields. Compone la parte
     * ORDER BY della query db e ricompone la parte di ordinamento dell'url, mantenendo l'ordine originale.
     * Per generare i link che permettono di cambiare l'ordinamento popola l'array $url_order_query_parts
     * con i parametri esplosi.
     *
     */
    protected function parse_order_request() {
        global $sortable_headers;
        $allowed_order_fields = array_filter(array_values($sortable_headers));
        $orderby = array();
        foreach($_GET as $field => $value) {
            list($db_fld) = sscanf($field, self::ord_prefix . "%s");
            if ($db_fld) {
                if (in_array($db_fld, $allowed_order_fields) && ($value == 'asc' or $value == 'desc')) {
                    $this->url_order_query_parts[$db_fld] = $value;
                    $orderby[] = $db_fld . " " . strtoupper($value);
                }
            }
        }
        $this->url_order_query = $this->make_url_order_query($this->url_order_query_parts);
        if (empty($orderby)) {
            foreach ($this->default_order as $field => $value)
                $orderby[] = $field . " " . strtoupper($value);
        }
        $this->orderby = implode(", ", $orderby);
    }

    /**
     * Stampa il numero di record, e se applicabile il n. di pagina corrente.
     */
    protected function count_header() {
        if ($this->count <= $this->passo) {
            $text = "record: $this->count";
        } elseif ($this->paginate) {
            $query = self::join_queries($this->url_search_query, $this->url_order_query, "pag=all");
            $text = "record: <a href='?$query' title='mostra tutti'>$this->count</a> / pag. $this->cur_page di $this->pages";
        } else {
            $query = self::join_queries($this->url_search_query, $this->url_order_query);
            $text = "record: $this->count / <a href='?$query'>sfoglia</a>";
        }
        echo "<div align='center'><font class='FacetFormDataFont'> $text </font></div>\n";
    }

    /**
     * Stampa, se occorrono, i link di navigazione tra le pagine.
     */
    public function output_navbar() {
        $this->count_header();
        if ($this->pages > 1 && $this->paginate) {
            $make_navtext = function ($target)  {
                global $script_transl;
                $text = sprintf(" %s ", ucfirst($script_transl[$target]));
                switch ($target) {
                    case 'first': $text = "&lt;" . $text;
                    case 'prev': $text = " &lt;" . $text; break;
                    case 'last': $text .= "&gt;";
                    case 'next': $text .= "&gt; ";
                }
                return "<span>$text</span>";
            };
            $linkify = function ($text, $page_number) {
                $query = self::join_queries($this->url_search_query, $this->url_order_query, "pag=$page_number");
                return "<a href='?$query' title='pag. $page_number'>$text</a>\n";
            };
            echo "<div align='center'>\n";
            $back = array_map($make_navtext, ["first", "prev"]);
            $forth = array_map($make_navtext, ["next", "last"]);
            if ($this->cur_page > 1)
                $back = array_map($linkify, $back, [1, $this->cur_page - 1]);
            if ($this->pages > $this->cur_page)
                $forth = array_map($linkify, $forth, [$this->cur_page + 1, $this->pages]);
            echo implode("|", array_merge($back, $forth));
            echo "</div>\n";
        }
    }

    /**
     * Ritorna il successivo modo di ordinamento disponibile.
     */
    protected function next_sort_order($current) {
        $keys = array_keys($this->arrows);
        return $keys[(array_search($current, $keys) + 1) % 3];
    }

    /**
     * Ritorna l'indicatore visivo dell'ordinamento di una colonna.
     */
    protected function make_arrows($field, $order, $style="") {
        $arrows = str_repeat($this->arrows[$order[$field]], array_search($field, array_keys($order)) + 1);
        return "<span style='float: right; $style'>$arrows</span>";
    }

    /**
     * Stampa il titolo cliccabile di una colonna che può essere ordinata.
     *
     * Utilizzata dal metodo output_headers().
     *
     */
    protected function make_header_link($text, $field) {
        $next = $this->next_sort_order("");
        $order = $this->url_order_query_parts;
        if (empty($order)) {
            if (isset($this->default_order[$field]))
                $text .= $this->make_arrows($field, $this->default_order, "opacity: 0.3");
        } elseif (isset($order[$field])) {
            $text .= $this->make_arrows($field, $order);
            if (!$next = $this->next_sort_order($order[$field]))
                unset($order[$field]);
        }
        if ($next) $order[$field] = $next;
        $url_query = self::join_queries($this->url_search_query, $this->make_url_order_query($order), $this->url_page_query);
        echo "<th class='$this->style' $this->align ><a href='?$url_query'>$text</a></th>\n";
    }

    /**
     * Stampa i titoli di tutte le colonne, con o senza link per l'ordinamento.
     *
     * Usa la variabile globale $sortable_headers, un array associativo tra titolo e
     * colonna del db corrispondente (o stringa vuota se quella colonna non deve poter
     * essere ordinata).
     *
     */
    public function output_headers() {
        global $sortable_headers;
        foreach ($sortable_headers as $text => $field) {
            if ($field <> "") {
                echo $this->make_header_link($text, $field);
            } else {
                echo "<th class='$this->style' $this->align >$text</th>\n";
            }
        }
    }

    /**
     * Stampa i parametri di ordinamento correnti per l'inclusione nella form di ricerca.
     *
     * In questo modo l'ordinamento delle colonne (e l'uso o meno della paginazione) può
     * essere mantenuto da una ricerca all'altra.
     *
     */
    public function output_order_form() {
        foreach ($this->url_order_query_parts as $field => $value) {
            printf("<input type='hidden' name='%s' value='%s' />\n", self::ord_prefix . $field, $value);
        }
        if (!$this->paginate) {
            echo "<input type='hidden' name='pag' value='all' />\n";
        }
    }
}

function redirect($filename) {
	$path_root = $_SERVER['DOCUMENT_ROOT'];
	$filename = str_replace($path_root, '', $filename);
	if (!headers_sent()) {
       	header("Location: ".$filename);
	} else {
		echo '<script type="text/javascript">';
	        echo 'window.location.href="'.$filename.'";';
      		echo '</script>';
       		echo '<noscript>';
        	echo '<meta http-equiv="refresh" content="0;url='.$filename.'" />';
		echo '</noscript>';
	}
	exit;
}

function checkAdmin($Livaut = 0) {
  global $gTables, $module, $table_prefix, $link;
  if (!isset($_SESSION["from_uri"])){
    $dn=explode('/',$_SERVER['REQUEST_URI']);
    if (isset($dn[2]) && $dn[2]=='modules' && $dn[3]!='root'){
      $_SESSION["from_uri"]='../'.$dn[3].'/'.basename($_SERVER['REQUEST_URI']);
    }
  }
  $_SESSION["Abilit"] = false;
  if (!$link) exit;
  // Se utente non è loggato lo mandiamo alla pagina di login
  if (!isset($_SESSION["user_name"])) {
    redirect('../root/login_user.php?tp=' . $table_prefix);
  }
  $rschk = checkAccessRights($_SESSION["user_name"], $module, $_SESSION['company_id']);
  if ($rschk == 0) {
    // Se utente non ha il diritto di accedere al modulo specifico lo invito a tornare alla home
    redirect("../root/access_error.php?module=" . $module);
    exit;
  } elseif (is_array($rschk)) { // questo utente ha almeno un script da escludere su questo modulo
    $bn = basename($_SERVER['PHP_SELF'],'.php');
    if (in_array($bn,$rschk)){
      redirect("../root/access_error.php?script=" . $bn);
      exit;
    }
  }
  $admin_aziend = gaz_dbi_get_row($gTables['admin'] . ' LEFT JOIN ' . $gTables['aziend'] . ' ON ' . $gTables['admin'] . '.company_id = ' . $gTables['aziend'] . '.codice', "user_name", $_SESSION["user_name"]);
  $currency = [];
  if (isset($admin_aziend['id_currency'])) {
    $currency = gaz_dbi_get_row($gTables['currencies'], "id", $admin_aziend['id_currency']);
  }
  if ($Livaut > $admin_aziend["Abilit"]) {
    redirect("../root/login_user.php?tp=" . $table_prefix);
    exit;
  } else {
    $_SESSION["Abilit"] = $admin_aziend["Abilit"];
    // includo le funzioni per la sincronizzazione dello shop online, il nome del modulo per il sync dell'ecommerce dev'essere sempre il primo rispetto ad altri eventuali moduli
    $admin_aziend['synccommerce_classname'] = '';
    $synccommerce=explode(',',$admin_aziend['gazSynchro'])[0];
    if ($synccommerce && file_exists('../'.$synccommerce.'/sync.function.php')) {
      include_once('../'.$synccommerce.'/sync.function.php');
      $admin_aziend['synccommerce_classname'] = preg_replace("/[^a-zA-Z]/","",$synccommerce)."gazSynchro";
    }
  }
  return array_merge($admin_aziend,$currency);
}

function changeEnterprise($new_co = 1) {
    global $gTables;
    gaz_dbi_put_row($gTables['admin'], "user_name", $_SESSION["user_name"], 'company_id', $new_co);
    $_SESSION['company_id'] = $new_co;
}

function encodeSendingNumber($data, $b = 62) {
    /* questa funzione mi serve per convertire un numero decimale in uno a base 36
      ------------------------- SCHEMA DEI DATI PER INVIO  ------------------------
      |   SEZIONE IVA   |  ANNO DOCUMENTO  | N.REINVII |    NUMERO PROTOCOLLO     |
      |     INT (1)     |      INT(1)      |   INT(1)  |        INT(5)            |
      |        3        |        9         |     9     |        99999             |
      | $data[sezione]  |   $data[anno] $data[fae_reinvii]  $data[protocollo]     |
      ------------------------------------------------------------------------------
     */
    $num = $data['sezione'] . substr($data['anno'], 3, 1).$data['fae_reinvii']. substr(str_pad($data['protocollo'], 5, '0', STR_PAD_LEFT), -5);
    $num = intval($num);
    $base = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $r = $num % $b;
    $res = $base[$r];
    $q = floor($num / $b);
    while ($q) {
        $r = $q % $b;
        $q = floor($q / $b);
        $res = $base[$r] . $res;
    }
    return $res;
}

function decodeFromSendingNumber($num, $b = 62) {
    $base = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $limit = strlen($num);
    $res = strpos($base, $num[0]);
    for ($i = 1; $i < $limit; $i++) {
        $res = $b * $res + strpos($base, $num[$i]);
    }
    return $res;
}

class Compute {
  public $total_imp;
  public $total_vat;
  public $total_exc_with_duty;
  public $total_isp;
  public $totroundcastle;
  public $castle;
  public $pay_taxstamp;

  function payment_taxstamp($value, $percent, $cents_ceil_round = 5) {
    if ($cents_ceil_round == 0) {
      $cents_ceil_round = 5;
    }
    $cents = 100 * $value * ($percent / 100 + $percent * $percent / 10000);
    if ($cents_ceil_round < 0) { // quando passo un arrotondamento negativo ritorno il valore di $percent
      $this->pay_taxstamp = round($percent, 2);
    } else {
      $this->pay_taxstamp = round(ceil($cents / $cents_ceil_round) * $cents_ceil_round / 100, 2);
    }
  }
  function add_value_to_VAT_castle($vat_castle, $value = 0, $vat_rate = 0) {
    global $gTables;
    $new_castle = [];
    $row = 0;
    $this->total_imp = 0;
    $this->total_vat = 0;
    $this->total_exc_with_duty = 0;
    $this->total_isp = 0; // totale degli inesigibili per split payment PA
    // ho due metodi di calcolo del castelletto IVA:
    // 1 - quando non ho l'aliquota IVA allora uso la ventilazione
    // 2 - in presenza di aliquota IVA e quindi devo aggiungere al castelletto
    $this->totroundcastle = 0;
    if ($vat_rate == 0) {        // METODO VENTILAZIONE (per mantenere la retrocompatibilit�)
      $total_imp = 0;
      $decalc_imp = 0;
      foreach ($vat_castle as $k => $v) { // attraverso dell'array per calcolare i totali
          $total_imp += $v['impcast'];
          $row++;
      }
      foreach ($vat_castle as $k => $v) {   // riattraverso l'array del castelletto
        // per aggiungere proporzionalmente (ventilazione)
        $vat = gaz_dbi_get_row($gTables['aliiva'], "codice", $k);
        if ($vat){
          $new_castle[$k]['codiva'] = $vat['codice'];
          $new_castle[$k]['periva'] = $vat['aliquo'];
          $new_castle[$k]['tipiva'] = $vat['tipiva'];
          $new_castle[$k]['descriz'] = $vat['descri'];
          $new_castle[$k]['fae_natura'] = $vat['fae_natura'];
          $row--;
          if (abs($total_imp) >= 0.01) { // per evitare il divide by zero in caso di imponibile 0
            if ($row == 0) { // � l'ultimo rigo del castelletto
              // aggiungo il resto
              $new_imp = round($total_imp - $decalc_imp + ($value * ($total_imp - $decalc_imp) / $total_imp), 2);
            } else {
              $new_imp = round($v['impcast'] + ($value * $v['impcast'] / $total_imp), 2);
              $decalc_imp += $v['impcast'];
            }
          } else {
            $new_imp = $v['impcast'];
          }
          $new_castle[$k]['impcast'] = $new_imp;
          $new_castle[$k]['imponi'] = $new_imp;
          $this->total_imp += $new_imp; // aggiungo all'accumulatore del totale
          if ($vat['aliquo'] < 0.01 && $vat['taxstamp'] > 0) { // � senza aliquota ed � soggetto a bolli
            $this->total_exc_with_duty += $new_imp; // aggiungo all'accumulatore degli esclusi/esenti/non imponibili
          }
          if(isset($v['impneg'])){$new_castle[$k]['impneg']=$v['impneg'];}
          $new_castle[$k]['ivacast'] = round(($new_imp * $vat['aliquo']) / 100, 2);
          if ($vat['tipiva'] == 'T') { // � un'IVA non esigibile per split payment
            $this->total_isp += $new_castle[$k]['ivacast']; // aggiungo all'accumulatore
          }
          $this->total_vat += $new_castle[$k]['ivacast']; // aggiungo anche l'IVA al totale
        }
      }
    } else {  // METODO DELL'AGGIUNTA DIRETTA (nuovo)
      $match = false;
      foreach ($vat_castle as $k => $v) { // attraverso dell'array
        $vat = gaz_dbi_get_row($gTables['aliiva'], "codice", $k);
        $new_castle[$k]['codiva'] = $vat['codice'];
        $new_castle[$k]['periva'] = $vat['aliquo'];
        $new_castle[$k]['tipiva'] = $vat['tipiva'];
        $new_castle[$k]['descriz'] = $vat['descri'];
        $new_castle[$k]['fae_natura'] = $vat['fae_natura'];
        if ($k == $vat_rate) { // SE è la stessa aliquota aggiungo il nuovo valore
          $match = true;
          $new_imp = $v['impcast'] + $value;
          $new_castle[$k]['impcast'] = $new_imp;
          $new_castle[$k]['imponi'] = $new_imp;
          $new_castle[$k]['ivacast'] = round(($new_imp * $vat['aliquo']) / 100, 2);
        } else { // è una aliquota che non interessa il valore che devo aggiungere
          $new_castle[$k]['impcast'] = $v['impcast'];
          $new_castle[$k]['imponi'] = $v['impcast'];
          $new_castle[$k]['ivacast'] = round(($v['impcast'] * $vat['aliquo']) / 100, 2);
        }
        if (isset($v['impneg'])){
          $new_castle[$k]['impneg']=$v['impneg'];
          $new_castle[$k]['ivaneg']=$v['ivaneg'];
        }
        if ($vat['aliquo'] < 0.01 && $vat['taxstamp'] > 0) { // � senza IVA ed � soggetto a bolli
            $this->total_exc_with_duty += $new_castle[$k]['impcast']; // aggiungo all'accumulatore degli esclusi/esenti/non imponibili
        }
        if ($vat['tipiva'] == 'T') { // è un'IVA non esigibile per split payment
            $this->total_isp += $new_castle[$k]['ivacast']; // aggiungo all'accumulatore
        }
        $this->total_imp += $new_castle[$k]['impcast']; // aggiungo all'accumulatore del totale
        $this->total_vat += $new_castle[$k]['ivacast']; // aggiungo anche l'IVA al totale
      }
      if (!$match && abs($value) >= 0.01) { // non ho trovato una aliquota uguale a quella del nuovo valore se > 0
        $vat = gaz_dbi_get_row($gTables['aliiva'], "codice", $vat_rate);
        $new_castle[$vat_rate]['codiva'] = $vat['codice'];
        $new_castle[$vat_rate]['periva'] = $vat['aliquo'];
        $new_castle[$vat_rate]['tipiva'] = $vat['tipiva'];
        $new_castle[$vat_rate]['impcast'] = $value;
        $new_castle[$vat_rate]['imponi'] = $value;
        $new_castle[$vat_rate]['ivacast'] = round(($value * $vat['aliquo']) / 100, 2);
        $new_castle[$vat_rate]['descriz'] = $vat['descri'];
        $new_castle[$vat_rate]['fae_natura'] = $vat['fae_natura'];
        if ($vat['aliquo'] < 0.01 && $vat['taxstamp'] > 0) { // � senza IVA ed � soggetto a bolli
          $this->total_exc_with_duty += $new_castle[$vat_rate]['impcast']; // aggiungo all'accumulatore degli esclusi/esenti/non imponibili
        }
        if ($vat['tipiva'] == 'T') { // è un'IVA non esigibile per split payment
          $this->total_isp += $new_castle[$vat_rate]['ivacast']; // aggiungo all'accumulatore
        }
        $this->total_imp += $new_castle[$vat_rate]['impcast']; // aggiungo all'accumulatore del totale
        $this->total_vat += $new_castle[$vat_rate]['ivacast']; // aggiungo anche l'IVA al totale
      }
    }
    $this->castle = $new_castle;
  }
  function round_VAT_castle($vat_castle, $valroundvat=[]) {
    global $gTables;
    $this->totroundcastle = 0;
    $new_castle=$vat_castle;
    foreach ($vat_castle as $k => $v) {   // riattraverso l'array del castelletto
      if(isset($valroundvat[$k])){
        $new_castle[$k]['ivacast'] = round($v['ivacast']+$valroundvat[$k],2);
        $this->total_vat += $valroundvat[$k];
        $this->totroundcastle += $valroundvat[$k];
      }
    }
    $this->castle = $new_castle;
  }

}

class Schedule {
	public $target = 0;
	public $id_target = 0;
  public $ExpiryStatus =[];
  public $Status;
  public $docData = [];
  public $PartnerStatus = [];
  public $Partners;
  public $Entries = [];
  public $RigmocEntries = [];

  function setPartnerTarget($account) {
  // setta il valore del conto (piano dei conti) del partner (cliente o fornitore)
      $this->target = $account;
  }

  function setIdTesdocRef($id_tesdoc_ref) {
  // setta sia l'identificativo di partita che il valore del conto (piano dei conti) del partner (cliente o fornitore)
      global $gTables;
      $rs = gaz_dbi_dyn_query($gTables['paymov'] . ".id_tesdoc_ref," . $gTables['tesmov'] . ".clfoco ", $gTables['paymov'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON " . $gTables['paymov'] . ".id_rigmoc_doc = " . $gTables['rigmoc'] . ".id_rig LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['tesmov'] . ".id_tes = " . $gTables['rigmoc'] . ".id_tes", $gTables['paymov'] . ".id_tesdoc_ref = '" . $id_tesdoc_ref . "'");
      while($r = gaz_dbi_fetch_array($rs)){
        $this->target = ($r['clfoco']>100000000)?$r['clfoco']:false;
      }
      $this->id_target = $id_tesdoc_ref;
  }

  function setScheduledPartner($partner_type = false,$datref=false) {
    // false=TUTTI altrimenti passare le prime tre cifre del mastro clienti o fornitori, oppure un partner specifico
    // in $datref si può passare una data di rifermiento nel formato leggibile GG-MM-AAAA,
    // in questo caso vengono presi in considerazione solo i movimenti di un anno (sei mesi prima e sei dopo)
    // restituisce in $this->Partners i codici dei clienti o dei fornitori che hanno almeno un movimento nell'archivio dello scadenzario
    global $gTables;
    if (!$partner_type) { // se NON mi è stato passato il mastro dei clienti o dei fornitori
      $partner_where = '';
    } elseif ($partner_type>=100000001) { //
      $partner_where = $gTables['rigmoc'] . ".codcon  = " . $partner_type ;
    } else  {
      $partner_where = $gTables['rigmoc'] . ".codcon  BETWEEN " . $partner_type . "000001 AND " . $partner_type . "999999";
    }
    if (!$datref) { // se NON mi è stata passata una data di riferimento prendo tutti i movimenti, altrimenti
      if (!$partner_type) {
        $partner_where.='1';
      }
    }else{
      $partner_where.=" AND ".$gTables["paymov"].".expiry BETWEEN DATE_SUB('".gaz_format_date($datref,true)."',INTERVAL 6 MONTH) AND DATE_ADD('".gaz_format_date($datref,true)."',INTERVAL 6 MONTH)";
    }
    $sqlquery = "SELECT " . $gTables['rigmoc'] . ".codcon
      FROM " . $gTables['paymov'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON (" . $gTables['paymov'] . ".id_rigmoc_pay  + " . $gTables['paymov'] . ".id_rigmoc_doc ) =  " . $gTables['rigmoc'] . ".id_rig WHERE  " . $partner_where . " GROUP BY " . $gTables['rigmoc'] . ".codcon ";
    $rs = gaz_dbi_query($sqlquery);
    $acc = [];
    while ($r = gaz_dbi_fetch_array($rs)) {
      if ($r['codcon']>=100000000) {
        $partner = gaz_dbi_get_row($gTables['clfoco'], 'codice', $r['codcon']);
        $acc[$r['codcon']] = $partner['descri'];
      }
    }
    asort($acc);
    $res = [];
    foreach ($acc as $k => $v) {
        $res[] = $k;
    }
    $this->Partners = $res;
  }

  function getScheduleEntries($ob = 0, $masclifor=0, $date = false) {
  // genera un array con tutti i movimenti di partite aperte con quattro tipi di ordinamento
  // se viene settato il partnerTarget allora prende in considerazione solo quelli relativi allo stesso
      global $gTables;
      if ($this->target == 0) {
          $where = $gTables['rigmoc'] . ".codcon LIKE '" . $masclifor . "%'";
      } else {
          $where = $gTables['rigmoc'] . ".codcon LIKE '" . $this->target . "%'";
      }
      if ($date != false) {
          $where .= " AND expiry>='" . date("Y-m-d", strtotime("-5 days")) . "' and expiry<='" . date("Y-m-d", strtotime("+2 month")) . "' group by id_tesdoc_ref ";
      }
      $sqlquery = "SELECT * FROM " . $gTables['paymov']
              . " LEFT JOIN " . $gTables['rigmoc'] . " ON (" . $gTables['paymov'] . ".id_rigmoc_pay + " . $gTables['paymov'] . ".id_rigmoc_doc) = " . $gTables['rigmoc'] . ".id_rig  "
              . " WHERE  " . $where . " ORDER BY id_tesdoc_ref, expiry";
      $rs = gaz_dbi_query($sqlquery);
      $this->Entries = [];
      $acc = array();
      while ($r = gaz_dbi_fetch_array($rs)) {
          $anagrafica = new Anagrafica();
          $partner = $anagrafica->getPartner($r['codcon']);
          $tes = gaz_dbi_get_row($gTables['tesmov'], 'id_tes', $r['id_tes']);
          $tes['ragsoc'] = $partner['ragso1'] . ' ' . $partner['ragso2'];
          switch ($ob) {
              case 1:
                  $acc[$r['expiry']][] = $r + $tes + $partner;
                  break;
              case 2:
              case 3:
                  $acc[$partner['ragso1']][] = $r + $tes + $partner;
                  break;
              default:
                  $acc[$r['expiry']][] = $r + $tes + $partner;
          }
      }
      if ($ob == 1 || $ob == 3) {
          krsort($acc);
      } else {
          ksort($acc);
      }
      $res = array();
      foreach ($acc as $v1) {
          foreach ($v1 as $v2) {
              $this->Entries[] = $v2;
          }
      }
  }

  function getPartnerAccountingBalance($clfoco, $date = false, $allrows=false) {
  // restituisce il valore del saldo contabile di un cliente ad una data, se passata, oppure alla data di sistema
      global $gTables;
      if ($this->target > 0 && $clfoco == 0) {
          $clfoco = $this->target;
      }
      if (!$date) {
          $date = date('Y-m-d');
      }
      // prima trovo la eventuale ultima apertura dei conti
      $sqllastAPE = "SELECT " . $gTables['tesmov'] . ".* ," . $gTables['rigmoc'] . ".import, " . $gTables['rigmoc'] . ".darave, '0' AS progressivo
          FROM " . $gTables['rigmoc'] . " LEFT JOIN " . $gTables['tesmov'] ." ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
          WHERE codcon = $clfoco AND caucon = 'APE' AND datreg < '".$date."' ORDER BY datreg DESC LIMIT 1";
      $rslastAPE = gaz_dbi_query($sqllastAPE);
      $lastAPE = gaz_dbi_fetch_array($rslastAPE);
      $dat =($lastAPE)?$lastAPE['datreg']:'2000-01-01';
      $acc_allrows = ($lastAPE)?array(0=>$lastAPE):[];
      $acc=0.00;
      $acc =($lastAPE && $lastAPE['darave']=='D')?$lastAPE['import']:$acc;
      $acc =($lastAPE && $lastAPE['darave']=='A')?-$lastAPE['import']:$acc;
      $sqlquery = "SELECT " . $gTables['tesmov'] . ".* ," . $gTables['rigmoc'] . ".import, " . $gTables['rigmoc'] . ".darave
          FROM " . $gTables['rigmoc'] . " LEFT JOIN " . $gTables['tesmov'] .
              " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
          WHERE datreg >= '".$dat."' AND codcon = $clfoco AND caucon <> 'CHI' AND caucon <> 'APE' ORDER BY datreg ASC";
      $rs = gaz_dbi_query($sqlquery);
      $date_ctrl = new DateTime($date);
      while ($r = gaz_dbi_fetch_array($rs)) {
          $dr = new DateTime($r['datreg']);
          if ($dr <= $date_ctrl) {
              if ($r['darave'] == 'D') {
                  $acc += $r['import'];
              } else {
                  $acc -= $r['import'];
              }
          }
          $r['progressivo']=round($acc, 2);
          $acc_allrows[]=$r;
      }
      $t= round($acc, 2);
      if ($allrows){
          return array('saldo'=>$t,'rows'=>$acc_allrows);
      } else {
          return $t;
      }
  }

  function getAmount($id_tesdoc_ref, $date = false) {
  //restituisce la differenza (stato) tra apertura e chiusura di una partita nel suo complesso, se passo una data viene restituito il valore del saldo della scadenza ultima più prossima ad essa
  global $gTables;
  $i=intval($id_tesdoc_ref);
  $rs = gaz_dbi_query("SELECT * FROM ".$gTables['paymov']." WHERE id_tesdoc_ref=".$id_tesdoc_ref." ORDER BY id_rigmoc_doc DESC, expiry ASC");
  $acc=[];
  $carry=[];
  $ret=[];
  while($r=gaz_dbi_fetch_array($rs)){	// attraverso e chiudo solo le "chiudibili" in ordine di scadenza
    if($r['id_rigmoc_doc']>0.01){ // le aperture le incontro prima
      $acc[$r['expiry']]=['id'=>$r['id'],'am'=>$r['amount']]; //accumulo gli id dei documenti ed il loro valore, compreso il progressivo
    }elseif($r['id_rigmoc_pay']>0.01){ // le chiusure le incontro dopo
      if (count($carry)>0){ // se ho un riporto attraverso le aperture rimaste per usarlo
        reset($acc);
        foreach($acc as $expiry=>$vap){
        if ($carry['amount']>0){
          if ($carry['amount']==$vap['am']) { // posso chiudere tutta l'apertura gli importi coincidono
            unset($acc[$expiry]); // tolgo lapertura per non ciclarlo più
            $carry['amount']=0; // azzero il valore di chiusura
            $ret[$expiry]=0.00; // ritorno valore scadenza
          } elseif ($carry['amount']>$vap['am']) { // la chiusura ecced
            unset($acc[$expiry]); // lo tolgo per non ciclarlo più
            $carry['amount']-=$vap['am']; // e riduco il valore di chiusura
            $ret[$expiry]=round($carry['amount'],2); // ritorno valore scadenza
          } else { // la chiusura è insufficiente
            $acc[$expiry]['am'] -= $carry['amount'];
            $carry['amount']=0; // azzero il valore di chiusura
            $ret[$expiry]=0.00; // ritorno valore scadenza
          }
        }
        }
      }
      reset($acc);
      foreach($acc as $expiry=>$vap){ // attraverso le aperture per chiuderle con il valore corrente di chiusura: $r['amount']
      if ($r['amount']>0){
        if ($r['amount']==$vap['am']) { // posso chiudere tutta l'apertura gli importi coincidono
          unset($acc[$expiry]); // tolgo lapertura per non ciclarlo più
          $r['amount']=0; // azzero il valore di chiusura
        } elseif ($r['amount']>$vap['am']) { // la chiusura ecced
          unset($acc[$expiry]); // lo tolgo per non ciclarlo più
          $r['amount']-=$vap['am']; // e riduco il valore di chiusura
        } else { // la chiusura è insufficiente
          $acc[$expiry]['am'] -= $r['amount'];
          $r['amount']=0; // azzero il valore di chiusura
        }
      }
      }
      if ($r['amount']>=0.01) { // se ho un residuo di chiusura lo accumulo sul riporto
      $carry=['id'=>$r['id'],'am'=>$r['amount']];
      }
    }
  }
  $retval=0.00;
  foreach($acc as $ex=>$v) {
    if ($date) { // dovrò restituire solo il valore dell'ultima più prossima alla referenza
    $ex_time = strtotime($ex);
    $da_time = strtotime($date);
    $retval=$v['am'];
    if ($ex_time>$da_time){ // ho superato la data di referenza non considero questa ma mi fermo e restituisco l'ultimo
      $retval=$last_v;
      break;
    }
    } else { // dovrò restituire il valore del saldo complessivo
    if ($v['am'] < 0.01) continue;
    $retval += $v['am'];
    }
    $last_v=$v['am'];
  }
  return round($retval,2);
  }

  function getStatus($id_tesdoc_ref, $date = false) {
  // restituisce in $this->Satus la differenza (stato) tra apertura e chiusura di una partita
      global $gTables;
  $date_ref = new DateTime($date);
  $date_ref->modify('+ 1 hour');
  $date_ctrl='1';
  if ($date){
    $date_ctrl=" (expiry <= '".$date."')";
  }
      $sqlquery = "SELECT SUM(amount*(id_rigmoc_doc>0) * ".$date_ctrl." - amount*(id_rigmoc_pay>0)) AS diff_paydoc,
      SUM(amount*(id_rigmoc_pay>0)) AS pay,
      SUM(amount*(id_rigmoc_doc>0))AS doc,
      MAX(expiry) AS exp
      FROM " . $gTables['paymov'] . "
      WHERE id_tesdoc_ref = '" . $id_tesdoc_ref . "' GROUP BY id_tesdoc_ref";
      $rs = gaz_dbi_query($sqlquery);
      $r = gaz_dbi_fetch_array($rs);
      if(is_array($r)){
        $ex = new DateTime($r['exp']);
        $interval = $date_ref->diff($ex);
        if ($r['diff_paydoc'] >= 0.01) { // la partita � aperta
            $r['sta'] = 0;
            $r['style'] = 'info';
            if ($date_ref > $ex) { // ... ed � pure scaduta
                $r['sta'] = 3;
        $r['style'] = 'danger';
            }
        } elseif ($r['diff_paydoc'] == 0.00) { // la partita � chiusa ma...
            if ($date_ref < $ex) { //  se � un pagamento che avverr� ma non � stato realmente effettuato , che comporta esposizione a rischio
                $r['sta'] = 2; // esposta
        $r['style'] = 'warning';
            } else { // altrimenti � chiusa completamente
                $r['sta'] = 1;
        $r['style'] = 'success';
            }
        } else {
            $r['sta'] = 9;
            $r['style'] = 'default';
        }
      }else{
        $r['sta'] = 1;
        $r['style'] = 'success';
      }
      $this->Status = $r;
  }

  function getExpiryStatus($expiry_ref) {
  // creo un array con le scadenze della stessa partita per controllare se sono aperte o chiuse
      global $gTables;
      $sqlquery = "SELECT * FROM " . $gTables['paymov'] . " WHERE id_tesdoc_ref = '" . $this->id_target. "' ORDER BY id_rigmoc_pay, expiry";
      $rs = gaz_dbi_query($sqlquery);
      $date_ctrl = new DateTime();
      $ctrl_id = 0;
  $acc=[];
      while ($r = gaz_dbi_fetch_array($rs)) {
          $expo = false;
          $k = $r['id_tesdoc_ref'];
          if ($k <> $ctrl_id) { // PARTITA DIVERSA DALLA PRECEDENTE
              $acc[$k] = [];
          }
          $ex = new DateTime($r['expiry']);
          $interval = $date_ctrl->diff($ex);
          if ($r['id_rigmoc_doc'] > 0) { // APERTURE (vengono prima delle chiusure)
              $s = 0;
      $style='info';
              if ($date_ctrl >= $ex) {
                  $s = 3; // SCADUTA
        $style='danger';
              }
              $acc[$k][] = array('id' => $r['id'], 'op_val' => $r['amount'], 'expiry' => $r['expiry'], 'cl_val' => 0, 'cl_exp' => '', 'expo_day' => 0, 'status' => $s,'style'=>$style,'cl_rig_data' => array());
          } else {                    // ATTRIBUZIONE EVENTUALI CHIUSURE ALLE APERTURE (in ordine di scadenza)
              if ($date_ctrl < $ex) { //  se � un pagamento che avverr� ma non � stato realmente effettuato , che comporta esposizione a rischio
                  $expo = true;
              }
              $v = $r['amount'];
      if (isset($acc[$k])){
                foreach ($acc[$k] as $ko => $vo) { // attraverso l'array delle aperture

                    $diff = round($vo['op_val'] - $vo['cl_val'], 2);
                    if ($v <= $diff) { // se c'è capienza
                        $acc[$k][$ko]['cl_val'] += $v;
                        if ($expo) { // è un pagamento che avverrà ma non è stato realmente effettuato , che comporta esposizione a rischio
                            $acc[$k][$ko]['expo_day'] = $interval->format('%a');
                            $acc[$k][$ko]['cl_exp'] = $r['expiry'];
                            $expo = false;
                        } else {
                            $acc[$k][$ko]['cl_exp'] = $r['expiry'];
                        }
                        $v = 0;
                    } else { // non c'è capienza
                        $acc[$k][$ko]['cl_val'] += $diff;
                        if ($expo && $diff >= 0.01) { // è un pagamento che avverrà ma non è stato realmente effettuato , che comporta esposizione a rischio
                            $acc[$k][$ko]['expo_day'] = $interval->format('%a');
                            $acc[$k][$ko]['cl_exp'] = $r['expiry'];
                        }
                        $v = round($v - $diff, 2);
                    }
                    if (round($acc[$k][$ko]['op_val'] - $acc[$k][$ko]['cl_val'], 2) < 0.01) { // è chiusa
                        $acc[$k][$ko]['status'] = 1;
                        $acc[$k][$ko]['style'] ='success';
                    }
                }
                if (count($acc[$k]) == 0) {
                    $acc[$k][] = array('id' => $r['id'], 'op_val' => 0, 'expiry' => 0, 'cl_val' => $r['amount'], 'cl_exp' => $r['expiry'], 'expo_day' => 0, 'style' =>'default', 'status' => 9, 'op_id_rig' => 0);
                }
      }
          }
          $ctrl_id = $r['id_tesdoc_ref'];
      }
  $ret=false;
  foreach($acc as $k0=>$v0){
    foreach($v0 as $k1=>$v1){
      if ($v1['expiry']==$expiry_ref){
        if($v1['expo_day']>=1){$v1['style']='warning';$v1['status']=2;}
        $ret=$v1;
      }
    }
  }
      $this->ExpiryStatus = $ret;
  }

  function getDocumentData($id_tesdoc_ref, $clfoco = null) {
  //restituisce i dati relativi al documento che ha aperto la partita
      global $gTables;

      if (!is_numeric($id_tesdoc_ref)) {
          $id_tesdoc_ref = "'" . $id_tesdoc_ref . "'";
      }

      $where_clfoco = "";
      if (isset($clfoco)) {
          $where_clfoco = " AND " . $gTables['tesmov'] . ".clfoco = $clfoco ";
      }

      $sqlquery = "SELECT " . $gTables['tesmov'] . ".*
          FROM " . $gTables['paymov'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON " . $gTables['paymov'] . ".id_rigmoc_doc = " . $gTables['rigmoc'] . ".id_rig
          LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
          WHERE " . $gTables['paymov'] . ".id_rigmoc_doc > 0 AND " . $gTables['paymov'] . ".id_tesdoc_ref = " . $id_tesdoc_ref . $where_clfoco . " ORDER BY datreg ASC";
      $rs = gaz_dbi_query($sqlquery);
      return gaz_dbi_fetch_array($rs);
  }

  function getDocFromID($id_rigmoc_doc) {
      global $gTables;
      $sqlquery = "SELECT " . $gTables['tesmov'] . ".* , " . $gTables['rigmoc'] . ".codcon
          FROM " . $gTables['rigmoc'] . " LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes
          WHERE " . $gTables['rigmoc'] . ".id_rig = " . $id_rigmoc_doc;
      $rs = gaz_dbi_query($sqlquery);
      return gaz_dbi_fetch_array($rs);
  }

  function getPartnerStatus($clfoco, $date = false, $order='') {
  // genera un array ($this->PartnerStatus)con i valori dell'esposizione verso un partner commerciale
  // riferito ad una data, se passata, oppure alla data di sistema
  // $this->docData verrà valorizzato con i dati relativi al documento di riferimento
      global $gTables;
      $this->PartnerStatus = array();
      if ($clfoco <= 999 && $clfoco >= 100) { // ho un mastro clienti o foritori
          $clfoco = "999999999 OR " . $gTables['clfoco'] . ".codice LIKE '" . $clfoco . "%'";
      } elseif ($this->target > 0 && $this->id_target > 0) {
        //print $this->target;
          $clfoco = $this->target . " AND id_tesdoc_ref = '" . $this->id_target . "'";
      } elseif ($this->target > 0 && $clfoco == 0) {
          $clfoco = $this->target;
      }
      if (!$date) {
          $date = date("Y-m-d");
      }
      $sqlquery = "SELECT " . $gTables['paymov'] . ".*, " . $gTables['tesmov'] . ".* ," . $gTables['rigmoc'] . ".*
          FROM " . $gTables['paymov'] . " LEFT JOIN " . $gTables['rigmoc'] . " ON (" . $gTables['paymov'] . ".id_rigmoc_pay + " . $gTables['paymov'] . ".id_rigmoc_doc) = " . $gTables['rigmoc'] . ".id_rig "
              . "LEFT JOIN " . $gTables['tesmov'] . " ON " . $gTables['rigmoc'] . ".id_tes = " . $gTables['tesmov'] . ".id_tes "
              . "LEFT JOIN " . $gTables['clfoco'] . " ON " . $gTables['rigmoc'] . ".codcon = " . $gTables['clfoco'] . ".codice
          WHERE " . $gTables['clfoco'] . ".codice  = " . $clfoco . " ORDER BY id_tesdoc_ref ".$order.", id_rigmoc_pay ASC, expiry ASC";
      $rs = gaz_dbi_query($sqlquery);
      $date_ctrl = new DateTime($date);
      $ctrl_id = 0;
      $acc = [];
      $acc_amount=0;
      $first_id_tesdoc_ref=0;
      while ($r = gaz_dbi_fetch_array($rs)) {
          $expo = false;
          $k = $r['id_tesdoc_ref'];
          if ($k <> $ctrl_id) { // PARTITA DIVERSA DALLA PRECEDENTE
              $acc[$k] = [];
              if ($ctrl_id==0){ $first_id_tesdoc_ref=$k; } // conservo l'id del documento iniziale per metterci il saldo alla fine del ciclo
          }
          $ex = new DateTime($r['expiry']);
          $interval = $date_ctrl->diff($ex);
          if ($r['id_rigmoc_doc'] > 0) { // APERTURE (vengono prima delle chiusure)
              $s = 0;
      $style='info';
              if ($date_ctrl >= $ex) {
                  $s = 3; // SCADUTA
        $style='danger';
              }
              $acc[$k][] = array('id' => $r['id'],'descri' => 'n.'.$r['numdoc'].' del '.gaz_format_date($r['datdoc']), 'op_val' => $r['amount'], 'expiry' => $r['expiry'], 'cl_val' => 0, 'cl_exp' => '', 'expo_day' => 0, 'status' => $s,'style'=>$style, 'op_id_rig' => $r['id_rig'], 'cl_rig_data' => array());
              // aggiungo l'apertura al totale
              $acc_amount += $r['amount'];
          } else {                    // ATTRIBUZIONE EVENTUALI CHIUSURE ALLE APERTURE (in ordine di scadenza)
              if ($date_ctrl < $ex) { //  se � un pagamento che avverr� ma non � stato realmente effettuato , che comporta esposizione a rischio
                  $expo = true;
              }
              // detraggo la chiusura al totale
              $acc_amount -= $r['amount'];
              $v = $r['amount'];
      if (isset($acc[$k])){
                foreach ($acc[$k] as $ko => $vo) { // attraverso l'array delle aperture
                    $diff = round($vo['op_val'] - $vo['cl_val'], 2);
                    if ($diff >= 0.01 && $v > 0.01) { // faccio il push sui dati del rigo
                        $acc[$k][$ko]['cl_rig_data'][] = array('id_rig' => $r['id_rig'], 'descri' => $r['descri'], 'id_tes' => $r['id_tes'], 'import' => $r['import']);
                    }
                    if ($v <= $diff) { // se c'� capienza
                        $acc[$k][$ko]['cl_val'] += $v;
                        if ($expo) { // � un pagamento che avverr� ma non � stato realmente effettuato , che comporta esposizione a rischio
                            $acc[$k][$ko]['expo_day'] = $interval->format('%a');
                            $acc[$k][$ko]['cl_exp'] = $r['expiry'];
                            $expo = false;
                        } else {
                            $acc[$k][$ko]['cl_exp'] = $r['expiry'];
                        }
                        $v = 0;
                    } else { // non c'� capienza
                        $acc[$k][$ko]['cl_val'] += $diff;
                        if ($expo && $diff >= 0.01) { // � un pagamento che avverr� ma non � stato realmente effettuato , che comporta esposizione a rischio
                            $acc[$k][$ko]['expo_day'] = $interval->format('%a');
                            $acc[$k][$ko]['cl_exp'] = $r['expiry'];
                        }
                        $v = round($v - $diff, 2);
                    }
                    if (round($acc[$k][$ko]['op_val'] - $acc[$k][$ko]['cl_val'], 2) < 0.01) { // � chiusa
                        $acc[$k][$ko]['status'] = 1;
                        $acc[$k][$ko]['style'] ='success';
                    }
                }
                if (count($acc[$k]) == 0) {
                    $acc[$k][] = array('id' => $r['id'], 'op_val' => 0, 'expiry' => 0, 'cl_val' => $r['amount'], 'cl_exp' => $r['expiry'], 'expo_day' => 0, 'style' =>'default', 'status' => 9, 'op_id_rig' => 0, 'cl_rig_data' => array(0 => array('id_rig' => $r['id_rig'], 'descri' => $r['descri'], 'import' => $r['import'], 'id_tes' => $r['id_tes'])));
                }
      }
          }
          $invocecau=substr($r['caucon'].'',0,2);
          if (!isset($this->docData[$k]) || $invocecau=='AF' || $invocecau=='FA' ) { // le note credito solo se non hanno già una fattura a riferimento
              $this->docData[$k] = array('id_tes' => $r['id_tes'], 'descri' => $r['descri'], 'numdoc' => $r['numdoc'], 'seziva' => $r['seziva'], 'datdoc' => $r['datdoc'], 'amount' => $r['amount']);
          }

          $ctrl_id = $r['id_tesdoc_ref'];
      }
      // alla fine attribuisco il saldo totale al primo documento
      $this->docData[$first_id_tesdoc_ref]['saldo']=round($acc_amount,2);
      $this->PartnerStatus = $acc;
  }

  function updatePaymov($data) {
      global $gTables;
      if (isset($data['id']) && !empty($data['id'])) { // se c'� l'id vuol dire che � un rigo da aggiornare
          paymovUpdate(array('id', $data['id']), $data);
      } elseif (is_numeric($data)) { /* se passo un dato numerico vuol dire che devo eliminare tutti i righi
       * di paymov che fanno riferimento a quell'id_rig */
          gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_doc", $data);
          gaz_dbi_del_row($gTables['paymov'], "id_rigmoc_pay", $data);
      } elseif (isset($data['id_del'])) { /* se passo un id da eliminare elimino SOLO quello */
          gaz_dbi_del_row($gTables['paymov'], "id", $data['id_del']);
      } else {    // altrimenti � un nuovo rigo da inserire
          paymovInsert($data);
      }
  }

  function setRigmocEntries($id_rig) {
      global $gTables;
      $sqlquery = "SELECT * FROM " . $gTables['paymov'] . " WHERE id_rigmoc_pay=$id_rig OR id_rigmoc_doc=$id_rig";
      $this->RigmocEntries = [];
      $rs = gaz_dbi_query($sqlquery);
      while ($r = gaz_dbi_fetch_array($rs)) {
          $this->RigmocEntries[] = $r;
      }
  }

  function deleteClosedPaymov($id_tesdoc_ref){
    // passando semplicemente il numero di partita cancella in ordine di scadenza tutte le eventuali scadenze chiuse lasciando aperte  quelle (anche parzialmente)  aperte
    global $gTables;
		$i=intval($id_tesdoc_ref);
		$rs = gaz_dbi_query("SELECT * FROM ".$gTables['paymov']." WHERE id_tesdoc_ref=".$i." ORDER BY id_rigmoc_doc DESC, expiry ASC");
		$acc=[];
		$carry=[];
		while($r=gaz_dbi_fetch_array($rs)){	// potrebbero non essere chiuse tutte le scadenze della partita allora la attraverso e chiudo solo le "chiudibili" in ordine di scadenza
			if($r['id_rigmoc_doc']>0.01){ // le aperture le incontro prima
				$acc[]=['id'=>$r['id'],'am'=>$r['amount']]; //accumulo gli id dei documenti ed il loro valore, compreso il progressivo
			}elseif($r['id_rigmoc_pay']>0.01){ // le chiusure le incontro dopo
			  if (count($carry)>0){ // se ho un riporto attraverso le aperture rimaste per usarlo
				  reset($acc);
				  foreach($acc as $k=>$vap){
					if ($carry['amount']>0){
						if ($carry['amount']==$vap['am']) { // posso chiudere tutta l'apertura gli importi coincidono
							gaz_dbi_del_row($gTables['paymov'],'id',$vap['id']); // rimovo apertura
							gaz_dbi_del_row($gTables['paymov'],'id',$carry['id']); // rimovo chiusura
							unset($acc[$k]); // tolgo lapertura per non ciclarlo più
							$carry['amount']=0; // azzero il valore di chiusura
						} elseif ($carry['amount']>$vap['am']) { // la chiusura ecced
							gaz_dbi_del_row($gTables['paymov'],'id',$vap['id']); // rimovo apertura
							gaz_dbi_put_row($gTables['paymov'],'id',$carry['id'], 'amount', round($carry['amount']-$vap['am'],2)); // riduco la chiusura
							unset($acc[$k]); // lo tolgo per non ciclarlo più
							$carry['amount']-=$vap['am']; // e riduco il valore di chiusura
						} else { // la chiusura è insufficiente
							gaz_dbi_put_row($gTables['paymov'],'id',$vap['id'], 'amount', round($vap['am']-$carry['amount'],2)); // riduco l'apertura
							gaz_dbi_del_row($gTables['paymov'],'id',$carry['id']); // rimuovo chiusura insufficiente
							$acc[$k]['am'] -= $carry['amount'];
							$carry['amount']=0; // azzero il valore di chiusura
						}
					}
				  }
			  }
			  reset($acc);
			  foreach($acc as $k=>$vap){ // attraverso le aperture per chiuderle con il valore corrente di chiusura: $r['amount']
				if ($r['amount']>0){
					if ($r['amount']==$vap['am']) { // posso chiudere tutta l'apertura gli importi coincidono
						gaz_dbi_del_row($gTables['paymov'],'id',$vap['id']); // rimovo apertura
						gaz_dbi_del_row($gTables['paymov'],'id',$r['id']); // rimovo chiusura
						unset($acc[$k]); // tolgo lapertura per non ciclarlo più
						$r['amount']=0; // azzero il valore di chiusura
					} elseif ($r['amount']>$vap['am']) { // la chiusura ecced
						gaz_dbi_del_row($gTables['paymov'],'id',$vap['id']); // rimovo apertura
						gaz_dbi_put_row($gTables['paymov'],'id',$r['id'], 'amount', round($r['amount']-$vap['am'],2)); // riduco la chiusura
						unset($acc[$k]); // lo tolgo per non ciclarlo più
						$r['amount']-=$vap['am']; // e riduco il valore di chiusura
					} else { // la chiusura è insufficiente
						gaz_dbi_put_row($gTables['paymov'],'id',$vap['id'], 'amount', round($vap['am']-$r['amount'],2)); // riduco l'apertura
						gaz_dbi_del_row($gTables['paymov'],'id',$r['id']); // rimuovo chiusura insufficiente
						$acc[$k]['am'] -= $r['amount'];
						$r['amount']=0; // azzero il valore di chiusura
					}
				}
			  }
			  if ($r['amount']>=0.01) { // se ho un residuo di chiusura lo accumulo sul riporto
				$carry=['id'=>$r['id'],'am'=>$r['amount']];
			  }
			}
		}
	}
}

// controllo se ho delle funzioni specifiche per il modulo corrente residente nella directory del module stesso, con queste caratteristiche: modules/nome_modulo/lib.function.php
if (@file_exists('./lib.function.php')) {
    require('./lib.function.php');
}
?>
