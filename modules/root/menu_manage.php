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
$admin_aziend = checkAdmin(9);
$gForm = new GAzieForm();

function coloreChiaroDaStringa(string $stringa): string {
  $hash = md5($stringa);
  $r = hexdec(substr($hash, 0, 2));
  $g = hexdec(substr($hash, 2, 2));
  $b = hexdec(substr($hash, 4, 2));
  $r = 205 + ($r % 41);
  $g = 205 + ($g % 41);
  $b = 205 + ($b % 41);
  return sprintf("#%02x%02x%02x", $r, $g, $b);
}

if (isset($_POST['hidden_req'])) {
  $form['lang_id'] = intval($_POST['lang_id']);
  $form['hidden_req'] = substr($_POST['hidden_req'],0,20);
} else {
  $usr_lang = gaz_dbi_get_row($gTables['languages'],'title_native',ucfirst($admin_aziend['lang']));
  $form['lang_id'] = $usr_lang['lang_id'];
  $form['hidden_req'] = '';
}

// ripristino con il valore presente in menu.italian.php
if (isset($_POST['default']) && preg_match('#^/[a-z0-9_]+/[a-z0-9_]+\.php(\?[a-z0-9_&=.%-]*)?$#i', $_POST['default'])) {
  header('Location: menu_manage.php');
  exit;
}


// modifica di nome e link di un preferito dell'utente: la riga diventa un form (?modifica=posizione nell'elenco)
$pattern_link = '#^/[a-z0-9_]+/[a-z0-9_]+\.php(\?[a-z0-9_&=.%-]*)?$#i';
$errore = '';
if (isset($_GET['modifica']) && isset($mine[(int) $_GET['modifica']])) {
  $editing = ['old' => $mine[(int) $_GET['modifica']]['link'], 'link' => $mine[(int) $_GET['modifica']]['link'], 'name' => (string) $mine[(int) $_GET['modifica']]['name'], 'code' => (string) ($mine[(int) $_GET['modifica']]['code'] ?? '')];
}

require("../../library/include/header.php");
$script_transl = HeadMain();
?>
<style>
/* Sblocca lo sticky dentro il contenitore principale di AdminLTE 3 */
html body .wrapper {
  overflow-x: clip !important;
  overflow-y: visible !important;
}
h4.card-title {
  min-height: 50px;
  display: flex;
  align-items: center;
}
/* Rende il box fluido lungo l'intera altezza della riga */
.sticky-box-adminlte {
  position: -webkit-sticky;
  position: sticky;
  top: 70px; /* Regolare in base all'altezza della navbar */
  z-index: 100;
}
@media (min-width: 768px) {
  .sticky-box-adminlte {
    position: -webkit-sticky;
    position: sticky;
    top: 70px;
    z-index: 100;
  }
}
.table > tbody > tr > td.right-box {
  padding: 0px;
  font-size: 11px;
}
.item {
  transition: background 0.2s;
}
.item.evidenziato {
  background: #ffeb3b88; /* Giallo evidenziatore */
}
/* Seleziona le ul dentro i li (secondo livello) */
ul li ul {
    min-height: 30px; /* Impedisce al contenitore di collassare a 0px */
    padding: 5px;     /* Crea un'area interna facile da centrare */
}

/* Opzionale: evidenzia la zona quando è vuota per facilitare l'utente */
ul li ul:empty {
    border: 1px dashed #ccc;
    background-color: #f9f9f9;
}

</style>
<form method="POST" name="myform">
<input type="hidden" name="hidden_req" value="<?= $form['hidden_req'] ?>" />
    <h4>Come funziona la modifica del menù</h4>
    <ul>
      <li><b>Aggiunta voce:</b> ----------------</li>
      <li><b>Modifica voce:</b> -------------.</li>
      <li><b>Elimina voce:</b> -------------.</li>
      <li><b>Ripristina default:</b> ---------------.</li>
    </ul>
<div class="container-fluid">
    <div class="text-center"><b>
    <?php
    $lang = gaz_dbi_get_row($gTables['languages'],'lang_id',$form['lang_id']);
    echo $lang['description'].': </b>';
    $gForm->selectLanguage('lang_id', $form['lang_id'],false,'lang-select','refresh_language');
    ?>
    </b></div>


    <?php
      $menu_data = getMenuData($form['lang_id']);
      foreach ($menu_data as $k1 => $v1) {
        $bgcol=coloreChiaroDaStringa($k1);
        ?>
        <div class="row align-items-start mb-5">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title " <?= 'style="background-color: '.$bgcol.'; color: #000;"' ?>><img src="../<?= $k1.'/'.$k1 ?>.svg" height=40 > <?= $v1['file_menu']['name'] ?></h4>
            </div>
            <div class="card-body p-0">
        <?php
        $nr_sc = count($v1['scripts'])-1;
        $nr_db = count($v1['db_menu'])-1;
        //$max_r = max($nr_sc,$nr_db);
        $prev_id_script = 9999;
        for ($i = 0; $i <= $nr_db; $i++) {
          $id_script = $v1['db_menu'][$i]['id_menu_script'];
          //var_dump($v1['db_menu'][$i]);
          $name_script = substr($v1['db_menu'][$i]['url_script_basename'],0,-4);
          $elem_li = '<li title="'.$v1['db_menu'][$i]['file_title'].' url: '.$v1['db_menu'][$i]['link'].'" id_menu_script='.$id_script.'  id_menu_module='.$v1['db_menu'][$i]['id_menu_module'].'><span class="item" script-connector="'.$k1.$name_script.'">'.$v1['db_menu'][$i]['file_label'].' </span> <a class="btn btn-xs btn-edit" href="" > <i class="glyphicon glyphicon-edit"></i></a>';
          if ( $id_script == 0 && $prev_id_script == 9999 ){
            // primo menu livello 2
            echo "<!-- INIZIO SPECCHIETTO ORDINABILE-->\n".'<ul id="sortbox-'.$k1.'" class="lista-modulo">'.$elem_li."<ul>\n";
          } else if ( $id_script < 1 ) {
            // successivi livello 2 - chiudo e riapro ul
            echo "</ul></li>\n".$elem_li."<ul>\n";
          } else {
            // menu livello 3
            echo $elem_li."</li>";
          }
          $prev_id_script = $id_script;
        }
        ?>
                </li>
              </ul>
              <!-- FINE SPECCHIETTO ORDINABILE-->
            </div>
          </div>
        </div>
        <div class="col-md-6 sticky-box-adminlte">
          <div class="card card-primary">
            <div class="card-header">
              <h4 class="card-title" <?= 'style="background-color: '.$bgcol.'; color: #000;"' ?>>Directory: modules/<?= $k1 ?></h4>
            </div>
            <div class="card-body">
              <table class="table" style="background-color:  background-color: rgba(128, 128, 128, 0.35);">
        <?php
        for ($j = 0; $j <= $nr_sc; $j++) {
          if ($j % 3 == 0) { echo '<tr>'; }
          echo '<td class="item right-box" script-connector="'.$k1.substr($v1['scripts'][$j],0,-4).'">'.$v1['scripts'][$j].'</td>';
          if (($j+1) % 3 == 0 || $j == $nr_sc) { echo '</tr>'; }
        }
        ?>
              </table>
            </div>
          </div>
        </div>
      </div>
      <?php
      }
      ?>
</div>
</form>
<script>
$(function () {

  $('.item').mouseenter(function() {
    var cat = $(this).attr('script-connector');
    $('.item[script-connector="' + cat + '"]').addClass('evidenziato');
  }).mouseleave(function() {
    var cat = $(this).attr('script-connector');
    $('.item[script-connector="' + cat + '"]').removeClass('evidenziato');
  });

  // 1. Drag & drop per le voci di secondo livvello m2 (trascinabili solo all'interno del modulo stesso
  $(".lista-modulo").sortable({
    items: "> li",
    tolerance: "pointer",
    cursor: "grabbing",
    dropOnEmpty: true,
    placeholder: "ui-state-highlight",

    start: function(event, ui) {
      $(this).find("> li > ul").hide();
      $(this).sortable("refreshPositions");
    },

    stop: function(event, ui) {
      $(this).find("> li > ul").show();
    },

    // Scatta solo se l'ordine dei m2 è effettivamente cambiato
    update: function(event, ui) {
      var cat = $(this).attr('script-connector');
      $('.item[script-connector="' + cat + '"]').removeClass('evidenziato');
      salvaOrdine($(this), 'm2');
    }
  });

  // 2. Drag & drop per le voci di terzo livello m3 (isolate per riquadro)
  $(".lista-modulo").each(function(index) {
    var gruppoId = "gruppo-" + index;
    var sottoliste = $(this).find("> li > ul");

    sottoliste.attr("data-gruppo", gruppoId);

    sottoliste.sortable({
      items: "> li",
      cursor: "grabbing",
      connectWith: "ul[data-gruppo='" + gruppoId + "']",
      dropOnEmpty: true,
      tolerance: "pointer",
      placeholder: "ui-state-highlight",

      // Scatta quando l'ordine dei figli cambia o quando un m3 cambia m2
      update: function(event, ui) {
        var cat = $(this).attr('script-connector');
        $('.item[script-connector="' + cat + '"]').removeClass('evidenziato');
        if (this === ui.item.parent()[0]) {
          salvaOrdine($(this), 'm3');
        }
      }
    });
  });

  // 3. La tua funzione di salvataggio AJAX
  function salvaOrdine(listaContainer, mode) {
    var lang_id = $('#lang_id').val();
    var modattr = listaContainer.closest('.lista-modulo').attr('id');
    var module_name = modattr.replace('sortbox-', '');
    let listaNuova = [];
    if ( mode == 'm3' ) {
      listaNuova = $('.lista-modulo[id="' + modattr + '"] li').map(function() {
          return {
              id_menu_script: $(this).attr('id_menu_script'),
              id_menu_module: $(this).attr('id_menu_module')
          };
      }).get();
    } else {
      listaNuova = $('.lista-modulo[id="' + modattr + '"] li[id_menu_script="0"]').map(function() {
          return {
              id_menu_script: $(this).attr('id_menu_script'),
              id_menu_module: $(this).attr('id_menu_module')
          };
      }).get();
    }
    //console.log(listaNuova);

    $.ajax({
      url: 'menu_ajax.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        mode: mode,
        modname: module_name,
        lang: lang_id,
        elem: listaNuova
      }),
      success: function(risposta) {
        console.log('Ordine salvato con successo:', risposta);
        console.log(modattr);
        window.location.reload();
      },
      error: function(errore) {
        console.error('Errore durante il salvataggio:', errore);
        alert('Impossibile salvare il nuovo ordine.');
      }
    });
  }

});
</script>
<?php
require("../../library/include/footer.php");
