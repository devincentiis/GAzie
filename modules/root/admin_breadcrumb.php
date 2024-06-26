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

require("../../library/include/datlib.inc.php");

$admin_aziend = checkAdmin();
if ( isset($_GET['url'])) {
    $form['file'] = $_GET['url'];
}
if ( isset($_POST['submit']) ){
    $form['title'] = $_POST['title'];
    $form['link'] = $_POST['link'];
}

if ( isset($_POST['add']) ) {
    $form['link'] = "../../".$form['file'];
    gaz_dbi_table_insert('breadcrumb',$form);
    header("Location: ../root/admin_breadcrumb.php?url=".$form['file'] );
}
if ( isset( $_POST['submit'] ) && $_POST['submit']=="save" ) {
    foreach ( $form['title'] as $val => $v ) {
        gaz_dbi_table_update ("breadcrumb", array ("0"=>"id_bread","1"=>$val), array("titolo"=>$v) );
    }
    foreach ( $form['link'] as $val => $v ) {
        gaz_dbi_table_update ("breadcrumb", array ("0"=>"id_bread","1"=>$val), array("link"=>$v) );
    }
    header("Location: ../root/admin_breadcrumb.php?url=".$form['file'] );
}
if ( isset( $_GET['del']) && $_GET['del']>0 ) {
    gaz_dbi_del_row($gTables['breadcrumb'], 'id_bread', $_GET['del'] );
    header("Location: ../root/admin_breadcrumb.php?url=".$form['file'] );
}

require("../../library/include/header.php");
$script_transl = HeadMain();

?>
<form method="POST" name="form">
    Posizione corrente <input type="text" name="file" value="<?php echo $form['file']; ?>">
    <br><br>il primo rigo di questa pagina è il titolo che compare alla sinistra dei tasti rapidi
    <br>esempio : Emetti DDT	../../modules/vendit/admin_docven.php?Insert&tipdoc=DDT
    <br><br>
    <?php
    $res_pos = gaz_dbi_dyn_query("*", $gTables['breadcrumb'], ' file="'. $form['file'].'"', ' id_bread',0,999);
    if ( gaz_dbi_num_rows($res_pos)>0 ) {
        echo "<table>";
        echo '<tr></tr><th>titolo</th><th>link</th></tr>';
        while ( $row = gaz_dbi_fetch_array($res_pos) ) {           
            echo '<tr>';           
            echo '<td> <input  type="text" name="title['.$row['id_bread'].']" value="'.$row['titolo'].'"> </td>';
            echo '<td> <input type="text" name="link['.$row['id_bread'].']" value="'.$row['link'].'"> </td>';
            echo '<td><a href="admin_breadcrumb.php?url='.$form['file'].'&del='.$row['id_bread'].'" class="btn btn-default btn-xs">del</td>';
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<br>no data<br><br>";
    }
    echo '<input type="submit" class="btn btn-default btn-xs" name="submit" value="save" autofocus>';
    echo '<input type="submit" class="btn btn-default btn-xs" name="add" value="add">';
    ?>
</form>
<?php
require("../../library/include/footer.php");
?>