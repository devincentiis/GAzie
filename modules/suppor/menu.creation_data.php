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
$menu_data = array('m1' => array('link' => "docume_suppor.php"), 
                        'm2' => array(  1 => array('link' => "report_assist.php", 'weight' => 1),
                                        2 => array('link' => "report_period.php", 'weight' => 2),
                                        3 => array('link' => "report_install.php", 'weight' => 3)
                         ),
                        'm3' => array('m2' => array(1 => array(
                                                                array('translate_key' => 1, 'link' => "admin_assist.php", 'weight' => 1)
                                                              ),
                                                    2 => array(
                                                                array('translate_key' => 2, 'link' => "admin_period.php", 'weight' => 2)
                                                              ),
                                                    3 => array( array('translate_key' => 3, 'link' => "admin_install.php", 'weight' => 3)
                                                              )
                                                    )
                                     )
                );
?>