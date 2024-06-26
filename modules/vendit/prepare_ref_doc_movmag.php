<?php
function vendit_prepare_ref_doc($tipdoc,$id_rif){
    global $gTables;
    $acc=[];
    $result = gaz_dbi_dyn_query("*", $gTables['rigdoc']." LEFT JOIN ".$gTables['tesdoc']." ON ".$gTables['rigdoc'].".id_tes = ".$gTables['tesdoc'].".id_tes", 'id_rig='.$id_rif, ' id_rig', 0, 1);
    $r = gaz_dbi_fetch_array($result);
    switch ($tipdoc){
        case 'VCO' :
        $acc['link']=($r)?"../vendit/admin_scontr.php?Update&id_tes=".$r['id_tes']:'';
        break;
        default:
        $acc['link']=($r)?"../vendit/admin_docven.php?Update&id_tes=".$r['id_tes']:'';
        break;
    }
    return $acc;
}
?>
