<?php
/*
   --------------------------------------------------------------------------
  GAzie - MODULO 'VACATION RENTAL'
  Copyright (C) 2022-2023 - Antonio Germani, Massignano (AP) - telefono +39 340 50 11 912
  (https://www.programmisitiweb.lacasettabio.it)

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
//require("lib.function.php");
$admin_aziend = checkAdmin(8);
$lang=gaz_dbi_get_row($gTables['languages'], 'lang_id', intval($admin_aziend['id_language']))['sef'];
$gForm = new GazieForm();
$genclass="active";
$feedclass="";
$remclass="";
$pointclass="";
$otaclass="";

// Se la richiesta è una chiamata AJAX per ottenere tourtax
if (isset($_GET['id_fornitore'])) {
  global $gTables;
    $id_fornitore = $_GET['id_fornitore'];
    // Query per ottenere il campo custom_field del fornitore
    $sql = "
        SELECT custom_field
        FROM {$gTables['agenti']}
        WHERE id_fornitore = '$id_fornitore'
    ";
    // Esegui la query usando la tua funzione gaz_dbi_query()
    $result = gaz_dbi_query($sql);
    // Verifica se la query ha restituito un risultato
    if ($result && gaz_dbi_num_rows($result) > 0) {
        $row = gaz_dbi_fetch_array($result);
        $custom_field_json = $row['custom_field'];
        // Decodifica il JSON
        $json_data = (isset($custom_field_json) && $custom_field_json !== null) ? json_decode($custom_field_json, true) : [];
        // Imposta il valore di tourtax a default "NO"
        $tourtax_value = "NO";
        // Verifica se la chiave vacation_rental esiste e se contiene tourtax
        if (isset($json_data['vacation_rental']) && isset($json_data['vacation_rental']['tourtax'])) {
            $tourtax_value = strtoupper($json_data['vacation_rental']['tourtax']) == "SI" ? "SI" : "NO";
        }
        // Restituisci il valore di tourtax
        echo $tourtax_value;
    } else {
        echo "NO"; // Se non trovato, restituisci NO
    }

    exit(); // Termina lo script, nessun output HTML aggiuntivo dopo l'ajax
}

if (isset($_POST['addElement'])){// se è stato richiesto di inserire un nuovo elemento feedback
  $genclass="";
  $feedclass="active";
  if (strlen($_POST['newElement'])>2){// se non è vuoto posso inserire
    $table = 'rental_feedback_elements';
    $set['element']=  mysqli_real_escape_string($link,substr($_POST['newElement'],0,64));
    $set['description']=  mysqli_real_escape_string($link,substr($_POST['description'],0,100));
    $set['facility']=  intval($_POST['newFacility']);
    $set['status']=  "CREATED";
    $columns = array('element', 'description', 'facility', 'status');
    tableInsert($table, $columns, $set);
  }
}

if (isset($_POST['submitOTA']) && $_POST['submitOTA']=='ota'){// se è stato richiesto di registrare le impostazioni OTA
// echo "<pre>",print_r($_POST),"</pre>";die;
  $row = gaz_dbi_get_row($gTables['agenti'], 'id_agente', $_POST['agente']);

  if ($row !== null && isset($row['custom_field'])) {
      $custom_field = $row['custom_field'];
  } else {
      $custom_field = null; // o valore di default
  }
 if (isset($custom_field) ){// se c'è un il custom field json ne carico tutto il contenuto
  $data = json_decode($custom_field,true);
 }
 $data['vacation_rental']['tourtax']=$_POST['tourtax'];// inserisco/modifico il valore impostato nel form
 $form['custom_field'] = json_encode($data);
 gaz_dbi_put_row($gTables['agenti'], 'id_fornitore', intval($_POST['agente']), 'custom_field', $form['custom_field']);
}
if (isset($_POST['delElement']) && intval($_POST['delElement'])>0){// se è stato richiesto di cancellare un elemento feedback
  $genclass="";
  $feedclass="active";
  if (!gaz_dbi_get_row($gTables['rental_feedback_scores'], 'element_id', intval($_POST['delElement']))){// se l'elemento non è mai stato usato lo posso cancellare
    gaz_dbi_del_row($gTables['rental_feedback_elements'], 'id', intval($_POST['delElement']));
  }else{// altrimenti segnalo l'impossibilità
    echo 'Non posso cancellare l\'elemento perché ad esso risulta associato almeno un feedback';
  }
}
if (isset($_POST['updElement']) && intval($_POST['updElement'])>0){// se è stato richiesto di modificare un elemento feedback
  $genclass="";
  $feedclass="active";
  $upd=gaz_dbi_get_row($gTables['rental_feedback_elements'], 'id', intval($_POST['updElement']));
}
if (isset($_POST['SaveupdElement']) && intval($_POST['SaveupdElement'])>0){// se è stato richiesto di salvare la modifica di un elemento feedback
  $genclass="";
  $feedclass="active";
   $table = 'rental_feedback_elements';
    $set['element']=  mysqli_real_escape_string($link,substr($_POST['newElement'],0,64));
    $set['description']=  mysqli_real_escape_string($link,substr($_POST['description'],0,100));
    $set['facility']=  intval($_POST['newFacility']);
    $set['status']=  "MODIFIED";
    $columns = array('element', 'description', 'facility', 'status');
    $codice=array();
    $codice[0]="id";
    $codice[1]=intval($_POST['SaveupdElement']);
    $newValue = array('element'=>$set['element'], 'description'=>$set['description'], 'facility'=>$set['facility'],'status'=>$set['status']);
    tableUpdate($table, $columns, $codice, $newValue);
}


if (count($_POST) > 1 && !isset($_POST['addElement']) && !isset($_POST['delElement']) && !isset($_POST['updElement']) && !isset($_POST['SaveupdElement'])) {

  $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  foreach ($_POST as $k => $v) {
    $value=filter_var($v, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $key=filter_var($k, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    gaz_dbi_put_row($gTables['company_config'], 'var', $key, 'val', $value);
  }
  header("Location: settings.php?ok_insert");
  exit;
}

require("../../library/include/header.php");
$script_transl = HeadMain();

$general = gaz_dbi_dyn_query("*", $gTables['company_config'], " var LIKE 'vacation%'", ' id ASC', 0, 1000);
$feedbacks = gaz_dbi_query("SELECT * FROM ".$gTables['rental_feedback_elements']." LEFT JOIN " . $gTables['artico_group'] . " ON " . $gTables['rental_feedback_elements'] . ".facility = " . $gTables['artico_group'] . ".id_artico_group ORDER BY id ASC");
$reminders_in = gaz_dbi_dyn_query("*", $gTables['company_config'], " var LIKE 'reminvacation%'", ' id ASC', 0, 1000);
$reminders_pay = gaz_dbi_dyn_query("*", $gTables['company_config'], " var LIKE 'rempayvacation%'", ' id ASC', 0, 1000);
$point = gaz_dbi_dyn_query("*", $gTables['company_config'], " var LIKE 'point%'", ' id ASC', 0, 1000);

?>
<div align="center" class="FacetFormHeaderFont">
    <?php echo $script_transl['title']; ?><br>
</div>
<div class="panel panel-default gaz-table-form div-bordered">
  <div class="container-fluid">

<ul class="nav nav-pills">
  <li class="<?php echo $genclass; ?>"><a data-toggle="pill" href="#generale">Configurazione</a></li>
  <li class="<?php echo $feedclass; ?>"><a data-toggle="pill" href="#feedback"><b>Recensioni</b></a></li>
  <li class="<?php echo $remclass; ?>"><a data-toggle="pill" href="#reminder"><b>Promemoria</b></a></li>
  <li class="<?php echo $pointclass; ?>"><a data-toggle="pill" href="#point"><b>Punti</b></a></li>
  <li class="<?php echo $otaclass; ?>"><a data-toggle="pill" href="#ota"><b>OTA</b></a></li>
  <li style="float: right;"><div class="btn btn-warning" id="upsave">Salva</div></li>
</ul>
<?php

?>
    <div class="tab-content">

      <div id="generale" class="tab-pane fade in <?php echo $genclass; ?>">
        <form method="post" id="sbmt-form">
<?php     if (isset($_GET["ok_insert"])) { ?>
            <div class="alert alert-success text-center" role="alert">
                <?php echo "Le modifiche sono state salvate correttamente<br/>"; ?>
            </div>
          <?php }
          if (gaz_dbi_num_rows($general) > 0) {
            ?>
            <div class="row text-info bg-info">
              IMPOSTAZIONI GENERALI PER TUTTI GLI ALLOGGI E TUTTE LE STRUTTURE
            </div><!-- chiude row  -->

            <?php
            while ($r = gaz_dbi_fetch_array($general)) {
                ?>
                <div class="row">
                  <div class="form-group" >
                    <label for="input<?php echo $r["id"]; ?>" class="col-sm-5 control-label"><?php echo $r["description"]; ?></label>
                    <div class="col-sm-7">
                        <?php
                            ?>
                            <input type="<?php
                            if (strpos($r["var"], "psw") === false) {
                                echo "text";
                            } else {
                                echo "password";
                            }
                            ?>" class="form-control input-sm" id="input<?php echo $r["id"]; ?>" name="<?php echo $r["var"]; ?>" placeholder="<?php echo $r["var"]; ?>" value="<?php echo $r["val"]; ?>">
                    </div>
                  </div>
                </div><!-- chiude row  -->
                <?php
            }
          }

          ?>
          <div class="row">
              <div class="form-group" >
                  <label class="col-sm-5 control-label"></label>
                  <div  style="float: right;">
                      <button type="submit" class="btn btn-warning">Salva</button>
                  </div>
              </div>
          </div>

      </div><!-- chiude generale  -->


      <div id="reminder" class="tab-pane fade in <?php echo $remclass; ?>">

          <div class="row text-info bg-info">
              IMPOSTAZIONI PER INVIO DI E-MAIL PROMEMORIA (richiede cron-job della versione PRO)
          </div><!-- chiude row  -->
          <div class="row text-success">
              <b>Promemoria prima del check-in</b>
          </div><!-- chiude row  -->
            <?php
            while ($r = gaz_dbi_fetch_array($reminders_in)) {
                ?>
                <div class="row">
                  <div class="form-group" >
                    <label for="input<?php echo $r["id"]; ?>" class="col-sm-5 control-label"><?php echo $r["description"]; ?></label>
                    <div class="col-sm-7">
                        <?php
                            ?>
                            <input type="text" class="form-control input-sm" id="input<?php echo $r["id"]; ?>" name="<?php echo $r["var"]; ?>" placeholder="<?php echo $r["var"]; ?>" value="<?php echo $r["val"]; ?>">
                    </div>
                  </div>
                </div><!-- chiude row  -->
                <?php
            }
          ?>


          <div class="row text-success">
              <b>Promemoria pagamento caparra confirmatoria</b> (le prenotazioni con stato confermato si intendono con caparra pagata)
          </div><!-- chiude row  -->
           <?php
            while ($r = gaz_dbi_fetch_array($reminders_pay)) {
                ?>
                <div class="row">
                  <div class="form-group" >
                    <label for="input<?php echo $r["id"]; ?>" class="col-sm-5 control-label"><?php echo $r["description"]; ?></label>
                    <div class="col-sm-7">
                        <?php
                            ?>
                            <input type="text" class="form-control input-sm" id="input<?php echo $r["id"]; ?>" name="<?php echo $r["var"]; ?>" placeholder="<?php echo $r["var"]; ?>" value="<?php echo $r["val"]; ?>">
                    </div>
                  </div>
                </div><!-- chiude row  -->
                <?php
            }
          ?>

          <div class="row">
              <div class="form-group" >
                  <label class="col-sm-5 control-label"></label>
                  <div  style="float: right;">
                      <button type="submit" class="btn btn-warning">Salva</button>
                  </div>
              </div>
          </div>

      </div><!-- chiude reminder  -->

      <div id="point" class="tab-pane fade in <?php echo $pointclass; ?>">
        <div class="row text-info bg-info">
          <p>IMPOSTAZIONI PER FIDELIZZAZIONE A PUNTI</p>
        </div><!-- chiude row  -->

        <?php
            while ($r = gaz_dbi_fetch_array($point)) {
                ?>
                <div class="row">
                  <div class="form-group" >
                    <label for="input<?php echo $r["id"]; ?>" class="col-sm-5 control-label"><?php echo $r["description"]; ?></label>
                    <div class="col-sm-7">
                        <?php
                            ?>
                            <input type="text" class="form-control input-sm" id="input<?php echo $r["id"]; ?>" name="<?php echo $r["var"]; ?>" placeholder="<?php echo $r["var"]; ?>" value="<?php echo $r["val"]; ?>">
                    </div>
                  </div>
                </div><!-- chiude row  -->
                <?php
            }
          ?>
      </div><!-- chiude point  -->
    </form>
      <div id="feedback" class="tab-pane fade in <?php echo $feedclass; ?>">
            <form method="post" id="feedback">
            <div class="row text-info bg-info">
              ELEMENTI DEI FEEDBACKS PER GLI ALLOGGI
            </div><!-- chiude row  -->
            <?php
            if (gaz_dbi_num_rows($feedbacks) > 0) {
              ?><div style="border: 1px solid black;"><?php
              foreach ($feedbacks as $feedback) {
                ?>
                <div class="row border border-primary">
                  <div class="form-group" >
                    <label for="existElement" class="col-sm-2 control-label"><?php echo "<b>".get_string_lang($feedback["element"], $lang)."</b> "; ?></label>
                    <label for="existElement" class="col-sm-4 control-label"><pre><?php echo get_string_lang($feedback["description"], $lang); ?></pre></label>

                    <?php if (intval($feedback["facility"])>0){
                      ?>
                      <span class="col-sm-4"> - Struttura: <?php echo $feedback["facility"]," ",$feedback["descri"]; ?></span>
                      <?php
                    }else{
                      ?>
                      <span class="col-sm-4"> - Tutte le strutture</span>
                      <?php
                    }
                    ?>
                    <button type="submit" class="btn btn-success col-sm-1" name="delElement" value="<?php echo $feedback["id"]; ?>">
                      <i class="glyphicon glyphicon-minus"> Elimina</i>
                    </button>
                    <button type="submit" class="btn btn-success col-sm-1" name="updElement" value="<?php echo $feedback["id"]; ?>">
                      <i class="glyphicon glyphicon-edit"> Modifica</i>
                    </button>
                  </div>
                </div>
                <?php
              }
              ?></div><?php

            }
              if (isset($_POST['updElement']) && intval($_POST['updElement'])>0){
                ?>
                <div class="row ">
                  <div class="form-group " >
                    <div class="row">
                      <label for="inputElement" class="col-sm-5 control-label">Modifica struttura</label>
                        <div class="col-sm-7">
                        <?php
                        $gForm->selectFromDB('artico_group', 'newFacility', 'id_artico_group', $upd['facility'], false, 0, ' - ', 'descri', '', 'col-sm-7', array('value'=>0,'descri'=>''), 'tabindex="18" style="max-width: 250px;"');
                        ?>
                        </div>
                    </div>
                    <div class="row">
                      <label for="inputElement" class="col-sm-5 control-label">Modifica titolo feedback&nbsp;<i class="glyphicon glyphicon-flag" title="accetta tag lingue (<it></it>)"></i></label>
                      <div class="col-sm-7">
                        <input class="col-sm-9" type="text" name="newElement" value="<?php echo $upd['element'];?>">
                      </div>
                    </div>
                    <div class="row">
                      <label for="inputElement" class="col-sm-5 control-label">Modifica descrizione feedback&nbsp;<i class="glyphicon glyphicon-flag" title="accetta tag lingue (<it></it>)"></i></label>
                      <div class="col-sm-7">
                        <input class="col-sm-9" type="text" name="description" value="<?php echo $upd['description'];?>">
                        <button type="submit" class="btn btn-success col-sm-3" name="SaveupdElement" value="<?php echo $upd['id']; ?>">
                          <i class="glyphicon glyphicon-record"> Modifica elemento</i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div><!-- chiude row  -->
                <?php
              }else{

              ?>
              <div class="row">
                <div class="form-group" >
                  <div class="row">
                    <label for="inputElement" class="col-sm-5 control-label">Inserisci eventuale struttura</label>
                      <div class="col-sm-7">
                      <?php
                      $gForm->selectFromDB('artico_group', 'newFacility', 'id_artico_group', 0, false, 0, ' - ', 'descri', '', 'col-sm-8', array('value'=>0,'descri'=>''), 'tabindex="18" style="max-width: 250px;"',"custom_field LIKE '%vacation_rental%'");
                      ?>
                      </div>
                  </div>
                  <div class="row">
                    <label for="inputElement" class="col-sm-5 control-label">Inserisci titolo nuovo elemento feedback&nbsp;<i class="glyphicon glyphicon-flag" title="accetta tag lingue (<it></it>)"></i></label>
                    <div class="col-sm-7">
                      <input class="col-sm-9" type="text" name="newElement">
                    </div>
                  </div>
                  <div class="row">
                    <label for="inputElement" class="col-sm-5 control-label">Inserisci descrizione nuovo elemento feedback&nbsp;<i class="glyphicon glyphicon-flag" title="accetta tag lingue (<it></it>)"></i></label>
                    <div class="col-sm-7">
                      <input class="col-sm-9" type="text" name="description">
                      <button type="submit" class="btn btn-success col-sm-3" name="addElement">
                        <i class="glyphicon glyphicon-plus"> Aggiungi elemento</i>
                      </button>
                    </div>
                  </div>
                </div>
              </div><!-- chiude row  -->
              <?php
              }
            ?>
            </form>
      </div><!-- chiude feedback  -->

      <div id="ota" class="tab-pane fade in <?php echo $otaclass; ?>">
            <form method="post" id="ota">
            <div class="row text-info bg-info">
              OTA Agenzia di viaggio online
            </div><!-- chiude row  -->
            <div class="row ">
                  <div class="form-group " >
                    <div class="row">
                      <label for="inputElement" class="col-sm-5 control-label">Seleziona se l'agenzia si occupa di riscuotere per proprio conto la tassa di soggiorno turistica.</label>
                        <div class="col-sm-7">
                        <?php
                        // Query per ottenere gli agenti
                       $sql = "
                            SELECT ag.id_fornitore, CONCAT(na.ragso1, ' ', na.ragso2) AS nome_agente
                            FROM {$gTables['agenti']} AS ag
                            JOIN {$gTables['clfoco']} AS cf ON cf.codice = ag.id_fornitore
                            JOIN {$gTables['anagra']} AS na ON na.id = cf.id_anagra
                        ";
                        $result = gaz_dbi_query($sql);
                        if ($result->num_rows > 0) {

                            echo '<select name="agente" id="agente">';
                              echo '<option value="">Seleziona un agente</option>'; // Opzione vuota di default
                            // Ciclo attraverso i risultati per creare le opzioni della select
                            while ($row = $result->fetch_assoc()) {
                                // Ottieni id_fornitore e nome_agente
                                $id_fornitore = $row['id_fornitore'];
                                $nome_agente = $row['nome_agente'];

                                echo "<option value='$id_fornitore'>$nome_agente</option>";
                            }


                            echo '</select>';

                        } else {
                            echo "Nessun agente trovato.";
                        }
                        // Campo input per tourtax, inizialmente "NO"
                        ?>
                           <label for="tourtax">Tourtax: </label>
                        <select id="tourtax" name="tourtax">
                            <option value="" selected></option>
                            <option value="NO">No</option>
                            <option value="SI">Sì</option>
                        </select>
                        <!-- Pulsante di submit inizialmente nascosto -->
                        <button name="submitOTA" id="submitOtaButton" value="ota" type="submit" style="display:none;">Inserisci</button>
                        <?php

                        ?>
                        </div>
                    </div>
                  </div>
            </div>

      </form>
      <script>
    $(document).ready(function() {
    // Variabile globale per memorizzare il valore iniziale di tourtax
    var initialTourtaxValue = null;

    // Funzione che gestisce la logica per verificare se il submit deve essere visualizzato
    function checkSelections() {
        var currentTourtaxValue = $('#tourtax').val();  // Ottieni il valore attuale di tourtax

        // Se entrambi i campi sono selezionati e il valore di tourtax è diverso da quello iniziale
        if ($('#tourtax').val() !== "" && $('#agente').val() !== "" && currentTourtaxValue !== initialTourtaxValue) {
            // Se le condizioni sono soddisfatte, mostra il pulsante di submit
            $('#submitOtaButton').show();
        } else {
            // Se le condizioni non sono soddisfatte, nascondi il pulsante di submit
            $('#submitOtaButton').hide();
        }
    }

    // Evento di change per monitorare i cambiamenti nelle selezioni
    $('#tourtax, #agente').on('change', function() {
        checkSelections(); // Verifica le selezioni ogni volta che una cambia
    });

    // Quando un tab viene selezionato e il suo contenuto è visibile
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        var targetTab = $(e.target).attr('href'); // Prendi l'ID del tab attivo

        if (targetTab === '#ota') {
            // Gestisci l'evento change per l'elemento #agente nel tab OTA
            $('#agente').change(function() {
                var idFornitore = $(this).val();

                // Esegui la richiesta AJAX per ottenere il valore di tourtax
                $.ajax({
                    url: '', // La stessa pagina
                    method: 'GET',
                    data: { id_fornitore: idFornitore },
                    success: function(response) {
                        //console.log("Risposta AJAX: " + response); // Log della risposta
                        $('#tourtax').val(response); // Aggiorna il campo input "tourtax" con la risposta
                        initialTourtaxValue = response;  // Memorizza il valore iniziale di tourtax
                        checkSelections();  // Verifica se entrambe le selezioni sono complete
                    },
                    error: function(xhr, status, error) {
                        console.error("Errore AJAX: " + error); // Log dell'errore in caso di problemi con la richiesta
                    }
                });
            });
        }
    });

});

</script>


      </div><!-- chiude feedback  -->


  </div><!-- chiude tab-content  -->
 </div><!-- chiude container-fluid  -->
</div><!-- chiude panel  -->
<script>
$( "#upsave" ).click(function() {
    $( "#sbmt-form" ).submit();
});
</script>
<a href="https://programmisitiweb.lacasettabio.it/gazie/vacation-rental-il-gestionale-per-case-vacanza-residence-bb-e-agriturismi/" target="_blank" class="navbar-fixed-bottom" style="max-width:350px; left:20%; z-index:2000;"> Vacation rental è un modulo di Antonio Germani</a>
<?php
require("../../library/include/footer.php");
?>
