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
    scriva   alla   Free  Software Foundation,  Inc.,   59
    Temple Place, Suite 330, Boston, MA 02111-1307 USA Stati Uniti.
 --------------------------------------------------------------------------
*/

class Expiry {
  public $year;
  public $month;
  public $day;
  public $effect;
  public $start_day;
  public $expiry_number;
  public $periodicity;
  public $foll_month;
  public $fix_day;
  public $ctrl_day;
  public $ctrl_month;
  public $ctrl_year;
  public $expiry;

  public function CalcExpiry($amount,$date,$effect='D',$start_day=0,$expiry_number=1,$periodicity='M',$foll_month=0,$fix_day=0,$distribution=false)
  {
    /* Questa funzione serve per il calcolo delle scadenze.
       Restituisce una matrice (array) bidimensionale dove
       sull'indice 'amount' c'è l'importo e
       sull'indice 'date' c'è la data di scadenza.
       $effect può avere i valori D,G,F
       $start_day sono i giorni di decorrenza della prima scadenza
       $expiry_number è il numero di rate
       $periodicity  è la periodicità in mesi
       $foll_month è il mese da saltare (es.agosto)
       $fix_day è il giorno fisso di scadenza quando $effect vale 'G'
       $distribution si deve passare la matrice contenente le rate in gaz_XXXpagame_distribution
    */

    // definisco le variabili comuni
    $this->year=intval(substr($date,0,4));
    $this->month=intval(substr($date,5,2));
    $this->day=intval(substr($date,8,2));
    $this->effect=strtoupper(substr($effect,0,1));
    $this->start_day=intval($start_day);
    $this->expiry_number=intval($expiry_number);
    $this->periodicity=strtoupper(substr($periodicity,0,1));
    $this->foll_month=intval($foll_month);
    $this->fix_day=intval($fix_day);

    // variabili di flusso
    $this->ctrl_day=$this->day;
    $this->ctrl_month=$this->month;
    $this->ctrl_year=$this->year;

    $this->expiry=[];

    // main
    if ($distribution && is_array($distribution) && count($distribution) >=1 ) { // in caso di riparto derivante da gaz_XXXpagame_distribution
      $totpercent=0.0;
      $partial=0.00;
      $c=1;
      foreach ($distribution as $d) {
        // amount
        $this->expiry[$c]['amount'] = round($amount*$d['ratperc']/100,2);
        $partial+=$this->expiry[$c]['amount'];
        // date
        if (intval($d['from_prev']) >=1 ) { // ho passato un numero
          $this->periodicity='D';
          $this->start_day=$d['from_prev'];
          $this->expiry[$c]['date'] = $this->_Date($c);
        } elseif(strlen($d['from_prev'].'')>=1) { // ho passato un carattere di periodicity
          $this->periodicity=$d['from_prev'];
          $this->expiry[$c]['date'] = $this->_Date($c);
        } else { // non ho passato nulla userò le descrizioni data coinciderà con $date o con la precedente
          $this->expiry[$c]['date']=($c==1)?$date:$this->expiry[($c-1)]['date'];
        }
        // descri
        $this->expiry[$c]['descri'] = $d['descri'];
        $c++;
      }
      $this->expiry[($c-1)]['amount'] += round($amount-$partial,2);
    } else { // in caso di dati derivanti solo da gaz_XXXpagame
      $partial=0.00;
      for ($c=1; $c<=$this->expiry_number; $c++) {
        if ($c==$this->expiry_number && $this->expiry_number==1) { // rata unica
          $this->expiry[$c]['amount'] = number_format($amount,2,'.','');
        } elseif ($c==$this->expiry_number && $this->expiry_number>1) { // ultima rata
          $this->expiry[$c]['amount'] = number_format(($amount-$partial),2,'.','');
        } elseif ($c<$this->expiry_number && $this->expiry_number>1) { // rate intermedie e prima
          $this->expiry[$c]['amount'] = number_format(($amount/$this->expiry_number),2,'.','');
        }
        $partial+=$this->expiry[$c]['amount'];
        // chiamo la funzione per il calcolo della data
        $this->expiry[$c]['date'] = $this->_Date($c);
        // popolo comunque descri con la data
        $this->expiry[$c]['descri'] = $this->expiry[$c]['date'];
      }
    }
    // end main
    return $this->expiry;

  }

  // calcolo date
  protected function _Date($c) {
    if ($c==1) { // alla prima scadenza si devono aggiungere i giorni di decorrenza
      switch($this->effect) {
        case "D": //caso in cui la scadenza fa riferimento alla data della fattura
          $uts = new DateTime('@'.mktime(12,0,0,$this->month,$this->day+$this->start_day,$this->year));
          $this->ctrl_day = $uts->format('d');
          $this->ctrl_month = $uts->format('m');
          $this->ctrl_year = $uts->format('Y');
        break;
        case "G": //caso in cui la scadenza fa riferimento ad un giorno fisso impostato sul relativo campo
          $uts = new DateTime('@'.mktime(12,0,0,$this->month,$this->day+$this->start_day,$this->year));
          $this->ctrl_day = $uts->format('d');
          $this->ctrl_month = $uts->format('m');
          $this->ctrl_year = $uts->format('Y');
          if ($this->fix_day<$this->ctrl_day) { // salto un mese se va a cadere in un giorno precedente
              $this->ctrl_month++;
          }
          $uts = new DateTime('@'.mktime(12,0,0,$this->ctrl_month,$this->fix_day,$this->ctrl_year));
          $this->ctrl_day = $uts->format('d');
          $this->ctrl_month = $uts->format('m');
          $this->ctrl_year = $uts->format('Y');
        break;
        case "F": //caso in cui la scadenza deve far riferimento al fine mese rispetto alla data della fattura
          // prima porto il riferimento al primo finemese
          $uts = new DateTime('@'.mktime(12,0,0,$this->ctrl_month+1,0,$this->ctrl_year));
          $this->ctrl_day = $uts->format('d');
          $this->ctrl_month = $uts->format('m');
          $this->ctrl_year = $uts->format('Y');
          // poi aumento dei giorni di decorrenza (-2 per compensare febbraio)
          $uts = new DateTime('@'.mktime(12,0,0,$this->ctrl_month,$this->ctrl_day+$this->start_day-2,$this->ctrl_year));
          $this->ctrl_month = $uts->format('m');
          $this->ctrl_year = $uts->format('Y');
          // quindi riporto a fine mese
          $uts = new DateTime('@'.mktime(12,0,0,$this->ctrl_month+1,0,$this->ctrl_year));
          $this->ctrl_day = $uts->format('d');
          $this->ctrl_month = $uts->format('m');
          $this->ctrl_year = $uts->format('Y');
          if ($this->fix_day>0){   // eventualmente vado al giorno successivo
            $uts = new DateTime('@'.mktime(12,0,0,$this->ctrl_month+1,$this->fix_day,$this->ctrl_year));
            $this->ctrl_day = $uts->format('d');
            $this->ctrl_month = $uts->format('m');
            $this->ctrl_year = $uts->format('Y');
          }
        break;
      }
      if ($this->foll_month>0 && $this->foll_month==$this->ctrl_month) {
        $this->ctrl_month++;
      }
    } else {  // le scadenze successive
      switch($this->periodicity) {
        case "Q":  // quindicinale
          if ($c%2==0) {
              $n_m=0;
              $this->ctrl_day += 15;
          } else {
              $n_m=1;
              $this->ctrl_day -= 15;
          }
        break;
        case "M":$n_m= 1;break;  // mensile
        case "B":$n_m= 2;break;  // bimestrale
        case "T":$n_m= 3;break;  // trimestrale
        case "U":$n_m= 4;break;  // quadrimestrale
        case "E":$n_m= 6;break;  // semestrale
        case "A":$n_m=12;break;  // annuale
      }
      $this->ctrl_month+=$n_m;
      if ($this->effect=='F' && $this->periodicity!='Q' && $this->fix_day==0) { // quando si deve andare a fine mese
        $this->ctrl_day=0;
        $this->ctrl_month++;
      }
      $uts = new DateTime('@'.mktime(12,0,0,$this->ctrl_month,$this->ctrl_day,$this->ctrl_year));
      $this->ctrl_day = $uts->format('d');
      $this->ctrl_month = $uts->format('m');
      $this->ctrl_year = $uts->format('Y');
      if ($this->foll_month>0 && $this->foll_month==$this->ctrl_month) {
        $this->ctrl_month++;
      }
    }
    $uts = new DateTime('@'.mktime(12,0,0,$this->ctrl_month,$this->ctrl_day,$this->ctrl_year));
    $this->ctrl_day = $uts->format('d');
    $this->ctrl_month = $uts->format('m');
    $this->ctrl_year = $uts->format('Y');
    return $this->ctrl_year.'-'.$this->ctrl_month.'-'.$this->ctrl_day;
  }
}
?>
