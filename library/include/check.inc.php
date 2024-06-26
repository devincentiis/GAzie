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

class check_VATno_TAXcode
{

function check_VAT_reg_no($pi,$country='IT')
{
    if ( $pi == '' ) {
        return '';
    }
    switch ( $country ) {
            case 'IT':
            // -- BEGIN ITALIAN CHECK
            if ( strlen($pi) != 11 ) {
                return "La lunghezza della partita IVA non &egrave;\n" ."corretta: la partita IVA dovrebbe essere lunga\n" ."esattamente 11 caratteri.\n";
            }
            if( ! preg_match("/^[0-9]+$/", $pi) ) {
                return "La partita IVA contiene dei caratteri non ammessi:\n" ."la partita IVA dovrebbe contenere solo cifre.\n";
            }
            $s = 0;
            for ($i = 0; $i <= 9; $i += 2 ) {
                $s += ord($pi[$i]) - ord('0');
            }
            for ($i = 1; $i <= 9; $i += 2 ) {
                $c = 2*( ord($pi[$i]) - ord('0') );
                if ($c > 9 ) $c = $c - 9;
                   $s += $c;
            }
            if ( ( 10 - $s%10 )%10 != ord($pi[10]) - ord('0') ) {
               return "La partita IVA non &egrave; valida:\n" ."il codice di controllo non corrisponde.";
            }
            // -- END ITALIAN CHECK
            break;
            // -- HERE CODE FOR CHECK OTHER COUNTRY
            default:
            break;
    }
    return '';
}



function check_TAXcode($cf,$country='IT')
{
    if ($cf == '' ) {
       return '';
    }
    switch ( $country ) {
            case 'IT':
            // -- BEGIN ITALIAN CHECK
            if (strlen($cf) == 11 ) { // e' un codice fiscale di persona giuridica
               return $this->check_VAT_reg_no($cf,$country='IT');
            }
            if (strlen($cf) != 16 ) {
               return "La lunghezza del codice fiscale non &egrave;\n" ."corretta: il codice fiscale dovrebbe essere lungo\n" ."esattamente 16 caratteri.";
            }
            $cf = strtoupper($cf);
            if (!preg_match("/^[A-Z0-9]+$/", $cf) ) {
               return "Il codice fiscale contiene dei caratteri non validi:\n" ."i soli caratteri validi sono le lettere e le cifre.";
            }
            $s = 0;
            for ( $i = 1; $i <= 13; $i += 2 ) {
                $c = $cf[$i];
                if ('0' <= $c && $c <= '9' ) {
                   $s += ord($c) - ord('0');
                } else {
                   $s += ord($c) - ord('A');
                }
            }
            for ($i = 0; $i <= 14; $i += 2 ) {
                $c = $cf[$i];
                switch ( $c ) {
                       case '0': $s += 1;
                       break;
                       case '1': $s += 0;
                       break;
                       case '2': $s += 5;
                       break;
                       case '3': $s += 7;
                       break;
                       case '4': $s += 9;
                       break;
                       case '5': $s += 13;
                       break;
                       case '6': $s += 15;
                       break;
                       case '7': $s += 17;
                       break;
                       case '8': $s += 19;
                       break;
                       case '9': $s += 21;
                       break;
                       case 'A': $s += 1;
                       break;
                       case 'B': $s += 0;
                       break;
                       case 'C': $s += 5;
                       break;
                       case 'D': $s += 7;
                       break;
                       case 'E': $s += 9;
                       break;
                       case 'F': $s += 13;
                       break;
                       case 'G': $s += 15;
                       break;
                       case 'H': $s += 17;
                       break;
                       case 'I': $s += 19;
                       break;
                       case 'J': $s += 21;
                       break;
                       case 'K': $s += 2;
                       break;
                       case 'L': $s += 4;
                       break;
                       case 'M': $s += 18;
                       break;
                       case 'N': $s += 20;
                       break;
                       case 'O': $s += 11;
                       break;
                       case 'P': $s += 3;
                       break;
                       case 'Q': $s += 6;
                       break;
                       case 'R': $s += 8;
                       break;
                       case 'S': $s += 12;
                       break;
                       case 'T': $s += 14;
                       break;
                       case 'U': $s += 16;
                       break;
                       case 'V': $s += 10;
                       break;
                       case 'W': $s += 22;
                       break;
                       case 'X': $s += 25;
                       break;
                       case 'Y': $s += 24;
                       break;
                       case 'Z': $s += 23;
                       break;
                }
            }
            if (chr($s%26 + ord('A')) != $cf[15] ) {
              return "Il codice fiscale non &egrave; corretto:\n" ."il codice di controllo non corrisponde.";
            }
            // -- END ITALIAN CHECK
            break;
            // -- HERE CODE FOR CHECK OTHER COUNTRY
            default:
            break;
    }
    return '';
}

}

class postal_code
{
    function check_postal_code($v,$country='IT',$db_cp_length=0)
    {
      // se il valore della lunghezza del codice postale è stato inserito sul db lo uso per il controllo
      if ($db_cp_length>0) {
         if(!preg_match("/^[0-9]{".$db_cp_length."}$/", $v) ) {
             return "Invalid postal code";
         } else {
             return false;
         }
      // altrimenti uso quello più selettivo per i paesi sottoriportati  
      } else { 
        switch ($country ) {
               case 'IT':
               case 'DE':
               case 'FR':
               case 'ES':
               case 'FI':
               case 'GR':
               case 'MC':
               case 'SM':
               if(!preg_match("/^[0-9]{5}$/", $v) ) {
                   return "Invalid postal code";
               } else {
                   return false;
               }
               break;
               case 'GB':
               //if(!preg_match("/^[A-Z]{1}[0-9A-Z]{3,5}[A-Z]{1}$/", $v) ) {
               //if(!preg_match('/^([g][i][r][0][a][a])$|^((([a-pr-uwyz]{1}([0]|[1-9]\d?))|([a-pr-uwyz]{1}[a-hk-y]{1}([0]|[1-9]\d?))|([a-pr-uwyz]{1}[1-9][a-hjkps-uw]{1})|([a-pr-uwyz]{1}[a-hk-y]{1}[1-9][a-z]{1}))(\d[abd-hjlnp-uw-z]{2})?)$/i', $v) ) {
               //if(!preg_match("/^[A-Z]{1}[0-9A-Z]{1,2}[A-Z- ]{1}[0-9A-Z]{1,3}$/", $v) ) {
               if(!preg_match("/^([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9]?[A-Za-z])))) [0-9][A-Za-z]{2})$/", $v) ) {
                   return "Invalid postal code";
               } else {
                   return false;
               }
               break;
               case 'LU':
               if(!preg_match("/^L[U]{0,1}[0-9]{4}$/", $v) ) {
                   return "Invalid postal code";
               } else {
                   return false;
               }
               break;
               case 'AD':
               if(!preg_match("/^AD[0-9]{3}$/", $v) ) {
                   return "Invalid postal code";
               } else {
                   return false;
               }
               break;
               case 'PL':
               if(!preg_match("/^[0-9]{2}-[0-9]{3}$/", $v) ) {
                   return "Invalid postal code";
               } else {
                   return false;
               }
               break;
               case 'PT':
               if(!preg_match("/^[0-9]{4}-[0-9]{3}$/", $v) ) {
                   return "Invalid postal code";
               } else {
                   return false;
               }
               break;
               case 'SE':
               if(!preg_match("/^[0-9]{3} [0-9]{2}$/", $v) ) {
                   return "Invalid postal code";
               } else {
                   return false;
               }
               break;
               case 'AT':
               case 'BE':
               case 'CH':
               case 'DK':
               case 'GL':
               case 'HU':
               case 'NL':
               case 'NO':
               case 'SI':
               if(!preg_match("/^[0-9]{4}$/", $v) ) {
                   return "Invalid postal code";
               } else {
                   return false;
               }
               break;
               case 'FO':
               case 'IS':
               if(!preg_match("/^[0-9]{3}$/", $v) ) {
                   return "Invalid postal code";
               } else {
                   return false;
               }
               break;
               default:  // per i paesi senza check vedo solo se c'e' ma non faccio i controlli formali
                if(empty($v)) {
                   return "Postal code empty";
                } else {
                   return false;
                }
               break;
        }
      }
    }
}

class IBAN{
    function checkIBAN($v){
        $val = str_replace(' ','',$v);
        if (strlen($val) < 5 or strlen($val) > 34) {
            return false;
        }
        $b = substr($val,4).substr($val,0,4);
        $r = 0;
        for ($i = 0; $i < strlen($b); $i++ ){
            $c=ord(substr($b,$i,1));
            if ($c <= 57 and $c >= 48){ // number
                if ($i == strlen($b)-4 || $i == strlen($b)-3) { // Positions 1 and 2 cannot contain  numbers
                    return false;
                }
                $k = $c-48;
            } elseif ($c <= 90 and $c >= 65){ // char
                if ($i == strlen($b)-1 || $i == strlen($b)-2) { // Positions 1 and 2 cannot contain  letters
                    return false;
                }
                $k = $c-55;
            } else { //char not valid
                return false;
            }
            if ($k > 9) {
                        $r = (100 * $r + $k) % 97;
            } else {
                        $r = (10 * $r + $k) % 97;
            }
        }
        if ($r != 1) {
            return false;
        }
        return true;
    }
}
?>