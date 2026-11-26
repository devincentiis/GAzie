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
  $form['lang_id'] = isset($_GET['lang_id']) ? intval($_GET['lang_id']) : $usr_lang['lang_id'];
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
if ($form['hidden_req'] == 'refresh_language') {
  echo
  "<script >
    const url = new URL(window.location.href);
    url.searchParams.set('lang_id', '".$form['lang_id']."');
    window.location.href = url.toString();
  </script>";
}
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

td.right-box.add-pointer {
  cursor: pointer;
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

<input type="hidden" name="hidden_req" id="hidden_req" value="<?= $form['hidden_req'] ?>" />
  <h4><?= $script_transl['title'] ?></h4>
  <ul>
    <li><b><?= $script_transl['add_item'] ?></b> ----------------</li>
    <li><b><?= $script_transl['upd_item'] ?></b> -------------.</li>
    <li><b><?= $script_transl['del_item'] ?></b> -------------.</li>
    <li><b><?= $script_transl['rst_item'] ?></b> ---------------.</li>
  </ul>

<div id="dialog_editmenu" title="Modifica Voce Menu" style="display:none;">
  <p class="bg-info"><small>id_menu_module: <span id="dialog_id_menu_module"></span> - id_menu_script: <span id="dialog_id_menu_script"></span></small></p>
  <input type="hidden" id="cmd_id" name="cmd_id">
  <input type="hidden" id="cmd_link" name="cmd_link">
  <input type="hidden" id="cmd_label" name="cmd_label">
  <input type="hidden" id="cmd_descri" name="cmd_descri">
  <input type="hidden" id="edit_id_script" name="id_menu_script">
  <input type="hidden" id="edit_id_module" name="id_menu_module">
  <input type="hidden" id="dialog_modname" name="dialog_modname">
  <input type="hidden" id="dialog_status" name="dialog_status">
  <div class="form-group">
    <label for="edit_label">Label</label>
    <input type="text" id="edit_label" name="label" class="form-control" placeholder="">
  </div>
  <div class="form-group">
    <label for="edit_link">Link</label>
    <input type="text" id="edit_link" name="link" class="form-control" placeholder="">
  </div>
  <div class="form-group">
    <label for="edit_descri">Descrizione</label>
    <input type="text" id="edit_descri" name="descri" class="form-control" placeholder="">
  </div>
</div>
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
              <h4 class="card-title" <?= 'style="background-color: '.$bgcol.'; color: #000;"' ?>><img src="../<?= $k1.'/'.$k1 ?>.svg" height=40 > <?= $v1['file_menu']['name'] ?></h4>
            </div>
            <div class="card-body p-0">
        <?php
        $nr_sc = count($v1['scripts'])-1;
        $nr_db = count($v1['db_menu'])-1;
        //$max_r = max($nr_sc,$nr_db);
        $prev_id_script = 9999;
        for ($i = 0; $i <= $nr_db; $i++) {
          $custom_menu_data = $v1['custom_menu'][$i]; // dati personalizzati, provenienti dalla tabella del db
          $cmd_data = $custom_menu_data ? ' cmd_id='.$custom_menu_data['id'].' cmd_label="'.$custom_menu_data['label'].'" cmd_link="'.$custom_menu_data['link'].'" cmd_descri="'.$custom_menu_data['descri'].'"' : ' cmd_id=0 cmd_label="" cmd_link="" cmd_descri=""';
          if ($v1['db_menu'][$i]['id_menu_script'] >= 1 || ( $v1['db_menu'][$i]['id_menu_script'] == 0 && ( !isset($v1['db_menu'][($i+1)]['id_menu_script']) || $v1['db_menu'][($i+1)]['id_menu_script'] == 0 ) ) ) {
            $eye = '<i class="glyphicon glyphicon-eye-'.($v1['db_menu'][$i]['status'] >= 1 ? 'open text-success' : 'close text-danger').'"></i>';
          } else {
            $eye = '';
            $v1['db_menu'][$i]['status'] = 9; // indico a dialog che non deve mettere il bottone per cambiare lo status
          }
          $id_script = $v1['db_menu'][$i]['id_menu_script'];
          //var_dump($v1['db_menu'][$i]);
          $name_script = substr($v1['db_menu'][$i]['url_script_basename'],0,-4);
          $elem_li = '<li title="'.$v1['db_menu'][$i]['file_title'].' url: '.$v1['db_menu'][$i]['link'].'" id_menu_script='.$id_script.' vis-status='.$v1['db_menu'][$i]['status'].' id_menu_module='.$v1['db_menu'][$i]['id_menu_module'].$cmd_data.'><span class="item" script-connector="'.$k1.'-'.$name_script.'">'.$v1['db_menu'][$i]['file_label'].' </span> <button type="button" class="btn btn-xs btn-edit"><i class="glyphicon glyphicon-edit"></i></button> '.$eye;
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
              <h4 id="scriptlist-<?= $k1 ?>" class="script-modulo card-title" <?= 'style="background-color: '.$bgcol.'; color: #000;"' ?>>Directory: modules/<?= $k1 ?></h4>
            </div>
            <div class="card-body">
              <table class="table" style="background-color:  background-color: rgba(128, 128, 128, 0.35);">
        <?php
        for ($j = 0; $j <= $nr_sc; $j++) {
          if ($j % 3 == 0) { echo '<tr>'; }
          echo '<td class="item right-box" script-connector="'.$k1.'-'.substr($v1['scripts'][$j],0,-4).'">'.$v1['scripts'][$j].'</td>';
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

  $("#dialog_editmenu").dialog({
    autoOpen: false,
    modal: false,
    width: 400,
    open: function(event, ui) {
      var $btn = $(this).data("triggerBtn");
      var $li = $btn.closest("li");

      $li.children("span.item").addClass("evidenziato");

      var idCmd = $li.attr("cmd_id");
      var labelCmd = $li.attr("cmd_label");
      var linkCmd = $li.attr("cmd_link");
      var descriCmd = $li.attr("cmd_descri");
      var idScript = $li.attr("id_menu_script");
      var idModule = $li.attr("id_menu_module");
      var visstatus = $li.attr("vis-status");
      var label = $li.children("span.item").text().trim();
      var titleAttr = $li.attr("title") || "";
      var partiTitle = titleAttr.split(" url: ");
      var descri = partiTitle[0] ? partiTitle[0].trim() : "";
      var link = partiTitle[1] ? partiTitle[1].trim() : "";
      $("span#dialog_id_menu_script").html(idScript);
      $("span#dialog_id_menu_module").html(idModule);
      $("#edit_id_script").val(idScript);
      $("#edit_id_module").val(idModule);
      $("#dialog_status").val(visstatus);

      var $btnVis = $("#btn_toggle_vis");
      if (visstatus == 1) {
        $btnVis
          .removeClass("btn-danger")
          .removeClass("btn-default")
          .addClass("btn-success")
          .html('<div title="Nascondi"> Visibile <i class="glyphicon glyphicon-eye-open"></i></div>');
      } else if (visstatus == 9) { // è un m2 non vuoto
        $btnVis
          .removeClass("btn-danger")
          .removeClass("btn-success")
          .addClass("btn-default")
          .html('<div title="Non vuoto: non modificabile"><i class="glyphicon glyphicon-eye-open"></i></div>');
      } else {
        $btnVis
          .removeClass("btn-success")
          .removeClass("btn-default")
          .addClass("btn-danger")
          .html('<div title="Rendi visibile">Nascosto <i class="glyphicon glyphicon-eye-close"></i></div>');
      }


      $("#edit_label").val(labelCmd);
      $("#edit_link").val(linkCmd);
      $("#edit_descri").val(descriCmd);
      $("#edit_label").attr("placeholder", label);
      $("#edit_link").attr("placeholder", link);
      $("#edit_descri").attr("placeholder", descri);
      var $targetPos = $(this).data("targetPos");
      if ($targetPos && $targetPos.length) {
        $(this).dialog("option", "position", {
          my: "right top",
          at: "left top",
          of: $targetPos,
          collision: "fit none" // Impedisce che venga spinto troppo in alto fuori dall'allineamento
        });

        // Fa scorrere la pagina in modo fluido fino all'altezza del dialog (con 20px di margine in alto)
        var $dialogWidget = $(this).dialog("widget");
        var dialogTop = $dialogWidget.offset().top;
        var dialogBottom = dialogTop + $dialogWidget.outerHeight();
        var windowTop = $(window).scrollTop();
        var windowBottom = windowTop + $(window).height();

        // Esegue lo scroll solo se il dialog esce dal bordo inferiore (o superiore) della finestra
        if (dialogBottom > windowBottom || dialogTop < windowTop) {
          $("html, body").animate({
            scrollTop: dialogTop - 20
          }, 300);
        }
      }
      var modname = $targetPos.attr('id').replace('scriptlist-','');
      $('td.right-box[script-connector^="' + modname + '"]').addClass("add-pointer");
    },
    close: function(event, ui) {
      $('td.right-box[script-connector]').removeClass("add-pointer");
      var $btn = $(this).data("triggerBtn");
      if ($btn) {
        $btn.closest("li").children("span.item").removeClass("evidenziato");
      }
    },
    buttons: [
      {
        id: "btn_toggle_vis",
        text: "Visibilità",
        class: "btn",
        click: function() {
          var $dialog = $(this);
          $("#edit_label").val('');
          $("#edit_link").val('');
          $("#edit_descri").val('');
          $("#dialog_modname").val('');
          var sts = $("#dialog_status").val();
          var data = JSON.stringify({
            id_menu_script: $("#edit_id_script").val(),
            id_menu_module: $("#edit_id_module").val(),
            status_toggle: sts
          });
          if (sts < 9) { // non faccio il toggle in caso di m2 non vuoto
            $.post("menu_ajax.php", data, function(risposta) {
              $dialog.dialog("close");
              window.location.reload();
            });
          }
        }
      },
      {
        text: "Annulla/chiudi",
        class: "btn btn-default",
        click: function() {
          $("#edit_label").val('');
          $("#edit_link").val('');
          $("#edit_descri").val('');
          $("#dialog_modname").val('');
          $("#dialog_status").val('');
          $(this).dialog("close");
        }
      },
      {
        text: "Conferma",
        class: "btn btn-warning",
        click: function() {
          var $dialog = $(this);
          var data = {
            lang_id: $('#lang_id').val(),
            id_menu_script: $("#edit_id_script").val(),
            id_menu_module: $("#edit_id_module").val(),
            label: $("#edit_label").val(),
            link: $("#edit_link").val(),
            descri: $("#edit_descri").val()
          };
          console.log("Dati pronti per il salvataggio:", data);
          /*
          $.post("menu_ajax.php", data, function(risposta) {
            $dialog.dialog("close");
            window.location.reload();
          });
          */
        }
      }
    ]
  });

  $('td.right-box[script-connector]').click(function() {
    if ($("#dialog_editmenu").dialog("isOpen")) {
      var dia_modname = $("#dialog_modname").val();
      var scr_modname = $(this).attr('script-connector').split('-')[0].trim();
      if ( dia_modname == scr_modname ) {
        var nomeScript = $(this).text().trim();
        $("#edit_link").val(nomeScript);
      }
    }
  });

  // Apertura dialog al click
  $(document).on("click", ".btn-edit", function(e) {
    if ($("#dialog_editmenu").dialog("isOpen")) {
      $("#dialog_editmenu").dialog("close");
      $("#edit_label").val('');
      $("#edit_link").val('');
      $("#edit_descri").val('');
      $("#dialog_modname").val('');
    }
    var mod = $(this).closest('.lista-modulo').attr('id');
    var id_position = mod.replace('sortbox','scriptlist');
    var modname = mod.replace('sortbox-','');
    $("#dialog_modname").val(modname);
    e.preventDefault();
    $("#dialog_editmenu").data("triggerBtn",$(this)).data("targetPos",$("#" + id_position)).dialog("open");;
  });

});
</script>
<?php
require("../../library/include/footer.php");
