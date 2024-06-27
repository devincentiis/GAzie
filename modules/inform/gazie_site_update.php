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
require("../../library/php-ico/class-php-ico.php");
$admin_aziend=checkAdmin(9);
$company_id=sprintf('%03d',$admin_aziend['company_id']);
//print_r($admin_aziend);
function getConfig($var)
{
    global $table_prefix;
    $query = "SELECT * FROM `".$gTables['company_config']."` WHERE var = '$var'";
    $result = gaz_dbi_query ($query);
    $data = gaz_dbi_fetch_array($result);
    return $data;
}

function createProductTable($company_id,$prezzi=false,$decimal_price,$backcolor='f7b6c5')
{
    global $gTables;
    // creo files immagini e tabella da inserire lla pagina "product"-  catalogo con le immagini ed eventualmente i prezzi degli articoli di magazzino
    $rs_art = gaz_dbi_dyn_query($gTables['artico'].".codice AS codart,".
    $gTables['artico'].".descri AS desart,".
    $gTables['artico'].".image AS imaart,".
    $gTables['artico'].".catmer,".
    $gTables['artico'].".unimis,".
    $gTables['artico'].".barcode AS barcod,".
    $gTables['artico'].".web_url AS linkart,".
    $gTables['artico'].".web_mu,".
    $gTables['artico'].".web_multiplier,".
    $gTables['artico'].".web_price AS prezzo,".
    $gTables['artico'].".annota AS annart,".
    $gTables['artico'].".pack_units AS units,".
    $gTables['catmer'].".descri AS descat,".
    $gTables['catmer'].".image AS imacat,".
    $gTables['catmer'].".codice AS codcat,".
    $gTables['catmer'].".web_url AS linkcat,".
    $gTables['aliiva'].".aliquo,".
    $gTables['catmer'].".annota AS anncat ",
    $gTables['artico']." LEFT JOIN ".$gTables['aliiva']." ON ".$gTables['artico'].".aliiva = ".$gTables['aliiva'].".codice ".
    " LEFT JOIN ".$gTables['catmer']." ON ".$gTables['artico'].".catmer = ".$gTables['catmer'].".codice",
    1,
    "codcat, codart");
    $ctrl_cm = 0;
    $html='<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0" align="center"><tbody>';
    $html .= '<tr>
            <td></td>
            <td>codice</td>
            <td>descrizione</td>
            <td>prezzo</td>
            <td>unità misura</td>
            <td>annotazione</td>
            </tr>
            ';     
    while ($r = gaz_dbi_fetch_array($rs_art)) {
        if ($prezzi==2) { // articoli con prezzo  
            $price = gaz_format_quantity($r['prezzo']*$r['web_multiplier'],true,$decimal_price);
            $vat='+IVA '.floatval($r['aliquo']).'%';
            $um= '€/'.$r['web_mu'];
        } elseif($prezzi==1) { // articoli con prezzo 
            $price = '';
            $vat='';
            $um='';
        }
        if ($r['codcat'] <> $ctrl_cm) { // categoria merceologica
            if ( !empty($r['imacat']) ) {
                // creo il file immagine
                $file=fopen('gazie_data.tmp','w+');
                fwrite($file,$r['imacat']);
                fclose($file);
                $imgd=getimagesize('gazie_data.tmp',$info);
                $mime_r=explode('/', $imgd['mime']);
                $mime_elem=end($mime_r);
                $file = fopen('gazie_site'.$company_id.'/images/catmer_'.$r['codcat'].".".$mime_elem, "w");
                fwrite($file, $r['imacat']);
                fclose($file);
                $src='images/catmer_'.$r['codcat'].".".$mime_elem;
            } else {
                $src='gazie_site_noimage.png';
            }
            $html .= '<tr><td colspan="5" bgcolor="#'.$backcolor.'">'.$r['codcat'].' - '.$r['descat'].'</td><td><img src="'.$src.'" height="50"></td>
            </tr>';
        }
        // articolo
        if ( !empty($r['imaart']) ) {
            // creo il file immagine
            $file=fopen('gazie_data.tmp','w+');
            fwrite($file,$r['imaart']);
            fclose($file);
            $imgd=getimagesize('gazie_data.tmp',$info);
            $mime_r=explode('/', $imgd['mime']);
            $mime_elem=end($mime_r);
            $file = fopen('gazie_site'.$company_id.'/images/artico_'.$r['codart'].".".$mime_elem, "w");
            fwrite($file, $r['imaart']);
            fclose($file);
            $src='images/artico_'.$r['codart'].".".$mime_elem;
        } else {
            $src='gazie_site_noimage.png';
        }
        $html .= '<tr><td><img src="'.$src.'" class="imgBox" title=" header=['.$r['codart'].'] body=[<center><img src=\''.$src.'\'>] fade=[on] fadespeed=[0.03] "></td><td>'.$r['codart'].'</td><td>'.$r['desart'].'</td><td align="right">'.$price.' </td> <td>'.$um.' '.$vat.'</td><td>'.$r['annart'].'</td>
        </tr>';
        $ctrl_cm = $r['codcat'];
    }
    return $html."</tbody></table>\n";
}

function createHtmlPage($title,$subtitle,$author,$keywords,$pages_name_and_descri,$data,$mime_logo,$footer='')
{
    global $admin_aziend;
    // defifinisco le variabili aziendali
    $title = $admin_aziend["ragso1"].' '.$admin_aziend["ragso2"].' '.$title;
    $subtitle .= ' '.$admin_aziend["indspe"].' '.$admin_aziend["capspe"].' '.$admin_aziend["citspe"].
                 ' ('.$admin_aziend["prospe"].') tel. <a href="tel:'.$admin_aziend["telefo"].'" >'.
                 $admin_aziend["telefo"].'</a> Partita IVA '.$admin_aziend["pariva"];
    // inizio creazione HEAD   
    $html='<!DOCTYPE html>
        <html dir="ltr" lang="it">
        <head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    $html .='<title>'.$title."</title>\n";
    $html .= '<meta content="'.$author.'" name="author">';
    $html .= '<meta content="'.$keywords.'" name="keywords">';
    $html .='<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
             <link rel="stylesheet" href="gazie_site.css" type="text/css" />
             <script type="text/javascript" src="gazie_site_boxover.js"></script>
             </head>';
    // fine creazione HEAD

    // inizio creazione BODY
    $html .='<body>
                <div id="header_cont">
                  <div class="header">
                    <h1><a href="index.html"><img alt="Company Logo" src="gazie_site_logo.'.$mime_logo.'" height="45"></a> '.
                    $title.'
                    </h1>
                    <h2>'.$subtitle.'</h2>      
                  </div>
                </div>
                <div id="menu">   
                    <ul class="gazie_menu">
                    ';

    // creo la barra del menu              
    foreach($pages_name_and_descri as $v) {
        if ($v['name']== substr($data['var'],13)){
            $html .='<li class="current"><a href="'.$v['name'].'.html"><b>'.$v['descri'].'</b></a></li>
                    ';
        } else {
            $html .= '<li><a href="'.$v['name'].'.html"><b>'.$v['descri'].'</b></a></li>
                    ';            
        }

    }
    
    $html .= '   </ul>
                </div>
                ';
    // creo il corpo della pagina
    $html .= '<div id="container">
      <div class="content">
        <h2>'.$data['description'].'</h2>        
    ';
    $html .= $data['data'];
    $html .= '</div>
      </div>
    ';
    // fine corpo
    $html .='</div><!-- chiude div container role main --></body>';
    return $html;
}


function filesTransfer($server,$user,$pass,$remote_path,$company_id)
{
    // questa funzione trasferisce via FTP tutti i files della directory gazie_siteNNN sul webserver remoto

    // set up a connection or die
    $conn_id = @ftp_connect($server);
    if (!$conn_id){
        return array('0+',$server); // torno l'errore di server
    }
    // faccio il login
    if (!@ftp_login($conn_id, $user, $pass)) {
        ftp_close($conn_id);
        return array('1+',$user.' - '.$pass); // torno l'errore di login
    }
    //turn passive mode on
    ftp_pasv($conn_id, true);
    
    // faccio i cambi di direttorio
    $fn_exp = explode("/", $remote_path);
    array_pop($fn_exp);
    foreach( $fn_exp as $dir){
        if (!@ftp_chdir($conn_id, $dir)) { // non ho il direttorio
            @ftp_mkdir($conn_id, $dir); // allora lo creo
            if (!@ftp_chdir($conn_id, $dir)) { // non ho ancora il nuovo direttorio
                ftp_close($conn_id);
                return array('2+',$dir); // torno l'errore di direttorio inesistente
            }
        }
    }
    // apro e attraverso la directory locale
    if ($handle = opendir('gazie_site'.$company_id)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != ".." && $file != "images") {
                gaz_set_time_limit (30); // azzero il tempo altrimenti vado in fatal_error
                if (!@ftp_put($conn_id, $file, 'gazie_site'.$company_id.'/'.$file, FTP_BINARY)){
                    ftp_close($conn_id);
                    return array('3+',$file); // torno l'errore di file
                }
            }
        }
        closedir($handle);
    }
    $dir='images';
    // faccio l'upload delle immagini nella subdir 'images'
    if (!@ftp_chdir($conn_id, $dir)) { // non ho il direttorio
        @ftp_mkdir($conn_id, $dir); // allora lo creo
        if (!@ftp_chdir($conn_id, $dir)) { // non ho ancora il nuovo direttorio
            ftp_close($conn_id);
            return array('2+',$dir); // torno l'errore di direttorio inesistente
        }
    }
    // apro e attraverso la directory images
    if ($handle = opendir('gazie_site'.$company_id.'/images')) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                gaz_set_time_limit (30); // azzero il tempo altrimenti vado in fatal_error
                if (!@ftp_put($conn_id, $file, 'gazie_site'.$company_id.'/images/'.$file, FTP_BINARY)){
                    ftp_close($conn_id);
                    return array('3+',$file); // torno l'errore di file
                }
            }
        }
        closedir($handle);
    }
    ftp_close($conn_id);
    return false;
}

if (isset($_POST['ritorno'])) {   //se non e' il primo accesso
    $form['ritorno'] = $_POST['ritorno'];
    $form['server'] = addslashes(substr($_POST['server'],0,100));
    $form['user'] = addslashes(substr($_POST['user'],0,100));
    $form['pass'] = addslashes(substr($_POST['pass'],0,100));
    $form['path'] = addslashes(substr($_POST['path'],0,100));
    $form['listin'] = intval($_POST['listin']);
    $form['title'] = substr($_POST['title'],0,100);
    $form['subtitle'] = substr($_POST['subtitle'],0,100);
    $form['author'] = substr($_POST['author'],0,100);
    $form['keywords'] = substr($_POST['keywords'],0,400);
    $pn=1;
    foreach ($_POST['page'] as $k=>$v) {
        $v['var'] = 'website_page_'.substr($v['var'],0,20);
        $form['page'][$k] = $v;
        $pn++;
    }
    if (isset($_POST['addpage']) && $pn<11) {   // aggiungo una pagina ma solo se c'è capienza 
        $k='new'.$pn;
        $form['page'][$k]['var'] = 'website_page_pagenew'.$pn;
        $form['page'][$k]['description'] = 'Pagina '.$pn;
        $form['page'][$k]['data'] = '<p>NEW Pagina '.$pn.'</p>';
    }

    if (isset($_POST['Return'])) { // torno indietro
          header("Location: ".$form['ritorno']);
          exit;
    }
} else { //se e' il primo accesso
    $form['ritorno']=$_SERVER['HTTP_REFERER'];
    $r = gaz_dbi_get_row($gTables['company_config'],'var','server');
    $form['server'] = $r['val'];
    $r = gaz_dbi_get_row($gTables['company_config'],'var','user');
    $form['user'] = $r['val'];
    $r = gaz_dbi_get_row($gTables['company_config'],'var','pass');
    $form['pass'] = $r['val'];
    $r = gaz_dbi_get_row($gTables['company_config'],'var','path');
    $form['path'] = $r['val'];
    $form['listin'] = 1;
    $r = gaz_dbi_get_row($gTables['company_data'],'var','website_title');
    $form['title'] = $r ['data'];
    $r = gaz_dbi_get_row($gTables['company_data'],'var','website_subtitle');
    $form['subtitle'] = $r ['data'];
    $r = gaz_dbi_get_row($gTables['company_data'],'var','website_meta_author');
    $form['author'] = $r ['data'];
    $r = gaz_dbi_get_row($gTables['company_data'],'var','website_meta_keywords');
    $form['keywords'] = $r ['data'];
    $r_pages = gaz_dbi_dyn_query("*", $gTables['company_data'], "var LIKE 'website_page%' ","id");
    while ($pages = gaz_dbi_fetch_array($r_pages)) {
        $form['page'][$pages['id']]=$pages;
    }
}

require("../../library/include/header.php");
$script_transl = HeadMain(0,array(/*'tiny_mce/tiny_mce'*/
                                ));
/* echo "<script type=\"text/javascript\">";
foreach ($form['page'] as $k => $v) {
     echo "\n// Initialize TinyMCE with the new plugin and menu button
          tinyMCE.init({
          mode : \"specific_textareas\",
          theme : \"advanced\",
          forced_root_block : false,
          force_br_newlines : true,
          force_p_newlines : false,
          elements : \"datapage_".$k."\",
          plugins : \"table,advlink\",
          theme_advanced_buttons1 : \"mymenubutton,bold,italic,underline,separator,strikethrough,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,undo,redo,|,link,unlink,code,|,formatselect,forecolor,backcolor,|,tablecontrols\",
          theme_advanced_buttons2 : \"\",
          theme_advanced_buttons3 : \"\",
          theme_advanced_toolbar_location : \"external\",
          theme_advanced_toolbar_align : \"left\",
          editor_selector  : \"mceClass".$k."\",
          content_css : \"gazie_site.css\",
          });\n";
}
echo "</script>\n";*/
echo "<form method=\"POST\" autocomplete=\"off\">";
echo "<input type=\"hidden\" name=\"ritorno\" value=\"".$form['ritorno']."\">\n";
$gForm = new GAzieForm();
echo "<div align=\"center\" class=\"FacetFormHeaderFont\">".$script_transl['title']."</div>\n";
echo "<table class=\"Tlarge table table-striped table-bordered table-condensed table-responsive\">\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" colspan=\"2\" width=\"25%\">".$script_transl['server']." * </td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"server\" value=\"".$form['server']."\" align=\"right\" maxlength=\"100\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" colspan=\"2\">".$script_transl['user']." * </td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"user\" value=\"".$form['user']."\" align=\"right\" maxlength=\"100\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" colspan=\"2\">".$script_transl['pass']." * </td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"password\" name=\"pass\" value=\"".$form['pass']."\" align=\"right\" maxlength=\"20\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" colspan=\"2\">".$script_transl['path']." </td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"path\" value=\"".$form['path']."\" align=\"right\" maxlength=\"40\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" colspan=\"2\">".$script_transl['head_title']." </td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"title\" value=\"".$form['title']."\" align=\"right\" maxlength=\"100\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" colspan=\"2\">".$script_transl['head_subtitle']." </td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"subtitle\" value=\"".$form['subtitle']."\" align=\"right\" maxlength=\"100\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" colspan=\"2\">".$script_transl['author']." </td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"author\" value=\"".$form['author']."\" align=\"right\" maxlength=\"100\"  /></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "\t<td class=\"FacetFieldCaptionTD\" colspan=\"2\">".$script_transl['keywords']." </td>\n";
echo "\t<td class=\"FacetDataTD\"><input type=\"text\" name=\"keywords\" value=\"".$form['keywords']."\" align=\"right\" maxlength=\"200\"  /></td>\n";
echo "</tr>\n";

foreach ($form['page'] as $k=>$v) {
    $pagename=substr($v['var'],13);
    // creo l'array con i nomi e le descrizioni delle pagine
    $pages_name_and_descri[$k]=array('name'=>$pagename,'descri'=>$v['description']);
    
    echo "<tr>\n";
    echo '<input type="hidden" name="page['.$k.'][name]" value="'.$pagename.'" />';
    echo "\t<td class=\"FacetFieldCaptionTD\">";
    if ($pagename=='index' ) { // le pagine index è modificabile solo nel contenuto
        echo $pagename;
        echo '<input type="hidden" id="namepage_'.$k.'" name="page['.$k.'][var]" value="'.$pagename.'" />';
        echo "\t</td>\n";
        echo "\t<td class=\"FacetDataTD\">\n";
        echo '<input type="text" id="descpage_'.$k.'" name="page['.$k.'][description]" value="'.$v['description'].'" maxlength="12" />';
        echo "</td>\n";
        echo "\t<td>\n";
       // echo '<textarea id="datapage_'.$k.'" name="page['.$k.'][data]" class="mceClass'.$k.'" style="width:100%;height:100px;">'.$v['data']."</textarea>\n";
        echo '<textarea id="datapage_'.$k.'" name="page['.$k.'][data]" class="mceClass" style="width:100%;height:100px;">'.$v['data']."</textarea>\n";
        echo "</td>\n";
    } elseif ($pagename=='product' ) { // la pagina product è modificabile solo nel valore del listino
        echo $pagename;
        echo '<input type="hidden" id="namepage_'.$k.'" name="page['.$k.'][var]" value="'.$pagename.'" />';
        echo "<br />\t".$script_transl['listin']."\n";
        $gForm->variousSelect('listin',$script_transl['listin_value'],$form['listin'],'FacetSelect',false);
        echo "\t</td>\n";
        echo "\t<td class=\"FacetDataTD\">\n";
        echo '<input type="text" id="descpage_'.$k.'" name="page['.$k.'][description]" value="'.$v['description'].'" maxlength="12" />';
        echo "</td>\n";
        echo "\t<td>\n";
        //echo '<textarea id="datapage_'.$k.'" name="page['.$k.'][data]" class="mceClass'.$k.'" style="width:100%;height:100px;">'.$v['data']."</textarea>\n";
        echo '<textarea id="datapage_'.$k.'" name="page['.$k.'][data]" class="mceClass" style="width:100%;height:100px;">'.$v['data']."</textarea>\n";
        echo "</td>\n";
    } else {
        echo '<input type="text" id="namepage_'.$k.'" name="page['.$k.'][var]" value="'.$pagename.'" maxlength="12" />';
        echo "\t</td>\n";
        echo "\t<td class=\"FacetDataTD\">\n";
        echo '<input type="text" id="descpage_'.$k.'" name="page['.$k.'][description]" value="'.$v['description'].'" maxlength="12" />';
        echo "</td>\n";
        echo "\t<td>\n";
        //echo '<textarea id="datapage_'.$k.'" name="page['.$k.'][data]" class="mceClass'.$k.'" style="width:100%;height:100px;">'.$v['data']."</textarea>\n";
        echo '<textarea id="datapage_'.$k.'" name="page['.$k.'][data]" class="mceClass" style="width:100%;height:100px;">'.$v['data']."</textarea>\n";
        echo "</td>\n";
    }
    echo "</tr>\n";
}
echo '<tr>
        <td colspan=3 class="FacetDataTD" align="center">
        <input name="addpage" type="submit" value="'.strtoupper($script_transl['addpage']).'">
        </td>
    </tr>';

    
if (isset($_POST['Submit'])) { // conferma
    $noexist_dir = @mkdir('gazie_site'.$company_id);
    if ($noexist_dir) { // è stata creata la directory quindi andrò a copiarci il css e le immagini menu 
      copy('gazie_site.css','gazie_site'.$company_id.'/gazie_site.css');                                     
      copy('gazie_site_boxover.js','gazie_site'.$company_id.'/gazie_site_boxover.js');                                     
      copy('gazie_site_header.jpg','gazie_site'.$company_id.'/gazie_site_header.jpg');                                     
      copy('gazie_site_depliant.png','gazie_site'.$company_id.'/gazie_site_depliant.png');                                     
      copy('gazie_site_menu_bo.png','gazie_site'.$company_id.'/gazie_site_menu_bo.png');                                     
      copy('gazie_site_menu_le.png','gazie_site'.$company_id.'/gazie_site_menu_le.png');                                     
      copy('gazie_site_menu_ri.png','gazie_site'.$company_id.'/gazie_site_menu_ri.png');
      copy('gazie_site_noimage.png','gazie_site'.$company_id.'/gazie_site_noimage.png');
      /* ATTENZIONE !!! Questi file vengono trasferiti sulla directory specifica per ogni azienda
       * solo la prima volta pertanto chi vuole personalizzarli può farlo modificando a piacimento
       * solo quelli della directory gazie_siteNNN mentre i file DI BASE presenti sul modulo "inform"
       * vengono sovrascritti dagli aggiornamenti  
       */
      @mkdir('gazie_site'.$company_id.'/images');
    }
    // creo la favicon
    $file=fopen('gazie_data.tmp','w+');
    fwrite($file,$admin_aziend['image']);
    fclose($file);
    $imgd=getimagesize('gazie_data.tmp',$info);
    $mime_logo=substr($imgd['mime'],-3);
    $ico_lib = new PHP_ICO('gazie_data.tmp',array(64,64));
    $ico_lib->save_ico( 'gazie_site'.$company_id.'/favicon.ico' );
    copy('gazie_data.tmp','gazie_site'.$company_id.'/gazie_site_logo.'.$mime_logo);
    // creo i file delle pagine sul filesystem del server
    $db_data=$form['page'];
    foreach ($pages_name_and_descri as $k=>$v) {
        $file = fopen('gazie_site'.$company_id.'/'.$v['name'].".html", "w");
        if ( $v['name']=='product' && $form['listin']>0 ) { // alla pagina dei prodotti aggiungo la tabella degli articoli con o senza prezzi listino
            $form['page'][$k]['data'] .= createProductTable($company_id,$form['listin'],$admin_aziend['decimal_price'],$admin_aziend['colore']);
        }
        $html = createHtmlPage($form['title'],$form['subtitle'],$form['author'],$form['keywords'], $pages_name_and_descri,$form['page'][$k], $mime_logo);
        fwrite($file, $html);
        fclose($file);
    }
    $r=filesTransfer($form['server'],$form['user'],$form['pass'],$form['path'],$company_id);
    if (!$r){ //  tutto è andato a buon fine
        echo '<tr><td colspan=3 class="FacetDataTD">COMPLETED!!!</td></tr>';
        // per ricordare le credenziali di accesso
        gaz_dbi_put_row($gTables['company_config'],'var','server','val',$form['server']);
        gaz_dbi_put_row($gTables['company_config'],'var','user','val',$form['user']);
        gaz_dbi_put_row($gTables['company_config'],'var','pass','val',$form['pass']);
        gaz_dbi_put_row($gTables['company_config'],'var','path','val',$form['path']);
        // inserisco sul database le scelte fatte per l'head della pagina
        gaz_dbi_put_row($gTables['company_data'],'var','website_title','data',$form['title']);
        gaz_dbi_put_row($gTables['company_data'],'var','website_subtitle','data',$form['subtitle']);
        gaz_dbi_put_row($gTables['company_data'],'var','website_meta_author','data',$form['author']);
        gaz_dbi_put_row($gTables['company_data'],'var','website_meta_keywords','data',$form['keywords']);
        // infine inserisco le pagine
        foreach ($pages_name_and_descri as $k=>$v) {
            $db_data[$k]['ref']='';
            $ks=str_split($k,3);
            if ($ks[0]=='new') { // se ho aggiunto una pagina allora la devo inserire nel database
                company_dataInsert($db_data[$k]);
            } else {
                company_dataUpdate(array('id',$k),$db_data[$k]);
            }

        }
    } else {
        echo '<tr><td colspan=3 class="FacetDataTDred">ERROR!!!'.$r[1]." failed: ".$gForm->outputErrors($r[0],$script_transl['errors'])."</td></tr>\n";
    }
} else {
    echo "<tr>\n";
    echo "\t<td class=\"FacetFieldCaptionTD\">".$script_transl['sqn']."</td>";
    echo "\t </td>\n";
    echo "\t<td  class=\"FacetDataTD\">\n";
    echo '<input name="Return" type="submit" value="'.$script_transl['return'].'">';
    echo "\t </td>\n";
    echo "\t<td  class=\"FacetDataTD\" align=\"right\">\n";
    echo '<input name="Submit" type="submit" value="'.ucfirst($script_transl['submit']).'">';
    echo "\t </td>\n";
    echo "</tr>\n";
}
echo "</table>\n";
?>
</form>
<?php
require("../../library/include/footer.php");
?>