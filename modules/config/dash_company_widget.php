<?php
function selectCompany($name, $val, $strSearch = '', $val_hiddenReq = '', $mesg='', $class = 'FacetSelect') {
    global $gTables, $admin_aziend;
    $table = $gTables['aziend'] . ' LEFT JOIN ' . $gTables['admin_module'] . ' ON ' . $gTables['admin_module'] . '.company_id = ' . $gTables['aziend'] . '.codice';
    $where = $gTables['admin_module'] . '.adminid=\'' . $admin_aziend['user_name'] . '\' GROUP BY company_id';
    if ($val > 0 && $val < 1000) { // vengo da una modifica della precedente select case quindi non serve la ricerca
        $co_rs = gaz_dbi_dyn_query("*", $table, 'company_id = ' . $val . ' AND ' . $where, "ragso1 ASC");
        $co = gaz_dbi_fetch_array($co_rs);
        changeEnterprise(intval($val));
        echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
        echo "\t<input type=\"hidden\" name=\"search[$name]\" value=\"%%\">\n";
        echo '<input type="submit" value="' . $co['ragso1'] . '" name="change" onclick="this.form.'.$name.'.value=\'0\'; this.form.hidden_req.value=\'change\';" title="'.$mesg[2].'" style="white-space:normal;">';
    } else {
        if (strlen($strSearch) >= 2) { //sto ricercando un nuovo partner
		?>
		<input type="hidden" name="prev" value="999">
		<?php
            echo "\t<select name=\"$name\" class=\"FacetSelect\" onchange=\"this.form.hidden_req.value='$name'; this.form.submit();\">\n";
            $co_rs = gaz_dbi_dyn_query("*", $table, "ragso1 LIKE '" . addslashes($strSearch) . "%' AND " . $where, "ragso1 ASC");
            if ($co_rs) {
                echo "<option value=\"0\"> ---------- </option>";
                while ($r = gaz_dbi_fetch_array($co_rs)) {
                    $selected = '';
                    if ($r['company_id'] == $val) {
                        $selected = "selected";
                    }
                    echo "\t\t <option value=\"" . $r['company_id'] . "\" $selected >" . intval($r['company_id']) . "-" . $r["ragso1"] . "</option>\n";
                }
                echo "\t </select>\n";
            } else {
                $msg = $mesg[0];
            }
        } else {
            $msg = $mesg[1];
            echo "\t<input type=\"hidden\" name=\"$name\" value=\"$val\">\n";
        }
        echo "\t<input type=\"text\" name=\"search[$name]\" value=\"" . $strSearch . "\" maxlength=\"15\"  class=\"FacetInput\">\n";
        if (isset($msg)) {
            echo "<input type=\"text\" style=\"color: red; font-weight: bold;\"  disabled value=\"$msg\">";
        }
        //echo "\t<input type=\"image\" align=\"middle\" name=\"search_str\" src=\"../../library/images/cerbut.gif\">\n";
        /** ENRICO FEDELE */
        /* Cambio l'aspetto del pulsante per renderlo bootstrap, con glyphicon */
        echo '<button type="submit" class="btn btn-default btn-sm" name="search_str"><i class="glyphicon glyphicon-search"></i></button>';
        /** ENRICO FEDELE */
    }
}

?>
<div class="panel panel-success col-md-12" >
    <div class="box-header company-color">
		<a class="pull-right dialog_grid" id_bread="<?php echo $grr['id_bread']; ?>" style="cursor:pointer;"><i class="glyphicon glyphicon-cog"></i></a>
        <b><?php echo $script_transl['company'] ?></b>
	</div>
    <div class="flip-image">
        <div class="flip-image-inner">
            <div class="flip-image-front"><a href="../config/admin_aziend.php">
            <img class="img-circle dit-picture" src="view.php?table=aziend&value=<?php echo $form['company_id']; ?>" alt="Logo" style="max-height: 150px; max-width: 100%;" border="0" title="<?php echo $script_transl['upd_company']; ?>" ></a>
            </div>
            <div class="flip-image-back"><a href="../config/admin_aziend.php"><div style="cursor:pointer;">
            <p><b><?php echo $admin_aziend['ragso1'].' '.$admin_aziend['ragso2']; ?></b></p>
            <p><?php echo $admin_aziend['indspe']; ?></p>
            <p><?php echo $admin_aziend['citspe'].' ('.$admin_aziend['prospe'].')'; ?></p>
            <p><?php echo 'P. IVA: '.$admin_aziend['pariva']; ?></p></div></a>
            </div>
        </div>
    </div>
    <div>
    <?php
	$student = false;
	if (!isset($_POST['prev'])){// primo accesso
		$form['prev']='';
	}else{
		$form['prev']=$form['company_id'];
	}

    if (isset ($_POST['company_id'])&& isset ($_POST['prev']) && $_POST['company_id']==$form['company_id'] && $form['company_id']>0 && strlen($form['prev'])>0){
      // ricarico la pagina dopo aver cambiato azienda
      ?>
		<script>
		//alert('Ricarico pagina');
		</script>
        <meta http-equiv="refresh" content="0; url=admin.php">
      <?php
    }

	if (preg_match("/([a-z0-9]{1,9})[0-9]{4}$/", $table_prefix, $tp)) {
		$rs_student = gaz_dbi_dyn_query("*", $tp[1] . '_students', "student_name = '" . trim($admin_aziend["user_name"]) . "'");
		$student = gaz_dbi_fetch_array($rs_student);
	}
	if ($company_choice==1 || $admin_aziend['Abilit'] >= 8 || $student ){
		?>

		<?php
		echo $script_transl['mesg_co'][2] . '<input class="btn btn-xs" type="submit" value="&rArr;" />  ';
		selectCompany('company_id', $form['company_id'], $form['search']['company_id'], $form['hidden_req'], $script_transl['mesg_co']);
    }else{
			echo '<input type="hidden" name="company_id" value="'.$form['company_id'].'" >	';
		}
		?>
    </div>
    <div>
        <?php echo $script_transl['logout']; ?> <input class="btn btn-xs" type="submit" value="&rArr;" /> <input name="logout" type="submit" value=" Logout ">
    </div>
</div>
<style type="text/css">

/*==============  image flip horizontal ====================*/

 /* The flip card container - set the width and height to whatever you want. We have added the border property to demonstrate that the flip itself goes out of the box on hover (remove perspective if you don't want the 3D effect */
.flip-image {
  margin: auto;
  width: 100%;
  height: 150px;
  // border: 1px solid #f1f1f1;
  // perspective: 1000px; /* Remove this if you don't want the 3D effect */
}

/* This container is needed to position the front and back side */
.flip-image-inner {
  position: relative;
  width: 100%;
  height: 100%;
  text-align: center;
  transition: transform 0.8s;
  transform-style: preserve-3d;
}

/* Do an horizontal flip when you move the mouse over the flip box container */
.flip-image:hover .flip-image-inner {
  transform: rotateY(180deg);
}

/* Position the front and back side */
.flip-image-front, .flip-image-back {
  position: absolute;
  width: 100%;
  height: 100%;
  -webkit-backface-visibility: hidden; /* Safari */
  backface-visibility: hidden;
}

/* Style the front side (fallback if image is missing) */
.flip-image-front {
 // background-color: #fff;
  color: black;
}

/* Style the back side */
.flip-image-back {
	background-color: #FCFCFC;
	color: white;
	transform: rotateY(180deg);
	border-radius: 20px;
	box-shadow: 0 0 5px #<?php echo $admin_aziend['colore']; ?>;
}
.flip-image-back>a {
  color: black;
}
</style>
