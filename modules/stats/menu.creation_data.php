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
$menu_data = array( 'm1'=>array('link'=>"docume_stats.php"),
                    'm2'=>array(1=>array('link'=>"stats_vendit.php",'weight'=>5),
								2=>array('link'=>"stats_acquis.php",'weight'=>10),
								3=>array('link'=>"stats_magazz.php",'weight'=>15)
                               ),
                    'm3'=>array('m2'=>array(1=>array(
                                                    array('translate_key'=>1,'link'=>"select_analisi_acquisti_clienti.php",'weight'=>5),
                                                    array('translate_key'=>2,'link'=>"select_analisi_agenti.php",'weight'=>10),
                                                    array('translate_key'=>3,'link'=>"select_analisi_fatturato_clienti.php",'weight'=>15),
                                                    array('translate_key'=>4,'link'=>"select_analisi_fatturato_cliente_fornitore.php",'weight'=>20),
                                                    array('translate_key'=>8,'link'=>"report_statis.php",'weight'=>25)
													),
											2=>array(
                                                    array('translate_key'=>5,'link'=>"select_analisi_avanzamento_per_fornitore.php",'weight'=>5),
													array('translate_key'=>6,'link'=>"esportazione_articoli_venduti_per_fornitore.php",'weight'=>10)
													),
											3=>array(
                                                    array('translate_key'=>7,'link'=>"stats_magazz",'weight'=>5)
													)
																						
											)	
								)
);
?>