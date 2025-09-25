<?php
/*
 --------------------------------------------------------------------------
                            GAzie - Gestione Azienda
    Copyright (C) 2004-2022 - Antonio De Vincentiis Montesilvano (PE)
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
require('booking_template_lease.php');
#[AllowDynamicProperties]
class Lease extends Template{
  public $lang = 'en';
  public $lang_transl = 'english';
	function get_string_lang($string, $lang){
		$string = " ".$string;
		$ini = strpos($string,"<".$lang.">");
		if ($ini == 0) return $string;
		$ini += strlen("<".$lang.">");
		$len = strpos($string,"</".$lang.">",$ini) - $ini;
	  if (intval($len)>0){// se è stato trovato il tag lingua restituisco filtrato
		return substr($string,$ini,$len);
	  }else{// altrimenti restituisco come era
		return $string;
	  }
	}
    function setTesDoc()
    {
        $this->tesdoc = $this->docVars->tesdoc;
        $this->giorno = substr($this->tesdoc['datemi'],8,2);
        $this->mese = substr($this->tesdoc['datemi'],5,2);
        $this->anno = substr($this->tesdoc['datemi'],0,4);
        $this->docVars->gazTimeFormatter->setPattern('MMMM');
        $this->nomemese = ucwords($this->docVars->gazTimeFormatter->format(new DateTime($this->tesdoc['datemi'])));
        $this->sconto = $this->tesdoc['sconto'];
        $this->trasporto = $this->tesdoc['traspo'];
        $this->tipdoc = 'Contratto n.'.$this->tesdoc['numdoc'].'/'.$this->tesdoc['seziva'].' del '.$this->giorno.' '.$this->nomemese.' '.$this->anno;
        $this->show_artico_composit = $this->docVars->show_artico_composit;
        if (($customtes = json_decode($this->tesdoc['custom_field'],true)) && (json_last_error() == JSON_ERROR_NONE)){
          if (array_key_exists('ip', $customtes['vacation_rental'])) {// se nel customfield c'è l'IP lo prendo
            $this->ip = $customtes['vacation_rental']['ip'];
          } else {
            $this->ip = "";
          }
          if (array_key_exists('date_ip', $customtes['vacation_rental'])) {// se nel customfield c'è date_ip lo prendo
            $this->date_ip = $customtes['vacation_rental']['date_ip'];
          } else {
            $this->date_ip = "";
          }
        }
    }
    function newPage() {

        $this->AddPage();
        $this->SetFillColor(hexdec(substr($this->colore,0,2)),hexdec(substr($this->colore,2,2)),hexdec(substr($this->colore,4,2)));
        $this->SetFont('helvetica','',9);
    }

    function pageHeader()
    {
        $this->setTesDoc();
        $this->StartPageGroup();
        $this->newPage();
    }
    function body()
    {
      require("./lang." . (!empty($this->lang_transl) ? $this->lang_transl : "english") . ".php"); // se + vuoto metto english di default
      $script_transl = $strScript["lease.php"];
      $lang = $this->lang;

      // create some HTML content
      $html = "<p><b>".$script_transl['parti']."</b><br>-<b>".$script_transl['locatore']."</b> ".$this->intesta1." ".$this->intesta2." ".$this->intesta3."<br>-"
      .$script_transl['e']."<b>".$script_transl['conduttore']."</b>"." ".$this->cliente1." ".$this->cliente2." ".$this->cliente3." ".$this->cliente4." ".$this->cliente4b." ".$this->cliente5." "."<br>".$script_transl['body1']."</p>
      <p>1- <b>".$script_transl['oggetto']."</b><br>".$script_transl['body2']."</p>";
      $html .= "<ul>";
      foreach ($lines as $rigo){
        //echo "<br><pre>",print_r($rigo);
        if (isset ($rigo['custom_field']) && ($custom = json_decode($rigo['custom_field'],true)) && (json_last_error() == JSON_ERROR_NONE)){
          if (array_key_exists('accommodation_type', $custom['vacation_rental'])) {// è un alloggio
              switch ($custom['vacation_rental']['accommodation_type']) {//3 => 'Appartamento', 4 => 'Casa indipendente', 5=> 'Bed & breakfast'
                case "3":
                  $accomodation_type=$script_transl['apartment'];
                  break;
                case "4":
                  $accomodation_type=$script_transl['house'];
                  break;
                case "5":
                  $accomodation_type=$script_transl['bandb'];
                  break;
              }

              if (intval ($rigo['id_artico_group'])>0){// se l'alloggio fa parte di una struttura
                if ($data = json_decode($rigo['group_custom_field'], TRUE)) { // se esiste un json nel custom field della struttura
                  if (is_array($data['vacation_rental']) && isset($data['vacation_rental']['facility_type'])){// se è una struttura prendo i dati che mi serviranno
                    $minor = (isset($data['vacation_rental']['minor']))?$data['vacation_rental']['minor']:'12';// se non c'è l'età dei minori la imposto a 12 anni d'ufficio
                    $checkin = (isset($data['vacation_rental']['check_in']))?$data['vacation_rental']['check_in']:'15 - 19';// se non c'è un tempo per check-in lo imposto d'ufficio
                    $checkout = (isset($data['vacation_rental']['check_out']))?$data['vacation_rental']['check_out']:'8 - 10';// se non c'è un tempo per check-out lo imposto d'ufficio

                  }
                }
              } else{ // se non fa parte di una struttura imposto d'ufficio i dati mancanti
                $minor='12';
                $checkin='15 - 19';
                $checkout='8 - 10';
              }
              $rigo['web_url']=get_string_lang($rigo['web_url'], $lang);
              $html .= "<li>".$accomodation_type." called "." ".$rigo['codart'].', '.get_string_lang($rigo['desart'], $lang).", ".$rigo['annota'];
              if (strlen($rigo['web_url'])>5){
                $html .= "<br>".$script_transl['body3'].": ".$rigo['web_url'].". ".$script_transl['body4'];
              }
              $html .= "</li>";
              $adult=$rigo['adult'];
              $child=$rigo['child'];
              $start=$rigo['start'];
              $end=$rigo['end'];
              if ($this->docVars->tesdoc['security_deposit']==-1){
                $secdep = $custom['vacation_rental']['security_deposit'];
              }else{
                $secdep = $this->docVars->tesdoc['security_deposit'];
              }          }
          if (array_key_exists('extra', $custom['vacation_rental'])) { // è un extra
              $html .= "<li>".intval($rigo['quanti'])."Extra ".get_string_lang($rigo['desart'], $lang)." ".$rigo['annota'];
              if (strlen($rigo['web_url'])>5){
                $html .= "<br>   ".$script_transl['body3'].": ".$rigo['web_url'].".   ".$script_transl['body4'];
              }
              $html .= "</li>";
          }
        } elseif($rigo['codart']=="TASSA-TURISTICA"){ // è la tassa turistica
          $html .= "<li>".$rigo['descri']."</li>";
        }
      }

      $diff=date_diff(date_create($start),date_create($end));
      $nights = $diff->format("%a");

      $this->docVars->setTotal($this->tesdoc['traspo']);
      $totimpfat = $this->docVars->totimpfat;
      $totivafat = $this->docVars->totivafat;
      $impbol = $this->docVars->impbol;
      $taxstamp=$this->docVars->taxstamp;
      $totamount = $totimpfat + $totivafat + $impbol + $taxstamp;
      $locale = 'it_IT';// creo l'importo in lettere
      $fmt = numfmt_create($locale, NumberFormatter::SPELLOUT);
      $in_words = numfmt_format($fmt, $totamount);

      $html .= "</ul>";

      $html .= "<dl>";

      $html .= "<dt>2- <b>".$script_transl['durata']."</b></dt>" ;
      $html .= "<dd>- ".$script_transl['durata1'].$nights."</dd><dd>- ".$script_transl['durata2']." ".date("Y-m-d", strtotime($start))." ".$script_transl['durata2bis']." ".get_string_lang($checkin, $lang)."</dd>
                <dd>- ".$script_transl['durata3']." ".date("Y-m-d", strtotime($end))." ".$script_transl['durata2bis']." ".get_string_lang($checkout, $lang).". ".$script_transl['durata4']."</dd>
                <dd>- ".$script_transl['durata5']."</dd>";

      $html .= "<dt>3- <b>".$script_transl['canone']."</b></dt>" ;
      $html .= "<dd>- ".$script_transl['body5'].(intval($adult)+intval($child)).$script_transl['body6'].$adult.$script_transl['body7'].$child.$script_transl['body8'].$minor."</dd>";

      $html .= "<dd>- ".$script_transl['canone1']." € ".number_format(($totamount),2,",",".")." (".$in_words.") ".$script_transl['canone2']."</dd>";
      if ($secdep>1){// se è previsto un deposito cauzionale lo scrivo
        $html .= "<dd>- ".$script_transl['canone3']." € ".number_format(($secdep),2,",",".")." ".$script_transl['canone4']."</dd>";
      }

      $html .= "<dt>4- <b>".$script_transl['divieti']."</b></dt>";
      $html .= "<dd>- ".$script_transl['divieto1']."</dd>"."<dd>- ".$script_transl['divieto2']."</dd>"."<dd>- ".$script_transl['divieto3']."</dd>"."<dd>- ".$script_transl['divieto5']."</dd>"."<dd>- ".$script_transl['divieto7']."</dd>"."<dd>- ".$script_transl['divieto4']."</dd>";

      $html .= "<dt>5- <b>".$script_transl['recesso']."</b></dt>" ;
      $html .= "<dd>- ".$script_transl['recesso1']."</dd>"."<dd>- ".$script_transl['recesso2']."</dd>"."<dd>- ".$script_transl['recesso3']."</dd>"."<dd>- ".$script_transl['recesso4']."</dd>"."<dd>- ".$script_transl['recesso5']."</dd>"."<dd>- ".$script_transl['recesso6']."</dd>"."<dd>- ".$script_transl['recesso7']."</dd>"."<dd>- ".$script_transl['recesso8']."</dd>";

      $html .= "<dt>6- <b>".$script_transl['rinvio']."</b></dt>" ;
      $html .= "<dd>- ".$script_transl['rinvio1']."</dd>";

      $html .= "<dt>7- <b>".$script_transl['accettazione']."</b></dt>" ;
      if (strlen($this->ip)>6){// se firmato on line lo preciso
        $html .= "<dd>- ".$script_transl['accettazione1']."</dd>";
      }
      $html .= "<dd>- ".$script_transl['accettazione2']."</dd>";

      $html .= "<dl>";
      if (strlen($this->ip)>7){// firme digitali
        $html .= "<br><p><b>".$script_transl['sign-online']."</b></p>";
        $html .= "<br><p><span>Il conduttore <b>".$this->cliente1." ".$this->cliente2."</b> - firma registrata con IP:".$this->ip;
        if (strlen($this->date_ip)>7){
          $html .= " ".$this->date_ip;
        }
        $html .= "<br><p><b>".$script_transl['sign-clauses']."</b></p>";
        $html .= "<br><p><span>Il conduttore <b>".$this->cliente1." ".$this->cliente2."</b> - firma registrata con IP:".$this->ip;
        if (strlen($this->date_ip)>7){
          $html .= " ".$this->date_ip;
        }
        $html .="</span></p>";
        $html .= "<p>Il locatore <b>".$this->intesta1."</b><br><br><br><br><br><br><br><br><br><br><br><br><br></p>";

      }else{// firme fisiche
        $html .= "<br><p><b>Letto, Firmato e Sottoscritto </b></p><span>Il locatore ".$this->intesta1."</span><span style=\" letter-spacing: 30px;\">&nbsp; &nbsp;</span><span> Il conduttore ".$this->cliente1." ".$this->cliente2."</span><br>";
        $html .= "<br><p><b>Ai sensi e per gli effetti degli articoli 1341 e 1342 del Codice Civile, il conduttore dichiara di aver preso visione e di approvare specificamente le seguenti clausole: 1,2,3,4,5,6,7.</b></p><span> Il conduttore ".$this->cliente1." ".$this->cliente2."</span><br><br><br><br><br><br><br><br><br><br><br><br><br>";
      }
	  $html .= "<br><br><br><br><br><br><br><br><br><b>CHECK-IN</b> The tenant declares to have checked the apartment, to have found it in a good state of maintenance and cleanliness with all the agreed facilities and extras and to receive the keys. <br><span style=\" letter-spacing: 270px;\">&nbsp; &nbsp;</span>___________________________________________________";
	 $html .= "<br><br><br><br><b>CHECK-IN</b> The landlord declares to receive the security deposit referred to in the point 3. ________________________________________";
	 $html .= "<br><br><br><br><b>CHECK-OUT</b> The tenant declares that the security deposit referred to in point 3 has been returned to him due to the termination of the lease. <br><span style=\" letter-spacing: 270px;\">&nbsp; &nbsp;</span>___________________________________________________";

      // output the HTML content
      $this->writeHTML($html, true, false, true, false, '');

    }


    function compose()
    {
        $this->body();
    }

    function pageFooter()
    {


    }

    function Footer()
    {
        //Page footer

    }
}

?>
