/*
 --------------------------------------------------------------------------
                            GAzie - Gestione Azienda
    Copyright (C) 2004-present - Antonio De Vincentiis Montesilvano (PE)
         (https://www.devincentiis.it)
           <https://gazie.sourceforge.net>
  Questo file: Copyright (C) 2026 Domenico Seccia - Assistenzacassa (https://assistenzacassa.it)
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
/*
  Tasti rapidi da qualsiasi pagina: F5 nuovo DDT, F6 nuova fattura,
  F7 nuovo ordine cliente. Caricato solo con inserimento_rapido = 1.
  Indirizzi relativi: tutte le pagine stanno in modules/<modulo>/.
  Con Ctrl, Alt, Shift o Meta il tasto resta al browser (es. Ctrl+F5).
*/
document.addEventListener("keydown", function (e) {
  if (e.ctrlKey || e.altKey || e.shiftKey || e.metaKey) {
    return;
  }
  var pagine = {
    116: "../vendit/admin_docven.php?Insert&tipdoc=DDT", // F5
    117: "../vendit/admin_docven.php?Insert&tipdoc=FAI", // F6
    118: "../vendit/admin_broven.php?Insert&tipdoc=VOR"  // F7
  };
  var tasto = e.keyCode || e.which;
  if (pagine[tasto]) {
    e.preventDefault();
    window.location.href = pagine[tasto];
  }
});
