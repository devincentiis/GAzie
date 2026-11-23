<?php

if (isset($_SESSION['table_prefix'])) {

   $table_prefix = substr($_SESSION['table_prefix'],0,12);

} elseif (isset($_POST['tp'])) {

	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {

		$table_prefix = filter_var(substr($_POST['tp'],0,12),FILTER_SANITIZE_ADD_SLASHES);

	} else if ( defined('FILTER_SANITIZE_MAGIC_QUOTES') ) {

		$table_prefix = filter_var(substr($_POST['tp'],0,12),FILTER_SANITIZE_MAGIC_QUOTES);

	} else {

		$table_prefix = addslashes(substr($_POST['tp'],0,12));

	}

} elseif(isset($_GET['tp'])) {

	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {

		$table_prefix = filter_var(substr($_GET['tp'],0,12),FILTER_SANITIZE_ADD_SLASHES);

	} else if ( defined('FILTER_SANITIZE_MAGIC_QUOTES') ) {

		$table_prefix = filter_var(substr($_GET['tp'],0,12),FILTER_SANITIZE_MAGIC_QUOTES);

	} else {

		$table_prefix = addslashes(substr($_GET['tp'],0,12));

	}

} else {

	if ( defined('FILTER_SANITIZE_ADD_SLASHES') ) {

		$table_prefix = filter_var(substr($table_prefix,0,12),FILTER_SANITIZE_ADD_SLASHES);

	} else if ( defined('FILTER_SANITIZE_MAGIC_QUOTES') ) {

		$table_prefix = filter_var(substr($table_prefix,0,12),FILTER_SANITIZE_MAGIC_QUOTES);

	} else {

		$table_prefix = addslashes(substr($table_prefix,0,12));

	}

}
$langs = gaz_dbi_dyn_query("*", $table_prefix.'_languages',1);
while ($lang = gaz_dbi_fetch_assoc($langs)) {
  $namlang = strtolower($lang['title_native']);
  $rsm1 = gaz_dbi_dyn_query("*", $table_prefix.'_module',1,'weight ASC');
  while ($m1 = gaz_dbi_fetch_assoc($rsm1)) {
    include("../../modules/".$m1['name']."/menu.".$namlang.".php");
    $m2_data = $transl[$m1['name']]['m2'];
    $m3_data = (isset($transl[$m1['name']]['m3'])) ? $transl[$m1['name']]['m3'] : false;
    $rsm2 = gaz_dbi_dyn_query("*", $table_prefix.'_menu_module','id_module = '.$m1['id'],'weight ASC');
    while ($m2 = gaz_dbi_fetch_assoc($rsm2)) {
      // inserisco le traduzioni del menù m2 e m3 sulla nuova tabella gaz_menu_custom_data
      gaz_dbi_query("INSERT INTO ".$table_prefix.'_menu_custom_data'." ( id_menu_module,id_menu_script,link,label,descri,lang_id ) VALUES (".$m2['id'].",0,'".$m2['link']."','".addslashes($m2_data[$m2['translate_key']][0])."','".addslashes($m2_data[$m2['translate_key']][1])."',".$lang['lang_id'].")");
      if ( $m3_data ) {
        $rsm3 = gaz_dbi_dyn_query("*", $table_prefix.'_menu_script','id_menu = '.$m2['id'],'weight ASC');
        while ($m3 = gaz_dbi_fetch_assoc($rsm3)) {
          gaz_dbi_query("INSERT INTO ".$table_prefix.'_menu_custom_data'." ( id_menu_module,id_menu_script,link,label,descri,lang_id ) VALUES (".$m2['id'].",".$m3['id'].",'".$m3['link']."','".addslashes($m3_data[$m3['translate_key']][0])."','".addslashes($m3_data[$m3['translate_key']][1])."',".$lang['lang_id'].")");
        }
      }
    }
  }
}
?>
