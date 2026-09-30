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
$menu_data=
['m1'=>['link'=>"docume_stats.php"],
 'm2'=>[1=>['link'=>"stats_vendit.php",'weight'=>5],
				2=>['link'=>"stats_acquis.php",'weight'=>10],
				3=>['link'=>"stats_magazz.php",'weight'=>15]
       ],
 'm3'=>['m2'=>[1=>[['translate_key'=>1,'link'=>"select_analisi_acquisti_clienti.php",'weight'=>5],
                   ['translate_key'=>2,'link'=>"select_analisi_agenti.php",'weight'=>10],
                   ['translate_key'=>3,'link'=>"select_analisi_fatturato_clienti.php",'weight'=>15],
                   ['translate_key'=>9,'link'=>"select_fatturato_pagamenti.php",'weight'=>17],
                   ['translate_key'=>4,'link'=>"select_analisi_fatturato_cliente_fornitore.php",'weight'=>20],
                   ['translate_key'=>8,'link'=>"report_statis.php",'weight'=>25]
									],
               2=>[['translate_key'=>5,'link'=>"select_analisi_avanzamento_per_fornitore.php",'weight'=>5],
									 ['translate_key'=>6,'link'=>"esportazione_articoli_venduti_per_fornitore.php",'weight'=>10]
									],
							 3=>[['translate_key'=>7,'link'=>"stats_magazz",'weight'=>5]
									]

              ]
			 ]
];
?>
