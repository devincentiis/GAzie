<?php
function magazz_prepare_ref_doc($tipdoc,$id_rif){
    global $gTables;
    $acc=[];
    switch ($tipdoc){
        default:
        $acc['link']=($id_rif)?"admin_movmag.php?Update&id_mov=".$id_rif:'';
        break;
    }
    return $acc;
}
?>