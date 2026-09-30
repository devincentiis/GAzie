<div class="panel panel-info col-md-12" >
    <div class="box-header company-color text-bold">Ultime operazioni
		<a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
	</div>
    <div class="img-containter">
    <!-- per adesso lo faccio collassare in caso di small device anche se si potrebbe fare uno switch in verticale -->
    <?php
    $res_last = gaz_dbi_dyn_query("*", $gTables['menu_usage'], ' company_id="' . $form['company_id'] . '" AND adminid="' . $admin_aziend['user_name'] . '" ', ' last_use DESC, click DESC', 0, 8);
    if (gaz_dbi_num_rows($res_last) > 0) {
        while ($r = gaz_dbi_fetch_array($res_last)) {
            $rref = explode('-', $r['transl_ref']);
            switch ($rref[1]) {
                case 'm1':
                    require '../' . $rref[0] . '/menu.' . $admin_aziend['lang'] . '.php';
                    $rref_name = $transl[$rref[0]]['title'];
                    break;
                case 'm2':
                    require '../' . $rref[0] . '/menu.' . $admin_aziend['lang'] . '.php';
                    $rref_name = $transl[$rref[0]]['m2'][$rref[2]][0];
                    break;
                case 'm3':
                    require '../' . $rref[0] . '/menu.' . $admin_aziend['lang'] . '.php';
                    $rref_name = $transl[$rref[0]]['m3'][$rref[2]][0];
                    break;
                case 'sc':
                    require '../' . $rref[0] . '/lang.' . $admin_aziend['lang'] . '.php';
                    $rref_name = $strScript[$rref[2]][$rref[3]];
                    break;
                default:
                    $rref_name = 'Nome script non trovato';
                    break;
            }
            ?>
            <div>
                    <a href="<?php
                    if ($r["link"] != "")
                        echo '../../modules' . $r["link"];
                    else
                        echo "&nbsp;";
                    ?>" type="button" class="btn btn-default btn-full" style="font-size: 85%; text-align: left;">
                        <span ><?php
                            echo '<b>';
                            if (is_string($rref_name)) {
                                echo $rref_name;
                            } else {
                                echo "Errore nello script (array)";
                            }
                            echo '</b> ('. gaz_time_from(strtotime($r["last_use"])) .')';
                            ?></span></a>
            </div>
            <?php
        }
    }
    ?>
	</div>
</div>
