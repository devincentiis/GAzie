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
global $gTables;

function submenu($menu_data) {
    if (!is_array($menu_data)) {
        return;
    }
    $numsub = 0;
    $submenu = '';
    foreach ($menu_data as $i => $mnu) {
        if (!is_array($mnu)) {
            continue;
        }
        $submnu = '';
        if ($numsub === 0) {
            echo "\n\t\t\t\t\t\t\t" . '<ul class="dropdown-menu">' . "\n";
        }
        if (preg_match("/^[A-Za-z0-9!@#$%&()*;:_.'\/\\\\ ]+\.png$/", $mnu['icon'])) {
            $submnu = '<img src="' . $mnu['icon'] . '" width="32"/> ';
        }
        $submnu = '<a href="' . $mnu['link'] . '">' . $submnu . stripslashes($mnu['name']);
        if (count($mnu) > 5) { //	Esiste un sotto menu
            echo "\t\t\t\t\t\t\t" . '<li>' . $submnu . "<span class=\"caret\"></span></a>";
            submenu($mnu);
            echo "\t\t\t\t\t\t\t</li>\n";
        } else {
            echo "\t\t\t\t\t\t\t<li>" . $submnu . "</a></li>\n";
        }
        $numsub++;
        if ($numsub == 0) {
            echo "\t\t\t\t\t\t\t</ul>\n";
        }
    }
    if ($numsub > 0) {
        echo "\t\t\t\t\t\t\t</ul>\n";
    }
}
?>
<script>
$(function() {
	$("#dialog_menu_alerts").dialog({ autoOpen: false });
});
function menu_alerts_check(mod,title,button,label,link,style){
	// questa funzione attiva l'alert sulla barra del menù e viene richiamata sia dalla funzione menu_check_from_modules() dal browser tramite setInterval che alla fine della pagina (lato server) quando il controllo fatto dal php tramite $_SESSION['menu_alerts_lastcheck'] è scaduto
    // faccio append solo se già non esiste
	style = style || 0;
    if (style && style.length >= 2) { // solo se style è valorizzato faccio l'alert sul menu
        $("div.blink").html( '<a mod="'+mod+'" class="btn btn-'+style+' dialog_menu_alerts" title="'+title.replace(/(<([^>]+)>)/ig,"")+'" >'+button+'</a>').click(function() {
			$("p#diatitle").html(title);
			$( "#dialog_menu_alerts" ).dialog({
                title: button ,
				minHeight: 200,
				width: "auto",
				modal: "true",
				show: "blind",
				hide: "explode",
				buttons: {
					'confirm':{
						text: label,
						'class':'btn btn-confirm',
						click:function (event, ui) {
						$.ajax({
							data: {'mod':mod },
							type: 'POST',
							url: '../root/delete_menu_alert.php',
							success: function(data){
								window.location.href=link;
							}
						});
					}},
					delete:{
						text:'Posponi',
						'class':'btn btn-danger',
						click:function (event, ui) {
						$.ajax({
							data: {'mod':mod },
							type: 'POST',
							url: '../root/delete_menu_alert.php',
							success: function(data){
								window.location.reload(true);
							}
						});
					}},
					"Lascia": function() {
						$(this).dialog('destroy');
					}
				}
			});
			$("#dialog_menu_alerts" ).dialog( "open" );
		});
    }
}

function menu_check_from_modules() {
    // chiamata al server per aggiornare il tempo dell'ultimo controllo
	$.ajax({
		type: 'GET',
		url: "../root/session_menu_alert_lastcheck.php",
		success: function(){
		  var j=0;
          // nome modulo
          var title = '';
          var button = '';
          var label = '';
          var style = '';
          var link = '';
          var mod = '';
          // controllo la presenza di nuove notifiche
          $.ajax({
            type: 'GET',
            url: '../root/get_sync_status_ajax.php',
            data: {},
            dataType: 'json',
            success: function (data) {
			  if (data) {
				$.each(data, function(i, v) {
				  // nome modulo
				  title = v['title'];
				  button = v['button'];
				  label = v['label'];
				  link = v['link'];
				  style = v['style'];
				  mod = i;
				  //console.log(mod);
				  j++;
				  menu_alerts_check(mod,title,button,label,link,style);
				});
              }
            }
          });
        }
	});
}
function opendoc(module) {
	$.ajax({
		type: "POST",
		url: module,
		data: 'mode=modal', // da lasciare perché alcuni moduli usano mode
		success: function (msg) {
			$("#doc_modal .modal-sm").css('width', '80%');
			$("#doc_modal .modal-body").html(msg);
		},
		error: function () {
			alert("Errore apertura documentazione");
		}
	});
};

</script>
<!-- Navbar static top per menu multilivello responsive -->
		<div style="display:none" id="dialog_menu_alerts" title="">
			<p class="ui-state-highlight" id="diatitle"></p>
		</div>
		<div id="doc_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header active">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="myModalLabel"><?php echo "Documentazione"; ?></h4>
					</div>
					<div class="modal-body edit-content small"></div>
				</div>
			</div>
		</div>
<div class="navbar navbar-default" role="navigation">
    <div id="l-wrapper" class="navbar-header company-color" style="padding-top: 8px; padding-bottom: 8px;">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a href="../../modules/root/admin.php" class="company-color" > <?php echo strtoupper($admin_aziend["ragso1"]); ?>
        </a>
    </div>

	<div class="blink" align="center" style="position:absolute; right:3px; padding:2px;"></div>


    <div class="collapse navbar-collapse">
        <ul class="nav navbar-nav">
            <?php
            // stampo la prima voce della barra del menù con il dropdown dei moduli
            $i = 0;
            foreach ($menuArray as $menu_modules_val) {
                if ($i == 0) { // sul modulo attivo non permetto i submenu in quanto verrano messi sulla barra orizzontale
                    echo "\t\t\t\t<li>" . '<a class="dropdown-toggle" data-toggle="dropdown"><img src="' . $menu_modules_val["icon"] . '" width="32"/>&nbsp;' . $menu_modules_val['name'] . '<span class="caret"></span></a>';
                    echo "\n\t\t\t\t\t" . '<ul class="dropdown-menu">' . "\n";
                } else {
                    echo "\t\t\t\t\t";
                    echo '<li><a id="docmodal" style="cursor:pointer;" onclick="opendoc(\''.$menu_modules_val['link'].'\')" data-toggle="modal" data-target="#doc_modal" title="Documentazione" ><img src="' . $menu_modules_val["icon"] . '" width="32"/>&nbsp;' . $menu_modules_val['name'] . "<span class=\"caret\"></span></a>\n";
                    submenu($menu_modules_val);
                    echo "\t\t\t\t\t</li>\n";
                }
                $i++;
            }
            // fine stampa prima voce menu
            ?>
        </ul>
        </li>
        <?php
        $i = 0;
        foreach ($menuArray[0] as $menu) {
            // stampo nella barra del menù il dropdown del modulo
            $icon_lnk = '';
            if (isset($menu['icon']) && preg_match("/^[A-Za-z0-9!@#$%&()*;:_.'\/\\\\ ]+\.png$/", $menu['icon'])) {
                $icon_lnk = '<img src="' . $menu['icon'] . '" width="32" />';
            }
            if ($i > 4) { // perché ci sono 5 indici prima dei dati veri e propri
                if (count($menu) > 5) { // Esiste un sotto menu
                    echo "\t\t\t" . '<li class="dropdown">'
                    . '<a href="' . $menu['link'] . '">' . $icon_lnk . ' ' . $menu['name'] . '<span class="caret"></span></a>';
                } else {
                    echo "\t\t\t" . '<li><a class="dropdown" href="' . $menu['link'] . '">' . $icon_lnk . '' . $menu['name'] . '</a>';
                }
                submenu($menu);
                echo "\t\t\t\t\t</li>\n";
                $livello3 = $menu;
            }
            $i++;
        }
        ?>
        <li id="user-position">
			<div>
        <a href="../config/admin_utente.php?user_name=<?php echo $admin_aziend["user_name"] ?>&Update"><img src="../root/view.php?table=admin&field=user_name&value=<?php echo $admin_aziend["user_name"] ?>" height="30" title="<?php echo $admin_aziend['user_lastname'] . ' ' . $admin_aziend['user_firstname']; ?>" ></a>
			</div>
        </li>

        </ul>
    </div>

</div><!-- chiude navbar -->
